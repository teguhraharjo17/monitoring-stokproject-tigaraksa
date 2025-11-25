<x-default-layout>
    @section('title', 'Approve Packing - SPK Packing Member')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Halaman Approve Packing Member</h3>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <label for="tanggal_filter" class="col-sm-2 col-form-label">Pilih Tanggal Proses:</label>
                <div class="col-sm-4">
                    <select id="tanggal_filter" class="form-select">
                        <option value="">-- Pilih Tanggal --</option>
                        @foreach($tanggalProsesList as $tgl)
                            @php
                                $formattedDate = \Carbon\Carbon::parse($tgl->tanggal_proses)->format('d M Y');
                                $isApproved = !is_null($tgl->approved_packing_member_at);
                            @endphp
                            <option 
                                value="{{ $tgl->tanggal_proses }}" 
                                data-approved="{{ $tgl->approved_packing_member_at }}"
                                class="{{ $isApproved ? 'bg-success text-white fw-bold' : '' }}">
                                {{ $isApproved ? '✅ ' : '' }}{{ $formattedDate }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 text-end">
                    <button type="button" id="approve-btn" class="btn btn-success btn-sm">
                        ✅ Approve SPK
                    </button>
                </div>
            </div>

            <div class="table table-bordered" id="data-table-container">
                
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button type="button" id="add-row-approve" class="btn btn-sm btn-primary">+ Tambah Baris</button>
                <button type="button" id="save-all" class="btn btn-success">✅ Simpan Perubahan</button>
            </div>
        </div>
        <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="approve-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="header_id" id="header_id">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approveModalLabel">Upload Tanda Tangan MIP</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">📁 Upload Gambar Tanda Tangan (PNG/JPG)</label>
                                <input type="file" class="form-control" name="ttd_upload" id="ttd_upload" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">📸 Preview</label>
                                <div id="ttd-preview-container" class="border p-2" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                    <img id="ttd-preview" src="" alt="Preview tanda tangan" style="max-height: 180px; max-width: 100%; display: none;">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">✅ Simpan dan Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <style>
        input.form-control-sm {
            text-align: center;
        }
        th, td {
            vertical-align: middle !important;
            text-align: center;
        }

        #signature-canvas {
            border: 1px solid #ccc;
            width: 100%;
            height: 200px;
            display: block;
        }

        select option.bg-success {
            background-color: #28a745 !important;
            color: #fff !important;
            font-weight: bold;
        }
    </style>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script>
        let selectedApprovedAt = null;
        $('#tanggal_filter').on('change', function () {
            const tanggal = $(this).val();
            const selectedOption = $('#tanggal_filter option:selected');
            selectedApprovedAt = selectedOption.data('approved') || null;

            if (!tanggal) {
                $('#approve-btn').prop('disabled', true);
                $('#header_id').val('');
                return;
            }

            $('#approve-btn').prop('disabled', false);

            $.get("{{ route('spkpacking.approvepacking.getbytanggal') }}", { tanggal_proses: tanggal }, function (data) {
                if (!data.length) {
                    $('#data-table-container').html('<div class="alert alert-warning">Tidak ada data ditemukan.</div>');
                    return;
                }

                currentHeaderId = data[0].id;
                $('#header_id').val(currentHeaderId);

                let html = `
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Part Number</th>
                                <th>Customer</th>
                                <th>Nama Models</th>
                                <th>Qty/Set Box</th>
                                <th>Level Stock</th>
                                <th>Stock FG</th>
                                <th>WIP</th>
                                <th>Total</th>
                                <th>Qty SPK (Set)</th>
                                <th>Qty SPK (Box)</th>
                                <th>Refer Kanban/PO</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                let count = 1;
                data.forEach(header => {
                    header.details.forEach(detail => {
                        html += `
                            <tr data-id="${detail.id}">
                                <td>${count++}</td>
                                <td>
                                    <select class="form-select form-select-sm select2-partnumber" style="width: 100%;" data-row="${row}">
                                        ${detail.part_number ? `<option selected value="${detail.part_number}">${detail.part_number}</option>` : ''}
                                    </select>
                                </td>
                                <td>${detail.customer}</td>
                                <td>${detail.nama_models}</td>
                                <td>${detail.qty_per_set_box}</td>
                                <td>${detail.level_stock}</td>
                                <td>${detail.stock_fg}</td>
                                <td><input type="number" class="form-control form-control-sm input-wip" value="${detail.wip ?? 0}"></td>
                                <td>${detail.total}</td>
                                <td><input type="number" class="form-control form-control-sm input-qty-set" value="${detail.qty_spk_set ?? 0}"></td>
                                <td>${parseFloat(detail.qty_spk_box ?? 0).toFixed(1)}</td>
                                <td><input type="text" class="form-control form-control-sm input-kanban" value="${detail.refer_kanban_po ?? ''}"></td>
                                <td><input type="text" class="form-control form-control-sm input-keterangan" value="${detail.keterangan ?? ''}"></td>
                            </tr>
                        `;
                    });
                });

                html += `</tbody></table>`;
                $('#data-table-container').html(html);
            });
        });

        function initializeSelect2PartNumber(row) {
            $(`.select2-partnumber[data-row="${row}"]`).select2({
                placeholder: "Pilih Part Number",
                ajax: {
                    url: "{{ route('spkpacking.formspk.masteritems') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ search: params.term }),
                    processResults: data => ({ results: data.results })
                }
            }).on('select2:select', function (e) {
                const partNumber = e.params.data.id;

                $.get("{{ route('spkpacking.formspk.getiteminfo') }}", { item_id: partNumber }, function (res) {
                    $(`#approve-table tbody tr[data-row="${row}"]`).find('input').each(function () {
                        const field = $(this).attr('class');
                        if (field.includes('customer')) $(this).val(res.customer);
                        if (field.includes('nama_models')) $(this).val(res.nama_models);
                        if (field.includes('qty_per_set_box')) $(this).val(res.qty_set_box);
                        if (field.includes('level_stock')) $(this).val(res.level_stock);
                        if (field.includes('stock_fg')) $(this).val(res.stock_fg);
                    });

                    updateTotal(row);
                    updateQtyBox(row);
                });
            });
        }

        function updateTotal(row) {
            const fg = parseFloat($(`tr[data-row="${row}"] input.stock_fg`).val()) || 0;
            const wip = parseFloat($(`tr[data-row="${row}"] .input-wip`).val()) || 0;
            $(`tr[data-row="${row}"] .total-field`).val(fg + wip);
        }

        function updateQtyBox(row) {
            const qtySet = parseFloat($(`tr[data-row="${row}"] .input-qty-set`).val()) || 0;
            const perBox = parseFloat($(`tr[data-row="${row}"] input.qty_per_set_box`).val()) || 1;
            const box = qtySet / perBox;
            $(`tr[data-row="${row}"] .qty-spk-box-field`).val(box.toFixed(1));
        }

        let rowCounter = 0;
        let currentHeaderId = null;

        function generateRow(row, data = {}) {
            return `
                <tr data-id="${data.id || ''}" data-row="${row}">
                    <td class="row-number">${row}</td>
                    <td>
                        <select class="form-select form-select-sm select2-partnumber" data-row="${row}" style="width: 100%;">
                            ${data.part_number ? `<option value="${data.part_number}" selected>${data.part_number}</option>` : ''}
                        </select>
                    </td>
                    <td><input type="text" class="form-control form-control-sm customer" value="${data.customer || ''}" readonly></td>
                    <td><input type="text" class="form-control form-control-sm nama_models" value="${data.nama_models || ''}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm qty_per_set_box" value="${data.qty_per_set_box ?? 0}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm level_stock" value="${data.level_stock ?? 0}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm stock_fg" value="${data.stock_fg ?? 0}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm input-wip" value="${data.wip ?? 0}"></td>
                    <td><input type="number" class="form-control form-control-sm total-field" value="${(data.stock_fg ?? 0) + (data.wip ?? 0)}" readonly></td>
                    <td><input type="number" class="form-control form-control-sm input-qty-set" value="${data.qty_spk_set ?? 0}"></td>
                    <td><input type="text" class="form-control form-control-sm qty-spk-box-field" value="${parseFloat(data.qty_spk_box ?? 0).toFixed(1)}" readonly></td>
                    <td><input type="text" class="form-control form-control-sm input-kanban" value="${data.refer_kanban_po ?? ''}"></td>
                    <td><input type="text" class="form-control form-control-sm input-keterangan" value="${data.keterangan ?? ''}"></td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-row">×</button></td>
                </tr>
            `;
        }

        $('#tanggal_filter').on('change', function () {
            const tanggal = $(this).val();
            if (!tanggal) return;

            $.get("{{ route('spkpacking.approvepacking.getbytanggal') }}", { tanggal_proses: tanggal }, function (data) {
                if (!data.length) {
                    $('#data-table-container').html('<div class="alert alert-warning">Tidak ada data ditemukan.</div>');
                    return;
                }

                currentHeaderId = data[0].id;
                rowCounter = 0;

                let html = `
                    <table class="table table-bordered text-center" id="approve-table">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Part Number</th>
                                <th>Customer</th>
                                <th>Nama Models</th>
                                <th>Qty/Set Box</th>
                                <th>Level Stock</th>
                                <th>Stock FG</th>
                                <th>WIP</th>
                                <th>Total</th>
                                <th>Qty SPK (Set)</th>
                                <th>Qty SPK (Box)</th>
                                <th>Refer Kanban/PO</th>
                                <th>Keterangan</th>
                                <th>Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(header => {
                    header.details.forEach(detail => {
                        rowCounter++;
                        html += generateRow(rowCounter, detail);
                    });
                });

                html += '</tbody></table>';
                $('#data-table-container').html(html);
                for (let i = 1; i <= rowCounter; i++) {
                    initializeSelect2PartNumber(i);
                }
            });
        });

        $(document).on('click', '#add-row-approve', function () {
            rowCounter++;
            $('#approve-table tbody').append(generateRow(rowCounter));
            initializeSelect2PartNumber(rowCounter);
            renumberRows();
        });

        $(document).on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            renumberRows();
        });

        function renumberRows() {
            $('#approve-table tbody tr').each(function (index) {
                $(this).find('.row-number').text(index + 1);
            });
        }

        $(document).on('input', '.input-wip, .input-qty-set', function () {
            const row = $(this).closest('tr');
            const fg = parseFloat(row.find('.stock_fg').val()) || 0;
            const wip = parseFloat(row.find('.input-wip').val()) || 0;
            const qtySet = parseFloat(row.find('.input-qty-set').val()) || 0;
            const perBox = parseFloat(row.find('.qty_per_set_box').val()) || 1;

            row.find('.total-field').val(fg + wip);

            // Perbaikan pembulatan 2 angka desimal
            const boxQty = qtySet / perBox;
            row.find('.qty-spk-box-field').val(boxQty.toFixed(2));
        });

        $('#save-all').on('click', function () {
            const details = [];

            $('#approve-table tbody tr').each(function () {
                const id = $(this).data('id');
                if (!id) return;

                details.push({
                    id,
                    wip: $(this).find('.input-wip').val(),
                    qty_spk_set: $(this).find('.input-qty-set').val(),
                    refer_kanban_po: $(this).find('.input-kanban').val(),
                    keterangan: $(this).find('.input-keterangan').val(),
                });
            });

            $.post("{{ route('spkpacking.approvepacking.bulkupdate') }}", {
                _token: '{{ csrf_token() }}',
                details: details
            }, function (res) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: res.message,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }).fail(function () {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: 'Gagal menyimpan!',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        });

        $('#ttd_upload').on('change', function (event) {
            const file = event.target.files[0];

            if (!file) {
                $('#ttd-preview').hide();
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#ttd-preview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        });

        $('#approve-form').on('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('spkpacking.approvepacking.approve') }}",
                type: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    Swal.fire({
                        icon: "success",
                        title: "Berhasil!",
                        text: response.message || "Tanda tangan berhasil disimpan!",
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    $('#approveModal').modal('hide');
                    $('#approve-form')[0].reset();
                    $('#ttd-preview').hide();
                },
                error: function (xhr) {
                    let errMsg = "Gagal mengirim data.";
                    if (xhr.responseJSON?.message) {
                        errMsg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: "error",
                        title: "Oops!",
                        text: errMsg,
                    });
                }
            });
        });

        $('#approve-btn').on('click', function (e) {
            if (selectedApprovedAt) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sudah Di-Approve',
                    html: `SPK ini telah di-approve sebelumnya pada:<br><strong>${selectedApprovedAt}</strong>`,
                    confirmButtonText: 'OK'
                });
            } else {
                const modal = new bootstrap.Modal(document.getElementById('approveModal'));
                modal.show();
            }
        });
    </script>
</x-default-layout>
