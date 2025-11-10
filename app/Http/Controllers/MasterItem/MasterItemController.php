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
                ->rawColumns(['action'])
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
}
