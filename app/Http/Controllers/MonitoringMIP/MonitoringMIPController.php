<?php

namespace App\Http\Controllers\MonitoringMIP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitoringMIPHeader;
use App\Models\MonitoringMIPDetail;
use App\Models\RekapData;
use App\Models\LevelStok;
use App\Models\SubAssy;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class MonitoringMIPController extends Controller
{
    public function index()
    {
        return view('pages.monitoringmip.index');
    }

    public function data(Request $request)
    {
        try {
            $bulan = $request->bulan;
            $tahun = $request->tahun;

            Log::info("MonitoringMIPController::data => bulan: $bulan, tahun: $tahun");

            $rekap = RekapData::where('bulan', $bulan)->where('tahun', $tahun)->get();
            if ($rekap->isEmpty()) {
                Log::warning("Tidak ada data Rekap untuk bulan: $bulan tahun: $tahun");
                return DataTables::of([])->make(true);
            }

            $levelStok = LevelStok::with('details')->where('bulan', $bulan)->where('tahun', $tahun)->first();
            Log::info("LevelStok ditemukan: " . ($levelStok ? 'YA' : 'TIDAK'));

            $data = [];

            foreach ($rekap as $item) {
                $row = [
                    'customer' => $item->customer,
                    'project' => $item->kode_project,
                    'part_number' => $item->part_number,
                    'part_name' => $item->models,
                    'stock_awal' => (int) $item->stock_awal_mip,
                    'total_po' => (int) $item->total_qty_bulan_ini ?? 0,
                    'total_in' => 0,
                    'total_out' => 0,
                    'level_min' => 0,
                    'level_safety' => 0,
                    'level_max' => 0,
                ];


                if ($levelStok) {
                    $levelDetail = $levelStok->details->firstWhere('part_number', $item->part_number);
                    if ($levelDetail) {
                        $row['level_min'] = $levelDetail->min ?? 0;
                        $row['level_safety'] = $levelDetail->safety_mip ?? 0;
                        $row['level_max'] = $levelDetail->max ?? 0;
                    }
                }

                $subAssy = SubAssy::with(['details' => function ($q) {
                    $q->where('tipe', 'Produksi');
                }])
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('part_number', $item->part_number)
                    ->first();

                $inList = [];
                if ($subAssy && $subAssy->details) {
                    foreach ($subAssy->details as $detail) {
                        $inList[(int) $detail->tanggal] = $detail->jumlah;
                    }
                }

                $header = MonitoringMIPHeader::where([
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'customer' => $item->customer,
                    'project' => $item->kode_project,
                    'part_number' => $item->part_number,
                ])->first();

                $details = $header
                    ? MonitoringMIPDetail::where('header_id', $header->id)->get()->keyBy('tanggal')
                    : collect();

                $balance = $row['stock_awal'];

                for ($i = 1; $i <= 31; $i++) {
                    $in = $inList[$i] ?? 0;
                    $out = optional($details->get($i))->out_qty ?? 0;

                    $balance = $balance + $in - $out;

                    $row["in_hari_$i"] = $in;
                    $row["out_hari_$i"] = $out;
                    $row["balance_hari_$i"] = $balance;
                    $row["total_in"] += $in;
                    $row["total_out"] += $out;
                }

                $data[] = $row;
            }

            return DataTables::of($data)->make(true);

        } catch (\Throwable $e) {
            Log::error('MonitoringMIPController::data() ERROR — ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function save(Request $request)
    {
        $header = MonitoringMIPHeader::updateOrCreate(
            [
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'customer' => $request->customer,
                'project' => $request->project,
                'part_number' => $request->part_number,
                'part_name' => $request->part_name,
            ],
            [
                'stock_awal' => $request->stock_awal,
                'level_min' => $request->level_min,
                'level_safety' => $request->level_safety,
                'level_max' => $request->level_max,
                'total_out' => collect($request->all())->filter(fn($v, $k) => str_starts_with($k, 'out_hari_'))->sum(),
                'total_in' => collect($request->all())->filter(fn($v, $k) => str_starts_with($k, 'in_hari_'))->sum(),
            ]
        );

        $balance = (int) $request->stock_awal;

        for ($i = 1; $i <= 31; $i++) {
            $in = (int) ($request->input("in_hari_$i") ?? 0);
            $out = (int) ($request->input("out_hari_$i") ?? 0);

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

        return response()->json(['status' => 'success']);
    }
}
