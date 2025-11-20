<?php

namespace App\Http\Controllers\DataKanban\Reguler;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RekapData;
use App\Models\MonitoringFGHeader;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\RegulerExport;
use Maatwebsite\Excel\Facades\Excel;

class RegulerController extends Controller
{
    public function index()
    {
        return view('pages.datakanban.reguler.index');
    }

    public function data(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;
        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $rekapData = RekapData::bulan($bulan)->tahun($tahun)->get();

        $data = $rekapData->map(function ($item, $index) use ($bulan, $tahun, $jumlahHari) {
            // Cari data monitoring FG yang cocok
            $header = MonitoringFGHeader::with('details')->where([
                ['bulan', $bulan],
                ['tahun', $tahun],
                ['customer', $item->customer],
                ['project', $item->kode_project],
                ['part_number', $item->part_number],
                ['part_name', $item->models]
            ])->first();

            // Total Qty Kanban dari OUT qty D dan N
            $totalKanban = 0;
            $details = $header?->details ?? collect();
            for ($i = 1; $i <= $jumlahHari; $i++) {
                $row = $details->firstWhere('tanggal', $i);
                $totalKanban += ($row->out_qty_d ?? 0) + ($row->out_qty_n ?? 0);
            }

            $poQty = (int) $item->po_bulan_ini;
            $persenPenyerapan = $poQty > 0 ? round(($totalKanban / $poQty) * 100, 2) : 0;
            $selisih = $totalKanban - $poQty;

            $result = [
                'no' => $index + 1,
                'customer' => $item->customer,
                'part_number' => $item->part_number,
                'models' => $item->models,
                'qty_po' => $poQty,
                'qty_kanban' => $totalKanban,
                'penyerapan' => $persenPenyerapan . '%',
                'selisih' => $selisih
            ];

            // Tambahkan kolom harian (D/N)
            for ($i = 1; $i <= $jumlahHari; $i++) {
                $row = $details->firstWhere('tanggal', $i);
                $result["d_{$i}"] = $row->out_qty_d ?? 0;
                $result["n_{$i}"] = $row->out_qty_n ?? 0;
            }

            return $result;
        });

        return DataTables::of($data)->make(true);
    }

    public function export(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        return Excel::download(new RegulerExport($bulan, $tahun), "kanban_reguler_{$bulan}_{$tahun}.xlsx");
    }
}
