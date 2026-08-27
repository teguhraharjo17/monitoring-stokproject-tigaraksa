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

    private function normalize($str)
    {
        $str = $str ?? '';
        return strtoupper(str_replace([' ', '-', '_'], '', $str));
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

            // 1. Load RekapData & Aggregate duplicates
            $rekapListRaw = RekapData::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->when($customer, function ($q) use ($customer) {
                    if (is_array($customer)) {
                        $q->whereIn('customer', $customer);
                    } else {
                        $q->where('customer', $customer);
                    }
                })
                ->get();

            $aggregatedRekap = [];
            foreach ($rekapListRaw as $item) {
                $key = $this->normalize($item->customer) . '|' . $this->normalize($item->kode_project) . '|' . $this->normalize($item->part_number) . '|' . $this->normalize($item->models);
                if (!isset($aggregatedRekap[$key])) {
                    $aggregatedRekap[$key] = $item;
                } else {
                    $aggregatedRekap[$key]->total_qty_bulan_ini += $item->total_qty_bulan_ini;
                    $aggregatedRekap[$key]->stock_awal_mip = max($aggregatedRekap[$key]->stock_awal_mip, $item->stock_awal_mip);
                }
            }

            // 2. Bulk Load Level Stok
            $levelStokMap = DB::table('level_stok_detail as d')
                ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
                ->where('l.bulan', $bulan)
                ->where('l.tahun', $tahun)
                ->get()
                ->keyBy(fn($l) => $this->normalize($l->customer) . '|' . $this->normalize($l->kode_projek) . '|' . $this->normalize($l->part_number));

            // 3. Bulk Load MIP Headers & Details
            $mipHeaders = MonitoringMIPHeader::with('details')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

            // 4. Bulk Load SubAssy (Produksi)
            $subAssyList = SubAssy::with(['details' => function ($q) {
                    $q->where('tipe', 'Produksi');
                }])
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn($s) => $this->normalize($s->customer) . '|' . $this->normalize($s->project) . '|' . $this->normalize($s->part_number));

            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
            $data = [];

            foreach ($aggregatedRekap as $item) {
                $matchKey = $this->normalize($item->customer) . '|' . $this->normalize($item->kode_project) . '|' . $this->normalize($item->part_number);
                
                $level = $levelStokMap[$matchKey] ?? null;
                $header = $mipHeaders[$matchKey] ?? null;
                $details = $header ? $header->details->keyBy('tanggal') : collect();
                
                $subAssy = $subAssyList[$matchKey] ?? null;
                $inList = [];
                if ($subAssy && $subAssy->details) {
                    foreach ($subAssy->details as $detail) {
                        $hari = (int) $detail->tanggal;
                        if ($hari >= 1 && $hari <= $jumlahHari) {
                            $inList[$hari] = (int) ($detail->jumlah ?? 0);
                        }
                    }
                }

                $stockAwal = (int) ($header->stock_awal ?? ($item->stock_awal_mip ?? 0));
                
                $row = [
                    'id' => $header->id ?? null,
                    'customer' => $item->customer,
                    'project' => $item->kode_project,
                    'part_number' => $item->part_number,
                    'part_name' => $item->models,
                    'total_po' => (int) ($item->total_qty_bulan_ini ?? 0),
                    'stock_awal' => $stockAwal,
                    'total_in' => 0,
                    'total_out' => 0,
                    'level_min' => (int) ($level->min ?? ($header->level_min ?? 0)),
                    'level_safety' => (int) ($level->safety_mip ?? ($header->level_safety ?? 0)),
                    'level_max' => (int) ($level->max ?? ($header->level_max ?? 0)),
                ];

                $balance = $stockAwal;
                for ($i = 1; $i <= $jumlahHari; $i++) {
                    $in = (int) ($inList[$i] ?? 0);
                    $out = (int) ($details[$i]->out_qty ?? 0);

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
            Log::error('MonitoringMIPController::data Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function save(Request $request)
    {
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $customer = trim($request->customer);
        $project = trim((string) $request->project);
        $partNumber = trim($request->part_number);
        $partName = trim($request->part_name);

        $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;

        $stockAwal = is_numeric($request->stock_awal)
            ? (int) $request->stock_awal
            : (int) (
                RekapData::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('customer', $customer)
                    ->where('part_number', $partNumber)
                    ->where(function($q) use ($project) {
                        if ($project === '' || $project === null) {
                            $q->whereNull('kode_project')->orWhere('kode_project', '');
                        } else {
                            $q->where('kode_project', $project);
                        }
                    })
                    ->value('stock_awal_mip') ?? 0
            );

        $totalIn = 0;
        $totalOut = 0;

        for ($i = 1; $i <= $jumlahHari; $i++) {
            $totalIn += (int) $request->input("in_hari_{$i}", 0);
            $totalOut += (int) $request->input("out_hari_{$i}", 0);
        }

        // Cari header dengan query fleksibel
        $header = MonitoringMIPHeader::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('customer', $customer)
            ->where('part_number', $partNumber)
            ->where(function($q) use ($project) {
                if ($project === '' || $project === null) {
                    $q->whereNull('project')->orWhere('project', '');
                } else {
                    $q->where('project', $project);
                }
            })
            ->first();

        if ($header) {
            $header->update([
                'part_name' => $partName,
                'stock_awal' => $stockAwal,
                'level_min' => (int) $request->level_min,
                'level_safety' => (int) $request->level_safety,
                'level_max' => (int) $request->level_max,
                'total_out' => $totalOut,
                'total_in' => $totalIn,
            ]);
        } else {
            $header = MonitoringMIPHeader::create([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'customer' => $customer,
                'project' => $project ?? '',
                'part_number' => $partNumber,
                'part_name' => $partName,
                'stock_awal' => $stockAwal,
                'level_min' => (int) $request->level_min,
                'level_safety' => (int) $request->level_safety,
                'level_max' => (int) $request->level_max,
                'total_out' => $totalOut,
                'total_in' => $totalIn,
            ]);
        }

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

        RekapData::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('customer', $customer)
            ->where('part_number', $partNumber)
            ->where(function($q) use ($project) {
                if ($project === '' || $project === null) {
                    $q->whereNull('kode_project')->orWhere('kode_project', '');
                } else {
                    $q->where('kode_project', $project);
                }
            })->update([
                'stock_awal_mip' => $stockAwal
            ]);

        // --- OTOMATISASI STOK AWAL REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
        try {
            $nextMonth = $bulan == 12 ? 1 : $bulan + 1;
            $nextYear = $bulan == 12 ? $tahun + 1 : $tahun;

            $rekapNext = RekapData::where('bulan', $nextMonth)
                ->where('tahun', $nextYear)
                ->where('customer', $customer)
                ->where('part_number', $partNumber)
                ->where(function($q) use ($project) {
                    if ($project === '' || $project === null) {
                        $q->whereNull('kode_project')->orWhere('kode_project', '');
                    } else {
                        $q->where('kode_project', $project);
                    }
                })
                ->first();

            if ($rekapNext) {
                $rekapNext->update([
                    'models' => $partName,
                    'stock_awal_mip' => $balance
                ]);
            } else {
                RekapData::create([
                    'bulan' => $nextMonth,
                    'tahun' => $nextYear,
                    'customer' => $customer,
                    'kode_project' => $project ?? '',
                    'part_number' => $partNumber,
                    'models' => $partName,
                    'stock_awal_mip' => $balance
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal sinkronisasi Stok Awal Rekap Data bulan depan', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'balance' => $balance,
            'warning' => $balance < 0
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
            $project = trim((string) $request->project);

            $header = MonitoringMIPHeader::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->where('customer', $request->customer)
                ->where('part_number', $request->part_number)
                ->where(function($q) use ($project) {
                    if ($project === null || $project === '') {
                        $q->whereNull('project')->orWhere('project', '');
                    } else {
                        $q->where('project', $project);
                    }
                })
                ->first();

            if (!$header) {
                $header = MonitoringMIPHeader::create([
                    'bulan' => (int) $request->bulan,
                    'tahun' => (int) $request->tahun,
                    'customer' => $request->customer,
                    'project' => $project ?? '',
                    'part_number' => $request->part_number,
                    'part_name' => $request->part_name ?? '',
                    'stock_awal' => (int) $request->stock_awal,
                    'level_min' => 0,
                    'level_safety' => 0,
                    'level_max' => 0,
                    'total_out' => 0,
                    'total_in' => 0,
                ]);
            } else {
                $header->update([
                    'stock_awal' => (int) $request->stock_awal
                ]);
            }

            RekapData::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->where('customer', $request->customer)
                ->where('part_number', $request->part_number)
                ->where(function($q) use ($project) {
                    if ($project === null || $project === '') {
                        $q->whereNull('kode_project')->orWhere('kode_project', '');
                    } else {
                        $q->where('kode_project', $project);
                    }
                })->update([
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

            // --- OTOMATISASI STOK AWAL REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
            $nextMonth = (int)$request->bulan == 12 ? 1 : (int)$request->bulan + 1;
            $nextYear = (int)$request->bulan == 12 ? (int)$request->tahun + 1 : (int)$request->tahun;

            $rekapNext = RekapData::where('bulan', $nextMonth)
                ->where('tahun', $nextYear)
                ->where('customer', $request->customer)
                ->where('part_number', $request->part_number)
                ->where(function($q) use ($project) {
                    if ($project === null) {
                        $q->whereNull('kode_project')->orWhere('kode_project', '');
                    } else {
                        $q->where('kode_project', $project);
                    }
                })
                ->first();

            if ($rekapNext) {
                $rekapNext->update([
                    'models' => $header->part_name,
                    'stock_awal_mip' => $balance
                ]);
            } else {
                RekapData::create([
                    'bulan' => $nextMonth,
                    'tahun' => $nextYear,
                    'customer' => $request->customer,
                    'kode_project' => $project,
                    'part_number' => $request->part_number,
                    'models' => $header->part_name,
                    'stock_awal_mip' => $balance
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
                'message' => 'Gagal update stock awal: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $customer = $request->customer;

        return Excel::download(
            new MonitoringMIPExport($bulan, $tahun, $customer),
            "Monitoring_MIP_{$bulan}_{$tahun}.xlsx"
        );
    }
}
