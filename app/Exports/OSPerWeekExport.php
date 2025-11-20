<?php

namespace App\Exports;

use App\Models\RekapData;
use App\Models\MonitoringFGHeader;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OSPerWeekExport implements FromArray, WithEvents, ShouldAutoSize
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
        $rows[] = ['REKAP DATA KANBAN - O/S PER WEEKLY'];
        $rows[] = ['Periode: ' . strtoupper(Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y'))];

        // Header row 3 and 4
        $rows[] = [
            'No', 'Customer', 'Part Number', 'Models', 'PO',
            'Week 1', '', 'Week 2', '', 'Week 3', '', 'Week 4', ''
        ];
        $rows[] = [
            '', '', '', '', '',
            'Delivery', 'O/S',
            'Delivery', 'O/S',
            'Delivery', 'O/S',
            'Delivery', 'O/S'
        ];

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

            $po = (int)($item->po_bulan_ini ?? 0);

            $rows[] = [
                $index + 1,
                $item->customer,
                $item->part_number,
                $item->models,
                $po,
                $minggu[1],
                $po - $minggu[1],
                $minggu[2],
                $po - $minggu[2],
                $minggu[3],
                $po - $minggu[3],
                $minggu[4],
                $po - $minggu[4],
            ];
        });

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $headerStartRow = 3;
                $headerEndRow = 4;
                $dataStartRow = 5;
                $lastColumn = 'M';
                $dataRowCount = count($this->data) - 4;

                // Merge Title and Period
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->mergeCells("A2:{$lastColumn}2");

                $sheet->getStyle("A1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
                ]);

                $sheet->getStyle("A2")->applyFromArray([
                    'font' => ['italic' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center'],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6C757D']],
                ]);

                // Merge header cells
                foreach (range('A', 'E') as $col) {
                    $sheet->mergeCells("{$col}{$headerStartRow}:{$col}{$headerEndRow}");
                }

                $sheet->mergeCells("F{$headerStartRow}:G{$headerStartRow}");
                $sheet->mergeCells("H{$headerStartRow}:I{$headerStartRow}");
                $sheet->mergeCells("J{$headerStartRow}:K{$headerStartRow}");
                $sheet->mergeCells("L{$headerStartRow}:M{$headerStartRow}");

                // Apply style to headers
                $sheet->getStyle("A{$headerStartRow}:{$lastColumn}{$headerEndRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAF7']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // Sub-column coloring
                $colorMap = [
                    'F' => 'D1ECF1', 'G' => 'F8D7DA',
                    'H' => 'D1ECF1', 'I' => 'F8D7DA',
                    'J' => 'D1ECF1', 'K' => 'F8D7DA',
                    'L' => 'D1ECF1', 'M' => 'F8D7DA',
                    'E' => 'FFF3CD',
                ];

                foreach ($colorMap as $col => $color) {
                    $sheet->getStyle("{$col}{$headerEndRow}")->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($color);
                }

                // Data rows styling
                $sheet->getStyle("A{$dataStartRow}:{$lastColumn}" . ($dataStartRow + $dataRowCount - 1))
                    ->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                    ]);

                // Freeze pane
                $sheet->freezePane("A{$dataStartRow}");
            }
        ];
    }
}
