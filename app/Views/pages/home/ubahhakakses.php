<!-- BEGIN: Subheader -->
<?= $this->include('layouts/subheader') ?>
<!-- END: Subheader -->

<!-- begin:: Content -->
<div class="kt-container  kt-container--fluid  kt-grid__item kt-grid__item--fluid">
	<div class="row">
		<div class="col-md-12">
			<div id="response"></div>
			<!--begin::Portlet-->
			<div class="kt-portlet">
				<div class="kt-portlet__head">
					<div class="kt-portlet__head-label">
						<h3 class="kt-portlet__head-title">
							<?=strtoupper($judul)?>
						</h3>
					</div>
				</div>

				<!--begin::Form-->
				<form class="kt-form" action="<?=$save_url?>" method="post" id="form_change_role">
				    <?= csrf_field() ?>
					<div class="kt-portlet__body">
						<div class="form-group">
							<label>Username</label>
							<input type="text" class="form-control" name="susrNama" placeholder="Username" aria-describedby="Username" value="<?=$susrNama!=false?$susrNama:''?>" readonly>
						</div>
						<div class="form-group">
							<label>Name</label>
							<input type="text" class="form-control" name="susrProfil" placeholder="Name" aria-describedby="Name" value="<?=$susrProfil!=false?$susrProfil:''?>" readonly>
						</div>
						<div class="form-group">
							<label>Role</label>
							<select class="form-control m-select2" name="hakakses">
									<option value=""></option>
							<?php 
							foreach($hakakses as $row):
								echo '<option value="'.$row['sgroupNama'].'" >'.$row['sgroupNama'].' - '.$row['sgroupKeterangan'].'</option>';
							endforeach;
							?>
							</select>
							
						</div>
					</div>
					<div class="kt-portlet__foot">
						<div class="kt-form__actions">
							<button type="submit" id="btn_save" class="btn btn-primary">Save</button>
							<button type="reset" class="btn btn-secondary">Cancel</button>
						</div>
					</div>
				</form>

				<!--end::Form-->
			</div>

			<!--end::Portlet-->
		</div>
	</div>
</div>