<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<?php
// Fallback data aman untuk testing dan rendering view independen
$stats            = $stats ?? [];
$manifest         = $manifest ?? ['quarantined' => [], 'history' => []];
$ticketingTotal   = (int) ($stats['ticketing_disk_total'] ?? 0);
$validCount       = (int) ($stats['valid_count'] ?? 0);
$legacyCount      = (int) ($stats['legacy_count'] ?? 0);
$orphanCount      = (int) ($stats['orphan_count'] ?? 0);
$quarantinedCount = (int) ($stats['quarantined_count'] ?? 0);
$unmatchedTotal   = (int) ($stats['unmatched_total'] ?? ($legacyCount + $orphanCount));
$page_judul       = $page_judul ?? 'Validasi & Keamanan Berkas Digital';
?>

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
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">

    <!-- begin:: Security Scanning Metric Cards (Integritas Berkas) -->
    <div class="row">
        <!-- Kartu 1: Integritas Berkas / Total Berkas Terpindai -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--border-bottom-brand h-100" style="border-radius: 4px;">
                <div class="kt-portlet__body kt-portlet__body--fluid py-4 px-4">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill kt-badge--bold">
                                    <i class="la la-shield mr-1"></i> Integritas Berkas
                                </span>
                                <i class="la la-files-o text-primary" style="font-size: 1.8rem;"></i>
                            </div>
                            <span class="kt-widget26__number text-dark font-weight-bold" id="stat-total-disk" style="font-size: 2rem;"><?= $ticketingTotal; ?></span>
                            <span class="kt-widget26__desc text-muted d-block mt-1 font-weight-500">Total Berkas Terpindai di Server</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 2: Berkas Aman (Sesuai Basis Data) -->
        <div class="col-xl-2 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--border-bottom-success h-100" style="border-radius: 4px;">
                <div class="kt-portlet__body kt-portlet__body--fluid py-4 px-4">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill kt-badge--bold">
                                    <i class="la la-check-circle mr-1"></i> Berkas Aman
                                </span>
                                <i class="la la-check text-success" style="font-size: 1.8rem;"></i>
                            </div>
                            <span class="kt-widget26__number text-success font-weight-bold" id="stat-valid" style="font-size: 2rem;"><?= $validCount; ?></span>
                            <span class="kt-widget26__desc text-muted d-block mt-1 font-weight-500">Valid Sesuai Database</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 3: Backup Berkas Lama (2020) -->
        <div class="col-xl-2 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--border-bottom-warning h-100" style="border-radius: 4px;">
                <div class="kt-portlet__body kt-portlet__body--fluid py-4 px-4">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="kt-badge kt-badge--unified-warning kt-badge--inline kt-badge--pill kt-badge--bold">
                                    <i class="la la-archive mr-1"></i> Backup Lama
                                </span>
                                <i class="la la-history text-warning" style="font-size: 1.8rem;"></i>
                            </div>
                            <span class="kt-widget26__number text-warning font-weight-bold" id="stat-legacy" style="font-size: 2rem;"><?= $legacyCount; ?></span>
                            <span class="kt-widget26__desc text-muted d-block mt-1 font-weight-500">Arsip Cadangan (2020)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 4: Berkas Tak Terdaftar (Orphan) -->
        <div class="col-xl-2 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--border-bottom-danger h-100" style="border-radius: 4px;">
                <div class="kt-portlet__body kt-portlet__body--fluid py-4 px-4">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill kt-badge--bold">
                                    <i class="la la-exclamation-triangle mr-1"></i> Berkas Orphan
                                </span>
                                <i class="la la-chain-broken text-danger" style="font-size: 1.8rem;"></i>
                            </div>
                            <span class="kt-widget26__number text-danger font-weight-bold" id="stat-orphan" style="font-size: 2rem;"><?= $orphanCount; ?></span>
                            <span class="kt-widget26__desc text-muted d-block mt-1 font-weight-500">Tanpa Referensi Database</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu 5: Berkas Dikarantina (Area Terisolasi) -->
        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
            <div class="kt-portlet kt-portlet--border-bottom-dark h-100" style="border-radius: 4px;">
                <div class="kt-portlet__body kt-portlet__body--fluid py-4 px-4">
                    <div class="kt-widget26">
                        <div class="kt-widget26__content">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill kt-badge--bold">
                                    <i class="la la-lock mr-1"></i> Berkas Dikarantina
                                </span>
                                <i class="la la-shield text-danger" style="font-size: 1.8rem;"></i>
                            </div>
                            <span class="kt-widget26__number text-danger font-weight-bold" id="stat-quarantine" style="font-size: 2rem;"><?= $quarantinedCount; ?></span>
                            <span class="kt-widget26__desc text-muted d-block mt-1 font-weight-500">Terisolasi di Folder Karantina</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end:: Security Scanning Metric Cards -->

    <!-- begin:: Portlet Main -->
    <div class="kt-portlet kt-portlet--tabs" style="border-radius: 4px; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
        <div class="kt-portlet__head" style="border-bottom: 1px solid #ebedf2; min-height: 60px;">
            <div class="kt-portlet__head-label">
                <span class="kt-portlet__head-icon mr-2">
                    <i class="la la-shield text-primary" style="font-size: 1.4rem;"></i>
                </span>
                <h3 class="kt-portlet__head-title font-weight-bold text-dark" style="font-size: 1.2rem;">
                    <?= esc(strtoupper($page_judul)); ?>
                </h3>
            </div>
            <div class="kt-portlet__head-toolbar">
                <ul class="nav nav-tabs nav-tabs-line nav-tabs-line-brand nav-tabs-line-2x nav-tabs-line-right" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" data-toggle="tab" href="#tab_files" role="tab">
                            <i class="la la-folder-open"></i> Berkas Aktif di Server
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" data-toggle="tab" href="#tab_quarantine" role="tab">
                            <i class="la la-lock"></i> Area Karantina <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill ml-1" id="badge-quarantine-tab"><?= $quarantinedCount; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" data-toggle="tab" href="#tab_manifest" role="tab">
                            <i class="la la-history"></i> Tabel Manifest & Audit Log
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="kt-portlet__body" style="padding: 25px;">
            <div class="tab-content">
                <!-- TAB 1: FILE AKTIF DI DISK -->
                <div class="tab-pane active" id="tab_files" role="tabpanel">
                    
                    <!-- Filter Toolbar -->
                    <div class="row align-items-center mb-4">
                        <div class="col-md-3">
                            <label class="font-weight-bold text-dark">Pilih Direktori Penyimpanan:</label>
                            <select class="form-control" id="select-folder" style="border-radius: 4px;">
                                <option value="ticketing" selected>upload_file/ticketing (Lampiran Tiket Pemohon)</option>
                                <option value="chat">upload_file/chat (Lampiran Balasan Petugas)</option>
                                <option value="qrcode">upload_file/qrcode (Aset QR Code Layanan)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="font-weight-bold text-dark">Filter Status Integritas:</label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                <label class="btn btn-outline-secondary active btn-sm filter-status-btn" data-status="" style="border-radius: 4px 0 0 4px;">
                                    <input type="radio" name="status_filter" value="" checked> Semua
                                </label>
                                <label class="btn btn-outline-success btn-sm filter-status-btn" data-status="VALID">
                                    <input type="radio" name="status_filter" value="VALID"> Valid
                                </label>
                                <label class="btn btn-outline-warning btn-sm filter-status-btn" data-status="LEGACY_BACKUP">
                                    <input type="radio" name="status_filter" value="LEGACY_BACKUP"> Backup 2020
                                </label>
                                <label class="btn btn-outline-danger btn-sm filter-status-btn" data-status="ORPHAN" style="border-radius: 0 4px 4px 0;">
                                    <input type="radio" name="status_filter" value="ORPHAN"> Orphan
                                </label>
                            </div>
                        </div>
                        <div class="col-md-5 text-right mt-3 mt-md-0">
                            <label class="d-none d-md-block">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-brand btn-elevate btn-sm mr-1" id="btn-refresh-all" style="border-radius: 4px;">
                                    <i class="la la-refresh"></i> Segarkan Data
                                </button>
                                <button type="button" class="btn btn-warning btn-elevate btn-sm mr-1" id="btn-quarantine-selected" disabled style="border-radius: 4px;">
                                    <i class="la la-lock"></i> Karantina Terpilih (<span id="count-selected">0</span>)
                                </button>
                                <button type="button" class="btn btn-danger btn-elevate btn-sm" id="btn-quarantine-all-unmatched" style="border-radius: 4px;">
                                    <i class="la la-shield"></i> Karantina Semua Tidak Sesuai (<span id="count-unmatched"><?= $unmatchedTotal; ?></span>)
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
                                    <th>Nama Berkas</th>
                                    <th width="160" class="text-center">Status Validasi</th>
                                    <th width="110" class="text-right">Ukuran</th>
                                    <th width="160" class="text-center">Tanggal Modifikasi</th>
                                    <th width="120" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dimuat dinamis melalui AJAX Server-Side DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 2: AREA KARANTINA -->
                <div class="tab-pane" id="tab_quarantine" role="tabpanel">
                    <div class="row align-items-center mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-0">
                                <i class="la la-info-circle text-info"></i> Berkas di area ini dipisahkan secara aman dari direktori publik ke folder terproteksi <code>writable/uploads/_quarantine/</code>. Berkas dapat dipulihkan (*restore*) kembali kapan saja atau dihapus secara permanen.
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-brand btn-elevate btn-sm mr-1" id="btn-restore-selected" disabled style="border-radius: 4px;">
                                <i class="la la-undo"></i> Pulihkan Terpilih (<span id="count-quarantine-selected">0</span>)
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm mr-1" id="btn-restore-all" style="border-radius: 4px;">
                                <i class="la la-reply-all"></i> Pulihkan Semua
                            </button>
                            <button type="button" class="btn btn-danger btn-elevate btn-sm" id="btn-delete-quarantine-selected" disabled style="border-radius: 4px;">
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
                                    <th>Nama Berkas</th>
                                    <th width="120" class="text-center">Folder Asal</th>
                                    <th width="110" class="text-right">Ukuran</th>
                                    <th width="160" class="text-center">Waktu Karantina</th>
                                    <th width="120" class="text-center">Eksekutor</th>
                                    <th width="140" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dimuat dinamis melalui DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 3: MANIFEST & AUDIT LOG -->
                <div class="tab-pane" id="tab_manifest" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="kt-font-bold text-dark mb-1">
                                <i class="la la-shield text-primary"></i> Tabel Manifest & Riwayat Keamanan Berkas
                            </h5>
                            <p class="text-muted mb-0 small">
                                Dokumen manifest audit integritas berkas tersimpan di <code>upload_file/_quarantine/manifest.json</code> sebagai bukti otentikasi jejak isolasi.
                            </p>
                        </div>
                        <button type="button" class="btn btn-brand btn-sm" id="btn-refresh-manifest" style="border-radius: 4px;">
                            <i class="la la-refresh"></i> Segarkan Manifest
                        </button>
                    </div>

                    <!-- Petunjuk Legend Status Keamanan -->
                    <div class="p-3 mb-3 bg-light border rounded" style="border-radius: 4px;">
                        <span class="font-weight-bold mr-3 text-dark">Status Keamanan Berkas:</span>
                        <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill kt-badge--bold mr-2">
                            <i class="la la-check mr-1"></i> Berkas Aman / Valid
                        </span>
                        <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill kt-badge--bold mr-2">
                            <i class="la la-lock mr-1"></i> Berkas Dikarantina
                        </span>
                        <span class="kt-badge kt-badge--unified-warning kt-badge--inline kt-badge--pill kt-badge--bold">
                            <i class="la la-exclamation-triangle mr-1"></i> Perlu Tinjauan / Backup
                        </span>
                    </div>

                    <!-- Tabel Manifest Ringkasan -->
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-bordered table-hover" id="table-manifest-summary">
                            <thead>
                                <tr class="bg-light text-dark font-weight-bold">
                                    <th width="40" class="text-center">No</th>
                                    <th>Nama Berkas</th>
                                    <th width="140" class="text-center">Direktori Asal</th>
                                    <th width="180" class="text-center">Status Keamanan</th>
                                    <th width="120" class="text-right">Ukuran</th>
                                    <th width="180" class="text-center">Waktu & Eksekutor</th>
                                    <th>Checksum SHA-256</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! empty($manifest['quarantined']) && is_array($manifest['quarantined'])): ?>
                                    <?php $no = 1; foreach ($manifest['quarantined'] as $qItem): ?>
                                        <tr>
                                            <td class="text-center align-middle"><?= $no++; ?></td>
                                            <td class="align-middle font-weight-bold">
                                                <i class="la la-file text-danger mr-1"></i>
                                                <?= esc($qItem['filename'] ?? '-'); ?>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-secondary"><?= esc($qItem['folder'] ?? 'ticketing'); ?></span>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill kt-badge--bold">
                                                    <i class="la la-lock mr-1"></i> Berkas Dikarantina
                                                </span>
                                            </td>
                                            <td class="text-right align-middle font-weight-bold text-muted">
                                                <?= esc($qItem['filesize_fmt'] ?? '-'); ?>
                                            </td>
                                            <td class="text-center align-middle text-muted small">
                                                <div><?= esc($qItem['quarantined_at'] ?? '-'); ?></div>
                                                <span class="badge badge-dark"><?= esc($qItem['quarantined_by'] ?? 'ADMIN'); ?></span>
                                            </td>
                                            <td class="align-middle small font-monospace text-muted" style="font-size: 11px;">
                                                <?= esc(substr($qItem['sha256'] ?? '', 0, 24)); ?>...
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="la la-shield text-success" style="font-size: 2.5rem;"></i>
                                            <p class="mt-2 mb-0 font-weight-bold">Tidak ada berkas yang sedang dikarantina.</p>
                                            <small>Seluruh berkas berada pada status <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill kt-badge--bold">Berkas Aman</span> atau direktori aktif.</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- JSON Raw Manifest Output -->
                    <div class="card p-3 border" style="border-radius: 4px; background: #fafbfc;">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="la la-code"></i> Data Mentah Dokumen Manifest JSON:</h6>
                        <pre id="json-manifest-viewer" class="bg-light p-3 border rounded mb-0" style="max-height: 380px; overflow-y: auto; font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, monospace; font-size: 12px;"><?= json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end:: Portlet Main -->

</div>
<!-- end:: Content -->

<!-- Modal 1: Preview Dokumen -->
<div class="modal fade" id="modal-preview-doc" tabindex="-1" role="dialog" aria-labelledby="modalPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 4px; box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15); border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #ebedf2;">
                <h5 class="modal-title font-weight-bold text-dark" id="modalPreviewLabel">
                    <i class="la la-file-pdf-o text-danger mr-1"></i> Pratinjau Dokumen: <span id="preview-filename" class="text-primary font-weight-bold"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="height: 75vh;">
                <iframe id="iframe-doc-preview" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #ebedf2;">
                <a href="#" target="_blank" class="btn btn-brand btn-sm" id="btn-open-external" style="border-radius: 4px;">
                    <i class="la la-external-link"></i> Buka di Jendela Baru
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 4px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Konfirmasi Tindakan Karantina Berkas -->
<div class="modal fade" id="modal-confirm-quarantine" tabindex="-1" role="dialog" aria-labelledby="modalQuarantineLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 4px; box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15); border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #ebedf2;">
                <h5 class="modal-title font-weight-bold text-dark" id="modalQuarantineLabel">
                    <i class="la la-lock text-warning mr-1"></i> Konfirmasi Karantina Berkas
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill kt-badge--bold mr-2">Pemisahan Berkas</span>
                    <span class="text-muted small">Tindakan Keamanan Sistem</span>
                </div>
                <p class="text-dark mb-2">
                    Apakah Anda yakin ingin memindahkan berkas yang dipilih ke folder karantina aman (<code>writable/uploads/_quarantine/</code>)?
                </p>
                <div class="alert alert-secondary mb-0" role="alert" style="border-radius: 4px;">
                    <div class="alert-icon"><i class="flaticon-information text-primary"></i></div>
                    <div class="alert-text small">
                        Berkas akan dicatat dalam berkas manifest dan dapat dipulihkan kembali sewaktu-waktu jika dibutuhkan.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #ebedf2;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                <button type="button" class="btn btn-brand btn-sm" id="btn-submit-quarantine-modal" style="border-radius: 4px;">
                    <i class="la la-lock"></i> Ya, Karantina Berkas
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Konfirmasi Pemulihan Berkas (Restore) -->
<div class="modal fade" id="modal-confirm-restore" tabindex="-1" role="dialog" aria-labelledby="modalRestoreLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 4px; box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15); border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #ebedf2;">
                <h5 class="modal-title font-weight-bold text-dark" id="modalRestoreLabel">
                    <i class="la la-undo text-success mr-1"></i> Konfirmasi Pemulihan Berkas
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="d-flex align-items-center mb-3">
                    <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill kt-badge--bold mr-2">Pemulihan Aman</span>
                    <span class="text-muted small">Restorasi ke Folder Asal</span>
                </div>
                <p class="text-dark mb-0">
                    Berkas yang dipilih akan dikembalikan dari folder karantina ke direktori unggahan aktif sistem.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #ebedf2;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                <button type="button" class="btn btn-brand btn-sm" id="btn-submit-restore-modal" style="border-radius: 4px;">
                    <i class="la la-undo"></i> Pulihkan Berkas
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Konfirmasi Penghapusan Permanen Berkas Karantina -->
<div class="modal fade" id="modal-confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modalDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 4px; box-shadow: 0px 0px 50px 0px rgba(82, 63, 105, 0.15); border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #ebedf2;">
                <h5 class="modal-title font-weight-bold text-danger" id="modalDeleteLabel">
                    <i class="la la-trash text-danger mr-1"></i> Hapus Permanen Berkas
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="alert alert-danger mb-3" role="alert" style="border-radius: 4px;">
                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                    <div class="alert-text font-weight-bold">
                        PERINGATAN: Tindakan ini permanen dan tidak dapat dibatalkan!
                    </div>
                </div>
                <p class="text-dark mb-0">
                    Berkas terpilih akan dihapus sepenuhnya dari media penyimpanan server dan statusnya dicatat sebagai riwayat pada manifest.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #ebedf2;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius: 4px;">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" id="btn-submit-delete-modal" style="border-radius: 4px;">
                    <i class="la la-trash"></i> Hapus Permanen
                </button>
            </div>
        </div>
    </div>
</div>
