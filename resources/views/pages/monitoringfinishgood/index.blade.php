<x-default-layout>
    @section('title', 'Monitoring Finish Good')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Monitoring Finish Good</h3>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-4">
                <div class="col-md-3 col-sm-6">
                    <label for="filter_bulan" class="form-label fw-semibold">Bulan</label>
                    <select id="filter_bulan" class="form-select form-select-sm">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-3 col-sm-6">
                    <label for="filter_tahun" class="form-label fw-semibold">Tahun</label>
                    <select id="filter_tahun" class="form-select form-select-sm">
                        @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- CUSTOMER -->
                <div class="col-md-3 col-sm-6">
                    <label for="filter_customer" class="form-label fw-semibold">Customer</label>
                    <select id="filter_customer" class="form-select form-select-sm">
                        <option value="">-- Semua Customer --</option>
                    </select>
                </div>

                <div class="col-md-3 col-sm-6 d-flex gap-2 justify-content-md-end">
                    <button id="reload_table" class="btn btn-primary btn-sm">🔄 Refresh</button>
                    <button id="export_excel" class="btn btn-success btn-sm">📁 Export</button>
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
        #fg_table thead th {
            text-align: center !important;
            vertical-align: middle !important;
        }
        #fg_table th, #fg_table td { white-space: nowrap; text-align: center; vertical-align: middle; }
        #fg_table th:nth-child(n+13), #fg_table td:nth-child(n+13) {
            min-width: 50px;
            max-width: 50px;
        }

        #fg_table {
            border: 2px solid #343a40;
        }

        .cell-input-group {
            display: grid;
            grid-template-rows: repeat(3, auto);
            gap: 2px;
            min-width: 50px;
        }

        .cell-input-group input {
            padding: 2px 4px;
            font-size: 12px;
            height: 26px;
        }

        .badge.bg-danger { background-color: #dc3545; }
        .badge.bg-warning { background-color: #ffc107; color: #212529; }
        .badge.bg-success { background-color: #28a745; }
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
                { data: null, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'customer' },
                { data: 'project' },
                { data: 'part_number' },
                { data: 'part_name' },
                { data: 'total_po', render: d => `<span>${d ?? 0}</span>` },
                {
                    data: 'advance_delivery',
                    render: (data, type, row) => `<input name="advance_delivery" class="form-control form-control-sm text-center" value="${data ?? 0}">`
                },
                {
                    data: 'outstanding',
                    render: d => `<span>${d ?? 0}</span>`
                },
                {
                    data: 'percentage',
                    render: d => `<span>${d ?? 0}%</span>`
                },
                {
                    data: 'stock_awal',
                    render: d => `
                        <input
                            name="stock_awal"
                            class="form-control form-control-sm text-center input-biru"
                            value="${d ?? 0}"
                        >
                    `
                },
                {
                    data: 'total_in',
                    render: d => `<span>${d ?? 0}</span>`
                },
                {
                    data: 'total_out',
                    render: d => `<span>${d ?? 0}</span>`
                },
                { data: 'level_min', render: d => `<span>${d ?? 0}</span>` },
                { data: 'level_safety', render: d => `<span>${d ?? 0}</span>` },
                { data: 'level_max', render: d => `<span>${d ?? 0}</span>` },
                { data: 'stock_on_hand', render: d => `<span>${d ?? 0}</span>` },
                {
                    data: 'status_stock',
                    render: d => {
                        let color = 'bg-secondary';
                        if (d === 'Problem') color = 'bg-danger';
                        else if (d === 'Over') color = 'bg-warning';
                        else if (d === 'Aman') color = 'bg-success';
                        return `<span class="badge ${color}">${d}</span>`;
                    }
                },
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

            if ($.fn.DataTable.isDataTable('#fg_table')) {
                $('#fg_table').DataTable().clear().destroy();
            }

            $('#fg_table thead').empty();
            $('#fg_table tbody').empty();

            let thead1 = `
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Customer</th>
                <th rowspan="2">Project</th>
                <th rowspan="2">Part Number</th>
                <th rowspan="2">Part Name</th>
                <th rowspan="2">Total PO</th>
                <th rowspan="2">Advance Delivery</th>
                <th rowspan="2">Outstanding</th>
                <th rowspan="2">% Delivery</th>
                <th rowspan="2">Stock Awal</th>
                <th colspan="2" class="text-center">Total</th>
                <th colspan="3" class="text-center">Level</th>
                <th rowspan="2">Stock On Hand</th>
                <th rowspan="2">Status Stock</th>
                <th rowspan="2">Status</th>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                thead1 += `<th colspan="2" class="text-center">${i}</th>`;
            }
            thead1 += `</tr><tr>
            <th>IN</th><th>OUT</th>
            <th>Min</th><th>Safety</th><th>Max</th>
            `;
            for (let i = 1; i <= jumlahHari; i++) {
                thead1 += `<th>D</th><th>N</th>`;
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
                        d.customer = $('#filter_customer').val();
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

            $('#filter_customer').select2({
                placeholder: '-- Semua Customer --',
                allowClear: true,
                width: '100%'
            });

            $('#fg_table tbody').on('blur', 'input[name^="out_hari_"], input[name^="in_hari_"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = new Date($('#filter_tahun').val(), $('#filter_bulan').val(), 0).getDate();

                let stockAwal = parseInt($row.find('input[name="stock_awal"]').val()) || 0;
                let balance = stockAwal;

                let totalOut = 0;

                for (let i = 1; i <= jumlahHari; i++) {
                    const inD = parseInt($row.find(`input[name="in_hari_${i}_d"]`).val()) || 0;
                    const outD = parseInt($row.find(`input[name="out_hari_${i}_d"]`).val()) || 0;
                    const inN = parseInt($row.find(`input[name="in_hari_${i}_n"]`).val()) || 0;
                    const outN = parseInt($row.find(`input[name="out_hari_${i}_n"]`).val()) || 0;

                    const balanceD = balance + inD - outD;
                    const balanceN = balanceD + inN - outN;

                    $row.find(`input[name="balance_hari_${i}_d"]`).val(balanceD);
                    $row.find(`input[name="balance_hari_${i}_n"]`).val(balanceN);

                    balance = balanceN;

                    totalOut += outD + outN;
                }

                $row.find('td:eq(15) span').text(balance);

                const levelMin = parseInt($row.find('td:eq(12)').text()) || 0;
                const levelSafety = parseInt($row.find('td:eq(13)').text()) || 0;
                const levelMax = parseInt($row.find('td:eq(14)').text()) || 0;
                let statusBadge = '';
                if (balance <= levelMin) {
                    statusBadge = `<span class="badge bg-danger">Problem</span>`;
                } else if (balance > levelMax) {
                    statusBadge = `<span class="badge bg-warning">Over</span>`;
                } else {
                    statusBadge = `<span class="badge bg-success">Aman</span>`;
                }
                $row.find('td:eq(16)').html(statusBadge);

                const totalPO = parseInt($row.find('td:eq(5)').text()) || 0;
                const advanceDelivery = parseInt($row.find('input[name="advance_delivery"]').val()) || 0;
                const outstanding = Math.max(0, totalPO - advanceDelivery - totalOut);
                const percentage = totalPO > 0 ? ((outstanding / totalPO) * 100).toFixed(2) : 0;

                $row.find('td:eq(7) span').text(outstanding);
                $row.find('td:eq(8) span').text(`${percentage}%`);

                const data = {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    customer: $row.find('td:eq(1)').text(),
                    project: $row.find('td:eq(2)').text(),
                    part_number: $row.find('td:eq(3)').text(),
                    part_name: $row.find('td:eq(4)').text(),
                    stock_awal: stockAwal,
                    level_min: levelMin,
                    level_safety: levelSafety,
                    level_max: levelMax,
                    advance_delivery: advanceDelivery
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

            $('#fg_table tbody').on('blur', 'input[name="advance_delivery"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = new Date($('#filter_tahun').val(), $('#filter_bulan').val(), 0).getDate();

                const totalPO = parseInt($row.find('td:eq(5)').text()) || 0;
                const advanceDelivery = parseInt($row.find('input[name="advance_delivery"]').val()) || 0;

                let totalOut = 0;
                for (let i = 1; i <= jumlahHari; i++) {
                    totalOut += (parseInt($row.find(`input[name="out_hari_${i}_d"]`).val()) || 0);
                    totalOut += (parseInt($row.find(`input[name="out_hari_${i}_n"]`).val()) || 0);
                }

                const outstanding = Math.max(0, totalPO - advanceDelivery - totalOut);
                const percentage = totalPO > 0 ? ((outstanding / totalPO) * 100).toFixed(2) : '0.00';

                $row.find('td:eq(7) span').text(outstanding);
                $row.find('td:eq(8) span').text(`${percentage}%`);

                const balance = parseInt($row.find('td:eq(15) span').text()) || 0;
                const levelMin = parseInt($row.find('td:eq(12)').text()) || 0;
                const levelMax = parseInt($row.find('td:eq(14)').text()) || 0;
                let statusBadge = '';
                if (balance <= levelMin) {
                    statusBadge = `<span class="badge bg-danger">Problem</span>`;
                } else if (balance > levelMax) {
                    statusBadge = `<span class="badge bg-warning">Over</span>`;
                } else {
                    statusBadge = `<span class="badge bg-success">Aman</span>`;
                }
                $row.find('td:eq(16)').html(statusBadge);

                const data = {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    customer: $row.find('td:eq(1)').text(),
                    project: $row.find('td:eq(2)').text(),
                    part_number: $row.find('td:eq(3)').text(),
                    part_name: $row.find('td:eq(4)').text(),
                    stock_awal: parseInt($row.find('input[name="stock_awal"]').val()) || 0,
                    level_min: levelMin,
                    level_safety: parseInt($row.find('td:eq(13)').text()) || 0,
                    level_max: levelMax,
                    advance_delivery: advanceDelivery
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
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Advance Delivery Disimpan',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    })
                    .fail(() => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Gagal menyimpan Advance Delivery!',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    });
            });

            $('#fg_table tbody').on('blur', 'input[name="stock_awal"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = new Date($('#filter_tahun').val(), $('#filter_bulan').val(), 0).getDate();

                let stockAwal = parseInt($(this).val()) || 0;
                let balance = stockAwal;
                let totalOut = 0;

                for (let i = 1; i <= jumlahHari; i++) {
                    const inD  = parseInt($row.find(`input[name="in_hari_${i}_d"]`).val()) || 0;
                    const outD = parseInt($row.find(`input[name="out_hari_${i}_d"]`).val()) || 0;
                    const inN  = parseInt($row.find(`input[name="in_hari_${i}_n"]`).val()) || 0;
                    const outN = parseInt($row.find(`input[name="out_hari_${i}_n"]`).val()) || 0;

                    const balanceD = balance + inD - outD;
                    const balanceN = balanceD + inN - outN;

                    $row.find(`input[name="balance_hari_${i}_d"]`).val(balanceD);
                    $row.find(`input[name="balance_hari_${i}_n"]`).val(balanceN);

                    balance = balanceN;
                    totalOut += outD + outN;
                }

                $row.find('td:eq(15) span').text(balance);

                const levelMin = parseInt($row.find('td:eq(12)').text()) || 0;
                const levelMax = parseInt($row.find('td:eq(14)').text()) || 0;

                let statusBadge = '<span class="badge bg-success">Aman</span>';
                if (balance <= levelMin) {
                    statusBadge = '<span class="badge bg-danger">Problem</span>';
                } else if (balance > levelMax) {
                    statusBadge = '<span class="badge bg-warning">Over</span>';
                }
                $row.find('td:eq(16)').html(statusBadge);

                $.post('{{ route("monitoring.finishgood.updateStockAwal") }}', {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    customer: $.trim($row.find('td:eq(1)').text()),
                    kode_project: $.trim($row.find('td:eq(2)').text()),
                    part_number: $.trim($row.find('td:eq(3)').text()),
                    stock_awal: stockAwal
                })
                .done(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: balance < 0 ? 'warning' : 'success',
                        title: balance < 0
                            ? `⚠️ Stock minus (${balance})`
                            : 'Stock Awal diperbarui',
                        showConfirmButton: false,
                        timer: 2000
                    });
                })
                .fail(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Gagal update Stock Awal',
                        showConfirmButton: false,
                        timer: 2000
                    });
                });
            });

            $('#export_excel').on('click', function () {
                const bulan = $('#filter_bulan').val();
                const tahun = $('#filter_tahun').val();

                const url = new URL('{{ route("monitoring.finishgood.export") }}', window.location.origin);
                url.searchParams.set('bulan', bulan);
                url.searchParams.set('tahun', tahun);

                window.location.href = url.toString();
            });

            function loadCustomerFilter() {
                $.ajax({
                    url: '{{ route("monitoring.finishgood.data") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        bulan: $('#filter_bulan').val(),
                        tahun: $('#filter_tahun').val(),
                        only_customer: true
                    },
                    success: function (res) {
                        const select = $('#filter_customer');
                        select.empty().append('<option value=""></option>');

                        res.data.forEach(r => {
                            select.append(`<option value="${r.customer}">${r.customer}</option>`);
                        });
                    }
                });
            }

            $('#filter_bulan, #filter_tahun').on('change', function () {
                $('#filter_customer').val(null).trigger('change');
                loadCustomerFilter();
                reloadTable();
            });

            $('#filter_customer').on('change', function () {
                reloadTable();
            });

            loadCustomerFilter();
            reloadTable();
        });
    </script>
</x-default-layout>
