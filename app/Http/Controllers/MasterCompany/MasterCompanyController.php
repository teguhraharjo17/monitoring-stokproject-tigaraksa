<?php

namespace App\Http\Controllers\MasterCompany;

use App\Models\MasterCompany;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;

class MasterCompanyController extends Controller
{
    public function index()
    {
        return view('pages.mastercompany.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $data = MasterCompany::latest()->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->addColumn('action', function ($item) {
                    return '
                        <div class="d-flex justify-content-center gap-2">
                            <button 
                                class="btn btn-xs btn-light border btn-edit" 
                                data-id="' . $item->id . '" 
                                data-kode="' . e($item->kode) . '" 
                                data-keterangan="' . e($item->keterangan) . '">
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
            'kode' => 'required|string|max:100|unique:master_companies,kode',
            'keterangan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        MasterCompany::create($request->only(['kode', 'keterangan']));

        return response()->json(['message' => 'Data berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $company = MasterCompany::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:100|unique:master_companies,kode,' . $id,
            'keterangan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal'], 422);
        }

        $company->update($request->only(['kode', 'keterangan']));

        return response()->json(['message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $company = MasterCompany::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
