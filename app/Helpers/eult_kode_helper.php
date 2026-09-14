<?php

/**
 * Helper kode tiket EULT.
 * Porting dari CI3 application/helpers/getkode_helper.php.
 */

if (! function_exists('eult_generate_kode')) {
    /**
     * Membuat kode acak 8 huruf format XXXX-XXXX (dipakai nomor tiket).
     */
    function eult_generate_kode(): string
    {
        $karakter = 'QWERTYUIOPASDFGHJKLZXCVBNM';
        $kode     = '';
        for ($i = 0; $i < 8; $i++) {
            $pos = rand(0, strlen($karakter) - 1);
            $kode .= $karakter[$pos];
        }

        return implode('-', str_split($kode, 4));
    }
}

if (! function_exists('eult_auto_increment')) {
    /**
     * Membuat ID arsip berikutnya (porting auto_increment CI3).
     *
     * @param array<string, mixed>|string $kondisi Kondisi WHERE. Caller yang menerima input
     *                                              dari request publik (mis. Login::savetiket())
     *                                              WAJIB memakai bentuk array (array binding aman).
     *                                              Bentuk string HANYA untuk caller internal yang
     *                                              tidak menerima input publik secara langsung
     *                                              (lihat ModelMaster::getByLastId()).
     */
    function eult_auto_increment(string $tabel, string $kolom, string $nip, array|string $kondisi): string
    {
        $model = new \App\Models\ModelMaster();

        /** @var array<string, mixed>|false $terakhir */
        $terakhir = $model->getByLastId($tabel, $kolom, $kondisi);

        if (! empty($terakhir) && isset($terakhir[$kolom])) {
            $id = $nip . sprintf('%04d', (int) substr((string) $terakhir[$kolom], 12, 4) + 1);
        } else {
            $id = $nip . '0001';
        }

        return $id;
    }
}
