<?php

namespace App\Http\Controllers\MonitoringSubAssy;

use App\Http\Controllers\Controller;
use App\Models\SubAssy;
use App\Models\RekapData;
use App\Models\SubAssyDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exports\SubAssyExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MonitoringSubAssyController extends Controller
{
    public function index()
    {
        return view('pages.monitoringsubassy.redesign');
    }

    public function data(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $customer = $request->input('customer');

        if ($request->boolean('only_customer')) {
            $rekapCustomer = RekapData::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->pluck('customer');

            $subAssyCustomer = SubAssy::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->pluck('customer');

            $customers = $rekapCustomer
                ->merge($subAssyCustomer)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->map(fn ($c) => ['customer' => $c]);

            return response()->json([
                'data' => $customers
            ]);
        }

        $forceRefresh = $request->boolean('force_refresh');
        $spkMap = $this->getSpkMap($bulan, $tahun, $forceRefresh);

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

        $subAssies = SubAssy::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($s) => $s->customer . '|' . $s->project . '|' . $s->part_number . '|' . $s->part_name);

        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
        $data = [];

        foreach ($rekapData as $rekap) {
            $key = $rekap->customer . '|' . $rekap->kode_project . '|' . $rekap->part_number . '|' . $rekap->models;
            $subAssy = $subAssies[$key] ?? null;

            $row = [
                'id' => $subAssy->id ?? null,
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
                'part_name' => $rekap->models,
                'total_po' => (int) ($rekap->total_qty_bulan_ini ?? 0),
                'wip_sebelumnya' => (int) ($subAssy && $subAssy->wip_sebelumnya != 0 ? $subAssy->wip_sebelumnya : ($rekap->wip_spk_sa ?? 0)),
                'total_spk' => (int) ($subAssy->total_spk ?? 0),
                'total_produksi' => (int) ($subAssy->total_produksi ?? 0),
                'wip_akhir' => (int) ($subAssy->wip_akhir ?? 0),
                'produktivitas' => (int) ($subAssy->produktivitas ?? 0),
            ];

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $row["spk_hari_{$i}"] = 0;
                $row["produksi_hari_{$i}"] = 0;
                $row["wip_hari_{$i}"] = 0;
            }

            if ($subAssy) {
                foreach ($subAssy->details as $detail) {
                    $tipe = strtolower($detail->tipe);
                    $day = (int) $detail->tanggal;

                    if ($day < 1 || $day > $jumlahHari) {
                        continue;
                    }

                    $keyHari = "{$tipe}_hari_{$day}";
                    $row[$keyHari] = (int) ($detail->jumlah ?? 0);
                }
            }

            $apiKey = $rekap->customer . '|' . $rekap->part_number;
            if (isset($spkMap[$apiKey])) {
                foreach ($spkMap[$apiKey] as $day => $jumlah) {
                    if ($day >= 1 && $day <= $jumlahHari) {
                        $row["spk_hari_{$day}"] = (int) $jumlah;
                    }
                }
            }

            $totalSPK = 0;
            $totalProduksi = 0;
            $wipSebelum = (int) $row['wip_sebelumnya'];

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $spk = (int) ($row["spk_hari_{$i}"] ?? 0);
                $produksi = (int) ($row["produksi_hari_{$i}"] ?? 0);

                $totalSPK += $spk;
                $totalProduksi += $produksi;

                $wip = $wipSebelum + $spk - $produksi;
                $row["wip_hari_{$i}"] = $wip;
                $wipSebelum = $wip;
            }

            $row['total_spk'] = $totalSPK;
            $row['total_produksi'] = $totalProduksi;
            $row['wip_akhir'] = $wipSebelum; 
            $row['produktivitas'] = $totalSPK > 0 ? (int) ceil(($totalProduksi / $totalSPK) * 100) : 0;

            $data[] = $row;
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
            'customer' => 'required|string',
            'project' => 'nullable|string',
            'part_number' => 'required|string',
            'part_name' => 'required|string',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $totalSPK = (int) ($request->total_spk ?? 0);
        $totalProduksi = (int) ($request->total_produksi ?? 0);
        $wipSebelumnya = (int) ($request->input('wip_sebelumnya', 0));
        
        $wipAkhir = $wipSebelumnya + $totalSPK - $totalProduksi;
        $produktivitas = $totalSPK > 0 ? (int) ceil(($totalProduksi / $totalSPK) * 100) : 0;

        return DB::transaction(function() use ($request, $bulan, $tahun, $wipSebelumnya, $totalSPK, $totalProduksi, $wipAkhir, $produktivitas, $jumlahHari) {
            $subAssy = SubAssy::updateOrCreate(
                [
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $request->customer,
                    'project' => $request->project,
                    'part_number' => $request->part_number,
                    'part_name' => $request->part_name,
                ],
                [
                    'wip_sebelumnya' => $wipSebelumnya,
                    'total_spk' => $totalSPK,
                    'total_produksi' => $totalProduksi,
                    'wip_akhir' => $wipAkhir,
                    'produktivitas' => $produktivitas,
                ]
            );

            SubAssyDetail::where('sub_assy_id', $subAssy->id)->delete();

            $details = [];
            $now = now();

            foreach (['SPK', 'Produksi', 'WIP'] as $tipe) {
                $prefix = strtolower($tipe);
                for ($i = 1; $i <= $jumlahHari; $i++) {
                    $jumlah = (int) $request->input("{$prefix}_hari_{$i}", 0);
                    $details[] = [
                        'sub_assy_id' => $subAssy->id,
                        'tanggal' => $i,
                        'tipe' => $tipe,
                        'jumlah' => $jumlah,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($details)) {
                SubAssyDetail::insert($details);
            }

            // --- OTOMATISASI WIP + SPK SA REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
            try {
                $nextMonth = $bulan == 12 ? 1 : $bulan + 1;
                $nextYear = $bulan == 12 ? $tahun + 1 : $tahun;

                RekapData::updateOrCreate(
                    [
                        'bulan' => $nextMonth,
                        'tahun' => $nextYear,
                        'customer' => $request->customer,
                        'kode_project' => $request->project,
                        'part_number' => $request->part_number,
                    ],
                    [
                        'models' => $request->part_name,
                        'wip_spk_sa' => $wipAkhir // WIP Akhir bulan ini jadi WIP bulan depan
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Gagal sinkronisasi WIP Rekap Data bulan depan', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan.',
                'id' => $subAssy->id,
            ]);
        });
    }

    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
        ]);

        $bulan = $request->bulan;
        $tahun = $request->tahun;

        return Excel::download(
            new SubAssyExport($bulan, $tahun),
            "Monitoring_SubAssy_{$bulan}_{$tahun}.xlsx"
        );
    }

    /**
     * Mengambil SPK Map dengan sistem caching dan fallback Last Known Good.
     */
    private function getSpkMap($bulan, $tahun, $forceRefresh = false)
    {
        $cacheKeyMap = "spk_map_{$bulan}_{$tahun}";
        
        if (!$forceRefresh && Cache::has($cacheKeyMap)) {
            return Cache::get($cacheKeyMap);
        }

        $spkData = [];
        $cacheKeyRaw = 'spk_data_raw';
        $cacheKeyLastGood = 'spk_data_last_good';

        // 1. Ambil data mentah (raw)
        if (!$forceRefresh && Cache::has($cacheKeyRaw)) {
            $spkData = Cache::get($cacheKeyRaw);
        } else {
            try {
                $apiUrl = config('services.spk_api.url', 'http://192.168.0.8:8080/sistem-spk-tigaraksa/api/spk-data');
                $response = Http::timeout(15)->get($apiUrl);
                
                if ($response->successful()) {
                    $spkData = $response->json();
                    Cache::put($cacheKeyRaw, $spkData, 600); // 10 menit
                    Cache::forever($cacheKeyLastGood, $spkData); // Simpan sebagai Last Known Good
                } else {
                    $spkData = Cache::get($cacheKeyLastGood, []);
                    Log::warning('SPK API returned error, using Last Known Good data.');
                }
            } catch (\Throwable $e) {
                $spkData = Cache::get($cacheKeyLastGood, []);
                Log::error('Gagal fetch SPK SubAssy, using Last Known Good data', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 2. Proses data mentah menjadi map yang siap pakai
        $spkMap = [];
        foreach ($spkData as $spk) {
            try {
                $tanggal = Carbon::parse($spk['tanggal_produksi']);
                if ($tanggal->month !== $bulan || $tanggal->year !== $tahun) {
                    continue;
                }

                $tgl = $tanggal->day;
                foreach (($spk['details'] ?? []) as $detail) {
                    $key = ($detail['customer'] ?? '') . '|' . ($detail['part_number'] ?? '');
                    $spkMap[$key][$tgl] = ($spkMap[$key][$tgl] ?? 0) + (int) ($detail['qty_order_prod'] ?? 0);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // 3. Simpan ke cache map (per bulan/tahun)
        Cache::put($cacheKeyMap, $spkMap, 600); // 10 menit

        return $spkMap;
    }
}
