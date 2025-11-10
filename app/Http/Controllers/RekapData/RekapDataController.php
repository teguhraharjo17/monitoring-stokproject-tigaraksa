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

        return view('pages.rekapdata.index', compact('masterItems'));
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $bulan = $request->bulan ?? now()->month;
            $tahun = $request->tahun ?? now()->year;

            $data = RekapData::bulan($bulan)->tahun($tahun)->orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
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
            ->where('kode_project', $validated['kode_project'])
            ->where('models', $validated['models']);

        if ($request->filled('id')) {
            // Jika update, jangan hitung dirinya sendiri
            $duplikat->where('id', '!=', $request->id);
        }

        if ($duplikat->exists()) {
            return response()->json(['error' => 'Data dengan Part Name ini sudah ada.'], 422);
        }

        if ($request->filled('id')) {
            $rekap = RekapData::find($request->id);

            if (!$rekap) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            $rekap->update($validated);
            return response()->json(['status' => 'updated', 'id' => $rekap->id]);
        }

        $rekap = RekapData::create($validated);

        return response()->json([
            'status' => 'created',
            'id' => $rekap->id,
            'data' => $rekap
        ]);
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
