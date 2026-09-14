<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper(esc(($status_page ?? 'Form') . ' ' . ($page_judul ?? 'Grup Modul'))) ?>
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions">
                            <a href="<?= site_url('modulgroup') ?>" class="btn btn-secondary btn-elevate btn-icon-sm">
                                <i class="flaticon2-back"></i>
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_form">
                    <?= csrf_field() ?>
                    <div class="kt-portlet__body">
                        <input type="hidden" name="susrmdgroupNamaOld" value="<?= $datas != false ? esc($datas['susrmdgroupNama']) : '' ?>">

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Nama Grup Modul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="susrmdgroupNama" placeholder="Contoh: admin, ticketing, referensi" aria-describedby="Nama Grup" value="<?= $datas != false ? esc($datas['susrmdgroupNama']) : '' ?>" required>
                            <span class="form-text text-muted">Identifier unik untuk grup modul (gunakan huruf kecil tanpa spasi).</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Label Tampilan Grup <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="susrmdgroupDisplay" placeholder="Contoh: Administrator, Laporan, Referensi" aria-describedby="Display Grup" value="<?= $datas != false ? esc($datas['susrmdgroupDisplay']) : '' ?>" required>
                            <span class="form-text text-muted">Nama yang akan ditampilkan pada menu navigasi samping dan navigasi sistem.</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Ikon Grup Modul <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="susrmdgroupIcon" rows="2" placeholder="Contoh: <i class=&quot;la la-desktop&quot;></i>" required><?= $datas != false ? esc($datas['susrmdgroupIcon']) : '' ?></textarea>
                            <span class="form-text text-muted">Gunakan tag HTML icon Metronic seperti <code>&lt;i class="la la-desktop"&gt;&lt;/i&gt;</code> atau <code>&lt;i class="flaticon-file"&gt;&lt;/i&gt;</code>.</span>
                        </div>
                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-brand btn-elevate">
                                <i class="flaticon2-checkmark"></i> Simpan Data
                            </button>
                            <a href="<?= site_url('modulgroup') ?>" class="btn btn-secondary ml-2">Batal</a>
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