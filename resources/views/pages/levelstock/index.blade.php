<x-default-layout>
    @section('title', 'Level Stock MIP & FG')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Level Stock MIP & FG</h3>
        </div>
        <div class="card-body">
            @php
                $now = now();
                $selectedBulan = request()->bulan ?? $now->format('m');
                $selectedTahun = request()->tahun ?? $now->format('Y');

                $latestLevel = \App\Models\LevelStok::where('bulan', $selectedBulan)->where('tahun', $selectedTahun)->first();
            @endphp

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="filter_bulan" class="form-label">Filter Bulan</label>
                    <select id="filter_bulan" class="form-select">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ (int)$selectedBulan == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="filter_tahun" class="form-label">Filter Tahun</label>
                    <select id="filter_tahun" class="form-select">
                        @for ($year = now()->year - 5; $year <= now()->year + 1; $year++)
                            <option value="{{ $year }}" {{ (int)$selectedTahun == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="hari_atas_100" class="form-label fw-bold">Jumlah Hari Kerja PO QTY > 100 set</label>
                    <input type="number" id="hari_atas_100" class="form-control input-biru" 
                        value="{{ $latestLevel->jumlah_hari_kerja_atas_100 ?? 0 }}" 
                        data-field="jumlah_hari_kerja_atas_100">
                </div>
                <div class="col-md-6">
                    <label for="hari_bawah_100" class="form-label fw-bold">Jumlah Hari Kerja PO QTY < 100 set</label>
                    <input type="number" id="hari_bawah_100" class="form-control input-biru" 
                        value="{{ $latestLevel->jumlah_hari_kerja_bawah_100 ?? 0 }}" 
                        data-field="jumlah_hari_kerja_bawah_100">
                </div>
            </div>

            <div class="table-responsive">
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <button id="tambah_baris" class="btn btn-sm btn-primary">+ Tambah Baris</button>
                    <button id="refresh_table" class="btn btn-sm btn-secondary">🔄 Refresh Table</button>
                </div>
                <table id="levelstock_table" class="table table-bordered table-striped align-middle w-100">
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #levelstock_table tbody tr:hover {
            background-color: #f5f7fa;
            cursor: pointer;
        }

        .dataTables_wrapper .dataTable {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.9rem;
            color: #333;
        }

        .dataTables_wrapper .dataTable th,
        .dataTables_wrapper .dataTable td {
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }

        .dataTables_wrapper .dataTable thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #444;
        }

        .dataTables_wrapper .dataTable tbody tr:nth-child(odd) {
            background-color: #fcfcfc;
        }

        .table-responsive {
            position: relative;
            overflow: visible;
        }

        .custom-button {
            font-size: 0.875rem;
            padding: 6px 12px;
            border-radius: 4px;
            transition: 0.2s ease;
        }

        .custom-button:hover {
            color: #fff;
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn-xs {
            font-size: 0.75rem;
            padding: 4px 10px;
            line-height: 1.3;
            min-width: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
            background: #f8f9fa;
            color: #333;
        }

        .btn-xs:hover {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        @media (max-width: 768px) {
            .dataTables_wrapper .dataTable {
                font-size: 0.8rem;
            }

            .custom-buttons-container {
                justify-content: center;
                margin-bottom: 10px;
            }

            .custom-button {
                margin-bottom: 5px;
            }
        }

        @media (max-width: 576px) {
            .opsi-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }

        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            background-color: #ffffff;
            transition: all 0.3s ease;
        }

        .modal-header {
            background-color: #fdfdfd;
            border-bottom: 1px solid #e6e6e6;
            padding: 1.25rem 1.75rem;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
            color: #222;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            background-color: #fafafa;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .modal.fade .modal-dialog {
            transform: translateY(-10%);
            transition: all 0.25s ease;
        }

        .modal.show .modal-dialog {
            transform: translateY(0);
        }

        .modal-body label {
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            display: block;
        }

        .modal-body input.form-control,
        .modal-body select.form-select {
            border: none;
            border-bottom: 1.5px solid #dcdcdc;
            border-radius: 0;
            background: transparent;
            padding-left: 0;
            padding-right: 0;
            padding-top: 0.4rem;
            padding-bottom: 0.4rem;
            font-size: 0.95rem;
            color: #333;
            box-shadow: none;
            transition: border-color 0.2s ease, color 0.2s ease;
        }

        .modal-body input.form-control:focus,
        .modal-body select.form-select:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: none;
        }

        .modal-body input.form-control::placeholder {
            color: #aaa;
            font-size: 0.9rem;
        }

        .modal-body input.form-control:hover,
        .modal-body select.form-select:hover {
            border-color: #bfbfbf;
        }

        fieldset.border {
            border: 1px dashed #e3e3e3 !important;
            padding: 1.5rem;
            margin-top: 1rem;
            border-radius: 6px;
            position: relative;
        }

        fieldset.border legend {
            float: unset;
            background: #fff;
            padding: 0 0.5rem;
            margin-left: 1rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #444;
        }

        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #cfcfcf #f9f9f9;
        }

        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background-color: #cfcfcf;
            border-radius: 4px;
        }

        .input-hijau {
            background-color: #d6f5d6 !important;
        }
        .input-hijau-gelap {
            background-color: #a8e6a8 !important;
        }

        .input-merah {
            background-color: #fcdcdc !important;
        }
        .input-merah-gelap {
            background-color: #f8a3a3 !important;
        }

        .input-biru {
            background-color: #d8e9ff !important;
            font-weight: bold;
            color: #004085;
        }

        .dataTables_scrollBody {
            scrollbar-width: thin;
            scrollbar-color: #bbb #f0f0f0;
        }

        .dataTables_scrollBody::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .dataTables_scrollBody::-webkit-scrollbar-thumb {
            background-color: #bbb;
            border-radius: 4px;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.currentLevelStokId = {{ $latestLevel?->id ?? 'null' }};
        const masterItems = @json($masterItems);
        $(function () {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
            });

            function showToast(message, icon = 'success') {
                Toast.fire({ icon, title: message });
            }

            function getFilterParams() {
                return {
                    bulan: $('#filter_bulan').val(),
                    tahun: $('#filter_tahun').val()
                };
            }

            function reloadTable() {
                table.ajax.reload();
            }

           function loadHariKerja() {
                const { bulan, tahun } = getFilterParams();

                $.get('{{ route("datastock.levelstock.getHariKerja") }}', { bulan, tahun }, res => {
                    $('#hari_atas_100').val(res.hari_atas_100 ?? 0);
                    $('#hari_bawah_100').val(res.hari_bawah_100 ?? 0);
                });

                $.get('{{ route("datastock.levelstock.getId") }}', { bulan, tahun }, res => {
                    window.currentLevelStokId = res.id ?? null;
                });
            }

            const table = $('#levelstock_table').DataTable({
                processing: true,
                serverSide: true,
                paging: false,
                scrollX: true,
                scrollY: '60vh',
                responsive: false,
                order: [[1, 'asc']],
                ajax: {
                    url: '{{ route("datastock.levelstock.data") }}',
                    data: d => {
                        const { bulan, tahun } = getFilterParams();
                        d.bulan = bulan;
                        d.tahun = tahun;
                    }
                },
                columns: [
                    {
                        data: null,
                        render: (data, type, row, meta) => meta.row + 1,
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'part_number',
                        render: function (data) {
                            const options = masterItems.map(item => {
                                const selected = data === item.part_number ? 'selected' : '';
                                return `<option value="${item.part_number}" ${selected}>${item.part_number}</option>`;
                            }).join('');
                            return `
                                <select class="form-select form-select-sm select-part-number" name="part_number">
                                    <option value="">-- Pilih --</option>
                                    ${options}
                                </select>
                            `;
                        }
                    },
                    ...['customer', 'kode_projek', 'models'].map(field => ({
                        data: field,
                        render: d => `<input type="text" class="form-control form-control-sm" name="${field}" value="${d ?? ''}" readonly>`
                    })),
                    ...['min', 'safety_mip', 'safety_fg', 'max'].map(field => ({
                        data: field,
                        render: d => `<input type="number" class="form-control form-control-sm input-biru" name="${field}" value="${d ?? ''}" readonly>`
                    })),
                    {
                        data: 'qty_set_box',
                        render: d => `<input type="number" class="form-control form-control-sm" name="qty_set_box" value="${d ?? ''}">`
                    },
                    {
                        data: null,
                        render: () => '',
                        orderable: false,
                        searchable: false
                    }
                ],
                rowCallback: function (row, data) {
                    $(row).attr('data-id', data.id);
                },
                drawCallback: function () {
                    $('.select-part-number').select2({
                        placeholder: '-- Pilih --',
                        allowClear: true,
                        width: '100%'
                    });
                },
                language: {
                    searchPlaceholder: "Cari...",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            $('#filter_bulan, #filter_tahun').on('change', function () {
                reloadTable();
                loadHariKerja();
            });

            $('#refresh_table').on('click', function () {
                const adaManual = $('#levelstock_table tbody .manual-row').length > 0;
                if (adaManual) {
                    Swal.fire({
                        title: 'Yakin?',
                        text: 'Baris manual akan hilang saat refresh. Lanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) reloadTable();
                    });
                } else {
                    reloadTable();
                }
            });

            $('#levelstock_table').on('change', '.select-part-number', function () {
                const partNumber = $(this).val();
                const selectedItem = masterItems.find(item => item.part_number === partNumber);
                const $row = $(this).closest('tr');

                if (!selectedItem) return;

                $row.find('input[name="customer"]').val(selectedItem.customer).prop('readonly', true);
                $row.find('input[name="kode_projek"]').val(selectedItem.kode_project).prop('readonly', true);
                $row.find('input[name="models"]').val(selectedItem.nama_part).prop('readonly', true);

                $row.find('input[name="customer"]').trigger('blur');
                hitungLevelStok($row);
            });

            $('#levelstock_table').on('blur', 'input[name="qty_set_box"]', function () {
                const $row = $(this).closest('tr');
                hitungLevelStok($row);
            });

            $('#hari_atas_100, #hari_bawah_100').on('blur', function () {
                const field = $(this).data('field');
                const value = $(this).val();
                const { bulan, tahun } = getFilterParams();

                $.post('{{ route("datastock.levelstock.updateHariKerja") }}', {
                    _token: '{{ csrf_token() }}',
                    field,
                    value,
                    bulan,
                    tahun
                }).done(() => showToast('Hari kerja diperbarui'))
                .fail(() => Swal.fire('Error', 'Gagal menyimpan hari kerja', 'error'));
            });

            function hitungLevelStok($row) {
                const partNumber = $row.find('select[name="part_number"]').val();
                const customer = $row.find('input[name="customer"]').val();
                const kodeProjek = $row.find('input[name="kode_projek"]').val();
                const models = $row.find('input[name="models"]').val();
                const qtyPerBox = parseInt($row.find('input[name="qty_set_box"]').val()) || 0;

                const { bulan, tahun } = getFilterParams();

                if (!partNumber || !qtyPerBox) return;

                $.get('{{ route("datastock.rekap.fetch") }}', {
                    bulan, tahun, part_number: partNumber, customer, kode_project: kodeProjek, models
                }, function (res) {
                    if (!res || !res.total_qty_bulan_ini) return;

                    const totalQty = parseInt(res.total_qty_bulan_ini) || 0;
                    const hariKerja = totalQty <= 100 ? parseInt($('#hari_bawah_100').val()) : parseInt($('#hari_atas_100').val());

                    if (!hariKerja || !qtyPerBox) return;

                    const daily = Math.ceil(totalQty / hariKerja);
                    const min = Math.ceil(daily / qtyPerBox) * qtyPerBox;
                    const safetyMip = min * 2;
                    const safetyFg = min * 2;
                    const max = min * 5;

                    $row.find('input[name="min"]').val(min);
                    $row.find('input[name="safety_mip"]').val(safetyMip);
                    $row.find('input[name="safety_fg"]').val(safetyFg);
                    $row.find('input[name="max"]').val(max);

                    // Set readonly
                    $row.find('input[name="min"]').prop('readonly', true);
                    $row.find('input[name="safety_mip"]').prop('readonly', true);
                    $row.find('input[name="safety_fg"]').prop('readonly', true);
                    $row.find('input[name="max"]').prop('readonly', true);

                    $row.find('input[name="min"]').trigger('change');
                    $row.find('input[name="safety_mip"]').trigger('change');
                    $row.find('input[name="safety_fg"]').trigger('change');
                    $row.find('input[name="max"]').trigger('change');
                });
            }

            $('#levelstock_table tbody').on('blur change', 'input, select', function () {
                const $row = $(this).closest('tr');
                const id = $row.data('id');
                const isManual = $row.hasClass('manual-row');

                const data = {
                    _token: '{{ csrf_token() }}',
                    level_stok_id: window.currentLevelStokId,
                    ...getFilterParams()
                };

                $row.find('input[name], select[name]').each(function () {
                    data[$(this).attr('name')] = $(this).val();
                });

                if (!data.part_number) return;

                if (isManual) return;

                if (id) data.id = id;

                $.post('{{ route("datastock.levelstock.detail.store") }}', data)
                    .done(res => {
                        if (!id && res.id) $row.attr('data-id', res.id);
                        showToast('Perubahan tersimpan');
                    })
                    .fail(() => Swal.fire('Gagal', 'Gagal menyimpan data', 'error'));
            });

            $('#tambah_baris').on('click', function () {
                const options = masterItems.map(item =>
                    `<option value="${item.part_number}">${item.part_number}</option>`
                ).join('');

                const newRow = `
                    <tr class="manual-row">
                        <td>#</td>
                        <td>
                            <select class="form-select form-select-sm select-part-number" name="part_number">
                                <option value="">-- Pilih --</option>
                                ${options}
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="customer" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="kode_projek" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="models" readonly></td>
                        <td><input type="number" class="form-control form-control-sm" name="min" readonly></td>
                        <td><input type="number" class="form-control form-control-sm" name="safety_mip" readonly></td>
                        <td><input type="number" class="form-control form-control-sm" name="safety_fg" readonly></td>
                        <td><input type="number" class="form-control form-control-sm" name="max" readonly></td>
                        <td><input type="number" class="form-control form-control-sm" name="qty_set_box"></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-xs btn-success simpan-row" title="Simpan">✔</button>
                                <button class="btn btn-xs btn-danger hapus-row" title="Hapus">✖</button>
                            </div>
                        </td>
                    </tr>
                `;
                $('#levelstock_table tbody').prepend(newRow);

                $('.select-part-number').select2({
                    placeholder: '-- Pilih --',
                    allowClear: true,
                    width: '100%'
                });

                setTimeout(() => {
                    table.columns.adjust();
                }, 10);
            });


            $('#levelstock_table').on('click', '.hapus-row', function () {
                $(this).closest('tr').remove();
            });

            $('#levelstock_table').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                const data = {
                    _token: '{{ csrf_token() }}',
                    level_stok_id: window.currentLevelStokId,
                    ...getFilterParams()
                };

                $row.find('input[name], select[name]').each(function () {
                    data[$(this).attr('name')] = $(this).val();
                });

                if (!data.part_number) {
                    Swal.fire('Wajib', 'Part number tidak boleh kosong', 'warning');
                    return;
                }

                $.post('{{ route("datastock.levelstock.detail.store") }}', data)
                    .done(res => {
                        showToast('Data baru disimpan');
                        $row.attr('data-id', res.id);
                        $row.removeClass('manual-row');

                        $row.find('.simpan-row').replaceWith(`<span class="badge bg-success">✔ Disimpan</span>`);
                    })
                    .fail(() => Swal.fire('Error', 'Gagal menyimpan data', 'error'));
            });
        });
    </script>
</x-default-layout>
