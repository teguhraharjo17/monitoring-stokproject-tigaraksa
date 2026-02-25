<x-default-layout>
    @section('title', 'Dashboard')
    <style>
        .table-sticky thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-body-bg);
        }
        .freeze-col {
            position: sticky;
            left: 0;
            z-index: 1;
            background: var(--bs-body-bg);
        }
        .freeze-col-2 {
            position: sticky;
            left: 170px;
            z-index: 1;
            background: var(--bs-body-bg);
        }
        .w-part { min-width: 170px; }
        .w-name { min-width: 260px; }
        .w-cust { min-width: 140px; }
        .w-proj { min-width: 140px; }
        .w-num  { min-width: 120px; }
        .w-stat { min-width: 140px; }

        .table-hover tbody tr:hover {
            background: rgba(0,0,0,.03);
        }

        @media (max-width: 991.98px) {
            .desktop-table { display: none; }
            .mobile-cards { display: block; }
        }
        @media (min-width: 992px) {
            .desktop-table { display: block; }
            .mobile-cards { display: none; }
        }
    </style>

    <div class="card mb-5 mt-5">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" value="{{ $date }}" class="form-control">
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input id="tableSearch" type="text" class="form-control" placeholder="Part / Name / Customer / Project">
                        <button type="button" id="clearSearch" class="btn btn-light">Clear</button>
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-2">
                    <button class="btn btn-primary w-100">Tampilkan</button>
                </div>

                <div class="col-12 col-lg-4 text-lg-end text-muted">
                    <div class="d-flex gap-2 justify-content-lg-end align-items-center flex-wrap">
                        <span class="badge bg-light text-dark">Bulan: {{ $bulan }}</span>
                        <span class="badge bg-light text-dark">Tahun: {{ $tahun }}</span>
                        <span class="badge bg-light text-dark">Hari: {{ $hari }}</span>
                        <span class="badge bg-light text-dark">Rows: <span id="rowCount">{{ count($rows) }}</span></span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-4">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between w-100">
                <div>
                    <div class="fw-bold">Rekap Harian</div>
                    <div class="text-muted small">SA(WIP), MIP(BAL), FG(BAL), Total, Level Stock N, Status Level (Day)</div>
                </div>

                <div class="d-flex gap-2">
                    <select id="pageSize" class="form-select form-select-sm" style="width: 110px;">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">

            {{-- Desktop Table --}}
            <div class="desktop-table table-responsive" style="max-height: 70vh;">
                <table id="rekapTable" class="table table-hover table-striped table-row-bordered align-middle table-sticky">
                    <thead>
                        <tr class="text-muted">
                            <th class="freeze-col w-part sort" data-key="part_number">Part Number</th>
                            <th class="freeze-col-2 w-name sort" data-key="part_name">Part Name</th>
                            <th class="w-cust sort" data-key="customer">Customer</th>
                            <th class="w-proj sort" data-key="project">Project</th>
                            <th class="text-end w-num sort" data-key="sa_wip" data-type="num">SA (WIP)</th>
                            <th class="text-end w-num sort" data-key="mip_bal" data-type="num">MIP (BAL)</th>
                            <th class="text-end w-num sort" data-key="fg_bal" data-type="num">FG (BAL)</th>
                            <th class="text-end w-num sort" data-key="total" data-type="num">Total</th>
                            <th class="text-end w-num sort" data-key="level_stock_n" data-type="num">Level Stock N</th>
                            <th class="text-end w-stat sort" data-key="status_level_day" data-type="num">Status Level (Day)</th>
                        </tr>
                    </thead>

                    <tbody id="tableBody">
                        @foreach($rows as $r)
                            @php
                                $status = is_numeric($r->status_level_day) ? (float)$r->status_level_day : null;
                                $badge =
                                    $status === null ? 'bg-light text-dark' :
                                    ($status >= 1 ? 'bg-success' :
                                    ($status >= 0.7 ? 'bg-warning text-dark' : 'bg-danger'));
                            @endphp
                            <tr
                                data-part_number="{{ $r->part_number }}"
                                data-part_name="{{ $r->part_name }}"
                                data-customer="{{ $r->customer }}"
                                data-project="{{ $r->project }}"
                                data-sa_wip="{{ (float)$r->sa_wip }}"
                                data-mip_bal="{{ (float)$r->mip_bal }}"
                                data-fg_bal="{{ (float)$r->fg_bal }}"
                                data-total="{{ (float)$r->total }}"
                                data-level_stock_n="{{ (float)$r->level_stock_n }}"
                                data-status_level_day="{{ is_numeric($r->status_level_day) ? (float)$r->status_level_day : -1 }}"
                            >
                                <td class="freeze-col w-part fw-semibold">{{ $r->part_number }}</td>
                                <td class="freeze-col-2 w-name text-truncate" style="max-width: 320px;" title="{{ $r->part_name }}">
                                    {{ $r->part_name }}
                                </td>
                                <td class="w-cust">{{ $r->customer }}</td>
                                <td class="w-proj">{{ $r->project }}</td>

                                <td class="text-end w-num">{{ number_format($r->sa_wip) }}</td>
                                <td class="text-end w-num">{{ number_format($r->mip_bal) }}</td>
                                <td class="text-end w-num">{{ number_format($r->fg_bal) }}</td>
                                <td class="text-end w-num fw-bold">{{ number_format($r->total) }}</td>
                                <td class="text-end w-num">{{ number_format($r->level_stock_n) }}</td>

                                <td class="text-end w-stat">
                                    <span class="badge {{ $badge }}">
                                        {{ is_numeric($r->status_level_day)
                                            ? rtrim(rtrim(number_format($r->status_level_day, 1, '.', ''), '0'), '.')
                                            : $r->status_level_day
                                        }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Menampilkan <span id="shownCount"></span> dari <span id="filteredCount"></span> baris
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-light" id="prevPage">Prev</button>
                        <button class="btn btn-sm btn-light" id="nextPage">Next</button>
                    </div>
                </div>
            </div>

            {{-- Mobile Cards --}}
            <div class="mobile-cards">
                <div id="cardContainer" class="row g-3">
                    @foreach($rows as $r)
                        @php
                            $status = is_numeric($r->status_level_day) ? (float)$r->status_level_day : null;
                            $badge =
                                $status === null ? 'bg-light text-dark' :
                                ($status >= 1 ? 'bg-success' :
                                ($status >= 0.7 ? 'bg-warning text-dark' : 'bg-danger'));
                        @endphp

                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-bold">{{ $r->part_number }}</div>
                                            <div class="text-muted">{{ $r->part_name }}</div>
                                            <div class="small mt-2">
                                                <span class="me-3"><span class="text-muted">Customer:</span> {{ $r->customer }}</span>
                                                <span><span class="text-muted">Project:</span> {{ $r->project }}</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge {{ $badge }}">
                                                {{ is_numeric($r->status_level_day)
                                                    ? rtrim(rtrim(number_format($r->status_level_day, 1, '.', ''), '0'), '.')
                                                    : $r->status_level_day
                                                }}
                                            </span>
                                        </div>
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

        </div>
    </div>

    <script>
        (function () {
            const table = document.getElementById('rekapTable');
            if (!table) return;

            const body = document.getElementById('tableBody');
            const rows = Array.from(body.querySelectorAll('tr'));
            const search = document.getElementById('tableSearch');
            const clear = document.getElementById('clearSearch');
            const pageSizeEl = document.getElementById('pageSize');
            const prev = document.getElementById('prevPage');
            const next = document.getElementById('nextPage');
            const shownCount = document.getElementById('shownCount');
            const filteredCount = document.getElementById('filteredCount');
            const rowCount = document.getElementById('rowCount');
            const exportBtn = document.getElementById('exportCsv');

            let currentPage = 1;
            let pageSize = parseInt(pageSizeEl.value, 10);
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

            function filterRows() {
                const q = (search.value || '').trim().toLowerCase();
                if (!q) return rows;
                return rows.filter(r => rowIndex.get(r).includes(q));
            }

            function sortRows(list) {
                if (!sortKey) return list;

                const isNum = table.querySelector(`th[data-key="${sortKey}"]`)?.dataset.type === 'num';

                return [...list].sort((a, b) => {
                    const av = a.dataset[sortKey] ?? '';
                    const bv = b.dataset[sortKey] ?? '';
                    if (isNum) return (parseFloat(av) - parseFloat(bv)) * sortDir;
                    return String(av).localeCompare(String(bv)) * sortDir;
                });
            }

            function paginate(list) {
                const start = (currentPage - 1) * pageSize;
                return list.slice(start, start + pageSize);
            }

            function render() {
                const filtered = filterRows();
                const sorted = sortRows(filtered);
                const paged = paginate(sorted);

                rows.forEach(r => r.style.display = 'none');
                paged.forEach(r => r.style.display = '');

                const totalFiltered = filtered.length;
                const totalShown = paged.length;

                rowCount.textContent = rows.length;
                filteredCount.textContent = totalFiltered;
                shownCount.textContent = totalShown;

                const maxPage = Math.max(1, Math.ceil(totalFiltered / pageSize));
                currentPage = Math.min(currentPage, maxPage);

                prev.disabled = currentPage <= 1;
                next.disabled = currentPage >= maxPage;
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
                pageSize = parseInt(pageSizeEl.value, 10);
                currentPage = 1;
                render();
            });

            prev.addEventListener('click', () => { currentPage = Math.max(1, currentPage - 1); render(); });
            next.addEventListener('click', () => { currentPage = currentPage + 1; render(); });

            document.querySelectorAll('th.sort').forEach(th => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => setSort(th.dataset.key));
            });

            exportBtn.addEventListener('click', () => {
                const filtered = sortRows(filterRows());
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
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
                        r.dataset.status_level_day
                    ].map(v => `"${String(v ?? '').replace(/"/g, '""')}"`);
                    lines.push(row.join(','));
                });

                const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `rekap_harian_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            });

            render();
        })();
    </script>
</x-default-layout>