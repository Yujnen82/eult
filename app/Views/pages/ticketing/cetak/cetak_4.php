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
		<table>
			<tr class="sub-title">
				<td width="20%">Nomor</td>
				<td>:</td>
				<td width="60%"><?= $datas != FALSE ? $datas['suratNomor'] : '' ?></td>
				<td align="right" rowspan="3" width="15%">
					<?= empty($datas['suratNomorTanggal']) ? '' : datetoindo($datas['suratNomorTanggal']) ?>
				</td>
			</tr>
			<tr>
				<td width="20%">Perihal</td>
				<td>:</td>
				<td width="60%"><?= $datas != FALSE ? (!empty($datas['suratJenis']) ? $datas['suratJenis'] : $judulSurat) : '' ?></td>
			</tr>
			<tr>
				<td width="20%">Lampiran</td>
				<td>:</td>
				<td width="60%"><?= $datas != FALSE ? (!empty($datas['suratLampiran']) ? $datas['suratLampiran'] : $lampSurat) : '' ?></td>
			</tr>
		</table>
		<div class="mb-20"></div>
		<div class="sub-title"><?= $datas != FALSE ? (!empty($datas['suratTujuan']) ? $datas['suratTujuan'] : $tujuanSurat) : '' ?></div>
		<div class="mb-20"></div>
		<div class="paragraf">
			Pegawai berikut :
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
			<?= $datas != FALSE ? (!empty($datas['suratBody']) ? $datas['suratBody'] : $isiSurat) : '' ?>
			<?php if (!empty($datas['ticketValidated'])) : ?>
				<div style="margin-left: 8cm">
					<p style="margin-bottom: 0px">a.n Rektor <br> <?= $datas['suratPejabatJabatan'] ?></p>
					<?php
					if (file_exists('../upload_file/qrcode/TTD_' . $datas['ticketTrackingId'] . '.png')) :
					?>
						<img width="100" src="../upload_file/qrcode/TTD_<?= $datas['ticketTrackingId'] ?>.png" alt="">
					<?php endif; ?>
					<p style="margin-top: 0px"><span class="bold"><?= $datas['suratPejabatNama'] ?> </span><br> NIP. <?= $datas['suratPejabatNIP'] ?></p>
				</div>
			<?php endif ?>
		</div>
	</div>
</body>

</html>