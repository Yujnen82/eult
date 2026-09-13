<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper($page_judul) ?>
                        </h3>
                    </div>
                    <!-- <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions">
                            <?php if ($user_group == 'ADMIN') { ?>
                                <a href="<?= $export_url ?>" class="btn btn-outline-success" id="btn-create">
                                    <span>
                                        <i class="fa fa-file-excel"></i>
                                        <span>Export To Excel</span>
                                    </span>
                                </a>
                            <?php } ?>
                        </div>
                    </div> -->
                </div>

                <div class="kt-portlet__body">

                    <!--begin::Section-->
                    <div class="kt-section">
                        <div class="kt-section__content">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="text-align: center">Nomor Tracking / Customer</th>
                                            <th style="text-align: center">Layanan / Keperluan</th>
                                            <th style="text-align: center">Prioritas</th>
                                            <th style="text-align: center">Status/Disposisi Tiket</th>
                                            <th style="text-align: center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($datas != false) {
                                            $i = 1;
                                            foreach ($datas as $row) {
                                                $key = service('enkripsi')->encode($row['ticketTrackingId']);
                                                $istrue = !($user_group == 'ADMIN' or (strpos($user_group, 'OPERATOR') !== FALSE)) ? $row['disposisiIsTrue'] : FALSE;
                                                $suratistrue = empty($row['ticketSuratCreated']);
                                                $isBottomTop = $row['sCatDisposisi'] != 'BOTTOMUP' ? FALSE : TRUE;                                        
                                                $isHome = $sgroup != false ? (($sgroup->sgroupunitUnitId == $row['ticketAssign'] and $row['disposisiIsTrue'] != 1) ? true : false) : false;
                                                $verified = ($row['ticketIsVerified'] != 1 AND $row['jenislayananId'] == 2 AND $row['sCatDisposisi'] == 'BOTTOMUP' AND $row['statusId'] == 3)? '<span class="kt-badge kt-badge--danger kt-badge--inline kt-badge--pill kt-badge--rounded">Belum Verifikasi</span>':'';
                                                $suratIsCreated = ($suratistrue != FALSE AND $isBottomTop == TRUE)? '<span class="kt-badge kt-badge--warning kt-badge--inline kt-badge--pill kt-badge--rounded">Surat Sedang Dalam Proses</span>':'';
                                        ?>
                                                <tr>
                                                    <td nowrap style="vertical-align: middle" align="center"><a href="<?= $detail_url . $key ?>"><?= $row['ticketTrackingId'] ?></a><br><?= $row['ticketName']."<p class='text-success'>".DateToIndo($row['ticketCreated'])." ".date('H:i:s',strtotime($row['ticketCreated']))."</p>" ?></td>
                                                    <td style="vertical-align: middle" align="center"><b><?= $row['categoryNama'] . "</b><br> " . $row['sCatNama'] ?></td>
                                                    <td style="vertical-align: middle" align="center"><?= $row['priorityName'] ?></td>
                                                    <td style="vertical-align: middle" nowrap align="center">
                                                        <p class="kt-font-boldest"><?= $row['unitNama'] ?></p><span class="kt-badge kt-badge--<?= $row['statusColor'] ?> kt-badge--inline kt-badge--pill kt-badge--rounded"><?= $row['statusNama'] ?></span><br><?=$verified?><?=$row['ticketIsVerified'] == 1?$suratIsCreated:''?>
                                                    </td>
                                                    <td style="vertical-align: middle" align="center" nowrap>
                                                        <?php if (in_array($row['ticketStatus'], array(1, 2, 3, 4, 5, 6, 7, 9, 10)) or $user_group == 'ADMIN') {
                                                            $param = array(
                                                                'key' => $key,
                                                                'status' => $row['ticketStatus'],
                                                                'urut' => $i,
                                                                'disposisiIsTrue' => $istrue,
                                                                'suratCreated' => $suratistrue,
                                                                'jenis' => $row['sCatDisposisi'],
                                                                'isVerified' => $row['ticketIsVerified'],
                                                                'isRejected' => $row['disposisiIsRejected'],
                                                                'isSehari' => $row['jenislayananId'],
                                                                'ishome' => $isHome
                                                                ,
                                                                'layanan' => $row['sCatId']
                                                            );
                                                            getaction($param);
                                                        } ?>
                                                    </td>
                                                </tr>
                                        <?php
                                                $i++;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!--end::Section-->
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