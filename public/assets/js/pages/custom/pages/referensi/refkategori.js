// CI4 mengirim fragmen halaman sebagai JSON {response: "..."} dan jQuery
// otomatis mengubahnya menjadi objek, sehingga .html(eultTerimaHtml(data)) akan mencetak
// "[object Object]". Helper ini mengambil isi HTML-nya dengan aman.
const eultTerimaHtml = function (data) {
    if (data && typeof data === 'object' && typeof data.response === 'string') {
        return data.response;
    }
    return data;
}

var refKategori = function() {
	const main_form = $('#ref_kategori');
	const handleWidgets = () => {
		$('.m-select2').select2({
			placeholder : 'Pilih'
		});
	}
	const handleEvent = () => {
		const formShow = KTUtil.getByID('form_show');
		const handleSave = () => {
			const formCrud = KTUtil.getByID('form_form');
			$('#btn_save').on('click', e=>{
				e.preventDefault();
				KTApp.block('#form_crud', {
					overlayColor: '#000000',
					type: 'v2',
					state: 'primary',
					message: 'Sedang Menyimpan ...'
				});
				$.ajax({
					type: 'POST',
					url: formCrud.action,
					data: $(formCrud).serialize(),
					success: function(data) {
						var res = (typeof data === 'string' ? JSON.parse(data) : data);
						KTApp.unblock('#form_crud');
						if (res.status != 'error') {
							swal.fire({
								html: res.message, 
								type: res.status,
								showConfirmButton: !1,
								timer: 1500
							}).then(() => { location.reload() });
						}else{
							swal.fire({
								html: res.message, 
								type: res.status,
								timer: 1500
							});
						}
						
					}
				});
			});
		}
		const handleDelete = (event) => {
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
						type: 'POST',
						url:$(idLink).attr('href'),
						data:{event:event},
						success:function(data) 
						{
							var res = (typeof data === 'string' ? JSON.parse(data) : data);
							$('#response').fadeIn('slow').html(res.response);
							swal.fire({title: "Deleted!", text: res.message, type: res.status}).then(function(){ location.reload() }) ;                   
						}
					});
				})    
			});
		}
		const handleAddSub = () => {
			$('#btn-add').on('click', e => {
				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: e.currentTarget.href,
					success: function(data) {
						$('#create').removeClass('response-hide');
						$('#create').html(eultTerimaHtml(data));
						KTUtil.animateClass(main_form.find('#create')[0], 'flipInX animated');
						$('#create').addClass('response-show');
						handleSave();
						handleWidgets();
					}
				});
			});
			$('#btn-edit').on('click', e => {
				e.preventDefault();
				$.ajax({
					type: 'POST',
					url: e.currentTarget.href,
					success: function(data) {
						$('#create').removeClass('response-hide');
						$('#create').html(eultTerimaHtml(data));
						KTUtil.animateClass(main_form.find('#create')[0], 'flipInX animated');
						$('#create').addClass('response-show');
						handleSave();
						handleWidgets();
					}
				});
			});
		}
		$('#btn_show').on('click', e=>{
			e.preventDefault();
			if ($(formShow).valid()) {
				$.ajax({
					type: 'POST',
					url: formShow.action,
					data: $(formShow).serialize(),
					success: function(data) {
						$('#create').removeClass('response-hide');
						$('#create').html(eultTerimaHtml(data));
						KTUtil.animateClass(main_form.find('#create')[0], 'flipInX animated');
						$('#create').addClass('response-show');
						handleAddSub();
						handleDelete('sub');
					}
				});
				return false
			}
		});
		$('#btn-create').on('click', e=>{
			e.preventDefault();
			$.ajax({
				type: 'POST',
				url: e.currentTarget.href,
				data: $(formShow).serialize(),
				success: function(data) {
					$('#create').removeClass('response-hide');
					$('#create').html(eultTerimaHtml(data));
					KTUtil.animateClass(main_form.find('#create')[0], 'flipInX animated');
					$('#create').addClass('response-show');
					handleSave();
				}
			});
			return false
		});
		$('#btn_edit').on('click', e=>{
			e.preventDefault();
			if ($(formShow).valid()) {
				$.ajax({
					type: 'POST',
					url: e.currentTarget.href,
					data: $(formShow).serialize(),
					success: function(data) {
						$('#create').removeClass('response-hide');
						$('#create').html(eultTerimaHtml(data));
						KTUtil.animateClass(main_form.find('#create')[0], 'flipInX animated');
						$('#create').addClass('response-show');
						handleSave();
						handleDelete('category');
					}
				});
				return false
			}
		});
		$(formShow).validate({
			rules: {
				categoryNama : 'required'
			},
			messages: {
				categoryNama : 'Silakan Pilih'
			},
		});
	}

	return {
        // public functions
        init: function() {
        	handleEvent();
        }
    };
}();
$(document).ready(function() {
	refKategori.init();
});