<?php

namespace Tests\Bugfix;

use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Task 14 (Klaster 3 — T3): Test EKSPLORASI bug condition Cookie
 * `captcha_code` Membypass Validasi Session (bugfix.md 1.21, 1.22).
 *
 * PENTING — metodologi bug condition:
 * Test ini WAJIB GAGAL pada kode yang belum diperbaiki. Kegagalan test
 * mengonfirmasi bug ada (Property 1: Bug Condition). Test ini TIDAK
 * BOLEH diperbaiki di sini — dia mengenkode expected/fixed behavior dan
 * akan berubah menjadi LULUS setelah fix T3 diimplementasikan (task 19,
 * yang menghapus TOTAL pemanggilan `get_cookie('captcha_code')` dari
 * `eult_captcha_check()` — lihat design.md keputusan final T3).
 *
 * GOAL: Surface counterexample bahwa cookie klien mengalahkan session
 * server pada `eult_captcha_check()` (app/Helpers/eult_captcha_helper.php:36-40):
 *
 *   function eult_captcha_check(string $input): bool
 *   {
 *       $tersimpan = get_cookie('captcha_code');
 *       if (! $tersimpan) {
 *           $tersimpan = session()->get('captcha');
 *       }
 *       if (! is_string($tersimpan) || $tersimpan === '') {
 *           return false;
 *       }
 *       return strtoupper($input) === strtoupper($tersimpan);
 *   }
 *
 * Cookie diperiksa LEBIH DULU (baris 38) — HANYA fallback ke session
 * (baris 40) ketika cookie tidak diset sama sekali. Cookie sepenuhnya
 * dikontrol klien (tidak signed/tidak diverifikasi server), sehingga
 * penyerang dapat mengatur `captcha_code=ABCD` pada browser sendiri dan
 * lolos validasi tanpa pernah mengetahui nilai captcha session server
 * yang sebenarnya (misal `XYZ9`).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEPUTUSAN ARSITEKTUR TEST — MENGAPA TIDAK PERLU HTTP DISPATCH/DATABASE
 * ═══════════════════════════════════════════════════════════════════
 *
 * Berbeda dari K1/K2/T1/T2/T4 (yang membutuhkan dispatch HTTP penuh
 * atau koneksi database MySQL nyata db_newtiket), `eult_captcha_check()`
 * adalah FUNGSI MURNI: tidak menyentuh database, tidak dipanggil sebagai
 * controller action (bukan `exit;`/`eult_message_kirim()`), dan tidak
 * memerlukan filter/routing pipeline CI4 apa pun. Fungsi ini HANYA
 * membaca dua sumber: `get_cookie('captcha_code')` dan
 * `session()->get('captcha')` — keduanya adalah SERVICE CI4 (bukan
 * akses `$_COOKIE`/`$_SESSION` langsung), sehingga test tetap perlu
 * berjalan di dalam konteks `CIUnitTestCase` (agar service container
 * CI4 — `request`, `session` — tersedia), namun TIDAK memerlukan
 * `FeatureTestTrait`/dispatch HTTP sama sekali.
 *
 * ═══════════════════════════════════════════════════════════════════
 * TEMUAN KRITIS — MENGAPA COOKIE TIDAK BISA DISET VIA $_COOKIE SAJA
 * ═══════════════════════════════════════════════════════════════════
 *
 * `tests/bootstrap.php` proyek ini memanggil `CodeIgniter\Boot::bootConsole()`
 * (bukan `bootWeb()`) — jalur boot CLI. `Config\Services::createRequest()`
 * (dipanggil `CodeIgniter::getRequestObject()` selama boot) menentukan
 * `$isCli = true` pada proses PHPUnit (proses CLI), sehingga
 * `service('request')` — yang dipanggil `get_cookie()` secara internal
 * (`cookie_helper.php`) — SECARA DEFAULT mengembalikan instance
 * `CodeIgniter\HTTP\CLIRequest`, BUKAN `IncomingRequest`.
 *
 * `CLIRequest::getCookie()` (vendor/codeigniter4/framework/system/HTTP/CLIRequest.php)
 * adalah METHOD PLACEHOLDER YANG SELALU MENGEMBALIKAN `null` untuk index
 * tunggal APAPUN — method ini TIDAK PERNAH membaca `$_COOKIE`/service
 * `superglobals` sama sekali:
 *
 *   public function getCookie($index = null, $filter = null, $flags = null)
 *   {
 *       return $this->returnNullOrEmptyArray($index);
 *   }
 *
 * SOLUSI BAGIAN 1 (mengganti kelas request): mengganti service `request`
 * dengan instance `CodeIgniter\HTTP\IncomingRequest` SUNGGUHAN (bukan
 * mock/stub palsu — kelas produksi CI4 yang SAMA dipakai saat request
 * HTTP nyata) via `Config\Services::injectMock()` — method yang secara
 * eksplisit didokumentasikan CI4 dengan tag `@testTag only available to
 * test code` (BaseService.php) sebagai mekanisme resmi untuk
 * menyuntikkan service pengganti pada test.
 *
 * SOLUSI BAGIAN 2 (TEMUAN KRITIS KEDUA, awalnya SALAH diasumsikan cukup
 * dengan mengisi `$_COOKIE['captcha_code']` langsung SEBELUM konstruksi
 * — percobaan pertama INI GAGAL, dibuktikan sanity-check test di bawah):
 * `IncomingRequest::getCookie()` meneruskan ke
 * `RequestTrait::fetchGlobal('cookie', ...)`, yang MEMBACA dari
 * `service('superglobals')->getGlobalArray('cookie')` — BUKAN `$_COOKIE`
 * secara langsung. `Config\Services::superglobals()` adalah SHARED
 * SINGLETON (`getShared = true` default) yang di-konstruksi SEKALI di
 * awal proses PHPUnit (sebelum `tests/bootstrap.php` mengizinkan test
 * apa pun berjalan) — konstruktornya men-SNAPSHOT `$_COOKIE` PADA SAAT
 * ITU (`$cookie ?? $_COOKIE`) ke property privat `$this->cookie`, lalu
 * SELURUH pembacaan berikutnya (`cookie()`/`getGlobalArray('cookie')`)
 * membaca snapshot privat tersebut — TIDAK PERNAH `$_COOKIE` lagi.
 * Karena itu, memutasi `$_COOKIE` superglobal setelah boot TIDAK PERNAH
 * tersurface ke `get_cookie()` — snapshot sudah dibekukan sejak awal
 * (kosong, karena tidak ada cookie HTTP sungguhan pada proses CLI ini).
 *
 * Mekanisme resmi CI4 untuk memutasi snapshot ini pada test ADALAH
 * `Superglobals::setCookie($key, $value)` (lihat docblock class-nya:
 * "Provides a clean API for accessing and manipulating PHP superglobals
 * with support for testing") — method ini memutasi property privat
 * DAN `$_COOKIE` sekaligus (keduanya tetap disinkronkan test ini demi
 * konsistensi, meski hanya property privat yang benar-benar dibaca
 * `fetchGlobal()`). Karena itu, `service('superglobals')->setCookie(...)`
 * dipanggil SEBELUM SETIAP assertion di bawah — bukan `$_COOKIE`
 * langsung — sebagai mekanisme resmi (bukan tebakan) untuk mengontrol
 * nilai yang akan dibaca `get_cookie('captcha_code')`.
 *
 * Service `request` yang disuntikkan DIKEMBALIKAN ke instance semula
 * (CLIRequest asli sebelum test) di `tearDown()`, agar test lain yang
 * berjalan pada proses PHPUnit yang sama TIDAK terpengaruh oleh
 * penggantian ini (isolasi antar-test, tanpa memanggil `Services::reset()`
 * yang akan menghapus SELURUH service — termasuk yang tidak terkait
 * test ini). Snapshot cookie pada `superglobals` juga dikosongkan
 * kembali di `tearDown()` demi alasan isolasi yang sama.
 *
 * Requirements: 1.21, 1.22 (bugfix.md)
 */
final class T3CookieCaptchaBypassExplorationTest extends CIUnitTestCase
{
    /** Nilai session captcha "sebenarnya" (server-side, valid) untuk seluruh test di file ini. */
    private const NILAI_SESSION_SEBENARNYA = 'XYZ9';

    /** Service `request` original SEBELUM test ini menyuntikkan IncomingRequest bercookie. */
    private object $requestServiceSemula;

    protected function setUp(): void
    {
        parent::setUp();

        // Simpan service request semula (kemungkinan CLIRequest, karena
        // tests/bootstrap.php memakai bootConsole()) agar dapat
        // dikembalikan di tearDown() — isolasi antar-test.
        $this->requestServiceSemula = service('request');

        // Ganti service request dengan IncomingRequest SUNGGUHAN SATU
        // KALI di sini (bukan per-test-case) — cukup dilakukan sekali
        // karena globals['cookie'] pada instance ini di-populate LAZY
        // (baru dibaca dari service('superglobals') saat fetchGlobal()
        // pertama kali dipanggil, bukan saat konstruksi) — lihat
        // docblock kelas bagian TEMUAN KRITIS.
        Services::injectMock('request', new IncomingRequest(
            config(App::class),
            service('uri'),
            'php://input',
            new UserAgent(),
        ));

        // Pastikan tidak ada residu cookie dari test/proses lain sebelum
        // setiap test dimulai — memutasi snapshot service superglobals
        // (BUKAN $_COOKIE langsung, lihat docblock kelas).
        service('superglobals')->unsetCookie('captcha_code');
    }

    protected function tearDown(): void
    {
        service('superglobals')->unsetCookie('captcha_code');
        Services::injectMock('request', $this->requestServiceSemula);

        parent::tearDown();
    }

    /**
     * Mengontrol nilai `captcha_code` yang akan dibaca `get_cookie()`,
     * via mekanisme resmi CI4 untuk memutasi snapshot cookie pada test:
     * `Superglobals::setCookie()`/`unsetCookie()` — BUKAN `$_COOKIE`
     * secara langsung (terbukti tidak berpengaruh, lihat docblock kelas
     * bagian TEMUAN KRITIS: snapshot service superglobals sudah
     * dibekukan sejak boot, sebelum test ini berjalan).
     */
    private function suntikkanCookieCaptcha(?string $nilaiCookie): void
    {
        $superglobals = service('superglobals');

        if ($nilaiCookie === null) {
            $superglobals->unsetCookie('captcha_code');
        } else {
            $superglobals->setCookie('captcha_code', $nilaiCookie);
        }
    }

    /**
     * Sanity: pastikan mekanisme injeksi IncomingRequest + mutasi
     * `service('superglobals')->setCookie()` di atas BENAR-BENAR membuat
     * `get_cookie('captcha_code')` membaca nilai yang diinginkan —
     * memverifikasi prasyarat test SEBELUM menyimpulkan apa pun tentang
     * bug T3 itu sendiri (agar kegagalan assertion di bawah tidak
     * disalahartikan sebagai kegagalan mekanisme test).
     */
    public function testSanityInjeksiCookieMembuatGetCookieMembacaNilaiYangDiset(): void
    {
        $this->suntikkanCookieCaptcha('ABCD');

        self::assertSame(
            'ABCD',
            get_cookie('captcha_code'),
            'Prasyarat test: get_cookie("captcha_code") HARUS membaca nilai yang diset via service("superglobals")->setCookie() setelah service request diganti ke IncomingRequest — bila ini gagal, mekanisme injeksi itu sendiri bermasalah (bukan bug T3).'
        );
    }

    /**
     * Sanity: pastikan tanpa cookie diset sama sekali, `get_cookie()`
     * mengembalikan nilai falsy (null/''), sesuai baris
     * `if (! $tersimpan) { $tersimpan = session()->get('captcha'); }`
     * pada kode asli — fallback ke session HANYA terjadi jika cookie
     * benar-benar tidak ada.
     */
    public function testSanityTanpaCookieGetCookieMengembalikanNilaiFalsy(): void
    {
        $this->suntikkanCookieCaptcha(null);

        self::assertEmpty(
            get_cookie('captcha_code'),
            'Prasyarat test: tanpa cookie diset, get_cookie("captcha_code") harus falsy (null/kosong) agar baseline "tanpa cookie -> fallback session" bermakna.'
        );
    }

    /**
     * Property 1 (Bug Condition) — SKENARIO UTAMA yang diminta task 14:
     * cookie `captcha_code=ABCD` (berbeda dari session `XYZ9`), kirim
     * `captcha=ABCD` -> `eult_captcha_check('ABCD')`.
     *
     * EXPECTED/FIXED behavior (setelah fix T3, task 19): SHALL `false`
     * — validasi captcha SEHARUSNYA murni dari session, cookie klien
     * SEHARUSNYA tidak pernah dipertimbangkan sama sekali. Karena
     * `'ABCD' !== 'XYZ9'` (tidak cocok session), hasil yang benar adalah
     * GAGAL validasi.
     *
     * *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
     * Pada kode belum diperbaiki, cookie diperiksa LEBIH DULU dan cocok
     * dengan input (`'ABCD' === 'ABCD'`), sehingga fungsi mengembalikan
     * `true` — assertion `assertFalse` di bawah akan GAGAL, membuktikan
     * cookie klien dapat membypass validasi session.
     */
    public function testCookieBerbedaDariSessionMembypassValidasi(): void
    {
        session()->set('captcha', self::NILAI_SESSION_SEBENARNYA);
        $this->suntikkanCookieCaptcha('ABCD');

        // Sanity eksplisit: cookie dan session BENAR-BENAR berbeda nilai
        // (prasyarat bermakna bagi counterexample bug condition T3).
        self::assertNotSame(
            self::NILAI_SESSION_SEBENARNYA,
            get_cookie('captcha_code'),
            'Prasyarat counterexample: nilai cookie HARUS berbeda dari nilai session agar test ini benar-benar menguji bug "cookie mengalahkan session", bukan kasus keduanya kebetulan sama.'
        );

        $hasil = eult_captcha_check('ABCD');

        self::assertFalse(
            $hasil,
            sprintf(
                'BUG CONDITION T3 TERKONFIRMASI: eult_captcha_check(\'ABCD\') mengembalikan %s meskipun cookie captcha_code (\'ABCD\') TIDAK COCOK dengan nilai session yang sebenarnya (\'%s\') — cookie yang sepenuhnya dikontrol klien (tidak signed/tidak diverifikasi server) berhasil MENGALAHKAN validasi session server, membuktikan get_cookie(\'captcha_code\') diperiksa LEBIH DULU sebelum session()->get(\'captcha\') pada eult_captcha_check() (app/Helpers/eult_captcha_helper.php:36-40).',
                $hasil ? 'true' : 'false',
                self::NILAI_SESSION_SEBENARNYA
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — Scoped PBT Approach (design.md Testing
     * Strategy: "T3: string cookie arbitrer"). Karena proyek ini TIDAK
     * memiliki dependency PBT khusus di composer.json (konsisten dengan
     * K1SqlInjectionExplorationTest::provideSqliMetacharacterPayloads()),
     * scoped-PBT diimplementasikan sebagai data provider berisi korpus
     * representatif pasangan (nilai cookie, nilai session) yang SENGAJA
     * dibuat BERBEDA satu sama lain — domain bug ini bukan tentang
     * semantik SQL, melainkan independensi COOKIE VALUE vs SESSION VALUE,
     * sehingga korpus difokuskan pada variasi bentuk string (pendek,
     * karakter khusus, beda kapitalisasi, tepi/edge seperti string
     * kosong) yang tetap berbeda dari session pasangannya.
     *
     * Untuk SETIAP pasangan di korpus ini, `eult_captcha_check($cookie)`
     * (input pengguna PERSIS SAMA dengan nilai cookie, mereproduksi
     * skenario penyerang yang tahu cookie yang dia set sendiri) SHALL
     * `false` pada expected/fixed behavior, karena `$cookie !== $session`
     * untuk seluruh pasangan di korpus.
     *
     * @dataProvider provideCookieBerbedaDariSessionPayloads
     */
    public function testCookieBerbedaDariSessionMembypassValidasiAcrossDomain(string $nilaiCookie, string $nilaiSession, string $keterangan): void
    {
        self::assertNotSame(
            strtoupper($nilaiCookie),
            strtoupper($nilaiSession),
            sprintf('Prasyarat korpus test ("%s"): nilai cookie dan session HARUS berbeda (case-insensitive) agar pasangan ini bermakna sebagai counterexample T3.', $keterangan)
        );

        session()->set('captcha', $nilaiSession);
        $this->suntikkanCookieCaptcha($nilaiCookie);

        $hasil = eult_captcha_check($nilaiCookie);

        self::assertFalse(
            $hasil,
            sprintf(
                'BUG CONDITION T3 TERKONFIRMASI (%s): eult_captcha_check("%s") mengembalikan %s meskipun cookie ("%s") TIDAK COCOK session ("%s") — cookie klien mengalahkan validasi session untuk pasangan nilai ini juga, membuktikan bug T3 berlaku merata di domain string cookie arbitrer (bukan hanya satu payload "ABCD"/"XYZ9").',
                $keterangan,
                $nilaiCookie,
                $hasil ? 'true' : 'false',
                $nilaiCookie,
                $nilaiSession
            )
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function provideCookieBerbedaDariSessionPayloads(): array
    {
        return [
            'contoh baku task (ABCD vs XYZ9)' => ['ABCD', 'XYZ9', 'pasangan persis seperti dicontohkan task 14/bugfix.md 1.22'],
            'string pendek 1 karakter'        => ['A', 'B', 'string pendek satu karakter'],
            'angka saja'                      => ['1234', '5678', 'nilai captcha alfanumerik-numerik-saja (alfabet captcha memuat 0-9)'],
            'karakter khusus'                 => ['!@#$', 'WXYZ', 'karakter khusus di luar alfabet captcha asli (klien bebas mengatur cookie apa pun)'],
            'beda kapitalisasi saja tapi beda huruf' => ['abcd', 'ABCE', 'variasi kapitalisasi dikombinasikan dengan huruf terakhir berbeda agar tetap tidak cocok case-insensitive'],
            'string panjang'                  => ['ABCDEFGHIJ', 'ZYXWVUTSRQ', 'string lebih panjang dari panjang captcha standar (4) — cookie tidak dibatasi format apa pun oleh klien'],
            'unicode'                         => ['ÄBCD', 'XYZ9', 'karakter unicode pada cookie (klien dapat mengirim byte apa pun sebagai nilai cookie)'],
        ];
    }

    /**
     * BASELINE/SANITY (BUKAN bagian dari assertion bug T3 yang wajib
     * gagal) — didokumentasikan sesuai instruksi task 14 poin 3: skenario
     * TIDAK ADA cookie sama sekali (hanya session) SEHARUSNYA tetap
     * berfungsi benar (fallback session) baik SEBELUM maupun SESUDAH
     * fix T3 — ini adalah Preservation 3.19 (bugfix.md), bukan tugas
     * task 14 untuk membuktikannya gagal. Diamati di sini HANYA sebagai
     * observasi baseline agar konteks bug (cookie DISET dan tidak cocok
     * session) dan non-bug (cookie TIDAK diset) jelas dibedakan penulis
     * test berikutnya (task 17/20) — assertion di bawah DIHARAPKAN
     * LULUS pada kode asli (bukan bagian dari counterexample yang wajib
     * gagal untuk task ini).
     */
    public function testBaselineTanpaCookieFallbackKeSessionTetapBenar(): void
    {
        session()->set('captcha', self::NILAI_SESSION_SEBENARNYA);
        $this->suntikkanCookieCaptcha(null);

        self::assertTrue(
            eult_captcha_check(self::NILAI_SESSION_SEBENARNYA),
            'Baseline (bukan bug): tanpa cookie captcha_code diset sama sekali, input yang cocok session SEHARUSNYA tetap lolos — perilaku ini SAMA di kode lama dan baru (bug T3 hanya muncul ketika cookie DISET dan tidak cocok session, bukan ketidakhadiran cookie).'
        );

        self::assertFalse(
            eult_captcha_check('SALAHTOTAL'),
            'Baseline (bukan bug): tanpa cookie, input yang TIDAK cocok session SEHARUSNYA tetap gagal (fallback session bekerja normal untuk kasus non-bug).'
        );
    }

    /**
     * Baseline case-insensitivity (Preservation 3.19 bugfix.md): baik
     * SEBELUM maupun SESUDAH fix, perbandingan tetap mengikuti
     * `strtoupper()` pada kedua sisi.
     *
     * **KOREKSI pasca-fix T3 (task 20.2)**: versi method ini SEBELUMNYA
     * bernama `testBaselineCaseInsensitiveTetapBerlakuMeskipunViaCookie`
     * dan mengklaim menguji case-insensitivity "via cookie" — klaim itu
     * SALAH. Skenario lamanya men-set session='lainlagi', cookie='abcd',
     * lalu mengecek `eult_captcha_check('aBcD')` mengharapkan `true`.
     * Nilai `'aBcD'` HANYA cocok (case-insensitive) dengan cookie
     * ('abcd'), BUKAN dengan session ('lainlagi') — sehingga skenario
     * itu, tanpa disadari penulisnya, justru BERGANTUNG PADA precedence
     * cookie yang menjadi inti bug T3 itu sendiri (Requirement 2.31/
     * 2.32: cookie SHALL TIDAK PERNAH dibaca lagi dalam bentuk apa pun,
     * tanpa kondisi/periode transisi). Setelah fix T3, cookie tidak lagi
     * dibaca sama sekali, sehingga `'aBcD'` dibandingkan HANYA dengan
     * session ('lainlagi') — TIDAK cocok — hasil yang benar SEHARUSNYA
     * `false`, bukan `true` seperti diklaim assertion lama. Ini adalah
     * defek logika pada TEST itu sendiri (skenario data yang keliru),
     * bukan pada implementasi fix T3.
     *
     * Skenario BARU di bawah menguji case-insensitivity MURNI dari
     * SESSION (bukan cookie): input adalah variasi kapitalisasi dari
     * nilai session itu sendiri, dan cookie DISET ke nilai yang BERBEDA
     * dari session (agar tetap membuktikan secara eksplisit bahwa
     * cookie diabaikan total, bukan sekadar dihapus dari skenario).
     * Jika fix T3 salah (cookie masih terbaca), input yang cocok cookie
     * (bukan variasi kapitalisasi session) akan ikut lolos secara keliru
     * — namun karena input di sini SELALU variasi kapitalisasi session
     * (tidak pernah menyamai cookie 'BERBEDASEKALI'), assertion `true`
     * di bawah tetap valid murni sebagai pembuktian case-insensitivity
     * session, terlepas dari status cookie.
     */
    public function testBaselineCaseInsensitiveTetapBerlakuMurniDariSessionCookieDiabaikan(): void
    {
        session()->set('captcha', 'lainlagi');
        $this->suntikkanCookieCaptcha('BERBEDASEKALI');

        foreach (['lainlagi', 'LAINLAGI', 'LainLagi', 'lAiNlAgI'] as $variasiKapitalisasi) {
            self::assertTrue(
                eult_captcha_check($variasiKapitalisasi),
                sprintf(
                    'Baseline: perbandingan captcha SHALL tetap case-insensitive (strtoupper() kedua sisi) murni terhadap nilai SESSION ("%s") — perilaku ini TIDAK berubah oleh fix T3, HANYA sumber nilai pembanding (session, cookie sudah tidak pernah dibaca sama sekali) yang berubah. Cookie diset ke nilai berbeda ("BERBEDASEKALI") dan tidak boleh berpengaruh pada hasil ini.',
                    $variasiKapitalisasi
                )
            );
        }

        // Pembuktian eksplisit tambahan: input yang HANYA cocok dengan
        // cookie (bukan dengan session) SHALL gagal — membuktikan cookie
        // benar-benar diabaikan, bukan sekadar "kebetulan tidak diuji".
        self::assertFalse(
            eult_captcha_check('BERBEDASEKALI'),
            'Cookie ("BERBEDASEKALI") SHALL diabaikan total pasca-fix T3 — input yang HANYA cocok dengan nilai cookie (bukan session "lainlagi") SEHARUSNYA tetap gagal validasi, membuktikan tidak ada jalur pembacaan cookie yang tersisa dalam bentuk apa pun (Requirement 2.32).'
        );
    }
}
