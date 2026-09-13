<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->
<!--Begin::Row-->
<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
    <div class="row">

        <div class="col-lg-6">
            <div class="kt-portlet" data-ktportlet="true" id="kt_portlet_tools_1">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            Detail Tiket
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-group">

                            <a href="#" data-ktportlet-tool="toggle" class="btn btn-sm btn-icon btn-clean btn-icon-md"><i class="la la-angle-down"></i></a>
                            <a href="#" data-ktportlet-tool="reload" class="btn btn-sm btn-icon btn-clean btn-icon-md"><i class="la la-refresh"></i></a>
                            <a href="#" data-ktportlet-tool="remove" class="btn btn-sm btn-icon btn-clean btn-icon-md"><i class="la la-close"></i></a>
                        </div>
                    </div>
                </div>
                <div class="kt-portlet__body">
                    <div class="kt-portlet__content">
                        <p><label style="font-weight: bold">Nomor Tiket :</label>
                            <label id="ticketId"><?= $datas['ticketTrackingId'] ?></label>
                        </p>
                        <p><label style="font-weight: bold">Nama :</label>
                            <?= $datas['ticketName'] . ' - ' . $datas['ticketIdentitas'] ?></p>
                        <p><label style="font-weight: bold">No. HP :</label>
                            <?= $datas['ticketNoHp'] ?></p>
                        <p><label style="font-weight: bold">Email :</label>
                            <?=  $datas['ticketEmail'] ?></p>
                        <p><label style="font-weight: bold">Data Mahasiswa :</label>
                            <?=$mhs != false ? $mhs : ''  ?></p>
                        <p><label style="font-weight: bold">Layanan :</label>
                            <?= $datas['sCatNama'] . ' - ' . $datas['categoryNama'] ?></p>
                        <p><label style="font-weight: bold">Subjek/Judul :</label>
                            <?= $datas['ticketSubject'] ?></p>
                        <p><label style="font-weight: bold">Pesan :</label>
                            <?= $datas['ticketMessage'] ?></p>
                        <p><label style="font-weight: bold">Tanggal Tiket :</label>
                            <?= datetoindo($datas['ticketCreated']) . ' ' . date('h:i A', strtotime($datas['ticketCreated'])) ?></p>
                        <?php if (!empty($worker)) : ?>
                            <label style="font-weight: bold">Anggota :</label>
                            <ol>
                                <?php foreach ($worker as $w) { ?>
                                    <li><?= $w->susrProfil ?></li>
                                <?php } ?>
                            </ol>
                        <?php endif; ?>
                        <label style="font-weight: bold">Status Tiket :</label><br>
                        <p class="kt-badge kt-badge--<?= $datas['statusColor'] ?> kt-badge--inline kt-badge--pill kt-badge--rounded"><?= $datas['statusNama'] ?></p><br><br>
                        <?php if ($datas['ticketStatus'] == 5) : ?>
                            <label style="font-weight: bold"> Rating :</label>
                            <p><input id="input-id" type="text" class="kv-uni-star rating-loading" value="<?= $datas['ratingNilai'] ?>" <?= !empty($datas['ratingTicketId']) ? 'disabled' : '' ?>></p>
                        <?php endif ?>

                    </div>
                </div>
                <div class="kt-portlet__foot">
                    <a href="<?= $cetakterima ?>" class="btn btn-instagram" target='_blank'><i class="la la-print"></i> Print Bukti</a>
                    <a href="https://docs.google.com/forms/d/e/1FAIpQLSe4Q8KLdpwCwx7CI3-IfS_YgrtNpc-tHeH14Ss75CkXyTaqRQ/viewform" class="btn btn-primary" target='_blank'><i class="la la-pencil"></i> Link Survey</a>
                    <?php if ($archive_url != false) : ?>
                        <a href="<?= $archive_url ?>" class="btn btn-success"><i class="la la-file"></i> Lampiran</a>
                    <?php endif; ?>
                    <?php if ($output_url != false) : ?>
                        <a href="<?= $output_url ?>" class="btn btn-warning"><i class="la la-file"></i> Output</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kt-grid__item kt-grid__item--fluid kt-app__content" id="kt_chat_content">
                <div class="kt-chat">
                    <div class="kt-portlet kt-portlet--head-lg kt-portlet--last">
                        <div class="kt-portlet__head">
                            <div class="kt-chat__head ">
                                <div class="kt-chat__center">
                                    <div class="kt-chat__label">
                                        <a href="#" class="kt-chat__title"><?= $user_group['susrProfil'] ?></a>
                                        <span class="kt-chat__status">
                                            <span class="kt-badge kt-badge--dot kt-badge--success"></span> Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kt-portlet__body">
                            <div class="kt-scroll kt-scroll--pull" data-mobile-height="400" data-scroll="true" data-height="250" data-scrollbar-shown="true">
                                <div class="kt-chat__messages">
                                    <?php if (!empty($replies)) :
                                        foreach ($replies as $value) { ?>
                                            <div class="kt-chat__message <?= $value->repliesStatus == $user_group['susrSgroupNama'] ? 'kt-chat__message--right' : '' ?>">
                                                <div class="kt-chat__user">
                                                    <?= $value->repliesStatus == $user_group['susrSgroupNama'] ? '<span class="kt-media kt-media--circle kt-media--sm">
                                                <img src="/assets/media/users/admin.jpg" alt="image">
                                                </span>' : '<span class="kt-chat__datetime">' . findTimeAgo($value->repliesDate) . '</span>' ?>
                                                    <a href="#" class="kt-chat__username"><?= $value->repliesStatus == $user_group['susrSgroupNama'] ? 'You' : $value->repliesBy ?></span></a>
                                                    <?= $value->repliesStatus != $user_group['susrSgroupNama'] ? '<span class="kt-media kt-media--circle kt-media--sm">
                                                <img src="/assets/media/users/user.jpg" alt="image">
                                                </span>' : '<span class="kt-chat__datetime">' . findTimeAgo($value->repliesDate) . '</span>' ?>
                                                </div>
                                                <div class="kt-chat__text kt-bg-light-<?= $value->repliesStatus == $user_group['susrSgroupNama'] ? 'primary' : 'success' ?>">
                                                    <?= $value->repliesMessage . (!empty($value->repliesFile) ? '<br/> <span><i class="flaticon-attachment icon-lg"></i> <a href="' . $load_attach . '/' . $value->repliesFile . '">' . $value->repliesFile . '</a></span>' : '') ?>
                                                </div>
                                            </div>
                                    <?php }
                                    endif ?>
                                </div>
                            </div>
                        </div>
                        <div class="kt-portlet__foot">
                            <div class="kt-chat__input">
                                <form action="<?= $save_url ?>" method="POST" enctype="multipart/form-data" id='form_chat'>
                                    <input type="hidden" name="repliesTicketId" value="<?= $datas['ticketTrackingId'] ?>">
                                    <div class="kt-chat__editor">
                                        <textarea style="height: 50px;resize: none" placeholder="Type here..." name="repliesMessage" required></textarea>
                                        <div class="custom-file">
                                            <input type="file" name="chatFile" class="custom-file-input" id="customFile">
                                            <label class="custom-file-label" for="customFile">Choose file</label>
                                        </div>
                                    </div>
                                    <div class="kt-chat__toolbar">
                                        <div class="kt_chat__actions">
                                            <button type="submit" class="btn btn-brand btn-md btn-upper btn-sm btn-bold kt-chat__reply">Reply</button>
                                            <?php if ($user_group['susrSgroupNama'] != 'USER') ?>
                                            <a href="<?= $close_url ?>" id="ts_close_btn" class="btn btn-secondary btn-sm btn-md btn-upper kt-chat__reply">Close Ticket</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="kt-portlet" data-ktportlet="true" id="kt_portlet_tools_1">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <h3 class="kt-portlet__head-title">
                            Riwayat Tiket
                        </h3>
                    </div>
                    <div class="kt-portlet__head-toolbar">
                        <div class="kt-portlet__head-group">
                            <a href="#" data-ktportlet-tool="toggle" class="btn btn-sm btn-icon btn-clean btn-icon-md"><i class="la la-angle-down"></i></a>
                        </div>
                    </div>
                </div>
                <div class="kt-portlet__body">
                    <div class="kt-notes">
                        <div class="kt-notes__items">
                            <?php if (!empty($history)) :
                                foreach ($history as $value) { ?>
                                    <div class="kt-notes__item kt-notes__item--clean">
                                        <div class="kt-notes__media">
                                            <span class="kt-notes__circle"></span>
                                        </div>
                                        <div class="kt-notes__content">
                                            <div class="kt-notes__section">
                                                <div class="kt-notes__info">
                                                    <!-- <a href="#" class="kt-notes__title">
                                                </a> -->
                                                    <span class="kt-notes__desc">
                                                        <b><?= datetoindo($value->tglHistory) . ' ' . date('h:i A', strtotime($value->tglHistory)) ?></b>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="kt-notes__body">
                                                <?= $value->detailHistory ?>
                                            </span>
                                        </div>
                                    </div>
                            <?php }
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



</div>
<!--End::Row-->