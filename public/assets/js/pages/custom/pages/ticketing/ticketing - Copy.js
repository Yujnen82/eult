const KTTicketing = function () {
    const formSimpan = $('#form_ticketing');
    const formShow = $('#form_show');
    const btnSave = $('#btn_save');
    const btnSaveText = btnSave.text();
    const btnAddWorker = $("#btnAddWorker");
    const btnWorker = $("#ticketAssign");
    const btnShow = $('#btn_show');
    const btnShowText = btnShow.text();
    const btnClose = $("#ts_close_btn");
    const _responseForm = KTUtil.getByID('response');

    const initEventHandler = () => {
        const btnAssign = $('.ts_assign_row');
        const formAssign = KTUtil.getByID('form_assign');
        if((formAssign == null) != true){
            $(formAssign).validate({
                rules: {
                    ticketAssign: "required"
                },
                submitHandler: res => {
                    $.ajax({
                        type: res.method,
                        url : res.action,
                        data : $(res).serialize(),
                        success : data => {
                            const res = (typeof data === 'string' ? JSON.parse(data) : data);
                            swal.fire({
                                position: 'center',
                                type: 'success',
                                title: res.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            _responseForm.innerHTML = '';
                        }
                    });
                }
            });
        }
        btnAssign.on('click', e => {
            e.preventDefault();
            $.ajax({
                url:e.currentTarget.href,
                success: data =>{
                    _responseForm.innerHTML = data;
                    _initWidgets();
                    initEventHandler();
                }
            });
        });
    }
    const _initWidgets = () => {
        let arrows;
        if (KTUtil.isRTL()) {
            arrows = {
                leftArrow: '<i class="la la-angle-right"></i>',
                rightArrow: '<i class="la la-angle-left"></i>'
            }
        } else {
            arrows = {
                leftArrow: '<i class="la la-angle-left"></i>',
                rightArrow: '<i class="la la-angle-right"></i>'
            }
        }

        $('#kt_datepicker_5').datepicker({
            rtl: KTUtil.isRTL(),
            todayHighlight: true,
            templates: arrows,
            autoclose: true,
            format: 'dd-mm-yyyy'
        });

        $('.selectpicker').selectpicker();
        $('.m-select2').select2({
            placeholder: "Pilih"
        });
    }
    btnClose.click(e => {
        e.preventDefault();
        swal.fire({
            title: "Apakah Anda Yakin Akan Mengakhiri Percakapan ?",
            text: "Percakapan Anda Akan Disimpan!!",
            type: "warning",
            showCancelButton: !0,
            confirmButtonText: "Ya, Akhiri!"
        }).then(function (e) {
            e.value &&
            $.ajax(
            {
                url: $(e.currentTarget).attr('href'),
                success: function (data) {
                    var res = (typeof data === 'string' ? JSON.parse(data) : data);
                    $('#response').fadeIn('slow').html(res.response);
                    swal.fire({ title: "Selesai!", text: res.message, type: res.status }).then(
                        function () {
                            location.reload();
                        }
                        );
                }
            });
        })
    });
    const eventShow = () => {
        btnShow.click(e => {
            e.preventDefault();
            const thisBtn = $(e.currentTarget);
            thisBtn.prop("disabled", true);
            thisBtn.addClass('disabled');
            thisBtn.text('Sedang Memproses...');
            KTApp.progress(thisBtn);
            $.ajax({
                type: $(formShow).attr('method'),
                url: $(formShow).attr('action'),
                data: $(formShow).serialize(),
                success: data => {
                    thisBtn.prop("disabled", false);
                    thisBtn.removeClass('disabled');
                    thisBtn.text(btnShowText);
                    KTApp.unprogress(thisBtn);
                    $('#response').html(data);
                    eventAccept();
                    eventValidate();
                    handleClickDelete();
                    initEventHandler();
                }
            });
        });
    }
    const eventCreate = () => {
        $("[name='ticketCategories']").on('change', e =>{
            e.preventDefault();
            const val = e.currentTarget.value;
            // const selectText = $("#responseLayanan").next().find('.select2-selection__placeholder');
            // KTApp.progress(selectText.prop('disabled',true));
            // console.log(selectText.text('Sedang Mengambil Data....'));
            $.ajax({
                type: 'POST',
                url:'/ticketing/getKeperluan',
                data: {layanan:val},
                success: data => {
                    const res = (typeof data === 'string' ? JSON.parse(data) : data);
                    $(res).each((i, v) => {
                        $("#responseLayanan").append(`
                            <option value="${v.sCatId}">${v.sCatNama}</option>
                            `);
                    });
                }
            });
        })
    }
    const handleClickDelete = () => {
        $(".ts_remove_row").click(e => {
            e.preventDefault();
            var idLink = '#' + $(e.currentTarget).attr('id');
            swal.fire({
                title: "Apakah Anda Yakin Akan Hapus Data?",
                text: "Data Tidak Dapat Dikembalikan!!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Yes, Hapus!"
            }).then(function (e) {
                e.value &&
                $.ajax(
                {
                    url: $(idLink).attr('href'),
                    success: function (data) {
                        var res = (typeof data === 'string' ? JSON.parse(data) : data);
                        $('#response').fadeIn('slow').html(res.response);
                        swal.fire({ title: "Deleted!", text: res.message, type: res.status }).then(
                            function () {
                                location.reload();
                            }
                            );
                    }
                });
            })
        });
    }
    const eventAccept = () => {
        const btnAccept = $(".ts_accept_row");
        btnAccept.click(e => {
            e.preventDefault();
            const idLink = '#' + $(e.currentTarget).attr('id');
            swal.fire({
                title: "Apakah Anda Yakin Akan Mengambil Pekerjaan ini?",
                text: "Pekerjaan tidak dapat di cancel!!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Ya, Ambil!"
            }).then(e => {
                e.value &&
                $.ajax({
                    url: $(idLink).attr('href'),
                    success: function (data) {
                        const res = (typeof data === 'string' ? JSON.parse(data) : data);
                        swal.fire({ title: "Diterima!", text: res.message, type: res.status });

                    }
                });
            });
        });
    }
    const eventValidate = () => {
        const btnValidated = $(".ts_validate_row");
        btnValidated.click(e => {
            e.preventDefault();
            const idLink = '#' + $(e.currentTarget).attr('id');
            swal.fire({
                title: "Apakah Anda Yakin Akan Validasi Pekerjaan ini?",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Ya, Validasi!"
            }).then(e => {
                e.value &&
                $.ajax({
                    url: $(idLink).attr('href'),
                    success: function (data) {
                        const res = (typeof data === 'string' ? JSON.parse(data) : data);
                        swal.fire({ title: "Validasi!", text: res.message, type: res.status });

                    }
                });
            });
        });
    }
    const showSubmit = (form) => {
        $('#response').html('');
        btnSave.prop("disabled", true);
        btnSave.addClass('disabled');
        btnSave.text('Sedang Memproses...');
        const dataSave = new FormData($(form)[0]);
        $.ajax({
            type: $(form).attr('method'),
            url: $(form).attr('action'),
            data: dataSave,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                try {
                    var res = (typeof data === 'string' ? JSON.parse(data) : data);
                    $('#response').fadeIn('slow').html(res.response);
                    swal.fire({
                        position: "top-right",
                        type: res.status,
                        title: res.message,
                        showConfirmButton: !1,
                        timer: 1500
                    });
                } catch (err) {
                    $('#response').fadeIn('slow').html(data);
                }
                btnSave.prop("disabled", false);
                btnSave.removeClass('disabled');
                btnSave.text(btnSaveText);
            }
        });
    }

    const formValidation = () => {
        formSimpan.validate({
            rules: {
                ticketCategories: "required",
                ticketsCategories: "required",
                ticketEmail: {
                    required: true,
                    email: true
                },
                ticketNoHp: {
                    required: true,
                    number: true
                },
                ticketSubject: "required",
                ticketMessage: "required"
            },
            messages: {
                ticketCategories: {
                    required: "Layanan Harus Dipilih !"
                },
                ticketsCategories: {
                    required: "Keperluan Harus Dipilih !"
                },
                ticketEmail: {
                    required: "Email Tidak Boleh Kosong",
                    email: "Email Tidak Valid"
                },
                ticketNoHp: {
                    required: "Nomor Handphone Tidak Boleh Kosong",
                    number: "Harus Berupa Angka !"
                },
                ticketSubject: {
                    required: "Subject Harus Diisi !"
                },
                ticketMessage: {
                    required: "Pesan Harus Diisi !"
                }
            },
            submitHandler: function (e) {
                showSubmit(e);
                return false
            }
        });
    }

    return {
        init: function () {
            formValidation();
            eventShow();
            _initWidgets();
            eventCreate();
        }
    };
}();

KTUtil.ready(function () {
    KTTicketing.init();
});