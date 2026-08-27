<?php

namespace App\Exports;

use App\Models\MonitoringFGHeader;
use App\Models\MonitoringMIPHeader;
use App\Models\RekapData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class FinishGoodsExport implements FromArray, WithEvents, WithStyles, WithColumnWidths
{
    protected $bulan, $tahun, $customer, $jumlahHari;

    public function __construct($bulan, $tahun, $customer = null)
    {
        $this->bulan = (int) $bulan;
        $this->tahun = (int) $tahun;
        $this->customer = $customer;
        $this->jumlahHari = Carbon::createFromDate($this->tahun, $this->bulan, 1)->daysInMonth;
    }

    private function normalize($str)
    {
        return strtoupper(str_replace([' ', '-', '_'], '', $str ?? ''));
    }

    public function array(): array
    {
        // === HEADER BARIS 1 ===
        $header1 = [
            'NO',
            'CUSTOMER',
            'PROJECT',
            'PART NUMBER',
            'PART NAME',
            'TOTAL PO',
            'ADVANCE DELIVERY',
            'OUTSTANDING',
            '% DELIVERY',
            'STOCK AWAL',
            'TOTAL',
            null, // untuk OUT
            'LEVEL',
            null, // untuk Safety
            null, // untuk Max
            'STOCK ON HAND',
            'STATUS STOCK',
            'STATUS'
        ];

        // === HEADER BARIS 2 ===
        $header2 = array_fill(0, 10, null); // NO s/d STOCK AWAL
        $header2[] = 'IN';
        $header2[] = 'OUT';
        $header2[] = 'Min';
        $header2[] = 'Safety';
        $header2[] = 'Max';
        $header2[] = null; // STOCK ON HAND
        $header2[] = null; // STATUS STOCK
        $header2[] = null; // STATUS

        // Kolom Tanggal Harian 1..jumlahHari
        for ($i = 1; $i <= $this->jumlahHari; $i++) {
            $header1[] = $i;
            $header1[] = null;
            $header2[] = 'D';
            $header2[] = 'N';
        }

        $sheetData = [$header1, $header2];

        // 1. Load RekapData & Aggregate duplicates
        $rekapListRaw = RekapData::where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->when($this->customer, function ($q) {
                if (is_array($this->customer)) {
                    $q->whereIn('customer', $this->customer);
                } else {
                    $q->where('customer', $this->customer);
                }
            })
            ->get();

        $aggregatedRekap = [];
        foreach ($rekapListRaw as $rekap) {
            $key = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number) . '|' . $this->normalize($rekap->models);
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $rekap;
            } else {
                $aggregatedRekap[$key]->total_qty_bulan_ini += $rekap->total_qty_bulan_ini;
                $aggregatedRekap[$key]->stock_awal_fg = max($aggregatedRekap[$key]->stock_awal_fg, $rekap->stock_awal_fg);
            }
        }

        // 2. Bulk Load Level Stok
        $levelStokMap = DB::table('level_stok_detail as d')
            ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
            ->where('l.bulan', $this->bulan)
            ->where('l.tahun', $this->tahun)
            ->get()
            ->keyBy(fn($l) => $this->normalize($l->customer) . '|' . $this->normalize($l->kode_projek) . '|' . $this->normalize($l->part_number));

        // 3. Bulk Load FG Headers & Details
        $fgHeaders = MonitoringFGHeader::with('details')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

        // 4. Bulk Load MIP Headers & Details
        $mipHeaders = MonitoringMIPHeader::with('details')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

        $itemIdx = 0;
        foreach ($aggregatedRekap as $rekap) {
            $itemIdx++;
            $matchKey = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number);

            $level = $levelStokMap[$matchKey] ?? null;
            $header = $fgHeaders[$matchKey] ?? null;
            $details = $header ? $header->details->keyBy('tanggal') : collect();

            $mipHeader = $mipHeaders[$matchKey] ?? null;
            $mipDetails = $mipHeader ? $mipHeader->details->keyBy('tanggal') : collect();

            $stockAwal = (int) ($header->stock_awal ?? ($rekap->stock_awal_fg ?? 0));

            $inRowDays = [];
            $outRowDays = [];
            $balRowDays = [];

            $balance = $stockAwal;
            $totalInActual = 0;
            $totalOutActual = 0;

            for ($i = 1; $i <= $this->jumlahHari; $i++) {
                $inD = (int) ($mipDetails[$i]->out_qty ?? 0);
                $inN = (int) ($details[$i]->in_qty_n ?? 0);
                $outD = (int) ($details[$i]->out_qty_d ?? 0);
                $outN = (int) ($details[$i]->out_qty_n ?? 0);

                $balanceD = $balance + $inD - $outD;
                $balanceN = $balanceD + $inN - $outN;

                $inRowDays[] = $inD;
                $inRowDays[] = $inN;
                $outRowDays[] = $outD;
                $outRowDays[] = $outN;
                $balRowDays[] = $balanceD;
                $balRowDays[] = $balanceN;

                $totalInActual += ($inD + $inN);
                $totalOutActual += ($outD + $outN);
                $balance = $balanceN;
            }

            $advance = (int) ($header->advance_delivery ?? 0);
            $totalPO = (int) ($rekap->total_qty_bulan_ini ?? 0);
            $outstanding = max(0, $totalPO - $advance - $totalOutActual);
            $totalDelivered = $advance + $totalOutActual;
            $deliveryPercentage = $totalPO > 0 ? round(($totalDelivered / $totalPO) * 100, 2) : 0;

            $levelMin = (int) ($level->min ?? ($header->level_min ?? 0));
            $levelSafety = (int) ($level->safety_fg ?? ($header->level_safety ?? 0));
            $levelMax = (int) ($level->max ?? ($header->level_max ?? 0));

            $statusStock = 'Aman';
            if ($balance <= $levelMin) {
                $statusStock = 'Problem';
            } elseif ($balance > $levelMax) {
                $statusStock = 'Over';
            }

            $fixed = [
                $itemIdx,
                $rekap->customer,
                $rekap->kode_project ?? '',
                $rekap->part_number,
                $rekap->models,
                $totalPO,
                $advance,
                $outstanding,
                "{$deliveryPercentage}%",
                $stockAwal,
                $totalInActual,
                $totalOutActual,
                $levelMin,
                $levelSafety,
                $levelMax,
                $balance,
                $statusStock,
            ];

            // Baris 1: IN
            $sheetData[] = array_merge($fixed, ['IN'], $inRowDays);
            // Baris 2: OUT
            $sheetData[] = array_merge($fixed, ['OUT'], $outRowDays);
            // Baris 3: BAL
            $sheetData[] = array_merge($fixed, ['BAL'], $balRowDays);
        }

        return $sheetData;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $lastColLetter = Coordinate::stringFromColumnIndex(18 + $this->jumlahHari * 2);

                // ==========================================
                // 1. MERGE HEADER KOLOM TETAP (Baris 1 s/d 2)
                // ==========================================
                $sheet->mergeCells('A1:A2'); // NO
                $sheet->mergeCells('B1:B2'); // CUSTOMER
                $sheet->mergeCells('C1:C2'); // PROJECT
                $sheet->mergeCells('D1:D2'); // PART NUMBER
                $sheet->mergeCells('E1:E2'); // PART NAME
                $sheet->mergeCells('F1:F2'); // TOTAL PO
                $sheet->mergeCells('G1:G2'); // ADVANCE DELIVERY
                $sheet->mergeCells('H1:H2'); // OUTSTANDING
                $sheet->mergeCells('I1:I2'); // % DELIVERY
                $sheet->mergeCells('J1:J2'); // STOCK AWAL
                $sheet->mergeCells('K1:L1'); // TOTAL (IN & OUT)
                $sheet->mergeCells('M1:O1'); // LEVEL (Min, Safety, Max)
                $sheet->mergeCells('P1:P2'); // STOCK ON HAND
                $sheet->mergeCells('Q1:Q2'); // STATUS STOCK
                $sheet->mergeCells('R1:R2'); // STATUS

                // ==========================================
                // 2. MERGE HEADER TANGGAL HARIAN (D & N)
                // ==========================================
                for ($i = 1; $i <= $this->jumlahHari; $i++) {
                    $c1 = Coordinate::stringFromColumnIndex(19 + ($i - 1) * 2);
                    $c2 = Coordinate::stringFromColumnIndex(20 + ($i - 1) * 2);
                    $sheet->mergeCells("{$c1}1:{$c2}1");
                }

                // ==========================================
                // 3. MERGE KOLOM DATA PER 3 BARIS (A s/d Q)
                // ==========================================
                for ($row = 3; $row <= $highestRow; $row += 3) {
                    for ($col = 1; $col <= 17; $col++) {
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $sheet->mergeCells("{$colLetter}{$row}:{$colLetter}" . ($row + 2));
                    }
                }

                // ==========================================
                // 4. STYLING GLOBAL & HEADER
                // ==========================================
                $sheet->setShowGridlines(true);
                $sheet->getSheetView()->setShowZeros(true);

                // Header Baris 1: Dark Slate Navy
                $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 10,
                        'name' => 'Calibri',
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                // Header Baris 2: Sub-headers Slate Navy
                $sheet->getStyle("A2:{$lastColLetter}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 9,
                        'name' => 'Calibri',
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '334155'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Set Header Row Height
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // ==========================================
                // 5. STYLING DATA ROWS
                // ==========================================
                if ($highestRow >= 3) {
                    // Default font & alignment all data
                    $sheet->getStyle("A3:{$lastColLetter}{$highestRow}")->applyFromArray([
                        'font' => [
                            'size' => 9.5,
                            'name' => 'Calibri',
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    // Format angka agar angka 0 tetap muncul jelas (bukan blank)
                    $sheet->getStyle("F3:H{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("J3:P{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("S3:{$lastColLetter}{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');

                    // Part Name (Kolom E) rata kiri
                    $sheet->getStyle("E3:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // Pewarnaan baris IN / OUT / BAL per part
                    for ($row = 3; $row <= $highestRow; $row += 3) {
                        $sheet->getRowDimension($row)->setRowHeight(20);
                        $sheet->getRowDimension($row + 1)->setRowHeight(20);
                        $sheet->getRowDimension($row + 2)->setRowHeight(20);

                        // --- Status Stock Badge (Kolom Q) ---
                        $statusVal = (string) $sheet->getCell("Q{$row}")->getValue();
                        $statusBg = 'DCFCE7'; // Default Aman
                        $statusText = '166534';
                        if ($statusVal === 'Problem') {
                            $statusBg = 'FEE2E2';
                            $statusText = '991B1B';
                        } elseif ($statusVal === 'Over') {
                            $statusBg = 'FEF9C3';
                            $statusText = '854D0E';
                        }
                        $sheet->getStyle("Q{$row}:Q" . ($row + 2))->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => $statusText]],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $statusBg],
                            ],
                        ]);

                        // --- Baris IN ---
                        $sheet->getStyle("R{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '15803D']],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'DCFCE7'],
                            ],
                        ]);
                        $sheet->getStyle("S{$row}:{$lastColLetter}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F0FDF4'],
                            ],
                            'font' => ['color' => ['rgb' => '166534']],
                        ]);

                        // --- Baris OUT ---
                        $outRow = $row + 1;
                        $sheet->getStyle("R{$outRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEE2E2'],
                            ],
                        ]);
                        $sheet->getStyle("S{$outRow}:{$lastColLetter}{$outRow}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEF2F2'],
                            ],
                            'font' => ['color' => ['rgb' => '991B1B']],
                        ]);

                        // --- Baris BAL ---
                        $balRow = $row + 2;
                        $sheet->getStyle("R{$balRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'DBEAFE'],
                            ],
                        ]);
                        $sheet->getStyle("S{$balRow}:{$lastColLetter}{$balRow}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'EFF6FF'],
                            ],
                            'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
                        ]);

                        // Garis pemisah tebal antar part
                        $sheet->getStyle("A{$balRow}:{$lastColLetter}{$balRow}")->applyFromArray([
                            'borders' => [
                                'bottom' => [
                                    'borderStyle' => Border::BORDER_MEDIUM,
                                    'color' => ['rgb' => '64748B'],
                                ],
                            ],
                        ]);
                    }
                }

                // ==========================================
                // 6. FREEZE PANE & HEADER BORDER
                // ==========================================
                $sheet->getStyle("A1:{$lastColLetter}2")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '475569'],
                        ],
                    ],
                ]);

                // Freeze di kolom S (setelah kolom STATUS) dan baris 3
                $sheet->freezePane('S3');
            }
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 6,   // NO
            'B' => 16,  // CUSTOMER
            'C' => 12,  // PROJECT
            'D' => 18,  // PART NUMBER
            'E' => 34,  // PART NAME
            'F' => 12,  // TOTAL PO
            'G' => 15,  // ADVANCE DELIVERY
            'H' => 14,  // OUTSTANDING
            'I' => 13,  // % DELIVERY
            'J' => 12,  // STOCK AWAL
            'K' => 9,   // TOTAL IN
            'L' => 9,   // TOTAL OUT
            'M' => 9,   // LEVEL MIN
            'N' => 9,   // LEVEL SAFETY
            'O' => 9,   // LEVEL MAX
            'P' => 14,  // STOCK ON HAND
            'Q' => 14,  // STATUS STOCK
            'R' => 10,  // STATUS
        ];

        // Lebar kolom harian (S dst)
        for ($i = 1; $i <= $this->jumlahHari * 2; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex(18 + $i);
            $widths[$colLetter] = 6.5;
        }

        return $widths;
    }
}
