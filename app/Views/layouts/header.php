<!-- begin:: Header -->
<div id="kt_header" class="kt-header kt-grid__item  kt-header--fixed ">
	<div>
		<div style="position:absolute;top:30%;left:14px;font-size:16px;font-weight:bold;color:aliceblue" class="kt-hidden-mobile">
			
		</div>
	</div>
	<!-- begin:: Header Topbar -->
	<div class="kt-header__topbar">
		<!--begin: User Bar -->
		<div class="kt-header__topbar-item kt-header__topbar-item--user">
			<div class="kt-header__topbar-wrapper" data-toggle="dropdown" data-offset="0px,0px">
				<div class="kt-header__topbar-user">
					<span class="kt-header__topbar-welcome kt-hidden-mobile">Hi,</span>
					<span class="kt-header__topbar-username kt-hidden-mobile"><?= substr($susrProfil, 0, strpos($susrProfil, ' ')) ?></span>
					<img class="kt-hidden" alt="Pic" src="<?= base_url(); ?>assets/media/users/default.jpg" />

					<!--use below badge element instead the user avatar to display username's first letter(remove kt-hidden class to display it) -->
					<span class="kt-badge kt-badge--username kt-badge--unified-success kt-badge--lg kt-badge--rounded kt-badge--bold"><?= substr($susrProfil, 0, 1) ?></span>
				</div>
			</div>
			<div class="dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropdown-menu-top-unround dropdown-menu-xl">

				<!--begin: Head -->
				<div class="kt-user-card kt-user-card--skin-dark kt-notification-item-padding-x" style="background-image: url(/assets/media/misc/bg-1.jpg)">
					<div class="kt-user-card__avatar">
						<img class="kt-hidden" alt="Pic" src="<?= base_url(); ?>assets/media/users/default.jpg" />

						<!--use below badge element instead the user avatar to display username's first letter(remove kt-hidden class to display it) -->
						<span class="kt-badge kt-badge--lg kt-badge--rounded kt-badge--bold kt-font-success"><?= substr($susrProfil, 0, 1) ?></span>
					</div>
					<div class="kt-user-card__name">
						<?= $susrProfil ?>
					</div>
					<div class="kt-user-card__badge">
						<span class="btn btn-success btn-sm btn-bold btn-font-md"><?= $susrSgroupNama ?></span>
					</div>
				</div>

				<!--end: Head -->

				<!--begin: Navigation -->
				<div class="kt-notification">
					<a href="javascript:void(0);" class="kt-notification__item">
						<div class="kt-notification__item-icon">
							<i class="flaticon2-calendar-3 kt-font-success"></i>
						</div>
						<div class="kt-notification__item-details">
							<div class="kt-notification__item-title kt-font-bold">
								Profile
							</div>
							<div class="kt-notification__item-time">
								My Profile
							</div>
						</div>
					</a>
					<?php if (session('logged_in')['susrSgroupNama_ori'] !== 'USER') : ?>
						<a href="/home/ubahpass" class="kt-notification__item">
							<div class="kt-notification__item-icon">
								<i class="flaticon2-gear kt-font-warning"></i>
							</div>
							<div class="kt-notification__item-details">
								<div class="kt-notification__item-title kt-font-bold">
									Ubah Password
								</div>
								<div class="kt-notification__item-time">
									Change Password
								</div>
							</div>
						</a>
						<a href="/home/ubahhakakses" class="kt-notification__item">
							<div class="kt-notification__item-icon">
								<i class="flaticon2-group kt-font-success"></i>
							</div>
							<div class="kt-notification__item-details">
								<div class="kt-notification__item-title kt-font-bold">
									Ubah Hak Akses
								</div>
								<div class="kt-notification__item-time">
									Change Role
								</div>
							</div>
						</a>
					<?php endif ?>
					<div class="kt-notification__custom kt-space-between">
						<a href="/home/logout" class="btn btn-label btn-label-brand btn-sm btn-bold">Sign Out</a>
					</div>
				</div>

				<!--end: Navigation -->
			</div>
		</div>

		<!--end: User Bar -->
	</div>

	<!-- end:: Header Topbar -->
</div>

<!-- end:: Header -->