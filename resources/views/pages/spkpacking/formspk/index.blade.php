<x-default-layout>
    @section('title', 'Form SPK - SPK Packing Member')

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Form SPK - SPK Packing Member</h3>
        </div>

        <form action="{{ route('spkpacking.formspk.store') }}" method="POST" id="form-spk-submit">
            @csrf
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <label for="tanggal_proses" class="form-label">Tanggal Proses:</label>
                        <input type="date" name="tanggal_proses" id="tanggal_proses" class="form-control" required>
                    </div>
                </div>
                <table class="table table-bordered" id="form-spk-table">
                    <thead>
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
                    <tbody id="form-spk-body">
                        {{-- Baris dinamis di-generate JS --}}
                    </tbody>
                </table>

                <div class="text-end mt-3 d-flex justify-content-between">
                    <button type="button" id="add-row" class="btn btn-sm btn-primary">+ Tambah Baris</button>
                    <button type="submit" class="btn btn-success">✅ Submit Form SPK</button>
                </div>
            </div>
        </form>
    </div>

    <style>
        th, td {
            vertical-align: middle !important;
            text-align: center;
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            let currentRow = 0;

            function generateRow(row) {
                const isFirstRow = row === 1;
                return `
                    <tr data-row="${row}">
                        <td class="row-number">${row}</td>
                        <td>
                            <select name="details[${row}][part_number]" class="form-select select2-partnumber" data-row="${row}" style="width: 100%;"></select>
                        </td>
                        <td><input type="text" name="details[${row}][customer]" class="form-control" id="customer-${row}" readonly></td>
                        <td><input type="text" name="details[${row}][nama_models]" class="form-control" id="nama_models-${row}" readonly></td>
                        <td><input type="number" name="details[${row}][qty_set_box]" class="form-control" id="qty_set_box-${row}" readonly></td>
                        <td><input type="number" name="details[${row}][level_stock]" class="form-control" id="level_stock-${row}" readonly></td>
                        <td><input type="number" name="details[${row}][stock_fg]" class="form-control" id="stock_fg-${row}" readonly></td>
                        <td><input type="number" name="details[${row}][wip]" class="form-control wip-input" data-row="${row}" id="wip-${row}"></td>
                        <td><input type="number" name="details[${row}][total]" class="form-control" id="total-${row}" readonly></td>
                        <td><input type="number" name="details[${row}][qty_spk_set]" class="form-control qty-set-input" data-row="${row}" id="qty_spk_set-${row}"></td>
                        <td><input type="text" name="details[${row}][qty_spk_box]" class="form-control" id="qty_spk_box-${row}" readonly></td>
                        <td><input type="text" name="details[${row}][refer_kanban]" class="form-control" id="refer_kanban-${row}"></td>
                        <td><input type="text" name="details[${row}][keterangan]" class="form-control" id="keterangan-${row}"></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row" data-row="${row}" ${isFirstRow ? 'disabled' : ''}>
                                &times;
                            </button>
                        </td>
                    </tr>
                `;
            }

            function initializeSelect2(row) {
                $(`.select2-partnumber[data-row="${row}"]`).select2({
                    placeholder: "Pilih Part Number",
                    width: "100%",
                    ajax: {
                        url: "{{ route('spkpacking.formspk.masteritems') }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ search: params.term }),
                        processResults: data => ({ results: data.results })
                    }
                }).on('select2:select', function (e) {
                    const data = e.params.data;
                    $.get("{{ route('spkpacking.formspk.getiteminfo') }}", { item_id: data.id }, function (res) {
                        $(`#customer-${row}`).val(res.customer);
                        $(`#nama_models-${row}`).val(res.nama_models);
                        $(`#qty_set_box-${row}`).val(res.qty_set_box);
                        $(`#level_stock-${row}`).val(res.level_stock);
                        $(`#stock_fg-${row}`).val(res.stock_fg);
                        updateTotal(row);
                        updateQtyBox(row);
                    });
                });
            }

            function updateTotal(row) {
                const fg = parseInt($(`#stock_fg-${row}`).val()) || 0;
                const wip = parseInt($(`#wip-${row}`).val()) || 0;
                $(`#total-${row}`).val(fg + wip);
            }

            function updateQtyBox(row) {
                const qtySet = parseFloat($(`#qty_spk_set-${row}`).val()) || 0;
                const perBox = parseFloat($(`#qty_set_box-${row}`).val()) || 1;
                const box = qtySet / perBox;
                $(`#qty_spk_box-${row}`).val(box.toFixed(1));
            }

            $('#add-row').on('click', function () {
                currentRow++;
                $('#form-spk-body').append(generateRow(currentRow));
                initializeSelect2(currentRow);

                $(`#wip-${currentRow}, #qty_spk_set-${currentRow}`).on('input', function () {
                    const row = $(this).data('row');
                    updateTotal(row);
                    updateQtyBox(row);
                });
            });

            // Hapus baris
            $('#form-spk-body').on('click', '.remove-row', function () {
                const row = $(this).data('row');

                // Cegah hapus baris pertama
                if (row === 1) return;

                $(this).closest('tr').remove();

                // Re-index nomor urut baris
                $('#form-spk-body tr').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
            });


            $('#form-spk-submit').on('submit', function (e) {
                e.preventDefault();

                let isValid = true;
                let firstInvalidRow = null;

                $('#form-spk-body tr').each(function () {
                    const row = $(this).data('row');
                    const partNumber = $(`select[name="details[${row}][part_number]"]`).val();
                    const qtySpkSet = parseFloat($(`#qty_spk_set-${row}`).val());

                    if (!partNumber || isNaN(qtySpkSet) || qtySpkSet <= 0) {
                        isValid = false;
                        firstInvalidRow = row;
                        return false; // break loop
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        toast: true,
                        icon: 'warning',
                        title: `⚠️ Harap lengkapi Part Number dan Qty SPK (Set) di baris ${firstInvalidRow}`,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                    return;
                }

                // Jika valid, lanjut submit
                const form = $(this);
                const formData = form.serialize();

                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    success: function () {
                        Swal.fire({
                            toast: true,
                            icon: 'success',
                            title: '✅ Data berhasil disimpan!',
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });

                        form.trigger('reset');
                        $('#form-spk-body').html('');
                        currentRow = 0;
                        $('#add-row').trigger('click');
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.errors
                            ? Object.values(xhr.responseJSON.errors).flat().join('\n')
                            : '❌ Terjadi kesalahan saat menyimpan data.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: msg
                        });
                    }
                });
            });

            // Tambahkan baris awal secara otomatis
            $('#add-row').trigger('click');
        });
    </script>
</x-default-layout>
