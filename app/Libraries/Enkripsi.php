<?php

namespace App\Libraries;

/**
 * Enkripsi tiket EULT (pengganti CI3 application/libraries/Encryptions.php).
 *
 * Format dipertahankan persis agar URL lama (cektiket/xxx, validitas/xxx)
 * tetap terbaca: HMAC-SHA512 hex + base64(iv+cipher) lalu dibungkus
 * base64 URL-aman. Algoritma mengikuti CI_Encryption 3.1.13
 * (AES-256-CBC OpenSSL + HKDF-SHA512).
 *
 * Kunci default dari .env (EULT_ENCRYPTION_LEGACY_KEY), fallback ke
 * kunci legacy CI3 agar data lama tetap terbaca. Jangan ganti kunci
 * ini sampai seluruh URL lama kedaluwarsa.
 */
class Enkripsi
{
    private string $metode = 'aes-256-cbc';

    private function kunciLegacy(): string
    {
        if (function_exists('env')) {
            $dariEnv = env('EULT_ENCRYPTION_LEGACY_KEY');
            if (is_string($dariEnv) && $dariEnv !== '') {
                return $dariEnv;
            }
        }

        return 'SuPer_Enc-Key2010';
    }

    public function safeB64Encode(string $string): string
    {
        $data = base64_encode($string);

        return str_replace(['+', '/', '='], ['-', '_', ''], $data);
    }

    public function safeB64Decode(string $string): string|false
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data);
    }

    /**
     * HKDF persis seperti CI_Encryption::hkdf (RFC 5869).
     */
    private function hkdf(string $kunci, string $digest = 'sha512', ?string $salt = null, ?int $panjang = null, string $info = ''): string|false
    {
        $ukuran = ['sha224' => 28, 'sha256' => 32, 'sha384' => 48, 'sha512' => 64];

        if (! isset($ukuran[$digest])) {
            return false;
        }

        if (empty($panjang) || ! is_int($panjang)) {
            $panjang = $ukuran[$digest];
        } elseif ($panjang > (255 * $ukuran[$digest])) {
            return false;
        }

        if ($salt === null || $salt === '') {
            $salt = str_repeat("\0", $ukuran[$digest]);
        }

        $prk   = hash_hmac($digest, $kunci, $salt, true);
        $hasil = '';
        for ($blok = '', $i = 1; strlen($hasil) < $panjang; $i++) {
            $blok = hash_hmac($digest, $blok . $info . chr($i), $prk, true);
            $hasil .= $blok;
        }

        return substr($hasil, 0, $panjang);
    }

    /**
     * Kunci mentah efektif: parameter eksplisit bila ada, sonst kunci legacy.
     * (CI3 selalu jatuh ke skey karena encryption_key-nya kosong.)
     */
    private function kunciMentah(string $kunci = ''): string
    {
        return $kunci !== '' ? $kunci : $this->kunciLegacy();
    }

    public function encode(?string $nilai, string $kunci = ''): string|false
    {
        if (! $nilai) {
            return false;
        }

        $mentah      = $this->kunciMentah($kunci);
        $kunciEnkrip = $this->hkdf($mentah, 'sha512', null, strlen($mentah), 'encryption');
        $kunciHmac   = $this->hkdf($mentah, 'sha512', null, null, 'authentication');

        if (! is_string($kunciEnkrip) || ! is_string($kunciHmac)) {
            return false;
        }

        $panjangIv   = openssl_cipher_iv_length($this->metode);
        $iv          = random_bytes($panjangIv);
        $terenkripsi = openssl_encrypt($nilai, $this->metode, $kunciEnkrip, OPENSSL_RAW_DATA, $iv);

        if ($terenkripsi === false) {
            return false;
        }

        $payload  = base64_encode($iv . $terenkripsi);
        $hmac     = hash_hmac('sha512', $payload, $kunciHmac);
        $gabungan = $hmac . $payload;

        return trim($this->safeB64Encode($gabungan));
    }

    public function decode(?string $nilai, string $kunci = ''): string|false
    {
        if (! $nilai) {
            return false;
        }

        // Coba kunci eksplisit/legacy dulu, lalu kunci .env (format hex2bin:) untuk migrasi.
        $kandidat = [$this->kunciMentah($kunci)];

        $dariEnv = function_exists('env') ? (string) (env('encryption.key') ?? '') : '';
        if (str_starts_with($dariEnv, 'hex2bin:')) {
            $biner = hex2bin(substr($dariEnv, 8));
            if (is_string($biner) && $biner !== '') {
                $kandidat[] = $biner;
            }
        } elseif ($dariEnv !== '') {
            $kandidat[] = $dariEnv;
        }

        foreach ($kandidat as $mentah) {
            $hasil = $this->decodeDenganKunci($nilai, $mentah);
            if ($hasil !== false) {
                return $hasil;
            }
        }

        return false;
    }

    private function decodeDenganKunci(string $nilai, string $mentah): string|false
    {
        $kunciEnkrip = $this->hkdf($mentah, 'sha512', null, strlen($mentah), 'encryption');
        $kunciHmac   = $this->hkdf($mentah, 'sha512', null, null, 'authentication');

        if (! is_string($kunciEnkrip) || ! is_string($kunciHmac)) {
            return false;
        }

        $gabungan = $this->safeB64Decode($nilai);

        if (! is_string($gabungan) || strlen($gabungan) <= 128) {
            return false;
        }

        $hmacTerima = substr($gabungan, 0, 128);
        $payload    = substr($gabungan, 128);
        $hmacHitung = hash_hmac('sha512', $payload, $kunciHmac);

        if (! hash_equals($hmacHitung, strtolower($hmacTerima))) {
            return false;
        }

        $biner = base64_decode($payload, true);
        if (! is_string($biner)) {
            return false;
        }

        $panjangIv = openssl_cipher_iv_length($this->metode);
        $iv        = substr($biner, 0, $panjangIv);
        $cipher    = substr($biner, $panjangIv);

        $hasil = openssl_decrypt($cipher, $this->metode, $kunciEnkrip, OPENSSL_RAW_DATA, $iv);

        return is_string($hasil) ? trim($hasil) : false;
    }
}
