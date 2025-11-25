<?php

namespace App\Http\Controllers\SpkPacking\ApprovePPIC;

use App\Http\Controllers\Controller;
use App\Models\SpkPackingHeader;
use App\Models\SpkPackingDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApprovePPICController extends Controller
{
    public function index()
    {
        $tanggalProsesList = SpkPackingHeader::select('tanggal_proses', 'approved_ppic_at')
                ->orderBy('tanggal_proses', 'desc')
                ->get();

        return view('pages.spkpacking.approveppic.index', compact('tanggalProsesList'));
    }

    public function getDataByTanggal(Request $request)
    {
        $tanggal = $request->input('tanggal_proses');

        $headers = SpkPackingHeader::with('details')
            ->where('tanggal_proses', $tanggal)
            ->get();

        return response()->json($headers);
    }

    public function updateDetail(Request $request, $id)
    {
        $detail = SpkPackingDetail::findOrFail($id);

        $validated = $request->validate([
            'wip' => 'nullable|numeric',
            'qty_spk_set' => 'nullable|numeric',
            'refer_kanban_po' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        $detail->update($validated);

        return response()->json(['message' => 'Data berhasil diperbarui.']);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'details' => 'required|array',
            'details.*.id' => 'required|exists:spk_packing_details,id',
            'details.*.wip' => 'nullable|numeric',
            'details.*.qty_spk_set' => 'nullable|numeric',
            'details.*.refer_kanban_po' => 'nullable|string',
            'details.*.keterangan' => 'nullable|string',
        ]);

        foreach ($validated['details'] as $detailData) {
            $detail = \App\Models\SpkPackingDetail::find($detailData['id']);
            $detail->update([
                'wip' => $detailData['wip'] ?? 0,
                'qty_spk_set' => $detailData['qty_spk_set'] ?? 0,
                'refer_kanban_po' => $detailData['refer_kanban_po'] ?? '',
                'keterangan' => $detailData['keterangan'] ?? '',
            ]);
        }

        return response()->json(['message' => 'Semua data berhasil diperbarui.']);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'header_id' => 'required|exists:spk_packing_headers,id',
            'ttd_upload' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($request->hasFile('ttd_upload')) {
            $path = $request->file('ttd_upload')->store('public/images/ttd_ppic');

            $header = SpkPackingHeader::find($request->header_id);
            $header->update([
                'approved_ppic_at' => now(),
            ]);
        } else {
            return response()->json(['message' => 'Upload gambar tanda tangan wajib diisi.'], 422);
        }

        return response()->json(['message' => 'Approve SPK berhasil disimpan.']);
    }

}
