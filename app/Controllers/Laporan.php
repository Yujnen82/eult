<?php

namespace App\Controllers;

use App\Models\ModelLaporan;

/**
 * Laporan tiket per tanggal (porting CI3 Laporan.php).
 */
class Laporan extends BaseController
{
    protected ?string $judul = 'Laporan';

    protected ?string $controllerName = 'laporan';

    protected ?string $pathPage = 'pages/laporan/';

    protected ?string $pathJs = 'laporan/';

    private ModelLaporan $laporan;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->laporan = new ModelLaporan();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [$this->pathJs . 'laporan'];
        $data['tanggal']    = session()->get('tanggal');
        $data['user_group'] = $this->sesiLogin();
        $data['show_url']   = site_url($this->controllerName . '/response') . '/';

        return view($this->template, $data);
    }

    public function response(): string
    {
        if (! $this->validate(['rentangTanggal' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $sesi     = $this->sesiLogin() ?? [];
        $tanggal  = (string) $this->request->getPost('rentangTanggal');
        session()->set('tanggal', $tanggal);

        $pecah        = explode('/', str_replace(' ', '', $tanggal));
        $tanggalAwal  = date('Y-m-d', strtotime($pecah[0]));
        $tanggalAkhir = date('Y-m-d', strtotime($pecah[1]));
        $datas        = $this->laporan->getLaporan([$tanggalAwal, $tanggalAkhir]);

        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['user_group'] = $sesi['susrSgroupNama'] ?? '';
        $data['awal']       = $tanggalAwal;
        $data['akhir']      = $tanggalAkhir;
        $data['datas']      = $datas;

        return view($this->pathPage . 'response', $data);
    }
}
