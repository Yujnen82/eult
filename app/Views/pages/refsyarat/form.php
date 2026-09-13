
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
                                        <?=strtoupper($page_judul)?>
                                    </h3>
                                </div>
                            </div>

                            <!--begin::Form-->
                            <form class="kt-form" action="<?=$save_url?>" method="post" id="form_form">
                                <div class="kt-portlet__body">
                                    <input type="hidden" name="berkasIdOld" value="<?=$datas!=false?$datas['berkasId']:''?>">
                                    
                                <div class="form-group">
                                    <label>Layanan</label>
                                    <select class="form-control m-select2" name="berkasidLayanan">
                                            <option value=""></option>
                                    <?php 
                                    foreach($ref_layanan as $row):
                                        echo '<option value="'.$row['layananId'].'" ' . ($datas != false ? $row['layananId'] == $datas['berkasidLayanan'] ? 'selected' : '' : '') . '>'.$row['layananNama'].'</option>';
                                    endforeach;
                                    ?>
                                    </select>
                                    
                                </div>
                            
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" class="form-control" name="berkasNama" placeholder="berkasNama" aria-describedby="berkasNama" value="<?=$datas!=false?$datas['berkasNama']:''?>">
                                </div>
                            
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <input type="text" class="form-control" name="berkasKeterangan" placeholder="berkasKeterangan" aria-describedby="berkasKeterangan" value="<?=$datas!=false?$datas['berkasKeterangan']:''?>">
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
            