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
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_form">
                    <div class="kt-portlet__body">
                        <input type="hidden" name="susrmdgroupNamaOld" value="<?= $datas != false ? $datas['susrmdgroupNama'] : '' ?>">

                        <div class="form-group">
                            <label>Group Nama</label>
                            <input type="text" class="form-control" name="susrmdgroupNama" placeholder="Group Nama" aria-describedby="Group Nama" value="<?= $datas != false ? $datas['susrmdgroupNama'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Group Display</label>
                            <input type="text" class="form-control" name="susrmdgroupDisplay" placeholder="Group Display" aria-describedby="Group Display" value="<?= $datas != false ? $datas['susrmdgroupDisplay'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Group Icon</label>
                            <textarea class="form-control" name="susrmdgroupIcon"><?= $datas != false ? $datas['susrmdgroupIcon'] : '' ?></textarea>
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