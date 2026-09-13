<!DOCTYPE html>

<html lang="en">

<!-- begin::Head -->

<head>
    <base href="../../">
    <meta charset="utf-8" />
    <title>Tracking Tiket</title>    
    <meta name="description" content="No aside layout examples">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!--begin::Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700">

    <!--end::Fonts -->

    <!--begin::Page Vendors Styles(used by this page) -->
    <link href="assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />

    <!--end::Page Vendors Styles -->

    <!--begin::Global Theme Styles(used by all pages) -->
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />

    <!--end::Global Theme Styles -->

    <!--begin::Layout Skins(used by all pages) -->
    <link href="assets/css/skins/header/base/light.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/skins/header/menu/light.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/skins/brand/light.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/skins/aside/dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/star-rating.min.css" media="all" rel="stylesheet" type="text/css" />

    <!-- optionally if you need to use a theme, then include the theme CSS file as mentioned below -->
    <link href="assets/css/theme-rating.css" media="all" rel="stylesheet" type="text/css" />


    <!--end::Layout Skins -->
    <link rel="shortcut icon" href="assets/media/logos/favicon_unmul.ico" />
</head>

<!-- end::Head -->

<!-- begin::Body -->

<body class="kt-quick-panel--right kt-demo-panel--right kt-offcanvas-panel--right kt-header--fixed kt-header-mobile--fixed kt-subheader--enabled kt-subheader--fixed kt-subheader--solid kt-page--loading">

    <!-- begin:: Page -->

    <!-- begin:: Header Mobile -->
    <div id="kt_header_mobile" class="kt-header-mobile  kt-header-mobile--fixed ">
        <div class="kt-header-mobile__logo">
        </div>
        <div class="kt-header-mobile__toolbar">
            <button class="kt-header-mobile__toggler" id="kt_header_mobile_toggler"><span></span></button>
            <button class="kt-header-mobile__topbar-toggler" id="kt_header_mobile_topbar_toggler"><i class="flaticon-more"></i></button>
        </div>
    </div>

    <!-- end:: Header Mobile -->
    <div class="kt-grid kt-grid--hor kt-grid--root">
        <div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--ver kt-page">
            <div class="kt-grid__item kt-grid__item--fluid kt-grid kt-grid--hor kt-wrapper" id="kt_wrapper">

                <!-- begin:: Header -->
                <div id="kt_header" class="kt-header kt-grid__item  kt-header--fixed ">

                    <!-- begin:: Header Menu -->
                    <div class="kt-header-menu-wrapper" id="kt_header_menu_wrapper">
                        <div class="kt-header-logo">
                            <a href="index.html">
                                <div style="font-size:18px;font-weight:bold;color:black" id="kt-aside__brand-name" class="kt-hidden-mobile">
                                    Tracking Tiket </div>
                            </a>
                        </div>
                    </div>

                    <!-- end:: Header Menu -->

                    <!-- begin:: Header Topbar -->
                    <div class="kt-header__topbar">

                        <!--begin: User Bar -->
                        <div class="kt-header__topbar-item kt-header__topbar-item--user">
                            <div class="kt-header__topbar-wrapper" data-toggle="dropdown" data-offset="0px,0px">
                                <div class="kt-header__topbar-user">
                                    <span class="kt-header__topbar-welcome kt-hidden-mobile">Hi,</span>
                                    <span class="kt-header__topbar-username kt-hidden-mobile">User</span>
                                    <img class="kt-hidden" alt="Pic" src="/assets/media/users/300_25.jpg" />

                                    <!--use below badge element instead the user avatar to display username's first letter(remove kt-hidden class to display it) -->
                                    <span class="kt-badge kt-badge--username kt-badge--unified-success kt-badge--lg kt-badge--rounded kt-badge--bold">U</span>
                                </div>
                            </div>
                        </div>

                        <!--end: User Bar -->
                    </div>

                    <!-- end:: Header Topbar -->
                </div>

                <!-- end:: Header -->
                <div class="kt-content  kt-grid__item kt-grid__item--fluid kt-grid kt-grid--hor" id="kt_content">

                    <!-- begin:: Subheader -->

                    <!-- end:: Subheader -->

                    <!-- begin:: Content -->
                    <div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
                        <!-- END: Subheader -->
                        <!--Begin::Row-->
                        <!-- begin:: Content -->
                        <div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
                            <div class="row">

                                <div class="col-lg-5">
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
                                                <p><a href="<?= $cetakterima ?>" class="btn btn-instagram" target='_blank'><i class="la la-print"></i> Print Bukti</a></p>
                                                <label style="font-weight: bold">Nomor Tiket :</label>
                                                <p id='ticketId'><?php echo $datas['ticketTrackingId'] ?></p>
                                                <p><label style="font-weight: bold">Layanan :</label>
                                                <?= $datas['sCatNama'] . ' - ' . $datas['categoryNama'] ?></p>
                                                <label style="font-weight: bold">Tanggal Tiket :</label>
                                                <p><?php echo datetoindo($datas['ticketCreated']) . ' ' . date('h:i A', strtotime($datas['ticketCreated'])) ?></p>

                                                <label style="font-weight: bold">Status Pekerjaan :</label><br>
                                                <p class="kt-badge kt-badge--<?php echo $datas['statusColor'] ?> kt-badge--inline kt-badge--pill kt-badge--rounded"><?php echo $datas['statusNama'] ?></p><br><br>
                                                <?php if ($datas['ticketStatus'] == 5) : ?>
                                                    <label style="font-weight: bold"> Indeks Kepuasan Masyarakat :</label>
                                                    <p><input id="input-id" type="text" class="kv-uni-star rating-loading" value="<?= $datas['ratingNilai'] ?>" <?= !empty($datas['ratingTicketId']) ? 'disabled' : '' ?>>
                                                        <input type="hidden" id="ticketId" value=<?= $datas['ticketTrackingId'] ?>>
                                                    </p>
                                                    <span style="color: red; font-size: 10px;">Mohon untuk mengisi IKM, Selanjutya berkas anda akan kami kirimkan via email</span>
                                                <?php endif ?>
                                                

                                            </div>
                                            <div class="kt-portlet__foot">                 
                                                <?php if ($output_url != false) : ?>
                                                    <a href="<?= $output_url ?>" class="btn btn-warning"><i class="la la-file"></i> Output</a>
                                                <?php endif; ?>
                                            </div>

                                            <div class="kt-portlet__foot">

                                            </div>
                                        </div>
                                    </div>
                                    <div class="kt-portlet kt-portlet" data-ktportlet="true" id="kt_portlet_tools_1">
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
                                                                                <b><?php echo datetoindo($value->tglHistory) . ' ' . date('h:i A', strtotime($value->tglHistory)) ?></b>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <span class="kt-notes__body">
                                                                        <?php echo $value->detailHistory ?>
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
                                <div class="col-lg-7">
                                    <div class="kt-grid__item kt-grid__item--fluid kt-app__content" id="kt_chat_content">
                                        <div class="kt-chat">
                                            <div class="kt-portlet kt-portlet--head-lg kt-portlet--last">
                                                <div class="kt-portlet__head">
                                                    <div class="kt-chat__head ">
                                                        <div class="kt-chat__center">
                                                            <div class="kt-chat__label">
                                                                <a href="#" class="kt-chat__title"><?php echo $user_group ?></a>
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
                                                                    <div class="kt-chat__message <?php echo $value->repliesStatus == 'USER' ? 'kt-chat__message--right' : '' ?>">
                                                                        <div class="kt-chat__user">
                                                                            <?php echo $value->repliesStatus == 'USER' ? '<span class="kt-media kt-media--circle kt-media--sm">
	                                                        <img src="/assets/media/users/user.jpg" alt="image">
	                                                    </span>' : '<span class="kt-chat__datetime">' . findTimeAgo($value->repliesDate) . '</span>' ?>
                                                                            <a href="#" class="kt-chat__username"><?php echo $value->repliesStatus == 'USER' ? 'You' : $value->repliesBy ?></span></a>
                                                                            <?php echo $value->repliesStatus != 'USER' ? '<span class="kt-media kt-media--circle kt-media--sm">
	                                                        <img src="/assets/media/users/admin.jpg" alt="image">
	                                                    </span>' : '<span class="kt-chat__datetime">' . findTimeAgo($value->repliesDate) . '</span>' ?>
                                                                        </div>
                                                                        <div class="kt-chat__text kt-bg-light-<?= $value->repliesStatus == 'USER' ? 'primary' : 'success' ?>">
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
                                                        <form action="<?= $save_url ?>" method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="repliesTicketId" value="<?php echo $datas['ticketTrackingId'] ?>">
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
                                                                    <a href="<?php echo $close_url ?>" id="ts_close_btn" class="btn btn-secondary btn-sm btn-md btn-upper kt-chat__reply">Close Ticket</a>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--End::Row-->
                    </div>

                    <!-- end:: Content -->
                </div>

                <!-- begin:: Footer -->
                <?= $this->include('layouts/footer') ?>


                <!-- end:: Footer -->
            </div>
        </div>
    </div>

    <!-- end:: Page -->

    <!-- begin::Scrolltop -->
    <div id="kt_scrolltop" class="kt-scrolltop">
        <i class="fa fa-arrow-up"></i>
    </div>

    <!-- begin::Global Config(global config for global JS sciprts) -->
    <script>
        var KTAppOptions = {
            "colors": {
                "state": {
                    "brand": "#5d78ff",
                    "dark": "#282a3c",
                    "light": "#ffffff",
                    "primary": "#5867dd",
                    "success": "#34bfa3",
                    "info": "#36a3f7",
                    "warning": "#ffb822",
                    "danger": "#fd3995"
                },
                "base": {
                    "label": [
                        "#c5cbe3",
                        "#a1a8c3",
                        "#3d4465",
                        "#3e4466"
                    ],
                    "shape": [
                        "#f0f3ff",
                        "#d9dffa",
                        "#afb4d4",
                        "#646c9a"
                    ]
                }
            }
        };
    </script>

    <!-- end::Global Config -->

    <!--begin::Global Theme Bundle(used by all pages) -->
    <script src="/assets/plugins/global/plugins.bundle.js" type="text/javascript"></script>
    <script src="/assets/js/scripts.bundle.js" type="text/javascript"></script>
    <!--begin::Page Scripts(used by this page) -->
    <script src="/assets/js/star-rating.min.js" type="text/javascript"></script>

    <script src="/assets/js/themes-rating.js"></script>

    <script type="text/javascript">
        const KTTicketing = function() {
            const main_form = $('#main_form');
            const initHandleWidgets = () => {
                $('.kv-uni-star').rating({
                    theme: 'krajee-uni',
                    filledStar: '&#x2605;',
                    emptyStar: '&#x2606;'
                });
            }
            const initHandleShow = () => {
                $('.kv-uni-star').on('change', function() {
                    const ticketId = $('#ticketId').text();
                    $.ajax({
                        type: 'POST',
                        url: '/cektiket/rating',
                        data: {
                            rating: $(this).val(),
                            nomorTiket: ticketId
                        },
                        success: data => {
                            console.log(data)
                            swal.fire({
                                title: "Indeks Kepuasan Masyarakat",
                                text: 'Terimakasih Telah Mengisi IKM, Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon Periksa Email Anda!',
                                type: 'success'
                            }).then(function () {
                                    location.reload()
                                });
                        }
                    });
                });
            }

            return {
                init: function() {
                    initHandleWidgets();
                    initHandleShow();
                }
            };
        }();

        KTUtil.ready(function() {
            KTTicketing.init();
        });
    </script>
    <!--end::Page Scripts -->
</body>

<!-- end::Body -->

</html>