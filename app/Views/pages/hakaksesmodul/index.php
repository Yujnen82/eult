<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!-- begin:: Content -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon">
                            <i class="flaticon-interface-7 text-primary"></i>
                        </span>
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper(esc($page_judul ?? 'Konfigurasi Hak Akses Modul')) ?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= esc($show_url ?? '#') ?>" method="post" id="form_show">
                    <div class="kt-portlet__body">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-dark">
                                Pilih Grup Pengguna <span class="text-danger">*</span>
                            </label>
                            <div class="row align-items-center">
                                <div class="col-md-9 col-lg-8">
                                    <select class="form-control m-select2" name="hakakses" id="select_role" required>
                                        <option value="">-- Pilih Grup Pengguna / Role --</option>
                                        <?php if (!empty($s_user_group)): ?>
                                            <?php foreach ($s_user_group as $row): ?>
                                                <option value="<?= esc($row['sgroupNama']) ?>">
                                                    <?= esc($row['sgroupNama']) ?> - <?= esc($row['sgroupKeterangan']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <span class="form-text text-muted mt-2">
                                        <i class="flaticon2-information mr-1"></i> Pilih grup pengguna untuk mengelola izin akses dan otorisasi menu modul sistem.
                                    </span>
                                </div>
                                <div class="col-md-3 col-lg-4 mt-3 mt-md-0">
                                    <button type="submit" id="btn_save" class="btn btn-brand btn-elevate btn-icon-sm">
                                        <i class="flaticon2-search-1"></i>
                                        Tampilkan Hak Akses
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <!--end::Form-->
            </div>
            <!--end::Portlet-->
        </div>
    </div>
</div>

<div id="response"></div>