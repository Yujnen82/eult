<?php

namespace App\Libraries;

use Config\Services;

/**
 * Klien REST legacy (porting CI3 application/libraries/Restclient.php).
 * Endpoint app-dev.unmul.ac.id sudah tidak aktif; class dipertahankan
 * sebagai dokumentasi + kompatibilitas bila view lama masih memanggilnya.
 */
class RestLegacy
{
    private string $dasar = 'http://app-dev.unmul.ac.id/service/public/servicesia/';

    private string $pengguna = 'suroot';

    /** @deprecated Kredensial legacy, jangan dipakai untuk fitur baru. */
    private string $sandi = '}0isKLX3*M3_|J5';

    public function getMahasiswa(string $nim): mixed
    {
        $respon = Services::curlrequest()->post($this->dasar . 'mahasiswa', [
            'auth'        => [$this->pengguna, $this->sandi],
            'form_params' => ['nim' => $nim],
            'http_errors' => false,
        ]);

        return json_decode($respon->getBody());
    }

    public function getMhs(): mixed
    {
        $respon = Services::curlrequest()->get($this->dasar . 'mhsnim', [
            'auth'        => [$this->pengguna, $this->sandi],
            'http_errors' => false,
        ]);

        return json_decode($respon->getBody());
    }
}
