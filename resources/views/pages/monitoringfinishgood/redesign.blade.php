<x-default-layout>
    @section('title', 'Monitoring Finish Good')

    <div class="fg-shell my-5">
        <section class="fg-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-7">
                        <span class="hero-chip mb-4"><i class="fas fa-truck-ramp-box me-2"></i>Monitoring stok Finish Good</span>
                        <h1 class="text-white fw-bolder mb-3">Pantau delivery, stock on hand, dan status FG dalam satu tampilan yang lebih rapi</h1>
                        <p class="hero-copy mb-0">Halaman ini difokuskan untuk pekerjaan operasional: update delivery, cek outstanding, lihat balance D/N, dan mengenali part problem lebih cepat.</p>
                    </div>
                    <div class="col-xl-5">
                        <div class="hero-stats">
                            <div class="hero-stat"><span class="label">Baris Aktif</span><span class="value" id="summary_rows">0</span><span class="note">Data sesuai filter saat ini</span></div>
                            <div class="hero-stat"><span class="label">Total IN</span><span class="value" id="summary_in">0</span><span class="note">Masuk dari proses MIP/FG</span></div>
                            <div class="hero-stat"><span class="label">Total OUT</span><span class="value" id="summary_out">0</span><span class="note">Delivery kumulatif berjalan</span></div>
                            <div class="hero-stat"><span class="label">Stock On Hand</span><span class="value" id="summary_soh">0</span><span class="note">Posisi stok akhir terhitung</span></div>
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
                            <div class="panel-subtitle">Pilih periode, customer, lalu cari part Finish Good secara instan.</div>
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
                                    <select id="filter_customer" class="form-select form-select-solid" multiple="multiple" data-placeholder="Semua Customer">
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label for="table_search" class="form-label fw-semibold">Cari Part</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                                        <input id="table_search" type="text" class="form-control form-control-solid border-0" placeholder="Customer, project, part">
                                    </div>
                                    <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                        <input class="form-check-input h-15px w-30px" type="checkbox" id="hide_zero_po">
                                        <label class="form-check-label fw-semibold text-gray-700 fs-7 ms-2" for="hide_zero_po">Sembunyikan PO = 0</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="panel-box h-100">
                            <div class="panel-title">Aksi cepat</div>
                            <div class="panel-subtitle">Segarkan data, ekspor laporan, dan cek periode aktif saat ini.</div>
                            <div class="quick-metrics mt-4">
                                <div class="quick-metric"><span class="label">Hari pada bulan ini</span><span class="value" id="summary_days">0</span></div>
                                <div class="quick-metric"><span class="label">Customer terfilter</span><span class="value" id="summary_customer">Semua</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button id="reload_table" class="btn btn-primary"><i class="fas fa-rotate-right me-2"></i>Refresh Data</button>
                                <button id="export_excel" class="btn btn-light-success"><i class="fas fa-file-export me-2"></i>Export Excel</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="table_loading" class="loading-banner d-none mt-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Memuat data monitoring Finish Good...
                </div>
            </div>
        </section>

        <section class="card table-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
                    <div>
                        <div class="panel-title mb-1">Tabel stok dan delivery FG</div>
                        <div class="panel-subtitle">Ubah `Advance Delivery`, `Stock Awal`, dan `OUT` harian. Nilai lainnya dihitung otomatis.</div>
                    </div>
                    <div class="table-legend">
                        <span class="legend-item"><span class="legend-box in"></span>IN</span>
                        <span class="legend-item"><span class="legend-box out"></span>OUT</span>
                        <span class="legend-item"><span class="legend-box bal"></span>BAL</span>
                    </div>
                </div>
                <div id="table_progress_bar" class="progress-container d-none">
                    <div class="progress-bar-fill"></div>
                </div>
                <div id="empty_state" class="empty-state d-none">
                    <div class="empty-icon"><i class="fas fa-table"></i></div>
                    <div class="fw-bold fs-4 mb-2">Belum ada data untuk filter ini</div>
                    <div class="text-muted">Coba ganti bulan, tahun, customer, atau kata kunci pencarian.</div>
                </div>
                <div class="table-wrap" id="table_wrap">
                    <table id="fg_table" class="table mb-0">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <style>
        .fg-shell { display: grid; gap: 1.5rem; }
        .fg-hero { border: 0; border-radius: 28px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #7c2d12 0%, #c2410c 44%, #0f766e 100%); box-shadow: 0 24px 60px rgba(124, 45, 18, .18); }
        .fg-hero .card-body { padding: 2rem; }
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
        #fg_table { width: max-content; min-width: 100%; margin: 0; border-collapse: separate; border-spacing: 0; }
        #fg_table th, #fg_table td { text-align: center; vertical-align: middle; white-space: nowrap; padding: 8px 10px; font-size: 13px; background: #fff; border-right: 1px solid #dbe3ee; border-bottom: 1px solid #dbe3ee; }
        #fg_table tr th:first-child, #fg_table tr td:first-child { border-left: 1px solid #dbe3ee; }
        #fg_table thead tr:first-child th { border-top: 1px solid #dbe3ee; top: 0; z-index: 5; }
        #fg_table thead th { position: sticky; background: #f8fafc; z-index: 3; font-weight: 700; padding-top: 10px; padding-bottom: 10px; color: #475569; text-transform: uppercase; letter-spacing: .04em; font-size: .76rem; }
        #fg_table thead tr:nth-child(2) th { top: 40px; z-index: 6; background: #f8fafc; padding-top: 12px; padding-bottom: 12px; border-bottom: 2px solid #94a3b8; }
        #fg_table thead tr:first-child th[rowspan="2"] { border-bottom: 2px solid #94a3b8; }
        #fg_table tbody td { padding-top: 12px; padding-bottom: 12px; }
        #fg_table thead th { 
            position: sticky; 
            background: #f8fafc; 
            z-index: 3; 
            font-weight: 800; 
            padding: 8px 2px; 
            color: #0f172a; 
            text-transform: uppercase; 
            letter-spacing: .01em; 
            font-size: 13px; 
            white-space: normal !important; 
            vertical-align: middle; 
            line-height: 1.2; 
            border: 1px solid #94a3b8 !important;
            height: 50px;
        }
        #fg_table thead tr:nth-child(2) th { 
            top: 50px; 
            z-index: 6; 
            background: #f1f5f9; 
            padding: 6px 1px; 
            color: #334155;
            font-size: 12px;
            height: 36px;
            border: 1px solid #94a3b8 !important;
        }
        #fg_table thead tr:first-child th[rowspan="2"] { height: 86px; }
        #fg_table tbody td { 
            padding: 8px 2px; 
            white-space: normal !important; 
            word-break: break-word; 
            font-size: 12.5px; 
            vertical-align: middle;
            color: #0f172a;
            border: 1px solid #cbd5e1 !important;
        }
        #fg_table tbody tr:hover td, #fg_table tbody tr:hover .freeze-col { background: #f0f7ff !important; }
        
        .col-no { min-width: 30px; width: 30px; } 
        .col-customer { min-width: 65px; width: 65px; font-weight: 700; font-size: 11.5px !important; } 
        .col-project { min-width: 65px; width: 65px; font-size: 11.5px !important; } 
        .col-part-number { min-width: 90px; width: 90px; font-weight: 700; font-size: 12px !important; } 
        .col-part-name { min-width: 120px; width: 120px; text-align: left !important; padding-left: 6px !important; font-size: 12px !important; } 
        .col-number { min-width: 55px; width: 55px; font-weight: 700; font-size: 12px !important; } 
        .col-status { min-width: 75px; width: 75px; font-size: 11px !important; } 
        .col-harian { min-width: 65px; width: 65px; }

        .col-harian input { 
            width: 60px; 
            min-width: 60px; 
            max-width: 60px; 
            padding: 0 !important; 
            font-size: 13px; 
            height: 28px; 
            text-align: center !important; 
            border-radius: 4px; 
            border: 1px solid #cbd5e1; 
            background: #fff;
            transition: all 0.2s;
            font-weight: 800;
            appearance: none;
            -moz-appearance: textfield;
        }
        .col-harian input::-webkit-outer-spin-button,
        .col-harian input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .col-harian input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            outline: none;
        }
        .input-hijau { background-color: #ecfdf5 !important; color: #065f46 !important; border-color: #a7f3d0 !important; } 
        .input-merah { background-color: #fff1f2 !important; color: #9f1239 !important; border-color: #fecdd3 !important; } 
        .input-biru { background-color: #eff6ff !important; color: #1e40af !important; border-color: #bfdbfe !important; }
        
        .badge { font-size: 9px; padding: 3px 6px; line-height: 1; border-radius: 5px; font-weight: 800; text-transform: uppercase; color: #fff !important; }

        /* Garis pemisah antar grup produk */
        .row-group-first td {
            border-top: 2px solid #94a3b8 !important;
        }
        
        input[name="advance_delivery"], input[name="stock_awal"] { 
            width: 55px; 
            min-width: 55px; 
            text-align: center; 
            margin: 0 auto; 
            font-size: 12px; 
            padding: 3px; 
            height: 28px; 
            border-radius: 5px;
            font-weight: 800;
            border: 1px solid #cbd5e1;
        }
        .saving-row { background-color: #fff8db !important; }
        .freeze-col, .freeze-group-total, .freeze-group-level { position: sticky; background: #fff !important; z-index: 2; }
        #fg_table thead .freeze-col, #fg_table thead .freeze-group-total, #fg_table thead .freeze-group-level { z-index: 8; }
        #fg_table thead tr:first-child .freeze-col, #fg_table thead tr:first-child .freeze-group-total, #fg_table thead tr:first-child .freeze-group-level { z-index: 9; }
        #fg_table thead tr:nth-child(2) .freeze-col { z-index: 10; }
        .saving-row .freeze-col { background-color: #fff8db !important; }
        .freeze-separator { position: sticky; }
        .freeze-separator::after { 
            content: ""; 
            position: absolute; 
            top: 0; 
            right: -4px; 
            width: 4px; 
            height: 100%; 
            background: linear-gradient(to right, rgba(0,0,0,0.08), transparent); 
            z-index: 30; 
            pointer-events: none; 
        }

        /* Premium Skeleton Loading */
        .skeleton-row td { padding: 12px 8px !important; }
        .skeleton-box { 
            height: 20px; 
            background: #e2e8f0; 
            border-radius: 6px; 
            position: relative; 
            overflow: hidden; 
        }
        .skeleton-box::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .progress-container {
            height: 3px;
            width: 100%;
            background: #f1f5f9;
            overflow: hidden;
            margin-bottom: 10px;
            border-radius: 999px;
        }
        .progress-bar-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, #c2410c, #fb923c);
            border-radius: 999px;
            animation: progress-move 1.2s infinite ease-in-out;
        }
        @keyframes progress-move {
            0% { margin-left: -30%; }
            100% { margin-left: 100%; }
        }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 991.98px) { .fg-hero .card-body { padding: 1.5rem; } .hero-stats, .quick-metrics { grid-template-columns: 1fr; } }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Select2 Premium Styling */
        .select2-container--default .select2-selection--multiple {
            border: 0 !important;
            background-color: #f1f5f9 !important;
            border-radius: 12px !important;
            padding: 4px 8px !important;
            min-height: 42px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            background-color: #fff !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
            border: 1px solid #3b82f6 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #3b82f6 !important;
            border: none !important;
            color: #fff !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            margin-top: 4px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
            margin-right: 5px !important;
            border: none !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background-color: rgba(255,255,255,0.2) !important;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #3b82f6 !important;
        }
    </style>

    <script>
        let currentData = [];
        let visibleData = [];
        let tableRequest = null;
        let customerRequest = null;
        let searchTimer = null;
        let loadSequence = 0;

        const customerOrder = [
            "TAM - SCY",
            "TAM - CCY",
            "TAM - NVDC",
            "TAM - BTM",
            "TAM - THAI",
            "TMAP",
            "TMMIN",
            "TMA",
            "HINO",
            "MAZDA",
            "HYUNDAI"
        ];

        function getCustomerGroup(customer) {
            const cust = $.trim(customer).toUpperCase();
            if (["TAM - SCY", "TAM - CCY", "TAM - NVDC"].includes(cust)) {
                return "GROUP 1";
            }
            if (["TAM - BTM", "TAM - THAI", "TMAP", "TMMIN", "TMA", "HINO"].includes(cust)) {
                return "GROUP 2";
            }
            if (["MAZDA", "HYUNDAI"].includes(cust)) {
                return "GROUP 3";
            }
            return "OTHERS";
        }

        $.ajaxSetup({
            headers: {
                'Accept': 'application/json'
            }
        });

        function getAjaxErrorMessage(xhr, fallback) {
            const response = xhr?.responseJSON || {};
            const parts = [];
            if (response.errors) {
                parts.push(Object.values(response.errors).flat().join(' '));
            }
            if (response.message) parts.push(response.message);
            if (response.error && response.error !== response.message) parts.push(response.error);
            if (response.exception) parts.push(response.exception);
            const detail = parts.filter(Boolean).join(' | ');
            if (detail) return fallback + ': ' + getReadableErrorDetail(detail);
            if (xhr?.status === 0) return fallback + ': koneksi ke server terputus atau request dibatalkan.';
            if (xhr?.status === 401) return fallback + ': session login habis, silakan login ulang.';
            if (xhr?.status === 419) return fallback + ': session halaman kadaluarsa, silakan refresh lalu login ulang.';
            if (xhr?.status === 422) return fallback + ': validasi gagal, periksa kembali input yang diubah.';
            if (xhr?.status) return fallback + ' (HTTP ' + xhr.status + ': ' + (xhr.statusText || 'tanpa detail') + ')';
            return fallback + ': server tidak mengirim detail error.';
        }

        function getResponseErrorMessage(response, fallback) {
            const parts = [];
            if (response?.message) parts.push(response.message);
            if (response?.error && response.error !== response.message) parts.push(response.error);
            if (response?.errors) parts.push(Object.values(response.errors).flat().join(' '));
            return fallback + ': ' + getReadableErrorDetail(parts.filter(Boolean).join(' | ') || 'response server tidak valid.');
        }

        function getReadableErrorDetail(detail) {
            if (!detail) return 'tidak ada detail dari server.';
            if (detail.includes('SQLSTATE[23000]') || detail.includes('Duplicate entry')) {
                return 'Data sudah ada untuk kombinasi bulan, tahun, customer, project, dan part number yang sama. Detail: ' + detail;
            }
            if (detail.includes('SQLSTATE[42S22]') || detail.includes('Unknown column')) {
                return 'Kolom database tidak ditemukan. Detail: ' + detail;
            }
            if (detail.includes('SQLSTATE[22003]') || detail.includes('Out of range')) {
                return 'Nilai angka melebihi batas tipe kolom database. Detail: ' + detail;
            }
            return detail;
        }

        function showErrorToast(message, timer = 5000) {
            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: message, showConfirmButton: false, timer: timer });
        }

        function payloadSignature(data) {
            const normalized = {};
            Object.keys(data).sort().forEach((key) => {
                if (key !== '_token') normalized[key] = String(data[key] ?? '');
            });
            return JSON.stringify(normalized);
        }

        function rememberRowState($row, data = null) {
            const $group = getRowGroup($row);
            $row.data('savedPayload', payloadSignature(data || collectRowData($group)));
            $row.removeData('saving');
        }

        function getParams() {
            return { bulan: $('#filter_bulan').val(), tahun: $('#filter_tahun').val(), customer: $('#filter_customer').val() };
        }

        function escapeHtml(text) {
            return String(text ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function getStatusBadge(status) {
            let color = 'bg-secondary';
            if (status === 'Problem') color = 'bg-danger';
            else if (status === 'Over') color = 'bg-warning';
            else if (status === 'Aman') color = 'bg-success';
            return '<span class="badge ' + color + '">' + escapeHtml(status) + '</span>';
        }

        function buildTableHeader(jumlahHari) {
            let thead = '<tr><th rowspan="2" class="col-no freeze-col freeze-h-0">No</th><th rowspan="2" class="col-customer freeze-col freeze-h-1">Customer</th><th rowspan="2" class="col-project freeze-col freeze-h-2">Project</th><th rowspan="2" class="col-part-number freeze-col freeze-h-3">Part Number</th><th rowspan="2" class="col-part-name freeze-col freeze-h-4">Part Name</th><th rowspan="2" class="col-number freeze-col freeze-h-5">Total PO</th><th rowspan="2" class="col-number freeze-col freeze-h-6">Advance Delivery</th><th rowspan="2" class="col-number freeze-col freeze-h-7">Outstanding</th><th rowspan="2" class="col-number freeze-col freeze-h-8">% Delivery</th><th rowspan="2" class="col-number freeze-col freeze-h-9">Stock Awal</th><th colspan="2" class="freeze-group-total">Total</th><th colspan="3" class="freeze-group-level">Level</th><th rowspan="2" class="col-number freeze-col freeze-h-15">Stock On Hand</th><th rowspan="2" class="col-status freeze-col freeze-h-16">Status Stock</th><th rowspan="2" class="col-status freeze-col freeze-h-17">Status</th>';
            for (let i = 1; i <= jumlahHari; i++) {
                thead += '<th colspan="2" class="col-harian ' + (i === 1 ? 'ps-3' : '') + '">' + i + '</th>';
            }
            thead += '</tr><tr><th class="col-number freeze-col freeze-h-10">IN</th><th class="col-number freeze-col freeze-h-11">OUT</th><th class="col-number freeze-col freeze-h-12">Min</th><th class="col-number freeze-col freeze-h-13">Safety</th><th class="col-number freeze-col freeze-h-14">Max</th>';
            for (let i = 1; i <= jumlahHari; i++) {
                thead += '<th class="col-harian ' + (i === 1 ? 'ps-3' : '') + '">D</th><th class="col-harian">N</th>';
            }
            $('#fg_table thead').html(thead + '</tr>');
        }

        function buildRow(row, index, jumlahHari) {
            // === Baris 1: IN ===
            let html = '<tr class="row-group-first" data-group="' + index + '" data-customer="' + escapeHtml(row.customer) + '" data-project="' + escapeHtml(row.project) + '" data-part-number="' + escapeHtml(row.part_number) + '" data-part-name="' + escapeHtml(row.part_name) + '">' +
                '<td class="freeze-col freeze-b-0" rowspan="3">' + (index + 1) + '</td>' +
                '<td class="freeze-col freeze-b-1" rowspan="3">' + escapeHtml(row.customer) + '</td>' +
                '<td class="freeze-col freeze-b-2" rowspan="3">' + escapeHtml(row.project) + '</td>' +
                '<td class="freeze-col freeze-b-3" rowspan="3">' + escapeHtml(row.part_number) + '</td>' +
                '<td class="freeze-col freeze-b-4" rowspan="3" style="text-align:left;">' + escapeHtml(row.part_name) + '</td>' +
                '<td class="freeze-col freeze-b-5" rowspan="3"><span>' + (row.total_po ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-6" rowspan="3"><input name="advance_delivery" class="form-control form-control-sm text-center" type="number" value="' + (row.advance_delivery ?? 0) + '"></td>' +
                '<td class="freeze-col freeze-b-7" rowspan="3"><span>' + (row.outstanding ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-8" rowspan="3"><span>' + (row.percentage ?? 0) + '%</span></td>' +
                '<td class="freeze-col freeze-b-9" rowspan="3"><input name="stock_awal" class="form-control form-control-sm text-center input-biru" type="number" value="' + (row.stock_awal ?? 0) + '"></td>' +
                '<td class="freeze-col freeze-b-10" rowspan="3"><span>' + (row.total_in ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-11" rowspan="3"><span>' + (row.total_out ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-12" rowspan="3"><span>' + (row.level_min ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-13" rowspan="3"><span>' + (row.level_safety ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-14" rowspan="3"><span>' + (row.level_max ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-15" rowspan="3"><span>' + (row.stock_on_hand ?? 0) + '</span></td>' +
                '<td class="freeze-col freeze-b-16" rowspan="3">' + getStatusBadge(row.status_stock ?? 'Aman') + '</td>' +
                '<td class="freeze-col freeze-b-17"><span class="badge bg-success">IN</span></td>';
            for (let i = 1; i <= jumlahHari; i++) {
                html += '<td class="col-harian' + (i === 1 ? ' ps-3' : '') + '"><input name="in_hari_' + i + '_d" class="form-control form-control-sm text-center input-hijau" value="' + (row['in_hari_' + i + '_d'] ?? 0) + '" readonly></td><td class="col-harian"><input name="in_hari_' + i + '_n" class="form-control form-control-sm text-center input-hijau" value="' + (row['in_hari_' + i + '_n'] ?? 0) + '" readonly></td>';
            }
            html += '</tr>';

            // === Baris 2: OUT ===
            html += '<tr data-group="' + index + '">' +
                '<td class="freeze-col freeze-b-17"><span class="badge bg-danger">OUT</span></td>';
            for (let i = 1; i <= jumlahHari; i++) {
                html += '<td class="col-harian' + (i === 1 ? ' ps-3' : '') + '"><input name="out_hari_' + i + '_d" class="form-control form-control-sm text-center input-merah" type="number" value="' + (row['out_hari_' + i + '_d'] ?? 0) + '"></td><td class="col-harian"><input name="out_hari_' + i + '_n" class="form-control form-control-sm text-center input-merah" type="number" value="' + (row['out_hari_' + i + '_n'] ?? 0) + '"></td>';
            }
            html += '</tr>';

            // === Baris 3: BAL ===
            html += '<tr data-group="' + index + '">' +
                '<td class="freeze-col freeze-b-17"><span class="badge bg-primary">BAL</span></td>';
            for (let i = 1; i <= jumlahHari; i++) {
                html += '<td class="col-harian' + (i === 1 ? ' ps-3' : '') + '"><input name="balance_hari_' + i + '_d" class="form-control form-control-sm text-center input-biru" value="' + (row['balance_hari_' + i + '_d'] ?? 0) + '" readonly></td><td class="col-harian"><input name="balance_hari_' + i + '_n" class="form-control form-control-sm text-center input-biru" value="' + (row['balance_hari_' + i + '_n'] ?? 0) + '" readonly></td>';
            }
            html += '</tr>';

            return html;
        }

        function updateSummary(data) {
            const totalIn = data.reduce((sum, row) => sum + (parseInt(row.total_in, 10) || 0), 0);
            const totalOut = data.reduce((sum, row) => sum + (parseInt(row.total_out, 10) || 0), 0);
            const totalSoh = data.reduce((sum, row) => sum + (parseInt(row.stock_on_hand, 10) || 0), 0);
            const bulan = parseInt($('#filter_bulan').val(), 10);
            const tahun = parseInt($('#filter_tahun').val(), 10);
            $('#summary_rows').text(data.length.toLocaleString('id-ID'));
            $('#summary_in').text(totalIn.toLocaleString('id-ID'));
            $('#summary_out').text(totalOut.toLocaleString('id-ID'));
            $('#summary_soh').text(totalSoh.toLocaleString('id-ID'));
            $('#summary_days').text(new Date(tahun, bulan, 0).getDate().toLocaleString('id-ID'));
            const cust = $('#filter_customer').val();
            $('#summary_customer').text(cust && cust.length > 0 ? cust.join(', ') : 'Semua');
        }

        function filterVisibleData() {
            const keyword = ($('#table_search').val() || '').trim().toLowerCase();
            const hideZeroPo = $('#hide_zero_po').is(':checked');
            let filtered = currentData;
            if (hideZeroPo) {
                filtered = filtered.filter((row) => (parseInt(row.total_po, 10) || 0) > 0);
            }
            if (keyword) {
                filtered = filtered.filter((row) => [row.customer, row.project, row.part_number, row.part_name].join(' ').toLowerCase().includes(keyword));
            }
            visibleData = filtered;
            return visibleData;
        }

        function renderTable(data) {
            const bulan = parseInt($('#filter_bulan').val(), 10);
            const tahun = parseInt($('#filter_tahun').val(), 10);
            const jumlahHari = new Date(tahun, bulan, 0).getDate();
            buildTableHeader(jumlahHari);
            
            let tbody = '';
            let currentGroup = null;
            let displayIndex = 1;
            
            data.forEach((row) => {
                const group = $.trim(row.customer).toUpperCase();
                if (group !== currentGroup) {
                    currentGroup = group;
                    tbody += `<tr class="group-header-row"><td colspan="100" class="text-start ps-4 py-3 fw-bold text-dark border-dark" style="background: #f1f5f9; border-top: 3px solid #cbd5e1 !important; border-bottom: 1px solid #cbd5e1 !important;"><i class="fas fa-building me-2 text-primary"></i> CUSTOMER: ${group}</td></tr>`;
                }
                tbody += buildRow(row, displayIndex - 1, jumlahHari);
                displayIndex++;
            });
            
            $('#fg_table tbody').html(tbody).addClass('fade-in');
            setTimeout(() => $('#fg_table tbody').removeClass('fade-in'), 500);
            $('#empty_state').toggleClass('d-none', data.length > 0);
            $('#table_wrap').toggleClass('d-none', data.length === 0);
            updateSummary(data);
            
            // rememberRowState per group (baris pertama tiap grup)
            $('#fg_table tbody tr.row-group-first').each(function () {
                rememberRowState($(this));
            });
            setTimeout(applyFreezeColumns, 50);
        }

        function refreshVisibleTable() {
            renderTable(filterVisibleData());
        }

        function debounceRefreshVisibleTable() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(refreshVisibleTable, 180);
        }

        function showSkeleton() {
            const jumlahHari = new Date(parseInt($('#filter_tahun').val(), 10), parseInt($('#filter_bulan').val(), 10), 0).getDate();
            buildTableHeader(jumlahHari);
            let skeletonRows = '';
            for (let i = 0; i < 5; i++) {
                skeletonRows += '<tr class="skeleton-row">';
                for (let j = 0; j < (18 + (jumlahHari * 2)); j++) {
                    skeletonRows += '<td><div class="skeleton-box"></div></td>';
                }
                skeletonRows += '</tr>';
            }
            $('#fg_table tbody').html(skeletonRows);
            $('#table_progress_bar').removeClass('d-none');
        }

        function hideSkeleton() {
            $('#table_progress_bar').addClass('d-none');
        }

        function applyFreezeColumns() {
            const $firstBodyRow = $('#fg_table tbody tr:first');
            if (!$firstBodyRow.length) return;
            const widths = [];
            let left = 0;
            $('.freeze-separator').removeClass('freeze-separator');
            for (let i = 0; i < 18; i++) {
                const width = Math.ceil($firstBodyRow.find('.freeze-b-' + i).first().outerWidth()) || 0;
                widths[i] = width;
                $('.freeze-b-' + i + ', .freeze-h-' + i).css('left', left + 'px');
                left += width;
            }
            $('.freeze-group-total').css({ left: $('.freeze-h-10').css('left'), minWidth: (widths[10] + widths[11]) + 'px', width: (widths[10] + widths[11]) + 'px' });
            $('.freeze-group-level').css({ left: $('.freeze-h-12').css('left'), minWidth: (widths[12] + widths[13] + widths[14]) + 'px', width: (widths[12] + widths[13] + widths[14]) + 'px' });
            $('.freeze-b-17, .freeze-h-17, .freeze-group-level').addClass('freeze-separator');
        }

        // Helper: dari sebuah <tr>, ambil seluruh row group-nya (3 baris)
        function getRowGroup($el) {
            const groupIdx = $el.closest('tr').data('group');
            return $('#fg_table tbody tr[data-group="' + groupIdx + '"]');
        }

        function calculateRow($rowGroup) {
            const jumlahHari = new Date(parseInt($('#filter_tahun').val(), 10), parseInt($('#filter_bulan').val(), 10), 0).getDate();
            let stockAwal = parseInt($rowGroup.find('input[name="stock_awal"]').val(), 10) || 0;
            let advanceDelivery = parseInt($rowGroup.find('input[name="advance_delivery"]').val(), 10) || 0;
            let totalPO = parseInt($rowGroup.first().find('td:eq(5) span').text(), 10) || 0;
            let levelMin = parseInt($rowGroup.first().find('td:eq(12) span').text(), 10) || 0;
            let levelMax = parseInt($rowGroup.first().find('td:eq(14) span').text(), 10) || 0;
            let balance = stockAwal;
            let totalIn = 0, totalOut = 0;
            for (let i = 1; i <= jumlahHari; i++) {
                const inD = parseInt($rowGroup.find('input[name="in_hari_' + i + '_d"]').val(), 10) || 0;
                const outD = parseInt($rowGroup.find('input[name="out_hari_' + i + '_d"]').val(), 10) || 0;
                const inN = parseInt($rowGroup.find('input[name="in_hari_' + i + '_n"]').val(), 10) || 0;
                const outN = parseInt($rowGroup.find('input[name="out_hari_' + i + '_n"]').val(), 10) || 0;
                const balanceD = balance + inD - outD;
                const balanceN = balanceD + inN - outN;
                $rowGroup.find('input[name="balance_hari_' + i + '_d"]').val(balanceD);
                $rowGroup.find('input[name="balance_hari_' + i + '_n"]').val(balanceN);
                balance = balanceN;
                totalIn += inD + inN;
                totalOut += outD + outN;
            }
            const $firstRow = $rowGroup.first();
            $firstRow.find('td:eq(10) span').text(totalIn);
            $firstRow.find('td:eq(11) span').text(totalOut);
            $firstRow.find('td:eq(15) span').text(balance);
            let statusText = 'Aman';
            if (balance <= levelMin) statusText = 'Problem';
            else if (balance > levelMax) statusText = 'Over';
            $firstRow.find('td:eq(16)').html(getStatusBadge(statusText));
            const outstanding = Math.max(0, totalPO - advanceDelivery - totalOut);
            const percentage = totalPO > 0 ? ((totalOut / totalPO) * 100).toFixed(2) : '0.00';
            $firstRow.find('td:eq(7) span').text(outstanding);
            $firstRow.find('td:eq(8) span').text(percentage + '%');
            return { stockAwal, totalIn, totalOut, stockOnHand: balance, statusText, outstanding, percentage, levelMin, levelMax, levelSafety: parseInt($firstRow.find('td:eq(13) span').text(), 10) || 0, advanceDelivery };
        }

        function collectRowData($rowGroup) {
            const jumlahHari = new Date(parseInt($('#filter_tahun').val(), 10), parseInt($('#filter_bulan').val(), 10), 0).getDate();
            const calc = calculateRow($rowGroup);
            const $firstRow = $rowGroup.first();
            const data = {
                _token: '{{ csrf_token() }}',
                bulan: $('#filter_bulan').val(),
                tahun: $('#filter_tahun').val(),
                customer: $.trim($firstRow.attr('data-customer')),
                project: $.trim($firstRow.attr('data-project')),
                part_number: $.trim($firstRow.attr('data-part-number')),
                part_name: $.trim($firstRow.attr('data-part-name')),
                stock_awal: calc.stockAwal,
                level_min: calc.levelMin,
                level_safety: calc.levelSafety,
                level_max: calc.levelMax,
                advance_delivery: calc.advanceDelivery
            };
            for (let i = 1; i <= jumlahHari; i++) {
                data['in_hari_' + i + '_d'] = $rowGroup.find('input[name="in_hari_' + i + '_d"]').val() || 0;
                data['out_hari_' + i + '_d'] = $rowGroup.find('input[name="out_hari_' + i + '_d"]').val() || 0;
                data['balance_hari_' + i + '_d'] = $rowGroup.find('input[name="balance_hari_' + i + '_d"]').val() || 0;
                data['in_hari_' + i + '_n'] = $rowGroup.find('input[name="in_hari_' + i + '_n"]').val() || 0;
                data['out_hari_' + i + '_n'] = $rowGroup.find('input[name="out_hari_' + i + '_n"]').val() || 0;
                data['balance_hari_' + i + '_n'] = $rowGroup.find('input[name="balance_hari_' + i + '_n"]').val() || 0;
            }
            return data;
        }

        function saveRow($rowGroup, successTitle = 'Data berhasil disimpan') {
            const $firstRow = $rowGroup.first();
            if ($firstRow.data('saving')) return;
            const payload = collectRowData($rowGroup);
            const signature = payloadSignature(payload);
            if ($firstRow.data('savedPayload') === signature) return;

            $firstRow.data('saving', true);
            $rowGroup.addClass('saving-row');
            $.ajax({
                url: '{{ route("monitoring.finishgood.save") }}',
                type: 'POST',
                data: payload,
                dataType: 'json'
            })
                .done((res) => {
                    if (!res || res.status !== 'success') {
                        showErrorToast(getResponseErrorMessage(res, 'Data belum tersimpan di server'));
                        return;
                    }
                    $firstRow.data('savedPayload', signature);
                    const title = res.warning ? successTitle + ' - Stock minus (' + res.balance + ')' : successTitle;
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: title, showConfirmButton: false, timer: 1800 });
                })
                .fail((xhr) => {
                    showErrorToast(getAjaxErrorMessage(xhr, 'Gagal menyimpan data'));
                })
                .always(() => {
                    $rowGroup.removeClass('saving-row');
                    $firstRow.removeData('saving');
                });
        }

        function loadTable() {
            const params = getParams();
            const sequence = ++loadSequence;
            $('#table_loading').removeClass('d-none');
            $('#table_progress_bar').removeClass('d-none');

            if (!currentData.length) {
                showSkeleton();
            }

            if (tableRequest) {
                tableRequest.abort();
            }

            tableRequest = $.ajax({
                url: '{{ route("monitoring.finishgood.data") }}',
                type: 'POST',
                dataType: 'json',
                timeout: 45000,
                data: { _token: '{{ csrf_token() }}', bulan: params.bulan, tahun: params.tahun, customer: params.customer },
                success: function (res) {
                    if (sequence !== loadSequence) return;
                    
                    const sortedData = (res.data || []).sort((a, b) => {
                        const custA = $.trim(a.customer).toUpperCase();
                        const custB = $.trim(b.customer).toUpperCase();
                        
                        let idxA = customerOrder.indexOf(custA);
                        let idxB = customerOrder.indexOf(custB);
                        
                        if (idxA === -1) idxA = 999;
                        if (idxB === -1) idxB = 999;
                        
                        if (idxA !== idxB) {
                            return idxA - idxB;
                        }
                        
                        const projA = $.trim(a.project).toUpperCase();
                        const projB = $.trim(b.project).toUpperCase();
                        if (projA !== projB) return projA.localeCompare(projB);
                        
                        return $.trim(a.part_number).localeCompare($.trim(b.part_number));
                    });
                    
                    currentData = sortedData;
                    refreshVisibleTable();
                },
                error: function (xhr, status) {
                    if (status === 'abort' || sequence !== loadSequence) return;
                    showErrorToast(getAjaxErrorMessage(xhr, 'Gagal memuat data Monitoring Finish Good'));
                },
                complete: function () {
                    if (sequence !== loadSequence) return;
                    $('#table_loading').addClass('d-none');
                    hideSkeleton();
                    tableRequest = null;
                }
            });
        }

        function loadCustomerFilter() {
            if (customerRequest) {
                customerRequest.abort();
            }

            customerRequest = $.ajax({
                url: '{{ route("monitoring.finishgood.data") }}',
                type: 'POST',
                dataType: 'json',
                timeout: 30000,
                data: { _token: '{{ csrf_token() }}', bulan: $('#filter_bulan').val(), tahun: $('#filter_tahun').val(), only_customer: true },
                success: function (res) {
                    const select = $('#filter_customer');
                    const currentValues = select.val() || [];
                    select.empty();
                    (res.data || []).forEach((row) => {
                        const isSelected = currentValues.includes(row.customer) ? 'selected' : '';
                        select.append('<option value="' + escapeHtml(row.customer) + '" ' + isSelected + '>' + escapeHtml(row.customer) + '</option>');
                    });
                    select.trigger('change.select2');
                },
                complete: function () {
                    customerRequest = null;
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

            $('#filter_customer').select2({
                allowClear: true,
                width: '100%',
                placeholder: 'Semua Customer'
            });

            loadCustomerFilter();
            loadTable();
            $('#reload_table').on('click', loadTable);
            $('#filter_bulan, #filter_tahun').on('change', function () {
                $('#filter_customer').val(null).trigger('change.select2');
                loadCustomerFilter();
                loadTable();
            });
            $('#filter_customer').on('change', function() {
                loadTable();
            });
            $('#table_search').on('input', debounceRefreshVisibleTable);
            $('#hide_zero_po').on('change', refreshVisibleTable);
            $('#fg_table tbody').on('blur', 'input[name^="out_hari_"]', function () {
                saveRow(getRowGroup($(this)));
            });
            $('#fg_table tbody').on('blur', 'input[name="advance_delivery"]', function () {
                saveRow(getRowGroup($(this)), 'Advance Delivery disimpan');
            });
            $('#fg_table tbody').on('blur', 'input[name="stock_awal"]', function () {
                const $group = getRowGroup($(this));
                calculateRow($group);
                saveRow($group, 'Stock Awal diperbarui');
            });
            $('#export_excel').on('click', function () {
                const url = new URL('{{ route("monitoring.finishgood.export") }}', window.location.origin);
                url.searchParams.set('bulan', $('#filter_bulan').val());
                url.searchParams.set('tahun', $('#filter_tahun').val());
                if ($('#filter_customer').val()) url.searchParams.set('customer', $('#filter_customer').val());
                window.location.href = url.toString();
            });
            $(window).on('resize', applyFreezeColumns);
        });
    </script>
</x-default-layout>
