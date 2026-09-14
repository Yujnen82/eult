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
                <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_reject">
                    <?= csrf_field() ?>
                    <div class="kt-portlet__body">
                        <div class="form-group">
                            <label>Pesan <strong style="color:red">*</strong></label>
                            <textarea class="form-control" name="ticketMessage" placeholder="Pesan" aria-describedby="Pesan" rows="4" style="resize: none"></textarea>
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