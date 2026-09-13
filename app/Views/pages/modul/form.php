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
                            <?= strtoupper(esc(($status_page ?? 'Form') . ' ' . ($page_judul ?? 'Modul'))) ?>
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions">
                            <a href="<?= site_url('modul') ?>" class="btn btn-secondary btn-elevate btn-icon-sm">
                                <i class="flaticon2-back"></i>
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_form">
                    <div class="kt-portlet__body">
                        <input type="hidden" name="susrmodulNamaOld" value="<?= $datas != false ? esc($datas['susrmodulNama']) : '' ?>">

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Nama / Route Modul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="susrmodulNama" placeholder="Contoh: modul, unit, ticketing" aria-describedby="Nama Modul" value="<?= $datas != false ? esc($datas['susrmodulNama']) : '' ?>" required>
                            <span class="form-text text-muted">Identifier rute sistem (gunakan huruf kecil tanpa spasi).</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Grup Modul <span class="text-danger">*</span></label>
                            <select class="form-control m-select2" name="susrmodulSusrmdgroupNama" required>
                                <option value="">Pilih Grup Modul</option>
                                <?php if (!empty($s_user_modul_group_ref)): ?>
                                    <?php foreach ($s_user_modul_group_ref as $row): ?>
                                        <option value="<?= esc($row['susrmdgroupNama']) ?>" <?= ($datas != false && $datas['susrmodulSusrmdgroupNama'] == $row['susrmdgroupNama']) ? 'selected' : '' ?>>
                                            <?= esc($row['susrmdgroupDisplay']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <span class="form-text text-muted">Kelompok menu induk di mana modul ini akan ditampilkan pada sidebar.</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Label Tampilan Modul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="susrmodulNamaDisplay" placeholder="Contoh: Manajemen Modul, Referensi Unit" aria-describedby="Display Modul" value="<?= $datas != false ? esc($datas['susrmodulNamaDisplay']) : '' ?>" required>
                            <span class="form-text text-muted">Teks judul yang akan tertera pada menu navigasi antarmuka.</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Status Akses Login <span class="text-danger">*</span></label>
                            <div class="kt-radio-inline">
                                <label class="kt-radio kt-radio--bold kt-radio--brand mr-4">
                                    <input type="radio" name="susrmodulIsLogin" value="1" <?= ($datas == false || (int)$datas['susrmodulIsLogin'] === 1) ? 'checked="checked"' : '' ?>>
                                    Wajib Login (Akses Khusus Petugas / Admin)
                                    <span></span>
                                </label>
                                <label class="kt-radio kt-radio--bold kt-radio--success">
                                    <input type="radio" name="susrmodulIsLogin" value="0" <?= ($datas != false && (int)$datas['susrmodulIsLogin'] === 0) ? 'checked="checked"' : '' ?>>
                                    Terbuka untuk Publik
                                    <span></span>
                                </label>
                            </div>
                            <span class="form-text text-muted">Tentukan apakah modul ini membutuhkan sesi login aktif untuk dapat diakses.</span>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark">Urutan Menu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="susrmodulUrut" min="1" max="99" placeholder="Contoh: 1" aria-describedby="Urut Modul" value="<?= $datas != false ? esc($datas['susrmodulUrut']) : '1' ?>" required>
                            <span class="form-text text-muted">Nomor urut penempatan menu di dalam grup navigasi (1, 2, 3..).</span>
                        </div>

                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-brand btn-elevate">
                                <i class="flaticon2-checkmark"></i> Simpan Data
                            </button>
                            <a href="<?= site_url('modul') ?>" class="btn btn-secondary ml-2">Batal</a>
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