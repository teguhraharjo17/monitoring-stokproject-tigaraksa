<x-default-layout>
    @section('title', 'Rekap Data Bulanan')

    <div class="rekap-shell my-5">
        <!-- Hero Header -->
        <section class="rekap-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-7">
                        <span class="hero-chip mb-3"><i class="fas fa-layer-group me-2"></i>Rekap Data Stok Bulanan</span>
                        <h1 class="text-white fw-bolder mb-2">Kontrol Rekap Stok & Kebutuhan Bulanan</h1>
                        <p class="hero-copy mb-0">Kelola komponen stok awal, input kebutuhan PO bulan berjalan, dan pantau selisih stok secara real-time dalam satu tampilan terpadu.</p>
                    </div>
                    <div class="col-xl-5">
                        <div class="hero-stats">
                            <div class="hero-stat"><span class="label">Total Baris</span><span class="value" id="summary_rows">0</span><span class="note">Item dalam filter</span></div>
                            <div class="hero-stat"><span class="label">Total Stok</span><span class="value" id="summary_stock">0</span><span class="note">MIP + FG + WIP</span></div>
                            <div class="hero-stat"><span class="label">Total Kebutuhan</span><span class="value" id="summary_qty">0</span><span class="note">O/S Lalu + PO Ini</span></div>
                            <div class="hero-stat"><span class="label">Total Selisih</span><span class="value" id="summary_gap">0</span><span class="note">Gap stok vs kebutuhan</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filter & Control Panel -->
        <section class="card control-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    <div class="col-xl-8">
                        <div class="panel-box">
                            <div class="panel-title"><i class="fas fa-filter text-primary me-2"></i>Filter Periode & Customer</div>
                            <div class="panel-subtitle">Pilih bulan, tahun, dan customer untuk memusatkan data kerja.</div>
                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-4">
                                    <label for="filter_bulan" class="form-label fw-bold fs-7">Bulan</label>
                                    <select id="filter_bulan" class="form-select form-select-solid">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_tahun" class="form-label fw-bold fs-7">Tahun</label>
                                    <select id="filter_tahun" class="form-select form-select-solid">
                                        @php($tahunSekarang = now()->year)
                                        @for ($y = $tahunSekarang; $y >= $tahunSekarang - 5; $y--)
                                            <option value="{{ $y }}" {{ $y == $tahunSekarang ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_customer" class="form-label fw-bold fs-7">Customer</label>
                                    <select id="filter_customer" class="form-select form-select-solid">
                                        <option value="">Semua Customer</option>
                                        @foreach ($masterItems->pluck('customer')->unique()->sort() as $customer)
                                            <option value="{{ $customer }}">{{ $customer }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="panel-box h-100">
                            <div class="panel-title"><i class="fas fa-bolt text-warning me-2"></i>Aksi Cepat</div>
                            <div class="panel-subtitle">Tambah baris manual atau segarkan data rekap.</div>
                            <div class="quick-metrics mt-3">
                                <div class="quick-metric"><span class="label">Customer Aktif</span><span class="value" id="summary_customer">Semua</span></div>
                                <div class="quick-metric"><span class="label">Periode</span><span class="value" id="summary_period">-</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button id="tambah_row" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Baris</button>
                                <button id="refresh_table" class="btn btn-light-primary"><i class="fas fa-rotate-right me-2"></i>Refresh</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Data Grid Section -->
        <section class="card table-panel">
            <div class="card-body p-4 p-lg-5">
                <!-- Toolbar Header -->
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
                    <div>
                        <div class="panel-title mb-1">Tabel Rekapitulasi Stok Bulanan</div>
                        <div class="panel-subtitle">Edit angka pada kolom input. Total stok, total kebutuhan, dan selisih akan otomatis dihitung dan disimpan.</div>
                    </div>
                    <div class="table-legend">
                        <span class="legend-item"><span class="legend-box stok"></span>Komponen Stok</span>
                        <span class="legend-item"><span class="legend-box kebutuhan"></span>Kebutuhan PO</span>
                        <span class="legend-item"><span class="legend-box hasil"></span>Hasil Hitung</span>
                    </div>
                </div>

                <!-- Search and Live Counter Bar -->
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
                    <div class="position-relative" style="width: 320px; max-width: 100%;">
                        <i class="fas fa-search position-absolute top-50 translate-middle-y ms-3 text-gray-400"></i>
                        <input type="text" id="table_search" class="form-control form-control-solid ps-10" placeholder="Cari part number, customer, model...">
                    </div>
                    <div>
                        <span class="badge badge-light-primary fs-7 fw-bold px-3 py-2" id="badge_counter">
                            <i class="fas fa-database me-1 text-primary"></i> <span id="counter_visible">0</span> baris ditampilkan
                        </span>
                    </div>
                </div>

                <!-- Loading Progress -->
                <div id="table_progress_bar" class="progress-container d-none">
                    <div class="progress-bar-fill"></div>
                </div>

                <!-- High-Performance Data Grid Container (100% Locked Scroll) -->
                <div class="table-grid-wrap">
                    <table id="rekap_table" class="table-grid align-middle mb-0">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-part">
                            <col class="col-cust">
                            <col class="col-proj">
                            <col class="col-model">
                            <col class="col-stk-mip">
                            <col class="col-stk-fg">
                            <col class="col-wip">
                            <col class="col-tot-stk">
                            <col class="col-os">
                            <col class="col-po">
                            <col class="col-tot-qty">
                            <col class="col-selisih">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="col-no text-center">#</th>
                                <th class="col-part text-start ps-3"><i class="fas fa-barcode text-muted me-1"></i> Part Number</th>
                                <th class="col-cust text-center"><i class="fas fa-building text-muted me-1"></i> Customer</th>
                                <th class="col-proj text-center"><i class="fas fa-hashtag text-muted me-1"></i> Proj</th>
                                <th class="col-model text-start ps-3"><i class="fas fa-tag text-muted me-1"></i> Model</th>
                                <th class="col-stk-mip th-stock text-end pe-3"><span class="th-badge th-badge-stk">MIP</span> Stock Awal</th>
                                <th class="col-stk-fg th-stock text-end pe-3"><span class="th-badge th-badge-stk">FG</span> Stock Awal</th>
                                <th class="col-wip th-stock text-end pe-3"><span class="th-badge th-badge-stk">WIP</span> + SPK SA</th>
                                <th class="col-tot-stk th-tot-stk text-end pe-3"><span class="th-badge th-badge-tot-stk">TOTAL</span> Stok</th>
                                <th class="col-os th-demand text-end pe-3"><span class="th-badge th-badge-demand">O/S</span> Lalu</th>
                                <th class="col-po th-demand text-end pe-3"><span class="th-badge th-badge-demand">PO</span> Bulan Ini</th>
                                <th class="col-tot-qty th-tot-demand text-end pe-3"><span class="th-badge th-badge-tot-demand">TOTAL</span> Kebutuhan</th>
                                <th class="col-selisih th-gap text-end pe-3"><span class="th-badge th-badge-gap">SELISIH</span> Stok</th>
                                <th class="col-action text-center"><i class="fas fa-sliders text-muted"></i></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Footer Summary Info -->
                <div class="d-flex justify-content-between align-items-center mt-3 text-muted fs-7">
                    <div id="table_info_text">Menampilkan 0 baris data rekap</div>
                    <div class="text-end"><i class="fas fa-arrows-left-right me-1"></i> Geser horizontal untuk melihat seluruh kolom</div>
                </div>
            </div>
        </section>
    </div>

    <style>
        .rekap-shell { display: grid; gap: 1.5rem; }
        .rekap-hero { border: 0; border-radius: 24px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #0f4c81 0%, #1d4ed8 44%, #0f766e 100%); box-shadow: 0 20px 50px rgba(15, 76, 129, .15); }
        .rekap-hero .card-body { padding: 2rem; }
        .hero-chip { display: inline-flex; align-items: center; padding: .55rem .95rem; border-radius: 999px; background: rgba(255,255,255,.15); color: #fff; font-size: .88rem; font-weight: 600; }
        .hero-copy { color: rgba(255,255,255,.85); font-size: .95rem; max-width: 680px; }
        .hero-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .hero-stat { padding: 1rem 1.1rem; border-radius: 18px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); color: #fff; }
        .hero-stat .label, .quick-metric .label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.75); }
        .hero-stat .value, .quick-metric .value { display: block; margin-top: .3rem; font-weight: 800; font-size: 1.45rem; line-height: 1.1; font-family: 'Fira Code', monospace; }
        .hero-stat .note { display: block; margin-top: .35rem; color: rgba(255,255,255,.75); font-size: .8rem; }
        .control-panel, .table-panel { border: 0; border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .05); border: 1px solid #e2e8f0; }
        .panel-box { height: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.1rem; }
        .panel-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .panel-subtitle { color: #64748b; font-size: .88rem; }
        .quick-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
        .quick-metric { padding: .75rem .9rem; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; }
        .quick-metric .label { color: #64748b; }
        .quick-metric .value { color: #0f172a; font-size: 1.1rem; }
        .table-legend { display: flex; flex-wrap: wrap; gap: .65rem; }
        .legend-item { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .75rem; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; font-size: .82rem; font-weight: 600; }
        .legend-box { width: 10px; height: 10px; border-radius: 3px; }
        .legend-box.stok { background: #10b981; } 
        .legend-box.kebutuhan { background: #f43f5e; } 
        .legend-box.hasil { background: #06b6d4; }

        /* Unified Table Grid Container (UI/UX Pro Max Architecture) */
        .table-grid-wrap { 
            max-height: 68vh;
            overflow: auto !important;
            border-radius: 12px; 
            border: 1px solid #cbd5e1; 
            background: #fff; 
            position: relative;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
            box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }
        .table-grid-wrap::-webkit-scrollbar { width: 8px; height: 8px; }
        .table-grid-wrap::-webkit-scrollbar-track { background: #f8fafc; }
        .table-grid-wrap::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .table-grid-wrap::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

        .table-grid { 
            width: 100% !important;
            min-width: 2015px !important;
            table-layout: fixed !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }

        /* Explicit Column Width Tokens (Spacious & Responsive on Zoom) */
        .col-no { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }
        .col-part { width: 330px !important; min-width: 330px !important; max-width: 330px !important; }
        .col-cust { width: 115px !important; min-width: 115px !important; max-width: 115px !important; }
        .col-proj { width: 110px !important; min-width: 110px !important; max-width: 110px !important; }
        .col-model { width: 240px !important; min-width: 240px !important; max-width: 240px !important; }
        .col-stk-mip { width: 135px !important; min-width: 135px !important; max-width: 135px !important; }
        .col-stk-fg { width: 135px !important; min-width: 135px !important; max-width: 135px !important; }
        .col-wip { width: 135px !important; min-width: 135px !important; max-width: 135px !important; }
        .col-tot-stk { width: 135px !important; min-width: 135px !important; max-width: 135px !important; }
        .col-os { width: 130px !important; min-width: 130px !important; max-width: 130px !important; }
        .col-po { width: 130px !important; min-width: 130px !important; max-width: 130px !important; }
        .col-tot-qty { width: 145px !important; min-width: 145px !important; max-width: 145px !important; }
        .col-selisih { width: 135px !important; min-width: 135px !important; max-width: 135px !important; }
        .col-action { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }

        .table-grid th, .table-grid td { 
            border-right: 1px solid #e2e8f0 !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-left: 0 !important;
            border-top: 0 !important;
            padding: 6px 8px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            background-color: #fff;
        }

        /* Sticky Header */
        .table-grid thead th { 
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #f8fafc !important; 
            color: #0f172a !important; 
            font-size: 11.5px !important; 
            text-transform: uppercase !important; 
            letter-spacing: .04em !important; 
            font-weight: 800 !important; 
            white-space: nowrap !important; 
            overflow: visible !important;
            border-bottom: 2px solid #cbd5e1 !important;
            border-right: 1px solid #e2e8f0 !important;
            padding: 10px 8px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            box-sizing: border-box !important;
        }

        /* Header Color Themes */
        .table-grid thead th.th-stock { background-color: #f0fdf4 !important; color: #166534 !important; border-top: 3px solid #10b981 !important; }
        .table-grid thead th.th-tot-stk { background-color: #ecfeff !important; color: #0e7490 !important; border-top: 3px solid #06b6d4 !important; }
        .table-grid thead th.th-demand { background-color: #fff1f2 !important; color: #9f1239 !important; border-top: 3px solid #f43f5e !important; }
        .table-grid thead th.th-tot-demand { background-color: #fffbeb !important; color: #92400e !important; border-top: 3px solid #f59e0b !important; }
        .table-grid thead th.th-gap { background-color: #f5f3ff !important; color: #5b21b6 !important; border-top: 3px solid #8b5cf6 !important; }

        .th-badge { font-size: 9.5px; padding: 2px 5px; border-radius: 4px; font-weight: 800; margin-right: 3px; display: inline-block; vertical-align: middle; }
        .th-badge-stk { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .th-badge-tot-stk { background: #cffafe; color: #0891b2; border: 1px solid #67e8f9; }
        .th-badge-demand { background: #ffe4e6; color: #be123c; border: 1px solid #fda4af; }
        .th-badge-tot-demand { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
        .th-badge-gap { background: #ede9fe; color: #6d28d9; border: 1px solid #c4b5fd; }

        .table-grid tbody tr:hover td { background-color: #f8fafc !important; }

        /* Typography & Badges in Cells */
        .badge-cust { display: inline-block; background: #f1f5f9; color: #1e293b; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; letter-spacing: 0.02em; border: 1px solid #e2e8f0; }
        .badge-proj { display: inline-block; color: #475569; font-family: 'Fira Code', monospace; font-size: 11px; font-weight: 600; background: #f8fafc; padding: 3px 6px; border-radius: 4px; border: 1px solid #e2e8f0; }
        .model-truncate { font-weight: 600; color: #1e293b; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 215px; }

        /* Sleek Cell Input for Numbers */
        .cell-num-input {
            width: 100% !important;
            height: 32px !important;
            background: #fff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            font-family: 'Fira Code', Consolas, monospace !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            text-align: right !important;
            padding: 0 8px !important;
            transition: all 0.15s ease !important;
            outline: none !important;
            box-shadow: none !important;
            appearance: textfield;
            -moz-appearance: textfield;
        }
        .cell-num-input::-webkit-outer-spin-button,
        .cell-num-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .cell-num-input:hover {
            border-color: #94a3b8 !important;
            background: #f8fafc !important;
        }
        .cell-num-input:focus {
            border-color: #2563eb !important;
            background: #fff !important;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important;
        }
        .cell-num-input.input-demand:focus {
            border-color: #f43f5e !important;
            box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.2) !important;
        }

        /* Metric Pill Display for Calculated Values (No Input Borders) */
        .metric-pill {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            height: 32px;
            padding: 0 8px;
            border-radius: 6px;
            font-family: 'Fira Code', Consolas, monospace;
            font-size: 12.5px;
            font-weight: 800;
            text-align: right;
        }
        .metric-stock { background: #ecfeff; color: #0891b2; border: 1px solid #a5f3fc; }
        .metric-qty { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .metric-selisih { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }

        /* Action Badges & Buttons */
        .badge-synced { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #ecfdf5; border: 1px solid #a7f3d0; font-size: 12px; }
        .btn-action-icon { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 6px; border: 0; font-size: 11px; cursor: pointer; transition: all 0.15s; }
        .btn-action-icon.save { background: #10b981; color: #fff; }
        .btn-action-icon.save:hover { background: #059669; }
        .btn-action-icon.delete { background: #ef4444; color: #fff; }
        .btn-action-icon.delete:hover { background: #dc2626; }

        /* Select2 In-Table Styling */
        .table-grid .select2-container { width: 100% !important; max-width: 100% !important; display: block !important; }
        .table-grid .select2-selection--single {
            min-height: 32px !important;
            height: 32px !important;
            border-radius: 6px !important;
            border: 1px solid #cbd5e1 !important;
            display: flex !important;
            align-items: center !important;
            font-size: 12.5px !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
        }
        .table-grid .select2-selection__rendered {
            padding-left: .5rem !important;
            padding-right: 1.5rem !important;
            line-height: 30px !important;
            font-weight: 700 !important;
            text-align: left !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block !important;
            width: 100% !important;
        }
        .table-grid .select2-selection__arrow { height: 30px !important; right: 4px !important; }

        /* Shimmer Progress */
        .progress-container { height: 3px; width: 100%; background: #f1f5f9; overflow: hidden; margin-bottom: 10px; border-radius: 999px; }
        .progress-bar-fill { height: 100%; width: 30%; background: linear-gradient(90deg, #1d4ed8, #60a5fa); border-radius: 999px; animation: progress-move 1.2s infinite ease-in-out; }
        @keyframes progress-move { 0% { margin-left: -30%; } 100% { margin-left: 100%; } }

        .skeleton-box { height: 22px; background: #e2e8f0; border-radius: 6px; position: relative; overflow: hidden; }
        .skeleton-box::after { content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; transform: translateX(-100%); background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0) 100%); animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }

        /* Badge Nonaktif */
        .badge-nonaktif {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .part-number-readonly {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .part-number-readonly .part-text {
            font-weight: 800;
            font-size: 13px;
            color: #64748b;
        }
        tr.row-nonaktif td {
            background-color: #fef2f2 !important;
            opacity: 0.85;
        }
        tr.row-nonaktif:hover td {
            background-color: #fee2e2 !important;
        }

        @media (max-width: 991.98px) { .rekap-hero .card-body { padding: 1.5rem; } .hero-stats, .quick-metrics { grid-template-columns: 1fr; } }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            const masterItems = @json($masterItems);
            let allRows = [];
            let activeRows = [];
            let searchTimer = null;
            let saveTimers = {};

            // Disable scroll-to-change on number inputs
            $(document).on('wheel', 'input[type=number]', function (e) {
                if ($(this).is(':focus')) {
                    $(this).blur();
                }
            });

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                return String(text).replace(/[&<>"']/g, function (m) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
                });
            }

            function decodeHtml(html) {
                if (!html) return '';
                const txt = document.createElement("textarea");
                txt.innerHTML = html;
                return txt.value;
            }

            function updateSummary(meta = {}) {
                const rows = meta.rows ?? allRows.length;
                const totalStock = meta.totalStock ?? allRows.reduce((sum, r) => sum + (parseFloat(r.total_stock) || 0), 0);
                const totalQty = meta.totalQty ?? allRows.reduce((sum, r) => sum + (parseFloat(r.total_qty_bulan_ini) || 0), 0);
                const totalGap = meta.totalGap ?? allRows.reduce((sum, r) => sum + (parseFloat(r.selisih_stock) || 0), 0);
                const bulanLabel = $('#filter_bulan option:selected').text();
                const tahunLabel = $('#filter_tahun').val();

                $('#summary_rows').text(Number(rows).toLocaleString('id-ID'));
                $('#summary_stock').text(Number(totalStock).toLocaleString('id-ID'));
                $('#summary_qty').text(Number(totalQty).toLocaleString('id-ID'));
                $('#summary_gap').text(Number(totalGap).toLocaleString('id-ID'));
                $('#summary_customer').text($('#filter_customer').val() || 'Semua');
                $('#summary_period').text((bulanLabel || '-') + ' ' + (tahunLabel || ''));
            }

            function showToast(message, icon = 'success') {
                Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 1500, timerProgressBar: true });
            }

            function buildPartOptions(selectedValue = '', selectedModel = '', selectedCustomer = '') {
                let options = '<option value="">-- Pilih Part Number --</option>';
                const cleanSelectedModel = decodeHtml(selectedModel || '').trim().toLowerCase();
                const cleanSelectedCustomer = decodeHtml(selectedCustomer || '').trim().toLowerCase();
                const cleanSelectedValue = (selectedValue || '').trim();

                masterItems.forEach((item) => {
                    const itemPart = (item.part_number || '').trim();
                    const itemModel = decodeHtml(item.nama_part || '').trim().toLowerCase();
                    const itemCust = decodeHtml(item.customer || '').trim().toLowerCase();

                    const isSelected = itemPart === cleanSelectedValue 
                        && (!cleanSelectedModel || itemModel === cleanSelectedModel)
                        && (!cleanSelectedCustomer || itemCust === cleanSelectedCustomer);
                    const selected = isSelected ? 'selected' : '';
                    const label = item.part_number + ' — ' + (item.nama_part || '-') + ' (' + (item.customer || '-') + ')';
                    options += `<option value="${escapeHtml(item.part_number)}" ${selected} data-customer="${escapeHtml(item.customer || '')}" data-kode_project="${escapeHtml(item.kode_project || '')}" data-models="${escapeHtml(item.nama_part || '')}">${escapeHtml(label)}</option>`;
                });
                return options;
            }

            function inisialisasiSelect2($context = $('#rekap_table')) {
                $context.find('tr:not(.row-nonaktif) .select-part-number:not(.select2-hidden-accessible)').select2({
                    placeholder: '-- Pilih Part Number --',
                    allowClear: true,
                    width: '100%'
                });
            }

            function hitungOtomatis($row) {
                const getVal = (name) => parseFloat($row.find('[name="' + name + '"]').val()) || 0;
                const stockMip = getVal('stock_awal_mip');
                const stockFg = getVal('stock_awal_fg');
                const wip = getVal('wip_spk_sa');
                const os = getVal('os_bulan_lalu');
                const po = getVal('po_bulan_ini');

                const totalStock = stockMip + stockFg + wip;
                const totalQty = os + po;
                const selisih = Math.abs(totalStock - totalQty);

                $row.find('[name="total_stock"]').val(totalStock);
                $row.find('.disp-tot-stock').text(totalStock.toLocaleString('id-ID'));

                $row.find('[name="total_qty_bulan_ini"]').val(totalQty);
                $row.find('.disp-tot-qty').text(totalQty.toLocaleString('id-ID'));

                $row.find('[name="selisih_stock"]').val(selisih);
                $row.find('.disp-selisih').text(selisih.toLocaleString('id-ID'));
            }

            function showSkeleton() {
                let skeletonRows = '';
                for (let i = 0; i < 6; i++) {
                    skeletonRows += '<tr>';
                    for (let j = 0; j < 14; j++) {
                        skeletonRows += '<td><div class="skeleton-box"></div></td>';
                    }
                    skeletonRows += '</tr>';
                }
                $('#rekap_table tbody').html(skeletonRows);
                $('#table_progress_bar').removeClass('d-none');
            }

            function hideSkeleton() {
                $('#table_progress_bar').addClass('d-none');
            }

            function buildRowHtml(row, index, isManual = false) {
                const rowId = row.id || '';
                const isInactive = row && row.is_active === false;
                const trClass = isManual ? 'manual-row' : (isInactive ? 'row-nonaktif' : '');
                const totStockVal = parseFloat(row.total_stock) || 0;
                const totQtyVal = parseFloat(row.total_qty_bulan_ini) || 0;
                const selisihVal = parseFloat(row.selisih_stock) || 0;
                const displayCustomer = decodeHtml(row.customer || '');
                const displayProject = decodeHtml(row.kode_project || '');
                const displayModel = decodeHtml(row.models || '');

                const partCellHtml = isInactive
                    ? `<div class="part-number-readonly">
                           <span class="part-text">${escapeHtml(row.part_number || '-')}</span>
                           <span class="badge-nonaktif"><i class="fas fa-ban" style="font-size:9px"></i> Nonaktif</span>
                       </div>
                       <input type="hidden" name="part_number" value="${escapeHtml(row.part_number || '')}">`
                    : `<select class="form-select form-select-sm select-part-number" name="part_number">
                           ${buildPartOptions(row.part_number, row.models, row.customer)}
                       </select>`;

                const readonlyAttr = isInactive ? 'readonly disabled' : '';

                const actionHtml = isInactive
                    ? `<span class="badge badge-light-danger fw-bold fs-8" title="Item Nonaktif"><i class="fas fa-lock text-danger me-1"></i> Nonaktif</span>`
                    : (isManual
                        ? `<div class="d-flex justify-content-center gap-1">
                               <button type="button" class="btn-action-icon save simpan-row" title="Simpan baris"><i class="fas fa-check"></i></button>
                               <button type="button" class="btn-action-icon delete hapus-row" title="Hapus baris"><i class="fas fa-trash"></i></button>
                           </div>`
                        : `<span class="badge-synced" title="Data tersimpan"><i class="fas fa-check text-success"></i></span>`);

                return `
                    <tr class="${trClass}" data-id="${rowId}">
                        <td class="col-no text-center"><span class="text-muted fw-bold">${index}</span></td>
                        <td class="col-part">
                            ${partCellHtml}
                        </td>
                        <td class="col-cust text-center">
                            <span class="badge-cust">${escapeHtml(displayCustomer || '-')}</span>
                            <input type="hidden" name="customer" value="${escapeHtml(displayCustomer)}">
                        </td>
                        <td class="col-proj text-center">
                            <span class="badge-proj">${escapeHtml(displayProject || '-')}</span>
                            <input type="hidden" name="kode_project" value="${escapeHtml(displayProject)}">
                        </td>
                        <td class="col-model">
                            <div class="model-truncate" title="${escapeHtml(displayModel)}">${escapeHtml(displayModel || '-')}</div>
                            <input type="hidden" name="models" value="${escapeHtml(displayModel)}">
                        </td>
                        <td class="col-stk-mip">
                            <input type="number" min="0" class="cell-num-input editable-cell" name="stock_awal_mip" value="${row.stock_awal_mip ?? ''}" placeholder="0" ${readonlyAttr}>
                        </td>
                        <td class="col-stk-fg">
                            <input type="number" min="0" class="cell-num-input editable-cell" name="stock_awal_fg" value="${row.stock_awal_fg ?? ''}" placeholder="0" ${readonlyAttr}>
                        </td>
                        <td class="col-wip">
                            <input type="number" min="0" class="cell-num-input editable-cell" name="wip_spk_sa" value="${row.wip_spk_sa ?? ''}" placeholder="0" ${readonlyAttr}>
                        </td>
                        <td class="col-tot-stk">
                            <div class="metric-pill metric-stock">
                                <span class="disp-tot-stock">${totStockVal.toLocaleString('id-ID')}</span>
                                <input type="hidden" name="total_stock" value="${totStockVal}">
                            </div>
                        </td>
                        <td class="col-os">
                            <input type="number" min="0" class="cell-num-input editable-cell input-demand" name="os_bulan_lalu" value="${row.os_bulan_lalu ?? ''}" placeholder="0" ${readonlyAttr}>
                        </td>
                        <td class="col-po">
                            <input type="number" min="0" class="cell-num-input editable-cell input-demand" name="po_bulan_ini" value="${row.po_bulan_ini ?? ''}" placeholder="0" ${readonlyAttr}>
                        </td>
                        <td class="col-tot-qty">
                            <div class="metric-pill metric-qty">
                                <span class="disp-tot-qty">${totQtyVal.toLocaleString('id-ID')}</span>
                                <input type="hidden" name="total_qty_bulan_ini" value="${totQtyVal}">
                            </div>
                        </td>
                        <td class="col-selisih">
                            <div class="metric-pill metric-selisih">
                                <span class="disp-selisih">${selisihVal.toLocaleString('id-ID')}</span>
                                <input type="hidden" name="selisih_stock" value="${selisihVal}">
                            </div>
                        </td>
                        <td class="col-action text-center">
                            ${actionHtml}
                        </td>
                    </tr>
                `;
            }

            function renderRows(rows) {
                activeRows = rows;
                if (!rows || rows.length === 0) {
                    $('#rekap_table tbody').html(`
                        <tr>
                            <td colspan="14" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fs-2x mb-3 d-block text-gray-400"></i>
                                Tidak ada data rekap untuk kriteria pencarian ini.
                            </td>
                        </tr>
                    `);
                    $('#counter_visible').text(0);
                    $('#table_info_text').text('Menampilkan 0 baris data rekap');
                    return;
                }

                let html = '';
                rows.forEach((r, idx) => {
                    html += buildRowHtml(r, idx + 1, false);
                });
                $('#rekap_table tbody').html(html);
                inisialisasiSelect2($('#rekap_table'));

                $('#counter_visible').text(rows.length);
                $('#table_info_text').text(`Menampilkan ${rows.length} dari ${allRows.length} baris data rekap`);
            }

            function loadData() {
                showSkeleton();
                const bulan = $('#filter_bulan').val();
                const tahun = $('#filter_tahun').val();
                const customer = $('#filter_customer').val();

                $.get('{{ route("datastock.rekap.data") }}', { bulan, tahun, customer })
                    .done(function (res) {
                        hideSkeleton();
                        allRows = res.data || [];
                        updateSummary({
                            rows: allRows.length,
                            totalStock: allRows.reduce((sum, r) => sum + (parseFloat(r.total_stock) || 0), 0),
                            totalQty: allRows.reduce((sum, r) => sum + (parseFloat(r.total_qty_bulan_ini) || 0), 0),
                            totalGap: allRows.reduce((sum, r) => sum + (parseFloat(r.selisih_stock) || 0), 0)
                        });
                        
                        const q = $('#table_search').val().toLowerCase().trim();
                        if (q) {
                            const filtered = allRows.filter(r => 
                                (r.part_number && r.part_number.toLowerCase().includes(q)) ||
                                (r.customer && r.customer.toLowerCase().includes(q)) ||
                                (r.kode_project && r.kode_project.toLowerCase().includes(q)) ||
                                (r.models && r.models.toLowerCase().includes(q))
                            );
                            renderRows(filtered);
                        } else {
                            renderRows(allRows);
                        }
                    })
                    .fail(function (xhr) {
                        hideSkeleton();
                        showToast('Gagal mengambil data rekap', 'error');
                    });
            }

            // Client-side quick filter
            $('#table_search').on('input', function () {
                clearTimeout(searchTimer);
                const q = $(this).val().toLowerCase().trim();
                searchTimer = setTimeout(() => {
                    if (!q) {
                        renderRows(allRows);
                    } else {
                        const filtered = allRows.filter(r => 
                            (r.part_number && r.part_number.toLowerCase().includes(q)) ||
                            (r.customer && r.customer.toLowerCase().includes(q)) ||
                            (r.kode_project && r.kode_project.toLowerCase().includes(q)) ||
                            (r.models && r.models.toLowerCase().includes(q))
                        );
                        renderRows(filtered);
                    }
                }, 180);
            });

            // Live calculate on input
            $('#rekap_table tbody').on('input', 'input.cell-num-input', function () {
                hitungOtomatis($(this).closest('tr'));
            });

            // Auto-save on blur
            $('#rekap_table tbody').on('blur', 'input.editable-cell', function () {
                const $row = $(this).closest('tr');
                if ($row.hasClass('manual-row') || $row.hasClass('row-nonaktif')) return;

                const rowKey = $row.attr('data-id') || $row.index();
                clearTimeout(saveTimers[rowKey]);

                saveTimers[rowKey] = setTimeout(() => {
                    hitungOtomatis($row);
                    const data = {
                        _token: '{{ csrf_token() }}',
                        id: $row.attr('data-id') || null,
                        bulan: $('#filter_bulan').val(),
                        tahun: $('#filter_tahun').val(),
                        part_number: $row.find('[name="part_number"]').val(),
                        customer: $row.find('[name="customer"]').val(),
                        kode_project: $row.find('[name="kode_project"]').val(),
                        models: $row.find('[name="models"]').val(),
                        stock_awal_mip: $row.find('[name="stock_awal_mip"]').val() || 0,
                        stock_awal_fg: $row.find('[name="stock_awal_fg"]').val() || 0,
                        wip_spk_sa: $row.find('[name="wip_spk_sa"]').val() || 0,
                        os_bulan_lalu: $row.find('[name="os_bulan_lalu"]').val() || 0,
                        po_bulan_ini: $row.find('[name="po_bulan_ini"]').val() || 0,
                        total_stock: $row.find('[name="total_stock"]').val() || 0,
                        total_qty_bulan_ini: $row.find('[name="total_qty_bulan_ini"]').val() || 0,
                        selisih_stock: $row.find('[name="selisih_stock"]').val() || 0
                    };

                    $.post('{{ route("datastock.rekap.store") }}', data)
                        .done((res) => {
                            if (!$row.attr('data-id') && res.id) $row.attr('data-id', res.id);
                            showToast('Perubahan tersimpan');
                            updateSummary();
                        })
                        .fail((xhr) => {
                            let message = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gagal menyimpan';
                            showToast(message, 'error');
                        });
                }, 200);
            });

            // Part Number Change
            $('#rekap_table tbody').on('change', '.select-part-number', function () {
                const selected = $(this).find('option:selected');
                const $row = $(this).closest('tr');
                const cust = selected.data('customer') || '';
                const proj = selected.data('kode_project') || '';
                const mdl = selected.data('models') || '';

                $row.find('[name="customer"]').val(cust);
                $row.find('.badge-cust').text(cust || '-');

                $row.find('[name="kode_project"]').val(proj);
                $row.find('.badge-proj').text(proj || '-');

                $row.find('[name="models"]').val(mdl);
                $row.find('.model-truncate').text(mdl || '-').attr('title', mdl || '');

                hitungOtomatis($row);

                if ($row.hasClass('manual-row')) return;
                const id = $row.attr('data-id');
                if (!id) return;

                $.post('{{ route("datastock.rekap.store") }}', {
                    _token: '{{ csrf_token() }}',
                    id,
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    part_number: selected.val(),
                    customer: cust,
                    kode_project: proj,
                    models: mdl,
                    stock_awal_mip: $row.find('[name="stock_awal_mip"]').val() || 0,
                    stock_awal_fg: $row.find('[name="stock_awal_fg"]').val() || 0,
                    wip_spk_sa: $row.find('[name="wip_spk_sa"]').val() || 0,
                    os_bulan_lalu: $row.find('[name="os_bulan_lalu"]').val() || 0,
                    po_bulan_ini: $row.find('[name="po_bulan_ini"]').val() || 0,
                    total_stock: $row.find('[name="total_stock"]').val() || 0,
                    total_qty_bulan_ini: $row.find('[name="total_qty_bulan_ini"]').val() || 0,
                    selisih_stock: $row.find('[name="selisih_stock"]').val() || 0
                }).done(() => {
                    showToast('Part number diperbarui');
                });
            });

            // Tambah Baris Manual
            $('#tambah_row').on('click', function () {
                const newRowHtml = buildRowHtml({}, '#', true);
                $('#rekap_table tbody').prepend(newRowHtml);
                const $newRow = $('#rekap_table tbody tr:first');
                inisialisasiSelect2($newRow);
                $newRow.find('select[name="part_number"]').select2('open');
            });

            // Simpan Baris Manual
            $('#rekap_table tbody').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                hitungOtomatis($row);

                const data = {
                    _token: '{{ csrf_token() }}',
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    part_number: $row.find('[name="part_number"]').val(),
                    customer: $row.find('[name="customer"]').val(),
                    kode_project: $row.find('[name="kode_project"]').val(),
                    models: $row.find('[name="models"]').val(),
                    stock_awal_mip: $row.find('[name="stock_awal_mip"]').val() || 0,
                    stock_awal_fg: $row.find('[name="stock_awal_fg"]').val() || 0,
                    wip_spk_sa: $row.find('[name="wip_spk_sa"]').val() || 0,
                    os_bulan_lalu: $row.find('[name="os_bulan_lalu"]').val() || 0,
                    po_bulan_ini: $row.find('[name="po_bulan_ini"]').val() || 0,
                    total_stock: $row.find('[name="total_stock"]').val() || 0,
                    total_qty_bulan_ini: $row.find('[name="total_qty_bulan_ini"]').val() || 0,
                    selisih_stock: $row.find('[name="selisih_stock"]').val() || 0
                };

                if (!data.part_number) {
                    Swal.fire('Validasi', 'Silakan pilih part number terlebih dahulu!', 'warning');
                    return;
                }

                $.post('{{ route("datastock.rekap.store") }}', data)
                    .done((res) => {
                        $row.removeClass('manual-row').attr('data-id', res.id);
                        $row.find('.col-action').html('<span class="badge-synced" title="Data tersimpan"><i class="fas fa-check text-success"></i></span>');
                        showToast('Baris baru berhasil disimpan');
                        loadData();
                    })
                    .fail((xhr) => {
                        let message = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gagal menyimpan baris baru';
                        Swal.fire('Gagal', message, 'error');
                    });
            });

            // Hapus Baris Manual
            $('#rekap_table tbody').on('click', '.hapus-row', function () {
                $(this).closest('tr').remove();
            });

            // Filter Change Events
            $('#filter_bulan, #filter_tahun, #filter_customer').on('change', function () {
                loadData();
            });

            $('#refresh_table').on('click', function () {
                loadData();
            });

            // Initial Load
            loadData();
        });
    </script>
</x-default-layout>
