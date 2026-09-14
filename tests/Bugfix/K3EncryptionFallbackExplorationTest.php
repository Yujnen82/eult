<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 23 (Klaster 4 — K3): Test EKSPLORASI bug condition Kunci Enkripsi
 * Tiket Fallback Hardcode Aktif Tanpa Syarat di Production (bugfix.md
 * 1.9, 1.10, 1.11; design.md bagian "K3 — Kunci Enkripsi Fallback
 * Hardcode di Production").
 *
 * PENTING — metodologi bug condition:
 * Test ini WAJIB GAGAL pada kode yang belum diperbaiki. Kegagalan test
 * mengonfirmasi bug ada (Property 1: Bug Condition). Test ini TIDAK
 * BOLEH diperbaiki di sini — dia mengenkode expected/fixed behavior dan
 * akan berubah menjadi LULUS setelah fix K3 diimplementasikan (task
 * 26.1, yang menambahkan pengecekan `ENVIRONMENT === 'production'` pada
 * `Enkripsi::kunciLegacy()` — lihat design.md/tasks.md).
 *
 * GOAL: Surface counterexample bahwa `Enkripsi::kunciLegacy()`
 * (`app/Libraries/Enkripsi.php:24-31`) mengembalikan fallback hardcode
 * `'SuPer_Enc-Key2010'` TANPA MEMANDANG `ENVIRONMENT`, DAN bahwa tidak
 * ada mekanisme log/warning apa pun ketika kunci efektif production
 * identik dengan fallback tersebut — kode SAAT INI tidak memiliki
 * pengecekan `ENVIRONMENT` apa pun di dalam method ini sama sekali
 * (diverifikasi langsung dari source sebelum menulis test ini).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEPUTUSAN ARSITEKTUR TEST — MENGAPA SUBPROCESS TERPISAH (proc_open),
 * BUKAN in-process/Reflection/redefine konstanta
 * ═══════════════════════════════════════════════════════════════════
 *
 * `ENVIRONMENT` adalah KONSTANTA PHP yang di-`define()` SEKALI sebagai
 * `'development'` di `tests/bootstrap.php`, SEBELUM PHPUnit boot sama
 * sekali (lihat docblock file tersebut — nilai ini SENGAJA dipilih agar
 * `Config\Database::defaultGroup` tetap MySQL nyata, bukan SQLite
 * `'tests'`). Konstanta PHP bersifat FINAL: memanggil
 * `define('ENVIRONMENT', 'production')` LAGI di tengah proses PHPUnit
 * yang sama akan FATAL ERROR ("Constant ENVIRONMENT already defined"),
 * BUKAN sekadar exception yang dapat ditangkap — ini dikonfirmasi
 * bukan hanya teoretis, melainkan batasan bahasa PHP itu sendiri.
 *
 * Investigasi dilakukan untuk mencari cara CI4-idiomatic mengubah
 * "environment efektif" tanpa redefine konstanta (misal parameter
 * injection pada method yang ditest, atau layer abstraksi
 * `Config\Optimize`/testing helper) — TIDAK DITEMUKAN mekanisme
 * semacam itu di codebase ini maupun CI4 sendiri, KARENA
 * `Enkripsi::kunciLegacy()` memang TIDAK menerima parameter environment
 * apa pun (itulah esensi bug K3: TIDAK ADA percabangan logika
 * berdasarkan environment sama sekali pada method ini). Menguji
 * "apakah method ini berperilaku sama di semua environment" TIDAK
 * dapat dilakukan dengan memalsukan nilai `ENVIRONMENT` secara
 * in-process — nilai tersebut harus BENAR-BENAR `production` pada
 * proses yang menjalankan kode, agar assertion test ini bermakna
 * secara faktual (bukan simulasi yang bisa salah merepresentasikan
 * kondisi nyata).
 *
 * Satu-satunya cara valid mendapatkan konstanta `ENVIRONMENT` BERBEDA
 * adalah menjalankan PROSES PHP BARU yang belum pernah men-define
 * konstanta tersebut. Ini analog dengan preseden Klaster 1-3
 * (cURL-ke-server-live pada `K1SqlInjectionHttpIntegrationTest`,
 * `T1CsrfAjaxGracefulHandlerHttpIntegrationTest`, dll — SecurityException
 * yang hanya bermakna pada proses server sungguhan) — NAMUN task 23
 * TIDAK memerlukan server HTTP penuh (`Enkripsi` tidak menyentuh
 * routing/filter/dispatch apa pun, ia adalah LIBRARY MURNI + `env()`
 * global + `log_message()` service), sehingga pendekatan yang dipilih
 * di sini adalah SUBPROCESS CLI RINGAN (`proc_open()` menjalankan
 * `K3EncryptionEnvironmentRunner.php` — lihat docblock file tersebut
 * untuk detail kontrak input/output) — LEBIH RINGAN dari HTTP server
 * (tidak perlu membuka port network/menunggu server siap), namun tetap
 * BENAR-BENAR menjalankan proses PHP terpisah dengan
 * `CI_ENVIRONMENT=production` sebagai environment variable PROSES OS
 * tersebut (bukan simulasi), sehingga `CodeIgniter\Boot::bootConsole()`
 * yang dijalankan subprocess tersebut BENAR-BENAR men-define
 * `ENVIRONMENT` sebagai `'production'` pada proses barunya — valid
 * dan tanpa fatal error, karena terjadi pada proses yang belum pernah
 * men-define konstanta itu sebelumnya.
 *
 * Isolasi environment variable subprocess dilakukan via array `$env`
 * EKSPLISIT pada `proc_open()` (BUKAN warisan `getenv()`/shell induk
 * secara implisit) — investigasi mengonfirmasi shell environment proses
 * ini SUDAH memiliki `EULT_ENCRYPTION_LEGACY_KEY=SuPer_Enc-Key2010`
 * ter-export (bukan dari `.env` project, tapi dari environment OS induk
 * itu sendiri), sehingga TANPA isolasi eksplisit, skenario "variabel
 * tidak terset" (Skenario A) tidak dapat direproduksi secara valid.
 * `proc_open()` dengan parameter `$env` array TIDAK mewarisi environment
 * proses induk secara default — ini dikonfirmasi via eksperimen manual
 * sebelum test ini ditulis (`getenv()` pada subprocess mengembalikan
 * `false` untuk variabel yang sengaja tidak disertakan array `$env`).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEPUTUSAN ARSITEKTUR TEST — MENGAPA PENGUJIAN VIA method PUBLIC
 * (encode()/decode()), BUKAN Reflection pada kunciLegacy() private
 * ═══════════════════════════════════════════════════════════════════
 *
 * `kunciLegacy()` adalah method PRIVATE. Mengikuti pola blackbox yang
 * KONSISTEN dipakai test lain di direktori ini (`T3CookieCaptchaBypass
 * ExplorationTest` tidak pernah memakai Reflection untuk mengakses
 * internal `eult_captcha_check()`, cukup observasi behavior via
 * pemanggilan fungsi publik), test ini menguji `kunciLegacy()` SECARA
 * TIDAK LANGSUNG melalui `Enkripsi::encode()` (method public yang
 * memanggilnya via `kunciMentah('')` ketika parameter `$kunci` kosong).
 *
 * Observasi LANGSUNG output `encode()` TIDAK cukup untuk membuktikan
 * kunci efektif PERSIS `'SuPer_Enc-Key2010'` (karena `encode()`
 * meng-HKDF-derive kunci enkripsi/HMAC lagi dari kunci mentah — output
 * ciphertext tidak secara visual menunjukkan kunci mentah apa yang
 * dipakai). Solusi: `decode()` HASIL `encode()` tersebut memakai kunci
 * LITERAL `'SuPer_Enc-Key2010'` sebagai PARAMETER EKSPLISIT kedua
 * (`decode($encoded, 'SuPer_Enc-Key2010')`). Karena `decode()` menerima
 * kunci parameter dan men-derive HKDF yang SAMA PERSIS dengan `encode()`
 * (kode identik, `hkdf($mentah, ...)`), BERHASIL-nya `decode()` ini
 * (mengembalikan nilai probe semula) adalah BUKTI TIDAK LANGSUNG namun
 * KONKLUSIF bahwa `kunciLegacy()` benar-benar mengembalikan string
 * literal tersebut sebagai kunci mentah efektif — tanpa perlu
 * Reflection pada method private.
 *
 * Requirements: 1.9, 1.10, 1.11 (bugfix.md)
 */
final class K3EncryptionFallbackExplorationTest extends CIUnitTestCase
{
    /** Nilai fallback hardcode literal — DIAMBIL LANGSUNG dari source Enkripsi.php:31 (BUKAN diketik ulang secara membuta), dipakai sebagai kunci pembanding eksplisit pada decode(). */
    private const HARDCODED_FALLBACK_KEY = 'SuPer_Enc-Key2010';

    /** Nilai probe yang di-encode/decode subprocess untuk membuktikan kunci efektif. */
    private const NILAI_PROBE = 'TIKET_PROBE_K3';

    private string $runnerScriptPath;

    /** Kumpulan path file log yang PERLU dibersihkan markernya (untuk isolasi, TIDAK menghapus seluruh file — hanya bagian yang ditambahkan test ini, lihat tearDown()). */
    private array $logMarkersDitulisPadaRun = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->runnerScriptPath = __DIR__ . '/K3EncryptionEnvironmentRunner.php';

        self::assertFileExists(
            $this->runnerScriptPath,
            'Prasyarat test: runner subprocess K3EncryptionEnvironmentRunner.php harus ada di direktori yang sama — lihat docblock file tersebut untuk kontrak input/output yang dipakai test ini.'
        );
    }

    /**
     * Menjalankan runner subprocess dengan environment variable PROSES
     * OS EKSPLISIT (array `$env` — TIDAK mewarisi shell/proses induk
     * secara implisit, lihat docblock kelas untuk alasan isolasi ini).
     *
     * @param array<string, string> $envTambahan Variabel tambahan SELAIN PATH/HOME/CI_ENVIRONMENT (misal K3_FORCE_EMPTY_LEGACY_KEY, K3_LOG_MARKER).
     *
     * @return array{stdout: string, stderr: string, exitCode: int, decoded: array<string, mixed>}
     */
    private function jalankanRunnerSubprocess(string $ciEnvironment, array $envTambahan = []): array
    {
        $env = array_merge(
            [
                // PATH/HOME tetap diwariskan (dibutuhkan `php` binary
                // resolve dan beberapa operasi filesystem CI4) — hanya
                // variabel APLIKASI (EULT_*, dll) yang diisolasi secara
                // eksplisit per-skenario di bawah, BUKAN warisan shell.
                'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
                'HOME' => getenv('HOME') ?: '/tmp',
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
            ['php', $this->runnerScriptPath],
            $descriptorSpec,
            $pipes,
            dirname($this->runnerScriptPath),
            $env
        );

        self::assertIsResource($proses, 'proc_open() gagal membuat subprocess runner — prasyarat mekanisme test ini tidak terpenuhi (bukan bagian dari bug K3 yang diuji).');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proses);

        self::assertSame(
            '',
            trim($stderr),
            sprintf(
                "Runner subprocess menulis ke STDERR (mengindikasikan error mekanisme test, BUKAN bagian dari bug K3 yang diuji) — kemungkinan fatal error/exception tidak tertangani pada subprocess:\nSTDERR:\n%s\nSTDOUT:\n%s",
                $stderr,
                $stdout
            )
        );
        self::assertSame(0, $exitCode, sprintf('Runner subprocess exit code non-zero (%d) — prasyarat mekanisme test tidak terpenuhi. STDOUT: %s', $exitCode, $stdout));

        $decoded = json_decode(trim($stdout), true);
        self::assertIsArray($decoded, sprintf('Runner subprocess tidak mengembalikan JSON valid di STDOUT. STDOUT mentah: %s', $stdout));

        return ['stdout' => $stdout, 'stderr' => $stderr, 'exitCode' => $exitCode, 'decoded' => $decoded];
    }

    /**
     * Sanity: pastikan mekanisme subprocess (isolasi env, boot CI4
     * penuh via bootConsole(), pemanggilan Enkripsi) BENAR-BENAR
     * berjalan dan mengembalikan ENVIRONMENT sesuai yang diminta,
     * SEBELUM menyimpulkan apa pun tentang bug K3 itu sendiri.
     */
    public function testSanitySubprocessBenarBenarBootDenganEnvironmentProduction(): void
    {
        $hasil = $this->jalankanRunnerSubprocess('production', ['K3_FORCE_EMPTY_LEGACY_KEY' => '1']);

        self::assertSame(
            'production',
            $hasil['decoded']['environment'],
            'Prasyarat test: subprocess HARUS benar-benar boot dengan konstanta ENVIRONMENT === "production" — bila ini gagal, mekanisme isolasi proses itu sendiri bermasalah (bukan bug K3).'
        );
    }

    /**
     * Sanity: pastikan skenario "K3_FORCE_EMPTY_LEGACY_KEY=1" BENAR-BENAR
     * membuat env('EULT_ENCRYPTION_LEGACY_KEY') kosong/tidak ada pada
     * subprocess, MESKI file .env FISIK project mengisi variabel
     * tersebut (lihat TEMUAN KRITIS TAMBAHAN pada docblock runner) —
     * tanpa sanity ini, Skenario A di bawah tidak bermakna.
     */
    public function testSanityForceEmptyLegacyKeyBenarBenarMengosongkanEnvKeyMeskipunEnvFisikProjectMengisi(): void
    {
        $hasil = $this->jalankanRunnerSubprocess('production', ['K3_FORCE_EMPTY_LEGACY_KEY' => '1']);

        self::assertNull(
            $hasil['decoded']['envKeyRawValue'],
            'Prasyarat Skenario A: env("EULT_ENCRYPTION_LEGACY_KEY") HARUS null/kosong pada subprocess ketika K3_FORCE_EMPTY_LEGACY_KEY=1 diset — bila masih terisi (misal karena residu file .env fisik project yang mengisi variabel ini), Skenario A tidak menguji kondisi yang benar.'
        );
    }

    /**
     * Property 1 (Bug Condition) — SKENARIO A task 23:
     * `ENVIRONMENT=production` + `EULT_ENCRYPTION_LEGACY_KEY` KOSONG.
     *
     * EXPECTED/FIXED behavior (setelah fix K3, task 26.1): kunci efektif
     * di production SHALL TIDAK PERNAH menjadi fallback hardcode
     * `'SuPer_Enc-Key2010'` — operasi kriptografi SHALL gagal terkontrol
     * (`decode()` dengan kunci literal fallback SEHARUSNYA GAGAL
     * mengembalikan nilai probe, karena `encode()` production seharusnya
     * TIDAK memakai kunci itu lagi sama sekali).
     *
     * *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
     * Pada kode belum diperbaiki, `kunciLegacy()` TIDAK memeriksa
     * `ENVIRONMENT` apa pun — fallback hardcode SELALU dikembalikan
     * ketika env key kosong, TANPA MEMANDANG environment. Assertion
     * `assertNotSame`/`assertFalse` di bawah akan GAGAL pada kode asli,
     * membuktikan fallback aktif tanpa syarat di production.
     */
    public function testFallbackHardcodeAktifTanpaSyaratDiProductionKetikaEnvKeyKosong(): void
    {
        $hasil = $this->jalankanRunnerSubprocess('production', ['K3_FORCE_EMPTY_LEGACY_KEY' => '1']);
        $decoded = $hasil['decoded'];

        // Sanity eksplisit tambahan: pastikan encode() BENAR-BENAR
        // menghasilkan ciphertext (operasi tidak diam-diam gagal karena
        // sebab lain di luar scope K3, misal ekstensi OpenSSL absen).
        self::assertNotFalse($decoded['encoded'], 'Prasyarat counterexample: Enkripsi::encode() harus menghasilkan ciphertext non-false pada kode asli (kegagalan encode() di luar scope bug K3 akan membuat assertion di bawah tidak bermakna).');
        self::assertIsString($decoded['encoded']);

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior (pasca fix K3): decode() dengan kunci
        // LITERAL fallback hardcode SEHARUSNYA GAGAL (false) di production
        // ketika env key kosong -- karena encode() production seharusnya
        // TIDAK PERNAH memakai kunci itu lagi. Pada kode asli, decode()
        // BERHASIL mengembalikan nilai probe semula, membuktikan
        // kunciLegacy() memakai fallback hardcode sebagai kunci efektif
        // TANPA MEMANDANG ENVIRONMENT === 'production'.
        self::assertNotSame(
            self::NILAI_PROBE,
            $decoded['decodedWithLiteralHardcodedKey'],
            sprintf(
                'BUG CONDITION K3 TERKONFIRMASI: pada ENVIRONMENT=production dengan EULT_ENCRYPTION_LEGACY_KEY KOSONG/tidak terset, Enkripsi::encode() (yang secara internal memanggil kunciLegacy() via kunciMentah("")) TETAP menghasilkan ciphertext yang berhasil di-decode() ulang memakai kunci LITERAL hardcode ("%s") menjadi nilai probe semula ("%s") — decodedWithLiteralHardcodedKey="%s". Ini membuktikan kunciLegacy() (app/Libraries/Enkripsi.php:24-31) mengembalikan fallback hardcode SEBAGAI KUNCI EFEKTIF di production, TANPA MEMANDANG ENVIRONMENT sama sekali (tidak ada pengecekan environment apa pun pada method tersebut saat ini) — persis bugfix.md 1.9.',
                self::HARDCODED_FALLBACK_KEY,
                self::NILAI_PROBE,
                var_export($decoded['decodedWithLiteralHardcodedKey'], true)
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — SKENARIO B task 23:
     * `ENVIRONMENT=production` + `EULT_ENCRYPTION_LEGACY_KEY=SuPer_Enc-Key2010`
     * (identik fallback hardcode — KEBETULAN ini SAMA DENGAN kondisi
     * `.env` project SAAT INI, namun disimulasikan TERISOLASI di sini
     * via subprocess dengan ENVIRONMENT=production sungguhan, BUKAN
     * bergantung pada `.env` project yang ENVIRONMENT-nya `development`).
     *
     * EXPECTED/FIXED behavior (setelah fix K3, task 26.1): kondisi ini
     * SHALL menghasilkan `log_message('critical', ...)` sebagai
     * defense-in-depth (bugfix.md 2.12) — operator diberi sinyal bahwa
     * kunci production masih sama dengan nilai default/hardcode.
     *
     * *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
     * Pada kode belum diperbaiki, TIDAK ADA mekanisme log/warning/
     * exception apa pun pada `kunciLegacy()` — assertion `assertTrue`
     * di bawah (mengharapkan pesan log critical statis ditemukan) akan
     * GAGAL pada kode asli, membuktikan tidak ada sinyal apa pun ke
     * operator.
     *
     * ═══════════════════════════════════════════════════════════════
     * REVISI MEKANISME DETEKSI (pasca task 26.1) — nama parameter
     * DIPERTAHANKAN, substansi deteksi DIGANTI:
     * ═══════════════════════════════════════════════════════════════
     * Docblock kelas ini dan `K3EncryptionEnvironmentRunner.php`
     * SEBELUM task 26.1 diimplementasikan mengasumsikan fix akan
     * menyisipkan `$markerUnik` ini KE DALAM argumen `log_message()`
     * agar setiap run test dapat dibedakan dari residu proses lain.
     * Asumsi ini TIDAK COCOK dengan instruksi task 26.1 yang SECARA
     * EKSPLISIT meminta STRING LITERAL STATIS untuk pesan log
     * ("Kunci enkripsi production masih sama dengan nilai default/
     * hardcode — harus dirotasi.", TANPA parameter dinamis apa pun) —
     * fix yang benar TIDAK PERNAH menyisipkan marker apa pun ke pesan
     * log, sehingga `$markerUnik` di bawah TIDAK LAGI dipakai untuk
     * deteksi kecocokan log (runner mengabaikan nilai `K3_LOG_MARKER`
     * ini sepenuhnya sekarang — lihat docblock runner). Variabel
     * `$markerUnik`/key env `K3_LOG_MARKER` DIPERTAHANKAN di sini HANYA
     * agar tidak perlu mengubah signature call, TIDAK bermakna
     * fungsional. Deteksi SEBENARNYA (field `logFileContainsMarker`,
     * nama field TETAP DIPERTAHANKAN untuk minimasi perubahan) sekarang
     * memeriksa apakah BAGIAN FILE LOG YANG BERTAMBAH mengandung
     * substring pesan log critical STATIS yang PERSIS sesuai
     * `Enkripsi::kunciLegacy()` SAAT INI — bukan bug pada fix, melainkan
     * gap instrumentasi test yang diperbaiki pada task lanjutan 26.1
     * (kategori sama dengan T3/20.2: asumsi keliru mekanisme deteksi
     * test, bukan keterbatasan struktural bahasa/framework).
     */
    public function testTidakAdaLogWarningKetikaKunciProductionIdentikFallbackHardcode(): void
    {
        $markerUnik = 'K3_MARKER_' . bin2hex(random_bytes(8));

        $hasil = $this->jalankanRunnerSubprocess('production', [
            // TIDAK menyertakan K3_FORCE_EMPTY_LEGACY_KEY -- .env fisik
            // project MEMANG sudah mengisi EULT_ENCRYPTION_LEGACY_KEY
            // dengan nilai identik fallback (SuPer_Enc-Key2010), sesuai
            // temuan orchestrator (Skenario B task 23 secara harfiah
            // meminta kondisi ini). Key env 'K3_LOG_MARKER' DIPERTAHANKAN
            // di sini untuk kompatibilitas signature (lihat docblock
            // method ini) -- runner TIDAK LAGI memakai nilainya untuk
            // deteksi apa pun pasca task 26.1-lanjutan.
            'K3_LOG_MARKER' => $markerUnik,
        ]);
        $decoded = $hasil['decoded'];

        // Sanity eksplisit: pastikan skenario benar-benar sesuai
        // deskripsi Skenario B (env key TERISI dan IDENTIK fallback) --
        // bila ini gagal, kondisi precondition test tidak terpenuhi.
        self::assertSame(
            self::HARDCODED_FALLBACK_KEY,
            $decoded['envKeyRawValue'],
            'Prasyarat Skenario B: EULT_ENCRYPTION_LEGACY_KEY pada subprocess harus TERISI dan IDENTIK dengan fallback hardcode (sesuai kondisi .env project saat ini, temuan orchestrator) — bila tidak, skenario ini tidak menguji kondisi B yang dimaksud task 23.'
        );
        self::assertSame(
            self::NILAI_PROBE,
            $decoded['decodedWithEnvKeyIfPresent'],
            'Prasyarat Skenario B: decode() dengan kunci dari env (yang identik fallback) harus BERHASIL mengembalikan nilai probe -- mengonfirmasi kunci ini benar-benar dipakai sebagai kunci efektif (baseline non-bug yang tidak diubah fix K3: kunci non-hardcode/apa pun yang diisi operator tetap dipakai).'
        );

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior (pasca fix K3 26.1): SHALL ada baris
        // log critical mengandung pesan statis
        // "Kunci enkripsi production masih sama dengan nilai default/
        // hardcode" ketika kunci production identik fallback. Pada kode
        // asli, TIDAK ADA log_message() apa pun dipanggil kunciLegacy()/
        // kode terkait -- logFileContainsMarker akan tetap false,
        // membuktikan operator tidak diberi tahu.
        self::assertTrue(
            $decoded['logFileContainsMarker'],
            sprintf(
                'BUG CONDITION K3 TERKONFIRMASI: pada ENVIRONMENT=production dengan EULT_ENCRYPTION_LEGACY_KEY TERISI dan IDENTIK fallback hardcode ("%s"), TIDAK ADA baris log baru mengandung pesan critical statis ("Kunci enkripsi production masih sama dengan nilai default/hardcode") ditemukan di file log CI4 (%s) -- ukuran file SEBELUM=%d byte, SESUDAH=%d byte. Ini membuktikan Enkripsi::kunciLegacy() (app/Libraries/Enkripsi.php:24-31) TIDAK memanggil log_message("critical", ...) atau mekanisme peringatan APA PUN ketika kunci enkripsi production efektif sama dengan nilai default/hardcode publik -- persis bugfix.md 1.11 ("TIDAK ADA mekanisme apa pun (log, warning, exception) yang memberi tahu operator bahwa kondisi ini terjadi").',
                self::HARDCODED_FALLBACK_KEY,
                $decoded['logFilePath'] ?? '(tidak diketahui)',
                $decoded['logFileSizeBeforeBytes'] ?? -1,
                $decoded['logFileSizeAfterBytes'] ?? -1
            )
        );
    }
}
