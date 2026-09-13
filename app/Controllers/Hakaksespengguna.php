<?php

namespace App\Controllers;

use App\Models\ModelHakaksespengguna;

/**
 * Matriks keanggotaan grup per pengguna (porting CI3 Hakaksespengguna.php).
 */
class Hakaksespengguna extends BaseController
{
    protected ?string $judul = 'Hak Akses Modul';

    protected ?string $controllerName = 'hakaksespengguna';

    protected ?string $pathPage = 'pages/hakaksespengguna/';

    private ModelHakaksespengguna $matriks;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->matriks = new ModelHakaksespengguna();
    }

    public function index(): string
    {
        $data             = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']  = ['user/' . $this->controllerName];
        $data['s_user']   = $this->matriks->tabelRef('s_user');
        $data['show_url'] = site_url($this->controllerName . '/response') . '/';

        return view($this->template, $data);
    }

    public function response(): string
    {
        if (! $this->validate(['pengguna' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $pengguna = (string) $this->request->getPost('pengguna');

        return view($this->pathPage . 'response', [
            'datas'    => $this->matriks->byId($pengguna),
            'pengguna' => $pengguna,
            'save_url' => site_url($this->controllerName . '/save') . '/',
        ]);
    }

    public function save()
    {
        if (! $this->validate(['cekModul' => 'required', 'susrNama' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $dipilih = $this->request->getPost('cekModul');
        $nama    = (string) $this->request->getPost('susrNama');

        if (! is_array($dipilih) || count($dipilih) === 0) {
            eult_message_kirim('Pilih Menu!! Minimal 1', 'error');
        }

        $this->matriks->hapus('s_user_group_user', ['sgroupSusrNama' => $nama]);

        foreach ($dipilih as $modul) {
            $this->matriks->tambah('s_user_group_user', [
                'sgroupSusrNama'   => $nama,
                'sgroupSgroupNama' => $modul,
            ]);
        }

        eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
    }
}
