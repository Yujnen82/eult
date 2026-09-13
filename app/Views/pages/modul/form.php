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
                        <input type="hidden" name="susrmodulNamaOld" value="<?= $datas != false ? $datas['susrmodulNama'] : '' ?>">

                        <div class="form-group">
                            <label>Modul</label>
                            <input type="text" class="form-control" name="susrmodulNama" placeholder="Modul" aria-describedby="Modul" value="<?= $datas != false ? $datas['susrmodulNama'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Group</label>
                            <select class="form-control m-select2" name="susrmodulSusrmdgroupNama">
                                <option value=""></option>
                                <?php
                                foreach ($s_user_modul_group_ref as $row) :
                                    echo '<option value="' . $row['susrmdgroupNama'] . '" ' . ($datas != false ? $datas['susrmodulSusrmdgroupNama'] == $row['susrmdgroupNama'] ? 'selected' : '' : '') . '>' . $row['susrmdgroupDisplay'] . '</option>';
                                endforeach;
                                ?>
                            </select>

                        </div>

                        <div class="form-group">
                            <label>Display</label>
                            <input type="text" class="form-control" name="susrmodulNamaDisplay" placeholder="Display" aria-describedby="Display" value="<?= $datas != false ? $datas['susrmodulNamaDisplay'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label>Is Login?</label>
                            <div class="kt-radio-inline">
                                <label class="kt-radio">
                                    <input type="radio" name="susrmodulIsLogin" value="0" <?= $datas != false ? $datas['susrmodulIsLogin'] == 0 ? 'checked="checked"' : '' : '' ?>> TIDAK
                                    <span></span>
                                </label>
                                <label class="kt-radio">
                                    <input type="radio" name="susrmodulIsLogin" value="1" <?= $datas != false ? $datas['susrmodulIsLogin'] == 1 ? 'checked="checked"' : '' : '' ?>> YA
                                    <span></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Urut</label>
                            <input type="number" class="form-control" name="susrmodulUrut" placeholder="Urut" aria-describedby="Urut" value="<?= $datas != false ? $datas['susrmodulUrut'] : '' ?>">
                            <span class="form-text text-muted">Urutan menu dalam 1 grup, 1 - 10..</span>
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