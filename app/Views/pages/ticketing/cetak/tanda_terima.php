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
      font-size: 10pt
    }

    .subheader {
      text-align: center;
      font-size: 8pt
    }

    .mb-10 {
      margin-bottom: 10px
    }

    .mt-30 {
      margin-top: 30px;
    }

    .sub-title {
      font-size: 11pt;
    }

    .elem-center {
      padding-left: 1cm;

    }

    .capitalize {
      text-transform: capitalize;
    }

    .height-5 {
      height: 5cm
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
  </style>
</head>

<body>
  <?= $this->include('pages/ticketing/cetak/header') ?>
  <div class="borderbottom"></div>
  <div class="body">
    <div class="text-center mb-10">
      <p class="underline bold title m-0">TANDA TERIMA</p>
      <span class="sub-title">Nomor Tiket : <b><?= $datas['ticketTrackingId'] ?></b></span>
    </div>
    <div class="text-center">
      <img width="100" src="../upload_file/qrcode/<?= $datas['ticketTrackingId'] ?>.png" alt="">
    </div>
    
    <div class="paragraf">
      <p>Telah diterima berkas permohonan :</p>
      <div class="elem-center ">
        <table>
          <tr>
            <td style="width:4.5cm">Pelayanan</td>
            <td style="width:10px">:</td>
            <td class="bold uppercase"><?= $datas['sCatNama'] . " (" . $datas['categoryNama'] . ")" ?></td>
          </tr>
          <tr>
            <td style="width:4.5cm">Nama Pemohon</td>
            <td style="width:10px">:</td>
            <td class="capitalize"><?= $datas['ticketName'] ?></td>
          </tr>
          <tr>
            <td style="width:4.5cm">NIM</td>
            <td style="width:10px">:</td>
            <td class="capitalize"><?= $datas['ticketIdentitas'] ?></td>
          </tr>
          <tr>
            <td style="width:4.5cm">No. Handphone</td>
            <td style="width:10px">:</td>
            <td class="capitalize"><?= $datas['ticketNoHp'] ?></td>
          </tr>
          <tr>
            <td style="width:4.5cm">E-mail</td>
            <td style="width:10px">:</td>
            <td><?= $datas['ticketEmail'] ?></td>
          </tr>
          <tr>
            <td style="width:4.5cm">Tanggal Terima</td>
            <td style="width:10px">:</td>
            <td><?= daytoindo(date('D', strtotime($datas['ticketCreated']))) . ", " . datetoindo($datas['ticketCreated']) ?></td>
          </tr>
        </table>
      </div>
    </div><br><br>
  </div>
</body>

</html>