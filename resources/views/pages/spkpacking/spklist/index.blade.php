<x-default-layout>
    @section('title', 'SPK List - SPK Packing Member')

    <div class="card mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">SPK List</h3>
        </div>

        <div class="card-body">
            <table class="table table-bordered align-middle text-center" id="spk-list-table">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal Proses</th>
                        <th>Dibuat Tanggal</th>
                        <th>Terakhir Diubah</th>
                        <th>Export Excel</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            $('#spk-list-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('spkpacking.spklist.datatable') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'tanggal_proses', name: 'tanggal_proses' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'updated_at', name: 'updated_at', searchable: false, orderable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data tersedia",
                    processing: "Memuat data...",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "›",
                        previous: "‹"
                    },
                }
            });
        });
    </script>
</x-default-layout>
