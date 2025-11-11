<x-default-layout>
    @section('title', 'Monitoring MIP')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Monitoring MIP</h3>
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
                <table id="mip_table" class="table table-bordered table-striped w-100">
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
        #mip_table th, #mip_table td { white-space: nowrap; text-align: center; vertical-align: middle; }
        #mip_table th:nth-child(n+13), #mip_table td:nth-child(n+13) {
            min-width: 50px;
            max-width: 50px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function () {
            function getParams() {
                return {
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val()
                };
            }

            const table = $('#mip_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                paging: false,
                scrollX: true,
                scrollY: '60vh',
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route("monitoring.mip.data") }}',
                    type: 'POST',
                    data: d => {
                        d._token = '{{ csrf_token() }}';
                        d.bulan = $('#filter_bulan').val();
                        d.tahun = $('#filter_tahun').val();
                    }
                },
                columns: [
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
                        data: 'stock_awal',
                        render: d => `<input type="text" class="form-control form-control-sm text-center input-biru" value="${d ?? 0}" name="stock_awal" readonly tabindex="-1">`
                    },
                    { data: 'total_in', render: d => `<span>${d ?? 0}</span>` },
                    { data: 'total_out', render: d => `<span>${d ?? 0}</span>` },
                    { data: 'level_min', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_min" readonly>` },
                    { data: 'level_safety', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_safety" readonly>` },
                    { data: 'level_max', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_max" readonly>` },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function () {
                            return `
                                <div class="d-flex flex-column gap-1 text-center">
                                    <span class="badge bg-danger">IN</span>
                                    <span class="badge bg-success">OUT</span>
                                    <span class="badge bg-primary">BALANCE</span>
                                </div>
                            `;
                        }
                    },
                    ...Array.from({ length: 31 }, (_, i) => {
                    const day = i + 1;
                    return {
                        data: null,
                        render: function (data, type, row) {
                            return `
                                <div class="d-flex flex-column gap-1">
                                    <input type="text" name="in_hari_${day}" class="form-control form-control-sm text-center input-merah" value="${row[`in_hari_${day}`] ?? 0}" readonly tabindex="-1">
                                    <input type="text" name="out_hari_${day}" class="form-control form-control-sm text-center input-hijau" placeholder="OUT" value="${row[`out_hari_${day}`] ?? ''}">
                                    <input type="text" name="balance_hari_${day}" class="form-control form-control-sm text-center input-biru" placeholder="BAL" value="${row[`balance_hari_${day}`] ?? ''}" readonly tabindex="-1">
                                </div>
                            `;
                        }
                    };
                })
                ]
            });

            $('#filter_bulan, #filter_tahun, #reload_table').on('change click', function () {
                reloadTable();
            });

            $('#mip_table tbody').on('blur', 'input[name^="out_hari_"]', function () {
                const $row = $(this).closest('tr');
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

                for (let i = 1; i <= 31; i++) {
                    data[`in_hari_${i}`] = $row.find(`input[name="in_hari_${i}"]`).val();
                    data[`out_hari_${i}`] = $row.find(`input[name="out_hari_${i}"]`).val();
                }

                $.post('{{ route("monitoring.mip.save") }}', data)
                    .done(() => {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Tersimpan!', showConfirmButton: false, timer: 1500 });
                    })
                    .fail(() => {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal menyimpan data!', showConfirmButton: false, timer: 2000 });
                    });
            });

            function calculateBalance($row) {
                let balance = parseInt($row.find('input[name="stock_awal"]').val()) || 0;

                for (let i = 1; i <= 31; i++) {
                    let valIn = parseInt($row.find(`input[name="in_hari_${i}"]`).val()) || 0;
                    let valOut = parseInt($row.find(`input[name="out_hari_${i}"]`).val()) || 0;
                    balance = balance + valIn - valOut;
                    $row.find(`input[name="balance_hari_${i}"]`).val(balance);
                }

            }

            $('#mip_table tbody').on('input', 'input[name^="out_hari_"]', function () {
                const $row = $(this).closest('tr');
                calculateBalance($row);
            });

            function generateTableHeader(jumlahHari) {
                let thead = `
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">Customer</th>
                        <th rowspan="2">Project</th>
                        <th rowspan="2">Part Number</th>
                        <th rowspan="2">Part Name</th>
                        <th rowspan="2">Stock Awal</th>
                        <th rowspan="2">Total IN</th>
                        <th rowspan="2">Total OUT</th>
                        <th colspan="3" class="text-center">Level Stock</th>
                        <th rowspan="2">Status</th>
                        <th colspan="${jumlahHari}" class="text-center">Tanggal</th>
                    </tr>
                    <tr>
                        <th>Min</th>
                        <th>Safety</th>
                        <th>Max</th>`;

                for (let i = 1; i <= jumlahHari; i++) {
                    thead += `<th>${i}</th>`;
                }

                thead += `</tr>`;

                $('#mip_table thead').html(thead);
            }

            function generateTableColumns(jumlahHari) {
                const staticColumns = [
                    {
                        data: null,
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    { data: 'customer' },
                    { data: 'project' },
                    { data: 'part_number' },
                    { data: 'part_name' },
                    {
                        data: 'stock_awal',
                        render: d => `<input type="text" class="form-control form-control-sm text-center input-biru" value="${d ?? 0}" name="stock_awal" readonly tabindex="-1">`
                    },
                    { data: 'total_in', render: d => `<span>${d ?? 0}</span>` },
                    { data: 'total_out', render: d => `<span>${d ?? 0}</span>` },
                    { data: 'level_min', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_min" readonly>` },
                    { data: 'level_safety', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_safety" readonly>` },
                    { data: 'level_max', render: d => `<input type="text" class="form-control form-control-sm text-center" value="${d ?? 0}" name="level_max" readonly>` },
                    {
                        data: null,
                        render: () => `
                            <div class="d-flex flex-column gap-1 text-center">
                                <span class="badge bg-danger">IN</span>
                                <span class="badge bg-success">OUT</span>
                                <span class="badge bg-primary">BAL</span>
                            </div>
                        `
                    }
                ];

                const dynamicColumns = [];
                for (let i = 1; i <= jumlahHari; i++) {
                    dynamicColumns.push({
                        data: null,
                        render: row => `
                            <div class="d-flex flex-column gap-1">
                                <input type="text" name="in_hari_${i}" class="form-control form-control-sm text-center input-merah" value="${row[`in_hari_${i}`] ?? 0}" readonly tabindex="-1">
                                <input type="text" name="out_hari_${i}" class="form-control form-control-sm text-center input-hijau" placeholder="OUT" value="${row[`out_hari_${i}`] ?? ''}">
                                <input type="text" name="balance_hari_${i}" class="form-control form-control-sm text-center input-biru" placeholder="BAL" value="${row[`balance_hari_${i}`] ?? ''}" readonly tabindex="-1">
                            </div>
                        `
                    });
                }

                return [...staticColumns, ...dynamicColumns];
            }

            function reloadTable() {
                const bulan = parseInt($('#filter_bulan').val());
                const tahun = parseInt($('#filter_tahun').val());
                const jumlahHari = new Date(tahun, bulan, 0).getDate();

                if ($.fn.DataTable.isDataTable('#mip_table')) {
                    $('#mip_table').DataTable().clear().destroy();
                }

                $('#mip_table thead').empty();
                $('#mip_table tbody').empty();

                generateTableHeader(jumlahHari);

                $('#mip_table').DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    paging: false,
                    scrollX: true,
                    scrollY: '60vh',
                    order: [[1, 'asc']],
                    ajax: {
                        url: '{{ route("monitoring.mip.data") }}',
                        data: d => {
                            d.bulan = bulan;
                            d.tahun = tahun;
                        }
                    },
                    columns: generateTableColumns(jumlahHari)
                });
            }

            reloadTable();
        });
    </script>
</x-default-layout>
