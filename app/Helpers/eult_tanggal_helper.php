<?php

/**
 * Helper tanggal Indonesia EULT.
 * Porting dari CI3 application/helpers/datetoindo_helper.php.
 */

if (! function_exists('eult_tanggal_indo')) {
    /**
     * Mengubah Y-m-d menjadi "d Bulan Y" (mis. 2026-09-13 -> 13 September 2026).
     */
    function eult_tanggal_indo(string $tanggal): string|false
    {
        if ($tanggal === '0000-00-00' || $tanggal === '') {
            return false;
        }

        $bulanIndo = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'Nopember', 'Desember',
        ];

        $tahun = substr($tanggal, 0, 4);
        $bulan = substr($tanggal, 5, 2);
        $tgl   = substr($tanggal, 8, 2);

        return $tgl . ' ' . $bulanIndo[(int) $bulan - 1] . ' ' . $tahun;
    }
}

if (! function_exists('eult_hari_indo')) {
    /**
     * Menerjemahkan singkatan hari Inggris (Sun..Sat) ke Indonesia.
     */
    function eult_hari_indo(string $hari): string
    {
        switch ($hari) {
            case 'Sun':
                return 'Minggu';
            case 'Mon':
                return 'Senin';
            case 'Tue':
                return 'Selasa';
            case 'Wed':
                return 'Rabu';
            case 'Thu':
                return 'Kamis';
            case 'Fri':
                return 'Jumat';
            case 'Sat':
                return 'Sabtu';
            default:
                return 'Tidak di ketahui';
        }
    }
}
