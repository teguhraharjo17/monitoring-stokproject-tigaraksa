<?php

namespace App\Http\Controllers\MonitoringMIP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitoringMIPHeader;
use App\Models\MonitoringMIPDetail;
use App\Models\RekapData;
use App\Models\LevelStok;
use App\Models\SubAssy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Exports\MonitoringMIPExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class MonitoringMIPController extends Controller
{
    public function index()
    {
        return view('pages.monitoringmip.redesign');
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

                $mipCustomer = MonitoringMIPHeader::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->pluck('customer');

                $customers = $rekapCustomer
                    ->merge($mipCustomer)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->map(fn ($c) => ['customer' => $c]);

                return response()->json([
                    'data' => $customers
                ]);
            }

            $rekap = RekapData::where('bulan', $bulan)
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

            if ($rekap->isEmpty()) {
                return response()->json([
                    'data' => []
                ]);
            }

            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

            $levelStok = LevelStok::with('details')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            $headers = MonitoringMIPHeader::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn ($h) => $h->customer . '|' . $h->project . '|' . $h->part_number);

            $allHeaderIds = $headers->pluck('id')->filter()->values();

            $detailsByHeader = MonitoringMIPDetail::whereIn('header_id', $allHeaderIds)
                ->get()
                ->groupBy('header_id')
                ->map(fn ($rows) => $rows->keyBy('tanggal'));

            $subAssyList = SubAssy::with(['details' => function ($q) {
                    $q->where('tipe', 'Produksi');
                }])
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn ($s) => $s->customer . '|' . $s->project . '|' . $s->part_number);

            $data = [];

            foreach ($rekap as $item) {
                $row = [
                    'customer' => $item->customer,
                    'project' => $item->kode_project,
                    'part_number' => $item->part_number,
                    'part_name' => $item->models,
                    'total_po' => (int) ($item->total_qty_bulan_ini ?? 0),
                    'stock_awal' => (int) ($item->stock_awal_mip ?? 0),
                    'total_in' => 0,
                    'total_out' => 0,
                    'level_min' => 0,
                    'level_safety' => 0,
                    'level_max' => 0,
                ];

                if ($levelStok) {
                    $levelDetail = $levelStok->details
                        ->firstWhere('part_number', $item->part_number);

                    if ($levelDetail) {
                        $row['level_min'] = (int) ($levelDetail->min ?? 0);
                        $row['level_safety'] = (int) ($levelDetail->safety_mip ?? 0);
                        $row['level_max'] = (int) ($levelDetail->max ?? 0);
                    }
                }

                $headerKey = $item->customer . '|' . $item->kode_project . '|' . $item->part_number;
                $header = $headers[$headerKey] ?? null;
                $details = $header ? ($detailsByHeader[$header->id] ?? collect()) : collect();

                $subAssyKey = $item->customer . '|' . $item->kode_project . '|' . $item->part_number;
                $subAssy = $subAssyList[$subAssyKey] ?? null;

                $inList = [];
                if ($subAssy && $subAssy->details) {
                    foreach ($subAssy->details as $detail) {
                        $hari = (int) $detail->tanggal;
                        if ($hari >= 1 && $hari <= $jumlahHari) {
                            $inList[$hari] = (int) ($detail->jumlah ?? 0);
                        }
                    }
                }

                $balance = (int) $row['stock_awal'];

                for ($i = 1; $i <= $jumlahHari; $i++) {
                    $in = (int) ($inList[$i] ?? 0);
                    $out = (int) (optional($details->get($i))->out_qty ?? 0);

                    $balance = $balance + $in - $out;

                    $row["in_hari_{$i}"] = $in;
                    $row["out_hari_{$i}"] = $out;
                    $row["balance_hari_{$i}"] = $balance;

                    $row['total_in'] += $in;
                    $row['total_out'] += $out;
                }

                $data[] = $row;
            }

            return response()->json([
                'data' => $data
            ]);

        } catch (\Throwable $e) {
            Log::error('MonitoringMIPController::data() ERROR', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Internal Server Error'
            ], 500);
        }
    }

    public function save(Request $request)
    {
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $stockAwal = is_numeric($request->stock_awal)
            ? (int) $request->stock_awal
            : (int) (
                RekapData::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('customer', $request->customer)
                    ->where('kode_project', $request->project)
                    ->where('part_number', $request->part_number)
                    ->value('stock_awal_mip') ?? 0
            );

        $totalIn = 0;
        $totalOut = 0;

        for ($i = 1; $i <= $jumlahHari; $i++) {
            $totalIn += (int) $request->input("in_hari_{$i}", 0);
            $totalOut += (int) $request->input("out_hari_{$i}", 0);
        }

        $header = MonitoringMIPHeader::updateOrCreate(
            [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'customer' => $request->customer,
                'project' => $request->project,
                'part_number' => $request->part_number,
            ],
            [
                'part_name' => $request->part_name,
                'stock_awal' => $stockAwal,
                'level_min' => (int) $request->level_min,
                'level_safety' => (int) $request->level_safety,
                'level_max' => (int) $request->level_max,
                'total_out' => $totalOut,
                'total_in' => $totalIn,
            ]
        );

        $balance = $stockAwal;

        for ($i = 1; $i <= $jumlahHari; $i++) {
            $in = (int) $request->input("in_hari_{$i}", 0);
            $out = (int) $request->input("out_hari_{$i}", 0);

            $balance = $balance + $in - $out;

            MonitoringMIPDetail::updateOrCreate(
                [
                    'header_id' => $header->id,
                    'tanggal' => $i
                ],
                [
                    'in_qty' => $in,
                    'out_qty' => $out,
                    'balance' => $balance,
                ]
            );
        }

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function updateStockAwal(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer',
            'tahun' => 'required|integer',
            'customer' => 'required|string',
            'project' => 'nullable|string',
            'part_number' => 'required|string',
            'stock_awal' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            $header = MonitoringMIPHeader::where([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'customer' => $request->customer,
                'project' => $request->project,
                'part_number' => $request->part_number,
            ])->firstOrFail();

            $header->update([
                'stock_awal' => $request->stock_awal
            ]);

            RekapData::where([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'customer' => $request->customer,
                'kode_project' => $request->project,
                'part_number' => $request->part_number,
            ])->update([
                'stock_awal_mip' => $request->stock_awal
            ]);

            $details = MonitoringMIPDetail::where('header_id', $header->id)
                ->orderBy('tanggal')
                ->get();

            $balance = (int) $request->stock_awal;

            foreach ($details as $detail) {
                $balance = $balance + (int) $detail->in_qty - (int) $detail->out_qty;

                $detail->update([
                    'balance' => $balance
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'balance' => $balance,
                'warning' => $balance < 0
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Update Stock Awal MIP gagal', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal update stock awal'
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
            new MonitoringMIPExport($bulan, $tahun),
            "Monitoring_MIP_{$bulan}_{$tahun}.xlsx"
        );
    }
}
