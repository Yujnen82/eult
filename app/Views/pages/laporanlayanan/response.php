<?php
$datas        = $datas ?? [];
$periodeAwal  = !empty($awal) && function_exists('eult_tanggal_indo') ? eult_tanggal_indo($awal) : (!empty($awal) ? date('d-m-Y', strtotime($awal)) : '-');
$periodeAkhir = !empty($akhir) && function_exists('eult_tanggal_indo') ? eult_tanggal_indo($akhir) : (!empty($akhir) ? date('d-m-Y', strtotime($akhir)) : '-');
$periodeLabel = $periodeAwal . ' s/d ' . $periodeAkhir;
$hasData      = !empty($datas);
$masuk = $terima = $tolak = $proses = $selesai = 0;
if ($hasData) {
    foreach ($datas as $row) {
        $masuk   += (int) ($row['Jumlah'] ?? 0);
        $terima  += (int) ($row['TERIMA'] ?? 0);
        $tolak   += (int) ($row['TOLAK'] ?? 0);
        $proses  += (int) ($row['PROSES'] ?? 0);
        $selesai += (int) ($row['SELESAI'] ?? 0);
    }
}
?>
<div class="kt-portlet kt-portlet--mobile mb-0">
    <div class="kt-portlet__head flex-wrap py-3" style="min-height: 60px;">
        <div class="kt-portlet__head-label py-1">
            <span class="kt-portlet__head-icon">
                <i class="flaticon2-layers-1 text-primary"></i>
            </span>
            <h3 class="kt-portlet__head-title font-weight-bold">
                Rekapitulasi Tiket per Layanan
                <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold ml-2 py-2 px-3">
                    <?= esc($periodeLabel) ?>
                </span>
            </h3>
        </div>
        <div class="kt-portlet__head-toolbar py-1">
            <div class="kt-portlet__head-actions">
                <?php if ($hasData) : ?>
                    <button type="button" id="export" data-name="laporan-layanan" class="btn btn-sm btn-outline-success btn-elevate font-weight-bold">
                        <i class="fa fa-file-excel mr-1"></i> Unduh Excel
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="kt-portlet__body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 no-datatable" id="table_export">
                <caption class="sr-only">Rekapitulasi tiket per layanan untuk periode <?= esc($periodeLabel) ?></caption>
                <thead class="thead-light">
                    <tr>
                        <th class="text-uppercase text-muted font-weight-bold align-middle" style="font-size: 11px; letter-spacing: 0.5px;">Jenis Layanan</th>
                        <th class="text-uppercase text-muted font-weight-bold align-middle" style="font-size: 11px; letter-spacing: 0.5px;">Nama Layanan</th>
                        <th class="text-uppercase text-muted font-weight-bold align-middle" style="font-size: 11px; letter-spacing: 0.5px;">Bidang</th>
                        <th class="text-uppercase text-muted font-weight-bold text-center align-middle laporan-num" style="font-size: 11px; letter-spacing: 0.5px;">Tiket Masuk</th>
                        <th class="text-uppercase text-muted font-weight-bold text-center align-middle laporan-num" style="font-size: 11px; letter-spacing: 0.5px;">Diterima</th>
                        <th class="text-uppercase text-muted font-weight-bold text-center align-middle laporan-num" style="font-size: 11px; letter-spacing: 0.5px;">Ditolak</th>
                        <th class="text-uppercase text-muted font-weight-bold text-center align-middle laporan-num" style="font-size: 11px; letter-spacing: 0.5px;">Diproses</th>
                        <th class="text-uppercase text-muted font-weight-bold text-center align-middle laporan-num" style="font-size: 11px; letter-spacing: 0.5px;">Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hasData) : ?>
                        <?php foreach ($datas as $row) : ?>
                            <?php
                            $rowJumlah  = (int) ($row['Jumlah'] ?? 0);
                            $rowTerima  = (int) ($row['TERIMA'] ?? 0);
                            $rowTolak   = (int) ($row['TOLAK'] ?? 0);
                            $rowProses  = (int) ($row['PROSES'] ?? 0);
                            $rowSelesai = (int) ($row['SELESAI'] ?? 0);
                            ?>
                            <tr>
                                <td class="align-middle text-dark"><?= esc($row['jenislayananNama'] ?? '-') ?></td>
                                <td class="align-middle font-weight-bold text-dark"><?= esc($row['layananNama'] ?? '-') ?></td>
                                <td class="align-middle">
                                    <span class="kt-badge kt-badge--unified-brand kt-badge--inline kt-badge--pill font-weight-bold">
                                        <?= esc($row['unitNama'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="align-middle text-center laporan-num">
                                    <?= $rowJumlah > 0 ? '<span class="font-weight-bold text-dark">' . $rowJumlah . '</span>' : '<span class="text-muted opacity-50">0</span>' ?>
                                </td>
                                <td class="align-middle text-center laporan-num">
                                    <?= $rowTerima > 0 ? '<span class="font-weight-bold text-primary">' . $rowTerima . '</span>' : '<span class="text-muted opacity-50">0</span>' ?>
                                </td>
                                <td class="align-middle text-center laporan-num">
                                    <?= $rowTolak > 0 ? '<span class="font-weight-bold text-danger">' . $rowTolak . '</span>' : '<span class="text-muted opacity-50">0</span>' ?>
                                </td>
                                <td class="align-middle text-center laporan-num">
                                    <?= $rowProses > 0 ? '<span class="font-weight-bold text-warning">' . $rowProses . '</span>' : '<span class="text-muted opacity-50">0</span>' ?>
                                </td>
                                <td class="align-middle text-center laporan-num">
                                    <?= $rowSelesai > 0 ? '<span class="font-weight-bold text-success">' . $rowSelesai . '</span>' : '<span class="text-muted opacity-50">0</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="flaticon2-information d-block mb-2" style="font-size: 2rem; color: #a1a5b7;"></i>
                                Tidak ada tiket pada periode <?= esc($periodeLabel) ?>. Ubah filter, lalu tampilkan ulang.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if ($hasData) : ?>
                    <tfoot>
                        <tr class="laporan-total">
                            <td colspan="3" class="align-middle font-weight-bold text-dark">Total</td>
                            <td class="align-middle text-center laporan-num font-weight-bold text-dark"><?= $masuk ?></td>
                            <td class="align-middle text-center laporan-num font-weight-bold text-primary"><?= $terima ?></td>
                            <td class="align-middle text-center laporan-num font-weight-bold text-danger"><?= $tolak ?></td>
                            <td class="align-middle text-center laporan-num font-weight-bold text-warning"><?= $proses ?></td>
                            <td class="align-middle text-center laporan-num font-weight-bold text-success"><?= $selesai ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
