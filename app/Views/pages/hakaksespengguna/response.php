<!--Begin::Row-->
<!-- begin:: Content -->

<!--begin::Form-->
<form action="<?=$save_url?>" method="post" id="form_custom">
<input type="hidden" name="susrNama" value="<?=!empty($pengguna)?$pengguna:''?>">
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <button type="submit" id="btn_save" class="btn btn-outline-primary">
                                <span>
                                    <i class="fa fa-save"></i>
                                    <span>Save</span>
                                </span>
                            </button>
                        </h3>
                    </div>
                </div>
                <div class="kt-portlet__body">

                    <!--begin::Section-->
                    <div class="kt-section">
                        <div class="kt-section__content">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Hak Akses</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($datas != false) {
                                            $i = 1;
                                            foreach ($datas as $row) {
                                                if (!empty($row['sgroupSgroupNama']))
                                                    $cek = "checked";
                                                else
                                                    $cek="";
                                                ?>
                                                <tr>
                                                    <th scope="row"><input type="checkbox" class="checked" <?=$cek?> name="cekModul[]" value="<?=$row['sgroupNama']?>"/></th>
                                                    <td><?= $row['sgroupNama'] ?></td>
                                                    <td><?= $row['sgroupKeterangan'] ?></td>
                                                </tr>
                                        <?php
                                            }
                                        }
                                        ?>
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
</form>