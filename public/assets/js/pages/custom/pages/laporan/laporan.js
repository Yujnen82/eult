const KTLaporan = function () {
    const main_form = $('#main_form');
    const initHandleWidgets = () => {
        $('#kt_daterangepicker_2').daterangepicker({
            buttonClasses: ' btn',
            applyClass: 'btn-primary',
            cancelClass: 'btn-secondary'
        }, function (start, end, label) {
            $('#kt_daterangepicker_2 .form-control').val(`${start.format('DD-MM-YYYY')} / ${end.format('DD-MM-YYYY')}`);
        });
        $('.m-select2').select2({
            placeholder: 'Pilih'
        });
    }
    const initHandleShow = () => {
        const btnShow = $('#btn_show');
        const formShow = $('#form_show');
        formShow.validate({
            rules: {
                
                rentangTanggal: 'required'
            },
            messages: {
               
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
                        $('#response').html(data);
                        KTUtil.animateClass(main_form.find('#response')[0], 'flipInX animated');
                        $('#response').addClass('response-show');
                        initHandleWidgets();
                        onClick();
                    }
                });
            }
        });
       
    }

    var onClick = function () {
        $("#export").click(function (e) {
            e.preventDefault();
            var val_name = $(this).attr('val_name');
            creteTableExcel("#table_export", val_name);
        });
    }

    var creteTableExcel = function (id, val_name) {
        $(id).tableExport({
            fileName: "excel_export_" + val_name,
            type: 'excel'            
        });
    }

    return {
        init: function () {
            initHandleWidgets();
            initHandleShow();
            onClick();
        }
    };
}();

KTUtil.ready(function () {
    KTLaporan.init();
});