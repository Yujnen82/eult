"use strict";

// Class Definition
var FormGeneral = function() {
    $('#ref_table').margetable({
        type: 2,
        colindex: [0, 1]
    });
    var handleClickDelete = function() {
        $(".ts_remove_row").click(function(e) {
            e.preventDefault();
            var idLink = '#'+$(this).attr('id');
            
            swal.fire({
                title: "Apakah Anda Yakin Akan Hapus Data?",
                text: "Data Tidak Dapat Dikembalikan!!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Yes, Hapus!"
            }).then(function(e) {
                e.value && 
                $.ajax(
                {
                    url:$(idLink).attr('href'),
                    success:function(data) 
                    {
                        var res = (typeof data === 'string' ? JSON.parse(data) : data);
                        $('#response').fadeIn('slow').html(res.response);
                        swal.fire({title: "Deleted!", text: res.message, type: res.status}).then(
                            function(){ 
                                location.reload();
                            }
                            ) ;                   
                    }
                });
            })    
        });
    }

    var handleSubmit = function(form) {
        $('#response').html('');
        var button = $('#btn_save');
        var button_text = button.text();
        button.prop( "disabled", true );
        button.addClass('disabled');
        button.text('Sedang Memproses...');
        $.ajax({
            type: $(form).attr('method'),
            url: $(form).attr('action'),
            data: $(form).serialize(),
            success: function(data) {
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
                } catch(err)
                {
                    $('#response').fadeIn('slow').html(data);
                }
                
                
                button.prop( "disabled", false );
                button.removeClass('disabled');
                button.text(button_text);  
            }
        });
    }

    var handleSubmitForm = function() {
        $("#form_form").validate({
            rules: {

            },
            submitHandler: function(e) {
                handleSubmit(e);
                return false
            }
        });
    }

    return {
        // public functions
        init: function() {
            handleSubmitForm();
            handleClickDelete();
        }
    };
}();

jQuery(document).ready(function() {
    FormGeneral.init()
});