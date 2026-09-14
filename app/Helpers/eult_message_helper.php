<?php

use App\Exceptions\ResponsAwalException;

/**
 * Helper pesan JSON EULT.
 * Porting dari CI3 application/helpers/message_helper.php yang
 * mengembalikan JSON {status, message, response, url} lalu exit.
 * Di CI4 respons dilempar sebagai ResponsableInterface agar dikirim oleh
 * CodeIgniter::run() (bukan exit), sehingga bisa diuji in-process.
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

        // Status HTTP yang sudah diset controller (mis. 403) dipertahankan;
        // pesan error tanpa status khusus tetap 200 agar JS existing yang
        // membaca `res.status` di handler success tidak berubah perilaku.
        // Token CSRF terbaru ikut dikirim karena respons ini melewati filter
        // global `after` (ditangkap langsung oleh CodeIgniter::run()).
        throw new ResponsAwalException(
            response()->setJSON($respons)->setHeader(csrf_header(), csrf_hash())
        );
    }
}
