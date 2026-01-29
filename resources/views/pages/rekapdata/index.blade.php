<x-default-layout>
    @section('title', 'Rekap Data')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Rekap Data</h3>
        </div>
        <div class="card-body">
            <div class="mb-4 d-flex justify-content-start align-items-center gap-3 flex-wrap">
                <div>
                    <label for="filter_bulan" class="mb-1 fw-bold">Pilih Bulan:</label>
                    <select id="filter_bulan" class="form-select form-select-sm" style="width: 200px;">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="filter_tahun" class="mb-1 fw-bold">Pilih Tahun:</label>
                    <select id="filter_tahun" class="form-select form-select-sm" style="width: 200px;">
                        @php
                            $tahunSekarang = now()->year;
                        @endphp
                        @for ($y = $tahunSekarang; $y >= $tahunSekarang - 5; $y--)
                            <option value="{{ $y }}" {{ $y == $tahunSekarang ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="mb-3 d-flex justify-content-end gap-2">
                <button id="tambah_row" class="btn btn-sm btn-primary">
                    + Tambah Baris
                </button>
                <button id="refresh_table" class="btn btn-sm btn-secondary">
                    🔄 Refresh Table
                </button>
            </div>
            <div class="table-responsive">
                <table id="rekap_table" class="table table-bordered table-striped align-middle">
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        #rekap_table tbody tr:hover {
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
        $(function () {
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
                    }
                },
                columns: [
                    {
                        data: null,
                        title: 'No',
                        orderable: false,
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    { data: 'part_number', name: 'part_number' },
                    { data: 'customer', name: 'customer' },
                    { data: 'kode_project', name: 'kode_project' },
                    { data: 'models', name: 'models' },
                    { data: 'stock_awal_mip', name: 'stock_awal_mip' },
                    { data: 'stock_awal_fg', name: 'stock_awal_fg' },
                    { data: 'wip_spk_sa', name: 'wip_spk_sa' },
                    { data: 'total_stock', name: 'total_stock' },
                    { data: 'os_bulan_lalu', name: 'os_bulan_lalu' },
                    { data: 'po_bulan_ini', name: 'po_bulan_ini' },
                    { data: 'total_qty_bulan_ini', name: 'total_qty_bulan_ini' },
                    { data: 'selisih_stock', name: 'selisih_stock' },
                    { data: null, orderable: false, searchable: false, render: () => '' }
                ],
                createdRow: function (row, data, dataIndex) {
                    $(row).attr('data-id', data.id);
                    const readonlyFields = ['total_stock', 'total_qty_bulan_ini', 'selisih_stock'];

                    $(row).find('td').each(function (index) {
                        if (index === 0 || index === 13) return;

                        const columns = table.settings().init().columns;
                        const key = columns[index].data;
                        const value = data[key] || '';
                        const readonly = readonlyFields.includes(key) ? 'readonly' : '';

                        let inputField = '';
                        let colorClass = '';

                        if (['stock_awal_mip', 'stock_awal_fg', 'wip_spk_sa'].includes(key)) {
                            colorClass = 'input-hijau';
                        } else if (key === 'total_stock') {
                            colorClass = 'input-hijau-gelap';
                        } else if (['os_bulan_lalu', 'po_bulan_ini'].includes(key)) {
                            colorClass = 'input-merah';
                        } else if (key === 'total_qty_bulan_ini') {
                            colorClass = 'input-merah-gelap';
                        } else if (key === 'selisih_stock') {
                            colorClass = 'input-biru';
                        }

                        if (key === 'part_number') {
                            let options = `<option value="">-- Pilih --</option>`;
                            const partNumberFromDB = value;

                            const masterItems = @json($masterItems);

                            masterItems.forEach(item => {
                                const selected = item.part_number === partNumberFromDB ? 'selected' : '';
                                options += `<option value="${item.part_number}" ${selected}
                                    data-customer="${item.customer}"
                                    data-kode_project="${item.kode_project}"
                                    data-models="${item.nama_part}">
                                    ${item.part_number}
                                </option>`;
                            });

                            inputField = `
                                <select class="form-select form-select-sm editable-cell select-part-number" name="part_number">
                                    ${options}
                                </select>`;
                        }

                        else if (['customer', 'kode_project', 'models'].includes(key)) {
                            inputField = `<input type="text"
                                class="form-control form-control-sm editable-cell"
                                name="${key}" value="${value}" readonly>`;
                        }

                        else {
                            inputField = `<input type="number"
                                class="form-control form-control-sm editable-cell ${colorClass}"
                                name="${key}" value="${value}" ${readonly}>`;
                        }

                        $(this).html(inputField);
                    });
                    inisialisasiSelect2($(row));
                }
            });

            $('#rekap_table tbody').on('input', 'input', function () {
                const $row = $(this).closest('tr');
                hitungOtomatis($row);
            });

            function hitungOtomatis($row) {
                const getVal = (name) => parseFloat($row.find(`[name="${name}"]`).val()) || 0;

                const stockAwalMIP = getVal('stock_awal_mip');
                const stockAwalFG = getVal('stock_awal_fg');
                const wipSPKSA = getVal('wip_spk_sa');
                const osBulanLalu = getVal('os_bulan_lalu');
                const poBulanIni = getVal('po_bulan_ini');

                const totalStock = stockAwalMIP + stockAwalFG + wipSPKSA;
                const totalQty = osBulanLalu + poBulanIni;
                const selisih = Math.abs(totalStock - totalQty);

                $row.find('[name="total_stock"]').val(totalStock);
                $row.find('[name="total_qty_bulan_ini"]').val(totalQty);
                $row.find('[name="selisih_stock"]').val(selisih);
            }

            $('#rekap_table tbody').on('blur', 'input.editable-cell', function () {
                const $input = $(this);
                const $row = $input.closest('tr');

                setTimeout(() => {
                    let id = $row.attr('data-id') || null;

                    hitungOtomatis($row);

                    const data = {
                        _token: '{{ csrf_token() }}',
                        id: id,
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
                        selisih_stock: $row.find('[name="selisih_stock"]').val() || 0,
                    };

                    $.ajax({
                        url: '{{ route("datastock.rekap.store") }}',
                        method: 'POST',
                        data: data,
                        success: function (res) {
                            if (!id && res.id) {
                                $row.attr('data-id', res.id);
                            }
                            showToast('Berhasil disimpan');
                        },
                        error: function (xhr) {
                            let message = 'Gagal menyimpan';
                            if (xhr.status === 422 && xhr.responseJSON?.error) {
                                message = xhr.responseJSON.error;
                            } else if (xhr.responseJSON?.message) {
                                message = xhr.responseJSON.message;
                            }
                            console.error(xhr.responseJSON || xhr.responseText);
                            showToast(message, 'error');
                        }
                    });
                }, 150);
            });

            function showToast(message, icon = 'success') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: icon,
                    title: message,
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                });
            }

            $('#tambah_row').on('click', function () {
                let options = `<option value="">-- Pilih Part Number --</option>`;

                @foreach ($masterItems as $item)
                    options += `<option value="{{ $item->part_number }}"
                        data-customer="{{ $item->customer }}"
                        data-kode_project="{{ $item->kode_project }}"
                        data-models="{{ $item->nama_part }}">
                        {{ $item->part_number }}
                    </option>`;
                @endforeach

                const newRow = `
                    <tr class="manual-row">
                        <td>#</td>
                        <td>
                            <select class="form-select form-select-sm select-part-number" name="part_number">
                                ${options}
                            </select>
                        </td>
                        <td><input type="text" class="form-control form-control-sm" name="customer" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="kode_project" readonly></td>
                        <td><input type="text" class="form-control form-control-sm" name="models" readonly></td>
                        <td><input type="number" class="form-control form-control-sm input-hijau" name="stock_awal_mip"></td>
                        <td><input type="number" class="form-control form-control-sm input-hijau" name="stock_awal_fg"></td>
                        <td><input type="number" class="form-control form-control-sm input-hijau" name="wip_spk_sa"></td>
                        <td><input type="number" class="form-control form-control-sm input-hijau-gelap" name="total_stock" readonly></td>
                        <td><input type="number" class="form-control form-control-sm input-merah" name="os_bulan_lalu"></td>
                        <td><input type="number" class="form-control form-control-sm input-merah" name="po_bulan_ini"></td>
                        <td><input type="number" class="form-control form-control-sm input-merah-gelap" name="total_qty_bulan_ini" readonly></td>
                        <td><input type="number" class="form-control form-control-sm input-biru" name="selisih_stock" readonly></td>
                        <td class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-success simpan-row" title="Simpan">✔</button>
                            <button class="btn btn-sm btn-danger hapus-row" title="Hapus">✖</button>
                        </td>
                    </tr>
                `;

                $('#rekap_table tbody').prepend(newRow);
                inisialisasiSelect2($('#rekap_table tbody tr.manual-row:first'));
            });

            $('#rekap_table tbody').on('click', '.simpan-row', function () {
                const $row = $(this).closest('tr');
                hitungOtomatis($row);

                const data = {};

                $row.find('input[name], select[name]').each(function () {
                    const $el = $(this);
                    const name = $el.attr('name');
                    let value;

                    if ($el.hasClass('select-part-number')) {
                        value = $el.find('option:selected').val();
                    } else {
                        value = $el.val();
                    }

                    data[name] = value;
                });

                const bulan = $('#filter_bulan').val();
                const tahun = $('#filter_tahun').val();

                data.bulan = bulan;
                data.tahun = tahun;

                if (!data.part_number) {
                    showToast('Part number belum dipilih!', 'error');
                    return;
                }

                $.ajax({
                    url: '{{ route("datastock.rekap.store") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ...data
                    },
                    success: function (res) {
                        showToast('Data baru berhasil disimpan');

                        const newData = {
                            id: res.id,
                            part_number: data.part_number,
                            customer: data.customer,
                            kode_project: data.kode_project,
                            models: data.models,
                            stock_awal_mip: data.stock_awal_mip,
                            stock_awal_fg: data.stock_awal_fg,
                            wip_spk_sa: data.wip_spk_sa,
                            os_bulan_lalu: data.os_bulan_lalu,
                            po_bulan_ini: data.po_bulan_ini,
                            total_stock: data.total_stock,
                            total_qty_bulan_ini: data.total_qty_bulan_ini,
                            selisih_stock: data.selisih_stock,
                            bulan: data.bulan,
                            tahun: data.tahun
                        };

                        const rowNode = table.row.add(newData).node();
                        $(rowNode).attr('data-id', res.id);

                        $(rowNode).find('td').each(function (index) {
                            if (index === 0 || index === 13) return;

                            const columns = table.settings().init().columns;
                            const key = columns[index].data;
                            const value = newData[key] || '';
                            const readonlyFields = ['total_stock', 'total_qty_bulan_ini', 'selisih_stock'];
                            const readonly = readonlyFields.includes(key) ? 'readonly' : '';

                            let inputField = '';
                            let colorClass = '';

                            if (['stock_awal_mip', 'stock_awal_fg', 'wip_spk_sa'].includes(key)) {
                                colorClass = 'input-hijau';
                            } else if (key === 'total_stock') {
                                colorClass = 'input-hijau-gelap';
                            } else if (['os_bulan_lalu', 'po_bulan_ini'].includes(key)) {
                                colorClass = 'input-merah';
                            } else if (key === 'total_qty_bulan_ini') {
                                colorClass = 'input-merah-gelap';
                            } else if (key === 'selisih_stock') {
                                colorClass = 'input-biru';
                            }

                            if (key === 'part_number') {
                                let options = `<option value="">-- Pilih --</option>`;
                                const masterItems = @json($masterItems);
                                masterItems.forEach(item => {
                                    const selected = item.part_number === value ? 'selected' : '';
                                    options += `<option value="${item.part_number}" ${selected}
                                        data-customer="${item.customer}"
                                        data-kode_project="${item.kode_project}"
                                        data-models="${item.nama_part}">
                                        ${item.part_number}
                                    </option>`;
                                });

                                inputField = `
                                    <select class="form-select form-select-sm editable-cell select-part-number" name="part_number">
                                        ${options}
                                    </select>`;
                            } else if (['customer', 'kode_project', 'models'].includes(key)) {
                                inputField = `<input type="text"
                                    class="form-control form-control-sm editable-cell"
                                    name="${key}" value="${value}" readonly>`;
                            } else {
                                inputField = `<input type="number"
                                    class="form-control form-control-sm editable-cell ${colorClass}"
                                    name="${key}" value="${value}" ${readonly}>`;
                            }

                            $(this).html(inputField);
                        });

                        inisialisasiSelect2($(rowNode));

                        $row.remove();
                    },
                    error: function (xhr) {
                        let message = 'Gagal menyimpan';

                        if (xhr.status === 422 && xhr.responseJSON?.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.responseJSON?.message) {
                            message = xhr.responseJSON.message;
                        }

                        console.error(xhr.responseJSON || xhr.responseText);
                        showToast(message, 'error');
                    }
                });
            });

            $('#filter_bulan, #filter_tahun').on('change', function () {
                table.ajax.reload();
            });

            $('#rekap_table').on('draw.dt', function () {
                $('#rekap_table').DataTable().column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            });

            $('#rekap_table tbody').on('click', '.hapus-row', function () {
                const $row = $(this).closest('tr');
                Swal.fire({
                    title: 'Hapus baris ini?',
                    text: 'Baris ini akan dihapus dan tidak disimpan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
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
                const id = $row.attr('data-id');

                // Set field readonly secara otomatis
                $row.find('[name="customer"]').val(selected.data('customer'));
                $row.find('[name="kode_project"]').val(selected.data('kode_project'));
                $row.find('[name="models"]').val(selected.data('models'));

                // Hitung total & selisih otomatis
                hitungOtomatis($row);

                // Kalau baris sudah ada (bukan manual), baru boleh simpan
                if (id) {
                    const data = {
                        _token: '{{ csrf_token() }}',
                        id: id,
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
                        selisih_stock: $row.find('[name="selisih_stock"]').val() || 0,
                    };

                    $.ajax({
                        url: '{{ route("datastock.rekap.store") }}',
                        method: 'POST',
                        data: data,
                        success: function () {
                            showToast('Berhasil memperbarui data');
                        },
                        error: function (xhr) {
                            let message = 'Gagal menyimpan';

                            if (xhr.status === 422 && xhr.responseJSON?.error) {
                                message = xhr.responseJSON.error;
                            } else if (xhr.responseJSON?.message) {
                                message = xhr.responseJSON.message;
                            }

                            console.error(xhr.responseJSON || xhr.responseText);
                            showToast(message, 'error');
                        }
                    });
                }
            });

            

            function inisialisasiSelect2($context = $(document)) {
                $context.find('.select-part-number').each(function () {
                    const $select = $(this);

                    if ($select.hasClass("select2-hidden-accessible")) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        dropdownParent: $select.parent(),
                        placeholder: '-- Pilih Part Number --',
                        allowClear: true,
                        width: '100%'
                    });
                });
            }

            $('#refresh_table').on('click', function () {
                const adaBarisManual = $('#rekap_table tbody tr.manual-row').length > 0;

                if (adaBarisManual) {
                    Swal.fire({
                        title: 'Baris manual akan hilang!',
                        text: 'Apakah kamu yakin ingin me-refresh tabel?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, lanjutkan',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            table.ajax.reload(null, false);
                            showToast('Tabel diperbarui');
                        }
                    });
                } else {
                    table.ajax.reload(null, false);
                    showToast('Tabel diperbarui');
                }
            });
        });
    </script>
</x-default-layout>
