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

        // Nilai yang bukan Y-m-d (mis. teks "14 September 2026" dari form
        // surat) dikembalikan apa adanya, bukan memicu index bulan negatif.
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tanggal, $bagian) !== 1) {
            return $tanggal;
        }

        [, $tahun, $bulan, $tgl] = $bagian;
        $indeksBulan = (int) $bulan - 1;

        if (! isset($bulanIndo[$indeksBulan])) {
            return $tanggal;
        }

        return $tgl . ' ' . $bulanIndo[$indeksBulan] . ' ' . $tahun;
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

if (! function_exists('DateToIndo')) {
    /**
     * Alias DateToIndo untuk kompatibilitas view lama/CI3.
     */
    function DateToIndo(string $tanggal): string|false
    {
        return eult_tanggal_indo($tanggal);
    }
}


if (! function_exists('DayToIndo')) {
    /**
     * Alias DayToIndo untuk kompatibilitas view lama/CI3 (tanda_terima.php).
     */
    function DayToIndo(string $hari): string
    {
        return eult_hari_indo($hari);
    }
}
