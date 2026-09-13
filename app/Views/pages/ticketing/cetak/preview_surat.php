<!DOCTYPE html>
<html lang="en">
<head>
	<title></title>
	<style>
		@page { margin: 2in 1in 1in 1in;}
		body {
			font-family: "bookmanoldstyle";
			line-height: 1.5;
		}
		.title{
			font-size:12pt;
		}
		.text-center{
			text-align: center
		}
		.underline{
			text-decoration: underline;
		}
		.bold{
			font-weight: bold;
		}
		.uppercase{
			text-transform: uppercase;
		}
		.m-0{
			margin:0;
		}
		.paragraf{
			text-align: justify;
			font-size: 12pt
		}
		.mb-10{
			margin-bottom: 10px
		}
		.mt-10 {
			margin-top: 10px;
		}
		.mt-30{
			margin-top:30px;
		}
		.sub-title{
			font-size: 11pt
		}
		.elem-center{
			padding-left: 1cm;
			
		}
		.capitalize{
			text-transform: capitalize;
		}
		td{
			vertical-align: top;
		}

		.footer {
			font-size:6pt;
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
	<div class="body" style="margin-top: 115px">
		<table align="center">
		    <tr align="center">
		      <td rowspan="5"><img src="<?php echo base_url(); ?>assets/media/logos/logo-unmul.png" width="80" height="80"/><br /></td>
		      <td class="bold uppercase text-center"><b>KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET DAN TEKNOLOGI</b></td>
		      <td rowspan="5"></td>
		    </tr>
		    <tr align="center"><td class="bold uppercase text-center"><b>UNIVERSITAS MULAWARMAN</b></td></tr>	   
		    <tr align="center"><td class="capitalize subheader">Alamat: Rektorat Kampus Gunung Kelua Jl. Kuaro Kotak Pos: 1068 Telp: (0541) 741118</td></tr>
		    <tr align="center"><td class="capitalize subheader">Fax : (0541) 747479 Samarinda 75119 </td></tr>
		    <tr align="center"><td class="capitalize subheader">E-mail: rektorat@unmul.ac.id Website: http://www.unmul.ac.id</td></tr>
		  </table>
		  <div class="borderbottom"></div>		
		<div class="text-center mb-10 mt-10">
			<p class="underline bold m-0 title"><?=$datas!=FALSE?$datas['suratJenis']:$this->session->judulSurat?></p>
			<span class="sub-title">NOMOR: <?=$datas!=FALSE?$datas['suratNomor']:''?></span>
		</div>
		<?php if ($identitas == 'MHS'){ ?>
			<div class="paragraf">
				<p>Rektor Universitas Mulawarman, dengan ini menerangkan bahwa :</p>
				<div class="elem-center ">
					<table class="paragraf">
						<tr>
							<td style="width:4.5cm">Nama</td>
							<td style="width:10px">:</td>
							<td class="bold uppercase"><?=$datas!=FALSE?$mahasiswa->name:''?></td>
						</tr>
						<tr>
							<td style="width:4.5cm">NIM</td>
							<td style="width:10px">:</td>
							<td ><?=$datas!=FALSE?$mahasiswa->nim:''?></td>
						</tr>
						<tr>
							<td style="width:4.5cm">Fakultas</td>
							<td style="width:10px">:</td>
							<td class="capitalize"><?=$datas!=FALSE?$mahasiswa->faculty_name:''?></td>
						</tr>
						<tr>
							<td style="width:4.5cm">Program Studi</td>
							<td style="width:10px">:</td>
							<td class="capitalize"><?=$datas!=FALSE?$mahasiswa->departement_name:''?></td>
						</tr>
						<tr>
							<td style="width:4.5cm">Jenjang Studi</td>
							<td style="width:10px">:</td>
							<td class="capitalize"><?=$datas!=FALSE?$mahasiswa->degree:''?></td>
						</tr>
					</table>
				</div>
				<p class="capitalize">
					<?=$datas!=FALSE?$datas['suratBody']:$this->session->isiSurat?>
				</p>
				<?php if (!empty($datas['ticketValidated'])){ ?>
					<div style="margin-left: 8cm">
						<p>Samarinda, <?=empty($datas['ticketSuratCreated'])?'':datetoindo($datas['ticketSuratCreated'])?></p>
						<p>a.n Rektor <br> <?=$datas['suratPejabatJabatan']?></p>
						<img width="100" src="../upload_file/qrcode/BAZK-HGRY-001.png" alt="">
						<p><span class="bold"><?=$datas['suratPejabatNama']?> </span><br> NIP. <?=$datas['suratPejabatNIP']?></p>
					</div>	
				<?php }else { ?>
					<div style="margin-left: 8cm">
					<p>Samarinda, <?= empty($datas['suratTanggal']) ? '' : datetoindo($datas['suratTanggal']) ?></p>
					<p style="margin-bottom: 0px"><?= empty($datas['suratPejabatJabatanAnDraft']) ? '' : $datas['suratPejabatJabatanAnDraft'] ?> <br> <?= $datas['suratPejabatJabatanDraft'] ?></p>					
					<p style="margin-top: 0px"><span class="bold"><?= $datas['suratPejabatNamaDraft'] ?> </span><br> NIP. <?= $datas['suratPejabatNIPDraft'] ?></p>
				</div>
				<?php }?>		
			</div>
		<?php }else{ ?>
			<div class="paragraf">
				<p>Yang bertanda tangan di bawah ini Rektor Universitas Mulawarman memberikan izin kepada Pegawai Negeri Sipil :</p>
				<div class="elem-center ">
					<table class="paragraf">
						<tr>
							<td style="width:5.5cm">Nama</td>
							<td style="width:10px">:</td>
							<td class="bold uppercase"><?=$datas!=FALSE?$mahasiswa->pegmNama:''?></td>
						</tr>
						<tr>
							<td style="width:5.5cm">NIP</td>
							<td style="width:10px">:</td>
							<td ><?=$datas!=FALSE?$mahasiswa->pegmNIP:''?></td>
						</tr>
						<tr>
							<td style="width:5.5cm">Pangkat / Golongan, Ruang</td>
							<td style="width:10px">:</td>
							<td><?=$datas!=FALSE?$mahasiswa->golNama:''?></td>
						</tr>
						<tr>
							<td style="width:5.5cm">Jabatan / Pekerjaan</td>
							<td style="width:10px">:</td>
							<td><?=$datas!=FALSE?$mahasiswa->jabNama:''?></td>
						</tr>
						<tr>
							<td style="width:5.5cm">Unit Kerja</td>
							<td style="width:10px">:</td>
							<td><?=$datas!=FALSE?$mahasiswa->unitNama:''?></td>
						</tr>
					</table>
				</div>
				<p class="capitalize">
					<?=$datas!=FALSE?$datas['suratBody']:$this->session->isiSurat?>
				</p>
				<?php if (!empty($datas['ticketValidated'])): ?>
					<div style="margin-left: 9cm">
						<p>Samarinda, <?=empty($datas['ticketSuratCreated'])?'':datetoindo($datas['ticketSuratCreated'])?></p>
						<p>a.n Rektor <br> <?=$datas['suratPejabatJabatan']?></p>
						<!-- <img width="100" src="<?=$load_image?>" alt=""> -->
						<p><span class="bold"><?=$datas['suratPejabatNama']?> </span><br> NIP. <?=$datas['suratPejabatNIP']?></p>
					</div>	
				<?php endif ?>		
			</div>
		<?php } ?>

	</div>
</body>
</html>