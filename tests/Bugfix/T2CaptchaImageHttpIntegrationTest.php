<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 19.1: Test verifikasi fix T2 (captcha sebagai gambar) —
 * "Implementasikan captcha sebagai gambar" (bugfix.md Requirement
 * 2.27, 2.28; tasks.md task 19.1).
 *
 * Membuktikan bahwa:
 * (a) endpoint `login/captcha_image` mengembalikan `Content-Type:
 *     image/png` DENGAN body yang benar-benar gambar PNG valid (magic
 *     bytes `\x89PNG` di awal body — bukan sekadar header yang benar);
 * (b) HTML hasil render `Login::index()` TIDAK PERNAH mengandung teks
 *     captcha plaintext di mana pun (nilai captcha session tidak lagi
 *     bisa dibaca langsung dari DOM/view-source);
 * (c) respons JSON `Login::refreshCaptcha()` TIDAK LAGI mengandung
 *     field `captcha` berisi string plaintext — field tersebut
 *     digantikan `captcha_image_url` berisi URL endpoint gambar.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA (a) MEMAKAI cURL-ke-SERVER-LIVE, BUKAN FeatureTestTrait —
 * TEMUAN EMPIRIS BARU (investigasi khusus task 19.1, belum pernah
 * didokumentasikan test lain sebelumnya):
 * ═══════════════════════════════════════════════════════════════════
 * Dikonfirmasi secara empiris (investigasi langsung sebelum test ini
 * ditulis) bahwa wrapper shell `<!DOCTYPE html>...<html><body>{...}
 * </body></html>` yang ditemukan T1/T2/T4 pada body RESPONS JSON di
 * bawah dispatch `FeatureTestTrait` JUGA berlaku untuk body BINARY
 * (bukan hanya JSON) — `$hasil->getBody()` pada
 * `$this->get('login/captcha_image')` mengembalikan body yang
 * DIBUNGKUS wrapper HTML yang sama persis, MESKI header
 * `Content-Type` yang dikembalikan tetap benar (`image/png`) dan
 * status HTTP tetap 200. Byte pertama body ter-wrap tersebut adalah
 * `<` (0x3C, karakter pembuka `<!DOCTYPE`), BUKAN `\x89` (magic byte
 * pembuka PNG asli) — sehingga assertion magic-bytes PNG TIDAK DAPAT
 * diandalkan di bawah dispatch in-process ini. Oleh karena itu
 * pengujian (a) — SATU-SATUNYA bagian yang butuh membaca BODY BINARY
 * mentah — memakai cURL terhadap server dev live (pola identik
 * `K1SqlInjectionHttpIntegrationTest::postKeServerLive()`, diadaptasi
 * untuk GET tanpa field POST). Header `Content-Type` yang dikembalikan
 * `FeatureTestTrait` TETAP RELIABLE (tidak terpengaruh wrapper —
 * dikonfirmasi test lain), namun test ini tetap memverifikasi
 * Content-Type via cURL juga sekaligus (byte-level end-to-end, bukan
 * gabungan dua sumber terpisah).
 *
 * Pengujian (b) dan (c) TIDAK menyentuh body binary — keduanya AMAN
 * memakai `FeatureTestTrait` in-process:
 * - (b) merender langsung view produksi via helper `view()` (pola
 *   sama `T1CsrfExplorationTest::testFormLoginTidakMengandungPemanggilan
 *   CsrfFieldApaPun()`/`T2CaptchaRateLimitExplorationTest::
 *   testCaptchaTerbacaLangsungDariDomTanpaOcr()`), bukan dispatch HTTP
 *   sama sekali — tidak ada body response yang dibaca, sehingga
 *   wrapper tidak relevan.
 * - (c) membaca body JSON `refreshCaptcha()` via SUBSTRING pada body
 *   mentah (BUKAN `json_decode()`, mengikuti pola
 *   `Klaster3PreservationTest::testRefreshCaptchaMenghasilkanCaptchaBaru()`)
 *   — nama field (`"captcha_image_url"`) tetap muncul verbatim sebagai
 *   substring literal pada body yang ter-wrap, dan ABSENSI field
 *   `"captcha"` (tanpa suffix `_image_url`) tetap dapat diverifikasi
 *   via regex yang membedakan kedua nama field.
 *
 * Requirements: 2.27, 2.28 (bugfix.md — Expected Behavior T2)
 *
 * @internal
 */
final class T2CaptchaImageHttpIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Base URL server dev live (app.baseURL, .env) — pola identik K1SqlInjectionHttpIntegrationTest. */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    /** Magic bytes pembuka file PNG (RFC 2083 §3.1) — 8 byte signature standar. */
    private const PNG_MAGIC_BYTES = "\x89PNG\r\n\x1a\n";

    /**
     * Property (a): `GET login/captcha_image` SHALL mengembalikan
     * `Content-Type: image/png` DENGAN body yang benar-benar diawali
     * magic bytes PNG standar — bukan sekadar header yang benar tanpa
     * konten gambar valid di baliknya.
     */
    public function testEndpointCaptchaImageMengembalikanContentTypePngDenganBodyPngValid(): void
    {
        $hasil = $this->getDariServerLive('login/captcha_image');

        self::assertSame(200, $hasil['status'], 'Endpoint login/captcha_image SHALL mengembalikan HTTP 200.');
        self::assertStringContainsString(
            'image/png',
            $hasil['contentType'],
            sprintf('Requirement 2.27: header Content-Type SHALL "image/png", aktual: "%s".', $hasil['contentType'])
        );

        self::assertStringStartsWith(
            self::PNG_MAGIC_BYTES,
            $hasil['body'],
            sprintf(
                'Requirement 2.27: body respons login/captcha_image SHALL benar-benar berupa gambar PNG valid (diawali magic bytes standar %s), bukan sekadar header Content-Type yang benar tanpa konten gambar. 20 byte pertama aktual (hex): %s',
                bin2hex(self::PNG_MAGIC_BYTES),
                bin2hex(substr($hasil['body'], 0, 20))
            )
        );

        self::assertGreaterThan(
            100,
            strlen($hasil['body']),
            'Body PNG SHALL memiliki ukuran wajar untuk gambar captcha (bukan file PNG header-only/kosong).'
        );
    }

    /**
     * Property (b): HTML hasil render `Login::index()` (halaman login
     * produksi sungguhan) TIDAK PERNAH mengandung teks captcha
     * plaintext di mana pun pada output-nya — nilai captcha session
     * SHALL TIDAK dapat dibaca langsung dari DOM/view-source dengan
     * cara apa pun (Requirement 2.28: "nilai captcha session TIDAK
     * PERNAH terkirim plaintext ke klien").
     *
     * Berbeda dari T2CaptchaRateLimitExplorationTest (yang mem-pass
     * nilai captcha diketahui via parameter view langsung), test ini
     * memanggil `Login::index()` SUNGGUHAN via dispatch HTTP —
     * memverifikasi nilai captcha SESUNGGUHNYA yang di-generate+
     * disimpan ke session oleh controller produksi tidak pernah
     * bocor ke HTML, bukan hanya memverifikasi struktur template.
     */
    public function testHalamanLoginTidakMengandungTeksCaptchaPlaintextDiManaPun(): void
    {
        $hasil = $this->get('/');

        $hasil->assertOK();

        $html = (string) $hasil->getBody();

        $nilaiCaptchaSesi = session()->get('captcha');

        self::assertIsString(
            $nilaiCaptchaSesi,
            'Prasyarat: Login::index() SHALL menyimpan nilai captcha ke session (perilaku eult_captcha_generate() TIDAK berubah, Requirement 2.28).'
        );
        self::assertNotSame('', $nilaiCaptchaSesi);

        // *** ASSERTION UTAMA (Requirement 2.28) ***
        self::assertStringNotContainsString(
            $nilaiCaptchaSesi,
            $html,
            sprintf(
                'BUG CONDITION T2 SEHARUSNYA TERPERBAIKI (Requirement 2.28) — nilai captcha session "%s" TIDAK BOLEH muncul di mana pun pada HTML hasil render Login::index(). Jika assertion ini gagal, berarti nilai captcha plaintext masih bocor ke response HTML (regresi dari fix task 19.1).',
                $nilaiCaptchaSesi
            )
        );

        // Defense-in-depth: pastikan juga elemen <span class="captcha-display">
        // (bentuk lama, sudah diganti <img>) tidak lagi ada di HTML.
        self::assertDoesNotMatchRegularExpression(
            '/<span[^>]*class="captcha-display"[^>]*>/',
            $html,
            'Requirement 2.27: elemen <span class="captcha-display"> (rendering teks plaintext lama) SHALL tidak lagi muncul — digantikan <img class="captcha-display">.'
        );

        self::assertMatchesRegularExpression(
            '/<img[^>]*class="captcha-display"[^>]*>/',
            $html,
            'Requirement 2.27: elemen <img class="captcha-display"> (rendering gambar) SHALL muncul sebagai pengganti <span> plaintext lama.'
        );
    }

    /**
     * Property (c): respons JSON `GET login/refresh_captcha`
     * (`Login::refreshCaptcha()`) TIDAK LAGI mengandung field
     * `captcha` berisi string plaintext — field tersebut digantikan
     * `captcha_image_url` berisi URL endpoint gambar (Requirement
     * 2.28: "response AJAX new_captcha, yang harus berubah menjadi
     * URL/endpoint gambar, bukan string captcha polos seperti
     * sekarang" — refresh_captcha mengikuti kontrak yang sama).
     *
     * Deteksi via regex pada body mentah (BUKAN json_decode(), body
     * ter-wrap shell HTML pada dispatch FeatureTestTrait — pola sama
     * Klaster3PreservationTest/M3TicketEnumerationExplorationTest).
     * Regex membedakan `"captcha"` (field lama, TIDAK BOLEH ada)
     * dari `"captcha_image_url"` (field baru, HARUS ada) — memastikan
     * assertion tidak keliru match substring `"captcha_image_url"`
     * sebagai kecocokan `"captcha"`.
     */
    public function testResponsRefreshCaptchaTidakLagiMengandungFieldCaptchaPlaintext(): void
    {
        $hasil = $this->get('login/refresh_captcha');

        $hasil->assertStatus(200);

        $bodiMentah = (string) $hasil->getBody();

        // *** ASSERTION UTAMA (Requirement 2.28) ***
        self::assertDoesNotMatchRegularExpression(
            '/"captcha"\s*:/',
            $bodiMentah,
            sprintf(
                'BUG CONDITION T2 SEHARUSNYA TERPERBAIKI (Requirement 2.28) — respons JSON login/refresh_captcha TIDAK BOLEH lagi mengandung field "captcha" berisi string plaintext. Body mentah: %s',
                $bodiMentah
            )
        );

        self::assertMatchesRegularExpression(
            '/"captcha_image_url"\s*:\s*"[^"]+"/',
            $bodiMentah,
            sprintf(
                'Requirement 2.28: respons JSON login/refresh_captcha SHALL mengandung field "captcha_image_url" berisi URL non-kosong sebagai pengganti field "captcha" plaintext. Body mentah: %s',
                $bodiMentah
            )
        );

        // Nilai captcha session SHALL tetap ada (mekanisme session
        // TIDAK berubah, Requirement 2.28), namun TIDAK PERNAH muncul
        // verbatim pada body respons ini.
        $nilaiCaptchaSesiSetelahRefresh = session()->get('captcha');
        self::assertIsString($nilaiCaptchaSesiSetelahRefresh);
        self::assertStringNotContainsString(
            $nilaiCaptchaSesiSetelahRefresh,
            $bodiMentah,
            sprintf(
                'Requirement 2.28: nilai captcha session "%s" TIDAK BOLEH muncul verbatim pada body respons login/refresh_captcha.',
                $nilaiCaptchaSesiSetelahRefresh
            )
        );
    }

    /**
     * GET sederhana terhadap server dev live via cURL — pola diadaptasi
     * dari `K1SqlInjectionHttpIntegrationTest::postKeServerLive()` untuk
     * method GET tanpa field POST (dibutuhkan HANYA untuk membaca body
     * BINARY mentah tanpa wrapper — lihat docblock kelas).
     *
     * @return array{status: int, contentType: string, body: string}
     */
    private function getDariServerLive(string $path): array
    {
        $ch = curl_init(self::BASE_URL_LIVE . $path);

        // Catatan CAINFO identik K1SqlInjectionHttpIntegrationTest —
        // lingkungan dev ini butuh bundle CA sistem eksplisit untuk
        // verifikasi SSL terhadap sertifikat dev lokal.
        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $opsi = [
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);

        $body        = curl_exec($ch);
        $errno       = curl_errno($ch);
        $error       = curl_error($ch);
        $status      = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server dev live tanpa error transport (errno=%d: %s). Pastikan server live reachable sebelum menjalankan test ini.', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'contentType' => $contentType, 'body' => $body];
    }
}
