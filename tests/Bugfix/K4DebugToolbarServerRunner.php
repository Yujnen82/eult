<?php

/**
 * Runner subprocess KHUSUS test eksplorasi task 24 (Klaster 4 — K4).
 *
 * BUKAN test PHPUnit (tidak diakhiri "Test.php"), dan BUKAN CLI script
 * satu-shot seperti `K3EncryptionEnvironmentRunner.php`. File ini adalah
 * WRAPPER yang men-spawn (via `proc_open()`) proses **server HTTP PHP
 * bawaan** (`php -S host:port public/index.php`) TERISOLASI dengan
 * environment variable OS dan/atau ini flag PHP yang dikontrol per-skenario,
 * dijalankan dari `K4DebugToolbarExplorationTest.php`. Server yang di-spawn
 * MELAYANI REQUEST HTTP SUNGGUHAN (dispatch lifecycle CI4 penuh via
 * `CodeIgniter\Boot::bootWeb()` → `$app->run()`), BUKAN dijalankan sebagai
 * command CLI satu-shot yang exit setelah selesai — inilah alasan namanya
 * "ServerRunner", bukan "Runner" seperti K3 (server tetap listen sampai
 * dihentikan `proc_terminate()` oleh test PHPUnit induk, K3 sebaliknya
 * exit sendiri setelah satu operasi Enkripsi selesai).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA SERVER HTTP TERPISAH DIPERLUKAN (bukan bootConsole() seperti K3)
 * ═══════════════════════════════════════════════════════════════════
 *
 * Investigasi source (`vendor/codeigniter4/framework/system/Debug/
 * Toolbar.php::prepare()`) mengonfirmasi method tersebut memanggil
 * `$app->getPerformanceStats()`
 * (`vendor/codeigniter4/framework/system/CodeIgniter.php:779`), yang
 * membaca `$this->benchmark->getElapsedTime('total_execution')` — sebuah
 * benchmark point yang HANYA di-start oleh `CodeIgniter::run()` selama
 * dispatch request HTTP PENUH (routing → controller → filter `after`).
 * `CodeIgniter\Boot::bootConsole()` (dipakai K3 via
 * `tests/bootstrap.php`) TIDAK PERNAH memanggil `$app->run()` — ia hanya
 * boot service container + load routes, TANPA dispatch apa pun. Memanggil
 * `Toolbar::prepare()` secara manual pasca-`bootConsole()` akan
 * menghasilkan exception/state tidak valid (`$app->getPerformanceStats()`
 * pada `CodeIgniter` yang belum pernah `run()` akan menghasilkan
 * `totalTime`/`startTime` yang tidak representatif — bahkan jika tidak
 * fatal, TIDAK membuktikan apa pun bermakna tentang bug K4 yang
 * sesungguhnya, yang murni tentang APAKAH filter `toolbar` teraktivasi
 * pada REQUEST NYATA, bukan tentang isi statistik performanya).
 *
 * Filter `toolbar` (`CodeIgniter\Filters\DebugToolbar::after()`, lihat
 * `vendor/codeigniter4/framework/system/Filters/DebugToolbar.php`) HANYA
 * dieksekusi sebagai bagian `$required['after']`
 * (`app/Config/Filters.php:66`) SELAMA `Filters::run()` yang dipanggil
 * `CodeIgniter::run()` — jalur ini HANYA ada pada `bootWeb()` (dipakai
 * `public/index.php`, request HTTP sungguhan) atau `bootSpark()` (dipakai
 * `spark serve`, juga berujung pada `bootWeb()`-equivalent dispatch).
 * Satu-satunya cara memicu filter `toolbar` secara BERMAKNA (bukan
 * simulasi) adalah men-dispatch request HTTP SUNGGUHAN ke proses yang
 * benar-benar menjalankan `bootWeb()` — persis pola yang SUDAH DIPAKAI
 * `K1SqlInjectionHttpIntegrationTest.php`/
 * `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php`/
 * `M1CspHeaderHttpIntegrationTest.php` (cURL-ke-server-live), NAMUN
 * task 24 TIDAK BISA memakai server live yang sudah berjalan (port
 * 8099/8100, proses PHP-FPM terpisah milik operator/environment ini,
 * TIDAK dikontrol test) karena server live tersebut TERIKAT PERMANEN
 * pada `.env` project (`CI_ENVIRONMENT=development`) — TIDAK ADA cara
 * mengubah `ENVIRONMENT` proses tersebut tanpa restart proses dengan
 * environment variable berbeda, yang DILARANG eksplisit oleh batasan
 * task ini (tidak boleh memodifikasi `.env` fisik/proses server live).
 *
 * Solusi: `proc_open()` men-spawn PROSES SERVER PHP BUILT-IN BARU
 * (`php -S 127.0.0.1:{port} public/index.php`) — proses OS TERPISAH
 * dari server live, mem-boot ULANG seluruh CI4 dari awal (termasuk
 * `defineEnvironment()`) dengan environment variable proses OS yang
 * DIKONTROL EKSPLISIT oleh test ini, PERSIS filosofi subprocess K3
 * (proses PHP baru yang belum pernah men-`define()` konstanta
 * `ENVIRONMENT`/`CI_DEBUG`), namun di sini prosesnya adalah SERVER
 * HTTP yang tetap hidup menerima banyak request cURL, bukan CLI
 * satu-shot yang langsung exit.
 *
 * ═══════════════════════════════════════════════════════════════════
 * TEMUAN KRITIS — KUIRK `php -S` + `variables_order` MESIN INI YANG
 * WAJIB DIATASI, BUKAN SEKADAR proc_open() `$env` ARRAY SAJA
 * ═══════════════════════════════════════════════════════════════════
 *
 * Investigasi EMPIRIS mendalam (dilakukan langsung sebelum menulis file
 * ini, BUKAN diasumsikan dari dokumentasi CI4) menemukan RANTAI SEBAB
 * yang lebih rumit dari sekadar "isolasi environment variable" seperti
 * K3:
 *
 * 1. Konfigurasi PHP mesin ini (`php.ini`) memiliki
 *    `variables_order = "GPCS"` — TIDAK menyertakan huruf `E`. Ini
 *    berarti `$_ENV` TIDAK PERNAH otomatis terisi dari environment
 *    variable proses OS oleh PHP sendiri (baik CLI biasa maupun
 *    `php -S`) — dikonfirmasi via probe langsung: `getenv()` SELALU
 *    benar melihat nilai environment variable proses OS
 *    (`CI_ENVIRONMENT=production php -S ...` → `getenv('CI_ENVIRONMENT')`
 *    di dalam script yang dilayani BENAR mengembalikan `'production'`),
 *    NAMUN `$_ENV['CI_ENVIRONMENT']` tetap `UNSET` pada titik yang sama.
 *
 * 2. `CodeIgniter\Boot::bootWeb()` memanggil `loadDotEnv()` SEBELUM
 *    `defineEnvironment()`. `CodeIgniter\Config\DotEnv::setVariable()`
 *    (`vendor/codeigniter4/framework/system/Config/DotEnv.php`) memiliki
 *    guard `if (empty($_ENV[$name])) { $_ENV[$name] = $value; }` —
 *    KARENA `$_ENV['CI_ENVIRONMENT']` genuinely EMPTY (poin 1 di atas,
 *    TERLEPAS dari `getenv()` yang sudah benar), guard ini SELALU
 *    `true`, sehingga `.env` FISIK project (`CI_ENVIRONMENT=development`)
 *    DITULISKAN ke `$_ENV['CI_ENVIRONMENT']` — TANPA MEMANDANG nilai
 *    `getenv()` yang SUDAH BENAR sekalipun.
 *
 * 3. `Boot::defineEnvironment()` memeriksa `$_ENV['CI_ENVIRONMENT'] ??
 *    $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production'`
 *    — KARENA `$_ENV['CI_ENVIRONMENT']` SEKARANG sudah terisi
 *    `'development'` (poin 2, ditulis SETELAH `getenv()` sempat benar),
 *    null-coalesce chain BERHENTI DI SINI dan `ENVIRONMENT` di-define
 *    SEBAGAI `'development'` — TIDAK PERNAH mencapai fallback
 *    `getenv()` yang sebenarnya masih menyimpan `'production'` dengan
 *    benar. Dikonfirmasi via probe langsung yang mereplikasi PERSIS
 *    urutan `loadDotEnv()` → chain `defineEnvironment()`:
 *    `chain_result` SELALU `'development'` MESKIPUN `getenv()` diperiksa
 *    tepat sebelumnya pada baris yang sama menunjukkan `'production'`.
 *
 * SOLUSI YANG DIVERIFIKASI BEKERJA (dikonfirmasi via reproduksi manual
 * end-to-end SEBELUM file ini ditulis, termasuk memverifikasi
 * `config.environment` pada file `writable/debugbar/*.json` yang
 * ditulis subprocess benar-benar `"production"`, bukan `"development"`):
 * tambahkan **`-d variables_order=EGPCS`** pada invocation `php -S` —
 * ini membuat PHP SENDIRI mengisi `$_ENV['CI_ENVIRONMENT']` dari
 * environment variable proses OS SEBELUM `loadDotEnv()` berjalan,
 * sehingga guard `empty($_ENV[$name])` pada `DotEnv::setVariable()`
 * SUDAH `false` (tidak overwrite), dan chain `defineEnvironment()`
 * menemukan `$_ENV['CI_ENVIRONMENT']` yang BENAR pada percobaan
 * pertama. `$_SERVER['CI_ENVIRONMENT']` MASIH akan tertimpa
 * `.env` fisik oleh `DotEnv` (karena `php -S` TIDAK mewariskan OS env
 * ke `$_SERVER` sama sekali, quirk terpisah dari `variables_order`),
 * NAMUN ini TIDAK relevan karena chain `defineEnvironment()` sudah
 * short-circuit pada `$_ENV` lebih dulu.
 *
 * INI ADALAH KUIRK KOMBINASI PHP CLI SERVER + KONFIGURASI `php.ini`
 * MESIN INI — BUKAN mekanisme CI4 yang didesain, dan BUKAN bagian dari
 * bug K4 itu sendiri (murni prasyarat mekanisme test yang harus diatasi
 * agar skenario `ENVIRONMENT=production` yang diminta task 24 BENAR-BENAR
 * teruji, bukan diam-diam menguji `ENVIRONMENT=development` karena
 * override gagal tanpa disadari). Sanity test eksplisit disediakan pada
 * kelas test PHPUnit induk untuk memverifikasi mekanisme ini SEBELUM
 * menyimpulkan apa pun tentang bug K4.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA `ForceHTTPS` (307 redirect) TIDAK MENGHALANGI OBSERVASI BUG
 * ═══════════════════════════════════════════════════════════════════
 *
 * `app/Config/App.php::$forceGlobalSecureRequests = true` menyebabkan
 * SETIAP request non-HTTPS (termasuk ke `127.0.0.1:{port}` HTTP polos
 * yang dilayani server test ini) menerima HTTP 307 dengan header
 * `Location` menuju `https://eult.appdev-papenajam.me/...` (base URL
 * PUBLIK dari `.env`, BUKAN `127.0.0.1:{port}` test ini). Investigasi
 * mengonfirmasi: `curl` dengan flag `-L` (follow redirect) akan
 * MENGIKUTI redirect tersebut ke SERVER LIVE SUNGGUHAN (port 8099/8100,
 * proses TERPISAH milik environment ini) — BUKAN lagi menguji server
 * test yang di-spawn `proc_open()` di sini sama sekali (ditemukan
 * sebagai jebakan investigasi manual sebelum file ini ditulis: hasil
 * "identik" yang membingungkan antara skenario forced/unforced ternyata
 * disebabkan KEDUANYA diam-diam membaca server live yang sama akibat
 * redirect-following, bukan server test yang benar-benar terisolasi).
 *
 * MITIGASI: test PHPUnit induk **TIDAK PERNAH memakai flag `-L`/
 * `CURLOPT_FOLLOWLOCATION`** — response 307 itu sendiri (tanpa
 * mengikuti redirect) tetap memiliki BODY yang SUDAH melalui filter
 * `after` (termasuk `toolbar`) SEBELUM header `Location` ditambahkan
 * filter `forcehttps` (`before`, urutan filter CI4: SELURUH filter
 * `before` dahulu, BARU controller, BARU filter `after` — response yang
 * sama, hanya statusnya diubah SEBELUM body dikembalikan ke controller;
 * dikonfirmasi empiris: body 307 tersebut BENAR mengandung
 * `debugbar_loader`/nonce CSP ketika toolbar aktif, dan BENAR 0 byte
 * ketika toolbar nonaktif — kontras yang bermakna dan dapat diandalkan
 * TANPA perlu mengikuti redirect ke server live sama sekali). Endpoint
 * `?debugbar_time=` (`respond()`) DIPERIKSA TERPISAH via status code
 * dan body-nya sendiri (200 vs 307 murni tanpa body toolbar), BUKAN
 * bergantung pada mengikuti redirect apa pun.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONTRAK PEMAKAIAN (dipanggil test PHPUnit induk)
 * ═══════════════════════════════════════════════════════════════════
 *
 * Class ini TIDAK dijalankan langsung sebagai script CLI (berbeda dari
 * K3EncryptionEnvironmentRunner.php) — ia adalah HELPER CLASS yang
 * di-`require` test PHPUnit induk untuk men-spawn/menghentikan server
 * test secara terprogram. Method utama:
 *   - `start(string $ciEnvironment, ?string $forcedCiDebugPrependPath,
 *      int $port): void` — men-spawn `proc_open()` server pada port
 *      yang diberikan, dengan `CI_ENVIRONMENT` proses OS EKSPLISIT dan
 *      `-d variables_order=EGPCS` SELALU disertakan (mitigasi kuirk di
 *      atas), PLUS `-d auto_prepend_file={$forcedCiDebugPrependPath}`
 *      JIKA parameter tersebut non-null (mekanisme simulasi
 *      misconfiguration `CI_DEBUG` independen dari `ENVIRONMENT`,
 *      lihat Opsi A pada docblock kelas test PHPUnit).
 *   - `stop(): void` — `proc_terminate()` + `proc_close()` server yang
 *      sedang berjalan (dipanggil `tearDown()` test PHPUnit induk,
 *      WAJIB dipanggil pada SETIAP test method agar tidak ada server
 *      test yang tersisa menyala setelah suite selesai).
 *   - `baseUrl(): string` — `http://127.0.0.1:{port}/`.
 *
 * Requirements: 1.12, 1.13, 1.14 (bugfix.md)
 */
final class K4DebugToolbarServerRunner
{
    private ?int $port = null;

    /** @var resource|null */
    private $process;

    /** @var array<int, resource> */
    private array $pipes = [];

    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    /**
     * Men-spawn server test PHP built-in TERISOLASI via `proc_open()`.
     *
     * @param string      $ciEnvironment            Nilai yang akan diset sebagai environment variable proses OS `CI_ENVIRONMENT` (dibaca `Boot::defineEnvironment()` pada proses BARU ini, TIDAK terikat konstanta proses PHPUnit induk).
     * @param string|null $forcedCiDebugPrependPath  Path absolut ke file PHP yang akan disertakan via `-d auto_prepend_file=` (JIKA non-null) — dipakai skenario yang men-simulasikan `CI_DEBUG` ter-override independen dari `ENVIRONMENT` (Opsi A). Null berarti `CI_DEBUG` dibiarkan murni hasil `app/Config/Boot/{ciEnvironment}.php` yang genuine (baseline negatif/Opsi B).
     */
    public function start(string $ciEnvironment, ?string $forcedCiDebugPrependPath, int $port): void
    {
        if ($this->process !== null) {
            throw new RuntimeException('Server test sudah berjalan — panggil stop() sebelum start() lagi.');
        }

        $this->port = $port;

        $env = [
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
            'HOME' => getenv('HOME') ?: '/tmp',
            // Isolasi EKSPLISIT — TIDAK mewarisi shell induk secara implisit,
            // persis prinsip K3EncryptionEnvironmentRunner.php.
            'CI_ENVIRONMENT' => $ciEnvironment,
        ];

        $phpArgs = ['php'];

        // Mitigasi kuirk variables_order=GPCS mesin ini (lihat docblock
        // kelas untuk penjelasan lengkap RANTAI SEBAB) -- TANPA ini,
        // ENVIRONMENT akan diam-diam ter-resolve ke nilai .env fisik
        // project ('development'), BUKAN nilai $ciEnvironment yang
        // diminta parameter ini, akibat DotEnv::setVariable() menimpa
        // $_ENV SEBELUM Boot::defineEnvironment() membaca chain-nya.
        $phpArgs[] = '-d';
        $phpArgs[] = 'variables_order=EGPCS';

        if ($forcedCiDebugPrependPath !== null) {
            $phpArgs[] = '-d';
            $phpArgs[] = 'auto_prepend_file=' . $forcedCiDebugPrependPath;
        }

        $phpArgs[] = '-S';
        $phpArgs[] = '127.0.0.1:' . $port;
        $phpArgs[] = 'public/index.php';

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($phpArgs, $descriptorSpec, $pipes, $this->projectRoot, $env);

        if (! is_resource($process)) {
            throw new RuntimeException('proc_open() gagal men-spawn server test K4 — prasyarat mekanisme test tidak terpenuhi.');
        }

        $this->process = $process;
        $this->pipes    = $pipes;

        fclose($this->pipes[0]);
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        // Tunggu server benar-benar siap menerima koneksi (poll port,
        // BUKAN sleep() tetap -- lebih robust terhadap variasi kecepatan
        // start-up proses PHP built-in server di lingkungan CI/lokal
        // yang berbeda-beda).
        $deadline = microtime(true) + 5.0;
        $ready    = false;

        while (microtime(true) < $deadline) {
            $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);

            if (is_resource($conn)) {
                fclose($conn);
                $ready = true;

                break;
            }

            usleep(50000);
        }

        if (! $ready) {
            $stderr = stream_get_contents($this->pipes[2]);
            $this->stop();

            throw new RuntimeException(sprintf('Server test K4 tidak siap menerima koneksi pada port %d dalam 5 detik. STDERR subprocess: %s', $port, $stderr));
        }
    }

    public function stop(): void
    {
        if ($this->process === null) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_terminate($this->process);
        proc_close($this->process);

        $this->process = null;
        $this->pipes    = [];
        $this->port     = null;
    }

    public function baseUrl(): string
    {
        if ($this->port === null) {
            throw new RuntimeException('Server test K4 belum di-start() -- port tidak tersedia.');
        }

        return sprintf('http://127.0.0.1:%d/', $this->port);
    }

    public function readStderr(): string
    {
        if ($this->process === null || ! isset($this->pipes[2]) || ! is_resource($this->pipes[2])) {
            return '';
        }

        return (string) stream_get_contents($this->pipes[2]);
    }
}
