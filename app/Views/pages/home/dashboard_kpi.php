<!-- Baris 1: 4 Kartu KPI Metrik Operasional -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #5d78ff; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
            <div class="kt-portlet__body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Baru Masuk</span>
                        <h2 class="font-weight-bold my-2" style="color: #1e1e2d; font-size: 28px;"><?= $total_masuk ?? 0 ?></h2>
                        <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-incoming text-primary mr-1"></i>Menunggu respons awal</span>
                    </div>
                    <div class="kt-badge kt-badge--unified-brand kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                        <i class="flaticon2-mail" style="font-size: 22px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #ffb822; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
            <div class="kt-portlet__body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Perlu Tindakan</span>
                        <h2 class="font-weight-bold my-2" style="color: #ffb822; font-size: 28px;"><?= $total_tindakan ?? 0 ?></h2>
                        <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-hourglass-1 text-warning mr-1"></i>Butuh disposisi / verifikasi</span>
                    </div>
                    <div class="kt-badge kt-badge--unified-warning kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                        <i class="flaticon-alarm" style="font-size: 22px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #5578eb; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
            <div class="kt-portlet__body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Dalam Proses</span>
                        <h2 class="font-weight-bold my-2" style="color: #5578eb; font-size: 28px;"><?= $total_proses ?? 0 ?></h2>
                        <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-reload text-info mr-1"></i>Sedang dikerjakan unit</span>
                    </div>
                    <div class="kt-badge kt-badge--unified-info kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                        <i class="flaticon2-document" style="font-size: 22px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="kt-portlet kt-portlet--fit kt-portlet--head-noborder" style="border-left: 4px solid #0abb87; box-shadow: 0px 0px 13px 0px rgba(82, 63, 105, 0.05);">
            <div class="kt-portlet__body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Selesai</span>
                        <h2 class="font-weight-bold my-2" style="color: #0abb87; font-size: 28px;"><?= $total_selesai ?? 0 ?></h2>
                        <span class="text-muted" style="font-size: 12px;"><i class="flaticon2-check-mark text-success mr-1"></i>Dokumen diterbitkan</span>
                    </div>
                    <div class="kt-badge kt-badge--unified-success kt-badge--lg kt-badge--rounded" style="width: 50px; height: 50px; border-radius: 8px;">
                        <i class="flaticon2-correct" style="font-size: 22px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
