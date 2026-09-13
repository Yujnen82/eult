
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
                                    <?=strtoupper($page_judul)?>
                                </h3>
                            </div>
                            <div class="kt-portlet__head-toolbar">
                                <div class="kt-portlet__head-actions">
                                    <a id="btn-create" href="<?=$create_url?>" class="btn btn-outline-primary">
                                        <span>
                                            <i class="flaticon2-plus"></i>
                                            <span>Create</span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <form class="kt-form" action="<?=$show_url?>" method="post" id="form_show">
                            <div class="kt-portlet__body">
                                <div class="form-group">
                                    <label>Layanan</label>
                                    <select class="form-control m-select2" name="categoryNama">
                                        <option value=""></option>
                                        <?php 
                                        foreach($s_user_group as $row):
                                            echo '<option value="'.$row['categoryId'].'" ' . ($datas != false ? $datas['categoryNama'] == $row['categoryNama'] ? 'selected' : '' : '') . '>'.$row['categoryNama'].'</option>';
                                        endforeach;
                                        ?>
                                    </select>

                                </div>
                            </div>
                            <div class="kt-portlet__foot">
                                <div class="kt-form__actions">
                                    <a href="<?=$show_url?>" id="btn_show" class="btn btn-primary">Show</a>
                                    <a href="<?=$edit_url?>" id="btn_edit" class="btn btn-secondary">Update</a>
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
