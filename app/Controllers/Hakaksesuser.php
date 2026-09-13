<?php

namespace App\Controllers;

/**
 * Placeholder Hakaksesuser (CI3 aslinya kosong — index() tanpa isi).
 * Dipertahankan sebagai rute agar menu lama tidak 404; menampilkan
 * daftar grup sebagai informasi.
 */
class Hakaksesuser extends BaseController
{
    protected ?string $judul = 'Hak Akses User';

    protected ?string $controllerName = 'hakaksesuser';

    protected ?string $pathPage = 'pages/hakaksesuser/';

    public function index(): string
    {
        $data            = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts'] = [];
        $data['datas']   = $this->modelMaster->tabelRef('s_user_group');

        return view($this->template, $data);
    }
}
