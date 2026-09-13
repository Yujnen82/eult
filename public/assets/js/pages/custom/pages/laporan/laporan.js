const eultTerimaHtml = function (data) {
    if (data && typeof data === 'object' && typeof data.response === 'string') {
        return data.response;
    }
    return data;
};

const KTLaporan = function () {
    const main_form = $('#main_form');
    const responseEl = () => $('#response');

    const initHandleWidgets = () => {
        const picker = $('#kt_daterangepicker_2');
        if (picker.length && !picker.data('daterangepicker')) {
            picker.daterangepicker({
                buttonClasses: ' btn',
                applyClass: 'btn-primary',
                cancelClass: 'btn-secondary',
                autoUpdateInput: false,
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: ' / ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    fromLabel: 'Dari',
                    toLabel: 'Sampai',
                    customRangeLabel: 'Kustom',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
                }
            }, function (start, end) {
                $('#kt_daterangepicker_2 .form-control').val(`${start.format('DD-MM-YYYY')} / ${end.format('DD-MM-YYYY')}`).trigger('change');
            });

            picker.on('apply.daterangepicker', function(ev, picker) {
                $(this).find('.form-control').val(picker.startDate.format('DD-MM-YYYY') + ' / ' + picker.endDate.format('DD-MM-YYYY')).trigger('change');
            });
            picker.on('cancel.daterangepicker', function(ev, picker) {
                $(this).find('.form-control').val('').trigger('change');
            });
        }
        $('.m-select2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            $el.select2({
                placeholder: $el.attr('data-placeholder') || 'Pilih unit layanan',
                allowClear: true,
                width: '100%'
            });
        });
    };

    const bindExport = () => {
        $(document).off('click.laporanExport', '#export').on('click.laporanExport', '#export', function (e) {
            e.preventDefault();
            const valName = $(this).attr('data-name') || $(this).attr('val_name') || 'laporan';
            const table = $('#table_export');
            if (!table.length || table.find('tbody tr').length === 0 || table.find('td[colspan]').length) {
                return;
            }

            if (typeof $.fn.tableExport !== 'function') {
                if (typeof swal !== 'undefined') {
                    swal.fire({
                        type: 'error',
                        title: 'Fitur Belum Siap',
                        text: 'Modul ekspor spreadsheet belum selesai dimuat. Silakan muat ulang halaman.'
                    });
                }
                return;
            }

            table.tableExport({
                fileName: 'rekap_' + valName,
                type: 'excel'
            });
        });
    };

    const setButtonBusy = (btn, busy) => {
        if (!btn.length) {
            return;
        }
        if (busy) {
            btn.prop('disabled', true).addClass('kt-spinner kt-spinner--right kt-spinner--md kt-spinner--light');
            if (typeof KTApp !== 'undefined' && KTApp.progress) {
                KTApp.progress(btn);
            }
        } else {
            btn.prop('disabled', false).removeClass('kt-spinner kt-spinner--right kt-spinner--md kt-spinner--light');
            if (typeof KTApp !== 'undefined' && KTApp.unprogress) {
                KTApp.unprogress(btn);
            }
        }
    };

    const renderError = (pesan) => {
        responseEl().removeClass('response-hide').addClass('response-show').html(
            '<div class="kt-portlet mb-0"><div class="kt-portlet__body text-center py-5">' +
            '<i class="flaticon2-warning d-block mb-3" style="font-size: 2rem; color: #fd397a;"></i>' +
            '<div class="font-weight-bold text-dark mb-1">Laporan gagal dimuat</div>' +
            '<span class="text-muted" style="font-size: 13px;">' + pesan + '</span>' +
            '</div></div>'
        );
    };

    const initHandleShow = () => {
        const btnShow = $('#btn_show');
        const formShow = $('#form_show');
        const rules = {
            rentangTanggal: 'required'
        };
        const messages = {
            rentangTanggal: 'Silakan pilih rentang tanggal'
        };
        if ($('[name="layanan"]').length) {
            rules.layanan = 'required';
            messages.layanan = 'Silakan pilih unit layanan';
        }
        formShow.validate({
            ignore: [],
            rules,
            messages,
            submitHandler: function (_form, event) {
                if (event && event.preventDefault) {
                    event.preventDefault();
                }
                setButtonBusy(btnShow, true);
                $.ajax({
                    type: formShow.attr('method'),
                    url: formShow.attr('action'),
                    data: formShow.serialize(),
                    success: data => {
                        const html = eultTerimaHtml(data);
                        const target = responseEl();
                        target.removeClass('response-hide').html(html).addClass('response-show');
                        if (main_form.find('#response')[0] && typeof KTUtil !== 'undefined') {
                            KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        }
                        const node = target[0];
                        if (node) {
                            const isReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                            if (!isReducedMotion && typeof node.scrollIntoView === 'function') {
                                node.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            } else if (!isReducedMotion) {
                                var targetTop = Math.max(0, $(node).offset().top - 135);
                                $('html, body').stop().animate({ scrollTop: targetTop }, 400, 'swing');
                            } else {
                                window.scrollTo(0, Math.max(0, $(node).offset().top - 135));
                            }
                        }
                        bindExport();
                    },
                    error: () => {
                        renderError('Tidak dapat mengambil data laporan. Periksa koneksi, lalu coba tampilkan ulang.');
                    },
                    complete: () => {
                        setButtonBusy(btnShow, false);
                    }
                });
                return false;
            }
        });
    };

    return {
        init: function () {
            initHandleWidgets();
            initHandleShow();
            bindExport();
        }
    };
}();

KTUtil.ready(function () {
    KTLaporan.init();
});
