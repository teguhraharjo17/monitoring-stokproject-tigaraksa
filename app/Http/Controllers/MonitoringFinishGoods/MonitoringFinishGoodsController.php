<?php

namespace App\Http\Controllers\MonitoringFinishGoods;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringFGDetail;
use App\Models\RekapData;
use App\Models\MonitoringMIPHeader;
use App\Models\MonitoringMIPDetail;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Exports\FinishGoodsExport;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringFinishGoodsController extends Controller
{
    public function index()
    {
        return view('pages.monitoringfinishgood.index');
    }

    public function data(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $customer = $request->customer;


        $rekapList = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer, function ($q) use ($customer) {
                $q->where('customer', $customer);
            })
            ->orderBy('customer')
            ->orderBy('kode_project')
            ->orderBy('part_number')
            ->get();
        $data = [];

        if ($request->only_customer) {

            $rekapCustomer = RekapData::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->pluck('customer');

            $fgCustomer = MonitoringFGHeader::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->pluck('customer');

            $customers = $rekapCustomer
                ->merge($fgCustomer)
                ->unique()
                ->sort()
                ->values()
                ->map(fn ($c) => ['customer' => $c]);

            return response()->json([
                'data' => $customers
            ]);
        }

        foreach ($rekapList as $rekap) {
            $level = DB::table('level_stok_detail as d')
                ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
                ->where('l.bulan', $bulan)
                ->where('l.tahun', $tahun)
                ->where('d.customer', $rekap->customer)
                ->where('d.kode_projek', $rekap->kode_project)
                ->where('d.part_number', $rekap->part_number)
                ->select('d.min', 'd.safety_fg', 'd.max')
                ->first();

            $header = MonitoringFGHeader::firstOrNew([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
            ]);

            $header->fill([
                'part_name' => $rekap->models,
                'stock_awal' => (int) $rekap->stock_awal_fg,
                'total_in' => $header->total_in ?? 0,
                'total_out' => $header->total_out ?? 0,
                'level_min' => $level->min ?? 0,
                'level_safety' => $level->safety_fg ?? 0,
                'level_max' => $level->max ?? 0,
            ]);

            $header->save();

            $details = MonitoringFGDetail::where('fg_header_id', $header->id)->get()->keyBy('tanggal');

            $mipHeader = MonitoringMIPHeader::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('customer', $rekap->customer)
                ->where('project', $rekap->kode_project)
                ->where('part_number', $rekap->part_number)
                ->first();

            $mipDetails = $mipHeader
                ? MonitoringMIPDetail::where('header_id', $mipHeader->id)->get()->keyBy('tanggal')
                : collect();

            $row = [
                'id' => $header->id,
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
                'part_name' => $rekap->models,
                'total_po' => (int) $rekap->total_qty_bulan_ini ?? 0,
                'stock_awal' => $header->stock_awal,
                'total_in' => $header->total_in,
                'total_out' => $header->total_out,
                'level_min' => $header->level_min,
                'level_safety' => $header->level_safety,
                'level_max' => $header->level_max,
            ];

            $balance = $header->stock_awal;

            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
            for ($i = 1; $i <= $jumlahHari; $i++) {
                $inD = (int) ($mipDetails[$i]->out_qty ?? 0);
                $inN = (int) ($details[$i]->in_qty_n ?? 0);
                $outD = (int) ($details[$i]->out_qty_d ?? 0);
                $outN = (int) ($details[$i]->out_qty_n ?? 0);

                $balanceD = $balance + $inD - $outD;
                $balanceN = $balanceD + $inN - $outN;

                $row["in_hari_{$i}_d"] = $inD;
                $row["in_hari_{$i}_n"] = $inN;
                $row["out_hari_{$i}_d"] = $outD;
                $row["out_hari_{$i}_n"] = $outN;
                $row["balance_hari_{$i}_d"] = $balanceD;
                $row["balance_hari_{$i}_n"] = $balanceN;

                $balance = $balanceN;
            }

            $advance = $header->advance_delivery ?? 0;
            $totalOut = $header->total_out ?? 0;
            $outstanding = max(0, ($row['total_po'] ?? 0) - $advance - $totalOut);
            $percentage = $row['total_po'] > 0 ? round(($outstanding / $row['total_po']) * 100, 2) : 0;

            $row['advance_delivery'] = $advance;
            $row['outstanding'] = $outstanding;
            $row['percentage'] = $percentage;

            $row['stock_on_hand'] = $balance ?? 0;

            if ($row['stock_on_hand'] <= $header->level_min) {
                $row['status_stock'] = 'Problem';
            } elseif ($row['stock_on_hand'] > $header->level_max) {
                $row['status_stock'] = 'Over';
            } else {
                $row['status_stock'] = 'Aman';
            }

            $data[] = $row;
        }

        return DataTables::of($data)->toJson();
    }

    public function save(Request $request)
    {
        DB::beginTransaction();

        try {
            $bulan = (int) $request->bulan;
            $tahun = (int) $request->tahun;

            $header = MonitoringFGHeader::updateOrCreate([
                'customer' => $request->customer,
                'project' => $request->project,
                'part_number' => $request->part_number,
                'bulan' => $bulan,
                'tahun' => $tahun,
            ], [
                'part_name' => $request->part_name,
                'stock_awal' => (int) $request->stock_awal,
                'advance_delivery' => (int) $request->advance_delivery,
                'level_min' => (int) $request->level_min,
                'level_safety' => (int) $request->level_safety,
                'level_max' => (int) $request->level_max,
            ]);

            $totalIn = 0;
            $totalOut = 0;

            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $inD = (int) $request->input("in_hari_{$i}_d", 0);
                $inN = (int) $request->input("in_hari_{$i}_n", 0);
                $outD = (int) $request->input("out_hari_{$i}_d", 0);
                $outN = (int) $request->input("out_hari_{$i}_n", 0);
                $balanceD = (int) $request->input("balance_hari_{$i}_d", 0);
                $balanceN = (int) $request->input("balance_hari_{$i}_n", 0);

                $totalIn += $inD + $inN;
                $totalOut += $outD + $outN;

                MonitoringFGDetail::updateOrCreate([
                    'fg_header_id' => $header->id,
                    'tanggal' => $i,
                ], [
                    'in_qty_d' => $inD,
                    'in_qty_n' => $inN,
                    'out_qty_d' => $outD,
                    'out_qty_n' => $outN,
                    'balance_d' => $balanceD,
                    'balance_n' => $balanceN,
                ]);
            }

            $header->update([
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ]);

            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $namaBulan = \Carbon\Carbon::create()->month($bulan)->translatedFormat('F');
        $fileName = "Monitoring_Finish_Goods_{$namaBulan}_{$tahun}.xlsx";

        return Excel::download(new FinishGoodsExport($bulan, $tahun), $fileName);
    }
}
