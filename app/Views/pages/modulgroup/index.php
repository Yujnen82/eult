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
                            <?= strtoupper(esc($page_judul ?? 'Modul Group')) ?>
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
                                            <th class="text-uppercase text-muted font-weight-bold" style="width: 25%; font-size: 11px; letter-spacing: 0.5px;">Nama Grup</th>
                                            <th class="text-uppercase text-muted font-weight-bold" style="width: 25%; font-size: 11px; letter-spacing: 0.5px;">Nama Tampilan</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 25%; font-size: 11px; letter-spacing: 0.5px;">Ikon Grup</th>
                                            <th class="text-uppercase text-muted font-weight-bold text-center" style="width: 20%; font-size: 11px; letter-spacing: 0.5px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($datas)): ?>
                                            <?php
                                            $i = 1;
                                            foreach ($datas as $row):
                                                $key = service('enkripsi')->encode($row['susrmdgroupNama']);
                                            ?>
                                                <tr>
                                                    <td class="text-center font-weight-bold align-middle"><?= $i++ ?></td>
                                                    <td class="align-middle">
                                                        <span class="font-weight-bold text-primary" style="font-family: monospace; font-size: 13px;"><?= esc($row['susrmdgroupNama']) ?></span>
                                                    </td>
                                                    <td class="font-weight-bold text-dark align-middle"><?= esc($row['susrmdgroupDisplay']) ?></td>
                                                    <td class="text-center align-middle">
                                                        <div class="d-inline-flex align-items-center justify-content-center">
                                                            <span class="kt-badge kt-badge--unified-brand kt-badge--lg kt-badge--rounded mr-2" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                                                <?= $row['susrmdgroupIcon'] ?>
                                                            </span>
                                                            <code class="text-muted small py-1 px-2 bg-light border rounded" style="font-family: monospace; font-size: 11px;"><?= esc($row['susrmdgroupIcon']) ?></code>
                                                        </div>
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
                                                <td colspan="5" class="text-center py-4 text-muted">
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