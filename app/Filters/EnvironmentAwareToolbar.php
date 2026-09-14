<?php

namespace App\Filters;

use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fix K4 (task 27.1, bugfix.md Requirement 2.17-2.20): filter toolbar
 * yang menambahkan lapisan pertahanan KEDUA independen memeriksa
 * `ENVIRONMENT` secara LANGSUNG, sebelum mendelegasikan ke behavior
 * asli `CodeIgniter\Filters\DebugToolbar` (vendor).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONTEKS BUG YANG DIPERBAIKI
 * ═══════════════════════════════════════════════════════════════════
 * Investigasi (task 23/24) mengonfirmasi `CodeIgniter\Filters\
 * DebugToolbar::after()` (vendor/codeigniter4/framework/system/
 * Filters/DebugToolbar.php) HANYA memanggil
 * `service('toolbar')->prepare($request, $response)` TANPA syarat
 * `ENVIRONMENT` apa pun — gerbang SATU-SATUNYA adalah `CI_DEBUG`
 * (diperiksa DI DALAM `Toolbar::prepare()` sendiri, bukan di filter).
 * `CI_DEBUG` bernilai `true` pada `development.php` DAN `testing.php`
 * (`app/Config/Boot/*.php`), dan dapat ter-override independen (misal
 * `auto_prepend_file` yang tertinggal) TANPA mengubah `ENVIRONMENT`
 * genuine — artinya TIDAK ADA kode yang secara EKSPLISIT memastikan
 * toolbar aktif JIKA DAN HANYA JIKA `ENVIRONMENT === 'development'`.
 *
 * Filter ini menutup jalur tersebut PADA TITIK `prepare()`/inject
 * script loader + penulisan `writable/debugbar/*.json`. Jalur KEDUA
 * bug K4 (`Toolbar::respond()` yang melayani endpoint
 * `?debugbar_time=`, dipanggil `app/Config/Events.php`, BUKAN dari
 * filter apa pun) diperbaiki TERPISAH di `app/Config/Events.php`
 * dengan guard `ENVIRONMENT === 'development'` langsung pada titik
 * panggil `service('toolbar')->respond()` — KEDUA titik WAJIB
 * diperbaiki bersamaan agar Requirement 2.18 (endpoint `debugbar_time`
 * tidak pernah mengembalikan data debug apa pun di non-development)
 * benar-benar terpenuhi.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CATATAN OPERASIONAL — WAJIB DIBACA OPERATOR SERVER PUBLIK
 * ═══════════════════════════════════════════════════════════════════
 * Filter ini adalah lapisan pertahanan KEDUA (defense-in-depth), BUKAN
 * pengganti konfigurasi environment yang benar. `CI_ENVIRONMENT=
 * production` WAJIB diatur secara EKSPLISIT di server publik
 * (`.env` fisik project, atau environment variable proses OS) —
 * filter ini TIDAK menggantikan kebutuhan tersebut, hanya mencegah
 * toolbar aktif meski `CI_DEBUG` ter-override independen dari
 * `ENVIRONMENT` yang sudah benar.
 *
 * @see \CodeIgniter\Filters\DebugToolbar Behavior asli yang di-wrap.
 * @see \Tests\Bugfix\K4DebugToolbarExplorationTest Test bug condition (task 24, harus LULUS pasca fix ini).
 * @see \Tests\Bugfix\Klaster4PreservationTest Test preservasi development (task 25, harus TETAP LULUS pasca fix ini).
 */
class EnvironmentAwareToolbar extends DebugToolbar
{
    /**
     * Delegasikan PENUH ke behavior asli — toolbar tidak memiliki
     * logic pada `before()` (`DebugToolbar::before()` adalah no-op),
     * sehingga tidak ada guard `ENVIRONMENT` yang relevan di sini.
     *
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        return parent::before($request, $arguments);
    }

    /**
     * Lapisan pertahanan KEDUA (defense-in-depth): toolbar HANYA
     * diizinkan menulis `writable/debugbar/*.json` dan meng-inject
     * `<script id="debugbar_loader">` ketika `ENVIRONMENT ===
     * 'development'` — TERLEPAS dari nilai `CI_DEBUG` independen apa
     * pun. Untuk nilai `ENVIRONMENT` lain (termasuk `production` dan
     * `testing`), response dikembalikan TANPA modifikasi apa pun
     * (pass-through) — `Toolbar::prepare()` (vendor) TIDAK PERNAH
     * dipanggil sama sekali.
     *
     * Mekanisme penyimpanan `writable/debugbar/*.json` itu sendiri
     * TIDAK diubah — hanya syarat aktivasi yang ditambahkan di sini.
     *
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! defined('ENVIRONMENT') || ENVIRONMENT !== 'development') {
            return null;
        }

        return parent::after($request, $response, $arguments);
    }
}
