"use strict";

// Definisi FormCustom untuk Hak Akses Modul
var FormCustom = function() {

    var updateCounter = function() {
        var total = $('.check-modul-item').length;
        var checked = $('.check-modul-item:checked').length;
        $('#selected_count').text(checked);
        $('#selected_count_badge').text(checked);

        if (total > 0 && checked === total) {
            $('#check_all_modul').prop('checked', true);
            $('#btn_toggle_all').html('<i class="flaticon2-cross"></i> Batal Pilih Semua');
        } else {
            $('#check_all_modul').prop('checked', false);
            $('#btn_toggle_all').html('<i class="flaticon2-check-mark"></i> Pilih Semua');
        }
    };

    var initCheckboxes = function() {
        $('#check_all_modul').off('change').on('change', function() {
            var isChecked = $(this).is(':checked');
            // Jika ada filter pencarian, hanya ubah baris yang tampak
            var $targetItems = $('.check-modul-item:visible').length > 0 && $('#quick_search_modul').val().trim() !== ''
                ? $('.check-modul-item:visible')
                : $('.check-modul-item');

            $targetItems.prop('checked', isChecked);
            $targetItems.each(function() {
                $(this).closest('tr').toggleClass('table-row-selected', $(this).is(':checked'));
            });
            updateCounter();
        });

        $('#btn_toggle_all').off('click').on('click', function(e) {
            e.preventDefault();
            var total = $('.check-modul-item').length;
            var checked = $('.check-modul-item:checked').length;
            var shouldCheck = (checked < total);

            $('.check-modul-item').prop('checked', shouldCheck);
            $('#check_all_modul').prop('checked', shouldCheck);
            $('.check-modul-item').each(function() {
                $(this).closest('tr').toggleClass('table-row-selected', shouldCheck);
            });
            updateCounter();
        });

        $(document).off('change', '.check-modul-item').on('change', '.check-modul-item', function() {
            $(this).closest('tr').toggleClass('table-row-selected', $(this).is(':checked'));
            updateCounter();
        });

        // Klik baris untuk mempermudah toggle checkbox
        $(document).off('click', '.matrix-row').on('click', '.matrix-row', function(e) {
            if ($(e.target).is('input, label, span, a, button')) {
                return;
            }
            var $cb = $(this).find('.check-modul-item');
            $cb.prop('checked', !$cb.is(':checked')).trigger('change');
        });

        updateCounter();
    };

    var initQuickSearch = function() {
        $('#quick_search_modul').off('keyup input').on('keyup input', function() {
            var val = $(this).val().toLowerCase().trim();
            var visibleCount = 0;

            $('.matrix-row').each(function() {
                var rowText = $(this).text().toLowerCase();
                var isMatch = rowText.indexOf(val) > -1;
                $(this).toggle(isMatch);
                if (isMatch) visibleCount++;
            });

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

                        // Jika berhasil menyimpan form_custom, muat ulang matriks agar perubahan terkonfirmasi
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

                // Scroll halus ke tabel matriks saat form_show dimuat
                if ($form.attr('id') === 'form_show' && $('#response').length) {
                    $('html, body').animate({
                        scrollTop: Math.max(0, $('#response').offset().top - 135)
                    }, 350);
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
                if ($('.check-modul-item:checked').length === 0) {
                    swal.fire({
                        type: 'warning',
                        title: 'Peringatan',
                        text: 'Pilih minimal 1 modul untuk hak akses role ini.'
                    });
                    return false;
                }
                handleSubmit(form);
                return false;
            }
        });
    };

    return {
        // public functions
        init: function() {
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