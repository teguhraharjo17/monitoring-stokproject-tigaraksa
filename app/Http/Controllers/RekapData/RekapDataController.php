<?php

namespace App\Http\Controllers\RekapData;

use App\Models\RekapData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;
use App\Models\MasterItem;

class RekapDataController extends Controller
{
    public function index()
    {
        $masterItems = MasterItem::select('part_number', 'customer', 'kode_project', 'nama_part')->get();

        return view('pages.rekapdata.redesign', compact('masterItems'));
    }

    public function data(Request $request)
    {
        $bulan = $request->bulan ?? now()->month;
        $tahun = $request->tahun ?? now()->year;

        $customer = $request->customer;

        $items = MasterItem::leftJoin('rekap_data', function ($join) use ($bulan, $tahun) {
                $join->on('master_items.part_number', '=', 'rekap_data.part_number')
                    ->where('rekap_data.bulan', $bulan)
                    ->where('rekap_data.tahun', $tahun);
            })
            ->when($customer, function ($query) use ($customer) {
                $query->where('master_items.customer', $customer);
            })
            ->select([
                'master_items.part_number',
                'master_items.customer',
                'master_items.kode_project',
                'master_items.nama_part as models',
                'rekap_data.id',
                'rekap_data.stock_awal_mip',
                'rekap_data.stock_awal_fg',
                'rekap_data.wip_spk_sa',
                'rekap_data.total_stock',
                'rekap_data.os_bulan_lalu',
                'rekap_data.po_bulan_ini',
                'rekap_data.total_qty_bulan_ini',
                'rekap_data.selisih_stock',
            ])
            ->orderBy('master_items.customer')
            ->orderBy('master_items.part_number');

        return DataTables::of($items)->make(true);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020',
            'part_number' => 'required|string',
            'customer' => 'nullable|string',
            'kode_project' => 'nullable|string',
            'models' => 'nullable|string',
            'stock_awal_mip' => 'nullable|numeric',
            'stock_awal_fg' => 'nullable|numeric',
            'wip_spk_sa' => 'nullable|numeric',
            'total_stock' => 'nullable|numeric',
            'os_bulan_lalu' => 'nullable|numeric',
            'po_bulan_ini' => 'nullable|numeric',
            'total_qty_bulan_ini' => 'nullable|numeric',
            'selisih_stock' => 'nullable|numeric',
        ]);

        // Cek duplikat
        $duplikat = RekapData::where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->where('part_number', $validated['part_number'])
            ->where('customer', $validated['customer'])
            ->where(function($q) use ($validated) {
                $proj = trim((string)($validated['kode_project'] ?? ''));
                if ($proj === '') {
                    $q->whereNull('kode_project')->orWhere('kode_project', '');
                } else {
                    $q->where('kode_project', $proj);
                }
            })
            ->where('models', $validated['models']);

        if ($request->filled('id')) {
            // Jika update, jangan hitung dirinya sendiri
            $duplikat->where('id', '!=', $request->id);
        }

        if ($duplikat->exists()) {
            return response()->json(['error' => 'Data dengan Part Name ini sudah ada.'], 422);
        }

        try {
            $res = \Illuminate\Support\Facades\DB::transaction(function() use ($request, $validated) {
                if ($request->filled('id')) {
                    $rekap = RekapData::find($request->id);

                    if (!$rekap) {
                        return response()->json(['error' => 'Data tidak ditemukan'], 404);
                    }

                    $rekap->update($validated);
                    $this->syncToMonitoring($rekap);
                    return response()->json(['status' => 'updated', 'id' => $rekap->id]);
                }

                $rekap = RekapData::create($validated);
                $this->syncToMonitoring($rekap);

                return response()->json([
                    'status' => 'created',
                    'id' => $rekap->id,
                    'data' => $rekap
                ]);
            });
            return $res;
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Terjadi kesalahan transaksi: ' . $e->getMessage()], 500);
        }
    }

    private function syncToMonitoring($rekap)
    {
        // 1. Sync to Monitoring MIP
        if ($rekap->stock_awal_mip !== null) {
            $mipHeader = \App\Models\MonitoringMIPHeader::where([
                'bulan' => $rekap->bulan,
                'tahun' => $rekap->tahun,
                'customer' => $rekap->customer,
                'part_number' => $rekap->part_number,
            ])
            ->where(function($q) use ($rekap) {
                $proj = trim((string)$rekap->kode_project);
                if ($proj === '') {
                    $q->whereNull('project')->orWhere('project', '');
                } else {
                    $q->where('project', $proj);
                }
            })
            ->first();

            if ($mipHeader) {
                $mipHeader->update([
                    'stock_awal' => (int) $rekap->stock_awal_mip
                ]);

                $details = \App\Models\MonitoringMIPDetail::where('header_id', $mipHeader->id)
                    ->orderBy('tanggal')
                    ->get();

                $balance = (int) $rekap->stock_awal_mip;

                foreach ($details as $detail) {
                    $balance = $balance + (int) $detail->in_qty - (int) $detail->out_qty;
                    $detail->update([
                        'balance' => $balance
                    ]);
                }

                // Sync ke RekapData bulan depan
                try {
                    $nextMonth = $rekap->bulan == 12 ? 1 : $rekap->bulan + 1;
                    $nextYear = $rekap->bulan == 12 ? $rekap->tahun + 1 : $rekap->tahun;

                    $nextRekap = RekapData::where('bulan', $nextMonth)
                        ->where('tahun', $nextYear)
                        ->where('customer', $rekap->customer)
                        ->where('part_number', $rekap->part_number)
                        ->where(function($q) use ($rekap) {
                            $proj = trim((string)$rekap->kode_project);
                            if ($proj === '') {
                                $q->whereNull('kode_project')->orWhere('kode_project', '');
                            } else {
                                $q->where('kode_project', $proj);
                            }
                        })
                        ->first();

                    if ($nextRekap) {
                        $nextRekap->update([
                            'models' => $rekap->models,
                            'stock_awal_mip' => $balance
                        ]);
                    } else {
                        RekapData::create([
                            'bulan' => $nextMonth,
                            'tahun' => $nextYear,
                            'customer' => $rekap->customer,
                            'kode_project' => $rekap->kode_project === '' ? null : $rekap->kode_project,
                            'part_number' => $rekap->part_number,
                            'models' => $rekap->models,
                            'stock_awal_mip' => $balance
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('MIP sync next month error: ' . $e->getMessage());
                }
            }
        }

        // 2. Sync to Monitoring FG
        if ($rekap->stock_awal_fg !== null) {
            $fgHeader = \App\Models\MonitoringFGHeader::where([
                'bulan' => $rekap->bulan,
                'tahun' => $rekap->tahun,
                'customer' => $rekap->customer,
                'part_number' => $rekap->part_number,
            ])
            ->where(function($q) use ($rekap) {
                $proj = trim((string)$rekap->kode_project);
                if ($proj === '') {
                    $q->whereNull('project')->orWhere('project', '');
                } else {
                    $q->where('project', $proj);
                }
            })
            ->first();

            if ($fgHeader) {
                $fgHeader->update([
                    'stock_awal' => (int) $rekap->stock_awal_fg
                ]);

                $details = \App\Models\MonitoringFGDetail::where('fg_header_id', $fgHeader->id)
                    ->orderBy('tanggal')
                    ->get();

                $balance = (int) $rekap->stock_awal_fg;

                foreach ($details as $detail) {
                    $inD = (int) $detail->in_qty_d;
                    $inN = (int) $detail->in_qty_n;
                    $outD = (int) $detail->out_qty_d;
                    $outN = (int) $detail->out_qty_n;

                    $balanceD = $balance + $inD - $outD;
                    $balanceN = $balanceD + $inN - $outN;

                    $detail->update([
                        'balance_d' => $balanceD,
                        'balance_n' => $balanceN,
                    ]);

                    $balance = $balanceN;
                }

                // Sync ke RekapData bulan depan
                try {
                    $nextMonth = $rekap->bulan == 12 ? 1 : $rekap->bulan + 1;
                    $nextYear = $rekap->bulan == 12 ? $rekap->tahun + 1 : $rekap->tahun;

                    $nextRekap = RekapData::where('bulan', $nextMonth)
                        ->where('tahun', $nextYear)
                        ->where('customer', $rekap->customer)
                        ->where('part_number', $rekap->part_number)
                        ->where(function($q) use ($rekap) {
                            $proj = trim((string)$rekap->kode_project);
                            if ($proj === '') {
                                $q->whereNull('kode_project')->orWhere('kode_project', '');
                            } else {
                                $q->where('kode_project', $proj);
                            }
                        })
                        ->first();

                    if ($nextRekap) {
                        $nextRekap->update([
                            'models' => $rekap->models,
                            'stock_awal_fg' => $balance
                        ]);
                    } else {
                        RekapData::create([
                            'bulan' => $nextMonth,
                            'tahun' => $nextYear,
                            'customer' => $rekap->customer,
                            'kode_project' => $rekap->kode_project === '' ? null : $rekap->kode_project,
                            'part_number' => $rekap->part_number,
                            'models' => $rekap->models,
                            'stock_awal_fg' => $balance
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('FG sync next month error: ' . $e->getMessage());
                }
            }
        }

        // 3. Sync to Monitoring Sub Assy
        if ($rekap->wip_spk_sa !== null) {
            $subAssy = \App\Models\SubAssy::where([
                'bulan' => $rekap->bulan,
                'tahun' => $rekap->tahun,
                'customer' => $rekap->customer,
                'part_number' => $rekap->part_number,
            ])
            ->where(function($q) use ($rekap) {
                $proj = trim((string)$rekap->kode_project);
                if ($proj === '') {
                    $q->whereNull('project')->orWhere('project', '');
                } else {
                    $q->where('project', $proj);
                }
            })
            ->first();

            if ($subAssy) {
                $subAssy->update([
                    'wip_sebelumnya' => (int) $rekap->wip_spk_sa
                ]);

                $detailsGrouped = \App\Models\SubAssyDetail::where('sub_assy_id', $subAssy->id)
                    ->get()
                    ->groupBy('tanggal');

                $jumlahHari = \Carbon\Carbon::createFromDate($rekap->tahun, $rekap->bulan, 1)->daysInMonth;
                $wipSmt = (int) $rekap->wip_spk_sa;

                for ($i = 1; $i <= $jumlahHari; $i++) {
                    $dayDetails = $detailsGrouped->get($i) ?? collect();
                    $spk = (int) ($dayDetails->where('tipe', 'SPK')->first()->jumlah ?? 0);
                    $prod = (int) ($dayDetails->where('tipe', 'Produksi')->first()->jumlah ?? 0);
                    $wip = $wipSmt + $spk - $prod;
                    $wipSmt = $wip;

                    \App\Models\SubAssyDetail::updateOrCreate(
                        [
                            'sub_assy_id' => $subAssy->id,
                            'tanggal' => $i,
                            'tipe' => 'WIP'
                        ],
                        [
                            'jumlah' => $wip
                        ]
                    );
                }

                $subAssy->update([
                    'wip_akhir' => $wipSmt
                ]);

                // Sync ke RekapData bulan depan
                try {
                    $nextMonth = $rekap->bulan == 12 ? 1 : $rekap->bulan + 1;
                    $nextYear = $rekap->bulan == 12 ? $rekap->tahun + 1 : $rekap->tahun;

                    $nextRekap = RekapData::where('bulan', $nextMonth)
                        ->where('tahun', $nextYear)
                        ->where('customer', $rekap->customer)
                        ->where('part_number', $rekap->part_number)
                        ->where(function($q) use ($rekap) {
                            $proj = trim((string)$rekap->kode_project);
                            if ($proj === '') {
                                $q->whereNull('kode_project')->orWhere('kode_project', '');
                            } else {
                                $q->where('kode_project', $proj);
                            }
                        })
                        ->first();

                    if ($nextRekap) {
                        $nextRekap->update([
                            'models' => $rekap->models,
                            'wip_spk_sa' => $wipSmt
                        ]);
                    } else {
                        RekapData::create([
                            'bulan' => $nextMonth,
                            'tahun' => $nextYear,
                            'customer' => $rekap->customer,
                            'kode_project' => $rekap->kode_project === '' ? null : $rekap->kode_project,
                            'part_number' => $rekap->part_number,
                            'models' => $rekap->models,
                            'wip_spk_sa' => $wipSmt
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('SubAssy sync next month error: ' . $e->getMessage());
                }
            }
        }
    }

    public function fetch(Request $request)
    {
        $data = RekapData::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->where('part_number', $request->part_number)
            ->where('customer', $request->customer)
            ->where('kode_project', $request->kode_project)
            ->where('models', $request->models)
            ->first();

        return response()->json($data);
    }
}
