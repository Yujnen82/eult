<!-- BEGIN: Subheader -->
<!-- begin:: Content Head -->
<div class="kt-subheader  kt-grid__item" id="kt_subheader">
	<div class="kt-container  kt-container--fluid ">
		<div class="kt-subheader__main">
			<h3 class="kt-subheader__title"><?= $page_judul ?? '' ?></h3>
			<?php if (isset($breadcrumb->susrmdgroupDisplay)) : ?>
				<span class="kt-subheader__separator kt-subheader__separator--v"></span>
				<span class="kt-subheader__desc"><?= $breadcrumb->susrmdgroupDisplay ?></span>
			<?php endif; ?>

			<?php
			if (isset($breadcrumb->susrmdgroupDisplay, $breadcrumb->susrmodulNamaDisplay) && $breadcrumb->susrmdgroupDisplay != $breadcrumb->susrmodulNamaDisplay) :
				?>
				<span class="kt-subheader__separator kt-subheader__separator--v"></span>
				<span class="kt-subheader__desc"><?= $breadcrumb->susrmodulNamaDisplay ?></span>
				<?php
			endif;
			?>

			<?php
			if (isset($status_page)) :
				?>
				<span class="kt-subheader__separator kt-subheader__separator--v"></span>
				<span class="kt-subheader__desc"><?= $status_page ?></span>
				<?php
			endif;
			?>
		</div>
		<div class="kt-subheader__toolbar">
			
			
			<!-- begin:: Header Menu -->
			<!--begin: Notifications -->
			<div class="kt-header__topbar-item dropdown">
								<div class="kt-header__topbar-wrapper" data-toggle="dropdown" data-offset="30px,0px" aria-expanded="true">
									<span class="kt-header__topbar-icon">
										<b> Notifikasi </b> 
									</span>
								</div>
        <div class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropdown-menu-top-unround dropdown-menu-lg">
        	<form>
        		<!--begin: Head -->
        		<div class="kt-head kt-head--skin-dark kt-head--fit-x kt-head--fit-b" style="background-image: url(/assets/media/misc/bg-1.jpg)">
        			<h3 class="kt-head__title" style="padding-bottom: 10px">
        				Notifikasi
        			</h3>
        		</div>
        		<!--end: Head -->
        		<div class="tab-content">
        			<div class="tab-pane active show" id="topbar_notifications_notifications" role="tabpanel">
        				<div class="kt-notification kt-margin-t-10 kt-margin-b-10 kt-scroll" data-scroll="true" data-height="300" data-mobile-height="200">
        					<?php
					if (!empty($notiftiket)) {
						$i = 1;
						foreach ($notiftiket as $row) {
							$key = service('enkripsi')->encode($row['repliesTicketId']);
							?>
							<?php
								if ($row['jumlah'] != 0) {
								?>
							<a href="<?= ($detail_url ?? site_url('ticketing/detail/')) . $key ?>" class="kt-notification__item">
        								<div class="kt-notification__item-icon">
        									<i class="flaticon2-bell-1 kt-font-success"></i>
        								</div>
										
        								<div class="kt-notification__item-details">
        									<div class="kt-notification__item-title">
        										<?= $row['repliesTicketId'] ?>
        										<br>
												<?= $row['categoryNama'] ?>
        										<span class="btn btn-success btn-sm btn-bold btn-font-md"><?= $row['jumlah'] ?> Pesan Baru</span>
        									</div>
        								</div>
        							</a>
        							<?php
        							$i++;
        						}
        					}
						}
							else echo ('<span class="kt-header__topbar-icon" style="padding-left: 70px"> <b> Tidak ada notifikasi terbaru </b> </span>') 
        					?>
							
							
        				</div>
        			</div>
        		</div>
        	</form>
        </div>
    </div>

    <!--end: Notifications -->
<div class="kt-header-menu-wrapper" id="kt_header_menu_wrapper">
	<div id="kt_header_menu" class="kt-header-menu kt-header-menu-mobile  kt-header-menu--layout-default ">
		<ul class="kt-menu__nav ">
		</ul>
	</div>
</div>

    <div class="kt-subheader__wrapper">
    	<a href="#" class="btn kt-subheader__btn-daterange" id="kt_dashboard_daterangepicker" data-toggle="kt-tooltip" title="Today is good day!!" data-placement="left">
    		<span class="kt-subheader__btn-daterange-title" id="kt_dashboard_daterangepicker_title">Today:</span>&nbsp;
    		<span class="kt-subheader__btn-daterange-date" id="kt_dashboard_daterangepicker_date"><?= date('M d, Y') ?></span>
    		<i class="flaticon2-calendar-1"></i>
    	</a>
    </div>
</div>
</div>
</div>

<!-- end:: Content Head -->