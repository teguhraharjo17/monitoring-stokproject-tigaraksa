<?php

namespace App\Http\Controllers\SpkPacking\ApproveFG;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SpkPackingHeader;
use App\Models\SpkPackingDetail;
use Carbon\Carbon;

class ApproveFGController extends Controller
{
    public function index()
    {
        $tanggalProsesList = SpkPackingHeader::orderByDesc('tanggal_proses')->get();
        return view('pages.spkpacking.approvefg.index', compact('tanggalProsesList'));
    }

    public function getDataByTanggal(Request $request)
    {
        $headers = SpkPackingHeader::with('details')
            ->where('tanggal_proses', $request->tanggal_proses)
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
        $filename = 'ttd_fg_' . $header->id . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('images/ttd_fg', $filename, 'public');

        $header->update([
            'approved_fg_at' => Carbon::now(),
            'approved_fg_path' => $path,
        ]);

        return response()->json([
            'message' => 'Berhasil disetujui oleh Finish Good!',
            'ttd_path' => $path,
        ]);
    }
}
