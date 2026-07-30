<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RekapData;
use App\Models\LevelStokDetail;
use App\Models\SubAssy;
use App\Models\SubAssyDetail;
use App\Models\MonitoringMIPHeader;
use App\Models\MonitoringMIPDetail;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringFGDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncMonitoringFromRekap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:sync {--bulan= : Bulan target (1-12)} {--tahun= : Tahun target (4 digit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi dan inisialisasi data Monitoring Sub Assy, MIP, dan Finish Goods dari tabel Rekap Data dan Level Stok';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bulan = (int) ($this->option('bulan') ?: now()->month);
        $tahun = (int) ($this->option('tahun') ?: now()->year);

        $this->info("Memulai sinkronisasi monitoring untuk Periode: {$bulan}-{$tahun}");

        // 1. Ambil RekapData
        $rekapList = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        if ($rekapList->isEmpty()) {
            $this->warn("Tidak ada data di tabel Rekap Data untuk periode {$bulan}-{$tahun}. Sinkronisasi dibatalkan.");
            return 0;
        }

        $this->info("Menemukan " . $rekapList->count() . " baris data rekap.");

        $daysInMonth = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        // 2. Load Level Stok Detail
        $levelStokHeader = DB::table('level_stok')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        $levelStokDetails = [];
        if ($levelStokHeader) {
            $levelStokDetails = LevelStokDetail::where('level_stok_id', $levelStokHeader->id)
                ->get()
                ->keyBy(fn($item) => $this->normalizeKey($item->customer, $item->kode_projek, $item->part_number));
        }

        $countSubAssy = 0;
        $countMIP = 0;
        $countFG = 0;

        foreach ($rekapList as $rekap) {
            $key = $this->normalizeKey($rekap->customer, $rekap->kode_project, $rekap->part_number);
            $level = $levelStokDetails[$key] ?? null;

            $project = trim((string)$rekap->kode_project);

            // ==========================================
            // A. SINKRONISASI SUB ASSY
            // ==========================================
            $subAssy = SubAssy::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $rekap->customer,
                    'project' => $project,
                    'part_number' => $rekap->part_number,
                ],
                [
                    'part_name' => $rekap->models,
                    'wip_sebelumnya' => (int) ($rekap->wip_spk_sa ?? 0),
                ]
            );

            // Inisialisasi Detail Sub Assy
            $wipAccumulator = (int) $subAssy->wip_sebelumnya;
            $totalSpk = 0;
            $totalProduksi = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                // Tipe SPK
                $spkDetail = SubAssyDetail::firstOrCreate(
                    [
                        'sub_assy_id' => $subAssy->id,
                        'tanggal' => $d,
                        'tipe' => 'SPK'
                    ],
                    ['jumlah' => 0]
                );
                $totalSpk += (int) $spkDetail->jumlah;

                // Tipe Produksi
                $prodDetail = SubAssyDetail::firstOrCreate(
                    [
                        'sub_assy_id' => $subAssy->id,
                        'tanggal' => $d,
                        'tipe' => 'Produksi'
                    ],
                    ['jumlah' => 0]
                );
                $totalProduksi += (int) $prodDetail->jumlah;

                // Hitung WIP Harian
                $wipAccumulator = $wipAccumulator + (int) $spkDetail->jumlah - (int) $prodDetail->jumlah;

                // Tipe WIP (Wajib update agar sinkron)
                SubAssyDetail::updateOrCreate(
                    [
                        'sub_assy_id' => $subAssy->id,
                        'tanggal' => $d,
                        'tipe' => 'WIP'
                    ],
                    ['jumlah' => $wipAccumulator]
                );
            }

            $produktivitas = $totalSpk > 0 ? round(($totalProduksi / $totalSpk) * 100, 2) : 0;
            $subAssy->update([
                'total_spk' => $totalSpk,
                'total_produksi' => $totalProduksi,
                'wip_akhir' => $wipAccumulator,
                'produktivitas' => $produktivitas
            ]);

            $countSubAssy++;

            // ==========================================
            // B. SINKRONISASI MONITORING MIP
            // ==========================================
            $mipHeader = MonitoringMIPHeader::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $rekap->customer,
                    'project' => $project,
                    'part_number' => $rekap->part_number,
                ],
                [
                    'part_name' => $rekap->models,
                    'stock_awal' => (int) ($rekap->stock_awal_mip ?? 0),
                    'level_min' => (int) ($level->min ?? 0),
                    'level_safety' => (int) ($level->safety_mip ?? 0),
                    'level_max' => (int) ($level->max ?? 0),
                ]
            );

            // Inisialisasi Detail MIP
            $balanceAccumulator = (int) $mipHeader->stock_awal;
            $totalIn = 0;
            $totalOut = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $mipDetail = MonitoringMIPDetail::firstOrCreate(
                    [
                        'header_id' => $mipHeader->id,
                        'tanggal' => $d
                    ],
                    [
                        'in_qty' => 0,
                        'out_qty' => 0,
                        'balance' => $balanceAccumulator
                    ]
                );

                $totalIn += (int) $mipDetail->in_qty;
                $totalOut += (int) $mipDetail->out_qty;
                $balanceAccumulator = $balanceAccumulator + (int) $mipDetail->in_qty - (int) $mipDetail->out_qty;

                $mipDetail->update(['balance' => $balanceAccumulator]);
            }

            $mipHeader->update([
                'total_in' => $totalIn,
                'total_out' => $totalOut
            ]);

            $countMIP++;

            // ==========================================
            // C. SINKRONISASI MONITORING FINISH GOODS
            // ==========================================
            $fgHeader = MonitoringFGHeader::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $rekap->customer,
                    'project' => $project,
                    'part_number' => $rekap->part_number,
                ],
                [
                    'part_name' => $rekap->models,
                    'stock_awal' => (int) ($rekap->stock_awal_fg ?? 0),
                    'total_po' => (int) ($rekap->total_qty_bulan_ini ?? 0),
                    'level_min' => (int) ($level->min ?? 0),
                    'level_safety' => (int) ($level->safety_fg ?? 0),
                    'level_max' => (int) ($level->max ?? 0),
                ]
            );

            // Inisialisasi Detail FG
            $balanceAccumulator = (int) $fgHeader->stock_awal;
            $totalInFG = 0;
            $totalOutFG = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $fgDetail = MonitoringFGDetail::firstOrCreate(
                    [
                        'fg_header_id' => $fgHeader->id,
                        'tanggal' => $d
                    ],
                    [
                        'in_qty_d' => 0,
                        'in_qty_n' => 0,
                        'out_qty_d' => 0,
                        'out_qty_n' => 0,
                        'balance_d' => $balanceAccumulator,
                        'balance_n' => $balanceAccumulator,
                    ]
                );

                $inD = (int) $fgDetail->in_qty_d;
                $inN = (int) $fgDetail->in_qty_n;
                $outD = (int) $fgDetail->out_qty_d;
                $outN = (int) $fgDetail->out_qty_n;

                $totalInFG += ($inD + $inN);
                $totalOutFG += ($outD + $outN);

                $balanceD = $balanceAccumulator + $inD - $outD;
                $balanceN = $balanceD + $inN - $outN;

                $fgDetail->update([
                    'balance_d' => $balanceD,
                    'balance_n' => $balanceN
                ]);

                $balanceAccumulator = $balanceN;
            }

            $fgHeader->update([
                'total_in' => $totalInFG,
                'total_out' => $totalOutFG,
            ]);

            $countFG++;
        }

        $this->info("Sinkronisasi Selesai!");
        $this->info("  - Sub Assy: {$countSubAssy} header berhasil disinkronkan.");
        $this->info("  - MIP: {$countMIP} header berhasil disinkronkan.");
        $this->info("  - Finish Goods: {$countFG} header berhasil disinkronkan.");

        return 0;
    }

    /**
     * Normalisasi key untuk matching Level Stok Detail
     */
    private function normalizeKey(?string $customer, ?string $project, ?string $partNumber): string
    {
        $cust = strtoupper(str_replace([' ', '-', '_'], '', $customer ?? ''));
        $proj = strtoupper(str_replace([' ', '-', '_'], '', $project ?? ''));
        $pn = strtoupper(str_replace([' ', '-', '_'], '', $partNumber ?? ''));

        return "{$cust}|{$proj}|{$pn}";
    }
}
