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
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

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

            <div id="table_loading" class="alert alert-info py-2 px-3 d-none">
                Memuat data...
            </div>

            <div class="table-wrap">
                <table id="fg_table" class="table table-bordered table-striped mb-0">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: 70vh;
            border: 2px solid #343a40;
            position: relative;
            background: #fff;
        }

        #fg_table {
            width: max-content;
            min-width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        #fg_table th,
        #fg_table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            border-right: 1px solid #343a40;
            border-bottom: 1px solid #343a40;
        }

        #fg_table tr th:first-child,
        #fg_table tr td:first-child {
            border-left: 1px solid #343a40;
        }

        #fg_table thead tr:first-child th {
            border-top: 1px solid #343a40;
        }

        #fg_table thead th {
            position: sticky;
            background: #fff;
            z-index: 3;
            font-weight: 700;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        #fg_table thead tr:first-child th {
            top: 0;
            z-index: 5;
        }

        #fg_table thead tr:nth-child(2) th {
            top: 40px;
            z-index: 6;
            background: #fff;
            padding-top: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #343a40;
        }

        #fg_table thead tr:first-child th[rowspan="2"] {
            border-bottom: 2px solid #343a40;
        }

        #fg_table tbody td {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .col-no { min-width: 50px; }
        .col-customer { min-width: 120px; }
        .col-project { min-width: 120px; }
        .col-part-number { min-width: 140px; }
        .col-part-name { min-width: 240px; text-align: left !important; }
        .col-number { min-width: 90px; }
        .col-status { min-width: 90px; }
        .col-harian { min-width: 78px; }

        .cell-input-group {
            display: grid;
            grid-template-rows: repeat(3, auto);
            gap: 4px;
            width: 64px;
            margin: 0 auto;
        }

        .cell-input-group input {
            width: 64px;
            min-width: 64px;
            max-width: 64px;
            padding: 2px 4px;
            font-size: 11px;
            height: 24px;
            text-align: center;
            border-radius: 3px;
        }

        .input-merah {
            background-color: #fcdcdc !important;
        }

        .input-hijau {
            background-color: #d6f5d6 !important;
        }

        .input-biru {
            background-color: #d8e9ff !important;
            font-weight: bold;
            color: #004085;
        }

        .legend-badge-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .badge {
            font-size: 14px;
            color: #ffffff;
            padding: 4px 6px;
            line-height: 1;
            border-radius: 4px;
        }

        .badge.bg-danger { background-color: #dc3545 !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .badge.bg-success { background-color: #28a745 !important; }
        .badge.bg-primary { background-color: #0d6efd !important; }

        input[name="advance_delivery"],
        input[name="stock_awal"] {
            width: 70px;
            min-width: 70px;
            text-align: center;
            margin: 0 auto;
        }

        .saving-row {
            background-color: #fff8db !important;
        }

        .freeze-col,
        .freeze-group-total,
        .freeze-group-level {
            position: sticky;
            background: #fff !important;
            z-index: 2;
        }

        #fg_table tbody .freeze-col {
            z-index: 2;
        }

        #fg_table thead .freeze-col,
        #fg_table thead .freeze-group-total,
        #fg_table thead .freeze-group-level {
            z-index: 8;
        }

        #fg_table thead tr:first-child .freeze-col,
        #fg_table thead tr:first-child .freeze-group-total,
        #fg_table thead tr:first-child .freeze-group-level {
            z-index: 9;
        }

        #fg_table thead tr:nth-child(2) .freeze-col {
            z-index: 10;
        }

        .saving-row .freeze-col {
            background-color: #fff8db !important;
        }

        /* separator freeze yang stabil */
        .freeze-separator {
            position: sticky;
        }

        .freeze-separator::after {
            content: "";
            position: absolute;
            top: -1px;
            right: -1px;
            width: 2px;
            height: calc(100% + 2px);
            background: #343a40;
            z-index: 30;
            pointer-events: none;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let currentData = [];

        function getParams() {
            return {
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val(),
                customer: $('#filter_customer').val()
            };
        }

        function escapeHtml(text) {
            return String(text ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildTableHeader(jumlahHari) {
            let thead = `
                <tr>
                    <th rowspan="2" class="col-no freeze-col freeze-h-0">No</th>
                    <th rowspan="2" class="col-customer freeze-col freeze-h-1">Customer</th>
                    <th rowspan="2" class="col-project freeze-col freeze-h-2">Project</th>
                    <th rowspan="2" class="col-part-number freeze-col freeze-h-3">Part Number</th>
                    <th rowspan="2" class="col-part-name freeze-col freeze-h-4">Part Name</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-5">Total PO</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-6">Advance Delivery</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-7">Outstanding</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-8">% Delivery</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-9">Stock Awal</th>

                    <th colspan="2" class="col-number freeze-group-total">Total</th>
                    <th colspan="3" class="col-number freeze-group-level">Level</th>

                    <th rowspan="2" class="col-number freeze-col freeze-h-15">Stock On Hand</th>
                    <th rowspan="2" class="col-status freeze-col freeze-h-16">Status Stock</th>
                    <th rowspan="2" class="col-status freeze-col freeze-h-17">Status</th>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                thead += `<th colspan="2" class="col-harian">${i}</th>`;
            }

            thead += `</tr><tr>
                <th class="col-number freeze-col freeze-h-10">IN</th>
                <th class="col-number freeze-col freeze-h-11">OUT</th>
                <th class="col-number freeze-col freeze-h-12">Min</th>
                <th class="col-number freeze-col freeze-h-13">Safety</th>
                <th class="col-number freeze-col freeze-h-14">Max</th>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                thead += `
                    <th class="col-harian">D</th>
                    <th class="col-harian">N</th>
                `;
            }

            thead += `</tr>`;

            $('#fg_table thead').html(thead);
        }

        function getStatusBadge(status) {
            let color = 'bg-secondary';
            if (status === 'Problem') color = 'bg-danger';
            else if (status === 'Over') color = 'bg-warning';
            else if (status === 'Aman') color = 'bg-success';

            return `<span class="badge ${color}">${escapeHtml(status)}</span>`;
        }

        function buildRow(row, index, jumlahHari) {
            let html = `
                <tr
                    data-customer="${escapeHtml(row.customer)}"
                    data-project="${escapeHtml(row.project)}"
                    data-part-number="${escapeHtml(row.part_number)}"
                    data-part-name="${escapeHtml(row.part_name)}"
                >
                    <td class="freeze-col freeze-b-0">${index + 1}</td>
                    <td class="freeze-col freeze-b-1">${escapeHtml(row.customer)}</td>
                    <td class="freeze-col freeze-b-2">${escapeHtml(row.project)}</td>
                    <td class="freeze-col freeze-b-3">${escapeHtml(row.part_number)}</td>
                    <td class="freeze-col freeze-b-4" style="text-align:left;">${escapeHtml(row.part_name)}</td>
                    <td class="freeze-col freeze-b-5"><span>${row.total_po ?? 0}</span></td>
                    <td class="freeze-col freeze-b-6">
                        <input
                            name="advance_delivery"
                            class="form-control form-control-sm text-center"
                            type="number"
                            value="${row.advance_delivery ?? 0}"
                        >
                    </td>
                    <td class="freeze-col freeze-b-7"><span>${row.outstanding ?? 0}</span></td>
                    <td class="freeze-col freeze-b-8"><span>${row.percentage ?? 0}%</span></td>
                    <td class="freeze-col freeze-b-9">
                        <input
                            name="stock_awal"
                            class="form-control form-control-sm text-center input-biru"
                            type="number"
                            value="${row.stock_awal ?? 0}"
                        >
                    </td>
                    <td class="freeze-col freeze-b-10"><span>${row.total_in ?? 0}</span></td>
                    <td class="freeze-col freeze-b-11"><span>${row.total_out ?? 0}</span></td>
                    <td class="freeze-col freeze-b-12"><span>${row.level_min ?? 0}</span></td>
                    <td class="freeze-col freeze-b-13"><span>${row.level_safety ?? 0}</span></td>
                    <td class="freeze-col freeze-b-14"><span>${row.level_max ?? 0}</span></td>
                    <td class="freeze-col freeze-b-15"><span>${row.stock_on_hand ?? 0}</span></td>
                    <td class="freeze-col freeze-b-16">${getStatusBadge(row.status_stock ?? 'Aman')}</td>
                    <td class="freeze-col freeze-b-17">
                        <div class="legend-badge-group">
                            <span class="badge bg-success">IN</span>
                            <span class="badge bg-danger">OUT</span>
                            <span class="badge bg-primary">BAL</span>
                        </div>
                    </td>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                html += `
                    <td class="col-harian">
                        <div class="cell-input-group">
                            <input name="in_hari_${i}_d" class="form-control form-control-sm text-center input-hijau" value="${row[`in_hari_${i}_d`] ?? 0}" readonly>
                            <input name="out_hari_${i}_d" class="form-control form-control-sm text-center input-merah" type="number" value="${row[`out_hari_${i}_d`] ?? 0}">
                            <input name="balance_hari_${i}_d" class="form-control form-control-sm text-center input-biru" value="${row[`balance_hari_${i}_d`] ?? 0}" readonly>
                        </div>
                    </td>
                    <td class="col-harian">
                        <div class="cell-input-group">
                            <input name="in_hari_${i}_n" class="form-control form-control-sm text-center input-hijau" value="${row[`in_hari_${i}_n`] ?? 0}" readonly>
                            <input name="out_hari_${i}_n" class="form-control form-control-sm text-center input-merah" type="number" value="${row[`out_hari_${i}_n`] ?? 0}">
                            <input name="balance_hari_${i}_n" class="form-control form-control-sm text-center input-biru" value="${row[`balance_hari_${i}_n`] ?? 0}" readonly>
                        </div>
                    </td>
                `;
            }

            html += `</tr>`;
            return html;
        }

        function renderTable(data) {
            const bulan = parseInt($('#filter_bulan').val(), 10);
            const tahun = parseInt($('#filter_tahun').val(), 10);
            const jumlahHari = new Date(tahun, bulan, 0).getDate();

            buildTableHeader(jumlahHari);

            let tbody = '';
            data.forEach((row, index) => {
                tbody += buildRow(row, index, jumlahHari);
            });

            $('#fg_table tbody').html(tbody);
            setTimeout(() => {
                applyFreezeColumns();
            }, 50);
        }

        function applyFreezeColumns() {
            const freezeCount = 18;
            const lefts = [];
            const widths = [];

            const $firstBodyRow = $('#fg_table tbody tr:first');
            if (!$firstBodyRow.length) return;

            let left = 0;

            for (let i = 0; i < freezeCount; i++) {
                const $cell = $firstBodyRow.find(`.freeze-b-${i}`).first();
                const width = Math.ceil($cell.outerWidth()) || 0;

                widths[i] = width;
                lefts[i] = left;
                left += width;
            }

            $('.freeze-col, .freeze-group-total, .freeze-group-level').css({
                left: '',
                minWidth: '',
                width: ''
            });

            $('.freeze-separator').removeClass('freeze-separator');

            for (let i = 0; i < freezeCount; i++) {
                $(`.freeze-b-${i}, .freeze-h-${i}`).css('left', `${lefts[i]}px`);
            }

            $('.freeze-group-total').css({
                left: `${lefts[10]}px`,
                minWidth: `${widths[10] + widths[11]}px`,
                width: `${widths[10] + widths[11]}px`
            });

            $('.freeze-group-level').css({
                left: `${lefts[12]}px`,
                minWidth: `${widths[12] + widths[13] + widths[14]}px`,
                width: `${widths[12] + widths[13] + widths[14]}px`
            });

            $('.freeze-b-17, .freeze-h-17, .freeze-group-level').addClass('freeze-separator');
        }

        function loadTable() {
            const params = getParams();

            $('#table_loading').removeClass('d-none');

            $.ajax({
                url: '{{ route("monitoring.finishgood.data") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    bulan: params.bulan,
                    tahun: params.tahun,
                    customer: params.customer
                },
                success: function (res) {
                    currentData = res.data || [];
                    renderTable(currentData);
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal memuat data',
                        text: 'Data table tidak bisa dimuat.'
                    });
                },
                complete: function () {
                    $('#table_loading').addClass('d-none');
                }
            });
        }

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
                    const current = select.val();

                    select.empty();
                    select.append('<option value="">-- Semua Customer --</option>');

                    (res.data || []).forEach(r => {
                        select.append(`<option value="${r.customer}">${r.customer}</option>`);
                    });

                    if (current) {
                        select.val(current);
                    }
                }
            });
        }

        function calculateRow($row) {
            const jumlahHari = new Date(
                parseInt($('#filter_tahun').val(), 10),
                parseInt($('#filter_bulan').val(), 10),
                0
            ).getDate();

            let stockAwal = parseInt($row.find('input[name="stock_awal"]').val()) || 0;
            let advanceDelivery = parseInt($row.find('input[name="advance_delivery"]').val()) || 0;
            let totalPO = parseInt($row.find('td:eq(5) span').text()) || 0;
            let levelMin = parseInt($row.find('td:eq(12) span').text()) || 0;
            let levelMax = parseInt($row.find('td:eq(14) span').text()) || 0;

            let balance = stockAwal;
            let totalIn = 0;
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
                totalIn += (inD + inN);
                totalOut += (outD + outN);
            }

            $row.find('td:eq(10) span').text(totalIn);
            $row.find('td:eq(11) span').text(totalOut);
            $row.find('td:eq(15) span').text(balance);

            let statusHtml = '<span class="badge bg-success">Aman</span>';
            let statusText = 'Aman';

            if (balance <= levelMin) {
                statusHtml = '<span class="badge bg-danger">Problem</span>';
                statusText = 'Problem';
            } else if (balance > levelMax) {
                statusHtml = '<span class="badge bg-warning">Over</span>';
                statusText = 'Over';
            }

            $row.find('td:eq(16)').html(statusHtml);

            const outstanding = Math.max(0, totalPO - advanceDelivery - totalOut);
            const percentage = totalPO > 0 ? ((totalOut / totalPO) * 100).toFixed(2) : '0.00';

            $row.find('td:eq(7) span').text(outstanding);
            $row.find('td:eq(8) span').text(`${percentage}%`);

            return {
                stockAwal,
                totalIn,
                totalOut,
                stockOnHand: balance,
                statusText,
                outstanding,
                percentage,
                levelMin,
                levelMax,
                levelSafety: parseInt($row.find('td:eq(13) span').text()) || 0,
                advanceDelivery,
                totalPO
            };
        }

        function collectRowData($row) {
            const jumlahHari = new Date(
                parseInt($('#filter_tahun').val(), 10),
                parseInt($('#filter_bulan').val(), 10),
                0
            ).getDate();

            const calc = calculateRow($row);

            const data = {
                _token: '{{ csrf_token() }}',
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val(),
                customer: $.trim($row.attr('data-customer')),
                project: $.trim($row.attr('data-project')),
                part_number: $.trim($row.attr('data-part-number')),
                part_name: $.trim($row.attr('data-part-name')),
                stock_awal: calc.stockAwal,
                level_min: calc.levelMin,
                level_safety: calc.levelSafety,
                level_max: calc.levelMax,
                advance_delivery: calc.advanceDelivery
            };

            for (let i = 1; i <= jumlahHari; i++) {
                data[`in_hari_${i}_d`] = $row.find(`input[name="in_hari_${i}_d"]`).val() || 0;
                data[`out_hari_${i}_d`] = $row.find(`input[name="out_hari_${i}_d"]`).val() || 0;
                data[`balance_hari_${i}_d`] = $row.find(`input[name="balance_hari_${i}_d"]`).val() || 0;

                data[`in_hari_${i}_n`] = $row.find(`input[name="in_hari_${i}_n"]`).val() || 0;
                data[`out_hari_${i}_n`] = $row.find(`input[name="out_hari_${i}_n"]`).val() || 0;
                data[`balance_hari_${i}_n`] = $row.find(`input[name="balance_hari_${i}_n"]`).val() || 0;
            }

            return data;
        }

        function saveRow($row, successTitle = 'Data berhasil disimpan!') {
            const data = collectRowData($row);

            $row.addClass('saving-row');

            $.post('{{ route("monitoring.finishgood.save") }}', data)
                .done(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: successTitle,
                        showConfirmButton: false,
                        timer: 1500
                    });
                })
                .fail(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Gagal menyimpan data!',
                        showConfirmButton: false,
                        timer: 2000
                    });
                })
                .always(() => {
                    $row.removeClass('saving-row');
                });
        }

        $(function () {
            loadCustomerFilter();
            loadTable();

            $('#reload_table').on('click', function () {
                loadTable();
            });

            $('#filter_bulan, #filter_tahun').on('change', function () {
                $('#filter_customer').val('');
                loadCustomerFilter();
                loadTable();
            });

            $('#filter_customer').on('change', function () {
                loadTable();
            });

            $('#fg_table tbody').on('blur', 'input[name^="out_hari_"]', function () {
                const $row = $(this).closest('tr');
                saveRow($row, 'Data berhasil disimpan!');
            });

            $('#fg_table tbody').on('blur', 'input[name="advance_delivery"]', function () {
                const $row = $(this).closest('tr');
                saveRow($row, 'Advance Delivery Disimpan');
            });

            $('#fg_table tbody').on('blur', 'input[name="stock_awal"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = new Date(
                    parseInt($('#filter_tahun').val(), 10),
                    parseInt($('#filter_bulan').val(), 10),
                    0
                ).getDate();

                let stockAwal = parseInt($(this).val()) || 0;
                let balance = stockAwal;

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
                }

                $row.find('td:eq(15) span').text(balance);

                const levelMin = parseInt($row.find('td:eq(12) span').text()) || 0;
                const levelMax = parseInt($row.find('td:eq(14) span').text()) || 0;

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
                    customer: $.trim($row.attr('data-customer')),
                    kode_project: $.trim($row.attr('data-project')),
                    part_number: $.trim($row.attr('data-part-number')),
                    stock_awal: stockAwal
                })
                .done(() => {
                    saveRow($row, 'Stock Awal diperbarui');
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
                const customer = $('#filter_customer').val();

                const url = new URL('{{ route("monitoring.finishgood.export") }}', window.location.origin);
                url.searchParams.set('bulan', bulan);
                url.searchParams.set('tahun', tahun);

                if (customer) {
                    url.searchParams.set('customer', customer);
                }

                window.location.href = url.toString();
            });

            $(window).on('resize', function () {
                applyFreezeColumns();
            });
        });
    </script>
</x-default-layout>