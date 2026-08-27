<?php

namespace App\Exports;

use App\Models\MonitoringMIPHeader;
use App\Models\RekapData;
use App\Models\SubAssy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonitoringMIPExport implements FromArray, WithStyles, WithEvents
{
    protected $bulan, $tahun, $customer;

    public function __construct($bulan, $tahun, $customer = null)
    {
        $this->bulan = (int) $bulan;
        $this->tahun = (int) $tahun;
        $this->customer = $customer;
    }

    private function normalize($str)
    {
        $str = $str ?? '';
        return strtoupper(str_replace([' ', '-', '_'], '', $str));
    }

    public function array(): array
    {
        $bulan = $this->bulan;
        $tahun = $this->tahun;
        $customer = $this->customer;
        $daysInMonth = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $data = [];

        // Row 1: Main Header
        $data[] = [
            'NO', 'CUSTOMER', 'PROJECT', 'PART NUMBER', 'PART NAME',
            'TOTAL PO', 'STOCK AWAL',
            'TOTAL', null,
            'LEVEL STOCK', null, null,
            'STATUS',
            'TANGGAL', ...array_fill(0, $daysInMonth - 1, null)
        ];

        // Row 2: Sub Header
        $dayNumbers = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayNumbers[] = (string) $i;
        }

        $data[] = [
            null, null, null, null, null,
            null, null,
            'IN', 'OUT',
            'MIN', 'SAFETY', 'MAX',
            null,
            ...$dayNumbers
        ];

        // 1. Rekap Data & Aggregation
        $rekapListRaw = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer, function ($q) use ($customer) {
                if (is_array($customer)) {
                    $q->whereIn('customer', $customer);
                } else {
                    $q->where('customer', $customer);
                }
            })
            ->orderBy('customer')
            ->orderBy('kode_project')
            ->orderBy('part_number')
            ->get();

        $aggregatedRekap = [];
        foreach ($rekapListRaw as $item) {
            $key = $this->normalize($item->customer) . '|' . $this->normalize($item->kode_project) . '|' . $this->normalize($item->part_number) . '|' . $this->normalize($item->models);
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $item;
            } else {
                $aggregatedRekap[$key]->total_qty_bulan_ini += $item->total_qty_bulan_ini;
                $aggregatedRekap[$key]->stock_awal_mip = max($aggregatedRekap[$key]->stock_awal_mip, $item->stock_awal_mip);
            }
        }

        // 2. Level Stok
        $levelStokMap = DB::table('level_stok_detail as d')
            ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
            ->where('l.bulan', $bulan)
            ->where('l.tahun', $tahun)
            ->get()
            ->keyBy(fn($l) => $this->normalize($l->customer) . '|' . $this->normalize($l->kode_projek) . '|' . $this->normalize($l->part_number));

        // 3. MIP Headers & Details
        $mipHeaders = MonitoringMIPHeader::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

        // 4. SubAssy Produksi (IN)
        $subAssyList = SubAssy::with(['details' => function ($q) {
                $q->where('tipe', 'Produksi');
            }])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($s) => $this->normalize($s->customer) . '|' . $this->normalize($s->project) . '|' . $this->normalize($s->part_number));

        $no = 1;
        foreach ($aggregatedRekap as $item) {
            $matchKey = $this->normalize($item->customer) . '|' . $this->normalize($item->kode_project) . '|' . $this->normalize($item->part_number);

            $level = $levelStokMap[$matchKey] ?? null;
            $header = $mipHeaders[$matchKey] ?? null;
            $details = $header ? $header->details->keyBy('tanggal') : collect();

            $subAssy = $subAssyList[$matchKey] ?? null;
            $inList = [];
            if ($subAssy && $subAssy->details) {
                foreach ($subAssy->details as $detail) {
                    $hari = (int) $detail->tanggal;
                    if ($hari >= 1 && $hari <= $daysInMonth) {
                        $inList[$hari] = (int) ($detail->jumlah ?? 0);
                    }
                }
            }

            $stockAwal = (int) ($header->stock_awal ?? ($item->stock_awal_mip ?? 0));
            $totalIn = 0;
            $totalOut = 0;

            $inRow = [];
            $outRow = [];
            $balRow = [];

            $balance = $stockAwal;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $in = (int) ($inList[$i] ?? 0);
                $out = (int) ($details[$i]->out_qty ?? 0);

                $balance = $balance + $in - $out;

                $inRow[] = $in;
                $outRow[] = $out;
                $balRow[] = $balance;

                $totalIn += $in;
                $totalOut += $out;
            }

            $levelMin = (int) ($level->min ?? ($header->level_min ?? 0));
            $levelSafety = (int) ($level->safety_mip ?? ($header->level_safety ?? 0));
            $levelMax = (int) ($level->max ?? ($header->level_max ?? 0));

            // Row IN
            $data[] = [
                $no++,
                $item->customer,
                $item->kode_project ?? '',
                $item->part_number,
                $item->models ?? '',
                (int) ($item->total_qty_bulan_ini ?? 0),
                $stockAwal,
                $totalIn,
                $totalOut,
                $levelMin,
                $levelSafety,
                $levelMax,
                'IN',
                ...$inRow
            ];

            // Row OUT
            $data[] = [
                '', '', '', '', '',
                '', '',
                '', '',
                '', '', '',
                'OUT',
                ...$outRow
            ];

            // Row BAL
            $data[] = [
                '', '', '', '', '',
                '', '',
                '', '',
                '', '', '',
                'BAL',
                ...$balRow
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $daysInMonth = Carbon::createFromDate($this->tahun, $this->bulan, 1)->daysInMonth;

                // Show explicit zeros
                $sheet->getSheetView()->setShowZeros(true);

                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Merge headers A1:A2 s/d G1:G2
                foreach (range('A', 'G') as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // Merge TOTAL H1:I1
                $sheet->mergeCells("H1:I1");

                // Merge LEVEL STOCK J1:L1
                $sheet->mergeCells('J1:L1');

                // Merge STATUS M1:M2
                $sheet->mergeCells("M1:M2");

                // Merge TANGGAL N1:...
                $sheet->mergeCells("N1:{$highestCol}1");

                // Merge kolom data identitas per 3 baris
                for ($row = 3; $row <= $highestRow; $row += 3) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                    $sheet->getRowDimension($row + 1)->setRowHeight(18);
                    $sheet->getRowDimension($row + 2)->setRowHeight(18);

                    foreach (range('A', 'L') as $col) {
                        $sheet->mergeCells("{$col}{$row}:{$col}".($row + 2));
                    }
                }

                // Column Widths
                $sheet->getColumnDimension('A')->setWidth(6);    // No
                $sheet->getColumnDimension('B')->setWidth(16);   // Customer
                $sheet->getColumnDimension('C')->setWidth(14);   // Project
                $sheet->getColumnDimension('D')->setWidth(20);   // Part Number
                $sheet->getColumnDimension('E')->setWidth(38);   // Part Name
                $sheet->getColumnDimension('F')->setWidth(12);   // Total PO
                $sheet->getColumnDimension('G')->setWidth(12);   // Stock Awal
                $sheet->getColumnDimension('H')->setWidth(10);   // Total IN
                $sheet->getColumnDimension('I')->setWidth(10);   // Total OUT
                $sheet->getColumnDimension('J')->setWidth(9);    // Min
                $sheet->getColumnDimension('K')->setWidth(9);    // Safety
                $sheet->getColumnDimension('L')->setWidth(9);    // Max
                $sheet->getColumnDimension('M')->setWidth(8);    // Status

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $colIdx = 13 + $d; // N = 14
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $sheet->getColumnDimension($colLetter)->setWidth(6);
                }

                // Global Borders & Alignment
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Left align for Part Name
                $sheet->getStyle("E3:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Number formatting for numeric columns
                $sheet->getStyle("F3:L{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("N3:{$highestCol}{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');

                // Row badge colors
                for ($row = 3; $row <= $highestRow; $row++) {
                    $status = strtoupper(trim((string) $sheet->getCell("M{$row}")->getValue()));

                    if ($status === 'IN') {
                        $sheet->getStyle("M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
                        $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB('166534');
                        $sheet->getStyle("M{$row}")->getFont()->setBold(true);
                    } elseif ($status === 'OUT') {
                        $sheet->getStyle("M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                        $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB('991B1B');
                        $sheet->getStyle("M{$row}")->getFont()->setBold(true);
                    } elseif ($status === 'BAL' || $status === 'BALANCE') {
                        $sheet->getStyle("M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
                        $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB('1E40AF');
                        $sheet->getStyle("M{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("A".($row - 2).":{$highestCol}{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('94A3B8');
                    }
                }

                $sheet->freezePane('N3');
            },
        ];
    }
}
