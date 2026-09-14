<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-xl-8 col-lg-10 col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head flex-wrap">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon">
                            <i class="flaticon2-file-1 text-brand"></i>
                        </span>
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper(esc(($status_page ?? 'Form') . ' ' . ($page_judul ?? 'Persyaratan Layanan'))) ?>
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions py-2">
                            <a href="<?= site_url('refsyarat') ?>" class="btn btn-secondary btn-elevate btn-icon-sm">
                                <i class="flaticon2-back"></i>
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_form">
                    <div class="kt-portlet__body">
                        <input type="hidden" name="berkasIdOld" value="<?= $datas != false ? esc($datas['berkasId']) : '' ?>">

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark" for="berkasidLayanan">Layanan <span class="text-danger">*</span></label>
                                    <select class="form-control m-select2" id="berkasidLayanan" name="berkasidLayanan" data-placeholder="Pilih Layanan" required>
                                        <option value="">Pilih Layanan</option>
                                        <?php if (!empty($ref_layanan)): ?>
                                            <?php foreach ($ref_layanan as $row): ?>
                                                <option value="<?= esc($row['layananId']) ?>" <?= ($datas != false && (string) $row['layananId'] === (string) $datas['berkasidLayanan']) ? 'selected' : '' ?>>
                                                    <?= esc($row['layananNama']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <span class="form-text text-muted">Layanan publik tempat persyaratan berkas ini berlaku.</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark" for="berkasNama">Nama Persyaratan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="berkasNama" name="berkasNama" placeholder="Contoh: Fotokopi Ijazah, KTP Pemohon" aria-describedby="berkasNamaHelp" value="<?= $datas != false ? esc($datas['berkasNama']) : '' ?>" required>
                                    <span class="form-text text-muted" id="berkasNamaHelp">Judul berkas yang harus dilampirkan pemohon saat mengajukan tiket.</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-dark" for="berkasKeterangan">Keterangan</label>
                            <textarea class="form-control" id="berkasKeterangan" name="berkasKeterangan" rows="3" placeholder="Contoh: Scan berwarna, masih berlaku, maksimal 2 MB" aria-describedby="berkasKeteranganHelp"><?= $datas != false ? esc($datas['berkasKeterangan'] ?? '') : '' ?></textarea>
                            <span class="form-text text-muted" id="berkasKeteranganHelp">Petunjuk tambahan untuk pemohon (format, ukuran, atau kondisi berkas). Opsional.</span>
                        </div>
                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions d-flex flex-wrap align-items-center">
                            <button type="submit" id="btn_save" class="btn btn-brand btn-elevate">
                                <i class="flaticon2-checkmark"></i> Simpan Data
                            </button>
                            <a href="<?= site_url('refsyarat') ?>" class="btn btn-secondary ml-2">Batal</a>
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
