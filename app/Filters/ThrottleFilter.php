<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Config\Throttle as ThrottleConfig;

/**
 * Filter rate limiting per-IP EULT (Task 19.2, bugfix.md T2/M3,
 * Requirement 2.29, 2.30, 2.42, 2.43).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KOREKSI PREMIS task text tasks.md 19.2 — TIDAK memakai
 * `Services::throttler()`/`Config\Throttle` bawaan CI4:
 * ═══════════════════════════════════════════════════════════════════
 * Task text tasks.md 19.2 menyebut "Buat file app/Config/Throttle.php
 * (bawaan CI4, belum ada di codebase) dengan definisi rate
 * Services::throttler()" — premis ini SALAH untuk versi framework
 * terinstall di proyek ini (codeigniter4/framework v4.7.4, dikonfirmasi
 * via `composer show`). Dikonfirmasi via pencarian LANGSUNG di seluruh
 * source `vendor/codeigniter4/framework`:
 * - TIDAK ADA file `Throttler.php` di manapun dalam framework ini.
 * - TIDAK ADA method `throttler()` di
 *   `vendor/codeigniter4/framework/system/Config/Services.php`.
 * - Pencarian string `throttle` (case-insensitive) di SELURUH
 *   `vendor/codeigniter4/framework/**\/*.php` menghasilkan NOL match.
 *
 * `Services::throttler()`/`Config\Throttle` builtin BUKAN fasilitas
 * yang tersedia di versi ini — mungkin fitur di versi CI4 lain yang
 * tidak terinstall di sini, atau asumsi yang keliru saat requirements/
 * design ditulis. Fakta ini SUDAH didokumentasikan lengkap di
 * tests/Bugfix/T2CaptchaRateLimitExplorationTest.php dan
 * tests/Bugfix/M3TicketEnumerationExplorationTest.php (task 13/16,
 * pencarian `Throttle`/`Throttler` di seluruh `app/` — NOL kecocokan
 * selain lodash-style `throttle()` pada vendor JS plugin tinymce yang
 * tidak terkait).
 *
 * PENDEKATAN YANG DIPAKAI SEBAGAI GANTINYA: mekanisme rate-limiting
 * mandiri menggunakan `Services::cache()` yang SUDAH tersedia dan
 * terkonfigurasi (`app/Config/Cache.php` — handler primer `'file'`,
 * backup `'dummy'`). Pola: key cache per-IP+route
 * (`throttle_{routeAlias}_{ip}`), counter dengan TTL window
 * (`get()` → increment manual → `save()` dengan TTL = sisa window),
 * melebihi limit dalam window → 429. Ini FUNGSIONAL SETARA dengan yang
 * diminta task text ("request ke-(N+1) dalam window yang sama dari IP
 * sama → 429"), hanya beda implementasi teknis.
 *
 * ═══════════════════════════════════════════════════════════════════
 * DEGRADASI GRACEFUL bila cache backup 'dummy' aktif:
 * ═══════════════════════════════════════════════════════════════════
 * `DummyHandler::get()` SELALU mengembalikan `false` (bukan array
 * counter) dan `DummyHandler::save()` SELALU mengembalikan `true` tanpa
 * benar-benar menyimpan apa pun — jika handler primer `'file'` gagal
 * (misal `writable/cache/` tidak writable) dan CI4 failover ke
 * `backupHandler = 'dummy'`, filter ini secara efektif TIDAK PERNAH
 * melihat counter melebihi limit (setiap `get()` seolah cache miss) —
 * request akan SELALU diloloskan (fail-open), bukan fail-closed. Ini
 * SENGAJA (bukan bug): rate-limiting adalah defense-in-depth, tidak
 * boleh membuat SELURUH endpoint publik down total hanya karena
 * masalah filesystem cache — trade-off ini konsisten dengan filosofi
 * "preservasi akses pengguna wajar" pada task 19.2.
 *
 * Diterapkan pada route `otentifikasi`, `login/savetiket`,
 * `login/cektiket` via `$filters` (BUKAN `$globals`) di
 * app/Config/Filters.php — argumen filter menentukan alias
 * konfigurasi limit/window yang dipakai (lihat Config\Throttle).
 *
 * Requirements: 2.29, 2.30, 2.42, 2.43
 */
class ThrottleFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments Alias rute untuk lookup Config\Throttle::$routes,
     *                                      contoh: ['otentifikasi'] dari 'throttle:otentifikasi'
     *                                      di app/Config/Filters.php. Alias yang tidak terdaftar
     *                                      memakai Config\Throttle::$default.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $aliasRute = is_array($arguments) ? (string) ($arguments[0] ?? '') : '';

        $konfig    = config(ThrottleConfig::class);
        $ambangBatas = $konfig->routes[$aliasRute] ?? $konfig->default;

        $limit  = $ambangBatas['limit'];
        $window = $ambangBatas['window'];

        // getIPAddress() tersedia pada IncomingRequest (request HTTP
        // sungguhan) — RequestInterface tidak menjaminnya secara statis,
        // namun filter ini HANYA didaftarkan pada rute HTTP publik
        // (bukan CLIRequest), sehingga method call ini aman di jalur
        // produksi. method_exists() dipertahankan sebagai guard murni
        // defensif untuk konteks non-HTTP (misal dipanggil dari test
        // dengan request palsu) — default ke '0.0.0.0' bila tidak ada,
        // agar filter tidak fatal error, bukan agar bisa dilewati.
        $ip = method_exists($request, 'getIPAddress') ? $request->getIPAddress() : '0.0.0.0';

        $kunciCache = 'throttle_' . preg_replace('/[^A-Za-z0-9_]/', '_', $aliasRute) . '_' . $ip;

        $cache = Services::cache();
        $data  = $cache->get($kunciCache);

        $sekarang = time();

        if (! is_array($data) || ! isset($data['count'], $data['resetAt']) || $data['resetAt'] <= $sekarang) {
            // Cache miss, data korup, atau window sebelumnya sudah
            // kedaluwarsa — mulai window baru dengan hitungan 1
            // (request saat ini terhitung sebagai percobaan pertama).
            $data = ['count' => 1, 'resetAt' => $sekarang + $window];
            $cache->save($kunciCache, $data, $window);

            return null;
        }

        if ($data['count'] >= $limit) {
            // Limit tercapai DALAM window yang sama — tolak TANPA
            // menambah counter lagi (request yang ditolak tidak perlu
            // memperpanjang window, cukup ditolak).
            return Services::response()
                ->setStatusCode(429)
                ->setJSON([
                    'status'  => 'danger',
                    'message' => 'Terlalu banyak percobaan. Silakan coba lagi setelah beberapa saat.',
                ]);
        }

        // Masih dalam limit — increment counter, pertahankan sisa TTL
        // window yang sama (bukan window baru) agar hitungan konsisten
        // per-window, bukan reset TTL setiap request.
        $data['count']++;
        $sisaTtl = max(1, $data['resetAt'] - $sekarang);
        $cache->save($kunciCache, $data, $sisaTtl);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
