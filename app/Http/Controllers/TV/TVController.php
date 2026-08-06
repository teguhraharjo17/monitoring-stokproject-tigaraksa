<?php

namespace App\Http\Controllers\TV;

use App\Http\Controllers\Controller;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringMIPHeader;
use App\Models\RekapData;
use App\Models\SubAssy;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TVController extends Controller
{
    public function index()
    {
        return view('pages.tv.index');
    }

    public function subAssyData(Request $request): JsonResponse
    {
        [$bulan, $tahun, $customer, $daysInMonth, $todayDay] = $this->resolvePeriodAndFilter($request);
        $rekapMap = $this->buildRekapMap($bulan, $tahun, $customer);

        $rows = SubAssy::query()
            ->select([
                'id',
                'customer',
                'project',
                'part_number',
                'part_name',
                'wip_sebelumnya',
                'total_spk',
                'total_produksi',
                'wip_akhir',
                'produktivitas',
                'bulan',
                'tahun',
            ])
            ->ofBulanTahun($bulan, $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
                $q->where('customer', $customer);
            })
            ->with([
                'details' => function ($q) {
                    $q->select([
                        'id',
                        'sub_assy_id',
                        'tanggal',
                        'tipe',
                        'jumlah',
                    ])->orderBy('tanggal')->orderBy('id');
                }
            ])
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number')
            ->get()
            ->map(function (SubAssy $item) use ($daysInMonth, $rekapMap) {
                return $this->transformSubAssyRow($item, $daysInMonth, $rekapMap);
            })
            ->values();

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
        $rekapMap = $this->buildRekapMap($bulan, $tahun, $customer);

        $rows = MonitoringMIPHeader::query()
            ->select([
                'id',
                'customer',
                'project',
                'part_number',
                'part_name',
                'stock_awal',
                'level_min',
                'level_safety',
                'level_max',
                'bulan',
                'tahun',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
                $q->where('customer', $customer);
            })
            ->with([
                'details' => function ($q) {
                    $q->orderBy('tanggal')->orderBy('id');
                }
            ])
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number')
            ->get()
            ->map(function (MonitoringMIPHeader $item) use ($daysInMonth, $rekapMap) {
                return $this->transformMIPRow($item, $daysInMonth, $rekapMap);
            })
            ->values();

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
        $rekapMap = $this->buildRekapMap($bulan, $tahun, $customer);

        $rows = MonitoringFGHeader::query()
            ->select([
                'id',
                'customer',
                'project',
                'part_number',
                'part_name',
                'stock_awal',
                'total_in',
                'total_out',
                'level_min',
                'level_safety',
                'level_max',
                'advance_delivery',
                'bulan',
                'tahun',
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer !== '', function ($q) use ($customer) {
                $q->where('customer', $customer);
            })
            ->with([
                'details' => function ($q) {
                    $q->orderBy('tanggal')->orderBy('id');
                }
            ])
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number')
            ->get()
            ->map(function (MonitoringFGHeader $item) use ($daysInMonth, $rekapMap) {
                return $this->transformFGRow($item, $daysInMonth, $rekapMap);
            })
            ->values();

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

    private function transformFGRow(MonitoringFGHeader $item, int $daysInMonth, array $rekapMap): array
    {
        $rekap = $this->getRekapRow($rekapMap, $item->customer, $item->project, $item->part_number);

        $inD = $this->blankDayMap($daysInMonth);
        $outD = $this->blankDayMap($daysInMonth);
        $balD = $this->blankDayMap($daysInMonth);

        $inN = $this->blankDayMap($daysInMonth);
        $outN = $this->blankDayMap($daysInMonth);
        $balN = $this->blankDayMap($daysInMonth);

        foreach ($item->details as $detail) {
            $day = (int) ($detail->tanggal ?? 0);
            if ($day < 1 || $day > $daysInMonth) {
                continue;
            }

            $inD[$day] += (int) ($detail->in_qty_d ?? 0);
            $outD[$day] += (int) ($detail->out_qty_d ?? 0);
            $inN[$day] += (int) ($detail->in_qty_n ?? 0);
            $outN[$day] += (int) ($detail->out_qty_n ?? 0);
        }

        $stockAwal = (int) ($rekap['stock_awal_fg'] ?? $item->stock_awal ?? 0);
        $running = $stockAwal;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $balanceD = $running + (int) $inD[$i] - (int) $outD[$i];
            $balanceN = $balanceD + (int) $inN[$i] - (int) $outN[$i];

            $balD[$i] = $balanceD;
            $balN[$i] = $balanceN;
            $running = $balanceN;
        }

        $totalIn = array_sum($inD) + array_sum($inN);
        $totalOut = array_sum($outD) + array_sum($outN);
        $stockOnHand = $balN[$daysInMonth] ?? $stockAwal;

        $totalPo = (int) ($rekap['total_po'] ?? 0);
        $advanceDelivery = (int) ($item->advance_delivery ?? 0);
        $outstanding = max(0, $totalPo - $advanceDelivery - $totalOut);
        $percentage = $totalPo > 0
            ? (float) round(($totalOut / $totalPo) * 100, 2)
            : 0.0;

        $levelMin = (int) ($item->level_min ?? 0);
        $levelMax = (int) ($item->level_max ?? 0);

        $statusStock = 'Aman';
        if ($stockOnHand <= $levelMin) {
            $statusStock = 'Problem';
        } elseif ($stockOnHand > $levelMax) {
            $statusStock = 'Over';
        }

        return [
            'customer' => (string) ($item->customer ?? ''),
            'project' => (string) ($item->project ?? ''),
            'part_number' => (string) ($item->part_number ?? ''),
            'part_name' => (string) ($item->part_name ?? ''),

            'total_po' => $totalPo,
            'advance_delivery' => $advanceDelivery,
            'outstanding' => $outstanding,
            'percentage' => $percentage,

            'stock_awal' => $stockAwal,
            'total_in' => $totalIn,
            'total_out' => $totalOut,

            'level_min' => $levelMin,
            'level_safety' => (int) ($item->level_safety ?? 0),
            'level_max' => (int) ($item->level_max ?? 0),

            'stock_on_hand' => $stockOnHand,
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
}