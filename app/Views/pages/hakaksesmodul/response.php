<!--begin::Form-->
<form action="<?= esc($save_url ?? '#') ?>" method="post" id="form_custom">
    <input type="hidden" name="sgroupNama" value="<?= esc(!empty($sgroupNama) ? $sgroupNama : '') ?>">
    <div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
        <div class="row">
            <div class="col-md-12">
                <!--begin::Portlet-->
                <div class="kt-portlet">
                    <div class="kt-portlet__head">
                        <div class="kt-portlet__head-label">
                            <span class="kt-portlet__head-icon">
                                <i class="flaticon-lock text-primary"></i>
                            </span>
                            <h3 class="kt-portlet__head-title">
                                Matriks Hak Akses Modul: <span class="text-primary font-weight-bold ml-1"><?= esc($sgroupNama ?? '') ?></span>
                            </h3>
                        </div>
                        <div class="kt-portlet__head-toolbar">
                            <div class="kt-portlet__head-actions">
                                <button type="button" class="btn btn-sm btn-outline-brand btn-elevate mr-2" id="btn_toggle_all">
                                    <i class="flaticon2-check-mark"></i> Pilih Semua
                                </button>
                                <button type="submit" id="btn_save" class="btn btn-sm btn-brand btn-elevate">
                                    <i class="flaticon2-checkmark"></i>
                                    Simpan Perubahan Hak Akses
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="kt-portlet__body">
                        <div class="alert alert-light alert-elevate fade show mb-4" role="alert">
                            <div class="alert-icon"><i class="flaticon-information text-primary"></i></div>
                            <div class="alert-text">
                                Mengonfigurasi izin akses modul untuk grup pengguna: <strong><?= esc($sgroupNama ?? '-') ?></strong>. Berikan centang pada modul yang diizinkan untuk diakses oleh role ini, lalu tekan tombol <strong>Simpan Perubahan Hak Akses</strong>.
                            </div>
                        </div>

                        <!--begin::Section-->
                        <div class="kt-section mb-0">
                            <div class="kt-section__content">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0 no-datatable" id="table_hakakses_modul">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center align-middle" style="width: 50px;">
                                                    <label class="kt-checkbox kt-checkbox--single kt-checkbox--brand mb-0" title="Pilih Semua / Batal Pilih">
                                                        <input type="checkbox" id="check_all_modul">
                                                        <span></span>
                                                    </label>
                                                </th>
                                                <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 25%; font-size: 11px; letter-spacing: 0.5px;">Nama / Route Modul</th>
                                                <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 35%; font-size: 11px; letter-spacing: 0.5px;">Label Tampilan Modul</th>
                                                <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 35%; font-size: 11px; letter-spacing: 0.5px;">Grup Induk Modul</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($datas)): ?>
                                                <?php foreach ($datas as $row): ?>
                                                    <?php $is_checked = !empty($row['sgroupmodulSgroupNama']); ?>
                                                    <tr>
                                                        <td class="text-center align-middle">
                                                            <label class="kt-checkbox kt-checkbox--single kt-checkbox--brand mb-0">
                                                                <input type="checkbox" class="check-modul-item" <?= $is_checked ? 'checked' : '' ?> name="cekModul[]" value="<?= esc($row['susrmodulNama']) ?>" />
                                                                <span></span>
                                                            </label>
                                                        </td>
                                                        <td class="align-middle">
                                                            <code class="text-primary font-weight-bold" style="font-size: 13px;"><?= esc($row['susrmodulNama']) ?></code>
                                                        </td>
                                                        <td class="align-middle font-weight-bold text-dark">
                                                            <?= esc($row['susrmodulNamaDisplay']) ?>
                                                        </td>
                                                        <td class="align-middle">
                                                            <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold">
                                                                <?= esc($row['susrmdgroupDisplay'] ?? '-') ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">
                                                        <i class="flaticon2-information d-block mb-2" style="font-size: 2rem; color: #a1a5b7;"></i>
                                                        Belum ada modul yang terdaftar dalam sistem.
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!--end::Section-->
                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions d-flex justify-content-between align-items-center">
                            <div class="text-muted" style="font-size: 13px;">
                                <span id="selected_count" class="font-weight-bold text-primary">0</span> dari <strong><?= !empty($datas) ? count($datas) : 0 ?></strong> modul dipilih
                            </div>
                            <button type="submit" class="btn btn-brand btn-elevate btn-icon-sm">
                                <i class="flaticon2-checkmark"></i>
                                Simpan Perubahan Hak Akses
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Portlet-->
            </div>
        </div>
    </div>
</form>
<!--End::Form-->