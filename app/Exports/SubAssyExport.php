<?php

namespace App\Exports;

use App\Models\SubAssy;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubAssyExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithEvents
{
    protected $bulan;
    protected $tahun;
    protected $rowCount = 0;

    public function __construct($bulan, $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function array(): array
    {
        $subAssies = SubAssy::with('details')
            ->where('bulan', $this->bulan)
            ->where('tahun', $this->tahun)
            ->get();

        $rows = [];
        $no = 1;

        foreach ($subAssies as $item) {
            $base = [
                $no++,
                $item->customer,
                $item->project,
                $item->part_number,
                $item->part_name,
                $item->wip_sebelumnya,
                $item->total_spk,
                $item->total_produksi,
                $item->wip_akhir,
                $item->produktivitas . '%',
            ];

            $spkRow = array_merge($base, ['SPK']);
            $produksiRow = array_merge(array_fill(0, count($base), ''), ['Produksi']);
            $wipRow = array_merge(array_fill(0, count($base), ''), ['WIP']);

            for ($i = 1; $i <= 31; $i++) {
                $spkRow[] = $item->details->firstWhere(fn($d) => $d->tanggal == $i && $d->tipe == 'SPK')->jumlah ?? '';
                $produksiRow[] = $item->details->firstWhere(fn($d) => $d->tanggal == $i && $d->tipe == 'Produksi')->jumlah ?? '';
                $wipRow[] = $item->details->firstWhere(fn($d) => $d->tanggal == $i && $d->tipe == 'WIP')->jumlah ?? '';
            }

            $rows[] = $spkRow;
            $rows[] = $produksiRow;
            $rows[] = $wipRow;
        }

        $this->rowCount = count($rows) + 2;

        return $rows;
    }

    public function headings(): array
    {
        $headers1 = [
            'NO',
            'CUSTOMER',
            'PROJECT',
            'PART NUMBER',
            'PART NAME',
            'WIP SEBELUMNYA',
            'TOTAL SPK',
            'TOTAL PRODUKSI',
            'WIP AKHIR',
            'Produktifitas',
            'STATUS'
        ];

        $dates = [];
        for ($i = 1; $i <= 31; $i++) {
            $dates[] = (string) $i;
        }

        return [
            array_merge($headers1, array_fill(0, 31, 'TANGGAL')),
            array_merge(array_fill(0, count($headers1), ''), $dates)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
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
                $highestRow = $this->rowCount;

                // Border + wrap all
                $sheet->getStyle("A1:AP{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                    'alignment' => ['vertical' => 'center', 'wrapText' => true],
                ]);

                // Merge header A1:K1 → A1:A2, ...
                foreach (range('A', 'K') as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                    $sheet->getStyle("{$col}1")->getAlignment()->setHorizontal('center');
                }

                // Merge tanggal header L1:AP1
                $sheet->mergeCells('L1:AP1');
                $sheet->getStyle('L1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('L1')->getFont()->setBold(true);

                // Merge data identitas tiap 3 baris
                for ($row = 3; $row < $highestRow; $row += 3) {
                    foreach (range('A', 'J') as $col) {
                        $sheet->mergeCells("{$col}{$row}:{$col}".($row + 2));
                        $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal('center');
                    }
                }

                // 🟨 Pewarnaan berdasarkan STATUS (kolom K)
                for ($row = 3; $row <= $highestRow; $row++) {
                    $status = $sheet->getCell("K{$row}")->getValue();
                    $color = null;

                    if (strtoupper($status) === 'SPK') {
                        $color = 'FFFF00'; // Kuning
                    } elseif (strtoupper($status) === 'PRODUKSI') {
                        $color = 'CCFFCC'; // Hijau muda
                    } elseif (strtoupper($status) === 'WIP') {
                        $color = 'CCE5FF'; // Biru muda
                    }

                    if ($color) {
                        // Hanya warnai kolom L–AP (tanggal)
                        $sheet->getStyle("L{$row}:AP{$row}")->getFill()->setFillType('solid')
                            ->getStartColor()->setRGB($color);
                    }

                    // 🔴 Pewarnaan merah jika Produktifitas < 100% (kolom J), hanya baris SPK
                    if (strtoupper($status) === 'SPK') {
                        $value = $sheet->getCell("J{$row}")->getValue();
                        $percentage = intval(str_replace('%', '', $value));

                        if ($percentage < 100) {
                            $sheet->getStyle("J{$row}")->getFont()->getColor()->setRGB('FF0000'); // Merah
                            $sheet->getStyle("J{$row}")->getFont()->setBold(true);
                        }
                    }
                }

                // 📏 Atur lebar kolom
                $sheet->getColumnDimension('B')->setWidth(15);  // CUSTOMER
                $sheet->getColumnDimension('E')->setWidth(40);  // PART NAME

                // Optional: Freeze header
                $sheet->freezePane('L3');
            },
        ];
    }
}
