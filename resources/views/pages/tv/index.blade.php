<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TV Display</title>
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

      /* ====== ZOOM OUT (ubah ini) ====== */
      --zoom: 0.85;        /* 0.80 - 0.95 biasanya enak buat TV */
      --topbar-h: 92px;    /* tinggi topbar, buat hitung layout */
      --wrap-pad: 14px;
    }

    html, body { height: 100%; }
    body{
      margin:0;
      background:var(--bg);
      color:var(--text);
      font-family:system-ui, Segoe UI, Roboto, Arial, sans-serif;
      overflow:hidden; /* penting: biar yang scroll hanya table-wrap */
    }

    /* ====== APP LAYOUT: topbar + content flex ====== */
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
      z-index: 200;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 12px;
    }

    .brand{ display:flex; align-items:center; gap:12px; min-width:0; }
    .brand-logo{
      height:64px; width:auto; object-fit:contain; display:block;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.35));
    }
    @media (max-width: 768px){ .brand-logo{ height:34px; } }

    .title{ font-weight:900; letter-spacing:.3px; font-size: clamp(16px, 1.6vw, 22px); }
    .meta{ color:var(--muted); font-size: clamp(12px, 1.1vw, 14px); }
    .badge-soft{ background: rgba(255,255,255,.08); border:1px solid var(--line); }
    code{ color:#ffd0db; }

    /* ====== ZOOM OUT ====== */
    /* Chrome/Edge: zoom paling enak */
    .zoom-root{ zoom: var(--zoom); }
    /* fallback (kalau browser gak support zoom) */
    @supports not (zoom: 1){
      .zoom-root{
        transform: scale(var(--zoom));
        transform-origin: top left;
        width: calc(100% / var(--zoom));
        height: calc(100% / var(--zoom));
      }
    }

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
    .tv-tabs .nav-link{ color:var(--muted); font-weight:900; letter-spacing:.2px; }
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

    /* ====== TABLE WRAP FULL HEIGHT ====== */
    .table-wrap{
      height:100%;
      overflow:auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
      background: rgba(255,255,255,.02);
    }

    .table-wrap::-webkit-scrollbar{ height:12px; width:12px; }
    .table-wrap::-webkit-scrollbar-thumb{ background: rgba(255,255,255,.18); border-radius:10px; }

    table{
      width:max-content;
      min-width:100%;
      border-collapse:separate;
      border-spacing:0;
      table-layout:auto;
    }

    th, td{
      border-right:1px solid var(--line);
      border-bottom:1px solid var(--line);
      padding: 10px 12px;
      text-align:center;
      white-space:nowrap;
      font-size: clamp(12px, 1.1vw, 16px);
      background: rgba(255,255,255,.02);
      vertical-align:middle;
    }

    thead th { background: var(--head); }.sticky-1,.sticky-2,.sticky-3,.sticky-4,.sticky-5,.sticky-6 { z-index: 600; }

    .w-no{ width:64px; min-width:64px; }
    .w-cust{ width:180px; min-width:180px; }
    .w-proj{ width:150px; min-width:150px; }
    .w-pn{ width:220px; min-width:220px; }
    .w-name{ width:280px; min-width:280px; }
    .w-status{ width:140px; min-width:140px; }
    .w-num{ width:140px; min-width:140px; }
    .w-date{ width: clamp(120px, 7vw, 160px); min-width: clamp(120px, 7vw, 160px); }

    /* Sticky left (kolom awal) */
    .sticky-1,.sticky-2,.sticky-3,.sticky-4,.sticky-5,.sticky-6{
      position: sticky;
      left: 0;
      z-index: 120;
      background: var(--head) !important;
    }
    td.sticky-1,td.sticky-2,td.sticky-3,td.sticky-4,td.sticky-5,td.sticky-6{
      background:#101b36 !important;
      z-index: 90;
    }

    tbody tr:nth-child(odd) td{ background: rgba(255,255,255,.03); }
    tbody tr:hover td{ background: rgba(255,255,255,.07); }
    tbody tr:hover td.sticky-1,
    tbody tr:hover td.sticky-2,
    tbody tr:hover td.sticky-3,
    tbody tr:hover td.sticky-4,
    tbody tr:hover td.sticky-5,
    tbody tr:hover td.sticky-6{ background:#14224a !important; }

    .cell-stack{ display:flex; flex-direction:column; gap:10px; width:100%; }
    .pill{
      border-radius:10px;
      padding:10px 10px;
      font-weight:900;
      line-height:1;
      border:1px solid transparent;
      font-size: clamp(12px, 1.0vw, 16px);
    }
    .pill-spk{ background:#3a1220; border-color:#6b1f3a; color:#ffd0db; }
    .pill-prod{ background:#0f2a1b; border-color:#1c6b3a; color:#bff7d2; }
    .pill-wip{ background:#0f2236; border-color:#1d4f8a; color:#cce5ff; }

    .legend{ display:flex; flex-direction:column; gap:10px; align-items:stretch; }
    .legend .tag{
      border-radius:10px;
      padding:10px 10px;
      font-weight:900;
      line-height:1;
      border:1px solid transparent;
      font-size: clamp(12px, 1.0vw, 15px);
    }
    .tag-spk{ background:#3a1220; border-color:#6b1f3a; color:#ffd0db; }
    .tag-prod{ background:#0f2a1b; border-color:#1c6b3a; color:#bff7d2; }
    .tag-wip{ background:#0f2236; border-color:#1d4f8a; color:#cce5ff; }

    .today-col{ outline:4px solid var(--today); outline-offset:-4px; }
    .prod-low{ color:#ff6b6b; font-weight:900; }
    .prod-ok{ color:#9dffb1; font-weight:900; }

    .loading{ padding:18px; color:var(--muted); }
    .errorbox{ padding:18px; color:#ff9a9a; white-space:normal; }
  </style>
</head>
<body>

<div class="app zoom-root">

  <div class="topbar">
    <div class="brand">
      <img
        src="{{ asset('assets/media/logos/logo_milenia_login.png') }}"
        alt="Logo Perusahaan"
        class="brand-logo"
      >
      <div style="min-width:0">
        <div class="title">TV Display - Monitoring Stok</div>
        <div class="meta" id="metaText">Memuat data...</div>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center">
      <span class="badge badge-soft" id="clock">--:--:--</span>
      <button class="btn btn-sm btn-outline-light" id="btnReload">Refresh</button>
    </div>
  </div>

  <div class="wrap">
    <div class="panel">
      <ul class="nav nav-tabs tv-tabs px-3 pt-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-subassy" type="button" role="tab">Sub Assy</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-mip" type="button" role="tab">MIP</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-fg" type="button" role="tab">Finish Goods</button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="pane-subassy" role="tabpanel">
          <div class="table-wrap" id="subassyWrap">
            <div class="loading" id="subassyLoading">Memuat Sub Assy...</div>
            <table class="table table-dark mb-0" id="subassyTable" style="display:none;">
              <thead id="subassyThead"></thead>
              <tbody id="subassyTbody"></tbody>
            </table>
          </div>
        </div>

        @include('pages.tv._mip')
        @include('pages.tv._fg')
      </div>
    </div>
  </div>

</div><!-- /app -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const subassyUrl = @json(route('tv.data.subassy'));
  const mipUrl = @json(route('tv.data.mip'));
  const fgUrl = @json(route('tv.data.fg'));

  function pad(n){ return String(n).padStart(2,'0'); }
  function tickClock(){
    const d = new Date();
    document.getElementById('clock').textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }
  setInterval(tickClock, 1000); tickClock();

  function prodBadge(p){
    const val = parseInt(p || 0, 10);
    return val < 100 ? `<span class="prod-low">${val}%</span>` : `<span class="prod-ok">${val}%</span>`;
  }

  function getQueryUrl(baseUrl){
    const url = new URL(baseUrl, window.location.origin);
    const params = new URLSearchParams(window.location.search);
    if (params.get('bulan')) url.searchParams.set('bulan', params.get('bulan'));
    if (params.get('tahun')) url.searchParams.set('tahun', params.get('tahun'));
    if (params.get('customer')) url.searchParams.set('customer', params.get('customer'));
    return url.toString();
  }

  function applyStickyOffsetsFor(tableId, stickyClasses){
    const table = document.getElementById(tableId);
    if (!table) return;
    const headRow = table.querySelector('thead tr');
    if (!headRow) return;

    let left = 0;
    stickyClasses.forEach(cls => {
      const th = headRow.querySelector(`th.${cls}`);
      if (!th) return;
      const w = th.offsetWidth;
      table.querySelectorAll(`th.${cls}, td.${cls}`).forEach(el => el.style.left = left + 'px');
      left += w;
    });
  }

  /* ====== FREEZE HEADER SEMUA BARIS THEAD ======
     - bikin tiap th sticky + top offset per baris
     - otomatis aman untuk thead 2 baris, 3 baris, dst
  */
    function freezeTheadAllRows(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const thead = table.querySelector('thead');
        if (!thead) return;

        const rows = Array.from(thead.querySelectorAll('tr'));
        if (!rows.length) return;

        // ambil tinggi tiap baris (REAL height)
        const rowHeights = rows.map(r => r.offsetHeight || 0);

        // cumulative top per baris
        const rowTops = [];
        let acc = 0;
        for (let i = 0; i < rowHeights.length; i++) {
            rowTops.push(acc);
            acc += rowHeights[i];
        }

        // apply sticky per TH, handle rowspan
        rows.forEach((row, rIndex) => {
            const ths = Array.from(row.querySelectorAll('th'));
            ths.forEach(th => {
            th.style.position = 'sticky';
            th.style.top = rowTops[rIndex] + 'px';

            // z-index: makin atas makin tinggi
            let z = 200 + (rows.length - rIndex);

            // kalau sticky-left (sticky-1 dst) harus paling atas di intersection
            if ([...th.classList].some(c => c.startsWith('sticky-'))) {
                z = 500 + (rows.length - rIndex);
            }
            th.style.zIndex = String(z);

            // handle rowspan: paksa height = total tinggi baris yang di-span
            const span = parseInt(th.getAttribute('rowspan') || '1', 10);
            if (span > 1) {
                const total = rowHeights.slice(rIndex, rIndex + span).reduce((a,b) => a + b, 0);
                if (total > 0) th.style.height = total + 'px';
                th.style.verticalAlign = 'middle';
            }

            // pastikan background kebaca saat sticky
            th.style.background = getComputedStyle(th).backgroundColor || 'var(--head)';
            th.style.backgroundClip = 'padding-box';
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

    const margin = 32;
    const targetLeft = target.offsetLeft;
    const desired = Math.max(0, targetLeft - stickyWidth - margin);
    wrap.scrollLeft = desired;
  }

  const autoScrollState = {};
  function startAutoScroll(wrapId){
    stopAutoScroll(wrapId);
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;

    const stepPx = 2;
    const intervalMs = 40;
    const pauseAtLoopMs = 700;

    autoScrollState[wrapId] = setInterval(() => {
      const maxScrollTop = wrap.scrollHeight - wrap.clientHeight;
      if (maxScrollTop <= 0) return;

      if (wrap.scrollTop >= maxScrollTop - 2) {
        stopAutoScroll(wrapId);
        wrap.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => startAutoScroll(wrapId), pauseAtLoopMs);
        return;
      }
      wrap.scrollTop = wrap.scrollTop + stepPx;
    }, intervalMs);
  }

  function stopAutoScroll(wrapId){
    if (autoScrollState[wrapId]){
      clearInterval(autoScrollState[wrapId]);
      delete autoScrollState[wrapId];
    }
  }

  async function fetchJsonOrThrow(url){
    const res = await fetch(url, { headers: { 'Accept':'application/json' } });
    const ct = res.headers.get('content-type') || '';
    if (!res.ok){
      const text = await res.text();
      throw new Error(`HTTP ${res.status} | ${text.slice(0,250)}`);
    }
    if (!ct.includes('application/json')){
      const text = await res.text();
      throw new Error(`Bukan JSON. Preview: ${text.slice(0,250)}`);
    }
    const json = await res.json();
    if (!json.success) throw new Error('Response success=false');
    return json;
  }

  function showErrorBox(loadingId, err){
    const el = document.getElementById(loadingId);
    el.style.display = 'block';
    el.className = 'errorbox';
    el.innerHTML = `Gagal memuat data:<br><code>${String(err)}</code>`;
    console.error(err);
  }
</script>

<!-- ========================= SUB ASSY ========================= -->
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

    for (let i=1;i<=daysInMonth;i++){
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
        <td class="w-cust sticky-2">${r.customer ?? ''}</td>
        <td class="w-proj sticky-3">${r.project ?? ''}</td>
        <td class="w-pn sticky-4">${r.part_number ?? ''}</td>
        <td class="w-name sticky-5">${r.part_name ?? ''}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-spk">SPK</div>
            <div class="tag tag-prod">Produksi</div>
            <div class="tag tag-wip">WIP</div>
          </div>
        </td>

        <td class="w-num"><b>${r.total_po ?? 0}</b></td>
        <td class="w-num"><b>${r.wip_sebelumnya ?? 0}</b></td>
        <td class="w-num"><b>${r.total_spk ?? 0}</b></td>
        <td class="w-num"><b>${r.total_produksi ?? 0}</b></td>
        <td class="w-num"><b>${r.wip_akhir ?? 0}</b></td>
        <td class="w-num">${prodBadge(r.produktivitas)}</td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const spk  = (r.days?.spk?.[d] ?? 0);
        const prod = (r.days?.produksi?.[d] ?? 0);
        const wip  = (r.days?.wip?.[d] ?? 0);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-spk">${spk}</div>
              <div class="pill pill-prod">${prod}</div>
              <div class="pill pill-wip">${wip}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  let lastTodayDaySubAssy = null;

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

    buildHeader(json.daysInMonth);
    buildBody(json.rows || [], json.daysInMonth);

    document.getElementById('metaText').textContent =
      `Sub Assy | ${pad(json.bulan)}-${json.tahun} | Update: ${json.timestamp}`;

    loading.style.display = 'none';
    table.style.display = 'table';

    lastTodayDaySubAssy = json.todayDay;

    requestAnimationFrame(() => {
      if (wrap) wrap.scrollTop = keepScrollTop;

      // Freeze header semua baris (ini yang kamu minta)
      freezeTheadAllRows('subassyTable');

      highlightTodayGeneric('subassyTable', json.todayDay);

      requestAnimationFrame(() => {
        applyStickyOffsetsFor('subassyTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
        focusTodayGeneric('subassyWrap','subassyTable','#subassyTable thead th.sticky-6', json.todayDay);
      });
    });
  }

  document.getElementById('btnReload').addEventListener('click', () => {
    loadSubAssy().catch(err => showErrorBox('subassyLoading', err));
    loadMIP().catch(err => showErrorBox('mipLoading', err));
    loadFG().catch(err => showErrorBox('fgLoading', err));
  });

  loadSubAssy().catch(err => showErrorBox('subassyLoading', err));
  setInterval(() => loadSubAssy().catch(console.error), 1800000);

  window.addEventListener('resize', () => {
    freezeTheadAllRows('subassyTable');
    applyStickyOffsetsFor('subassyTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
    if (lastTodayDaySubAssy) focusTodayGeneric('subassyWrap','subassyTable','#subassyTable thead th.sticky-6', lastTodayDaySubAssy);

    freezeTheadAllRows('mipTable');
    applyStickyOffsetsFor('mipTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
    if (lastTodayDayMIP) focusTodayGeneric('mipWrap','mipTable','#mipTable thead th.sticky-6', lastTodayDayMIP);

    freezeTheadAllRows('fgTable');
    applyStickyOffsetsFor('fgTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
    if (lastTodayDayFG) focusTodayGeneric('fgWrap','fgTable','#fgTable thead th.sticky-6', lastTodayDayFG);
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden){
      stopAutoScroll('subassyWrap'); stopAutoScroll('mipWrap'); stopAutoScroll('fgWrap');
    } else {
      startAutoScroll('subassyWrap'); startAutoScroll('mipWrap'); startAutoScroll('fgWrap');
      if (lastTodayDaySubAssy) focusTodayGeneric('subassyWrap','subassyTable','#subassyTable thead th.sticky-6', lastTodayDaySubAssy);
      if (lastTodayDayMIP) focusTodayGeneric('mipWrap','mipTable','#mipTable thead th.sticky-6', lastTodayDayMIP);
      if (lastTodayDayFG) focusTodayGeneric('fgWrap','fgTable','#fgTable thead th.sticky-6', lastTodayDayFG);
    }
  });

  window.addEventListener('load', () => startAutoScroll('subassyWrap'));
</script>

<!-- ========================= MIP ========================= -->
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

    for (let i=1;i<=daysInMonth;i++){
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
        <td class="w-cust sticky-2">${r.customer ?? ''}</td>
        <td class="w-proj sticky-3">${r.project ?? ''}</td>
        <td class="w-pn sticky-4">${r.part_number ?? ''}</td>
        <td class="w-name sticky-5">${r.part_name ?? ''}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-prod">IN</div>
            <div class="tag tag-spk">OUT</div>
            <div class="tag tag-wip">BAL</div>
          </div>
        </td>

        <td class="w-num"><b>${r.stock_awal ?? 0}</b></td>
        <td class="w-num"><b>${r.total_in ?? 0}</b></td>
        <td class="w-num"><b>${r.total_out ?? 0}</b></td>
        <td class="w-num"><b>${r.balance_akhir ?? 0}</b></td>

        <td class="w-num"><b>${r.level_min ?? 0}</b></td>
        <td class="w-num"><b>${r.level_safety ?? 0}</b></td>
        <td class="w-num"><b>${r.level_max ?? 0}</b></td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const inQty  = (r.days?.in?.[d] ?? 0);
        const outQty = (r.days?.out?.[d] ?? 0);
        const bal    = (r.days?.balance?.[d] ?? 0);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${inQty}</div>
              <div class="pill pill-spk">${outQty}</div>
              <div class="pill pill-wip">${bal}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  let lastTodayDayMIP = null;

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

    buildMIPHeader(json.daysInMonth);
    buildMIPBody(json.rows || [], json.daysInMonth);

    loading.style.display = 'none';
    table.style.display = 'table';

    lastTodayDayMIP = json.todayDay;

    requestAnimationFrame(() => {
      if (wrap) wrap.scrollTop = keepScrollTop;

      freezeTheadAllRows('mipTable');
      highlightTodayGeneric('mipTable', json.todayDay);

      requestAnimationFrame(() => {
        applyStickyOffsetsFor('mipTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
        focusTodayGeneric('mipWrap','mipTable','#mipTable thead th.sticky-6', json.todayDay);
      });
    });

    startAutoScroll('mipWrap');
  }

  loadMIP().catch(err => showErrorBox('mipLoading', err));
  setInterval(() => loadMIP().catch(console.error), 1800000);
  window.addEventListener('load', () => startAutoScroll('mipWrap'));
</script>

<!-- ========================= FINISH GOODS ========================= -->
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

    for (let i=1;i<=daysInMonth;i++){
      row2 += `<th class="w-date" data-day="${i}" colspan="2">${i}</th>`;
    }
    row2 += `</tr>`;

    // Baris D/N (baris header ke-3)
    let dn = `
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
    for (let i=1;i<=daysInMonth;i++){
      dn += `<th class="w-date">D</th><th class="w-date">N</th>`;
    }
    dn += `</tr>`;

    thead.innerHTML = row1 + row2 + dn;
  }

  function statusStockBadge(v){
    const val = String(v || '').toLowerCase();
    if (val === 'problem') return `<span class="tag tag-spk">Problem</span>`;
    if (val === 'over') return `<span class="tag" style="background:#3a2f12;border-color:#6b5a1f;color:#ffe8b3;">Over</span>`;
    return `<span class="tag tag-prod">Aman</span>`;
  }

  function buildFGBody(rows, daysInMonth){
    const tbody = document.getElementById('fgTbody');
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
      const tr = document.createElement('tr');

      let html = `
        <td class="w-no sticky-1">${idx+1}</td>
        <td class="w-cust sticky-2">${r.customer ?? ''}</td>
        <td class="w-proj sticky-3">${r.project ?? ''}</td>
        <td class="w-pn sticky-4">${r.part_number ?? ''}</td>
        <td class="w-name sticky-5">${r.part_name ?? ''}</td>

        <td class="w-status sticky-6">
          <div class="legend">
            <div class="tag tag-prod">IN</div>
            <div class="tag tag-spk">OUT</div>
            <div class="tag tag-wip">BAL</div>
          </div>
        </td>

        <td class="w-num"><b>${r.total_po ?? 0}</b></td>
        <td class="w-num"><b>${r.advance_delivery ?? 0}</b></td>
        <td class="w-num"><b>${r.outstanding ?? 0}</b></td>
        <td class="w-num"><b>${(r.percentage ?? 0)}%</b></td>

        <td class="w-num"><b>${r.stock_awal ?? 0}</b></td>
        <td class="w-num"><b>${r.total_in ?? 0}</b></td>
        <td class="w-num"><b>${r.total_out ?? 0}</b></td>

        <td class="w-num"><b>${r.level_min ?? 0}</b></td>
        <td class="w-num"><b>${r.level_safety ?? 0}</b></td>
        <td class="w-num"><b>${r.level_max ?? 0}</b></td>

        <td class="w-num"><b>${r.stock_on_hand ?? 0}</b></td>
        <td class="w-num">${statusStockBadge(r.status_stock)}</td>
      `;

      for (let d=1; d<=daysInMonth; d++){
        const inD  = (r.days?.in_d?.[d] ?? 0);
        const outD = (r.days?.out_d?.[d] ?? 0);
        const balD = (r.days?.bal_d?.[d] ?? 0);

        const inN  = (r.days?.in_n?.[d] ?? 0);
        const outN = (r.days?.out_n?.[d] ?? 0);
        const balN = (r.days?.bal_n?.[d] ?? 0);

        html += `
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${inD}</div>
              <div class="pill pill-spk">${outD}</div>
              <div class="pill pill-wip">${balD}</div>
            </div>
          </td>
          <td class="day-cell w-date" data-day="${d}">
            <div class="cell-stack">
              <div class="pill pill-prod">${inN}</div>
              <div class="pill pill-spk">${outN}</div>
              <div class="pill pill-wip">${balN}</div>
            </div>
          </td>
        `;
      }

      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
  }

  let lastTodayDayFG = null;

  async function loadFG(){
    const loading = document.getElementById('fgLoading');
    const table = document.getElementById('fgTable');
    const wrap = document.getElementById('fgWrap');

    const keepScrollTop = wrap ? wrap.scrollTop : 0;

    loading.style.display = 'block';
    loading.className = 'loading';
    loading.textContent = 'Memuat Finish Good...';
    table.style.display = 'none';

    const json = await fetchJsonOrThrow(getQueryUrl(fgUrl));

    buildFGHeader(json.daysInMonth);
    buildFGBody(json.rows || [], json.daysInMonth);

    loading.style.display = 'none';
    table.style.display = 'table';

    lastTodayDayFG = json.todayDay;

    requestAnimationFrame(() => {
      if (wrap) wrap.scrollTop = keepScrollTop;

      freezeTheadAllRows('fgTable');
      highlightTodayGeneric('fgTable', json.todayDay);

      requestAnimationFrame(() => {
        applyStickyOffsetsFor('fgTable', ['sticky-1','sticky-2','sticky-3','sticky-4','sticky-5','sticky-6']);
        focusTodayGeneric('fgWrap','fgTable','#fgTable thead th.sticky-6', json.todayDay);
      });
    });

    startAutoScroll('fgWrap');
  }

  loadFG().catch(err => showErrorBox('fgLoading', err));
  setInterval(() => loadFG().catch(console.error), 1800000);
  window.addEventListener('load', () => startAutoScroll('fgWrap'));
</script>
</body>
</html>