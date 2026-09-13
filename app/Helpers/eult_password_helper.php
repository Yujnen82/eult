<?php

/**
 * Helper password numerik EULT.
 * Porting dari CI3 application/helpers/generatepassword_helper.php.
 */

if (! function_exists('eult_generate_password')) {
    /**
     * Membuat password angka unik sepanjang $panjang (maks 10 digit).
     */
    function eult_generate_password(int $panjang = 6): string
    {
        $password  = '';
        $karakter  = '1234567890';
        $maksPanjang = strlen($karakter);

        if ($panjang > $maksPanjang) {
            $panjang = $maksPanjang;
        }

        $i = 0;
        while ($i < $panjang) {
            $char = substr($karakter, mt_rand(0, $maksPanjang - 1), 1);
            if (! strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }

        return $password;
    }
}
