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
    private const MAX_STORED_PRODUCTIVITY = 999.99;

    public function index()
    {
        return view('pages.monitoringsubassy.redesign');
    }

    private function normalize($str)
    {
        $str = $str ?? '';
        return strtoupper(str_replace([' ', '-', '_'], '', $str));
    }

    private function rekapKey($customer, $project, $partNumber, $partName): string
    {
        return $this->normalize($customer) . '|'
            . $this->normalize($project) . '|'
            . $this->normalize($partNumber) . '|'
            . $this->normalize($partName);
    }

    private function itemKey($customer, $project, $partNumber): string
    {
        return $this->normalize($customer) . '|'
            . $this->normalize($project) . '|'
            . $this->normalize($partNumber);
    }

    private function spkKey($customer, $partNumber): string
    {
        return $this->normalize($customer) . '|' . $this->normalize($partNumber);
    }

    private function partKey($partNumber, $partName): string
    {
        return $this->normalize($partNumber) . '|' . $this->normalize($partName);
    }

    private function calculateProductivity(int $totalProduksi, int $totalSpk, int $wipSebelumnya = 0): float
    {
        $divider = $wipSebelumnya + $totalSpk;
        if ($divider <= 0) {
            return 0;
        }

        $value = ceil(($totalProduksi / $divider) * 100);

        return min((float) $value, self::MAX_STORED_PRODUCTIVITY);
    }

    public function data(Request $request)
    {
        try {
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
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get()
            ->keyBy(fn ($s) => $this->itemKey($s->customer, $s->project, $s->part_number));

        $prevBulan = $bulan == 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan == 1 ? $tahun - 1 : $tahun;
        $prevSubAssies = SubAssy::where('bulan', $prevBulan)
            ->where('tahun', $prevTahun)
            ->get()
            ->keyBy(fn ($s) => $this->partKey($s->part_number, $s->part_name));

        $aggregatedRekap = [];
        foreach ($rekapData as $rekap) {
            $key = $this->rekapKey($rekap->customer, $rekap->kode_project, $rekap->part_number, $rekap->models);
            
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $rekap;
            } else {
                // Jika duplikat, jumlahkan total PO dan ambil WIP terbesar
                $aggregatedRekap[$key]->total_qty_bulan_ini += $rekap->total_qty_bulan_ini;
                $aggregatedRekap[$key]->wip_spk_sa = max($aggregatedRekap[$key]->wip_spk_sa, $rekap->wip_spk_sa);
            }
        }

        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
        $data = [];
        foreach ($aggregatedRekap as $rekap) {
            $key = $this->itemKey($rekap->customer, $rekap->kode_project, $rekap->part_number);
            $subAssy = $subAssies[$key] ?? null;
            $wipSebelumnya = 0;
            if ($subAssy && $subAssy->wip_sebelumnya != 0) {
                $wipSebelumnya = (int) $subAssy->wip_sebelumnya;
            } else {
                $prevKey = $this->partKey($rekap->part_number, $rekap->models);
                $prevSubAssy = $prevSubAssies[$prevKey] ?? null;
                $wipSebelumnya = (int) ($prevSubAssy ? $prevSubAssy->wip_akhir : ($rekap->wip_spk_sa ?? 0));
            }

            $row = [
                'id' => $subAssy->id ?? null,
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
                'part_name' => $rekap->models,
                'total_po' => (int) ($rekap->total_qty_bulan_ini ?? 0),
                'wip_sebelumnya' => $wipSebelumnya,
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

            $apiKeyNormalized = $this->spkKey($rekap->customer, $rekap->part_number);
            
            if (isset($spkMap[$apiKeyNormalized])) {
                foreach ($spkMap[$apiKeyNormalized] as $day => $jumlah) {
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
            $row['produktivitas'] = $this->calculateProductivity($totalProduksi, $totalSPK, $row['wip_sebelumnya']);

            $data[] = $row;
        }

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            Log::error('MonitoringSubAssyController::data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
            'customer' => 'required|string',
            'project' => 'nullable|string',
            'part_number' => 'required|string',
            'part_name' => 'required|string',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $customer = trim($request->customer);
        $project = trim((string) $request->project);
        $partNumber = trim($request->part_number);
        $partName = trim($request->part_name);

        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        // 1. Ambil data SPK terbaru dari Map (Backend/Cache source of truth)
        $spkMap = $this->getSpkMap($bulan, $tahun);
        $apiKeyNormalized = $this->spkKey($customer, $partNumber);
        $localSpkData = $spkMap[$apiKeyNormalized] ?? [];

        // 2. Hitung ulang total SPK dan Produksi berdasarkan data harian
        $totalSPK = 0;
        $totalProduksi = 0;
        $dailyData = [];

        for ($i = 1; $i <= $jumlahHari; $i++) {
            // SPK utama dari API/cache. Jika API sedang tidak tersedia dan cache kosong,
            // pertahankan angka SPK yang sedang tampil agar update produksi tetap bisa disimpan.
            $spkVal = (int) ($localSpkData[$i] ?? $request->input("spk_hari_{$i}", 0));
            $prodVal = (int) ($request->input("produksi_hari_{$i}", 0));
            
            $totalSPK += $spkVal;
            $totalProduksi += $prodVal;
            
            $dailyData[$i] = [
                'SPK' => $spkVal,
                'Produksi' => $prodVal,
            ];
        }

        $wipSebelumnya = (int) ($request->input('wip_sebelumnya', 0));
        $wipAkhir = $wipSebelumnya + $totalSPK - $totalProduksi;
        $produktivitas = $this->calculateProductivity($totalProduksi, $totalSPK, $wipSebelumnya);

        try {
            return DB::transaction(function() use ($request, $bulan, $tahun, $customer, $project, $partNumber, $partName, $wipSebelumnya, $totalSPK, $totalProduksi, $wipAkhir, $produktivitas, $jumlahHari, $dailyData) {
            $subAssy = null;
            $rowId = (int) $request->input('id', 0);

            if ($rowId > 0) {
                $subAssy = SubAssy::where('id', $rowId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$subAssy) {
                $projectKey = $project ?? '';

                $subAssy = SubAssy::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->whereRaw("TRIM(COALESCE(customer, '')) = ?", [$customer])
                    ->whereRaw("TRIM(COALESCE(project, '')) = ?", [$projectKey])
                    ->whereRaw("TRIM(COALESCE(part_number, '')) = ?", [$partNumber])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
            }

            $oldWipAkhir = $subAssy ? (int) $subAssy->wip_akhir : 0;

            if (!$subAssy) {
                $subAssy = new SubAssy([
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $customer,
                    'project' => $project,
                    'part_number' => $partNumber,
                ]);
            }

            $subAssy->fill([
                'customer' => $customer,
                'project' => $project,
                'part_number' => $partNumber,
                'part_name' => $partName,
                'wip_sebelumnya' => $wipSebelumnya,
                'total_spk' => $totalSPK,
                'total_produksi' => $totalProduksi,
                'wip_akhir' => $wipAkhir,
                'produktivitas' => $produktivitas,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ]);
            $subAssy->save();

            // Sync ke RekapData bulan berjalan
            try {
                $rekapCur = RekapData::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('customer', $customer)
                    ->where('part_number', $partNumber)
                    ->where(function($q) use ($project) {
                        $proj = trim((string)$project);
                        if ($proj === '') {
                            $q->whereNull('kode_project')->orWhere('kode_project', '');
                        } else {
                            $q->where('kode_project', $proj);
                        }
                    })
                    ->first();

                if ($rekapCur) {
                    $rekapCur->update([
                        'models' => $partName,
                        'wip_spk_sa' => $wipSebelumnya
                    ]);
                } else {
                    RekapData::create([
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'customer' => $customer,
                        'kode_project' => $project === '' ? null : $project,
                        'part_number' => $partNumber,
                        'models' => $partName,
                        'wip_spk_sa' => $wipSebelumnya
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal sinkronisasi WIP Rekap Data bulan berjalan', ['error' => $e->getMessage()]);
            }

            // Hapus detail lama dan ganti dengan yang baru (recalculated)
            SubAssyDetail::where('sub_assy_id', $subAssy->id)->delete();

            $details = [];
            $now = now();
            $wipSmt = $wipSebelumnya;

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $spk = $dailyData[$i]['SPK'];
                $prod = $dailyData[$i]['Produksi'];
                $wip = $wipSmt + $spk - $prod;
                $wipSmt = $wip;

                // Insert SPK
                $details[] = [
                    'sub_assy_id' => $subAssy->id,
                    'tanggal' => $i,
                    'tipe' => 'SPK',
                    'jumlah' => $spk,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                // Insert Produksi
                $details[] = [
                    'sub_assy_id' => $subAssy->id,
                    'tanggal' => $i,
                    'tipe' => 'Produksi',
                    'jumlah' => $prod,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                // Insert WIP
                $details[] = [
                    'sub_assy_id' => $subAssy->id,
                    'tanggal' => $i,
                    'tipe' => 'WIP',
                    'jumlah' => $wip,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($details)) {
                SubAssyDetail::insert($details);
            }

            // --- OTOMATISASI WIP + SPK SA REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
            try {
                $nextMonth = $bulan == 12 ? 1 : $bulan + 1;
                $nextYear = $bulan == 12 ? $tahun + 1 : $tahun;

                $rekapNext = RekapData::where('bulan', $nextMonth)
                    ->where('tahun', $nextYear)
                    ->where('customer', $customer)
                    ->where('part_number', $partNumber)
                    ->where(function($q) use ($project) {
                        $proj = trim((string)$project);
                        if ($proj === '') {
                            $q->whereNull('kode_project')->orWhere('kode_project', '');
                        } else {
                            $q->where('kode_project', $proj);
                        }
                    })
                    ->first();

                if ($rekapNext) {
                    $rekapNext->update([
                        'models' => $partName,
                        'wip_spk_sa' => $wipAkhir
                    ]);
                } else {
                    RekapData::create([
                        'bulan' => $nextMonth,
                        'tahun' => $nextYear,
                        'customer' => $customer,
                        'kode_project' => $project === '' ? null : $project,
                        'part_number' => $partNumber,
                        'models' => $partName,
                        'wip_spk_sa' => $wipAkhir
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal sinkronisasi WIP Rekap Data bulan depan', ['error' => $e->getMessage()]);
            }

            // Cascade WIP updates to subsequent months
            $this->cascadeWipUpdate($customer, $project, $partNumber, $bulan, $tahun, $oldWipAkhir, $wipAkhir);

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan.',
                'id' => $subAssy->id,
            ]);
            });
        } catch (\Throwable $e) {
            Log::error('MonitoringSubAssyController::save Error: ' . $e->getMessage(), [
                'customer' => $customer,
                'project' => $project,
                'part_number' => $partNumber,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data gagal disimpan: ' . $e->getMessage(),
            ], 500);
        }
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
        $cacheKeyMap = "spk_map_v2_{$bulan}_{$tahun}";
        
        if (!$forceRefresh && Cache::has($cacheKeyMap)) {
            return Cache::get($cacheKeyMap);
        }

        $spkData = [];
        $cacheKeyRaw = 'spk_data_raw';
        $cacheKeyLastGood = 'spk_data_last_good';

        // 1. Ambil data mentah (raw)
        if (!$forceRefresh && Cache::has($cacheKeyRaw)) {
            $spkData = $this->extractSpkRows(Cache::get($cacheKeyRaw));
        } else {
            try {
                $apiUrl = config('services.spk_api.url', 'http://192.168.0.8:8080/sistem-spk-tigaraksa/api/spk-data');
                $response = Http::timeout(15)->get($apiUrl);
                
                if ($response->successful()) {
                    $spkData = $this->extractSpkRows($response->json());
                    Cache::put($cacheKeyRaw, $spkData, 600); // 10 menit
                    Cache::forever($cacheKeyLastGood, $spkData); // Simpan sebagai Last Known Good
                } else {
                    $spkData = $this->extractSpkRows(Cache::get($cacheKeyLastGood, []));
                    Log::warning('SPK API returned error, using Last Known Good data.');
                }
            } catch (\Throwable $e) {
                $spkData = $this->extractSpkRows(Cache::get($cacheKeyLastGood, []));
                Log::error('Gagal fetch SPK SubAssy, using Last Known Good data', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 2. Proses data mentah menjadi map yang siap pakai
        $spkMap = [];
        $today = Carbon::today();
        foreach ($spkData as $spk) {
            try {
                $tanggal = Carbon::parse($spk['tanggal_produksi']);
                if ($tanggal->month !== $bulan || $tanggal->year !== $tahun) {
                    continue;
                }

                // Jangan ambil data SPK yang tanggal_produksi-nya di masa depan (melebihi hari ini)
                if ($tanggal->greaterThan($today)) {
                    continue;
                }

                $tgl = $tanggal->day;
                foreach (($spk['details'] ?? []) as $detail) {
                    $key = $this->spkKey($detail['customer'] ?? '', $detail['part_number'] ?? '');
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

    private function extractSpkRows($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        foreach (['data', 'results', 'items'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        return $payload;
    }

    private function cascadeWipUpdate($customer, $project, $partNumber, $bulan, $tahun, $oldWipAkhir, $newWipAkhir)
    {
        if ((int)$oldWipAkhir === (int)$newWipAkhir) {
            return;
        }

        $nextBulan = $bulan == 12 ? 1 : $bulan + 1;
        $nextTahun = $bulan == 12 ? $tahun + 1 : $tahun;

        // Find next month's SubAssy record
        $nextSubAssy = SubAssy::where([
            'bulan' => $nextBulan,
            'tahun' => $nextTahun,
            'customer' => $customer,
            'project' => $project,
            'part_number' => $partNumber,
        ])->first();

        if ($nextSubAssy) {
            // If the next month's starting WIP matches the previous month's old ending WIP,
            // or if the next month's starting WIP is 0 (meaning it was probably never properly updated or is just default),
            // we should update it.
            if ((int)$nextSubAssy->wip_sebelumnya === (int)$oldWipAkhir || (int)$nextSubAssy->wip_sebelumnya === 0) {
                $oldNextWipAkhir = $nextSubAssy->wip_akhir;
                $newNextWipSebelumnya = $newWipAkhir;
                
                // Recalculate next month's ending WIP
                $newNextWipAkhir = $newNextWipSebelumnya + $nextSubAssy->total_spk - $nextSubAssy->total_produksi;
                $newNextProductivity = $this->calculateProductivity($nextSubAssy->total_produksi, $nextSubAssy->total_spk, $newNextWipSebelumnya);

                $nextSubAssy->update([
                    'wip_sebelumnya' => $newNextWipSebelumnya,
                    'wip_akhir' => $newNextWipAkhir,
                    'produktivitas' => $newNextProductivity,
                ]);

                // Recalculate daily WIP details
                $this->recalculateDailyWip($nextSubAssy);

                // Cascade recursively to the month after next
                $this->cascadeWipUpdate($customer, $project, $partNumber, $nextBulan, $nextTahun, $oldNextWipAkhir, $newNextWipAkhir);
            }
        }
    }

    private function recalculateDailyWip(SubAssy $subAssy)
    {
        $daysInMonth = Carbon::createFromDate($subAssy->tahun, $subAssy->bulan, 1)->daysInMonth;
        
        $details = $subAssy->details()->get();
        
        $spkDays = [];
        $prodDays = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $spkDays[$d] = 0;
            $prodDays[$d] = 0;
        }

        foreach ($details as $detail) {
            $day = (int) $detail->tanggal;
            $tipe = strtoupper($detail->tipe);
            if ($tipe === 'SPK') {
                $spkDays[$day] = (int) $detail->jumlah;
            } elseif ($tipe === 'PRODUKSI') {
                $prodDays[$day] = (int) $detail->jumlah;
            }
        }

        // Delete existing WIP details to prevent duplicates or clean them up
        $subAssy->details()->where('tipe', 'WIP')->delete();

        $detailsToInsert = [];
        $wipAccumulator = (int) $subAssy->wip_sebelumnya;
        $now = now();
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $wipAccumulator = $wipAccumulator + $spkDays[$d] - $prodDays[$d];
            
            $detailsToInsert[] = [
                'sub_assy_id' => $subAssy->id,
                'tanggal' => $d,
                'tipe' => 'WIP',
                'jumlah' => $wipAccumulator,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($detailsToInsert)) {
            SubAssyDetail::insert($detailsToInsert);
        }
    }
}
