<?php

namespace App\Libraries;

use Mpdf\QrCode\Output\Png;
use Mpdf\QrCode\QrCode;

/**
 * Generator QR validasi surat (porting CI3 Qrcode_generator.php).
 * Output dialihkan dari ../upload_file/qrcode/ ke writable/uploads/qrcode/.
 */
class GeneratorQr
{
    public function generate(string $konten, string $namaFile): string
    {
        $tujuan = WRITEPATH . 'uploads/qrcode/';
        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        $qr     = new QrCode($konten);
        $output = new Png();

        $lokasi = $tujuan . $namaFile . '.png';
        file_put_contents($lokasi, $output->output($qr, 255, [255, 255, 255], [0, 0, 0]));

        return $lokasi;
    }
}
