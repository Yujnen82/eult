<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <!--begin::Portlet-->
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            <?=strtoupper($page_judul)?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?=$show_url?>" method="post" id="form_show">
                    <div class="kt-portlet__body">
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control m-select2" name="hakakses">
                                <option value=""></option>
                                <?php 
                            foreach($s_user_group as $row):
                                echo '<option value="'.$row['sgroupNama'].'" >'.$row['sgroupNama'].' - '.$row['sgroupKeterangan'].'</option>';
                            endforeach;
                            ?>
                            </select>

                        </div>
                    </div>
                    <div class="kt-portlet__foot">
                        <div class="kt-form__actions">
                            <button type="submit" id="btn_save" class="btn btn-primary">Show</button>
                        </div>
                    </div>
                </form>

                <!--end::Form-->
            </div>

            <!--end::Portlet-->
        </div>
    </div>
</div>

<div id="response"></div>