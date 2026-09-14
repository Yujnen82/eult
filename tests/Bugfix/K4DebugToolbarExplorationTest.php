<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use K4DebugToolbarServerRunner;

require_once __DIR__ . '/K4DebugToolbarServerRunner.php';

/**
 * Task 24 (Klaster 4 — K4): Test EKSPLORASI bug condition Toolbar Debug
 * Aktif Tanpa Syarat Environment (bugfix.md 1.12, 1.13, 1.14; design.md
 * bagian "K4 — Debug toolbar publik membocorkan session & PII lintas
 * pengguna").
 *
 * PENTING — metodologi bug condition:
 * Test ini WAJIB GAGAL pada kode yang belum diperbaiki. Kegagalan test
 * mengonfirmasi bug ada (Property 1: Bug Condition). Test ini TIDAK
 * BOLEH diperbaiki di sini — dia mengenkode expected/fixed behavior dan
 * akan berubah menjadi LULUS setelah fix K4 diimplementasikan (task
 * 27.1, yang akan menambahkan pengecekan `ENVIRONMENT === 'development'`
 * pada titik filter `toolbar` — lihat bugfix.md Requirement 2.17-2.20).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KOREKSI PREMIS TASK TEXT — WAJIB DIBACA SEBELUM MEMAHAMI TEST INI
 * ═══════════════════════════════════════════════════════════════════
 *
 * Task text asli (tasks.md task 24) menyebut skenario literal: "Set
 * `ENVIRONMENT=testing` (setara production untuk tujuan fix ini),
 * request `?debugbar_time={ts}` ... assert response mengandung data
 * debug". Investigasi LANGSUNG source
 * (`vendor/codeigniter4/framework/system/Debug/Toolbar.php`) SEBELUM
 * menulis test ini menemukan bahwa skenario literal tersebut TIDAK
 * DAPAT mereproduksi bug K4 secara bermakna, karena ADA DUA method
 * berbeda dengan gerbang yang BERBEDA:
 *
 * 1. `Toolbar::prepare()` (dipanggil filter `after` `toolbar` —
 *    `CodeIgniter\Filters\DebugToolbar::after()` →
 *    `service('toolbar')->prepare($request, $response)` — method
 *    INILAH yang menulis `writable/debugbar/*.json` DAN meng-inject
 *    `<script id="debugbar_loader">` ke `<head>` HTML) — gerbangnya
 *    HANYA `if (CI_DEBUG && ! is_cli())`. **TIDAK ADA pengecekan
 *    `ENVIRONMENT` SAMA SEKALI** pada method ini.
 *
 * 2. `Toolbar::respond()` (dipanggil `app/Config/Events.php:47-49`,
 *    DI DALAM blok `if (CI_DEBUG && ! is_cli())` yang SAMA — method
 *    inilah yang melayani `?debugbar_time=`/`?debugbar` — dan method
 *    ini punya baris PALING AWAL: `if (ENVIRONMENT === 'testing') {
 *    return; }` — HANYA mengecualikan `ENVIRONMENT` PERSIS `'testing'`,
 *    TIDAK mengecualikan `production`/nilai lain apa pun.
 *
 * KONSEKUENSI: skenario literal task text ("set ENVIRONMENT=testing,
 * request ?debugbar_time") justru akan mengenai pengecualian KHUSUS
 * `respond()` di atas dan TIDAK PERNAH mengembalikan data debug apa
 * pun — BUKAN karena bug sudah "fixed", melainkan karena
 * `ENVIRONMENT === 'testing'` adalah pengecualian YANG SUDAH ADA di
 * framework CI4 sendiri (kemungkinan untuk mencegah gangguan test
 * PHPUnit yang berjalan dengan `ENVIRONMENT=testing`), SAMA SEKALI
 * TIDAK berhubungan dengan fix K4 yang akan dibuat task 27.1. Memakai
 * skenario literal ini akan menghasilkan test yang GAGAL untuk alasan
 * YANG SALAH (tidak membuktikan bug K4, hanya membuktikan pengecualian
 * `respond()` yang sudah ada dan tidak relevan) — test SEPERTI ITU
 * TIDAK AKAN berubah menjadi LULUS setelah fix 27.1 diimplementasikan
 * dengan cara yang bermakna (fix 27.1 menyasar titik filter/`prepare()`,
 * BUKAN baris `ENVIRONMENT === 'testing'` bawaan framework pada
 * `respond()`), melanggar prinsip "test eksplorasi mengenkode expected
 * behavior yang akan lulus pasca-fix secara BERMAKNA".
 *
 * ═══════════════════════════════════════════════════════════════════
 * BUG K4 YANG SEBENARNYA (rekonstruksi dari source, dikonfirmasi
 * `app/Config/Filters.php`/`app/Config/Events.php`)
 * ═══════════════════════════════════════════════════════════════════
 *
 * `prepare()` (menulis file debugbar + inject script) HANYA bergantung
 * pada `CI_DEBUG`, TIDAK PERNAH memeriksa `ENVIRONMENT`. Filter
 * `toolbar` (`app/Config/Filters.php:34,66`, kategori
 * `$required['after']`) memanggil `prepare()` ini TANPA syarat
 * environment TAMBAHAN apa pun di level filter — `DebugToolbar::
 * after()` (vendor) HANYA memanggil `service('toolbar')->
 * prepare(...)`, titik. `respond()` (melayani `?debugbar_time=`) juga
 * HANYA dipanggil di dalam gerbang `CI_DEBUG` yang SAMA
 * (`app/Config/Events.php:47`), dan gerbang internalnya SENDIRI HANYA
 * mengecualikan `'testing'`, bukan `!== 'development'`.
 *
 * Bug SESUNGGUHNYA (formal spec design.md, PERSIS dikonfirmasi):
 * `isBugCondition(input) = input.environment != 'development' AND
 * toolbarFilterExecutes(input)` — yaitu: TIDAK ADA kode/filter APA PUN
 * di project ini yang secara EKSPLISIT memeriksa `ENVIRONMENT ===
 * 'development'` sebelum mengizinkan toolbar aktif. Toolbar aktif
 * SEMATA-MATA karena `CI_DEBUG=true` — nilai yang KEBETULAN sama pada
 * `development.php` DAN `testing.php` (dikonfirmasi baca langsung
 * ketiga file `app/Config/Boot/*.php`: development=true, testing=true,
 * production=false), TANPA lapisan pertahanan KEDUA yang independen
 * memeriksa `ENVIRONMENT`.
 *
 * ═══════════════════════════════════════════════════════════════════
 * SKENARIO BUG CONDITION YANG DIPILIH — Opsi A (CI_DEBUG, bukan
 * ENVIRONMENT literal) + Opsi B (respond() pada ENVIRONMENT non-testing)
 * DIGABUNGKAN (setara "Opsi C" investigasi awal)
 * ═══════════════════════════════════════════════════════════════════
 *
 * Investigasi TAMBAHAN pada `CodeIgniter\Boot::defineEnvironment()`/
 * `loadEnvironmentBootstrap()` (vendor/.../system/Boot.php) mengonfirmasi
 * TIDAK ADA validasi konsistensi `CI_DEBUG` ↔ `ENVIRONMENT` apa pun
 * saat boot NORMAL — NAMUN `loadEnvironmentBootstrap()` HANYA berhasil
 * jika file `app/Config/Boot/{ENVIRONMENT}.php` ADA (`is_file()`
 * check); project ini HANYA memiliki 3 file (`development`/`testing`/
 * `production`), sehingga `ENVIRONMENT` bernilai APAPUN selain 3 string
 * itu akan 503 SEBELUM mencapai kode aplikasi apa pun (dikonfirmasi
 * `bootWeb()` memanggil `loadEnvironmentBootstrap()` dengan `$exit=true`
 * default). Ini berarti kombinasi "ENVIRONMENT=production TAPI
 * CI_DEBUG=true" TIDAK BISA dicapai HANYA dengan mengatur environment
 * variable `CI_ENVIRONMENT` pada BOOT NORMAL project ini — `production.
 * php` SENDIRI SELALU `define('CI_DEBUG', false)` tanpa syarat ketika
 * benar-benar dicapai secara normal.
 *
 * Test ini karena itu men-SIMULASIKAN operator misconfiguration
 * (`CI_DEBUG` ter-override independen dari `ENVIRONMENT`, misal via
 * `auto_prepend_file`/ini directive lain yang tertinggal — skenario
 * REALISTIS: operator men-debug isu production dengan menambahkan
 * override sementara, lalu lupa menghapusnya) memakai
 * `-d auto_prepend_file=` pada server test yang di-spawn
 * (`K4ForceCiDebugTruePrepend.php`, lihat docblock file tersebut) —
 * BUKAN memodifikasi `production.php`/Boot file apa pun secara fisik.
 * `ENVIRONMENT` PROSES tetap GENUINELY `'production'` (dikonfirmasi
 * `config.environment` pada JSON debugbar yang dihasilkan, DAN via
 * baseline negatif tanpa prepend pada test sanity), HANYA `CI_DEBUG`
 * yang disimulasikan ter-override — mereplikasi PERSIS kondisi
 * `isBugCondition()` formal spec design.md (`input.environment !=
 * 'development'`, di sini `'production'`) TANPA memerlukan mekanisme
 * boot abnormal/rusak.
 *
 * Nilai ini dipilih (bukan Opsi A murni yang HANYA memeriksa file
 * debugbar tanpa HTTP, dan bukan Opsi B murni yang HANYA menguji
 * respond() tanpa membuktikan prepare() menulis datanya) karena
 * MEMBUKTIKAN RANTAI LENGKAP end-to-end yang PALING relevan untuk
 * risiko operasional nyata: (1) `prepare()` MENULIS file debugbar +
 * meng-inject script PADA REQUEST HTML BIASA (`GET /login`) ketika
 * `CI_DEBUG` ter-override, TANPA MEMANDANG `ENVIRONMENT=production`
 * genuine (test method 1); (2) `respond()` KEMUDIAN BENAR-BENAR
 * MELAYANI file tersebut secara PENUH via `GET ?debugbar_time=`
 * PERSIS seperti yang diminta task text (walau bukan `ENVIRONMENT=
 * testing` seperti literalnya, melainkan `ENVIRONMENT=production`
 * yang TIDAK dikecualikan `respond()`) (test method 2) — kombinasi ini
 * membuktikan risiko OPERASIONAL PENUH: operator yang salah
 * meninggalkan override `CI_DEBUG=true` pada server `ENVIRONMENT=
 * production` SUNGGUHAN akan benar-benar mengekspos toolbar penuh via
 * HTTP publik, PERSIS skenario `1.13` bugfix.md (curl ke server publik
 * mengembalikan 200 data debug lengkap), hanya root cause pemicunya
 * `CI_DEBUG` (bukan `ENVIRONMENT=development` seperti kondisi server
 * live SAAT INI, TAPI mekanisme gating YANG SAMA PERSIS yang akan
 * diperbaiki fix 27.1 sekali untuk keduanya).
 *
 * **Klasifikasi**: bug ini adalah defense-in-depth yang hilang — pada
 * boot NORMAL project ini (tanpa override apa pun), kondisi ini HANYA
 * reachable jika operator salah mengatur `.env` production dengan
 * `CI_ENVIRONMENT=development`/`testing` (persis kondisi server live
 * SAAT INI, dikonfirmasi `.env` fisik project: `CI_ENVIRONMENT =
 * development`) — BUKAN via `CI_DEBUG` yang disimulasikan test ini
 * secara terisolasi. Simulasi `CI_DEBUG` di sini murni untuk MEMBUKTIKAN
 * SECARA ISOLASI bahwa TIDAK ADA lapisan pertahanan KEDUA yang
 * independen memeriksa `ENVIRONMENT` — nilai tambahnya adalah
 * mengonfirmasi fix 27.1 (yang akan menambahkan pengecekan `ENVIRONMENT
 * === 'development'` LANGSUNG, bukan HANYA memindahkan ketergantungan
 * ke `CI_DEBUG` dengan cara lain) benar-benar menutup KEDUA jalur
 * (baik `CI_ENVIRONMENT` salah maupun `CI_DEBUG` ter-override
 * independen) sekaligus — bukan hanya menutup SATU jalur yang
 * kebetulan cocok dengan kondisi server live saat ini.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEPUTUSAN ARSITEKTUR TEST — MENGAPA SERVER PHP TERPISAH VIA
 * proc_open() (BUKAN FeatureTestTrait, BUKAN cURL-ke-server-live)
 * ═══════════════════════════════════════════════════════════════════
 *
 * `Toolbar::prepare()` membutuhkan `$app->getPerformanceStats()`
 * (benchmark `total_execution` yang HANYA di-start `CodeIgniter::
 * run()` selama dispatch HTTP PENUH) — TIDAK tersedia bermakna pada
 * `bootConsole()` (dipakai K3) MAUPUN `CodeIgniter\Test\
 * FeatureTestTrait::call()` (dikonfirmasi TIDAK memanggil
 * `Response::send()`/lifecycle run() penuh yang sama, presedan
 * `M1CspHeaderHttpIntegrationTest.php`/investigasi CSP sebelumnya).
 * Server live (port 8099/8100, proses terpisah) TIDAK BISA dipakai
 * karena `ENVIRONMENT`-nya TERIKAT PERMANEN pada `.env` fisik project
 * (`development`) — mengubahnya memerlukan restart proses server live
 * dengan environment variable berbeda, DILARANG eksplisit oleh
 * batasan task ini. Solusi: `proc_open()` men-spawn SERVER PHP
 * BUILT-IN BARU (`php -S`) yang benar-benar menjalankan
 * `Boot::bootWeb()` dari awal dengan `CI_ENVIRONMENT` proses OS yang
 * DIKONTROL test — lihat `K4DebugToolbarServerRunner.php` untuk detail
 * lengkap mekanisme ini TERMASUK kuirk `variables_order`/`DotEnv` yang
 * WAJIB diatasi (`-d variables_order=EGPCS`) agar `ENVIRONMENT` benar2
 * ter-resolve sesuai yang diminta test, BUKAN diam-diam jatuh ke nilai
 * `.env` fisik project akibat urutan `loadDotEnv()` vs
 * `defineEnvironment()` pada `Boot::bootWeb()`.
 *
 * Response 307 (`ForceHTTPS`, `$forceGlobalSecureRequests=true` di
 * `app/Config/App.php`) SELALU muncul untuk request HTTP polos ke
 * server test — TIDAK diikuti (`CURLOPT_FOLLOWLOCATION` TIDAK
 * diaktifkan, lihat docblock `K4DebugToolbarServerRunner.php` untuk
 * alasan kritis: mengikuti redirect akan diam-diam mengarah ke server
 * live SUNGGUHAN, bukan server test yang terisolasi) — BODY 307
 * TERSEBUT SENDIRI sudah melalui filter `after` (termasuk `toolbar`)
 * SEBELUM redirect ditentukan filter `before` `forcehttps`, sehingga
 * tetap bermakna untuk observasi bug K4 tanpa perlu mengikuti redirect
 * sama sekali (dikonfirmasi empiris pada investigasi manual sebelum
 * test ini ditulis).
 *
 * Requirements: 1.12, 1.13, 1.14 (bugfix.md)
 */
final class K4DebugToolbarExplorationTest extends CIUnitTestCase
{
    private const PORT_BASELINE_NEGATIVE = 9611;
    private const PORT_FORCED_CI_DEBUG   = 9612;
    private const PORT_RESPOND_CHECK     = 9613;

    private K4DebugToolbarServerRunner $runner;

    private string $prependScriptPath;

    /** Path file debugbar yang ditulis SELAMA test ini berjalan -- dibersihkan tearDown() agar tidak menumpuk residu permanen di writable/debugbar/. */
    private array $debugbarFilesDitulisTest = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->runner             = new K4DebugToolbarServerRunner(dirname(__DIR__, 2));
        $this->prependScriptPath  = __DIR__ . '/K4ForceCiDebugTruePrepend.php';

        self::assertFileExists(
            $this->prependScriptPath,
            'Prasyarat test: K4ForceCiDebugTruePrepend.php harus ada di direktori yang sama -- dipakai simulasi CI_DEBUG ter-override independen dari ENVIRONMENT (lihat docblock kelas ini bagian "Skenario Bug Condition Yang Dipilih").'
        );
    }

    protected function tearDown(): void
    {
        $this->runner->stop();

        foreach ($this->debugbarFilesDitulisTest as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    /**
     * Mengirim GET ke server test yang di-spawn `proc_open()`, TANPA
     * mengikuti redirect (lihat docblock kelas untuk alasan kritis
     * tidak memakai CURLOPT_FOLLOWLOCATION di sini).
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function getKeServerTest(string $baseUrl, string $path): array
    {
        $ch = curl_init($baseUrl . $path);

        $headerMentah = [];

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET             => true,
            CURLOPT_RETURNTRANSFER      => true,
            CURLOPT_HEADER              => false,
            CURLOPT_TIMEOUT             => 10,
            CURLOPT_FOLLOWLOCATION      => false, // KRITIS -- lihat docblock kelas.
            CURLOPT_HEADERFUNCTION      => static function ($curl, $baris) use (&$headerMentah) {
                $panjang = strlen($baris);
                $bagian  = explode(':', $baris, 2);

                if (count($bagian) === 2) {
                    $headerMentah[strtolower(trim($bagian[0]))] = trim($bagian[1]);
                }

                return $panjang;
            },
        ]);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server test K4 tanpa error transport (errno=%d: %s).', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'headers' => $headerMentah, 'body' => $body];
    }

    /**
     * Sanity: pastikan mekanisme server test (proc_open, isolasi env,
     * mitigasi kuirk variables_order/DotEnv) BENAR-BENAR menghasilkan
     * ENVIRONMENT sesuai yang diminta -- SEBELUM menyimpulkan apa pun
     * tentang bug K4 itu sendiri. Baseline NEGATIF: ENVIRONMENT=
     * production TANPA prepend (CI_DEBUG murni dari production.php,
     * SHALL false) -- toolbar SHALL TIDAK aktif sama sekali.
     */
    public function testSanityBaselineNegatifProductionTanpaForceMenunjukkanToolbarNonaktif(): void
    {
        $this->runner->start('production', null, self::PORT_BASELINE_NEGATIVE);

        $hasil = $this->getKeServerTest($this->runner->baseUrl(), 'login');

        // Baseline negatif harus 307 (ForceHTTPS, presedan seluruh
        // HttpIntegrationTest lain di direktori ini) dengan body KOSONG
        // (toolbar tidak menyuntikkan apa pun karena CI_DEBUG=false
        // genuine pada production.php yang tidak di-override).
        self::assertSame(307, $hasil['status'], 'Prasyarat sanity: server test SHALL mengembalikan 307 (ForceHTTPS) untuk request HTTP polos, sama seperti seluruh server test lain di direktori ini.');

        self::assertStringNotContainsString(
            'debugbar_loader',
            $hasil['body'],
            'Prasyarat sanity BASELINE NEGATIF: pada ENVIRONMENT=production GENUINE tanpa override CI_DEBUG apa pun, toolbar SHALL TIDAK menyuntikkan script loader -- bila ini gagal, mekanisme isolasi server test itu sendiri bermasalah (bukan bug K4), KEMUNGKINAN mekanisme mitigasi kuirk variables_order/DotEnv tidak bekerja sebagaimana didokumentasikan K4DebugToolbarServerRunner.php.'
        );
    }

    /**
     * Property 1 (Bug Condition) — bagian 1/2: `Toolbar::prepare()`
     * MENULIS file debugbar DAN meng-inject script loader pada
     * `ENVIRONMENT=production` GENUINE ketika `CI_DEBUG` disimulasikan
     * ter-override `true` secara independen (lihat docblock kelas
     * untuk alasan lengkap simulasi ini, DAN koreksi premis task text
     * mengapa `ENVIRONMENT=testing` literal tidak dipakai).
     *
     * *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
     * Pada kode belum diperbaiki, `prepare()` (`Toolbar.php`) HANYA
     * memeriksa `CI_DEBUG`, TIDAK PERNAH `ENVIRONMENT`. Assertion
     * `assertStringNotContainsString`/`assertFileDoesNotExist` di bawah
     * (yang mengenkode EXPECTED/FIXED behavior pasca 27.1) akan GAGAL
     * pada kode asli, karena toolbar SESUNGGUHNYA aktif meski
     * `ENVIRONMENT=production`.
     */
    public function testBugConditionPrepareMenyuntikkanToolbarPadaEnvironmentProductionKetikaCiDebugTerOverride(): void
    {
        $this->runner->start('production', $this->prependScriptPath, self::PORT_FORCED_CI_DEBUG);

        $hasil = $this->getKeServerTest($this->runner->baseUrl(), 'login');

        self::assertSame(307, $hasil['status'], 'Prasyarat: server test SHALL tetap mengembalikan 307 (ForceHTTPS) -- status code TIDAK berubah oleh CI_DEBUG, hanya BODY-nya yang relevan untuk bug K4.');

        $dataTimeMatches = [];
        preg_match('/data-time="([^"]+)"/', $hasil['body'], $dataTimeMatches);

        if (isset($dataTimeMatches[1])) {
            $this->debugbarFilesDitulisTest[] = dirname(__DIR__, 2) . '/writable/debugbar/debugbar_' . $dataTimeMatches[1] . '.json';
        }

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior (pasca fix K4 27.1): toolbar SHALL
        // TIDAK aktif pada ENVIRONMENT != 'development', TERLEPAS dari
        // nilai CI_DEBUG independen apa pun (defense-in-depth: fix
        // SHALL memeriksa ENVIRONMENT secara LANGSUNG, bukan HANYA
        // mengandalkan CI_DEBUG). Pada kode asli, prepare() BERHASIL
        // menyuntikkan script loader HANYA berdasarkan CI_DEBUG,
        // TANPA MEMANDANG ENVIRONMENT=production sungguhan.
        self::assertStringNotContainsString(
            'debugbar_loader',
            $hasil['body'],
            sprintf(
                'BUG CONDITION K4 TERKONFIRMASI: pada ENVIRONMENT=production GENUINE (dikonfirmasi via sanity test terpisah) dengan CI_DEBUG disimulasikan ter-override true secara independen, Toolbar::prepare() (vendor/codeigniter4/framework/system/Debug/Toolbar.php) TETAP menyuntikkan script "<script id=\"debugbar_loader\">" ke body response -- membuktikan TIDAK ADA pengecekan ENVIRONMENT apa pun pada method ini, hanya CI_DEBUG, persis bugfix.md 1.12 ("filter toolbar terdaftar tanpa pengecekan environment"). Body length: %d byte (baseline negatif SHALL 0 byte).',
                strlen($hasil['body'])
            )
        );

        // Bukti tambahan tidak langsung -- file debugbar SHALL TIDAK
        // ditulis sama sekali pasca-fix pada environment non-development.
        if (isset($dataTimeMatches[1])) {
            $expectedFilePath = dirname(__DIR__, 2) . '/writable/debugbar/debugbar_' . $dataTimeMatches[1] . '.json';

            self::assertFileDoesNotExist(
                $expectedFilePath,
                sprintf(
                    'BUG CONDITION K4 TERKONFIRMASI (bukti tambahan): file debug "%s" TERTULIS ke writable/debugbar/ pada ENVIRONMENT=production sungguhan -- persis bugfix.md 1.14 (nama file predictable, dapat ditemukan tanpa otorisasi tambahan). Isi config.environment file tersebut (jika ada) SHALL "production" bukan "development", membuktikan ini BUKAN kesalahan resolusi ENVIRONMENT pada mekanisme test.',
                    basename($expectedFilePath)
                )
            );
        }
    }

    /**
     * Property 1 (Bug Condition) — bagian 2/2: `Toolbar::respond()`
     * MELAYANI `?debugbar_time=` dengan data debug LENGKAP pada
     * `ENVIRONMENT=production` (BUKAN `'testing'` seperti literal task
     * text yang telah dikoreksi di atas) — melengkapi rantai end-to-end
     * yang persis diminta task text (curl ke `?debugbar_time`), hanya
     * dengan environment value yang BENAR-BENAR reachable secara
     * bermakna (`respond()` HANYA mengecualikan `'testing'`, bukan
     * `production`).
     *
     * *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
     * Pada kode belum diperbaiki, `respond()` (`Toolbar.php`) HANYA
     * mengecualikan `ENVIRONMENT === 'testing'` -- `production` LOLOS
     * pengecualian tersebut sepenuhnya. Assertion `assertNotSame(200,
     * ...)` di bawah (mengenkode expected/fixed behavior pasca 27.1)
     * akan GAGAL pada kode asli, karena endpoint benar-benar
     * mengembalikan 200 dengan data debug lengkap.
     *
     * CATATAN (task 27.2, pasca-fix 27.1): method ini SEKARANG memakai
     * SATU request tunggal ke `?debugbar_time=` dengan timestamp
     * SINTETIS (bukan lagi 2-langkah chaining via request `/login`
     * terpisah seperti versi asli task 24) -- lihat docblock DI DALAM
     * body method (bukan di sini) untuk analisis gap instrumentasi
     * lengkap dan alasan perbaikan.
     */
    public function testBugConditionRespondMelayaniDebugbarTimeDenganDataLengkapPadaEnvironmentProduction(): void
    {
        // ═══════════════════════════════════════════════════════════
        // GAP INSTRUMENTASI TEST DITEMUKAN + DIPERBAIKI (task 27.2,
        // pasca-fix 27.1 -- pola identik gap K3 task 26.1, format
        // dokumentasi mengikuti presedan tersebut, substansi berbeda)
        // ═══════════════════════════════════════════════════════════
        //
        // Versi ASLI method ini (ditulis SEBELUM fix 27.1 ada, task 24)
        // men-chain 2 langkah: (1) GET /login dengan CI_DEBUG
        // ter-override untuk memperoleh timestamp valid dari atribut
        // data-time yang disuntikkan prepare() -- lihat method
        // testBugConditionPrepareMenyuntikkanToolbar...() di atas; (2)
        // GET ?debugbar_time={timestamp} memakai timestamp tersebut
        // untuk menguji respond(). Asumsi ini VALID sebelum fix 27.1
        // (prepare() TIDAK ditutup, sehingga langkah 1 SELALU
        // menghasilkan data-time secara "natural"), namun TIDAK LAGI
        // valid pasca-fix 27.1: prepare() SEKARANG BENAR ditutup pada
        // ENVIRONMENT != 'development' (EnvironmentAwareToolbar::
        // after() return null tanpa memanggil parent), sehingga
        // langkah 1 tidak lagi menghasilkan data-time apa pun --
        // precondition assertArrayHasKey(1, $dataTimeMatches) GAGAL
        // SEBELUM assertion utama yang menguji respond() (di bawah)
        // sempat dieksekusi sama sekali.
        //
        // Investigasi source LANGSUNG (app/Config/Events.php,
        // vendor/.../Debug/Toolbar.php::respond()) DAN reproduksi
        // manual (curl ke server test dengan ENVIRONMENT=production
        // genuine, TANPA file writable/debugbar/*.json apa pun) SEBELUM
        // memperbaiki test ini mengonfirmasi: guard
        // `if (ENVIRONMENT === 'development') { service('toolbar')->
        // respond(); }` pada Events.php MEMBUNGKUS TITIK PANGGIL
        // respond() itu sendiri -- ketika ENVIRONMENT != 'development',
        // respond() TIDAK PERNAH dipanggil SAMA SEKALI (bukan dipanggil
        // lalu menolak secara internal), sehingga method tersebut TIDAK
        // PERNAH mencapai baris yang membaca file
        // writable/debugbar/{filename}.json apa pun. Ini berarti
        // keberadaan/isi file debugbar SAMA SEKALI TIDAK RELEVAN untuk
        // assertion utama (`assertNotSame(200, ...)`) pasca-fix --
        // guard di titik panggil (aplikasi) sudah menutup jalur SEBELUM
        // logic internal respond() (termasuk pembacaan file) tereksekusi.
        //
        // Reproduksi manual mengonfirmasi EMPIRIS: request
        // ?debugbar_time={timestamp SEMBARANG, file TIDAK PERNAH ada}
        // pada server ENVIRONMENT=production genuine (CI_ENVIRONMENT
        // diset sebagai environment variable proses OS EKSPLISIT,
        // PERSIS mekanisme K4DebugToolbarServerRunner::start()) SELALU
        // menghasilkan 307 (ForceHTTPS, status SAMA seperti route lain
        // apa pun di aplikasi ini) dengan body 0 byte -- IDENTIK dengan
        // baseline negatif (testSanityBaselineNegatifProduction...()),
        // TIDAK PERNAH 200/404-dari-respond()/500 dari template toolbar
        // apa pun. Ini membuktikan assertion utama TETAP BERMAKNA tanpa
        // bergantung pada file debugbar ATAU pada prepare() yang sudah
        // (benar) ditutup -- timestamp SEMBARANG cukup untuk menguji
        // APAKAH respond() dipanggil sama sekali (bug K4: dipanggil
        // unconditional, TERLEPAS environment; fixed: dipanggil hanya
        // jika ENVIRONMENT === 'development').
        //
        // KEPUTUSAN: dipilih pendekatan "timestamp sintetis, TANPA
        // ketergantungan pada prepare()/file debugbar apa pun" --
        // BUKAN Opsi A (menulis file JSON manual) karena TERBUKTI TIDAK
        // DIPERLUKAN sama sekali (file tidak pernah dibaca ketika guard
        // aktif, dikonfirmasi investigasi di atas); BUKAN Opsi B
        // (server kedua ENVIRONMENT=development genuine untuk
        // menghasilkan file, lalu dibaca server production) karena
        // menambah kompleksitas (2 server, timing antar-proses) TANPA
        // manfaat pembuktian tambahan dibanding pendekatan yang lebih
        // sederhana ini. Timestamp sintetis dipilih dengan FORMAT
        // PERSIS yang diharapkan `sanitize_filename()`/pola
        // 'debugbar_{time}.json' (lihat Toolbar::prepare() vendor,
        // `sprintf('%.6F', ...)`) agar tetap merepresentasikan skenario
        // realistis (penyerang menebak/mengamati format timestamp dari
        // response lain), BUKAN string sembarang yang tidak valid
        // sebagai timestamp.
        $this->runner->start('production', $this->prependScriptPath, self::PORT_RESPOND_CHECK);

        $timestampSintetis = sprintf('%.6F', microtime(true));

        $this->debugbarFilesDitulisTest[] = dirname(__DIR__, 2) . '/writable/debugbar/debugbar_' . $timestampSintetis . '.json';

        self::assertFileDoesNotExist(
            $this->debugbarFilesDitulisTest[0],
            'Prasyarat test (pasca-fix): file debugbar SHALL TIDAK ada untuk timestamp sintetis ini -- prepare() sudah (benar) ditutup pada ENVIRONMENT != development (task 27.1), sehingga tidak ada file "natural" yang dihasilkan. Assertion utama di bawah SHALL tetap bermakna TANPA file ini (lihat docblock method untuk penjelasan lengkap mengapa keberadaan file tidak relevan ketika guard Events.php aktif).'
        );

        // Request TUNGGAL ke ?debugbar_time= (persis pola task text
        // bugfix.md 1.13) memakai timestamp sintetis di atas -- TIDAK
        // ADA langkah request /login terpisah lagi (berbeda dari versi
        // asli method ini), karena timestamp tidak lagi bergantung pada
        // hasil prepare() pada request lain mana pun.
        $hasilDebugbarTime = $this->getKeServerTest($this->runner->baseUrl(), 'index.php?debugbar_time=' . $timestampSintetis);

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior (pasca fix K4 27.1, Requirement 2.18):
        // endpoint ?debugbar_time SHALL TIDAK mengembalikan data debug
        // apa pun pada ENVIRONMENT != 'development' -- SHALL setara
        // toolbar nonaktif (404/kosong), bukan 200 dengan data lengkap.
        // Pada kode asli, respond() HANYA mengecualikan 'testing',
        // sehingga 'production' (environment GENUINE test ini) LOLOS
        // dan endpoint mengembalikan 200 dengan HTML toolbar penuh.
        self::assertNotSame(
            200,
            $hasilDebugbarTime['status'],
            sprintf(
                'BUG CONDITION K4 TERKONFIRMASI: pada ENVIRONMENT=production GENUINE (BUKAN \'testing\' seperti literal task text yang telah dikoreksi -- lihat docblock kelas), GET ?debugbar_time=%s mengembalikan HTTP %d dengan body %d byte -- membuktikan Toolbar::respond() HANYA mengecualikan ENVIRONMENT===\'testing\' secara spesifik (baris pertama method tersebut), TIDAK mengecualikan environment lain manapun termasuk \'production\' sungguhan, persis bugfix.md 1.13 (curl ke endpoint debugbar_time mengembalikan 200 dengan data debug lengkap tanpa autentikasi apa pun).',
                $timestampSintetis,
                $hasilDebugbarTime['status'],
                strlen($hasilDebugbarTime['body'])
            )
        );

        // Bukti tambahan: body SHALL tidak mengandung marker toolbar
        // (kint-rich-script/collector) yang menandakan data debug
        // benar-benar terlayani, bukan sekadar status code kebetulan
        // non-200 karena sebab lain.
        self::assertStringNotContainsString(
            'kint-rich',
            $hasilDebugbarTime['body'],
            'BUG CONDITION K4 TERKONFIRMASI (bukti tambahan): body response ?debugbar_time mengandung marker toolbar (kint-rich, dari Kint::dump() yang dipanggil format()/toolbar.tpl.php) -- mengonfirmasi data debug BENAR-BENAR terlayani penuh (bukan sekadar page kosong dengan status berbeda).'
        );
    }
}
