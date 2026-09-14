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
			<tr>
				<td width="10%">Nomor</td>
				<td width="50%">: <?= $noSurat?></td>
				<td align="right" rowspan="3" width="40%">
					Samarinda, <?= $datas!=false ? datetoindo($datas['suratTanggal']) : datetoindo(date('Y-m-d')) ?>
				</td>
			</tr>
			<tr>
				<td width="10%">Lampiran</td>
				<td width="50%">: 1 (satu) lembar</td>
			</tr>
			<tr>
				<td width="10%">Perihal</td>
				<td width="50%">: Surat Pengantar Pengurusan Kehilangan E-KTM </td>
			</tr>			
		</table>
		<div class="mb-20"></div>
		<div class="sub-title"><p>Kepada Yth.<br />Pimpinan Kantor Cabang Pembantu<br /><?=$bank?> Kampus Unmul Gunung Kelua<br />d/a. Jalan Kuaro<br />di -<br />&nbsp; &nbsp; &nbsp; &nbsp;Samarinda</p></div>
		<div class="mb-20"></div>
		<div class="paragraf">
			<p>Menindaklanjuti Surat Keterangan dari Wakil Dekan Bidang Akademik, Kemahasiswaan dan Alumni Fakultas <?=ucwords(strtolower($mahasiswa->program_studi->nama_fakultas))?> Universitas Mulawarman Nomor <?=$noSuratPemohon?> tanggal <?=datetoindo($tglSuratPemohon)?>, yang menerangkan bahwa telah hilang E-KTM Sdr :</p>			
			<div class="elem-center ">
				<table class="paragraf">
					<tr>
						<td style="width:4.5cm">Nama</td>
						<td style="width:10px">:</td>
						<td class="bold uppercase"><?= $mahasiswa != false ? $mahasiswa->peserta_didik->nama : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">NIM</td>
						<td style="width:10px">:</td>
						<td><?= $mahasiswa != false ? $mahasiswa->nim : '' ?></td>
					</tr>
                    <tr>
						<td style="width:4.5cm">Tempat/Tanggal Lahir</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa->peserta_didik->tempat_lahir != '' ? $mahasiswa->peserta_didik->tempat_lahir.', ' : '' ?><?= $mahasiswa->peserta_didik->tanggal_lahir != '' ? datetoindo(substr($mahasiswa->peserta_didik->tanggal_lahir, 0,10)) : '' ?> </td>
					</tr>
					<tr>
						<td style="width:4.5cm">Fakultas</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa != false ? $mahasiswa->program_studi->nama_fakultas : '' ?></td>
					</tr>
					<tr>
						<td style="width:4.5cm">Program Studi</td>
						<td style="width:10px">:</td>
						<td class="capitalize"><?= $mahasiswa != false ? $mahasiswa->program_studi->nama : '' ?></td>
					</tr>
				</table>
			</div>
			<p>Berkaitan dengan hal tersebut di atas, mohon kiranya pihak <?=$bank?> agar dapat memproses pergantian E-KTM dimaksud, sesuai ketentuan yang berlaku di <?=$bank?> cabang Samarinda.</p>
			<p>Demikian atas perhatian dan kerjasamanya disampaikan terima kasih.</p>			
			<div style="margin-left: 8cm">				
				<p style="margin-bottom: 0px"><?= $datas!=false ?$datas['suratPejabatJabatanDraft']: $suratPejabatJabatan?>,</p>	
				<?php
					if (file_exists(WRITEPATH . 'uploads/qrcode/OUTPUT_'.$id. '.png')) :
					?>
						<img width="100" src="<?= WRITEPATH ?>uploads/qrcode/OUTPUT_<?=$datas!=false ?$datas['ticketTrackingId']:$id ?>.png" alt="">
					<?php endif; ?>
				<p style="margin-top: 0px"><span class="bold"><?= $datas!=false ?$datas['suratPejabatNamaDraft'] : $suratPejabatNama?> </span><br> NIP. <?= $datas!=false ?$datas['suratPejabatNIPDraft']:$suratPejabatNIP ?></p>
			</div>	
		</div>
	</div>
</body>

</html>