<x-default-layout>
    @section('title', 'Master User')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Master User</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="user_table" class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Dibuat Pada</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow">
                <form id="formAddUser" action="{{ route('master.user.store') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Tambah User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="">Pilih Role</option>
                                <option value="Admin">Admin</option>
                                <option value="User">User</option>
                                <option value="PPIC">PPIC</option>
                                <option value="MIP">MIP</option>
                                <option value="Finish Good">Finish Good</option>
                                <option value="Packing">Packing</option>
                                <option value="Sub Assy">Sub Assy</option>
                                <option value="Diketahui">Diketahui</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow">
                <form id="formEditUser" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password <small>(Opsional, isi jika ingin ganti)</small></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="">Pilih Role</option>
                                <option value="Admin">Admin</option>
                                <option value="User">User</option>
                                <option value="PPIC">PPIC</option>
                                <option value="MIP">MIP</option>
                                <option value="Finish Good">Finish Good</option>
                                <option value="Packing">Packing</option>
                                <option value="Sub Assy">Sub Assy</option>
                                <option value="Diketahui">Diketahui</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4 text-white">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* ==== TABLE STYLING ==== */
        #user_table tbody tr:hover {
            background-color: #f5f7fa;
            cursor: pointer;
        }

        .dataTables_wrapper .dataTable {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.9rem;
            color: #333;
        }

        .dataTables_wrapper .dataTable th,
        .dataTables_wrapper .dataTable td {
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }

        .dataTables_wrapper .dataTable thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #444;
        }

        .dataTables_wrapper .dataTable tbody tr:nth-child(odd) {
            background-color: #fcfcfc;
        }

        .table-responsive {
            position: relative;
            overflow: visible;
        }

        /* ==== BUTTONS ==== */
        .custom-button {
            font-size: 0.875rem;
            padding: 6px 12px;
            border-radius: 4px;
            transition: 0.2s ease;
        }

        .custom-button:hover {
            color: #fff;
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-xs {
            font-size: 0.75rem;
            padding: 4px 10px;
            line-height: 1.3;
            min-width: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
            background: #f8f9fa;
            color: #333;
        }

        .btn-xs:hover {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        @media (max-width: 768px) {
            .dataTables_wrapper .dataTable {
                font-size: 0.8rem;
            }

            .custom-buttons-container {
                justify-content: center;
                margin-bottom: 10px;
            }

            .custom-button {
                margin-bottom: 5px;
            }
        }

        @media (max-width: 576px) {
            .opsi-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }

        /* ==== MODAL BASE ==== */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            background-color: #ffffff;
            transition: all 0.3s ease;
        }

        .modal-header {
            background-color: #fdfdfd;
            border-bottom: 1px solid #e6e6e6;
            padding: 1.25rem 1.75rem; /* ⬆️ lebih tinggi dan lebar */
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            align-items: center; /* biar icon & text sejajar */
            justify-content: space-between;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
            color: #222;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            background-color: #fafafa;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .modal.fade .modal-dialog {
            transform: translateY(-10%);
            transition: all 0.25s ease;
        }

        .modal.show .modal-dialog {
            transform: translateY(0);
        }

        /* ==== FORM INPUT (GARIS BAWAH STYLE) ==== */
        .modal-body label {
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .modal-body input.form-control,
        .modal-body select.form-select {
            border: none;
            border-bottom: 1.5px solid #dcdcdc;
            border-radius: 0;
            background: transparent;
            padding-left: 0;
            padding-right: 0;
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
            font-size: 0.95rem;
            color: #333;
            box-shadow: none;
            transition: border-color 0.2s ease, color 0.2s ease;
        }

        .modal-body input.form-control:focus,
        .modal-body select.form-select:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: none;
        }

        .modal-body input.form-control::placeholder {
            color: #aaa;
            font-size: 0.9rem;
        }

        .modal-body input.form-control:hover,
        .modal-body select.form-select:hover {
            border-color: #bfbfbf;
        }

        /* ==== TOGGLE PASSWORD ==== */
        .toggle-password {
            cursor: pointer;
            color: #aaa;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #0d6efd;
        }

        /* ==== FIELDSET (OPTIONAL SECTION STYLE) ==== */
        fieldset.border {
            border: 1px dashed #e3e3e3 !important;
            padding: 1.5rem;
            margin-top: 1rem;
            border-radius: 6px;
            position: relative;
        }

        fieldset.border legend {
            float: unset;
            background: #fff;
            padding: 0 0.5rem;
            margin-left: 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #444;
        }

        /* ==== SCROLL TABLE ==== */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #cfcfcf #f9f9f9;
        }

        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background-color: #cfcfcf;
            border-radius: 4px;
        }
    </style>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            const table = $('#user_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master.user.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'username', name: 'username' },
                    { data: 'role', name: 'role' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                dom: '<"row mb-3"' +
                        '<"col-md-6 d-flex align-items-center gap-2"B>' +
                        '<"col-md-6 text-end"f>>' +
                     '<"row"<"col-sm-12 table-responsive"t>>' +
                     '<"row mt-3"' +
                        '<"col-sm-6"l><"col-sm-6 text-end"p>>',
                buttons: [
                    {
                        text: '<i class="fas fa-plus"></i> Tambah User',
                        className: 'btn btn-sm btn-primary',
                        action: function () {
                            $('#formAddUser')[0].reset();
                            $('#addUserModal').modal('show');
                        }
                    }
                ],
                language: {
                    searchPlaceholder: "Cari...",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            // Submit Add
            $('#formAddUser').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    beforeSend: () => Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() }),
                    success: res => {
                        $('#addUserModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message });
                    },
                    error: err => {
                        let msg = 'Gagal menyimpan user.';
                        if (err.responseJSON?.errors) {
                            msg = Object.values(err.responseJSON.errors).join('\n');
                        }
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: msg });
                    }
                });
            });

            // Show Edit
            $(document).on('click', '.btn-edit', function () {
                const data = $(this).data();
                $('#edit_id').val(data.id);
                $('#edit_name').val(data.name);
                $('#edit_username').val(data.username);
                $('#edit_role').val(data.role);
                $('#editUserModal').modal('show');
            });

            // Submit Edit
            $('#formEditUser').on('submit', function (e) {
                e.preventDefault();
                const id = $('#edit_id').val();
                const url = "{{ route('master.user.update', ':id') }}".replace(':id', id);
                $.ajax({
                    url,
                    method: 'POST',
                    data: $(this).serialize(),
                    beforeSend: () => Swal.fire({ title: 'Memperbarui...', didOpen: () => Swal.showLoading() }),
                    success: res => {
                        $('#editUserModal').modal('hide');
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message });
                    },
                    error: err => {
                        let msg = 'Update gagal.';
                        if (err.responseJSON?.errors) {
                            msg = Object.values(err.responseJSON.errors).join('\n');
                        }
                        Swal.fire({ icon: 'error', title: 'Gagal!', text: msg });
                    }
                });
            });

            // Delete
            $(document).on('click', '.btn-delete', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Yakin hapus user ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('master.user.destroy', ':id') }}".replace(':id', id),
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: res => {
                                table.ajax.reload();
                                Swal.fire({ icon: 'success', title: 'Dihapus!', text: res.message });
                            },
                            error: () => Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal menghapus user.' })
                        });
                    }
                });
            });
        });
    </script>
</x-default-layout>
