<?php

/**
 * Runner subprocess KHUSUS test eksplorasi task 23 (Klaster 4 — K3).
 *
 * BUKAN test PHPUnit (tidak diakhiri "Test.php" — sesuai filter default
 * PHPUnit 10, sehingga file ini TIDAK akan ter-load sebagai testsuite oleh
 * `<directory>tests/Bugfix</directory>` pada phpunit.xml). File ini adalah
 * SCRIPT CLI mandiri yang dijalankan sebagai proses PHP TERPISAH (via
 * `proc_open()` dari `K3EncryptionFallbackExplorationTest.php`), BUKAN
 * di-include dalam proses PHPUnit yang sedang berjalan.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA SUBPROCESS TERPISAH DIPERLUKAN (bukan pendekatan in-process)
 * ═══════════════════════════════════════════════════════════════════
 *
 * `ENVIRONMENT` adalah KONSTANTA PHP yang di-`define()` SEKALI di
 * `tests/bootstrap.php` (nilai `'development'`, lihat docblock file
 * tersebut) SEBELUM PHPUnit boot sama sekali. Konstanta PHP bersifat
 * final — memanggil `define('ENVIRONMENT', 'production')` lagi di
 * tengah proses PHPUnit yang sama akan FATAL ERROR
 * ("Constant ENVIRONMENT already defined"). Task 23 mensyaratkan
 * skenario `ENVIRONMENT=production` yang SUNGGUHAN (bukan simulasi
 * palsu) — satu-satunya cara valid mendapatkan nilai konstanta
 * `ENVIRONMENT` yang BERBEDA adalah menjalankan PROSES PHP BARU yang
 * belum pernah men-define konstanta tersebut, dengan environment
 * variable `CI_ENVIRONMENT` proses OS yang berbeda.
 *
 * Investigasi kode sumber CI4 (`vendor/codeigniter4/framework/system/
 * Boot.php::defineEnvironment()`, dipanggil dari `bootSpark()`/`bootWeb()`
 * TAPI TIDAK dipanggil `bootConsole()`) mengonfirmasi: `ENVIRONMENT`
 * SEHARUSNYA di-define dari `$_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_
 * ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production'` pada jalur
 * boot produksi (`public/index.php`/`bootWeb()`). Script ini mereplikasi
 * pola yang SAMA (baca `getenv('CI_ENVIRONMENT')`, define ENVIRONMENT,
 * BARU kemudian `bootConsole()` — identik pola `tests/bootstrap.php`
 * namun dengan nilai environment yang dapat dikontrol subprocess demi
 * demi kebutuhan test), lalu boot CI4 PENUH (`CodeIgniter\Boot::
 * bootConsole()`) agar `Enkripsi::kunciLegacy()` (yang memanggil
 * `env()` — fungsi global CI4 dari `Common.php`, TERSEDIA setelah boot)
 * dan `log_message()` (memerlukan service `logger` CI4, JUGA tersedia
 * setelah boot) dapat dipanggil PERSIS seperti pada proses produksi
 * sungguhan — bukan hanya memanggil `require Common.php` secara
 * terisolasi tanpa service container.
 *
 * ═══════════════════════════════════════════════════════════════════
 * TEMUAN KRITIS TAMBAHAN — .env FISIK PROJECT TETAP DIBACA bootConsole()
 * ═══════════════════════════════════════════════════════════════════
 *
 * `CodeIgniter\Boot::bootConsole()` MEMANGGIL `loadDotEnv()` (lihat
 * `Boot.php`), yang membaca file `.env` FISIK di root project dan
 * mengisi `$_ENV`/`$_SERVER`/`putenv()` untuk SETIAP variabel yang
 * BELUM terisi di environment proses saat itu (`DotEnv::setVariable()`
 * hanya mengisi jika `empty($_ENV[$name])`). Tidak dapat dihindari
 * (config lain seperti kredensial database JUGA berasal dari `.env`
 * yang sama dan dibutuhkan agar boot tidak fatal error) -- DAN `.env`
 * project ini SUDAH mengisi `EULT_ENCRYPTION_LEGACY_KEY=SuPer_Enc-Key2010`
 * (lihat temuan orchestrator: nilai ini IDENTIK fallback hardcode).
 *
 * Konsekuensinya: TIDAK MENYERTAKAN `EULT_ENCRYPTION_LEGACY_KEY` di
 * array `$env` `proc_open()` TIDAK CUKUP untuk mereproduksi Skenario A
 * (variabel tidak terset) -- pasca-`bootConsole()`, `Dotenv` akan
 * *tetap* mengisinya dari file `.env` fisik. Solusi: `putenv()`/unset
 * EKSPLISIT DILAKUKAN DI SINI (dikontrol flag `K3_FORCE_EMPTY_LEGACY_KEY`)
 * SETELAH `bootConsole()` selesai (Dotenv sudah sempat mengisi) dan
 * SEBELUM `Enkripsi` dipanggil -- ini TIDAK mengubah/menulis file `.env`
 * fisik project sama sekali (aman untuk proses lain yang membaca file
 * itu secara bersamaan, sesuai batasan yang diberikan orchestrator),
 * hanya memutasi state in-memory PROSES SUBPROCESS INI SAJA (yang akan
 * exit sepenuhnya setelah script ini selesai, tanpa efek samping ke
 * proses lain).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONTRAK INPUT/OUTPUT (dipakai test PHPUnit induk untuk verifikasi)
 * ═══════════════════════════════════════════════════════════════════
 *
 * INPUT (environment variable proses OS, diset test induk via
 * `proc_open()` dengan array `$env` EKSPLISIT — BUKAN warisan shell
 * induk, lihat docblock test PHPUnit untuk alasan isolasi ini):
 *   - CI_ENVIRONMENT       : nilai environment yang akan di-define
 *                            sebagai konstanta ENVIRONMENT ('production'
 *                            untuk skenario K3 task 23).
 *   - EULT_ENCRYPTION_LEGACY_KEY (opsional, SENGAJA tidak diset di array
 *                            env `proc_open()` untuk Skenario A -- NAMUN
 *                            lihat TEMUAN KRITIS TAMBAHAN di bawah:
 *                            `bootConsole()` tetap memanggil `loadDotEnv()`
 *                            yang membaca file `.env` FISIK project, dan
 *                            `.env` project ini SUDAH mengisi variabel
 *                            tersebut (`SuPer_Enc-Key2010`, identik
 *                            fallback). Karena itu, tidak-menyertakan
 *                            variabel ini di array env `proc_open()` TIDAK
 *                            CUKUP -- script ini WAJIB melakukan
 *                            `putenv()` unset EKSPLISIT pasca-boot (lihat
 *                            kode di bawah, dikontrol oleh
 *                            `K3_FORCE_EMPTY_LEGACY_KEY=1`) agar Skenario
 *                            A benar-benar teruji tanpa memodifikasi file
 *                            `.env` fisik project.
 *   - K3_FORCE_EMPTY_LEGACY_KEY : jika `'1'`, script SECARA EKSPLISIT
 *                            menghapus `EULT_ENCRYPTION_LEGACY_KEY` dari
 *                            `$_ENV`/`$_SERVER`/`putenv()` SETELAH boot
 *                            CI4 selesai (setelah Dotenv sempat mengisi
 *                            nilainya dari `.env` fisik) dan SEBELUM
 *                            `Enkripsi` dipanggil -- mereproduksi kondisi
 *                            "variabel tidak terset/kosong" (Skenario A
 *                            task 23) TANPA mengubah file `.env` fisik
 *                            project sama sekali.
 *   - K3_OVERRIDE_LEGACY_KEY : (ditambahkan task 25, Klaster 4
 *                            Preservasi Skenario 2 -- BUKAN dipakai
 *                            task 23) jika non-empty-string, script
 *                            SECARA EKSPLISIT menuliskan nilai ini ke
 *                            `$_ENV`/`$_SERVER`/`putenv()` SETELAH boot
 *                            CI4 selesai (setelah Dotenv sempat mengisi
 *                            `EULT_ENCRYPTION_LEGACY_KEY` dari `.env`
 *                            fisik project) dan SEBELUM `Enkripsi`
 *                            dipanggil -- diperlukan karena KUIRK YANG
 *                            SAMA PERSIS dengan `variables_order=GPCS`
 *                            yang didokumentasikan lengkap pada
 *                            `K4DebugToolbarServerRunner.php` JUGA
 *                            berlaku pada `php` CLI biasa (dikonfirmasi
 *                            empiris: `ini_get('variables_order')` di
 *                            mesin ini SAMA `'GPCS'` untuk CLI DAN
 *                            `php -S`) -- mengisi
 *                            `EULT_ENCRYPTION_LEGACY_KEY` HANYA lewat
 *                            array `$env` `proc_open()` (tanpa flag ini)
 *                            TIDAK CUKUP: `$_ENV` tidak otomatis terisi
 *                            dari OS env oleh PHP sendiri (huruf `E`
 *                            absen dari `variables_order`), sehingga
 *                            guard `empty($_ENV[$name])` pada
 *                            `DotEnv::setVariable()` tetap `true` dan
 *                            `.env` FISIK project (`SuPer_Enc-Key2010`)
 *                            TETAP menimpa `$_ENV['EULT_ENCRYPTION_
 *                            LEGACY_KEY']` TERLEPAS dari nilai yang
 *                            sudah diisi di environment variable proses
 *                            OS -- dikonfirmasi via reproduksi manual
 *                            SEBELUM flag ini ditambahkan (`envKeyRawValue`
 *                            hasil runner tanpa flag ini SELALU
 *                            `"SuPer_Enc-Key2010"` meski `EULT_ENCRYPTION_
 *                            LEGACY_KEY` proses OS diisi nilai lain).
 *                            TIDAK MEMENGARUHI Skenario A/B task 23 sama
 *                            sekali (flag opt-in baru, default tidak
 *                            diset -- behavior existing tidak berubah).
 *   - K3_LOG_MARKER        : (DIPERTAHANKAN untuk kompatibilitas nama
 *                            parameter dengan caller existing — LIHAT
 *                            REVISI DETEKSI di bawah, dokblock ini
 *                            SEBELUMNYA berasumsi keliru) — SEBELUM
 *                            task 26.1 (fix `Enkripsi::kunciLegacy()`)
 *                            diimplementasikan, docblock ini
 *                            mengasumsikan fix akan menyisipkan marker
 *                            unik ini KE DALAM argumen pertama
 *                            `log_message()` agar setiap run test dapat
 *                            dibedakan dari residu proses lain.
 *                            ASUMSI INI TERBUKTI TIDAK COCOK dengan
 *                            instruksi task 26.1 yang SECARA EKSPLISIT
 *                            meminta STRING LITERAL STATIS untuk pesan
 *                            log ("Kunci enkripsi production masih sama
 *                            dengan nilai default/hardcode — harus
 *                            dirotasi.", TANPA parameter dinamis apa
 *                            pun) — bukan pesan yang menyisipkan
 *                            argumen/marker apa pun. Nilai parameter ini
 *                            (jika diisi caller) TIDAK LAGI dipakai
 *                            untuk deteksi apa pun oleh script ini —
 *                            dipertahankan HANYA agar signature env yang
 *                            sudah dipanggil test existing
 *                            (`K3EncryptionFallbackExplorationTest.php`,
 *                            `Klaster4PreservationTest.php`) tidak perlu
 *                            menghapus key ini dari array `$envTambahan`
 *                            mereka — script ini cukup mengabaikannya.
 *                            Deteksi SEBENARNYA sekarang memakai
 *                            substring pesan log STATIS yang PERSIS
 *                            sesuai `Enkripsi::kunciLegacy()` SAAT INI
 *                            (lihat konstanta
 *                            `K3_CRITICAL_LOG_MESSAGE_SUBSTRING` di
 *                            bawah) — TIDAK BERGANTUNG pada nilai
 *                            parameter ini sama sekali.
 *
 * OUTPUT (JSON tunggal ke STDOUT, satu baris, dibaca `json_decode()`
 * oleh test PHPUnit induk):
 *   {
 *     "environment": "production",              // ENVIRONMENT::const setelah boot
 *     "envKeyRawValue": false|string,             // env('EULT_ENCRYPTION_LEGACY_KEY') mentah SEBELUM dipanggil kunciLegacy()
 *     "encoded": "...",                          // hasil encode('TIKET_PROBE_K3')
 *     "decodedWithLiteralHardcodedKey": "...",   // decode(encoded, 'SuPer_Enc-Key2010') -- observasi TIDAK LANGSUNG kunci efektif
 *     "decodedWithEnvKeyIfPresent": "..."|null,  // decode(encoded, envKeyRawValue) jika envKeyRawValue non-empty string, else null
 *     "logFileSizeBeforeBytes": int,             // ukuran file log SEBELUM operasi Enkripsi (untuk deteksi pertambahan)
 *     "logFileSizeAfterBytes": int,              // ukuran file log SESUDAH operasi Enkripsi
 *     "logFileContainsMarker": bool,              // true jika BAGIAN FILE YANG BERTAMBAH mengandung pesan log critical STATIS yang ditulis Enkripsi::kunciLegacy() ketika kunci production identik fallback hardcode (REVISI task 26.1-lanjutan — SEBELUMNYA field ini mencari K3_LOG_MARKER acak, yang STRUKTURAL TIDAK PERNAH BISA cocok karena fix memakai string literal statis tanpa parameter dinamis apa pun; nama field DIPERTAHANKAN agar caller existing tidak perlu diubah, substansi deteksi yang berubah)
 *     "logFilePath": "..."                       // path file log yang diperiksa, untuk debugging jika assertion gagal
 *   }
 *
 * Semua exception yang tidak tertangani DIBIARKAN menulis ke STDERR
 * (bukan STDOUT) agar tidak mencemari parsing JSON — test PHPUnit induk
 * memeriksa STDERR kosong sebagai sanity check tambahan.
 */

$environmentValue = getenv('CI_ENVIRONMENT') ?: 'production';
define('ENVIRONMENT', $environmentValue);

require __DIR__ . '/../../app/Config/Paths.php';

$paths = new Config\Paths();

require $paths->systemDirectory . '/Boot.php';

defined('FCPATH') || define('FCPATH', __DIR__ . '/../../public/');

CodeIgniter\Boot::bootConsole($paths);

// --- Skenario A (task 23): paksa EULT_ENCRYPTION_LEGACY_KEY benar-benar
// tidak terset, MESKI Dotenv (dipanggil bootConsole() di atas) sudah
// mengisinya dari file .env FISIK project (lihat TEMUAN KRITIS TAMBAHAN
// pada docblock kelas). Override dilakukan pada SELURUH sumber yang
// dibaca fungsi env() global CI4 (Common.php: $_ENV, $_SERVER, getenv())
// agar tidak ada jalur baca mana pun yang masih menemukan nilai lama --
// TANPA menyentuh file .env fisik sama sekali.
if (getenv('K3_FORCE_EMPTY_LEGACY_KEY') === '1') {
    unset($_ENV['EULT_ENCRYPTION_LEGACY_KEY'], $_SERVER['EULT_ENCRYPTION_LEGACY_KEY']);
    putenv('EULT_ENCRYPTION_LEGACY_KEY');
}

// --- Task 25 (Klaster 4 Preservasi, Skenario 2): paksa
// EULT_ENCRYPTION_LEGACY_KEY bernilai KUSTOM tertentu, MESKI Dotenv
// (dipanggil bootConsole() di atas) sudah menimpanya dari file .env
// FISIK project akibat kuirk variables_order=GPCS yang SAMA PERSIS
// dengan yang didokumentasikan K4DebugToolbarServerRunner.php (lihat
// docblock kelas). Override dilakukan pada SELURUH sumber yang dibaca
// fungsi env() global CI4 (Common.php: $_ENV, $_SERVER, getenv()) --
// TANPA menyentuh file .env fisik sama sekali. Mutually exclusive
// secara logis dengan K3_FORCE_EMPTY_LEGACY_KEY di atas (tidak ada
// test yang menyertakan kedua flag sekaligus).
$overrideLegacyKey = getenv('K3_OVERRIDE_LEGACY_KEY');
if (is_string($overrideLegacyKey) && $overrideLegacyKey !== '') {
    $_ENV['EULT_ENCRYPTION_LEGACY_KEY']    = $overrideLegacyKey;
    $_SERVER['EULT_ENCRYPTION_LEGACY_KEY'] = $overrideLegacyKey;
    putenv('EULT_ENCRYPTION_LEGACY_KEY=' . $overrideLegacyKey);
}

// --- Path file log CI4 yang akan ditulis handler default (FileHandler,
// lihat app/Config/Logger.php) untuk TANGGAL HARI INI, agar pertambahan
// ukuran file dapat diamati sebelum/sesudah panggilan Enkripsi.
$logFilePath = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';

// --- Substring pesan log critical STATIS yang ditulis
// Enkripsi::kunciLegacy() ketika kunci production identik fallback
// hardcode -- DIAMBIL LANGSUNG dari source app/Libraries/Enkripsi.php
// SAAT INI (argumen kedua log_message('critical', ...) pada method
// tersebut), BUKAN diketik ulang secara membuta. Fix ini SENGAJA
// memakai string literal statis TANPA parameter dinamis apa pun
// (bukan pesan yang menyisipkan marker/argumen apa pun) -- sehingga
// deteksi di sini mencari substring pesan itu sendiri, BUKAN marker
// acak per-run (lihat REVISI docblock K3_LOG_MARKER di atas untuk
// riwayat kenapa pendekatan sebelumnya tidak dapat bekerja).
const K3_CRITICAL_LOG_MESSAGE_SUBSTRING = 'Kunci enkripsi production masih sama dengan nilai default/hardcode';

$logSizeBefore = is_file($logFilePath) ? filesize($logFilePath) : 0;

// --- Nilai mentah EULT_ENCRYPTION_LEGACY_KEY SEBELUM memanggil apa pun
// pada Enkripsi -- observasi baseline murni memakai fungsi env() global
// CI4 yang sama yang dipanggil Enkripsi::kunciLegacy() secara internal.
$envKeyRawValue = env('EULT_ENCRYPTION_LEGACY_KEY');

$enkripsi = new App\Libraries\Enkripsi();

// Reproduksi jalur observable PUBLIK: encode() tanpa parameter kunci
// eksplisit memaksa kunciMentah('') -> kunciLegacy() dipanggil secara
// internal (private, TIDAK diakses via Reflection -- lihat docblock
// test PHPUnit untuk alasan pendekatan blackbox ini).
$nilaiProbe = 'TIKET_PROBE_K3';
$encoded    = $enkripsi->encode($nilaiProbe);

// Observasi TIDAK LANGSUNG kunci efektif: decode ulang hasil encode()
// di atas memakai kunci LITERAL hardcode ('SuPer_Enc-Key2010') sebagai
// PARAMETER EKSPLISIT. Jika hasilnya berhasil kembali ke $nilaiProbe,
// ini membuktikan kunciLegacy() BENAR-BENAR mengembalikan nilai literal
// tersebut sebagai kunci efektif internal encode() di atas -- tanpa
// perlu Reflection pada method private kunciLegacy()/kunciMentah().
$decodedWithLiteralHardcodedKey = $enkripsi->decode($encoded, 'SuPer_Enc-Key2010');

// Jika EULT_ENCRYPTION_LEGACY_KEY memang terisi (Skenario B task 23),
// verifikasi TAMBAHAN: decode ulang memakai nilai env tersebut sebagai
// parameter eksplisit -- untuk membedakan kasus "kunci efektif = nilai
// env yang diisi" vs "kunci efektif = fallback hardcode" ketika KEDUA
// nilai tersebut kebetulan identik (Skenario B task 23: env key diisi
// SAMA DENGAN fallback hardcode).
$decodedWithEnvKeyIfPresent = null;
if (is_string($envKeyRawValue) && $envKeyRawValue !== '') {
    $decodedWithEnvKeyIfPresent = $enkripsi->decode($encoded, $envKeyRawValue);
}

$logSizeAfter = is_file($logFilePath) ? filesize($logFilePath) : 0;

$logContainsMarker = false;
if (is_file($logFilePath) && $logSizeAfter > $logSizeBefore) {
    // Hanya periksa BAGIAN FILE YANG BERTAMBAH (dari offset $logSizeBefore
    // hingga akhir) -- BUKAN seluruh file (yang bisa berisi ribuan baris
    // dari proses server dev live lain yang berjalan bersamaan) -- agar
    // pemeriksaan pesan critical benar-benar spesifik terhadap eksekusi
    // script subprocess ini, bukan residu proses lain. Mekanisme offset
    // ini TIDAK BERUBAH dari revisi sebelumnya -- hanya substring yang
    // dicari yang diganti (pesan log statis, bukan marker acak).
    $handle = fopen($logFilePath, 'rb');
    if ($handle !== false) {
        fseek($handle, $logSizeBefore);
        $bagianBaru = stream_get_contents($handle);
        fclose($handle);
        $logContainsMarker = is_string($bagianBaru) && str_contains($bagianBaru, K3_CRITICAL_LOG_MESSAGE_SUBSTRING);
    }
}

echo json_encode([
    'environment'                     => ENVIRONMENT,
    'envKeyRawValue'                  => $envKeyRawValue,
    'encoded'                         => $encoded,
    'decodedWithLiteralHardcodedKey'  => $decodedWithLiteralHardcodedKey,
    'decodedWithEnvKeyIfPresent'      => $decodedWithEnvKeyIfPresent,
    'logFileSizeBeforeBytes'          => $logSizeBefore,
    'logFileSizeAfterBytes'           => $logSizeAfter,
    'logFileContainsMarker'           => $logContainsMarker,
    'logFilePath'                     => $logFilePath,
], JSON_UNESCAPED_SLASHES) . PHP_EOL;
