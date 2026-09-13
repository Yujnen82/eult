<?= $this->include('layouts/subheader') ?>
<div id='main_form'>
    <div id="first-form">
        <div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="kt-portlet">
                        <div class="kt-portlet__head">
                            <div class="kt-portlet__head-label">
                                <h3 class="kt-portlet__head-title"> <?= strtoupper($page_judul) ?></h3>
                            </div>
                            <div class="kt-portlet__head-toolbar">
                                <div class="kt-portlet__head-actions">
                                    <?php if ($user_group['susrSgroupNama'] == 'ADMIN' or strpos($user_group['susrSgroupNama'], 'OPERATOR') !== FALSE) { ?>
                                        <a href="<?= $create_url ?>" class="btn btn-outline-primary" id="btn-create">
                                            <span>
                                                <i class="flaticon2-plus"></i>
                                                <span>Create Ticket</span>
                                            </span>
                                        </a>
                                    <?php } ?>

                                </div>
                            </div>
                        </div>
                        <form class="kt-form" action="<?= $show_url ?>" method="post" id="form_show">
                            <div class="kt-portlet__body">
                                <div class="form-group">
                                    <label class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">Pilih Tanggal</label>
                                    <div class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">
                                        <div class="input-group" id="kt_daterangepicker_2">
                                            <input type="text" class="form-control" name="rentangTanggal" readonly="1" placeholder="Pilih rentang tanggal" value="<?= $tanggal ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="la la-calendar-check-o"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($user_group['susrSgroupNama'] == 'ADMIN' or (strpos($user_group['susrSgroupNama'], 'OPERATOR') !== FALSE)) : ?>
                                    <div class="form-group">
                                        <label class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">Layanan</label>
                                        <div class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">
                                            <select class="form-control m-select2" name="layanan">
                                                <option value=""></option>
                                                <?php
                                                foreach ($category as $row) :
                                                    echo '<option value="' . $row['unitId'] . '" >' . $row['unitNama'] . '</option>';
                                                endforeach;
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <div class="form-group">
                                        <label class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">Status Layanan</label>
                                        <div class="col-lg-10 col-md-10 col-sm-12 offset-md-1 offset-lg-1">
                                            <select class="form-control m-select2" name="status_layanan">
                                                <option value=""></option>
                                                <?php
                                                foreach ($status_layanan as $row) :
                                                    echo '<option value="' . $row['statusId'] . '" >' . $row['statusNama'] . '</option>';
                                                endforeach;
                                                ?>
                                            </select>
                                            <span class="form-text text-muted">*dipilih boleh, lewatin juga boleh</span>
                                        </div>
                                    </div> -->
                                <?php endif ?>
                               

                            </div>
                            <div class="kt-portlet__foot">
                                <div class="kt-form__actions">
                                    <button type="submit" id="btn_show" class="btn btn-primary">Show</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div id="response" class=""></div>
    </div>
    <div id="second-form" class="response-hide"></div>
</div>