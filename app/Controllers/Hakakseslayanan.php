<?php

namespace App\Controllers;

use App\Models\ModelHakakseslayanan;

/**
 * Matriks hak akses layanan per grup (porting CI3 Hakakseslayanan.php).
 */
class Hakakseslayanan extends BaseController
{
    protected ?string $judul = 'Hak Akses Layanan';

    protected ?string $controllerName = 'hakakseslayanan';

    protected ?string $pathPage = 'pages/hakakseslayanan/';

    private ModelHakakseslayanan $matriks;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->matriks = new ModelHakakseslayanan();
    }

    public function index(): string
    {
        $data                 = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']      = ['user/' . $this->controllerName];
        $data['s_user_group'] = $this->matriks->tabelRef('s_user_group');
        $data['show_url']     = site_url($this->controllerName . '/response') . '/';

        return view($this->pathPage . 'response', $data);
    }

    public function response(): string
    {
        if (! $this->validate(['hakakses' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $grup = (string) $this->request->getPost('hakakses');

        return view($this->pathPage . 'response', [
            'datas'         => $this->matriks->byId($grup),
            'sgroupSgroupId' => $grup,
            'save_url'      => site_url($this->controllerName . '/save') . '/',
        ]);
    }

    public function save()
    {
        if (! $this->validate(['cekModul' => 'required', 'sgroupSgroupId' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $dipilih = $this->request->getPost('cekModul');
        $grup    = (string) $this->request->getPost('sgroupSgroupId');

        if (! is_array($dipilih) || count($dipilih) === 0) {
            eult_message_kirim('Pilih Menu!! Minimal 1', 'error');
        }

        $this->matriks->hapus('s_group_category', ['sgroupSgroupId' => $grup]);

        foreach ($dipilih as $modul) {
            $kategori = $this->matriks->ambilSatu('r_category_sub', ['sCatId' => $modul]);
            $this->matriks->tambah('s_group_category', [
                'sgroupCategoryId'    => $kategori !== false ? $kategori['sCatCategoryId'] : null,
                'sgroupSubCategoryId' => $modul,
                'sgroupSgroupId'      => $grup,
            ]);
        }

        eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
    }
}
