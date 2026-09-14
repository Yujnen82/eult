<!DOCTYPE html>
<html lang="en">

<head>
	<title></title>
	<style>
		@page {
			margin: 0.2in 0.6in 0.6in 0.6in;
		}

		body {
			font-family: "bookmanoldstyle";
			line-height: 1.5;
		}

		.title {
			font-size: 10pt;
		}

		.text-center {
			text-align: center
		}

		.underline {
			text-decoration: underline;
		}

		.bold {
			font-weight: bold;
		}

		.uppercase {
			text-transform: uppercase;
		}

		.m-0 {
			margin: 0;
		}

		.paragraf {
			text-align: justify;
			font-size: 10pt
		}

		.mb-10 {
			margin-bottom: 10px
		}

		.mb-20 {
			margin-bottom: 10px
		}

		.mt-30 {
			margin-top: 30px;
		}

		.mt-10 {
			margin-top: 10px;
		}

		.sub-title {
			font-size: 10pt
		}

		.elem-center {
			padding-left: 1cm;

		}

		.capitalize {
			text-transform: capitalize;
		}

		td {
			vertical-align: top;
		}

		.footer {
			font-size: 6pt;
		}

		.borderbottom {
			border-bottom: double;
		}

		.subheader {
			text-align: center;
			font-size: 8pt
		}
	</style>
</head>

<body>
	<div class="header"></div>
	<div class="body">
		<?= $this->include('pages/ticketing/cetak/header') ?>
		<div class="borderbottom"></div>		
		<table class="sub-title">
			<tr>

				<td width="15%">Nomor</td>
				<td width="60%">: <?= $datas != FALSE ? $datas['suratNomor'] : '' ?></td>
				<td align="right" rowspan="3" width="20%">
					<?= empty($datas['suratNomorTanggal']) ? '' : datetoindo($datas['suratNomorTanggal']) ?>
				</td>
			</tr>			
			<tr>
				<td width="15%">Lampiran</td>
				<td width="60%">: <?= $datas['suratLampiran'] ?></td>
			</tr>			
			<tr>
				<td width="15%">Perihal</td>
				<td width="60%">: <?=$datas['suratPerihal']?></td>
			</tr>
		</table>
		<div class="mb-20"></div>
		<div class="sub-title"><?= $datas['suratTujuan'] ?></div>
		<div class="mb-20"></div>
		<div class="paragraf">
			<?=$datas['suratBody'] ?>
			<?php if (!empty($datas['ticketValidated'])) : ?>
				<div style="margin-left: 8cm">
					<p style="margin-bottom: 0px">a.n Rektor <br> <?= $datas['suratPejabatJabatan'] ?></p>
					<?php
					if (file_exists(WRITEPATH . 'uploads/qrcode/TTD_' . $datas['ticketTrackingId'] . '.png')) :
					?>
						<img width="100" src="<?= WRITEPATH ?>uploads/qrcode/TTD_<?= $datas['ticketTrackingId'] ?>.png" alt="">
					<?php endif; ?>
					<p style="margin-top: 0px"><span class="bold"><?= $datas['suratPejabatNama'] ?> </span><br> NIP. <?= $datas['suratPejabatNIP'] ?></p>
				</div>
			<?php endif ?>
		</div>
	</div>
</body>

</html>