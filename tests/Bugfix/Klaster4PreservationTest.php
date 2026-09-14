<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use K4DebugToolbarServerRunner;

require_once __DIR__ . '/K4DebugToolbarServerRunner.php';

/**
 * Task 25 (Klaster 4 — Preservasi): Test PROPERTI PRESERVASI (Property 2)
 * — "Development Tetap Memakai Fallback dan Toolbar Penuh" (bugfix.md
 * 3.8, 3.9, 3.10, 3.11, 3.12; design.md bagian "K3 — Kunci Enkripsi
 * Fallback Hardcode di Production" dan "K4 — Debug toolbar publik
 * membocorkan session & PII lintas pengguna").
 *
 * PENTING — metodologi observation-first:
 * Test ini mengobservasi perilaku kode BELUM diperbaiki untuk kondisi
 * yang HARUS TETAP SAMA setelah fix K3 (task 26.1, pengecekan
 * `ENVIRONMENT === 'production'` pada `Enkripsi::kunciLegacy()`) dan
 * fix K4 (task 27.1, `App\Filters\EnvironmentAwareToolbar`) diimplementasikan
 * — dan menetapkannya sebagai BASELINE yang WAJIB dipertahankan
 * identik pasca-fix. Test ini WAJIB LULUS pada kode belum diperbaiki
 * (BUKAN mengonfirmasi bug, berbeda dari
 * `K3EncryptionFallbackExplorationTest.php`/
 * `K4DebugToolbarExplorationTest.php` pada task 23-24 — tetapi
 * MEMAKAI kembali PERSIS infrastruktur/runner yang sama dari kedua
 * file tersebut, lihat bagian REUSE POLA di bawah).
 *
 * ═══════════════════════════════════════════════════════════════════
 * REUSE POLA — TIDAK ADA MEKANISME DISPATCH BARU DITEMUKAN DI SINI:
 * ═══════════════════════════════════════════════════════════════════
 * Seluruh mekanisme dispatch/subprocess pada file ini diambil LANGSUNG
 * dari infrastruktur task 23 (K3) dan task 24 (K4), TANPA mekanisme
 * baru:
 *
 * - K3 (observasi a, b): `K3EncryptionEnvironmentRunner.php` dipanggil
 *   via `proc_open()` PERSIS pola
 *   `K3EncryptionFallbackExplorationTest::jalankanRunnerSubprocess()`
 *   (method tersebut DISALIN ke kelas ini, TIDAK diekstrak ke trait/
 *   base class baru — mengikuti preseden `Klaster3PreservationTest`
 *   yang juga tidak memperkenalkan abstraksi lintas-file baru).
 *   `ENVIRONMENT`/`CI_DEBUG` adalah konstanta PHP `define()`-once
 *   (lihat docblock `K3EncryptionEnvironmentRunner.php` untuk
 *   penjelasan lengkap) — subprocess TERISOLASI tetap WAJIB dipakai
 *   di sini untuk alasan bahasa yang SAMA PERSIS dengan task 23,
 *   BUKAN kebutuhan baru.
 * - K3 observasi (b) MEMERLUKAN nilai `EULT_ENCRYPTION_LEGACY_KEY`
 *   KUSTOM non-hardcode pada subprocess — flag `K3_OVERRIDE_LEGACY_KEY`
 *   DITAMBAHKAN MINIMAL pada `K3EncryptionEnvironmentRunner.php` untuk
 *   task ini (lihat docblock flag tersebut untuk kuirk
 *   `variables_order=GPCS` yang SAMA PERSIS dengan yang
 *   didokumentasikan `K4DebugToolbarServerRunner.php`, dikonfirmasi
 *   berlaku juga pada `php` CLI biasa, bukan hanya `php -S`) — flag
 *   ini OPT-IN (default tidak diset), TIDAK mengubah SATU PUN
 *   assertion/perilaku `K3EncryptionFallbackExplorationTest.php` yang
 *   sudah ada (diverifikasi ulang via regression check, lihat laporan
 *   akhir task ini).
 * - K4 (observasi c, d): `K4DebugToolbarServerRunner.php` dipanggil
 *   PERSIS pola `K4DebugToolbarExplorationTest` — `start('development',
 *   null, $port)` (TANPA `$forcedCiDebugPrependPath`, BERBEDA dari
 *   task 24 yang menyimulasikan misconfiguration `CI_DEBUG` — observasi
 *   di sini murni behavior NORMAL `development.php` boot, `CI_DEBUG=true`
 *   GENUINE tanpa trik apa pun, sesuai instruksi task 25 poin "K4
 *   skenario 1"). `CURLOPT_FOLLOWLOCATION` TIDAK diaktifkan (alasan
 *   PERSIS sama, lihat docblock `K4DebugToolbarServerRunner.php`).
 *   TIDAK ADA modifikasi apa pun pada `K4DebugToolbarServerRunner.php`/
 *   `K4DebugToolbarExplorationTest.php`/`K4ForceCiDebugTruePrepend.php`
 *   untuk file ini.
 *
 * ═══════════════════════════════════════════════════════════════════
 * OBSERVASI d (K4 skenario 2) — CARA MEMVERIFIKASI FILTER
 * pagecache/performance TIDAK TERPENGARUH SECARA OBSERVABLE
 * ═══════════════════════════════════════════════════════════════════
 * Investigasi source (`vendor/codeigniter4/framework/system/Filters/
 * Filters.php::getRequiredFilters()`) mengonfirmasi CI4 SENGAJA
 * memanggil `setToolbarToLast()` pada kategori `required.after`,
 * memastikan `toolbar` SELALU dieksekusi PALING TERAKHIR di antara
 * `pagecache`, `performance`, `toolbar` (urutan `app/Config/
 * Filters.php::$required['after']` project ini: `['pagecache',
 * 'performance', 'toolbar']`) — artinya `PageCache::before()`/
 * `after()` dan `PerformanceMetrics::after()` SELALU berjalan
 * berdampingan dengan `toolbar` pada SETIAP request, TIDAK PERNAH
 * di-skip karena toolbar aktif atau sebaliknya.
 *
 * `PageCache::before()`
 * (`vendor/codeigniter4/framework/system/Filters/PageCache.php`)
 * memanggil `$this->pageCache->get($request, $response)` (cache lookup
 * dari handler `file`, `app/Config/Cache.php::$handler`) SEBELUM
 * controller/toolbar dijalankan sama sekali — TIDAK ADA exception yang
 * mungkin dilempar berdasarkan investigasi source (get() cache-miss
 * hanya mengembalikan `null`). `PageCache::after()` MENYIMPAN response
 * ke cache TANPA memandang apakah `toolbar` sudah menyuntikkan apa pun
 * (`$this->cacheStatusCodes === []` pada `app/Config/Cache.php`,
 * sehingga SEMUA status code termasuk 307 disimpan). Cara OBSERVABLE
 * paling langsung dan tidak spekulatif untuk memverifikasi filter ini
 * TIDAK TERGANGGU oleh `toolbar` yang aktif bersamaan (dan sebaliknya)
 * adalah: kirim DUA request GET berturut-turut ke path YANG SAMA pada
 * server test yang SAMA (request kedua akan melalui `PageCache::
 * before()` yang mencoba serve dari cache milik request pertama) →
 * assert KEDUA request tetap 307 (status tidak berubah akibat
 * pagecache/performance apa pun) DAN KEDUA body tetap mengandung
 * `debugbar_loader` (toolbar TETAP aktif pada request kedua, TIDAK
 * di-override/dihalangi oleh pagecache yang mencoba serve cache) —
 * dikonfirmasi EMPIRIS via reproduksi manual SEBELUM test ini ditulis
 * (dua request `curl` berturut ke server test `ENVIRONMENT=development`
 * genuine: KEDUANYA 307/28834 byte, KEDUANYA mengandung
 * `debugbar_loader`, TIDAK ADA baris STDERR error/exception apa pun
 * dari server test). Tidak ada 500/exception pada request mana pun
 * adalah bukti bahwa `PageCache`/`PerformanceMetrics` (yang BERJALAN
 * SEBELUM `toolbar` pada urutan `required.after` yang SAMA) tidak
 * crash akibat toolbar aktif bersamaan mereka.
 *
 * Requirements: 3.8, 3.9, 3.10, 3.11, 3.12 (bugfix.md)
 */
final class Klaster4PreservationTest extends CIUnitTestCase
{
    /** Nilai fallback hardcode literal — DIAMBIL LANGSUNG dari source Enkripsi.php:31 (BUKAN diketik ulang secara membuta), persis konstanta K3EncryptionFallbackExplorationTest. */
    private const HARDCODED_FALLBACK_KEY = 'SuPer_Enc-Key2010';

    /**
     * Nilai probe yang di-encode/decode subprocess untuk membuktikan
     * kunci efektif — HARUS PERSIS `'TIKET_PROBE_K3'`, KARENA
     * `K3EncryptionEnvironmentRunner.php` men-HARDCODE nilai probe ini
     * SECARA INTERNAL (`$nilaiProbe = 'TIKET_PROBE_K3';` pada script
     * runner, BUKAN parameter yang dapat dikustomisasi test pemanggil)
     * — TIDAK dapat diganti nilai lain tanpa memodifikasi runner itu
     * sendiri (di luar scope minimal task 25).
     */
    private const NILAI_PROBE = 'TIKET_PROBE_K3';

    /**
     * Kunci KUAT NON-HARDCODE (32+ karakter acak) untuk Skenario K3-b —
     * SENGAJA BUKAN `HARDCODED_FALLBACK_KEY` (persis instruksi task 25:
     * "misal string acak 32+ karakter, BUKAN 'SuPer_Enc-Key2010'").
     * Digenerate SEKALI sebagai konstanta (bukan random per-run) agar
     * hasil test reproducible/mudah dibaca pada kegagalan.
     */
    private const KUNCI_KUAT_NON_HARDCODE = 'Zx9qP2vLwK7mR4tN8sJ1cF6hB3yA5dQ0uE2gI7oW';

    private string $k3RunnerScriptPath;

    private K4DebugToolbarServerRunner $k4Runner;

    private const PORT_K4_DEVELOPMENT = 9621;

    /** Path file debugbar yang ditulis SELAMA test ini berjalan -- dibersihkan tearDown() agar tidak menumpuk residu permanen di writable/debugbar/ (persis pola K4DebugToolbarExplorationTest). */
    private array $debugbarFilesDitulisTest = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->k3RunnerScriptPath = __DIR__ . '/K3EncryptionEnvironmentRunner.php';
        $this->k4Runner            = new K4DebugToolbarServerRunner(dirname(__DIR__, 2));

        self::assertFileExists(
            $this->k3RunnerScriptPath,
            'Prasyarat test: runner subprocess K3EncryptionEnvironmentRunner.php harus ada di direktori yang sama (dipakai ulang dari task 23) -- lihat docblock file tersebut untuk kontrak input/output.'
        );
    }

    protected function tearDown(): void
    {
        $this->k4Runner->stop();

        foreach ($this->debugbarFilesDitulisTest as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    /**
     * Menjalankan runner subprocess K3 dengan environment variable
     * PROSES OS EKSPLISIT -- DISALIN PERSIS dari
     * `K3EncryptionFallbackExplorationTest::jalankanRunnerSubprocess()`
     * (lihat docblock kelas tersebut untuk penjelasan lengkap mengapa
     * subprocess terisolasi diperlukan dan mengapa array `$env`
     * eksplisit, bukan warisan shell induk).
     *
     * @param array<string, string> $envTambahan Variabel tambahan SELAIN PATH/HOME/CI_ENVIRONMENT (misal K3_OVERRIDE_LEGACY_KEY).
     *
     * @return array{stdout: string, stderr: string, exitCode: int, decoded: array<string, mixed>}
     */
    private function jalankanRunnerSubprocessK3(string $ciEnvironment, array $envTambahan = []): array
    {
        $env = array_merge(
            [
                'PATH'           => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
                'HOME'           => getenv('HOME') ?: '/tmp',
                'CI_ENVIRONMENT' => $ciEnvironment,
            ],
            $envTambahan
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proses = proc_open(
            ['php', $this->k3RunnerScriptPath],
            $descriptorSpec,
            $pipes,
            dirname($this->k3RunnerScriptPath),
            $env
        );

        self::assertIsResource($proses, 'proc_open() gagal membuat subprocess runner K3 -- prasyarat mekanisme test ini tidak terpenuhi.');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proses);

        self::assertSame(
            '',
            trim($stderr),
            sprintf("Runner subprocess K3 menulis ke STDERR (mengindikasikan error mekanisme test) -- kemungkinan fatal error/exception tidak tertangani:\nSTDERR:\n%s\nSTDOUT:\n%s", $stderr, $stdout)
        );
        self::assertSame(0, $exitCode, sprintf('Runner subprocess K3 exit code non-zero (%d). STDOUT: %s', $exitCode, $stdout));

        $decoded = json_decode(trim($stdout), true);
        self::assertIsArray($decoded, sprintf('Runner subprocess K3 tidak mengembalikan JSON valid di STDOUT. STDOUT mentah: %s', $stdout));

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exitCode' => $exitCode, 'decoded' => $decoded];
    }

    /**
     * Mengirim GET ke server test K4 -- DISALIN PERSIS dari
     * `K4DebugToolbarExplorationTest::getKeServerTest()` (lihat
     * docblock kelas tersebut untuk alasan `CURLOPT_FOLLOWLOCATION`
     * TIDAK diaktifkan).
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function getKeServerTestK4(string $baseUrl, string $path): array
    {
        $ch = curl_init($baseUrl . $path);

        $headerMentah = [];

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => false, // KRITIS -- lihat docblock K4DebugToolbarServerRunner.php.
            CURLOPT_HEADERFUNCTION => static function ($curl, $baris) use (&$headerMentah) {
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
     * Property 2 (Preservation) — OBSERVASI a: K3 Skenario 1.
     * `ENVIRONMENT=development` dengan `EULT_ENCRYPTION_LEGACY_KEY`
     * KOSONG (bugfix.md 3.8) — fallback hardcode SHALL CONTINUE TO
     * aktif tanpa peringatan blocking apa pun, agar workflow
     * development tidak terganggu. Penghapusan fallback K3 (task 26.1)
     * HANYA berlaku untuk jalur production -- baseline development ini
     * TIDAK BOLEH berubah pasca-fix.
     */
    public function testK3Skenario1DevelopmentDenganEnvKeyKosongFallbackTetapAktifTanpaWarning(): void
    {
        $markerUnik = 'K4PRES_K3S1_MARKER_' . bin2hex(random_bytes(8));

        $hasil = $this->jalankanRunnerSubprocessK3('development', [
            'K3_FORCE_EMPTY_LEGACY_KEY' => '1',
            'K3_LOG_MARKER'             => $markerUnik,
        ]);
        $decoded = $hasil['decoded'];

        self::assertSame(
            'development',
            $decoded['environment'],
            'Prasyarat observasi K3-a: subprocess SHALL benar-benar boot dengan ENVIRONMENT="development".'
        );
        self::assertNull(
            $decoded['envKeyRawValue'],
            'Prasyarat observasi K3-a: EULT_ENCRYPTION_LEGACY_KEY SHALL kosong/tidak terset pada subprocess (K3_FORCE_EMPTY_LEGACY_KEY=1) -- tanpa ini skenario "development dengan .env kosong" tidak teruji.'
        );
        self::assertNotFalse($decoded['encoded'], 'Prasyarat: Enkripsi::encode() SHALL menghasilkan ciphertext non-false pada kode belum diperbaiki.');

        // *** BASELINE PRESERVASI (bugfix.md 3.8) ***
        // Fallback hardcode SHALL CONTINUE TO dipakai sebagai kunci
        // efektif di development ketika env key kosong -- decode()
        // dengan kunci literal fallback SHALL berhasil mengembalikan
        // nilai probe semula.
        self::assertSame(
            self::NILAI_PROBE,
            $decoded['decodedWithLiteralHardcodedKey'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.8): pada ENVIRONMENT=development dengan EULT_ENCRYPTION_LEGACY_KEY kosong, fallback hardcode SHALL CONTINUE TO dipakai sebagai kunci efektif Enkripsi::encode() -- baseline ini WAJIB dipertahankan identik pasca-fix K3 (task 26.1), karena penghapusan fallback HANYA berlaku untuk ENVIRONMENT=production, agar workflow development tidak terganggu.'
        );

        // *** BASELINE PRESERVASI (bugfix.md 3.8, "tanpa peringatan") ***
        // TIDAK ADA log/warning apa pun SHALL tercatat untuk kondisi
        // development ini -- baseline non-bug yang tidak boleh berubah
        // menjadi noise pasca-fix (fix K3 26.1 HANYA log critical untuk
        // ENVIRONMENT=production dengan kunci identik fallback, BUKAN
        // untuk development apa pun).
        self::assertFalse(
            $decoded['logFileContainsMarker'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.8): pada ENVIRONMENT=development dengan EULT_ENCRYPTION_LEGACY_KEY kosong, TIDAK ADA baris log mengandung marker unik ditemukan -- baseline ini WAJIB dipertahankan pasca-fix K3 (task 26.1 HANYA menambahkan log critical untuk kondisi production, TIDAK untuk development apa pun), agar workflow development tidak menerima peringatan blocking yang tidak perlu.'
        );
    }

    /**
     * Property 2 (Preservation) — OBSERVASI b: K3 Skenario 2.
     * `ENVIRONMENT=production` dengan `EULT_ENCRYPTION_LEGACY_KEY`
     * diisi nilai KUAT NON-HARDCODE (bugfix.md 3.9) — nilai tersebut
     * SHALL CONTINUE TO dipakai sebagai kunci efektif DAN SHALL TIDAK
     * menampilkan peringatan apa pun (peringatan HANYA muncul ketika
     * kunci efektif == fallback hardcode, BUKAN untuk SEMUA kunci
     * non-development -- baseline yang harus dipertahankan pasca-fix
     * 26.1, agar operator yang SUDAH benar mengisi kunci kuat TIDAK
     * mendapat warning yang tidak perlu).
     */
    public function testK3Skenario2ProductionDenganKunciKuatNonHardcodeDipakaiTanpaWarning(): void
    {
        $markerUnik = 'K4PRES_K3S2_MARKER_' . bin2hex(random_bytes(8));

        $hasil = $this->jalankanRunnerSubprocessK3('production', [
            'K3_OVERRIDE_LEGACY_KEY' => self::KUNCI_KUAT_NON_HARDCODE,
            'K3_LOG_MARKER'          => $markerUnik,
        ]);
        $decoded = $hasil['decoded'];

        self::assertSame(
            'production',
            $decoded['environment'],
            'Prasyarat observasi K3-b: subprocess SHALL benar-benar boot dengan ENVIRONMENT="production".'
        );
        self::assertSame(
            self::KUNCI_KUAT_NON_HARDCODE,
            $decoded['envKeyRawValue'],
            'Prasyarat observasi K3-b: EULT_ENCRYPTION_LEGACY_KEY pada subprocess SHALL benar-benar bernilai kunci kuat non-hardcode kustom (via flag K3_OVERRIDE_LEGACY_KEY, ditambahkan task 25 -- lihat docblock K3EncryptionEnvironmentRunner.php) -- tanpa ini skenario "operator sudah mengisi kunci kuat" tidak teruji.'
        );
        self::assertNotSame(
            self::HARDCODED_FALLBACK_KEY,
            $decoded['envKeyRawValue'],
            'Sanity eksplisit: kunci kuat kustom SHALL BUKAN identik fallback hardcode (prasyarat literal instruksi task 25 -- "BUKAN identik fallback").'
        );

        // *** BASELINE PRESERVASI (bugfix.md 3.9) ***
        // Kunci kuat non-hardcode SHALL CONTINUE TO dipakai sebagai
        // kunci efektif -- decode() dengan kunci tersebut SHALL
        // berhasil mengembalikan nilai probe semula.
        self::assertSame(
            self::NILAI_PROBE,
            $decoded['decodedWithEnvKeyIfPresent'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.9): pada ENVIRONMENT=production dengan EULT_ENCRYPTION_LEGACY_KEY diisi kunci kuat non-hardcode oleh operator, kunci tersebut SHALL CONTINUE TO dipakai sebagai kunci efektif Enkripsi::encode() -- baseline ini WAJIB dipertahankan identik pasca-fix K3 (task 26.1 TIDAK memblokir/mengganti kunci non-hardcode apa pun yang sudah diisi operator).'
        );

        // Bukti tambahan: kunci literal fallback hardcode SHALL GAGAL
        // decode ciphertext ini (membuktikan kunci efektif encode()
        // BUKAN fallback hardcode, melainkan benar-benar kunci kustom
        // di atas -- observasi tidak langsung yang sama presisinya
        // dengan K3EncryptionFallbackExplorationTest).
        self::assertNotSame(
            self::NILAI_PROBE,
            $decoded['decodedWithLiteralHardcodedKey'],
            'Sanity tambahan observasi K3-b: decode() dengan kunci LITERAL fallback hardcode SHALL GAGAL mengembalikan nilai probe pada ciphertext ini -- membuktikan kunci efektif encode() benar-benar kunci kustom (bukan fallback hardcode), sehingga observasi baseline di atas bermakna secara faktual.'
        );

        // *** BASELINE PRESERVASI (bugfix.md 3.9, "tidak menampilkan
        // peringatan apa pun") ***
        // TIDAK ADA log/warning apa pun SHALL tercatat -- baseline
        // non-bug yang harus dipertahankan pasca-fix (fix K3 26.1
        // HANYA log critical ketika kunci efektif == fallback
        // hardcode, BUKAN untuk kunci non-hardcode APAPUN termasuk
        // kunci kuat kustom ini).
        self::assertFalse(
            $decoded['logFileContainsMarker'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.9): pada ENVIRONMENT=production dengan kunci kuat non-hardcode, TIDAK ADA baris log mengandung marker unik ditemukan -- baseline ini WAJIB dipertahankan pasca-fix K3 (task 26.1 HANYA log critical untuk kondisi kunci efektif IDENTIK fallback hardcode, BUKAN untuk kunci non-hardcode apa pun), agar operator yang sudah benar mengisi kunci kuat tidak mendapat warning yang tidak perlu.'
        );
    }

    /**
     * Property 2 (Preservation) — OBSERVASI c: K4 Skenario 1.
     * `ENVIRONMENT=development` (bugfix.md 3.11) — toolbar debug SHALL
     * CONTINUE TO aktif dan menyajikan informasi debug seperti biasa,
     * agar workflow development tidak terganggu. Reuse LANGSUNG
     * `K4DebugToolbarServerRunner` dengan `$forcedCiDebugPrependPath =
     * null` (TANPA simulasi CI_DEBUG override apa pun -- murni behavior
     * NORMAL `development.php` boot, `CI_DEBUG=true` genuine, BERBEDA
     * dari task 24 yang menyimulasikan misconfiguration).
     */
    public function testK4Skenario1DevelopmentToolbarAktifPenuhDenganFileDebugTertulis(): void
    {
        $this->k4Runner->start('development', null, self::PORT_K4_DEVELOPMENT);

        $hasil = $this->getKeServerTestK4($this->k4Runner->baseUrl(), 'login');

        self::assertSame(
            307,
            $hasil['status'],
            'Prasyarat: server test SHALL mengembalikan 307 (ForceHTTPS) untuk request HTTP polos, sama seperti seluruh server test K4 lain di direktori ini.'
        );

        // *** BASELINE PRESERVASI (bugfix.md 3.11) ***
        // Toolbar SHALL CONTINUE TO aktif penuh (script loader tersuntik)
        // pada ENVIRONMENT=development GENUINE tanpa override apa pun.
        self::assertStringContainsString(
            'debugbar_loader',
            $hasil['body'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.11): pada ENVIRONMENT=development GENUINE (tanpa simulasi CI_DEBUG override apa pun -- murni boot NORMAL development.php), Toolbar::prepare() SHALL CONTINUE TO menyuntikkan script loader "<script id=\"debugbar_loader\">" ke body response -- baseline ini WAJIB dipertahankan identik pasca-fix K4 (task 27.1, EnvironmentAwareToolbar), agar developer lokal tidak kehilangan toolbar.'
        );

        $dataTimeMatches = [];
        preg_match('/data-time="([^"]+)"/', $hasil['body'], $dataTimeMatches);

        self::assertArrayHasKey(
            1,
            $dataTimeMatches,
            'Prasyarat observasi K4-c: response SHALL menghasilkan atribut data-time (toolbar aktif) -- tanpa ini, keberadaan file debugbar tidak dapat diverifikasi.'
        );

        $pathFileDebugbar = dirname(__DIR__, 2) . '/writable/debugbar/debugbar_' . $dataTimeMatches[1] . '.json';
        $this->debugbarFilesDitulisTest[] = $pathFileDebugbar;

        // *** BASELINE PRESERVASI (bugfix.md 3.11, "file debug
        // tersimpan di writable/debugbar/*.json") ***
        self::assertFileExists(
            $pathFileDebugbar,
            sprintf(
                'PRESERVATION BASELINE Klaster 4 (Requirement 3.11): file debug "%s" SHALL CONTINUE TO tertulis ke writable/debugbar/ pada ENVIRONMENT=development GENUINE -- baseline ini WAJIB dipertahankan pasca-fix K4 (task 27.1 TIDAK mengubah mekanisme penyimpanan writable/debugbar/*.json, hanya syarat aktivasi).',
                basename($pathFileDebugbar)
            )
        );

        $isiDebugbar = json_decode((string) file_get_contents($pathFileDebugbar), true);

        self::assertIsArray($isiDebugbar, 'Prasyarat: file debugbar SHALL berisi JSON valid.');
        self::assertSame(
            'development',
            $isiDebugbar['config']['environment'] ?? null,
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.11): field config.environment pada file debugbar SHALL "development" -- mengonfirmasi ini BUKAN kesalahan resolusi ENVIRONMENT mekanisme test (sanity yang sama dipakai K4DebugToolbarExplorationTest untuk membuktikan environment genuine, bukan residu .env fisik project).'
        );
    }

    /**
     * Property 2 (Preservation) — OBSERVASI d: K4 Skenario 2.
     * Filter `pagecache`/`performance` (kategori `required.after` SAMA
     * dengan `toolbar`, bugfix.md 3.12) — TIDAK TERPENGARUH oleh
     * toolbar yang aktif bersamaan, dan sebaliknya. Dijalankan pada
     * SERVER YANG SAMA dengan observasi c (development, tanpa restart)
     * -- dua request GET berturut-turut ke path yang sama, request
     * kedua akan melalui `PageCache::before()` yang mencoba serve dari
     * cache milik request pertama (lihat docblock kelas untuk investigasi
     * lengkap `Filters::getRequiredFilters()`/`setToolbarToLast()` yang
     * menjamin `pagecache`+`performance` SELALU berjalan berdampingan
     * `toolbar` pada urutan `required.after` yang sama).
     */
    public function testK4Skenario2FilterPagecachePerformanceTidakTerpengaruhToolbarAktif(): void
    {
        $this->k4Runner->start('development', null, self::PORT_K4_DEVELOPMENT + 1);

        $hasilPertama = $this->getKeServerTestK4($this->k4Runner->baseUrl(), 'login');
        $hasilKedua   = $this->getKeServerTestK4($this->k4Runner->baseUrl(), 'login');

        foreach (['requestPertama' => $hasilPertama, 'requestKedua' => $hasilKedua] as $label => $hasil) {
            $dataTimeMatches = [];
            preg_match('/data-time="([^"]+)"/', $hasil['body'], $dataTimeMatches);

            if (isset($dataTimeMatches[1])) {
                $this->debugbarFilesDitulisTest[] = dirname(__DIR__, 2) . '/writable/debugbar/debugbar_' . $dataTimeMatches[1] . '.json';
            }

            // *** BASELINE PRESERVASI (bugfix.md 3.12) ***
            // Status code SHALL tetap konsisten (307) pada KEDUA
            // request -- filter pagecache/performance SHALL TIDAK
            // mengubah status akibat toolbar aktif bersamaan mereka
            // (tidak ada 500/exception yang terjadi).
            self::assertSame(
                307,
                $hasil['status'],
                sprintf(
                    'PRESERVATION BASELINE Klaster 4 (Requirement 3.12), %s: status response SHALL tetap 307 (ForceHTTPS) meski filter pagecache/performance (kategori required.after SAMA dengan toolbar) berjalan berdampingan toolbar yang aktif -- perubahan pada K4 SHALL TIDAK mengubah perilaku filter-filter tersebut.',
                    $label
                )
            );

            // *** BASELINE PRESERVASI (bugfix.md 3.12) ***
            // Toolbar SHALL TETAP aktif pada KEDUA request (termasuk
            // request kedua yang melalui PageCache::before() mencoba
            // serve cache milik request pertama) -- membuktikan
            // pagecache/performance TIDAK menghalangi/mengganti
            // aktivasi toolbar, dan toolbar TIDAK menyebabkan
            // pagecache/performance crash.
            self::assertStringContainsString(
                'debugbar_loader',
                $hasil['body'],
                sprintf(
                    'PRESERVATION BASELINE Klaster 4 (Requirement 3.12), %s: body SHALL tetap mengandung script loader toolbar meski filter pagecache (yang mencoba serve/menyimpan cache pada urutan required.after yang sama) berjalan berdampingan -- membuktikan filter pagecache/performance TIDAK terpengaruh oleh toolbar aktif, dan toolbar TIDAK terpengaruh oleh pagecache/performance yang berjalan lebih dulu dalam urutan filter (Filters::setToolbarToLast() menjamin toolbar dieksekusi terakhir pada kategori after yang sama).',
                    $label
                )
            );
        }

        self::assertSame(
            $hasilPertama['status'],
            $hasilKedua['status'],
            'PRESERVATION BASELINE Klaster 4 (Requirement 3.12): status code request pertama dan kedua SHALL identik -- filter pagecache (yang menyimpan/melihat cache di antara kedua request ini) SHALL TIDAK mengubah perilaku response akibat toolbar aktif bersamaan.'
        );
    }
}
