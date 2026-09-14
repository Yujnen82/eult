<?php

/**
 * File prepend KHUSUS untuk `K4DebugToolbarServerRunner::start()`
 * (dipakai via ini flag `-d auto_prepend_file=` pada invocation `php -S`).
 *
 * BUKAN test PHPUnit, BUKAN dieksekusi langsung sebagai script CLI biasa
 * — file ini disertakan PHP SENDIRI (via `auto_prepend_file`) SEBELUM
 * `public/index.php` (dan dengan demikian sebelum
 * `app/Config/Boot/{ENVIRONMENT}.php`) dieksekusi pada SETIAP request
 * yang dilayani server test yang di-spawn dengan opsi ini.
 *
 * TUJUAN: men-simulasikan skenario operator misconfiguration di mana
 * konstanta `CI_DEBUG` ter-override menjadi `true` SECARA INDEPENDEN
 * dari `ENVIRONMENT` (misal via `auto_prepend_file` yang tidak disengaja
 * tertinggal di `php.ini` server production, atau mekanisme override
 * konfigurasi PHP lain yang setara) — TIDAK merepresentasikan cara
 * BOOT NORMAL project ini menghasilkan kombinasi ini (dikonfirmasi
 * eksplisit: HANYA 3 file `app/Config/Boot/*.php` yang ada
 * — `development.php`/`testing.php`/`production.php` — dan
 * `production.php` SENDIRI SELALU men-`define('CI_DEBUG', false)`
 * tanpa syarat ketika dicapai secara NORMAL; TIDAK ADA kombinasi
 * `ENVIRONMENT` × Boot-file bawaan project ini yang menghasilkan
 * `CI_DEBUG=true` pada `ENVIRONMENT` selain `development`/`testing`).
 *
 * Mekanisme: `app/Config/Boot/production.php` memakai
 * `defined('CI_DEBUG') || define('CI_DEBUG', false)` — pola "define
 * jika belum ada". Karena file prepend INI dieksekusi LEBIH DAHULU
 * (sebelum Boot file apa pun), `CI_DEBUG` SUDAH `true` pada titik Boot
 * file production.php dieksekusi, sehingga `defined('CI_DEBUG')`
 * bernilai `true` dan `define()` milik Boot file menjadi NO-OP —
 * `CI_DEBUG` TETAP `true` untuk sisa request, TANPA menyentuh
 * `production.php`/Boot file apa pun secara fisik.
 *
 * Requirements: 1.12, 1.13, 1.14 (bugfix.md) -- pendukung
 * K4DebugToolbarExplorationTest.php, lihat docblock kelas tersebut
 * untuk penjelasan lengkap mengapa skenario ini valid sebagai bug
 * condition K4 (defense-in-depth yang hilang: TIDAK ADA pengecekan
 * ENVIRONMENT independen dari CI_DEBUG pada titik manapun di
 * Toolbar::prepare()/filter toolbar).
 */
define('CI_DEBUG', true);
