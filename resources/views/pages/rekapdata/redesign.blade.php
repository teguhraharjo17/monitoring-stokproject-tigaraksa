<x-default-layout>
    @section('title', 'Rekap Data')

    <div class="rekap-shell my-5">
        <section class="rekap-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-7">
                        <span class="hero-chip mb-4"><i class="fas fa-table-columns me-2"></i>Rekap data stok bulanan</span>
                        <h1 class="text-white fw-bolder mb-3">Input dan cek rekap stok dalam tampilan yang lebih mudah dipahami</h1>
                        <p class="hero-copy mb-0">Halaman ini dirapikan agar user bisa lebih cepat memilih periode, menambah baris, mengisi angka inti, dan langsung memahami total stok serta selisihnya.</p>
                    </div>
                    <div class="col-xl-5">
                        <div class="hero-stats">
                            <div class="hero-stat"><span class="label">Total Baris</span><span class="value" id="summary_rows">0</span><span class="note">Baris sesuai filter aktif</span></div>
                            <div class="hero-stat"><span class="label">Total Stock</span><span class="value" id="summary_stock">0</span><span class="note">Akumulasi stok terhitung</span></div>
                            <div class="hero-stat"><span class="label">Total Kebutuhan</span><span class="value" id="summary_qty">0</span><span class="note">OS bulan lalu + PO bulan ini</span></div>
                            <div class="hero-stat"><span class="label">Total Selisih</span><span class="value" id="summary_gap">0</span><span class="note">Beda stok versus kebutuhan</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card control-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-stretch">
                    <div class="col-xl-8">
                        <div class="panel-box">
                            <div class="panel-title">Filter data rekap</div>
                            <div class="panel-subtitle">Pilih bulan, tahun, dan customer untuk fokus ke data yang sedang Anda kerjakan.</div>
                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-4">
                                    <label for="filter_bulan" class="form-label fw-semibold">Bulan</label>
                                    <select id="filter_bulan" class="form-select form-select-solid">
                                        @for ($m = 1; $m <= 12; $m++)
                                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_tahun" class="form-label fw-semibold">Tahun</label>
                                    <select id="filter_tahun" class="form-select form-select-solid">
                                        @php($tahunSekarang = now()->year)
                                        @for ($y = $tahunSekarang; $y >= $tahunSekarang - 5; $y--)
                                            <option value="{{ $y }}" {{ $y == $tahunSekarang ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="filter_customer" class="form-label fw-semibold">Customer</label>
                                    <select id="filter_customer" class="form-select form-select-solid">
                                        <option value="">Semua Customer</option>
                                        @foreach ($masterItems->pluck('customer')->unique()->sort() as $customer)
                                            <option value="{{ $customer }}">{{ $customer }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="panel-box h-100">
                            <div class="panel-title">Aksi cepat</div>
                            <div class="panel-subtitle">Tambah baris baru atau refresh tabel tanpa kehilangan konteks kerja.</div>
                            <div class="quick-metrics mt-4">
                                <div class="quick-metric"><span class="label">Customer aktif</span><span class="value" id="summary_customer">Semua</span></div>
                                <div class="quick-metric"><span class="label">Periode</span><span class="value" id="summary_period">-</span></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button id="tambah_row" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Baris</button>
                                <button id="refresh_table" class="btn btn-light-primary"><i class="fas fa-rotate-right me-2"></i>Refresh</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card table-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
                    <div>
                        <div class="panel-title mb-1">Tabel rekap bulanan</div>
                        <div class="panel-subtitle">Kolom hijau adalah sumber stok, kolom merah adalah kebutuhan, dan kolom biru adalah hasil perhitungan otomatis.</div>
                    </div>
                    <div class="table-legend">
                        <span class="legend-item"><span class="legend-box stok"></span>Komponen stok</span>
                        <span class="legend-item"><span class="legend-box kebutuhan"></span>Kebutuhan / order</span>
                        <span class="legend-item"><span class="legend-box hasil"></span>Hasil hitung otomatis</span>
                    </div>
                </div>
                <div class="table-wrap">
                    <table id="rekap_table" class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Part Number</th>
                                <th>Customer</th>
                                <th>Kode Project</th>
                                <th>Models</th>
                                <th>Stock Awal MIP</th>
                                <th>Stock Awal FG</th>
                                <th>WIP + SPK SA</th>
                                <th>Total Stock</th>
                                <th>O/S Bulan Lalu</th>
                                <th>PO Bulan Ini</th>
                                <th>Total QTY Bulan Ini</th>
                                <th>Selisih Stock</th>
                                <th>Aksi Baris</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <style>
        .rekap-shell { display: grid; gap: 1.5rem; }
        .rekap-hero { border: 0; border-radius: 28px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #0f4c81 0%, #1d4ed8 44%, #0f766e 100%); box-shadow: 0 24px 60px rgba(15, 76, 129, .18); }
        .rekap-hero .card-body { padding: 2rem; }
        .hero-chip { display: inline-flex; align-items: center; padding: .6rem .95rem; border-radius: 999px; background: rgba(255,255,255,.14); color: #fff; font-size: .9rem; }
        .hero-copy { color: rgba(255,255,255,.82); font-size: 1rem; max-width: 720px; }
        .hero-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .hero-stat { padding: 1rem 1.1rem; border-radius: 20px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); color: #fff; }
        .hero-stat .label, .quick-metric .label { display: block; font-size: .76rem; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.72); }
        .hero-stat .value, .quick-metric .value { display: block; margin-top: .35rem; font-weight: 700; font-size: 1.5rem; line-height: 1.1; }
        .hero-stat .note { display: block; margin-top: .45rem; color: rgba(255,255,255,.74); font-size: .84rem; }
        .control-panel, .table-panel { border: 0; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 16px 40px rgba(15, 23, 42, .07); }
        .panel-box { height: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 1rem; }
        .panel-title { font-size: 1.1rem; font-weight: 700; color: #12324a; }
        .panel-subtitle { color: #64748b; font-size: .92rem; }
        .quick-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; }
        .quick-metric { padding: .9rem 1rem; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .quick-metric .label { color: #64748b; }
        .quick-metric .value { color: #0f172a; font-size: 1.1rem; }
        .table-legend { display: flex; flex-wrap: wrap; gap: .75rem; }
        .legend-item { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .8rem; border-radius: 999px; background: #fff; border: 1px solid #e2e8f0; color: #334155; font-size: .88rem; font-weight: 600; }
        .legend-box { width: 12px; height: 12px; border-radius: 4px; }
        .legend-box.stok { background: #dcfce7; border: 1px solid #86efac; } .legend-box.kebutuhan { background: #fee2e2; border: 1px solid #fca5a5; } .legend-box.hasil { background: #dbeafe; border: 1px solid #93c5fd; }
        .table-wrap { overflow: auto; border: 1px solid #cbd5e1; border-radius: 20px; background: #fff; }
        #rekap_table { width: 100%; }
        #rekap_table thead th { background: #f8fafc; color: #475569; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid #e2e8f0; }
        #rekap_table tbody tr:hover { background-color: #f8fbff; }
        #rekap_table tbody td { vertical-align: middle; }
        .form-control.form-control-sm, .form-select.form-select-sm { min-width: 110px; }
        .btn-xs { font-size: .75rem; padding: 4px 10px; line-height: 1.3; min-width: 90px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; transition: all 0.2s ease; background: #f8f9fa; color: #333; }
        .btn-xs:hover { background-color: #0d6efd; color: #fff; border-color: #0d6efd; }
        .input-hijau { background-color: #dcfce7 !important; }
        .input-hijau-gelap { background-color: #bbf7d0 !important; }
        .input-merah { background-color: #fee2e2 !important; }
        .input-merah-gelap { background-color: #fecaca !important; }
        .input-biru { background-color: #dbeafe !important; font-weight: bold; color: #1d4ed8; }
        .action-cell { min-width: 190px; }
        .action-stack { display: flex; align-items: center; justify-content: center; gap: .5rem; flex-wrap: wrap; }
        .action-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 600; line-height: 1; }
        .action-chip.info { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .action-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .85rem; border-radius: 10px; font-size: .8rem; font-weight: 700; border: 0; }
        .action-btn.save { background: #166534; color: #fff; }
        .action-btn.delete { background: #b91c1c; color: #fff; }
        .action-btn:hover { opacity: .92; }
        .dataTables_wrapper .dataTables_filter { display: none; }
        .dataTables_wrapper .dataTables_info { color: #64748b; padding-top: 1rem; }
        .dataTables_scrollBody { scrollbar-width: thin; scrollbar-color: #bbb #f0f0f0; }
        .dataTables_scrollBody::-webkit-scrollbar { height: 6px; width: 6px; }
        .dataTables_scrollBody::-webkit-scrollbar-thumb { background-color: #bbb; border-radius: 4px; }
        #filter_customer + .select2 { display: block !important; }
        @media (max-width: 991.98px) { .rekap-hero .card-body { padding: 1.5rem; } .hero-stats, .quick-metrics { grid-template-columns: 1fr; } }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            const masterItems = @json($masterItems);
            const readonlyFields = ['total_stock', 'total_qty_bulan_ini', 'selisih_stock'];

            // Disable scroll-to-change on number inputs
            $(document).on('wheel', 'input[type=number]', function (e) {
                if ($(this).is(':focus')) {
                    $(this).blur();
                }
            });

            function updateSummary(meta = {}) {
                const rows = meta.rows ?? 0;
                const totalStock = meta.totalStock ?? 0;
                const totalQty = meta.totalQty ?? 0;
                const totalGap = meta.totalGap ?? 0;
                const bulanLabel = $('#filter_bulan option:selected').text();
                const tahunLabel = $('#filter_tahun').val();
                $('#summary_rows').text(Number(rows).toLocaleString('id-ID'));
                $('#summary_stock').text(Number(totalStock).toLocaleString('id-ID'));
                $('#summary_qty').text(Number(totalQty).toLocaleString('id-ID'));
                $('#summary_gap').text(Number(totalGap).toLocaleString('id-ID'));
                $('#summary_customer').text($('#filter_customer').val() || 'Semua');
                $('#summary_period').text((bulanLabel || '-') + ' ' + (tahunLabel || ''));
            }

            function getColorClass(key) {
                if (['stock_awal_mip', 'stock_awal_fg', 'wip_spk_sa'].includes(key)) return 'input-hijau';
                if (key === 'total_stock') return 'input-hijau-gelap';
                if (['os_bulan_lalu', 'po_bulan_ini'].includes(key)) return 'input-merah';
                if (key === 'total_qty_bulan_ini') return 'input-merah-gelap';
                if (key === 'selisih_stock') return 'input-biru';
                return '';
            }

            function hitungOtomatis($row) {
                const getVal = (name) => parseFloat($row.find('[name="' + name + '"]').val()) || 0;
                const totalStock = getVal('stock_awal_mip') + getVal('stock_awal_fg') + getVal('wip_spk_sa');
                const totalQty = getVal('os_bulan_lalu') + getVal('po_bulan_ini');
                const selisih = Math.abs(totalStock - totalQty);
                $row.find('[name="total_stock"]').val(totalStock);
                $row.find('[name="total_qty_bulan_ini"]').val(totalQty);
                $row.find('[name="selisih_stock"]').val(selisih);
            }

            function showToast(message, icon = 'success') {
                Swal.fire({ toast: true, position: 'top-end', icon, title: message, showConfirmButton: false, timer: 1500, timerProgressBar: true });
            }

            function buildPartOptions(selectedValue = '') {
                let options = '<option value="">-- Pilih Part Number --</option>';
                masterItems.forEach((item) => {
                    const selected = item.part_number === selectedValue ? 'selected' : '';
                    options += '<option value="' + item.part_number + '" ' + selected + ' data-customer="' + item.customer + '" data-kode_project="' + item.kode_project + '" data-models="' + item.nama_part + '">' + item.part_number + '</option>';
                });
                return options;
            }

            function buildCell(key, value = '') {
                if (key === 'part_number') {
                    return '<select class="form-select form-select-sm editable-cell select-part-number" name="part_number">' + buildPartOptions(value) + '</select>';
                }
                if (['customer', 'kode_project', 'models'].includes(key)) {
                    return '<input type="text" class="form-control form-control-sm editable-cell" name="' + key + '" value="' + (value ?? '') + '" readonly>';
                }
                const readonly = readonlyFields.includes(key) ? 'readonly' : '';
                return '<input type="number" class="form-control form-control-sm editable-cell ' + getColorClass(key) + '" name="' + key + '" value="' + (value ?? '') + '" ' + readonly + '>';
            }

            function inisialisasiSelect2($context = $(document)) {
                $context.find('.select-part-number').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
                    $select.select2({ dropdownParent: $select.parent(), placeholder: '-- Pilih Part Number --', allowClear: true, width: '100%' });
                });
            }

            const table = $('#rekap_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                scrollX: true,
                scrollY: '60vh',
                responsive: false,
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route("datastock.rekap.data") }}',
                    data: function (d) {
                        d.bulan = $('#filter_bulan').val();
                        d.tahun = $('#filter_tahun').val();
                        d.customer = $('#filter_customer').val();
                    },
                    dataSrc: function (json) {
                        const rows = json.data || [];
                        updateSummary({
                            rows: rows.length,
                            totalStock: rows.reduce((sum, row) => sum + (parseFloat(row.total_stock) || 0), 0),
                            totalQty: rows.reduce((sum, row) => sum + (parseFloat(row.total_qty_bulan_ini) || 0), 0),
                            totalGap: rows.reduce((sum, row) => sum + (parseFloat(row.selisih_stock) || 0), 0)
                        });
                        return rows;
                    }
                },
                columns: [
                    { data: null, orderable: false, searchable: false, render: (d, t, r, m) => m.row + 1 },
                    { data: 'part_number',  name: 'master_items.part_number' },
                    { data: 'customer',     name: 'master_items.customer' },
                    { data: 'kode_project', name: 'master_items.kode_project' },
                    { data: 'models',       name: 'master_items.nama_part' },
                    { data: 'stock_awal_mip',      name: 'rekap_data.stock_awal_mip' },
                    { data: 'stock_awal_fg',       name: 'rekap_data.stock_awal_fg' },
                    { data: 'wip_spk_sa',          name: 'rekap_data.wip_spk_sa' },
                    { data: 'total_stock',         name: 'rekap_data.total_stock' },
                    { data: 'os_bulan_lalu',       name: 'rekap_data.os_bulan_lalu' },
                    { data: 'po_bulan_ini',        name: 'rekap_data.po_bulan_ini' },
                    { data: 'total_qty_bulan_ini', name: 'rekap_data.total_qty_bulan_ini' },
                    { data: 'selisih_stock',       name: 'rekap_data.selisih_stock' },
                    { data: null, orderable: false, searchable: false, className: 'action-cell', render: () => '<div class="action-stack"><span class="action-chip info"><i class="fas fa-pen-to-square"></i>Edit langsung di sel</span></div>' }
                ],
                createdRow: function (row, data) {
                    $(row).attr('data-id', data.id);
                    $(row).find('td').each(function (index) {
                        if (index === 0 || index === 13) return;
                        const key = table.settings().init().columns[index].data;
                        $(this).html(buildCell(key, data[key]));
                    });
                    inisialisasiSelect2($(row));
                }
            });

            $('#filter_customer').select2({
                placeholder: 'Semua Customer',
                allowClear: true,
                width: '100%'
            });

            $('#rekap_table tbody').on('input', 'input', function () {
                hitungOtomatis($(this).closest('tr'));
            });

            $('#rekap_table tbody').on('blur', 'input.editable-cell', function () {
                const $row = $(this).closest('tr');
                setTimeout(() => {
                    hitungOtomatis($row);
                    const data = {
                        _token: '{{ csrf_token() }}',
                        id: $row.attr('data-id') || null,
                        bulan: $('#filter_bulan').val(),
                        tahun: $('#filter_tahun').val(),
                        part_number: $row.find('[name="part_number"]').val(),
                        customer: $row.find('[name="customer"]').val(),
                        kode_project: $row.find('[name="kode_project"]').val(),
                        models: $row.find('[name="models"]').val(),
                        stock_awal_mip: $row.find('[name="stock_awal_mip"]').val() || 0,
                        stock_awal_fg: $row.find('[name="stock_awal_fg"]').val() || 0,
                        wip_spk_sa: $row.find('[name="wip_spk_sa"]').val() || 0,
                        os_bulan_lalu: $row.find('[name="os_bulan_lalu"]').val() || 0,
                        po_bulan_ini: $row.find('[name="po_bulan_ini"]').val() || 0,
                        total_stock: $row.find('[name="total_stock"]').val() || 0,
                        total_qty_bulan_ini: $row.find('[name="total_qty_bulan_ini"]').val() || 0,
                        selisih_stock: $row.find('[name="selisih_stock"]').val() || 0
                    };
                    $.post('{{ route("datastock.rekap.store") }}', data)
                        .done((res) => {
                            if (!$row.attr('data-id') && res.id) $row.attr('data-id', res.id);
                            showToast('Berhasil disimpan');
                        })
                        .fail((xhr) => {
                            let message = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gagal menyimpan';
                            showToast(message, 'error');
                        });
                }, 150);
            });

            $('#tambah_row').on('click', function () {
                const newRow = '<tr class="manual-row"><td>#</td><td><select class="form-select form-select-sm select-part-number" name="part_number">' + buildPartOptions() + '</select></td><td><input type="text" class="form-control form-control-sm" name="customer" readonly></td><td><input type="text" class="form-control form-control-sm" name="kode_project" readonly></td><td><input type="text" class="form-control form-control-sm" name="models" readonly></td><td><input type="number" class="form-control form-control-sm input-hijau" name="stock_awal_mip"></td><td><input type="number" class="form-control form-control-sm input-hijau" name="stock_awal_fg"></td><td><input type="number" class="form-control form-control-sm input-hijau" name="wip_spk_sa"></td><td><input type="number" class="form-control form-control-sm input-hijau-gelap" name="total_stock" readonly></td><td><input type="number" class="form-control form-control-sm input-merah" name="os_bulan_lalu"></td><td><input type="number" class="form-control form-control-sm input-merah" name="po_bulan_ini"></td><td><input type="number" class="form-control form-control-sm input-merah-gelap" name="total_qty_bulan_ini" readonly></td><td><input type="number" class="form-control form-control-sm input-biru" name="selisih_stock" readonly></td><td class="action-cell"><div class="action-stack"><button class="action-btn save simpan-row" title="Simpan baris baru"><i class="fas fa-check"></i>Simpan</button><button class="action-btn delete hapus-row" title="Hapus baris baru"><i class="fas fa-trash"></i>Hapus</button></div></td></tr>';
                $('#rekap_table tbody').prepend(newRow);
                inisialisasiSelect2($('#rekap_table tbody tr.manual-row:first'));
            });

            $('#rekap_table tbody').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                hitungOtomatis($row);
                const data = {};
                $row.find('input[name], select[name]').each(function () {
                    data[$(this).attr('name')] = $(this).val();
                });
                data._token = '{{ csrf_token() }}';
                data.bulan = $('#filter_bulan').val();
                data.tahun = $('#filter_tahun').val();
                if (!data.part_number) return showToast('Part number belum dipilih', 'error');
                $.post('{{ route("datastock.rekap.store") }}', data)
                    .done((res) => {
                        showToast('Data baru berhasil disimpan');
                        table.ajax.reload(null, false);
                        $row.remove();
                    })
                    .fail((xhr) => {
                        showToast(xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gagal menyimpan', 'error');
                    });
            });

            $('#filter_bulan, #filter_tahun, #filter_customer').on('change', function () {
                table.ajax.reload(null, false);
            });

            $('#rekap_table').on('draw.dt', function () {
                table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            });

            $('#rekap_table tbody').on('click', '.hapus-row', function () {
                const $row = $(this).closest('tr');
                Swal.fire({
                    title: 'Hapus baris ini?',
                    text: 'Baris manual ini akan dibuang.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $row.remove();
                        showToast('Baris berhasil dihapus');
                    }
                });
            });

            $('#rekap_table tbody').on('change', '.select-part-number', function () {
                const selected = $(this).find('option:selected');
                const $row = $(this).closest('tr');
                $row.find('[name="customer"]').val(selected.data('customer') || '');
                $row.find('[name="kode_project"]').val(selected.data('kode_project') || '');
                $row.find('[name="models"]').val(selected.data('models') || '');
                hitungOtomatis($row);
                const id = $row.attr('data-id');
                if (!id) return;
                $.post('{{ route("datastock.rekap.store") }}', {
                    _token: '{{ csrf_token() }}',
                    id,
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val(),
                    part_number: selected.val(),
                    customer: selected.data('customer'),
                    kode_project: selected.data('kode_project'),
                    models: selected.data('models'),
                    stock_awal_mip: $row.find('[name="stock_awal_mip"]').val() || 0,
                    stock_awal_fg: $row.find('[name="stock_awal_fg"]').val() || 0,
                    wip_spk_sa: $row.find('[name="wip_spk_sa"]').val() || 0,
                    os_bulan_lalu: $row.find('[name="os_bulan_lalu"]').val() || 0,
                    po_bulan_ini: $row.find('[name="po_bulan_ini"]').val() || 0,
                    total_stock: $row.find('[name="total_stock"]').val() || 0,
                    total_qty_bulan_ini: $row.find('[name="total_qty_bulan_ini"]').val() || 0,
                    selisih_stock: $row.find('[name="selisih_stock"]').val() || 0
                }).done(() => {
                    showToast('Part number diperbarui');
                }).fail((xhr) => {
                    showToast(xhr.responseJSON?.error || xhr.responseJSON?.message || 'Gagal menyimpan', 'error');
                });
            });

            $('#refresh_table').on('click', function () {
                const adaBarisManual = $('#rekap_table tbody tr.manual-row').length > 0;
                if (!adaBarisManual) {
                    table.ajax.reload(null, false);
                    return showToast('Tabel diperbarui');
                }
                Swal.fire({
                    title: 'Baris manual akan hilang',
                    text: 'Lanjutkan refresh tabel?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        table.ajax.reload(null, false);
                        showToast('Tabel diperbarui');
                    }
                });
            });
            updateSummary();
        });
    </script>
</x-default-layout>
