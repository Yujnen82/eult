"use strict";

// Class Definition
var FormCustom = function() {

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
                FormCustom.init();
            }
        })
    }

    var handleSubmitFormShow = function() {
        $("#form_show").validate({
            rules: {
                hakakses: {
                    required: true
                }
            },
            submitHandler: function(e) {
                handleSubmit(e);
                return false
            }
        });
    }

    var handleSubmitForm = function() {
        $("#form_custom").validate({
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
            handleSubmitFormShow();
            handleSubmitForm();
        }
    };
}();

jQuery(document).ready(function() {
    FormCustom.init()
});