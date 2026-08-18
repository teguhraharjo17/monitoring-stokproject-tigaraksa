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

      --topbar-h: 56px;
      --wrap-pad: 6px;

      --tbl-font: 11px;
      --tbl-pad-y: 4px;
      --tbl-pad-x: 4px;

      --tbl-pill-font: 9.5px;
      --tbl-pill-pad-y: 1.5px;
      --tbl-pill-pad-x: 2px;
      --tbl-pill-gap: 1.5px;

      --tbl-legend-font: 9px;
      --tbl-legend-pad-y: 2px;
      --tbl-legend-pad-x: 4px;
      --tbl-legend-gap: 1.5px;

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
      padding: 4px 14px;
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
      gap: 10px;
      min-width: 0;
    }

    .brand-logo{
      height: 36px;
      width: auto;
      object-fit: contain;
      display: block;
      filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.5));
    }

    .title{
      font-weight: 800;
      letter-spacing: 0.5px;
      font-size: 15px;
      color: #f8fafc;
      line-height: 1.2;
    }

    .meta{
      color: var(--muted);
      font-size: 11px;
      font-weight: 500;
    }

    #clock {
      font-family: 'Outfit', monospace;
      font-size: 14px;
      font-weight: 800;
      color: #60a5fa;
      text-shadow: 0 0 8px rgba(96, 165, 250, 0.4);
      background-color: rgba(13, 21, 39, 0.8);
      border: 1px solid #1e293b;
      padding: 3px 8px;
      border-radius: 5px;
      letter-spacing: 1px;
    }

    .wrap{
      flex: 1;
      min-height: 0;
      padding: var(--wrap-pad) 8px;
      display: flex;
      flex-direction: column;
    }

    .panel{
      height: 100%;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 6px;
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
      padding-top: 4px;
    }

    .tv-tabs .nav-link{
      color: #94a3b8;
      font-weight: 700;
      font-size: 12px;
      letter-spacing: 0.5px;
      border: none;
      padding: 5px 14px;
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

    .table-wrap::-webkit-scrollbar{ height: 6px; width: 6px; }
    .table-wrap::-webkit-scrollbar-track{ background: #070a13; }
    .table-wrap::-webkit-scrollbar-thumb{
      background: #1e293b;
      border-radius: 3px;
    }
    .table-wrap::-webkit-scrollbar-thumb:hover{ background: #334155; }

    .table-container{
      display: inline-block;
      min-width: 100%;
      transform-origin: top left;
    }

    table.tv-table{
      width: 100%;
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
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      font-size: 10.5px;
      border-bottom: 2px solid #334155;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .tv-table thead tr.thead-sub th{
      background-color: var(--head-sub) !important;
      font-size: 9.5px;
      color: #cbd5e1;
    }

    .tv-table tbody tr:nth-child(odd) td{
      background-color: var(--cell-bg-odd);
    }

    .tv-table tbody tr:hover td{
      background-color: #1a2744 !important;
    }

    /* Column Sizing & Alignment */
    .col-no { width: 24px; min-width: 24px; text-align: center !important; font-size: 10px; }
    .col-cust { width: 65px; min-width: 60px; text-align: left !important; font-weight: 700; color: #e2e8f0 !important; }
    .col-proj { width: 50px; min-width: 45px; text-align: left !important; font-weight: 700; color: #e2e8f0 !important; }
    .col-pn { width: 95px; min-width: 90px; text-align: left !important; font-weight: 700; color: #38bdf8 !important; font-family: monospace; font-size: 10.5px; }
    .col-name { min-width: 130px; max-width: 220px; text-align: left !important; font-weight: 600; color: #f8fafc !important; overflow: hidden; text-overflow: ellipsis; }
    .col-num { width: 44px; min-width: 38px; text-align: center !important; font-weight: 800; font-size: 10.5px; }
    .col-status { width: 42px; min-width: 38px; text-align: center !important; }
    .col-date { width: 24px; min-width: 20px; text-align: center !important; padding-left: 1px !important; padding-right: 1px !important; font-size: 10px !important; }
    .col-shift { width: 14px; min-width: 13px; text-align: center !important; font-size: 8.5px !important; padding-left: 1px !important; padding-right: 1px !important; }

    /* Legend & Stacked Badges */
    .legend{
      display: flex;
      flex-direction: column;
      gap: var(--tbl-legend-gap);
      align-items: stretch;
    }

    .legend .tag{
      border-radius: 2px;
      padding: var(--tbl-legend-pad-y) var(--tbl-legend-pad-x);
      font-weight: 800;
      line-height: 1;
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
      border-radius: 2px;
      padding: var(--tbl-pill-pad-y) var(--tbl-pill-pad-x);
      font-weight: 800;
      line-height: 1;
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
      background-color: rgba(239, 68, 68, 0.12) !important;
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
      padding: 30px;
      color: #94a3b8;
      font-size: 14px;
      font-weight: 500;
      text-align: center;
    }

    .errorbox{
      padding: 24px;
      color: #f87171;
      white-space: normal;
      font-weight: 500;
      text-align: center;
    }

    .emptybox{
      padding: 24px;
      color: #94a3b8;
      font-weight: 500;
      text-align: center;
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

        <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-sm btn-outline-info active d-inline-flex align-items-center gap-1" id="btnAutoRotate" type="button" style="font-weight: 700; border-radius: 5px; font-size: 11px; padding: 4px 8px;" title="Ganti tab Sub Assy ➡️ MIP ➡️ Finish Goods otomatis setiap 25 detik">
            <i class="bi bi-arrow-repeat"></i>
            <span id="btnAutoRotateText">Auto Tab: ON</span>
          </button>
          <button class="btn btn-sm btn-outline-secondary text-light d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="modal" data-bs-target="#tvInfoModal" style="font-weight: 700; border-radius: 5px; font-size: 11px; padding: 4px 8px;" title="Petunjuk & Informasi Penggunaan">
            <i class="bi bi-info-circle text-info"></i>
            <span>Notes / Info</span>
          </button>
          <span id="clock">--:--:--</span>
          <button class="btn btn-sm btn-outline-light d-inline-flex align-items-center gap-1" id="btnReload" type="button" style="font-weight: 700; border-radius: 5px; font-size: 11px; padding: 4px 10px;">
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
                <div class="table-container" id="subassyContainer">
                  <table class="tv-table" id="subassyTable" style="display:none;">
                    <thead id="subassyThead"></thead>
                    <tbody id="subassyTbody"></tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-mip" role="tabpanel">
              <div class="table-wrap" id="mipWrap">
                <div class="loading" id="mipLoading">Memuat MIP...</div>
                <div class="table-container" id="mipContainer">
                  <table class="tv-table" id="mipTable" style="display:none;">
                    <thead id="mipThead"></thead>
                    <tbody id="mipTbody"></tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-fg" role="tabpanel">
              <div class="table-wrap" id="fgWrap">
                <div class="loading" id="fgLoading">Memuat Finish Goods...</div>
                <div class="table-container" id="fgContainer">
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
          <h6 class="text-info fw-bold mb-1"><i class="bi bi-aspect-ratio me-1"></i> Auto-Fit Layar TV</h6>
          <p class="mb-0 text-white" style="color: #ffffff !important;">
            Lebar tabel secara otomatis diskalakan agar <b>seluruh tanggal 1 s.d. 31</b> tampil penuh dalam satu layar TV tanpa perlu digeser mendatar (*no horizontal scroll*).
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
        th.style.zIndex = String(20 - rIndex);
      });
      acc += rowHeight;
    });
  }

  function autoScaleTabTable(tabKey){
    const wrap = document.getElementById(`${tabKey}Wrap`);
    const container = document.getElementById(`${tabKey}Container`);
    const table = document.getElementById(`${tabKey}Table`);
    if (!wrap || !container || !table || table.style.display === 'none') return;

    // Reset zoom/transform first to measure natural width
    container.style.zoom = '';
    container.style.transform = '';
    container.style.width = 'max-content';

    const wrapWidth = wrap.clientWidth - 4;
    const naturalWidth = table.scrollWidth;

    if (naturalWidth > wrapWidth && wrapWidth > 100){
      const scale = wrapWidth / naturalWidth;
      // Use CSS zoom (standard for Chromium on Smart TVs)
      if ('zoom' in container.style){
        container.style.zoom = scale;
      } else {
        container.style.transform = `scale(${scale})`;
      }
    }
  }

  function highlightTodayGeneric(tableId, todayDay){
    document.querySelectorAll(`#${tableId} [data-day]`).forEach(el => el.classList.remove('today-col'));
    if (!todayDay) return;

    document.querySelectorAll(`#${tableId} th[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
    document.querySelectorAll(`#${tableId} td.day-cell[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
  }

  let autoRotateEnabled = true;
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

  function postRenderTable({ tabKey, tableId, todayDay }){
    requestAnimationFrame(() => {
      fixHeaderStickyTops(tableId);
      highlightTodayGeneric(tableId, todayDay);
      autoScaleTabTable(tabKey);
    });
  }
</script>

<!-- ==================== SUB ASSY ==================== -->
<script>
  function buildSubAssyHeader(daysInMonth){
    const thead = document.getElementById('subassyThead');

    let row1 = `
      <tr>
        <th rowspan="2" class="col-no">No</th>
        <th rowspan="2" class="col-cust">Customer</th>
        <th rowspan="2" class="col-proj">Project</th>
        <th rowspan="2" class="col-pn">Part Number</th>
        <th rowspan="2" class="col-name">Part Name</th>
        <th rowspan="2" class="col-num">Total PO</th>
        <th rowspan="2" class="col-num">WIP Sblm</th>
        <th rowspan="2" class="col-num">Total SPK</th>
        <th rowspan="2" class="col-num">Total Prod</th>
        <th rowspan="2" class="col-num">WIP Akhir</th>
        <th rowspan="2" class="col-num">Prod %</th>
        <th rowspan="2" class="col-status">Status</th>
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
        <td class="col-no">${idx + 1}</td>
        <td class="col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-num">${escapeHtml(n(r.wip_sebelumnya))}</td>
        <td class="col-num">${escapeHtml(n(r.total_spk))}</td>
        <td class="col-num">${escapeHtml(n(r.total_produksi))}</td>
        <td class="col-num">${escapeHtml(n(r.wip_akhir))}</td>
        <td class="col-num">${prodBadge(r.produktivitas)}</td>

        <td class="col-status">
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
      tabKey: 'subassy',
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
        <th rowspan="2" class="col-no">No</th>
        <th rowspan="2" class="col-cust">Customer</th>
        <th rowspan="2" class="col-proj">Project</th>
        <th rowspan="2" class="col-pn">Part Number</th>
        <th rowspan="2" class="col-name">Part Name</th>
        <th rowspan="2" class="col-num">Total PO</th>
        <th rowspan="2" class="col-num">Stk Awal</th>
        <th rowspan="2" class="col-num">Tot IN</th>
        <th rowspan="2" class="col-num">Tot OUT</th>
        <th rowspan="2" class="col-num">Bal Akhir</th>
        <th rowspan="2" class="col-num">Min</th>
        <th rowspan="2" class="col-num">Safety</th>
        <th rowspan="2" class="col-num">Max</th>
        <th rowspan="2" class="col-status">Status</th>
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
        <td class="col-no">${idx + 1}</td>
        <td class="col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-num">${escapeHtml(n(r.stock_awal))}</td>
        <td class="col-num">${escapeHtml(n(r.total_in))}</td>
        <td class="col-num">${escapeHtml(n(r.total_out))}</td>
        <td class="col-num">${escapeHtml(n(r.balance_akhir))}</td>
        <td class="col-num">${escapeHtml(n(r.level_min))}</td>
        <td class="col-num">${escapeHtml(n(r.level_safety))}</td>
        <td class="col-num">${escapeHtml(n(r.level_max))}</td>

        <td class="col-status">
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
      tabKey: 'mip',
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
      return `<span class="tag tag-red" style="padding: 2px 4px; font-size: 9px; border-radius: 2px;">Problem</span>`;
    }

    if (val === 'over'){
      return `<span class="tag tag-amber" style="padding: 2px 4px; font-size: 9px; border-radius: 2px;">Over</span>`;
    }

    return `<span class="tag tag-green" style="padding: 2px 4px; font-size: 9px; border-radius: 2px;">Aman</span>`;
  }

  function buildFGHeader(daysInMonth){
    const thead = document.getElementById('fgThead');

    let row1 = `
      <tr>
        <th rowspan="3" class="col-no">No</th>
        <th rowspan="3" class="col-cust">Customer</th>
        <th rowspan="3" class="col-proj">Project</th>
        <th rowspan="3" class="col-pn">Part Number</th>
        <th rowspan="3" class="col-name">Part Name</th>
        <th rowspan="3" class="col-num">Total PO</th>
        <th rowspan="3" class="col-num">Adv Del</th>
        <th rowspan="3" class="col-num">Outst</th>
        <th rowspan="3" class="col-num">% Del</th>
        <th rowspan="3" class="col-num">Stk Awal</th>
        <th rowspan="3" class="col-num">Tot IN</th>
        <th rowspan="3" class="col-num">Tot OUT</th>
        <th rowspan="3" class="col-num">Min</th>
        <th rowspan="3" class="col-num">Safety</th>
        <th rowspan="3" class="col-num">Max</th>
        <th rowspan="3" class="col-num">Stk Hand</th>
        <th rowspan="3" class="col-num">Status Stk</th>
        <th rowspan="3" class="col-status">Status</th>
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
        <td class="col-no">${idx + 1}</td>
        <td class="col-cust">${escapeHtml(r.customer ?? '')}</td>
        <td class="col-proj">${escapeHtml(r.project ?? '')}</td>
        <td class="col-pn">${escapeHtml(r.part_number ?? '')}</td>
        <td class="col-name" title="${escapeHtml(r.part_name ?? '')}">${escapeHtml(r.part_name ?? '')}</td>
        <td class="col-num">${escapeHtml(n(r.total_po))}</td>
        <td class="col-num">${escapeHtml(n(r.advance_delivery))}</td>
        <td class="col-num">${escapeHtml(n(r.outstanding))}</td>
        <td class="col-num">${escapeHtml(n(r.percentage))}%</td>
        <td class="col-num">${escapeHtml(n(r.stock_awal))}</td>
        <td class="col-num">${escapeHtml(n(r.total_in))}</td>
        <td class="col-num">${escapeHtml(n(r.total_out))}</td>
        <td class="col-num">${escapeHtml(n(r.level_min))}</td>
        <td class="col-num">${escapeHtml(n(r.level_safety))}</td>
        <td class="col-num">${escapeHtml(n(r.level_max))}</td>
        <td class="col-num">${escapeHtml(n(r.stock_on_hand))}</td>
        <td class="col-num">${statusStockBadge(r.status_stock)}</td>

        <td class="col-status">
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
      tabKey: 'fg',
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

    startAutoRotate();
  }

  function reflowVisibleTables(){
    const activeTab = getActiveTabKey();
    if (activeTab === 'subassy'){
      fixHeaderStickyTops('subassyTable');
      highlightTodayGeneric('subassyTable', state.subassy.todayDay);
      autoScaleTabTable('subassy');
    } else if (activeTab === 'mip'){
      fixHeaderStickyTops('mipTable');
      highlightTodayGeneric('mipTable', state.mip.todayDay);
      autoScaleTabTable('mip');
    } else if (activeTab === 'fg'){
      fixHeaderStickyTops('fgTable');
      highlightTodayGeneric('fgTable', state.fg.todayDay);
      autoScaleTabTable('fg');
    }
  }

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