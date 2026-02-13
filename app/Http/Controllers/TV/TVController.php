<?php

namespace App\Http\Controllers\TV;

use App\Http\Controllers\Controller;
use App\Models\RekapData;
use App\Models\SubAssy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TVController extends Controller
{
    public function index()
    {
        return view('pages.tv.index');
    }

    public function subAssyData(Request $request)
    {
        $bulan = (int)($request->get('bulan') ?? now()->month);
        $tahun = (int)($request->get('tahun') ?? now()->year);
        $customer = $request->get('customer');

        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $today = now();
        $todayDay = ($today->year === $tahun && $today->month === $bulan) ? (int)$today->day : null;

        $spkMap = $this->buildSpkMapFromApi($bulan, $tahun);

        $rekapData = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer, fn ($q) => $q->where('customer', $customer))
            ->orderBy('customer')
            ->orderBy('kode_project')
            ->orderBy('part_number')
            ->get();

        $subAssies = SubAssy::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($s) => $s->customer . '|' . $s->project . '|' . $s->part_number . '|' . $s->part_name);

        $rows = [];

        foreach ($rekapData as $rekap) {
            $key = $rekap->customer . '|' . $rekap->kode_project . '|' . $rekap->part_number . '|' . $rekap->models;
            $subAssy = $subAssies[$key] ?? null;

            $spkDays = array_fill(1, $daysInMonth, 0);
            $prodDays = array_fill(1, $daysInMonth, 0);

            if ($subAssy) {
                foreach ($subAssy->details as $detail) {
                    $day = (int)$detail->tanggal;
                    if ($day < 1 || $day > $daysInMonth) {
                        continue;
                    }

                    if ($detail->tipe === 'Produksi') {
                        $prodDays[$day] = (int)($detail->jumlah ?? 0);
                    }
                }
            }

            $apiKey = $rekap->customer . '|' . $rekap->part_number;
            if (isset($spkMap[$apiKey])) {
                foreach ($spkMap[$apiKey] as $day => $qty) {
                    $day = (int)$day;
                    if ($day < 1 || $day > $daysInMonth) {
                        continue;
                    }
                    $spkDays[$day] = (int)$qty;
                }
            }

            $wipSebelumnya = (int)($subAssy->wip_sebelumnya ?? 0);

            $totalSpk = 0;
            $totalProd = 0;
            $wipDays = [];

            $wipPrev = $wipSebelumnya;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $spk = (int)$spkDays[$i];
                $prod = (int)$prodDays[$i];

                $totalSpk += $spk;
                $totalProd += $prod;

                $wip = $wipPrev + $spk - $prod;
                $wipPrev = $wip;

                $wipDays[$i] = $wip;
            }

            $wipAkhir = $wipDays[$daysInMonth] ?? $wipSebelumnya;
            $produktivitas = $totalSpk > 0 ? (int)ceil(($totalProd / $totalSpk) * 100) : 0;

            $rows[] = [
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
                'part_name' => $rekap->models,
                'total_po' => (int)($rekap->total_qty_bulan_ini ?? 0),

                'wip_sebelumnya' => $wipSebelumnya,
                'total_spk' => $totalSpk,
                'total_produksi' => $totalProd,
                'wip_akhir' => $wipAkhir,
                'produktivitas' => $produktivitas,

                'days' => [
                    'spk' => $spkDays,
                    'produksi' => $prodDays,
                    'wip' => $wipDays,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'daysInMonth' => $daysInMonth,
            'todayDay' => $todayDay,
            'rows' => $rows,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function buildSpkMapFromApi(int $bulan, int $tahun): array
    {
        $spkData = [];

        try {
            $response = Http::timeout(10)->get('http://192.168.0.8:8080/sistem-spk-tigaraksa/api/spk-data');
            if ($response->successful()) {
                $spkData = $response->json();
            }
        } catch (\Exception $e) {
            Log::error('TV SubAssy: Gagal fetch SPK: ' . $e->getMessage());
            return [];
        }

        $spkMap = [];

        foreach ($spkData as $spk) {
            if (empty($spk['tanggal_produksi'])) {
                continue;
            }

            try {
                $tanggal = Carbon::parse($spk['tanggal_produksi']);
            } catch (\Exception $e) {
                continue;
            }

            if ((int)$tanggal->month !== $bulan || (int)$tanggal->year !== $tahun) {
                continue;
            }

            $day = (int)$tanggal->day;

            foreach (($spk['details'] ?? []) as $detail) {
                $cust = (string)($detail['customer'] ?? '');
                $pn = (string)($detail['part_number'] ?? '');
                if ($cust === '' || $pn === '') {
                    continue;
                }

                $key = $cust . '|' . $pn;
                $qty = (int)($detail['qty_order_prod'] ?? 0);

                $spkMap[$key][$day] = (int)($spkMap[$key][$day] ?? 0) + $qty;
            }
        }

        return $spkMap;
    }
}
