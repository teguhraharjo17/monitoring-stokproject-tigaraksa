<x-default-layout>
    @section('title', 'Level Stock MIP & FG')

    <style>
        .level-shell { display: grid; gap: 1.5rem; }
        .surface-card { border: 1px solid #dbe4f0; border-radius: 24px; background: linear-gradient(180deg, #fff 0%, #f8fbff 100%); box-shadow: 0 16px 40px rgba(15, 23, 42, .06); }
        .hero-card { padding: 1.75rem; background: radial-gradient(circle at top right, rgba(14, 165, 233, .16), transparent 28%), linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); }
        .eyebrow { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .85rem; border-radius: 999px; font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #0f766e; background: rgba(20, 184, 166, .12); }
        .hero-title { margin: .9rem 0 .65rem; font-size: clamp(1.7rem, 2vw, 2.4rem); line-height: 1.1; font-weight: 800; color: #0f172a; }
        .hero-copy, .section-copy { color: #64748b; line-height: 1.7; }
        .hero-metrics, .stats-grid, .filter-grid { display: grid; gap: 1rem; }
        .hero-metrics, .stats-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .filter-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .metric-card, .stat-card, .formula-item { padding: 1rem 1.1rem; border-radius: 18px; background: #fff; border: 1px solid #dce6f3; }
        .metric-card .label, .stat-card .label, .field-label { display: block; color: #64748b; font-size: .78rem; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .35rem; font-weight: 700; }
        .metric-card .value, .stat-card .value { font-size: 1.35rem; font-weight: 800; color: #0f172a; }
        .panel-card, .table-card { padding: 1.35rem; }
        .section-title { margin: 0 0 .35rem; font-size: 1.05rem; font-weight: 800; color: #0f172a; }
        .field-hint { margin-top: .4rem; font-size: .83rem; color: #64748b; }
        .table-toolbar, .table-toolbar-actions, .table-help, .action-row { display: flex; flex-wrap: wrap; gap: .75rem; }
        .table-toolbar { justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .table-chip, .help-pill, .action-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 700; }
        .table-chip { background: #eef6ff; color: #1d4ed8; }
        .help-pill { background: #f8fafc; border: 1px solid #dbe4f0; color: #475569; }
        .table-responsive { border-radius: 20px; overflow: hidden; border: 1px solid #dce6f3; background: #fff; }
        #levelstock_table { width: 100% !important; margin: 0 !important; }
        #levelstock_table thead th { background: #eff6ff; color: #0f172a; font-size: .78rem; text-transform: uppercase; letter-spacing: .07em; font-weight: 800; white-space: nowrap; }
        #levelstock_table tbody td { vertical-align: middle; border-color: #e7eef7; }
        #levelstock_table tbody tr:hover td { background: #f8fbff; }
        #levelstock_table .form-control, #levelstock_table .form-select { min-width: 120px; border-radius: 12px; border-color: #d6e0ec; box-shadow: none; }
        #levelstock_table .form-control[readonly] { background: #f8fbff; color: #0f172a; font-weight: 700; }
        .calc-input { background: #e0f2fe !important; color: #0c4a6e !important; font-weight: 800 !important; }
        .action-cell { min-width: 180px; }
        .action-stack { display: flex; flex-direction: column; gap: .45rem; }
        .action-chip.info { color: #1d4ed8; background: #eff6ff; }
        .action-chip.pending { color: #92400e; background: #fff7ed; }
        .action-btn { border: 0; border-radius: 999px; padding: .55rem .9rem; font-size: .8rem; font-weight: 800; display: inline-flex; align-items: center; gap: .45rem; }
        .action-btn.save { color: #fff; background: linear-gradient(135deg, #0f766e, #14b8a6); }
        .action-btn.delete { color: #fff; background: linear-gradient(135deg, #b91c1c, #ef4444); }
        .dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select { border-radius: 12px; border: 1px solid #d6e0ec; padding: .45rem .7rem; background: #fff; }
        .dataTables_wrapper .dataTables_info { color: #64748b; padding-top: .85rem; }
        .select2-container--default .select2-selection--single { min-height: 38px; border-radius: 12px; border: 1px solid #d6e0ec; display: flex; align-items: center; }
        .select2-container .select2-selection__rendered { padding-left: .85rem !important; line-height: 36px !important; }
        .select2-container .select2-selection__arrow { height: 36px !important; }
        @media (max-width: 768px) { .surface-card { border-radius: 18px; } .table-toolbar { flex-direction: column; } }
    </style>

    <div class="level-shell mt-5">
        <section class="surface-card hero-card">
            <span class="eyebrow"><i class="fas fa-layer-group"></i> Kontrol Level Stock</span>
            <h1 class="hero-title">Level Stock MIP & FG yang lebih jelas</h1>
            <p class="hero-copy">Tentukan hari kerja, isi QTY per box, lalu sistem hitung otomatis nilai minimum, safety stock, dan maksimum agar keputusan stok lebih konsisten dan mudah dicek.</p>
            <div class="hero-metrics">
                <div class="metric-card"><span class="label">Periode aktif</span><span class="value" id="metricPeriod">{{ \Carbon\Carbon::create()->month((int) $selectedBulan)->translatedFormat('F') }} {{ $selectedTahun }}</span></div>
                <div class="metric-card"><span class="label">Hari kerja > 100 set</span><span class="value" id="metricHariAtas">{{ $latestLevel->jumlah_hari_kerja_atas_100 ?? 0 }}</span></div>
                <div class="metric-card"><span class="label">Hari kerja PO < 100 set</span><span class="value" id="metricHariBawah">{{ $latestLevel->jumlah_hari_kerja_bawah_100 ?? 0 }}</span></div>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="surface-card panel-card h-100">
                    <h2 class="section-title">Filter dan parameter hitung</h2>
                    <p class="section-copy mb-4">Pilih periode aktif dan isi jumlah hari kerja untuk batas PO di atas atau di bawah 100 set.</p>
                    <div class="filter-grid mb-4">
                        <div>
                            <label class="field-label" for="filter_bulan">Bulan</label>
                            <select id="filter_bulan" class="form-select">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ (int) $selectedBulan === $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="filter_tahun">Tahun</label>
                            <select id="filter_tahun" class="form-select">
                                @for ($year = now()->year - 5; $year <= now()->year + 1; $year++)
                                    <option value="{{ $year }}" {{ (int) $selectedTahun === $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="hari_atas_100">Hari kerja untuk PO > 100 set</label>
                            <input type="number" min="0" id="hari_atas_100" class="form-control" value="{{ $latestLevel->jumlah_hari_kerja_atas_100 ?? 0 }}" data-field="jumlah_hari_kerja_atas_100">
                            <div class="field-hint">Dipakai saat total qty bulan ini lebih dari 100 set.</div>
                        </div>
                        <div>
                            <label class="field-label" for="hari_bawah_100">Hari kerja untuk PO &lt; 100 set</label>
                            <input type="number" min="0" id="hari_bawah_100" class="form-control" value="{{ $latestLevel->jumlah_hari_kerja_bawah_100 ?? 0 }}" data-field="jumlah_hari_kerja_bawah_100">
                            <div class="field-hint">Dipakai saat total qty bulan ini tidak lebih dari 100 set.</div>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card"><span class="label">Total baris</span><span class="value" id="summaryRows">0</span></div>
                        <div class="stat-card"><span class="label">Sudah punya QTY/Box</span><span class="value" id="summaryConfigured">0</span></div>
                        <div class="stat-card"><span class="label">Rata-rata Min</span><span class="value" id="summaryMin">0</span></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="surface-card panel-card h-100">
                    <h2 class="section-title">Rumus yang dipakai</h2>
                    <p class="section-copy mb-4">Aturan hitung dibuat transparan supaya user bisa memahami hasil MIP dan FG tanpa menebak-nebak.</p>
                    <div class="d-grid gap-3">
                        <div class="formula-item"><strong>Langkah 1</strong> hitung <strong>total qty bulan ini / hari kerja</strong>.</div>
                        <div class="formula-item"><strong>Langkah 2</strong> nilai <strong>Min</strong> mengikuti Excel: <strong>CEILING(total qty bulan ini / hari kerja, QTY per box)</strong>.</div>
                        <div class="formula-item"><strong>Safety MIP</strong> = <strong>Min x 2</strong> dan <strong>Safety FG</strong> = <strong>Min x 2</strong>.</div>
                        <div class="formula-item"><strong>Max</strong> = <strong>Min x 5</strong>.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="surface-card table-card">
            <div class="table-toolbar">
                <div>
                    <h2 class="section-title mb-1">Tabel level stock</h2>
                    <p class="section-copy">Pilih part number, isi QTY per box, lalu simpan. Nilai Min, Safety, dan Max dihitung otomatis.</p>
                </div>
                <div class="table-toolbar-actions">
                    <span class="table-chip"><i class="fas fa-circle-info"></i> Kolom biru dihitung otomatis</span>
                    <button id="tambah_baris" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Baris</button>
                    <button id="refresh_table" class="btn btn-light-primary"><i class="fas fa-rotate-right"></i> Refresh Data</button>
                </div>
            </div>

            <div class="table-help mb-3">
                <span class="help-pill"><i class="fas fa-arrow-pointer"></i> Pilih part number untuk isi customer, project, dan model otomatis.</span>
                <span class="help-pill"><i class="fas fa-calculator"></i> Isi QTY/Set Box agar rumus Min, Safety, dan Max langsung dihitung.</span>
                <span class="help-pill"><i class="fas fa-floppy-disk"></i> Baris baru perlu disimpan sekali, setelah itu perubahan tersimpan otomatis.</span>
            </div>

            <div class="table-responsive">
                <table id="levelstock_table" class="table align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Part Number</th>
                            <th>Customer</th>
                            <th>Kode Projek</th>
                            <th>Models</th>
                            <th>Min</th>
                            <th>Safety MIP</th>
                            <th>Safety FG</th>
                            <th>Max</th>
                            <th>QTY/Set Box</th>
                            <th>Aksi Baris</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.currentLevelStokId = {{ $latestLevel?->id ?? 'null' }};
        const masterItems = @json($masterItems);

        $(function () {
            // Disable scroll-to-change on number inputs
            $(document).on('wheel', 'input[type=number]', function (e) {
                if ($(this).is(':focus')) {
                    $(this).blur();
                }
            });

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1600,
                timerProgressBar: true,
            });

            function showToast(message, icon = 'success') {
                Toast.fire({ icon, title: message });
            }

            function formatNumber(value) {
                return new Intl.NumberFormat('id-ID').format(parseInt(value, 10) || 0);
            }

            function getFilterParams() {
                return {
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val()
                };
            }

            function updateHeroPeriod() {
                $('#metricPeriod').text(`${$('#filter_bulan option:selected').text()} ${$('#filter_tahun').val()}`);
            }

            function updateHariMetrics() {
                $('#metricHariAtas').text($('#hari_atas_100').val() || 0);
                $('#metricHariBawah').text($('#hari_bawah_100').val() || 0);
            }

            function updateSummary() {
                const rows = $('#levelstock_table tbody tr').filter(function () {
                    return $(this).find('td').length > 1;
                });

                let configured = 0;
                let totalMin = 0;

                rows.each(function () {
                    const qty = parseInt($(this).find('input[name="qty_set_box"]').val(), 10) || 0;
                    const min = parseInt($(this).find('input[name="min"]').val(), 10) || 0;
                    if (qty > 0) configured += 1;
                    totalMin += min;
                });

                $('#summaryRows').text(formatNumber(rows.length));
                $('#summaryConfigured').text(formatNumber(configured));
                $('#summaryMin').text(formatNumber(rows.length ? Math.round(totalMin / rows.length) : 0));
            }

            function getPartOptions(selectedPartNumber = '') {
                return masterItems.map(item => {
                    const selected = selectedPartNumber === item.part_number ? 'selected' : '';
                    return `<option value="${item.part_number}" ${selected}>${item.part_number}</option>`;
                }).join('');
            }

            function enhanceSelect2($scope) {
                $scope.find('.select-part-number').select2({
                    placeholder: '-- Pilih --',
                    allowClear: true,
                    width: '100%'
                });
            }

            function getActionMarkup(isManual) {
                if (!isManual) {
                    return `
                        <div class="action-stack">
                            <span class="action-chip info"><i class="fas fa-wand-magic-sparkles"></i> Hitung otomatis saat QTY/Box diisi</span>
                            <span class="action-chip pending"><i class="fas fa-cloud-arrow-up"></i> Perubahan tersimpan otomatis</span>
                        </div>
                    `;
                }

                return `
                    <div class="action-stack">
                        <span class="action-chip pending"><i class="fas fa-circle-exclamation"></i> Baris baru belum tersimpan</span>
                        <div class="action-row">
                            <button type="button" class="action-btn save simpan-row"><i class="fas fa-check"></i> Simpan Baris</button>
                            <button type="button" class="action-btn delete hapus-row"><i class="fas fa-trash"></i> Hapus</button>
                        </div>
                    </div>
                `;
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

            function normalizeNumberInput($input) {
                const value = parseInt($input.val(), 10);
                $input.val(Number.isNaN(value) ? '' : Math.max(value, 0));
            }

            function syncCalculatedFields($row, values) {
                $row.find('input[name="min"]').val(values.min);
                $row.find('input[name="safety_mip"]').val(values.safetyMip);
                $row.find('input[name="safety_fg"]').val(values.safetyFg);
                $row.find('input[name="max"]').val(values.max);
                updateSummary();
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

            function saveRow($row, successMessage, silent = false) {
                const data = buildRowPayload($row);

                if (!data.part_number) {
                    Swal.fire('Part number wajib dipilih', 'Pilih part number terlebih dulu sebelum menyimpan.', 'warning');
                    return $.Deferred().reject().promise();
                }

                return $.post('{{ route("datastock.levelstock.detail.store") }}', data)
                    .done(res => {
                        if (res.id) $row.attr('data-id', res.id);

                        if ($row.hasClass('manual-row')) {
                            $row.removeClass('manual-row');
                            $row.find('td:last').html(getActionMarkup(false));
                        }

                        updateSummary();
                        if (!silent) showToast(successMessage);
                    })
                    .fail(xhr => {
                        const message = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Gagal menyimpan data level stock.';
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
                    Swal.fire('Gagal hitung', 'Data rekap untuk part ini tidak berhasil diambil.', 'error');
                });
            }

            const table = $('#levelstock_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                scrollX: true,
                scrollY: '58vh',
                responsive: false,
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route("datastock.levelstock.data") }}',
                    data: d => Object.assign(d, getFilterParams())
                },
                columns: [
                    { data: null, render: (data, type, row, meta) => meta.row + 1, orderable: false, searchable: false },
                    {
                        data: 'part_number',
                        render: data => `
                            <select class="form-select form-select-sm select-part-number" name="part_number">
                                <option value="">-- Pilih --</option>
                                ${getPartOptions(data ?? '')}
                            </select>
                        `
                    },
                    ...['customer', 'kode_projek', 'models'].map(field => ({
                        data: field,
                        render: d => `<input type="text" class="form-control form-control-sm" name="${field}" value="${d ?? ''}" readonly>`
                    })),
                    ...['min', 'safety_mip', 'safety_fg', 'max'].map(field => ({
                        data: field,
                        render: d => `<input type="number" class="form-control form-control-sm calc-input" name="${field}" value="${d ?? ''}" readonly>`
                    })),
                    { data: 'qty_set_box', render: d => `<input type="number" min="0" class="form-control form-control-sm" name="qty_set_box" value="${d ?? ''}">` },
                    { data: null, className: 'action-cell', orderable: false, searchable: false, render: () => getActionMarkup(false) }
                ],
                rowCallback: function (row, data) {
                    $(row).attr('data-id', data.id || '');
                },
                drawCallback: function () {
                    enhanceSelect2($('#levelstock_table'));
                    updateSummary();
                },
                language: {
                    searchPlaceholder: 'Cari part, customer, atau model...',
                    search: '',
                    zeroRecords: 'Belum ada data level stock untuk periode ini.',
                    info: 'Menampilkan _TOTAL_ baris level stock',
                    infoEmpty: 'Belum ada data level stock'
                }
            });

            $('#filter_bulan, #filter_tahun').on('change', function () {
                loadHariKerja().always(() => table.ajax.reload(updateSummary, false));
            });

            $('#refresh_table').on('click', function () {
                const adaManual = $('#levelstock_table tbody .manual-row').length > 0;
                if (!adaManual) {
                    table.ajax.reload(updateSummary, false);
                    return;
                }

                Swal.fire({
                    title: 'Refresh data periode?',
                    text: 'Baris baru yang belum disimpan akan hilang saat refresh.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, refresh',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) table.ajax.reload(updateSummary, false);
                });
            });

            $('#hari_atas_100, #hari_bawah_100').on('input blur', function () {
                normalizeNumberInput($(this));
                updateHariMetrics();
            });

            $('#hari_atas_100, #hari_bawah_100').on('blur', function () {
                $.post('{{ route("datastock.levelstock.updateHariKerja") }}', {
                    _token: '{{ csrf_token() }}',
                    field: $(this).data('field'),
                    value: parseInt($(this).val(), 10) || 0,
                    ...getFilterParams()
                }).done(() => {
                    updateHariMetrics();
                    showToast('Hari kerja diperbarui');
                    $('#levelstock_table tbody tr').each(function () {
                        const $row = $(this);
                        calculateLevelStock($row).done(() => {
                            if (!$row.hasClass('manual-row') && $row.find('select[name="part_number"]').val()) {
                                saveRow($row, 'Perubahan level stock tersimpan', true);
                            }
                        });
                    });
                }).fail(() => Swal.fire('Error', 'Gagal menyimpan jumlah hari kerja.', 'error'));
            });

            $('#levelstock_table').on('change', '.select-part-number', function () {
                const partNumber = $(this).val();
                const selectedItem = masterItems.find(item => item.part_number === partNumber);
                const $row = $(this).closest('tr');

                if (!selectedItem) {
                    $row.find('input[name="customer"], input[name="kode_projek"], input[name="models"]').val('');
                    calculateLevelStock($row);
                    return;
                }

                $row.find('input[name="customer"]').val(selectedItem.customer || '');
                $row.find('input[name="kode_projek"]').val(selectedItem.kode_project || '');
                $row.find('input[name="models"]').val(selectedItem.nama_part || '');
                calculateLevelStock($row).done(() => {
                    if (!$row.hasClass('manual-row')) {
                        saveRow($row, 'Perubahan level stock tersimpan');
                    }
                });
            });

            $('#levelstock_table').on('input', 'input[name="qty_set_box"]', function () {
                normalizeNumberInput($(this));
                updateSummary();
            });

            $('#levelstock_table').on('blur', 'input[name="qty_set_box"]', function () {
                normalizeNumberInput($(this));
                const $row = $(this).closest('tr');
                calculateLevelStock($row).done(() => {
                    if ($row.hasClass('manual-row')) {
                        updateSummary();
                        return;
                    }

                    saveRow($row, 'Perubahan level stock tersimpan');
                });
            });

            $('#tambah_baris').on('click', function () {
                $('#levelstock_table tbody').prepend(`
                    <tr class="manual-row">
                        <td>#</td>
                        <td><select class="form-select form-select-sm select-part-number" name="part_number"><option value="">-- Pilih --</option>${getPartOptions()}</select></td>
                        <td><input type="text" class="form-control form-control-sm" name="customer" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="kode_projek" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="models" readonly></td>
                        <td><input type="number" class="form-control form-control-sm calc-input" name="min" readonly></td>
                        <td><input type="number" class="form-control form-control-sm calc-input" name="safety_mip" readonly></td>
                        <td><input type="number" class="form-control form-control-sm calc-input" name="safety_fg" readonly></td>
                        <td><input type="number" class="form-control form-control-sm calc-input" name="max" readonly></td>
                        <td><input type="number" min="0" class="form-control form-control-sm" name="qty_set_box"></td>
                        <td class="action-cell">${getActionMarkup(true)}</td>
                    </tr>
                `);
                enhanceSelect2($('#levelstock_table tbody tr:first'));
                updateSummary();
                setTimeout(() => table.columns.adjust(), 10);
            });

            $('#levelstock_table').on('click', '.hapus-row', function () {
                $(this).closest('tr').remove();
                updateSummary();
            });

            $('#levelstock_table').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                ensureLevelStokId().done(() => saveRow($row, 'Baris baru berhasil disimpan'));
            });

            loadHariKerja().always(() => {
                updateHeroPeriod();
                updateHariMetrics();
                updateSummary();
            });
        });
    </script>
</x-default-layout>
