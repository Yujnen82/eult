"use strict";

// Definisi FormCustom untuk Hak Akses Unit
var FormCustom = function() {

    var updateCounter = function() {
        var total = $('.check-unit-item').length;
        var checked = $('.check-unit-item:checked').length;
        $('#selected_count').text(checked);
        $('#selected_count_badge').text(checked);

        if (total > 0 && checked === total) {
            $('#check_all_unit').prop('checked', true);
            $('#btn_toggle_all').html('<i class="flaticon2-cross"></i> Batal Pilih Semua');
        } else {
            $('#check_all_unit').prop('checked', false);
            $('#btn_toggle_all').html('<i class="flaticon2-check-mark"></i> Pilih Semua');
        }
    };

    var syncHomeWithUnit = function($unitCheckbox) {
        var $home = $unitCheckbox.closest('tr').find('.check-ishome-item');
        if (!$unitCheckbox.is(':checked')) {
            $home.prop('checked', false);
        }
    };

    var initCheckboxes = function() {
        $('#check_all_unit').off('change').on('change', function() {
            var isChecked = $(this).is(':checked');
            var $targetItems = $('.check-unit-item:visible').length > 0 && $('#quick_search_unit').val().trim() !== ''
                ? $('.check-unit-item:visible')
                : $('.check-unit-item');

            $targetItems.prop('checked', isChecked);
            $targetItems.each(function() {
                $(this).closest('tr').toggleClass('table-row-selected', $(this).is(':checked'));
                syncHomeWithUnit($(this));
            });
            updateCounter();
        });

        $('#btn_toggle_all').off('click').on('click', function(e) {
            e.preventDefault();
            var total = $('.check-unit-item').length;
            var checked = $('.check-unit-item:checked').length;
            var shouldCheck = (checked < total);

            $('.check-unit-item').prop('checked', shouldCheck);
            $('#check_all_unit').prop('checked', shouldCheck);
            $('.check-unit-item').each(function() {
                $(this).closest('tr').toggleClass('table-row-selected', shouldCheck);
                syncHomeWithUnit($(this));
            });
            updateCounter();
        });

        $(document).off('change', '.check-unit-item').on('change', '.check-unit-item', function() {
            $(this).closest('tr').toggleClass('table-row-selected', $(this).is(':checked'));
            syncHomeWithUnit($(this));
            updateCounter();
        });

        $(document).off('change', '.check-ishome-item').on('change', '.check-ishome-item', function() {
            if ($(this).is(':checked')) {
                var $unit = $(this).closest('tr').find('.check-unit-item');
                if (!$unit.is(':checked')) {
                    $unit.prop('checked', true).trigger('change');
                }
            }
        });

        $(document).off('click', '.matrix-row').on('click', '.matrix-row', function(e) {
            if ($(e.target).is('input, label, span, a, button')) {
                return;
            }
            var $cb = $(this).find('.check-unit-item');
            $cb.prop('checked', !$cb.is(':checked')).trigger('change');
        });

        updateCounter();
    };

    var initQuickSearch = function() {
        $('#quick_search_unit').off('keyup input').on('keyup input', function() {
            var val = $(this).val().toLowerCase().trim();
            var visibleCount = 0;

            $('.matrix-row').each(function() {
                var rowText = $(this).text().toLowerCase();
                var isMatch = rowText.indexOf(val) > -1;
                $(this).toggle(isMatch);
                if (isMatch) visibleCount++;
            });

            var $tableContainer = $('#table_hakakses_unit').parent();
            if ($tableContainer.length && $tableContainer.scrollTop() > 0) {
                $tableContainer[0].scrollTo({ top: 0, behavior: 'smooth' });
            }

            if (visibleCount === 0 && $('.matrix-row').length > 0) {
                $('#empty_search_row').show();
            } else {
                $('#empty_search_row').hide();
            }
        });
    };

    var handleSubmit = function(form) {
        var $form = $(form);
        var button = $form.find('button[type="submit"]');
        var originalHtml = button.html();

        button.prop("disabled", true).addClass('disabled');
        button.html('<i class="fa fa-spinner fa-spin mr-1"></i> Sedang Memproses...');

        $.ajax({
            type: $form.attr('method'),
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function(data) {
                try {
                    var res = (typeof data === 'string' ? JSON.parse(data) : data);
                    if (res.status === 'success') {
                        swal.fire({
                            position: "top-right",
                            type: "success",
                            title: res.message,
                            showConfirmButton: false,
                            timer: 1500
                        });

                        if ($form.attr('id') === 'form_custom') {
                            button.prop("disabled", false).removeClass('disabled');
                            button.html(originalHtml);
                            $("#form_show").submit();
                            return;
                        }
                    } else {
                        swal.fire({
                            type: res.status || 'error',
                            title: 'Pemberitahuan',
                            text: res.message
                        });
                    }

                    if (res.response) {
                        $('#response').fadeIn('slow').html(res.response);
                    }
                } catch (err) {
                    $('#response').fadeIn('slow').html(data);
                }

                button.prop("disabled", false).removeClass('disabled');
                button.html(originalHtml);
                FormCustom.init();

                if ($form.attr('id') === 'form_show') {
                    var responseEl = document.getElementById('response');
                    if (responseEl) {
                        var isReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        if (!isReducedMotion && typeof responseEl.scrollIntoView === 'function') {
                            responseEl.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        } else if (!isReducedMotion) {
                            var targetTop = Math.max(0, $(responseEl).offset().top - 135);
                            $('html, body').stop().animate({ scrollTop: targetTop }, 400, 'swing');
                        } else {
                            window.scrollTo(0, Math.max(0, $(responseEl).offset().top - 135));
                        }
                    }
                }
            },
            error: function() {
                button.prop("disabled", false).removeClass('disabled');
                button.html(originalHtml);
                swal.fire({
                    type: 'error',
                    title: 'Terjadi Kesalahan',
                    text: 'Gagal menghubungi server untuk memproses data.'
                });
            }
        });
    };

    var handleSubmitFormShow = function() {
        $("#form_show").validate({
            rules: {
                hakakses: {
                    required: true
                }
            },
            messages: {
                hakakses: {
                    required: "Silakan pilih grup pengguna terlebih dahulu."
                }
            },
            submitHandler: function(form) {
                handleSubmit(form);
                return false;
            }
        });
    };

    var handleSubmitForm = function() {
        $("#form_custom").validate({
            rules: {},
            submitHandler: function(form) {
                if ($('.check-unit-item:checked').length === 0) {
                    swal.fire({
                        type: 'warning',
                        title: 'Peringatan',
                        text: 'Pilih minimal 1 unit untuk hak akses role ini.'
                    });
                    return false;
                }
                handleSubmit(form);
                return false;
            }
        });
    };

    var initSelect2 = function() {
        if ($.fn.select2) {
            $('#select_role').each(function() {
                var $el = $(this);
                if (!$el.hasClass('select2-hidden-accessible')) {
                    $el.select2({
                        placeholder: $el.attr('data-placeholder') || '-- Pilih Grup Pengguna / Role --',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }
    };

    return {
        init: function() {
            initSelect2();
            handleSubmitFormShow();
            handleSubmitForm();
            initCheckboxes();
            initQuickSearch();
        }
    };
}();

jQuery(document).ready(function() {
    FormCustom.init();
});
