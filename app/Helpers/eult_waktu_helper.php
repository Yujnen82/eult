<?php

/**
 * Helper "waktu lalu" EULT.
 * Porting verbatim dari CI3 application/helpers/findtimeago_helper.php.
 */

if (! function_exists('eult_waktu_lalu')) {
    function eult_waktu_lalu(string $lalu, string $sekarang = 'now'): string
    {
        $detikPerMenit = 60;
        $detikPerJam   = 3600;
        $detikPerHari  = 86400;
        $detikPerBulan = 2592000;
        $detikPerTahun = 31104000;

        $lalu     = strtotime($lalu);
        $sekarang = strtotime($sekarang);

        $waktuLalu = '';

        $selisih = $sekarang - $lalu;

        if ($selisih <= 29) {
            $waktuLalu = 'less than a minute';
        } elseif ($selisih > 29 && $selisih <= 89) {
            $waktuLalu = '1 minute';
        } elseif (
            $selisih > 89 &&
            $selisih <= (($detikPerMenit * 44) + 29)
        ) {
            $menit     = floor($selisih / $detikPerMenit);
            $waktuLalu = $menit . ' minutes';
        } elseif (
            $selisih > (($detikPerMenit * 44) + 29)
            &&
            $selisih < (($detikPerMenit * 89) + 29)
        ) {
            $waktuLalu = 'about 1 hour';
        } elseif (
            $selisih > (
                ($detikPerMenit * 89) +
                29)
            &&
            $selisih <= (
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
        ) {
            $jam       = floor($selisih / $detikPerJam);
            $waktuLalu = $jam . ' hours';
        } elseif (
            $selisih > (
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
            &&
            $selisih <= (
                ($detikPerJam * 47) +
                ($detikPerMenit * 59) +
                29)
        ) {
            $waktuLalu = '1 day';
        } elseif (
            $selisih > (
                ($detikPerJam * 47) +
                ($detikPerMenit * 59) +
                29)
            &&
            $selisih <= (
                ($detikPerHari * 29) +
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
        ) {
            $hari      = floor($selisih / $detikPerHari);
            $waktuLalu = $hari . ' days';
        } elseif (
            $selisih > (
                ($detikPerHari * 29) +
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
            &&
            $selisih <= (
                ($detikPerHari * 59) +
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
        ) {
            $waktuLalu = 'about 1 month';
        } elseif (
            $selisih > (
                ($detikPerHari * 59) +
                ($detikPerJam * 23) +
                ($detikPerMenit * 59) +
                29)
            &&
            $selisih < $detikPerTahun
        ) {
            $bulan = round($selisih / $detikPerBulan);
            if ($bulan == 1) {
                $bulan = 2;
            }

            $waktuLalu = $bulan . ' months';
        } elseif (
            $selisih >= $detikPerTahun
            &&
            $selisih < ($detikPerTahun * 2)
        ) {
            $waktuLalu = 'about 1 year';
        } else {
            $tahun     = floor($selisih / $detikPerTahun);
            $waktuLalu = 'over ' . $tahun . ' years';
        }

        return $waktuLalu . ' ago';
    }
}

if (! function_exists('findTimeAgo')) {
    /**
     * Alias findTimeAgo untuk kompatibilitas view lama/CI3 (detail.php).
     */
    function findTimeAgo(string $lalu, string $sekarang = 'now'): string
    {
        return eult_waktu_lalu($lalu, $sekarang);
    }
}
