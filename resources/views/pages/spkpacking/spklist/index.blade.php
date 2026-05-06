<x-default-layout>
    @section('title', 'SPK List - SPK Packing Member')

    <div class="spk-shell my-5">
        <section class="spk-hero card">
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-xl-8">
                        <span class="hero-chip mb-4"><i class="fas fa-file-invoice me-2"></i>Daftar SPK Packing</span>
                        <h1 class="text-white fw-bolder mb-3">Kelola dan pantau seluruh Surat Perintah Kerja (SPK) dengan lebih terorganisir</h1>
                        <p class="hero-copy mb-0">Halaman ini memudahkan Anda dalam mencari histori SPK, memantau status pengerjaan, hingga melakukan ekspor data ke Excel dengan satu klik.</p>
                    </div>
                    <div class="col-xl-4 text-xl-end">
                        <div class="hero-icon-blob">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card control-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label for="filter-tanggal-proses" class="form-label fw-bold text-gray-700">Filter Rentang Tanggal Proses</label>
                        <div class="input-group input-group-solid">
                            <span class="input-group-text border-0"><i class="fas fa-calendar-alt text-primary"></i></span>
                            <input type="text" id="filter-tanggal-proses" class="form-control form-control-solid ps-0" placeholder="Pilih Tanggal Proses" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-8">
                        <div class="d-flex gap-2">
                            <button id="reset-filter" class="btn btn-light-danger px-6"><i class="fas fa-rotate-left me-2"></i>Reset Filter</button>
                            <button id="reload_table" class="btn btn-primary px-6"><i class="fas fa-sync-alt me-2"></i>Refresh</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card table-panel">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="panel-title mb-1">Histori SPK Packing</h2>
                        <p class="panel-subtitle">Daftar seluruh SPK yang telah dibuat berdasarkan tanggal proses.</p>
                    </div>
                </div>

                <div id="table_progress_bar" class="progress-container d-none">
                    <div class="progress-bar-fill"></div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle text-center mb-0" id="spk-list-table">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">
                                <th class="text-center" style="min-width: 50px;">No</th>
                                <th class="text-center" style="min-width: 120px;">Tanggal Proses</th>
                                <th class="text-center" style="min-width: 140px;">Dibuat Tanggal</th>
                                <th class="text-center" style="min-width: 140px;">Terakhir Diubah</th>
                                <th class="text-center" style="min-width: 320px;">Approval Status</th>
                                <th class="text-center" style="min-width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-bold"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <style>
        .spk-shell { display: grid; gap: 1.5rem; }
        .spk-hero { border: 0; border-radius: 28px; overflow: hidden; background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 28%), linear-gradient(135deg, #1e3a8a 0%, #3b82f6 44%, #6366f1 100%); box-shadow: 0 24px 60px rgba(59, 130, 246, .18); }
        .spk-hero .card-body { padding: 2.5rem; }
        .hero-chip { display: inline-flex; align-items: center; padding: .6rem .95rem; border-radius: 999px; background: rgba(255,255,255,.14); color: #fff; font-size: .9rem; }
        .hero-copy { color: rgba(255,255,255,.82); font-size: 1rem; max-width: 720px; }
        .hero-icon-blob { width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 35% 65% 70% 30% / 30% 30% 70% 70%; display: inline-grid; place-items: center; font-size: 3.5rem; color: #fff; animation: blobby 12s infinite linear alternate; }
        @keyframes blobby { 0% { border-radius: 35% 65% 70% 30% / 30% 30% 70% 70%; } 50% { border-radius: 50% 50% 33% 67% / 55% 27% 73% 45%; } 100% { border-radius: 70% 30% 30% 70% / 60% 40% 60% 40%; } }

        .control-panel, .table-panel { border: 0; border-radius: 24px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); box-shadow: 0 16px 40px rgba(15, 23, 42, .07); }
        .panel-title { font-size: 1.25rem; font-weight: 800; color: #1e293b; }
        .panel-subtitle { color: #64748b; font-size: .95rem; }

        .table thead th { background: #f1f5f9; color: #475569; padding: 1.25rem 1rem; border-bottom: 2px solid #e2e8f0; }
        .table tbody td { padding: 1.25rem 1rem; vertical-align: middle; }
        .table tbody tr:hover { background-color: #f8fbff; }

        /* Premium Skeleton Loading */
        .skeleton-row td { padding: 20px 10px !important; }
        .skeleton-box { 
            height: 20px; 
            background: #e2e8f0; 
            border-radius: 8px; 
            position: relative; 
            overflow: hidden; 
        }
        .skeleton-box::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.5) 50%, rgba(255,255,255,0) 100%);
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .progress-container {
            height: 3px;
            width: 100%;
            background: #f1f5f9;
            overflow: hidden;
            margin-bottom: 15px;
            border-radius: 999px;
        }
        .progress-bar-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, #3b82f6, #6366f1);
            border-radius: 999px;
            animation: progress-move 1.2s infinite ease-in-out;
        }
        @keyframes progress-move {
            0% { margin-left: -30%; }
            100% { margin-left: 100%; }
        }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Status Badge Style */
        .badge { font-weight: 700; padding: .65em 1em; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge-light-success { background-color: #dcfce7; color: #166534; }
        .badge-light-primary { background-color: #dbeafe; color: #1e40af; }
        .badge-light-warning { background-color: #fef3c7; color: #92400e; }

        /* Approval Tracker Styles */
        .approval-tracker { padding: 5px 0; }
        .approval-step { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 6px; 
            width: 60px; 
            position: relative;
            transition: all 0.3s ease;
        }
        .step-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            background: #f1f5f9;
            color: #94a3b8;
            border: 2px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .step-date {
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
            margin-top: -2px;
        }
        .step-time {
            font-size: 0.6rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-top: -3px;
        }
        .approval-step.approved .step-icon {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ffffff !important;
            border-color: #15803d;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            animation: pulse-green 2s infinite;
        }
        .approval-step.approved .step-label { color: #15803d; }
        .approval-step.approved .step-date { color: #16a34a; }
        .approval-step.approved .step-time { color: #22c55e; }
        .approval-step.pending .step-icon {
            background: #fff;
            color: #cbd5e1;
            border: 2px dashed #cbd5e1;
        }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .approval-step:hover { transform: translateY(-3px); }
        .approval-step:hover .step-icon { filter: brightness(1.1); }

        .btn-light-danger { background-color: #fff1f2; color: #e11d48; border: 0; }
        .btn-light-danger:hover { background-color: #ffe4e6; color: #be123c; }
        
        .daterangepicker { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .daterangepicker .applyBtn { background-color: #3b82f6 !important; border: 0; }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function () {
            function showSkeleton() {
                let skeletonRows = '';
                for (let i = 0; i < 5; i++) {
                    skeletonRows += '<tr class="skeleton-row">';
                    for (let j = 0; j < 6; j++) {
                        skeletonRows += '<td><div class="skeleton-box"></div></td>';
                    }
                    skeletonRows += '</tr>';
                }
                $('#spk-list-table tbody').html(skeletonRows);
                $('#table_progress_bar').removeClass('d-none');
            }

            function hideSkeleton() {
                $('#table_progress_bar').addClass('d-none');
            }

            let table = $('#spk-list-table').DataTable({
                processing: false, // We use our own skeleton
                serverSide: true,
                ajax: {
                    url: '{{ route('spkpacking.spklist.datatable') }}',
                    data: function (d) {
                        d.tanggal_proses = $('#filter-tanggal-proses').val();
                    },
                    beforeSend: function() {
                        showSkeleton();
                    },
                    dataSrc: function(json) {
                        hideSkeleton();
                        return json.data || [];
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'tanggal_proses', name: 'tanggal_proses' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'updated_at', name: 'updated_at', searchable: false, orderable: false },
                    { 
                        data: 'status', 
                        name: 'status', 
                        orderable: false, 
                        searchable: false,
                        render: function(data) {
                            let badgeClass = 'badge-light-primary';
                            if (data && data.toLowerCase().includes('selesai')) badgeClass = 'badge-light-success';
                            if (data && data.toLowerCase().includes('proses')) badgeClass = 'badge-light-warning';
                            return `<span class="badge ${badgeClass}">${data}</span>`;
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                drawCallback: function() {
                    $('#spk-list-table tbody').addClass('fade-in');
                    setTimeout(() => $('#spk-list-table tbody').removeClass('fade-in'), 500);
                },
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data tersedia",
                    zeroRecords: "Data tidak ditemukan",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "›",
                        previous: "‹"
                    },
                }
            });

            $('#filter-tanggal-proses').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Batal',
                    applyLabel: 'Terapkan',
                    format: 'YYYY-MM-DD'
                }
            });

            $('#filter-tanggal-proses').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                table.draw();
            });

            $('#filter-tanggal-proses').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                table.draw();
            });

            $('#reset-filter').click(function () {
                $('#filter-tanggal-proses').val('');
                table.draw();
            });

            $('#reload_table').click(function () {
                table.ajax.reload(null, false);
            });
        });
    </script>
</x-default-layout>
