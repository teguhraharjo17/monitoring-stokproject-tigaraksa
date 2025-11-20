<?php

namespace App\Http\Controllers\DataKanban\OSPERWEEK;

use App\Http\Controllers\Controller;
use App\Models\RekapData;
use App\Models\MonitoringFGHeader;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\OSPerWeekExport;
use Maatwebsite\Excel\Facades\Excel;

class OSPERWEEKController extends Controller
{
    public function index()
    {
        return view('pages.datakanban.osperweek.index');
    }

    public function data(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $jumlahHari = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $rekapData = RekapData::bulan($bulan)->tahun($tahun)->get();

        $data = $rekapData->map(function ($item, $index) use ($bulan, $tahun, $jumlahHari) {
            $header = MonitoringFGHeader::with('details')->where([
                ['bulan', $bulan],
                ['tahun', $tahun],
                ['customer', $item->customer],
                ['project', $item->kode_project],
                ['part_number', $item->part_number],
                ['part_name', $item->models]
            ])->first();

            $details = $header?->details ?? collect();

            $minggu = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
            ];

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $row = $details->firstWhere('tanggal', $i);
                $d = (int)($row->out_qty_d ?? 0);
                $n = (int)($row->out_qty_n ?? 0);
                $total = $d + $n;

                if ($i >= 1 && $i <= 7) {
                    $minggu[1] += $total;
                } elseif ($i >= 8 && $i <= 14) {
                    $minggu[2] += $total;
                } elseif ($i >= 15 && $i <= 21) {
                    $minggu[3] += $total;
                } else {
                    $minggu[4] += $total;
                }
            }

            $po = (int)($item->po_bulan_ini ?? 0);

            return [
                'no' => $index + 1,
                'customer' => $item->customer,
                'part_number' => $item->part_number,
                'models' => $item->models,
                'po' => $po,
                'week1_delivery' => $minggu[1],
                'week1_os' => $po - $minggu[1],
                'week2_delivery' => $minggu[2],
                'week2_os' => $po - $minggu[2],
                'week3_delivery' => $minggu[3],
                'week3_os' => $po - $minggu[3],
                'week4_delivery' => $minggu[4],
                'week4_os' => $po - $minggu[4],
            ];
        });

        return DataTables::of($data)->make(true);
    }

    public function export(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $filename = 'OSPerWeek_' . $bulan . '_' . $tahun . '.xlsx';

        return Excel::download(new OSPerWeekExport($bulan, $tahun), $filename);
    }
}
