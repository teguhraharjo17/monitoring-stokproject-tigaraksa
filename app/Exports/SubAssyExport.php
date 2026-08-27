<?php

namespace App\Exports;

use App\Http\Controllers\MonitoringSubAssy\MonitoringSubAssyController;
use App\Models\RekapData;
use App\Models\SubAssy;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SubAssyExport implements FromArray, WithStyles, WithTitle, WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $customer;

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

        // Header 1
        $data[] = [
            'NO', 'CUSTOMER', 'PROJECT', 'PART NUMBER', 'PART NAME',
            'TOTAL PO', 'WIP SEBELUMNYA', 'TOTAL SPK', 'TOTAL PRODUKSI', 'WIP AKHIR', 'PRODUKTIVITAS',
            'STATUS',
            'TANGGAL', ...array_fill(0, $daysInMonth - 1, null)
        ];

        // Header 2 (Day Numbers)
        $dayNumbers = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayNumbers[] = (string) $i;
        }

        $data[] = [
            null, null, null, null, null,
            null, null, null, null, null, null,
            null,
            ...$dayNumbers
        ];

        // 1. Rekap Data & Aggregation
        $rekapData = RekapData::where('bulan', $bulan)
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

        $subAssyController = app(MonitoringSubAssyController::class);
        $spkMap = $subAssyController->getSpkMap($bulan, $tahun);

        $subAssies = SubAssy::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($s) => $this->normalize($s->customer) . '|' . $this->normalize($s->project) . '|' . $this->normalize($s->part_number));

        $prevBulan = $bulan == 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan == 1 ? $tahun - 1 : $tahun;
        $prevSubAssies = SubAssy::where('bulan', $prevBulan)
            ->where('tahun', $prevTahun)
            ->get()
            ->keyBy(fn ($s) => $this->normalize($s->part_number) . '|' . $this->normalize($s->part_name));

        $aggregatedRekap = [];
        foreach ($rekapData as $rekap) {
            $key = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number) . '|' . $this->normalize($rekap->models);
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $rekap;
            } else {
                $aggregatedRekap[$key]->total_qty_bulan_ini += $rekap->total_qty_bulan_ini;
                $aggregatedRekap[$key]->wip_spk_sa = max($aggregatedRekap[$key]->wip_spk_sa, $rekap->wip_spk_sa);
            }
        }

        $no = 1;
        foreach ($aggregatedRekap as $rekap) {
            $key = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number);
            $subAssy = $subAssies[$key] ?? null;

            $wipSebelumnya = 0;
            if ($subAssy && $subAssy->wip_sebelumnya != 0) {
                $wipSebelumnya = (int) $subAssy->wip_sebelumnya;
            } else {
                $prevKey = $this->normalize($rekap->part_number) . '|' . $this->normalize($rekap->models);
                $prevSubAssy = $prevSubAssies[$prevKey] ?? null;
                $wipSebelumnya = (int) ($prevSubAssy ? $prevSubAssy->wip_akhir : ($rekap->wip_spk_sa ?? 0));
            }

            $apiKeyNormalized = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->part_number);
            $localSpk = $spkMap[$apiKeyNormalized] ?? [];

            $prodMap = [];
            if ($subAssy && $subAssy->details) {
                foreach ($subAssy->details as $detail) {
                    if (strtolower($detail->tipe) === 'produksi') {
                        $prodMap[(int) $detail->tanggal] = (int) ($detail->jumlah ?? 0);
                    }
                }
            }

            $spkDays = [];
            $prodDays = [];
            $wipDays = [];

            $totalSPK = 0;
            $totalProduksi = 0;
            $currentWip = $wipSebelumnya;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $spk = (int) ($localSpk[$i] ?? 0);
                $prod = (int) ($prodMap[$i] ?? 0);

                $totalSPK += $spk;
                $totalProduksi += $prod;

                $currentWip = $currentWip + $spk - $prod;

                $spkDays[] = $spk;
                $prodDays[] = $prod;
                $wipDays[] = $currentWip;
            }

            $divider = $wipSebelumnya + $totalSPK;
            $produktivitas = $divider > 0 ? (ceil(($totalProduksi / $divider) * 100) / 100) : 0;

            // Row SPK
            $data[] = [
                $no++,
                $rekap->customer,
                $rekap->kode_project ?? '',
                $rekap->part_number,
                $rekap->models ?? '',
                (int) ($rekap->total_qty_bulan_ini ?? 0),
                $wipSebelumnya,
                $totalSPK,
                $totalProduksi,
                $currentWip,
                $produktivitas,
                'SPK',
                ...$spkDays
            ];

            // Row Produksi
            $data[] = [
                '', '', '', '', '',
                '', '', '', '', '', '',
                'PROD',
                ...$prodDays
            ];

            // Row WIP
            $data[] = [
                '', '', '', '', '',
                '', '', '', '', '', '',
                'WIP',
                ...$wipDays
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

    public function title(): string
    {
        return "Monitoring SubAssy";
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

                // Merge headers A1:A2 s/d L1:L2
                foreach (range('A', 'L') as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // Merge TANGGAL M1:...
                $sheet->mergeCells("M1:{$highestCol}1");

                // Merge kolom data identitas per 3 baris
                for ($row = 3; $row <= $highestRow; $row += 3) {
                    $sheet->getRowDimension($row)->setRowHeight(18);
                    $sheet->getRowDimension($row + 1)->setRowHeight(18);
                    $sheet->getRowDimension($row + 2)->setRowHeight(18);

                    foreach (range('A', 'K') as $col) {
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
                $sheet->getColumnDimension('G')->setWidth(12);   // WIP Sblm
                $sheet->getColumnDimension('H')->setWidth(11);   // Total SPK
                $sheet->getColumnDimension('I')->setWidth(11);   // Total Prod
                $sheet->getColumnDimension('J')->setWidth(11);   // WIP Akhir
                $sheet->getColumnDimension('K')->setWidth(14);   // Produktivitas
                $sheet->getColumnDimension('L')->setWidth(8);    // Status

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $colIdx = 12 + $d; // M = 13
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

                // Number formatting
                $sheet->getStyle("F3:J{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("K3:K{$highestRow}")->getNumberFormat()->setFormatCode('0%');
                $sheet->getStyle("M3:{$highestCol}{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');

                // Row badge colors
                for ($row = 3; $row <= $highestRow; $row++) {
                    $status = strtoupper(trim((string) $sheet->getCell("L{$row}")->getValue()));

                    if ($status === 'SPK') {
                        $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
                        $sheet->getStyle("L{$row}")->getFont()->getColor()->setRGB('1E40AF');
                        $sheet->getStyle("L{$row}")->getFont()->setBold(true);
                    } elseif ($status === 'PROD' || $status === 'PRODUKSI') {
                        $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCFCE7');
                        $sheet->getStyle("L{$row}")->getFont()->getColor()->setRGB('166534');
                        $sheet->getStyle("L{$row}")->getFont()->setBold(true);
                    } elseif ($status === 'WIP') {
                        $sheet->getStyle("L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                        $sheet->getStyle("L{$row}")->getFont()->getColor()->setRGB('991B1B');
                        $sheet->getStyle("L{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("A".($row - 2).":{$highestCol}{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('94A3B8');
                    }
                }

                $sheet->freezePane('M3');
            },
        ];
    }
}
