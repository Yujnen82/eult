<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelTicketing;

/**
 * Validasi surat publik via QR (porting CI3 Validitas.php).
 */
class Validitas extends BaseController
{
    protected ?string $judul = 'Validasi Surat';

    protected ?string $controllerName = 'validitas';

    protected ?string $pathPage = 'pages/validitas/';

    private ModelTicketing $tiket;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->tiket    = new ModelTicketing();
        $this->enkripsi = new Enkripsi();
    }

    public function index(string $kunci = ''): string
    {
        $terbuka = $this->enkripsi->decode($kunci);
        $datas   = $this->tiket->byId(['ticketTrackingId' => $terbuka]);

        return view('pages/validitas/index', [
            'datas'       => $datas,
            'loadpdf_url' => site_url('validitas/loadpdf') . '/' . $kunci,
        ]);
    }

    /**
     * Menyajikan PDF surat milik tiket yang kuncinya discan dari QR.
     * Nama berkas ditentukan dari relasi tiket (bukan dari URL).
     */
    public function loadpdf(string $kunci = '')
    {
        $nomorTiket = $this->enkripsi->decode($kunci);
        $arsip      = false;

        if ($nomorTiket !== false) {
            $arsip = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $nomorTiket, 'archiveJenis' => 'TTD']);

            if ($arsip === false) {
                $arsip = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $nomorTiket, 'archiveJenis' => 'OUTPUT']);
            }
        }

        if ($arsip === false) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $lokasi = WRITEPATH . 'uploads/ticketing/' . basename((string) $arsip['archiveFile']);

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        return $this->response
            ->setHeader('Content-type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($lokasi) . '"')
            ->setBody(file_get_contents($lokasi));
    }
}
