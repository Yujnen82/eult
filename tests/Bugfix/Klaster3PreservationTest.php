<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Task 17 (Klaster 3 — Preservasi): Test PROPERTI PRESERVASI (Property 2)
 * — "Request Sah, Captcha Benar, Aset CSP, Pengguna Wajar Tidak
 * Terblokir" (bugfix.md 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20,
 * 3.23, 3.24, 3.27, 3.28; design.md Property 10/11/13).
 *
 * PENTING — metodologi observation-first:
 * Test ini mengobservasi perilaku kode BELUM diperbaiki untuk input SAH
 * (request AJAX legitimate, captcha benar, pengguna wajar) dan
 * menetapkannya sebagai baseline yang WAJIB dipertahankan identik
 * setelah fix T1 (task 18, aktivasi CSRF/secureheaders/honeypot/
 * invalidchars), T2/M3 (task 19, captcha gambar + rate limiting), T3
 * (task 20, hapus fallback cookie captcha), dan M1 (task 21, aktivasi
 * CSP) diimplementasikan. Test ini WAJIB LULUS pada kode belum
 * diperbaiki — kelulusan mengonfirmasi baseline yang harus tidak
 * berubah pasca-fix (BUKAN mengonfirmasi bug, berbeda dari
 * T1CsrfExplorationTest.php/T2CaptchaRateLimitExplorationTest.php/
 * T3CookieCaptchaBypassExplorationTest.php/
 * M1SecurityHeadersExplorationTest.php/
 * M3TicketEnumerationExplorationTest.php pada task 12-16).
 *
 * ═══════════════════════════════════════════════════════════════════
 * REUSE POLA — TIDAK ADA MEKANISME DISPATCH BARU DITEMUKAN DI SINI:
 * ═══════════════════════════════════════════════════════════════════
 * Seluruh mekanisme dispatch/seeding pada file ini diambil LANGSUNG
 * dari 5 file eksplorasi Klaster 3 (task 12-16), TANPA modifikasi:
 *
 * - `FeatureTestTrait::withSession(['captcha' => ...])->post(...)` +
 *   header `X-Requested-With: XMLHttpRequest` — persis
 *   `T1CsrfExplorationTest::testPostSavetiketTanpaTokenCsrfDitolak...()`
 *   dan `T2CaptchaRateLimitExplorationTest::
 *   testSeluruhRequestSavetiketSuksesBeruntunDiprosesTanpaRateLimit()`.
 * - Field POST `login/savetiket` mengikuti persis struktur
 *   `T1CsrfExplorationTest::fieldFormLengkapTanpaCsrf()` (validasi
 *   $aturan + captcha benar + FK ticketPriority='1'), dengan email
 *   fixture BERBEDA/independen (lihat konstanta EMAIL_AMAN di bawah)
 *   untuk isolasi lifecycle dari T1/T2/T4 (instruksi task 17 poin 6).
 * - Deteksi sinyal respons via substring pada body mentah (BUKAN
 *   `json_decode()`) — kuirk pembungkus `<!DOCTYPE html>...` yang
 *   sudah didokumentasikan lengkap di docblock
 *   T2CaptchaRateLimitExplorationTest.php/T1CsrfExplorationTest.php/
 *   M3TicketEnumerationExplorationTest.php, dan DIVERIFIKASI ULANG
 *   secara empiris untuk request spesifik file ini (lihat hasil
 *   dump body mentah pada komentar method uji terkait) — bukan
 *   diasumsikan berlaku otomatis.
 * - `eult_captcha_check()` dipanggil LANGSUNG (tanpa HTTP) — persis
 *   `T3CookieCaptchaBypassExplorationTest::
 *   testBaselineTanpaCookieFallbackKeSessionTetapBenar()` (skenario
 *   TANPA cookie apa pun diset, murni session — bukan skenario bug T3
 *   itu sendiri yang melibatkan cookie berbeda dari session).
 * - Render `layouts/login` via helper `view()` — persis
 *   `M1SecurityHeadersExplorationTest`/
 *   `T1CsrfExplorationTest::testFormLoginTidakMengandungPemanggilan...()`
 *   yang men-dispatch/merender view produksi yang sama.
 * - Route `refresh_captcha` DIVERIFIKASI ULANG secara empiris berada
 *   di dalam grup `login` (path lengkap `login/refresh_captcha`, BUKAN
 *   bare `refresh_captcha` — percobaan pertama dengan path bare
 *   menghasilkan `PageNotFoundException`, dikoreksi setelah dispatch
 *   nyata mengonfirmasi struktur grup di `app/Config/Routes.php`).
 * - Nomor tiket read-only `QEHO-HTTV-001` — persis
 *   `M3TicketEnumerationExplorationTest::NOMOR_TIKET_VALID` (tiket
 *   nyata terverifikasi ada di `d_ticketing`, dipakai lintas beberapa
 *   file test di direktori ini) — dipilih di sini KHUSUS untuk bagian
 *   cektiket yang read-only (tidak perlu insert/cleanup apa pun).
 *
 * ═══════════════════════════════════════════════════════════════════
 * ISOLASI DATA — EMAIL FIXTURE BERBEDA DARI T1/T2/T4:
 * ═══════════════════════════════════════════════════════════════════
 * `login/savetiket` pada file ini memakai
 * `klaster3preservasi-uji@example.invalid` (domain RFC 2606 `.invalid`,
 * tidak pernah resolve DNS sungguhan) — BERBEDA dari
 * `t1csrf-uji@example.invalid` (T1),
 * `t2captcha-uji@example.invalid` (T2), dan `t4http-uji@example.invalid`
 * (T4), agar cleanup file ini (`WHERE ticketEmail = fixture`) tidak
 * pernah bersinggungan dengan lifecycle test lain (instruksi task 17
 * poin 6). Bagian `login/cektiket` memakai nomor tiket read-only
 * `QEHO-HTTV-001` (M3-style) — TIDAK menghasilkan baris baru, TIDAK
 * memerlukan cleanup.
 *
 * ═══════════════════════════════════════════════════════════════════
 * SCOPE — BASELINE-CAPTURE, BUKAN EXHAUSTIVE SUITE:
 * ═══════════════════════════════════════════════════════════════════
 * Mengikuti instruksi task 17 poin 7: test ini SENGAJA dibatasi pada 5
 * observasi yang diminta (struktur JSON, kebenaran captcha,
 * refresh-captcha, frekuensi wajar tidak terblokir, aset statis) —
 * TIDAK menambah edge-case di luar itu, TIDAK mengulang loop 15-request
 * milik T2/M3 (frekuensi wajar di sini cukup dibuktikan dengan SATU
 * request sukses + SATU request typo, bukan load-test berulang).
 *
 * Requirements: 3.13, 3.14, 3.15, 3.16, 3.17, 3.18, 3.19, 3.20, 3.23,
 * 3.24, 3.27, 3.28 (bugfix.md)
 */
final class Klaster3PreservationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, INDEPENDEN dari fixture T1/T2/T4. */
    private const EMAIL_AMAN = 'klaster3preservasi-uji@example.invalid';

    /** Nilai captcha yang di-seed langsung ke session test ini sendiri (pola sama dengan T1/T2CsrfExplorationTest). */
    private const CAPTCHA_DIKETAHUI = 'K3OK';

    /** Nomor tiket VALID sungguhan, read-only, terverifikasi ada di d_ticketing (sama seperti M3TicketEnumerationExplorationTest). */
    private const NOMOR_TIKET_VALID = 'QEHO-HTTV-001';

    /** Nomor tiket tebakan/typo — TIDAK ADA di database, murni untuk skenario "salah ketik sesekali" (Requirement 3.28). */
    private const NOMOR_TIKET_TYPO = 'ZZZZ-K3PR-999-TYPO';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test preservasi ini WAJIB tersambung ke database nyata db_newtiket agar baseline yang diobservasi bermakna (bukan DB tests/mock).'
        );

        // Idempoten: bersihkan sisa data uji dari run sebelumnya SEBELUM
        // test berjalan (mengikuti konvensi T1CsrfExplorationTest.php).
        $this->bersihkanData();
    }

    protected function tearDown(): void
    {
        $this->bersihkanData();

        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        $this->koneksi->table('d_ticketing')->where('ticketEmail', self::EMAIL_AMAN)->delete();
    }

    /**
     * Menambahkan field token CSRF (nama+nilai terkini) ke payload POST.
     *
     * Task 18.1 mengaktifkan filter global `csrf` (app/Config/Filters.php),
     * sehingga SELURUH dispatch POST pada test ini kini memerlukan token
     * CSRF valid agar tidak ditolak `SecurityException` — persis mekanisme
     * yang divalidasi task 18.2 pada produksi (csrf_field()/csrf_token()/
     * csrf_hash(), fungsi bawaan CI4 Common.php, service('security') yang
     * sama dipakai request nyata). Dipanggil ULANG (bukan di-cache) sebelum
     * SETIAP dispatch karena `Config\Security::$regenerate = true` merotasi
     * hash setelah setiap verifikasi sukses — token yang di-cache dari
     * dispatch pertama TIDAK valid lagi untuk dispatch kedua pada test yang
     * melakukan lebih dari satu POST (lihat
     * testPenggunaSahCekTiketSendiriFrekuensiWajarTidakTerblokirDanTypoSesekaliTidakLangsungDiblokir()).
     *
     * CATATAN TEKNIS — mengapa `Services::injectMock('request', ...)` +
     * `Services::resetSingle('security')` diperlukan SEBELUM memanggil
     * `csrf_token()`/`csrf_hash()`: `Security::__construct()` menyimpan
     * `service('request')` ke properti `private readonly IncomingRequest
     * $request` (vendor/codeigniter4/framework/system/Security/Security.php).
     * Default request PHPUnit CLI adalah `CLIRequest` (bukan
     * `IncomingRequest`) — bila `security` singleton lahir SEBELUM
     * `FeatureTestTrait::call()` menyuntikkan `IncomingRequest` yang
     * sebenarnya (yang terjadi tepat SETELAH helper ini dipanggil, di
     * dalam `$this->post()`), konstruksi akan gagal dengan
     * `TypeError: Cannot assign CodeIgniter\HTTP\CLIRequest to property
     * ...Security::$request of type CodeIgniter\HTTP\IncomingRequest`
     * (diverifikasi empiris — error ini muncul persis saat helper ini
     * pertama kali dipanggil tanpa baris injeksi berikut). Menyuntikkan
     * `IncomingRequest` nyata (via `setupRequest()`, method PROTECTED
     * bawaan `FeatureTestTrait` yang dipakai `call()` sendiri secara
     * internal) lalu me-reset `security` MEMASTIKAN singleton lahir
     * dengan tipe request yang benar sebelum `csrf_token()`/`csrf_hash()`
     * membacanya.
     *
     * @param array<string, string> $field
     *
     * @return array<string, string>
     */
    private function denganTokenCsrf(array $field): array
    {
        Services::injectMock('request', $this->setupRequest('POST', 'login'));
        Services::resetSingle('security');

        return array_merge($field, [csrf_token() => csrf_hash()]);
    }

    /**
     * Field POST lengkap untuk `login/savetiket` — persis struktur
     * `T1CsrfExplorationTest::fieldFormLengkapTanpaCsrf()` (SATU-SATUNYA
     * perbedaan: ticketEmail fixture independen file ini).
     *
     * @return array<string, string>
     */
    private function fieldFormSavetiketLengkap(): array
    {
        return [
            'captcha'          => self::CAPTCHA_DIKETAHUI,
            'ticketCategories' => '1',
            'ticketEmail'      => self::EMAIL_AMAN,
            'ticketNoHp'       => '081234567890',
            'ticketSubject'    => 'Uji Preservasi Klaster 3',
            'ticketMessage'    => 'Pesan uji preservasi baseline Klaster 3 — request AJAX sah dengan struktur existing.',
            'ticketName'       => 'Pemohon Uji Klaster3Preservasi',
            'ticketPriority'   => '1',
        ];
    }

    /**
     * Property 2 (Preservation), observasi 1 — Requirement 3.13, 3.14:
     * request AJAX SAH ke `login/savetiket` (struktur existing, captcha
     * benar, header AJAX — TANPA token apa pun, karena belum ada
     * mekanisme CSRF pada kode belum diperbaiki) SHALL diproses PENUH
     * dan respons JSON SHALL mengandung field `status`, `message`,
     * `new_captcha` (dikonfirmasi langsung dari source `Login::
     * savetiket()` jalur sukses: `return $this->response->setJSON([
     * 'status' => 'success', 'message' => ..., 'new_captcha' => ...])`).
     *
     * Deteksi field dilakukan via substring pada body mentah (BUKAN
     * `json_decode()`) — diverifikasi ULANG secara empiris untuk
     * request INI secara spesifik (bukan asumsi berlaku otomatis dari
     * T1/T2): body yang dikembalikan `$hasil->getBody()` pada dispatch
     * ini SAMA-SAMA dibungkus shell `<!DOCTYPE html>...<body>{json}
     * </body></html>` seperti temuan T1/T2 — `json_decode()`
     * mengembalikan `null` di sini juga. Nama field (`"status"`,
     * `"message"`, `"new_captcha"`) tetap muncul VERBATIM sebagai
     * substring literal di dalam body (bagian dari JSON asli yang
     * terbungkus), sehingga pencarian substring nama field TETAP
     * reliable sebagai bukti struktur respons dipertahankan.
     */
    public function testSavetiketResponsJsonMempertahankanStrukturFieldStatusMessageNewCaptcha(): void
    {
        $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('login/savetiket', $this->denganTokenCsrf($this->fieldFormSavetiketLengkap()));

        $hasil->assertStatus(200);

        $bodiMentah = (string) $hasil->getBody();

        // Sanity empiris (bukan asumsi): konfirmasi ulang json_decode()
        // memang mengembalikan null pada body ini (kuirk pembungkus),
        // agar dokumentasi di atas bukan klaim tanpa verifikasi.
        self::assertNull(
            json_decode($bodiMentah, true),
            'Prasyarat/sanity: body respons dispatch ini SEHARUSNYA (mengikuti temuan T1/T2 sebelumnya) dibungkus shell non-JSON sehingga json_decode() mengembalikan null — jika assertion ini gagal (body sudah JSON murni), pendekatan deteksi substring pada method ini tetap valid namun catatan docblock perlu diperbarui.'
        );

        foreach (['"status"', '"message"', '"new_captcha"'] as $namaField) {
            self::assertStringContainsString(
                $namaField,
                $bodiMentah,
                sprintf(
                    'PRESERVATION BASELINE Klaster 3 (Requirement 3.13, 3.14): field %s SHALL tetap muncul pada struktur respons JSON login/savetiket untuk request AJAX sah — baseline ini WAJIB dipertahankan identik pasca-fix T1 (task 18, aktivasi CSRF), karena fix T1 SHALL TIDAK mengubah kontrak response API existing (bugfix.md 3.14: "struktur tersebut SHALL CONTINUE TO sama persis"). Body mentah: %s',
                    $namaField,
                    $bodiMentah
                )
            );
        }

        self::assertStringContainsString(
            '"success"',
            $bodiMentah,
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.13): request AJAX sah (captcha benar, field lengkap, header AJAX) SHALL menghasilkan status "success" pada kode belum diperbaiki — baseline ini WAJIB dipertahankan pasca-fix T1 selama token CSRF valid disertakan (bugfix.md 3.13).'
        );

        // Counterexample-preservasi konkret: baris d_ticketing SUNGGUHAN
        // ter-insert untuk request AJAX sah ini (bukti request diproses
        // tuntas, bukan sekadar status HTTP 200 kosong).
        $tiketTercipta = $this->koneksi->table('d_ticketing')
            ->where('ticketEmail', self::EMAIL_AMAN)
            ->get()->getRowArray();

        self::assertNotNull(
            $tiketTercipta,
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.13): request AJAX sah ke login/savetiket SHALL menghasilkan baris d_ticketing ter-insert pada kode belum diperbaiki — baseline ini WAJIB dipertahankan pasca-fix T1 untuk request yang menyertakan token CSRF valid.'
        );
    }

    /**
     * Property 2 (Preservation), observasi 1 (lanjutan) — Requirement
     * 3.13, 3.14, 3.27: request `login/cektiket` SAH (nomor tiket
     * benar-benar ada) SHALL diproses PENUH dan respons JSON SHALL
     * mengandung field `redirect_url` pada jalur sukses (dikonfirmasi
     * langsung dari source `Login::cektiket()`:
     * `return $this->response->setJSON(['status' => 'success',
     * 'message' => ..., 'redirect_url' => ...])`).
     *
     * `login/cektiket` HANYA melakukan SELECT read-only (byId()) — TIDAK
     * ADA insert/update/delete (dikonfirmasi ulang dari source, sama
     * seperti dikonfirmasi M3TicketEnumerationExplorationTest.php) —
     * TIDAK memerlukan cleanup apa pun.
     */
    public function testCektiketResponsJsonMempertahankanFieldRedirectUrlUntukTiketValid(): void
    {
        $baris = $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::NOMOR_TIKET_VALID)->get()->getFirstRow('array');

        self::assertNotFalse(
            $baris,
            sprintf('Prasyarat test: nomor tiket %s SHALL benar-benar ada di d_ticketing agar baseline "ditemukan" bermakna.', self::NOMOR_TIKET_VALID)
        );

        $hasil = $this->post('login/cektiket', $this->denganTokenCsrf([
            'nomorTiket' => self::NOMOR_TIKET_VALID,
        ]));

        $hasil->assertStatus(200);

        $bodiMentah = (string) $hasil->getBody();

        self::assertStringContainsString(
            '"redirect_url"',
            $bodiMentah,
            sprintf(
                'PRESERVATION BASELINE Klaster 3 (Requirement 3.13, 3.14, 3.27): field "redirect_url" SHALL tetap muncul pada struktur respons JSON login/cektiket untuk nomor tiket VALID (%s) — baseline ini WAJIB dipertahankan pasca-fix M3 (task 19, rate limiting) selama request berada di bawah ambang batas wajar. Body mentah: %s',
                self::NOMOR_TIKET_VALID,
                $bodiMentah
            )
        );

        self::assertStringContainsString(
            'Nomor tiket ditemukan',
            $bodiMentah,
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.27): pesan "ditemukan" SHALL tetap muncul untuk nomor tiket valid pada frekuensi wajar (satu request) — baseline ini WAJIB dipertahankan pasca-fix M3.'
        );
    }

    /**
     * Property 2 (Preservation), observasi 2 — Requirement 3.19, 3.20:
     * captcha BENAR sesuai session (TANPA cookie apa pun diset) SHALL
     * lolos (`true`); captcha SALAH SHALL gagal (`false`).
     *
     * Reuse LANGSUNG pola `T3CookieCaptchaBypassExplorationTest::
     * testBaselineTanpaCookieFallbackKeSessionTetapBenar()` — pemanggilan
     * `eult_captcha_check()` LANGSUNG (fungsi murni, tidak menyentuh
     * database/HTTP) TANPA menyuntikkan cookie apa pun (berbeda dari
     * skenario bug T3 itu sendiri yang menyuntikkan cookie BERBEDA dari
     * session) — cepat, tidak memerlukan setup IncomingRequest/superglobals
     * karena tidak ada cookie yang perlu dibaca sama sekali (get_cookie()
     * pada CLIRequest default proses PHPUnit mengembalikan null/falsy,
     * yang justru SESUAI dengan skenario "tanpa cookie" yang diinginkan
     * di sini — tidak perlu injeksi IncomingRequest seperti T3).
     */
    public function testCaptchaBenarSesuaiSessionLolosTanpaCookieDanCaptchaSalahGagal(): void
    {
        $nilaiSessionSebenarnya = 'K3PR';

        session()->set('captcha', $nilaiSessionSebenarnya);

        self::assertTrue(
            eult_captcha_check($nilaiSessionSebenarnya),
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.19): captcha BENAR sesuai session (tanpa cookie apa pun diset) SHALL lolos (true) pada kode belum diperbaiki — baseline ini WAJIB dipertahankan pasca-fix T3 (task 20, hapus fallback cookie) karena validasi murni session SHALL tetap berlaku sama untuk kasus tanpa cookie.'
        );

        // Baseline case-insensitivity (strtoupper() kedua sisi) — SHALL
        // tetap berlaku pasca-fix T3.
        self::assertTrue(
            eult_captcha_check(strtolower($nilaiSessionSebenarnya)),
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.19): perbandingan captcha SHALL tetap case-insensitive (strtoupper() kedua sisi) — baseline ini WAJIB dipertahankan pasca-fix T3.'
        );

        self::assertFalse(
            eult_captcha_check('SALAHTOTALK3'),
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.20): captcha SALAH (tidak cocok session, tanpa cookie apa pun) SHALL gagal (false) pada kode belum diperbaiki — baseline ini WAJIB dipertahankan pasca-fix T3.'
        );
    }

    /**
     * Property 2 (Preservation), observasi 3 — Requirement 3.17: request
     * `GET login/refresh_captcha` (route: `app/Config/Routes.php`,
     * DIVERIFIKASI ULANG secara empiris pada test ini — bukan diasumsikan
     * dari teks task "refresh-captcha" apa adanya. Baris rute berada DI
     * DALAM `$routes->group('login', ...)`:
     * `$routes->get('refresh_captcha', 'Login::refreshCaptcha');` —
     * sehingga path LENGKAP adalah `login/refresh_captcha` (BUKAN
     * `refresh_captcha` bare tanpa prefix grup — percobaan pertama
     * dengan path bare menghasilkan `PageNotFoundException`, sesuai
     * hasil dispatch nyata yang mengonfirmasi struktur grup ini) — GET,
     * bukan POST, dikonfirmasi via pembacaan langsung file routes)
     * SHALL menghasilkan captcha BARU.
     *
     * **Diperbarui pasca-fix T2 (task 19.1, captcha sebagai gambar,
     * Requirement 2.27/2.28)**: field respons berubah dari `captcha`
     * (string plaintext) menjadi `captcha_image_url` (URL gambar) —
     * PERSIS perubahan yang diantisipasi docblock ORIGINAL test ini
     * ("hanya FORMAT nilainya yang berubah... dari sebelumnya"; lihat
     * juga bugfix.md Requirement 2.28: "nilai captcha itu sendiri...
     * SHALL TIDAK pernah dikirim ke klien dalam bentuk plain text apa
     * pun... yang harus berubah menjadi URL/endpoint gambar"). Baseline
     * FUNGSIONAL (endpoint menghasilkan sesuatu yang valid & non-kosong
     * setiap kali dipanggil) dipertahankan — hanya nama
     * field+bentuk-nilai yang disesuaikan mengikuti kontrak baru
     * `Login::refreshCaptcha()`.
     */
    public function testRefreshCaptchaMenghasilkanCaptchaBaru(): void
    {
        $hasil = $this->get('login/refresh_captcha');

        $hasil->assertStatus(200);

        $bodiMentah = (string) $hasil->getBody();

        self::assertStringContainsString(
            '"captcha_image_url"',
            $bodiMentah,
            sprintf(
                'PRESERVATION BASELINE Klaster 3 (Requirement 3.17, diperbarui pasca-fix T2 task 19.1): field "captcha_image_url" SHALL muncul pada respons GET login/refresh_captcha (menggantikan field "captcha" string plaintext sesuai Requirement 2.28 — captcha TIDAK PERNAH lagi dikirim plaintext ke klien). Body mentah: %s',
                $bodiMentah
            )
        );

        // Nilai URL gambar yang dihasilkan SHALL non-kosong DAN mengarah
        // ke endpoint captcha_image (baseline fungsional minimal:
        // mekanisme generate+URL benar-benar berjalan).
        $cocok = preg_match('/"captcha_image_url"\s*:\s*"([^"]*)"/', $bodiMentah, $tangkapan);

        self::assertSame(1, $cocok, 'Prasyarat: field "captcha_image_url" SHALL berbentuk pasangan key-value string pada JSON respons agar nilainya dapat diperiksa non-kosong.');

        $nilaiUrl = $tangkapan[1] ?? '';
        self::assertNotSame('', $nilaiUrl, 'PRESERVATION BASELINE Klaster 3 (Requirement 3.17): nilai captcha_image_url yang dihasilkan refresh_captcha SHALL non-kosong.');
        self::assertStringContainsString('login/captcha_image', $nilaiUrl, 'PRESERVATION BASELINE Klaster 3 (Requirement 2.27): captcha_image_url SHALL mengarah ke endpoint gambar captcha (Login::captchaImage()).');
    }

    /**
     * Property 2 (Preservation), observasi 4 — Requirement 3.16, 3.18,
     * 3.27, 3.28: pengguna SAH mengecek tiketnya sendiri dalam
     * FREKUENSI WAJAR (satu request) SHALL "ditemukan" + redirect_url
     * benar; salah ketik SESEKALI (satu request typo) SHALL "tidak
     * ditemukan" TANPA langsung terblokir pada percobaan
     * pertama/kedua.
     *
     * Mengikuti instruksi task 17 poin 4: TIDAK mengulang loop
     * 15-request milik T2/M3 — SATU request sukses + SATU request typo
     * sudah cukup membuktikan baseline "frekuensi wajar tidak
     * terblokir" (absennya rate-limit sudah dibuktikan gagal oleh
     * M3TicketEnumerationExplorationTest.php pada task 16; observasi
     * di sini murni memastikan pengalaman pengguna WAJAR — bukan
     * penyerang — tidak terganggu, sebagai baseline terpisah yang tetap
     * relevan diverifikasi ulang pasca-fix M3 pada ambang batas yang
     * longgar).
     */
    public function testPenggunaSahCekTiketSendiriFrekuensiWajarTidakTerblokirDanTypoSesekaliTidakLangsungDiblokir(): void
    {
        // (a) Request SUKSES — pengguna sah cek tiketnya sendiri.
        $hasilSukses = $this->post('login/cektiket', $this->denganTokenCsrf([
            'nomorTiket' => self::NOMOR_TIKET_VALID,
        ]));

        $hasilSukses->assertStatus(200);
        $bodiSukses = (string) $hasilSukses->getBody();

        self::assertStringContainsString(
            'Nomor tiket ditemukan',
            $bodiSukses,
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.16, 3.27): pengguna sah cek tiketnya sendiri dalam frekuensi wajar SHALL tetap mendapat pesan "ditemukan" tanpa hambatan — baseline ini WAJIB dipertahankan pasca-fix M3 (rate limit hanya menghalangi frekuensi TIDAK wajar).'
        );

        // (b) Request TYPO SESEKALI — SATU kesalahan ketik, BUKAN pola
        // serangan berulang. SHALL tetap diproses normal ("tidak
        // ditemukan"), TIDAK langsung diblokir pada percobaan pertama.
        // Token CSRF diambil ULANG (bukan dari sisa variabel dispatch
        // (a)) karena verify() sukses pada (a) sudah merotasi hash.
        $hasilTypo = $this->post('login/cektiket', $this->denganTokenCsrf([
            'nomorTiket' => self::NOMOR_TIKET_TYPO,
        ]));

        $hasilTypo->assertStatus(200);
        $bodiTypo = (string) $hasilTypo->getBody();

        self::assertStringContainsString(
            'Nomor tiket tidak ditemukan.',
            $bodiTypo,
            sprintf(
                'PRESERVATION BASELINE Klaster 3 (Requirement 3.18, 3.28): pengguna sah salah mengetik nomor tiket SESEKALI (nomor tebakan: %s) SHALL tetap mendapat respons normal "tidak ditemukan" (status HTTP 200, bukan diblokir/429) pada percobaan pertama — baseline ini WAJIB dipertahankan pasca-fix M3 (ambang batas rate limit SHALL longgar untuk pengguna wajar).',
                self::NOMOR_TIKET_TYPO
            )
        );

        self::assertNotSame(
            429,
            $hasilTypo->response()->getStatusCode(),
            'PRESERVATION BASELINE Klaster 3 (Requirement 3.18, 3.28): SATU kesalahan ketik nomor tiket SHALL TIDAK langsung memicu status 429 (rate-limit) — baseline ini WAJIB dipertahankan pasca-fix M3.'
        );
    }

    /**
     * Property 2 (Preservation), observasi 5 — Requirement 3.23, 3.24:
     * dokumentasi/prep whitelist CSP (task 21) — render `layouts/login`
     * (view produksi yang SAMA, reuse pendekatan
     * M1SecurityHeadersExplorationTest.php/
     * T1CsrfExplorationTest::testFormLoginTidakMengandungPemanggilan...())
     * dan pastikan halaman TETAP merender serta mengandung sumber daya
     * eksternal/statis yang dikenal (font Google, asset bundle
     * base_url()-prefixed) — dikonfirmasi ADA pada pembacaan langsung
     * app/Views/layouts/login.php sebelum test ini ditulis.
     *
     * Ini BUKAN kontrak perilaku ketat (task 17 poin 5: "tidak perlu
     * assert apa pun strict di sini beyond halaman tetap merender dan
     * mengandung aset yang dikenal") — murni dokumentasi baseline daftar
     * sumber daya untuk dipakai sebagai whitelist CSP pada task 21.
     */
    public function testHalamanLoginTetapMerenderDanMengandungAsetStatisYangDikenalUntukWhitelistCsp(): void
    {
        // Diperbarui pasca-fix T2 (task 19.1): view layouts/login kini
        // menerima 'captcha_image_url' (bukan 'captcha' string
        // plaintext) — lihat Login::index()/Requirement 2.27, 2.28.
        $htmlTerender = view('layouts/login', [
            'captcha_image_url' => base_url('login/captcha_image') . '?t=1',
            'r_priority'         => [],
            'datas'              => false,
        ]);

        self::assertNotSame('', $htmlTerender, 'Prasyarat: render layouts/login SHALL menghasilkan HTML non-kosong.');

        // Daftar aset yang DIKENAL (dikonfirmasi ada pada pembacaan
        // langsung source app/Views/layouts/login.php) — dokumentasi
        // baseline untuk whitelist CSP task 21, BUKAN daftar lengkap
        // seluruh aset (di luar scope task 17).
        $asetYangDiharapkanAda = [
            'https://fonts.googleapis.com' => 'font eksternal Google Fonts (link stylesheet Poppins)',
            'assets/plugins/global/plugins.bundle.css' => 'bundle CSS plugin global (base_url()-prefixed)',
            'assets/css/style.bundle.css' => 'bundle CSS style utama (base_url()-prefixed)',
        ];

        foreach ($asetYangDiharapkanAda as $sumberDaya => $keterangan) {
            self::assertStringContainsString(
                $sumberDaya,
                $htmlTerender,
                sprintf(
                    'PRESERVATION/DOKUMENTASI BASELINE Klaster 3 (Requirement 3.23, 3.24): HTML hasil render layouts/login SHALL mengandung referensi ke "%s" (%s) — sumber daya ini WAJIB masuk whitelist CSP pada fix M1 (task 21) agar halaman tetap dapat dimuat browser tanpa diblokir CSP pasca-aktivasi (bugfix.md 3.23).',
                    $sumberDaya,
                    $keterangan
                )
            );
        }
    }
}
