<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelModulgroup;

/**
 * Master grup modul (porting CI3 Modulgroup.php).
 */
class Modulgroup extends BaseController
{
    protected ?string $judul = 'Modul Group';

    protected ?string $controllerName = 'modulgroup';

    protected ?string $pathPage = 'pages/modulgroup/';

    private ModelModulgroup $grup;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->grup     = new ModelModulgroup();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->grup->tabelRef('s_user_modul_group_ref');
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
        $data['datas']       = $this->grup->ambilSatu('s_user_modul_group_ref', ['susrmdgroupNama' => $terbuka]);

        return view($this->template, $data);
    }

    public function save()
    {
        $namaLama = (string) $this->request->getPost('susrmdgroupNamaOld');

        $aturanNama = $namaLama === '' ? 'required|is_unique[s_user_modul_group_ref.susrmdgroupNama]' : 'required';

        if (! $this->validate([
            'susrmdgroupNama'    => $aturanNama,
            'susrmdgroupDisplay' => 'required',
            'susrmdgroupIcon'    => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param = [
            'susrmdgroupNama'    => (string) $this->request->getPost('susrmdgroupNama'),
            'susrmdgroupDisplay' => (string) $this->request->getPost('susrmdgroupDisplay'),
            'susrmdgroupIcon'    => (string) $this->request->getPost('susrmdgroupIcon'),
        ];

        $proses = $namaLama === ''
            ? $this->grup->tambah('s_user_modul_group_ref', $param)
            : $this->grup->ubah('s_user_modul_group_ref', $param, ['susrmdgroupNama' => $namaLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->grup->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->grup->hapus('s_user_modul_group_ref', ['susrmdgroupNama' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->grup->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
