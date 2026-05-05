<x-default-layout>
    @section('title', 'Monitoring MIP')

    <div class="mip-shell my-5">
        <section class="mip-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-7">
                        <span class="hero-chip mb-4"><i class="fas fa-boxes-stacked me-2"></i>Monitoring stok MIP</span>
                        <h1 class="text-white fw-bolder mb-3">Kontrol arus IN, OUT, dan balance MIP dalam dashboard yang lebih jelas</h1>
                        <p class="hero-copy mb-0">Filter, cari part, ubah stock awal atau OUT harian, lalu lihat perubahan balance dan ringkasan stok tanpa gangguan template demo.</p>
                    </div>
                    <div class="col-xl-5">
                        <div class="hero-stats">
                            <div class="hero-stat"><span class="label">Baris Aktif</span><span class="value" id="summary_rows">0</span><span class="note">Data sesuai filter saat ini</span></div>
                            <div class="hero-stat"><span class="label">Total IN</span><span class="value" id="summary_in">0</span><span class="note">Supply dari proses sebelumnya</span></div>
                            <div class="hero-stat"><span class="label">Total OUT</span><span class="value" id="summary_out">0</span><span class="note">Pemakaian / keluaran bulan ini</span></div>
                            <div class="hero-stat"><span class="label">Balance Akhir</span><span class="value" id="summary_balance">0</span><span class="note">Akumulasi balance terakhir</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card control-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    <div class="col-xl-8">
                        <div class="panel-box">
                            <div class="panel-title">Filter dan pencarian</div>
                            <div class="panel-subtitle">Pilih periode, customer, lalu cari part MIP secara instan.</div>
                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-3 col-sm-6">
                                    <label for="filter_bulan" class="form-label fw-semibold">Bulan</label>
                                    <select id="filter_bulan" class="form-select form-select-solid">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="filter_tahun" class="form-label fw-semibold">Tahun</label>
                                    <select id="filter_tahun" class="form-select form-select-solid">
                                        @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="filter_customer" class="form-label fw-semibold">Customer</label>
                                    <select id="filter_customer" class="form-select form-select-solid">
                                        <option value="">Semua Customer</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="table_search" class="form-label fw-semibold">Cari Part</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                                        <input id="table_search" type="text" class="form-control form-control-solid border-0" placeholder="Customer, project, part">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="panel-box h-100">
                            <div class="panel-title">Aksi cepat</div>
                            <div class="panel-subtitle">Segarkan data, ekspor laporan, atau lihat periode aktif saat ini.</div>
                            <div class="quick-metrics mt-4">
                                <div class="quick-metric"><span class="label">Hari pada bulan ini</span><span class="value" id="summary_days">0</span></div>
                                <div class="quick-metric"><span class="label">Customer terfilter</span><span class="value" id="summary_customer">Semua</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button id="reload_table" class="btn btn-primary"><i class="fas fa-rotate-right me-2"></i>Refresh Data</button>
                                <form id="export_form" action="{{ route('monitoring.mip.export') }}" method="GET" class="d-inline">
                                    <input type="hidden" name="bulan" id="export_bulan">
                                    <input type="hidden" name="tahun" id="export_tahun">
                                    <button type="submit" class="btn btn-light-success"><i class="fas fa-file-export me-2"></i>Export Excel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="table_loading" class="loading-banner d-none mt-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Memuat data monitoring MIP...
                </div>
            </div>
        </section>

        <section class="card table-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
                    <div>
                        <div class="panel-title mb-1">Tabel balance harian MIP</div>
                        <div class="panel-subtitle">Ubah `Stock Awal` dan `OUT Harian`. IN dan balance akan dihitung otomatis.</div>
                    </div>
                    <div class="table-legend">
                        <span class="legend-item"><span class="legend-box in"></span>IN</span>
                        <span class="legend-item"><span class="legend-box out"></span>OUT</span>
                        <span class="legend-item"><span class="legend-box bal"></span>BAL</span>
                    </div>
                </div>
                <div id="empty_state" class="empty-state d-none">
                    <div class="empty-icon"><i class="fas fa-table"></i></div>
                    <div class="fw-bold fs-4 mb-2">Belum ada data untuk filter ini</div>
                    <div class="text-muted">Coba ganti bulan, tahun, customer, atau kata kunci pencarian.</div>
                </div>
                <div class="table-wrap" id="table_wrap">
                    <table id="mip_table" class="table mb-0">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <style>
        .mip-shell { display: grid; gap: 1.5rem; }
        .mip-hero { border: 0; border-radius: 28px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #164e63 0%, #0f766e 44%, #1d4ed8 100%); box-shadow: 0 24px 60px rgba(21, 94, 117, .18); }
        .mip-hero .card-body { padding: 2rem; }
        .hero-chip { display: inline-flex; align-items: center; padding: .6rem .95rem; border-radius: 999px; background: rgba(255,255,255,.14); color: #fff; font-size: .9rem; }
        .hero-copy { color: rgba(255,255,255,.82); font-size: 1rem; max-width: 720px; }
        .hero-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .hero-stat { padding: 1rem 1.1rem; border-radius: 20px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); color: #fff; }
        .hero-stat .label, .quick-metric .label { display: block; font-size: .76rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.72); }
        .hero-stat .value, .quick-metric .value { display: block; margin-top: .35rem; font-weight: 700; font-size: 1.5rem; line-height: 1.1; }
        .hero-stat .note { display: block; margin-top: .45rem; color: rgba(255,255,255,.74); font-size: .84rem; }
        .control-panel, .table-panel { border: 0; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 16px 40px rgba(15, 23, 42, .07); }
        .panel-box { height: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 1rem; }
        .panel-title { font-size: 1.1rem; font-weight: 700; color: #12324a; }
        .panel-subtitle { color: #64748b; font-size: .92rem; }
        .quick-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
        .quick-metric { padding: .9rem 1rem; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .quick-metric .label { color: #64748b; }
        .quick-metric .value { color: #0f172a; font-size: 1.2rem; }
        .loading-banner { display: inline-flex; align-items: center; padding: .85rem 1rem; border-radius: 14px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .table-legend { display: flex; flex-wrap: wrap; gap: .75rem; }
        .legend-item { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .8rem; border-radius: 999px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: .88rem; font-weight: 600; }
        .legend-box { width: 12px; height: 12px; border-radius: 4px; }
        .legend-box.in { background: #dcfce7; border: 1px solid #86efac; } .legend-box.out { background: #fee2e2; border: 1px solid #fca5a5; } .legend-box.bal { background: #dbeafe; border: 1px solid #93c5fd; }
        .empty-state { padding: 3rem 1rem; text-align: center; }
        .empty-icon { width: 70px; height: 70px; border-radius: 22px; margin: 0 auto 1rem; display: grid; place-items: center; background: #e2e8f0; color: #334155; font-size: 1.5rem; }
        .table-wrap { width: 100%; overflow: auto; max-height: 70vh; border: 1px solid #cbd5e1; border-radius: 20px; position: relative; background: #fff; }
        #mip_table { width: max-content; min-width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
        #mip_table th, #mip_table td { text-align: center; vertical-align: middle; white-space: nowrap; padding: 8px 10px; font-size: 13px; background: #fff; border-right: 1px solid #dbe3ee; border-bottom: 1px solid #dbe3ee; }
        #mip_table tr th:first-child, #mip_table tr td:first-child { border-left: 1px solid #dbe3ee; }
        #mip_table thead tr:first-child th { border-top: 1px solid #dbe3ee; top: 0; z-index: 5; }
        #mip_table thead th { position: sticky; background: #f8fafc; z-index: 3; font-weight: 700; padding-top: 10px; padding-bottom: 10px; color: #475569; text-transform: uppercase; letter-spacing: .04em; font-size: .76rem; }
        #mip_table thead tr:nth-child(2) th { top: 42px; z-index: 6; background: #f8fafc; padding-top: 12px; padding-bottom: 12px; border-bottom: 2px solid #94a3b8; }
        #mip_table thead tr:first-child th[rowspan="2"] { border-bottom: 2px solid #94a3b8; }
        #mip_table tbody td { padding-top: 12px; padding-bottom: 12px; }
        #mip_table tbody tr:hover td, #mip_table tbody tr:hover .freeze-col { background: #f8fbff !important; }
        .col-no { min-width: 50px; } .col-customer { min-width: 140px; } .col-project { min-width: 140px; } .col-part-number { min-width: 150px; } .col-part-name { min-width: 260px; text-align: left !important; } .col-number { min-width: 110px; } .col-status { min-width: 120px; } .col-harian { min-width: 78px; }
        .cell-input-group { display: grid; grid-template-rows: repeat(3, auto); gap: 4px; width: 64px; margin: 0 auto; }
        .cell-input-group input { width: 64px; min-width: 64px; max-width: 64px; padding: 2px 6px; font-size: 11px; height: 26px; text-align: center; border-radius: 6px; border: 1px solid #cbd5e1; }
        .input-hijau { background-color: #dcfce7 !important; } .input-merah { background-color: #fee2e2 !important; } .input-biru { background-color: #dbeafe !important; font-weight: bold; color: #1d4ed8; }
        .legend-badge-group { display: flex; flex-direction: column; gap: 4px; align-items: center; }
        .badge { font-size: 10px; padding: 5px 7px; line-height: 1; border-radius: 999px; }
        .badge.bg-danger { background-color: #dc2626 !important; } .badge.bg-success { background-color: #16a34a !important; } .badge.bg-primary { background-color: #2563eb !important; }
        input[name="stock_awal"] { width: 72px; min-width: 72px; text-align: center; margin: 0 auto; }
        .saving-row { background-color: #fff8db !important; }
        .freeze-col, .freeze-group-level { position: sticky; background: #fff !important; z-index: 2; }
        #mip_table thead .freeze-col, #mip_table thead .freeze-group-level { z-index: 8; }
        #mip_table thead tr:first-child .freeze-col, #mip_table thead tr:first-child .freeze-group-level { z-index: 9; }
        #mip_table thead tr:nth-child(2) .freeze-col { z-index: 10; }
        .saving-row .freeze-col { background-color: #fff8db !important; }
        .freeze-separator { position: sticky; }
        .freeze-separator::after { content: ""; position: absolute; top: -1px; right: -1px; width: 2px; height: calc(100% + 2px); background: #94a3b8; z-index: 30; pointer-events: none; }
        @media (max-width: 991.98px) { .mip-hero .card-body { padding: 1.5rem; } .hero-stats, .quick-metrics { grid-template-columns: 1fr; } }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let currentData = [];
        let visibleData = [];

        function getParams() {
            return { bulan: $('#filter_bulan').val(), tahun: $('#filter_tahun').val(), customer: $('#filter_customer').val() };
        }

        function escapeHtml(text) {
            return String(text ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function buildTableHeader(jumlahHari) {
            let thead = '<tr><th rowspan="2" class="col-no freeze-col freeze-h-0">No</th><th rowspan="2" class="col-customer freeze-col freeze-h-1">Customer</th><th rowspan="2" class="col-project freeze-col freeze-h-2">Project</th><th rowspan="2" class="col-part-number freeze-col freeze-h-3">Part Number</th><th rowspan="2" class="col-part-name freeze-col freeze-h-4">Part Name</th><th rowspan="2" class="col-number freeze-col freeze-h-5">Total PO</th><th rowspan="2" class="col-number freeze-col freeze-h-6">Stock Awal</th><th rowspan="2" class="col-number freeze-col freeze-h-7">Total IN</th><th rowspan="2" class="col-number freeze-col freeze-h-8">Total OUT</th><th colspan="3" class="freeze-group-level">Level Stock</th><th rowspan="2" class="col-status freeze-col freeze-h-12">Status</th><th colspan="' + jumlahHari + '">Tanggal</th></tr><tr><th class="col-number freeze-col freeze-h-9">Min</th><th class="col-number freeze-col freeze-h-10">Safety</th><th class="col-number freeze-col freeze-h-11">Max</th>';
            for (let i = 1; i <= jumlahHari; i++) thead += '<th class="col-harian">' + i + '</th>';
            $('#mip_table thead').html(thead + '</tr>');
        }

        function buildRow(row, index, jumlahHari) {
            let html = '<tr data-customer="' + escapeHtml(row.customer) + '" data-project="' + escapeHtml(row.project) + '" data-part-number="' + escapeHtml(row.part_number) + '" data-part-name="' + escapeHtml(row.part_name) + '"><td class="freeze-col freeze-b-0">' + (index + 1) + '</td><td class="freeze-col freeze-b-1">' + escapeHtml(row.customer) + '</td><td class="freeze-col freeze-b-2">' + escapeHtml(row.project) + '</td><td class="freeze-col freeze-b-3">' + escapeHtml(row.part_number) + '</td><td class="freeze-col freeze-b-4" style="text-align:left;">' + escapeHtml(row.part_name) + '</td><td class="freeze-col freeze-b-5"><span>' + (row.total_po ?? 0) + '</span></td><td class="freeze-col freeze-b-6"><input type="number" name="stock_awal" class="form-control form-control-sm text-center input-biru" value="' + (row.stock_awal ?? 0) + '"></td><td class="freeze-col freeze-b-7"><span>' + (row.total_in ?? 0) + '</span></td><td class="freeze-col freeze-b-8"><span>' + (row.total_out ?? 0) + '</span></td><td class="freeze-col freeze-b-9"><span>' + (row.level_min ?? 0) + '</span></td><td class="freeze-col freeze-b-10"><span>' + (row.level_safety ?? 0) + '</span></td><td class="freeze-col freeze-b-11"><span>' + (row.level_max ?? 0) + '</span></td><td class="freeze-col freeze-b-12"><div class="legend-badge-group"><span class="badge bg-success">IN</span><span class="badge bg-danger">OUT</span><span class="badge bg-primary">BAL</span></div></td>';
            for (let i = 1; i <= jumlahHari; i++) html += '<td class="col-harian"><div class="cell-input-group"><input type="number" name="in_hari_' + i + '" class="form-control form-control-sm text-center input-hijau" value="' + (row['in_hari_' + i] ?? 0) + '" readonly tabindex="-1"><input type="number" name="out_hari_' + i + '" class="form-control form-control-sm text-center input-merah" value="' + (row['out_hari_' + i] ?? 0) + '"><input type="number" name="balance_hari_' + i + '" class="form-control form-control-sm text-center input-biru" value="' + (row['balance_hari_' + i] ?? 0) + '" readonly tabindex="-1"></div></td>';
            return html + '</tr>';
        }

        function updateSummary(data) {
            const totalIn = data.reduce((sum, row) => sum + (parseInt(row.total_in, 10) || 0), 0);
            const totalOut = data.reduce((sum, row) => sum + (parseInt(row.total_out, 10) || 0), 0);
            const totalBalance = data.reduce((sum, row) => {
                const bulan = parseInt($('#filter_bulan').val(), 10);
                const tahun = parseInt($('#filter_tahun').val(), 10);
                const lastDay = new Date(tahun, bulan, 0).getDate();
                return sum + (parseInt(row['balance_hari_' + lastDay], 10) || 0);
            }, 0);
            const bulan = parseInt($('#filter_bulan').val(), 10);
            const tahun = parseInt($('#filter_tahun').val(), 10);
            $('#summary_rows').text(data.length.toLocaleString('id-ID'));
            $('#summary_in').text(totalIn.toLocaleString('id-ID'));
            $('#summary_out').text(totalOut.toLocaleString('id-ID'));
            $('#summary_balance').text(totalBalance.toLocaleString('id-ID'));
            $('#summary_days').text(new Date(tahun, bulan, 0).getDate().toLocaleString('id-ID'));
            $('#summary_customer').text($('#filter_customer').val() || 'Semua');
        }

        function filterVisibleData() {
            const keyword = ($('#table_search').val() || '').trim().toLowerCase();
            visibleData = !keyword ? [...currentData] : currentData.filter((row) => [row.customer, row.project, row.part_number, row.part_name].join(' ').toLowerCase().includes(keyword));
            return visibleData;
        }

        function calculateRow($row) {
            const jumlahHari = new Date(parseInt($('#filter_tahun').val(), 10), parseInt($('#filter_bulan').val(), 10), 0).getDate();
            let balance = parseInt($row.find('input[name="stock_awal"]').val(), 10) || 0;
            let totalIn = 0, totalOut = 0;
            for (let i = 1; i <= jumlahHari; i++) {
                const valIn = parseInt($row.find('input[name="in_hari_' + i + '"]').val(), 10) || 0;
                const valOut = parseInt($row.find('input[name="out_hari_' + i + '"]').val(), 10) || 0;
                balance = balance + valIn - valOut;
                totalIn += valIn;
                totalOut += valOut;
                $row.find('input[name="balance_hari_' + i + '"]').val(balance);
            }
            $row.find('td:eq(7) span').text(totalIn);
            $row.find('td:eq(8) span').text(totalOut);
            return { totalIn, totalOut, lastBalance: balance };
        }

        function renderTable(data) {
            const bulan = parseInt($('#filter_bulan').val(), 10);
            const tahun = parseInt($('#filter_tahun').val(), 10);
            const jumlahHari = new Date(tahun, bulan, 0).getDate();
            buildTableHeader(jumlahHari);
            let tbody = '';
            data.forEach((row, index) => { tbody += buildRow(row, index, jumlahHari); });
            $('#mip_table tbody').html(tbody);
            $('#empty_state').toggleClass('d-none', data.length > 0);
            $('#table_wrap').toggleClass('d-none', data.length === 0);
            updateSummary(data);
            $('#mip_table tbody tr').each(function () { calculateRow($(this)); });
            setTimeout(applyFreezeColumns, 50);
        }

        function refreshVisibleTable() {
            renderTable(filterVisibleData());
        }

        function applyFreezeColumns() {
            const $firstBodyRow = $('#mip_table tbody tr:first');
            if (!$firstBodyRow.length) return;
            const widths = [];
            let left = 0;
            $('.freeze-separator').removeClass('freeze-separator');
            for (let i = 0; i < 13; i++) {
                const width = Math.ceil($firstBodyRow.find('.freeze-b-' + i).first().outerWidth()) || 0;
                widths[i] = width;
                $('.freeze-b-' + i + ', .freeze-h-' + i).css('left', left + 'px');
                left += width;
            }
            $('.freeze-group-level').css({ left: $('.freeze-h-9').css('left'), minWidth: (widths[9] + widths[10] + widths[11]) + 'px', width: (widths[9] + widths[10] + widths[11]) + 'px' });
            $('.freeze-b-12, .freeze-h-12, .freeze-group-level').addClass('freeze-separator');
        }

        function collectRowData($row) {
            const jumlahHari = new Date(parseInt($('#filter_tahun').val(), 10), parseInt($('#filter_bulan').val(), 10), 0).getDate();
            calculateRow($row);
            const data = {
                _token: '{{ csrf_token() }}',
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val(),
                customer: $.trim($row.attr('data-customer')),
                project: $.trim($row.attr('data-project')),
                part_number: $.trim($row.attr('data-part-number')),
                part_name: $.trim($row.attr('data-part-name')),
                stock_awal: parseInt($row.find('input[name="stock_awal"]').val(), 10) || 0,
                level_min: parseInt($row.find('td:eq(9) span').text(), 10) || 0,
                level_safety: parseInt($row.find('td:eq(10) span').text(), 10) || 0,
                level_max: parseInt($row.find('td:eq(11) span').text(), 10) || 0
            };
            for (let i = 1; i <= jumlahHari; i++) {
                data['in_hari_' + i] = parseInt($row.find('input[name="in_hari_' + i + '"]').val(), 10) || 0;
                data['out_hari_' + i] = parseInt($row.find('input[name="out_hari_' + i + '"]').val(), 10) || 0;
            }
            return data;
        }

        function saveRow($row, successTitle = 'Tersimpan') {
            $row.addClass('saving-row');
            $.post('{{ route("monitoring.mip.save") }}', collectRowData($row))
                .done(() => {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: successTitle, showConfirmButton: false, timer: 1500 });
                })
                .fail(() => {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal menyimpan data', showConfirmButton: false, timer: 2000 });
                })
                .always(() => {
                    $row.removeClass('saving-row');
                });
        }

        function loadTable() {
            const params = getParams();
            $('#table_loading').removeClass('d-none');
            $.ajax({
                url: '{{ route("monitoring.mip.data") }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', bulan: params.bulan, tahun: params.tahun, customer: params.customer },
                success: function (res) {
                    currentData = res.data || [];
                    refreshVisibleTable();
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Gagal memuat data', text: 'Data monitoring tidak bisa dimuat.' });
                },
                complete: function () {
                    $('#table_loading').addClass('d-none');
                }
            });
        }

        function loadCustomerFilter() {
            $.ajax({
                url: '{{ route("monitoring.mip.data") }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', bulan: $('#filter_bulan').val(), tahun: $('#filter_tahun').val(), only_customer: true },
                success: function (res) {
                    const select = $('#filter_customer');
                    const current = select.val();
                    select.empty().append('<option value="">Semua Customer</option>');
                    (res.data || []).forEach((row) => {
                        select.append('<option value="' + escapeHtml(row.customer) + '">' + escapeHtml(row.customer) + '</option>');
                    });
                    if (current) select.val(current);
                }
            });
        }

        $(function () {
            // Disable scroll-to-change on number inputs
            $(document).on('wheel', 'input[type=number]', function (e) {
                if ($(this).is(':focus')) {
                    $(this).blur();
                }
            });

            loadCustomerFilter();
            loadTable();
            $('#reload_table').on('click', loadTable);
            $('#filter_bulan, #filter_tahun').on('change', function () {
                $('#filter_customer').val('');
                loadCustomerFilter();
                loadTable();
            });
            $('#filter_customer').on('change', loadTable);
            $('#table_search').on('input', refreshVisibleTable);
            $('#mip_table tbody').on('input', 'input[name^="out_hari_"]', function () {
                calculateRow($(this).closest('tr'));
            });
            $('#mip_table tbody').on('blur', 'input[name^="out_hari_"]', function () {
                saveRow($(this).closest('tr'));
            });
            $('#mip_table tbody').on('blur', 'input[name="stock_awal"]', function () {
                const $row = $(this).closest('tr');
                const stockAwal = parseInt($(this).val(), 10) || 0;
                calculateRow($row);
                $.post('{{ route("monitoring.mip.updateStockAwal") }}', {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    customer: $.trim($row.attr('data-customer')),
                    project: $.trim($row.attr('data-project')),
                    part_number: $.trim($row.attr('data-part-number')),
                    stock_awal: stockAwal
                }).done((res) => {
                    saveRow($row, res.warning ? 'Stock minus (' + res.balance + ')' : 'Stock Awal diperbarui');
                }).fail(() => {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Gagal update Stock Awal', showConfirmButton: false, timer: 2000 });
                });
            });
            $('#export_form').on('submit', function () {
                $('#export_bulan').val($('#filter_bulan').val());
                $('#export_tahun').val($('#filter_tahun').val());
            });
            $(window).on('resize', applyFreezeColumns);
        });
    </script>
</x-default-layout>
