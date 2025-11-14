<x-default-layout>
    @section('title', 'Monitoring Finish Good')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Monitoring Finish Good</h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
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
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button id="reload_table" class="btn btn-primary">🔄 Refresh Data</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="fg_table" class="table table-bordered table-striped w-100">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .input-merah { background-color: #fcdcdc !important; }
        .input-hijau { background-color: #d6f5d6 !important; }
        .input-biru { background-color: #d8e9ff !important; font-weight: bold; color: #004085; }
        #fg_table th, #fg_table td { white-space: nowrap; text-align: center; vertical-align: middle; }
        #fg_table th:nth-child(n+13), #fg_table td:nth-child(n+13) {
            min-width: 50px;
            max-width: 50px;
        }

        .cell-input-group {
            display: grid;
            grid-template-rows: repeat(3, auto);
            gap: 2px;
            min-width: 60px;
        }

        .cell-input-group input {
            padding: 2px 4px;
            font-size: 12px;
            height: 26px;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let table = null;

        function getParams() {
            return {
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val()
            };
        }

        function generateTable(jumlahHari) {
            const columns = [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row, meta) => meta.row + 1
                },
                { data: 'customer' },
                { data: 'project' },
                { data: 'part_number' },
                { data: 'part_name' },
                {
                    data: 'total_po',
                    render: d => `<span>${d ?? 0}</span>`
                },
                {
                    data: 'stock_awal',
                    render: d => `<span>${d ?? 0}</span>`
                },
                { data: 'total_in', render: d => `<span>${d ?? 0}</span>` },
                { data: 'total_out', render: d => `<span>${d ?? 0}</span>` },
                { data: 'level_min', render: d => `<span>${d ?? 0}</span>` },
                { data: 'level_safety', render: d => `<span>${d ?? 0}</span>` },
                { data: 'level_max', render: d => `<span>${d ?? 0}</span>` },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: () => `
                        <div class="d-flex flex-column gap-1 text-center">
                            <span class="badge bg-success">IN</span>
                            <span class="badge bg-danger">OUT</span>
                            <span class="badge bg-primary">BAL</span>
                        </div>
                    `
                }
            ];

            for (let i = 1; i <= jumlahHari; i++) {
                columns.push(
                    {
                        data: null,
                        render: row => `
                            <div class="cell-input-group">
                                <input name="in_hari_${i}_d" class="form-control form-control-sm text-center input-hijau" value="${row[`in_hari_${i}_d`] ?? 0}" readonly>
                                <input name="out_hari_${i}_d" class="form-control form-control-sm text-center input-merah" value="${row[`out_hari_${i}_d`] ?? ''}">
                                <input name="balance_hari_${i}_d" class="form-control form-control-sm text-center input-biru" value="${row[`balance_hari_${i}_d`] ?? 0}" readonly>
                            </div>
                        `
                    },
                    {
                        data: null,
                        render: row => `
                            <div class="cell-input-group">
                                <input name="in_hari_${i}_n" class="form-control form-control-sm text-center input-hijau" value="${row[`in_hari_${i}_n`] ?? 0}" readonly>
                                <input name="out_hari_${i}_n" class="form-control form-control-sm text-center input-merah" value="${row[`out_hari_${i}_n`] ?? ''}">
                                <input name="balance_hari_${i}_n" class="form-control form-control-sm text-center input-biru" value="${row[`balance_hari_${i}_n`] ?? 0}" readonly>
                            </div>
                        `
                    }
                );
            }

            return columns;
        }

        function reloadTable() {
            const { bulan, tahun } = getParams();
            const jumlahHari = new Date(tahun, bulan, 0).getDate();

            // FIX #1: destroy kalau sudah ada
            if ($.fn.DataTable.isDataTable('#fg_table')) {
                $('#fg_table').DataTable().clear().destroy();
            }

            // FIX #2: bersihkan thead dan tbody
            $('#fg_table thead').empty();
            $('#fg_table tbody').empty();

            // Build thead again
            let thead1 = `
                <tr>
                    <th rowspan="2">No</th>
                    <th rowspan="2">Customer</th>
                    <th rowspan="2">Project</th>
                    <th rowspan="2">Part Number</th>
                    <th rowspan="2">Part Name</th>
                    <th rowspan="2">Total PO</th>
                    <th rowspan="2">Stock Awal</th>
                    <th rowspan="2">Total IN</th>
                    <th rowspan="2">Total OUT</th>
                    <th rowspan="2">Level Min</th>
                    <th rowspan="2">Level Safety</th>
                    <th rowspan="2">Level Max</th>
                    <th rowspan="2">Status</th>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                thead1 += `<th colspan="2"  class="text-center">${i}</th>`;
            }
            thead1 += `</tr><tr>`;
            for (let i = 1; i <= jumlahHari; i++) {
                thead1 += `<th  class="text-center">D</th><th  class="text-center">N</th>`;
            }
            thead1 += `</tr>`;

            $('#fg_table thead').html(thead1);

            table = $('#fg_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                paging: false,
                scrollX: true,
                scrollY: '60vh',
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route("monitoring.finishgood.data") }}',
                    type: 'POST',
                    data: function (d) {
                        d._token = '{{ csrf_token() }}';
                        d.bulan = bulan;
                        d.tahun = tahun;
                    }
                },
                columns: generateTable(jumlahHari)
            });
        }

        $(function () {
            reloadTable();

            $('#filter_bulan, #filter_tahun, #reload_table').on('change click', function () {
                reloadTable();
            });

            $('#fg_table tbody').on('blur', 'input[name^="out_hari_"], input[name^="in_hari_"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = new Date($('#filter_tahun').val(), $('#filter_bulan').val(), 0).getDate();

                const data = {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    customer: $row.find('td:eq(1)').text(),
                    project: $row.find('td:eq(2)').text(),
                    part_number: $row.find('td:eq(3)').text(),
                    part_name: $row.find('td:eq(4)').text(),
                    stock_awal: $row.find('input[name="stock_awal"]').val(),
                    level_min: $row.find('input[name="level_min"]').val(),
                    level_safety: $row.find('input[name="level_safety"]').val(),
                    level_max: $row.find('input[name="level_max"]').val()
                };

                for (let i = 1; i <= jumlahHari; i++) {
                    data[`in_hari_${i}_d`] = $row.find(`input[name="in_hari_${i}_d"]`).val();
                    data[`out_hari_${i}_d`] = $row.find(`input[name="out_hari_${i}_d"]`).val();
                    data[`balance_hari_${i}_d`] = $row.find(`input[name="balance_hari_${i}_d"]`).val();
                    data[`in_hari_${i}_n`] = $row.find(`input[name="in_hari_${i}_n"]`).val();
                    data[`out_hari_${i}_n`] = $row.find(`input[name="out_hari_${i}_n"]`).val();
                    data[`balance_hari_${i}_n`] = $row.find(`input[name="balance_hari_${i}_n"]`).val();
                }

                $.post('{{ route("monitoring.finishgood.save") }}', data)
                    .done(() => {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Tersimpan!', showConfirmButton: false, timer: 1500 });
                    })
                    .fail(() => {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal menyimpan data!', showConfirmButton: false, timer: 2000 });
                    });
            });
        });
    </script>
</x-default-layout>
