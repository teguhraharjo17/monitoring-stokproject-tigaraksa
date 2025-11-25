<?php

namespace App\Http\Controllers\SpkPacking\ApproveDiketahui;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SpkPackingHeader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ApproveDiketahuiController extends Controller
{
    public function index()
    {
        $tanggalProsesList = SpkPackingHeader::select('tanggal_proses', 'approved_diketahui_at')
            ->orderBy('tanggal_proses', 'desc')
            ->get();

        return view('pages.spkpacking.approvediketahui.index', compact('tanggalProsesList'));
    }

    public function getDataByTanggal(Request $request)
    {
        $header = SpkPackingHeader::with('details')
            ->where('tanggal_proses', $request->tanggal_proses)
            ->get();

        return response()->json($header);
    }

    public function bulkUpdate(Request $request)
    {
        foreach ($request->details as $detail) {
            DB::table('spk_packing_details')->where('id', $detail['id'])->update([
                'wip' => $detail['wip'],
                'qty_spk_set' => $detail['qty_spk_set'],
                'refer_kanban_po' => $detail['refer_kanban_po'],
                'keterangan' => $detail['keterangan'],
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'Data berhasil disimpan.']);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'header_id' => 'required|exists:spk_packing_headers,id',
            'ttd_upload' => 'required|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $file = $request->file('ttd_upload');
        $filename = 'ttd_diketahui_' . $request->header_id . '.' . $file->getClientOriginalExtension();
        $file->storeAs('images/ttd_diketahui', $filename, 'public');

        DB::table('spk_packing_headers')->where('id', $request->header_id)->update([
            'approved_diketahui_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Berhasil disetujui oleh pihak Diketahui.']);
    }
}