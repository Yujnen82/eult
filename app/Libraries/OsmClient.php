<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

/**
 * Klien API OSM UNMUL (porting CI3 application/libraries/Apiclient.php).
 *
 * Perilaku dipertahankan: ambil token per akses -> POST /login untuk
 * Postlogin -> GET untuk data mahasiswa/pegawai/referensi.
 * Kredensial dibaca dari .env (EULT_OSM_*), bukan auth.json yang berisi
 * password plaintext di repo CI3.
 */
class OsmClient
{
    private string $dasar;

    /** @var array<string, array{username:string,password:string,apikey:string}> */
    private array $kredensial = [];

    private CURLRequest $http;

    public function __construct(?CURLRequest $http = null)
    {
        $bacaEnv = static fn (string $kunci, string $bawaan = ''): string => function_exists('env') && env($kunci) !== null && env($kunci) !== '' ? (string) env($kunci) : $bawaan;

        $this->dasar = rtrim($bacaEnv('EULT_OSM_URL', 'https://osm.unmul.ac.id'), '/');

        $this->kredensial = [
            'plpkkn' => [
                'username' => $bacaEnv('EULT_OSM_PLPKKN_USER'),
                'password' => $bacaEnv('EULT_OSM_PLPKKN_PASS'),
                'apikey'   => $bacaEnv('EULT_OSM_PLPKKN_KEY'),
            ],
            'simkeu' => [
                'username' => $bacaEnv('EULT_OSM_SIMKEU_USER'),
                'password' => $bacaEnv('EULT_OSM_SIMKEU_PASS'),
                'apikey'   => $bacaEnv('EULT_OSM_SIMKEU_KEY'),
            ],
            'balai-bahasa' => [
                'username' => $bacaEnv('EULT_OSM_BAHASA_USER'),
                'password' => $bacaEnv('EULT_OSM_BAHASA_PASS'),
                'apikey'   => $bacaEnv('EULT_OSM_BAHASA_KEY'),
            ],
        ];

        $this->http = $http ?? Services::curlrequest([
            'timeout'     => 30,
            'http_errors' => false,
            'headers'     => ['User-Agent' => 'eult/unmul'],
        ]);
    }

    private function gabungParam(string $pemisah, array $array, string $simbol = '='): string
    {
        $potongan = [];
        foreach ($array as $k => $v) {
            $potongan[] = $k . $simbol . $v;
        }

        return implode($pemisah, $potongan);
    }

    /**
     * Meminta token akses OSM untuk satu akun (plpkkn/simkeu/balai-bahasa).
     */
    private function ambilToken(string $akses): object|false
    {
        if (! isset($this->kredensial[$akses])) {
            return false;
        }

        try {
            $respon = $this->http->post($this->dasar . '/auth', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey'       => $this->kredensial[$akses]['apikey'],
                ],
                'body' => json_encode([
                    'username' => $this->kredensial[$akses]['username'],
                    'password' => $this->kredensial[$akses]['password'],
                ]),
            ]);

            $hasil = json_decode($respon->getBody());

            return is_object($hasil) ? $hasil : false;
        } catch (\Throwable $e) {
            log_message('error', 'OSM ambilToken gagal ({akses}): {pesan}', ['akses' => $akses, 'pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * POST terotentikasi ke endpoint OSM (mempertahankan bug CI3:
     * URL selalu /login terlepas dari $endpoint).
     */
    private function clientPost(string $akses, string $endpoint, array $param = []): object|false
    {
        $token = $this->ambilToken($akses);

        if (! is_object($token) || ! isset($token->data->token)) {
            return false;
        }

        try {
            $respon = $this->http->post($this->dasar . '/login', [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'apikey'        => $this->kredensial[$akses]['apikey'],
                    'Authorization' => 'Bearer ' . $token->data->token,
                ],
                'body' => json_encode($param),
            ]);

            $hasil = json_decode($respon->getBody());

            return is_object($hasil) ? $hasil : false;
        } catch (\Throwable $e) {
            log_message('error', 'OSM clientPost gagal ({akses}): {pesan}', ['akses' => $akses, 'pesan' => $e->getMessage()]);

            return false;
        }
    }

    private function clientGet(string $akses, string $endpoint, array $param = []): object|false
    {
        $url = $this->dasar . '/' . $endpoint;
        if (count($param) > 0) {
            $url .= '?' . $this->gabungParam('&', $param);
        }

        $token = $this->ambilToken($akses);

        if (! is_object($token) || ! isset($token->data->token)) {
            return false;
        }

        try {
            $respon = $this->http->get($url, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'apikey'        => $this->kredensial[$akses]['apikey'],
                    'Authorization' => 'Bearer ' . $token->data->token,
                ],
            ]);

            $hasil = json_decode($respon->getBody());

            return is_object($hasil) ? $hasil : false;
        } catch (\Throwable $e) {
            log_message('error', 'OSM clientGet gagal ({akses} {url}): {pesan}', ['akses' => $akses, 'url' => $url, 'pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Login pegawai ke OSM (dipakai Otentifikasi::check_database).
     */
    public function postLogin(string $username, string $password): object|string|false
    {
        $data = $this->clientPost('plpkkn', 'login', [
            'username' => $username,
            'password' => $password,
            'usertype' => 'PEGAWAI',
        ]);

        if (! isset($data->data)) {
            return $data->message ?? false;
        }
        if (is_object($data->data)) {
            return $data;
        }

        return false;
    }

    public function dtMhs(int $halaman, int $batas, string $cari, string $angkatan = ''): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/mahasiswa', [
            'page'     => $halaman,
            'limit'    => $batas,
            'search'   => $cari,
            'fakultas' => '',
            'status'   => '',
            'angkatan' => $angkatan,
        ]);

        return isset($data->data) ? $data->data : false;
    }

    public function dtMhsHalaman(int $halaman, int $batas, string $cari = '', string $angkatan = '', string $prodi = ''): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/mahasiswa', [
            'page'          => $halaman,
            'limit'         => $batas,
            'search'        => $cari,
            'fakultas'      => '',
            'status'        => '',
            'angkatan'      => $angkatan,
            'program_studi' => $prodi,
        ]);

        return isset($data->data) ? $data->data : false;
    }

    public function mhsId(string $id): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/mahasiswa/' . $id);

        return isset($data->data) ? $data->data : false;
    }

    public function mhsId2(string $id): mixed
    {
        $data = $this->clientGet('balai-bahasa', 'sia/v1/reg_pd/nim/' . $id);

        return isset($data->data) ? $data->data : false;
    }

    public function pegawaiId(string $id): mixed
    {
        $data = $this->clientGet('plpkkn', 'kepegawaian/pegawai/detail/' . $id);

        return isset($data->data) ? $data->data : false;
    }

    public function semester(): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/referensi/semester');

        return isset($data->data) ? $data->data : false;
    }

    public function semesterAktif(): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/referensi/semester_aktif');

        return isset($data->data) ? $data->data : false;
    }

    public function prodiId(string $id): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/referensi/program_studi/' . $id);

        return isset($data->data) ? $data->data : false;
    }

    public function prodi(): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/referensi/program_studi', ['aktif' => '1']);

        return isset($data->data) ? $data->data : false;
    }

    public function diktiShift(): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/referensi/shift_dikti');

        return isset($data->data) ? $data->data : false;
    }

    public function daftarMhs(int $halaman, int $batas, string $prodi, string $angkatan = ''): mixed
    {
        $param = [
            'page'          => $halaman,
            'limit'         => $batas,
            'status'        => 'A',
            'program_studi' => $prodi,
        ];
        if (! empty($angkatan) && $angkatan !== '%') {
            $param['angkatan'] = $angkatan;
        }

        $data = $this->clientGet('simkeu', 'sia/mahasiswa', $param);

        return isset($data->data) ? $data->data : false;
    }

    public function mhsStatus(string $nim): mixed
    {
        $data = $this->clientGet('simkeu', 'sia/mahasiswa', [
            'page'   => '1',
            'limit'  => '100',
            'search' => $nim,
        ]);

        return isset($data->data[0]) ? $data->data[0] : false;
    }
}
