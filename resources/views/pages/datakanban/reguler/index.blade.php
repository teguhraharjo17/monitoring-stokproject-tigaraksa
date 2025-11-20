<x-default-layout>
    @section('title', 'Data Kanban - Reguler')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Data Kanban - Reguler</h3>
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
                        data-base-url="{{ route('datakanban.reguler.export') }}"
                        href="#"
                    >
                        📤 Export Excel
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="kanban_table" class="table table-bordered table-striped w-100">
                    <thead id="kanban_head">
                        <tr id="header-main"></tr>
                        <tr id="header-sub"></tr>
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

        #kanban_table thead th {
            text-align: center !important;
            vertical-align: middle !important;
        }

        #kanban_table th, #kanban_table td { white-space: nowrap; text-align: center; vertical-align: middle; }
        .border-left-strong {
            border-left: 2px solid #000 !important;
        }

        #kanban_table tbody tr:hover td {
            background-color: #e2f0ff !important;
            cursor: pointer;
        }

        #kanban_table tbody tr.selected td {
            background-color: #cdeaff !important;
        }

        #kanban_table tbody tr td {
            transition: background-color 0.2s ease;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function getFilterParams() {
            return {
                bulan: parseInt($('#filter_bulan').val()),
                tahun: parseInt($('#filter_tahun').val())
            };
        }

        function buildTableHeader(jumlahHari) {
            const headerMain = $('#header-main');
            const headerSub = $('#header-sub');

            headerMain.html(`
                <th rowspan="2">No</th>
                <th rowspan="2">Customer</th>
                <th rowspan="2">Part Number</th>
                <th rowspan="2">Models</th>
                <th rowspan="2">QTY PO</th>
                <th rowspan="2">Total Qty Kanban</th>
                <th rowspan="2">% Penyerapan PO</th>
                <th rowspan="2">+/- (pcs)</th>
            `);

            headerSub.html('');

            for (let i = 1; i <= jumlahHari; i++) {
                const borderClass = i === 1 ? 'border-left-strong' : '';
                headerMain.append(`<th colspan="2" class="text-center ${borderClass}">${i}</th>`);
                headerSub.append(`<th class="text-center ${borderClass}">D</th><th class="text-center">N</th>`);
            }
        }

        function buildColumns(jumlahHari) {
            let columns = [
                { data: 'no' },
                { data: 'customer' },
                { data: 'part_number' },
                { data: 'models' },
                { data: 'qty_po' },
                { data: 'qty_kanban' },
                { data: 'penyerapan' },
                { data: 'selisih' }
            ];

            for (let i = 1; i <= jumlahHari; i++) {
                columns.push({ data: `d_${i}` });
                columns.push({ data: `n_${i}` });
            }

            return columns;
        }

        function reloadTable() {
            const { bulan, tahun } = getFilterParams();
            const jumlahHari = new Date(tahun, bulan, 0).getDate();

            if ($.fn.DataTable.isDataTable('#kanban_table')) {
                $('#kanban_table').DataTable().clear().destroy();
            }

            buildTableHeader(jumlahHari);

            $('#kanban_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                paging: false,
                scrollX: true,
                ajax: {
                    url: "{{ route('datakanban.reguler.data') }}",
                    data: {
                        bulan,
                        tahun
                    }
                },
                createdRow: function(row, data, dataIndex) {
                    const jumlahHari = new Date(tahun, bulan, 0).getDate();

                    let startIndex = 8;
                    for (let i = 1; i <= jumlahHari; i++) {
                        const tdD = $('td', row).eq(startIndex);
                        const tdN = $('td', row).eq(startIndex + 1);

                        tdD.css('background-color', '#d4edda');
                        tdN.css('background-color', '#f8d7da');

                        tdD.addClass('border-left-strong');

                        startIndex += 2;
                    }
                },
                columns: buildColumns(jumlahHari),
                initComplete: function () {
                    this.api().columns.adjust();
                }
            });
        }

        $('#filter_bulan, #filter_tahun').on('change', function () {
            updateExportLink();
        });

        $('#kanban_table tbody').on('click', 'tr', function () {
            $('#kanban_table tbody tr').removeClass('selected');
            $(this).addClass('selected');
        });

        function updateExportLink() {
            const bulan = $('#filter_bulan').val();
            const tahun = $('#filter_tahun').val();

            const baseUrl = $('#btn_export_excel').data('base-url');
            const finalUrl = `${baseUrl}?bulan=${bulan}&tahun=${tahun}`;
            $('#btn_export_excel').attr('href', finalUrl);
        }

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
