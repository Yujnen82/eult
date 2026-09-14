<!DOCTYPE html>
<html lang="en">

<head>
	<title></title>
	<style>
		@page {
			margin: 0.2in 1in 1in 1in;
		}

		body {
			font-family: "bookmanoldstyle";
			line-height: 1.5;
		}

		.title {
			font-size: 12pt;
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
			font-size: 11pt
		}

		.mb-10 {
			margin-bottom: 10px
		}

		.mt-30 {
			margin-top: 30px;
		}

		.mt-10 {
			margin-top: 10px;
		}

		.sub-title {
			font-size: 11pt
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
		<div class="text-center mb-10 mt-10">
			<p class="underline bold m-0 title">
				<?= $datas != FALSE ? (!empty($datas['suratJenis']) ? $datas['suratJenis'] : $judulSurat) : '' ?>
			</p>
			<span class="sub-title">NOMOR: <?= $datas != FALSE ? $datas['suratNomor'] : '' ?></span>
		</div>
		<div class="paragraf">
			<p>Yang bertanda tangan dibawah ini Rektor Universitas Mulawarman memberikan izin kepada:</p>
			<div class="elem-center ">
				<table class="paragraf">
					<tr>
						<td style="width:4.5cm">Nama</td>
						<td style="width:10px">:</td>
						<td class="bold uppercase"><?= $pegawai != false ? $pegawai['pegmNama'] : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">NIP</td>
						<td style="width:10px">:</td>
						<td><?= $pegawai != false ? $pegawai['pegmNIP'] : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Pangkat/Gol. Ruang </td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $pegawai != false ? $pegawai['golNama'] : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Jabatan</td>
						<td style="width:10px">:</td>
						<td class=""><?= $pegawai != false ? $pegawai['jabNama'] : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Unit</td>
						<td style="width:10px">:</td>
						<td class=""><?= $pegawai != false ? $pegawai['unitNama']  : '' ?></td>
					</tr>
				</table>
			</div>
			<p class="capitalize">
				<?= $datas != FALSE ? (!empty($datas['suratBody']) ? $datas['suratBody'] : $isiSurat) : '' ?>
			</p>
			<?php if (!empty($datas['ticketValidated'])) : ?>
				<div style="margin-left: 8cm">
					<p>Samarinda, <?= empty($datas['suratNomorTanggal']) ? '' : datetoindo($datas['suratNomorTanggal']) ?></p>
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