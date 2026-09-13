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
                            <?= strtoupper($page_judul) ?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_custom">
                    <div class="kt-portlet__body">
                        <input type="hidden" name="susrNamaOld" value="<?= $datas != false ? $datas['susrNama'] : '' ?>">

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="susrNama" placeholder="Username" aria-describedby="Username" value="<?= $datas != false ? $datas['susrNama'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Hak Akses</label>
                            <select class="form-control m-select2" name="susrSgroupNama">
                                <option value=""></option>
                                <?php
                                foreach ($s_user_group as $row) :
                                    echo '<option value="' . $row['sgroupNama'] . '" ' . ($datas != false ? $datas['susrSgroupNama'] == $row['sgroupNama'] ? 'selected' : '' : '') . '>' . $row['sgroupNama'] .' ( '.$row['sgroupKeterangan'].')'. '</option>';
                                endforeach;
                                ?>
                            </select>

                        </div>
                         
                        <div class="form-group">
                            <label>Profile Name</label>
                            <input type="text" class="form-control" name="susrProfil" placeholder="Profile Name" aria-describedby="Profile Name" value="<?= $datas != false ? $datas['susrProfil'] : '' ?>">
                        </div>

                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-primary">Save</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
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