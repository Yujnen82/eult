<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelPengguna;

/**
 * Master pengguna + reset password (porting CI3 Pengguna.php).
 */
class Pengguna extends BaseController
{
    protected ?string $judul = 'Pengguna';

    protected ?string $controllerName = 'pengguna';

    protected ?string $pathPage = 'pages/pengguna/';

    protected ?string $pathJs = 'user/';

    private ModelPengguna $pengguna;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->pengguna = new ModelPengguna();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data                      = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']           = [$this->pathJs . $this->controllerName];
        $data['datas']             = $this->pengguna->semua();
        $data['create_url']        = site_url($this->controllerName . '/create') . '/';
        $data['update_url']        = site_url($this->controllerName . '/update') . '/';
        $data['delete_url']        = site_url($this->controllerName . '/delete') . '/';
        $data['resetpassword_url'] = site_url($this->controllerName . '/resetpassword') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data                 = $this->getMaster($this->pathPage . 'form');
        $data['scripts']      = [$this->pathJs . $this->controllerName];
        $data['save_url']     = site_url($this->controllerName . '/save') . '/';
        $data['status_page']  = 'Create';
        $data['datas']        = false;
        $data['s_user_group'] = $this->pengguna->tabelRef('s_user_group');

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka              = $this->enkripsi->decode($kunci);
        $data                 = $this->getMaster($this->pathPage . 'form');
        $data['scripts']      = [$this->pathJs . $this->controllerName];
        $data['r_category']   = $this->pengguna->tabelRef('r_category');
        $data['save_url']     = site_url($this->controllerName . '/save') . '/';
        $data['status_page']  = 'Update';
        $data['datas']        = $this->pengguna->byId(['susrNama' => $terbuka]);
        $data['s_user_group'] = $this->pengguna->tabelRef('s_user_group');

        return view($this->template, $data);
    }

    public function save()
    {
        $namaLama = (string) $this->request->getPost('susrNamaOld');

        $aturanNama = $namaLama === '' ? 'required|is_unique[s_user.susrNama]' : 'required';

        if (! $this->validate([
            'susrNama'       => $aturanNama,
            'susrSgroupNama' => 'required',
            'susrProfil'     => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $nama     = (string) $this->request->getPost('susrNama');
        $grup     = (string) $this->request->getPost('susrSgroupNama');
        $param    = [
            'susrNama'       => $nama,
            'susrSgroupNama' => $grup,
            'susrProfil'     => (string) $this->request->getPost('susrProfil'),
            'susrCategoryId' => (string) $this->request->getPost('susrCategoryId'),
        ];

        if ($namaLama === '') {
            $proses = $this->pengguna->tambah('s_user', $param)
                && $this->pengguna->tambah('s_user_group_user', ['sgroupSusrNama' => $nama, 'sgroupSgroupNama' => $grup]);
        } else {
            $proses = $this->pengguna->ubah('s_user', $param, ['susrNama' => $namaLama]);
        }

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->pengguna->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->pengguna->hapus('s_user', ['susrNama' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->pengguna->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function resetpassword(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $sandi   = eult_generate_password();

        $proses = $this->pengguna->ubah('s_user', ['susrPassword' => password_hash($sandi, PASSWORD_DEFAULT)], ['susrNama' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim('Password ' . $this->judul . ' Berhasil Diubah!! Username: <strong>' . $terbuka . '</strong>, Password: <strong>' . $sandi . '</strong>', 'success');
        }

        eult_message_kirim('Password ' . $this->judul . ' Gagal Diubah!!', 'error');
    }
}
