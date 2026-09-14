<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 21.2 (Klaster 3 / M1): Test INTEGRASI HTTP untuk header
 * `Content-Security-Policy` yang benar-benar terkirim pada response
 * HTTP sungguhan (bugfix.md 1.25, 2.38, 2.39; Requirement 2.38, 2.39).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA cURL-ke-server-live, BUKAN `CodeIgniter\Test\
 * FeatureTestTrait` (in-process) — SAMA KATEGORI dengan
 * T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php/
 * K1SqlInjectionHttpIntegrationTest.php, alasan struktural BERBEDA:
 * ═══════════════════════════════════════════════════════════════════
 * Diverifikasi LANGSUNG dari source (bukan diasumsikan) pada task ini:
 * `CodeIgniter\HTTP\ResponseTrait::send()`
 * (vendor/codeigniter4/framework/system/HTTP/ResponseTrait.php,
 * method `send()`) memanggil `$this->CSP->finalize($this)` SEBELUM
 * `sendHeaders()` — `ContentSecurityPolicy::finalize()`
 * (vendor/codeigniter4/framework/system/HTTP/ContentSecurityPolicy.php)
 * memanggil `generateNonces()` lalu `buildHeaders($response)`, method
 * INILAH yang benar-benar menambahkan header `Content-Security-Policy`
 * ke response.
 *
 * `send()` HANYA dipanggil pada request lifecycle produksi CI4 yang
 * sesungguhnya (`CodeIgniter\CodeIgniter::run()` → `$response->send()`
 * di ujung). `CodeIgniter\Test\FeatureTestTrait::call()` (dikonfirmasi
 * dari pembacaan langsung source-nya, sama seperti investigasi
 * K1SqlInjectionHttpIntegrationTest.php sebelumnya) TIDAK PERNAH
 * memanggil `send()` — ia mengembalikan objek Response mentah hasil
 * `$this->app->...->run()` sebelum tahap output. Konsekuensinya:
 * `ContentSecurityPolicy::finalize()`/`buildHeaders()` TIDAK PERNAH
 * tereksekusi pada dispatch in-process, sehingga
 * `$hasil->response()->getHeaderLine('Content-Security-Policy')`
 * SELALU mengembalikan string kosong pada `FeatureTestTrait`
 * — TERLEPAS dari nilai `$CSPEnabled` yang sesungguhnya
 * dikonfigurasi. Ini murni keterbatasan struktural harness test
 * in-process, BUKAN kegagalan implementasi fix 21.1 (dibuktikan pada
 * dokumentasi task 21.1 sendiri via curl ke server live + PHP
 * built-in server lokal SEBELUM test ini ditulis, dan diverifikasi
 * ULANG independen pada task ini — lihat hasil di bawah).
 *
 * Dispatch HTTP NYATA (cURL, koneksi TCP terpisah, proses server PHP
 * terpisah dari proses PHPUnit) adalah SATU-SATUNYA cara memicu
 * `Response::send()` yang sesungguhnya dan dengan demikian
 * `ContentSecurityPolicy::finalize()`. Base URL, opsi CURLOPT
 * (termasuk CAINFO bundle sistem untuk sertifikat dev lokal), dan pola
 * dasar mengikuti K1SqlInjectionHttpIntegrationTest.php/
 * T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php — versi GET
 * (bukan POST) karena `Login::index()` (`GET /login`) adalah endpoint
 * yang diuji, konsisten dengan M1SecurityHeadersExplorationTest.php
 * (task 15) yang juga men-dispatch `GET /login`.
 *
 * Server dev live (`https://eult.appdev-papenajam.me/`) dikonfirmasi
 * REACHABLE dan mengembalikan `GET /login` → HTTP 200 pada saat test
 * ini ditulis (berbeda dari kondisi yang dilaporkan task 21.1, di mana
 * server live sempat mengembalikan 404 pada seluruh path akibat
 * masalah sync deployment yang terpisah dari fix CSP — kondisi
 * tersebut TIDAK lagi terjadi saat task 21.2 dikerjakan, diverifikasi
 * ulang langsung sebelum test ini ditulis). Dipilih server live
 * (bukan PHP built-in server lokal yang di-start manual) karena: (a)
 * reachable dan mengembalikan header lengkap sesuai whitelist 21.1
 * saat diverifikasi; (b) konsisten dengan SELURUH test HTTP-integration
 * lain di direktori ini (K1/T1/T2/T2M3/T4) yang seluruhnya
 * mengasumsikan server live reachable tanpa auto-start programatik —
 * tidak ada precedent test lain di direktori ini yang men-start server
 * built-in sendiri di `setUpBeforeClass()`, sehingga menambahkannya
 * di sini akan menjadi pola baru yang tidak konsisten tanpa nilai
 * tambah (server live sudah reachable dan cukup).
 *
 * Test ini TIDAK memerlukan setup/cleanup database — `Login::index()`
 * murni method GET read-only (generate captcha, ambil daftar layanan/
 * prioritas untuk populate form, `return view(...)`), sama seperti
 * dikonfirmasi pada docblock M1SecurityHeadersExplorationTest.php.
 *
 * Requirements: 1.25, 2.38, 2.39 (bugfix.md)
 */
final class M1CspHeaderHttpIntegrationTest extends CIUnitTestCase
{
    /** Base URL server dev live (app.baseURL, .env) — presedan K1SqlInjectionHttpIntegrationTest.php. */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    /**
     * Mengirim GET sungguhan ke server dev live — koneksi TCP
     * terpisah, BUKAN dispatch in-process PHPUnit. Implementasi dasar
     * (CURLOPT_SSL_VERIFYPEER, CAINFO bundle sistem) mengikuti
     * K1SqlInjectionHttpIntegrationTest::postKeServerLive(), versi GET
     * (tanpa CURLOPT_POST/CURLOPT_POSTFIELDS).
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function getKeServerLive(string $path): array
    {
        $ch = curl_init(self::BASE_URL_LIVE . $path);

        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $headerMentah = [];

        $opsi = [
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($curl, $baris) use (&$headerMentah) {
                $panjang = strlen($baris);
                $bagian  = explode(':', $baris, 2);

                if (count($bagian) === 2) {
                    $headerMentah[strtolower(trim($bagian[0]))] = trim($bagian[1]);
                }

                return $panjang;
            },
        ];

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
     * Property 1 (Expected Behavior) — checkpoint 21.2, bagian header
     * `Content-Security-Policy`: dispatch HTTP NYATA (cURL)
     * `GET /login` ke server live SHALL menghasilkan response yang
     * benar-benar menyertakan header `Content-Security-Policy`
     * (non-kosong), DAN header tersebut SHALL mengandung minimal
     * beberapa directive kunci sesuai whitelist final yang
     * dikonfigurasi task 21.1 (`app/Config/ContentSecurityPolicy.php`).
     *
     * Ini adalah gate checkpoint 21.2 yang BENAR untuk bagian CSP
     * (menggantikan `M1SecurityHeadersExplorationTest::
     * testHalamanPublikTidakMengandungHeaderCspDanXFrameOptions` UNTUK
     * BAGIAN CSP SPESIFIK-nya — lihat dokumentasi redefinisi gate di
     * tasks.md task 21.2 untuk alasan lengkap).
     */
    public function testGetLoginKeServerLiveMengandungHeaderContentSecurityPolicyDenganDirectiveKunci(): void
    {
        $hasil = $this->getKeServerLive('login');

        self::assertSame(
            200,
            $hasil['status'],
            sprintf('Prasyarat: GET /login ke server live SHALL 200 agar response header dapat diperiksa bermakna. Status aktual: %d.', $hasil['status'])
        );

        $headerCsp = $hasil['headers']['content-security-policy'] ?? '';

        self::assertNotSame(
            '',
            $headerCsp,
            'BUG FIXED — Task 21.1/21.2 (Requirement 2.39): response GET /login SUNGGUHAN dari server live SHALL menyertakan header "Content-Security-Policy" (non-kosong). Ini adalah pembuktian bahwa ContentSecurityPolicy::finalize()/buildHeaders() benar-benar tereksekusi pada Response::send() sungguhan, sesuatu yang TIDAK BISA dibuktikan via dispatch in-process FeatureTestTrait (lihat docblock kelas ini).'
        );

        // Directive kunci sesuai whitelist final task 21.1
        // (app/Config/ContentSecurityPolicy.php) — bukan pencocokan
        // string CSP secara keseluruhan (rapuh terhadap urutan/nonce
        // yang berubah setiap request), melainkan potongan directive
        // yang WAJIB ada berdasarkan audit aset nyata login.php/
        // detail_user.php pada task 21.1.
        $directiveWajib = [
            "default-src 'self'",
            "font-src 'self' https://fonts.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data:",
        ];

        foreach ($directiveWajib as $directive) {
            self::assertStringContainsString(
                $directive,
                $headerCsp,
                sprintf(
                    'Requirement 2.39 (whitelist CSP task 21.1): header Content-Security-Policy SHALL mengandung directive "%s" sesuai audit aset nyata login.php/detail_user.php. Header CSP aktual: %s',
                    $directive,
                    $headerCsp
                )
            );
        }

        // script-src mengandung nonce dinamis per-request (autoNonce),
        // sehingga diperiksa via awalan tetap (bukan string penuh).
        self::assertStringContainsString(
            "script-src 'self' 'unsafe-inline'",
            $headerCsp,
            sprintf('Requirement 2.39: directive script-src SHALL mengandung "self" dan "unsafe-inline" (2 blok <script> inline login.php/detail_user.php yang tidak direfaktor, task 21.1). Header CSP aktual: %s', $headerCsp)
        );
    }

    /**
     * Pelengkap regression-guard: ketiga header lain yang berasal dari
     * filter `secureheaders` (BUKAN CSP, sudah aktif sejak task 18.1,
     * TIDAK terpengaruh keterbatasan struktural `finalize()`/`send()`
     * di atas — `SecureHeaders` filter menambahkan header langsung
     * via `$response->setHeader()` di titik filter `after`, sebelum
     * `send()`, sehingga SEHARUSNYA juga terlihat pada dispatch
     * in-process `FeatureTestTrait` DAN pada cURL server live).
     * Diperiksa di sini sekali lagi via cURL server live untuk
     * memberi bukti HTTP-level penuh yang konsisten dengan assertion
     * CSP di atas pada response yang SAMA persis (satu request GET),
     * BUKAN redefinisi gate untuk ketiganya (yang TETAP lulus normal
     * via `M1SecurityHeadersExplorationTest.php` in-process, tidak
     * termasuk bagian yang diredefinisi task 21.2).
     */
    public function testGetLoginKeServerLiveMengandungHeaderSecureheadersLengkap(): void
    {
        $hasil = $this->getKeServerLive('login');

        self::assertSame(200, $hasil['status']);

        self::assertNotSame(
            '',
            $hasil['headers']['x-frame-options'] ?? '',
            sprintf('Requirement 1.26/2.38: header X-Frame-Options SHALL hadir pada response GET /login sungguhan (filter secureheaders, aktif sejak task 18.1). Header lengkap: %s', json_encode($hasil['headers']))
        );

        self::assertNotSame(
            '',
            $hasil['headers']['x-content-type-options'] ?? '',
            sprintf('Requirement 1.26/2.38: header X-Content-Type-Options SHALL hadir pada response GET /login sungguhan. Header lengkap: %s', json_encode($hasil['headers']))
        );

        self::assertNotSame(
            '',
            $hasil['headers']['referrer-policy'] ?? '',
            sprintf('Requirement 1.26/2.38: header Referrer-Policy SHALL hadir pada response GET /login sungguhan. Header lengkap: %s', json_encode($hasil['headers']))
        );
    }
}
