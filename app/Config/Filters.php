<?php

namespace Config;

use App\Filters\AuthFilter;
use App\Filters\CsrfTokenHeader;
use App\Filters\EnvironmentAwareToolbar;
use App\Filters\GuestFilter;
use App\Filters\ThrottleFilter;
use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'auth'          => AuthFilter::class,
        'guest'         => GuestFilter::class,
        'csrf'          => CSRF::class,
        // Fix K4 (task 27.1, bugfix.md 2.17-2.20): EnvironmentAwareToolbar
        // menambahkan pengecekan ENVIRONMENT === 'development' LANGSUNG
        // sebelum mendelegasikan ke DebugToolbar::after() (vendor) —
        // lihat docblock App\Filters\EnvironmentAwareToolbar untuk
        // detail lengkap. CI_ENVIRONMENT=production WAJIB tetap diatur
        // eksplisit di server publik; filter ini adalah pertahanan
        // KEDUA (defense-in-depth), bukan pengganti konfigurasi tersebut.
        'toolbar'       => EnvironmentAwareToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'throttle'      => ThrottleFilter::class,
        'csrftoken'     => CsrfTokenHeader::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
            // ResponsAwalException (eult_message_kirim) ditangkap di
            // CodeIgniter::run(), SETELAH globals['after'] dilewati. Header
            // keamanan harus required agar respons JSON helper tetap
            // membawanya.
            'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            'csrf',
            'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            // Token CSRF terbaru untuk request AJAX (dibaca eult-csrf.js).
            'csrftoken',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * Rute terproteksi EULT (pengganti MY_Controller guard).
     * Rute publik (/, login, otentifikasi, cektiket, validitas)
     * sengaja tidak masuk daftar agar bisa diakses tanpa login.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'auth' => [
            'before' => [
                'home',
                'home/*',
                'ticketing',
                'ticketing/*',
                'validasifile',
                'validasifile/*',
                'laporan',
                'laporan/*',
                'laporanlayanan',
                'laporanlayanan/*',
                'refkategori',
                'refkategori/*',
                'refsurat',
                'refsurat/*',
                'refsyarat',
                'refsyarat/*',
                'unit',
                'unit/*',
                'modul',
                'modul/*',
                'modulgroup',
                'modulgroup/*',
                'hakakses',
                'hakakses/*',
                'hakaksesmodul',
                'hakaksesmodul/*',
                'hakaksesunit',
                'hakaksesunit/*',
                'hakakseslayanan',
                'hakakseslayanan/*',
                'hakaksespengguna',
                'hakaksespengguna/*',
                'hakaksesuser',
                'hakaksesuser/*',
                'pengguna',
                'pengguna/*',
            ],
        ],
        'guest' => [
            'before' => [
                'login',
            ],
        ],
        // Rate limiting per-IP (Task 19.2, bugfix.md T2/M3, Requirement
        // 2.29, 2.30, 2.42, 2.43) — HANYA route ini yang dibatasi, TIDAK
        // via $globals agar route lain tidak terpengaruh. Setiap alias
        // filter dengan argumen berbeda ('throttle:otentifikasi', dst)
        // memakai ambang batas per-route dari Config\Throttle::$routes
        // (lihat app/Filters/ThrottleFilter.php untuk implementasi —
        // BUKAN Services::throttler() bawaan CI4, karena fasilitas
        // tersebut tidak ada di versi framework terinstall — lihat
        // docblock ThrottleFilter untuk detail lengkap).
        'throttle:otentifikasi' => [
            'before' => [
                'otentifikasi',
                'otentifikasi/*',
            ],
        ],
        'throttle:login/savetiket' => [
            'before' => [
                'login/savetiket',
            ],
        ],
        'throttle:login/cektiket' => [
            'before' => [
                'login/cektiket',
            ],
        ],
    ];
}
