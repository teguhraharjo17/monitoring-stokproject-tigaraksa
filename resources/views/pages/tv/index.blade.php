<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TV Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg: #0b1220;
            --panel: #0f1a33;
            --line: #223055;
            --text: #eaf0ff;
            --muted: #a9b4d0;
            --head: #122042;
            --today: #ff3b3b;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, Segoe UI, Roboto, Arial, sans-serif;
        }

        .topbar {
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
            background: rgba(15, 26, 51, .95);
            position: sticky;
            top: 0;
            z-index: 60;
        }

        .title {
            font-weight: 900;
            letter-spacing: .3px;
            font-size: clamp(16px, 1.6vw, 22px);
        }

        .meta {
            color: var(--muted);
            font-size: clamp(12px, 1.1vw, 14px);
        }

        .wrap {
            padding: 14px 18px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        .tv-tabs .nav-link {
            color: var(--muted);
            font-weight: 900;
            letter-spacing: .2px;
        }

        .tv-tabs .nav-link.active {
            color: var(--text);
            background: rgba(255, 255, 255, .06);
            border-color: var(--line);
        }

        .table-wrap {
            height: calc(100vh - 190px);
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: rgba(255, 255, 255, .02);
        }

        .table-wrap::-webkit-scrollbar {
            height: 12px;
            width: 12px;
        }

        .table-wrap::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .18);
            border-radius: 10px;
        }

        table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: auto;
        }

        th, td {
            border-right: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            padding: 10px 12px;
            text-align: center;
            white-space: nowrap;
            font-size: clamp(12px, 1.15vw, 16px);
            background: rgba(255, 255, 255, .02);
            vertical-align: middle;
        }

        thead th {
            position: sticky;
            top: 0;
            background: var(--head);
            z-index: 30;
        }

        .w-no { width: 64px; min-width: 64px; }
        .w-cust { width: 180px; min-width: 180px; }
        .w-proj { width: 150px; min-width: 150px; }
        .w-pn { width: 220px; min-width: 220px; }
        .w-name { width: 280px; min-width: 280px; }
        .w-num { width: 140px; min-width: 140px; }

        .w-date {
            width: clamp(120px, 7vw, 160px);
            min-width: clamp(120px, 7vw, 160px);
        }

        .sticky-1 { position: sticky; left: 0; z-index: 45; background: var(--head) !important; }
        .sticky-2 { position: sticky; left: 64px; z-index: 45; background: var(--head) !important; }
        .sticky-3 { position: sticky; left: 244px; z-index: 45; background: var(--head) !important; }
        .sticky-4 { position: sticky; left: 394px; z-index: 45; background: var(--head) !important; }

        .sticky-5 { position: sticky; left: 614px; z-index: 45; background: var(--head) !important; }

        td.sticky-1, td.sticky-2, td.sticky-3, td.sticky-4, td.sticky-5 {
            background: #101b36 !important;
        }

        tbody tr:nth-child(odd) td {
            background: rgba(255, 255, 255, .03);
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, .07);
        }

        tbody tr:hover td.sticky-1,
        tbody tr:hover td.sticky-2,
        tbody tr:hover td.sticky-3,
        tbody tr:hover td.sticky-4,
        tbody tr:hover td.sticky-5 {
            background: #14224a !important;
        }

        .cell-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }

        .pill {
            border-radius: 10px;
            padding: 10px 10px;
            font-weight: 900;
            line-height: 1;
            border: 1px solid transparent;
            font-size: clamp(12px, 1.1vw, 16px);
        }

        .pill-spk {
            background: #3a1220;
            border-color: #6b1f3a;
            color: #ffd0db;
        }

        .pill-prod {
            background: #0f2a1b;
            border-color: #1c6b3a;
            color: #bff7d2;
        }

        .pill-wip {
            background: #0f2236;
            border-color: #1d4f8a;
            color: #cce5ff;
        }

        .today-col {
            outline: 4px solid var(--today);
            outline-offset: -4px;
        }

        .prod-low { color: #ff6b6b; font-weight: 900; }
        .prod-ok { color: #9dffb1; font-weight: 900; }

        .badge-soft {
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--line);
        }

        .loading {
            padding: 18px;
            color: var(--muted);
        }

        .errorbox {
            padding: 18px;
            color: #ff9a9a;
            white-space: normal;
        }

        code {
            color: #ffd0db;
        }

        @media (max-width: 1200px) {
            th, td { font-size: 14px; padding: 10px; }
            .w-date { min-width: 140px; }
        }
    </style>
</head>
<body>

<div class="topbar d-flex justify-content-between align-items-center gap-3">
    <div>
        <div class="title">TV Display - Monitoring Stok</div>
        <div class="meta" id="metaText">Memuat data...</div>
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
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-mip" type="button" role="tab">MIP (soon)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-fg" type="button" role="tab">Finish Goods (soon)</button>
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

            <div class="tab-pane fade" id="pane-mip" role="tabpanel">
                <div class="p-4 text-center" style="color:var(--muted);">Nanti menyusul</div>
            </div>

            <div class="tab-pane fade" id="pane-fg" role="tabpanel">
                <div class="p-4 text-center" style="color:var(--muted);">Nanti menyusul</div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const subassyUrl = @json(route('tv.data.subassy'));

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function tickClock() {
        const d = new Date();
        document.getElementById('clock').textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    setInterval(tickClock, 1000);
    tickClock();

    function prodBadge(p) {
        const val = parseInt(p || 0, 10);
        return val < 100 ? `<span class="prod-low">${val}%</span>` : `<span class="prod-ok">${val}%</span>`;
    }

    function buildHeader(daysInMonth) {
        const thead = document.getElementById('subassyThead');

        let row1 = `
            <tr>
                <th class="w-no sticky-1">No</th>
                <th class="w-cust sticky-2">Customer</th>
                <th class="w-proj sticky-3">Project</th>
                <th class="w-pn sticky-4">Part Number</th>
                <th class="w-name sticky-5">Part Name</th>
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
                <th class="w-num"></th>
                <th class="w-num"></th>
                <th class="w-num"></th>
                <th class="w-num"></th>
                <th class="w-num"></th>
                <th class="w-num"></th>
        `;

        for (let i = 1; i <= daysInMonth; i++) {
            row2 += `<th class="w-date" data-day="${i}">${i}</th>`;
        }

        row2 += `</tr>`;
        thead.innerHTML = row1 + row2;
    }

    function buildBody(rows, daysInMonth) {
        const tbody = document.getElementById('subassyTbody');
        tbody.innerHTML = '';

        rows.forEach((r, idx) => {
            const tr = document.createElement('tr');

            let html = `
                <td class="w-no sticky-1">${idx + 1}</td>
                <td class="w-cust sticky-2">${r.customer ?? ''}</td>
                <td class="w-proj sticky-3">${r.project ?? ''}</td>
                <td class="w-pn sticky-4">${r.part_number ?? ''}</td>
                <td class="w-name sticky-5">${r.part_name ?? ''}</td>
                <td class="w-num"><b>${r.total_po ?? 0}</b></td>
                <td class="w-num"><b>${r.wip_sebelumnya ?? 0}</b></td>
                <td class="w-num"><b>${r.total_spk ?? 0}</b></td>
                <td class="w-num"><b>${r.total_produksi ?? 0}</b></td>
                <td class="w-num"><b>${r.wip_akhir ?? 0}</b></td>
                <td class="w-num">${prodBadge(r.produktivitas)}</td>
            `;

            for (let d = 1; d <= daysInMonth; d++) {
                const spk = (r.days?.spk?.[d] ?? 0);
                const prod = (r.days?.produksi?.[d] ?? 0);
                const wip = (r.days?.wip?.[d] ?? 0);

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

    function highlightToday(todayDay) {
        document.querySelectorAll('#subassyTable [data-day]').forEach(el => el.classList.remove('today-col'));
        if (!todayDay) return;

        document.querySelectorAll(`#subassyTable th[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
        document.querySelectorAll(`#subassyTable td.day-cell[data-day="${todayDay}"]`).forEach(el => el.classList.add('today-col'));
    }

    function focusToday(todayDay) {
        if (!todayDay) return;

        const wrap = document.getElementById('subassyWrap');
        const target = document.querySelector(`#subassyTable th[data-day="${todayDay}"]`);
        if (!wrap || !target) return;

        const wrapRect = wrap.getBoundingClientRect();
        const tRect = target.getBoundingClientRect();
        const desiredLeft = (tRect.left - wrapRect.left) - (wrap.clientWidth * 0.35);

        wrap.scrollLeft += desiredLeft;
    }

    async function loadSubAssy() {
        const loading = document.getElementById('subassyLoading');
        const table = document.getElementById('subassyTable');

        loading.style.display = 'block';
        loading.className = 'loading';
        loading.textContent = 'Memuat Sub Assy...';
        table.style.display = 'none';

        const url = new URL(subassyUrl, window.location.origin);
        const params = new URLSearchParams(window.location.search);

        if (params.get('bulan')) url.searchParams.set('bulan', params.get('bulan'));
        if (params.get('tahun')) url.searchParams.set('tahun', params.get('tahun'));
        if (params.get('customer')) url.searchParams.set('customer', params.get('customer'));

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
        const ct = res.headers.get('content-type') || '';

        if (!res.ok) {
            const text = await res.text();
            throw new Error(`HTTP ${res.status} | ${text.slice(0, 250)}`);
        }

        if (!ct.includes('application/json')) {
            const text = await res.text();
            throw new Error(`Bukan JSON. Preview: ${text.slice(0, 250)}`);
        }

        const json = await res.json();
        if (!json.success) throw new Error('Response success=false');

        buildHeader(json.daysInMonth);
        buildBody(json.rows || [], json.daysInMonth);

        document.getElementById('metaText').textContent =
            `Sub Assy | ${pad(json.bulan)}-${json.tahun} | Update: ${json.timestamp}`;

        loading.style.display = 'none';
        table.style.display = 'table';

        requestAnimationFrame(() => {
            highlightToday(json.todayDay);
            focusToday(json.todayDay);
        });
    }

    function showError(err) {
        const el = document.getElementById('subassyLoading');
        el.style.display = 'block';
        el.className = 'errorbox';
        el.innerHTML = `Gagal memuat data:<br><code>${String(err)}</code>`;
        console.error(err);
    }

    document.getElementById('btnReload').addEventListener('click', () => {
        loadSubAssy().catch(showError);
    });

    loadSubAssy().catch(showError);
    setInterval(() => loadSubAssy().catch(console.error), 60000);
</script>
</body>
</html>
