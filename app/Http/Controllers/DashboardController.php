<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : now();

        $bulan = (int) $date->month;
        $tahun = (int) $date->year;
        $hari  = (int) $date->day;

        $saSub = DB::table('sub_assies as sa')
            ->join('sub_assy_details as sad', 'sad.sub_assy_id', '=', 'sa.id')
            ->where('sa.bulan', $bulan)
            ->where('sa.tahun', $tahun)
            ->where('sad.tanggal', $hari)
            ->where('sad.tipe', 'WIP')
            ->groupBy('sa.customer', 'sa.project', 'sa.part_number')
            ->selectRaw("
                TRIM(COALESCE(sa.customer,'')) as customer,
                TRIM(COALESCE(sa.project,'')) as project,
                TRIM(COALESCE(sa.part_number,'')) as part_number,
                MAX(COALESCE(sa.part_name,'')) as part_name,
                SUM(COALESCE(sad.jumlah,0)) as sa_wip
            ");

        $mipSub = DB::table('monitoring_mip_headers as mh')
            ->join('monitoring_mip_details as md', 'md.header_id', '=', 'mh.id')
            ->where('mh.bulan', $bulan)
            ->where('mh.tahun', $tahun)
            ->where('md.tanggal', $hari)
            ->groupBy('mh.customer', 'mh.project', 'mh.part_number')
            ->selectRaw("
                TRIM(COALESCE(mh.customer,'')) as customer,
                TRIM(COALESCE(mh.project,'')) as project,
                TRIM(COALESCE(mh.part_number,'')) as part_number,
                MAX(COALESCE(mh.part_name,'')) as part_name,
                MAX(COALESCE(md.balance,0)) as mip_bal
            ");

        $fgSub = DB::table('monitoring_fg_headers as fh')
            ->join('monitoring_fg_details as fd', 'fd.fg_header_id', '=', 'fh.id')
            ->where('fh.bulan', $bulan)
            ->where('fh.tahun', $tahun)
            ->where('fd.tanggal', $hari)
            ->groupBy('fh.customer', 'fh.project', 'fh.part_number')
            ->selectRaw("
                TRIM(COALESCE(fh.customer,'')) as customer,
                TRIM(COALESCE(fh.project,'')) as project,
                TRIM(COALESCE(fh.part_number,'')) as part_number,
                MAX(COALESCE(fh.part_name,'')) as part_name,
                MAX(COALESCE(fd.balance_n, fd.balance_d, 0)) as fg_bal
            ");

        $levelSub = DB::table('level_stok as ls')
            ->join('level_stok_detail as lsd', 'lsd.level_stok_id', '=', 'ls.id')
            ->where('ls.bulan', $bulan)
            ->where('ls.tahun', $tahun)
            ->groupBy('lsd.part_number')
            ->selectRaw("
                TRIM(COALESCE(lsd.part_number,'')) as part_number,
                MAX(COALESCE(lsd.min,0)) as level_min
            ");

        $rows = DB::table('rekap_data as r')
            ->where('r.bulan', $bulan)
            ->where('r.tahun', $tahun)
            ->leftJoinSub($saSub, 'sa', function ($join) {
                $join->whereRaw("sa.customer <=> TRIM(COALESCE(r.customer,''))")
                    ->whereRaw("sa.project  <=> TRIM(COALESCE(r.kode_project,''))")
                    ->whereRaw("sa.part_number <=> TRIM(COALESCE(r.part_number,''))");
            })
            ->leftJoinSub($mipSub, 'mip', function ($join) {
                $join->whereRaw("mip.customer <=> TRIM(COALESCE(r.customer,''))")
                    ->whereRaw("mip.project  <=> TRIM(COALESCE(r.kode_project,''))")
                    ->whereRaw("mip.part_number <=> TRIM(COALESCE(r.part_number,''))");
            })
            ->leftJoinSub($fgSub, 'fg', function ($join) {
                $join->whereRaw("fg.customer <=> TRIM(COALESCE(r.customer,''))")
                    ->whereRaw("fg.project  <=> TRIM(COALESCE(r.kode_project,''))")
                    ->whereRaw("fg.part_number <=> TRIM(COALESCE(r.part_number,''))");
            })
            ->leftJoinSub($levelSub, 'ls', function ($join) {
                $join->whereRaw("ls.part_number <=> TRIM(COALESCE(r.part_number,''))");
            })
            ->selectRaw("
                r.part_number,
                COALESCE(NULLIF(TRIM(r.models),''), sa.part_name, mip.part_name, fg.part_name, '') as part_name,
                r.customer,
                r.kode_project as project,

                COALESCE(sa.sa_wip,0) as sa_wip,
                COALESCE(mip.mip_bal,0) as mip_bal,
                COALESCE(fg.fg_bal,0) as fg_bal,

                (COALESCE(sa.sa_wip,0) + COALESCE(mip.mip_bal,0) + COALESCE(fg.fg_bal,0)) as total,

                COALESCE(ls.level_min,0) as level_stock_n,

                CASE
                    WHEN COALESCE(ls.level_min,0) <= 0 THEN 0
                    ELSE
                        CASE
                            WHEN ROUND(
                                (COALESCE(sa.sa_wip,0) + COALESCE(mip.mip_bal,0) + COALESCE(fg.fg_bal,0)) / ls.level_min
                            , 2)
                            != ROUND(
                                (COALESCE(sa.sa_wip,0) + COALESCE(mip.mip_bal,0) + COALESCE(fg.fg_bal,0)) / ls.level_min
                            , 1)
                            THEN ROUND(
                                (COALESCE(sa.sa_wip,0) + COALESCE(mip.mip_bal,0) + COALESCE(fg.fg_bal,0)) / ls.level_min
                            , 0)
                            ELSE ROUND(
                                (COALESCE(sa.sa_wip,0) + COALESCE(mip.mip_bal,0) + COALESCE(fg.fg_bal,0)) / ls.level_min
                            , 1)
                        END
                END as status_level_day
            ")
            ->orderBy('r.part_number')
            ->get();

        return view('pages.dashboards.index', [
            'rows'  => $rows,
            'date'  => $date->toDateString(),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'hari'  => $hari,
        ]);
    }
}