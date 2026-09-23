<x-default-layout>
    @section('title', 'Level Stock MIP & FG')

    <div class="level-shell my-5">
        <!-- Hero Header -->
        <section class="level-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-7">
                        <span class="hero-chip mb-3"><i class="fas fa-layer-group me-2"></i>Kontrol Level Stock MIP & FG</span>
                        <h1 class="text-white fw-bolder mb-2">Penetapan Level Stock Minimum & Maksimum</h1>
                        <p class="hero-copy mb-0">Tentukan parameter hari kerja dan QTY per box. Sistem menghitung otomatis nilai Min, Safety MIP, Safety FG, dan Max berdasarkan kebutuhan PO bulan ini.</p>
                    </div>
                    <div class="col-xl-5">
                        <div class="hero-stats">
                            <div class="hero-stat"><span class="label">Periode Aktif</span><span class="value" id="metricPeriod">{{ \Carbon\Carbon::create()->month((int) $selectedBulan)->translatedFormat('F') }} {{ $selectedTahun }}</span><span class="note">Bulan & tahun aktif</span></div>
                            <div class="hero-stat"><span class="label">Hari PO > 100</span><span class="value" id="metricHariAtas">{{ $latestLevel->jumlah_hari_kerja_atas_100 ?? 0 }}</span><span class="note">Hari kerja order tinggi</span></div>
                            <div class="hero-stat"><span class="label">Hari PO ≤ 100</span><span class="value" id="metricHariBawah">{{ $latestLevel->jumlah_hari_kerja_bawah_100 ?? 0 }}</span><span class="note">Hari kerja order rendah</span></div>
                            <div class="hero-stat"><span class="label">Item Terkonfigurasi</span><span class="value" id="summaryConfigured">0</span><span class="note">Part dengan QTY/Box</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filter & Control Panel -->
        <section class="card control-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    <div class="col-xl-7">
                        <div class="panel-box h-100">
                            <div class="panel-title"><i class="fas fa-sliders text-primary me-2"></i>Parameter Hari Kerja & Periode</div>
                            <div class="panel-subtitle">Atur hari kerja bulanan untuk batas order > 100 set dan ≤ 100 set. Perubahan tersimpan otomatis.</div>
                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-bold fs-7" for="filter_bulan">Bulan</label>
                                    <select id="filter_bulan" class="form-select form-select-solid">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" {{ (int) $selectedBulan === $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-bold fs-7" for="filter_tahun">Tahun</label>
                                    <select id="filter_tahun" class="form-select form-select-solid">
                                        @for ($year = now()->year - 5; $year <= now()->year + 1; $year++)
                                            <option value="{{ $year }}" {{ (int) $selectedTahun === $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-bold fs-7" for="hari_atas_100">Hari (PO &gt; 100)</label>
                                    <input type="number" min="0" id="hari_atas_100" class="form-control form-control-solid" value="{{ $latestLevel->jumlah_hari_kerja_atas_100 ?? 0 }}" data-field="jumlah_hari_kerja_atas_100">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="form-label fw-bold fs-7" for="hari_bawah_100">Hari (PO ≤ 100)</label>
                                    <input type="number" min="0" id="hari_bawah_100" class="form-control form-control-solid" value="{{ $latestLevel->jumlah_hari_kerja_bawah_100 ?? 0 }}" data-field="jumlah_hari_kerja_bawah_100">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="panel-box h-100">
                            <div class="panel-title"><i class="fas fa-calculator text-info me-2"></i>Panduan Rumus Hitung</div>
                            <div class="formula-list mt-2">
                                <div class="formula-row"><span class="badge-step">1</span> <span><strong>Kebutuhan/Hari</strong> = Total QTY Bulan Ini / Hari Kerja</span></div>
                                <div class="formula-row"><span class="badge-step">2</span> <span><strong>Min</strong> = CEILING(Kebutuhan/Hari ÷ QTY Box) × QTY Box</span></div>
                                <div class="formula-row"><span class="badge-step">3</span> <span><strong>Safety MIP & FG</strong> = Min × 2 &nbsp;|&nbsp; <strong>Max</strong> = Min × 5</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button id="tambah_baris" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Baris</button>
                                <button id="refresh_table" class="btn btn-light-primary"><i class="fas fa-rotate-right me-2"></i>Refresh Data</button>
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
                        <div class="panel-title mb-1">Tabel Penetapan Level Stock</div>
                        <div class="panel-subtitle">Isi kolom <strong>QTY/Set Box</strong> untuk part terkait. Sistem akan menarik total order dan menghitung level stock otomatis.</div>
                    </div>
                    <div class="table-legend">
                        <span class="legend-item"><span class="legend-box limit"></span>Batas Hitung (Min/Safety/Max)</span>
                        <span class="legend-item"><span class="legend-box qtybox"></span>Input QTY per Box</span>
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
                    <table id="levelstock_table" class="table-grid align-middle mb-0">
                        <colgroup>
                            <col class="col-no">
                            <col class="col-part">
                            <col class="col-cust">
                            <col class="col-proj">
                            <col class="col-model">
                            <col class="col-min">
                            <col class="col-safety-mip">
                            <col class="col-safety-fg">
                            <col class="col-max">
                            <col class="col-qty-box">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="col-no text-center">#</th>
                                <th class="col-part text-start ps-3"><i class="fas fa-barcode text-muted me-1"></i> Part Number</th>
                                <th class="col-cust text-center"><i class="fas fa-building text-muted me-1"></i> Customer</th>
                                <th class="col-proj text-center"><i class="fas fa-hashtag text-muted me-1"></i> Proj</th>
                                <th class="col-model text-start ps-3"><i class="fas fa-tag text-muted me-1"></i> Model</th>
                                <th class="col-min th-limit text-end pe-2"><span class="th-badge th-badge-min">MIN</span> Minimum</th>
                                <th class="col-safety-mip th-limit text-end pe-2"><span class="th-badge th-badge-safety">MIP</span> Safety Stock</th>
                                <th class="col-safety-fg th-limit text-end pe-2"><span class="th-badge th-badge-safety">FG</span> Safety Stock</th>
                                <th class="col-max th-limit text-end pe-2"><span class="th-badge th-badge-max">MAX</span> Maksimum</th>
                                <th class="col-qty-box th-qtybox text-end pe-2"><span class="th-badge th-badge-qtybox">INPUT</span> QTY/Box</th>
                                <th class="col-action text-center"><i class="fas fa-sliders text-muted me-1"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Footer Summary Info -->
                <div class="d-flex justify-content-between align-items-center mt-3 text-muted fs-7">
                    <div id="table_info_text">Menampilkan 0 baris level stock</div>
                    <div class="text-end"><i class="fas fa-arrows-left-right me-1"></i> Geser horizontal untuk melihat seluruh kolom</div>
                </div>
            </div>
        </section>
    </div>

    <style>
        .level-shell { display: grid; gap: 1.5rem; }
        .level-hero { border: 0; border-radius: 24px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #0284c7 0%, #0369a1 44%, #0f766e 100%); box-shadow: 0 20px 50px rgba(3, 105, 161, .15); }
        .level-hero .card-body { padding: 2rem; }
        .hero-chip { display: inline-flex; align-items: center; padding: .55rem .95rem; border-radius: 999px; background: rgba(255,255,255,.15); color: #fff; font-size: .88rem; font-weight: 600; }
        .hero-copy { color: rgba(255,255,255,.85); font-size: .95rem; max-width: 680px; }
        .hero-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem; }
        .hero-stat { padding: 1rem 1.1rem; border-radius: 18px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); color: #fff; }
        .hero-stat .label { display: block; font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.75); }
        .hero-stat .value { display: block; margin-top: .3rem; font-weight: 800; font-size: 1.45rem; line-height: 1.1; font-family: 'Fira Code', monospace; }
        .hero-stat .note { display: block; margin-top: .35rem; color: rgba(255,255,255,.75); font-size: .8rem; }
        .control-panel, .table-panel { border: 0; border-radius: 20px; background: #fff; box-shadow: 0 10px 30px rgba(15, 23, 42, .05); border: 1px solid #e2e8f0; }
        .panel-box { height: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.1rem; }
        .panel-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        .panel-subtitle { color: #64748b; font-size: .88rem; }
        
        .formula-list { display: grid; gap: .4rem; }
        .formula-row { font-size: .84rem; color: #334155; display: flex; align-items: center; gap: .5rem; }
        .badge-step { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: #0284c7; color: #fff; font-size: 10px; font-weight: 800; flex-shrink: 0; }

        .table-legend { display: flex; flex-wrap: wrap; gap: .65rem; }
        .legend-item { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .75rem; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; font-size: .82rem; font-weight: 600; }
        .legend-box { width: 10px; height: 10px; border-radius: 3px; }
        .legend-box.limit { background: #0284c7; }
        .legend-box.qtybox { background: #8b5cf6; }

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
            min-width: 1525px !important;
            table-layout: fixed !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
        }

        /* Explicit Column Width Tokens (Spacious & Responsive on Zoom) */
        .col-no { width: 45px !important; min-width: 45px !important; max-width: 45px !important; }
        .col-part { width: 310px !important; min-width: 310px !important; max-width: 310px !important; }
        .col-cust { width: 105px !important; min-width: 105px !important; max-width: 105px !important; }
        .col-proj { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }
        .col-model { width: 225px !important; min-width: 225px !important; max-width: 225px !important; }
        .col-min { width: 120px !important; min-width: 120px !important; max-width: 120px !important; }
        .col-safety-mip { width: 145px !important; min-width: 145px !important; max-width: 145px !important; }
        .col-safety-fg { width: 145px !important; min-width: 145px !important; max-width: 145px !important; }
        .col-max { width: 120px !important; min-width: 120px !important; max-width: 120px !important; }
        .col-qty-box { width: 125px !important; min-width: 125px !important; max-width: 125px !important; }
        .col-action { width: 95px !important; min-width: 95px !important; max-width: 95px !important; }

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
        .table-grid thead th.th-limit { background-color: #f0f9ff !important; color: #0369a1 !important; border-top: 3px solid #0284c7 !important; }
        .table-grid thead th.th-qtybox { background-color: #f5f3ff !important; color: #5b21b6 !important; border-top: 3px solid #8b5cf6 !important; }

        .th-badge { font-size: 9.5px; padding: 2px 5px; border-radius: 4px; font-weight: 800; margin-right: 3px; display: inline-block; vertical-align: middle; }
        .th-badge-min { background: #bae6fd; color: #0369a1; border: 1px solid #7dd3fc; }
        .th-badge-safety { background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; }
        .th-badge-max { background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; }
        .th-badge-qtybox { background: #ede9fe; color: #6d28d9; border: 1px solid #c4b5fd; }

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
            border-color: #8b5cf6 !important;
            background: #fff !important;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2) !important;
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
        .metric-min { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .metric-safety { background: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4; }
        .metric-max { background: #faf5ff; color: #9333ea; border: 1px solid #e9d5ff; }

        /* Action Badges & Buttons */
        .badge-synced { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .65rem; border-radius: 999px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; font-size: 11px; font-weight: 700; }
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
        .progress-bar-fill { height: 100%; width: 30%; background: linear-gradient(90deg, #0284c7, #38bdf8); border-radius: 999px; animation: progress-move 1.2s infinite ease-in-out; }
        @keyframes progress-move { 0% { margin-left: -30%; } 100% { margin-left: 100%; } }

        .skeleton-box { height: 22px; background: #e2e8f0; border-radius: 6px; position: relative; overflow: hidden; }
        .skeleton-box::after { content: ""; position: absolute; top: 0; right: 0; bottom: 0; left: 0; transform: translateX(-100%); background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0) 100%); animation: shimmer 1.4s infinite; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }

        @media (max-width: 991.98px) { .level-hero .card-body { padding: 1.5rem; } .hero-stats { grid-template-columns: 1fr; } }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.currentLevelStokId = {{ $latestLevel?->id ?? 'null' }};
        const masterItems = @json($masterItems);

        $(function () {
            let allRows = [];
            let activeRows = [];
            let searchTimer = null;

            // Disable scroll-to-change on number inputs
            $(document).on('wheel', 'input[type=number]', function (e) {
                if ($(this).is(':focus')) {
                    $(this).blur();
                }
            });

            function showToast(message, icon = 'success') {
                Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 1500, timerProgressBar: true });
            }

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

            function formatVal(v) {
                return (v !== null && v !== undefined && v !== '' && !Number.isNaN(Number(v))) ? Number(v).toLocaleString('id-ID') : '-';
            }

            function getFilterParams() {
                return {
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val()
                };
            }

            function updateHeroPeriod() {
                const bulanLabel = $('#filter_bulan option:selected').text();
                const tahunLabel = $('#filter_tahun').val();
                $('#metricPeriod').text(`${bulanLabel} ${tahunLabel}`);
            }

            function updateHariMetrics() {
                $('#metricHariAtas').text($('#hari_atas_100').val() || 0);
                $('#metricHariBawah').text($('#hari_bawah_100').val() || 0);
            }

            function updateSummary() {
                const total = allRows.length;
                let configured = 0;
                allRows.forEach(r => {
                    if (parseInt(r.qty_set_box, 10) > 0) configured++;
                });
                $('#summaryConfigured').text(configured);
            }

            function ensureLevelStokId() {
                return $.get('{{ route("datastock.levelstock.getId") }}', getFilterParams()).done(res => {
                    window.currentLevelStokId = res.id ?? null;
                });
            }

            function loadHariKerja() {
                updateHeroPeriod();
                return $.when(
                    $.get('{{ route("datastock.levelstock.getHariKerja") }}', getFilterParams(), res => {
                        $('#hari_atas_100').val(res.hari_atas_100 ?? 0);
                        $('#hari_bawah_100').val(res.hari_bawah_100 ?? 0);
                        updateHariMetrics();
                    }),
                    ensureLevelStokId()
                );
            }

            function buildPartOptions(selectedValue = '', selectedModel = '') {
                let options = '<option value="">-- Pilih Part Number --</option>';
                const cleanSelectedModel = decodeHtml(selectedModel || '').trim().toLowerCase();
                const cleanSelectedValue = (selectedValue || '').trim();

                masterItems.forEach(item => {
                    const itemPart = (item.part_number || '').trim();
                    const itemModel = decodeHtml(item.nama_part || '').trim().toLowerCase();

                    const isSelected = itemPart === cleanSelectedValue && (!cleanSelectedModel || itemModel === cleanSelectedModel);
                    const selected = isSelected ? 'selected' : '';
                    const label = item.part_number + ' — ' + (item.nama_part || '-') + ' (' + (item.customer || '-') + ')';
                    options += `<option value="${escapeHtml(item.part_number)}" data-id="${item.id}" data-customer="${escapeHtml(item.customer || '')}" data-kode_projek="${escapeHtml(item.kode_project || '')}" data-models="${escapeHtml(item.nama_part || '')}" ${selected}>${escapeHtml(label)}</option>`;
                });
                return options;
            }

            function enhanceSelect2($context = $('#levelstock_table')) {
                $context.find('.select-part-number:not(.select2-hidden-accessible)').select2({
                    placeholder: '-- Pilih Part Number --',
                    allowClear: true,
                    width: '100%'
                });
            }

            function showSkeleton() {
                let skeletonRows = '';
                for (let i = 0; i < 6; i++) {
                    skeletonRows += '<tr>';
                    for (let j = 0; j < 11; j++) {
                        skeletonRows += '<td><div class="skeleton-box"></div></td>';
                    }
                    skeletonRows += '</tr>';
                }
                $('#levelstock_table tbody').html(skeletonRows);
                $('#table_progress_bar').removeClass('d-none');
            }

            function hideSkeleton() {
                $('#table_progress_bar').addClass('d-none');
            }

            function buildRowHtml(row, index, isManual = false) {
                const rowId = row.id || '';
                const trClass = isManual ? 'manual-row' : '';
                const displayCustomer = decodeHtml(row.customer || '');
                const displayProject = decodeHtml(row.kode_projek || row.kode_project || '');
                const displayModel = decodeHtml(row.models || '');

                return `
                    <tr class="${trClass}" data-id="${rowId}">
                        <td class="col-no text-center"><span class="text-muted fw-bold">${index}</span></td>
                        <td class="col-part">
                            <select class="form-select form-select-sm select-part-number" name="part_number">
                                ${buildPartOptions(row.part_number, row.models)}
                            </select>
                        </td>
                        <td class="col-cust text-center">
                            <span class="badge-cust">${escapeHtml(displayCustomer || '-')}</span>
                            <input type="hidden" name="customer" value="${escapeHtml(displayCustomer)}">
                        </td>
                        <td class="col-proj text-center">
                            <span class="badge-proj">${escapeHtml(displayProject || '-')}</span>
                            <input type="hidden" name="kode_projek" value="${escapeHtml(displayProject)}">
                        </td>
                        <td class="col-model">
                            <div class="model-truncate" title="${escapeHtml(displayModel)}">${escapeHtml(displayModel || '-')}</div>
                            <input type="hidden" name="models" value="${escapeHtml(displayModel)}">
                        </td>
                        <td class="col-min">
                            <div class="metric-pill metric-min">
                                <span class="disp-min">${formatVal(row.min)}</span>
                                <input type="hidden" name="min" value="${row.min ?? ''}">
                            </div>
                        </td>
                        <td class="col-safety-mip">
                            <div class="metric-pill metric-safety">
                                <span class="disp-safety-mip">${formatVal(row.safety_mip)}</span>
                                <input type="hidden" name="safety_mip" value="${row.safety_mip ?? ''}">
                            </div>
                        </td>
                        <td class="col-safety-fg">
                            <div class="metric-pill metric-safety">
                                <span class="disp-safety-fg">${formatVal(row.safety_fg)}</span>
                                <input type="hidden" name="safety_fg" value="${row.safety_fg ?? ''}">
                            </div>
                        </td>
                        <td class="col-max">
                            <div class="metric-pill metric-max">
                                <span class="disp-max">${formatVal(row.max)}</span>
                                <input type="hidden" name="max" value="${row.max ?? ''}">
                            </div>
                        </td>
                        <td class="col-qty-box">
                            <input type="number" min="0" class="cell-num-input editable-cell input-qty-box" name="qty_set_box" value="${row.qty_set_box ?? ''}" placeholder="0">
                        </td>
                        <td class="col-action text-center">
                            ${isManual ? `
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn-action-icon save simpan-row" title="Simpan baris"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn-action-icon delete hapus-row" title="Hapus baris"><i class="fas fa-trash"></i></button>
                                </div>
                            ` : `
                                <span class="badge-synced" title="Level stock tersinkron"><i class="fas fa-check-circle text-success"></i> Auto</span>
                            `}
                        </td>
                    </tr>
                `;
            }

            function renderRows(rows) {
                activeRows = rows;
                if (!rows || rows.length === 0) {
                    $('#levelstock_table tbody').html(`
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fs-2x mb-3 d-block text-gray-400"></i>
                                Belum ada data level stock untuk kriteria pencarian ini.
                            </td>
                        </tr>
                    `);
                    $('#counter_visible').text(0);
                    $('#table_info_text').text('Menampilkan 0 baris level stock');
                    return;
                }

                let html = '';
                rows.forEach((r, idx) => {
                    html += buildRowHtml(r, idx + 1, false);
                });
                $('#levelstock_table tbody').html(html);
                enhanceSelect2($('#levelstock_table'));

                $('#counter_visible').text(rows.length);
                $('#table_info_text').text(`Menampilkan ${rows.length} dari ${allRows.length} baris level stock`);
            }

            function syncCalculatedFields($row, values) {
                $row.find('input[name="min"]').val(values.min);
                $row.find('.disp-min').text(formatVal(values.min));

                $row.find('input[name="safety_mip"]').val(values.safetyMip);
                $row.find('.disp-safety-mip').text(formatVal(values.safetyMip));

                $row.find('input[name="safety_fg"]').val(values.safetyFg);
                $row.find('.disp-safety-fg').text(formatVal(values.safetyFg));

                $row.find('input[name="max"]').val(values.max);
                $row.find('.disp-max').text(formatVal(values.max));

                updateSummary();
            }

            function buildRowPayload($row) {
                const data = {
                    _token: '{{ csrf_token() }}',
                    level_stok_id: window.currentLevelStokId,
                    ...getFilterParams()
                };

                $row.find('input[name], select[name]').each(function () {
                    data[$(this).attr('name')] = $(this).val();
                });

                const rowId = $row.attr('data-id');
                if (rowId) data.id = rowId;
                return data;
            }

            function saveRow($row, successMessage, silent = false) {
                const data = buildRowPayload($row);

                if (!data.part_number) {
                    Swal.fire('Validasi', 'Silakan pilih part number terlebih dahulu!', 'warning');
                    return $.Deferred().reject().promise();
                }

                return $.post('{{ route("datastock.levelstock.detail.store") }}', data)
                    .done(res => {
                        if (res.id) $row.attr('data-id', res.id);

                        if ($row.hasClass('manual-row')) {
                            $row.removeClass('manual-row');
                            $row.find('.col-action').html('<span class="badge-synced" title="Level stock tersinkron"><i class="fas fa-check-circle text-success"></i> Auto</span>');
                        }

                        updateSummary();
                        if (!silent) showToast(successMessage);
                    })
                    .fail(xhr => {
                        const message = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Gagal menyimpan level stock.';
                        Swal.fire('Gagal', message, 'error');
                    });
            }

            function calculateLevelStock($row) {
                const partNumber = $row.find('select[name="part_number"]').val();
                const customer = $row.find('input[name="customer"]').val();
                const kodeProjek = $row.find('input[name="kode_projek"]').val();
                const models = $row.find('input[name="models"]').val();
                const qtyPerBox = parseInt($row.find('input[name="qty_set_box"]').val(), 10) || 0;

                if (!partNumber || qtyPerBox <= 0) {
                    syncCalculatedFields($row, { min: '', safetyMip: '', safetyFg: '', max: '' });
                    return $.Deferred().resolve().promise();
                }

                return $.get('{{ route("datastock.rekap.fetch") }}', {
                    ...getFilterParams(),
                    part_number: partNumber,
                    customer,
                    kode_project: kodeProjek,
                    models
                }).done(res => {
                    const totalQty = parseInt(res?.total_qty_bulan_ini, 10) || 0;
                    const hariKerja = totalQty > 100
                        ? parseInt($('#hari_atas_100').val(), 10) || 0
                        : parseInt($('#hari_bawah_100').val(), 10) || 0;

                    if (hariKerja <= 0) {
                        syncCalculatedFields($row, { min: '', safetyMip: '', safetyFg: '', max: '' });
                        return;
                    }

                    const kebutuhanPerHari = totalQty / hariKerja;
                    const min = kebutuhanPerHari > 0
                        ? Math.ceil(kebutuhanPerHari / qtyPerBox) * qtyPerBox
                        : 0;

                    syncCalculatedFields($row, {
                        min,
                        safetyMip: min * 2,
                        safetyFg: min * 2,
                        max: min * 5
                    });
                }).fail(() => {
                    showToast('Gagal menarik data rekap', 'error');
                });
            }

            function loadData() {
                showSkeleton();
                return $.when(
                    loadHariKerja(),
                    $.get('{{ route("datastock.levelstock.data") }}', getFilterParams())
                ).done(function(hariRes, tableRes) {
                    hideSkeleton();
                    const data = tableRes[0]?.data || [];
                    allRows = data;

                    const q = $('#table_search').val().toLowerCase().trim();
                    if (q) {
                        const filtered = allRows.filter(r => 
                            (r.part_number && r.part_number.toLowerCase().includes(q)) ||
                            (r.customer && r.customer.toLowerCase().includes(q)) ||
                            (r.kode_projek && r.kode_projek.toLowerCase().includes(q)) ||
                            (r.models && r.models.toLowerCase().includes(q))
                        );
                        renderRows(filtered);
                    } else {
                        renderRows(allRows);
                    }
                    updateSummary();
                }).fail(function() {
                    hideSkeleton();
                    showToast('Gagal memuat data level stock', 'error');
                });
            }

            // Quick Client-Side Search
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
                            (r.kode_projek && r.kode_projek.toLowerCase().includes(q)) ||
                            (r.models && r.models.toLowerCase().includes(q))
                        );
                        renderRows(filtered);
                    }
                }, 180);
            });

            // QTY Set Box change and blur auto-save
            $('#levelstock_table tbody').on('blur', 'input[name="qty_set_box"]', function () {
                const $row = $(this).closest('tr');
                calculateLevelStock($row).done(() => {
                    if ($row.hasClass('manual-row')) {
                        updateSummary();
                        return;
                    }
                    saveRow($row, 'Level stock diperbarui');
                });
            });

            // Part Number Change
            $('#levelstock_table tbody').on('change', '.select-part-number', function () {
                const $selectedOption = $(this).find('option:selected');
                const partNumber = $(this).val();
                const itemId = $selectedOption.data('id');
                const selectedItem = (itemId ? masterItems.find(item => item.id == itemId) : null) || masterItems.find(item => item.part_number === partNumber);
                const $row = $(this).closest('tr');

                if (!selectedItem) {
                    $row.find('input[name="customer"], input[name="kode_projek"], input[name="models"]').val('');
                    $row.find('.badge-cust').text('-');
                    $row.find('.badge-proj').text('-');
                    $row.find('.model-truncate').text('-').attr('title', '');
                    calculateLevelStock($row);
                    return;
                }

                $row.find('input[name="customer"]').val(selectedItem.customer || '');
                $row.find('.badge-cust').text(selectedItem.customer || '-');

                $row.find('input[name="kode_projek"]').val(selectedItem.kode_project || '');
                $row.find('.badge-proj').text(selectedItem.kode_project || '-');

                $row.find('input[name="models"]').val(selectedItem.nama_part || '');
                $row.find('.model-truncate').text(selectedItem.nama_part || '-').attr('title', selectedItem.nama_part || '');

                calculateLevelStock($row).done(() => {
                    if (!$row.hasClass('manual-row')) {
                        saveRow($row, 'Level stock diperbarui');
                    }
                });
            });

            // Hari Kerja update on blur
            $('#hari_atas_100, #hari_bawah_100').on('blur', function () {
                const field = $(this).data('field');
                const val = parseInt($(this).val(), 10) || 0;

                $.post('{{ route("datastock.levelstock.updateHariKerja") }}', {
                    _token: '{{ csrf_token() }}',
                    field: field,
                    value: val,
                    ...getFilterParams()
                }).done(() => {
                    updateHariMetrics();
                    showToast('Hari kerja diperbarui');
                    $('#levelstock_table tbody tr').each(function () {
                        const $row = $(this);
                        calculateLevelStock($row).done(() => {
                            if (!$row.hasClass('manual-row') && $row.find('select[name="part_number"]').val()) {
                                saveRow($row, 'Level stock dihitung ulang', true);
                            }
                        });
                    });
                }).fail(() => Swal.fire('Error', 'Gagal menyimpan jumlah hari kerja.', 'error'));
            });

            // Tambah Baris Manual
            $('#tambah_baris').on('click', function () {
                const newRowHtml = buildRowHtml({}, '#', true);
                $('#levelstock_table tbody').prepend(newRowHtml);
                const $newRow = $('#levelstock_table tbody tr:first');
                enhanceSelect2($newRow);
                $newRow.find('select[name="part_number"]').select2('open');
            });

            // Simpan Baris Manual
            $('#levelstock_table tbody').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                ensureLevelStokId().done(() => {
                    calculateLevelStock($row).done(() => {
                        saveRow($row, 'Baris level stock berhasil disimpan');
                    });
                });
            });

            // Hapus Baris Manual
            $('#levelstock_table tbody').on('click', '.hapus-row', function () {
                $(this).closest('tr').remove();
                updateSummary();
            });

            // Filter Change Events
            $('#filter_bulan, #filter_tahun').on('change', function () {
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
