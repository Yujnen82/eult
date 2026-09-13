<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<?php
helper(['eult_tanggal', 'eult_waktu']);

// Normalisasi data tiket dari controller (array $datas) atau unit test (object $ticket)
$rawTicket = $ticket ?? ($datas ?? []);
if (is_array($rawTicket)) {
    $t = (object) $rawTicket;
} elseif (is_object($rawTicket)) {
    $t = $rawTicket;
} else {
    $t = (object) [];
}

// Ekstraksi data pemohon & atribut tiket
$trackingId   = $t->ticketTrackingId ?? ($t->tracking_id ?? '-');
$namaPemohon  = $t->ticketName ?? ($t->nama ?? '-');
$identitas    = $t->ticketIdentitas ?? ($t->ticketIdentity ?? ($t->identitas ?? ''));
$email        = $t->ticketEmail ?? ($t->email ?? '-');
$telepon      = $t->ticketPhone ?? ($t->ticketNoHp ?? ($t->telepon ?? '-'));
$alamat       = $t->ticketAddress ?? ($t->ticketAlamat ?? ($t->alamat ?? '-'));
$tglDibuat    = $t->ticketCreated ?? ($t->created_at ?? date('Y-m-d H:i:s'));
$subjek       = $t->ticketSubject ?? ($t->subjek ?? '-');
$pesan        = $t->ticketMessage ?? ($t->ticketNotes ?? ($t->catatan ?? ''));
$statusId     = (int) ($t->ticketStatus ?? 1);
$statusNama   = $t->statusNama ?? 'Baru';
$statusColor  = !empty($t->statusColor) ? $t->statusColor : 'primary';
$kategoriNama = $t->categoryNama ?? '-';
$subKategori  = $t->sCatNama ?? '-';
$unitKerja    = $t->unitNama ?? '-';
$isVerified   = !empty($t->ticketIsVerified);
$verifiedBy   = $t->ticketVerifiedBy ?? '';
$verifiedAt   = $t->ticketVerified ?? '';

// Format tanggal Indonesia
$tglFormatted = function_exists('eult_tanggal_indo') ? eult_tanggal_indo(substr($tglDibuat, 0, 10)) : (function_exists('datetoindo') ? datetoindo(substr($tglDibuat, 0, 10)) : date('d-m-Y', strtotime($tglDibuat)));
$jamFormatted = date('H:i', strtotime($tglDibuat));

// Info sesi pengguna saat ini
$userGroup      = $user_group ?? [];
$userGroupName  = is_array($userGroup) ? ($userGroup['susrSgroupNama'] ?? 'ADMIN') : ($userGroup->susrSgroupNama ?? 'ADMIN');
$userProfilName = is_array($userGroup) ? ($userGroup['susrProfil'] ?? 'Petugas Layanan') : ($userGroup->susrProfil ?? 'Petugas Layanan');

// Kunci terenkripsi & URL aksi
$encKey      = $key ?? ($kunci ?? '');
$cetakTerima = $cetakterima ?? (!empty($encKey) ? site_url('ticketing/cetakterima/' . $encKey) : '#');
$surveyUrl   = 'https://docs.google.com/forms/d/e/1FAIpQLSe4Q8KLdpwCwx7CI3-IfS_YgrtNpc-tHeH14Ss75CkXyTaqRQ/viewform';
$archiveUrl  = $archive_url ?? false;
$outputUrl   = $output_url ?? false;
$saveUrl     = $save_url ?? site_url('ticketing/save_replies/');
$closeUrl    = $close_url ?? (!empty($encKey) ? site_url('ticketing/close/' . $encKey) : '#');
$loadAttach  = $load_attach ?? site_url('ticketing/loadattach');

// Riwayat & balasan percakapan
$historyItems = $history ?? [];
$replyItems   = $replies ?? [];
$workerItems  = $worker ?? [];
$fileItems    = $files ?? [];
$dataMhs      = $mhs ?? false;

// Kumpulan dokumen persyaratan & lampiran
$dokumenList = [];
if (!empty($archiveUrl) && $archiveUrl !== false) {
    $dokumenList[] = [
        'nama'        => 'Berkas Lampiran Pengajuan',
        'file'        => basename((string) $archiveUrl),
        'tipe'        => 'PDF',
        'ukuran'      => 'Berkas Digital',
        'status'      => $isVerified ? 'verified' : 'pending',
        'statusLabel' => $isVerified ? 'Terverifikasi' : 'Menunggu Verifikasi',
        'url'         => $archiveUrl,
    ];
}
if (!empty($outputUrl) && $outputUrl !== false) {
    $dokumenList[] = [
        'nama'        => 'Dokumen Output Layanan Resmi',
        'file'        => basename((string) $outputUrl),
        'tipe'        => 'PDF',
        'ukuran'      => 'Dokumen Terbit',
        'status'      => 'verified',
        'statusLabel' => 'Terverifikasi',
        'url'         => $outputUrl,
    ];
}
if (!empty($fileItems) && is_iterable($fileItems)) {
    foreach ($fileItems as $f) {
        $fObj          = is_array($f) ? (object) $f : $f;
        $dokumenList[] = [
            'nama'        => $fObj->fileLabel ?? ($fObj->nama ?? ($fObj->fileName ?? 'Berkas Persyaratan')),
            'file'        => $fObj->fileName ?? ($fObj->file ?? '-'),
            'tipe'        => strtoupper($fObj->fileType ?? ($fObj->tipe ?? 'PDF')),
            'ukuran'      => $fObj->fileSize ?? ($fObj->ukuran ?? '1.2 MB'),
            'status'      => !empty($fObj->isVerified) || ($fObj->status ?? '') === 'verified' ? 'verified' : 'pending',
            'statusLabel' => !empty($fObj->isVerified) || ($fObj->status ?? '') === 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi',
            'url'         => $fObj->fileUrl ?? ($fObj->url ?? '#'),
        ];
    }
}
?>

<!-- BEGIN: Content Workstation -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">

    <!-- Baris 1: Banner Ringkasan Tiket -->
    <div class="kt-portlet mb-4" style="border-top: 4px solid #5d78ff; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05); border-radius: 4px; background: #ffffff;">
        <div class="kt-portlet__body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="kt-badge kt-badge--unified-brand mr-3" style="width: 52px; height: 52px; border-radius: 6px; font-size: 24px; display: flex; align-items: center; justify-content: center;">
                        <i class="flaticon2-document text-primary" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <span id="ticketId" class="font-weight-bold" style="font-family: monospace; font-size: 1.35rem; color: #5d78ff; letter-spacing: 0.5px;">
                                <?= esc($trackingId) ?>
                            </span>
                            <span class="kt-badge kt-badge--unified-<?= esc($statusColor) ?> kt-badge--inline kt-badge--pill font-weight-bold px-3 py-1">
                                <?= esc($statusNama) ?>
                            </span>
                            <?php if ($isVerified): ?>
                                <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill font-weight-bold px-3 py-1">
                                    <i class="la la-check-circle mr-1"></i>Terverifikasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center text-muted mt-2 flex-wrap" style="font-size: 13px; gap: 16px;">
                            <span><i class="la la-user mr-1 text-primary"></i><strong><?= esc($namaPemohon) ?></strong><?= !empty($identitas) ? ' (' . esc($identitas) . ')' : '' ?></span>
                            <span><i class="la la-bookmark mr-1 text-primary"></i><?= esc($subKategori) ?><?= !empty($kategoriNama) && $kategoriNama !== '-' ? ' &bull; ' . esc($kategoriNama) : '' ?></span>
                            <span><i class="la la-calendar mr-1 text-primary"></i><?= esc($tglFormatted) ?> <?= esc($jamFormatted) ?> WITA</span>
                            <?php if (!empty($unitKerja) && $unitKerja !== '-'): ?>
                                <span><i class="la la-institution mr-1 text-primary"></i><?= esc($unitKerja) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <?php if (!empty($cetakTerima) && $cetakTerima !== '#'): ?>
                        <a href="<?= esc($cetakTerima) ?>" target="_blank" class="btn btn-sm btn-secondary font-weight-bold">
                            <i class="la la-print mr-1"></i> Cetak Bukti
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($surveyUrl)): ?>
                        <a href="<?= esc($surveyUrl) ?>" target="_blank" class="btn btn-sm btn-secondary font-weight-bold">
                            <i class="la la-pencil mr-1"></i> Survei IKM
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Baris 2: Workstation Dua Kolom (col-lg-5 & col-lg-7) -->
    <div class="row">

        <!-- KOLOM KIRI (col-lg-5): Data Pemohon & Dokumen Persyaratan -->
        <div class="col-lg-5">

            <!-- Card 1: Data Pemohon -->
            <div class="kt-portlet" style="box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05); border-radius: 4px;">
                <div class="kt-portlet__head kt-portlet__head--noborder" style="min-height: 54px; border-bottom: 1px solid #ebedf2;">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon-profile-1 text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold" style="font-size: 1.15rem; color: #48465b;">
                            Data Pemohon
                        </h3>
                    </div>
                </div>
                <div class="kt-portlet__body p-4">
                    <table class="table table-borderless table-sm mb-0" style="font-size: 13px;">
                        <tbody>
                            <tr>
                                <td class="text-muted font-weight-bold" style="width: 38%; padding: 6px 0;">Nama Pemohon</td>
                                <td class="font-weight-bold text-dark" style="padding: 6px 0;">: <?= esc($namaPemohon) ?></td>
                            </tr>
                            <?php if (!empty($identitas)): ?>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">No. Identitas / NIM</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($identitas) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">Email</td>
                                <td class="text-dark" style="padding: 6px 0;">
                                    : <a href="mailto:<?= esc($email) ?>" class="text-primary"><?= esc($email) ?></a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">No. Telepon / HP</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($telepon) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">Alamat</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($alamat) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">Tanggal Pengajuan</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($tglFormatted) ?> <?= esc($jamFormatted) ?> WITA</td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">Layanan</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($subKategori) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-weight-bold" style="padding: 6px 0;">Unit Penanggung Jawab</td>
                                <td class="text-dark" style="padding: 6px 0;">: <?= esc($unitKerja) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <hr style="border-top: 1px dashed #ebedf2; margin: 15px 0;">

                    <div class="mb-3">
                        <label class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Subjek / Keperluan</label>
                        <div class="font-weight-bold text-dark" style="font-size: 13px;">
                            <?= esc($subjek) ?>
                        </div>
                    </div>

                    <?php if (!empty($pesan)): ?>
                    <div class="mb-3">
                        <label class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Catatan Permohonan</label>
                        <div class="p-3 bg-light rounded text-body" style="font-size: 12.5px; border-left: 3px solid #5d78ff;">
                            <?= nl2br(esc($pesan)) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($dataMhs !== false && !empty($dataMhs)): ?>
                    <div class="mb-3">
                        <label class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Data Riwayat Mahasiswa (SIA)</label>
                        <div class="p-2 bg-light rounded text-dark" style="font-size: 12px;">
                            <?= esc($dataMhs) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($workerItems)): ?>
                    <div class="mb-3">
                        <label class="text-muted font-weight-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Petugas yang Menangani</label>
                        <ul class="pl-3 mb-0" style="font-size: 12.5px;">
                            <?php foreach ($workerItems as $w): ?>
                                <li><?= esc(is_object($w) ? ($w->susrProfil ?? '') : ($w['susrProfil'] ?? '')) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($statusId === 5): ?>
                    <div class="mt-3 pt-3" style="border-top: 1px solid #ebedf2;">
                        <label class="text-muted font-weight-bold text-uppercase d-block mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Indeks Kepuasan Pemohon (IKM)</label>
                        <input id="input-id" type="text" class="kv-uni-star rating-loading" value="<?= esc($t->ratingNilai ?? '') ?>" <?= !empty($t->ratingTicketId) ? 'disabled' : '' ?>>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card 2: Dokumen Persyaratan -->
            <div class="kt-portlet" style="box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05); border-radius: 4px;">
                <div class="kt-portlet__head kt-portlet__head--noborder" style="min-height: 54px; border-bottom: 1px solid #ebedf2;">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-document text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold" style="font-size: 1.15rem; color: #48465b;">
                            Dokumen Persyaratan
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <span class="kt-badge kt-badge--unified-brand kt-badge--inline font-weight-bold">
                            <?= count($dokumenList) ?> Berkas
                        </span>
                    </div>
                </div>
                <div class="kt-portlet__body p-3">
                    <?php if (!empty($dokumenList)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($dokumenList as $doc): ?>
                                <div class="list-group-item d-flex align-items-center justify-content-between px-2 py-3">
                                    <div class="d-flex align-items-center" style="max-width: 70%;">
                                        <div class="mr-3">
                                            <i class="la la-file-pdf-o text-danger" style="font-size: 28px;"></i>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark text-truncate" style="font-size: 13px;" title="<?= esc($doc['nama']) ?>">
                                                <?= esc($doc['nama']) ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 11.5px;">
                                                <span class="badge badge-light"><?= esc($doc['tipe']) ?></span> &bull; <?= esc($doc['ukuran']) ?>
                                            </div>
                                            <div class="mt-1">
                                                <?php if ($doc['status'] === 'verified'): ?>
                                                    <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill font-weight-bold" style="font-size: 11px;">
                                                        <i class="la la-check-circle mr-1"></i>Terverifikasi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="kt-badge kt-badge--unified-warning kt-badge--inline kt-badge--pill font-weight-bold" style="font-size: 11px;">
                                                        <i class="la la-clock-o mr-1"></i>Menunggu Verifikasi
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center" style="gap: 4px;">
                                        <?php if (!empty($doc['url']) && $doc['url'] !== '#'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-brand btn-icon btn-preview-doc" data-doc-url="<?= esc($doc['url']) ?>" data-doc-title="<?= esc($doc['nama']) ?>" title="Pratinjau Dokumen">
                                                <i class="la la-eye"></i>
                                            </button>
                                            <a href="<?= esc($doc['url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon" title="Unduh Berkas">
                                                <i class="la la-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted">
                            <i class="flaticon-attachment text-muted mb-2" style="font-size: 2rem; display: block;"></i>
                            <div class="font-weight-bold mb-1" style="font-size: 13px;">Belum Ada Berkas Digital Terunggah</div>
                            <span style="font-size: 12px;">Dokumen persyaratan permohonan ini diverifikasi langsung dari berkas fisik atau melalui lampiran pada formulir permohonan.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN (col-lg-7): Linimasa Disposisi & Tanggapan / Aksi Tiket -->
        <div class="col-lg-7">

            <!-- Card 1: Linimasa Disposisi & Riwayat Status -->
            <div class="kt-portlet" style="box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05); border-radius: 4px;">
                <div class="kt-portlet__head kt-portlet__head--noborder" style="min-height: 54px; border-bottom: 1px solid #ebedf2;">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-time text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold" style="font-size: 1.15rem; color: #48465b;">
                            Linimasa Disposisi & Riwayat Status
                        </h3>
                    </div>
                </div>
                <div class="kt-portlet__body p-4" style="max-height: 320px; overflow-y: auto;">
                    <div class="kt-notes">
                        <div class="kt-notes__items">
                            <?php if (!empty($historyItems)): ?>
                                <?php foreach ($historyItems as $h): ?>
                                    <?php
                                        $hObj    = is_array($h) ? (object) $h : $h;
                                        $tglH    = $hObj->tglHistory ?? ($hObj->created_at ?? '');
                                        $detailH = $hObj->detailHistory ?? ($hObj->keterangan ?? '');
                                        $tglHFormatted = !empty($tglH) ? (function_exists('eult_tanggal_indo') ? eult_tanggal_indo(substr($tglH, 0, 10)) : (function_exists('datetoindo') ? datetoindo(substr($tglH, 0, 10)) : date('d-m-Y', strtotime($tglH)))) : '-';
                                        $jamHFormatted = !empty($tglH) ? date('H:i', strtotime($tglH)) : '';
                                    ?>
                                    <div class="kt-notes__item kt-notes__item--clean">
                                        <div class="kt-notes__media">
                                            <span class="kt-notes__circle"></span>
                                        </div>
                                        <div class="kt-notes__content">
                                            <div class="kt-notes__section">
                                                <div class="kt-notes__info">
                                                    <span class="kt-notes__desc font-weight-bold text-dark" style="font-size: 12px;">
                                                        <i class="flaticon2-calendar-1 text-primary mr-1"></i>
                                                        <?= esc($tglHFormatted) ?> <?= esc($jamHFormatted) ?> WITA
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="kt-notes__body text-body mt-1" style="font-size: 13px;">
                                                <?= esc($detailH) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="flaticon2-time text-muted mb-2" style="font-size: 2rem; display: block;"></i>
                                    <span style="font-size: 13px;">Belum ada catatan linimasa disposisi atau perubahan status pada tiket ini.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Tanggapan & Aksi Tiket -->
            <div class="kt-portlet" id="kt_chat_content" style="box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05); border-radius: 4px;">
                <div class="kt-portlet__head kt-portlet__head--noborder" style="min-height: 54px; border-bottom: 1px solid #ebedf2;">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon-chat-1 text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold" style="font-size: 1.15rem; color: #48465b;">
                            Tanggapan & Aksi Tiket
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <span class="kt-badge kt-badge--unified-success kt-badge--inline font-weight-bold">
                            <span class="kt-badge kt-badge--dot kt-badge--success mr-1"></span> <?= esc($userProfilName) ?>
                        </span>
                    </div>
                </div>

                <!-- Bagian Pesan Tanggapan -->
                <div class="kt-portlet__body p-4">
                    <div class="kt-scroll" data-mobile-height="300" data-scroll="true" data-height="250" data-scrollbar-shown="true" style="max-height: 260px; overflow-y: auto; padding-right: 5px;">
                        <div class="kt-chat__messages">
                            <?php if (!empty($replyItems)): ?>
                                <?php foreach ($replyItems as $val): ?>
                                    <?php
                                        $valObj   = is_array($val) ? (object) $val : $val;
                                        $isMine   = ($valObj->repliesStatus ?? '') === $userGroupName;
                                        $dateMsg  = $valObj->repliesDate ?? '';
                                        $timeText = !empty($dateMsg) ? (function_exists('findTimeAgo') ? findTimeAgo($dateMsg) : (function_exists('eult_waktu_lalu') ? eult_waktu_lalu($dateMsg) : date('d/m/Y H:i', strtotime($dateMsg)))) : '';
                                    ?>
                                    <div class="kt-chat__message <?= $isMine ? 'kt-chat__message--right' : '' ?> mb-3">
                                        <div class="kt-chat__user mb-1">
                                            <span class="kt-media kt-media--circle kt-media--sm">
                                                <img src="/assets/media/users/<?= $isMine ? 'admin.jpg' : 'user.jpg' ?>" alt="avatar">
                                            </span>
                                            <a href="#" class="kt-chat__username font-weight-bold ml-1"><?= $isMine ? 'Anda' : esc($valObj->repliesBy ?? 'Pemohon') ?></a>
                                            <span class="kt-chat__datetime text-muted ml-2" style="font-size: 11px;"><?= esc($timeText) ?></span>
                                        </div>
                                        <div class="kt-chat__text p-3 rounded kt-bg-light-<?= $isMine ? 'primary' : 'success' ?>" style="font-size: 13px;">
                                            <?= esc($valObj->repliesMessage ?? '') ?>
                                            <?php if (!empty($valObj->repliesFile)): ?>
                                                <div class="mt-2 pt-2" style="border-top: 1px dashed rgba(0,0,0,0.1);">
                                                    <i class="flaticon-attachment mr-1"></i>
                                                    <a href="<?= esc($loadAttach . '/' . $valObj->repliesFile) ?>" target="_blank" class="font-weight-bold">
                                                        <?= esc($valObj->repliesFile) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="flaticon-chat text-muted mb-2" style="font-size: 2rem; display: block;"></i>
                                    <span style="font-size: 13px;">Belum ada tanggapan pada tiket ini. Tulis tanggapan atau instruksi pada formulir di bawah.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bagian Form Tanggapan & Tombol Aksi Terpadu -->
                <div class="kt-portlet__foot p-4" style="border-top: 1px solid #ebedf2; background: #fafbfe;">
                    <form action="<?= esc($saveUrl) ?>" method="POST" enctype="multipart/form-data" id="form_chat">
                        <input type="hidden" name="repliesTicketId" value="<?= esc($trackingId) ?>">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold text-dark mb-1" style="font-size: 12px;">Tulis Tanggapan Resmi</label>
                            <textarea class="form-control" style="height: 65px; resize: none; font-size: 13px; border-radius: 4px;" placeholder="Tulis tanggapan, arahan, atau verifikasi untuk pemohon..." name="repliesMessage" required></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <div class="custom-file">
                                <input type="file" name="chatFile" class="custom-file-input" id="customFile">
                                <label class="custom-file-label text-muted" for="customFile" style="font-size: 12px; border-radius: 4px;">Pilih berkas lampiran pendukung (maks. 15MB)...</label>
                            </div>
                        </div>

                        <!-- Baris Aksi Tiket & Tanggapan Terpadu -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between pt-2" style="gap: 8px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                <button type="submit" class="btn btn-brand btn-sm font-weight-bold kt-chat__reply">
                                    <i class="la la-paper-plane mr-1"></i> Kirim Tanggapan
                                </button>
                                <?php if (!empty($encKey) && $userGroupName !== 'USER'): ?>
                                    <a href="<?= site_url('ticketing/terima/' . $encKey) ?>" class="btn btn-secondary btn-sm font-weight-bold" id="btn-verifikasi-cepat" title="Verifikasi berkas persyaratan">
                                        <i class="la la-check text-success mr-1"></i> Verifikasi Berkas
                                    </a>
                                    <a href="<?= site_url('ticketing/assign/' . $encKey) ?>" class="btn btn-secondary btn-sm font-weight-bold" title="Disposisikan tiket ke unit kerja">
                                        <i class="la la-share text-primary mr-1"></i> Disposisikan
                                    </a>
                                    <a href="<?= site_url('ticketing/createSurat/' . $encKey) ?>" class="btn btn-secondary btn-sm font-weight-bold" title="Buat draf surat resmi">
                                        <i class="la la-file-text text-info mr-1"></i> Draf Surat
                                    </a>
                                    <a href="<?= site_url('ticketing/last_validated/' . $encKey) ?>" class="btn btn-secondary btn-sm font-weight-bold" title="Terbitkan QR Code keabsahan berkas">
                                        <i class="la la-qrcode text-dark mr-1"></i> Terbitkan QR
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (!empty($closeUrl) && $closeUrl !== '#' && $userGroupName !== 'USER'): ?>
                                    <a href="<?= esc($closeUrl) ?>" id="ts_close_btn" class="btn btn-danger btn-sm font-weight-bold kt-chat__reply" onclick="return confirm('Apakah Anda yakin ingin menutup tiket ini?');">
                                        <i class="la la-times-circle mr-1"></i> Tutup Tiket
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>

</div>
<!-- END: Content Workstation -->

<!-- Modal Pratinjau Dokumen -->
<div class="modal fade" id="modal-preview-doc" tabindex="-1" role="dialog" aria-labelledby="modalPreviewDocLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 4px; overflow: hidden;">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title font-weight-bold" id="modalPreviewDocLabel" style="font-size: 1.1rem; color: #48465b;">
                    <i class="la la-file-pdf-o text-danger mr-1"></i> Pratinjau Dokumen: <span id="preview-filename" class="text-primary font-weight-normal"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="height: 75vh; background: #525659;">
                <iframe id="iframe-doc-preview" src="" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer py-2">
                <a href="#" target="_blank" class="btn btn-outline-brand btn-sm font-weight-bold" id="btn-open-external">
                    <i class="la la-external-link mr-1"></i> Buka di Tab Baru
                </a>
                <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Penanganan klik pratinjau dokumen modal
    $('.btn-preview-doc').on('click', function (e) {
        e.preventDefault();
        var docUrl = $(this).attr('data-doc-url');
        var docTitle = $(this).attr('data-doc-title') || 'Dokumen Persyaratan';
        $('#preview-filename').text(docTitle);
        $('#iframe-doc-preview').attr('src', docUrl);
        $('#btn-open-external').attr('href', docUrl);
        $('#modal-preview-doc').modal('show');
    });

    // Reset URL iframe saat modal ditutup untuk mencegah memori bocor
    $('#modal-preview-doc').on('hidden.bs.modal', function () {
        $('#iframe-doc-preview').attr('src', '');
    });

    // Tampilkan nama berkas pada custom file input
    $('#customFile').on('change', function () {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass('selected').html(fileName || 'Pilih berkas lampiran pendukung...');
    });
});
</script>