<?php

namespace App\Controllers;

use App\Models\ModelHakaksesunit;

/**
 * Matriks hak akses unit per grup (porting CI3 Hakaksesunit.php).
 */
class Hakaksesunit extends BaseController
{
    protected ?string $judul = 'Hak Akses Unit';

    protected ?string $controllerName = 'hakaksesunit';

    protected ?string $pathPage = 'pages/hakaksesunit/';

    private ModelHakaksesunit $matriks;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->matriks = new ModelHakaksesunit();
    }

    public function index(): string
    {
        $data                 = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']      = ['user/' . $this->controllerName];
        $data['s_user_group'] = $this->matriks->tabelRef('s_user_group');
        $data['show_url']     = site_url($this->controllerName . '/response') . '/';

        return view($this->template, $data);
    }

    public function response(): string
    {
        if (! $this->validate(['hakakses' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $pecah = explode('|', (string) $this->request->getPost('hakakses'));

        return view($this->pathPage . 'response', [
            'datas'      => $this->matriks->byId($pecah[0], $pecah[1] ?? ''),
            'sgroupNama' => $pecah[0],
            'save_url'   => site_url($this->controllerName . '/save') . '/',
        ]);
    }

    public function save()
    {
        if (! $this->validate(['cekModul' => 'required', 'sgroupNama' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $dipilih = $this->request->getPost('cekModul');
        $grup    = (string) $this->request->getPost('sgroupNama');
        $isHome  = $this->request->getPost('cekIsHome') ?? [];

        if (! is_array($dipilih) || count($dipilih) === 0) {
            eult_message_kirim('Pilih Unit!! Minimal 1', 'error');
        }

        $this->matriks->hapus('s_user_group_unit', ['sgroupunitSgroupNama' => $grup]);

        foreach ($dipilih as $unit) {
            $this->matriks->tambah('s_user_group_unit', [
                'sgroupunitSgroupNama' => $grup,
                'sgroupunitUnitId'     => $unit,
                'sgroupunitUnitRead'   => 1,
                'sgroupunitIsHome'     => isset($isHome[$unit]) ? $isHome[$unit] : 0,
            ]);
        }

        eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
    }
}
