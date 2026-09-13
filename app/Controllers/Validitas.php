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

        return view('pages/validitas/index', ['datas' => $datas]);
    }

    public function loadpdf(string $namaFile = '')
    {
        $lokasi = WRITEPATH . 'uploads/ticketing/' . basename($namaFile);

        return $this->response
            ->setHeader('Content-type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($namaFile) . '"')
            ->setBody(file_exists($lokasi) ? file_get_contents($lokasi) : '');
    }
}
