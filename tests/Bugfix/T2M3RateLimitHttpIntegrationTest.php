<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Config\Throttle as ThrottleConfig;

/**
 * Task 19.2 (Klaster 3 / T2, M3): Test verifikasi rate limiting per-IP
 * (bugfix.md 2.29, 2.30, 2.42, 2.43; tasks.md 19.2).
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA `login/cektiket` (BUKAN `otentifikasi`/`login/savetiket`)
 * DIPILIH SEBAGAI TARGET SATU-SATUNYA test verifikasi ini:
 * ═══════════════════════════════════════════════════════════════════
 * Instruksi task 19.2 menyebut "MINIMAL 1 dari 3 route... pilih yang
 * paling murah/aman untuk ditest berulang". `Login::cektiket()`
 * (app/Controllers/Login.php) dikonfirmasi HANYA melakukan `byId()`
 * (SELECT read-only) — TIDAK ADA panggilan `eult_message_kirim()`,
 * TIDAK ADA panggilan API eksternal (OsmClient), TIDAK ADA
 * insert/update/delete apa pun (konfirmasi identik dengan
 * M3TicketEnumerationExplorationTest.php task 16). Ini menjadikannya
 * SATU-SATUNYA dari 3 route yang benar-benar bebas side-effect nyata
 * (tidak ada risiko SMTP nyata seperti `login/savetiket`, tidak ada
 * risiko panggilan HTTP nyata ke `osm.unmul.ac.id` seperti
 * `otentifikasi`) — aman didispatch berulang kali via `FeatureTestTrait`
 * in-process TANPA batasan jumlah request tambahan.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CSRF WAJIB pada SETIAP request (termasuk request ke-(limit+1) yang
 * diharapkan 429) — URUTAN EKSEKUSI FILTER DIVERIFIKASI LANGSUNG DARI
 * SOURCE CI4 (vendor/codeigniter4/framework/system/Filters/Filters.php):
 * ═══════════════════════════════════════════════════════════════════
 * `app/Config/Feature.php::$oldFilterOrder = false` (default proyek
 * ini, dikonfirmasi dari pembacaan langsung) — dengan pengaturan ini,
 * `Filters::initialize()` memanggil `processFilters()` (filter
 * route-specific, termasuk `throttle`) LEBIH DULU, baru
 * `processGlobals()` (filter global, termasuk `csrf`) — NAMUN kedua
 * method tersebut membangun `$this->filters['before']` dengan
 * `array_merge($filterBaru, $this->filters['before'])`, yaitu
 * MEN-PREPEND filter baru ke DEPAN array yang sudah ada (bukan
 * append ke belakang). Konsekuensinya, urutan EKSEKUSI akhir
 * (`runBefore()` mengiterasi array dari indeks 0) adalah:
 * `['csrf', 'invalidchars', 'throttle:login/cektiket']` — filter
 * GLOBAL (csrf) dieksekusi LEBIH DULU daripada filter ROUTE-SPECIFIC
 * (throttle), karena `processGlobals()` dipanggil PALING TERAKHIR
 * dalam `initialize()` sehingga hasil prepend-nya menempati posisi
 * PALING DEPAN pada array final.
 *
 * Dikonfirmasi ULANG secara empiris (bukan hanya pembacaan source):
 * menjalankan `M3TicketEnumerationExplorationTest.php` (task 16, yang
 * TIDAK menyertakan token CSRF sama sekali) pada state kode SAAT INI
 * (CSRF sudah aktif dari task 18.1) menghasilkan
 * `CodeIgniter\Security\Exceptions\SecurityException` PERSIS pada
 * request PERTAMA — mengonfirmasi filter csrf benar-benar dieksekusi
 * SEBELUM request mencapai body controller (dan karenanya SEBELUM
 * throttle, yang didaftarkan di dalam `$filters`, bukan `$globals`).
 *
 * Oleh karena itu SETIAP dispatch pada test ini (termasuk yang
 * ke-(limit+1) yang diharapkan 429 dari THROTTLE) WAJIB menyertakan
 * token CSRF valid — jika tidak, request akan ditolak 403 oleh CSRF
 * TERLEBIH DAHULU, sebelum sempat mencapai (apalagi ditolak oleh)
 * filter throttle sama sekali, sehingga TIDAK membuktikan apa pun
 * tentang rate-limit itu sendiri. Pola pengambilan token
 * (`denganTokenCsrf()`) di-reuse LANGSUNG dari
 * `Klaster3PreservationTest::denganTokenCsrf()` — termasuk keharusan
 * memanggil ulang (bukan cache) sebelum SETIAP dispatch karena
 * `Config\Security::$regenerate = true` merotasi hash setelah setiap
 * verifikasi sukses.
 *
 * ═══════════════════════════════════════════════════════════════════
 * AMBANG BATAS YANG DIUJI — `login/cektiket` = 20 request/60 detik
 * (Config\Throttle::$routes, app/Config/Throttle.php):
 * ═══════════════════════════════════════════════════════════════════
 * Test ini membaca ambang batas LANGSUNG dari `Config\Throttle`
 * (bukan hardcode angka literal terpisah) agar tetap sinkron otomatis
 * jika konfigurasi limit diubah di masa depan — mengirim TEPAT
 * `$limit` request (SEMUA diharapkan diproses normal, HTTP 200) diikuti
 * SATU request tambahan ke-($limit+1) DALAM WINDOW YANG SAMA (window
 * 60 detik, dispatch in-process jauh lebih cepat dari itu) — SATU
 * request tambahan ini diharapkan 429 (persis anchor "request
 * ke-(N+1) dalam window yang sama dari IP sama → 429" pada task 19.2
 * dan task 19.3).
 *
 * "IP sama" pada dispatch in-process `FeatureTestTrait` — seluruh
 * request pada satu test method berbagi konteks request/lingkungan
 * PHPUnit yang sama, sehingga `$request->getIPAddress()` bernilai
 * IDENTIK di seluruh iterasi (secara efektif mensimulasikan "IP sama"
 * tanpa perlu memanipulasi header X-Forwarded-For secara eksplisit) —
 * konsisten dengan cara T2CaptchaRateLimitExplorationTest.php/
 * M3TicketEnumerationExplorationTest.php mensimulasikan "IP sama" pada
 * dispatch in-process.
 *
 * Nomor tiket TIDAK ADA (`ZZZZ-T2M3-RATE-LIMIT-UJI`) dipakai untuk
 * SELURUH request pada test ini — pemilihan ini SENGAJA (bukan
 * kelalaian) karena tujuan test murni membuktikan mekanisme
 * rate-limit di level FILTER (sebelum controller body `cektiket()`
 * dieksekusi sama sekali pada request yang ditolak), bukan membuktikan
 * oracle "ditemukan"/"tidak ditemukan" itu sendiri (yang sudah
 * dibuktikan tetap konsisten oleh M3TicketEnumerationExplorationTest.php
 * task 16, DAN oleh Klaster3PreservationTest.php task 17 — Preservation
 * 2.43: "pesan ditemukan/tidak ditemukan... TIDAK diubah menjadi
 * generik"). Tidak perlu tiket VALID di sini karena filter throttle
 * berjalan SEBELUM `cektiket()` dipanggil — pesan respons pada request
 * yang LOLOS (200) tidak relevan untuk membuktikan properti rate-limit
 * itu sendiri.
 *
 * `login/cektiket` HANYA melakukan SELECT read-only — TIDAK ADA
 * insert/update/delete apa pun (dikonfirmasi ulang dari source
 * `Login::cektiket()`, identik dengan konfirmasi
 * M3TicketEnumerationExplorationTest.php) — TIDAK memerlukan
 * setUp()/tearDown() cleanup database apa pun. SATU hal yang PERLU
 * dibersihkan adalah entri cache rate-limit itu sendiri (agar test ini
 * idempoten antar-run dan tidak bocor state ke test lain yang mungkin
 * memakai IP/alias rute yang sama) — dibersihkan di tearDown() via
 * `Services::cache()->delete()` dengan kunci yang SAMA persis dengan
 * yang dibangun `App\Filters\ThrottleFilter`.
 *
 * Requirements: 2.29, 2.30, 2.42, 2.43 (bugfix.md)
 *
 * ═══════════════════════════════════════════════════════════════════
 * CATATAN TASK 19.3 — method
 * `testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429()`
 * DITAMBAHKAN pada task 19.3 (BUKAN task 19.2) untuk melengkapi
 * cakupan gate checkpoint T2's aspek rate-limiting (TERPISAH dari
 * aspek captcha-gambar T2 yang sudah dicover
 * `T2CaptchaImageHttpIntegrationTest.php` task 19.1) — SEBELUM
 * penambahan ini, aspek rate-limiting T2 pada
 * `otentifikasi`/`login/savetiket` belum pernah dibuktikan lulus via
 * dispatch HTTP sungguhan sama sekali (hanya `login/cektiket`/M3 yang
 * tercover). Lihat docblock method tersebut untuk alasan lengkap
 * pemilihan `otentifikasi` (bukan `login/savetiket`) dan alasan
 * `login/savetiket` sengaja TIDAK ditambah test serupa.
 *
 * @internal
 */
final class T2M3RateLimitHttpIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Nilai captcha yang di-seed langsung ke session — TIDAK relevan untuk cektiket() (tidak ada gerbang captcha di controller ini), dipertahankan untuk konsistensi pola dengan file test lain. */
    private const CAPTCHA_DIKETAHUI = 'T2M3OK';

    /** Nomor tiket TIDAK ADA — lihat docblock kelas untuk alasan pemilihan (bug/fitur yang diuji murni level filter, bukan oracle ditemukan/tidak-ditemukan). */
    private const NOMOR_TIKET_UJI = 'ZZZZ-T2M3-RATE-LIMIT-UJI';

    /** Placeholder field POST otentifikasi — TIDAK PERNAH dipakai untuk login sungguhan pada test ini (lihat docblock testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429() — request berhenti di gerbang captcha filter/controller SEBELUM cekDatabase()/OsmClient). */
    private const USERNAME_TIDAK_DIPAKAI = 'uji.t2.otentifikasi.throttle.tidak.dipakai';
    private const CAPTCHA_SENGAJA_SALAH  = 'SALAH-SENGAJA';
    private const PASSWORD_TIDAK_DIPAKAI = 'password-tidak-dipakai-uji-throttle';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test verifikasi ini WAJIB tersambung ke database nyata db_newtiket untuk konsistensi dengan konvensi test lain di direktori ini (meski login/cektiket() sendiri read-only, memverifikasi konteks lingkungan test tetap benar).'
        );

        $this->bersihkanCacheThrottle();
    }

    protected function tearDown(): void
    {
        $this->bersihkanCacheThrottle();

        parent::tearDown();
    }

    /**
     * Menghapus entri cache rate-limit untuk alias rute
     * 'login/cektiket' — kunci dibangun PERSIS mengikuti format yang
     * dipakai App\Filters\ThrottleFilter::before(), memakai IP yang
     * SAMA seperti yang akan dipakai request pada test ini
     * (setupRequest('POST', 'login/cektiket')->getIPAddress()) agar
     * benar-benar menghapus entri yang relevan, bukan kunci acak lain.
     */
    private function bersihkanCacheThrottle(): void
    {
        $request = $this->setupRequest('POST', 'login/cektiket');
        $ip      = $request->getIPAddress();

        $kunciCache = 'throttle_' . preg_replace('/[^A-Za-z0-9_]/', '_', 'login/cektiket') . '_' . $ip;

        Services::cache()->delete($kunciCache);
    }

    /**
     * Menambahkan field token CSRF (nama+nilai terkini) ke payload
     * POST — reuse LANGSUNG Klaster3PreservationTest::denganTokenCsrf()
     * (lihat docblock kelas file ini untuk alasan lengkap KEHARUSAN
     * token CSRF pada SETIAP request, termasuk yang ke-(limit+1)).
     *
     * @param array<string, string> $field
     *
     * @return array<string, string>
     */
    private function denganTokenCsrf(array $field): array
    {
        Services::injectMock('request', $this->setupRequest('POST', 'login/cektiket'));
        Services::resetSingle('security');

        return array_merge($field, [csrf_token() => csrf_hash()]);
    }

    /**
     * Variante `denganTokenCsrf()` untuk rute `otentifikasi` — dipakai
     * SATU-SATUNYA oleh
     * `testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429()` di
     * bawah (Task 19.3, lihat docblock method tersebut untuk alasan
     * penambahan test ini). `setupRequest()` per-rute diperlukan agar
     * `csrf_hash()` dibangun terhadap konteks request/IP yang SAMA
     * dengan yang akan dipakai dispatch `post('otentifikasi', ...)`,
     * mengikuti pola `denganTokenCsrf()` di atas persis.
     *
     * @param array<string, string> $field
     *
     * @return array<string, string>
     */
    private function denganTokenCsrfOtentifikasi(array $field): array
    {
        Services::injectMock('request', $this->setupRequest('POST', 'otentifikasi'));
        Services::resetSingle('security');

        return array_merge($field, [csrf_token() => csrf_hash()]);
    }

    /**
     * Menghapus entri cache rate-limit untuk alias rute 'otentifikasi'
     * — kunci dibangun PERSIS mengikuti format
     * `App\Filters\ThrottleFilter::before()`, analog
     * `bersihkanCacheThrottle()` di atas namun untuk rute berbeda.
     * Dipanggil di setUp()/tearDown() method
     * `testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429()`
     * SAJA (bukan seluruh test file, agar tidak menambah overhead pada
     * 2 test `login/cektiket` yang sudah ada dan tidak menyentuh alias
     * rute ini).
     */
    private function bersihkanCacheThrottleOtentifikasi(): void
    {
        $request = $this->setupRequest('POST', 'otentifikasi');
        $ip      = $request->getIPAddress();

        $kunciCache = 'throttle_' . preg_replace('/[^A-Za-z0-9_]/', '_', 'otentifikasi') . '_' . $ip;

        Services::cache()->delete($kunciCache);
    }

    /**
     * Property 1 (Expected Behavior) — Task 19.2: request ke-N (dalam
     * limit) SHALL diproses normal (HTTP 200, BUKAN 429); request
     * ke-(N+1) DALAM WINDOW YANG SAMA dari IP yang sama SHALL 429.
     *
     * Limit dibaca LANGSUNG dari Config\Throttle::$routes['login/cektiket']
     * (bukan hardcode literal terpisah) — lihat docblock kelas.
     */
    public function testRequestKeNDiprosesNormalDanRequestKeNPlus1DalamWindowSamaMendapat429(): void
    {
        $konfig = config(ThrottleConfig::class);
        $limit  = $konfig->routes['login/cektiket']['limit'];

        self::assertGreaterThan(
            0,
            $limit,
            'Prasyarat test: Config\\Throttle::$routes[\'login/cektiket\'][\'limit\'] SHALL berupa integer positif agar skenario N/N+1 bermakna.'
        );

        $statusHttpTerkumpul = [];

        // Kirim TEPAT $limit request — SELURUHNYA diharapkan diproses
        // normal (200), TIDAK SATU PUN 429, karena masing-masing masih
        // berada DALAM ambang batas limit pada window yang sama.
        for ($i = 1; $i <= $limit; $i++) {
            $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
                ->post('login/cektiket', $this->denganTokenCsrf([
                    'nomorTiket' => self::NOMOR_TIKET_UJI,
                ]));

            $statusHttpTerkumpul[] = $hasil->response()->getStatusCode();
        }

        self::assertSame(
            array_fill(0, $limit, 200),
            $statusHttpTerkumpul,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.2 (Requirement 2.30) — request ke-1 hingga ke-%d (TEPAT pada ambang batas limit) SHALL SELURUHNYA diproses normal (HTTP 200), TIDAK SATU PUN 429. Status terkumpul: %s.',
                $limit,
                implode(', ', $statusHttpTerkumpul)
            )
        );

        // *** ASSERTION UTAMA — request ke-(N+1) DALAM WINDOW YANG SAMA ***
        $hasilKeNPlus1 = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
            ->post('login/cektiket', $this->denganTokenCsrf([
                'nomorTiket' => self::NOMOR_TIKET_UJI,
            ]));

        $statusKeNPlus1 = $hasilKeNPlus1->response()->getStatusCode();

        self::assertSame(
            429,
            $statusKeNPlus1,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.2 (Requirement 2.30, 2.42) — request ke-(%d+1) DALAM WINDOW YANG SAMA (60 detik, dispatch in-process jauh lebih cepat dari itu) dari IP yang sama SHALL ditolak dengan HTTP 429 — request sebelumnya (status terkumpul: %s) sudah TEPAT mencapai limit %d. Status aktual request ke-%d: %d.',
                $limit,
                implode(', ', $statusHttpTerkumpul),
                $limit,
                $limit + 1,
                $statusKeNPlus1
            )
        );

        $bodiKeNPlus1 = (string) $hasilKeNPlus1->getBody();

        self::assertStringContainsString(
            '"danger"',
            $bodiKeNPlus1,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.2 (Requirement 2.30) — body respons 429 SHALL mengandung struktur JSON graceful ({\'status\': \'danger\', \'message\': \'...\'}, konsisten dengan pola respons error endpoint lain) — BUKAN halaman error HTML mentah, agar dapat ditangani JS AJAX existing. Body mentah: %s',
                $bodiKeNPlus1
            )
        );
    }

    /**
     * Property 2 (Preservation) — Requirement 2.43: rate limiting
     * (BUKAN penyamaran pesan) adalah kontrol PRIMER untuk M3 — pesan
     * "tidak ditemukan" pada login/cektiket TIDAK diubah menjadi
     * generik. Membuktikan request yang MASIH DALAM limit (bukan yang
     * ke-(N+1)) tetap menghasilkan pesan spesifik "tidak ditemukan"
     * seperti biasa (baseline TIDAK berubah oleh kehadiran filter
     * throttle, selama request tersebut lolos filter).
     */
    public function testRequestDalamLimitTetapMenghasilkanPesanTidakDitemukanSpesifikBukanGenerik(): void
    {
        $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
            ->post('login/cektiket', $this->denganTokenCsrf([
                'nomorTiket' => self::NOMOR_TIKET_UJI,
            ]));

        $hasil->assertStatus(200);

        $bodiMentah = (string) $hasil->getBody();

        self::assertStringContainsString(
            'Nomor tiket tidak ditemukan.',
            $bodiMentah,
            sprintf(
                'PRESERVATION Task 19.2 (Requirement 2.43) — request yang MASIH DALAM ambang batas limit SHALL tetap menghasilkan pesan spesifik "Nomor tiket tidak ditemukan." (BUKAN pesan generik) — kehadiran filter throttle SHALL TIDAK mengubah kontrak respons endpoint untuk request yang lolos filter. Body mentah: %s',
                $bodiMentah
            )
        );
    }

    /**
     * Property 1 (Expected Behavior) — Task 19.3: melengkapi cakupan
     * gate checkpoint T2 (rate-limiting, TERPISAH dari aspek captcha
     * gambar yang SUDAH dicover T2CaptchaImageHttpIntegrationTest.php
     * task 19.1) dengan bukti HTTP-integration-level pada rute
     * `otentifikasi` — sebelum method ini ditambahkan, SATU-SATUNYA
     * bukti end-to-end rate-limit yang lulus hanya menyasar
     * `login/cektiket` (M3); aspek rate-limiting T2 pada
     * `otentifikasi`/`login/savetiket` belum pernah dibuktikan lulus
     * via dispatch HTTP sungguhan sama sekali — HANYA diverifikasi
     * tidak-error pada level konfigurasi ($filters terdaftar,
     * ThrottleFilter ada) tanpa dispatch nyata yang membuktikan angka
     * 429 benar-benar muncul pada request ke-(limit+1).
     *
     * DIPILIH `otentifikasi` (BUKAN `login/savetiket`) untuk menutup
     * gap ini — alasan biaya/risiko:
     * - `otentifikasi` limit = 10/60detik (Config\Throttle) — MAKSIMAL
     *   11 request diperlukan untuk membuktikan 429 pada request
     *   ke-11, TETAP DI BAWAH ambang 15 request yang sudah ditetapkan
     *   presedan `T2CaptchaRateLimitExplorationTest.php`
     *   (JUMLAH_REQUEST_BERUNTUN, lihat docblock kelas test tersebut)
     *   untuk membatasi panggilan HTTP nyata ke API pihak ketiga
     *   `osm.unmul.ac.id` (OsmClient::postLogin(), dipanggil
     *   Otentifikasi::cekDatabase() — HANYA jika request melewati
     *   gerbang validasi $aturan DAN gerbang captcha).
     * - Field POST pada method ini SENGAJA mengirim `captcha` KOSONG
     *   (bukan captcha benar) — dibaca ulang dari
     *   `Otentifikasi::index()` (app/Controllers/Otentifikasi.php):
     *   urutan gerbang adalah `$this->validate([...])` (username,
     *   captcha, password SEMUA hanya 'required', bukan format/nilai
     *   spesifik) DULU, baru `eult_captcha_check()`. Filter
     *   `throttle:otentifikasi` berjalan di stage `before` (SEBELUM
     *   controller body dieksekusi sama sekali) — sehingga TIDAK
     *   PERLU captcha benar ATAU kredensial valid untuk membuktikan
     *   properti rate-limit filter itu sendiri; field `captcha`
     *   diisi placeholder non-kosong (memenuhi 'required') namun
     *   SENGAJA salah, cukup untuk melewati validasi tanpa perlu
     *   membaca captcha session yang sesungguhnya (test ini tidak
     *   men-seed session captcha sama sekali) — request akan berhenti
     *   di gerbang captcha controller (status 200, JSON
     *   `status: danger, message: CAPTCHA tidak valid`) SETELAH
     *   melewati filter throttle, TANPA PERNAH mencapai
     *   `cekDatabase()`/OsmClient sama sekali pada 10 request pertama
     *   ATAU pada request ke-11 (yang seharusnya malah ditolak filter
     *   SEBELUM controller body, sehingga TIDAK PERNAH mencapai
     *   controller/OsmClient sama sekali) — MENOL-KAN panggilan HTTP
     *   nyata ke OSM API pada test ini (0 panggilan, bukan hanya
     *   dibatasi ≤15).
     *
     * `login/savetiket` SENGAJA TIDAK ditambah test serupa pada task
     * ini — keputusan teknis, bukan kelalaian: `ThrottleFilter`
     * (app/Filters/ThrottleFilter.php) adalah SATU class generik yang
     * SAMA persis dipakai ketiga alias rute (`otentifikasi`,
     * `login/savetiket`, `login/cektiket`) — HANYA argumen alias yang
     * berbeda per-rute (menentukan lookup limit/window dari
     * `Config\Throttle::$routes`, bukan logika berbeda). Properti inti
     * yang perlu dibuktikan ("request ke-(limit+1) dalam window sama
     * → 429, request ke-limit tetap 200") SUDAH dibuktikan LULUS DUA
     * KALI pada mekanisme filter yang SAMA persis (`login/cektiket`
     * pada method test di atas, `otentifikasi` pada method ini) —
     * menambah pengujian KETIGA pada alias rute ketiga tidak
     * mengungkap fakta baru tentang MEKANISME filter (satu-satunya hal
     * yang bisa gagal secara independen per-rute adalah SALAH BACA
     * angka limit dari config, yang sudah tercakup prinsip
     * "limit dibaca LANGSUNG dari Config\Throttle, bukan hardcode" —
     * sama pada method ini), sementara `login/savetiket` MEMERLUKAN
     * ≥6 email SMTP NYATA (limit 5/60detik, lihat
     * `T2CaptchaRateLimitExplorationTest.php` docblock kelas untuk
     * concern SMTP nyata yang identik) untuk mendemonstrasikannya —
     * biaya yang tidak proporsional dengan nilai bukti tambahan yang
     * diperoleh, mengikuti prinsip "skala request minimal yang tetap
     * meyakinkan" yang sudah ditetapkan presedan test lain di direktori
     * ini.
     */
    public function testOtentifikasiRequestKeNPlus1DalamWindowSamaMendapat429(): void
    {
        $this->bersihkanCacheThrottleOtentifikasi();

        $konfig = config(ThrottleConfig::class);
        $limit  = $konfig->routes['otentifikasi']['limit'];

        self::assertGreaterThan(
            0,
            $limit,
            'Prasyarat test: Config\\Throttle::$routes[\'otentifikasi\'][\'limit\'] SHALL berupa integer positif agar skenario N/N+1 bermakna.'
        );

        $statusHttpTerkumpul = [];

        // Kirim TEPAT $limit request — SELURUHNYA diharapkan diproses
        // normal (200, gerbang captcha controller, BUKAN 429), karena
        // masing-masing masih berada DALAM ambang batas limit filter
        // pada window yang sama. Captcha SENGAJA salah (lihat docblock
        // method) — cukup memenuhi validasi 'required', tidak pernah
        // mencapai OsmClient.
        for ($i = 1; $i <= $limit; $i++) {
            $hasil = $this->post('otentifikasi', $this->denganTokenCsrfOtentifikasi([
                'username' => self::USERNAME_TIDAK_DIPAKAI,
                'captcha'  => self::CAPTCHA_SENGAJA_SALAH,
                'password' => self::PASSWORD_TIDAK_DIPAKAI,
            ]));

            $statusHttpTerkumpul[] = $hasil->response()->getStatusCode();
        }

        self::assertSame(
            array_fill(0, $limit, 200),
            $statusHttpTerkumpul,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.3 (Requirement 2.29, 2.30) — request ke-1 hingga ke-%d ke otentifikasi (TEPAT pada ambang batas limit) SHALL SELURUHNYA diproses normal (HTTP 200, lolos filter throttle, berhenti di gerbang captcha controller), TIDAK SATU PUN 429. Status terkumpul: %s.',
                $limit,
                implode(', ', $statusHttpTerkumpul)
            )
        );

        // *** ASSERTION UTAMA — request ke-(N+1) DALAM WINDOW YANG SAMA ***
        $hasilKeNPlus1 = $this->post('otentifikasi', $this->denganTokenCsrfOtentifikasi([
            'username' => self::USERNAME_TIDAK_DIPAKAI,
            'captcha'  => self::CAPTCHA_SENGAJA_SALAH,
            'password' => self::PASSWORD_TIDAK_DIPAKAI,
        ]));

        $statusKeNPlus1 = $hasilKeNPlus1->response()->getStatusCode();

        self::assertSame(
            429,
            $statusKeNPlus1,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.3 (Requirement 2.29, 2.30) — request ke-(%d+1) ke otentifikasi DALAM WINDOW YANG SAMA dari IP yang sama SHALL ditolak dengan HTTP 429 — request sebelumnya (status terkumpul: %s) sudah TEPAT mencapai limit %d. Status aktual request ke-%d: %d.',
                $limit,
                implode(', ', $statusHttpTerkumpul),
                $limit,
                $limit + 1,
                $statusKeNPlus1
            )
        );

        $bodiKeNPlus1 = (string) $hasilKeNPlus1->getBody();

        self::assertStringContainsString(
            '"danger"',
            $bodiKeNPlus1,
            sprintf(
                'EXPECTED BEHAVIOR Task 19.3 (Requirement 2.29, 2.30) — body respons 429 SHALL mengandung struktur JSON graceful ({\'status\': \'danger\', \'message\': \'...\'}) — BUKAN halaman error HTML mentah. Body mentah: %s',
                $bodiKeNPlus1
            )
        );

        $this->bersihkanCacheThrottleOtentifikasi();
    }
}
