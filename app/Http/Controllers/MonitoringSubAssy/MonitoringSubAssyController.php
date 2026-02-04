<?php

namespace App\Http\Controllers\MonitoringSubAssy;

use App\Http\Controllers\Controller;
use App\Models\SubAssy;
use App\Models\RekapData;
use App\Models\SubAssyDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\SubAssyExport;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringSubAssyController extends Controller
{
    public function index()
    {
        return view('pages.monitoringsubassy.index');
    }

    public function data(Request $request)
    {
        if (!$request->ajax()) abort(404);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Ambil data dari API SPK
        $spkData = [];
        try {
            $response = Http::timeout(10)->get('http://192.168.0.8:8080/sistem-spk-tigaraksa/api/spk-data');
            if ($response->successful()) {
                $spkData = $response->json();
            }
        } catch (\Exception $e) {
            Log::error("Gagal fetch SPK: " . $e->getMessage());
        }

        $spkMap = [];

        foreach ($spkData as $spk) {
            $tanggal = \Carbon\Carbon::parse($spk['tanggal_produksi']);
            $tgl = $tanggal->day;
            $bln = $tanggal->month;
            $thn = $tanggal->year;

            if ($bln != $bulan || $thn != $tahun) continue;

            foreach ($spk['details'] as $detail) {
                $key = $detail['customer'] . '|' . $detail['part_number'];
                $spkMap[$key][$tgl] = ($spkMap[$key][$tgl] ?? 0) + (int) $detail['qty_order_prod'];
            }
        }

        $customer = $request->customer;

        $rekapData = RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($customer, function ($q) use ($customer) {
                $q->where('customer', $customer);
            })
            ->orderBy('customer')
            ->orderBy('kode_project')
            ->orderBy('part_number')
            ->get();

        $subAssies = SubAssy::with('details')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn($s) => $s->customer . '|' . $s->project . '|' . $s->part_number . '|' . $s->part_name);

        $data = [];

        foreach ($rekapData as $rekap) {
            $key = $rekap->customer . '|' . $rekap->kode_project . '|' . $rekap->part_number . '|' . $rekap->models;
            $subAssy = $subAssies[$key] ?? null;

            $row = [
                'id' => $subAssy->id ?? null,
                'customer' => $rekap->customer,
                'project' => $rekap->kode_project,
                'part_number' => $rekap->part_number,
                'part_name' => $rekap->models,

                'wip_sebelumnya' => $subAssy->wip_sebelumnya ?? '',
                'total_po' => $rekap->total_qty_bulan_ini ?? 0,
                'total_spk' => $subAssy->total_spk ?? '',
                'total_produksi' => $subAssy->total_produksi ?? '',
                'wip_akhir' => $subAssy->wip_akhir ?? '',
                'produktivitas' => $subAssy->produktivitas ?? '',
            ];

            for ($i = 1; $i <= 31; $i++) {
                $row["spk_hari_{$i}"] = '';
                $row["produksi_hari_{$i}"] = '';
                $row["wip_hari_{$i}"] = '';
            }

            if ($subAssy) {
                foreach ($subAssy->details as $detail) {
                    $tipe = strtolower($detail->tipe);
                    $day = (int) $detail->tanggal;
                    $keyHari = "{$tipe}_hari_{$day}";
                    $row[$keyHari] = $detail->jumlah ?? '';
                }
            }

            $apiKey = $rekap->customer . '|' . $rekap->part_number;
            if (isset($spkMap[$apiKey])) {
                foreach ($spkMap[$apiKey] as $day => $jumlah) {
                    $row["spk_hari_{$day}"] = $jumlah;
                }
            }

            $data[] = $row;
        }

        return DataTables::of($data)->make(true);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'bulan'        => 'required|integer|between:1,12',
            'tahun'        => 'required|integer|min:2020',
            'customer'     => 'required|string',
            'project'      => 'required|string',
            'part_number'  => 'required|string',
            'part_name'    => 'required|string',
        ]);

        $data = $request->except(['_token']);

        $total_spk = 0;
        $total_produksi = 0;

        for ($i = 1; $i <= 31; $i++) {
            $spk = (int) ($request->input("spk_hari_$i") ?? 0);
            $produksi = (int) ($request->input("produksi_hari_$i") ?? 0);

            $total_spk += $spk;
            $total_produksi += $produksi;
        }

        $wip_sebelumnya = (int) ($data['wip_sebelumnya'] ?? 0);
        $wip_akhir = $wip_sebelumnya + $total_spk - $total_produksi;
        $produktivitas = $total_spk > 0 ? ceil(($total_produksi / $total_spk) * 100) : 0;

        $subAssy = SubAssy::updateOrCreate(
            [
                'bulan'       => $data['bulan'],
                'tahun'       => $data['tahun'],
                'customer'    => $data['customer'],
                'project'     => $data['project'],
                'part_number' => $data['part_number'],
                'part_name'   => $data['part_name'],
            ],
            [
                'wip_sebelumnya' => $wip_sebelumnya,
                'total_spk'      => $total_spk,
                'total_produksi' => $total_produksi,
                'wip_akhir'      => $wip_akhir,
                'produktivitas'  => $produktivitas,
            ]
        );

        foreach (['SPK', 'Produksi', 'WIP'] as $tipe) {
            $prefix = strtolower($tipe);

            for ($i = 1; $i <= 31; $i++) {
                $jumlah = $request->input("{$prefix}_hari_{$i}");

                if ($jumlah !== null && $jumlah !== '') {
                    SubAssyDetail::updateOrCreate(
                        [
                            'sub_assy_id' => $subAssy->id,
                            'tanggal'     => $i,
                            'tipe'        => $tipe,
                        ],
                        [
                            'jumlah' => $jumlah,
                        ]
                    );
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil disimpan.',
            'id' => $subAssy->id,
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
        ]);

        $bulan = $request->bulan;
        $tahun = $request->tahun;

        return Excel::download(new SubAssyExport($bulan, $tahun), "Monitoring_SubAssy_{$bulan}_{$tahun}.xlsx");
    }
}
