<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelUnit;

/**
 * Master unit kerja (porting CI3 Unit.php).
 */
class Unit extends BaseController
{
    protected ?string $judul = 'Unit Kerja';

    protected ?string $controllerName = 'unit';

    protected ?string $pathPage = 'pages/unit/';

    private ModelUnit $unit;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->unit     = new ModelUnit();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->unit->tabelRef('s_unit');
        $data['create_url'] = site_url($this->controllerName . '/create') . '/';
        $data['update_url'] = site_url($this->controllerName . '/update') . '/';
        $data['delete_url'] = site_url($this->controllerName . '/delete') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data                = $this->getMaster($this->pathPage . 'form');
        $data['scripts']     = [];
        $data['save_url']    = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Create';
        $data['datas']       = false;

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka             = $this->enkripsi->decode($kunci);
        $data                = $this->getMaster($this->pathPage . 'form');
        $data['scripts']     = [];
        $data['save_url']    = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Update';
        $data['datas']       = $this->unit->ambilSatu('s_unit', ['unitId' => $terbuka]);

        return view($this->template, $data);
    }

    public function save()
    {
        $idLama = (string) $this->request->getPost('unitIdOld');

        $aturanKode = $idLama === '' ? 'required|is_unique[s_unit.unitKode]' : 'required';

        if (! $this->validate(['unitKode' => $aturanKode, 'unitNama' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param  = ['unitKode' => (string) $this->request->getPost('unitKode'), 'unitNama' => (string) $this->request->getPost('unitNama')];
        $proses = $idLama === ''
            ? $this->unit->tambah('s_unit', $param)
            : $this->unit->ubah('s_unit', $param, ['unitId' => $idLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->unit->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->unit->hapus('s_unit', ['unitId' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->unit->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
