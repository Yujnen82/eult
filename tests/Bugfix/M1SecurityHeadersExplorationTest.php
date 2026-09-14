<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 15 (Klaster 3 — M1): Test EKSPLORASI bug condition Header
 * Keamanan Browser Tidak Lengkap (bugfix.md 1.25, 1.26; design.md
 * bagian "M1 — Header security browser tidak lengkap"; Property 18
 * "Bug Condition - M1 Header Keamanan Selalu Hadir").
 *
 * PENTING — metodologi bug condition:
 * Test ini WAJIB GAGAL pada kode yang belum diperbaiki. Kegagalan test
 * mengonfirmasi bug ada (Property 1: Bug Condition). Test ini TIDAK
 * BOLEH diperbaiki di sini — dia mengenkode expected/fixed behavior dan
 * akan berubah menjadi LULUS setelah fix M1 (task 20, mengaktifkan
 * `$CSPEnabled` di `app/Config/App.php` + filter `secureheaders` di
 * `app/Config/Filters.php` — bagian dari T1) diimplementasikan.
 *
 * GOAL: Surface counterexample bahwa header `Content-Security-Policy`
 * DAN `X-Frame-Options` (nama header yang secara eksplisit disebut task
 * 15) seluruhnya ABSEN dari response HTTP halaman publik pada kode
 * asli, sebagai akibat dari DUA kondisi independen yang dikonfirmasi
 * langsung dari source:
 *
 *   1. `app/Config/App.php:192` — `public bool $CSPEnabled = false;`
 *   2. `app/Config/Filters.php` `$globals['after']` — baris
 *      `// 'secureheaders',` masih dikomentari (bagian dari bug T1,
 *      task 12), sehingga filter `SecureHeaders::class` (yang pada
 *      kode SETELAH fix akan menambahkan `X-Frame-Options`,
 *      `X-Content-Type-Options`, `Referrer-Policy`) tidak pernah
 *      dijalankan untuk request apa pun.
 *
 * Dua header tambahan (`X-Content-Type-Options`, `Referrer-Policy`)
 * yang disebut design.md sebagai bagian Bug Condition/Expected
 * Behavior M1 (Property 18) turut diperiksa pada test kedua di bawah
 * agar counterexample lengkap mencakup keempat header yang disebutkan
 * — bukan hanya pasangan CSP/X-Frame-Options yang disebut literal pada
 * teks task.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEPUTUSAN ARSITEKTUR TEST — MENGAPA `FeatureTestTrait` IN-PROCESS
 * AMAN DIPAKAI DI SINI (berbeda dari beberapa test lain di direktori
 * ini yang harus memakai cURL-ke-server-live):
 * ═══════════════════════════════════════════════════════════════════
 * Test ini men-dispatch `GET /` (`Login::index()`), method yang HANYA
 * merender view `layouts/login` (lihat app/Controllers/Login.php) —
 * TIDAK ada satu pun panggilan `eult_message_kirim()` (yang memanggil
 * `response()->send(); exit;` sungguhan, tidak kompatibel dengan
 * dispatch in-process, lihat docblock T1CsrfExplorationTest.php/
 * T4RatingHttpIntegrationTest.php untuk investigasi lengkap pola ini
 * di controller lain). `Login::index()` murni method GET read-only:
 * generate captcha, ambil daftar layanan/prioritas untuk populate form,
 * lalu `return view(...)` — tidak ada side-effect database (insert/
 * update/delete) sama sekali, sehingga test ini TIDAK memerlukan
 * setup/cleanup baris database apa pun (murni pemeriksaan header
 * response, sesuai instruksi task 15 poin 3).
 *
 * Requirements: 1.25, 1.26 (bugfix.md)
 */
final class M1SecurityHeadersExplorationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * Property 1 (Bug Condition) — SKENARIO UTAMA yang diminta task 15:
     * dispatch `GET /` (halaman publik `Login::index()`, form
     * login/buat/lacak tiket) pada kode ASLI, lalu assert response
     * SHALL menyertakan header `Content-Security-Policy` DAN
     * `X-Frame-Options` — dua nama header yang secara eksplisit
     * disebut task 15.
     *
     * **EXPECTED OUTCOME test ini pada kode asli: GAGAL** — assertion
     * di bawah menuntut kedua header tersebut HADIR (non-empty), yang
     * TIDAK akan terjadi pada kode asli (`$CSPEnabled = false` DAN
     * filter `secureheaders` masih dikomentari), sehingga assertion
     * gagal — kegagalan inilah yang menjadi counterexample yang
     * membuktikan bug M1 ada.
     */
    public function testHalamanPublikTidakMengandungHeaderCspDanXFrameOptions(): void
    {
        $hasil = $this->get('login');

        $hasil->assertStatus(200);

        $headerCsp         = $hasil->response()->getHeaderLine('Content-Security-Policy');
        $headerXFrameOptions = $hasil->response()->getHeaderLine('X-Frame-Options');

        // *** ASSERTION UTAMA (Property 1 — Bug Condition) ***
        // Pada kode SETELAH fix M1 (`$CSPEnabled = true` +
        // `secureheaders` aktif), kedua header ini SHALL selalu hadir
        // (non-empty) pada response halaman publik apa pun. Pada kode
        // ASLI, `getHeaderLine()` untuk header yang tidak pernah
        // ditambahkan mengembalikan string kosong (''), sehingga
        // `assertNotSame('', ...)` GAGAL — membuktikan header
        // benar-benar absen, bukan berisi nilai yang salah.
        self::assertNotSame(
            '',
            $headerCsp,
            sprintf(
                "BUG CONDITION M1 (Requirement 1.25) — COUNTEREXAMPLE: response GET /login (halaman publik Login::index()) TIDAK mengandung header 'Content-Security-Policy' sama sekali (nilai getHeaderLine() kosong), karena \$CSPEnabled = false pada app/Config/App.php:192. Ini membuktikan browser klien manapun tidak menerima instruksi CSP apa pun dari server, membuka permukaan serangan XSS/clickjacking/injection resource yang seharusnya dimitigasi CSP."
            )
        );

        self::assertNotSame(
            '',
            $headerXFrameOptions,
            "BUG CONDITION M1 (Requirement 1.26) — COUNTEREXAMPLE: response GET /login (halaman publik Login::index()) TIDAK mengandung header 'X-Frame-Options' sama sekali (nilai getHeaderLine() kosong), karena filter 'secureheaders' (CodeIgniter\\Filters\\SecureHeaders) masih dikomentari pada \$globals['after'] di app/Config/Filters.php. Ini membuktikan halaman dapat di-embed ke <iframe> milik situs manapun, membuka permukaan serangan clickjacking."
        );
    }

    /**
     * Pelengkap counterexample (design.md Property 18/Bug Condition M1
     * menyebut EMPAT header: CSP, X-Frame-Options, X-Content-Type-Options,
     * Referrer-Policy — ketiga terakhir seluruhnya berasal dari filter
     * `secureheaders` yang sama, bukan CSP). Diperiksa terpisah dari
     * test utama di atas (yang berfokus pada dua nama literal yang
     * disebut teks task 15) agar counterexample yang didokumentasikan
     * mencakup gambaran lengkap dari akar kedua bug condition M1 (CSP
     * config + filter secureheaders), bukan hanya sebagian.
     *
     * **EXPECTED OUTCOME test ini pada kode asli: GAGAL** — sama seperti
     * test utama, kedua header ini SHALL absen pada kode asli karena
     * filter `secureheaders` yang sama belum aktif.
     */
    public function testHalamanPublikTidakMengandungHeaderXContentTypeOptionsDanReferrerPolicy(): void
    {
        $hasil = $this->get('login');

        $hasil->assertStatus(200);

        $headerXContentTypeOptions = $hasil->response()->getHeaderLine('X-Content-Type-Options');
        $headerReferrerPolicy      = $hasil->response()->getHeaderLine('Referrer-Policy');

        self::assertNotSame(
            '',
            $headerXContentTypeOptions,
            "BUG CONDITION M1 (design.md Bug Condition M1 / Property 18) — COUNTEREXAMPLE: response GET /login TIDAK mengandung header 'X-Content-Type-Options' sama sekali, karena filter 'secureheaders' masih dikomentari pada \$globals['after'] di app/Config/Filters.php. Header ini seharusnya mencegah browser melakukan MIME-sniffing yang dapat mengeksekusi konten upload sebagai HTML/JS."
        );

        self::assertNotSame(
            '',
            $headerReferrerPolicy,
            "BUG CONDITION M1 (design.md Bug Condition M1 / Property 18) — COUNTEREXAMPLE: response GET /login TIDAK mengandung header 'Referrer-Policy' sama sekali, karena filter 'secureheaders' masih dikomentari pada \$globals['after'] di app/Config/Filters.php. Tanpa header ini, URL halaman (yang dapat memuat kunci terenkripsi tiket pada endpoint lain) berpotensi ter-leak via header Referer ke pihak ketiga saat pengguna mengklik link keluar."
        );
    }

    /**
     * Pembuktian PROGRAMATIK (bukan sekadar pembacaan teks) bahwa
     * `$CSPEnabled` benar-benar `false` pada konfigurasi App yang
     * dimuat CI4 saat ini — mengonfirmasi akar bug condition M1 yang
     * pertama (dari dua akar independen) secara langsung dari objek
     * konfigurasi yang sesungguhnya dipakai framework, melengkapi
     * counterexample response header di atas dengan bukti konfigurasi
     * sumber.
     *
     * **EXPECTED OUTCOME test ini pada kode asli: GAGAL** — assertion
     * menuntut `$CSPEnabled === true` (expected/fixed behavior),
     * namun nilai aktual pada kode asli adalah `false`.
     */
    public function testKonfigurasiCspEnabledMasihFalsePadaKodeAsli(): void
    {
        $konfigurasiApp = config('App');

        self::assertTrue(
            $konfigurasiApp->CSPEnabled,
            sprintf(
                "BUG CONDITION M1 (Requirement 1.25) — COUNTEREXAMPLE KONFIGURASI: \$CSPEnabled pada Config\\App yang dimuat framework bernilai %s (bukan true), sesuai app/Config/App.php:192. Selama nilai ini false, CodeIgniter TIDAK PERNAH menambahkan header Content-Security-Policy apa pun ke response manapun, terlepas dari filter apa pun yang aktif.",
                $konfigurasiApp->CSPEnabled ? 'true' : 'false'
            )
        );
    }
}
