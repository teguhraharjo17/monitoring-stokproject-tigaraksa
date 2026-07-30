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

  <style>
    :root{
      --bg: #070a13;
      --panel: #0d1527;
      --line: #1e293b;
      --text: #f8fafc;
      --muted: #94a3b8;
      --head: #1e293b;
      --today: #ef4444;

      --zoom: 0.85;
      --auto-fit: 1;
      --design-w: 1920;
      --design-h: 1080;

      --topbar-h: 96px;
      --wrap-pad: 16px;

      --tbl-font: 14px;
      --tbl-pad-y: 16px;
      --tbl-pad-x: 10px;

      --tbl-pill-font: 12px;
      --tbl-pill-pad-y: 5px;
      --tbl-pill-pad-x: 5px;
      --tbl-pill-gap: 5px;

      --tbl-legend-font: 12px;
      --tbl-legend-pad-y: 6px;
      --tbl-legend-pad-x: 12px;
      --tbl-legend-gap: 8px;

      --w-no: 54px;
      --w-cust: 150px;
      --w-proj: 130px;
      --w-pn: 190px;
      --w-name: 240px;
      --w-status: 120px;
      --w-num: 120px;
      --w-date-min: 52px;
      --w-date-max: 60px;
    }

    html, body { height: 100%; }

    body{
      margin: 0;
      background-color: #070a13;
      background: var(--bg);
      color: #f8fafc;
      color: var(--text);
      font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
      overflow: hidden;
    }

    .scale-stage{
      position: fixed;
      inset: 0;
      overflow: hidden;
      background-color: #070a13;
      background: var(--bg);
    }

    .scale-root{
      position: absolute;
      top: 0;
      left: 0;
      transform-origin: top left;
      transform: scale(0.85);
      transform: scale(var(--zoom));
      width: 117%;
      width: calc(100% / var(--zoom));
      height: 117%;
      height: calc(100% / var(--zoom));
    }

    @supports (zoom: 1){
      .scale-root{
        transform: none;
        width: 100%;
        height: 100%;
        zoom: 0.85;
        zoom: var(--zoom);
      }
    }

    .app{
      height: 100%;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .topbar{
      height: 96px;
      height: var(--topbar-h);
      padding: 14px 24px;
      border-bottom: 2px solid #1e293b;
      border-bottom: 2px solid var(--line);
      background-color: #0d1527;
      background: rgba(13, 21, 39, 0.95);
      position: sticky;
      top: 0;
      z-index: 300;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }

    .brand{
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
    }

    .brand-logo{
      height: 64px;
      width: auto;
      object-fit: contain;
      display: block;
      filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.5));
    }

    @media (max-width: 768px){
      .brand-logo{ height: 40px; }
    }

    .title{
      font-weight: 900;
      letter-spacing: 0.5px;
      font-size: 22px;
      font-size: clamp(18px, 1.8vw, 24px);
      color: #f8fafc;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .meta{
      color: #94a3b8;
      color: var(--muted);
      font-size: 13px;
      font-size: clamp(11px, 1.0vw, 14px);
      font-weight: 500;
    }

    .badge-soft{
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid #1e293b;
      border: 1px solid var(--line);
      color: #f8fafc;
    }

    #clock {
      font-family: 'Outfit', monospace;
      font-size: 22px;
      font-weight: 800;
      color: #60a5fa;
      text-shadow: 0 0 10px rgba(96, 165, 250, 0.4);
      background-color: rgba(13, 21, 39, 0.6);
      border: 1px solid #1e293b;
      padding: 6px 14px;
      border-radius: 8px;
      letter-spacing: 1px;
    }

    code{ color: #fda4af; }

    .wrap{
      flex: 1;
      min-height: 0;
      padding: 16px 24px;
      padding: var(--wrap-pad) 24px;
    }

    .panel{
      height: 100%;
      background-color: #0d1527;
      background: var(--panel);
      border: 2px solid #1e293b;
      border: 2px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 0;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    }

    .tv-tabs{
      flex: 0 0 auto;
      background-color: #070a13;
      border-bottom: 2px solid #1e293b;
      padding-top: 10px;
    }

    .tv-tabs .nav-link{
      color: #94a3b8;
      font-weight: 800;
      font-size: 15px;
      letter-spacing: 0.5px;
      border: none;
      padding: 12px 24px;
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
      overscroll-behavior: contain;
      background-color: #070a13;
    }

    .table-wrap::-webkit-scrollbar{ height: 10px; width: 10px; }
    .table-wrap::-webkit-scrollbar-track{ background: #070a13; }
    .table-wrap::-webkit-scrollbar-thumb{
      background: #1e293b;
      border-radius: 6px;
      border: 2px solid #070a13;
    }
    .table-wrap::-webkit-scrollbar-thumb:hover{ background: #334155; }

    table{
      width: max-content;
      min-width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      table-layout: auto;
    }

    .table-compact th,
    .table-compact td{
      border-right: 1px solid #1e293b;
      border-right: 1px solid var(--line);
      border-bottom: 1px solid #1e293b;
      border-bottom: 1px solid var(--line);
      padding: 8px 10px;
      padding: var(--tbl-pad-y) var(--tbl-pad-x);
      text-align: center;
      white-space: nowrap;
      font-size: 13px;
      font-size: var(--tbl-font);
      background-color: #0d1527;
      vertical-align: middle;
      color: #f8fafc;
    }

    .table-compact thead th{
      background-color: #1e293b !important;
      color: #94a3b8;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-size: 12px;
      border-bottom: 2px solid #334155;
    }

    th.sticky-1, th.sticky-2, th.sticky-3, th.sticky-4, th.sticky-5, th.sticky-6 {
      position: sticky !important;
      left: 0;
      z-index: 120;
      background-color: #1e293b !important;
      background: #1e293b !important;
      box-shadow: 2px 0 5px rgba(0,0,0,0.3) !important;
    }

    td.sticky-1, td.sticky-2, td.sticky-3, td.sticky-4, td.sticky-5, td.sticky-6 {
      position: sticky !important;
      left: 0;
      z-index: 90;
      background-color: #0f172a !important;
      background: #0f172a !important;
      box-shadow: 2px 0 5px rgba(0,0,0,0.3) !important;
    }

    .table-compact tbody tr:nth-child(odd) td{
      background-color: #0b0f19;
    }

    .table-compact tbody tr:hover td{
      background-color: #1e293b !important;
    }

    .table-compact tbody tr:hover td.sticky-1,
    .table-compact tbody tr:hover td.sticky-2,
    .table-compact tbody tr:hover td.sticky-3,
    .table-compact tbody tr:hover td.sticky-4,
    .table-compact tbody tr:hover td.sticky-5,
    .table-compact tbody tr:hover td.sticky-6{
      background-color: #1e293b !important;
    }

    .cell-stack{
      display: flex;
      flex-direction: column;
      gap: 5px;
      gap: var(--tbl-pill-gap);
      width: 100%;
    }

    .pill{
      border-radius: 4px;
      padding: 6px 8px;
      padding: var(--tbl-pill-pad-y) var(--tbl-pill-pad-x);
      font-weight: 900;
      line-height: 1.2;
      border: 1px solid transparent;
      font-size: 13px;
      font-size: var(--tbl-pill-font);
      text-align: center;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
    }

    .pill-spk{
      background-color: #dc2626 !important;
      border-color: #b91c1c !important;
      color: #ffffff !important;
    }
    .pill-prod{
      background-color: #16a34a !important;
      border-color: #15803d !important;
      color: #ffffff !important;
    }
    .pill-wip{
      background-color: #2563eb !important;
      border-color: #1d4ed8 !important;
      color: #ffffff !important;
    }

    .legend{
      display: flex;
      flex-direction: column;
      gap: 5px;
      gap: var(--tbl-legend-gap);
      align-items: stretch;
    }

    .legend .tag{
      border-radius: 4px;
      padding: 6px 8px;
      padding: var(--tbl-legend-pad-y) var(--tbl-legend-pad-x);
      font-weight: 900;
      line-height: 1.2;
      border: 1px solid transparent;
      font-size: 12px;
      font-size: var(--tbl-legend-font);
      text-align: center;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
    }

    .tag-spk{
      background-color: #dc2626 !important;
      border-color: #b91c1c !important;
      color: #ffffff !important;
    }
    .tag-prod{
      background-color: #16a34a !important;
      border-color: #15803d !important;
      color: #ffffff !important;
    }
    .tag-wip{
      background-color: #2563eb !important;
      border-color: #1d4ed8 !important;
      color: #ffffff !important;
    }

    .w-no{ width: 54px; min-width: 54px; }
    .w-cust{ width: 150px; min-width: 150px; }
    .w-proj{ width: 130px; min-width: 130px; }
    .w-pn{ width: 190px; min-width: 190px; }
    .w-name{ width: 240px; min-width: 240px; }
    .w-status{ width: 120px; min-width: 120px; }
    .w-num{ width: 120px; min-width: 120px; }
    .table-compact td.w-num {
      font-size: 15px !important;
      color: #ffffff !important;
      font-weight: 900 !important;
    }
    .table-compact td.w-cust,
    .table-compact td.w-proj,
    .table-compact td.w-pn,
    .table-compact td.w-name {
      font-weight: 700 !important;
      color: #ffffff !important;
    }
    .table-compact td.w-date {
      padding-left: 2px !important;
      padding-right: 2px !important;
    }
    .table-compact th.sticky-6,
    .table-compact td.sticky-6 {
      border-right: 4px solid #334155 !important;
    }
    .w-date{
      width: clamp(var(--w-date-min), 3vw, var(--w-date-max));
      min-width: clamp(var(--w-date-min), 3vw, var(--w-date-max));
    }

    .today-col{
      outline: 2px solid #ef4444 !important;
      outline-offset: -2px;
      background-color: rgba(239, 68, 68, 0.05) !important;
    }

    .prod-low{ color: #f87171; font-weight: 800; }
    .prod-ok{ color: #34d399; font-weight: 800; }

    .loading{
      padding: 24px;
      color: #94a3b8;
      font-size: 16px;
      font-weight: 500;
    }

    .errorbox{
      padding: 24px;
      color: #f87171;
      white-space: normal;
      font-weight: 500;
    }

    .emptybox{
      padding: 24px;
      color: #94a3b8;
      font-weight: 500;
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
            <div class="title">TV Display - Monitoring Stok</div>
            <div class="meta" id="metaText">Memuat data...</div>
          </div>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-sm btn-outline-info active" id="btnAutoRotate" type="button" style="font-weight: 700; border-radius: 6px; font-size: 13px;">🔄 Rotate: ON</button>
          <button class="btn btn-sm btn-outline-info active" id="btnAutoScroll" type="button" style="font-weight: 700; border-radius: 6px; font-size: 13px;">📜 Scroll: ON</button>
          <span id="clock">--:--:--</span>
          <button class="btn btn-sm btn-outline-light" id="btnReload" type="button" style="font-weight: 700; border-radius: 6px; font-size: 13px;">Refresh</button>
        </div>
      </div>

      <div class="wrap">
        <div class="panel">

          <ul class="nav nav-tabs tv-tabs px-3 pt-3" role="tablist" id="tvTabs">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-subassy" data-bs-toggle="tab" data-bs-target="#pane-subassy" type="button" role="tab">Sub Assy</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-mip" data-bs-toggle="tab" data-bs-target="#pane-mip" type="button" role="tab">MIP</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-fg" data-bs-toggle="tab" data-bs-target="#pane-fg" type="button" role="tab">Finish Goods</button>
            </li>
          </ul>

          <div class="tab-content">

            <div class="tab-pane fade show active" id="pane-subassy" role="tabpanel">
              <div class="table-wrap" id="subassyWrap">
                <div class="loading" id="subassyLoading">Memuat Sub Assy...</div>
                <table class="table table-dark mb-0 table-compact" id="subassyTable" style="display:none;">
                  <thead id="subassyThead"></thead>
                  <tbody id="subassyTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-mip" role="tabpanel">
              <div class="table-wrap" id="mipWrap">
                <div class="loading" id="mipLoading">Memuat MIP...</div>
                <table class="table table-dark mb-0 table-compact" id="mipTable" style="display:none;">
                  <thead id="mipThead"></thead>
                  <tbody id="mipTbody"></tbody>
                </table>
              </div>
            </div>

            <div class="tab-pane fade" id="pane-fg" role="tabpanel">
              <div class="table-wrap" id="fgWrap">
                <div class="loading" id="fgLoading">Memuat Finish Goods...</div>
                <table class="table table-dark mb-0 table-compact" id="fgTable" style="display:none;">
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

  function clamp(n,min,max){
    return Math.min(max, Math.max(min,n));
  }

  function applyZoom(value){
    document.documentElement.style.setProperty('--zoom', String(value));
  }

  function getQueryParam(name){
    return new URLSearchParams(location.search).get(name);
  }

  function autoFitZoom(){
    const autoFit = getComputedStyle(document.documentElement).getPropertyValue('--auto-fit').trim();
    if (autoFit !== '1') return;

    const designW = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--design-w')) || 1920;
    const designH = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--design-h')) || 1080;

    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const fit = Math.min(vw / designW, vh / designH);
    applyZoom(clamp(fit, 0.6, 1.05));
  }

  (function initZoom(){
    const q = getQueryParam('zoom');
    if (q){
      document.documentElement.style.setProperty('--auto-fit', '0');
      applyZoom(clamp(parseFloat(q), 0.45, 1.0));
    } else {
      autoFitZoom();
    }
  })();

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

  function applyStickyOffsetsFor(tableId, stickyClasses){
    const table = document.getElementById(tableId);
    if (!table || table.style.display === 'none') return;

    const headRow = table.querySelector('thead tr');
    if (!headRow) return;

    let left = 0;

    stickyClasses.forEach((cls, index) => {
      const th = headRow.querySelector(`th.${cls}`);
      if (!th) return;

      const width = th.getBoundingClientRect().width;
      const zBaseHead = 600 - index;
      const zBaseBody = 300 - index;

      table.querySelectorAll(`th.${cls}`).forEach(el => {
        el.style.left = `${left}px`;
        el.style.zIndex = String(zBaseHead);
      });

      table.querySelectorAll(`td.${cls}`).forEach(el => {
        el.style.left = `${left}px`;
        el.style.zIndex = String(zBaseBody);
      });

      left += width;
    });
  }

  function freezeTheadAllRows(tableId){
    const table = document.getElementById(tableId);
    if (!table || table.style.display === 'none') return;

    const thead = table.querySelector('thead');
    if (!thead) return;

    const rows = Array.from(thead.querySelectorAll('tr'));
    if (!rows.length) return;

    const rowHeights = rows.map(row => row.getBoundingClientRect().height || 0);
    const rowTops = [];
    let acc = 0;

    for (let i = 0; i < rowHeights.length; i++){
      rowTops.push(acc);
      acc += rowHeights[i];
    }

    rows.forEach((row, rIndex) => {
      const ths = Array.from(row.querySelectorAll('th'));

      ths.forEach(th => {
        th.style.position = 'sticky';
        th.style.top = `${rowTops[rIndex]}px`;

        let z = 200 + (rows.length - rIndex);
        if ([...th.classList].some(c => c.startsWith('sticky-'))) {
          z = 700 + (rows.length - rIndex);
        }

        th.style.zIndex = String(z);
        th.style.background = 'var(--head)';
        th.style.backgroundClip = 'padding-box';

        const span = parseInt(th.getAttribute('rowspan') || '1', 10);
        if (span > 1){
          const totalHeight = rowHeights.slice(rIndex, rIndex + span).reduce((a,b) => a + b, 0);
          if (totalHeight > 0){
            th.style.height = `${totalHeight}px`;
          }
          th.style.verticalAlign = 'middle';
        }
      });
    });
  }

  function highlightTodayGeneric(tableId, todayDay){
    document.querySelectorAll(`#${tableId} [data-day]`).forEach(el => el.classList.remove('today-col'));
    if (!todayDay) return;

    document.querySelectorAll(`#${tableId} th[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
    document.querySelectorAll(`#${tableId} td.day-cell[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
  }

  function focusTodayGeneric(wrapId, tableId, stickyLastSelector, todayDay){
    if (!todayDay) return;

    const wrap = document.getElementById(wrapId);
    const target = document.querySelector(`#${tableId} th[data-day="${todayDay}"]`);
    if (!wrap || !target) return;

    const stickyLast = document.querySelector(stickyLastSelector);
    const stickyWidth = stickyLast ? (stickyLast.offsetLeft + stickyLast.offsetWidth) : 0;
    const margin = 24;

    wrap.scrollLeft = Math.max(0, target.offsetLeft - stickyWidth - margin);
  }

  let autoRotateEnabled = true;
  let rotateTimer = null;
  const ROTATE_INTERVAL = 25000; // 25 detik per tab

  let autoScrollEnabled = true;
  let scrollTimers = {};

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

  function runAutoScroll(tabKey) {
    if (scrollTimers[tabKey]) {
      clearInterval(scrollTimers[tabKey]);
      delete scrollTimers[tabKey];
    }

    const wrapId = `${tabKey}Wrap`;
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;

    wrap.scrollTop = 0;
    let pauseCounter = 0;

    scrollTimers[tabKey] = setInterval(() => {
      if (!autoScrollEnabled || getActiveTabKey() !== tabKey) return;

      const maxScroll = wrap.scrollHeight - wrap.clientHeight;
      if (maxScroll <= 0) return;

      if (pauseCounter > 0) {
        pauseCounter--;
        return;
      }

      if (wrap.scrollTop >= maxScroll) {
        // Berhenti sebentar 6 detik (60 * 100ms) di dasar tabel sebelum kembali ke atas
        pauseCounter = 60;
        wrap.scrollTop = 0;
        return;
      }

      wrap.scrollTop += 1;
    }, 60); // Kecepatan scroll perlahan
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
    updateActiveMetaRaw();
  }

  function updateActiveMetaRaw(){
    updateMetaTextByTab(getActiveTabKey());
  }

  function postRenderTable({ tableId, wrapId, stickyLastSelector, todayDay, restoreScrollTop = true, keepScrollTop = 0 }){
    const wrap = document.getElementById(wrapId);

    requestAnimationFrame(() => {
      if (wrap && restoreScrollTop){
        wrap.scrollTop = keepScrollTop;
      }

      freezeTheadAllRows(tableId);
      highlightTodayGeneric(tableId, todayDay);

      requestAnimationFrame(() => {
        applyStickyOffsetsFor(tableId, ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
        focusTodayGeneric(wrapId, tableId, stickyLastSelector, todayDay);
      });
    });
  }
</script>

<script>
  function buildHeader(daysInMonth){
    const thead = document.getElementById('subassyThead');

    let row1 = `
      <tr>
        <th class="w-no sticky-1">No</th>
        <th class="w-cust sticky-2">Customer</th>
        <th class="w-proj sticky-3">Project</th>
        <th class="w-pn sticky-4">Part Number</th>
        <th class="w-name sticky-5">Part Name</th>
        <th class="w-status sticky-6">Status</th>
        <th class="w-num">Total PO</th>
        <th class="w-num">WIP Sebelumnya</th>
        <th class="w-num">Total SPK</th>
        <th class="w-num">Total Produksi</th>
        <th class="w-num">WIP Akhir</th>
        <th class="w-num">Produktivitas</th>
        <th colspan="${daysInMonth}">Tanggal</th>
      </tr>
    `;

    let row2 = `
      <tr>
        <th class="w-no sticky-1"></th>
        <th class="w-cust sticky-2"></th>
        <th class="w-proj sticky-3"></th>
        <th class="w-pn sticky-4"></th>
        <th class="w-name sticky-5"></th>
        <th class="w-status sticky-6"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
    `;

    for (let i=1; i<=daysInMonth; i++){
      row2 += `<th class="w-date" data-day="${i}">${i}</th>`;
    }

    row2 += `</tr>`;
    thead.innerHTML = row1 + row2;
  }

  function buildBody(rows, daysInMonth){
    const tbody = document.getElementById('subassyTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="w-no sticky-1">${idx+1}</td>
        <td class="w-cust sticky-2">${escapeHtml(r.customer ?? '')}</td>
        <td class="w-proj sticky-3">${escapeHtml(r.project ?? '')}</td>
        <td class="w-pn sticky-4">${escapeHtml(r.part_number ?? '')}</td>
        <td class="w-name sticky-5">${escapeHtml(r.part_name ?? '')}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-spk">SPK</div>
            <div class="tag tag-prod">Produksi</div>
            <div class="tag tag-wip">WIP</div>
          </div>
        </td>

        <td class="w-num"><b>${escapeHtml(n(r.total_po))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.wip_sebelumnya))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_spk))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_produksi))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.wip_akhir))}</b></td>
        <td class="w-num">${prodBadge(r.produktivitas)}</td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const spk  = n(r.days?.spk?.[d]);
        const prod = n(r.days?.produksi?.[d]);
        const wip  = n(r.days?.wip?.[d]);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-spk">${escapeHtml(spk)}</div>
              <div class="pill pill-prod">${escapeHtml(prod)}</div>
              <div class="pill pill-wip">${escapeHtml(wip)}</div>
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
    const wrap = document.getElementById('subassyWrap');
    const keepScrollTop = wrap ? wrap.scrollTop : 0;

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

    buildHeader(json.daysInMonth);
    buildBody(json.rows, json.daysInMonth);

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
      wrapId: 'subassyWrap',
      stickyLastSelector: '#subassyTable thead th.sticky-6',
      todayDay: state.subassy.todayDay,
      restoreScrollTop: true,
      keepScrollTop
    });

    if (getActiveTabKey() === 'subassy') {
      runAutoScroll('subassy');
    }
  }
</script>

<script>
  function buildMIPHeader(daysInMonth){
    const thead = document.getElementById('mipThead');

    let row1 = `
      <tr>
        <th class="w-no sticky-1">No</th>
        <th class="w-cust sticky-2">Customer</th>
        <th class="w-proj sticky-3">Project</th>
        <th class="w-pn sticky-4">Part Number</th>
        <th class="w-name sticky-5">Part Name</th>
        <th class="w-status sticky-6">Status</th>

        <th class="w-num">Stock Awal</th>
        <th class="w-num">Total IN</th>
        <th class="w-num">Total OUT</th>
        <th class="w-num">Balance Akhir</th>

        <th class="w-num">Level Min</th>
        <th class="w-num">Safety</th>
        <th class="w-num">Level Max</th>

        <th colspan="${daysInMonth}">Tanggal</th>
      </tr>
    `;

    let row2 = `
      <tr>
        <th class="w-no sticky-1"></th>
        <th class="w-cust sticky-2"></th>
        <th class="w-proj sticky-3"></th>
        <th class="w-pn sticky-4"></th>
        <th class="w-name sticky-5"></th>
        <th class="w-status sticky-6"></th>

        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>

        <th class="w-num"></th>
        <th class="w-num"></th>
        <th class="w-num"></th>
    `;

    for (let i=1; i<=daysInMonth; i++){
      row2 += `<th class="w-date" data-day="${i}">${i}</th>`;
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
        <td class="w-no sticky-1">${idx+1}</td>
        <td class="w-cust sticky-2">${escapeHtml(r.customer ?? '')}</td>
        <td class="w-proj sticky-3">${escapeHtml(r.project ?? '')}</td>
        <td class="w-pn sticky-4">${escapeHtml(r.part_number ?? '')}</td>
        <td class="w-name sticky-5">${escapeHtml(r.part_name ?? '')}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-prod">IN</div>
            <div class="tag tag-spk">OUT</div>
            <div class="tag tag-wip">BAL</div>
          </div>
        </td>

        <td class="w-num"><b>${escapeHtml(n(r.stock_awal))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_in))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_out))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.balance_akhir))}</b></td>

        <td class="w-num"><b>${escapeHtml(n(r.level_min))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.level_safety))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.level_max))}</b></td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const inQty  = n(r.days?.in?.[d]);
        const outQty = n(r.days?.out?.[d]);
        const bal    = n(r.days?.balance?.[d]);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${escapeHtml(inQty)}</div>
              <div class="pill pill-spk">${escapeHtml(outQty)}</div>
              <div class="pill pill-wip">${escapeHtml(bal)}</div>
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
    const wrap = document.getElementById('mipWrap');
    const keepScrollTop = wrap ? wrap.scrollTop : 0;

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
      wrapId: 'mipWrap',
      stickyLastSelector: '#mipTable thead th.sticky-6',
      todayDay: state.mip.todayDay,
      restoreScrollTop: true,
      keepScrollTop
    });

    if (getActiveTabKey() === 'mip') {
      runAutoScroll('mip');
    }
  }
</script>

<script>
  function buildFGHeader(daysInMonth){
    const thead = document.getElementById('fgThead');

    let row1 = `
      <tr>
        <th class="w-no sticky-1" rowspan="2">No</th>
        <th class="w-cust sticky-2" rowspan="2">Customer</th>
        <th class="w-proj sticky-3" rowspan="2">Project</th>
        <th class="w-pn sticky-4" rowspan="2">Part Number</th>
        <th class="w-name sticky-5" rowspan="2">Part Name</th>
        <th class="w-status sticky-6" rowspan="2">Status</th>

        <th class="w-num" rowspan="2">Total PO</th>
        <th class="w-num" rowspan="2">Advance Delivery</th>
        <th class="w-num" rowspan="2">Outstanding</th>
        <th class="w-num" rowspan="2">% Delivery</th>

        <th class="w-num" rowspan="2">Stock Awal</th>
        <th class="w-num" colspan="2">Total</th>

        <th class="w-num" colspan="3">Level</th>

        <th class="w-num" rowspan="2">Stock On Hand</th>
        <th class="w-num" rowspan="2">Status Stock</th>

        <th colspan="${daysInMonth * 2}">Tanggal</th>
      </tr>
    `;

    let row2 = `
      <tr>
        <th class="w-num">IN</th>
        <th class="w-num">OUT</th>

        <th class="w-num">Min</th>
        <th class="w-num">Safety</th>
        <th class="w-num">Max</th>
    `;

    for (let i=1; i<=daysInMonth; i++){
      row2 += `<th class="w-date" data-day="${i}" colspan="2">${i}</th>`;
    }

    row2 += `</tr>`;

    let row3 = `
      <tr>
        <th class="sticky-1"></th>
        <th class="sticky-2"></th>
        <th class="sticky-3"></th>
        <th class="sticky-4"></th>
        <th class="sticky-5"></th>
        <th class="sticky-6"></th>

        <th></th><th></th><th></th><th></th>
        <th></th><th></th><th></th>
        <th></th><th></th><th></th>
        <th></th><th></th>
    `;

    for (let i=1; i<=daysInMonth; i++){
      row3 += `<th class="w-date">D</th><th class="w-date">N</th>`;
    }

    row3 += `</tr>`;

    thead.innerHTML = row1 + row2 + row3;
  }

  function statusStockBadge(v){
    const val = String(v || '').toLowerCase();

    if (val === 'problem'){
      return `<span class="tag tag-spk">Problem</span>`;
    }

    if (val === 'over'){
      return `<span class="tag" style="background:#3a2f12;border-color:#6b5a1f;color:#ffe8b3;font-weight:700;border-radius:6px;padding:5px 7px;">Over</span>`;
    }

    return `<span class="tag tag-prod">Aman</span>`;
  }

  function buildFGBody(rows, daysInMonth){
    const tbody = document.getElementById('fgTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="w-no sticky-1">${idx+1}</td>
        <td class="w-cust sticky-2">${escapeHtml(r.customer ?? '')}</td>
        <td class="w-proj sticky-3">${escapeHtml(r.project ?? '')}</td>
        <td class="w-pn sticky-4">${escapeHtml(r.part_number ?? '')}</td>
        <td class="w-name sticky-5">${escapeHtml(r.part_name ?? '')}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-prod">IN</div>
            <div class="tag tag-spk">OUT</div>
            <div class="tag tag-wip">BAL</div>
          </div>
        </td>

        <td class="w-num"><b>${escapeHtml(n(r.total_po))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.advance_delivery))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.outstanding))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.percentage))}%</b></td>

        <td class="w-num"><b>${escapeHtml(n(r.stock_awal))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_in))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.total_out))}</b></td>

        <td class="w-num"><b>${escapeHtml(n(r.level_min))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.level_safety))}</b></td>
        <td class="w-num"><b>${escapeHtml(n(r.level_max))}</b></td>

        <td class="w-num"><b>${escapeHtml(n(r.stock_on_hand))}</b></td>
        <td class="w-num">${statusStockBadge(r.status_stock)}</td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const inD  = n(r.days?.in_d?.[d]);
        const outD = n(r.days?.out_d?.[d]);
        const balD = n(r.days?.bal_d?.[d]);

        const inN  = n(r.days?.in_n?.[d]);
        const outN = n(r.days?.out_n?.[d]);
        const balN = n(r.days?.bal_n?.[d]);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${escapeHtml(inD)}</div>
              <div class="pill pill-spk">${escapeHtml(outD)}</div>
              <div class="pill pill-wip">${escapeHtml(balD)}</div>
            </div>
          </td>
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${escapeHtml(inN)}</div>
              <div class="pill pill-spk">${escapeHtml(outN)}</div>
              <div class="pill pill-wip">${escapeHtml(balN)}</div>
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
    const wrap = document.getElementById('fgWrap');
    const keepScrollTop = wrap ? wrap.scrollTop : 0;

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
      wrapId: 'fgWrap',
      stickyLastSelector: '#fgTable thead th.sticky-6',
      todayDay: state.fg.todayDay,
      restoreScrollTop: true,
      keepScrollTop
    });

    if (getActiveTabKey() === 'fg') {
      runAutoScroll('fg');
    }
  }
</script>

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
    autoFitZoom();

    const activeTab = getActiveTabKey();

    if (activeTab === 'subassy'){
      freezeTheadAllRows('subassyTable');
      applyStickyOffsetsFor('subassyTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
      if (state.subassy.todayDay){
        focusTodayGeneric('subassyWrap', 'subassyTable', '#subassyTable thead th.sticky-6', state.subassy.todayDay);
      }
    }

    if (activeTab === 'mip'){
      freezeTheadAllRows('mipTable');
      applyStickyOffsetsFor('mipTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
      if (state.mip.todayDay){
        focusTodayGeneric('mipWrap', 'mipTable', '#mipTable thead th.sticky-6', state.mip.todayDay);
      }
    }

    if (activeTab === 'fg'){
      freezeTheadAllRows('fgTable');
      applyStickyOffsetsFor('fgTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
      if (state.fg.todayDay){
        focusTodayGeneric('fgWrap', 'fgTable', '#fgTable thead th.sticky-6', state.fg.todayDay);
      }
    }
  }

  document.getElementById('btnReload').addEventListener('click', reloadAll);

  const btnAutoRotate = document.getElementById('btnAutoRotate');
  btnAutoRotate.addEventListener('click', () => {
    autoRotateEnabled = !autoRotateEnabled;
    if (autoRotateEnabled) {
      btnAutoRotate.textContent = '🔄 Rotate: ON';
      btnAutoRotate.classList.remove('btn-outline-secondary');
      btnAutoRotate.classList.add('btn-outline-info', 'active');
      startAutoRotate();
    } else {
      btnAutoRotate.textContent = '🔄 Rotate: OFF';
      btnAutoRotate.classList.remove('btn-outline-info', 'active');
      btnAutoRotate.classList.add('btn-outline-secondary');
      stopAutoRotate();
    }
  });

  const btnAutoScroll = document.getElementById('btnAutoScroll');
  btnAutoScroll.addEventListener('click', () => {
    autoScrollEnabled = !autoScrollEnabled;
    if (autoScrollEnabled) {
      btnAutoScroll.textContent = '📜 Scroll: ON';
      btnAutoScroll.classList.remove('btn-outline-secondary');
      btnAutoScroll.classList.add('btn-outline-info', 'active');
      runAutoScroll(getActiveTabKey());
    } else {
      btnAutoScroll.textContent = '📜 Scroll: OFF';
      btnAutoScroll.classList.remove('btn-outline-info', 'active');
      btnAutoScroll.classList.add('btn-outline-secondary');
    }
  });

  document.querySelectorAll('#tvTabs .nav-link').forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => {
      updateActiveMeta();
      requestAnimationFrame(reflowVisibleTables);
      
      const tabKey = getActiveTabKey();
      runAutoScroll(tabKey);
      
      if (autoRotateEnabled) {
        startAutoRotate();
      }
    });
  });

  reloadAll();

  setInterval(() => {
    loadSubAssy().catch(err => showErrorBox('subassyLoading', 'subassyTable', err));
    loadMIP().catch(err => showErrorBox('mipLoading', 'mipTable', err));
    loadFG().catch(err => showErrorBox('fgLoading', 'fgTable', err));
  }, 1800000);

  let resizeTimer = null;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(reflowVisibleTables, 120);
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