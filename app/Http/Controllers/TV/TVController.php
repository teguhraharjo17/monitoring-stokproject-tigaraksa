<?php

namespace App\Http\Controllers\TV;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MonitoringSubAssy\MonitoringSubAssyController;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringMIPHeader;
use App\Models\RekapData;
use App\Models\SubAssy;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TVController extends Controller
{
    public function index()
    {
        return view('pages.tv.index');
    }

    private function normalizeKey($str)
    {
        return strtoupper(str_replace([' ', '-', '_'], '', $str ?? ''));
    }

    public function subAssyData(Request $request): JsonResponse
    {
        [$bulan, $tahun, $customer, $daysInMonth, $todayDay] = $this->resolvePeriodAndFilter($request);

        // 1. Rekap Data
        $rekapData = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
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

        $subAssyController = app(MonitoringSubAssyController::class);
        $spkMap = $subAssyController->getSpkMap($bulan, $tahun);

        $subAssies = SubAssy::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($s) => $this->normalizeKey($s->customer) . '|' . $this->normalizeKey($s->project) . '|' . $this->normalizeKey($s->part_number));

        $prevBulan = $bulan == 1 ? 12 : $bulan - 1;
        $prevTahun = $bulan == 1 ? $tahun - 1 : $tahun;
        $prevSubAssies = SubAssy::where('bulan', $prevBulan)
            ->where('tahun', $prevTahun)
            ->get()
            ->keyBy(fn ($s) => $this->normalizeKey($s->part_number) . '|' . $this->normalizeKey($s->part_name));

        $aggregatedRekap = [];
        foreach ($rekapData as $rekap) {
            $key = $this->normalizeKey($rekap->customer) . '|' . $this->normalizeKey($rekap->kode_project) . '|' . $this->normalizeKey($rekap->part_number) . '|' . $this->normalizeKey($rekap->models);
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $rekap;
            } else {
                $aggregatedRekap[$key]->total_qty_bulan_ini += $rekap->total_qty_bulan_ini;
                $aggregatedRekap[$key]->wip_spk_sa = max($aggregatedRekap[$key]->wip_spk_sa, $rekap->wip_spk_sa);
            }
        }

        $rows = [];
        foreach ($aggregatedRekap as $rekap) {
            $key = $this->normalizeKey($rekap->customer) . '|' . $this->normalizeKey($rekap->kode_project) . '|' . $this->normalizeKey($rekap->part_number);
            $subAssy = $subAssies[$key] ?? null;

            $wipSebelumnya = 0;
            if ($subAssy && $subAssy->wip_sebelumnya != 0) {
                $wipSebelumnya = (int) $subAssy->wip_sebelumnya;
            } else {
                $prevKey = $this->normalizeKey($rekap->part_number) . '|' . $this->normalizeKey($rekap->models);
                $prevSubAssy = $prevSubAssies[$prevKey] ?? null;
                $wipSebelumnya = (int) ($prevSubAssy ? $prevSubAssy->wip_akhir : ($rekap->wip_spk_sa ?? 0));
            }

            $apiKeyNormalized = $this->normalizeKey($rekap->customer) . '|' . $this->normalizeKey($rekap->part_number);
            $localSpk = $spkMap[$apiKeyNormalized] ?? [];

            $prodMap = [];
            if ($subAssy && $subAssy->details) {
                foreach ($subAssy->details as $detail) {
                    if (strtolower($detail->tipe) === 'produksi') {
                        $prodMap[(int) $detail->tanggal] = (int) ($detail->jumlah ?? 0);
                    }
                }
            }

            $spkDays = [];
            $prodDays = [];
            $wipDays = [];

            $totalSPK = 0;
            $totalProduksi = 0;
            $currentWip = $wipSebelumnya;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $spk = (int) ($localSpk[$i] ?? 0);
                $prod = (int) ($prodMap[$i] ?? 0);

                $totalSPK += $spk;
                $totalProduksi += $prod;

                $currentWip = $currentWip + $spk - $prod;

                $spkDays[$i] = $spk;
                $prodDays[$i] = $prod;
                $wipDays[$i] = $currentWip;
            }

            $divider = $wipSebelumnya + $totalSPK;
            $produktivitas = $divider > 0 ? min(999, ceil(($totalProduksi / $divider) * 100)) : 0;

            $rows[] = [
                'id' => $subAssy->id ?? null,
                'customer' => (string) ($rekap->customer ?? ''),
                'project' => (string) ($rekap->kode_project ?? ''),
                'part_number' => (string) ($rekap->part_number ?? ''),
                'part_name' => (string) ($rekap->models ?? ''),
                'total_po' => (int) ($rekap->total_qty_bulan_ini ?? 0),
                'wip_sebelumnya' => $wipSebelumnya,
                'total_spk' => $totalSPK,
                'total_produksi' => $totalProduksi,
                'wip_akhir' => $currentWip,
                'produktivitas' => $produktivitas,
                'days' => [
                    'spk' => $spkDays,
                    'produksi' => $prodDays,
                    'wip' => $wipDays,
                ],
            ];
        }

        return response()->json([
            'success'     => true,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'daysInMonth' => $daysInMonth,
            'todayDay'    => $todayDay,
            'rows'        => $rows,
            'timestamp'   => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function mipData(Request $request): JsonResponse
    {
        [$bulan, $tahun, $customer, $daysInMonth, $todayDay] = $this->resolvePeriodAndFilter($request);

        // 1. Rekap Data
        $rekapListRaw = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
                if (is_array($customer)) {
                    $q->whereIn('customer', $customer);
                } else {
                    $q->where('customer', $customer);
                }
            })
            ->get();

        $aggregatedRekap = [];
        foreach ($rekapListRaw as $item) {
            $key = $this->normalizeKey($item->customer) . '|' . $this->normalizeKey($item->kode_project) . '|' . $this->normalizeKey($item->part_number) . '|' . $this->normalizeKey($item->models);
            if (!isset($aggregatedRekap[$key])) {
                $aggregatedRekap[$key] = $item;
            } else {
                $aggregatedRekap[$key]->total_qty_bulan_ini += $item->total_qty_bulan_ini;
                $aggregatedRekap[$key]->stock_awal_mip = max($aggregatedRekap[$key]->stock_awal_mip, $item->stock_awal_mip);
            }
        }

        // 2. Bulk Load Level Stok
        $levelStokMap = DB::table('level_stok_detail as d')
            ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
            ->where('l.bulan', $bulan)
            ->where('l.tahun', $tahun)
            ->get()
            ->keyBy(fn($l) => $this->normalizeKey($l->customer) . '|' . $this->normalizeKey($l->kode_projek) . '|' . $this->normalizeKey($l->part_number));

        // 3. Bulk Load MIP Headers & Details
        $mipHeaders = MonitoringMIPHeader::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalizeKey($h->customer) . '|' . $this->normalizeKey($h->project) . '|' . $this->normalizeKey($h->part_number));

        // 4. Bulk Load SubAssy (Produksi)
        $subAssyList = SubAssy::with(['details' => function ($q) {
                $q->where('tipe', 'Produksi');
            }])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($s) => $this->normalizeKey($s->customer) . '|' . $this->normalizeKey($s->project) . '|' . $this->normalizeKey($s->part_number));

        $rows = [];
        foreach ($aggregatedRekap as $item) {
            $matchKey = $this->normalizeKey($item->customer) . '|' . $this->normalizeKey($item->kode_project) . '|' . $this->normalizeKey($item->part_number);

            $level = $levelStokMap[$matchKey] ?? null;
            $header = $mipHeaders[$matchKey] ?? null;
            $details = $header ? $header->details->keyBy('tanggal') : collect();

            $subAssy = $subAssyList[$matchKey] ?? null;
            $inList = [];
            if ($subAssy && $subAssy->details) {
                foreach ($subAssy->details as $detail) {
                    $hari = (int) $detail->tanggal;
                    if ($hari >= 1 && $hari <= $daysInMonth) {
                        $inList[$hari] = (int) ($detail->jumlah ?? 0);
                    }
                }
            }

            $stockAwal = (int) ($header->stock_awal ?? ($item->stock_awal_mip ?? 0));

            $inDays = [];
            $outDays = [];
            $balDays = [];

            $balance = $stockAwal;
            $totalInActual = 0;
            $totalOutActual = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $in = (int) ($inList[$i] ?? 0);
                $out = (int) ($details[$i]->out_qty ?? 0);

                $balance = $balance + $in - $out;

                $inDays[$i] = $in;
                $outDays[$i] = $out;
                $balDays[$i] = $balance;

                $totalInActual += $in;
                $totalOutActual += $out;
            }

            $levelMin = (int) ($level->min ?? ($header->level_min ?? 0));
            $levelSafety = (int) ($level->safety_mip ?? ($header->level_safety ?? 0));
            $levelMax = (int) ($level->max ?? ($header->level_max ?? 0));

            $rows[] = [
                'id' => $header->id ?? null,
                'customer' => (string) ($item->customer ?? ''),
                'project' => (string) ($item->kode_project ?? ''),
                'part_number' => (string) ($item->part_number ?? ''),
                'part_name' => (string) ($item->models ?? ''),
                'total_po' => (int) ($item->total_qty_bulan_ini ?? 0),
                'stock_awal' => $stockAwal,
                'total_in' => $totalInActual,
                'total_out' => $totalOutActual,
                'balance_akhir' => $balance,
                'level_min' => $levelMin,
                'level_safety' => $levelSafety,
                'level_max' => $levelMax,
                'days' => [
                    'in' => $inDays,
                    'out' => $outDays,
                    'balance' => $balDays,
                ],
            ];
        }

        return response()->json([
            'success'     => true,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'daysInMonth' => $daysInMonth,
            'todayDay'    => $todayDay,
            'rows'        => $rows,
            'timestamp'   => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function fgData(Request $request): JsonResponse
    {
        [$bulan, $tahun, $customer, $daysInMonth, $todayDay] = $this->resolvePeriodAndFilter($request);

        // 1. Load RekapData & Aggregate duplicates
        $rekapListRaw = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
                if (is_array($customer)) {
                    $q->whereIn('customer', $customer);
                } else {
                    $q->where('customer', $customer);
                }
            })
            ->get();

        $aggregatedRekap = [];
        foreach ($rekapListRaw as $rekap) {
            $key = $this->normalizeText($rekap->customer) . '|' . $this->normalizeText($rekap->kode_project) . '|' . $this->normalizeText($rekap->part_number) . '|' . $this->normalizeText($rekap->models);
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
            ->where('l.bulan', $bulan)
            ->where('l.tahun', $tahun)
            ->get()
            ->keyBy(fn($l) => $this->normalizeText($l->customer) . '|' . $this->normalizeText($l->kode_projek) . '|' . $this->normalizeText($l->part_number));

        // 3. Bulk Load FG Headers & Details
        $fgHeaders = MonitoringFGHeader::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalizeText($h->customer) . '|' . $this->normalizeText($h->project) . '|' . $this->normalizeText($h->part_number));

        // 4. Bulk Load MIP Headers & Details
        $mipHeaders = MonitoringMIPHeader::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($h) => $this->normalizeText($h->customer) . '|' . $this->normalizeText($h->project) . '|' . $this->normalizeText($h->part_number));

        $rows = [];

        foreach ($aggregatedRekap as $rekap) {
            $matchKey = $this->normalizeText($rekap->customer) . '|' . $this->normalizeText($rekap->kode_project) . '|' . $this->normalizeText($rekap->part_number);

            $level = $levelStokMap[$matchKey] ?? null;
            $header = $fgHeaders[$matchKey] ?? null;
            $details = $header ? $header->details->keyBy('tanggal') : collect();

            $mipHeader = $mipHeaders[$matchKey] ?? null;
            $mipDetails = $mipHeader ? $mipHeader->details->keyBy('tanggal') : collect();

            $stockAwal = (int) ($header->stock_awal ?? ($rekap->stock_awal_fg ?? 0));

            $inD = $this->blankDayMap($daysInMonth);
            $outD = $this->blankDayMap($daysInMonth);
            $balD = $this->blankDayMap($daysInMonth);

            $inN = $this->blankDayMap($daysInMonth);
            $outN = $this->blankDayMap($daysInMonth);
            $balN = $this->blankDayMap($daysInMonth);

            $balance = $stockAwal;
            $totalInActual = 0;
            $totalOutActual = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $inValD = (int) ($mipDetails[$i]->out_qty ?? 0);
                $inValN = (int) ($details[$i]->in_qty_n ?? 0);
                $outValD = (int) ($details[$i]->out_qty_d ?? 0);
                $outValN = (int) ($details[$i]->out_qty_n ?? 0);

                $balanceD = $balance + $inValD - $outValD;
                $balanceN = $balanceD + $inValN - $outValN;

                $inD[$i] = $inValD;
                $inN[$i] = $inValN;
                $outD[$i] = $outValD;
                $outN[$i] = $outValN;
                $balD[$i] = $balanceD;
                $balN[$i] = $balanceN;

                $totalInActual += ($inValD + $inValN);
                $totalOutActual += ($outValD + $outValN);
                $balance = $balanceN;
            }

            $advance = (int) ($header->advance_delivery ?? 0);
            $totalPo = (int) ($rekap->total_qty_bulan_ini ?? 0);
            $outstanding = max(0, $totalPo - $advance - $totalOutActual);
            $totalDelivered = $advance + $totalOutActual;
            $percentage = $totalPo > 0 ? (float) round(($totalDelivered / $totalPo) * 100, 2) : 0.0;

            $levelMin = (int) ($level->min ?? ($header->level_min ?? 0));
            $levelSafety = (int) ($level->safety_fg ?? ($header->level_safety ?? 0));
            $levelMax = (int) ($level->max ?? ($header->level_max ?? 0));

            $statusStock = 'Aman';
            if ($balance <= $levelMin) {
                $statusStock = 'Problem';
            } elseif ($balance > $levelMax) {
                $statusStock = 'Over';
            }

            $rows[] = [
                'id' => $header->id ?? null,
                'customer' => (string) ($rekap->customer ?? ''),
                'project' => (string) ($rekap->kode_project ?? ''),
                'part_number' => (string) ($rekap->part_number ?? ''),
                'part_name' => (string) ($rekap->models ?? ''),

                'total_po' => $totalPo,
                'advance_delivery' => $advance,
                'outstanding' => $outstanding,
                'percentage' => $percentage,

                'stock_awal' => $stockAwal,
                'total_in' => $totalInActual,
                'total_out' => $totalOutActual,

                'level_min' => $levelMin,
                'level_safety' => $levelSafety,
                'level_max' => $levelMax,

                'stock_on_hand' => $balance,
                'status_stock' => $statusStock,

                'days' => [
                    'in_d' => $inD,
                    'out_d' => $outD,
                    'bal_d' => $balD,
                    'in_n' => $inN,
                    'out_n' => $outN,
                    'bal_n' => $balN,
                ],
            ];
        }

        return response()->json([
            'success'     => true,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'daysInMonth' => $daysInMonth,
            'todayDay'    => $todayDay,
            'rows'        => $rows,
            'timestamp'   => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function resolvePeriodAndFilter(Request $request): array
    {
        $bulan = (int) ($request->get('bulan') ?? now()->month);
        $tahun = (int) ($request->get('tahun') ?? now()->year);
        $customer = trim((string) $request->get('customer', ''));

        $bulan = max(1, min(12, $bulan));
        $tahun = max(2000, min(2100, $tahun));

        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $today = now();
        $isCurrentMonth = ((int) $today->month === $bulan) && ((int) $today->year === $tahun);
        $todayDay = $isCurrentMonth ? (int) $today->day : null;

        return [$bulan, $tahun, $customer, $daysInMonth, $todayDay];
    }

    private function blankDayMap(int $daysInMonth): array
    {
        $map = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $map[$i] = 0;
        }
        return $map;
    }

    private function normalizeText(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function normalizeSubAssyType(?string $type): ?string
    {
        $value = $this->normalizeText($type);

        return match ($value) {
            'spk' => 'spk',
            'produksi' => 'produksi',
            'wip' => 'wip',
            default => null,
        };
    }

    private function makeRekapKey(string $customer, string $project, string $partNumber): string
    {
        return $customer . '||' . $project . '||' . $partNumber;
    }

    private function buildRekapMap(int $bulan, int $tahun, string $customer = ''): array
    {
        $query = RekapData::query()
            ->select([
                'bulan',
                'tahun',
                'customer',
                'kode_project',
                'part_number',
                'stock_awal_mip',
                'stock_awal_fg',
                'wip_spk_sa',
                'os_bulan_lalu',
                'po_bulan_ini',
                'total_qty_bulan_ini',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($customer !== '') {
            $query->where('customer', $customer);
        }

        $rows = $query->get();
        $map = [];

        foreach ($rows as $row) {
            $customerKey = $this->normalizeText($row->customer);
            $projectKey = $this->normalizeText($row->kode_project);
            $partNumberKey = $this->normalizeText($row->part_number);

            $keyExact = $this->makeRekapKey($customerKey, $projectKey, $partNumberKey);

            if (!isset($map[$keyExact])) {
                $map[$keyExact] = [
                    'total_po' => 0,
                    'total_qty_bulan_ini' => 0,
                    'stock_awal_mip' => 0,
                    'stock_awal_fg' => 0,
                    'wip_spk_sa' => 0,
                    'count' => 0,
                ];
            }

            $map[$keyExact]['total_po'] += (int) ($row->os_bulan_lalu ?? 0) + (int) ($row->po_bulan_ini ?? 0);
            $map[$keyExact]['total_qty_bulan_ini'] += (int) ($row->total_qty_bulan_ini ?? 0);
            $map[$keyExact]['stock_awal_mip'] += (int) ($row->stock_awal_mip ?? 0);
            $map[$keyExact]['stock_awal_fg'] += (int) ($row->stock_awal_fg ?? 0);
            $map[$keyExact]['wip_spk_sa'] += (int) ($row->wip_spk_sa ?? 0);
            $map[$keyExact]['count']++;
        }

        return $map;
    }

    private function getRekapRow(array $rekapMap, ?string $customer, ?string $project, ?string $partNumber): array
    {
        $customerKey = $this->normalizeText($customer);
        $projectKey = $this->normalizeText($project);
        $partNumberKey = $this->normalizeText($partNumber);

        $exactKey = $this->makeRekapKey($customerKey, $projectKey, $partNumberKey);
        $fallbackKey = $this->makeRekapKey($customerKey, '', $partNumberKey);

        if ($projectKey !== '' && isset($rekapMap[$exactKey])) {
            return $rekapMap[$exactKey];
        }

        if (isset($rekapMap[$fallbackKey])) {
            return $rekapMap[$fallbackKey];
        }

        if (isset($rekapMap[$exactKey])) {
            return $rekapMap[$exactKey];
        }

        return [
            'total_po' => 0,
            'total_qty_bulan_ini' => 0,
            'stock_awal_mip' => 0,
            'stock_awal_fg' => 0,
            'wip_spk_sa' => 0,
            'count' => 0,
        ];
    }

    private function transformSubAssyRow(SubAssy $item, int $daysInMonth, array $rekapMap): array
    {
        $rekap = $this->getRekapRow($rekapMap, $item->customer, $item->project, $item->part_number);

        $spkDays = $this->blankDayMap($daysInMonth);
        $prodDays = $this->blankDayMap($daysInMonth);
        $wipExplicitDays = $this->blankDayMap($daysInMonth);
        $hasExplicitWip = false;

        foreach ($item->details as $detail) {
            $day = (int) ($detail->tanggal ?? 0);
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $type = $this->normalizeSubAssyType($detail->tipe);
            if ($type === null) {
                continue;
            }

            $qty = (int) ($detail->jumlah ?? 0);

            if ($type === 'spk') {
                $spkDays[$day] += $qty;
                continue;
            }

            if ($type === 'produksi') {
                $prodDays[$day] += $qty;
                continue;
            }

            if ($type === 'wip') {
                $wipExplicitDays[$day] += $qty;
                $hasExplicitWip = true;
            }
        }

        $wipPrev = (int) ($item->wip_sebelumnya ?? $rekap['wip_spk_sa'] ?? 0);
        $wipDays = $this->blankDayMap($daysInMonth);

        for ($i = 1; $i <= $daysInMonth; $i++) {
            if ($hasExplicitWip) {
                $wipDays[$i] = (int) $wipExplicitDays[$i];
            } else {
                $wipPrev = $wipPrev + (int) $spkDays[$i] - (int) $prodDays[$i];
                $wipDays[$i] = $wipPrev;
            }
        }

        $totalSpk = array_sum($spkDays);
        $totalProduksi = array_sum($prodDays);
        $wipAkhir = $wipDays[$daysInMonth] ?? 0;
        $produktivitas = $totalSpk > 0
            ? (float) round(($totalProduksi / $totalSpk) * 100, 2)
            : 0.0;

        return [
            'customer' => (string) ($item->customer ?? ''),
            'project' => (string) ($item->project ?? ''),
            'part_number' => (string) ($item->part_number ?? ''),
            'part_name' => (string) ($item->part_name ?? ''),

            'total_po' => (int) ($rekap['total_po'] ?? 0),
            'wip_sebelumnya' => (int) ($item->wip_sebelumnya ?? $rekap['wip_spk_sa'] ?? 0),
            'total_spk' => $totalSpk,
            'total_produksi' => $totalProduksi,
            'wip_akhir' => $wipAkhir,
            'produktivitas' => $produktivitas,

            'days' => [
                'spk' => $spkDays,
                'produksi' => $prodDays,
                'wip' => $wipDays,
            ],
        ];
    }

    private function transformMIPRow(MonitoringMIPHeader $item, int $daysInMonth, array $rekapMap): array
    {
        $rekap = $this->getRekapRow($rekapMap, $item->customer, $item->project, $item->part_number);

        $inDays = $this->blankDayMap($daysInMonth);
        $outDays = $this->blankDayMap($daysInMonth);
        $balDays = $this->blankDayMap($daysInMonth);

        foreach ($item->details as $detail) {
            $day = (int) ($detail->tanggal ?? 0);
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $inDays[$day] += (int) ($detail->in_qty ?? 0);
            $outDays[$day] += (int) ($detail->out_qty ?? 0);
        }

        $stockAwal = (int) ($rekap['stock_awal_mip'] ?? $item->stock_awal ?? 0);
        $running = $stockAwal;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $running += (int) $inDays[$i];
            $running -= (int) $outDays[$i];
            $balDays[$i] = $running;
        }

        return [
            'customer' => (string) ($item->customer ?? ''),
            'project' => (string) ($item->project ?? ''),
            'part_number' => (string) ($item->part_number ?? ''),
            'part_name' => (string) ($item->part_name ?? ''),

            'total_po' => (int) ($rekap['total_po'] ?? 0),
            'stock_awal' => $stockAwal,
            'total_in' => array_sum($inDays),
            'total_out' => array_sum($outDays),
            'balance_akhir' => $balDays[$daysInMonth] ?? $stockAwal,

            'level_min' => (int) ($item->level_min ?? 0),
            'level_safety' => (int) ($item->level_safety ?? 0),
            'level_max' => (int) ($item->level_max ?? 0),

            'days' => [
                'in' => $inDays,
                'out' => $outDays,
                'balance' => $balDays,
            ],
        ];
    }
}