
<!DOCTYPE html>
<!--
Template Name: Metronic - Bootstrap 4 HTML, React, Angular 9 & VueJS Admin Dashboard Theme
Author: KeenThemes
Website: http://www.keenthemes.com/
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
Purchase: https://1.envato.market/EA4JP
Renew Support: https://1.envato.market/EA4JP
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<html lang="en">
    <!--begin::Head-->
    <head><base href="../../../">
        <meta charset="utf-8" />
        <title><?= base_url(); ?> | Unit Layanan Terpadu UNMUL</title>
        <meta name="description" content="Invoice example" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <link rel="canonical" href="https://keenthemes.com/metronic" />
        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
        <!--end::Fonts-->
        <!--begin::Global Theme Styles(used by all pages)-->
        <link href="<?= base_url(); ?>assets_val/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="<?= base_url(); ?>assets_val/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
        <link href="<?= base_url(); ?>assets_val/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <!--end::Global Theme Styles-->
        <!--begin::Layout Themes(used by all pages)-->
        <!--end::Layout Themes-->
        <link rel="shortcut icon" href="assets_val/media/logos/favicon.ico" />
    </head>
    <!--end::Head-->
    <!--begin::Body-->
    <body id="kt_body" class="print-content-only quick-panel-right demo-panel-right offcanvas-right header-fixed header-mobile-fixed subheader-enabled aside-enabled aside-static page-loading">
        <!--begin::Main-->
        <!--begin::Header Mobile-->
        <div id="kt_header_mobile" class="header-mobile header-mobile-fixed">
            <!--begin::Logo-->
            <a href="<?= base_url(); ?>">
                <img alt="Logo" src="<?= base_url(); ?>assets_val/logo-ult12.png" class="logo-default max-h-60px" />
            </a>
            <div class="header-title"> <h1>Unit Layanan Terpadu Universitas Mulawarman</h1></div>
            <!--end::Logo-->            
        </div>
        <!--end::Header Mobile-->
        <div class="d-flex flex-column flex-root">
            <!--begin::Page-->
            <div class="d-flex flex-row flex-column-fluid page">
                <!--begin::Aside-->
                <div class="aside aside-left d-flex flex-column flex-row-auto" id="kt_aside">
                    <!--begin::Aside Menu-->
                    <div class="aside-menu-wrapper flex-column-fluid" id="kt_aside_menu_wrapper">
                        <!--begin::Menu Container-->
                        <div id="kt_aside_menu" class="aside-menu min-h-lg-800px" data-menu-vertical="1" data-menu-scroll="1" data-menu-dropdown-timeout="500">
                        </div>
                        <!--end::Menu Container-->
                    </div>
                    <!--end::Aside Menu-->
                </div>
                <!--end::Aside-->
                <!--begin::Wrapper-->
                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
                    <!--begin::Header-->
                    <div id="kt_header" class="header header-fixed">
                        <!--begin::Container-->
                        <div class="container d-flex align-items-stretch justify-content-between">
                            <!--begin::Left-->
                            <div class="d-none d-lg-flex align-items-center mr-3">
                                <!--begin::Logo-->
                                <a href="<?= base_url(); ?>">
                                    <img alt="Logo" src="<?= base_url(); ?>assets_val/logo-ult12.png" class="logo-sticky max-h-60px" />
                                </a>
                                <div class="header-title"> <h1>Unit Layanan Terpadu Universitas Mulawarman</h1></div>
                            </div>
                            <div class="topbar">
                        <!--begin::Tablet & Mobile Search-->
                        <div class="dropdown d-flex d-lg-none">
                            <!--begin::Toggle-->
                            <div class="topbar-item" data-toggle="dropdown" data-offset="10px,0px">
                                <div class="btn btn-icon btn-clean btn-lg btn-dropdown mr-1">
                                    <span class="svg-icon svg-icon-xl">
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="topbar-item mr-4">
                            <a href="<?=base_url();?>">
                                <div class="btn font-weight-bolder btn-sm btn-light-warning px-5">
                                    Login
                                </div>
                            </a>
                            &nbsp
                            <a href="http://ult.unmul.ac.id/">
                                <div class="btn font-weight-bolder btn-sm btn-light-success px-5">
                                    Website ULT
                                </div>
                            </a>                            
                        </div>
                        <!--end::User-->
                        <!--begin::Notifications-->

                        <!--end::Notifications-->
                    </div>
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Header-->
                    <!--begin::Content-->
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                        <!--begin::Entry-->
                        <div class="d-flex flex-column-fluid">
                            <!--begin::Container-->
                            <div class="container">
                                <!-- begin::Card-->
                                <div class="card card-custom overflow-hidden">
                                    <div class="card-header ribbon ribbon-top ribbon-ver">
                                        <div class="ribbon-target bg-danger" style="top: -2px; right: 20px;">                                            
                                        </div>                              
                                        <h3 class="card-title">Validasi Surat Unit Layanan Terpadu Universitas Mulawarman</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="card-body py-12">
                                            <div class="row">
                                                <div class="col-lg-12">                         
                                                    <table border="0" width="60%">
                                                    <tr>
                                                        <td>NIM/NIP</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ? $datas['ticketIdentitas']: '' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Nama</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ?$datas['ticketName'] :'' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Email</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ?$datas['ticketEmail'] :'' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>No. HP</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ?$datas['ticketNoHp']:'' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Nomor Tiket</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ? $datas['ticketTrackingId']:'' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tanggal Tiket</td>
                                                        <td>:</td>
                                                        <td><?= $datas != false ? datetoindo($datas['ticketCreated']) . ' ' . date('h:i A', strtotime($datas['ticketCreated'])):'' ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Layanan</td>
                                                        <td>:</td>
                                                        <td><?= $datas['sCatNama'] . ' - ' . $datas['categoryNama'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Status Tiket</td>
                                                        <td>:</td>
                                                        <td><?= $datas['statusNama'] ?></td>
                                                    </tr>
                                                </table>
                                                    
                                                    <hr>

                                                    <iframe src="<?= esc($loadpdf_url ?? '') ?>" frameborder="0" width="100%" height="600"></iframe>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- end::Card-->
                            </div>
                            <!--end::Container-->
                        </div>
                        <!--end::Entry-->
                    </div>
                    <!--end::Content-->
                    <!--begin::Footer-->
                    <div class="footer bg-white py-4 d-flex flex-lg-column" id="kt_footer">
                        <!--begin::Container-->
                        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between">
                            <!--begin::Copyright-->
                            <div class="text-dark order-2 order-md-1">
                                <span class="text-muted font-weight-bold mr-2">Copyright&nbsp;&copy;&nbsp;2019&nbsp;</span><a href="https://ict.unmul.ac.id/" target="_blank" class="text-dark-75 text-hover-primary">UPT. Teknologi Informasi dan Komunikasi</a>
                            </div>
                            <!--end::Copyright-->
                            <!--begin::Nav-->
                            <div class="nav nav-dark order-1 order-md-2">
                                <a href="https://eult.unmul.ac.id/" target="_blank" class="text-dark-75 text-hover-primary">
                                    Unit Layanan Terpadu Universitas Mulawarman</a>
                            </div>
                            <!--end::Nav-->
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Footer-->                    
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Page-->
        </div>
        
        </div>
        <!--end::Chat Panel-->
        <!--begin::Scrolltop-->
        
        <!--end::Scrolltop-->
        <!--begin::Sticky Toolbar-->
        
        <!--end::Sticky Toolbar-->
        <!--begin::Demo Panel-->
        
        <!--end::Demo Panel-->

        <!--begin::Global Config(global config for global JS scripts)-->
        <script>var KTAppSettings = { "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1200 }, "colors": { "theme": { "base": { "white": "#ffffff", "primary": "#6993FF", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#8950FC", "warning": "#FFA800", "danger": "#F64E60", "light": "#F3F6F9", "dark": "#212121" }, "light": { "white": "#ffffff", "primary": "#E1E9FF", "secondary": "#ECF0F3", "success": "#C9F7F5", "info": "#EEE5FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#212121", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": { "gray-100": "#F3F6F9", "gray-200": "#ECF0F3", "gray-300": "#E5EAEE", "gray-400": "#D6D6E0", "gray-500": "#B5B5C3", "gray-600": "#80808F", "gray-700": "#464E5F", "gray-800": "#1B283F", "gray-900": "#212121" } }, "font-family": "Poppins" };</script>
        <!--end::Global Config-->
        <!--begin::Global Theme Bundle(used by all pages)-->
        <script src="assets_val/plugins/global/plugins.bundle.js"></script>
        <script src="assets_val/plugins/custom/prismjs/prismjs.bundle.js"></script>
        <script src="assets_val/js/scripts.bundle.js"></script>
        <!--end::Global Theme Bundle-->
    </body>
    <!--end::Body-->
</html>