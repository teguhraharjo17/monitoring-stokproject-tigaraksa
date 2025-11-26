<?php

namespace App\Http\Controllers\MasterUser;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MasterUserController extends Controller
{
    public function index()
    {
        return view('pages.masteruser.index');
    }

    public function data(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select(['id', 'name', 'username', 'role', 'created_at']);

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->addColumn('action', function ($user) {
                    return '
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-xs btn-light border btn-edit"
                                data-id="' . $user->id . '"
                                data-name="' . e($user->name) . '"
                                data-username="' . e($user->username) . '"
                                data-role="' . e($user->role) . '">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-xs btn-light border btn-delete"
                                data-id="' . $user->id . '">
                                <i class="fas fa-trash-alt me-1"></i>Hapus
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
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:Admin,User,PPIC,MIP,Finish Good,Packing,Diketahui,Sub Assy',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return response()->json(['message' => 'User berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'role' => 'required|string|in:Admin,User,PPIC,MIP,Finish Good,Packing,Diketahui,Sub Assy',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'username', 'role']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(['message' => 'User berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }
}
