<x-default-layout>
    @section('title', 'Monitoring Sub Assy')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Monitoring Sub Assy</h3>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end mb-4">
                <!-- Bulan -->
                <div class="col-md-3 col-sm-6">
                    <label for="filter_bulan" class="form-label fw-semibold">Bulan</label>
                    <select id="filter_bulan" class="form-select form-select-sm">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Tahun -->
                <div class="col-md-3 col-sm-6">
                    <label for="filter_tahun" class="form-label fw-semibold">Tahun</label>
                    <select id="filter_tahun" class="form-select form-select-sm">
                        @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Customer -->
                <div class="col-md-3 col-sm-6">
                    <label for="filter_customer" class="form-label fw-semibold">Customer</label>
                    <select id="filter_customer" class="form-select form-select-sm">
                        <option value="">-- Semua Customer --</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="col-md-3 col-sm-6 d-flex gap-2 justify-content-md-end">
                    <button id="reload_table" class="btn btn-primary btn-sm">
                        🔄 Refresh
                    </button>

                    <form id="export_form"
                        action="{{ route('monitoring.subassy.export') }}"
                        method="GET"
                        target="_blank"
                        class="d-inline">
                        <input type="hidden" name="bulan" id="export_bulan">
                        <input type="hidden" name="tahun" id="export_tahun">
                        <button type="submit" class="btn btn-success btn-sm">
                            📤 Export
                        </button>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table id="subassy_table" class="table table-bordered table-striped w-100">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        #subassy_table tbody tr:hover {
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

        #subassy_table th,
        #subassy_table td {
            white-space: nowrap;
        }

        #subassy_table th:nth-child(n+12),
        #subassy_table td:nth-child(n+12) {
            min-width: 50px;
            max-width: 50px;
            text-align: center;
        }

        input[readonly] {
            cursor: not-allowed;
        }

        .form-label {
            font-size: 0.85rem;
            color: #555;
        }

        .card-body {
            background: #fff;
        }

    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function () {
            function getParams() {
                return {
                    bulan: parseInt($('#filter_bulan').val()),
                    tahun: parseInt($('#filter_tahun').val()),
                    customer: $('#filter_customer').val()
                };
            }

            function generateTableHeader(jumlahHari) {
                let thead = `
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">Customer</th>
                        <th rowspan="2">Project</th>
                        <th rowspan="2">Part Number</th>
                        <th rowspan="2">Part Name</th>
                        <th rowspan="2">Total PO</th>
                        <th rowspan="2">WIP Sebelumnya</th>
                        <th rowspan="2">Total SPK</th>
                        <th rowspan="2">Total Produksi</th>
                        <th rowspan="2">WIP Akhir</th>
                        <th rowspan="2">Produktivitas</th>
                        <th rowspan="2">Status</th>
                        <th colspan="${jumlahHari}">Tanggal</th>
                    </tr>
                    <tr>`;

                for (let i = 1; i <= jumlahHari; i++) {
                    thead += `<th>${i}</th>`;
                }

                thead += `</tr>`;
                $('#subassy_table thead').html(thead);
            }

            function generateTableColumns(jumlahHari) {
                const staticCols = [
                    {
                        data: null,
                        render: (data, type, row, meta) => meta.row + 1
                    },
                    { data: 'customer' },
                    { data: 'project' },
                    { data: 'part_number' },
                    { data: 'part_name' },
                    {
                        data: 'total_po',
                        render: d => `<span>${d ?? ''}</span>`
                    },
                    {
                        data: 'wip_sebelumnya',
                        render: d => `<input type="text" name="wip_sebelumnya" class="form-control form-control-sm text-center" value="${d ?? ''}">`
                    },
                    { data: 'total_spk', render: d => `<span>${d ?? ''}</span>` },
                    { data: 'total_produksi', render: d => `<span>${d ?? ''}</span>` },
                    { data: 'wip_akhir', render: d => `<span>${d ?? ''}</span>` },
                    {
                        data: 'produktivitas',
                        render: d => {
                            const val = parseInt(d);
                            return isNaN(val)
                                ? ''
                                : (val < 100
                                    ? `<span style="color:red;font-weight:bold">${val}%</span>`
                                    : `${val}%`);
                        }
                    },
                    {
                        data: null,
                        render: () => `
                            <div class="d-flex flex-column gap-1 text-center">
                                <span class="badge bg-danger">SPK</span>
                                <span class="badge bg-success">Produksi</span>
                                <span class="badge bg-primary">WIP</span>
                            </div>
                        `
                    }
                ];

                const dynamicCols = [];

                for (let i = 1; i <= jumlahHari; i++) {
                    dynamicCols.push({
                        data: null,
                        render: row => {
                            return `
                                <div class="d-flex flex-column gap-1">
                                    <input type="text" name="spk_hari_${i}" class="form-control form-control-sm text-center input-merah" value="${row[`spk_hari_${i}`] ?? ''}" readonly tabindex="-1">
                                    <input type="text" name="produksi_hari_${i}" class="form-control form-control-sm text-center input-hijau" value="${row[`produksi_hari_${i}`] ?? ''}">
                                    <input type="text" name="wip_hari_${i}" class="form-control form-control-sm text-center input-biru" value="${row[`wip_hari_${i}`] ?? ''}" readonly tabindex="-1">
                                </div>
                            `;
                        }
                    });
                }

                return [...staticCols, ...dynamicCols];
            }

            let dt;
            function reloadTable() {
                const params = getParams();
                const { bulan, tahun, customer } = params;
                const jumlahHari = new Date(tahun, bulan, 0).getDate();

                if ($.fn.DataTable.isDataTable('#subassy_table')) {
                    $('#subassy_table').DataTable().clear().destroy();
                }

                $('#subassy_table thead').empty();
                $('#subassy_table tbody').empty();
                generateTableHeader(jumlahHari);

                dt = $('#subassy_table').DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    paging: false,
                    scrollX: true,
                    scrollY: '60vh',
                    order: [[1, 'asc']],
                    ajax: {
                        url: '{{ route("monitoring.subassy.data") }}',
                        type: 'POST',
                        data: d => {
                            d._token = '{{ csrf_token() }}';
                            d.bulan = bulan;
                            d.tahun = tahun;
                            d.customer = customer;
                        }
                    },
                    columns: generateTableColumns(jumlahHari)
                });

                dt.on('draw', function () {
                    $('#subassy_table tbody tr').each(function () {
                        calculateTotals($(this));
                    });
                });
            }

            $('#filter_customer').select2({
                placeholder: '-- Semua Customer --',
                allowClear: true,
                width: '100%'
            });

            $('#reload_table').on('click', function () {
                $('#export_bulan').val($('#filter_bulan').val());
                $('#export_tahun').val($('#filter_tahun').val());
                reloadTable();
            });


            $('#export_form').on('submit', function () {
                $('#export_bulan').val($('#filter_bulan').val());
                $('#export_tahun').val($('#filter_tahun').val());
            });

            function calculateTotals($row) {
                const jumlahHari = $('#subassy_table thead tr:last th').length;
                let totalSPK = 0;
                let totalProduksi = 0;

                const spkValues = [];
                const produksiValues = [];
                const wipValues = [];

                for (let i = 1; i <= jumlahHari; i++) {
                    const spk = parseInt($row.find(`input[name="spk_hari_${i}"]`).val()) || 0;
                    const produksi = parseInt($row.find(`input[name="produksi_hari_${i}"]`).val()) || 0;
                    spkValues[i] = spk;
                    produksiValues[i] = produksi;
                    totalSPK += spk;
                    totalProduksi += produksi;
                }

                const wipSebelumnya = parseInt($row.find('input[name="wip_sebelumnya"]').val()) || 0;
                let wipSebelum = wipSebelumnya;

                for (let i = 1; i <= jumlahHari; i++) {
                    const wip = wipSebelum + spkValues[i] - produksiValues[i];
                    $row.find(`input[name="wip_hari_${i}"]`).val(wip);
                    wipValues[i] = wip;
                    wipSebelum = wip;
                }

                const wipAkhir = wipValues[jumlahHari] || 0;
                const produktivitas = totalSPK > 0 ? Math.ceil((totalProduksi / totalSPK) * 100) : 0;

                $row.find('td:eq(7)').html(`<span>${totalSPK}</span>`);
                $row.find('td:eq(8)').html(`<span>${totalProduksi}</span>`);
                $row.find('td:eq(9)').html(`<span>${wipAkhir}</span>`);
                $row.find('td:eq(10)').html(
                produktivitas < 100
                    ? `<span style="color:red;font-weight:bold">${produktivitas}%</span>`
                    : `${produktivitas}%`
                );
            }

            $('#subassy_table tbody').on('input', 'input[name="wip_sebelumnya"]', function () {
                const $row = $(this).closest('tr');
                const jumlahHari = $('#subassy_table thead tr:last th').length;

                for (let i = 1; i <= jumlahHari; i++) {
                    $row.find(`input[name="produksi_hari_${i}"]`).val(0);
                }

                calculateTotals($row);
            });

            $('#subassy_table tbody').on('input', 'input[name^="produksi_hari_"]', function () {
                const $row = $(this).closest('tr');
                calculateTotals($row);
            });


            $('#subassy_table tbody').on('blur', 'input', function () {
                const $row = $(this).closest('tr');
                const rowData = dt.row($row).data();
                if (!rowData) return;

                const { bulan, tahun } = getParams();
                const jumlahHari = $('#subassy_table thead tr:last th').length;

                const data = {
                    _token: '{{ csrf_token() }}',
                    bulan,
                    tahun,
                    customer: rowData.customer,
                    project: rowData.project,
                    part_number: rowData.part_number,
                    part_name: rowData.part_name,
                    wip_sebelumnya: $row.find('input[name="wip_sebelumnya"]').val(),
                };

                for (let i = 1; i <= jumlahHari; i++) {
                    data[`spk_hari_${i}`] = $row.find(`input[name="spk_hari_${i}"]`).val();
                    data[`produksi_hari_${i}`] = $row.find(`input[name="produksi_hari_${i}"]`).val();
                    data[`wip_hari_${i}`] = $row.find(`input[name="wip_hari_${i}"]`).val();
                }

                $.post('{{ route("monitoring.subassy.save") }}', data)
                    .done(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Tersimpan!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    })
                    .fail((xhr) => {
                    console.log('SAVE ERROR', xhr.status, xhr.responseJSON);
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: xhr.responseJSON?.message ?? 'Gagal menyimpan data!',
                        showConfirmButton: false,
                        timer: 3000
                    });
                });
            });


            function loadCustomerFilter() {
                $.ajax({
                    url: '{{ route("monitoring.subassy.data") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        bulan: $('#filter_bulan').val(),
                        tahun: $('#filter_tahun').val(),
                        only_customer: true
                    },
                    success: function (res) {
                        const select = $('#filter_customer');
                        select.empty().append('<option value=""></option>');

                        const customers = [...new Set(res.data.map(r => r.customer))].sort();
                        customers.forEach(c => {
                            select.append(`<option value="${c}">${c}</option>`);
                        });
                    }
                });
            }

            $('#filter_bulan, #filter_tahun, #filter_customer').on('change', function () {
                reloadTable();
            });

            $('#filter_bulan, #filter_tahun').on('change', function () {
                $('#filter_customer').val(null).trigger('change');
                loadCustomerFilter();
            });

            loadCustomerFilter();
            reloadTable();
        });
    </script>
</x-default-layout>
