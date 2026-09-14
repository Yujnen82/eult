<?php

namespace App\Controllers;

use App\Libraries\OsmClient;
use App\Models\ModelLogin;

/**
 * Endpoint AJAX login EULT (porting CI3 Otentifikasi.php).
 * Alur: captcha -> OSM Postlogin -> cocokkan s_user -> fallback lokal password_verify.
 */
class Otentifikasi extends BaseController
{
    /** @var array<string, mixed> */
    private array $dataSesi = [];

    private ModelLogin $masuk;

    private OsmClient $osm;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->masuk = new ModelLogin();
        $this->osm   = new OsmClient();
    }

    public function index()
    {
        if (! $this->validate(['username' => 'required', 'captcha' => 'required', 'password' => 'required'])) {
            log_message('debug', 'Otentifikasi gagal validasi untuk {user} dari {ip}', [
                'user' => (string) $this->request->getPost('username') ?: 'unknown',
                'ip'   => $this->request->getIPAddress(),
            ]);

            eult_captcha_generate(4);

            return $this->response->setJSON([
                'status'      => 'danger',
                'message'     => 'Username atau Password salah. Silakan coba lagi.',
                'new_captcha' => $this->urlGambarCaptcha(),
            ]);
        }

        if (! eult_captcha_check((string) $this->request->getPost('captcha'))) {
            log_message('debug', 'Otentifikasi captcha salah untuk {user}', ['user' => (string) $this->request->getPost('username')]);

            eult_captcha_generate(4);

            return $this->response->setJSON([
                'status'      => 'danger',
                'message'     => 'CAPTCHA tidak valid. Silakan coba lagi.',
                'new_captcha' => $this->urlGambarCaptcha(),
            ]);
        }

        if (! $this->cekDatabase((string) $this->request->getPost('password'))) {
            log_message('debug', 'Otentifikasi kredensial salah untuk {user} dari {ip}', [
                'user' => (string) $this->request->getPost('username'),
                'ip'   => $this->request->getIPAddress(),
            ]);

            eult_captcha_generate(4);

            return $this->response->setJSON([
                'status'      => 'danger',
                'message'     => 'Username atau Password salah. Silakan coba lagi.',
                'new_captcha' => $this->urlGambarCaptcha(),
            ]);
        }

        if ($this->dataSesi !== []) {
            $username = (string) $this->request->getPost('username');
            $this->masuk->ubah('s_user', ['susrLastLogin' => date('Y-m-d H:i:s')], ['susrNama' => $username]);
            session()->set('logged_in', $this->dataSesi);
            log_message('debug', 'Otentifikasi sukses untuk {user}', ['user' => $username]);
        }

        return $this->response->setJSON([
            'status'       => 'success',
            'message'      => 'You have successfully logged in.',
            'redirect_url' => base_url() . 'home',
        ]);
    }

    /**
     * URL endpoint gambar captcha dengan nonce cache-busting — nilai
     * captcha session TIDAK PERNAH dikirim plaintext ke klien.
     */
    private function urlGambarCaptcha(): string
    {
        return base_url('login/captcha_image') . '?t=' . time();
    }

    /**
     * Verifikasi kredensial: OSM dulu, fallback password lokal.
     * Kegagalan jaringan OSM tidak boleh meledak jadi 500 — fallback lokal tetap dicoba.
     */
    public function cekDatabase(string $sandi): bool
    {
        $username = (string) $this->request->getPost('username');

        try {
            $proses = $this->osm->postLogin($username, $sandi);
        } catch (\Throwable $e) {
            log_message('error', 'OSM tidak terjangkau saat login {user}: {pesan}', ['user' => $username, 'pesan' => $e->getMessage()]);
            $proses = false;
        }

        $datas = $this->masuk->ambilSatu('s_user', ['susrNama' => $username]);

        if (isset($proses->data) && $datas !== false) {
            if ($datas['susrNama'] == $proses->data->id) {
                $this->dataSesi = $this->bangunSesi($datas);

                return true;
            }
        } elseif ($datas !== false) {
            if (password_verify($sandi, (string) $datas['susrPassword'])) {
                $this->dataSesi = $this->bangunSesi($datas);

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $datas
     * @return array<string, mixed>
     */
    private function bangunSesi(array $datas): array
    {
        return [
            'susrNama'           => $datas['susrNama'],
            'susrSgroupNama'     => $datas['susrSgroupNama'] ?? 'USER',
            'susrSgroupNama_ori' => $datas['susrSgroupNama'] ?? 'USER',
            'susrProfil'         => $datas['susrProfil'],
            'susrCategoryId'     => $datas['susrCategoryId'] ?? '',
        ];
    }
}
