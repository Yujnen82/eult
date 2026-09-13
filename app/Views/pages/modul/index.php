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
                            <?= strtoupper(esc($page_judul ?? 'Modul Sistem')) ?>
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-actions">
                            <a href="<?= $create_url ?? '#' ?>" class="btn btn-brand btn-elevate btn-icon-sm" id="btn-create">
                                <i class="flaticon2-plus"></i>
                                Tambah Data
                            </a>
                        </div>
                    </div>
                </div>

                <div class="kt-portlet__body">
                    <!--begin::Section-->
                    <div class="kt-section mb-0">
                        <div class="kt-section__content">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 5%; font-size: 11px; letter-spacing: 0.5px;">No</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="width: 20%; font-size: 11px; letter-spacing: 0.5px;">Nama Modul</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="width: 20%; font-size: 11px; letter-spacing: 0.5px;">Grup Modul</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="width: 20%; font-size: 11px; letter-spacing: 0.5px;">Label Tampilan</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 12%; font-size: 11px; letter-spacing: 0.5px;">Akses Login</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 8%; font-size: 11px; letter-spacing: 0.5px;">Urut</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 15%; font-size: 11px; letter-spacing: 0.5px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($datas)): ?>
                                            <?php
                                            $i = 1;
                                            foreach ($datas as $row):
                                                $key = service('enkripsi')->encode($row['susrmodulNama']);
                                            ?>
                                                <tr>
                                                    <td class="text-center font-weight-bold align-middle"><?= $i++ ?></td>
                                                    <td class="align-middle">
                                                        <span class="font-weight-bold text-primary" style="font-family: monospace; font-size: 13px;"><?= esc($row['susrmodulNama']) ?></span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold">
                                                            <?= esc($row['susrmdgroupDisplay']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="font-weight-bold text-dark align-middle"><?= esc($row['susrmodulNamaDisplay']) ?></td>
                                                    <td class="text-center align-middle">
                                                        <?php if ((int)$row['susrmodulIsLogin'] === 1): ?>
                                                            <span class="kt-badge kt-badge--unified-success kt-badge--inline kt-badge--pill font-weight-bold">
                                                                <i class="flaticon2-check-mark mr-1"></i> Wajib Login
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="kt-badge kt-badge--unified-info kt-badge--inline kt-badge--pill font-weight-bold">
                                                                Publik
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge badge-secondary font-weight-bold" style="font-size: 12px;"><?= esc($row['susrmodulUrut']) ?></span>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        <a href="<?= ($update_url ?? '#') . $key ?>" title="Ubah Data" class="btn btn-sm btn-label-brand btn-bold">
                                                            <i class="flaticon2-edit"></i> Ubah
                                                        </a>
                                                        <a href="<?= ($delete_url ?? '#') . $key ?>" title="Hapus Data" id="ts_remove_row<?= $i; ?>" class="ts_remove_row btn btn-sm btn-label-danger btn-bold ml-1">
                                                            <i class="flaticon2-trash"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    <i class="flaticon2-information d-block mb-2" style="font-size: 2rem; color: #a1a5b7;"></i>
                                                    Belum ada data yang tersedia.
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
            </div>
            <!--end::Portlet-->
        </div>
    </div>
</div>
<!--End::Row-->