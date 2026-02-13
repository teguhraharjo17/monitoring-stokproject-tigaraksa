<?php

namespace App\Http\Controllers\TV;

use App\Http\Controllers\Controller;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringMIPHeader;
use App\Models\SubAssy;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $isCurrentMonth = ($today->year === $tahun && $today->month === $bulan);
        $todayDay = $isCurrentMonth ? (int)$today->day : null;

        $query = SubAssy::query()
            ->ofBulanTahun($bulan, $tahun)
            ->with('details')
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number');

        if (!empty($customer)) {
            $query->where('customer', $customer);
        }

        $rows = $query->get()->map(function (SubAssy $item) use ($daysInMonth) {
            $map = [
                'spk' => [],
                'produksi' => [],
            ];

            foreach ($item->details as $d) {
                $day = (int)$d->tanggal;
                $tipe = strtolower((string)$d->tipe);
                if (!isset($map[$tipe])) continue;
                $map[$tipe][$day] = (int)$d->jumlah;
            }

            $spkDays = [];
            $prodDays = [];
            $wipDays = [];

            $totalSpk = 0;
            $totalProd = 0;

            $wipPrev = (int)($item->wip_sebelumnya ?? 0);

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $spk = (int)($map['spk'][$i] ?? 0);
                $prod = (int)($map['produksi'][$i] ?? 0);

                $totalSpk += $spk;
                $totalProd += $prod;

                $wip = $wipPrev + $spk - $prod;
                $wipPrev = $wip;

                $spkDays[$i] = $spk;
                $prodDays[$i] = $prod;
                $wipDays[$i] = $wip;
            }

            $wipAkhir = $wipDays[$daysInMonth] ?? 0;
            $produktivitas = $totalSpk > 0 ? (int)ceil(($totalProd / $totalSpk) * 100) : 0;

            return [
                'customer' => $item->customer,
                'project' => $item->project,
                'part_number' => $item->part_number,
                'part_name' => $item->part_name,
                'total_po' => null,
                'wip_sebelumnya' => (int)($item->wip_sebelumnya ?? 0),
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
        });

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

    public function mipData(Request $request)
    {
        $bulan = (int)($request->get('bulan') ?? now()->month);
        $tahun = (int)($request->get('tahun') ?? now()->year);
        $customer = $request->get('customer');

        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $today = now();
        $isCurrentMonth = ($today->year === $tahun && $today->month === $bulan);
        $todayDay = $isCurrentMonth ? (int)$today->day : null;

        $query = MonitoringMIPHeader::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->with('details')
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number');

        if (!empty($customer)) {
            $query->where('customer', $customer);
        }

        $rows = $query->get()->map(function (MonitoringMIPHeader $h) use ($daysInMonth) {
            $inDays = [];
            $outDays = [];
            $balDays = [];

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $inDays[$i] = 0;
                $outDays[$i] = 0;
                $balDays[$i] = 0;
            }

            foreach ($h->details as $d) {
                $day = (int)$d->tanggal;
                if ($day < 1 || $day > $daysInMonth) continue;

                $inDays[$day] = (int)($d->in_qty ?? 0);
                $outDays[$day] = (int)($d->out_qty ?? 0);
            }

            $stockAwal = (int)($h->stock_awal ?? 0);
            $running = $stockAwal;

            $totalIn = 0;
            $totalOut = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $in = (int)($inDays[$i] ?? 0);
                $out = (int)($outDays[$i] ?? 0);

                $totalIn += $in;
                $totalOut += $out;

                $running = $running + $in - $out;
                $balDays[$i] = $running;
            }

            $balanceAkhir = $balDays[$daysInMonth] ?? $stockAwal;

            return [
                'customer' => $h->customer,
                'project' => $h->project,
                'part_number' => $h->part_number,
                'part_name' => $h->part_name,

                'stock_awal' => $stockAwal,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'balance_akhir' => $balanceAkhir,

                'level_min' => (int)($h->level_min ?? 0),
                'level_safety' => (int)($h->level_safety ?? 0),
                'level_max' => (int)($h->level_max ?? 0),

                'days' => [
                    'in' => $inDays,
                    'out' => $outDays,
                    'balance' => $balDays,
                ],
            ];
        });

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

    public function fgData(Request $request)
    {
        $bulan = (int)($request->get('bulan') ?? now()->month);
        $tahun = (int)($request->get('tahun') ?? now()->year);
        $customer = $request->get('customer');

        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $today = now();
        $isCurrentMonth = ($today->year === $tahun && $today->month === $bulan);
        $todayDay = $isCurrentMonth ? (int)$today->day : null;

        $query = MonitoringFGHeader::query()
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->with('details')
            ->orderBy('customer')
            ->orderBy('project')
            ->orderBy('part_number');

        if (!empty($customer)) {
            $query->where('customer', $customer);
        }

        $rows = $query->get()->map(function (MonitoringFGHeader $h) use ($daysInMonth) {
            $inD = $outD = $balD = [];
            $inN = $outN = $balN = [];

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $inD[$i] = 0; $outD[$i] = 0; $balD[$i] = 0;
                $inN[$i] = 0; $outN[$i] = 0; $balN[$i] = 0;
            }

            foreach ($h->details as $d) {
                $day = (int)$d->tanggal;
                if ($day < 1 || $day > $daysInMonth) continue;

                $inD[$day] = (int)($d->in_qty_d ?? 0);
                $outD[$day] = (int)($d->out_qty_d ?? 0);
                $inN[$day] = (int)($d->in_qty_n ?? 0);
                $outN[$day] = (int)($d->out_qty_n ?? 0);
            }

            $stockAwal = (int)($h->stock_awal ?? 0);
            $running = $stockAwal;

            $totalIn = 0;
            $totalOut = 0;

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dIn = $inD[$i] ?? 0;
                $dOut = $outD[$i] ?? 0;
                $nIn = $inN[$i] ?? 0;
                $nOut = $outN[$i] ?? 0;

                $totalIn += ($dIn + $nIn);
                $totalOut += ($dOut + $nOut);

                $balanceD = $running + $dIn - $dOut;
                $balanceN = $balanceD + $nIn - $nOut;

                $balD[$i] = $balanceD;
                $balN[$i] = $balanceN;

                $running = $balanceN;
            }

            $stockOnHand = $balN[$daysInMonth] ?? $stockAwal;

            $totalPO = (int)($h->total_po ?? 0);
            $advanceDelivery = (int)($h->advance_delivery ?? 0);

            $outstanding = max(0, $totalPO - $advanceDelivery - $totalOut);
            $percentage = $totalPO > 0 ? round(($totalOut / $totalPO) * 100, 2) : 0.0;

            $levelMin = (int)($h->level_min ?? 0);
            $levelMax = (int)($h->level_max ?? 0);

            $statusStock = 'Aman';
            if ($stockOnHand <= $levelMin) $statusStock = 'Problem';
            elseif ($stockOnHand > $levelMax) $statusStock = 'Over';

            return [
                'customer' => $h->customer,
                'project' => $h->project,
                'part_number' => $h->part_number,
                'part_name' => $h->part_name,

                'total_po' => $totalPO,
                'advance_delivery' => $advanceDelivery,
                'outstanding' => $outstanding,
                'percentage' => $percentage,

                'stock_awal' => $stockAwal,
                'total_in' => $totalIn,
                'total_out' => $totalOut,

                'level_min' => (int)($h->level_min ?? 0),
                'level_safety' => (int)($h->level_safety ?? 0),
                'level_max' => (int)($h->level_max ?? 0),

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
        });

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
}
