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
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= esc($save_url) ?>" method="post" id="form_surat">
                    <div class="kt-portlet__body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="suratTrackingId" id="suratTrackingId" value="<?= esc($kunci ?? '', 'attr') ?>">
                        <input type="hidden" name="suratIdOld" value="<?= $datas != FALSE ? service('enkripsi')->encode($datas['suratId']) : ''; ?>">
                        <div class="form-group">
                            <label>Nomor Surat <strong style="color:red">*</strong></label>
                            <input class="form-control" type="text" name="suratNomor" value="<?= $datas != FALSE ? $datas['suratNomor'] : (empty($surat) ? '' : $surat['tsuratNomor']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Surat <strong style="color:red">*</strong></label>
                            <input class="form-control" type="text" name="suratJenis" value="<?= $datas != FALSE ? $datas['suratJenis'] : (empty($surat) ? '' : $surat['tsuratPerihal']) ?>">
                        </div>
                        <!-- <div class="form-group">
                            <label>Nomor Surat <strong style="color:red">*</strong></label>
                            <input class="form-control" type="text" name="suratNomor">
                        </div> -->
                        <div class="form-group">
                            <label>Perihal <strong style="color:red">*</strong></label>
                            <input class="form-control" type="text" name="suratPerihal" value="<?= $datas != FALSE ? $datas['suratPerihal'] : (empty($surat) ? '' : $surat['tsuratPerihal']) ?>">
                        </div>
                        <?php               
                        if ($surat != false): ?>
                        <div class="form-group">
                            <label>Lampiran</label>
                            <input class="form-control" type="text" name="suratLampiran" value="<?= $datas != FALSE ? $datas['suratLampiran'] : (empty($surat) ? '' : $surat['tsuratLampiran']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tujuan Surat</label>
                            <div class="kt-tinymce">
                                <textarea class="form-control kt-tinymce-4" id="suratTujuan" name="suratTujuan" class="tox-target" rows="25">
                                    <?= $datas != FALSE ? $datas['suratTujuan'] : (empty($surat) ? '' : $surat['tsuratTujuan']) ?>
                                </textarea>
                            </div>
                            <span class="form-text text-muted">* Harap diperhatikan tulisan dalam kurung cetak tebal.</span>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Isi Surat <strong style="color:red">*</strong></label>
                            <div class="kt-tinymce">
                                <textarea class="form-control kt-tinymce-4" id="suratBody" name="suratBody" class="tox-target" rows="25">
                                    <?= $datas != FALSE ? $datas['suratBody'] : (empty($surat) ? '' : $surat['tsuratIsi']) ?>
                                </textarea>
                            </div>
                            <span class="form-text text-muted">* Harap diperhatikan tulisan dalam kurung cetak tebal.</span>
                        </div>
                        <div class="form-group">
                            <label>Tembusan <strong style="color:red">*</strong></label>
                            <div class="kt-tinymce">
                                <textarea class="form-control kt-tinymce-4" id="suratFooter" name="suratFooter" class="tox-target" rows="25">
                                    <?= $datas != FALSE ? $datas['suratFooter'] : (empty($surat) ? '' : $surat['tsuratFooter']) ?>
                                </textarea>
                            </div>
                            <span class="form-text text-muted">* Harap diperhatikan tulisan dalam kurung cetak tebal.</span>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Surat <strong style="color:red">*</strong></label>
                            <div class="input-group date">
                                <input type="text" autocomplete="off" class="form-control" placeholder="Select date" id="kt_datepicker_4" name="suratTanggal" value="<?= $datas != false ? $datas['suratTanggal'] : '' ?>" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <i class="la la-calendar-check-o"></i>
                                    </span>
                                </div>
                            </div>                            
                        </div>
                        
                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-primary">Save</button>
                            <!-- <a href="<?= $preview_url ?><?= $datas != FALSE ? service('enkripsi')->encode($datas['suratId']) : ''; ?>"  class="btn btn-secondary">Previewmm</a> -->
                        </div>
                    </div>
                </form>

                <!--end::Form-->
            </div>

            <!--end::Portlet-->
        </div>
    </div>
</div>
<!--End::Row-->