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
                $steps = [
                    ['label' => 'PPIC', 'at' => $row->approved_ppic_at, 'icon' => 'fa-user-gear'],
                    ['label' => 'MIP',  'at' => $row->approved_mip_at,  'icon' => 'fa-boxes-stacked'],
                    ['label' => 'FG',   'at' => $row->approved_fg_at,   'icon' => 'fa-truck-fast'],
                    ['label' => 'Pack', 'at' => $row->approved_packing_member_at, 'icon' => 'fa-box-open'],
                    ['label' => 'Spv',  'at' => $row->approved_diketahui_at, 'icon' => 'fa-user-check'],
                ];

                $html = '<div class="approval-tracker d-flex justify-content-center gap-2">';
                foreach ($steps as $step) {
                    $isApproved = !empty($step['at']);
                    $dateText = $isApproved ? \Carbon\Carbon::parse($step['at'])->format('d/m') : '-';
                    $timeText = $isApproved ? \Carbon\Carbon::parse($step['at'])->format('H:i') : '';
                    $statusClass = $isApproved ? 'approved' : 'pending';
                    
                    $html .= '
                        <div class="approval-step ' . $statusClass . '">
                            <div class="step-icon"><i class="fas ' . $step['icon'] . '"></i></div>
                            <div class="step-label">' . $step['label'] . '</div>
                            <div class="step-date">' . $dateText . '</div>
                            <div class="step-time">' . $timeText . '</div>
                        </div>';
                }
                $html .= '</div>';
                return $html;
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
