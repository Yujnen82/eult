<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!--Begin::Row-->
<!-- begin:: Content -->
<div id='ref_kategori'>
    <div id="index" class="response-show ">
        <div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
            <div class="row">
                <div class="col-md-12">
                    <div id="response"></div>
                    <!--begin::Portlet-->
                    <div class="kt-portlet">
                        <div class="kt-portlet__head">
                            <div class="kt-portlet__head-label">
                                <h3 class="kt-portlet__head-title">
                                    <?= strtoupper(esc($page_judul ?? 'Master Kategori Layanan')) ?>
                                </h3>
                            </div>
                            <div class="kt-portlet__head-toolbar">
                                <div class="kt-portlet__head-actions">
                                    <a id="btn-create" href="<?= $create_url ?? '#' ?>" class="btn btn-brand btn-elevate btn-icon-sm">
                                        <i class="flaticon2-plus"></i>
                                        Tambah Data
                                    </a>
                                </div>
                            </div>
                        </div>

                        <form class="kt-form" action="<?= $show_url ?? '#' ?>" method="post" id="form_show">
                            <?= csrf_field() ?>
                            <div class="kt-portlet__body">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark">Pilih Kategori Layanan</label>
                                    <select class="form-control m-select2" name="categoryNama">
                                        <option value="">-- Pilih Layanan --</option>
                                        <?php 
                                        $sUserGroup = $s_user_group ?? [];
                                        foreach($sUserGroup as $row):
                                            $selected = (!empty($datas) && is_array($datas) && isset($datas['categoryNama']) && $datas['categoryNama'] == $row['categoryNama']) ? 'selected' : '';
                                            echo '<option value="' . esc($row['categoryId']) . '" ' . $selected . '>' . esc($row['categoryNama']) . '</option>';
                                        endforeach;
                                        ?>
                                    </select>
                                    <span class="form-text text-muted">Pilih kategori layanan di atas untuk menampilkan atau mengelola sub-layanan terkait.</span>
                                </div>
                            </div>
                            <div class="kt-portlet__foot">
                                <div class="kt-form__actions">
                                    <a href="<?= $show_url ?? '#' ?>" id="btn_show" class="btn btn-brand">
                                        <i class="flaticon2-search-1"></i> Tampilkan Sub Layanan
                                    </a>
                                    <a href="<?= $edit_url ?? '#' ?>" id="btn_edit" class="btn btn-secondary">
                                        <i class="flaticon2-edit"></i> Ubah Kategori
                                    </a>
                                </div>
                            </div>
                        </form>

                    </div>

                    <!--end::Portlet-->
                </div>
            </div>
        </div>

    </div>
    <div id="create" class="response-hide "></div>
</div> 
<!--End::Row-->
