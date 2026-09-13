<?php

/**
 * Helper pesan JSON EULT.
 * Porting dari CI3 application/helpers/message_helper.php yang
 * mengembalikan JSON {status, message, response, url} lalu exit.
 * Di CI4 respons dikirim via service response agar bisa diuji.
 */

if (! function_exists('eult_message_kirim')) {
    /**
     * Mengirim respons JSON lalu menghentikan request (perilaku sama dengan CI3).
     *
     * @return never
     */
    function eult_message_kirim(string $pesan = '', string $tipe = '', string $url = '')
    {
        $respons = [
            'status'   => $tipe,
            'message'  => $pesan,
            'response' => '<div class="alert alert-' . ($tipe === 'error' ? 'danger' : $tipe) . ' alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"></button>
                                ' . strtoupper($pesan) . '
                            </div>',
            'url' => $url,
        ];

        response()->setJSON($respons)->send();
        exit;
    }
}
