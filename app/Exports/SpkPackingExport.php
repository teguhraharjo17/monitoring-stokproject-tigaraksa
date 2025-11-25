<?php

namespace App\Exports;

use App\Models\SpkPackingHeader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Storage;

class SpkPackingExport
{
    protected $header;

    public function __construct(SpkPackingHeader $header)
    {
        $this->header = $header;
    }

    public function export()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ==== LOGO ====
        $logo = new Drawing();
        $logo->setPath(public_path('assets/media/logos/logo_milenia_login.png'));
        $logo->setHeight(70);
        $logo->setCoordinates('B2');
        $logo->setOffsetX(10);
        $logo->setWorksheet($sheet);
        $sheet->mergeCells('B2:B5');

        // ==== JUDUL ====
        $sheet->mergeCells('C2:J3');
        $sheet->setCellValue('C2', 'SURAT PERINTAH KERJA PACKING MEMBER');
        $sheet->getStyle('C2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ==== INFO SPK ====
        $sheet->setCellValue('C4', 'No SPK :');
        $sheet->mergeCells('D4:E4');
        $sheet->setCellValue('D4', 'MMM/SPK-PM/4/2025/14'); // Bisa kamu buat dinamis

        $sheet->setCellValue('C5', 'Tanggal Proses :');
        $sheet->mergeCells('D5:E5');
        $sheet->setCellValue('D5', optional($this->header->tanggal_proses)->format('d-m-Y'));

        // ==== BORDER UTAMA ====
        $sheet->getStyle('B2:J5')->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THICK]]
        ]);

        // ==== TTD ====
        $ttdLabels = ['PPIC', 'M.I.P', 'Finish Goods', 'Packing Member', 'Diketahui'];
        $ttdPaths = [
            $this->header->approved_ppic_path,
            $this->header->approved_mip_path,
            $this->header->approved_fg_path,
            $this->header->approved_packing_member_path,
            $this->header->approved_diketahui_path,
        ];
        $columns = ['L', 'M', 'N', 'O', 'P'];

        foreach ($columns as $i => $col) {
            // Merge 3 baris untuk TTD
            $sheet->mergeCells("{$col}2:{$col}4");

            // Label posisi
            $sheet->setCellValue("{$col}2", $ttdLabels[$i]);
            $sheet->getStyle("{$col}2")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP],
                'font' => ['bold' => true],
            ]);

            $path = $ttdPaths[$i];
            if ($path) {
                $fullPath = storage_path('app/public/' . $path);
                if (file_exists($fullPath)) {
                    $drawing = new Drawing();
                    $drawing->setPath($fullPath);
                    $drawing->setHeight(45); // Perkecil agar pas
                    $drawing->setCoordinates("{$col}3");
                    $drawing->setOffsetX(10);
                    $drawing->setOffsetY(5);
                    $drawing->setWorksheet($sheet);
                }
            }

            $sheet->getStyle("{$col}2:{$col}4")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $sheet->getColumnDimension($col)->setWidth(18);
        }

        // ==== HEADER TABEL ====
        $headerRow = 6;
        $headers = [
            'Tanggal Proses', 'Part Number', 'Customer', 'Model',
            'Qty/Set Box', 'Level Stock', 'Stock FG', 'WIP',
            'Total', 'Qty SPK (Set)', 'Qty SPK (Box)', 'Refer Kanban/PO', 'Keterangan',
        ];

        $sheet->fromArray([$headers], null, "B{$headerRow}");
        $sheet->getStyle("B{$headerRow}:N{$headerRow}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ==== DATA ====
        $row = $headerRow + 1;
        foreach ($this->header->details as $detail) {
            $sheet->fromArray([
                optional($this->header->tanggal_proses)->format('d-m-Y'),
                $detail->part_number,
                $detail->customer,
                $detail->nama_models,
                $detail->qty_per_set_box,
                $detail->level_stock,
                $detail->stock_fg,
                $detail->wip,
                $detail->total,
                $detail->qty_spk_set,
                $detail->qty_spk_box,
                $detail->refer_kanban_po,
                $detail->keterangan,
            ], null, "B{$row}");

            $sheet->getStyle("B{$row}:N{$row}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $row++;
        }

        // ==== WIDTH ====
        $widths = [
            'B' => 15, 'C' => 18, 'D' => 20, 'E' => 25,
            'F' => 15, 'G' => 15, 'H' => 15, 'I' => 15,
            'J' => 12, 'K' => 15, 'L' => 15, 'M' => 15,
            'N' => 25,
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ==== EXPORT ====
        $filename = 'SPK_Packing_' . $this->header->id . '_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = storage_path("app/public/export/{$filename}");

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
