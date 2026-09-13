<?php

namespace App\Controllers;

use App\Models\ModelHome;

/**
 * Dashboard + manajemen sesi pengguna (porting CI3 Home.php).
 */
class Home extends BaseController
{
    protected ?string $judul = 'Dashboard';

    protected ?string $controllerName = 'home';

    protected ?string $pathPage = 'pages/home/';

    private ModelHome $beranda;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->beranda = new ModelHome();
    }

    public function index(): string
    {
        $data            = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts'] = [];

        return view($this->template, $data);
    }

    public function logout()
    {
        session()->remove('logged_in');
        session()->destroy();

        return redirect()->to(site_url('home'));
    }

    public function ubahpass(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $sesi = $this->sesiLogin() ?? [];

        if (($sesi['susrSgroupNama_ori'] ?? 'USER') === 'USER') {
            return redirect()->to(site_url('login'));
        }

        $data             = $this->getMaster($this->pathPage . 'ubahpassword');
        $data['save_url'] = site_url($this->controllerName . '/prosesubahpassword');
        $data['judul']    = 'Ubah Password';
        $data['scripts']  = [];

        return view($this->template, $data);
    }

    public function ubahhakakses(): string
    {
        $sesi = $this->sesiLogin() ?? [];

        $data             = $this->getMaster($this->pathPage . 'ubahhakakses');
        $data['save_url'] = site_url($this->controllerName . '/prosesubahhakakses');
        $data['judul']    = 'Ubah Hak Akses';
        $data['hakakses'] = $this->beranda->byId((string) ($sesi['susrNama'] ?? ''));
        $data['scripts']  = [];

        return view($this->template, $data);
    }

    public function prosesubahpassword()
    {
        $sesi = $this->sesiLogin() ?? [];

        if (! $this->validate([
            'susrPasswordOld'         => 'required',
            'susrPasswordNew'         => 'required',
            'susrPasswordNewConfirm'  => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $lama     = (string) $this->request->getPost('susrPasswordOld');
        $baru     = (string) $this->request->getPost('susrPasswordNew');
        $konfirm  = (string) $this->request->getPost('susrPasswordNewConfirm');
        $nama     = (string) ($sesi['susrNama'] ?? '');

        $cek = $this->beranda->ambilSatu('s_user', ['susrNama' => $nama]);

        if ($cek === false) {
            eult_message_kirim('Username/Password Lama Salah', 'error');
        }

        if (! password_verify($lama, (string) $cek['susrPassword'])) {
            eult_message_kirim('Username dan Password Lama Salah', 'error');
        }

        if ($baru !== $konfirm) {
            eult_message_kirim('Password Baru Tidak Sama Dengan Konfirmasi', 'error');
        }

        $proses = $this->beranda->ubah('s_user', ['susrPassword' => password_hash($baru, PASSWORD_DEFAULT)], ['susrNama' => $nama]);

        eult_message_kirim($proses ? 'Password Berhasil Diubah' : 'Password Gagal diubah', $proses ? 'success' : 'error');
    }

    public function prosesubahhakakses()
    {
        $sesi = $this->sesiLogin() ?? [];

        if (! $this->validate(['hakakses' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        if (($sesi['susrSgroupNama_ori'] ?? 'USER') === 'USER') {
            return redirect()->to(site_url('login'));
        }

        session()->set('logged_in', [
            'susrNama'           => $sesi['susrNama'],
            'susrSgroupNama'     => (string) $this->request->getPost('hakakses'),
            'susrSgroupNama_ori' => $sesi['susrSgroupNama_ori'],
            'susrProfil'         => $sesi['susrProfil'],
        ]);

        eult_message_kirim('Hak Akses Berhasil Diubah', 'success');
    }
}
