<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet kt-portlet--mobile">
                <div class="kt-portlet__head kt-portlet__head--lg">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-layers-1 text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold">
                            <?= esc(strtoupper($page_judul ?? 'Daftar Tiket Permohonan Layanan')) ?>
                        </h3>
                    </div>
                    <?php if (!empty($export_url) && ($user_group ?? '') === 'ADMIN'): ?>
                        <div class="kt-portlet__head-toolbar">
                            <div class="kt-portlet__head-actions">
                                <a href="<?= esc($export_url) ?>" class="btn btn-sm btn-outline-success font-weight-bold" id="btn-export">
                                    <i class="fa fa-file-excel mr-1"></i> Ekspor Excel
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="kt-portlet__body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0" id="table_ticketing">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 22%; font-size: 12px; vertical-align: middle;">Nomor Tracking / Pemohon</th>
                                    <th style="width: 28%; font-size: 12px; vertical-align: middle;">Layanan &amp; Keperluan</th>
                                    <th style="width: 12%; font-size: 12px; text-align: center; vertical-align: middle;">Prioritas</th>
                                    <th style="width: 20%; font-size: 12px; vertical-align: middle;">Status / Unit</th>
                                    <th style="width: 18%; font-size: 12px; text-align: center; vertical-align: middle;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($datas)) {
                                    $i = 1;
                                    foreach ($datas as $row) {
                                        $key            = service('enkripsi')->encode($row['ticketTrackingId']);
                                        $istrue         = !(($user_group ?? '') === 'ADMIN' || (strpos(($user_group ?? ''), 'OPERATOR') !== false)) ? ($row['disposisiIsTrue'] ?? false) : false;
                                        $suratistrue    = empty($row['ticketSuratCreated']);
                                        $isBottomTop    = ($row['sCatDisposisi'] ?? '') === 'BOTTOMUP';
                                        $isHome         = !empty($sgroup) ? ((($sgroup->sgroupunitUnitId ?? null) == ($row['ticketAssign'] ?? null) && ($row['disposisiIsTrue'] ?? null) != 1)) : false;
                                        $verified       = (($row['ticketIsVerified'] ?? 0) != 1 && ($row['jenislayananId'] ?? 0) == 2 && ($row['sCatDisposisi'] ?? '') === 'BOTTOMUP' && ($row['statusId'] ?? 0) == 3)
                                            ? '<span class="kt-badge kt-badge--unified-danger kt-badge--inline kt-badge--pill font-weight-bold mt-1">Belum Verifikasi</span>'
                                            : '';
                                        $suratIsCreated = ($suratistrue && $isBottomTop)
                                            ? '<span class="kt-badge kt-badge--unified-warning kt-badge--inline kt-badge--pill font-weight-bold mt-1">Surat Sedang Dalam Proses</span>'
                                            : '';
                                        $tglFormatted   = function_exists('eult_tanggal_indo')
                                            ? eult_tanggal_indo(substr($row['ticketCreated'], 0, 10))
                                            : date('d-m-Y', strtotime($row['ticketCreated']));
                                        $jamFormatted   = date('H:i:s', strtotime($row['ticketCreated']));
                                ?>
                                        <tr>
                                            <!-- Kolom 1: Nomor Tracking & Pemohon -->
                                            <td style="vertical-align: middle;">
                                                <a href="<?= esc(($detail_url ?? base_url('ticketing/detail/')) . $key) ?>" class="font-weight-bold" style="font-family: monospace; font-size: 13px; color: #5d78ff; text-decoration: none;">
                                                    <?= esc($row['ticketTrackingId']) ?>
                                                </a>
                                                <div class="font-weight-bold text-dark mt-1" style="font-size: 13px;">
                                                    <?= esc($row['ticketName']) ?>
                                                </div>
                                                <span class="text-muted" style="font-size: 11px;">
                                                    <i class="flaticon2-calendar-1 mr-1"></i><?= $tglFormatted ?> <?= $jamFormatted ?>
                                                </span>
                                            </td>

                                            <!-- Kolom 2: Layanan & Keperluan -->
                                            <td style="vertical-align: middle;">
                                                <div class="font-weight-bold text-dark" style="font-size: 13px;">
                                                    <?= esc($row['categoryNama'] ?? '') ?>
                                                </div>
                                                <div class="text-muted mt-1" style="font-size: 12px; line-height: 1.4;">
                                                    <?= esc($row['sCatNama'] ?? '') ?>
                                                </div>
                                            </td>

                                            <!-- Kolom 3: Prioritas -->
                                            <td style="vertical-align: middle; text-align: center;">
                                                <span class="kt-badge kt-badge--unified-<?= (in_array(strtolower($row['priorityName'] ?? ''), ['tinggi', 'urgent', 'high'])) ? 'danger' : 'secondary' ?> kt-badge--inline font-weight-bold">
                                                    <?= esc($row['priorityName'] ?? 'Normal') ?>
                                                </span>
                                            </td>

                                            <!-- Kolom 4: Status / Unit -->
                                            <td style="vertical-align: middle;">
                                                <div class="font-weight-bold text-dark mb-1" style="font-size: 12px;">
                                                    <?= esc($row['unitNama'] ?? '') ?>
                                                </div>
                                                <span class="kt-badge kt-badge--unified-<?= esc($row['statusColor'] ?? 'info') ?> kt-badge--inline kt-badge--pill font-weight-bold">
                                                    <?= esc($row['statusNama'] ?? '') ?>
                                                </span>
                                                <?php if (!empty($verified)): ?>
                                                    <div><?= $verified ?></div>
                                                <?php endif; ?>
                                                <?php if (($row['ticketIsVerified'] ?? 0) == 1 && !empty($suratIsCreated)): ?>
                                                    <div><?= $suratIsCreated ?></div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Kolom 5: Aksi -->
                                            <td style="vertical-align: middle; text-align: center;" nowrap>
                                                <div class="d-flex align-items-center justify-content-center flex-wrap" style="gap: 4px;">
                                                    <?php if (in_array($row['ticketStatus'] ?? 0, [1, 2, 3, 4, 5, 6, 7, 9, 10]) || ($user_group ?? '') === 'ADMIN') {
                                                        $param = [
                                                            'key'             => $key,
                                                            'status'          => $row['ticketStatus'] ?? 0,
                                                            'urut'            => $i,
                                                            'disposisiIsTrue' => $istrue,
                                                            'suratCreated'    => $suratistrue,
                                                            'jenis'           => $row['sCatDisposisi'] ?? null,
                                                            'isVerified'      => $row['ticketIsVerified'] ?? 0,
                                                            'isRejected'      => $row['disposisiIsRejected'] ?? 0,
                                                            'isSehari'        => $row['jenislayananId'] ?? 0,
                                                            'ishome'          => $isHome,
                                                            'layanan'         => $row['sCatId'] ?? '',
                                                            'user_group'      => $user_group ?? 'ADMIN',
                                                        ];
                                                        if (function_exists('getaction')) {
                                                            getaction($param);
                                                        } elseif (function_exists('eult_tombol_aksi')) {
                                                            eult_tombol_aksi($param);
                                                        }
                                                    } ?>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                                        $i++;
                                    }
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="my-3">
                                                <i class="flaticon2-open-box" style="font-size: 3rem; color: #a1a8c3;"></i>
                                            </div>
                                            <div class="font-weight-bold" style="font-size: 14px; color: #48465b;">Tidak Ada Data Tiket Ditemukan</div>
                                            <span class="text-muted" style="font-size: 12px;">Silakan pilih filter tanggal atau unit layanan yang berbeda.</span>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--end::Portlet-->
        </div>
    </div>
</div>
<!--End::Row-->


<!--begin::Modal-->
<div class="modal fade" id="nomor_surat" tabindex="-1" role="dialog" aria-labelledby="nomor_surat" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nomor_surat">Nomor Surat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <form id="form_nomor_surat">
                    <div class="form-group">
                        <label class="form-control-label">Nomor Surat:</label>
                        <input type="hidden" name="f_nomor_tiket" class="form-control" id="f_nomor_tiket">
                        <input type="text" name="f_nomor_surat" class="form-control" id="f_nomor_surat">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="btn_nomor_surat" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->

<!--begin::Modal-->
<div class="modal fade" id="pesan_tolak" tabindex="-1" role="dialog" aria-labelledby="pesan_tolak" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pesan_tolak">Pesan Tolak</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <form id="form_pesan_tolak">
                    <div class="form-group">
                        <label class="form-control-label">Pesan Tolak:</label>
                        <input type="hidden" name="f_nomor_tiket_tolak" class="form-control" id="f_nomor_tiket_tolak">
                        <textarea name="f_pesan_tolak" class="form-control" id="f_pesan_tolak" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="btn_pesan_tolak" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->

<!--begin::Modal-->
<div class="modal fade" id="pesan_validasi" tabindex="-1" role="dialog" aria-labelledby="nomor_surat" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nomor_surat">Pesan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <form id="form_nomor_surat" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-control-label">Pesan:</label>
                        <input type="hidden" name="f_nomor_tiket" class="form-control" id="f_nomor_tiket_validasi">
                        <textarea type="text" name="f_pesan_validasi" class="form-control" id="f_pesan_validasi"></textarea>
                    </div>
                    <div class="form-group">
                            <label>Dokumen</label>
                            <input type="file" class="form-control" id="f_archive_id" name="f_archive_id" placeholder="Tiket Dokumen" aria-describedby="Tiket Dokumen">
                        </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="btn_pesan_validasi" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->

<!--begin::Modal-->
<div class="modal fade" id="ektm" tabindex="-1" role="dialog" aria-labelledby="ektm" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ektm">Pengantar E-KTM</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <form id="form_ektm">
                    <div class="form-group">
                        <label class="form-control-label">Nomor Surat Permohonan</label>
                        <input type="hidden" name="f_nomor_tiket_ektm" class="form-control" id="f_nomor_tiket_ektm" readonly>
                        <input type="text" name="ns_pemohon" class="form-control" id="ns_pemohon">
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">Tanggal Surat Permohonan</label>
                        <input type="text" autocomplete="off" class="ts_pemohon form-control" placeholder="Select date" id="kt_datepicker_4" name="ts_pemohon"/>
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">Nomor Surat Pengantar E-KTM</label>
                        <input type="text" name="ns_pengantar" class="form-control" id="ns_pengantar">
                    </div>
                    <div class="form-group">
                        <label class="form-control-label">Bank Tujuan</label>
                        <select class="form-control m-select2" id="bank" name="bank">
                                    <option value="">Pilih</option>
                                    <option value="BTN">BTN</option>
                                    <option value="BANKALTIMTARA">BANKALTIMTARA</option>
                                    <option value="BNI">BNI</option>
                                </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="btn_ektm" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal-->