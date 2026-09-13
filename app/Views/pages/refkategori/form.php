<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <!--begin::Portlet-->
            <div class="kt-portlet" id="form_crud">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <?=strtoupper($page_judul)?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?=$save_url?>" method="post" id="form_form">
                    <div class="kt-portlet__body">
                        <input type="hidden" name="categoryIdOld" value="<?=$datas!=false?$datas['categoryId']:''?>">
                        <div class="form-group">
                            <label>Layanan</label>
                            <input type="text" class="form-control" name="categoryNama" placeholder="Layanan" aria-describedby="categoryNama" value="<?=$datas!=false?$datas['categoryNama']:''?>">
                        </div>

                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-primary">Save</button>
                            <?php if ($datas!=false): ?>
                                <a href="<?=$delete_url.$datas['categoryId']?>" id="<?='ts_remove_row'?>" class="ts_remove_row btn btn-danger">Remove</a>
                            <?php endif ?>
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
