<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelModul;

/**
 * Master modul (porting CI3 Modul.php).
 */
class Modul extends BaseController
{
    protected ?string $judul = 'Modul';

    protected ?string $controllerName = 'modul';

    protected ?string $pathPage = 'pages/modul/';

    private ModelModul $modul;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->modul    = new ModelModul();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->modul->semua();
        $data['create_url'] = site_url($this->controllerName . '/create') . '/';
        $data['update_url'] = site_url($this->controllerName . '/update') . '/';
        $data['delete_url'] = site_url($this->controllerName . '/delete') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data                              = $this->getMaster($this->pathPage . 'form');
        $data['scripts']                   = [];
        $data['save_url']                  = site_url($this->controllerName . '/save') . '/';
        $data['status_page']               = 'Create';
        $data['datas']                     = false;
        $data['s_user_modul_group_ref']    = $this->modul->tabelRef('s_user_modul_group_ref');

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka                           = $this->enkripsi->decode($kunci);
        $data                              = $this->getMaster($this->pathPage . 'form');
        $data['scripts']                   = [];
        $data['save_url']                  = site_url($this->controllerName . '/save') . '/';
        $data['status_page']               = 'Update';
        $data['datas']                     = $this->modul->byId(['susrmodulNama' => $terbuka]);
        $data['s_user_modul_group_ref']    = $this->modul->tabelRef('s_user_modul_group_ref');

        return view($this->template, $data);
    }

    public function save()
    {
        $namaLama = (string) $this->request->getPost('susrmodulNamaOld');

        $aturanNama = $namaLama === '' ? 'required|is_unique[s_user_modul_ref.susrmodulNama]' : 'required';

        if (! $this->validate([
            'susrmodulNama'            => $aturanNama,
            'susrmodulSusrmdgroupNama' => 'required',
            'susrmodulNamaDisplay'     => 'required',
            'susrmodulIsLogin'         => 'required',
            'susrmodulUrut'            => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param = [
            'susrmodulNama'            => (string) $this->request->getPost('susrmodulNama'),
            'susrmodulSusrmdgroupNama' => (string) $this->request->getPost('susrmodulSusrmdgroupNama'),
            'susrmodulNamaDisplay'     => (string) $this->request->getPost('susrmodulNamaDisplay'),
            'susrmodulIsLogin'         => (string) $this->request->getPost('susrmodulIsLogin'),
            'susrmodulUrut'            => (string) $this->request->getPost('susrmodulUrut'),
        ];

        $proses = $namaLama === ''
            ? $this->modul->tambah('s_user_modul_ref', $param)
            : $this->modul->ubah('s_user_modul_ref', $param, ['susrmodulNama' => $namaLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->modul->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->modul->hapus('s_user_modul_ref', ['susrmodulNama' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->modul->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
