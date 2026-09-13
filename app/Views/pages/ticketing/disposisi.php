<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">
        <div class="col-md-12">
            <div id="response"></div>
            <?php if ($is_home) : ?>
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
                    <form class="kt-form" action="<?= $save_url ?>" method="post" id="form_assign" enctype='multipart/form-data'>
                        <div class="kt-portlet__body">
                            <input type="hidden" name="ticketIdOld" value="<?= $datas != false ? $datas['ticketTrackingId'] : '' ?>">
                            <div class="form-group">
                                <label>Disposisi Tiket</label>
                                <select class="form-control m-select2" name="ticketAssign" id="ticketAssign">
                                    <option value=""></option>
                                    <?php
                                    $head_now = '';
                                    $i = 0;
                                    foreach ($unit as $row) :
                                        if ($head_now != $row['parentNama']) {
                                            $head_now = $row['parentNama'];
                                            if ($i > 0)
                                                echo '</optgroup>';

                                            echo '<optgroup label="' . $head_now . '">';
                                        }
                                        echo '<option value="' . $row['unitId'] . '" ' . '>' . $row['unitKode'] . ' - ' . $row['unitNama'] . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Prioritas</label>
                                <select class="form-control m-select2" name="ticketPriority">
                                    <option value=""></option>
                                    <?php
                                    foreach ($r_priority as $row) :
                                        echo '<option value="' . $row['priorityId'] . '" ' . ($row['priorityId'] == $datas['ticketPriority'] ? 'selected' : '') . '>' . $row['priorityName'] . '</option>';
                                    endforeach;
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Pesan <strong style="color:red">*</strong></label>
                                <textarea class="form-control" name="ticketMessage" placeholder="Pesan" aria-describedby="Pesan" rows="4" style="resize: none"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Dokumen</label>
                                <input type="file" class="form-control" name="ticketArchiveId" placeholder="Tiket Dokumen" aria-describedby="Tiket Dokumen">
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
            <?php endif; ?>
            <?php
            echo view('pages/ticketing/disposisi_history');
            ?>
        </div>
    </div>
</div>
<!--End::Row-->