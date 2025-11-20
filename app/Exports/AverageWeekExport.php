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

class AverageWeekExport implements FromArray, WithEvents, ShouldAutoSize
{
    protected $bulan, $tahun, $data;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->data = $this->generateData();
    }

    public function array(): array
    {
        return $this->data;
    }

    protected function generateData(): array
    {
        $jumlahHari = Carbon::create($this->tahun, $this->bulan, 1)->daysInMonth;
        $rekapData = RekapData::bulan($this->bulan)->tahun($this->tahun)->get();

        $rows = [];

        // Title and Periode
        $rows[] = ['REKAP DATA KANBAN TARIKAN REGULER - AVERAGE WEEK'];
        $rows[] = ['Periode: ' . strtoupper(Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y'))];

        // Header rows start at row 3
        $rows[] = ['No', 'Customer', 'Part Number', 'Models', 'Sum Per Weekly', '', '', ''];
        $rows[] = ['', '', '', '', 'I', 'II', 'III', 'IV'];

        $rekapData->each(function ($item, $index) use (&$rows, $jumlahHari) {
            $header = MonitoringFGHeader::with('details')->where([
                ['bulan', $this->bulan],
                ['tahun', $this->tahun],
                ['customer', $item->customer],
                ['project', $item->kode_project],
                ['part_number', $item->part_number],
                ['part_name', $item->models],
            ])->first();

            $details = $header?->details ?? collect();

            $minggu = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $row = $details->firstWhere('tanggal', $i);
                $d = (int)($row->out_qty_d ?? 0);
                $n = (int)($row->out_qty_n ?? 0);
                $total = $d + $n;

                if ($i >= 1 && $i <= 7) {
                    $minggu[1] += $total;
                } elseif ($i >= 8 && $i <= 14) {
                    $minggu[2] += $total;
                } elseif ($i >= 15 && $i <= 21) {
                    $minggu[3] += $total;
                } else {
                    $minggu[4] += $total;
                }
            }

            $rows[] = [
                $index + 1,
                $item->customer,
                $item->part_number,
                $item->models,
                $minggu[1],
                $minggu[2],
                $minggu[3],
                $minggu[4],
            ];
        });

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'H';
                $headerStartRow = 3;
                $headerEndRow = 4;
                $dataStartRow = 5;
                $dataRowCount = count($this->data) - 4;

                // Merge title and periode
                $sheet->mergeCells("A1:H1");
                $sheet->mergeCells("A2:H2");

                // Style title
                $sheet->getStyle("A1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
                ]);

                // Style periode
                $sheet->getStyle("A2")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C757D']],
                ]);

                // Merge main headers
                foreach (range('A', 'D') as $col) {
                    $sheet->mergeCells("{$col}{$headerStartRow}:{$col}{$headerEndRow}");
                }
                $sheet->mergeCells("E{$headerStartRow}:H{$headerStartRow}");

                // Style header rows
                $sheet->getStyle("A{$headerStartRow}:H{$headerEndRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // Sub header coloring
                foreach (range('E', 'H') as $col) {
                    $sheet->getStyle("{$col}{$headerEndRow}")->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E2EFDA');
                }

                // Data rows border & alignment
                $sheet->getStyle("A{$dataStartRow}:H" . ($dataStartRow + $dataRowCount - 1))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$dataStartRow}:H" . ($dataStartRow + $dataRowCount - 1))
                    ->getAlignment()->setHorizontal('center')->setVertical('center');

                // Freeze pane below header
                $sheet->freezePane("A{$dataStartRow}");
            }
        ];
    }
}
