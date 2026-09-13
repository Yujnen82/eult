"use strict";

// Definisi FormCustom untuk Hak Akses Modul
var FormCustom = function() {

    var updateCounter = function() {
        var total = $('.check-modul-item').length;
        var checked = $('.check-modul-item:checked').length;
        $('#selected_count').text(checked);
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
            $('.check-modul-item').prop('checked', isChecked);
            updateCounter();
        });

        $('#btn_toggle_all').off('click').on('click', function(e) {
            e.preventDefault();
            var total = $('.check-modul-item').length;
            var checked = $('.check-modul-item:checked').length;
            var shouldCheck = (checked < total);
            $('.check-modul-item').prop('checked', shouldCheck);
            $('#check_all_modul').prop('checked', shouldCheck);
            updateCounter();
        });

        $(document).off('change', '.check-modul-item').on('change', '.check-modul-item', function() {
            updateCounter();
        });

        updateCounter();
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
        }
    };
}();

jQuery(document).ready(function() {
    FormCustom.init();
});