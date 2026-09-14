<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Regression-guard Opsi B aset admin (vendor lokal): layout admin
 * (`layouts/template.php`) TIDAK BOLEH memuat skrip/stylesheet dari CDN
 * eksternal. Seluruh aset pihak ketiga wajib di-vendor ke
 * `public/assets/vendor/` (same-origin, tercover directive `'self'`),
 * dan `font-src` WAJIB mengizinkan `data:`.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONTEKS BUG YANG DIPERBAIKI
 * ═══════════════════════════════════════════════════════════════════
 * Halaman `/laporan` (dan seluruh halaman admin) mencatat error CSP:
 * 7× skrip eksternal diblokir `script-src-elem` (1× webfont.js
 * `ajax.googleapis.com` + 6× tableExport `rawcdn.githack.com`), 1× CSS
 * `unpkg.com` diblokir `style-src-elem`, dan 1× font `data:` diblokir
 * `font-src` — berujung `ReferenceError: WebFont is not defined`.
 * Keputusan desain (disetujui user, Opsi B): vendor-kan ke-8 aset ke
 * repo (byte-identik dari URL yang dipakai hari ini, zero behavior
 * change) alih-alih me-whitelist 3 domain CDN di CSP; `data:` hanya
 * ditambahkan ke `font-src` (dibutuhkan icon font `fcicons`
 * FullCalendar yang di-embed via data: URI di CSS first-party).
 */
final class AdminCspAssetsPreservationTest extends CIUnitTestCase
{
    /** Layout admin yang diaudit. */
    private const TEMPLATE_PATH = FCPATH . '../app/Views/layouts/template.php';

    /** Direktori vendor lokal. */
    private const VENDOR_DIR = FCPATH . '../public/assets/vendor';

    /**
     * Delapan berkas vendor yang wajib ada (path relatif VENDOR_DIR),
     * dipetakan ke URL upstream asalnya untuk audit provenance.
     *
     * @var array<string, string> [path lokal => URL upstream]
     */
    private const ASET_VENDOR = [
        'webfont/1.6.16/webfont.js' => 'https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js',
        'tableexport/ac867b5/tableexport.min.css' => 'https://unpkg.com/tableexport/dist/css/tableexport.min.css',
        'tableexport/ac867b5/xlsx.core.min.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/libs/js-xlsx/xlsx.core.min.js',
        'tableexport/ac867b5/FileSaver.min.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/libs/FileSaver/FileSaver.min.js',
        'tableexport/ac867b5/html2canvas.min.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/libs/html2canvas/html2canvas.min.js',
        'tableexport/ac867b5/tableExport.min.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/tableExport.min.js',
        'tableexport/ac867b5/jspdf.min.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/libs/jsPDF/jspdf.min.js',
        'tableexport/ac867b5/jspdf.plugin.autotable.js' => 'https://rawcdn.githack.com/hhurz/tableExport.jquery.plugin/ac867b593f515e2e920aed075e895757f51ee0e4/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset();

        self::assertFileExists(
            self::TEMPLATE_PATH,
            'Prasyarat: app/Views/layouts/template.php SHALL ada untuk diaudit.'
        );
    }

    /**
     * `template.php` SHALL tidak memuat URL http(s) eksternal apa pun
     * kecuali `fonts.googleapis.com` (satu-satunya domain CSS eksternal
     * yang diizinkan kebijakan CSP). Komentar HTML diabaikan (mis. contoh
     * URL cdnjs yang terkomentari).
     */
    public function testTemplateTidakMemuatAsetEksternalKecualiGoogleFonts(): void
    {
        $isi = file_get_contents(self::TEMPLATE_PATH);

        self::assertIsString($isi, 'Prasyarat: template.php SHALL terbaca.');

        // Abaikan komentar HTML agar contoh URL terkomentari tidak dihitung.
        $tanpaKomentar = preg_replace('/<!--.*?-->/s', '', $isi);

        preg_match_all('#https?://[^"\'\s<>]+#i', $tanpaKomentar, $cocok);

        $terlarang = array_values(array_filter(
            array_unique($cocok[0]),
            static fn (string $url): bool => ! str_contains($url, 'fonts.googleapis.com')
        ));

        self::assertSame(
            [],
            $terlarang,
            "template.php STILL memuat aset eksternal di luar whitelist (akan diblokir CSP):\n - "
                . implode("\n - ", $terlarang)
        );
    }

    /**
     * Kedelapan berkas vendor SHALL ada, tidak kosong, dan berkas
     * provenance SHALL mendokumentasikan seluruh URL upstream asalnya
     * (agar pembaruan manual di masa depan terlacak).
     */
    public function testBerkasVendorAdaTidakKosongDanTerprovenance(): void
    {
        foreach (self::ASET_VENDOR as $path => $url) {
            $penuh = self::VENDOR_DIR . '/' . $path;

            self::assertFileExists($penuh, sprintf('Berkas vendor SHALL ada: %s (sumber: %s).', $path, $url));
            self::assertGreaterThan(
                0,
                filesize($penuh),
                sprintf('Berkas vendor SHALL tidak kosong: %s.', $path)
            );
        }

        $provenance = self::VENDOR_DIR . '/README-ASAL.txt';

        self::assertFileExists($provenance, 'Dokumen provenance public/assets/vendor/README-ASAL.txt SHALL ada.');

        $isi = file_get_contents($provenance);

        foreach (self::ASET_VENDOR as $url) {
            self::assertStringContainsString(
                $url,
                $isi,
                sprintf('Provenance SHALL mendokumentasikan URL upstream: %s.', $url)
            );
        }
    }

    /**
     * Setiap path `assets/vendor/…` yang dirujuk `template.php` SHALL
     * cocok dengan berkas nyata di `public/` — menutup risiko salah
     * ketik path yang lolos dari audit source (browser akan 404).
     */
    public function testSeluruhPathVendorYangDirujukTemplateAdaDiPublic(): void
    {
        $isi = file_get_contents(self::TEMPLATE_PATH);

        preg_match_all('#assets/vendor/[^"\'\s<>]+#', $isi, $cocok);

        $path = array_values(array_unique($cocok[0]));

        self::assertCount(
            8,
            $path,
            'template.php SHALL merujuk tepat 8 path vendor lokal.'
        );

        foreach ($path as $relatif) {
            self::assertFileExists(
                FCPATH . $relatif,
                sprintf('Path vendor yang dirujuk template SHALL ada di public/: %s.', $relatif)
            );
        }
    }

    /**
     * `font-src` SHALL mengizinkan `data:` — dibutuhkan icon font
     * `fcicons` FullCalendar yang di-embed via data: URI di CSS
     * first-party (`fullcalendar.bundle.css`).
     */
    public function testFontSrcMengizinkanDataUri(): void
    {
        $fontSrc = (array) (new \Config\ContentSecurityPolicy())->fontSrc;

        self::assertContains(
            'data:',
            $fontSrc,
            'font-src SHALL memuat "data:" agar icon font data: URI first-party tidak diblokir.'
        );
    }
}
