<?php

namespace App\Exports;

use App\Models\MonitoringMIPHeader;
use App\Models\RekapData;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringMIPExport implements FromArray, WithStyles, WithEvents
{
    protected $bulan, $tahun;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function array(): array
    {
        $data = [];

        $data[] = [
            'NO', 'CUSTOMER', 'PROJECT', 'PART NUMBER', 'PART NAME',
            'TOTAL PO', 'STOCK AWAL',
            null, null,
            null, null, null,
            'STATUS',
            ...array_fill(0, 31, null)
        ];

        $data[] = [
            null, null, null, null, null, null, null,
            'IN', 'OUT',
            'MIN', 'SAFETY', 'MAX',
            null,
            ...range(1, 31)
        ];

        // Ambil data dari DB
        $headers = MonitoringMIPHeader::with('details')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->get();

        $no = 1;

        foreach ($headers as $header) {
            $po = RekapData::where('bulan', $this->bulan)
                ->where('tahun', $this->tahun)
                ->where('customer', $header->customer)
                ->where('part_number', $header->part_number)
                ->value('total_qty_bulan_ini') ?? 0;

            $rowData = [
                [
                    $no++, $header->customer, $header->project, $header->part_number, $header->part_name,
                    $po,
                    $header->stock_awal ?? 0,
                    $header->total_in ?? 0,
                    $header->total_out ?? 0,
                    $header->level_min ?? 0,
                    $header->level_safety ?? 0,
                    $header->level_max ?? 0,
                    'IN'
                ],
                [
                    '', '', '', '', '', '', '', '', '', '', '', '', 'OUT'
                ],
                [
                    '', '', '', '', '', '', '', '', '', '', '', '', 'BALANCE'
                ]
            ];

            $detailMap = $header->details->keyBy('tanggal');

            for ($i = 1; $i <= 31; $i++) {
                $d = $detailMap[$i] ?? null;
                $rowData[0][] = $d ? ($d->in_qty ?? 0) : 0;
                $rowData[1][] = $d ? ($d->out_qty ?? 0) : 0;
                $rowData[2][] = $d ? ($d->balance ?? 0) : 0;
            }

            foreach ($rowData as $r) {
                $data[] = $r;
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();

                // Set lebar kolom
                $sheet->getColumnDimension('A')->setWidth(5);    // No
                $sheet->getColumnDimension('B')->setWidth(15);   // Customer
                $sheet->getColumnDimension('C')->setWidth(12);   // Project
                $sheet->getColumnDimension('D')->setWidth(18);   // Part Number
                $sheet->getColumnDimension('E')->setWidth(40);   // Part Name

                // Merge kolom A–G dan M (Status)
                foreach (range('A', 'G') as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // Merge kolom H–I = TOTAL
                $sheet->mergeCells("H1:I1");
                $sheet->setCellValue('H1', 'TOTAL');

                // Merge kolom J–L = LEVEL STOCK
                $sheet->mergeCells('J1:L1');
                $sheet->setCellValue('J1', 'LEVEL STOCK');

                // Merge kolom M = STATUS
                $sheet->mergeCells("M1:M2");

                // Merge kolom N–AR = TANGGAL
                $sheet->mergeCells('N1:AR1');
                $sheet->setCellValue('N1', 'TANGGAL');

                // Style center
                $sheet->getStyle('H1:I1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('J1:L1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('N1:AR1')->getAlignment()->setHorizontal('center');

                // Merge kolom A–L per 3 baris data
                for ($row = 3; $row <= $highestRow; $row += 3) {
                    foreach (range('A', 'L') as $col) {
                        $sheet->mergeCells("{$col}{$row}:{$col}".($row + 2));
                    }
                }

                // Border + center
                $sheet->getStyle("A1:AR{$highestRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $sheet->getStyle("A1:AR{$highestRow}")
                    ->getAlignment()->setVertical('center')->setHorizontal('center');

                // Pewarnaan baris berdasarkan STATUS
                for ($row = 3; $row <= $highestRow; $row++) {
                    $status = strtoupper(trim((string) $sheet->getCell("M{$row}")->getValue()));

                    $color = match ($status) {
                        'IN' => 'CCFFCC',
                        'OUT' => 'FCDCDC',
                        'BALANCE' => 'D8E9FF',
                        default => null
                    };

                    if ($color) {
                        $sheet->getStyle("N{$row}:AR{$row}")
                            ->getFill()
                            ->setFillType('solid')
                            ->getStartColor()
                            ->setRGB($color);
                    }

                    if (strtoupper($status) === 'BALANCE') {
                        $sheet->getStyle("M{$row}:AR{$row}")
                            ->getBorders()
                            ->getOutline()
                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                    }
                }
            },
        ];
    }
}
