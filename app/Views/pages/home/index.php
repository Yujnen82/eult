<?= $this->include('layouts/subheader') ?>

<div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
    <!-- Baris 1: 4 Kartu KPI Metrik Operasional -->
    <?= $this->include('pages/home/dashboard_kpi') ?>

    <!-- Baris 2: Antrean Tindakan Mendesak & Pintasan Cepat -->
    <div class="row">
        <div class="col-xl-8 col-lg-12">
            <?= $this->include('pages/home/dashboard_urgent') ?>
        </div>
        <div class="col-xl-4 col-lg-12">
            <div class="kt-portlet">
                <div class="kt-portlet__head">
                    <div class="kt-portlet__head-label">
                        <span class="kt-portlet__head-icon"><i class="flaticon2-layers-1 text-primary"></i></span>
                        <h3 class="kt-portlet__head-title font-weight-bold">Pintasan Layanan</h3>
                    </div>
                </div>
                <div class="kt-portlet__body">
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= base_url('ticketing') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Daftar Antrean Tiketing</div>
                                <small class="text-muted">Kelola disposisi & verifikasi tiket masuk</small>
                            </div>
                            <i class="flaticon2-right-arrow text-primary"></i>
                        </a>
                        <a href="<?= base_url('validasifile') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Validasi & Keamanan Berkas</div>
                                <small class="text-muted">Pindai integritas dan status karantina file</small>
                            </div>
                            <i class="flaticon2-shield text-warning"></i>
                        </a>
                        <a href="<?= base_url('laporan') ?>" class="btn btn-outline-secondary text-left d-flex align-items-center justify-content-between p-3 mb-2" style="border-radius: 6px;">
                            <div>
                                <div class="font-weight-bold text-dark">Laporan & Rekapitulasi IKM</div>
                                <small class="text-muted">Unduh rekapitulasi data dan indeks kepuasan</small>
                            </div>
                            <i class="flaticon2-pie-chart-1 text-success"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>