/**
 * E-ULT v2 - Global Metronic DataTable Auto-Initializer
 * Menginisialisasi DataTables bawaan template Metronic v6 pada seluruh tabel admin
 * agar data tersaji ringkas (distilled) dengan pagination, filter pencarian instan,
 * dan tata letak responsif tanpa scrolling vertikal yang panjang.
 */
'use strict';

var EultDataTables = (function () {
    var initTable = function (selector) {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') {
            return;
        }

        var $ = jQuery;
        var targetSelector = selector || 'table.kt-datatable-default, table.table-hover:not(.no-datatable)';

        $(targetSelector).each(function () {
            var $table = $(this);

            // 1. Lewati jika sudah diinisialisasi sebagai DataTable
            if ($.fn.DataTable.isDataTable($table)) {
                return;
            }

            // 2. Lewati tabel khusus: dashboard widget, modal form, print view, atau tabel tanpa thead
            if (
                $table.hasClass('no-datatable') ||
                $table.closest('#modal-action-dialog').length > 0 ||
                $table.closest('.modal').length > 0 ||
                $table.find('thead').length === 0
            ) {
                return;
            }

            // 2b. Lewati tabel yang memiliki input checkbox (seperti matriks hak akses/RBAC atau pemilih massal)
            // agar seluruh opsi tetap terlihat utuh dan tidak terpotong oleh paginasi (UX lebih baik)
            if ($table.find('input[type="checkbox"]').length > 0 || $table.find('.kt-checkbox').length > 0) {
                return;
            }

            // 3. Lewati tabel jika hanya ada satu baris empty state dengan colspan (menghindari DataTables alert)
            var $tbodyRows = $table.find('tbody tr');
            if ($tbodyRows.length === 1 && $tbodyRows.find('td[colspan]').length > 0) {
                return;
            }

            // 4. Konfigurasi kolom otomatis (non-orderable pada No dan Aksi)
            var defs = [];
            var $thFirst = $table.find('thead tr th:first-child');
            var $thLast = $table.find('thead tr th:last-child');

            if ($thFirst.length) {
                var firstText = $thFirst.text().trim().toLowerCase();
                if (firstText === 'no' || firstText === '#' || firstText === 'nomor') {
                    defs.push({
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        width: '45px'
                    });
                }
            }

            if ($thLast.length) {
                var lastText = $thLast.text().trim().toLowerCase();
                if (lastText === 'aksi' || lastText === 'action' || lastText === 'opsi') {
                    defs.push({
                        targets: -1,
                        orderable: false,
                        searchable: false
                    });
                }
            }

            // 4b. Deteksi Fitur Row Grouping (Group Header Rows)
            var isRowGroup = $table.is('#ref_table') || $table.data('row-group') === true || $table.hasClass('table-row-group');
            var groupColumn = 0;
            var drawCallbackHandler = null;

            if (isRowGroup) {
                // Sembunyikan kolom grup dari baris data individual karena sudah diwakili oleh header grup
                defs.push({
                    targets: groupColumn,
                    visible: false
                });

                drawCallbackHandler = function (settings) {
                    var api = this.api();
                    var rows = api.rows({ page: 'current' }).nodes();
                    var last = null;
                    var groupCounts = {};

                    // Hitung jumlah item per grup pada halaman aktif
                    api.column(groupColumn, { page: 'current' }).data().each(function (group) {
                        var text = $('<div>').html(group).text().trim();
                        groupCounts[text] = (groupCounts[text] || 0) + 1;
                    });

                    var visibleColCount = api.columns(':visible').count();

                    api.column(groupColumn, { page: 'current' }).data().each(function (group, i) {
                        var text = $('<div>').html(group).text().trim();
                        if (last !== text) {
                            var count = groupCounts[text] || 1;
                            $(rows).eq(i).before(
                                '<tr class="refsyarat-group-row" style="background-color: #f7f8fa; border-top: 2px solid #ebedf2; border-bottom: 1px solid #ebedf2;">' +
                                    '<td colspan="' + visibleColCount + '" class="py-2 px-3" style="border-left: 4px solid #5d78ff;">' +
                                        '<div class="d-flex align-items-center justify-content-between">' +
                                            '<span class="font-weight-bold text-dark d-flex align-items-center" style="font-size: 12.5px; letter-spacing: 0.2px;">' +
                                                '<i class="flaticon2-layers-1 text-primary mr-2" style="font-size: 14px;"></i> ' +
                                                text +
                                            '</span>' +
                                            '<span class="badge badge-secondary font-weight-bold text-muted" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; background: #ffffff; border: 1px solid #e2e5ec;">' +
                                                count + ' Dokumen Persyaratan' +
                                            '</span>' +
                                        '</div>' +
                                    '</td>' +
                                '</tr>'
                            );
                            last = text;
                        }
                    });
                };
            }

            // 5. Inisialisasi DataTable dengan opsi resmi Metronic v6
            try {
                $table.DataTable({
                    responsive: isRowGroup ? false : true,
                    pageLength: isRowGroup ? 25 : 10,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'Semua']
                    ],
                    order: isRowGroup ? [[groupColumn, 'asc']] : [],
                    columnDefs: defs,
                    drawCallback: drawCallbackHandler,
                    language: {
                        emptyTable: 'Belum ada data yang tersedia di tabel ini',
                        zeroRecords: 'Tidak ditemukan data yang sesuai kriteria pencarian',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ total data',
                        infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                        infoFiltered: '(disaring dari _MAX_ total data)',
                        lengthMenu: 'Tampilkan _MENU_ data',
                        loadingRecords: 'Memuat data...',
                        processing: 'Sedang memproses...',
                        search: 'Cari Data:',
                        searchPlaceholder: isRowGroup ? 'Cari layanan atau berkas...' : 'Ketik kata kunci...',
                        paginate: {
                            first: '<i class="la la-angle-double-left"></i>',
                            last: '<i class="la la-angle-double-right"></i>',
                            next: '<i class="la la-angle-right"></i>',
                            previous: '<i class="la la-angle-left"></i>'
                        }
                    },
                    dom:
                        "<'row align-items-center mb-3'<'col-sm-12 col-md-6 d-flex align-items-center'l><'col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start mt-2 mt-md-0'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row align-items-center mt-3'<'col-sm-12 col-md-5 text-muted'i><'col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center mt-2 mt-md-0'p>>"
                });
            } catch (err) {
                console.warn('[EultDataTables] Gagal inisialisasi tabel:', err);
            }
        });
    };

    return {
        init: function () {
            initTable();

            // Dengarkan event AJAX complete untuk tabel yang dirender secara dinamis
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ajaxComplete(function () {
                    setTimeout(function () {
                        initTable('#response table.table-hover:not(.no-datatable), #response_add table.table-hover:not(.no-datatable)');
                    }, 100);
                });
            }
        },
        initSelector: function (selector) {
            initTable(selector);
        }
    };
})();

// Jalankan otomatis saat DOM siap
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function () {
        EultDataTables.init();
    });
}
