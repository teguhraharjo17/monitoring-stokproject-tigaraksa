<x-default-layout>
    @section('title', 'Data Kanban - Average Week')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Data Kanban - Average Week</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filter_bulan" class="form-label">Bulan</label>
                    <select id="filter_bulan" class="form-select">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_tahun" class="form-label">Tahun</label>
                    <select id="filter_tahun" class="form-select">
                        @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                    <button id="reload_table" class="btn btn-primary">🔄 Refresh Data</button>
                    <a
                        id="btn_export_excel"
                        class="btn btn-success"
                        data-base-url="{{ route('datakanban.averageweek.export') }}"
                        href="#"
                    >
                        📤 Export Excel
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="avgweek_table" class="table table-bordered table-striped w-100">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Customer</th>
                            <th rowspan="2">Part Number</th>
                            <th rowspan="2">Models</th>
                            <th colspan="4" class="text-center">Sum Per Weekly</th>
                        </tr>
                        <tr>
                            <th class="text-center">I</th>
                            <th class="text-center">II</th>
                            <th class="text-center">III</th>
                            <th class="text-center">IV</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Style --}}
    <style>
        .table-responsive {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding-bottom: 5px;
        }

        #avgweek_table th,
        #avgweek_table td {
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        #avgweek_table thead th {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Hover effect */
        #avgweek_table tbody tr:hover {
            background-color: #e2f0ff !important;
            cursor: pointer;
        }

        #avgweek_table tbody tr {
            transition: background-color 0.2s ease;
        }
    </style>

    {{-- Script --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function () {
            $('#avgweek_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                paging: true,
                scrollX: true,
                ajax: {
                    url: "{{ route('datakanban.averageweek.data') }}"
                },
                columns: [
                    { data: 'no' },
                    { data: 'customer' },
                    { data: 'part_number' },
                    { data: 'models' },
                    { data: 'minggu_1' },
                    { data: 'minggu_2' },
                    { data: 'minggu_3' },
                    { data: 'minggu_4' }
                ],
                initComplete: function () {
                    this.api().columns.adjust();
                }
            });

            function getFilterParams() {
                return {
                    bulan: parseInt($('#filter_bulan').val()),
                    tahun: parseInt($('#filter_tahun').val())
                };
            }

            function updateExportLink() {
                const { bulan, tahun } = getFilterParams();
                const baseUrl = $('#btn_export_excel').data('base-url');
                const finalUrl = `${baseUrl}?bulan=${bulan}&tahun=${tahun}`;
                $('#btn_export_excel').attr('href', finalUrl);
            }

            function reloadTable() {
                const { bulan, tahun } = getFilterParams();

                if ($.fn.DataTable.isDataTable('#avgweek_table')) {
                    $('#avgweek_table').DataTable().clear().destroy();
                }

                $('#avgweek_table').DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    paging: true,
                    scrollX: true,
                    ajax: {
                        url: "{{ route('datakanban.averageweek.data') }}",
                        data: {
                            bulan,
                            tahun
                        }
                    },
                    columns: [
                        { data: 'no' },
                        { data: 'customer' },
                        { data: 'part_number' },
                        { data: 'models' },
                        { data: 'minggu_1' },
                        { data: 'minggu_2' },
                        { data: 'minggu_3' },
                        { data: 'minggu_4' }
                    ],
                    initComplete: function () {
                        this.api().columns.adjust();
                    }
                });
            }

            $(document).ready(function () {
                reloadTable();
                updateExportLink();

                $('#filter_bulan, #filter_tahun, #reload_table').on('change click', function () {
                    reloadTable();
                    updateExportLink();
                });
            });
        });
    </script>
</x-default-layout>
