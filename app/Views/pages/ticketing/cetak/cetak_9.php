<!DOCTYPE html>
<html lang="en">

<head>
	<title></title>
	<style>
		@page {
			margin: 0.2in 0.7in 1in 0.7in;
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
			font-size: 10pt
		}
		.f-16 {
			font-size: 16pt
		}
		.f-12 {
			font-size: 12pt
		}
		.f-10 {
			font-size: 10pt
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
				<td width="15%">Nomor</td>
				<td width="60%">: <?= $datas != FALSE ? $datas['suratNomor'] : '' ?></td>
				<td rowspan="3" width="30%">
					Samarinda, <?= empty($datas['suratNomorTanggal']) ? '' : datetoindo($datas['suratNomorTanggal']) ?>
				</td>
			</tr>
			<tr>
				<td width="15%">Perihal</td>
				<td width="60%">: <?= $datas != FALSE ? (!empty($datas['suratJenis']) ? $datas['suratJenis'] : $judulSurat) : '' ?></td>
			</tr>
		</table>
		<div class="mb-20"></div>
		Yang bertanda tangan di bawah ini :
			<div class="elem-center ">
				<table class="paragraf">
					<tr>
						<td style="width:4.5cm">Nama</td>
						<td style="width:10px">:</td>
						<td class="bold uppercase">Prof. Dr. H. Moh. Bahzar, M.Si</td>
					</tr>
					<tr>
						<td style="width:4.5cm">Jabatan</td>
						<td style="width:10px">:</td>
						<td>Wakil Rektor Bidang Kemahasiswaan dan Alumni</td>
					</tr>
					<tr>
						<td style="width:4.5cm">Unit Kerja</td>
						<td style="width:10px">:</td>
						<td class="capitalize">Universitas Mulawarman</td>
					</tr>
				</table>
			</div>
		<div class="mb-20"></div>
		<div class="paragraf">
			Dengan ini memberikan dispensasi kepada mahasiswa berikut :
			<div class="elem-center ">
				<table class="paragraf">
					<tr>
						<td style="width:4.5cm">Nama</td>
						<td style="width:10px">:</td>
						<td class="bold uppercase"><?= $mahasiswa != false ? $mahasiswa->name : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">NIM</td>
						<td style="width:10px">:</td>
						<td><?= $mahasiswa != false ? $mahasiswa->nim : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Fakultas</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa != false ? $mahasiswa->faculty_name : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Program Studi</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa != false ? $mahasiswa->departement_name : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Jenjang Studi</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa != false ? $mahasiswa->degree : '' ?></td>
					</tr>
				</table>
			</div>
			<?= $datas != FALSE ? (!empty($datas['suratBody']) ? $datas['suratBody'] : $isiSurat) : '' ?>
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