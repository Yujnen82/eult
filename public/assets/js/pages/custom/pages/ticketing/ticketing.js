// CI4 mengirim fragmen halaman sebagai JSON {response: "..."} dan jQuery
// otomatis mengubahnya menjadi objek, sehingga .html(eultTerimaHtml(data)) akan mencetak
// "[object Object]". Helper ini mengambil isi HTML-nya dengan aman.
const eultTerimaHtml = function (data) {
    if (data && typeof data === 'object' && typeof data.response === 'string') {
        return data.response;
    }
    return data;
}

const KTTicketing = function () {
    const main_form = $('#main_form');
    const initHandleWidgets = () => {
        $('#kt_daterangepicker_2').daterangepicker({
            buttonClasses: ' btn',
            applyClass: 'btn-primary',
            cancelClass: 'btn-secondary'
        }, function (start, end, label) {
            $('#kt_daterangepicker_2 .form-control').val(`${start.format('DD-MM-YYYY')} / ${end.format('DD-MM-YYYY')}`);
        });
        $('#kt_datepicker_1,#kt_datepicker_2,#kt_datepicker_3,#kt_datepicker_4').datepicker({
            format: "yyyy-mm-dd",            
            todayHighlight: true,
            autoclose: true,                      
        });
        $("#kt_datepicker_4").datepicker().on('show.bs.modal', function(event) {
            // prevent datepicker from firing bootstrap modal "show.bs.modal"
            event.stopPropagation(); 
        });

        $('.m-select2').select2({
            placeholder: 'Pilih',
            allowClear: true,
        });
        $('.selectpicker').selectpicker();
        tinymce.remove();
        tinymce.init({
            selector: '.kt-tinymce-4',
            menubar: false,
            toolbar: ['styleselect fontselect fontsizeselect',
            'undo redo | cut copy paste | bold italic | link image | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | blockquote subscript superscript | advlist | autolink | lists charmap | print preview |  code'
            ],
            plugins: 'advlist autolink link image lists charmap print preview code'
        });
        $('.kv-uni-star').rating({
            theme: 'krajee-uni',
            filledStar: '&#x2605;',
            emptyStar: '&#x2606;'
        });
    }
    const initHandleShow = () => {
        const handleEventCreate = (status) => {
            // Select2 mengunci tampilan placeholder/opsi saat pertama di-init,
            // jadi isi <select> diganti lewat elemen baru agar tampilannya ikut
            // berubah (pola sama dengan form publik di login.js).
            const renderKategori = function (html, disabled) {
                const lama = document.getElementById('ticketCategories');
                if (lama) {
                    const $lama = $('#ticketCategories');
                    if ($lama.hasClass('select2-hidden-accessible')) {
                        $lama.select2('destroy');
                    }
                    const baru = document.createElement('select');
                    baru.id = 'ticketCategories';
                    baru.name = 'ticketCategories';
                    baru.className = 'form-control m-select2';
                    baru.setAttribute('data-placeholder', 'Pilih Kategori Layanan Kampus');
                    baru.disabled = disabled;
                    baru.required = true;
                    baru.innerHTML = html;
                    lama.replaceWith(baru);
                }
                const $categories = $('#ticketCategories');
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
            }
            const loadLayanan = function (istrue) {
                if (istrue) {
                    renderKategori('<option value="">Memuat kategori layanan...</option>', false);
                    $.ajax({
                        type: 'POST',
                        url: '/ticketing/getLayanan',
                        data: {
                            id: istrue
                        },
                        success: data => {
                            renderKategori(data, false);
                        }
                    });
                } else {
                    renderKategori('<option value="">Masukkan nomor identitas terlebih dahulu...</option>', true);
                }
            }
            const formSimpan = $('#form_ticketing');
            const btnSimpan = $('#btn_save');
            // const btnSelect = $("[name='ticketCategories']");
            const btnCheck = $('#btn_check');
            btnCheck.on('click', e => {
                const $this = e.currentTarget;
                e.preventDefault();
                const $_id = $("[name='ticketIdentitas']").val();
                const text_old = $this.text;
                $this.text = 'Sedang Memproses';
                $this.disabled = true;
                KTApp.progress($this);
                console.log($_id);
                if ($_id == '') {
                    swal.fire({
                        text: 'Silakan Isi Identitas',
                        type: 'error'
                    });
                    $this.text = text_old;
                    $this.disabled = false;
                    KTApp.unprogress($this);
                } else {
                    $.ajax({
                        type: 'POST',
                        url: '/ticketing/getIdentitas',
                        data: {
                            identitas: $_id
                        },
                        success: data => {
                            const res = (typeof data === 'string' ? JSON.parse(data) : data);
                            if (res.status == true) {
                                if (res.umum == true) {
                                    $("[name='ticketName']").val('');
                                } else if (res.ismhs == true || res.ismhs == false) {
                                    const nama = res.name || (res.datamhs ? res.datamhs.name : '') || (res.datapegawai ? res.datapegawai.name : '');
                                    $("[name='ticketName']").val(nama);
                                    $("[name='ticketEmail']").val(res.email || '');
                                    $("[name='ticketNoHp']").val(res.phone || '');
                                }
                                loadLayanan(true);
                            } else {
                                swal.fire({
                                    text: res.message,
                                    type: 'warning'
                                });
                                loadLayanan(false);
                            }
                            $this.text = text_old;
                            $this.disabled = false;
                            KTApp.unprogress($this);

                        }
                    });
                }
            });
            // btnSelect.on('change', e => {

            //     $("#responseLayanan").html('');
            //     e.preventDefault();
            //     const val = e.currentTarget.value;
            //     $.ajax({
            //         type: 'POST',
            //         url: '/ticketing/getKeperluan',
            //         data: {
            //             layanan: val
            //         },
            //         success: data => {
            //             const res = (typeof data === 'string' ? JSON.parse(data) : data);
            //             $(res).each((i, v) => {
            //                 $("#responseLayanan").append(`
            //                     <option value="${v.sCatId}">${v.sCatNama}</option>
            //                     `);
            //             });
            //         }
            //     });
            // });
            formSimpan.validate({
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
                    ticketMessage: "required"
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
                    }
                }
            });
            btnSimpan.on('click', e => {
                const dataSave = new FormData($(formSimpan)[0]);
                const $this = e.currentTarget;
                const title = status;
                $($this).text('Sedang Menyimpan');
                KTApp.progress($this);
                e.preventDefault();
                if (formSimpan.valid()) {
                    $.ajax({
                        type: formSimpan[0].method,
                        url: formSimpan[0].action,
                        data: dataSave,
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: data => {
                            const res = (typeof data === 'string' ? JSON.parse(data) : data);
                            $($this).text('Save');
                            KTApp.unprogress($this);
                            if (res.status != 'error') {
                                swal.fire({
                                    title: title,
                                    text: res.message,
                                    type: res.status
                                }).then(function () {
                                    window.location.replace(res.url)
                                });
                            } else {
                                swal.fire({
                                    title: title,
                                    text: res.message,
                                    type: res.status
                                });
                            }

                        }
                    });
                } else {
                    KTUtil.scrollTo($('.is-invalid')[0]);
                    $($this).text('Save');
                    KTApp.unprogress($this);
                }
            });
        }
        const handleEventDeliver = (status) => {
            const btnPreview = $('#btn_preview');
            const formDeliver = $('#form_surat');
            const btnSimpan = $('#btn_save');
            btnPreview.on('click', e => {
                e.preventDefault();
                const id = $('#suratTrackingId').val();
                $('#suratBody').html(tinymce.get('suratBody').getContent());
                $('#suratFooter').html(tinymce.get('suratFooter').getContent());
                if(tinymce.get('suratTujuan')!==null)
                    $('#suratTujuan').html(tinymce.get('suratTujuan').getContent());
                $.ajax({
                    type: 'POST',
                    url: e.currentTarget.href,
                    data: formDeliver.serialize(),
                    success: data => {
                        console.log(data)
                        window.open(`/ticketing/preview/${id}`, '_blank');
                    }
                });
            });
            formDeliver.validate({
                rules: {
                    suratJenis: "required",
                    suratBody: "required",
                },
                messages: {
                    suratJenis: {
                        required: "Judul Surat / Keterangan Surat Harus Diisi !"
                    },
                    suratBody: {
                        required: "Isi Surat Harus Diisi !"
                    }
                }
            });
            btnSimpan.on('click', e => {
                const $this = e.currentTarget;
                $($this).text('Sedang Menyimpan');
                KTApp.progress($this);
                e.preventDefault();
                $('#suratBody').html(tinymce.get('suratBody').getContent());
                $('#suratFooter').html(tinymce.get('suratFooter').getContent());
                if(tinymce.get('suratTujuan')!==null)
                    $('#suratTujuan').html(tinymce.get('suratTujuan').getContent());
                if (formDeliver.valid()) {
                    $.ajax({
                        type: formDeliver.attr('method'),
                        url: formDeliver.attr('action'),
                        data: formDeliver.serialize(),
                        success: data => {
                            console.log(data);
                            const eR = (typeof data === 'string' ? JSON.parse(data) : data);                            
                            if (eR.status != 'error') {
                                swal.fire({
                                    text: eR.message,
                                    type: eR.status
                                }).then(function () {
                                    location.reload()
                                });;
                            } else {
                                swal.fire({
                                    text: eR.message,
                                    type: eR.status
                                });
                            }
                            KTApp.unprogress($this);
                            $this.text = 'Save';
                        }
                    });
                } else {
                    KTApp.unprogress($this);
                    $this.text = 'Save';
                }
            });
        }
        const handleEvent = () => {
            const btnAssign = $('.ts_assign_row');
            const btnAccept = $('.ts_accept_row');
            const btnEdit = $('.ts_update_row');
            const btnValidate = $('.ts_validate_row');
            const btnDelete = $('.ts_remove_row');
            const btnDeliver = $('.ts_deliver_row');
            const btnFinish = $('#btn_nomor_surat');
            const btnFinish2 = $('.ts_finish_row');
            const btnTerima = $('.ts_terima_row');
            const btnReject = $('.ts_reject_row');
            const btnDelivered = $('.ts_delivered_row');
            const btnTolak = $('#btn_pesan_tolak');
            const btnFinish3 = $('#btn_pesan_validasi');
            const btnEktm = $('#btn_ektm');

            const mdNoSurat = $('#nomor_surat');
            const mdPesanTolak = $('#pesan_tolak');
            const mdPesanValidasi = $('#pesan_validasi');
            const mdEktm = $('#ektm');

            mdNoSurat.on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Button that triggered the modal
                var keys = button.data('linkkeys') // Extract info from data-* attributes
                // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
                // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
                var myVal = button.data('val')
                console.log(myVal)
                var modal = $(this)
                modal.find("#f_nomor_surat").val(myVal)
                modal.find('#f_nomor_tiket').val(keys)
            })

            mdPesanValidasi.on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Button that triggered the modal
                var keys = button.data('linkkeys') // Extract info from data-* attributes
                // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
                // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.

                console.log(keys);
                var modal = $(this)
                modal.find('#f_nomor_tiket_validasi').val(keys)
            })

            mdPesanTolak.on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Button that triggered the modal
                var keys = button.data('linkkeys') // Extract info from data-* attributes
                // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
                // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.

                console.log(keys);
                var modal = $(this)
                modal.find('#f_nomor_tiket_tolak').val(keys)
            })

            mdEktm.on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Button that triggered the modal
                var keys = button.data('linkkeys') // Extract info from data-* attributes
                // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
                // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
                var modal = $(this)
                modal.find('#f_nomor_tiket_ektm').val(keys)
            })                        

            const handleEventReject = () => {
                $('#btn_save').on('click', e => {
                    e.preventDefault();
                    swal.fire({
                        title: "Apakah Anda Yakin Akan Menolak Tiket?",
                        text: "Data Tidak Dapat Dikembalikan!!",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Ya, Tolak!"
                    }).then(function (e) {
                        e.value &&
                        $.ajax({
                            url: $('#form_reject')[0].action,
                            type: $('#form_reject')[0].method,
                            data: $('#form_reject').serialize(),
                            success: data => {
                                const res = (typeof data === 'string' ? JSON.parse(data) : data);
                                if (res.status != 'error') {
                                    swal.fire({
                                        title: "Rejected !!",
                                        html: res.message,
                                        type: res.status
                                    }).then(function () {
                                        location.reload()
                                    });
                                } else {
                                    swal.fire({
                                        title: "Error !!",
                                        html: res.message,
                                        type: res.status
                                    });
                                }
                            }
                        });
                    });
                });
            }
            btnReject.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        handleEventReject();
                    }
                });
            });
            btnTerima.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                swal.fire({
                    title: "Apakah Anda Yakin Akan Memverifikasi Berkas?",
                    text: "Data Tidak Dapat Dikembalikan!!",
                    type: "warning",
                    showCancelButton: !0,
                    confirmButtonText: "Ya, Verifikasi!"
                }).then(function (e) {
                    e.value &&
                    $.ajax({
                        type: 'POST',
                        url: $this.href,
                        success: data => {
                            var res = (typeof data === 'string' ? JSON.parse(data) : data);
                            $('#response').fadeIn('slow').html(res.response);
                            swal.fire({
                                title: "Verified!",
                                text: res.message,
                                type: res.status
                            }).then(function () {
                                location.reload()
                            });
                        }
                    });
                });
            });
            btnFinish.on('click', e => {
                e.preventDefault();
                const url = $("#f_nomor_tiket").val();
                const val = $("#f_nomor_surat").val();
                if (val) {
                    swal.fire({
                        title: "Apakah Anda Yakin Akan Menyelesaikan Tiket?",
                        text: "Data Tidak Dapat Dikembalikan!!",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Ya, Selesai!"
                    }).then(function (e) {
                        e.value &&
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                nomor_surat: val
                            },
                            success: data => {
                                var res = (typeof data === 'string' ? JSON.parse(data) : data);
                                $('#response').fadeIn('slow').html(res.response);
                                swal.fire({
                                    title: "Finished!",
                                    text: res.message,
                                    type: res.status
                                }).then(function () {
                                    location.reload()
                                });
                            }
                        });
                    });
                } else {
                    swal.fire({
                        title: "Information!",
                        text: 'Nomor Surat Wajib Diisi',
                        type: 'error'
                    })
                }
            });
            btnEktm.on('click', e => {
                e.preventDefault();
                const url       = $("#f_nomor_tiket_ektm").val();
                const nsPemohon = $("#ns_pemohon").val();
                const tsPemohon = $(".ts_pemohon").val();
                const noSurat   = $("#ns_pengantar").val();
                const bank      = $("#bank").val();                
                
                const form_data = new FormData();
                form_data.append('ns_pemohon', nsPemohon);
                form_data.append('ts_pemohon', tsPemohon);
                form_data.append('ns_pengantar', noSurat);
                form_data.append('bank', bank);
                if (nsPemohon && tsPemohon && noSurat && bank) {
                    swal.fire({
                        title: "Apakah Anda Yakin Akan Validasi dan Membuat Surat E-KTM?",
                        text: "Data Tidak Dapat Dikembalikan!!",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Ya, Proses!"
                    }).then(function (e) {
                        e.value &&
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: form_data,
                            cache: false,
                            contentType: false,
                            processData: false,                      
                            success: data => {                                
                                var res = (typeof data === 'string' ? JSON.parse(data) : data);
                                $('#response').fadeIn('slow').html(res.response);
                                swal.fire({
                                    title: "Validasi Dan Buat Surat E-KTM!",
                                    text: res.message,
                                    type: res.status
                                }).then(function () {
                                    location.reload()
                                });
                            }
                        });
                    });
                } else {
                    swal.fire({
                        title: "Information!",
                        text: 'Data Wajib Diisi Semua!',
                        type: 'error'
                    })
                }
            });
            btnFinish3.on('click', e => {
                e.preventDefault();
                const url = $("#f_nomor_tiket_validasi").val();
                const val = $("#f_pesan_validasi").val();

                const uploadFile = $('#f_archive_id').prop('files')[0];
                const form_data = new FormData();
                form_data.append('ticketArchiveId', uploadFile);
                form_data.append('pesanvalidasi', val);

                if (val) {
                    swal.fire({
                        title: "Apakah Anda Yakin Akan Menyelesaikan Tiket?",
                        text: "Data Tidak Dapat Dikembalikan!!",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Ya, Selesai!"
                    }).then(function (e) {
                        e.value &&
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: form_data,
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: data => {
                                var res = (typeof data === 'string' ? JSON.parse(data) : data);
                                $('#response').fadeIn('slow').html(res.response);
                                swal.fire({
                                    title: "Finished!",
                                    text: res.message,
                                    type: res.status
                                }).then(function () {
                                    location.reload()
                                });
                            }
                        });
                    });
                } else {
                    swal.fire({
                        title: "Information!",
                        text: 'Pesan Wajib Diisi',
                        type: 'error'
                    })
                }
            });
            btnFinish2.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                swal.fire({
                    title: "Apakah Anda Yakin Akan Menyelesaikan Tiket?",
                    text: "Data Tidak Dapat Dikembalikan!!",
                    type: "warning",
                    showCancelButton: !0,
                    confirmButtonText: "Ya, Selesai!"
                }).then(function (e) {
                    e.value &&
                    $.ajax({
                        type: 'POST',
                        url: $this.href,
                        success: data => {
                            var res = (typeof data === 'string' ? JSON.parse(data) : data);
                            $('#response').fadeIn('slow').html(res.response);
                            swal.fire({
                                title: "Finished!",
                                text: res.message,
                                type: res.status
                            }).then(function () {
                                location.reload()
                            });
                        }
                    });
                });
            });
            btnDeliver.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEventDeliver();
                    }
                });
            });
            btnDelivered.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEventDeliver();
                    }
                });
            });
            btnAssign.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEventAssign();
                    }
                });
            });
            btnAccept.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEventAssign();
                    }
                });
            });
            btnEdit.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#first-form').removeClass('response-show');
                        $('#first-form').addClass('response-hide');
                        $('#second-form').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#second-form')[0], 'flipInX animated');
                        $('#second-form').addClass('response-show');
                        initHandleWidgets();
                        //handleEventEdit();
                        handleEventCreate('Edited');
                    }
                });
            });
            btnValidate.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                $.ajax({
                    url: $this.href,
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEventValidate();
                    }
                });
            });
            btnDelete.on('click', e => {
                e.preventDefault();
                const $this = e.currentTarget;
                swal.fire({
                    title: "Apakah Anda Yakin Akan Hapus Data?",
                    text: "Data Tidak Dapat Dikembalikan!!",
                    type: "warning",
                    showCancelButton: !0,
                    confirmButtonText: "Yes, Hapus!"
                }).then(function (e) {
                    e.value &&
                    $.ajax({
                        type: 'POST',
                        url: $this.href,
                        success: data => {
                            var res = (typeof data === 'string' ? JSON.parse(data) : data);
                            $('#response').fadeIn('slow').html(res.response);
                            swal.fire({
                                title: "Deleted!",
                                text: res.message,
                                type: res.status
                            }).then(function () {
                                location.reload()
                            });
                        }
                    });
                });
            });
            btnTolak.on('click', e => {
                console.log('swal');
                e.preventDefault();
                const url = $("#f_nomor_tiket_tolak").val();
                const val = $("#f_pesan_tolak").val();
                if (val) {
                    swal.fire({
                        title: "Apakah Anda Yakin Akan Menolak Tiket?",
                        text: "Data Tidak Dapat Dikembalikan!!",
                        type: "warning",
                        showCancelButton: !0,
                        confirmButtonText: "Ya, Tolak!"
                    }).then(function (e) {
                        e.value &&
                        $.ajax({
                            type: 'POST',
                            url: url,
                            data: {
                                pesan_tolak: val
                            },
                            success: data => {
                                var res = (typeof data === 'string' ? JSON.parse(data) : data);
                                $('#response').fadeIn('slow').html(res.response);
                                swal.fire({
                                    title: "Rejected!",
                                    text: res.message,
                                    type: res.status
                                }).then(function () {
                                    location.reload()
                                });
                            }
                        });
                    });
                } else {
                    swal.fire({
                        title: "Information!",
                        text: 'Pesan tolak wajib diisi',
                        type: 'error'
                    })
                }
            });
            // const handleEventEdit = () => {
            //     const val = $("[name='ticketCategories']").val();
            //     $.ajax({
            //         type: 'POST',
            //         url: '/ticketing/getKeperluan',
            //         data: {
            //             layanan: val
            //         },
            //         success: data => {
            //             const res = (typeof data === 'string' ? JSON.parse(data) : data);
            //             $(res).each((i, v) => {
            //                 $("#responseLayanan").append(`
            //                     <option value="${v.sCatId}">${v.sCatNama}</option>
            //                     `);
            //             });
            //         }
            //     });
            // }
            const handleEventValidate = () => {
                const form = $('#form_tandatangan');
                const btnSimpan = $('#btn_save');
                form.validate({
                    rules: {
                        ticketMessage: "required"
                    }
                });
                btnSimpan.on('click', e => {
                    if (form.valid()) {
                        const dataSave = new FormData($(form)[0]);
                        e.preventDefault();
                        swal.fire({
                            title: "Apakah Anda Yakin Akan Menandatangani Surat?",
                            text: "Data Tidak Dapat Dikembalikan!!",
                            type: "warning",
                            showCancelButton: !0,
                            confirmButtonText: "Yes, Validasi!"
                        }).then(function (e) {
                            e.value &&
                            $.ajax({
                                type: form[0].method,
                                url: form[0].action,
                                data: dataSave,
                                cache: false,
                                contentType: false,
                                processData: false,
                                success: data => {
                                    var res = (typeof data === 'string' ? JSON.parse(data) : data);
                                    $('#response').fadeIn('slow').html(res.response);
                                    swal.fire({
                                        title: "Validasi",
                                        text: res.message,
                                        type: res.status
                                    }).then(function () {
                                        location.reload()
                                    });
                                }
                            });
                        });
                    }
                });
            }
            const handleEventAssign = () => {
                const formAssign = $('#form_assign');
                const btnSimpan = $('#btn_save');
                formAssign.validate({
                    rules: {
                        ticketAssign: "required",
                        ticketMessage: "required",
                        ticketPriority: "required"
                    }
                });
                btnSimpan.on('click', e => {
                    const dataSave = new FormData($(formAssign)[0]);
                    const $this = e.currentTarget;
                    e.preventDefault();
                    $($this).text('Sedang Menyimpan');
                    KTApp.progress($this);
                    if (formAssign.valid()) {
                        $.ajax({
                            type: formAssign[0].method,
                            url: formAssign[0].action,
                            data: dataSave,
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: data => {
                                $($this).text('Save');
                                KTApp.unprogress($this);
                                const res = (typeof data === 'string' ? JSON.parse(data) : data);
                                if (res.status != 'error') {
                                    swal.fire({
                                        title: "Disposisi",
                                        text: res.message,
                                        type: res.status
                                    }).then(function () {
                                        location.reload()
                                    });
                                } else {
                                    swal.fire({
                                        title: "Disposisi",
                                        text: res.message,
                                        type: res.status
                                    });
                                }

                            }
                        });
                    } else {
                        KTUtil.scrollTo($('.is-invalid')[0]);
                        $($this).text('Save');
                        KTApp.unprogress($this);
                    }
                });
            }            
        }
        $('#btn-create').on('click', e => {
            e.preventDefault();
            const $this = e.currentTarget;
            $.ajax({
                url: $this.href,
                success: data => {
                    KTUtil.scrollTop();
                    $('#first-form').removeClass('response-show');
                    $('#first-form').addClass('response-hide');
                    $('#second-form').html(eultTerimaHtml(data));
                    KTUtil.animateClass(main_form.find('#second-form')[0], 'flipInY animated');
                    $('#second-form').addClass('response-show');
                    initHandleWidgets();
                    handleEventCreate('Created');
                }
            });
        });
        const btnShow = $('#btn_show');
        const formShow = $('#form_show');
        formShow.validate({
            rules: {
                layanan: 'required',
                rentangTanggal: 'required'
            },
            messages: {
                layanan: 'Silakan Pilih Layanan',
                rentangTanggal: 'Silakan Pilih tanggal'
            },
        });
        btnShow.on('click', e => {
            e.preventDefault();
            if (formShow.valid()) {
                $.ajax({
                    type: formShow.attr('method'),
                    url: formShow.attr('action'),
                    data: formShow.serialize(),
                    success: data => {
                        $('#response').removeClass('response-hide');
                        $('#response').html(eultTerimaHtml(data));
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        handleEvent();
                    }
                });
            }
        });
        $('.kv-uni-star').on('change', function () {
            const ticketId = $('#ticketId').text();
            $.ajax({
                type: 'POST',
                url: '/ticketing/rating',
                data: {
                    rating: $(this).val(),
                    nomorTiket: ticketId
                },
                success: data => {
                     console.log(data);
                     swal.fire({
                                title: "Indeks Kepuasan Masyarakat",
                                text: 'Terimakasih Telah Mengisi IKM, Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon Periksa Email Anda!',
                                type: 'success'
                            }).then(function () {
                                    location.reload()
                                });     
                }
            });
        });
    }

    // Ekspor daftar tiket yang sedang tampil, mengikuti pola laporan.js.
    // Endpoint ticketing/export/(:any) mengembalikan JSON satu tiket, jadi
    // tidak cocok dipasang sebagai href tombol "Ekspor Excel" pada daftar.
    const bindExport = () => {
        $(document).off('click.ticketingExport', '#btn-export').on('click.ticketingExport', '#btn-export', function (e) {
            e.preventDefault();

            const table = $('#table_ticketing');
            if (!table.length || table.find('tbody tr').length === 0 || table.find('td[colspan]').length) {
                swal.fire({
                    title: 'Tidak Ada Data',
                    text: 'Tampilkan daftar tiket terlebih dahulu sebelum mengekspor.',
                    type: 'info'
                });
                return;
            }

            if (typeof $.fn.tableExport !== 'function') {
                swal.fire({
                    title: 'Fitur Belum Siap',
                    text: 'Modul ekspor spreadsheet belum selesai dimuat. Silakan muat ulang halaman.',
                    type: 'error'
                });
                return;
            }

            const stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');

            table.tableExport({
                fileName: 'daftar_tiket_' + stamp,
                type: 'excel',
                // Kolom terakhir (Aksi) hanya berisi tombol, tidak bermakna di spreadsheet.
                ignoreColumn: [table.find('thead th').length - 1]
            });
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
    KTTicketing.init();
});