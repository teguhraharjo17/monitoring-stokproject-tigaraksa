<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>TV Display - Monitoring Stok</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --bg:#0b1220;
      --panel:#0f1a33;
      --line:#223055;
      --text:#eaf0ff;
      --muted:#a9b4d0;
      --head:#122042;
      --today:#ff3b3b;

      --zoom: 0.80;
      --auto-fit: 1;
      --design-w: 1920;
      --design-h: 1080;

      --topbar-h: 92px;
      --wrap-pad: 14px;

      --tbl-font: clamp(13px, 0.85vw, 13px);
      --tbl-pad-y: 6px;
      --tbl-pad-x: 8px;

      --tbl-pill-font: clamp(10px, 0.82vw, 12px);
      --tbl-pill-pad-y: 6px;
      --tbl-pill-pad-x: 6px;
      --tbl-pill-gap: 6px;

      --tbl-legend-font: clamp(10px, 0.82vw, 12px);
      --tbl-legend-pad-y: 6px;
      --tbl-legend-pad-x: 6px;
      --tbl-legend-gap: 6px;

      --w-no: 54px;
      --w-cust: 150px;
      --w-proj: 130px;
      --w-pn: 190px;
      --w-name: 240px;
      --w-status: 120px;
      --w-num: 120px;
      --w-date-min: 96px;
      --w-date-max: 130px;
    }

    html, body { height: 100%; }

    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family:system-ui, Segoe UI, Roboto, Arial, sans-serif;
      overflow:hidden;
    }

    .scale-stage{
      position: fixed;
      inset: 0;
      overflow: hidden;
      background: var(--bg);
    }

    .scale-root{
      position: absolute;
      top: 0;
      left: 0;
      transform-origin: top left;
      transform: scale(var(--zoom));
      width: calc(100% / var(--zoom));
      height: calc(100% / var(--zoom));
    }

    @supports (zoom: 1){
      .scale-root{
        transform:none;
        width:100%;
        height:100%;
        zoom: var(--zoom);
      }
    }

    .app{
      height:100%;
      display:flex;
      flex-direction:column;
      min-height:0;
    }

    .topbar{
      height: var(--topbar-h);
      padding: 14px 18px;
      border-bottom: 1px solid var(--line);
      background: rgba(15, 26, 51, .95);
      position: sticky;
      top: 0;
      z-index: 300;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 12px;
    }

    .brand{
      display:flex;
      align-items:center;
      gap:12px;
      min-width:0;
    }

    .brand-logo{
      height:64px;
      width:auto;
      object-fit:contain;
      display:block;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.35));
    }

    @media (max-width: 768px){
      .brand-logo{ height:34px; }
    }

    .title{
      font-weight:900;
      letter-spacing:.3px;
      font-size: clamp(16px, 1.6vw, 22px);
    }

    .meta{
      color:var(--muted);
      font-size: clamp(12px, 1.1vw, 14px);
    }

    .badge-soft{
      background: rgba(255,255,255,.08);
      border:1px solid var(--line);
    }

    code{ color:#ffd0db; }

    .wrap{
      flex:1;
      min-height:0;
      padding: var(--wrap-pad) 18px;
    }

    .panel{
      height:100%;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow:hidden;
      display:flex;
      flex-direction:column;
      min-height:0;
    }

    .tv-tabs{ flex:0 0 auto; }

    .tv-tabs .nav-link{
      color:var(--muted);
      font-weight:900;
      letter-spacing:.2px;
    }

    .tv-tabs .nav-link.active{
      color:var(--text);
      background: rgba(255,255,255,.06);
      border-color: var(--line);
    }

    .tab-content{
      flex:1;
      min-height:0;
    }

    .tab-pane{
      height:100%;
      min-height:0;
    }

    .table-wrap{
      height:100%;
      overflow:auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
      background: rgba(255,255,255,.02);
    }

    .table-wrap::-webkit-scrollbar{ height:12px; width:12px; }
    .table-wrap::-webkit-scrollbar-thumb{
      background: rgba(255,255,255,.18);
      border-radius:10px;
    }

    table{
      width:max-content;
      min-width:100%;
      border-collapse:separate;
      border-spacing:0;
      table-layout:auto;
    }

    .table-compact th,
    .table-compact td{
      border-right:1px solid var(--line);
      border-bottom:1px solid var(--line);
      padding: var(--tbl-pad-y) var(--tbl-pad-x);
      text-align:center;
      white-space:nowrap;
      font-size: var(--tbl-font);
      background: rgba(255,255,255,.02);
      vertical-align:middle;
    }

    .table-compact thead th{
      background: var(--head);
      box-shadow: inset 0 -1px 0 var(--line);
    }

    .sticky-1,.sticky-2,.sticky-3,.sticky-4,.sticky-5,.sticky-6{
      position: sticky;
      left: 0;
      z-index: 120;
      background: var(--head) !important;
      box-shadow: 1px 0 0 var(--line);
    }

    .table-compact td.sticky-1,
    .table-compact td.sticky-2,
    .table-compact td.sticky-3,
    .table-compact td.sticky-4,
    .table-compact td.sticky-5,
    .table-compact td.sticky-6{
      background:#101b36 !important;
      z-index: 90;
    }

    .table-compact tbody tr:nth-child(odd) td{
      background: rgba(255,255,255,.03);
    }

    .table-compact tbody tr:hover td{
      background: rgba(255,255,255,.07);
    }

    .table-compact tbody tr:hover td.sticky-1,
    .table-compact tbody tr:hover td.sticky-2,
    .table-compact tbody tr:hover td.sticky-3,
    .table-compact tbody tr:hover td.sticky-4,
    .table-compact tbody tr:hover td.sticky-5,
    .table-compact tbody tr:hover td.sticky-6{
      background:#14224a !important;
    }

    .cell-stack{
      display:flex;
      flex-direction:column;
      gap: var(--tbl-pill-gap);
      width:100%;
    }

    .pill{
      border-radius:8px;
      padding: var(--tbl-pill-pad-y) var(--tbl-pill-pad-x);
      font-weight:900;
      line-height:1;
      border:1px solid transparent;
      font-size: var(--tbl-pill-font);
    }

    .pill-spk{ background:#3a1220; border-color:#6b1f3a; color:#ffd0db; }
    .pill-prod{ background:#0f2a1b; border-color:#1c6b3a; color:#bff7d2; }
    .pill-wip{ background:#0f2236; border-color:#1d4f8a; color:#cce5ff; }

    .legend{
      display:flex;
      flex-direction:column;
      gap: var(--tbl-legend-gap);
      align-items:stretch;
    }

    .legend .tag{
      border-radius:8px;
      padding: var(--tbl-legend-pad-y) var(--tbl-legend-pad-x);
      font-weight:900;
      line-height:1;
      border:1px solid transparent;
      font-size: var(--tbl-legend-font);
    }

    .tag-spk{ background:#3a1220; border-color:#6b1f3a; color:#ffd0db; }
    .tag-prod{ background:#0f2a1b; border-color:#1c6b3a; color:#bff7d2; }
    .tag-wip{ background:#0f2236; border-color:#1d4f8a; color:#cce5ff; }

    .w-no{ width:var(--w-no); min-width:var(--w-no); }
    .w-cust{ width:var(--w-cust); min-width:var(--w-cust); }
    .w-proj{ width:var(--w-proj); min-width:var(--w-proj); }
    .w-pn{ width:var(--w-pn); min-width:var(--w-pn); }
    .w-name{ width:var(--w-name); min-width:var(--w-name); }
    .w-status{ width:var(--w-status); min-width:var(--w-status); }
    .w-num{ width:var(--w-num); min-width:var(--w-num); }
    .w-date{
      width: clamp(var(--w-date-min), 6vw, var(--w-date-max));
      min-width: clamp(var(--w-date-min), 6vw, var(--w-date-max));
    }

    .today-col{
      outline:3px solid var(--today);
      outline-offset:-3px;
    }

    .prod-low{ color:#ff6b6b; font-weight:900; }
    .prod-ok{ color:#9dffb1; font-weight:900; }

    .loading{
      padding:18px;
      color:var(--muted);
    }

    .errorbox{
      padding:18px;
      color:#ff9a9a;
      white-space:normal;
    }

    .emptybox{
      padding:18px;
      color:var(--muted);
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
          <span class="badge badge-soft" id="clock">--:--:--</span>
          <button class="btn btn-sm btn-outline-light" id="btnReload" type="button">Refresh</button>
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
    applyZoom(clamp(fit, 0.55, 0.95));
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
      return `<span class="tag" style="background:#3a2f12;border-color:#6b5a1f;color:#ffe8b3;">Over</span>`;
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

  document.querySelectorAll('#tvTabs .nav-link').forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => {
      updateActiveMeta();
      requestAnimationFrame(reflowVisibleTables);
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