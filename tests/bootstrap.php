<?php

/**
 * Bootstrap KHUSUS untuk test eksplorasi bug condition semua-remediasi.
 *
 * PENTING — mengapa file ini TIDAK memakai bootstrap standar CI4
 * (`vendor/codeigniter4/framework/system/Test/bootstrap.php`):
 * bootstrap standar men-define `ENVIRONMENT = 'testing'` secara hardcode,
 * yang membuat `Config\Database::__construct()` memaksa `defaultGroup`
 * menjadi `'tests'` (SQLite in-memory kosong). Task ini secara eksplisit
 * mensyaratkan pengujian terhadap data NYATA di MySQL `db_newtiket`
 * (38441 baris `d_ticketing`, konsisten dengan reproduksi manual via
 * `spark` yang sudah dikonfirmasi pada sesi sebelumnya) — bukan mock/fake.
 *
 * Solusi: define `ENVIRONMENT` sebagai `development` (mengikuti nilai
 * `CI_ENVIRONMENT` yang sesungguhnya di `.env`) SEBELUM memanggil
 * `CodeIgniter\Boot::bootConsole()` — jalur boot yang dipakai `spark`
 * untuk operasi CLI (tanpa dispatch command apa pun), sehingga
 * `Config\Database::defaultGroup` tetap `'default'` (MySQL nyata),
 * persis seperti saat class model dipanggil dari controller sungguhan.
 */

define('ENVIRONMENT', 'development');

require __DIR__ . '/../app/Config/Paths.php';

$paths = new Config\Paths();

require $paths->systemDirectory . '/Boot.php';

// FCPATH tidak didefinisikan oleh bootConsole() (hanya dipakai bootWeb()/
// bootSpark()), namun beberapa helper internal CI4 (exception handler,
// clean_path()) mengasumsikan konstanta ini ada. Definisikan defensif
// SEBELUM boot agar tidak fatal error sekunder saat menampilkan error.
defined('FCPATH') || define('FCPATH', __DIR__ . '/../public/');

CodeIgniter\Boot::bootConsole($paths);

// Helper path constant yang dipakai beberapa test (upload dsb).
defined('TESTPATH') || define('TESTPATH', __DIR__ . '/');
