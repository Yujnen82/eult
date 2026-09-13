<?php

/**
 * Helper captcha EULT.
 * Porting dari CI3 application/helpers/get_captcha_helper.php.
 * Nilai captcha disimpan di session (pengganti cookie mentah CI3),
 * perbandingan tetap case-insensitive seperti check_captcha CI3.
 */

if (! function_exists('eult_captcha_generate')) {
    /**
     * Membuat kode captcha baru dan menyimpannya ke session.
     */
    function eult_captcha_generate(int $panjang = 4): string
    {
        // Alfabet sama persis dengan CI3 agar perilaku tidak berubah
        $alfabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $angka   = range(0, 35);
        $hasil   = '';
        shuffle($angka);
        for ($x = 0; $x < $panjang; $x++) {
            $hasil .= substr($alfabet, $angka[$x], 1);
        }

        session()->set('captcha', $hasil);

        return $hasil;
    }
}

if (! function_exists('eult_captcha_check')) {
    /**
     * Memeriksa input captcha (case-insensitive).
     * Kompatibel dengan cookie lama 'captcha_code' bila masih ada.
     */
    function eult_captcha_check(string $input): bool
    {
        $tersimpan = get_cookie('captcha_code');

        if (! $tersimpan) {
            $tersimpan = session()->get('captcha');
        }

        if (! is_string($tersimpan) || $tersimpan === '') {
            return false;
        }

        return strtoupper($input) === strtoupper($tersimpan);
    }
}
