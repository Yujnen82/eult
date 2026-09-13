"use strict";

// Class Definition
var Dashboard = function() {
    var initSelect2 = function() {
        $(".m-select2").select2({
            placeholder: "Pilih...",
            allowClear: !0
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
                var res = (typeof data === 'string' ? JSON.parse(data) : data);
                $('#response').fadeIn('slow').html(res.response);
                swal.fire({
                    position: "top-right",
                    type: res.status,
                    title: res.message,
                    showConfirmButton: !1,
                    timer: 1500
                })
                button.prop( "disabled", false );
                button.removeClass('disabled');
                button.text(button_text);  
            }
        })
    }

    var chartPie = function() {
        var options = {
            series: {
                pie: {
                    show: !0,
                        innerRadius: 0.5,
                    radius: 1,
                    label: {
                        show: !0,
                        radius: 1,
                        formatter: function(t, e) {
                            var number = e.data[0][1];
                            return '<div style="font-size:8pt;text-align:center;padding:2px;color:white;">' + t + "<br/>" + Math.round(e.percent) + "% (" + number + ")</div>"
                        },
                        background: {
                            opacity: .8
                        }
                    }
                }
            },
            grid: {
                hoverable: true,
                clickable: true
            }
        };

        if (0 != $("#m_chart_profit_share").length) {
            
            function onDataReceived(series) {
                $.plot("#m_chart_profit_share", series, options);
            }

            $.ajax(
            {
                url: document.location.origin+'/home/datasall',
                dataType: "json",
                success: onDataReceived
            })
            
        }
        
        if (0 != $("#m_chart_profit_shareY").length) {
            function onDataReceivedY(series) {
                $.plot("#m_chart_profit_shareY", series, options);
            }
            
            $.ajax(
            {
                url: document.location.origin+'/home/datasyear',
                dataType: "json",
                success: onDataReceivedY
            })
        }
    }

    var handleSubmitChangeRole = function() {
        $("#form_change_role").validate({
            rules: {
                hakakses: {
                    required: !0
                }
            },
            submitHandler: function(e) {
                handleSubmit(e);
                return false
            }
        });
    }

    var handleSubmitChangePassword = function() {
        $("#form_change_password").validate({
            rules: {
                susrPasswordOld: {
                    required: !0
                },
                susrPasswordNew: {
                    required: !0
                },
                susrPasswordNewConfirm: {
                    required: !0
                }
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
            chartPie();
            handleSubmitChangeRole();
            handleSubmitChangePassword();
            initSelect2();
        }
    };
}();

jQuery(document).ready(function() {
    Dashboard.init()
});