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
                            <?=strtoupper($page_judul)?>
                        </h3>
                    </div>
                </div>

                <!--begin::Form-->
                <form class="kt-form" action="<?= esc($save_url) ?>" method="post" id="form_form">
                    <div class="kt-portlet__body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="categoryIdOld" value="<?= esc($datas != false ? $datas['sCatId'] : '', 'attr') ?>">
                        <input type="hidden" name="sCatCategoryId" value="<?= esc($datas != false ? $datas['sCatCategoryId'] : ($kunci ?? ''), 'attr') ?>">
                        <div class="form-group">
                            <label>Sub Layanan</label>
                            <input type="text" class="form-control" name="sCategoryNama" placeholder="Sub Layanan" aria-describedby="categoryNama" value="<?=$datas!=false?$datas['sCatNama']:''?>">
                        </div>
                        <div class="form-group">
                            <label>Jenis Disposisi</label>
                            <select class="form-control m-select2" name="sCategoryDisposisi">
                                <option value=""></option>
                                <option value="TOPBOTTOM"<?=$datas!=false?($datas['sCatDisposisi'] == 'TOPBOTTOM'?'selected':''):''?>>WR->KABAG->KASUBBAG</option>
                                <option value="BOTTOMTOP"<?=$datas!=false?($datas['sCatDisposisi'] == 'BOTTOMTOP'?'selected':''):''?>>KASUBBAG->KABAG->WR</option>
                            </select>

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
