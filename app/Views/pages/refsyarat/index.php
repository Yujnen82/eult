<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

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
                            <?= strtoupper(esc($page_judul ?? 'Master Persyaratan Layanan')) ?>
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
                    <div class="kt-section">
                        <div class="kt-section__content">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="ref_table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px; width: 25%;">Layanan</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px; width: 25%;">Nama Persyaratan</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Keterangan</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="font-size: 11px; letter-spacing: 0.5px; width: 18%;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($datas)): ?>
                                            <?php
                                            $i = 1;
                                            foreach ($datas as $row):
                                                $key = service('enkripsi')->encode($row['berkasId']);
                                            ?>
                                                <tr>
                                                    <td class="font-weight-bold text-dark"><?= esc($row['layananNama']) ?></td>
                                                    <td><?= esc($row['berkasNama']) ?></td>
                                                    <td class="text-muted"><?= esc($row['berkasKeterangan']) ?></td>
                                                    <td class="text-center">
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
                                                <td colspan="4" class="text-center py-4 text-muted">
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
