<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<script>
    var URL_GET_DATA       = "<?= site_url('validasifile/get_data_ajax'); ?>";
    var URL_GET_QUARANTINE = "<?= site_url('validasifile/get_quarantine_ajax'); ?>";
    var URL_GET_STATS      = "<?= site_url('validasifile/get_stats_ajax'); ?>";
    var URL_GET_MANIFEST   = "<?= site_url('validasifile/get_manifest_ajax'); ?>";
    var URL_KARANTINA      = "<?= site_url('validasifile/proses_karantina'); ?>";
    var URL_RESTORE        = "<?= site_url('validasifile/proses_restore'); ?>";
    var URL_DELETE         = "<?= site_url('validasifile/proses_delete'); ?>";
    var URL_PREVIEW        = "<?= site_url('validasifile/preview'); ?>";
</script>

<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">

    <!-- begin:: Stats Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-lg-6">
            <div class="kt-portlet kt-portlet--border-bottom-brand">
                <div class="kt-portlet__body kt-portlet__body--fluid">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <span class="kt-widget26__number" id="stat-total-disk"><?= $stats['ticketing_disk_total']; ?></span>
                            <span class="kt-widget26__desc">Total File di Folder Ticketing</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-6">
            <div class="kt-portlet kt-portlet--border-bottom-success">
                <div class="kt-portlet__body kt-portlet__body--fluid">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <span class="kt-widget26__number text-success" id="stat-valid"><?= $stats['valid_count']; ?></span>
                            <span class="kt-widget26__desc">File Valid (Sesuai DB)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-6">
            <div class="kt-portlet kt-portlet--border-bottom-warning">
                <div class="kt-portlet__body kt-portlet__body--fluid">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <span class="kt-widget26__number text-warning" id="stat-legacy"><?= $stats['legacy_count']; ?></span>
                            <span class="kt-widget26__desc">Backup Lama (2020)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-6">
            <div class="kt-portlet kt-portlet--border-bottom-danger">
                <div class="kt-portlet__body kt-portlet__body--fluid">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <span class="kt-widget26__number text-danger" id="stat-orphan"><?= $stats['orphan_count']; ?></span>
                            <span class="kt-widget26__desc">Orphan (Tidak di DB)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="kt-portlet kt-portlet--border-bottom-dark">
                <div class="kt-portlet__body kt-portlet__body--fluid">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <span class="kt-widget26__number text-dark" id="stat-quarantine"><?= $stats['quarantined_count']; ?></span>
                            <span class="kt-widget26__desc">File Terkarantina</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end:: Stats Summary Cards -->

    <!-- begin:: Portlet Main -->
    <div class="kt-portlet kt-portlet--tabs">
        <div class="kt-portlet__head">
            <div class="kt-portlet__head-label">
                <h3 class="kt-portlet__head-title">
                    <i class="la la-shield text-primary"></i> <?= strtoupper($page_judul); ?>
                </h3>
            </div>
            <div class="kt-portlet__head-toolbar">
                <ul class="nav nav-tabs nav-tabs-line nav-tabs-line-danger nav-tabs-line-2x nav-tabs-line-right" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab_files" role="tab">
                            <i class="la la-folder-open"></i> File Aktif di Disk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab_quarantine" role="tab">
                            <i class="la la-lock"></i> Area Karantina <span class="badge badge-dark ml-1" id="badge-quarantine-tab"><?= $stats['quarantined_count']; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab_manifest" role="tab">
                            <i class="la la-history"></i> Log & Manifest Karantina
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="kt-portlet__body">
            <div class="tab-content">
                <!-- TAB 1: FILE AKTIF DI DISK -->
                <div class="tab-pane active" id="tab_files" role="tabpanel">
                    
                    <!-- Filter Toolbar -->
                    <div class="row align-items-center mb-3">
                        <div class="col-md-3">
                            <label class="font-weight-bold">Pilih Direktori:</label>
                            <select class="form-control" id="select-folder">
                                <option value="ticketing" selected>upload_file/ticketing (Lampiran Tiket)</option>
                                <option value="chat">upload_file/chat (Lampiran Chat / Balasan)</option>
                                <option value="qrcode">upload_file/qrcode (QR Code)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="font-weight-bold">Filter Status Validasi:</label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                <label class="btn btn-outline-secondary active btn-sm filter-status-btn" data-status="">
                                    <input type="radio" name="status_filter" value="" checked> Semua
                                </label>
                                <label class="btn btn-outline-success btn-sm filter-status-btn" data-status="VALID">
                                    <input type="radio" name="status_filter" value="VALID"> Valid
                                </label>
                                <label class="btn btn-outline-warning btn-sm filter-status-btn" data-status="LEGACY_BACKUP">
                                    <input type="radio" name="status_filter" value="LEGACY_BACKUP"> Backup 2020
                                </label>
                                <label class="btn btn-outline-danger btn-sm filter-status-btn" data-status="ORPHAN">
                                    <input type="radio" name="status_filter" value="ORPHAN"> Orphan
                                </label>
                            </div>
                        </div>
                        <div class="col-md-5 text-right mt-3 mt-md-0">
                            <label class="d-none d-md-block">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-brand btn-elevate btn-sm mr-1" id="btn-refresh-all">
                                    <i class="la la-refresh"></i> Refresh
                                </button>
                                <button type="button" class="btn btn-warning btn-elevate btn-sm mr-1" id="btn-quarantine-selected" disabled>
                                    <i class="la la-lock"></i> Karantina Terpilih (<span id="count-selected">0</span>)
                                </button>
                                <button type="button" class="btn btn-danger btn-elevate btn-sm" id="btn-quarantine-all-unmatched">
                                    <i class="la la-shield"></i> Karantina Semua Tidak Sesuai (<span id="count-unmatched"><?= $stats['unmatched_total']; ?></span>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DataTable Files (Server-Side Paginated) -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-checkable" id="table-files">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center">
                                        <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">
                                            <input type="checkbox" id="check-all-files" class="kt-group-checkable">
                                            <span></span>
                                        </label>
                                    </th>
                                    <th width="40" class="text-center">No</th>
                                    <th>Nama File</th>
                                    <th width="150" class="text-center">Status Validasi</th>
                                    <th width="110" class="text-right">Ukuran</th>
                                    <th width="160" class="text-center">Tanggal Modifikasi</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded dynamically via Server-Side DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: AREA KARANTINA -->
                <div class="tab-pane" id="tab_quarantine" role="tabpanel">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-0">
                                <i class="la la-info-circle text-info"></i> File di area ini dipisahkan secara aman dari direktori upload utama ke <code>upload_file/_quarantine/</code>. File dapat dipulihkan (*restore*) kapan saja.
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-success btn-elevate btn-sm mr-1" id="btn-restore-selected" disabled>
                                <i class="la la-undo"></i> Pulihkan Terpilih (<span id="count-quarantine-selected">0</span>)
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm mr-1" id="btn-restore-all">
                                <i class="la la-reply-all"></i> Pulihkan Semua
                            </button>
                            <button type="button" class="btn btn-danger btn-elevate btn-sm" id="btn-delete-quarantine-selected" disabled>
                                <i class="la la-trash"></i> Hapus Permanen Terpilih
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-checkable" id="table-quarantine">
                            <thead>
                                <tr>
                                    <th width="30" class="text-center">
                                        <label class="kt-checkbox kt-checkbox--single kt-checkbox--solid">
                                            <input type="checkbox" id="check-all-quarantine" class="kt-group-checkable">
                                            <span></span>
                                        </label>
                                    </th>
                                    <th width="40" class="text-center">No</th>
                                    <th>Nama File</th>
                                    <th width="120" class="text-center">Folder Asal</th>
                                    <th width="110" class="text-right">Ukuran</th>
                                    <th width="160" class="text-center">Waktu Karantina</th>
                                    <th width="120" class="text-center">Eksekutor</th>
                                    <th width="140" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded dynamically via DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: MANIFEST & AUDIT LOG -->
                <div class="tab-pane" id="tab_manifest" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="kt-font-bold text-dark mb-0"><i class="la la-file-code-o"></i> Manifest & Riwayat Karantina</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-refresh-manifest">
                            <i class="la la-refresh"></i> Refresh Manifest
                        </button>
                    </div>
                    <div class="alert alert-secondary" role="alert">
                        <div class="alert-icon"><i class="flaticon-information"></i></div>
                        <div class="alert-text">
                            File manifest tersimpan di <code>upload_file/_quarantine/manifest.json</code> sebagai bukti audit jejak pemisahan file.
                        </div>
                    </div>
                    <pre id="json-manifest-viewer" class="bg-light p-3 border rounded" style="max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px;"><?= json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                </div>
            </div>
        </div>
    </div>
    <!-- end:: Portlet Main -->

</div>
<!-- end:: Content -->

<!-- Modal Preview Document -->
<div class="modal fade" id="modal-preview-doc" tabindex="-1" role="dialog" aria-labelledby="modalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPreviewLabel"><i class="la la-file-pdf-o text-danger"></i> Preview Dokumen: <span id="preview-filename"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="height: 75vh;">
                <iframe id="iframe-doc-preview" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <a href="#" target="_blank" class="btn btn-outline-brand btn-sm" id="btn-open-external">
                    <i class="la la-external-link"></i> Buka di Tab Baru
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
