<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        $value = ini_get('zlib.output_compression');

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');

        // Fix K4 (task 27.1, bugfix.md Requirement 2.17-2.20): jalur
        // KEDUA bug K4. Toolbar::respond() (vendor/codeigniter4/
        // framework/system/Debug/Toolbar.php) melayani endpoint
        // ?debugbar_time=/?debugbar dengan data debug penuh (session,
        // riwayat SQL, dsb) TANPA otorisasi apa pun -- method tersebut
        // HANYA mengecualikan ENVIRONMENT === 'testing' secara spesifik
        // (baris pertama respond()), TIDAK mengecualikan 'production'
        // atau nilai lain, dan TIDAK boleh diedit karena berada di
        // vendor/ (hilang saat composer update). Guard di titik panggil
        // ini (file aplikasi) memastikan endpoint tersebut TIDAK PERNAH
        // mengembalikan data debug apa pun ketika ENVIRONMENT !==
        // 'development' -- CI_ENVIRONMENT=production WAJIB tetap
        // diatur eksplisit di server publik; guard ini adalah
        // pertahanan KEDUA (defense-in-depth), bukan pengganti
        // konfigurasi tersebut. Listener DBQuery di atas (kolektor
        // data, bukan penyaji data ke luar) TIDAK digating tambahan --
        // tidak melayani apa pun ke luar secara langsung.
        if (ENVIRONMENT === 'development') {
            service('toolbar')->respond();
        }

        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});
