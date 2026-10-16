<?php

namespace App\Http\Controllers\MasterItem;

use App\Models\MasterItem;
use App\Models\MasterCompany;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;

class MasterItemController extends Controller
{
    public function index()
    {
        $companies = MasterCompany::orderBy('kode')->get();

        return view('pages.masteritem.index', compact('companies'));
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $data = MasterItem::latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->addColumn('kode_project', fn($row) => $row->kode_project ?? '-')
                ->addColumn('status', function ($item) {
                    $checked = $item->is_active ? 'checked' : '';
                    $label = $item->is_active ? 'Aktif' : 'Nonaktif';
                    $labelClass = $item->is_active ? 'text-success' : 'text-danger';
                    return '
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input toggle-status" 
                                    type="checkbox" 
                                    data-id="' . $item->id . '" 
                                    ' . $checked . '
                                    style="cursor: pointer; width: 2.5em; height: 1.25em;">
                            </div>
                            <span class="badge-status ' . $labelClass . '" 
                                  id="status-label-' . $item->id . '"
                                  style="font-size: 0.75rem; font-weight: 600;">
                                ' . $label . '
                            </span>
                        </div>
                    ';
                })
                ->addColumn('action', function ($item) {
                    return '
                        <div class="d-flex justify-content-center gap-2">
                            <button 
                                class="btn btn-xs btn-light border btn-edit" 
                                data-id="' . $item->id . '" 
                                data-customer="' . e($item->customer) . '" 
                                data-part_number="' . e($item->part_number) . '" 
                                data-kode_project="' . e($item->kode_project) . '" 
                                data-nama_part="' . e($item->nama_part) . '">
                                <i class="fas fa-edit me-1"></i> Edit
                            </button>
                            <button 
                                class="btn btn-xs btn-light border btn-delete" 
                                data-id="' . $item->id . '">
                                <i class="fas fa-trash-alt me-1"></i> Hapus
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'nama_part' => 'required|string|max:255',
            'kode_project' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = MasterItem::where('customer', $request->customer)
            ->where('part_number', $request->part_number)
            ->where('nama_part', $request->nama_part)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => [
                    'nama_part' => ['Item dengan Customer, Part Number, dan Nama Part ini sudah terdaftar.']
                ]
            ], 422);
        }

        MasterItem::create($request->only(['customer', 'part_number', 'nama_part', 'kode_project']));

        return response()->json([
            'message' => 'Item berhasil ditambahkan'
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = MasterItem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer' => 'required|string|max:255',
            'part_number' => 'required|string|max:255',
            'nama_part' => 'required|string|max:255',
            'kode_project' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $exists = MasterItem::where('customer', $request->customer)
            ->where('part_number', $request->part_number)
            ->where('nama_part', $request->nama_part)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => [
                    'nama_part' => ['Item dengan Customer, Part Number, dan Nama Part ini sudah terdaftar.']
                ]
            ], 422);
        }

        $item->update($request->only(['customer', 'part_number', 'nama_part', 'kode_project']));

        return response()->json([
            'message' => 'Item berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        $item = MasterItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Item berhasil dihapus'
        ]);
    }

    /**
     * Toggle status aktif/nonaktif item
     */
    public function toggleStatus($id)
    {
        $item = MasterItem::findOrFail($id);

        $item->is_active = !$item->is_active;
        $item->deactivated_at = $item->is_active ? null : now();
        $item->save();

        $status = $item->is_active ? 'Aktif' : 'Nonaktif';

        return response()->json([
            'message' => "Status item berhasil diubah menjadi {$status}",
            'is_active' => $item->is_active,
            'deactivated_at' => $item->deactivated_at?->format('d-m-Y H:i'),
        ]);
    }
}

