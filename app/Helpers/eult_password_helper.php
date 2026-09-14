<?php

/**
 * Helper password sementara EULT (reset password oleh admin).
 */

if (! function_exists('eult_generate_password')) {
    /**
     * Membuat password sementara acak (CSPRNG) dari huruf besar/kecil dan
     * angka, tanpa karakter yang mudah tertukar (0/O, 1/l/I).
     * Panjang minimum 12 karakter agar tidak dapat ditebak/brute-force
     * meskipun password hanya dipakai sampai pengguna menggantinya.
     */
    function eult_generate_password(int $panjang = 12): string
    {
        $panjang  = max(12, $panjang);
        $karakter = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $maks     = strlen($karakter) - 1;
        $password = '';

        for ($i = 0; $i < $panjang; $i++) {
            $password .= $karakter[random_int(0, $maks)];
        }

        return $password;
    }
}
