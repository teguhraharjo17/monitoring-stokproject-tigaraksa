<x-default-layout>
    @section('title', 'Monitoring Sub Assy')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Monitoring Sub Assy</h3>
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

                    <form id="export_form"
                        action="{{ route('monitoring.subassy.export') }}"
                        method="GET"
                        target="_blank"
                        class="d-inline">
                        <input type="hidden" name="bulan" id="export_bulan">
                        <input type="hidden" name="tahun" id="export_tahun">
                        <button type="submit" class="btn btn-success btn-sm">📤 Export</button>
                    </form>
                </div>
            </div>

            <div id="table_loading" class="alert alert-info py-2 px-3 d-none">
                Memuat data...
            </div>

            <div class="table-wrap">
                <table id="subassy_table" class="table mb-0">
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

        #subassy_table {
            width: max-content;
            min-width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        #subassy_table th,
        #subassy_table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            border-right: 1px solid #343a40;
            border-bottom: 1px solid #343a40;
        }

        #subassy_table tr th:first-child,
        #subassy_table tr td:first-child {
            border-left: 1px solid #343a40;
        }

        #subassy_table thead tr:first-child th {
            border-top: 1px solid #343a40;
        }

        #subassy_table thead th {
            position: sticky;
            background: #fff;
            z-index: 3;
            font-weight: 700;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        #subassy_table thead tr:first-child th {
            top: 0;
            z-index: 5;
        }

        #subassy_table thead tr:nth-child(2) th {
            top: 42px;
            z-index: 6;
            background: #fff;
            padding-top: 12px;
            padding-bottom: 12px;
            border-bottom: 2px solid #343a40;
        }

        #subassy_table thead tr:first-child th[rowspan="2"] {
            border-bottom: 2px solid #343a40;
        }

        #subassy_table tbody td {
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .col-no { min-width: 50px; }
        .col-customer { min-width: 120px; }
        .col-project { min-width: 120px; }
        .col-part-number { min-width: 140px; }
        .col-part-name { min-width: 240px; text-align: left !important; }
        .col-number { min-width: 95px; }
        .col-status { min-width: 95px; }
        .col-harian { min-width: 78px; }

        .col-harian input {
            width: 64px;
            min-width: 64px;
            max-width: 64px;
            padding: 2px 4px;
            font-size: 11px;
            height: 24px;
            text-align: center;
            border-radius: 3px;
        }

        .input-hijau {
            background-color: #d6f5d6 !important;
        }

        .input-merah {
            background-color: #fcdcdc !important;
        }

        .input-biru {
            background-color: #d8e9ff !important;
            font-weight: bold;
            color: #004085;
        }

        .badge {
            font-size: 10px;
            padding: 4px 6px;
            line-height: 1;
            border-radius: 4px;
        }

        .badge.bg-danger { background-color: #dc3545 !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #212529 !important; }
        .badge.bg-success { background-color: #28a745 !important; }
        .badge.bg-primary { background-color: #0d6efd !important; }

        /* Garis pemisah antar grup produk */
        .row-group-first td {
            border-top: 2px solid #343a40 !important;
        }

        input[name="wip_sebelumnya"] {
            width: 72px;
            min-width: 72px;
            text-align: center;
            margin: 0 auto;
        }

        .saving-row {
            background-color: #fff8db !important;
        }

        .freeze-col {
            position: sticky;
            background: #fff !important;
            z-index: 2;
        }

        #subassy_table thead .freeze-col {
            z-index: 8;
        }

        #subassy_table thead tr:first-child .freeze-col {
            z-index: 9;
        }

        #subassy_table thead tr:first-child .freeze-col,
        #subassy_table thead tr:first-child .freeze-group-tanggal {
            z-index: 9;
        }

        #subassy_table thead tr:nth-child(2) .freeze-col {
            z-index: 10;
        }

        .saving-row .freeze-col {
            background-color: #fff8db !important;
        }

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

        .produktifitas-kurang {
            color: #dc3545;
            font-weight: 700;
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
                    <th rowspan="2" class="col-number freeze-col freeze-h-6">WIP Sebelumnya</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-7">Total SPK</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-8">Total Produksi</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-9">WIP Akhir</th>
                    <th rowspan="2" class="col-number freeze-col freeze-h-10">Produktivitas</th>
                    <th rowspan="2" class="col-status freeze-col freeze-h-11">Status</th>
                    <th colspan="${jumlahHari}">Tanggal</th>
                </tr>
                <tr>
            `;

            for (let i = 1; i <= jumlahHari; i++) {
                thead += `<th class="col-harian">${i}</th>`;
            }

            thead += `</tr>`;

            $('#subassy_table thead').html(thead);
        }

        function buildProduktivitas(val) {
            const angka = parseInt(val) || 0;
            if (angka < 100) {
                return `<span class="produktifitas-kurang">${angka}%</span>`;
            }
            return `<span>${angka}%</span>`;
        }

        function buildRow(row, index, jumlahHari) {
            // === Baris 1: SPK ===
            let html = `
                <tr class="row-group-first" data-group="${index}"
                    data-customer="${escapeHtml(row.customer)}"
                    data-project="${escapeHtml(row.project)}"
                    data-part-number="${escapeHtml(row.part_number)}"
                    data-part-name="${escapeHtml(row.part_name)}"
                >
                    <td class="freeze-col freeze-b-0" rowspan="3">${index + 1}</td>
                    <td class="freeze-col freeze-b-1" rowspan="3">${escapeHtml(row.customer)}</td>
                    <td class="freeze-col freeze-b-2" rowspan="3">${escapeHtml(row.project)}</td>
                    <td class="freeze-col freeze-b-3" rowspan="3">${escapeHtml(row.part_number)}</td>
                    <td class="freeze-col freeze-b-4" rowspan="3" style="text-align:left;">${escapeHtml(row.part_name)}</td>
                    <td class="freeze-col freeze-b-5" rowspan="3"><span>${row.total_po ?? 0}</span></td>
                    <td class="freeze-col freeze-b-6" rowspan="3">
                        <input
                            type="number"
                            name="wip_sebelumnya"
                            class="form-control form-control-sm text-center"
                            value="${row.wip_sebelumnya ?? 0}"
                        >
                    </td>
                    <td class="freeze-col freeze-b-7" rowspan="3"><span>${row.total_spk ?? 0}</span></td>
                    <td class="freeze-col freeze-b-8" rowspan="3"><span>${row.total_produksi ?? 0}</span></td>
                    <td class="freeze-col freeze-b-9" rowspan="3"><span>${row.wip_akhir ?? 0}</span></td>
                    <td class="freeze-col freeze-b-10" rowspan="3">${buildProduktivitas(row.produktivitas ?? 0)}</td>
                    <td class="freeze-col freeze-b-11"><span class="badge bg-primary">SPK</span></td>
            `;
            for (let i = 1; i <= jumlahHari; i++) {
                html += `<td class="col-harian"><input type="number" name="spk_hari_${i}" class="form-control form-control-sm text-center input-biru" value="${row[`spk_hari_${i}`] ?? 0}" readonly tabindex="-1"></td>`;
            }
            html += `</tr>`;

            // === Baris 2: Produksi ===
            html += `<tr data-group="${index}">
                    <td class="freeze-col freeze-b-11"><span class="badge bg-success">Produksi</span></td>`;
            for (let i = 1; i <= jumlahHari; i++) {
                html += `<td class="col-harian"><input type="number" name="produksi_hari_${i}" class="form-control form-control-sm text-center input-hijau" value="${row[`produksi_hari_${i}`] ?? 0}"></td>`;
            }
            html += `</tr>`;

            // === Baris 3: WIP ===
            html += `<tr data-group="${index}">
                    <td class="freeze-col freeze-b-11"><span class="badge bg-danger">WIP</span></td>`;
            for (let i = 1; i <= jumlahHari; i++) {
                html += `<td class="col-harian"><input type="number" name="wip_hari_${i}" class="form-control form-control-sm text-center input-merah" value="${row[`wip_hari_${i}`] ?? 0}" readonly tabindex="-1"></td>`;
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

            $('#subassy_table tbody').html(tbody);

            // Hitung totals per group (setiap 3 baris = 1 produk)
            $('#subassy_table tbody tr.row-group-first').each(function () {
                const groupIdx = $(this).data('group');
                const $group = $(`#subassy_table tbody tr[data-group="${groupIdx}"]`);
                calculateTotals($group);
            });

            setTimeout(() => {
                applyFreezeColumns();
            }, 50);
        }

        function applyFreezeColumns() {
            const freezeCount = 12;
            const lefts = [];
            const widths = [];

            const $firstBodyRow = $('#subassy_table tbody tr:first');
            if (!$firstBodyRow.length) return;

            let left = 0;

            for (let i = 0; i < freezeCount; i++) {
                const $cell = $firstBodyRow.find(`.freeze-b-${i}`).first();
                const width = Math.ceil($cell.outerWidth()) || 0;

                widths[i] = width;
                lefts[i] = left;
                left += width;
            }

            $('.freeze-col, .freeze-group-tanggal').css({
                left: '',
                minWidth: '',
                width: ''
            });

            $('.freeze-separator').removeClass('freeze-separator');

            for (let i = 0; i < freezeCount; i++) {
                $(`.freeze-b-${i}, .freeze-h-${i}`).css('left', `${lefts[i]}px`);
            }

            $('.freeze-b-11, .freeze-h-11').addClass('freeze-separator');
        }

        // $rowGroup = jQuery object berisi 3 <tr> dari satu produk
        function calculateTotals($rowGroup) {
            const jumlahHari = new Date(
                parseInt($('#filter_tahun').val(), 10),
                parseInt($('#filter_bulan').val(), 10),
                0
            ).getDate();

            let totalSPK = 0;
            let totalProduksi = 0;

            const spkValues = {};
            const produksiValues = {};
            const wipValues = {};

            for (let i = 1; i <= jumlahHari; i++) {
                const spk = parseInt($rowGroup.find(`input[name="spk_hari_${i}"]`).val()) || 0;
                const produksi = parseInt($rowGroup.find(`input[name="produksi_hari_${i}"]`).val()) || 0;

                spkValues[i] = spk;
                produksiValues[i] = produksi;
                totalSPK += spk;
                totalProduksi += produksi;
            }

            const wipSebelumnya = parseInt($rowGroup.find('input[name="wip_sebelumnya"]').val()) || 0;
            let wipSebelum = wipSebelumnya;

            for (let i = 1; i <= jumlahHari; i++) {
                const wip = wipSebelum + spkValues[i] - produksiValues[i];
                $rowGroup.find(`input[name="wip_hari_${i}"]`).val(wip);
                wipValues[i] = wip;
                wipSebelum = wip;
            }

            const wipAkhir = wipValues[jumlahHari] || 0;
            const produktivitas = totalSPK > 0 ? Math.ceil((totalProduksi / totalSPK) * 100) : 0;

            // Kolom rowspan ada di baris pertama grup
            const $firstRow = $rowGroup.first();
            $firstRow.find('td:eq(7) span').text(totalSPK);
            $firstRow.find('td:eq(8) span').text(totalProduksi);
            $firstRow.find('td:eq(9) span').text(wipAkhir);
            $firstRow.find('td:eq(10)').html(buildProduktivitas(produktivitas));

            return {
                totalSPK,
                totalProduksi,
                wipAkhir,
                produktivitas
            };
        }

        // Helper: dari sebuah <tr>, ambil seluruh row group-nya
        function getRowGroup($row) {
            const groupIdx = $row.closest('tr').data('group');
            return $(`#subassy_table tbody tr[data-group="${groupIdx}"]`);
        }

        function collectRowData($rowGroup) {
            const jumlahHari = new Date(
                parseInt($('#filter_tahun').val(), 10),
                parseInt($('#filter_bulan').val(), 10),
                0
            ).getDate();

            calculateTotals($rowGroup);

            const $firstRow = $rowGroup.first();
            const data = {
                _token: '{{ csrf_token() }}',
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val(),
                customer: $.trim($firstRow.attr('data-customer')),
                project: $.trim($firstRow.attr('data-project')),
                part_number: $.trim($firstRow.attr('data-part-number')),
                part_name: $.trim($firstRow.attr('data-part-name')),
                wip_sebelumnya: parseInt($rowGroup.find('input[name="wip_sebelumnya"]').val()) || 0
            };

            for (let i = 1; i <= jumlahHari; i++) {
                data[`spk_hari_${i}`] = parseInt($rowGroup.find(`input[name="spk_hari_${i}"]`).val()) || 0;
                data[`produksi_hari_${i}`] = parseInt($rowGroup.find(`input[name="produksi_hari_${i}"]`).val()) || 0;
                data[`wip_hari_${i}`] = parseInt($rowGroup.find(`input[name="wip_hari_${i}"]`).val()) || 0;
            }

            return data;
        }

        function saveRow($rowGroup, successTitle = 'Tersimpan!') {
            const data = collectRowData($rowGroup);

            $rowGroup.addClass('saving-row');

            $.post('{{ route("monitoring.subassy.save") }}', data)
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
                .fail((xhr) => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: xhr.responseJSON?.message ?? 'Gagal menyimpan data!',
                        showConfirmButton: false,
                        timer: 3000
                    });
                })
                .always(() => {
                    $rowGroup.removeClass('saving-row');
                });
        }

        function loadTable() {
            const params = getParams();

            $('#table_loading').removeClass('d-none');

            $.ajax({
                url: '{{ route("monitoring.subassy.data") }}',
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
                url: '{{ route("monitoring.subassy.data") }}',
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

            $('#export_form').on('submit', function () {
                $('#export_bulan').val($('#filter_bulan').val());
                $('#export_tahun').val($('#filter_tahun').val());
            });

            $('#subassy_table tbody').on('input', 'input[name="wip_sebelumnya"]', function () {
                const $group = getRowGroup($(this));
                calculateTotals($group);
            });

            $('#subassy_table tbody').on('input', 'input[name^="produksi_hari_"]', function () {
                const $group = getRowGroup($(this));
                calculateTotals($group);
            });

            $('#subassy_table tbody').on('blur', 'input[name="wip_sebelumnya"], input[name^="produksi_hari_"]', function () {
                const $group = getRowGroup($(this));
                saveRow($group);
            });

            $(window).on('resize', function () {
                applyFreezeColumns();
            });
        });
    </script>
</x-default-layout>