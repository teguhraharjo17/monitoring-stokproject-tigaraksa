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

        if ($request->filled('tanggal_proses')) {
            [$start, $end] = explode(' - ', $request->tanggal_proses);
            $query->whereBetween('tanggal_proses', [$start, $end]);
        }

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
            ->addColumn('status', function ($row) {
                $statuses = [
                    'PPIC'       => $row->approved_ppic_at,
                    'MIP'        => $row->approved_mip_at,
                    'Finish Good'=> $row->approved_fg_at,
                    'Packing'    => $row->approved_packing_member_at,
                    'Diketahui'  => $row->approved_diketahui_at,
                ];

                $result = '<div class="d-flex flex-column align-items-start gap-1">';
                foreach ($statuses as $label => $approvedAt) {
                    $badge = $approvedAt
                        ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>' . $label . '</span>'
                        : '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>' . $label . '</span>';
                    $result .= $badge;
                }
                $result .= '</div>';
                return $result;
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('spkpacking.spklist.export', $row->id) . '" class="btn btn-sm btn-success">📥 Export</a>';
            })
            ->rawColumns(['action', 'status'])
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
