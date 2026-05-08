<?php

namespace App\Http\Controllers\MonitoringFinishGoods;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitoringFGHeader;
use App\Models\MonitoringFGDetail;
use App\Models\RekapData;
use App\Models\MonitoringMIPHeader;
use App\Models\MonitoringMIPDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Exports\FinishGoodsExport;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringFinishGoodsController extends Controller
{
    public function index()
    {
        return view('pages.monitoringfinishgood.redesign');
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

            if ($request->only_customer) {
                $rekapCustomer = RekapData::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->pluck('customer');

                $fgCustomer = MonitoringFGHeader::where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->pluck('customer');

                $customers = $rekapCustomer
                    ->merge($fgCustomer)
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
            foreach ($rekapListRaw as $rekap) {
                $key = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number) . '|' . $this->normalize($rekap->models);
                if (!isset($aggregatedRekap[$key])) {
                    $aggregatedRekap[$key] = $rekap;
                } else {
                    $aggregatedRekap[$key]->total_qty_bulan_ini += $rekap->total_qty_bulan_ini;
                    $aggregatedRekap[$key]->stock_awal_fg = max($aggregatedRekap[$key]->stock_awal_fg, $rekap->stock_awal_fg);
                }
            }

            // 2. Bulk Load Level Stok
            $levelStokMap = DB::table('level_stok_detail as d')
                ->join('level_stok as l', 'l.id', '=', 'd.level_stok_id')
                ->where('l.bulan', $bulan)
                ->where('l.tahun', $tahun)
                ->get()
                ->keyBy(fn($l) => $this->normalize($l->customer) . '|' . $this->normalize($l->kode_projek) . '|' . $this->normalize($l->part_number));

            // 3. Bulk Load FG Headers & Details
            $fgHeaders = MonitoringFGHeader::with('details')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

            // 4. Bulk Load MIP Headers & Details
            $mipHeaders = MonitoringMIPHeader::with('details')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()
                ->keyBy(fn($h) => $this->normalize($h->customer) . '|' . $this->normalize($h->project) . '|' . $this->normalize($h->part_number));

            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
            $data = [];

            foreach ($aggregatedRekap as $rekap) {
                $matchKey = $this->normalize($rekap->customer) . '|' . $this->normalize($rekap->kode_project) . '|' . $this->normalize($rekap->part_number);
                
                $level = $levelStokMap[$matchKey] ?? null;
                $header = $fgHeaders[$matchKey] ?? null;
                $details = $header ? $header->details->keyBy('tanggal') : collect();
                
                $mipHeader = $mipHeaders[$matchKey] ?? null;
                $mipDetails = $mipHeader ? $mipHeader->details->keyBy('tanggal') : collect();

                $stockAwal = (int) ($header->stock_awal ?? ($rekap->stock_awal_fg ?? 0));
                
                $row = [
                    'id' => $header->id ?? null,
                    'customer' => $rekap->customer,
                    'project' => $rekap->kode_project,
                    'part_number' => $rekap->part_number,
                    'part_name' => $rekap->models,
                    'total_po' => (int) ($rekap->total_qty_bulan_ini ?? 0),
                    'stock_awal' => $stockAwal,
                    'total_in' => (int) ($header->total_in ?? 0),
                    'total_out' => (int) ($header->total_out ?? 0),
                    'level_min' => (int) ($level->min ?? ($header->level_min ?? 0)),
                    'level_safety' => (int) ($level->safety_fg ?? ($header->level_safety ?? 0)),
                    'level_max' => (int) ($level->max ?? ($header->level_max ?? 0)),
                ];

                $balance = $stockAwal;
                $totalInActual = 0;
                $totalOutActual = 0;

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

                    $totalInActual += ($inD + $inN);
                    $totalOutActual += ($outD + $outN);
                    $balance = $balanceN;
                }

                $advance = (int) ($header->advance_delivery ?? 0);
                $outstanding = max(0, $row['total_po'] - $advance - $totalOutActual);
                $percentage = $row['total_po'] > 0 ? round(($totalOutActual / $row['total_po']) * 100, 2) : 0;

                $row['advance_delivery'] = $advance;
                $row['outstanding'] = $outstanding;
                $row['percentage'] = $percentage;
                $row['stock_on_hand'] = $balance;
                $row['total_in'] = $totalInActual;
                $row['total_out'] = $totalOutActual;

                if ($row['stock_on_hand'] <= $row['level_min']) {
                    $row['status_stock'] = 'Problem';
                } elseif ($row['stock_on_hand'] > $row['level_max']) {
                    $row['status_stock'] = 'Over';
                } else {
                    $row['status_stock'] = 'Aman';
                }

                $data[] = $row;
            }

            return response()->json([
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            Log::error('MonitoringFinishGoodsController::data Error: ' . $e->getMessage(), [
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
        DB::beginTransaction();

        try {
            $bulan = (int) $request->bulan;
            $tahun = (int) $request->tahun;
            $customer = trim($request->customer);
            $project = trim((string) $request->project);
            $partNumber = trim($request->part_number);
            $partName = trim($request->part_name);

            $header = MonitoringFGHeader::updateOrCreate([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'customer' => $customer,
                'project' => $project,
                'part_number' => $partNumber,
            ], [
                'part_name' => $partName,
                'stock_awal' => (int) $request->stock_awal,
                'advance_delivery' => (int) $request->advance_delivery,
                'level_min' => (int) $request->level_min,
                'level_safety' => (int) $request->level_safety,
                'level_max' => (int) $request->level_max,
            ]);

            $totalIn = 0;
            $totalOut = 0;
            $jumlahHari = Carbon::createFromDate($tahun, $bulan, 1)->daysInMonth;
            $balance = (int) $header->stock_awal;

            for ($i = 1; $i <= $jumlahHari; $i++) {
                $inD = (int) $request->input("in_hari_{$i}_d", 0);
                $inN = (int) $request->input("in_hari_{$i}_n", 0);
                $outD = (int) $request->input("out_hari_{$i}_d", 0);
                $outN = (int) $request->input("out_hari_{$i}_n", 0);

                $balanceD = $balance + $inD - $outD;
                $balanceN = $balanceD + $inN - $outN;

                $totalIn += $inD + $inN;
                $totalOut += $outD + $outN;

                MonitoringFGDetail::updateOrCreate(
                    [
                        'fg_header_id' => $header->id,
                        'tanggal' => $i,
                    ],
                    [
                        'in_qty_d' => $inD,
                        'in_qty_n' => $inN,
                        'out_qty_d' => $outD,
                        'out_qty_n' => $outN,
                        'balance_d' => $balanceD,
                        'balance_n' => $balanceN,
                    ]
                );

                $balance = $balanceN;
            }

            $header->update([
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ]);

            RekapData::where([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'customer' => $customer,
                'kode_project' => $project,
                'part_number' => $partNumber,
            ])->update([
                'stock_awal_fg' => (int) $request->stock_awal
            ]);

            // --- OTOMATISASI STOK AWAL FG REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
            try {
                $nextMonth = $bulan == 12 ? 1 : $bulan + 1;
                $nextYear = $bulan == 12 ? $tahun + 1 : $tahun;

                RekapData::updateOrCreate(
                    [
                        'bulan' => $nextMonth,
                        'tahun' => $nextYear,
                        'customer' => $customer,
                        'kode_project' => $project,
                        'part_number' => $partNumber,
                    ],
                    [
                        'models' => $partName,
                        'stock_awal_fg' => $balance
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Gagal sinkronisasi Stok Awal FG Rekap Data bulan depan', ['error' => $e->getMessage()]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'balance' => $balance,
                'warning' => $balance < 0
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Save Monitoring FG gagal', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStockAwal(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer',
            'tahun' => 'required|integer',
            'customer' => 'required|string',
            'kode_project' => 'nullable|string',
            'part_number' => 'required|string',
            'stock_awal' => 'required|integer',
        ]);

        DB::beginTransaction();

        try {
            $project = trim((string) $request->kode_project);

            $header = MonitoringFGHeader::where([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'customer' => $request->customer,
                'project' => $project,
                'part_number' => $request->part_number,
            ])->firstOrFail();

            $header->update([
                'stock_awal' => $request->stock_awal
            ]);

            RekapData::where([
                'bulan' => $request->bulan,
                'tahun' => $request->tahun,
                'customer' => $request->customer,
                'kode_project' => $project,
                'part_number' => $request->part_number,
            ])->update([
                'stock_awal_fg' => $request->stock_awal
            ]);

            $details = MonitoringFGDetail::where('fg_header_id', $header->id)
                ->orderBy('tanggal')
                ->get();

            $balance = (int) $request->stock_awal;

            foreach ($details as $detail) {
                $balanceD = $balance + $detail->in_qty_d - $detail->out_qty_d;
                $balanceN = $balanceD + $detail->in_qty_n - $detail->out_qty_n;

                $detail->update([
                    'balance_d' => $balanceD,
                    'balance_n' => $balanceN,
                ]);

                $balance = $balanceN;
            }

            // --- OTOMATISASI STOK AWAL FG REKAP DATA BULAN DEPAN (100% OTOMATIS) ---
            $nextMonth = (int)$request->bulan == 12 ? 1 : (int)$request->bulan + 1;
            $nextYear = (int)$request->bulan == 12 ? (int)$request->tahun + 1 : (int)$request->tahun;

            RekapData::updateOrCreate(
                [
                    'bulan' => $nextMonth,
                    'tahun' => $nextYear,
                    'customer' => $request->customer,
                    'kode_project' => $project,
                    'part_number' => $request->part_number,
                ],
                [
                    'models' => $header->part_name,
                    'stock_awal_fg' => $balance
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'balance' => $balance,
                'warning' => $balance < 0
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Update Stock Awal FG gagal', [
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
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');
        $fileName = "Monitoring_Finish_Goods_{$namaBulan}_{$tahun}.xlsx";

        return Excel::download(new FinishGoodsExport($bulan, $tahun), $fileName);
    }
}
