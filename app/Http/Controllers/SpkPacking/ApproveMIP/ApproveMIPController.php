<?php

namespace App\Http\Controllers\SpkPacking\ApproveMIP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SpkPackingHeader;
use App\Models\SpkPackingDetail;
use Carbon\Carbon;

class ApproveMIPController extends Controller
{
    public function index()
    {
        $tanggalProsesList = SpkPackingHeader::orderByDesc('tanggal_proses')->get();
        return view('pages.spkpacking.approvemip.index', compact('tanggalProsesList'));
    }

    public function getDataByTanggal(Request $request)
    {
        $tanggal = $request->tanggal_proses;

        $headers = SpkPackingHeader::with(['details'])
            ->where('tanggal_proses', $tanggal)
            ->get();

        return response()->json($headers);
    }

    public function bulkUpdate(Request $request)
    {
        foreach ($request->details as $detail) {
            $row = SpkPackingDetail::find($detail['id']);
            if (!$row) continue;

            $row->update([
                'wip' => $detail['wip'] ?? 0,
                'qty_spk_set' => $detail['qty_spk_set'] ?? 0,
                'refer_kanban_po' => $detail['refer_kanban_po'],
                'keterangan' => $detail['keterangan'],
            ]);
        }

        return response()->json(['message' => 'Berhasil menyimpan perubahan!']);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'header_id' => 'required|exists:spk_packing_headers,id',
            'ttd_upload' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $header = SpkPackingHeader::findOrFail($request->header_id);

        $file = $request->file('ttd_upload');
        $filename = 'ttd_mip_' . $header->id . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('images/ttd_mip', $filename, 'public');

        $header->update([
            'approved_mip_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Berhasil disetujui oleh MIP!',
            'ttd_path' => $path,
        ]);
    }
}
