"use strict";

// Class Definition
var KTLoginGeneral = function () {
    const initHandleWidgets = () => {
        $('.m-select2').select2({
            width: '100%',
            language: {
                noResults: function () {
                    return "Tidak ada data yang sesuai";
                },
                searching: function () {
                    return "Mencari...";
                }
            }
        });
    }
    // Function untuk memperbarui CAPTCHA
    var refreshCaptcha = function (captchaCode) {
        if (captchaCode) {
            $('.captcha-display').text(captchaCode);
            $('input[name="captcha"]').val(''); // Reset nilai input captcha
        }
    }

    var login = $('#kt_login');

    var showErrorMsg = function (form, type, msg) {
        var alert = $('<div class="alert alert-bold alert-solid-' + type + ' alert-dismissible kt-login__error" role="alert">\
         <div class="alert-text">' + msg + '</div>\
         <div class="alert-close">\
         <i class="flaticon2-cross kt-icon-sm" data-dismiss="alert"></i>\
         </div>\
         </div>');

        form.find('.kt-login__error').remove();
        alert.prependTo(form);
        //alert.animateClass('fadeIn animated');
        KTUtil.animateClass(alert[0], 'fadeIn animated');
        alert.find('span').html(msg);
    }
    var displayTrackForm = function () {
        $('#kt-login--track').show();
        $('#kt-login--create').hide();
        $('#kt-login--signin').hide();
        login.removeClass('kt-login__create');
        login.removeClass('kt-login__signin');
        login.addClass('kt-login__track');
        //login.find('.kt-login--forgot').animateClass('flipInX animated');
        KTUtil.animateClass(login.find('.kt-login__track')[0], 'flipInX animated');

    }
    var displaySignInForm = function () {
        $('#kt-login--track').hide();
        $('#kt-login--create').hide();
        $('#kt-login--signin').show();

        login.removeClass('kt-login__create');
        login.removeClass('kt-login__track');
        login.addClass('kt-login__signin');
        KTUtil.animateClass(login.find('.kt-login__signin')[0], 'flipInX animated');
        //login.find('.kt-login__signin').animateClass('flipInX animated');
    }

    var displayCreateForm = function () {
        $('#kt-login--track').hide();
        $('#kt-login--create').show();
        $('#kt-login--signin').hide();

        login.removeClass('kt-login__signin');
        login.removeClass('kt-login__track');
        login.addClass('kt-login__create');
        KTUtil.animateClass(login.find('.kt-login__create')[0], 'flipInX animated');
        //login.find('.kt-login__signin').animateClass('flipInX animated');
    }

    $('#kt_tracking').click(function (e) {
        e.preventDefault();
        displayTrackForm();
    });

    $('#kt_signin').click(function (e) {
        e.preventDefault();
        displaySignInForm();
    });

    $('#kt_create').click(function (e) {
        e.preventDefault();
        displayCreateForm();
    });

    $('#ticketCategories').on('change', e => {
        e.preventDefault();
        const id = e.currentTarget.value;
        loadSyarat(id);
    });

    var loadLayanan = function (istrue, layananId) {
        var $categories = $('#ticketCategories');
        if (istrue) {
            $categories.prop('disabled', false).attr('data-placeholder', 'Pilih Kategori Layanan Kampus');
            $categories.html('<option value="">Memuat kategori layanan...</option>');
            $categories.select2({
                width: '100%',
                language: {
                    noResults: function () {
                        return "Tidak ada data yang sesuai";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });
            $.ajax({
                type: 'POST',
                url: '/login/getLayanan',
                data: {
                    id: istrue,
                    layananId: layananId
                },
                success: data => {
                    $categories.html(data).trigger('change');
                },
                error: () => {
                    $categories.html('<option value="">Gagal memuat layanan. Silakan coba lagi.</option>').trigger('change');
                }
            });
        } else {
            $categories.prop('disabled', true).attr('data-placeholder', 'Masukkan nomor identitas terlebih dahulu...');
            $categories.html('<option value="">Masukkan nomor identitas terlebih dahulu...</option>');
            $categories.select2({
                width: '100%',
                language: {
                    noResults: function () {
                        return "Tidak ada data yang sesuai";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            }).trigger('change');
            $('#syarat').html('');
        }
    }

    let loadSyarat = id => {
        $('#syarat').html('');
        $.ajax({
            type: 'POST',
            url: '/login/getSyarat',
            data: {
                id: id
            },
            success: data => {
                $('#syarat').html(data);
            }
        });
    }

    var checkIdentitas = function () {
        var val_id = $('#ticketIdentitas').val();
        $("[name='ticketName']").val('');
        $("[name='ticketName']").attr('readonly', false);
        if (val_id.length == 10 || val_id.length == 16 || val_id.length == 18 || val_id.length == 14) {
            $('#identitas-feedback').html('<span class="text-primary"><i class="flaticon2-refresh kt-spinner kt-spinner--sm kt-spinner--primary"></i> Memeriksa data...</span>');
            $.ajax({
                type: 'POST',
                url: '/login/getIdentitas',
                data: {
                    id: val_id
                },
                dataType: 'json',
                success: res => {
                    if (res.status == true) {
                        if (res.umum == true) {
                            $("[name='ticketName']").attr('readonly', false);
                            $('#identitas-feedback').html('<span class="text-info font-weight-bold"><i class="flaticon2-information"></i> Pemohon Umum — isi nama manual</span>');
                        } else {
                            $("[name='ticketName']").attr('readonly', true);
                            if (res.ismhs == true) {
                                $("[name='ticketName']").val(res.datamhs.name);
                            } else {
                                $("[name='ticketName']").val(res.datapegawai.name);
                            }
                            $("[name='ticketName']").trigger('change');
                            $('#identitas-feedback').html('<span class="text-success font-weight-bold"><i class="flaticon2-check-mark"></i> Data Ditemukan</span>');
                        }
                        loadLayanan(res.status);
                    } else {
                        $('#identitas-feedback').html('<span class="text-danger font-weight-bold"><i class="flaticon2-cross"></i> Data Tidak Ditemukan</span>');
                        loadLayanan(false);
                    }
                },
                error: () => {
                    $('#identitas-feedback').html('<span class="text-danger small">Gagal memeriksa data</span>');
                    loadLayanan(false);
                }
            });
        } else {
            if (val_id.length > 0) {
                $('#identitas-feedback').html('<span class="text-muted">(' + val_id.length + ' digit)</span>');
            } else {
                $('#identitas-feedback').html('');
            }
            loadLayanan(false);
        }
    }

    $('#ticketIdentitas').on('input keyup', function (e) {
        checkIdentitas();
    });

    var handleSignInFormSubmit = function () {
        $('#kt_signin_submit').click(function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = $('#kt_login_form');

            form.validate({
                rules: {
                    username: {
                        required: true
                    },
                    password: {
                        required: true
                    },
                    captcha: {  // Tambahkan validasi untuk captcha
                        required: true
                    }
                },
                messages: {
                    captcha: {
                        required: "Silakan masukkan CAPTCHA"
                    }
                }
            });

            if (!form.valid()) {
                return;
            }

            btn.addClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', true);

            form.ajaxSubmit({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function (response, status, xhr, $form) {
                    var eR = (typeof response === 'string' ? JSON.parse(response) : response);

                    setTimeout(function () {
                        btn.removeClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', false);
                        showErrorMsg(form, eR.status, eR.message);
                        // Refresh CAPTCHA jika ada captcha baru
                        if (eR.new_captcha) {
                            refreshCaptcha(eR.new_captcha);
                        }
                    }, 1e3);

                    if (eR.status == 'success') {
                        setTimeout(function () {
                            window.location.href = eR.redirect_url;
                        }, 2e3);

                    }
                }
            });
        });
    }

    var handleTrackFormSubmit = function () {
        $('#kt_track_submit').click(function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = $('#kt_track_form');

            form.validate({
                rules: {
                    nomorTiket: {
                        required: true
                    }
                }
            });

            if (!form.valid()) {
                return;
            }

            btn.addClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', true);

            form.ajaxSubmit({
                type: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                success: function (response, status, xhr, $form) {
                    var eR = (typeof response === 'string' ? JSON.parse(response) : response);

                    setTimeout(function () {
                        btn.removeClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', false);
                        showErrorMsg(form, eR.status, eR.message);
                    }, 1e3);

                    if (eR.status == 'success') {
                        setTimeout(function () {
                            window.location.href = eR.redirect_url;
                        }, 2e3);

                    }
                }
            });
        });
    }

    var handleCreateFormSubmit = function () {
        $('#kt_create_submit').click(function (e) {
            e.preventDefault();
            var btn = $(this);
            var form = $('#kt_create_form');

            form.validate({
                rules: {
                    ticketIdentitas: {
                        required: true,
                        number: true
                    },
                    ticketName: "required",
                    ticketCategories: "required",
                    ticketEmail: {
                        required: true,
                        email: true
                    },
                    ticketNoHp: {
                        required: true,
                        number: true
                    },
                    ticketPriority: "required",
                    ticketSubject: "required",
                    ticketMessage: "required",
                    captcha: {
                        required: true
                    }
                },
                messages: {
                    ticketIdentitas: {
                        required: "Identitas Wajib Diisi !",
                        number: "Hanya Berupa Angka !"
                    },
                    ticketName: {
                        required: "Nama Wajib Diisi !"
                    },
                    ticketCategories: {
                        required: "Layanan Wajib Dipilih !"
                    },
                    ticketEmail: {
                        required: "Email Wajib Diisi !",
                        email: "Email Tidak Valid !"
                    },
                    ticketNoHp: {
                        required: "Nomor Handphone Wajib Diisi !",
                        number: "Hanya Berupa Angka !"
                    },
                    ticketPriority: {
                        required: "Prioritas Wajib Dipilih !"
                    },
                    ticketSubject: {
                        required: "Subjek / Judul Wajib Diisi !"
                    },
                    ticketMessage: {
                        required: "Pesan Wajib Diisi !"
                    },
                    captcha: {
                        required: "Silakan masukkan CAPTCHA"
                    }
                }
            });

            if (!form.valid()) {
                return;
            }

            btn.addClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', true);

            //var dataSave = new FormData($(form)[0]);

            form.ajaxSubmit({
                type: form.attr('method'),
                url: form.attr('action'),
                // Mengizinkan ajaxSubmit menangani multipart/form-data untuk upload berkas PDF
                success: function (response, status, xhr, $form) {
                    var eR = (typeof response === 'string' ? JSON.parse(response) : response);

                    setTimeout(function () {
                        btn.removeClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', false);
                        showErrorMsg(form, eR.status, eR.message);
                        // Refresh CAPTCHA jika ada captcha baru
                        if (eR.new_captcha) {
                            refreshCaptcha(eR.new_captcha);
                        }
                    }, 1e3);

                    swal.fire({
                        title: 'Information',
                        text: eR.message.replace("<br/>", "\n"),
                        type: eR.status
                    }).then(function () {
                        KTUtil.scrollTop();
                    });
                }
            });
        });
    }


    // Public Functions
    return {
        // public functions
        init: function () {
            $('#kt-login--signin').hide();
            $('#kt-login--track').hide();
            $('#kt-login--create').hide();
            initHandleWidgets();
            handleSignInFormSubmit();

            // Toggle Password logic
            $('#toggle_password').on('click', function(e) {
                e.preventDefault();
                const passwordInput = $('#login_password');
                const icon = $(this).find('i');
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });
            // Optional: Tambahkan tombol refresh CAPTCHA
            $('.refresh-captcha').on('click', function (e) {
                e.preventDefault();

                $.ajax({
                    url: 'login/refresh_captcha', // Sesuaikan dengan URL endpoint refresh captcha Anda
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        if (response && response.captcha) {
                            $('.captcha-display').text(response.captcha);
                            $('input[name="captcha"]').val('');
                        } else {
                            console.error('Invalid response format');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.log('Failed to refresh CAPTCHA: ' + error);
                    }
                });
            });

            handleTrackFormSubmit();
            handleCreateFormSubmit();
            // Cek sinkronisasi identitas awal jika input identitas sudah memiliki nilai
            if ($('#ticketIdentitas').val() && $('#ticketIdentitas').val().trim() !== '') {
                checkIdentitas();
            }
        }
    };
}();

// Class Initialization
jQuery(document).ready(function () {
    KTLoginGeneral.init();
});
