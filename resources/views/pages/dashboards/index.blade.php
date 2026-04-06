<x-default-layout>
    @section('title', 'Dashboard')
    @php
        $totalRows = $rows->count();
        $sumTotal = $rows->sum(fn ($row) => (float) $row->total);
        $sumSa = $rows->sum(fn ($row) => (float) $row->sa_wip);
        $sumMip = $rows->sum(fn ($row) => (float) $row->mip_bal);
        $sumFg = $rows->sum(fn ($row) => (float) $row->fg_bal);
        $criticalCount = $rows->filter(fn ($row) => is_numeric($row->status_level_day) && (float) $row->status_level_day < 0.7)->count();
        $warningCount = $rows->filter(fn ($row) => is_numeric($row->status_level_day) && (float) $row->status_level_day >= 0.7 && (float) $row->status_level_day < 1)->count();
        $safeCount = $rows->filter(fn ($row) => is_numeric($row->status_level_day) && (float) $row->status_level_day >= 1)->count();
        $displayDate = \Carbon\Carbon::parse($date)->translatedFormat('d F Y');
    @endphp
    <style>
        .dashboard-shell { display: grid; gap: 1.5rem; }
        .dashboard-hero {
            position: relative; overflow: hidden; border: 0; border-radius: 28px; color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,.22), transparent 30%),
                radial-gradient(circle at bottom left, rgba(255,209,102,.24), transparent 28%),
                linear-gradient(135deg, #0f4c81 0%, #136f63 45%, #1f3c88 100%);
            box-shadow: 0 24px 60px rgba(17,55,93,.18);
        }
        .dashboard-hero .card-body { position: relative; z-index: 1; padding: 2rem; }
        .hero-chip {
            display: inline-flex; align-items: center; gap: .5rem; padding: .55rem .9rem; border-radius: 999px;
            background: rgba(255,255,255,.14); color: rgba(255,255,255,.9); font-size: .9rem;
        }
        .hero-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 1rem; }
        .hero-stat {
            padding: 1rem 1.1rem; border-radius: 22px; background: rgba(255,255,255,.12);
            min-height: 108px; backdrop-filter: blur(10px);
        }
        .hero-stat-label { margin-bottom: .45rem; color: rgba(255,255,255,.75); font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; }
        .hero-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .hero-stat-note { margin-top: .45rem; color: rgba(255,255,255,.76); font-size: .85rem; }
        .dashboard-panel {
            border: 0; border-radius: 24px; background: linear-gradient(180deg, #fff 0%, #f7fafc 100%);
            box-shadow: 0 16px 40px rgba(15,23,42,.08);
        }
        .panel-title { margin-bottom: .35rem; font-size: 1.15rem; font-weight: 700; color: #12324a; }
        .panel-subtitle { color: #64748b; font-size: .92rem; }
        .filter-card {
            height: 100%; padding: 1rem; border-radius: 20px; border: 1px solid #e2e8f0; background: #fff;
        }
        .metric-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: .85rem; }
        .metric-pill { padding: .9rem 1rem; border-radius: 18px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .metric-pill .label { display: block; color: #64748b; font-size: .76rem; text-transform: uppercase; letter-spacing: .08em; }
        .metric-pill .value { display: block; margin-top: .35rem; font-size: 1.2rem; font-weight: 700; color: #0f172a; }
        .legend-list { display: flex; flex-wrap: wrap; gap: .75rem; }
        .legend-item {
            display: inline-flex; align-items: center; gap: .55rem; padding: .55rem .85rem; border-radius: 999px;
            background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); color: #fff; font-size: .88rem;
        }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-danger { background: #ef4444; }
        .dot-warning { background: #f59e0b; }
        .dot-safe { background: #10b981; }
        .table-shell { border-radius: 22px; border: 1px solid #e2e8f0; overflow: hidden; background: #fff; }
        .table-sticky thead th {
            position: sticky; top: 0; z-index: 4; background: #f8fafc; color: #475569; font-size: .8rem;
            letter-spacing: .04em; text-transform: uppercase; box-shadow: inset 0 -1px 0 #e2e8f0;
        }
        .freeze-col, .freeze-col-2 { position: sticky; z-index: 3; background: #fff; }
        .freeze-col { left: 0; }
        .freeze-col-2 { left: 180px; }
        .table-sticky tbody tr:hover td,
        .table-sticky tbody tr:hover td.freeze-col,
        .table-sticky tbody tr:hover td.freeze-col-2 { background: #f8fbff; }
        .sort-indicator { margin-left: .35rem; font-size: .82rem; color: #94a3b8; }
        .status-badge {
            display: inline-flex; align-items: center; justify-content: center; min-width: 72px; padding: .45rem .8rem;
            border-radius: 999px; font-size: .84rem; font-weight: 700;
        }
        .status-danger { color: #fff; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .status-warning { color: #7c2d12; background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%); }
        .status-safe { color: #fff; background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .status-neutral { color: #334155; background: #e2e8f0; }
        .stock-card { height: 100%; border: 1px solid #e2e8f0; border-radius: 22px; background: linear-gradient(180deg, #fff 0%, #f8fafc 100%); }
        .stock-card-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
        .stock-card-meta .label { display: block; color: #64748b; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; }
        .stock-card-meta .value { display: block; margin-top: .25rem; color: #0f172a; font-weight: 700; }
        .empty-state { display: none; padding: 3rem 1rem; text-align: center; color: #64748b; }
        .empty-icon {
            width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 20px; display: grid; place-items: center;
            background: #e2e8f0; color: #334155; font-size: 1.4rem;
        }
        .w-part { min-width: 180px; }
        .w-name { min-width: 280px; }
        .w-cust { min-width: 160px; }
        .w-proj { min-width: 140px; }
        .w-num  { min-width: 125px; }
        .w-stat { min-width: 150px; }
        .mobile-cards { display: none; }

        @media (max-width: 991.98px) {
            .dashboard-hero .card-body { padding: 1.5rem; }
            .desktop-table { display: none; }
            .mobile-cards { display: block; }
        }
        @media (min-width: 992px) {
            .desktop-table { display: block; }
            .mobile-cards { display: none; }
        }
    </style>

    <div class="dashboard-shell my-5">
        <div class="card dashboard-hero">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-6">
                        <span class="hero-chip mb-4">
                            <i class="fas fa-chart-line"></i>
                            Monitoring stok harian untuk {{ $displayDate }}
                        </span>
                        <h1 class="fs-1 fw-bolder text-white mb-3">Dashboard stok harian yang lebih jelas dan lebih nyaman dipakai</h1>
                        <p class="fs-5 mb-4" style="color: rgba(255,255,255,.82); max-width: 640px;">
                            Ringkasan SA, MIP, FG, total stok, dan level harian kini tersusun lebih rapi supaya tim bisa cepat menangkap part yang perlu perhatian.
                        </p>
                        <div class="legend-list">
                            <span class="legend-item"><span class="legend-dot dot-danger"></span>Kritis &lt; 0.7 hari</span>
                            <span class="legend-item"><span class="legend-dot dot-warning"></span>Waspada 0.7 - 0.9 hari</span>
                            <span class="legend-item"><span class="legend-dot dot-safe"></span>Aman &ge; 1 hari</span>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="hero-stat-grid">
                            <div class="hero-stat">
                                <div class="hero-stat-label">Total Part</div>
                                <div class="hero-stat-value"><span id="rowCount">{{ number_format($totalRows) }}</span></div>
                                <div class="hero-stat-note">Part number di rekap harian</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-label">Total Persediaan</div>
                                <div class="hero-stat-value">{{ number_format($sumTotal) }}</div>
                                <div class="hero-stat-note">Akumulasi SA, MIP, dan FG</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-label">Part Kritis</div>
                                <div class="hero-stat-value">{{ number_format($criticalCount) }}</div>
                                <div class="hero-stat-note">Butuh perhatian cepat</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-label">Part Aman</div>
                                <div class="hero-stat-value">{{ number_format($safeCount) }}</div>
                                <div class="hero-stat-note">{{ number_format($warningCount) }} part masih waspada</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-7">
                        <div class="filter-card">
                            <div class="panel-title">Filter Dashboard</div>
                            <div class="panel-subtitle mb-4">Pilih tanggal dan cari data tanpa perlu bolak-balik halaman.</div>
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Tanggal</label>
                                    <input type="date" name="date" value="{{ $date }}" class="form-control form-control-solid">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Cari Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input id="tableSearch" type="text" class="form-control form-control-solid border-0 ps-2" placeholder="Part, nama, customer, project">
                                        <button type="button" id="clearSearch" class="btn btn-light">Reset</button>
                                    </div>
                                </div>
                                <div class="col-md-3 d-grid">
                                    <button class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>
                                        Tampilkan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="filter-card">
                            <div class="panel-title">Snapshot Hari Ini</div>
                            <div class="panel-subtitle mb-4">Ringkasan utama untuk {{ $displayDate }}.</div>
                            <div class="metric-strip">
                                <div class="metric-pill">
                                    <span class="label">SA (WIP)</span>
                                    <span class="value">{{ number_format($sumSa) }}</span>
                                </div>
                                <div class="metric-pill">
                                    <span class="label">MIP (BAL)</span>
                                    <span class="value">{{ number_format($sumMip) }}</span>
                                </div>
                                <div class="metric-pill">
                                    <span class="label">FG (BAL)</span>
                                    <span class="value">{{ number_format($sumFg) }}</span>
                                </div>
                                <div class="metric-pill">
                                    <span class="label">Rows Aktif</span>
                                    <span class="value" id="filteredCount">{{ number_format($totalRows) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card dashboard-panel">
            <div class="card-body p-4 p-lg-5">

            <div class="d-flex flex-wrap gap-3 align-items-start justify-content-between mb-4">
                <div>
                    <div class="panel-title">Rekap Harian Stok</div>
                    <div class="panel-subtitle">Klik kolom untuk sorting. Export akan mengikuti filter dan urutan data yang sedang aktif.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <select id="pageSize" class="form-select form-select-sm" style="width: 120px;">
                        <option value="10">10 / halaman</option>
                        <option value="25" selected>25 / halaman</option>
                        <option value="50">50 / halaman</option>
                        <option value="100">100 / halaman</option>
                    </select>
                    <button type="button" class="btn btn-light-primary btn-sm" id="exportCsv">
                        <i class="fas fa-file-export me-2"></i>
                        Export CSV
                    </button>
                </div>
            </div>

            {{-- Desktop Table --}}
            <div class="desktop-table">
                <div class="table-shell">
                <div class="table-responsive" style="max-height: 70vh;">
                <table id="rekapTable" class="table table-row-bordered align-middle mb-0 table-sticky">
                    <thead>
                        <tr>
                            <th class="freeze-col w-part sort" data-key="part_number">Part Number <span class="sort-indicator">&#8597;</span></th>
                            <th class="freeze-col-2 w-name sort" data-key="part_name">Part Name <span class="sort-indicator">&#8597;</span></th>
                            <th class="w-cust sort" data-key="customer">Customer <span class="sort-indicator">&#8597;</span></th>
                            <th class="w-proj sort" data-key="project">Project <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-num sort" data-key="sa_wip" data-type="num">SA (WIP) <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-num sort" data-key="mip_bal" data-type="num">MIP (BAL) <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-num sort" data-key="fg_bal" data-type="num">FG (BAL) <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-num sort" data-key="total" data-type="num">Total <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-num sort" data-key="level_stock_n" data-type="num">Level Stock N <span class="sort-indicator">&#8597;</span></th>
                            <th class="text-end w-stat sort" data-key="status_level_day" data-type="num">Status Level <span class="sort-indicator">&#8597;</span></th>
                        </tr>
                    </thead>

                    <tbody id="tableBody">
                        @foreach($rows as $r)
                            @php
                                $status = is_numeric($r->status_level_day) ? (float) $r->status_level_day : null;
                                $statusLabel = $status === null
                                    ? 'N/A'
                                    : rtrim(rtrim(number_format($status, 1, '.', ''), '0'), '.');
                                $statusClass = $status === null
                                    ? 'status-neutral'
                                    : ($status >= 1 ? 'status-safe' : ($status >= 0.7 ? 'status-warning' : 'status-danger'));
                            @endphp
                            <tr
                                data-rowid="desktop-{{ $loop->index }}"
                                data-part_number="{{ $r->part_number }}"
                                data-part_name="{{ $r->part_name }}"
                                data-customer="{{ $r->customer }}"
                                data-project="{{ $r->project }}"
                                data-sa_wip="{{ (float)$r->sa_wip }}"
                                data-mip_bal="{{ (float)$r->mip_bal }}"
                                data-fg_bal="{{ (float)$r->fg_bal }}"
                                data-total="{{ (float)$r->total }}"
                                data-level_stock_n="{{ (float) $r->level_stock_n }}"
                                data-status_level_day="{{ $status ?? -1 }}"
                                data-status_label="{{ $statusLabel }}"
                            >
                                <td class="freeze-col w-part fw-bold text-gray-800">{{ $r->part_number }}</td>
                                <td class="freeze-col-2 w-name" title="{{ $r->part_name }}">
                                    <div class="fw-semibold text-gray-900">{{ $r->part_name ?: '-' }}</div>
                                    <div class="text-muted fs-8">{{ $r->customer ?: 'Tanpa customer' }}</div>
                                </td>
                                <td class="w-cust">{{ $r->customer ?: '-' }}</td>
                                <td class="w-proj">{{ $r->project ?: '-' }}</td>

                                <td class="text-end w-num">{{ number_format($r->sa_wip) }}</td>
                                <td class="text-end w-num">{{ number_format($r->mip_bal) }}</td>
                                <td class="text-end w-num">{{ number_format($r->fg_bal) }}</td>
                                <td class="text-end w-num fw-bold text-gray-900">{{ number_format($r->total) }}</td>
                                <td class="text-end w-num">{{ number_format($r->level_stock_n) }}</td>

                                <td class="text-end w-stat">
                                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
                    <div class="text-muted small">
                        Menampilkan <span id="shownCount">{{ number_format(min($totalRows, 25)) }}</span> dari <span id="totalCount">{{ number_format($totalRows) }}</span> baris
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-light" id="prevPage">Prev</button>
                        <button type="button" class="btn btn-sm btn-light" id="nextPage">Next</button>
                    </div>
                </div>
            </div>

            {{-- Mobile Cards --}}
            <div class="mobile-cards">
                <div id="cardContainer" class="row g-3">
                    @foreach($rows as $r)
                        @php
                            $status = is_numeric($r->status_level_day) ? (float) $r->status_level_day : null;
                            $statusLabel = $status === null
                                ? 'N/A'
                                : rtrim(rtrim(number_format($status, 1, '.', ''), '0'), '.');
                            $statusClass = $status === null
                                ? 'status-neutral'
                                : ($status >= 1 ? 'status-safe' : ($status >= 0.7 ? 'status-warning' : 'status-danger'));
                        @endphp

                        <div class="col-12 col-md-6 stock-card-item" data-rowid="mobile-{{ $loop->index }}">
                            <div class="card stock-card"
                                data-part_number="{{ $r->part_number }}"
                                data-part_name="{{ $r->part_name }}"
                                data-customer="{{ $r->customer }}"
                                data-project="{{ $r->project }}">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-bold fs-5">{{ $r->part_number }}</div>
                                            <div class="text-muted">{{ $r->part_name ?: '-' }}</div>
                                        </div>
                                        <div class="text-end">
                                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-4">
                                        <span class="badge badge-light-primary">{{ $r->customer ?: 'Tanpa customer' }}</span>
                                        <span class="badge badge-light-info">{{ $r->project ?: 'Tanpa project' }}</span>
                                    </div>

                                    <div class="row g-2 mt-3">
                                        <div class="col-6">
                                            <div class="text-muted small">SA (WIP)</div>
                                            <div class="fw-semibold">{{ number_format($r->sa_wip) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">MIP (BAL)</div>
                                            <div class="fw-semibold">{{ number_format($r->mip_bal) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">FG (BAL)</div>
                                            <div class="fw-semibold">{{ number_format($r->fg_bal) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Total</div>
                                            <div class="fw-bold">{{ number_format($r->total) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small">Level Stock N</div>
                                            <div class="fw-semibold">{{ number_format($r->level_stock_n) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="emptyState" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="fw-bold fs-5 mb-2">Data tidak ditemukan</div>
                <div>Coba ubah kata kunci pencarian atau pilih tanggal lain.</div>
            </div>

        </div>
    </div>
    </div>

    <script>
        (function () {
            const table = document.getElementById('rekapTable');
            const body = document.getElementById('tableBody');
            const search = document.getElementById('tableSearch');
            const clear = document.getElementById('clearSearch');
            const pageSizeEl = document.getElementById('pageSize');
            const prev = document.getElementById('prevPage');
            const next = document.getElementById('nextPage');
            const shownCount = document.getElementById('shownCount');
            const filteredCount = document.getElementById('filteredCount');
            const totalCount = document.getElementById('totalCount');
            const rowCount = document.getElementById('rowCount');
            const exportBtn = document.getElementById('exportCsv');
            const emptyState = document.getElementById('emptyState');
            const cardItems = Array.from(document.querySelectorAll('.stock-card-item'));

            if (!table || !body || !search || !clear || !pageSizeEl || !prev || !next) return;

            const rows = Array.from(body.querySelectorAll('tr'));
            let currentPage = 1;
            let pageSize = parseInt(pageSizeEl.value, 10) || 25;
            let sortKey = null;
            let sortDir = 1;

            function getTextIndex(r) {
                return (
                    (r.dataset.part_number || '') + ' ' +
                    (r.dataset.part_name || '') + ' ' +
                    (r.dataset.customer || '') + ' ' +
                    (r.dataset.project || '')
                ).toLowerCase();
            }

            const rowIndex = new Map(rows.map(r => [r, getTextIndex(r)]));
            const cardIndex = new Map(cardItems.map(item => [item, getTextIndex(item.querySelector('.stock-card') || item)]));

            function filterRows() {
                const q = (search.value || '').trim().toLowerCase();
                if (!q) return rows;
                return rows.filter(r => rowIndex.get(r).includes(q));
            }

            function sortRows(list) {
                if (!sortKey) return [...list];

                const isNum = table.querySelector(`th[data-key="${sortKey}"]`)?.dataset.type === 'num';

                return [...list].sort((a, b) => {
                    const av = a.dataset[sortKey] ?? '';
                    const bv = b.dataset[sortKey] ?? '';
                    if (isNum) return ((parseFloat(av) || 0) - (parseFloat(bv) || 0)) * sortDir;
                    return String(av).localeCompare(String(bv), undefined, { sensitivity: 'base' }) * sortDir;
                });
            }

            function paginate(list) {
                const start = (currentPage - 1) * pageSize;
                return list.slice(start, start + pageSize);
            }

            function updateSortIndicators() {
                table.querySelectorAll('th.sort').forEach(th => {
                    const indicator = th.querySelector('.sort-indicator');
                    if (!indicator) return;

                    if (th.dataset.key !== sortKey) {
                        indicator.textContent = '↕';
                        indicator.style.color = '#94a3b8';
                        return;
                    }

                    indicator.textContent = sortDir === 1 ? '↑' : '↓';
                    indicator.style.color = '#0f4c81';
                });
            }

            function syncCards(query, visibleRowIds) {
                cardItems.forEach(item => {
                    const haystack = cardIndex.get(item) || '';
                    const matchesQuery = !query || haystack.includes(query);
                    const matchesPage = visibleRowIds === null || visibleRowIds.has(item.dataset.rowid);
                    item.style.display = matchesQuery && matchesPage ? '' : 'none';
                });
            }

            function render() {
                const query = (search.value || '').trim().toLowerCase();
                const filtered = filterRows();
                const maxPage = Math.max(1, Math.ceil(filtered.length / pageSize));
                currentPage = Math.min(currentPage, maxPage);

                const sorted = sortRows(filtered);
                const paged = paginate(sorted);
                const visibleRowIds = new Set(paged.map(r => r.dataset.rowid));

                rows.forEach(r => {
                    r.style.display = visibleRowIds.has(r.dataset.rowid) ? '' : 'none';
                });

                syncCards(query, window.innerWidth < 992 ? new Set(Array.from(visibleRowIds).map(id => id.replace('desktop-', 'mobile-'))) : null);

                const totalFiltered = filtered.length;
                const totalShown = paged.length;

                if (rowCount) rowCount.textContent = rows.length.toLocaleString('id-ID');
                if (filteredCount) filteredCount.textContent = totalFiltered.toLocaleString('id-ID');
                if (totalCount) totalCount.textContent = totalFiltered.toLocaleString('id-ID');
                if (shownCount) shownCount.textContent = totalShown.toLocaleString('id-ID');

                prev.disabled = currentPage <= 1;
                next.disabled = currentPage >= maxPage;

                if (emptyState) emptyState.style.display = totalFiltered ? 'none' : 'block';
                updateSortIndicators();
            }

            function setSort(key) {
                if (sortKey === key) sortDir *= -1;
                else { sortKey = key; sortDir = 1; }
                currentPage = 1;
                render();
            }

            search.addEventListener('input', () => { currentPage = 1; render(); });
            clear.addEventListener('click', () => { search.value = ''; currentPage = 1; render(); });

            pageSizeEl.addEventListener('change', () => {
                pageSize = parseInt(pageSizeEl.value, 10) || 25;
                currentPage = 1;
                render();
            });

            prev.addEventListener('click', () => { currentPage = Math.max(1, currentPage - 1); render(); });
            next.addEventListener('click', () => { currentPage = currentPage + 1; render(); });

            document.querySelectorAll('th.sort').forEach(th => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => setSort(th.dataset.key));
            });

            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    const filtered = sortRows(filterRows());
                    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.childNodes[0].textContent.trim());
                    const lines = [headers.join(',')];

                    filtered.forEach(r => {
                        const row = [
                            r.dataset.part_number,
                            r.dataset.part_name,
                            r.dataset.customer,
                            r.dataset.project,
                            r.dataset.sa_wip,
                            r.dataset.mip_bal,
                            r.dataset.fg_bal,
                            r.dataset.total,
                            r.dataset.level_stock_n,
                            r.dataset.status_label
                        ].map(v => `"${String(v ?? '').replace(/"/g, '""')}"`);
                        lines.push(row.join(','));
                    });

                    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `rekap_harian_{{ $date }}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                });
            }

            window.addEventListener('resize', render);
            render();
        })();
    </script>
</x-default-layout>
