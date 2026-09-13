<?php

namespace App\Controllers;

use App\Models\ModelLaporan;

/**
 * Laporan per layanan (porting CI3 Laporanlayanan.php).
 */
class Laporanlayanan extends BaseController
{
    protected ?string $judul = 'Laporan Layanan ULT';

    protected ?string $controllerName = 'laporanlayanan';

    protected ?string $pathPage = 'pages/laporanlayanan/';

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
        $data['category']   = $this->laporan->tabelRef('db_ult.ref_unit', '', 'unitUrut');
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
        $kategori = (string) $this->request->getPost('layanan');
        session()->set('tanggal', $tanggal);

        $pecah        = explode('/', str_replace(' ', '', $tanggal));
        $tanggalAwal  = date('Y-m-d', strtotime($pecah[0]));
        $tanggalAkhir = date('Y-m-d', strtotime($pecah[1]));

        if (($sesi['susrSgroupNama'] ?? '') === 'ADMIN' || strpos($sesi['susrSgroupNama'] ?? '', 'OPERATOR') !== false) {
            $datas = $this->laporan->getLaporanLayanan($tanggalAwal, $tanggalAkhir, $sesi['susrSgroupNama'], $kategori);
        } else {
            $datas = $this->laporan->getLaporanLayanan($tanggalAwal, $tanggalAkhir, $sesi['susrSgroupNama'] ?? '');
        }

        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['user_group'] = $sesi['susrSgroupNama'] ?? '';
        $data['awal']       = $tanggalAwal;
        $data['akhir']      = $tanggalAkhir;
        $data['datas']      = $datas;

        return view($this->pathPage . 'response', $data);
    }
}
