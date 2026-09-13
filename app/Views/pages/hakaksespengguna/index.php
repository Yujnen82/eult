<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<style>
    html {
        scroll-behavior: smooth;
    }
    #response {
        scroll-margin-top: 135px;
    }
    #response .table-row-selected td {
        background-color: rgba(93, 120, 255, 0.08);
    }
    @media (prefers-reduced-motion: reduce) {
        html {
            scroll-behavior: auto;
        }
    }
</style>

<!-- begin:: Content -->
<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <!--begin::Portlet Filter-->
            <div class="kt-portlet mb-4">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon">
                            <i class="flaticon-users text-primary"></i>
                        </span>
                        <h3 class="kt-portlet__head-title">
                            <?= strtoupper(esc($page_judul ?? 'Konfigurasi Hak Akses Pengguna')) ?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= esc($show_url ?? '#') ?>" method="post" id="form_show">
                    <div class="kt-portlet__body">
                        <?php if (empty($s_user)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="flaticon2-user d-block mb-2" style="font-size: 2rem; color: #a1a5b7;"></i>
                                Belum ada pengguna yang terdaftar dalam sistem.
                            </div>
                        <?php else: ?>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold text-dark mb-2" for="select_pengguna">
                                    Pilih Pengguna <span class="text-danger">*</span>
                                </label>
                                <div class="row align-items-start">
                                    <div class="col-md-8 col-lg-9">
                                        <select class="form-control m-select2" name="pengguna" id="select_pengguna" data-placeholder="-- Pilih Pengguna --" required>
                                            <option value="">-- Pilih Pengguna --</option>
                                            <?php foreach ($s_user as $row): ?>
                                                <option value="<?= esc($row['susrNama']) ?>">
                                                    <?= esc($row['susrNama']) ?> - <?= esc($row['susrProfil'] ?? '') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-lg-3 mt-3 mt-md-0 text-md-right">
                                        <button type="submit" id="btn_show" class="btn btn-brand btn-elevate btn-icon-sm w-100 w-md-auto">
                                            <i class="flaticon2-search-1"></i>
                                            Tampilkan Hak Akses
                                        </button>
                                    </div>
                                </div>
                                <span class="form-text text-muted mt-2">
                                    <i class="flaticon2-information mr-1"></i> Pilih akun pengguna untuk mengelola keanggotaan grup dan hak akses yang melekat padanya.
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
                <!--end::Form-->
            </div>
            <!--end::Portlet Filter-->

            <!--begin::Matriks Response Container-->
            <div id="response"></div>
            <!--end::Matriks Response Container-->
        </div>
    </div>
</div>
