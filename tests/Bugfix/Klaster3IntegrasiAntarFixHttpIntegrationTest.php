<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 22 (Checkpoint Klaster 3): Test INTEGRASI HTTP end-to-end yang
 * membuktikan CSRF token + captcha gambar + rate limit + CSP AKTIF
 * SECARA BERSAMAAN tidak saling menghalangi flow submit tiket publik
 * (bugfix.md T1/T2/T3/M1/M3; tasks.md task 22).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA TEST INI DIPERLUKAN — GAP YANG BELUM PERNAH DITUTUP TEST
 * MANA PUN SEBELUM TASK 22:
 * ═══════════════════════════════════════════════════════════════════
 * SELURUH test Klaster 3 sebelum task ini (task 12-21) menguji setiap
 * fix SATU-PER-SATU secara TERISOLASI:
 * - `Klaster3PreservationTest.php`/`T2M3RateLimitHttpIntegrationTest.php`
 *   men-seed captcha LANGSUNG ke session via `withSession(['captcha' =>
 *   ...])` — TIDAK PERNAH membaca gambar captcha sungguhan yang
 *   di-generate `Login::captchaImage()` dan didecode dari HTML halaman
 *   nyata.
 * - `T2CaptchaImageHttpIntegrationTest.php` (task 19.1) membuktikan
 *   gambar captcha valid DAN token tidak bocor plaintext, TAPI TIDAK
 *   menyertakan validasi CSRF penuh dalam skenario SUKSES submit tiket
 *   (test tersebut murni GET, tidak pernah POST savetiket).
 * - `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` (task 18.3)
 *   membuktikan CSRF DITOLAK dengan graceful, TAPI TIDAK PERNAH
 *   membuktikan skenario CSRF LOLOS bersamaan captcha gambar
 *   sungguhan.
 * - `M1CspHeaderHttpIntegrationTest.php` (task 21.2) hanya men-GET
 *   `/login`, TIDAK PERNAH memverifikasi CSP hadir pada respons POST
 *   `savetiket` yang BERHASIL.
 *
 * TIDAK ADA satu test pun yang membuktikan skenario PENGGUNA
 * SUNGGUHAN: buka `/login` sungguhan → dapat token CSRF dari HTML
 * halaman itu sendiri (bukan digenerate manual test helper) → baca
 * captcha yang di-generate BERSAMAAN render halaman tersebut (bukan
 * diseed manual) → submit `login/savetiket` dengan SEMUA elemen asli
 * sekaligus → sukses TANPA diblokir salah satu dari empat mekanisme
 * (CSRF, captcha, rate-limit, CSP). Test ini menutup gap tersebut.
 *
 * ═══════════════════════════════════════════════════════════════════
 * PENDEKATAN YANG DIPILIH (dari 3 opsi task 22) DAN ALASANNYA:
 * ═══════════════════════════════════════════════════════════════════
 * Kombinasi Opsi 1 (test HTTP-integration baru) + Opsi 2 (analisis
 * manual filter order) — BUKAN Opsi 1 murni tanpa analisis, BUKAN
 * Opsi 2 murni tanpa test:
 *
 * 1. SATU test HTTP-integration END-TO-END (method
 *    `testFlowLengkapCsrfCaptchaRateLimitCspAktifBersamaanTidakSalingMenghalangi()`
 *    di bawah) — MINIMAL (satu skenario sukses saja, BUKAN suite
 *    kombinatorial N×M×K yang rapuh) karena properti inti yang perlu
 *    dibuktikan hanya SATU: "keempat mekanisme aktif bersamaan tidak
 *    saling memblokir request yang sah". Membuktikan ini SATU KALI
 *    dengan bukti byte-level (curl ke server live, cookie jar
 *    sungguhan, HTML sungguhan, session store sungguhan) jauh lebih
 *    bernilai dan lebih TIDAK RAPUH daripada mencoba MENYIMULASIKAN
 *    "membaca captcha dari gambar PNG" (yang secara desain TIDAK
 *    MUNGKIN dilakukan test otomatis tanpa OCR — captcha gambar
 *    sengaja dibuat begitu, Requirement 2.27/2.28) dengan cara yang
 *    rapuh (retry-until-lucky, OCR library tambahan, dsb).
 *
 * 2. ANALISIS MANUAL urutan eksekusi filter (didokumentasikan di
 *    docblock method kedua di bawah,
 *    `testAnalisisUrutanFilterCsrfSelaluDieksekusiSebelumThrottleSehinggaTidakAdaFalsePositiveKonsumsiQuota()`)
 *    — menjawab pertanyaan konkret yang diajukan task 22: "apakah
 *    throttle counter bisa false-positive increment duluan sebelum
 *    CSRF gagal, menghabiskan quota rate-limit pengguna sah yang
 *    salah CSRF sekali". Dijawab DENGAN BUKTI (pembacaan source CI4
 *    LANGSUNG + assertion terhadap urutan array filter final), bukan
 *    hanya diklaim.
 *
 * TIDAK dipilih pendekatan "membaca nilai captcha via file session
 * server-side" sebagai BAGIAN test method utama di bawah — meski
 * pendekatan ini SUDAH DIVERIFIKASI VALID dan BERHASIL secara manual
 * sebelum test ini ditulis (lihat catatan investigasi pada
 * dokumentasi tasks.md task 22 hasil akhir): request
 * `GET https://eult.appdev-papenajam.me/login` sungguhan (cookie jar)
 * menghasilkan cookie `ci_session={id}` + HTML mengandung token CSRF
 * `csrf_test_name` sungguhan; file `writable/session/ci_session{id}`
 * (session driver `FileHandler`, `app/Config/Session.php`) — YANG
 * DIBACA DI SINI ADALAH FILESYSTEM SERVER SENDIRI, BUKAN SESUATU YANG
 * DIKIRIM KE KLIEN — berisi baris `captcha|s:4:"XXXX";` plaintext yang
 * BENAR-BENAR sama dengan nilai yang di-generate `eult_captcha_generate()`
 * dan direndernya sebagai gambar oleh `Login::captchaImage()` pada
 * request yang SAMA. Ini BUKAN pelanggaran Requirement 2.28
 * ("captcha tidak pernah dikirim plaintext ke KLIEN") — test membaca
 * store SERVER-SIDE langsung dari filesystem, bukan mengintersepsi
 * apa pun yang benar-benar dikirim melalui HTTP ke klien manapun;
 * "server live" pada proyek ini ADALAH proses PHP lokal yang menulis
 * ke `writable/session/` workspace ini sendiri (dikonfirmasi:
 * `/etc/hosts` memetakan `eult.appdev-papenajam.me` ke `127.0.0.1`).
 * POST `login/savetiket` dengan KETIGA nilai asli tersebut (kunci
 * CSRF+cookie dari HTML, captcha dari file session, dalam window rate
 * limit) BENAR-BENAR menghasilkan HTTP 200 `{"status":"success",...}`
 * DENGAN baris `d_ticketing` ter-insert nyata, DAN header respons yang
 * SAMA mengandung `Content-Security-Policy` lengkap serta
 * `X-Frame-Options`/`X-Content-Type-Options`/`Referrer-Policy` —
 * membuktikan KEEMPAT mekanisme aktif bersamaan pada SATU request yang
 * SAMA tanpa saling menghalangi. Baris uji dibersihkan segera setelah
 * verifikasi (`ticketEmail` fixture domain `.invalid`, tidak pernah
 * mengirim email sungguhan ke pihak ketiga, sesuai aturan fixture
 * proyek ini).
 *
 * TEST METHOD DI BAWAH INI MENDOKUMENTASIKAN ULANG prosedur investigasi
 * MANUAL tersebut sebagai TEST OTOMATIS PERMANEN yang dapat dijalankan
 * ulang kapan saja (bukan hanya sekali saat investigasi task 22),
 * mengikuti pola cURL-ke-server-live PERSIS
 * `T2CaptchaImageHttpIntegrationTest.php`/
 * `M1CspHeaderHttpIntegrationTest.php`/
 * `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` — HANYA
 * ditambahkan cookie jar (untuk membawa `ci_session`+`csrf_cookie_name`
 * dari GET ke POST, sesuatu yang TIDAK diperlukan test-test HTTP-
 * integration sebelumnya karena masing-masing hanya menguji SATU
 * mekanisme, bukan urutan GET→baca-session-file→POST yang menyatukan
 * keempatnya).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA TIDAK MEMBACA SESSION FILE LANGSUNG DI DALAM METHOD TEST
 * PERMANEN INI (berbeda dari investigasi manual):
 * ═══════════════════════════════════════════════════════════════════
 * Investigasi manual SEBELUM test ini ditulis mengonfirmasi pendekatan
 * "baca file session dari `writable/session/`" VALID dan BERHASIL.
 * NAMUN test method PERMANEN di bawah TIDAK memakainya — digantikan
 * pembacaan `session()->get('captcha')` LANGSUNG di dalam proses
 * PHPUnit SETELAH memicu `Login::index()` via `file_get_contents()`
 * konteks HTTP terpisah TIDAK memungkinkan berbagi session in-memory
 * antar proses, sehingga satu-satunya cara test PHPUnit (proses
 * terpisah dari server live) mengetahui nilai captcha yang di-generate
 * proses SERVER adalah membaca session store-nya di filesystem —
 * PERSIS teknik yang divalidasi manual. Test method di bawah
 * MENGADOPSI teknik yang SAMA (baca file session server-side via
 * session ID dari cookie jar) sebagai bagian resminya — BUKAN teknik
 * berbeda. Dijelaskan ulang di docblock method itu sendiri untuk
 * kejelasan pembaca yang hanya membaca method tersebut tanpa
 * membaca seluruh docblock kelas ini.
 *
 * Requirements: 1.15-1.30, 2.21-2.43 (bugfix.md — seluruh T1/T2/T3/M1/M3)
 *
 * @internal
 */
final class Klaster3IntegrasiAntarFixHttpIntegrationTest extends CIUnitTestCase
{
    /** Base URL server dev live (app.baseURL, .env) — presedan K1SqlInjectionHttpIntegrationTest.php. */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, fixture INDEPENDEN dari test lain. */
    private const EMAIL_FIXTURE = 'k22-integrasi-antar-fix@example.invalid';

    private BaseConnection $koneksi;

    private ?string $cookieJarPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test verifikasi ini WAJIB tersambung ke database nyata db_newtiket agar bukti insert d_ticketing bermakna.'
        );

        $this->cookieJarPath = tempnam(sys_get_temp_dir(), 'k3_integrasi_cj_');

        $this->bersihkanData();
    }

    protected function tearDown(): void
    {
        $this->bersihkanData();

        if ($this->cookieJarPath !== null && is_file($this->cookieJarPath)) {
            unlink($this->cookieJarPath);
        }

        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        $this->koneksi->table('d_ticketing')->where('ticketEmail', self::EMAIL_FIXTURE)->delete();
    }

    /**
     * Property (Integrasi Antar-Fix) — Task 22: form dengan CSRF token
     * SUNGGUHAN (diambil dari HTML halaman `/login` nyata, bukan
     * digenerate manual) + captcha gambar SUNGGUHAN (nilai dibaca dari
     * session store server-side yang SAMA dengan yang di-render
     * `Login::captchaImage()` pada request GET yang sama, BUKAN
     * diseed manual ke session test) + rate limit AKTIF (request ini
     * jelas di bawah ambang batas `login/savetiket` = 5/60detik) + CSP
     * AKTIF (header disertakan pada SETIAP response, termasuk
     * response 200 sukses ini) — SEMUA BERSAMAAN pada SATU request
     * yang SAMA — SHALL menghasilkan submit tiket SUKSES (HTTP 200,
     * `status: success`, baris `d_ticketing` ter-insert), DAN response
     * yang SAMA SHALL tetap menyertakan header keamanan lengkap
     * (Content-Security-Policy, X-Frame-Options, X-Content-Type-Options,
     * Referrer-Policy) TANPA satu pun dari empat mekanisme tersebut
     * saling menghalangi yang lain.
     *
     * ═══════════════════════════════════════════════════════════════
     * LANGKAH (mengikuti persis skenario pengguna sungguhan):
     * ═══════════════════════════════════════════════════════════════
     * (a) GET `/login` sungguhan dengan cookie jar → dapat
     *     `ci_session`+`csrf_cookie_name` (cookie asli server,
     *     `Config\Security::$csrfProtection = 'cookie'`) DAN token
     *     CSRF `csrf_test_name` di-parse dari HTML form (bukan
     *     `csrf_token()`/`csrf_hash()` helper test manual).
     * (b) Nilai captcha SESUNGGUHNYA yang di-generate request (a)
     *     dibaca dari file session server-side
     *     (`writable/session/ci_session{id}`, session ID dari cookie
     *     jar (a)) — teknik ini DIBAHAS LENGKAP pada docblock kelas
     *     ini (bagian "MENGAPA TIDAK DIPILIH...membaca session file
     *     DI DALAM method test"). Server live PADA PROYEK INI adalah
     *     proses PHP LOKAL yang menulis session ke `writable/session/`
     *     workspace ini sendiri (`/etc/hosts` memetakan
     *     `eult.appdev-papenajam.me` ke `127.0.0.1`) — membaca file
     *     ini adalah membaca store SERVER-SIDE, BUKAN mengintersepsi
     *     apa pun yang dikirim ke klien manapun (Requirement 2.28
     *     TIDAK dilanggar).
     * (c) POST `login/savetiket` dengan cookie jar YANG SAMA (a),
     *     field `csrf_test_name` = token dari (a), field `captcha` =
     *     nilai dari (b), field form lengkap lain — SATU request,
     *     assert 200 + `status: success` + insert nyata + header CSP/
     *     secureheaders tetap hadir pada response yang SAMA.
     */
    public function testFlowLengkapCsrfCaptchaRateLimitCspAktifBersamaanTidakSalingMenghalangi(): void
    {
        // --- (a) GET /login sungguhan, cookie jar sungguhan ---
        $halamanLogin = $this->httpKeServerLive('GET', 'login', [], []);

        self::assertSame(
            200,
            $halamanLogin['status'],
            sprintf('Prasyarat: GET /login ke server live SHALL 200. Status aktual: %d.', $halamanLogin['status'])
        );

        $cocokToken = preg_match(
            '/name="csrf_test_name"\s+value="([a-f0-9]{32,})"/',
            $halamanLogin['body'],
            $tangkapanToken
        );

        self::assertSame(
            1,
            $cocokToken,
            'Prasyarat: HTML halaman /login SHALL mengandung field csrf_test_name dengan nilai token CSRF sungguhan (Requirement 2.22, csrf_field()).'
        );

        $tokenCsrfAsli = $tangkapanToken[1];

        $sessionId = $this->ambilSessionIdDariCookieJar();

        self::assertNotNull(
            $sessionId,
            'Prasyarat: GET /login SHALL menghasilkan cookie ci_session (session driver FileHandler, app/Config/Session.php) pada cookie jar.'
        );

        // --- (b) Baca nilai captcha SESUNGGUHNYA dari session store server-side ---
        $captchaAsli = $this->bacaCaptchaDariSessionStore($sessionId);

        self::assertNotNull(
            $captchaAsli,
            sprintf(
                'Prasyarat: session store server-side (writable/session/ci_session%s) SHALL berisi nilai captcha plaintext hasil eult_captcha_generate() dari request GET /login (a) — nilai ini identik dengan yang dirender Login::captchaImage() sebagai gambar pada request yang sama (Requirement 2.28: nilai captcha TIDAK PERNAH dikirim plaintext KE KLIEN, namun TETAP tersimpan di session server yang boleh dibaca langsung dari filesystem server itu sendiri).',
                $sessionId
            )
        );

        // --- (c) POST login/savetiket dengan SEMUA elemen asli sekaligus ---
        $hasilSubmit = $this->httpKeServerLive('POST', 'login/savetiket', [
            'csrf_test_name'   => $tokenCsrfAsli,
            'captcha'          => $captchaAsli,
            'ticketCategories' => '1',
            'ticketEmail'      => self::EMAIL_FIXTURE,
            'ticketNoHp'       => '081234567890',
            'ticketSubject'    => 'Uji Integrasi Antar-Fix Task 22',
            'ticketMessage'    => 'Verifikasi flow end-to-end: CSRF token asli dari HTML + captcha gambar asli dari session + rate limit + CSP aktif bersamaan.',
            'ticketName'       => 'Pemohon Uji Integrasi Task22',
            'ticketPriority'   => '1',
        ], ['X-Requested-With: XMLHttpRequest']);

        self::assertSame(
            200,
            $hasilSubmit['status'],
            sprintf(
                'INTEGRASI ANTAR-FIX Task 22 — request dengan CSRF token asli + captcha asli + dalam batas rate-limit SHALL diproses 200 (BUKAN 403 CSRF, BUKAN ditolak captcha invalid, BUKAN 429 rate-limit) — keempat mekanisme SHALL tidak saling menghalangi request yang sah. Status aktual: %d. Body: %s',
                $hasilSubmit['status'],
                $hasilSubmit['body']
            )
        );

        self::assertStringContainsString(
            '"success"',
            $hasilSubmit['body'],
            sprintf(
                'INTEGRASI ANTAR-FIX Task 22 (Requirement 2.21-2.37 gabungan) — respons SHALL berstatus "success", membuktikan CSRF LOLOS (token valid), captcha LOLOS (nilai gambar asli benar), rate-limit LOLOS (di bawah ambang batas). Body aktual: %s',
                $hasilSubmit['body']
            )
        );

        // *** ASSERTION UTAMA CSP — pada RESPONSE SUKSES yang SAMA ***
        $headerCsp = $hasilSubmit['headers']['content-security-policy'] ?? '';

        self::assertNotSame(
            '',
            $headerCsp,
            'INTEGRASI ANTAR-FIX Task 22 (Requirement 2.39) — response HTTP 200 sukses submit tiket (CSRF+captcha+rate-limit LOLOS) SHALL TETAP menyertakan header Content-Security-Policy — CSP SHALL TIDAK PERNAH menghalangi PROSES request masuk (CSP murni directive browser untuk resource loading pada RESPONSE, bukan validasi request), dan kehadirannya SHALL TIDAK bergantung/terpengaruh oleh hasil validasi CSRF/captcha/throttle.'
        );

        foreach (['x-frame-options', 'x-content-type-options', 'referrer-policy'] as $namaHeader) {
            self::assertNotSame(
                '',
                $hasilSubmit['headers'][$namaHeader] ?? '',
                sprintf(
                    'INTEGRASI ANTAR-FIX Task 22 (Requirement 2.38) — header "%s" (filter secureheaders) SHALL tetap hadir pada response sukses submit tiket yang SAMA. Header lengkap: %s',
                    $namaHeader,
                    json_encode($hasilSubmit['headers'])
                )
            );
        }

        // Counterexample-integrasi konkret: baris d_ticketing SUNGGUHAN
        // ter-insert — bukti request diproses TUNTAS end-to-end (bukan
        // sekadar status HTTP 200 kosong), dengan KEEMPAT mekanisme
        // aktif bersamaan pada request yang menghasilkannya.
        $tiketTercipta = $this->koneksi->table('d_ticketing')
            ->where('ticketEmail', self::EMAIL_FIXTURE)
            ->get()->getRowArray();

        self::assertNotNull(
            $tiketTercipta,
            'INTEGRASI ANTAR-FIX Task 22 — baris d_ticketing SHALL ter-insert nyata untuk request yang lolos KEEMPAT mekanisme (CSRF, captcha, rate-limit, CSP) secara bersamaan — bukti proses end-to-end tuntas, bukan hanya status HTTP kosong.'
        );
    }

    /**
     * Property (Analisis Struktural) — Task 22, menjawab pertanyaan
     * konkret yang diajukan task text: "apakah throttle counter bisa
     * false-positive increment duluan sebelum CSRF gagal, menghabiskan
     * quota rate-limit pengguna sah yang salah CSRF sekali".
     *
     * JAWABAN (dibuktikan via assertion terhadap urutan array filter
     * FINAL, bukan hanya diklaim dalam komentar): TIDAK — filter
     * global `csrf` SELALU menempati posisi LEBIH DEPAN daripada
     * filter route-specific `throttle:*` pada array
     * `Filters::$filters['before']` FINAL, sehingga request yang
     * ditolak CSRF berhenti SEBELUM `ThrottleFilter::before()`
     * (yang melakukan increment counter) dieksekusi sama sekali —
     * TIDAK ADA konsumsi quota untuk request yang gagal CSRF.
     *
     * ═══════════════════════════════════════════════════════════════
     * DIBUKTIKAN DARI SOURCE CI4 LANGSUNG (bukan hanya baca
     * dokumentasi, method ini MENJALANKAN `Filters::initialize()`
     * SUNGGUHAN dan MEMBACA hasil urutan array final):
     * ═══════════════════════════════════════════════════════════════
     * `app/Config/Feature.php::$oldFilterOrder = false` (dikonfirmasi
     * ulang oleh assertion pertama method ini) — dengan pengaturan
     * ini, `Filters::initialize()`
     * (vendor/codeigniter4/framework/system/Filters/Filters.php)
     * memanggil `processFilters($uri)` (populate filter ROUTE-SPECIFIC
     * dari `$this->config->filters`, termasuk `throttle:login/savetiket`
     * untuk URI `login/savetiket`) LEBIH DULU, baru `processGlobals($uri)`
     * (populate filter GLOBAL dari `$this->config->globals`, termasuk
     * `csrf`/`invalidchars`) TERAKHIR — NAMUN `processGlobals()`
     * membangun `$this->filters['before']` dengan
     * `array_merge($filters['before'], $this->filters['before'])`
     * (baris ~709 Filters.php), yaitu MEN-PREPEND filter global ke
     * DEPAN array yang SUDAH ADA (bukan append ke belakang) —
     * `processFilters()` melakukan hal SERUPA untuk filter
     * route-specific SEBELUM itu (prepend terhadap filter yang sudah
     * ada dari method sebelumnya, jika ada). Konsekuensinya, posisi
     * PALING DEPAN pada array final SELALU ditempati oleh filter yang
     * diproses PALING TERAKHIR dalam `initialize()` — yaitu filter
     * GLOBAL (`processGlobals()` dipanggil terakhir).
     *
     * Method ini TIDAK hanya mengklaim ini dari pembacaan source —
     * ia benar-benar memicu `Filters::initialize('login/savetiket')`
     * pada konfigurasi filter PRODUKSI proyek ini (`Config\Filters`
     * SUNGGUHAN, bukan tiruan/stub) dan mengambil urutan filter
     * `before` final via `getFilters()` untuk membuktikan posisi
     * `csrf` benar-benar berada SEBELUM `throttle:login/savetiket`
     * pada array yang SAMA yang dipakai `runBefore()` sungguhan saat
     * request diproses.
     *
     * ═══════════════════════════════════════════════════════════════
     * TEMUAN TAMBAHAN (didokumentasikan, BUKAN bug — di luar
     * pertanyaan spesifik task 22, namun relevan disebutkan untuk
     * kelengkapan analisis integrasi):
     * ═══════════════════════════════════════════════════════════════
     * Captcha SALAH (bukan CSRF) TIDAK mendapat proteksi struktural
     * yang sama — `eult_captcha_check()` dipanggil DI DALAM controller
     * body (`Login::savetiket()`), yang berjalan SETELAH SELURUH
     * filter `before` (termasuk `throttle:login/savetiket`) sudah
     * lolos dan counternya SUDAH di-increment. Artinya: SATU percobaan
     * captcha yang salah OLEH PENGGUNA SAH (typo, bukan serangan) TETAP
     * mengonsumsi 1 dari 5 quota `login/savetiket` per menit. Ini
     * BUKAN kegagalan integrasi antar-fix yang perlu diperbaiki pada
     * checkpoint ini (task 22 adalah VERIFIKASI, bukan task fix) —
     * dicatat sebagai OBSERVASI, bukan BUG NYATA, karena: (1) ambang
     * batas `login/savetiket` (5/60detik, `Config\Throttle`) SUDAH
     * didokumentasikan EKSPLISIT pada task 19.2 sebagai "tetap memberi
     * ruang untuk percobaan ulang jika validasi/captcha gagal beberapa
     * kali" — trade-off ini SUDAH disengaja, BUKAN ditemukan baru di
     * sini; (2) captcha SALAH karena TYPO WAJAR (bukan brute-force
     * otomatis massal) secara realistis terjadi paling banyak 1-2 kali
     * per sesi kunjungan manusia, JAUH di bawah ambang 5, sehingga
     * TIDAK PERNAH benar-benar memblokir pengguna sah dalam skenario
     * wajar; (3) mengubah urutan (memvalidasi captcha SEBELUM filter
     * throttle) akan memerlukan REFAKTOR ARSITEKTUR (memindahkan
     * pengecekan captcha ke filter, bukan controller body) yang BUKAN
     * scope task 22 (checkpoint/verifikasi) dan BERPOTENSI mengubah
     * kontrak response existing — DILAPORKAN sebagai temuan analitis
     * pada dokumentasi tasks.md task 22, BUKAN diperbaiki di sini,
     * sesuai instruksi task 22 untuk BUG NYATA vs observasi trade-off
     * yang sudah disengaja.
     */
    public function testAnalisisUrutanFilterCsrfSelaluDieksekusiSebelumThrottleSehinggaTidakAdaFalsePositiveKonsumsiQuota(): void
    {
        $oldFilterOrder = config(\Config\Feature::class)->oldFilterOrder ?? false;

        self::assertFalse(
            $oldFilterOrder,
            'Prasyarat analisis: app/Config/Feature.php::$oldFilterOrder SHALL false (default proyek ini) — urutan prepend processGlobals() terakhir hanya berlaku pada pengaturan ini.'
        );

        $konfigFilters = new \Config\Filters();

        self::assertArrayHasKey(
            'throttle:login/savetiket',
            $konfigFilters->filters,
            'Prasyarat analisis: alias filter "throttle:login/savetiket" SHALL terdaftar di Config\\Filters::$filters (task 19.2).'
        );

        self::assertContains(
            'csrf',
            $konfigFilters->globals['before'],
            'Prasyarat analisis: alias filter "csrf" SHALL terdaftar di Config\\Filters::$globals[\'before\'] (task 18.1).'
        );

        // Memicu Filters::initialize() SUNGGUHAN terhadap konfigurasi
        // produksi proyek ini, untuk URI 'login/savetiket' — SAMA
        // persis dengan yang dipakai runtime request sungguhan.
        $filtersEngine = new \CodeIgniter\Filters\Filters($konfigFilters, service('request'), service('response'));
        $filtersEngine->initialize('login/savetiket');

        $urutanFilterBefore = $filtersEngine->getFilters()['before'];

        $posisiCsrf = array_search('csrf', $urutanFilterBefore, true);
        $posisiThrottle = array_search('throttle:login/savetiket', $urutanFilterBefore, true);

        self::assertNotFalse(
            $posisiCsrf,
            sprintf('Prasyarat analisis: filter "csrf" SHALL muncul pada urutan filter before FINAL untuk URI login/savetiket. Urutan aktual: %s', implode(', ', $urutanFilterBefore))
        );

        self::assertNotFalse(
            $posisiThrottle,
            sprintf('Prasyarat analisis: filter "throttle:login/savetiket" SHALL muncul pada urutan filter before FINAL untuk URI login/savetiket. Urutan aktual: %s', implode(', ', $urutanFilterBefore))
        );

        // *** ASSERTION UTAMA — csrf SELALU sebelum throttle ***
        self::assertLessThan(
            $posisiThrottle,
            $posisiCsrf,
            sprintf(
                'ANALISIS INTEGRASI Task 22 — filter "csrf" SHALL menempati posisi LEBIH DEPAN (indeks lebih kecil) daripada "throttle:login/savetiket" pada urutan eksekusi before FINAL — ini membuktikan request yang ditolak CSRF berhenti SEBELUM ThrottleFilter::before() (yang melakukan increment counter quota) dieksekusi sama sekali, sehingga TIDAK ADA false-positive konsumsi quota rate-limit akibat kegagalan CSRF. Urutan aktual: %s (posisi csrf=%d, posisi throttle=%d).',
                implode(', ', $urutanFilterBefore),
                $posisiCsrf,
                $posisiThrottle
            )
        );
    }

    /**
     * Mengirim request HTTP sungguhan ke server dev live via cURL,
     * DENGAN cookie jar (persist antar-panggilan pada test yang SAMA)
     * — perbedaan UTAMA dari seluruh helper cURL test lain di
     * direktori ini (yang TIDAK memakai cookie jar karena masing-masing
     * hanya menguji SATU mekanisme independen). Implementasi dasar
     * (CURLOPT_SSL_VERIFYPEER, CAINFO bundle sistem, CURLOPT_HEADERFUNCTION)
     * mengikuti pola PERSIS M1CspHeaderHttpIntegrationTest::getKeServerLive().
     *
     * @param array<string, string> $post
     * @param list<string>          $headerTambahan
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function httpKeServerLive(string $method, string $path, array $post, array $headerTambahan): array
    {
        $ch = curl_init(self::BASE_URL_LIVE . $path);

        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';
        $headerMentah   = [];

        $opsi = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $headerTambahan,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEFILE     => $this->cookieJarPath,
            CURLOPT_COOKIEJAR      => $this->cookieJarPath,
            CURLOPT_HEADERFUNCTION => static function ($curl, $baris) use (&$headerMentah) {
                $panjang = strlen($baris);
                $bagian  = explode(':', $baris, 2);

                if (count($bagian) === 2) {
                    $headerMentah[strtolower(trim($bagian[0]))] = trim($bagian[1]);
                }

                return $panjang;
            },
        ];

        if ($method === 'POST') {
            $opsi[CURLOPT_POST]       = true;
            $opsi[CURLOPT_POSTFIELDS] = $post;
        } else {
            $opsi[CURLOPT_HTTPGET] = true;
        }

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server dev live tanpa error transport (errno=%d: %s). Pastikan server live reachable sebelum menjalankan test ini.', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'headers' => $headerMentah, 'body' => $body];
    }

    /**
     * Membaca session ID dari cookie jar (format Netscape) — mencari
     * baris dengan nama cookie `ci_session` (Config\Session::$cookieName).
     */
    private function ambilSessionIdDariCookieJar(): ?string
    {
        if ($this->cookieJarPath === null || ! is_file($this->cookieJarPath)) {
            return null;
        }

        $isi = file_get_contents($this->cookieJarPath);

        if ($isi === false) {
            return null;
        }

        foreach (explode("\n", $isi) as $baris) {
            $kolom = preg_split('/\t/', trim($baris));

            if (count($kolom) === 7 && $kolom[5] === 'ci_session') {
                return $kolom[6];
            }
        }

        return null;
    }

    /**
     * Membaca nilai captcha PLAINTEXT dari session store SERVER-SIDE
     * (`writable/session/ci_session{id}`, session driver FileHandler)
     * — LIHAT docblock kelas ini (bagian "MENGAPA TIDAK DIPILIH...")
     * untuk pembahasan lengkap mengapa ini BUKAN pelanggaran
     * Requirement 2.28 ("captcha tidak pernah dikirim plaintext ke
     * KLIEN" — ini membaca store SERVER, bukan mengintersepsi respons
     * HTTP apa pun yang dikirim ke klien manapun).
     *
     * Parsing PHP session serialization format (`key|s:len:"value";`)
     * — regex sederhana cukup karena kunci `captcha` diketahui persis
     * (bukan parser serialisasi umum, cukup untuk kebutuhan spesifik
     * ini).
     */
    private function bacaCaptchaDariSessionStore(string $sessionId): ?string
    {
        $pathSessionFile = WRITEPATH . 'session/ci_session' . $sessionId;

        if (! is_file($pathSessionFile)) {
            return null;
        }

        $isi = file_get_contents($pathSessionFile);

        if ($isi === false) {
            return null;
        }

        $cocok = preg_match('/captcha\|s:\d+:"([^"]*)";/', $isi, $tangkapan);

        if ($cocok !== 1) {
            return null;
        }

        return $tangkapan[1];
    }
}
