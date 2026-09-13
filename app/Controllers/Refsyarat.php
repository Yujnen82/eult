<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelRefsyarat;

/**
 * Referensi persyaratan layanan (porting CI3 Refsyarat.php).
 */
class Refsyarat extends BaseController
{
    protected ?string $judul = 'Referensi Persyaratan Layanan';

    protected ?string $controllerName = 'refsyarat';

    protected ?string $pathPage = 'pages/refsyarat/';

    private ModelRefsyarat $syarat;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->syarat   = new ModelRefsyarat();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->syarat->semua();
        $data['create_url'] = site_url($this->controllerName . '/create') . '/';
        $data['update_url'] = site_url($this->controllerName . '/update') . '/';
        $data['delete_url'] = site_url($this->controllerName . '/delete') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data                  = $this->getMaster($this->pathPage . 'form');
        $data['scripts']       = [];
        $data['save_url']      = site_url($this->controllerName . '/save') . '/';
        $data['status_page']   = 'Create';
        $data['datas']         = false;
        $data['ref_layanan']   = $this->syarat->tabelRef('db_ult.ref_layanan');

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka             = $this->enkripsi->decode($kunci);
        $data                = $this->getMaster($this->pathPage . 'form');
        $data['scripts']     = [];
        $data['save_url']    = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Update';
        $data['datas']       = $this->syarat->byId(['berkasId' => $terbuka]);
        $data['ref_layanan'] = $this->syarat->tabelRef('db_ult.ref_layanan');

        return view($this->template, $data);
    }

    public function save()
    {
        $idLama = (string) $this->request->getPost('berkasIdOld');

        $param = [
            'berkasidLayanan'   => (string) $this->request->getPost('berkasidLayanan'),
            'berkasNama'        => (string) $this->request->getPost('berkasNama'),
            'berkasKeterangan'  => (string) $this->request->getPost('berkasKeterangan'),
        ];

        $proses = $idLama === ''
            ? $this->syarat->tambah('r_berkas_layanan', $param)
            : $this->syarat->ubah('r_berkas_layanan', $param, ['berkasId' => $idLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->syarat->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->syarat->hapus('r_berkas_layanan', ['berkasId' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->syarat->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
