<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelHakakses;

/**
 * Master grup hak akses (porting CI3 Hakakses.php).
 */
class Hakakses extends BaseController
{
    protected ?string $judul = 'Hak Akses';

    protected ?string $controllerName = 'hakakses';

    protected ?string $pathPage = 'pages/hakakses/';

    private ModelHakakses $akses;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->akses    = new ModelHakakses();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->akses->tabelRef('s_user_group');
        $data['create_url'] = site_url($this->controllerName . '/create') . '/';
        $data['update_url'] = site_url($this->controllerName . '/update') . '/';
        $data['delete_url'] = site_url($this->controllerName . '/delete') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data               = $this->getMaster($this->pathPage . 'form');
        $data['scripts']    = [];
        $data['r_category'] = $this->akses->tabelRef('db_ult.ref_unit', '', 'unitUrut');
        $data['save_url']   = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Create';
        $data['datas']      = false;

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka            = $this->enkripsi->decode($kunci);
        $data               = $this->getMaster($this->pathPage . 'form');
        $data['scripts']    = [];
        $data['r_category'] = $this->akses->tabelRef('db_ult.ref_unit', '', 'unitUrut');
        $data['save_url']   = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Update';
        $data['datas']      = $this->akses->ambilSatu('s_user_group', ['sgroupNama' => $terbuka]);

        return view($this->template, $data);
    }

    public function save()
    {
        $namaLama = (string) $this->request->getPost('sgroupNamaOld');

        $aturanNama = $namaLama === '' ? 'required|is_unique[s_user_group.sgroupNama]' : 'required';

        if (! $this->validate(['sgroupNama' => $aturanNama, 'sgroupKeterangan' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param = [
            'sgroupNama'       => (string) $this->request->getPost('sgroupNama'),
            'sgroupKeterangan' => (string) $this->request->getPost('sgroupKeterangan'),
            'sgroupCategoryId' => (string) $this->request->getPost('sgroupCategoryId'),
        ];

        $proses = $namaLama === ''
            ? $this->akses->tambah('s_user_group', $param)
            : $this->akses->ubah('s_user_group', $param, ['sgroupNama' => $namaLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->akses->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->akses->hapus('s_user_group', ['sgroupNama' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->akses->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
