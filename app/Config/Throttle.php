<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi rate limiting per-IP EULT (Task 19.2, T2/M3).
 *
 * PENTING — INI BUKAN fasilitas bawaan CodeIgniter 4. CI4 versi
 * terinstall di proyek ini (v4.7.4, dikonfirmasi via `composer show`)
 * TIDAK memiliki `Services::throttler()` maupun class `Throttler`
 * apa pun (dikonfirmasi via pencarian penuh `Throttle`/`Throttler` di
 * seluruh `vendor/codeigniter4/framework/**\/*.php` — NOL kecocokan).
 * Task text tasks.md 19.2 yang menyebut "Config\Throttle bawaan CI4"
 * salah premis untuk versi ini — lihat docblock App\Filters\ThrottleFilter
 * untuk detail lengkap dan alasan pendekatan cache-manual yang dipakai
 * sebagai gantinya (Services::cache(), bukan Services::throttler()).
 *
 * Array ini murni konfigurasi limit/window buatan sendiri, dikonsumsi
 * oleh App\Filters\ThrottleFilter melalui argumen filter di
 * app/Config/Filters.php (`'throttle:otentifikasi'`, dst).
 *
 * Ambang batas (bugfix.md 2.29/2.30, task 19.2 Preservation: "longgar
 * untuk normal namun ketat untuk brute force") dipilih sebagai berikut
 * — keputusan implementasi teknis, bukan kebijakan yang memerlukan
 * konfirmasi angka spesifik dari user:
 *
 * - `otentifikasi` (login admin): 10 percobaan/menit per-IP. Login
 *   wajar (termasuk typo password sesekali) jauh di bawah 10x/menit;
 *   brute force credential butuh ratusan/ribuan percobaan untuk
 *   efektif, sehingga 10/menit sudah membuatnya tidak praktis tanpa
 *   mengganggu pengguna sah.
 * - `login/savetiket` (buat tiket + kirim email): 5 percobaan/menit
 *   per-IP. Lebih ketat dari otentifikasi karena setiap request sukses
 *   memicu SMTP nyata (mail bombing/cost amplification, bugfix.md
 *   1.20) — pengguna wajar hanya butuh membuat 1 tiket per sesi
 *   kunjungan, 5/menit tetap memberi ruang untuk percobaan ulang jika
 *   validasi/captcha gagal beberapa kali.
 * - `login/cektiket` (lacak/enumerasi tiket): 20 percobaan/menit
 *   per-IP. Paling longgar dari ketiganya karena endpoint ini read-only
 *   (tidak ada side-effect email/SMTP) dan pengguna wajar mungkin
 *   mengecek beberapa nomor tiket berbeda (miliknya sendiri + tiket
 *   yang ditanyakan orang lain di sekitarnya) dalam sesi singkat;
 *   20/menit tetap jauh di bawah skala enumerasi masif (ratusan/ribuan)
 *   yang menjadi concern M3 (bugfix.md 1.30).
 *
 * Window seragam 60 detik untuk ketiganya — sederhana untuk dipahami
 * operator dan cukup untuk membedakan traffic wajar vs otomatis pada
 * skala di atas.
 */
class Throttle extends BaseConfig
{
    /**
     * Ambang batas per rute — [alias => ['limit' => int, 'window' => int (detik)]].
     *
     * @var array<string, array{limit: int, window: int}>
     */
    public array $routes = [
        'otentifikasi' => [
            'limit'  => 10,
            'window' => 60,
        ],
        'login/savetiket' => [
            'limit'  => 5,
            'window' => 60,
        ],
        'login/cektiket' => [
            'limit'  => 20,
            'window' => 60,
        ],
    ];

    /**
     * Ambang batas default untuk alias rute yang tidak terdaftar
     * eksplisit di atas (jaring pengaman jika filter diterapkan ke
     * rute baru di masa depan tanpa konfigurasi khusus).
     *
     * @var array{limit: int, window: int}
     */
    public array $default = [
        'limit'  => 10,
        'window' => 60,
    ];
}
