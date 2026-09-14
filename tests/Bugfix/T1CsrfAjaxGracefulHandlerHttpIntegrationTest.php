<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 18.3 (Klaster 3 / T1): Test INTEGRASI HTTP untuk graceful handler
 * kegagalan CSRF pada request AJAX (bugfix.md 1.17, 2.24; Requirement
 * 2.24).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA cURL-ke-server-live, BUKAN `CodeIgniter\Test\
 * FeatureTestTrait` (in-process):
 * ═══════════════════════════════════════════════════════════════════
 * `FeatureTestTrait::call()` men-dispatch request IN-PROCESS di dalam
 * proses PHPUnit yang sama — TIDAK ADA titik di mana
 * `set_exception_handler()`/`Config\Exceptions::handler()`
 * (mekanisme YANG DIUJI test ini) benar-benar terpicu, karena
 * exception yang lolos filter `csrf` (`CodeIgniter\Security\
 * Exceptions\SecurityException`) akan ditangkap LANGSUNG oleh PHPUnit
 * sebagai kegagalan test (PHPUnit sendiri memasang exception
 * handler-nya sendiri di dalam proses yang sama), BUKAN oleh
 * `CodeIgniter\Debug\Exceptions::exceptionHandler()` yang memanggil
 * `Config\Exceptions::handler($statusCode, $exception)` — SATU-SATUNYA
 * titik ekstensi yang dipakai `AjaxSecurityExceptionHandler`
 * (app/Exceptions/AjaxSecurityExceptionHandler.php, dikonfirmasi dari
 * pembacaan langsung vendor/codeigniter4/framework/system/Debug/
 * Exceptions.php — `set_exception_handler($this->exceptionHandler(...))`
 * hanya berlaku untuk exception yang benar-benar lolos tidak
 * tertangkap sampai ke top-level runtime PHP, TIDAK PERNAH terjadi di
 * dalam proses PHPUnit tunggal yang men-dispatch berulang kali secara
 * in-process). Dispatch HTTP NYATA (cURL, koneksi TCP terpisah, proses
 * server PHP terpisah dari proses test) adalah SATU-SATUNYA cara
 * memicu jalur `set_exception_handler` yang sesungguhnya — mengikuti
 * presedan teknis PERSIS `K1SqlInjectionHttpIntegrationTest.php`/
 * `T4RatingHttpIntegrationTest.php` (meski alasan strukturalnya
 * berbeda: di sana karena `exit;` sungguhan pada
 * `eult_message_kirim()`, di sini karena mekanisme exception handler
 * global yang hanya aktif di proses server sungguhan).
 *
 * Base URL, opsi CURLOPT (termasuk CAINFO bundle sistem untuk
 * sertifikat dev lokal), dan pola dasar mengikuti
 * K1SqlInjectionHttpIntegrationTest.php/T4RatingHttpIntegrationTest.php.
 * Test ini TIDAK memerlukan cleanup database — endpoint yang dipakai
 * (`cektiket/rating/{kunci-tidak-valid}`, `login/savetiket` tanpa
 * cookie/token CSRF) berhenti pada validasi CSRF SEBELUM controller
 * body apa pun dieksekusi (filter `csrf` berjalan SEBELUM routing ke
 * method controller), sehingga TIDAK ADA side-effect
 * insert/update/email apa pun yang perlu dibersihkan.
 *
 * ═══════════════════════════════════════════════════════════════════
 * SKENARIO YANG DIVERIFIKASI:
 * ═══════════════════════════════════════════════════════════════════
 * (a) AJAX (header `X-Requested-With: XMLHttpRequest`) TANPA
 *     cookie/token CSRF apa pun → SHALL 403 DAN body SHALL JSON valid
 *     `{"status": "danger", "message": "..."}` (BUKAN body kosong `""`
 *     yang merupakan behavior default CI4 SEBELUM fix ini — dibuktikan
 *     empiris SEBELUM implementasi: `curl -X POST cektiket/rating/
 *     {kunci-invalid} -H 'X-Requested-With: XMLHttpRequest'` →
 *     HTTP 403, Content-Type application/json, body literal `""`
 *     2 byte).
 * (b) NON-AJAX (header `Accept: text/html,...`, TANPA
 *     `X-Requested-With`) TANPA cookie/token CSRF apa pun → SHALL TETAP
 *     403 DENGAN HALAMAN HTML ERROR PENUH (regresi guard — behavior
 *     default CI4 SHALL TIDAK berubah untuk request non-AJAX, sesuai
 *     Requirement 2.24 dan instruksi task 18.3 poin "Jika BUKAN
 *     AJAX/JSON request: biarkan behavior default CI4... TIDAK
 *     diubah").
 *
 * Kedua endpoint (`cektiket/rating/(:any)`, `login/savetiket`) dipilih
 * agar test ini independen dari path decode `Enkripsi` T4 — filter
 * `csrf` global berjalan pada SEMUA route POST SEBELUM controller
 * body apa pun (termasuk decode kunci) dieksekusi, sehingga kunci
 * path segment yang dikirim di sini TIDAK PERNAH mencapai
 * `Enkripsi::decode()` — kegagalan yang diuji MURNI kegagalan CSRF,
 * bukan kegagalan decode kunci T4.
 *
 * Requirements: 1.17, 2.24 (bugfix.md)
 */
final class T1CsrfAjaxGracefulHandlerHttpIntegrationTest extends CIUnitTestCase
{
    /** Base URL server dev live (app.baseURL, .env) — presedan K1SqlInjectionHttpIntegrationTest.php. */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    /**
     * Mengirim POST sungguhan ke server dev live TANPA cookie jar apa
     * pun (SENGAJA — test ini membuktikan skenario TIDAK ADA
     * token/cookie CSRF valid sama sekali, bukan skenario T4RatingHttp
     * IntegrationTest.php yang justru MENYERTAKAN token CSRF valid).
     * Implementasi dasar (CURLOPT_SSL_VERIFYPEER, CAINFO bundle sistem)
     * mengikuti K1SqlInjectionHttpIntegrationTest::postKeServerLive(),
     * MINUS logika cookie jar/token CSRF (dihilangkan SENGAJA di sini).
     *
     * @param array<string, string> $headerTambahan
     * @param array<string, string> $post
     *
     * @return array{status: int, contentType: string, body: string}
     */
    private function postKeServerLiveTanpaCsrf(string $path, array $post, array $headerTambahan): array
    {
        $ch = curl_init(self::BASE_URL_LIVE . $path);

        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $header = [];
        foreach ($headerTambahan as $nama => $nilai) {
            $header[] = $nama . ': ' . $nilai;
        }

        $opsi = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // SENGAJA TIDAK ADA CURLOPT_COOKIEFILE/CURLOPT_COOKIEJAR —
            // request ini TIDAK PERNAH menyertakan cookie CSRF apa pun,
            // meniru klien yang tidak pernah memuat halaman form sama
            // sekali (skenario token hilang total) ATAU token
            // kedaluwarsa/tidak valid.
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

    /**
     * Skenario (a) — Property 1 (Expected Behavior): request AJAX
     * (`X-Requested-With: XMLHttpRequest`) ke `cektiket/rating/
     * {kunci-tidak-valid}` TANPA cookie/token CSRF apa pun SHALL
     * ditolak dengan HTTP 403 DAN body respons SHALL JSON valid
     * `{"status": "danger", "message": "..."}` — BUKAN body kosong
     * `""` (behavior default CI4 sebelum fix, dibuktikan empiris pada
     * docblock kelas ini).
     */
    public function testPostAjaxTanpaCsrfKeCektiketRatingMengembalikanJson403Graceful(): void
    {
        $hasil = $this->postKeServerLiveTanpaCsrf(
            'cektiket/rating/KUNCI_TIDAK_VALID_UJI_T1AJAX',
            ['rating' => '5'],
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        self::assertSame(
            403,
            $hasil['status'],
            sprintf('Requirement 1.17: request AJAX TANPA token/cookie CSRF apa pun SHALL ditolak filter csrf global dengan HTTP 403. Status aktual: %d. Body: %s', $hasil['status'], $hasil['body'])
        );

        self::assertStringContainsString(
            'application/json',
            strtolower($hasil['contentType']),
            sprintf('Requirement 2.24: Content-Type respons SHALL application/json untuk request AJAX yang ditolak CSRF. Content-Type aktual: %s', $hasil['contentType'])
        );

        $terdekode = json_decode($hasil['body'], true);

        self::assertIsArray(
            $terdekode,
            sprintf(
                'BUG FIXED — Task 18.3 (Requirement 2.24): body respons SHALL JSON valid (objek) untuk request AJAX yang ditolak CSRF — SEBELUM fix ini, behavior default CI4 mengembalikan body literal `""` (string JSON kosong, BUKAN objek) pada kombinasi Content-Type application/json + display_errors off, yang TIDAK BERGUNA bagi client-side JS. Body mentah aktual: %s',
                $hasil['body']
            )
        );

        self::assertSame(
            'danger',
            $terdekode['status'] ?? null,
            sprintf(
                'Task 18.3 (Requirement 2.24): field "status" SHALL bernilai "danger" — kontrak JSON graceful IDENTIK dengan pola error existing Login::savetiket()/Login::cektiket() ({\'status\': \'danger\', \'message\': \'...\'}). Body aktual: %s',
                $hasil['body']
            )
        );

        self::assertNotEmpty(
            $terdekode['message'] ?? '',
            sprintf('Task 18.3 (Requirement 2.24): field "message" SHALL berisi pesan non-kosong yang dapat ditampilkan ke pengguna. Body aktual: %s', $hasil['body'])
        );
    }

    /**
     * Skenario (a), pelengkap — endpoint independen `login/savetiket`
     * (route berbeda dari cektiket/rating, memastikan handler bekerja
     * lintas controller/route, bukan hanya kebetulan berlaku pada satu
     * endpoint spesifik).
     */
    public function testPostAjaxTanpaCsrfKeLoginSavetiketMengembalikanJson403Graceful(): void
    {
        $hasil = $this->postKeServerLiveTanpaCsrf(
            'login/savetiket',
            [
                'ticketEmail'      => 'zz-t1ajax-graceful-uji@example.invalid',
                'captcha'          => 'TIDAKDIPAKAI',
                'ticketCategories' => '1',
                'ticketSubject'    => 'x',
                'ticketMessage'    => 'x',
                'ticketName'       => 'x',
                'ticketNoHp'       => '08123',
                'ticketPriority'   => '1',
            ],
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        self::assertSame(
            403,
            $hasil['status'],
            sprintf('Requirement 1.17: request AJAX TANPA token/cookie CSRF apa pun ke login/savetiket SHALL ditolak filter csrf global dengan HTTP 403. Status aktual: %d. Body: %s', $hasil['status'], $hasil['body'])
        );

        $terdekode = json_decode($hasil['body'], true);

        self::assertIsArray(
            $terdekode,
            sprintf('Task 18.3 (Requirement 2.24): body respons SHALL JSON valid untuk request AJAX ke login/savetiket yang ditolak CSRF (lintas-endpoint, bukan spesifik satu controller). Body mentah aktual: %s', $hasil['body'])
        );

        self::assertSame('danger', $terdekode['status'] ?? null, sprintf('Body aktual: %s', $hasil['body']));
    }

    /**
     * Skenario (b) — Property 2 (Preservation/Regression Guard):
     * request NON-AJAX (`Accept: text/html,...`, TANPA
     * `X-Requested-With`) ke `login/savetiket` TANPA cookie/token CSRF
     * apa pun SHALL TETAP menghasilkan HALAMAN HTML ERROR PENUH — fix
     * task 18.3 SHALL TIDAK mengubah behavior default CI4 untuk request
     * non-AJAX sama sekali (Requirement 2.24, instruksi task 18.3:
     * "Jika BUKAN AJAX/JSON request: biarkan behavior default CI4...
     * TIDAK diubah").
     *
     * Baseline pembanding (didokumentasikan pada docblock kelas ini,
     * diverifikasi empiris SEBELUM implementasi fix): request identik
     * pada kode SEBELUM fix menghasilkan HTTP 403, Content-Type
     * text/html, body 3510 byte berisi `<!doctype html>...Whoops!`.
     */
    public function testPostNonAjaxTanpaCsrfKeLoginSavetiketTetapMendapatHalamanHtmlErrorPenuh(): void
    {
        $hasil = $this->postKeServerLiveTanpaCsrf(
            'login/savetiket',
            [
                'ticketEmail'      => 'zz-t1nonajax-regresi-uji@example.invalid',
                'captcha'          => 'TIDAKDIPAKAI',
                'ticketCategories' => '1',
                'ticketSubject'    => 'x',
                'ticketMessage'    => 'x',
                'ticketName'       => 'x',
                'ticketNoHp'       => '08123',
                'ticketPriority'   => '1',
            ],
            ['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
        );

        self::assertSame(
            403,
            $hasil['status'],
            sprintf('Prasyarat: request non-AJAX TANPA token CSRF apa pun SHALL TETAP ditolak filter csrf global dengan HTTP 403 (perilaku ini TIDAK diubah fix task 18.3). Status aktual: %d.', $hasil['status'])
        );

        self::assertStringContainsString(
            'text/html',
            strtolower($hasil['contentType']),
            sprintf(
                'REGRESSION GUARD Task 18.3 (Requirement 2.24): Content-Type respons untuk request NON-AJAX yang ditolak CSRF SHALL TETAP text/html (behavior default CI4, TIDAK diubah handler AjaxSecurityExceptionHandler yang HANYA aktif untuk request AJAX/JSON). Content-Type aktual: %s',
                $hasil['contentType']
            )
        );

        self::assertStringContainsString(
            '<!doctype html>',
            strtolower($hasil['body']),
            sprintf(
                'REGRESSION GUARD Task 18.3 (Requirement 2.24): body respons untuk request NON-AJAX yang ditolak CSRF SHALL TETAP berupa halaman HTML error penuh (elemen <!doctype html> SHALL ada) — SAMA seperti behavior sebelum fix task 18.3, karena AjaxSecurityExceptionHandler mendelegasikan SEPENUHNYA ke CodeIgniter\\Debug\\ExceptionHandler bawaan untuk request non-AJAX. Body aktual (500 char pertama): %s',
                substr($hasil['body'], 0, 500)
            )
        );

        self::assertStringContainsString(
            'Whoops',
            $hasil['body'],
            'REGRESSION GUARD Task 18.3: halaman error non-AJAX SHALL tetap menampilkan judul "Whoops!" khas halaman error CI4 default — TIDAK diganti JSON/pesan graceful apa pun.'
        );

        // json_decode() pada body HTML SHALL selalu null — memastikan
        // body ini BUKAN JSON graceful (regresi guard: fix task 18.3
        // TIDAK BOLEH "meng-hijack" respons non-AJAX menjadi JSON).
        self::assertNull(
            json_decode($hasil['body'], true),
            'REGRESSION GUARD Task 18.3: body respons non-AJAX SHALL TETAP HTML (json_decode() SHALL mengembalikan null) — fix task 18.3 HANYA mengubah respons untuk request AJAX/JSON, TIDAK PERNAH untuk request non-AJAX.'
        );
    }
}
