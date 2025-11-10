<?php

namespace App\Http\Controllers\LevelStock;

use App\Models\LevelStok;
use App\Models\LevelStokDetail;
use App\Models\MasterItem;
use App\Models\RekapData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class LevelStockController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $selectedBulan = $request->bulan ?? $now->format('m');
        $selectedTahun = $request->tahun ?? $now->format('Y');

        $latestLevel = LevelStok::where('bulan', $selectedBulan)
            ->where('tahun', $selectedTahun)
            ->first();

        $masterItems = MasterItem::select('part_number', 'customer', 'kode_project', 'nama_part')->get();

        return view('pages.levelstock.index', compact(
            'selectedBulan',
            'selectedTahun',
            'latestLevel',
            'masterItems'
        ));
    }

    public function data(Request $request)
    {
        try {
            $bulan = (int)($request->bulan ?? now()->month);
            $tahun = (int)($request->tahun ?? now()->year);

            // Pastikan level_stok ada
            $levelStok = LevelStok::firstOrCreate(
                ['bulan' => $bulan, 'tahun' => $tahun],
                ['jumlah_hari_kerja_atas_100' => 0, 'jumlah_hari_kerja_bawah_100' => 0]
            );

            $levelStokId = $levelStok->id;

            // Ambil override data dari level_stok_detail
            $overrideDetails = LevelStokDetail::where('level_stok_id', $levelStokId)->get();

            // Ambil semua item dari rekap_data
            $rekapItems = RekapData::where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            // Merge data rekap + override (jika ada)
            $finalData = $rekapItems->map(function ($item) use ($overrideDetails) {
                $matchedOverride = $overrideDetails->first(function ($detail) use ($item) {
                    return trim($detail->part_number) === trim($item->part_number)
                        && trim($detail->customer) === trim($item->customer)
                        && trim($detail->kode_projek) === trim($item->kode_project)
                        && trim($detail->models) === trim($item->models);
                });

                return [
                    'id' => $matchedOverride->id ?? null,
                    'part_number' => $item->part_number,
                    'customer' => $item->customer,
                    'kode_projek' => $item->kode_project,
                    'models' => $item->models,
                    'min' => $matchedOverride->min ?? null,
                    'safety_mip' => $matchedOverride->safety_mip ?? null,
                    'safety_fg' => $matchedOverride->safety_fg ?? null,
                    'max' => $matchedOverride->max ?? null,
                    'qty_set_box' => $matchedOverride->qty_set_box ?? null,
                ];
            })->values();

            return DataTables::of($finalData->toArray())
                ->setRowId(fn ($row) => $row['id'] ?? 'row_' . uniqid())
                ->make(true);

        } catch (\Throwable $e) {
            Log::error('ERROR get data level stok', ['message' => $e->getMessage()]);
            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function storeDetail(Request $request)
    {
        // Kosongkan field null agar tidak disimpan string kosong
        foreach (['min', 'safety_mip', 'safety_fg', 'max', 'qty_set_box'] as $field) {
            if ($request->has($field) && $request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        $validated = $request->validate([
            'level_stok_id' => 'required|exists:level_stok,id',
            'part_number' => 'required|string',
            'customer' => 'nullable|string',
            'kode_projek' => 'nullable|string',
            'models' => 'nullable|string',
            'min' => 'nullable|integer',
            'safety_mip' => 'nullable|integer',
            'safety_fg' => 'nullable|integer',
            'max' => 'nullable|integer',
            'qty_set_box' => 'nullable|integer',
        ]);

        if ($request->filled('id')) {
            $detail = LevelStokDetail::find($request->id);
            if (!$detail) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            $detail->update($validated);
            return response()->json(['status' => 'updated', 'id' => $detail->id]);
        }

        $detail = LevelStokDetail::create($validated);
        return response()->json(['status' => 'created', 'id' => $detail->id]);
    }

    public function updateJumlahHariKerja(Request $request)
    {
        $validated = $request->validate([
            'field' => 'required|in:jumlah_hari_kerja_atas_100,jumlah_hari_kerja_bawah_100',
            'value' => 'required|integer|min:0',
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020'
        ]);

        $levelStok = LevelStok::firstOrCreate(
            ['bulan' => $validated['bulan'], 'tahun' => $validated['tahun']],
            ['jumlah_hari_kerja_atas_100' => 0, 'jumlah_hari_kerja_bawah_100' => 0]
        );

        $levelStok->update([
            $validated['field'] => $validated['value'],
        ]);

        return response()->json(['success' => true]);
    }

    public function getJumlahHariKerja(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
        ]);

        $level = LevelStok::where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->first();

        return response()->json([
            'hari_atas_100' => $level?->jumlah_hari_kerja_atas_100 ?? 0,
            'hari_bawah_100' => $level?->jumlah_hari_kerja_bawah_100 ?? 0,
        ]);
    }

    public function getLevelStokId(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020',
        ]);

        $level = LevelStok::where('bulan', $request->bulan)
            ->where('tahun', $request->tahun)
            ->first();

        return response()->json(['id' => $level?->id]);
    }
}
