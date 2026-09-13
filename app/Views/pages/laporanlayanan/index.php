<?= $this->include('layouts/subheader') ?>

<style>
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
        #response.flipInX {
            animation: none !important;
        }
    }
</style>

<?php $isOperator = (($user_group['susrSgroupNama'] ?? '') === 'ADMIN' || (strpos(($user_group['susrSgroupNama'] ?? ''), 'OPERATOR') !== false)); ?>

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
                                    <?= esc(strtoupper($page_judul ?? 'Laporan Layanan ULT')) ?>
                                </h3>
                            </div>
                        </div>
                        <form class="kt-form" action="<?= esc($show_url ?? '#') ?>" method="post" id="form_show">
                            <div class="kt-portlet__body">
                                <div class="row">
                                    <div class="col-lg-<?= $isOperator ? '5' : '9' ?> col-md-12 mb-3 mb-lg-0">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold text-dark mb-2" for="rentangTanggal">
                                                Rentang Tanggal <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group" id="kt_daterangepicker_2">
                                                <input type="text" class="form-control" id="rentangTanggal" name="rentangTanggal" readonly placeholder="Pilih rentang tanggal" value="<?= esc($tanggal ?? '') ?>" autocomplete="off" aria-required="true">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" aria-hidden="true"><i class="la la-calendar-check-o"></i></span>
                                                </div>
                                            </div>
                                            <span class="form-text text-muted">Periode tiket yang dihitung dalam rekapitulasi</span>
                                        </div>
                                    </div>
                                    <?php if ($isOperator) : ?>
                                        <div class="col-lg-4 col-md-12 mb-3 mb-lg-0">
                                            <div class="form-group mb-0">
                                                <label class="font-weight-bold text-dark mb-2" for="filter_layanan">
                                                    Unit Layanan <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control m-select2" name="layanan" id="filter_layanan" required>
                                                    <option value=""></option>
                                                    <?php if (!empty($category)) : ?>
                                                        <?php foreach ($category as $row) : ?>
                                                            <option value="<?= esc($row['unitId']) ?>"><?= esc($row['unitNama']) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <span class="form-text text-muted">Wajib dipilih agar rekap per layanan tampil</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-lg-3 col-md-12 d-flex align-items-end">
                                        <button type="submit" id="btn_show" class="btn btn-brand btn-elevate btn-icon-sm w-100 mb-0 mb-lg-4">
                                            <i class="flaticon2-search-1"></i>
                                            Tampilkan Laporan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div id="response">
                        <div class="kt-portlet mb-0" id="laporan_empty_hint">
                            <div class="kt-portlet__body text-center py-5 text-muted">
                                <i class="flaticon2-pie-chart-1 d-block mb-3" style="font-size: 2rem; color: #a1a5b7;"></i>
                                <div class="font-weight-bold text-dark mb-1">Belum ada rekapitulasi ditampilkan</div>
                                <span style="font-size: 13px;">Pilih rentang tanggal<?= $isOperator ? ' dan unit layanan' : '' ?>, lalu klik Tampilkan Laporan untuk melihat rekap per jenis layanan.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="second-form" class="response-hide"></div>
</div>
