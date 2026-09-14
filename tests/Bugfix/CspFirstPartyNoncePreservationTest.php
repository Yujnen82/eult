<?php

namespace Tests\Bugfix;

use CodeIgniter\HTTP\ContentSecurityPolicy;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Regression-guard Opsi B (nonce untuk inline first-party): seluruh blok
 * `<script>`/`<style>` inline milik aplikasi di `app/Views` WAJIB memakai
 * placeholder nonce resmi CI4 (`{csp-script-nonce}` / `{csp-style-nonce}`)
 * dalam tag yang well-formed (tertutup `>` pada baris yang sama).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONTEKS BUG YANG DIPERBAIKI
 * ═══════════════════════════════════════════════════════════════════
 * Halaman login rusak total di production: header CSP mengandung
 * `'unsafe-inline'` DAN `'nonce-…'` sekaligus pada `script-src-elem` /
 * `style-src-elem` (nonce disuntik Debug Toolbar yang aktif karena
 * server berjalan `CI_ENVIRONMENT=development`). Sesuai spesifikasi CSP,
 * kehadiran nonce membuat browser MENGABAIKAN `unsafe-inline`, sehingga
 * seluruh inline `<script>`/`<style>` tanpa atribut nonce diblokir —
 * termasuk `var KTAppOptions` dan controller jQuery halaman login.
 *
 * Keputusan desain (disetujui user, Opsi B): JANGAN melawan kehadiran
 * nonce — sebaliknya buat seluruh inline first-party TAHAN terhadapnya
 * dengan memberi placeholder nonce resmi (terdokumentasi di
 * codeigniter4/userguide `outgoing/csp.html`, diverifikasi silang dengan
 * source vendor terinstall). `ContentSecurityPolicy::finalize()` (yang
 * berjalan di `Response::send()`) mengganti placeholder menjadi atribut
 * `nonce="…"` yang cocok dengan `'nonce-…'` di header.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CAKUPAN DAN PENGECUALIAN
 * ═══════════════════════════════════════════════════════════════════
 * - `<script src="…">` eksternal/same-origin TIDAK butuh placeholder
 *   (diizinkan via directive `'self'` — nonce hanya relevan untuk inline).
 * - `app/Views/pages/ticketing/cetak/*` DIKECUALIKAN: terbukti dari
 *   `app/Controllers/Ticketing.php` seluruhnya dirender ke mPDF via
 *   `$mpdf->WriteHTML(view(…))`, TIDAK PERNAH ke browser — placeholder
 *   mentah nir-manfaat di sana dan berisiko pada parser PDF.
 * - Atribut event-handler (`onclick=`, …) dan URL `javascript:` TIDAK
 *   dicakup test ini (tidak bisa memakai nonce; pra-existing, di luar
 *   scope Opsi B — dicatat sebagai temuan tindak lanjut terpisah).
 *
 * ═══════════════════════════════════════════════════════════════════
 * DUA LAPIS VERIFIKASI (mengikuti pola R1EscBindingAuditExplorationTest)
 * ═══════════════════════════════════════════════════════════════════
 * 1) BEHAVIORAL: render `layouts/login` via renderer CI4 dan pastikan
 *    tag ber-placeholder berbentuk eksak `<script {csp-script-nonce}>`
 *    / `<style {csp-style-nonce}>` (tertutup `>`). Lapisan ini menangkap
 *    kegagalan mekanis (mis. `>` hilang saat penyuntingan massal) yang
 *    TIDAK terlihat oleh pemindaian source semata.
 * 2) STRUKTURAL: pindai source seluruh view — setiap tag inline WAJIB
 *    ber-placeholder DAN tertutup `>` sebaris (pola `[^>\n]` menolak
 *    match lintas-baris), plus deteksi eksplisit baris tag tak-tertutup.
 */
final class CspFirstPartyNoncePreservationTest extends CIUnitTestCase
{
    /** Direktori view yang dipindai. */
    private const VIEWS_DIR = FCPATH . '../app/Views';

    /** Prefix path yang dikecualikan (template PDF mPDF, bukan browser). */
    private const CETAK_PREFIX = 'pages/ticketing/cetak/';

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset();

        self::assertDirectoryExists(
            self::VIEWS_DIR,
            'Prasyarat: direktori app/Views SHALL ada untuk dipindai.'
        );
    }

    /**
     * Lapis behavioral: render `layouts/login` (data dummy, tanpa DB)
     * SHALL menghasilkan tag ber-placeholder yang well-formed — bentuk
     * eksak `<script {csp-script-nonce}>` (2×) dan
     * `<style {csp-style-nonce}>` (1×) — serta isi blok tetap utuh.
     */
    public function testRenderLoginMenghasilkanTagNonceWellFormed(): void
    {
        $html = service('renderer')->setData([
            'captcha_image_url' => 'http://localhost/captcha.png?t=1',
            'r_priority'        => [],
            'datas'             => false,
        ])->render('layouts/login');

        self::assertStringContainsString(
            '<style {csp-style-nonce}>',
            $html,
            'Render login SHALL memuat blok <style> ber-placeholder yang tertutup ">".'
        );

        self::assertSame(
            2,
            substr_count($html, '<script {csp-script-nonce}>'),
            'Render login SHALL memuat tepat 2 blok <script> inline ber-placeholder yang tertutup ">".'
        );

        self::assertStringContainsString(
            'var KTAppOptions',
            $html,
            'Isi blok konfigurasi KTAppOptions SHALL tetap utuh di output render.'
        );

        // Bentuk rusak (placeholder tanpa penutup ">") SHALL tidak ada.
        self::assertStringNotContainsString(
            "{csp-script-nonce}\n",
            $html,
            'Placeholder script SHALL selalu diikuti ">" pada baris yang sama (tag tak-tertutup terdeteksi).'
        );
        self::assertStringNotContainsString(
            "{csp-style-nonce}\n",
            $html,
            'Placeholder style SHALL selalu diikuti ">" pada baris yang sama (tag tak-tertutup terdeteksi).'
        );
    }

    /**
     * Seluruh `<script>` inline (tanpa atribut `src=`) di app/Views
     * SHALL mengandung placeholder `{csp-script-nonce}` dalam tag yang
     * tertutup `>` sebaris, agar selamat dari pemblokiran browser saat
     * header CSP memuat nonce.
     */
    public function testSeluruhInlineScriptViewMengandungPlaceholderNonceScript(): void
    {
        $pelanggaran = $this->pindaiTagTanpaPlaceholder(
            '/<script\b[^>\n]*>/i',
            '{csp-script-nonce}',
            true
        );

        self::assertSame(
            [],
            $pelanggaran,
            "Inline <script> TANPA placeholder nonce / TIDAK well-formed ditemukan (akan diblokir browser saat header CSP memuat 'nonce-…'):\n - "
                . implode("\n - ", $pelanggaran)
        );
    }

    /**
     * Seluruh `<style>` di app/Views SHALL mengandung placeholder
     * `{csp-style-nonce}` dalam tag yang tertutup `>` sebaris, dengan
     * alasan yang sama seperti di atas.
     */
    public function testSeluruhInlineStyleViewMengandungPlaceholderNonceStyle(): void
    {
        $pelanggaran = $this->pindaiTagTanpaPlaceholder(
            '/<style\b[^>\n]*>/i',
            '{csp-style-nonce}',
            false
        );

        self::assertSame(
            [],
            $pelanggaran,
            "Blok <style> TANPA placeholder nonce / TIDAK well-formed ditemukan (akan diblokir browser saat header CSP memuat 'nonce-…'):\n - "
                . implode("\n - ", $pelanggaran)
        );
    }

    /**
     * Mengunci kontrak framework yang diandalkan fix ini:
     * `ContentSecurityPolicy::finalize()` SHALL mengganti placeholder di
     * body menjadi atribut `nonce="…"` yang NILAINYA COCOK dengan
     * `'nonce-…'` pada directive header, dan tidak menyisakan placeholder
     * mentah di body.
     */
    public function testFinalizeMenyuntikNonceYangCocokAntaraHeaderDanBody(): void
    {
        $csp = new ContentSecurityPolicy(new \Config\ContentSecurityPolicy());

        $response = new Response(new \Config\App());
        $response->setBody(
            '<html><head><style {csp-style-nonce}>.a{color:red}</style></head>'
            . '<body><script {csp-script-nonce}>var a=1;</script></body></html>'
        );

        // Persis yang dilakukan Response::send() pada lifecycle nyata.
        $csp->finalize($response);

        $header = $response->getHeaderLine('Content-Security-Policy');
        $body   = (string) $response->getBody();

        self::assertStringNotContainsString(
            '{csp-script-nonce}',
            $body,
            'finalize() SHALL mengganti seluruh placeholder script di body.'
        );
        self::assertStringNotContainsString(
            '{csp-style-nonce}',
            $body,
            'finalize() SHALL mengganti seluruh placeholder style di body.'
        );

        $cocokScript = preg_match("/script-src-elem[^;]*'nonce-([^']+)'/", $header, $mScript);
        $cocokStyle  = preg_match("/style-src-elem[^;]*'nonce-([^']+)'/", $header, $mStyle);

        self::assertSame(1, $cocokScript, 'Header SHALL memuat nonce pada script-src-elem. Header: ' . $header);
        self::assertSame(1, $cocokStyle, 'Header SHALL memuat nonce pada style-src-elem. Header: ' . $header);

        self::assertStringContainsString(
            'nonce="' . $mScript[1] . '"',
            $body,
            'Atribut nonce pada <script> SHALL cocok dengan header.'
        );
        self::assertStringContainsString(
            'nonce="' . $mStyle[1] . '"',
            $body,
            'Atribut nonce pada <style> SHALL cocok dengan header.'
        );
    }

    /**
     * Memindai seluruh `*.php` di app/Views (rekursif, kecuali direktori
     * cetak PDF) dan mengembalikan daftar "path:baris: temuan" untuk
     * setiap tag yang cocok pola tetapi TIDAK mengandung placeholder
     * wajib, DITAMBAH setiap baris tag `<script>`/`<style>` yang tidak
     * tertutup `>` sebaris (TAG-RUSAK).
     *
     * @return list<string>
     */
    private function pindaiTagTanpaPlaceholder(string $polaTag, string $placeholderWajib, bool $abaikanTagDenganSrc): array
    {
        $pelanggaran = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::VIEWS_DIR, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $berkas */
        foreach ($iterator as $berkas) {
            if ($berkas->getExtension() !== 'php') {
                continue;
            }

            $pathRelatif = substr($berkas->getPathname(), strlen(self::VIEWS_DIR) + 1);

            // Template PDF mPDF — tidak pernah dirender ke browser.
            if (str_starts_with($pathRelatif, self::CETAK_PREFIX)) {
                continue;
            }

            $isi = file_get_contents($berkas->getPathname());

            if (! is_string($isi)) {
                continue;
            }

            if (preg_match_all($polaTag, $isi, $cocok, PREG_OFFSET_CAPTURE)) {
                foreach ($cocok[0] as [$tag, $offset]) {
                    if ($abaikanTagDenganSrc && preg_match('/\ssrc\s*=/i', $tag)) {
                        continue;
                    }

                    if (str_contains($tag, $placeholderWajib)) {
                        continue;
                    }

                    $baris = substr_count($isi, "\n", 0, $offset) + 1;
                    $pelanggaran[] = sprintf('%s:%d: %s', $pathRelatif, $baris, trim($tag));
                }
            }

            // Deteksi eksplisit tag tak-tertutup sebaris (mis. hilangnya
            // ">" akibat penyuntingan massal — pola utama di atas tidak
            // menangkapnya karena mensyaratkan penutup ">").
            if (preg_match_all('/^[ \t]*<(script|style)\b[^>\n]*$/mi', $isi, $rusak, PREG_OFFSET_CAPTURE)) {
                foreach ($rusak[0] as [$tag, $offset]) {
                    $baris = substr_count($isi, "\n", 0, $offset) + 1;
                    $pelanggaran[] = sprintf('%s:%d: TAG-RUSAK %s', $pathRelatif, $baris, trim($tag));
                }
            }
        }

        sort($pelanggaran);

        return array_values(array_unique($pelanggaran));
    }
}
