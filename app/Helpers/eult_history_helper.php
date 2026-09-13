<?php

/**
 * Helper riwayat tiket EULT.
 * Porting dari CI3 application/helpers/history_helper.php.
 */

if (! function_exists('eult_save_history')) {
    /**
     * Mencatat kejadian ke tabel d_history.
     */
    function eult_save_history(string $pesan, string $idTiket): bool
    {
        $model = new \App\Models\ModelMaster();

        return $model->tambah('d_history', [
            'detailHistory'           => $pesan,
            'tglHistory'              => date('Y-m-d H:i:s'),
            'ticketTrackingIdHistory' => $idTiket,
        ]);
    }
}
