<?php

namespace App\Exports;

use App\Models\MonitoringFGHeader;
use App\Models\RekapData;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FinishGoodsExport implements FromArray, WithEvents, WithStyles, WithColumnWidths
{
    protected $bulan, $tahun, $jumlahHari;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
    }

    public function array(): array
    {
        $header1 = [
            'CUSTOMER', 'PROJECT', 'PART NUMBER', 'PART NAME',
            'ADVANCE DELIVERY', 'TOTAL PO', 'OUTSTANDING', 'DELIVERY %',
            'TOTAL', '', // IN / OUT
            'STOCK ON HAND',
            'LEVEL', '', '', // Min / Safety / Max
            'STATUS STOCK', 'STATUS'
        ];

        $header2 = array_fill(0, 8, null); // Up to DELIVERY %
        $header2[] = 'IN';
        $header2[] = 'OUT';
        $header2[] = null; // STOCK ON HAND
        $header2[] = 'Min';
        $header2[] = 'Safety';
        $header2[] = 'Max';
        $header2[] = null; // STATUS STOCK
        $header2[] = null; // STATUS

        // Tanggal harian (D/N)
        for ($i = 1; $i <= $this->jumlahHari; $i++) {
            $header1[] = $i;
            $header1[] = null;
            $header2[] = 'D';
            $header2[] = 'N';
        }

        $data = [$header1, $header2];

        MonitoringFGHeader::with('details')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->chunk(100, function ($headers) use (&$data) {
                foreach ($headers as $header) {
                    $details = $header->details->keyBy('tanggal');

                    // ✅ Ambil balance terakhir yang ada (tanggal max dengan balance_n tidak null)
                    $lastBalance = 0;
                    for ($i = $this->jumlahHari; $i >= 1; $i--) {
                        if (!empty($details[$i]) && $details[$i]->balance_n !== null) {
                            $lastBalance = $details[$i]->balance_n;
                            break;
                        }
                    }

                    // Ambil TOTAL PO dari RekapData
                    $rekap = RekapData::where('bulan', $this->bulan)
                        ->where('tahun', $this->tahun)
                        ->where('customer', $header->customer)
                        ->where('kode_project', $header->project)
                        ->where('part_number', $header->part_number)
                        ->first();

                    $totalPO = (int) ($rekap->total_qty_bulan_ini ?? 0);
                    $advance = (int) ($header->advance_delivery ?? 0);
                    $totalOut = (int) ($header->total_out ?? 0);
                    $outstanding = max(0, $totalPO - $advance - $totalOut);
                    $deliveryPercentage = $totalPO > 0 ? round(($outstanding / $totalPO) * 100) : 0;

                    // Status stock
                    $statusStock = 'Aman';
                    if ($lastBalance <= $header->level_min) $statusStock = 'Problem';
                    elseif ($lastBalance > $header->level_max) $statusStock = 'Over';

                    $fixed = [
                        $header->customer,
                        $header->project,
                        $header->part_number,
                        $header->part_name,
                        $advance,
                        $totalPO,
                        $outstanding,
                        "{$deliveryPercentage}%",
                        $header->total_in,
                        $header->total_out,
                        $lastBalance,
                        $header->level_min,
                        $header->level_safety,
                        $header->level_max,
                        $statusStock
                    ];

                    foreach (['IN', 'OUT', 'BALANCE'] as $status) {
                        $row = [...$fixed, $status];
                        for ($i = 1; $i <= $this->jumlahHari; $i++) {
                            $d = $details->get($i);
                            if ($status === 'IN') {
                                $row[] = $d->in_qty_d ?? 0;
                                $row[] = $d->in_qty_n ?? 0;
                            } elseif ($status === 'OUT') {
                                $row[] = $d->out_qty_d ?? 0;
                                $row[] = $d->out_qty_n ?? 0;
                            } else {
                                $row[] = $d->balance_d ?? 0;
                                $row[] = $d->balance_n ?? 0;
                            }
                        }
                        $data[] = $row;
                    }
                }
            });

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();

                // ✅ Merge headers
                foreach (range('A', 'H') as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                $sheet->mergeCells("I1:J1"); // TOTAL
                $sheet->mergeCells("K1:K2"); // STOCK ON HAND
                $sheet->mergeCells("L1:N1"); // LEVEL
                $sheet->mergeCells("O1:O2"); // STATUS STOCK
                $sheet->mergeCells("P1:P2"); // STATUS

                // Tanggal merge per 2 kolom
                $colIndex = 17;
                for ($i = 1; $i <= $this->jumlahHari; $i++) {
                    $col1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
                    $col2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex++);
                    $sheet->mergeCells("{$col1}1:{$col2}1");
                    $sheet->setCellValue("{$col1}1", $i);
                }

                // ✅ Merge fixed columns per 3 data rows
                for ($row = 3; $row <= $highestRow; $row += 3) {
                    foreach (range(1, 15) as $colIdx) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->mergeCells("{$colLetter}{$row}:{$colLetter}" . ($row + 2));
                    }
                }

                // ✅ Styling
                $sheet->getStyle("A1:ZZ{$highestRow}")->applyFromArray([
                    'font' => ['bold' => false],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Header bold
                $sheet->getStyle("A1:ZZ2")->getFont()->setBold(true);

                // ✅ Border TEBAK hanya untuk row BALANCE → kolom P sampai akhir
                for ($row = 5; $row <= $highestRow; $row += 3) {
                    $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(17); // P = index 16
                    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(15 + $this->jumlahHari * 2 + 2); // After tanggal

                    $sheet->getStyle("{$startCol}{$row}:{$endCol}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_THICK,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }

                $sheet->freezePane('Q3'); // Freeze after STATUS
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, 'B' => 12, 'C' => 15, 'D' => 30,
            'E' => 15, 'F' => 12, 'G' => 15, 'H' => 12,
            'I' => 10, 'J' => 10,
            'K' => 14,
            'L' => 10, 'M' => 10, 'N' => 10,
            'O' => 15, 'P' => 10,
        ];
    }
}
