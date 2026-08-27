<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>TV Display - Monitoring Stok</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root{
      --bg: #070a13;
      --panel: #0d1527;
      --line: #1e293b;
      --text: #f8fafc;
      --muted: #94a3b8;
      --head: #162032;
      --head-sub: #1a253c;
      --today: #ef4444;

      --topbar-h: 60px;
      --wrap-pad: 8px;

      --tbl-font: 12.5px;
      --tbl-pad-y: 6px;
      --tbl-pad-x: 6px;

      --tbl-pill-font: 11px;
      --tbl-pill-pad-y: 2px;
      --tbl-pill-pad-x: 4px;
      --tbl-pill-gap: 2px;

      --tbl-legend-font: 10px;
      --tbl-legend-pad-y: 2.5px;
      --tbl-legend-pad-x: 5px;
      --tbl-legend-gap: 2px;

      --cell-bg: #0d1527;
      --cell-bg-odd: #090e1b;
    }

    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
      overflow: hidden;
    }

    .scale-stage{
      position: fixed;
      inset: 0;
      overflow: hidden;
      background: var(--bg);
    }

    .scale-root{
      width: 100%;
      height: 100%;
    }

    .app{
      height: 100%;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .topbar{
      height: var(--topbar-h);
      padding: 6px 16px;
      border-bottom: 2px solid var(--line);
      background: #0d1527;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
      z-index: 100;
    }

    .brand{
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .brand-logo{
      height: 38px;
      width: auto;
      object-fit: contain;
      display: block;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.5));
    }

    .title{
      font-weight: 800;
      letter-spacing: 0.5px;
      font-size: 16px;
      color: #f8fafc;
      line-height: 1.2;
    }

    .meta{
      color: var(--muted);
      font-size: 11.5px;
      font-weight: 600;
    }

    #clock {
      font-family: 'Outfit', monospace;
      font-size: 15px;
      font-weight: 800;
      color: #60a5fa;
      text-shadow: 0 0 8px rgba(96, 165, 250, 0.4);
      background-color: rgba(13, 21, 39, 0.8);
      border: 1px solid #1e293b;
      padding: 4px 10px;
      border-radius: 6px;
      letter-spacing: 1px;
    }

    .wrap{
      flex: 1;
      min-height: 0;
      padding: var(--wrap-pad) 10px;
      display: flex;
      flex-direction: column;
    }

    .panel{
      height: 100%;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 8px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 0;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    .tv-tabs{
      flex: 0 0 auto;
      background-color: #070a13;
      border-bottom: 2px solid #1e293b;
      padding-top: 6px;
    }

    .tv-tabs .nav-link{
      color: #94a3b8;
      font-weight: 700;
      font-size: 13px;
      letter-spacing: 0.5px;
      border: none;
      padding: 7px 18px;
      background: transparent;
      border-radius: 0;
      transition: all 0.2s ease;
    }

    .tv-tabs .nav-link:hover{
      color: #f8fafc;
      background: rgba(255, 255, 255, 0.03);
    }

    .tv-tabs .nav-link.active{
      color: #60a5fa;
      background-color: rgba(96, 165, 250, 0.08);
      border-bottom: 3px solid #60a5fa;
    }

    .tab-content{
      flex: 1;
      min-height: 0;
    }

    .tab-pane{
      height: 100%;
      min-height: 0;
    }

    .table-wrap{
      height: 100%;
      overflow: auto;
      -webkit-overflow-scrolling: touch;
      background-color: #070a13;
      position: relative;
    }

    .table-wrap::-webkit-scrollbar{ height: 8px; width: 8px; }
    .table-wrap::-webkit-scrollbar-track{ background: #070a13; }
    .table-wrap::-webkit-scrollbar-thumb{
      background: #1e293b;
      border-radius: 4px;
      border: 1px solid #070a13;
    }
    .table-wrap::-webkit-scrollbar-thumb:hover{ background: #334155; }

    table.tv-table{
      width: max-content;
      min-width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: auto;
      margin: 0;
    }

    .tv-table th,
    .tv-table td{
      border-right: 1px solid #1e293b;
      border-bottom: 1px solid #1e293b;
      padding: var(--tbl-pad-y) var(--tbl-pad-x);
      text-align: center;
      white-space: nowrap;
      font-size: var(--tbl-font);
      background-color: var(--cell-bg);
      vertical-align: middle;
      color: #f8fafc;
    }

    .tv-table thead th{
      background-color: var(--head) !important;
      color: #94a3b8;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      font-size: 11.5px;
      border-bottom: 2px solid #334155;
      position: sticky;
      top: 0;
      z-index: 10;
      padding: 8px 6px;
    }

    .tv-table thead tr.thead-sub th{
      background-color: var(--head-sub) !important;
      font-size: 11px;
      color: #cbd5e1;
      font-weight: 700;
      padding: 6px 4px;
    }

    .tv-table tbody tr:nth-child(odd) td{
      background-color: var(--cell-bg-odd);
    }

    .tv-table tbody tr:hover td{
      background-color: #1a2744 !important;
    }

    /* ========================================================
       FROZEN / STICKY LEFT STYLES (No s/d Status)
       ======================================================== */
    td.col-freeze {
      background-color: #0d1527 !important;
    }
    .tv-table tbody tr:nth-child(odd) td.col-freeze {
      background-color: #090e1b !important;
    }
    .tv-table tbody tr:hover td.col-freeze {
      background-color: #1a2744 !important;
    }
    thead th.col-freeze {
      background-color: #162032 !important;
    }

    /* Column Sizing */
    .col-no { width: 34px; min-width: 34px; text-align: center !important; font-size: 12px; }
    .col-cust { min-width: 75px; text-align: left !important; font-weight: 700; color: #e2e8f0 !important; }
    .col-proj { min-width: 60px; text-align: left !important; font-weight: 700; color: #e2e8f0 !important; }
    .col-pn { min-width: 110px; text-align: left !important; font-weight: 700; color: #38bdf8 !important; font-family: monospace; font-size: 12px; }
    .col-name { min-width: 180px; max-width: 250px; text-align: left !important; font-weight: 600; color: #ffffff !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .col-num { min-width: 52px; text-align: center !important; font-weight: 800; font-size: 12px; }
    .col-status { min-width: 54px; text-align: center !important; }
    .col-date { min-width: 36px; text-align: center !important; padding-left: 2px !important; padding-right: 2px !important; font-size: 11.5px !important; font-weight: 700; }
    .col-shift { min-width: 26px; text-align: center !important; font-size: 10.5px !important; padding-left: 2px !important; padding-right: 2px !important; font-weight: 700; }

    /* Legend & Stacked Badges */
    .legend{
      display: flex;
      flex-direction: column;
      gap: var(--tbl-legend-gap);
      align-items: stretch;
    }

    .legend .tag{
      border-radius: 3px;
      padding: var(--tbl-legend-pad-y) var(--tbl-legend-pad-x);
      font-weight: 800;
      line-height: 1.1;
      border: 1px solid transparent;
      font-size: var(--tbl-legend-font);
      text-align: center;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
    }

    .cell-stack{
      display: flex;
      flex-direction: column;
      gap: var(--tbl-pill-gap);
      width: 100%;
    }

    .pill{
      border-radius: 3px;
      padding: var(--tbl-pill-pad-y) var(--tbl-pill-pad-x);
      font-weight: 800;
      line-height: 1.1;
      border: 1px solid transparent;
      font-size: var(--tbl-pill-font);
      text-align: center;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
    }

    /* Standard Palette for Badges */
    .tag-blue, .pill-blue {
      background-color: #2563eb !important;
      border-color: #1d4ed8 !important;
      color: #ffffff !important;
    }
    .tag-green, .pill-green {
      background-color: #16a34a !important;
      border-color: #15803d !important;
      color: #ffffff !important;
    }
    .tag-red, .pill-red {
      background-color: #dc2626 !important;
      border-color: #b91c1c !important;
      color: #ffffff !important;
    }
    .tag-amber, .pill-amber {
      background-color: #d97706 !important;
      border-color: #b45309 !important;
      color: #ffffff !important;
    }

    .today-col{
      background-color: rgba(239, 68, 68, 0.15) !important;
      border-left: 2px solid #ef4444 !important;
      border-right: 2px solid #ef4444 !important;
    }
    th.today-col{
      color: #fca5a5 !important;
      font-weight: 900 !important;
    }

    .prod-low{ color: #f87171; font-weight: 800; }
    .prod-ok{ color: #34d399; font-weight: 800; }

    .loading{
      padding: 36px;
      color: #94a3b8;
      font-size: 15px;
      font-weight: 600;
      text-align: center;
    }

    .errorbox{
      padding: 24px;
      color: #f87171;
      white-space: normal;
      font-weight: 500;
      text-align: center;
    }

    /* Toolbar Control Buttons */
    .ctrl-group {
      background: rgba(15, 23, 42, 0.9);
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 2px 3px;
      display: inline-flex;
      align-items: center;
      gap: 2px;
    }
    .nav-ctrl-btn {
      font-weight: 700;
      font-size: 13px;
      padding: 4px 8px;
      border-radius: 6px;
      background: transparent;
      border: none;
      color: #e2e8f0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.15s ease-in-out;
      user-select: none;
      min-width: 28px;
      height: 28px;
    }
    .nav-ctrl-btn:hover {
      background: #2563eb;
      color: #ffffff;
      transform: scale(1.06);
      box-shadow: 0 0 8px rgba(59, 130, 246, 0.6);
    }
    .nav-ctrl-btn:active {
      transform: scale(0.94);
    }
    .btn-today {
      background: linear-gradient(135deg, #1d4ed8, #2563eb) !important;
      color: #ffffff !important;
      font-weight: 700;
      font-size: 12px;
      padding: 4px 10px;
    }
    .btn-today:hover {
      background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
      box-shadow: 0 0 12px rgba(59, 130, 246, 0.8) !important;
    }
    .btn-zoom-badge {
      min-width: 48px;
      font-weight: 800 !important;
      font-size: 11px !important;
      color: #60a5fa !important;
      background: rgba(30, 41, 59, 0.9) !important;
      letter-spacing: 0.5px;
      font-family: 'Outfit', monospace;
    }
    .btn-zoom-badge:hover {
      background: #1e3a8a !important;
      color: #93c5fd !important;
    }
  </style>
</head>
<body>

<div class="scale-stage">
  <div class="scale-root" id="scaleRoot">
    <div class="app">

      <div class="topbar">
        <div class="brand">
          <img src="{{ asset('assets/media/logos/logo_milenia_login.png') }}" alt="Logo Perusahaan" class="brand-logo">
          <div style="min-width:0">
            <div class="title d-flex align-items-center gap-2">
              <i class="bi bi-tv text-primary"></i>
              <span>TV Display - Monitoring Stok</span>
            </div>
            <div class="meta" id="metaText">Memuat data...</div>
          </div>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap">
          <!-- 1. Navigasi Baris Vertikal (Up / Down) -->
          <div class="ctrl-group" title="Navigasi Baris (Gulir Sedikit)">
            <button class="nav-ctrl-btn" id="btnScrollUp" type="button" title="Gulir Baris ke Atas (Up / ▲)">
              <i class="bi bi-chevron-up"></i>
            </button>
            <button class="nav-ctrl-btn" id="btnScrollDown" type="button" title="Gulir Baris ke Bawah (Down / ▼)">
              <i class="bi bi-chevron-down"></i>
            </button>
          </div>

          <!-- 2. Navigasi Tanggal Horizontal (Left / Hari Ini / Right) -->
          <div class="ctrl-group" title="Navigasi Kolom Tanggal">
            <button class="nav-ctrl-btn" id="btnScrollLeft" type="button" title="Geser Tanggal ke Kiri (Left / ◀)">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="nav-ctrl-btn btn-today text-nowrap" id="btnFocusToday" type="button" title="Lompat ke Kolom Tanggal Hari Ini">
              <i class="bi bi-calendar2-check-fill me-1"></i>Hari Ini
            </button>
            <button class="nav-ctrl-btn" id="btnScrollRight" type="button" title="Geser Tanggal ke Kanan (Right / ▶)">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <!-- 3. Zoom Controls (% Skala Tampilan) -->
          <div class="ctrl-group" title="Pengatur Ukuran Tampilan (Zoom)">
            <button class="nav-ctrl-btn" id="btnZoomOut" type="button" title="Perkecil Tampilan (Zoom Out / -)">
              <i class="bi bi-zoom-out"></i>
            </button>
            <button class="nav-ctrl-btn btn-zoom-badge text-nowrap" id="btnZoomReset" type="button" title="Klik untuk Reset Zoom ke 100%">
              <span id="zoomLevelText">100%</span>
            </button>
            <button class="nav-ctrl-btn" id="btnZoomIn" type="button" title="Perbesar Tampilan (Zoom In / +)">
              <i class="bi bi-zoom-in"></i>
            </button>
          </div>

          <!-- 4. Auto Tab & Info Notes -->
          <button class="btn btn-sm btn-outline-secondary text-light d-inline-flex align-items-center gap-1" id="btnAutoRotate" type="button" style="font-weight: 700; border-radius: 6px; font-size: 12px; padding: 5px 10px;" title="Ganti tab Sub Assy ➡️ MIP ➡️ Finish Goods otomatis setiap 25 detik">
            <i class="bi bi-arrow-repeat"></i>
            <span id="btnAutoRotateText">Auto Tab: OFF</span>
          </button>
          <button class="btn btn-sm btn-outline-secondary text-light d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#tvInfoModal" style="font-weight: 700; border-radius: 6px; font-size: 12px; padding: 5px 10px;" title="Petunjuk & Informasi Penggunaan">
            <i class="bi bi-info-circle text-info"></i>
            <span>Notes</span>
          </button>

          <!-- 5. Fullscreen Toggle -->
          <div class="ctrl-group">
            <button class="nav-ctrl-btn" id="btnFullscreen" type="button" title="Layar Penuh (Fullscreen)">
              <i class="bi bi-fullscreen" id="fsIcon"></i>
            </button>
          </div>

          <!-- 6. Jam & Tombol Refresh -->
          <span id="clock">--:--:--</span>
          <button class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1" id="btnReload" type="button" style="font-weight: 700; border-radius: 6px; font-size: 12px; padding: 5px 12px;">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <div class="wrap">
        <div class="panel">

          <ul class="nav nav-tabs tv-tabs px-3" role="tablist" id="tvTabs">
            <li class="nav-item" role="presentation">
              <button class="nav-link active d-inline-flex align-items-center gap-2" id="tab-subassy" data-bs-toggle="tab" data-bs-target="#pane-subassy" type="button" role="tab">
                <i class="bi bi-gear-wide-connected text-primary fs-6"></i>
                <span>Sub Assy</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link d-inline-flex align-items-center gap-2" id="tab-mip" data-bs-toggle="tab" data-bs-target="#pane-mip" type="button" role="tab">
                <i class="bi bi-boxes text-success fs-6"></i>
                <span>MIP</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link d-inline-flex align-items-center gap-2" id="tab-fg" data-bs-toggle="tab" data-bs-target="#pane-fg" type="button" role="tab">
                <i class="bi bi-check2-circle text-info fs-6"></i>
                <span>Finish Goods</span>
              </button>
            </li>
          </ul>

          <div class="tab-content">

            <div class="tab-pane fade show active" id="pane-subassy" role="tabpanel">
              <div class="table-wrap" id="subassyWrap">
                <div class="loading" id="subassyLoading">Memuat Sub Assy...</div>
                <table class="tv-table" id="subassyTable" style="display:none;">
                  <thead id="subassyThead"></thead>
                  <tbody id="subassyTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-mip" role="tabpanel">
              <div class="table-wrap" id="mipWrap">
                <div class="loading" id="mipLoading">Memuat MIP...</div>
                <table class="tv-table" id="mipTable" style="display:none;">
                  <thead id="mipThead"></thead>
                  <tbody id="mipTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-fg" role="tabpanel">
              <div class="table-wrap" id="fgWrap">
                <div class="loading" id="fgLoading">Memuat Finish Goods...</div>
                <table class="tv-table" id="fgTable" style="display:none;">
                  <thead id="fgThead"></thead>
                  <tbody id="fgTbody"></tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Info / Notes Modal -->
<div class="modal fade" id="tvInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #0d1527; border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 14px; color: #ffffff; box-shadow: 0 20px 50px rgba(0,0,0,0.9);">
      <div class="modal-header border-secondary border-opacity-50 pb-3">
        <h5 class="modal-title d-flex align-items-center gap-2 fw-bold text-white fs-6">
          <i class="bi bi-info-circle-fill text-primary"></i>
          Petunjuk & Catatan Penggunaan TV Display
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body fs-7" style="color: #ffffff; line-height: 1.6;">
        <div class="mb-3">
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-arrows-move me-1"></i> Tombol Navigasi Remote & Kursor</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Gunakan tombol <b>▲ / ▼</b> untuk menggulir baris secara perlahan (per baris), <b>◀ / ▶</b> untuk menggeser tanggal, dan tombol <b>Hari Ini</b> untuk langsung melompat ke kolom tanggal aktif saat ini. Anda juga dapat menekan <b>tombol panah di remote TV</b> secara langsung.
          </p>
        </div>
        <hr class="border-secondary border-opacity-50 my-2">
        <div class="mb-3">
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-zoom-in me-1"></i> Fitur Zoom In / Out Skala Layar</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Gunakan tombol <b>- / +</b> di samping persentase zoom untuk memperkecil atau memperbesar skala tabel (50% s/d 150%) agar pas dan nyaman dibaca dari jarak jauh pada layar TV. Klik angka persentase (misal <b>100%</b>) untuk reset ke ukuran normal.
          </p>
        </div>
        <hr class="border-secondary border-opacity-50 my-2">
        <div class="mb-3">
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-arrow-repeat me-1"></i> Auto Tab (Rotate: ON / OFF)</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Bila status <b>ON</b>, layar TV akan secara otomatis berpindah tab setiap <b>25 detik</b> (Sub Assy ➡️ MIP ➡️ Finish Goods) secara berulang agar seluruh bagian dapat terpantau tanpa operator. Bila status <b>OFF</b>, tampilan akan tetap diam di tab yang dipilih.
          </p>
        </div>
        <hr class="border-secondary border-opacity-50 my-2">
        <div class="mb-3">
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-arrow-clockwise me-1"></i> Tombol Refresh Manual</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Untuk memuat ulang data stok terkini secara instan dari database kapan saja, klik tombol <b>Refresh</b>.
          </p>
        </div>
        <hr class="border-secondary border-opacity-50 my-2">
        <div class="mb-3">
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-pin-angle me-1"></i> Kolom Tetap (Fixed) & Scroll</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Semua kolom identitas dan ringkasan dari <b>No s/d Status</b> dikunci tetap di sebelah kiri. Kolom rincian harian <b>Tanggal 1 s.d. 31</b> dapat digeser/scroll ke samping (*horizontal scroll*) secara leluasa.
          </p>
        </div>
        <hr class="border-secondary border-opacity-50 my-2">
        <div>
          <h6 class="text-info fw-bold mb-2"><i class="bi bi-palette me-1"></i> Arti Warna Status</h6>
          <div class="d-flex flex-column gap-2 text-white" style="color: #ffffff !important;">
            <div><span class="badge bg-primary me-2 fw-bold">BIRU</span> : SPK (Sub Assy) / Balance Akhir (MIP & FG)</div>
            <div><span class="badge bg-success me-2 fw-bold">HIJAU</span> : Produksi (Sub Assy) / Total IN (MIP & FG)</div>
            <div><span class="badge bg-danger me-2 fw-bold">MERAH</span> : WIP (Sub Assy) / Total OUT (MIP & FG)</div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-secondary border-opacity-50 pt-2 pb-2">
        <button type="button" class="btn btn-sm btn-primary px-4 fw-bold" data-bs-dismiss="modal">Mengerti</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const subassyUrl = @json(route('tv.data.subassy'));
  const mipUrl     = @json(route('tv.data.mip'));
  const fgUrl      = @json(route('tv.data.fg'));

  const state = {
    subassy: { todayDay: null, bulan: null, tahun: null, timestamp: null, loaded: false },
    mip:     { todayDay: null, bulan: null, tahun: null, timestamp: null, loaded: false },
    fg:      { todayDay: null, bulan: null, tahun: null, timestamp: null, loaded: false }
  };

  function escapeHtml(value){
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function n(value){
    const num = Number(value);
    return Number.isFinite(num) ? num : 0;
  }

  function pad(v){
    return String(v).padStart(2, '0');
  }

  function tickClock(){
    const d = new Date();
    document.getElementById('clock').textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }
  setInterval(tickClock, 1000);
  tickClock();

  function prodBadge(p){
    const val = parseInt(p || 0, 10);
    return val < 100
      ? `<span class="prod-low">${escapeHtml(val)}%</span>`
      : `<span class="prod-ok">${escapeHtml(val)}%</span>`;
  }

  function getQueryUrl(baseUrl){
    const url = new URL(baseUrl, window.location.origin);
    const params = new URLSearchParams(window.location.search);

    ['bulan','tahun','customer'].forEach(key => {
      const v = params.get(key);
      if (v) url.searchParams.set(key, v);
    });

    return url.toString();
  }

  function fixHeaderStickyTops(tableId){
    const table = document.getElementById(tableId);
    if (!table || table.style.display === 'none') return;

    const thead = table.querySelector('thead');
    if (!thead) return;

    const rows = Array.from(thead.querySelectorAll('tr'));
    if (rows.length <= 1) return;

    let acc = 0;
    rows.forEach((row, rIndex) => {
      const rowHeight = row.getBoundingClientRect().height || 0;
      const ths = Array.from(row.querySelectorAll('th'));
      ths.forEach(th => {
        th.style.top = `${acc}px`;
      });
      acc += rowHeight;
    });
  }

  function applyStickyLeftOffsets(tableId){
    const table = document.getElementById(tableId);
    if (!table || table.style.display === 'none') return;

    const thead = table.querySelector('thead');
    if (!thead) return;

    const headerRow = thead.querySelector('tr');
    if (!headerRow) return;

    const frozenHeaders = Array.from(headerRow.querySelectorAll('th.col-freeze'));
    if (frozenHeaders.length === 0) return;

    let leftOffsets = [];
    let currentLeft = 0;

    // Measure rendered natural widths and set sticky left for each frozen header
    frozenHeaders.forEach(th => {
      leftOffsets.push(currentLeft);
      th.style.position = 'sticky';
      th.style.left = `${currentLeft}px`;
      th.style.zIndex = '40';
      currentLeft += th.offsetWidth;
    });

    // Apply separator styling on the last frozen column (Status)
    const lastHeader = frozenHeaders[frozenHeaders.length - 1];
    if (lastHeader) {
      lastHeader.style.borderRight = '2px solid #3b82f6';
      lastHeader.style.boxShadow = '4px 0 10px rgba(0, 0, 0, 0.5)';
    }

    // Apply matching left offsets to all body rows
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const bodyRows = tbody.querySelectorAll('tr');
    bodyRows.forEach(tr => {
      const frozenCells = Array.from(tr.querySelectorAll('td.col-freeze'));
      frozenCells.forEach((td, idx) => {
        if (leftOffsets[idx] !== undefined) {
          td.style.position = 'sticky';
          td.style.left = `${leftOffsets[idx]}px`;
          td.style.zIndex = '20';
        }
      });

      const lastCell = frozenCells[frozenCells.length - 1];
      if (lastCell) {
        lastCell.style.borderRight = '2px solid #3b82f6';
        lastCell.style.boxShadow = '4px 0 10px rgba(0, 0, 0, 0.5)';
      }
    });
  }

  function highlightTodayGeneric(tableId, todayDay){
    document.querySelectorAll(`#${tableId} [data-day]`).forEach(el => el.classList.remove('today-col'));
    if (!todayDay) return;

    document.querySelectorAll(`#${tableId} th[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
    document.querySelectorAll(`#${tableId} td.day-cell[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
  }

  let autoRotateEnabled = false;
  let rotateTimer = null;
  const ROTATE_INTERVAL = 25000; // 25 detik per tab

  function startAutoRotate() {
    stopAutoRotate();
    if (!autoRotateEnabled) return;
    
    rotateTimer = setInterval(() => {
      const tabs = Array.from(document.querySelectorAll('#tvTabs .nav-link'));
      if (tabs.length === 0) return;
      let activeIndex = tabs.findIndex(t => t.classList.contains('active'));
      let nextIndex = (activeIndex + 1) % tabs.length;
      
      const tabTrigger = new bootstrap.Tab(tabs[nextIndex]);
      tabTrigger.show();
    }, ROTATE_INTERVAL);
  }

  function stopAutoRotate() {
    if (rotateTimer) {
      clearInterval(rotateTimer);
      rotateTimer = null;
    }
  }

  async function fetchJsonOrThrow(url){
    const res = await fetch(url, {
      headers: { 'Accept':'application/json' },
      cache: 'no-store'
    });

    const ct = res.headers.get('content-type') || '';

    if (!res.ok){
      const text = await res.text();
      throw new Error(`HTTP ${res.status} | ${text.slice(0, 250)}`);
    }

    if (!ct.includes('application/json')){
      const text = await res.text();
      throw new Error(`Response bukan JSON. Preview: ${text.slice(0, 250)}`);
    }

    const json = await res.json();
    if (!json || json.success !== true){
      throw new Error('Response success=false');
    }

    return json;
  }

  function showErrorBox(loadingId, tableId, err){
    const loading = document.getElementById(loadingId);
    const table = document.getElementById(tableId);

    if (table) table.style.display = 'none';

    loading.style.display = 'block';
    loading.className = 'errorbox';
    loading.innerHTML = `Gagal memuat data:<br><code>${escapeHtml(String(err))}</code>`;
    console.error(err);
  }

  function showEmptyBox(loadingId, tableId, message){
    const loading = document.getElementById(loadingId);
    const table = document.getElementById(tableId);

    if (table) table.style.display = 'none';

    loading.style.display = 'block';
    loading.className = 'emptybox';
    loading.textContent = message;
  }

  function updateMetaTextByTab(tabKey){
    const meta = document.getElementById('metaText');
    const item = state[tabKey];

    if (!item || !item.loaded){
      meta.textContent = 'Memuat data...';
      return;
    }

    const labelMap = {
      subassy: 'Sub Assy',
      mip: 'MIP',
      fg: 'Finish Goods'
    };

    meta.textContent = `${labelMap[tabKey]} | ${pad(item.bulan)}-${item.tahun} | Update: ${item.timestamp}`;
  }

  function getActiveTabKey(){
    const activeBtn = document.querySelector('#tvTabs .nav-link.active');
    if (!activeBtn) return 'subassy';

    if (activeBtn.id === 'tab-mip') return 'mip';
    if (activeBtn.id === 'tab-fg') return 'fg';
    return 'subassy';
  }

  function updateActiveMeta(){
    updateMetaTextByTab(getActiveTabKey());
  }

  function postRenderTable({ tableId, todayDay }){
    requestAnimationFrame(() => {
      fixHeaderStickyTops(tableId);
      applyStickyLeftOffsets(tableId);
      highlightTodayGeneric(tableId, todayDay);
    });
  }
</script>

<!-- ==================== SUB ASSY ==================== -->
<script>
  function buildSubAssyHeader(daysInMonth){
    const thead = document.getElementById('subassyThead');

    let row1 = `
      <tr>
        <th rowspan="2" class="col-freeze col-no">No</th>
        <th rowspan="2" class="col-freeze col-cust">Customer</th>
        <th rowspan="2" class="col-freeze col-proj">Project</th>
        <th rowspan="2" class="col-freeze col-pn">Part Number</th>
        <th rowspan="2" class="col-freeze col-name">Part Name</th>
        <th rowspan="2" class="col-freeze col-num">Total PO</th>
        <th rowspan="2" class="col-freeze col-num">WIP Sblm</th>
        <th rowspan="2" class="col-freeze col-num">Total SPK</th>
        <th rowspan="2" class="col-freeze col-num">Total Prod</th>
        <th rowspan="2" class="col-freeze col-num">WIP Akhir</th>
        <th rowspan="2" class="col-freeze col-num">Prod %</th>
        <th rowspan="2" class="col-freeze col-status">Status</th>
        <th colspan="${daysInMonth}">Tanggal</th>
      </tr>
    `;

    let row2 = `<tr class="thead-sub">`;
    for (let i = 1; i <= daysInMonth; i++){
      row2 += `<th class="col-date" data-day="${i}">${i}</th>`;
    }
    row2 += `</tr>`;

    thead.innerHTML = row1 + row2;
  }

  function buildSubAssyBody(rows, daysInMonth){
    const tbody = document.getElementById('subassyTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="col-freeze col-no">${idx + 1}</td>
        <td class="col-freeze col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-freeze col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-freeze col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-freeze col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.wip_sebelumnya))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_spk))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_produksi))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.wip_akhir))}</td>
        <td class="col-freeze col-num">${prodBadge(r.produktivitas)}</td>

        <td class="col-freeze col-status">
          <div class="legend">
            <div class="tag tag-blue">SPK</div>
            <div class="tag tag-green">PROD</div>
            <div class="tag tag-red">WIP</div>
          </div>
        </td>
      `;

      for (let d = 1; d <= daysInMonth; d++){
        const spk  = n(r.days?.spk?.[d]);
        const prod = n(r.days?.produksi?.[d]);
        const wip  = n(r.days?.wip?.[d]);

        html += `
          <td class="day-cell col-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-blue">${escapeHtml(spk)}</div>
              <div class="pill pill-green">${escapeHtml(prod)}</div>
              <div class="pill pill-red">${escapeHtml(wip)}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  async function loadSubAssy(){
    const loading = document.getElementById('subassyLoading');
    const table = document.getElementById('subassyTable');

    loading.style.display = 'block';
    loading.className = 'loading';
    loading.textContent = 'Memuat Sub Assy...';
    table.style.display = 'none';

    const json = await fetchJsonOrThrow(getQueryUrl(subassyUrl));

    if (!Array.isArray(json.rows) || json.rows.length === 0){
      document.getElementById('subassyThead').innerHTML = '';
      document.getElementById('subassyTbody').innerHTML = '';
      showEmptyBox('subassyLoading', 'subassyTable', 'Data Sub Assy kosong.');
      state.subassy = {
        todayDay: json.todayDay ?? null,
        bulan: json.bulan ?? null,
        tahun: json.tahun ?? null,
        timestamp: json.timestamp ?? '-',
        loaded: true
      };
      updateActiveMeta();
      return;
    }

    buildSubAssyHeader(json.daysInMonth);
    buildSubAssyBody(json.rows, json.daysInMonth);

    loading.style.display = 'none';
    table.style.display = 'table';

    state.subassy = {
      todayDay: json.todayDay ?? null,
      bulan: json.bulan ?? null,
      tahun: json.tahun ?? null,
      timestamp: json.timestamp ?? '-',
      loaded: true
    };

    updateActiveMeta();
    postRenderTable({
      tableId: 'subassyTable',
      todayDay: state.subassy.todayDay
    });
  }
</script>

<!-- ==================== MIP ==================== -->
<script>
  function buildMIPHeader(daysInMonth){
    const thead = document.getElementById('mipThead');

    let row1 = `
      <tr>
        <th rowspan="2" class="col-freeze col-no">No</th>
        <th rowspan="2" class="col-freeze col-cust">Customer</th>
        <th rowspan="2" class="col-freeze col-proj">Project</th>
        <th rowspan="2" class="col-freeze col-pn">Part Number</th>
        <th rowspan="2" class="col-freeze col-name">Part Name</th>
        <th rowspan="2" class="col-freeze col-num">Total PO</th>
        <th rowspan="2" class="col-freeze col-num">Stk Awal</th>
        <th rowspan="2" class="col-freeze col-num">Tot IN</th>
        <th rowspan="2" class="col-freeze col-num">Tot OUT</th>
        <th rowspan="2" class="col-freeze col-num">Bal Akhir</th>
        <th rowspan="2" class="col-freeze col-num">Min</th>
        <th rowspan="2" class="col-freeze col-num">Safety</th>
        <th rowspan="2" class="col-freeze col-num">Max</th>
        <th rowspan="2" class="col-freeze col-status">Status</th>
        <th colspan="${daysInMonth}">Tanggal</th>
      </tr>
    `;

    let row2 = `<tr class="thead-sub">`;
    for (let i = 1; i <= daysInMonth; i++){
      row2 += `<th class="col-date" data-day="${i}">${i}</th>`;
    }
    row2 += `</tr>`;

    thead.innerHTML = row1 + row2;
  }

  function buildMIPBody(rows, daysInMonth){
    const tbody = document.getElementById('mipTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="col-freeze col-no">${idx + 1}</td>
        <td class="col-freeze col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-freeze col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-freeze col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-freeze col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.stock_awal))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_in))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_out))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.balance_akhir))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_min))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_safety))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_max))}</td>

        <td class="col-freeze col-status">
          <div class="legend">
            <div class="tag tag-green">IN</div>
            <div class="tag tag-red">OUT</div>
            <div class="tag tag-blue">BAL</div>
          </div>
        </td>
      `;

      for (let d = 1; d <= daysInMonth; d++){
        const inQty  = n(r.days?.in?.[d]);
        const outQty = n(r.days?.out?.[d]);
        const bal    = n(r.days?.balance?.[d]);

        html += `
          <td class="day-cell col-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-green">${escapeHtml(inQty)}</div>
              <div class="pill pill-red">${escapeHtml(outQty)}</div>
              <div class="pill pill-blue">${escapeHtml(bal)}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  async function loadMIP(){
    const loading = document.getElementById('mipLoading');
    const table = document.getElementById('mipTable');

    loading.style.display = 'block';
    loading.className = 'loading';
    loading.textContent = 'Memuat MIP...';
    table.style.display = 'none';

    const json = await fetchJsonOrThrow(getQueryUrl(mipUrl));

    if (!Array.isArray(json.rows) || json.rows.length === 0){
      document.getElementById('mipThead').innerHTML = '';
      document.getElementById('mipTbody').innerHTML = '';
      showEmptyBox('mipLoading', 'mipTable', 'Data MIP kosong.');
      state.mip = {
        todayDay: json.todayDay ?? null,
        bulan: json.bulan ?? null,
        tahun: json.tahun ?? null,
        timestamp: json.timestamp ?? '-',
        loaded: true
      };
      updateActiveMeta();
      return;
    }

    buildMIPHeader(json.daysInMonth);
    buildMIPBody(json.rows, json.daysInMonth);

    loading.style.display = 'none';
    table.style.display = 'table';

    state.mip = {
      todayDay: json.todayDay ?? null,
      bulan: json.bulan ?? null,
      tahun: json.tahun ?? null,
      timestamp: json.timestamp ?? '-',
      loaded: true
    };

    updateActiveMeta();
    postRenderTable({
      tableId: 'mipTable',
      todayDay: state.mip.todayDay
    });
  }
</script>

<!-- ==================== FINISH GOODS ==================== -->
<script>
  function statusStockBadge(v){
    const val = String(v || '').toLowerCase();

    if (val === 'problem'){
      return `<span class="tag tag-red" style="padding: 2.5px 5px; font-size: 10px; border-radius: 3px;">Problem</span>`;
    }

    if (val === 'over'){
      return `<span class="tag tag-amber" style="padding: 2.5px 5px; font-size: 10px; border-radius: 3px;">Over</span>`;
    }

    return `<span class="tag tag-green" style="padding: 2.5px 5px; font-size: 10px; border-radius: 3px;">Aman</span>`;
  }

  function buildFGHeader(daysInMonth){
    const thead = document.getElementById('fgThead');

    let row1 = `
      <tr>
        <th rowspan="3" class="col-freeze col-no">No</th>
        <th rowspan="3" class="col-freeze col-cust">Customer</th>
        <th rowspan="3" class="col-freeze col-proj">Project</th>
        <th rowspan="3" class="col-freeze col-pn">Part Number</th>
        <th rowspan="3" class="col-freeze col-name">Part Name</th>
        <th rowspan="3" class="col-freeze col-num">Total PO</th>
        <th rowspan="3" class="col-freeze col-num">Adv Del</th>
        <th rowspan="3" class="col-freeze col-num">Outst</th>
        <th rowspan="3" class="col-freeze col-num">% Del</th>
        <th rowspan="3" class="col-freeze col-num">Stk Awal</th>
        <th rowspan="3" class="col-freeze col-num">Tot IN</th>
        <th rowspan="3" class="col-freeze col-num">Tot OUT</th>
        <th rowspan="3" class="col-freeze col-num">Min</th>
        <th rowspan="3" class="col-freeze col-num">Safety</th>
        <th rowspan="3" class="col-freeze col-num">Max</th>
        <th rowspan="3" class="col-freeze col-num">Stk Hand</th>
        <th rowspan="3" class="col-freeze col-num">Status Stk</th>
        <th rowspan="3" class="col-freeze col-status">Status</th>
        <th colspan="${daysInMonth * 2}">Tanggal</th>
      </tr>
    `;

    let row2 = `<tr class="thead-sub">`;
    for (let i = 1; i <= daysInMonth; i++){
      row2 += `<th colspan="2" class="col-date" data-day="${i}">${i}</th>`;
    }
    row2 += `</tr>`;

    let row3 = `<tr class="thead-sub">`;
    for (let i = 1; i <= daysInMonth; i++){
      row3 += `<th class="col-shift" data-day="${i}">D</th><th class="col-shift" data-day="${i}">N</th>`;
    }
    row3 += `</tr>`;

    thead.innerHTML = row1 + row2 + row3;
  }

  function buildFGBody(rows, daysInMonth){
    const tbody = document.getElementById('fgTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="col-freeze col-no">${idx + 1}</td>
        <td class="col-freeze col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-freeze col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-freeze col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-freeze col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.advance_delivery))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.outstanding))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.percentage))}%</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.stock_awal))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_in))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.total_out))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_min))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_safety))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.level_max))}</td>
        <td class="col-freeze col-num">${escapeHtml(n(r.stock_on_hand))}</td>
        <td class="col-freeze col-num">${statusStockBadge(r.status_stock)}</td>

        <td class="col-freeze col-status">
          <div class="legend">
            <div class="tag tag-green">IN</div>
            <div class="tag tag-red">OUT</div>
            <div class="tag tag-blue">BAL</div>
          </div>
        </td>
      `;

      for (let d = 1; d <= daysInMonth; d++){
        const inD  = n(r.days?.in_d?.[d]);
        const outD = n(r.days?.out_d?.[d]);
        const balD = n(r.days?.bal_d?.[d]);

        const inN  = n(r.days?.in_n?.[d]);
        const outN = n(r.days?.out_n?.[d]);
        const balN = n(r.days?.bal_n?.[d]);

        html += `
          <td class="day-cell col-shift" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-green">${escapeHtml(inD)}</div>
              <div class="pill pill-red">${escapeHtml(outD)}</div>
              <div class="pill pill-blue">${escapeHtml(balD)}</div>
            </div>
          </td>
          <td class="day-cell col-shift" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-green">${escapeHtml(inN)}</div>
              <div class="pill pill-red">${escapeHtml(outN)}</div>
              <div class="pill pill-blue">${escapeHtml(balN)}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  async function loadFG(){
    const loading = document.getElementById('fgLoading');
    const table = document.getElementById('fgTable');

    loading.style.display = 'block';
    loading.className = 'loading';
    loading.textContent = 'Memuat Finish Goods...';
    table.style.display = 'none';

    const json = await fetchJsonOrThrow(getQueryUrl(fgUrl));

    if (!Array.isArray(json.rows) || json.rows.length === 0){
      document.getElementById('fgThead').innerHTML = '';
      document.getElementById('fgTbody').innerHTML = '';
      showEmptyBox('fgLoading', 'fgTable', 'Data Finish Goods kosong.');
      state.fg = {
        todayDay: json.todayDay ?? null,
        bulan: json.bulan ?? null,
        tahun: json.tahun ?? null,
        timestamp: json.timestamp ?? '-',
        loaded: true
      };
      updateActiveMeta();
      return;
    }

    buildFGHeader(json.daysInMonth);
    buildFGBody(json.rows, json.daysInMonth);

    loading.style.display = 'none';
    table.style.display = 'table';

    state.fg = {
      todayDay: json.todayDay ?? null,
      bulan: json.bulan ?? null,
      tahun: json.tahun ?? null,
      timestamp: json.timestamp ?? '-',
      loaded: true
    };

    updateActiveMeta();
    postRenderTable({
      tableId: 'fgTable',
      todayDay: state.fg.todayDay
    });
  }
</script>

<!-- ==================== CONTROLS & INIT ==================== -->
<script>
  async function reloadAll(){
    const btn = document.getElementById('btnReload');
    btn.disabled = true;
    btn.textContent = 'Refreshing...';

    const results = await Promise.allSettled([
      loadSubAssy(),
      loadMIP(),
      loadFG()
    ]);

    results.forEach((result, index) => {
      if (result.status === 'rejected'){
        if (index === 0) showErrorBox('subassyLoading', 'subassyTable', result.reason);
        if (index === 1) showErrorBox('mipLoading', 'mipTable', result.reason);
        if (index === 2) showErrorBox('fgLoading', 'fgTable', result.reason);
      }
    });

    updateActiveMeta();

    btn.disabled = false;
    btn.textContent = 'Refresh';

    if (autoRotateEnabled) {
      startAutoRotate();
    }
  }

  function reflowVisibleTables(){
    const activeTab = getActiveTabKey();
    const tableIdMap = { subassy: 'subassyTable', mip: 'mipTable', fg: 'fgTable' };
    const tableId = tableIdMap[activeTab];
    if (tableId){
      fixHeaderStickyTops(tableId);
      applyStickyLeftOffsets(tableId);
      highlightTodayGeneric(tableId, state[activeTab]?.todayDay);
    }
  }

  // ==================== MANUAL & REMOTE NAVIGATION ====================
  function getActiveTableWrap() {
    const activeTab = getActiveTabKey();
    return document.getElementById(`${activeTab}Wrap`);
  }

  function focusToday(tabKey = getActiveTabKey()) {
    const todayDay = state[tabKey]?.todayDay;
    if (!todayDay) return;

    const wrap = document.getElementById(`${tabKey}Wrap`);
    const table = document.getElementById(`${tabKey}Table`);
    if (!wrap || !table) return;

    const targetHeader = table.querySelector(`th[data-day="${todayDay}"]`);
    if (!targetHeader) return;

    const frozenHeaders = Array.from(table.querySelectorAll('th.col-freeze'));
    let stickyWidth = 0;
    frozenHeaders.forEach(th => {
      stickyWidth += th.offsetWidth;
    });

    const targetLeft = targetHeader.offsetLeft;
    const scrollTo = Math.max(0, targetLeft - stickyWidth - 10);
    wrap.scrollTo({ left: scrollTo, behavior: 'smooth' });
  }

  // Scroll sedikit (gentle step 75px per klik)
  document.getElementById('btnScrollUp')?.addEventListener('click', () => {
    const wrap = getActiveTableWrap();
    if (wrap) wrap.scrollBy({ top: -75, behavior: 'smooth' });
  });

  document.getElementById('btnScrollDown')?.addEventListener('click', () => {
    const wrap = getActiveTableWrap();
    if (wrap) wrap.scrollBy({ top: 75, behavior: 'smooth' });
  });

  document.getElementById('btnScrollLeft')?.addEventListener('click', () => {
    const wrap = getActiveTableWrap();
    if (wrap) wrap.scrollBy({ left: -200, behavior: 'smooth' });
  });

  document.getElementById('btnScrollRight')?.addEventListener('click', () => {
    const wrap = getActiveTableWrap();
    if (wrap) wrap.scrollBy({ left: 200, behavior: 'smooth' });
  });

  document.getElementById('btnFocusToday')?.addEventListener('click', () => {
    focusToday();
  });

  // ==================== ZOOM / SCALE CONTROLS ====================
  let currentZoom = parseFloat(localStorage.getItem('tv_zoom') || '1.0');
  if (isNaN(currentZoom) || currentZoom < 0.5 || currentZoom > 1.6) currentZoom = 1.0;

  function applyZoom(zoom) {
    currentZoom = Math.min(1.5, Math.max(0.5, Math.round(zoom * 10) / 10));
    localStorage.setItem('tv_zoom', currentZoom.toString());
    
    const zoomTextEl = document.getElementById('zoomLevelText');
    if (zoomTextEl) {
      zoomTextEl.textContent = `${Math.round(currentZoom * 100)}%`;
    }
    
    document.querySelectorAll('.tv-table').forEach(table => {
      table.style.zoom = currentZoom;
    });

    requestAnimationFrame(reflowVisibleTables);
  }

  document.getElementById('btnZoomIn')?.addEventListener('click', () => {
    applyZoom(currentZoom + 0.1);
  });

  document.getElementById('btnZoomOut')?.addEventListener('click', () => {
    applyZoom(currentZoom - 0.1);
  });

  document.getElementById('btnZoomReset')?.addEventListener('click', () => {
    applyZoom(1.0);
  });

  // Apply initial saved zoom
  applyZoom(currentZoom);

  // ==================== FULLSCREEN CONTROLS ====================
  document.getElementById('btnFullscreen')?.addEventListener('click', () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(err => console.log(err));
    } else {
      document.exitFullscreen().catch(err => console.log(err));
    }
  });

  document.addEventListener('fullscreenchange', () => {
    const fsIcon = document.getElementById('fsIcon');
    if (fsIcon) {
      fsIcon.className = document.fullscreenElement ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
    }
  });

  // Dukungan Navigasi Tombol Remote TV (D-Pad Panah, Zoom, & Home)
  window.addEventListener('keydown', (e) => {
    if (['input', 'textarea', 'select'].includes(document.activeElement?.tagName?.toLowerCase())) return;

    const wrap = getActiveTableWrap();
    if (!wrap) return;

    if (e.key === 'ArrowUp') {
      wrap.scrollBy({ top: -65, behavior: 'smooth' });
    } else if (e.key === 'ArrowDown') {
      wrap.scrollBy({ top: 65, behavior: 'smooth' });
    } else if (e.key === 'ArrowLeft') {
      wrap.scrollBy({ left: -180, behavior: 'smooth' });
    } else if (e.key === 'ArrowRight') {
      wrap.scrollBy({ left: 180, behavior: 'smooth' });
    } else if (e.key === 'Home' || e.key === 'h' || e.key === 'H') {
      focusToday();
    } else if (e.key === '+' || e.key === '=') {
      applyZoom(currentZoom + 0.1);
    } else if (e.key === '-' || e.key === '_') {
      applyZoom(currentZoom - 0.1);
    } else if (e.key === '0') {
      applyZoom(1.0);
    }
  });

  document.getElementById('btnReload').addEventListener('click', reloadAll);

  const btnAutoRotate = document.getElementById('btnAutoRotate');
  btnAutoRotate.addEventListener('click', () => {
    autoRotateEnabled = !autoRotateEnabled;
    if (autoRotateEnabled) {
      btnAutoRotate.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Auto Tab: ON</span>';
      btnAutoRotate.classList.remove('btn-outline-secondary');
      btnAutoRotate.classList.add('btn-outline-info', 'active');
      startAutoRotate();
    } else {
      btnAutoRotate.innerHTML = '<i class="bi bi-arrow-repeat"></i><span>Auto Tab: OFF</span>';
      btnAutoRotate.classList.remove('btn-outline-info', 'active');
      btnAutoRotate.classList.add('btn-outline-secondary');
      stopAutoRotate();
    }
  });

  document.querySelectorAll('#tvTabs .nav-link').forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => {
      updateActiveMeta();
      requestAnimationFrame(reflowVisibleTables);
      
      if (autoRotateEnabled) {
        startAutoRotate();
      }
    });
  });

  reloadAll();

  let resizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(reflowVisibleTables, 100);
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden){
      requestAnimationFrame(reflowVisibleTables);
    }
  });

  window.addEventListener('load', () => {
    requestAnimationFrame(reflowVisibleTables);
  });
</script>

</body>
</html>