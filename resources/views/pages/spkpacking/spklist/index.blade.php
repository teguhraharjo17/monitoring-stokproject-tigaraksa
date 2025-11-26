<x-default-layout>
    @section('title', 'SPK List - SPK Packing Member')

    <div class="card mt-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">SPK List</h3>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <input type="text" id="filter-tanggal-proses" class="form-control" placeholder="Filter Tanggal Proses" readonly>
                </div>
                <div class="col-md-2">
                    <button id="reset-filter" class="btn btn-secondary">Reset Filter</button>
                </div>
            </div>
            <table class="table table-bordered align-middle text-center" id="spk-list-table">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Tanggal Proses</th>
                        <th>Dibuat Tanggal</th>
                        <th>Terakhir Diubah</th>
                        <th>Status</th>
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
            let table = $('#spk-list-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('spkpacking.spklist.datatable') }}',
                    data: function (d) {
                        d.tanggal_proses = $('#filter-tanggal-proses').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'tanggal_proses', name: 'tanggal_proses' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'updated_at', name: 'updated_at', searchable: false, orderable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
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

            $('#filter-tanggal-proses').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Batal',
                    applyLabel: 'Terapkan',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#filter-tanggal-proses').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw();
            });

            $('#filter-tanggal-proses').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                table.draw();
            });

            $('#reset-filter').click(function () {
                $('#filter-tanggal-proses').val('');
                table.draw();
            });
        });
    </script>
</x-default-layout>
