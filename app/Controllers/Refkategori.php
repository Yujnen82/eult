<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelRefkategori;

/**
 * Master kategori + sub-layanan (porting CI3 Refkategori.php).
 */
class Refkategori extends BaseController
{
    protected ?string $judul = 'Refkategori';

    protected ?string $controllerName = 'refkategori';

    protected ?string $pathPage = 'pages/refkategori/';

    protected ?string $pathJs = 'referensi/';

    private ModelRefkategori $kategori;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->kategori = new ModelRefkategori();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data                  = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']       = [$this->pathJs . 'refkategori'];
        $data['create_url']    = site_url($this->controllerName . '/create') . '/';
        $data['edit_url']      = site_url($this->controllerName . '/edit') . '/';
        $data['show_url']      = site_url($this->controllerName . '/show') . '/';
        $data['s_user_group']  = $this->kategori->tabelRef('r_category');

        return view($this->template, $data);
    }

    public function create()
    {
        return $this->response->setJSON(['response' => view($this->pathPage . 'form', [
            'scripts'      => [],
            'page_judul'   => 'Layanan',
            'save_url'     => site_url($this->controllerName . '/save') . '/',
            'status_page'  => 'Create',
            'datas'        => false,
            's_user_group' => $this->kategori->tabelRef('s_user_group'),
        ])]);
    }

    public function edit()
    {
        $id = (string) $this->request->getPost('categoryNama');

        return $this->response->setJSON(['response' => view($this->pathPage . 'form', [
            'page_judul'   => 'Edit Layanan',
            'scripts'      => [],
            'save_url'     => site_url($this->controllerName . '/save') . '/',
            'delete_url'   => site_url($this->controllerName . '/delete') . '/',
            'status_page'  => 'Edit',
            'datas'        => $this->kategori->ambilSatu('r_category', ['categoryId' => $id]),
        ])]);
    }

    public function show()
    {
        $id = (string) $this->request->getPost('categoryNama');

        return $this->response->setJSON(['response' => view($this->pathPage . 'index_add', [
            'page_judul'   => 'Sub Layanan',
            'scripts'      => [],
            'create_url'   => site_url($this->controllerName . '/add_sub') . '/',
            'update_url'   => site_url($this->controllerName . '/edit_sub') . '/',
            'delete_url'   => site_url($this->controllerName . '/delete') . '/',
            'status_page'  => 'Show',
            'datas'        => $this->kategori->tabelRef('r_category_sub', ['sCatCategoryId' => $id]),
            'catId'        => $this->enkripsi->encode($id),
        ])]);
    }

    public function addSub(string $kunci = '')
    {
        return $this->response->setJSON(['response' => view($this->pathPage . 'form_add', [
            'page_judul'      => 'Sub Layanan',
            'scripts'         => [],
            'save_url'        => site_url($this->controllerName . '/save_sub') . '/',
            'status_page'     => 'Create',
            'datas'           => false,
            'sCatCategoryId'  => $this->enkripsi->decode($kunci),
        ])]);
    }

    public function editSub(string $kunci = '')
    {
        $id = $this->enkripsi->decode($kunci);

        return $this->response->setJSON(['response' => view($this->pathPage . 'form_add', [
            'page_judul'  => 'Sub Layanan',
            'scripts'     => [],
            'save_url'    => site_url($this->controllerName . '/save_sub') . '/',
            'status_page' => 'Create',
            'datas'       => $this->kategori->ambilSatu('r_category_sub', ['sCatId' => $id]),
        ])]);
    }

    public function saveSub()
    {
        $idLama = (string) $this->request->getPost('categoryIdOld');

        if (! $this->validate(['sCategoryNama' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param = [
            'sCatNama'      => (string) $this->request->getPost('sCategoryNama'),
            'sCatDisposisi' => (string) $this->request->getPost('sCategoryDisposisi'),
        ];

        if ($idLama === '') {
            $param['sCatCategoryId'] = $this->enkripsi->decode((string) $this->request->getPost('sCatCategoryId'));
            $proses                  = $this->kategori->tambah('r_category_sub', $param);
        } else {
            $param['sCatCategoryId'] = (string) $this->request->getPost('sCatCategoryId');
            $proses                  = $this->kategori->ubah('r_category_sub', $param, ['sCatId' => $idLama]);
        }

        if (! empty($proses)) {
            eult_message_kirim('Sub Layanan Berhasil Disimpan', 'success');
        }

        $galat = $this->kategori->dbAktif()->error();
        eult_message_kirim('Sub Layanan Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function update(string $kunci = ''): string
    {
        $terbuka          = $this->enkripsi->decode($kunci);
        $data             = $this->getMaster($this->pathPage . 'form');
        $data['scripts']  = [];
        $data['save_url'] = site_url($this->controllerName . '/save') . '/';
        $data['status_page']  = 'Update';
        $data['datas']        = $this->kategori->byId(['categoryId' => $terbuka]);
        $data['s_user_group'] = $this->kategori->tabelRef('s_user_group');

        return view($this->template, $data);
    }

    public function save()
    {
        $idLama = (string) $this->request->getPost('categoryIdOld');

        if (! $this->validate(['categoryNama' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $param  = ['categoryNama' => (string) $this->request->getPost('categoryNama')];
        $proses = $idLama === ''
            ? $this->kategori->tambah('r_category', $param)
            : $this->kategori->ubah('r_category', $param, ['categoryId' => $idLama]);

        if (! empty($proses)) {
            eult_message_kirim('Layanan Berhasil Disimpan', 'success');
        }

        $galat = $this->kategori->dbAktif()->error();
        eult_message_kirim('Layanan Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $event = (string) $this->request->getPost('event');

        if ($event === 'category') {
            $proses = $this->kategori->hapus('r_category', ['categoryId' => $kunci])
                && $this->kategori->hapus('r_category_sub', ['sCatCategoryId' => $kunci]);
        } else {
            $terbuka = $this->enkripsi->decode($kunci);
            $proses  = $this->kategori->hapus('r_category_sub', ['sCatId' => $terbuka]);
        }

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->kategori->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }
}
