<?php

namespace App\Controllers;

use App\Models\ModelHakaksesmodul;

/**
 * Matriks hak akses modul per grup (porting CI3 Hakaksesmodul.php).
 */
class Hakaksesmodul extends BaseController
{
    protected ?string $judul = 'Hak Akses Modul';

    protected ?string $controllerName = 'hakaksesmodul';

    protected ?string $pathPage = 'pages/hakaksesmodul/';

    private ModelHakaksesmodul $matriks;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->matriks = new ModelHakaksesmodul();
    }

    public function index(): string
    {
        $data                  = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']       = ['user/' . $this->controllerName];
        $data['s_user_group']  = $this->matriks->tabelRef('s_user_group');
        $data['show_url']      = site_url($this->controllerName . '/response') . '/';

        return view($this->template, $data);
    }

    public function response(): string
    {
        if (! $this->validate(['hakakses' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $grup = (string) $this->request->getPost('hakakses');

        return view($this->pathPage . 'response', [
            'datas'      => $this->matriks->byId($grup),
            'sgroupNama' => $grup,
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

        if (! is_array($dipilih) || count($dipilih) === 0) {
            eult_message_kirim('Pilih Menu!! Minimal 1', 'error');
        }

        $this->matriks->hapus('s_user_group_modul', ['sgroupmodulSgroupNama' => $grup]);

        foreach ($dipilih as $modul) {
            $this->matriks->tambah('s_user_group_modul', [
                'sgroupmodulSgroupNama'     => $grup,
                'sgroupmodulSusrmodulNama'  => $modul,
                'sgroupmodulSusrmodulRead'  => 1,
            ]);
        }

        eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
    }
}
