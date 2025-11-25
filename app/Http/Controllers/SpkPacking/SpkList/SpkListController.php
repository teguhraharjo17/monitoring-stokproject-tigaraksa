<?php

namespace App\Http\Controllers\SpkPacking\SpkList;

use App\Http\Controllers\Controller;
use App\Models\SpkPackingHeader;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\SpkPackingExport;
use Maatwebsite\Excel\Facades\Excel;

class SpkListController extends Controller
{
    public function index()
    {
        return view('pages.spkpacking.spklist.index');
    }

    public function datatable(Request $request)
    {
        $query = SpkPackingHeader::with('details');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal_proses', function ($row) {
                return $row->tanggal_proses
                    ? \Carbon\Carbon::parse($row->tanggal_proses)->format('d M Y')
                    : '-';
            })
            ->editColumn('created_at', fn($row) => optional($row->created_at)->format('d M Y H:i'))
            ->addColumn('updated_at', function ($row) {
                return $row->details->max('updated_at')?->format('d M Y H:i') ?? '-';
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('spkpacking.spklist.export', $row->id) . '" class="btn btn-sm btn-success">📥 Export</a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function export($id)
    {
        $header = SpkPackingHeader::with('details')->findOrFail($id);
        $filename = 'SPK_' . $header->tanggal_proses . '.xlsx';

        $export = new \App\Exports\SpkPackingExport($header);
        return $export->export();
    }
}
