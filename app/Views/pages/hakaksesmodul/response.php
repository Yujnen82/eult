<!--begin::Form-->
<form action="<?= esc($save_url ?? '#') ?>" method="post" id="form_custom">
    <input type="hidden" name="sgroupNama" value="<?= esc(!empty($sgroupNama) ? $sgroupNama : '') ?>">
    <div class="kt-portlet kt-portlet--mobile mb-0">
        <div class="kt-portlet__head flex-wrap py-3" style="min-height: 60px;">
            <div class="kt-portlet__head-label py-1">
                <span class="kt-portlet__head-icon">
                    <i class="flaticon-lock text-primary"></i>
                </span>
                <h3 class="kt-portlet__head-title">
                    Matriks Hak Akses Modul: <span class="text-primary font-weight-bold ml-1"><?= esc($sgroupNama ?? '') ?></span>
                    <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold ml-2 py-2 px-3" id="badge_counter_wrapper" title="Jumlah modul dipilih">
                        <span id="selected_count_badge">0</span> / <?= !empty($datas) ? count($datas) : 0 ?> Modul
                    </span>
                </h3>
            </div>
            <div class="kt-portlet__head-toolbar py-1">
                <div class="kt-portlet__head-actions d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-brand btn-elevate mr-2 my-1" id="btn_toggle_all">
                        <i class="flaticon2-check-mark"></i> Pilih Semua
                    </button>
                    <button type="submit" id="btn_save" class="btn btn-sm btn-brand btn-elevate my-1">
                        <i class="flaticon2-checkmark"></i>
                        <span class="d-none d-sm-inline">Simpan Perubahan </span>Hak Akses
                    </button>
                </div>
            </div>
        </div>
        <div class="kt-portlet__body py-3">
            <div class="row align-items-center justify-content-between mb-3">
                <div class="col-md-7 col-lg-8">
                    <p class="text-muted mb-0 font-weight-normal" style="font-size: 13px;">
                        <i class="flaticon-information text-primary mr-1"></i> Centang modul yang diizinkan untuk diakses oleh role <strong><?= esc($sgroupNama ?? '-') ?></strong>, lalu simpan perubahan.
                    </p>
                </div>
                <div class="col-md-5 col-lg-4 mt-2 mt-md-0 text-md-right">
                    <div class="input-icon input-icon--right ml-auto" style="max-width: 280px;">
                        <input type="text" id="quick_search_modul" class="form-control form-control-sm" placeholder="Cari nama atau grup modul...">
                        <span class="input-icon__icon input-icon__icon--right">
                            <span><i class="flaticon2-search-1 text-muted" style="font-size: 11px;"></i></span>
                        </span>
                    </div>
                </div>
            </div>

            <!--begin::Table Section-->
            <div class="table-responsive border rounded" style="max-height: 65vh; overflow-y: auto; scroll-behavior: smooth;">
                <table class="table table-hover table-striped mb-0 no-datatable" id="table_hakakses_modul" style="min-width: 620px;">
                    <thead class="thead-light">
                        <tr style="position: sticky; top: 0; background-color: #f7f8fa; z-index: 10; box-shadow: inset 0 -1px 0 #ebedf2;">
                            <th class="text-center align-middle" style="width: 50px; min-width: 50px; background-color: #f7f8fa;">
                                <label class="kt-checkbox kt-checkbox--single kt-checkbox--brand mb-0" title="Pilih Semua / Batal Pilih">
                                    <input type="checkbox" id="check_all_modul">
                                    <span></span>
                                </label>
                            </th>
                            <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 25%; min-width: 150px; font-size: 11px; letter-spacing: 0.5px; background-color: #f7f8fa;">Nama / Route Modul</th>
                            <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 40%; min-width: 180px; font-size: 11px; letter-spacing: 0.5px; background-color: #f7f8fa;">Label Tampilan Modul</th>
                            <th class="text-uppercase text-muted font-weight-bold align-middle" style="width: 30%; min-width: 140px; font-size: 11px; letter-spacing: 0.5px; background-color: #f7f8fa;">Grup Induk Modul</th>
                        </tr>
                    </thead>
                    <tbody id="matrix_tbody">
                        <?php if (!empty($datas)): ?>
                            <?php foreach ($datas as $row): ?>
                                <?php $is_checked = !empty($row['sgroupmodulSgroupNama']); ?>
                                <tr class="matrix-row <?= $is_checked ? 'table-row-selected' : '' ?>" style="cursor: pointer;">
                                    <td class="text-center align-middle py-3">
                                        <label class="kt-checkbox kt-checkbox--single kt-checkbox--brand mb-0">
                                            <input type="checkbox" class="check-modul-item" <?= $is_checked ? 'checked' : '' ?> name="cekModul[]" value="<?= esc($row['susrmodulNama']) ?>" />
                                            <span></span>
                                        </label>
                                    </td>
                                    <td class="align-middle py-3">
                                        <code class="text-primary font-weight-bold" style="font-size: 13px; font-family: monospace; background: rgba(93, 120, 255, 0.08); padding: 3px 6px; border-radius: 4px; white-space: nowrap;"><?= esc($row['susrmodulNama']) ?></code>
                                    </td>
                                    <td class="align-middle py-3 font-weight-bold text-dark">
                                        <?= esc($row['susrmodulNamaDisplay']) ?>
                                    </td>
                                    <td class="align-middle py-3">
                                        <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold">
                                            <?= esc($row['susrmdgroupDisplay'] ?? '-') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="flaticon2-information d-block mb-2" style="font-size: 2rem; color: #a1a5b7;"></i>
                                    Belum ada modul yang terdaftar dalam sistem.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr id="empty_search_row" style="display: none;">
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="flaticon2-search d-block mb-1" style="font-size: 1.5rem; color: #a1a5b7;"></i>
                                Tidak ditemukan modul yang sesuai dengan kata kunci pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!--end::Table Section-->
        </div>
        <div class="kt-portlet__foot py-3">
            <div class="kt-form__actions d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <div class="text-muted mb-2 mb-sm-0" style="font-size: 13px;">
                    Total <span id="selected_count" class="font-weight-bold text-primary">0</span> dari <strong><?= !empty($datas) ? count($datas) : 0 ?></strong> modul dipilih
                </div>
                <button type="submit" class="btn btn-brand btn-elevate btn-icon-sm">
                    <i class="flaticon2-checkmark"></i>
                    Simpan Perubahan Hak Akses
                </button>
            </div>
        </div>
    </div>
</form>
<!--End::Form-->