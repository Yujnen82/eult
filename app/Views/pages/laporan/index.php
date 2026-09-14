<?= $this->include('layouts/subheader') ?>

<style {csp-style-nonce}>
    html {
        scroll-behavior: smooth;
    }
    #response {
        scroll-margin-top: 135px;
    }
    #table_export td.laporan-num,
    #table_export th.laporan-num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum" 1;
    }
    #table_export tfoot td,
    #table_export tr.laporan-total td {
        background-color: #f7f8fa;
    }
    @media (prefers-reduced-motion: reduce) {
        html {
            scroll-behavior: auto;
        }
        #response.flipInX {
            animation: none !important;
        }
    }
</style>

<div id="main_form">
    <div id="first-form">
        <div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="kt-portlet mb-4">
                        <div class="kt-portlet__head">
                            <div class="kt-portlet__head-label">
                                <span class="kt-portlet__head-icon">
                                    <i class="flaticon2-pie-chart-1 text-primary"></i>
                                </span>
                                <h3 class="kt-portlet__head-title font-weight-bold">
                                    <?= esc(strtoupper($page_judul ?? 'Laporan')) ?>
                                </h3>
                            </div>
                        </div>
                        <form class="kt-form" action="<?= esc($show_url ?? '#') ?>" method="post" id="form_show">
                            <div class="kt-portlet__body">
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold text-dark mb-2" for="rentangTanggal">
                                        Rentang Tanggal <span class="text-danger">*</span>
                                    </label>
                                    <div class="row align-items-start">
                                        <div class="col-md-8 col-lg-9">
                                            <div class="input-group" id="kt_daterangepicker_2">
                                                <input type="text" class="form-control" id="rentangTanggal" name="rentangTanggal" readonly placeholder="Pilih rentang tanggal" value="<?= esc($tanggal ?? '') ?>" autocomplete="off" aria-required="true">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" aria-hidden="true"><i class="la la-calendar-check-o"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-lg-3 mt-3 mt-md-0 text-md-right">
                                            <button type="submit" id="btn_show" class="btn btn-brand btn-elevate btn-icon-sm w-100 w-md-auto">
                                                <i class="flaticon2-search-1"></i>
                                                Tampilkan Laporan
                                            </button>
                                        </div>
                                    </div>
                                    <span class="form-text text-muted mt-2">
                                        <i class="flaticon2-information mr-1"></i> Pilih periode, lalu tampilkan rekapitulasi tiket masuk, diterima, ditolak, diproses, dan selesai per bidang.
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id="response">
                        <div class="kt-portlet mb-0" id="laporan_empty_hint">
                            <div class="kt-portlet__body text-center py-5 text-muted">
                                <i class="flaticon2-pie-chart-1 d-block mb-3" style="font-size: 2rem; color: #a1a5b7;"></i>
                                <div class="font-weight-bold text-dark mb-1">Belum ada rekapitulasi ditampilkan</div>
                                <span style="font-size: 13px;">Pilih rentang tanggal, lalu klik Tampilkan Laporan untuk melihat rekap tiket per bidang.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="second-form" class="response-hide"></div>
</div>
