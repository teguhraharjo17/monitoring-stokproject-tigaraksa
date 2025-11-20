<?php

namespace App\Exports;

use App\Models\RekapData;
use App\Models\MonitoringFGHeader;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegulerExport implements FromArray, WithEvents, ShouldAutoSize
{
    protected $bulan, $tahun, $jumlahHari, $data;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
        $this->data = $this->generateData();
    }

    public function array(): array
    {
        return $this->data;
    }

    protected function generateData(): array
    {
        $rekapData = RekapData::bulan($this->bulan)->tahun($this->tahun)->get();

        $rows = [];

        $judul = ['REKAP DATA KANBAN TARIKAN REGULER'];
        $periode = ['Periode: ' . strtoupper(Carbon::createFromDate($this->tahun, $this->bulan, 1)->translatedFormat('F Y'))];
        $rows[] = $judul;
        $rows[] = $periode;

        $staticHeaders = ['No', 'Customer', 'Part Number', 'Models', 'Qty PO', 'Total Qty Kanban', '% Penyerapan PO', '+/- (PCS)'];
        $headerRow1 = $staticHeaders;

        for ($i = 1; $i <= $this->jumlahHari; $i++) {
            $headerRow1[] = $i;
            $headerRow1[] = '';
        }

        $headerRow2 = array_fill(0, count($staticHeaders), '');
        for ($i = 1; $i <= $this->jumlahHari; $i++) {
            $headerRow2[] = 'D';
            $headerRow2[] = 'N';
        }

        $rows[] = $headerRow1;
        $rows[] = $headerRow2;

        $rekapData->each(function ($item, $index) use (&$rows) {
            $header = MonitoringFGHeader::with('details')->where([
                ['bulan', $this->bulan],
                ['tahun', $this->tahun],
                ['customer', $item->customer],
                ['project', $item->kode_project],
                ['part_number', $item->part_number],
                ['part_name', $item->models]
            ])->first();

            $details = $header?->details ?? collect();
            $totalKanban = 0;

            $row = [
                $index + 1,
                $item->customer,
                $item->part_number,
                $item->models,
                (int) $item->po_bulan_ini,
                0,
                '0%',
                0
            ];

            for ($i = 1; $i <= $this->jumlahHari; $i++) {
                $d = $details->firstWhere('tanggal', $i)?->out_qty_d ?? 0;
                $n = $details->firstWhere('tanggal', $i)?->out_qty_n ?? 0;
                $row[] = $d;
                $row[] = $n;
                $totalKanban += $d + $n;
            }

            $po = (int) $item->po_bulan_ini;
            $penyerapan = $po > 0 ? round(($totalKanban / $po) * 100, 2) . '%' : '0%';
            $selisih = $totalKanban - $po;

            $row[5] = $totalKanban;
            $row[6] = $penyerapan;
            $row[7] = $selisih;

            $rows[] = $row;
        });

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $titleRow = 1;
                $periodeRow = 2;
                $firstHeaderRow = 3;
                $secondHeaderRow = 4;
                $dataStartRow = 5;
                $dataRowCount = count($this->data) - 4;
                $colCount = 8 + ($this->jumlahHari * 2);
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

                $sheet->mergeCells("A{$titleRow}:{$lastCol}{$titleRow}");
                $sheet->mergeCells("A{$periodeRow}:{$lastCol}{$periodeRow}");

                $sheet->getStyle("A{$titleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
                ]);

                $sheet->getStyle("A{$periodeRow}")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C757D']],
                ]);

                foreach (range(0, 7) as $i) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->mergeCells("{$col}{$firstHeaderRow}:{$col}{$secondHeaderRow}");
                }

                $colIdx = 9;
                for ($i = 1; $i <= $this->jumlahHari; $i++) {
                    $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                    $sheet->mergeCells("{$startCol}{$firstHeaderRow}:{$endCol}{$firstHeaderRow}");
                    $colIdx += 2;
                }

                $headerRange = "A{$firstHeaderRow}:{$lastCol}{$secondHeaderRow}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DEEAF6']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                for ($row = $dataStartRow; $row < $dataStartRow + $dataRowCount; $row++) {
                    $colIndex = 9;
                    for ($i = 1; $i <= $this->jumlahHari; $i++) {
                        $sheet->getStyleByColumnAndRow($colIndex, $row)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('D4EDDA');
                        $colIndex++;
                        $sheet->getStyleByColumnAndRow($colIndex, $row)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8D7DA');
                        $colIndex++;
                    }
                }

                $sheet->getStyle("A{$firstHeaderRow}:{$lastCol}" . ($dataStartRow + $dataRowCount - 1))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A{$dataStartRow}:{$lastCol}" . ($dataStartRow + $dataRowCount - 1))
                    ->getAlignment()->setHorizontal('center')->setVertical('center');
            }

        ];
    }
}
