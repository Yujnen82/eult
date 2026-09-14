<?= $this->include('layouts/subheader') ?>
<div id="main_form">
    <div id="first-form">
        <div class="kt-container kt-container--fluid kt-grid__item kt-grid__item--fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="kt-portlet kt-portlet--mobile">
                        <div class="kt-portlet__head kt-portlet__head--lg">
                            <div class="kt-portlet__head-label">
                                <span class="kt-portlet__head-icon"><i class="flaticon2-layers-1 text-primary"></i></span>
                                <h3 class="kt-portlet__head-title font-weight-bold">
                                    <?= esc(strtoupper($page_judul ?? 'Antrean Tiket Layanan')) ?>
                                </h3>
                            </div>
                            <div class="kt-portlet__head-toolbar">
                                <div class="kt-portlet__head-actions">
                                    <?php if (($user_group['susrSgroupNama'] ?? '') === 'ADMIN' || (strpos(($user_group['susrSgroupNama'] ?? ''), 'OPERATOR') !== false)) : ?>
                                        <a href="<?= esc($create_url ?? '#') ?>" class="btn btn-sm btn-outline-brand font-weight-bold" id="btn-create">
                                            <i class="flaticon2-plus mr-1"></i>
                                            <span>Buat Tiket Baru</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <form class="kt-form" action="<?= esc($show_url ?? '#') ?>" method="post" id="form_show">
                            <div class="kt-portlet__body p-4">
                                <!-- Baris Tab Filter Cepat Status Tiket -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-3 border-bottom">
                                    <div class="d-flex align-items-center flex-wrap mb-2 mb-md-0" id="status_filter_pills" style="gap: 6px;">
                                        <span class="text-muted font-weight-bold mr-2" style="font-size: 12px;">
                                            <i class="flaticon2-filter mr-1"></i>Status Tiket:
                                        </span>
                                        <button type="button" class="btn btn-sm btn-pill btn-brand active status-pill" data-status="" data-filter="all">Semua</button>
                                        <button type="button" class="btn btn-sm btn-pill btn-outline-secondary status-pill" data-status="1" data-filter="baru">Baru</button>
                                        <button type="button" class="btn btn-sm btn-pill btn-outline-secondary status-pill" data-status="2" data-filter="disposisi">Disposisi</button>
                                        <button type="button" class="btn btn-sm btn-pill btn-outline-secondary status-pill" data-status="3" data-filter="verifikasi">Verifikasi</button>
                                        <button type="button" class="btn btn-sm btn-pill btn-outline-secondary status-pill" data-status="5" data-filter="selesai">Selesai</button>
                                        <input type="hidden" name="status_layanan" id="filter_status_layanan" value="">
                                    </div>
                                    <div>
                                        <button type="submit" id="btn_show" class="btn btn-brand btn-sm font-weight-bold">
                                            <i class="flaticon2-search mr-1"></i> Tampilkan Data
                                        </button>
                                    </div>
                                </div>

                                <!-- Baris Form Filter 3-Kolom Terpadu -->
                                <div class="row">
                                    <!-- Kolom 1: Rentang Tanggal -->
                                    <div class="col-lg-4 col-md-12 mb-3">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold" style="font-size: 12px; color: #48465b;">Rentang Tanggal</label>
                                            <div class="input-group" id="kt_daterangepicker_2">
                                                <input type="text" class="form-control" name="rentangTanggal" readonly placeholder="Pilih rentang tanggal" value="<?= esc($tanggal ?? '') ?>">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="la la-calendar-check-o"></i></span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Periode tanggal pembuatan tiket</small>
                                        </div>
                                    </div>

                                    <!-- Kolom 2: Layanan / Kategori Unit Kerja -->
                                    <div class="col-lg-4 col-md-12 mb-3">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold" style="font-size: 12px; color: #48465b;">Unit Layanan</label>
                                            <?php if (($user_group['susrSgroupNama'] ?? '') === 'ADMIN' || (strpos(($user_group['susrSgroupNama'] ?? ''), 'OPERATOR') !== false)) : ?>
                                                <select class="form-control m-select2" name="layanan" id="filter_layanan">
                                                    <option value=""></option>
                                                    <?php if (!empty($category)) : ?>
                                                        <?php foreach ($category as $row) : ?>
                                                            <option value="<?= esc($row['unitId']) ?>"><?= esc($row['unitNama']) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                <small class="form-text text-muted">Pilih unit kerja penyedia layanan</small>
                                            <?php else: ?>
                                                <input type="text" class="form-control bg-light" readonly value="<?= esc($user_group['susrSgroupNama'] ?? '') ?>">
                                                <small class="form-text text-muted">Tersaring otomatis berdasarkan unit kerja Anda</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Kolom 3: Kata Kunci Pencarian -->
                                    <div class="col-lg-4 col-md-12 mb-3">
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold" style="font-size: 12px; color: #48465b;">Cari Tiket / Pemohon</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="flaticon-search"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="keyword_filter" placeholder="Nomor tracking / nama pemohon...">
                                            </div>
                                            <small class="form-text text-muted">Pencarian instan pada tabel antrean di bawah</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div id="response" class=""></div>
    </div>
    <div id="second-form" class="response-hide"></div>
</div>

<script {csp-script-nonce}>
document.addEventListener('DOMContentLoaded', function() {
    // Interaksi tombol pill filter cepat status
    $(document).on('click', '.status-pill', function(e) {
        e.preventDefault();
        $('.status-pill').removeClass('btn-brand active text-white').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-brand active text-white');

        var statusVal = $(this).data('status');
        var filterVal = ($(this).data('filter') || '').toString().toLowerCase();
        $('#filter_status_layanan').val(statusVal);

        // Filter langsung baris tabel yang sudah dimuat
        var $rows = $('#response table tbody tr');
        if ($rows.length > 0) {
            if (filterVal === 'all' || !filterVal) {
                $rows.show();
            } else {
                $rows.each(function() {
                    var rowText = $(this).text().toLowerCase();
                    if (rowText.indexOf(filterVal) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        }
    });

    // Filter instan berdasarkan kata kunci pencarian
    $(document).on('keyup', '#keyword_filter', function() {
        var keyword = $(this).val().toLowerCase();
        var $rows = $('#response table tbody tr');
        $rows.each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(keyword) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>