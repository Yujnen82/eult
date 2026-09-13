<div class="kt-portlet kt-portlet--mobile">
    <div class="kt-portlet__head kt-portlet__head--lg">
        <div class="kt-portlet__head-label">
            <span class="kt-portlet__head-icon"><i class="flaticon2-time text-warning"></i></span>
            <h3 class="kt-portlet__head-title font-weight-bold">Antrean Tiket Memerlukan Tindakan Segera</h3>
        </div>
        <div class="kt-portlet__head-toolbar">
            <a href="<?= base_url('ticketing') ?>" class="btn btn-sm btn-outline-brand font-weight-bold">
                Lihat Semua Tiket <i class="la la-angle-right ml-1"></i>
            </a>
        </div>
    </div>
    <div class="kt-portlet__body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 no-datatable">
                <thead class="thead-light">
                    <tr>
                        <th style="font-size: 12px;">Nomor Tiket</th>
                        <th style="font-size: 12px;">Pemohon</th>
                        <th style="font-size: 12px;">Unit Kerja</th>
                        <th style="font-size: 12px;">Status</th>
                        <th style="font-size: 12px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tiket_urgent)): ?>
                        <?php foreach ($tiket_urgent as $t): ?>
                            <tr>
                                <td class="font-weight-bold" style="font-family: monospace; font-size: 13px; color: #5d78ff;">
                                    <?= esc($t['ticketTrackingId']) ?>
                                </td>
                                <td style="font-size: 13px;">
                                    <div class="font-weight-bold"><?= esc($t['ticketName']) ?></div>
                                    <span class="text-muted" style="font-size: 11px;"><?= esc($t['ticketCreated']) ?></span>
                                </td>
                                <td style="font-size: 13px;"><?= esc($t['unitNama'] ?? 'Pusat ULT') ?></td>
                                <td>
                                    <span class="kt-badge kt-badge--unified-<?= esc($t['statusColor'] ?? 'warning') ?> kt-badge--inline kt-badge--pill font-weight-bold">
                                        <?= esc($t['statusNama']) ?>
                                    </span>
                                </td>
                                <td align="center">
                                    <a href="<?= base_url('ticketing/detail/' . service('enkripsi')->encode($t['ticketTrackingId'])) ?>" class="btn btn-sm btn-label-brand btn-icon" title="Periksa Tiket">
                                        <i class="flaticon-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="flaticon2-checkmark text-success mr-2"></i>Semua antrean tiket saat ini telah tertangani dengan baik.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
