<x-default-layout>
    @section('title', 'Data Kanban - O/S Per Weekly')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Data Kanban - O/S Per Weekly</h3>
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
                        data-base-url="{{ route('datakanban.osperweek.export') }}"
                        href="#"
                    >
                        📤 Export Excel
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="osperweek_table" class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th rowspan="2">No</th>
                            <th rowspan="2">Customer</th>
                            <th rowspan="2">Part Number</th>
                            <th rowspan="2">Models</th>
                            <th rowspan="2">PO</th>
                            <th colspan="2" class="text-center">Week 1</th>
                            <th colspan="2" class="text-center">Week 2</th>
                            <th colspan="2" class="text-center">Week 3</th>
                            <th colspan="2" class="text-center">Week 4</th>
                        </tr>
                        <tr>
                            <th>Delivery</th>
                            <th>O/S</th>
                            <th>Delivery</th>
                            <th>O/S</th>
                            <th>Delivery</th>
                            <th>O/S</th>
                            <th>Delivery</th>
                            <th>O/S</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

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

        #osperweek_table th,
        #osperweek_table td {
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        #osperweek_table thead th {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Hover effect full row + pointer */
        #osperweek_table tbody tr:hover {
            background-color: #e2f0ff !important;
            cursor: pointer;
        }

        #osperweek_table tbody tr.selected td {
            background-color: #cdeaff !important;
        }

        #osperweek_table tbody tr {
            transition: background-color 0.2s ease;
        }

        /* Highlight untuk kolom genap */
        .highlight-genap {
            background-color: #d4edda !important;
        }

        /* Warna untuk PO */
        .po-column {
            background-color: #fff3cd !important; /* kuning muda */
        }

        /* Warna untuk kolom Delivery */
        .delivery-column {
            background-color: #d1ecf1 !important; /* biru muda */
        }

        /* Warna untuk kolom O/S */
        .os-column {
            background-color: #f8d7da !important; /* merah muda */
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Script --}}
    <script>
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

            if ($.fn.DataTable.isDataTable('#osperweek_table')) {
                $('#osperweek_table').DataTable().clear().destroy();
            }

            $('#osperweek_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                scrollX: true,
                ajax: {
                    url: "{{ route('datakanban.osperweek.data') }}",
                    data: { bulan, tahun }
                },
                columns: [
                    { data: 'no' },
                    { data: 'customer' },
                    { data: 'part_number' },
                    { data: 'models' },
                    { data: 'po' },
                    { data: 'week1_delivery' },
                    { data: 'week1_os' },
                    { data: 'week2_delivery' },
                    { data: 'week2_os' },
                    { data: 'week3_delivery' },
                    { data: 'week3_os' },
                    { data: 'week4_delivery' },
                    { data: 'week4_os' }
                ],
                createdRow: function (row, data, dataIndex) {
                    $('td', row).each(function (index) {
                        if (index === 4) {
                            $(this).addClass('po-column');
                        }
                        if ([5, 7, 9, 11].includes(index)) {
                            $(this).addClass('delivery-column');
                        }
                        if ([6, 8, 10, 12].includes(index)) {
                            $(this).addClass('os-column');
                        }
                    });
                }
            });
        }

        $('#osperweek_table tbody').on('click', 'tr', function () {
            $('#osperweek_table tbody tr').removeClass('selected');
            $(this).addClass('selected');
        });

        $(document).ready(function () {
            reloadTable();
            updateExportLink();

            $('#filter_bulan, #filter_tahun, #reload_table').on('change click', function () {
                reloadTable();
                updateExportLink();
            });
        });
    </script>
</x-default-layout>
