"use strict";

var KTValidasifile = function () {
    var tableFiles = null;
    var tableQuarantine = null;

    var initTableFiles = function () {
        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#table-files')) {
            $('#table-files').DataTable().destroy();
        }

        tableFiles = $('#table-files').DataTable({
            responsive: true,
            serverSide: true,
            processing: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250], [10, 25, 50, 100, 250]],
            ajax: {
                url: URL_GET_DATA,
                type: 'POST',
                data: function (d) {
                    d.folder = $('#select-folder').val();
                    d.status = $('input[name="status_filter"]:checked').val() || '';
                },
                error: function (xhr, error, code) {
                    console.error("DataTables AJAX Error:", xhr.responseText);
                }
            },
            columns: [
                {
                    data: 'filename',
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        if (row.status === 'VALID') {
                            return `<label class="kt-checkbox kt-checkbox--single kt-checkbox--solid"><input type="checkbox" disabled class="row-check-file" value="${data}"><span></span></label>`;
                        }
                        return `<label class="kt-checkbox kt-checkbox--single kt-checkbox--solid"><input type="checkbox" class="row-check-file" value="${data}"><span></span></label>`;
                    }
                },
                {
                    data: 'no',
                    orderable: false,
                    className: 'text-center align-middle'
                },
                {
                    data: 'filename',
                    className: 'align-middle font-weight-bold',
                    render: function (data, type, row) {
                        var icon = data.endsWith('.pdf') ? '<i class="la la-file-pdf-o text-danger mr-1"></i>' : '<i class="la la-file-image-o text-primary mr-1"></i>';
                        var tracking = row.tracking_id ? `<br><small class="text-muted">Tracking ID: <b>${row.tracking_id}</b></small>` : '';
                        return `<div>${icon} <span class="text-dark">${data}</span>${tracking}</div>`;
                    }
                },
                {
                    data: 'status',
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        return `<span class="badge ${row.badge_class}">${row.status_label}</span>`;
                    }
                },
                {
                    data: 'filesize_fmt',
                    orderable: false,
                    className: 'text-right align-middle font-weight-bold text-muted'
                },
                {
                    data: 'mtime',
                    orderable: false,
                    className: 'text-center align-middle text-muted'
                },
                {
                    data: 'filename',
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        var folder = row.folder || $('#select-folder').val();
                        var btnPreview = `<button type="button" class="btn btn-sm btn-outline-info btn-icon btn-preview-file" data-file="${data}" data-folder="${folder}" data-quarantine="0" title="Preview"><i class="la la-eye"></i></button>`;
                        var btnQuarantine = '';
                        if (row.status !== 'VALID') {
                            btnQuarantine = `<button type="button" class="btn btn-sm btn-outline-warning btn-icon ml-1 btn-single-quarantine" data-file="${data}" data-folder="${folder}" title="Karantina"><i class="la la-lock"></i></button>`;
                        }
                        return `<div class="d-flex justify-content-center">${btnPreview} ${btnQuarantine}</div>`;
                    }
                }
            ],
            order: [[3, 'desc'], [2, 'asc']],
            drawCallback: function () {
                $('#check-all-files').prop('checked', false);
                updateSelectedFilesCount();
            }
        });
    };

    var initTableQuarantine = function () {
        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#table-quarantine')) {
            $('#table-quarantine').DataTable().destroy();
        }

        tableQuarantine = $('#table-quarantine').DataTable({
            responsive: true,
            serverSide: false,
            processing: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
            ajax: {
                url: URL_GET_QUARANTINE,
                type: 'POST',
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columns: [
                {
                    data: 'filename',
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        return `<label class="kt-checkbox kt-checkbox--single kt-checkbox--solid"><input type="checkbox" class="row-check-quarantine" value="${data}" data-folder="${row.folder}"><span></span></label>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'filename',
                    className: 'align-middle font-weight-bold',
                    render: function (data, type, row) {
                        var icon = data.endsWith('.pdf') ? '<i class="la la-file-pdf-o text-danger mr-1"></i>' : '<i class="la la-file-image-o text-primary mr-1"></i>';
                        var sha = row.sha256 ? `<br><small class="text-muted font-monospace" style="font-size: 10px;">SHA256: ${row.sha256.substring(0, 16)}...</small>` : '';
                        return `<div>${icon} <span class="text-dark">${data}</span>${sha}</div>`;
                    }
                },
                {
                    data: 'folder',
                    className: 'text-center align-middle',
                    render: function (data) {
                        return `<span class="badge badge-secondary">${data}</span>`;
                    }
                },
                {
                    data: 'filesize_fmt',
                    className: 'text-right align-middle font-weight-bold'
                },
                {
                    data: 'quarantined_at',
                    className: 'text-center align-middle text-muted'
                },
                {
                    data: 'quarantined_by',
                    className: 'text-center align-middle',
                    render: function (data) {
                        return `<span class="badge badge-dark">${data || 'ADMIN'}</span>`;
                    }
                },
                {
                    data: 'filename',
                    orderable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row) {
                        var btnPreview = `<button type="button" class="btn btn-sm btn-outline-info btn-icon btn-preview-file" data-file="${data}" data-folder="${row.folder}" data-quarantine="1" title="Preview"><i class="la la-eye"></i></button>`;
                        var btnRestore = `<button type="button" class="btn btn-sm btn-outline-success btn-icon ml-1 btn-single-restore" data-file="${data}" data-folder="${row.folder}" title="Pulihkan"><i class="la la-undo"></i></button>`;
                        var btnDelete = `<button type="button" class="btn btn-sm btn-outline-danger btn-icon ml-1 btn-single-delete" data-file="${data}" data-folder="${row.folder}" title="Hapus Permanen"><i class="la la-trash"></i></button>`;
                        return `<div class="d-flex justify-content-center">${btnPreview} ${btnRestore} ${btnDelete}</div>`;
                    }
                }
            ],
            order: [[5, 'desc']],
            drawCallback: function () {
                $('#check-all-quarantine').prop('checked', false);
                updateSelectedQuarantineCount();
            }
        });
    };

    var refreshStats = function () {
        $.ajax({
            url: URL_GET_STATS,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status && res.data) {
                    var s = res.data;
                    $('#stat-total-disk').text(s.ticketing_disk_total);
                    $('#stat-valid').text(s.valid_count);
                    $('#stat-legacy').text(s.legacy_count);
                    $('#stat-orphan').text(s.orphan_count);
                    $('#stat-quarantine').text(s.quarantined_count);
                    $('#badge-quarantine-tab').text(s.quarantined_count);
                    $('#count-unmatched').text(s.unmatched_total);
                }
            }
        });
    };

    var refreshManifest = function () {
        $.ajax({
            url: URL_GET_MANIFEST,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status && res.data) {
                    $('#json-manifest-viewer').text(JSON.stringify(res.data, null, 2));
                }
            }
        });
    };

    var updateSelectedFilesCount = function () {
        var count = $('.row-check-file:checked').length;
        $('#count-selected').text(count);
        $('#btn-quarantine-selected').prop('disabled', count === 0);
    };

    var updateSelectedQuarantineCount = function () {
        var count = $('.row-check-quarantine:checked').length;
        $('#count-quarantine-selected').text(count);
        $('#btn-restore-selected').prop('disabled', count === 0);
        $('#btn-delete-quarantine-selected').prop('disabled', count === 0);
    };

    var handleEvents = function () {
        // Check all files
        $('#check-all-files').on('change', function () {
            var checked = $(this).is(':checked');
            $('.row-check-file:not(:disabled)').prop('checked', checked);
            updateSelectedFilesCount();
        });

        $(document).on('change', '.row-check-file', function () {
            updateSelectedFilesCount();
        });

        // Check all quarantine
        $('#check-all-quarantine').on('change', function () {
            var checked = $(this).is(':checked');
            $('.row-check-quarantine').prop('checked', checked);
            updateSelectedQuarantineCount();
        });

        $(document).on('change', '.row-check-quarantine', function () {
            updateSelectedQuarantineCount();
        });

        // Folder select change
        $('#select-folder').on('change', function () {
            tableFiles.ajax.reload();
        });

        // Status filter buttons
        $('.filter-status-btn').on('click', function () {
            $('.filter-status-btn').removeClass('active');
            $(this).addClass('active');
            setTimeout(function () {
                tableFiles.ajax.reload();
            }, 50);
        });

        // Refresh all button
        $('#btn-refresh-all').on('click', function () {
            tableFiles.ajax.reload();
            tableQuarantine.ajax.reload();
            refreshStats();
            refreshManifest();
            swal.fire({
                position: 'top-right',
                type: 'success',
                title: 'Data berhasil diperbarui',
                showConfirmButton: false,
                timer: 1200
            });
        });

        // Refresh manifest button
        $('#btn-refresh-manifest').on('click', function () {
            refreshManifest();
        });

        // Preview file modal
        $(document).on('click', '.btn-preview-file', function () {
            var filename = $(this).data('file');
            var folder = $(this).data('folder');
            var isQuarantine = $(this).data('quarantine');

            var previewUrl = `${URL_PREVIEW}/${folder}/${filename}/${isQuarantine}`;
            $('#preview-filename').text(filename);
            $('#iframe-doc-preview').attr('src', previewUrl);
            $('#btn-open-external').attr('href', previewUrl);
            $('#modal-preview-doc').modal('show');
        });

        // Single quarantine
        $(document).on('click', '.btn-single-quarantine', function () {
            var file = $(this).data('file');
            var folder = $(this).data('folder');

            swal.fire({
                title: 'Karantina File Ini?',
                text: `File "${file}" akan dipindahkan ke folder karantina.`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffb822',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Karantina!'
            }).then(function (result) {
                if (result.value) {
                    doQuarantine([file], folder, 'selected');
                }
            });
        });

        // Quarantine selected
        $('#btn-quarantine-selected').on('click', function () {
            var selected = [];
            $('.row-check-file:checked').each(function () {
                selected.push($(this).val());
            });

            if (selected.length === 0) return;

            var folder = $('#select-folder').val();
            swal.fire({
                title: `Karantina ${selected.length} File Terpilih?`,
                text: 'File akan dipindahkan ke folder _quarantine/ dan dicatat dalam manifest.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffb822',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: `Ya, Karantina ${selected.length} File!`
            }).then(function (result) {
                if (result.value) {
                    doQuarantine(selected, folder, 'selected');
                }
            });
        });

        // Quarantine ALL unmatched
        $('#btn-quarantine-all-unmatched').on('click', function () {
            var folder = $('#select-folder').val();
            var unmatchedCount = $('#count-unmatched').text();

            swal.fire({
                title: 'Karantina Semua File Tidak Sesuai?',
                html: `Akan memindahkan <b>semua file Orphan dan Backup Lama</b> (${unmatchedCount} file) ke folder karantina (<code>upload_file/_quarantine/</code>).<br><br>File dapat dipulihkan kapan saja via tab <b>Area Karantina</b>.`,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fd3995',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Karantina Semua!'
            }).then(function (result) {
                if (result.value) {
                    doQuarantine([], folder, 'all_unmatched');
                }
            });
        });

        // Single restore
        $(document).on('click', '.btn-single-restore', function () {
            var file = $(this).data('file');
            var folder = $(this).data('folder');

            swal.fire({
                title: 'Pulihkan File?',
                text: `File "${file}" akan dikembalikan ke direktori upload asal (${folder}/).`,
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#34bfa3',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Pulihkan!'
            }).then(function (result) {
                if (result.value) {
                    doRestore([file], folder, 'selected');
                }
            });
        });

        // Restore selected
        $('#btn-restore-selected').on('click', function () {
            var selected = [];
            var folder = 'ticketing';
            $('.row-check-quarantine:checked').each(function () {
                selected.push($(this).val());
                folder = $(this).data('folder') || folder;
            });

            if (selected.length === 0) return;

            swal.fire({
                title: `Pulihkan ${selected.length} File Terpilih?`,
                text: 'File akan dikembalikan ke folder upload asalnya.',
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#34bfa3',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Pulihkan!'
            }).then(function (result) {
                if (result.value) {
                    doRestore(selected, folder, 'selected');
                }
            });
        });

        // Restore all
        $('#btn-restore-all').on('click', function () {
            swal.fire({
                title: 'Pulihkan Semua File Terkarantina?',
                text: 'Semua file di area karantina akan dipulihkan kembali ke folder asalnya.',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#34bfa3',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Pulihkan Semua!'
            }).then(function (result) {
                if (result.value) {
                    doRestore([], 'ticketing', 'all');
                }
            });
        });

        // Single delete permanent
        $(document).on('click', '.btn-single-delete', function () {
            var file = $(this).data('file');
            var folder = $(this).data('folder');

            swal.fire({
                title: 'Hapus Permanen File Ini?',
                text: `PERINGATAN: File "${file}" akan dihapus permanen dan tidak dapat dipulihkan!`,
                type: 'error',
                showCancelButton: true,
                confirmButtonColor: '#fd3995',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Hapus Permanen!'
            }).then(function (result) {
                if (result.value) {
                    doDelete([file], folder);
                }
            });
        });

        // Delete selected
        $('#btn-delete-quarantine-selected').on('click', function () {
            var selected = [];
            var folder = 'ticketing';
            $('.row-check-quarantine:checked').each(function () {
                selected.push($(this).val());
                folder = $(this).data('folder') || folder;
            });

            if (selected.length === 0) return;

            swal.fire({
                title: `HAPUS PERMANEN ${selected.length} File Terpilih?`,
                text: 'TINDAKAN TIDAK DAPAT DIBATALKAN! File akan dihapus permanen dari sistem.',
                type: 'error',
                showCancelButton: true,
                confirmButtonColor: '#fd3995',
                cancelButtonColor: '#a1a8c3',
                confirmButtonText: 'Ya, Hapus Permanen!'
            }).then(function (result) {
                if (result.value) {
                    doDelete(selected, folder);
                }
            });
        });
    };

    var doQuarantine = function (files, folder, mode) {
        KTApp.blockPage({
            overlayColor: '#000000',
            type: 'v2',
            state: 'primary',
            message: 'Memindahkan file ke folder karantina...'
        });

        $.ajax({
            url: URL_KARANTINA,
            type: 'POST',
            dataType: 'json',
            data: { files: files, folder: folder, mode: mode },
            success: function (res) {
                KTApp.unblockPage();
                if (res.status) {
                    swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        type: 'success'
                    });
                    tableFiles.ajax.reload();
                    tableQuarantine.ajax.reload();
                    refreshStats();
                    refreshManifest();
                } else {
                    swal.fire({
                        title: 'Gagal!',
                        text: res.message,
                        type: 'error'
                    });
                }
            },
            error: function () {
                KTApp.unblockPage();
                swal.fire('Error', 'Terjadi kesalahan pada server.', 'error');
            }
        });
    };

    var doRestore = function (files, folder, mode) {
        KTApp.blockPage({
            overlayColor: '#000000',
            type: 'v2',
            state: 'primary',
            message: 'Memulihkan file dari karantina...'
        });

        $.ajax({
            url: URL_RESTORE,
            type: 'POST',
            dataType: 'json',
            data: { files: files, folder: folder, mode: mode },
            success: function (res) {
                KTApp.unblockPage();
                if (res.status) {
                    swal.fire({
                        title: 'Dipulihkan!',
                        text: res.message,
                        type: 'success'
                    });
                    tableFiles.ajax.reload();
                    tableQuarantine.ajax.reload();
                    refreshStats();
                    refreshManifest();
                } else {
                    swal.fire({
                        title: 'Gagal!',
                        text: res.message,
                        type: 'error'
                    });
                }
            },
            error: function () {
                KTApp.unblockPage();
                swal.fire('Error', 'Terjadi kesalahan pada server.', 'error');
            }
        });
    };

    var doDelete = function (files, folder) {
        KTApp.blockPage({
            overlayColor: '#000000',
            type: 'v2',
            state: 'primary',
            message: 'Menghapus file permanen...'
        });

        $.ajax({
            url: URL_DELETE,
            type: 'POST',
            dataType: 'json',
            data: { files: files, folder: folder },
            success: function (res) {
                KTApp.unblockPage();
                if (res.status) {
                    swal.fire({
                        title: 'Dihapus!',
                        text: res.message,
                        type: 'success'
                    });
                    tableQuarantine.ajax.reload();
                    refreshStats();
                    refreshManifest();
                } else {
                    swal.fire({
                        title: 'Gagal!',
                        text: res.message,
                        type: 'error'
                    });
                }
            },
            error: function () {
                KTApp.unblockPage();
                swal.fire('Error', 'Terjadi kesalahan pada server.', 'error');
            }
        });
    };

    return {
        init: function () {
            initTableFiles();
            initTableQuarantine();
            refreshManifest();
            handleEvents();
        }
    };
}();

jQuery(document).ready(function () {
    KTValidasifile.init();
});
