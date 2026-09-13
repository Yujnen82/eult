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
                        <input type="hidden" name="sgroupNamaOld" value="<?= $datas != false ? $datas['sgroupNama'] : '' ?>">

                        <div class="form-group">
                            <label>Hak Akses</label>
                            <input type="text" class="form-control" name="sgroupNama" placeholder="Hak Akses" aria-describedby="Hak Akses" value="<?= $datas != false ? $datas['sgroupNama'] : '' ?>">
                        </div>
                        <div class="form-group">
                            <label>Hak Layanan</label>
                            
                            <select class="form-control m-select2" name="sgroupCategoryId">
                                <option value=""></option>
                                <option value="99">Semua Layanan</option>
                                <?php
                                foreach ($r_category as $row) :
                                    echo '<option value="' . $row['unitId'] . '" ' . ($datas != false ? $datas['sgroupCategoryId'] == $row['unitId'] ? 'selected' : '' : '') . '>' . $row['unitNama'] . '</option>';
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <input type="text" class="form-control" name="sgroupKeterangan" placeholder="Keterangan" aria-describedby="Keterangan" value="<?= $datas != false ? $datas['sgroupKeterangan'] : '' ?>">
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