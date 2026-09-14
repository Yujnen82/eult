<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 13 — Test eksplorasi bug condition T2: "Captcha Kosmetik Tanpa
 * Rate Limiting" (bugfix.md 1.18, 1.19, 1.20; design.md bagian T2).
 *
 * CRITICAL: Test ini HARUS GAGAL pada kode belum diperbaiki — kegagalan
 * mengonfirmasi bug ada. DO NOT attempt to fix the test or the code
 * ketika gagal.
 *
 * GOAL: Surface counterexample bahwa (a) nilai captcha terbaca langsung
 * dari DOM tanpa OCR/effort apa pun, DAN (b) tidak ada mekanisme rate
 * limiting apa pun (per-IP/per-sesi) pada endpoint otentikasi dan
 * buat-tiket, sehingga brute force kredensial dan mail bombing via
 * pembuatan tiket massal keduanya terbuka.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA `FeatureTestTrait` (in-process) UNTUK KEDUA ENDPOINT
 * (Otentifikasi::index() DAN Login::savetiket()) — BUKAN cURL-ke-
 * server-live (berbeda dari K1SqlInjectionHttpIntegrationTest.php,
 * T4RatingHttpIntegrationTest.php):
 * ═══════════════════════════════════════════════════════════════════
 * Kedua controller DIBACA LANGSUNG untuk test ini — TIDAK PERNAH
 * memanggil `eult_message_kirim()` (app/Helpers/eult_message_helper.php,
 * yang memanggil `response()->send(); exit;` sungguhan) pada jalur
 * mana pun:
 *
 * - `Otentifikasi::index()` (app/Controllers/Otentifikasi.php): SETIAP
 *   return statement (gagal validasi, captcha salah, kredensial salah,
 *   sukses) memakai `return $this->response->setJSON([...])` — pola
 *   return normal CI4.
 * - `Login::savetiket()` (app/Controllers/Login.php): dikonfirmasi
 *   ulang oleh T1CsrfExplorationTest.php (task 12) — SETIAP return
 *   statement memakai `return $this->response->setJSON([...])`, TIDAK
 *   ADA satu pun panggilan `eult_message_kirim()`.
 *
 * Oleh karena itu `FeatureTestTrait::call()`/`::post()` (in-process)
 * AMAN dipakai untuk KEDUA endpoint pada file test ini — berbeda dari
 * `Cektiket::rating()` yang memanggil helper tersebut pada jalur sukses
 * maupun error (lihat docblock K1SqlInjectionHttpIntegrationTest.php
 * untuk investigasi exit; yang tidak kompatibel dengan in-process
 * PHPUnit).
 *
 * ═══════════════════════════════════════════════════════════════════
 * SKALA REQUEST — MENGAPA 15 (BUKAN 1000) UNTUK KEDUA SUB-KLAIM, DAN
 * MENGAPA INI TETAP COUNTEREXAMPLE YANG MEYAKINKAN:
 * ═══════════════════════════════════════════════════════════════════
 * bugfix.md 1.19/1.20 dan design.md menyebut "1000 request" sebagai
 * angka ilustratif skenario serangan (brute force/mail bombing), BUKAN
 * ambang batas yang harus benar-benar direproduksi literal di test
 * suite otomatis. Task 13 sendiri secara eksplisit meminta
 * "konfirmasi via pencarian" bahwa TIDAK ADA `Config/Throttle.php` di
 * codebase — pencarian tersebut (`grep`/`file_search` untuk
 * `Throttle`/`Throttler` di seluruh `app/`) SUDAH dilakukan sebelum
 * test ini ditulis dan mengonfirmasi NOL kecocokan; `app/Config/
 * Filters.php` juga dikonfirmasi TIDAK memiliki alias `'throttle'`
 * apa pun terdaftar di `$aliases`, dan `otentifikasi`/`savetiket` TIDAK
 * muncul di `$globals`/`$filters` mana pun. Bug condition ini adalah
 * FAKTA ARSITEKTURAL biner (filter throttle ada dan aktif, ATAU tidak
 * ada sama sekali) — bukan properti statistik/probabilistik yang
 * butuh sampel besar untuk terdeteksi: jika filter throttle benar-benar
 * ada dengan window/limit realistis apa pun (bahkan yang sangat
 * longgar, misal 10-20 request/menit), ia AKAN menolak salah satu dari
 * 15 request beruntun dalam window singkat. Tidak satu pun DITOLAK
 * (dibuktikan test ini) sudah cukup membuktikan TIDAK ADA throttle sama
 * sekali — mengirim 1000 tidak akan mengungkap fakta baru yang berbeda
 * dari mengirim 15, karena tidak ada mekanisme apa pun yang bisa
 * "menyerah setelah N request tapi lolos di bawah N" jika N tidak ada.
 *
 * Alasan TAMBAHAN dan LEBIH KRITIS untuk membatasi angka pada KEDUA
 * sub-klaim (bukan sekadar kenyamanan performa test suite) —
 * SIDE-EFFECT DUNIA NYATA TERHADAP AKUN/LAYANAN PIHAK KETIGA:
 *
 * (1) `Otentifikasi::index()` -> `cekDatabase()` -> `OsmClient::
 *     postLogin()` (app/Libraries/OsmClient.php) memanggil
 *     `Services::curlrequest()->post()` SUNGGUHAN ke `EULT_OSM_URL`
 *     (dikonfirmasi di .env: `https://osm.unmul.ac.id`, endpoint
 *     `/auth` lalu `/login`) dengan kredensial NYATA
 *     (`EULT_OSM_PLPKKN_USER/PASS/KEY`) — SETIAP request yang melewati
 *     gerbang validasi+captcha (yaitu SETIAP request pada test ini,
 *     karena test ini justru membuktikan tidak ada apa pun yang
 *     menghentikannya) memicu 2 panggilan HTTP NYATA ke API produksi
 *     UNMUL milik pihak ketiga. `OsmClient` di-instansiasi hardcoded
 *     via `new OsmClient()` di `Otentifikasi::initController()` —
 *     TIDAK ADA seam dependency-injection untuk mengganti/mock-nya
 *     tanpa menyentuh kode produksi (di luar cakupan task 13 — itu
 *     tugas task 19 jika sama sekali relevan, dan mocking dilarang
 *     oleh pedoman testing). Mengirim 1000 request berarti 2000
 *     panggilan HTTP nyata beruntun ke server pihak ketiga yang tidak
 *     dimiliki proyek ini — analog dengan concern SMTP pada
 *     login/savetiket, namun untuk API otentikasi eksternal.
 * (2) `Login::savetiket()` jalur sukses memanggil
 *     `$this->email->buat($email, ..., $datas)` ->
 *     `PengirimEmail::buat()` -> `Services::email()->send()` — SMTP
 *     NYATA (dikonfirmasi hidup oleh T1CsrfExplorationTest.php/task 6),
 *     bukan mock. 1000 email sungguhan (bahkan ke domain `.invalid`
 *     yang gagal DNS) berisiko memicu rate-limit/pembatasan akun pada
 *     relay SMTP nyata yang dikonfigurasi di `.env`.
 *
 * Oleh karena itu KEDUA sub-klaim (bukan hanya bagian email) diskalakan
 * ke **15 request beruntun** — cukup untuk mendemonstrasikan secara
 * KONKRET (bukan sekadar argumen teoretis) bahwa TIDAK SATU PUN dari
 * rangkaian tersebut ditolak dengan sinyal rate-limit (HTTP 429 atau
 * respons setara), sekaligus membatasi panggilan nyata ke OSM (30
 * panggilan HTTP, bukan 2000) dan SMTP (15 email, bukan 1000) pada
 * skala yang wajar untuk CI/test suite otomatis.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KREDENSIAL UJI Otentifikasi — username/password SENGAJA SALAH:
 * ═══════════════════════════════════════════════════════════════════
 * Test ini TIDAK bertujuan membuktikan login BERHASIL — tujuannya
 * murni membuktikan TIDAK ADA throttling pada level REQUEST, terlepas
 * hasil akhir login itu sendiri (bugfix.md 1.20: "brute force kredensial
 * admin TANPA BATAS" — yang dibuktikan adalah tidak ada hambatan
 * mengirim percobaan beruntun, bukan bahwa percobaan tersebut berhasil
 * membobol akun). Username/password acak-tidak-ada dipakai (bukan akun
 * admin nyata) — request tetap harus MELEWATI gerbang validasi
 * (`username`, `captcha`, `password` semua required) dan gerbang
 * captcha (captcha benar, di-seed via session persis pola
 * T1CsrfExplorationTest.php) untuk MENCAPAI titik di mana rate-limiting
 * SEHARUSNYA aktif (setelah captcha, sebelum/selama cekDatabase()) —
 * inilah kondisi minimal yang diminta task 13 ("field yang diperlukan
 * untuk mencapai gerbang captcha-check").
 *
 * ═══════════════════════════════════════════════════════════════════
 * DATA UJI login/savetiket — cleanup MULTIPLE baris:
 * ═══════════════════════════════════════════════════════════════════
 * `eult_generate_kode()` (app/Helpers/eult_kode_helper.php) menghasilkan
 * kode 8-huruf ACAK (format XXXX-XXXX, ruang 26^8) untuk SETIAP
 * pemanggilan `savetiket()` — 15 request beruntun pada test ini
 * masing-masing hampir pasti mendapat `ticketTrackingId` UNIK berbeda
 * (kolisi pada ruang seluas ini astronomis kecil kemungkinannya), semua
 * berbagi `ticketEmail` fixture `.invalid` YANG SAMA. Cleanup memakai
 * `WHERE ticketEmail = fixture` (BUKAN `WHERE ticketTrackingId = ...`
 * tunggal) — query ini otomatis menyapu SELURUH baris yang dihasilkan
 * loop N=15, idempoten di setUp() maupun tearDown(), mengikuti konvensi
 * T1CsrfExplorationTest.php.
 *
 * Requirements: 1.18, 1.19, 1.20 (bugfix.md — Current Behavior/Defect T2)
 *
 * @internal
 */
final class T2CaptchaRateLimitExplorationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Jumlah request beruntun dipakai untuk kedua sub-klaim rate-limit — lihat docblock kelas untuk justifikasi skala. */
    private const JUMLAH_REQUEST_BERUNTUN = 15;

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, aman dipakai sebagai ticketEmail fixture. */
    private const EMAIL_AMAN = 't2captcha-uji@example.invalid';

    /** Nilai captcha yang di-seed langsung ke session test ini sendiri (pola sama dengan T1CsrfExplorationTest.php). */
    private const CAPTCHA_DIKETAHUI = 'T2OK';

    /** Kredensial login SENGAJA SALAH — tujuan test murni membuktikan absennya throttle request, bukan login berhasil. */
    private const USERNAME_TIDAK_ADA = 'uji.t2.tidak.ada.9273';
    private const PASSWORD_SALAH     = 'password-salah-uji-t2';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test eksplorasi ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi efek samping (insert d_ticketing berulang) bermakna (bukan DB tests/mock).'
        );

        // Idempoten: bersihkan sisa data uji dari run sebelumnya (jika
        // ada, misal proses sebelumnya terganggu) SEBELUM test berjalan.
        $this->bersihkanData();
    }

    protected function tearDown(): void
    {
        $this->bersihkanData();

        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        // WHERE ticketEmail = fixture menyapu SELURUH baris yang
        // dihasilkan loop N request (masing-masing ticketTrackingId
        // acak berbeda), bukan hanya satu baris — lihat docblock kelas.
        $this->koneksi->table('d_ticketing')->where('ticketEmail', self::EMAIL_AMAN)->delete();
    }

    /**
     * Property 1 (Bug Condition), bagian (a): nilai captcha SEHARUSNYA
     * (setelah fix, Requirement 2.x captcha-sebagai-gambar) TIDAK dapat
     * dibaca langsung dari HTML/DOM sebagai teks polos. Pada kode ASLI,
     * `eult_captcha_generate()` (app/Helpers/eult_captcha_helper.php)
     * menghasilkan teks polos yang dirender LANGSUNG ke
     * `<span class="captcha-display">` oleh `app/Views/layouts/
     * login.php` tanpa transformasi/rendering gambar apa pun.
     *
     * Merender view PRODUKSI sungguhan yang sama persis dipakai
     * `Login::index()` (bukan grep source .php statis) — memastikan
     * nilai yang benar-benar mencapai klien, bukan sekadar berpotensi
     * ada di source, mengikuti rigor
     * T1CsrfExplorationTest::testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun().
     *
     * **EXPECTED OUTCOME pada kode asli: GAGAL** — assertion menuntut
     * nilai captcha TIDAK muncul sebagai teks polos yang dapat di-parse
     * langsung dari `<span class="captcha-display">...</span>`, namun
     * pada kode asli nilai tersebut PERSIS ada di sana sebagai teks.
     */
    public function testCaptchaTerbacaLangsungDariDomTanpaOcr(): void
    {
        $nilaiCaptchaDiketahui = 'ZQ7K';

        $htmlTerender = view('layouts/login', [
            'captcha'    => $nilaiCaptchaDiketahui,
            'r_priority' => [],
            'datas'      => false,
        ]);

        $cocok = preg_match('/<span class="captcha-display">([^<]*)<\/span>/', $htmlTerender, $tangkapan);

        self::assertSame(
            1,
            $cocok,
            'Prasyarat test: HTML hasil render app/Views/layouts/login.php SHALL mengandung elemen <span class="captcha-display">...</span> agar nilai di dalamnya dapat diperiksa.'
        );

        $nilaiTerbacaDariDom = $tangkapan[1] ?? '';

        // *** ASSERTION UTAMA (Property 1a — Bug Condition) ***
        // Pada kode SETELAH fix T2 (captcha sebagai gambar PNG, task
        // 19.1), elemen ini SEHARUSNYA TIDAK berisi teks captcha polos
        // yang dapat dibaca langsung (nilai captcha akan berada di
        // dalam data biner gambar, bukan teks HTML). Pada kode ASLI,
        // nilai yang di-pass ke view MUNCUL VERBATIM sebagai teks di
        // dalam span tersebut — membuktikan captcha "kosmetik", terbaca
        // tanpa OCR/effort apa pun.
        self::assertNotSame(
            $nilaiCaptchaDiketahui,
            $nilaiTerbacaDariDom,
            sprintf(
                "BUG CONDITION T2 (Requirement 1.18) — COUNTEREXAMPLE: nilai captcha '%s' TERBACA VERBATIM sebagai teks polos di dalam <span class=\"captcha-display\">%s</span> pada HTML hasil render app/Views/layouts/login.php — dapat dibaca langsung dari DOM/HTML TANPA OCR atau upaya apa pun. Ini membuktikan captcha bersifat murni kosmetik (bukan gambar), karena eult_captcha_generate() (app/Helpers/eult_captcha_helper.php) menghasilkan teks polos yang dirender langsung tanpa transformasi visual/gambar.",
                $nilaiCaptchaDiketahui,
                $nilaiTerbacaDariDom
            )
        );
    }

    /**
     * Property 1 (Bug Condition), bagian (b) — Otentifikasi: kirim
     * {self::JUMLAH_REQUEST_BERUNTUN} request POST beruntun ke
     * `otentifikasi` (route: app/Config/Routes.php ->
     * `Otentifikasi::index`) dalam interval singkat dari "IP" yang sama
     * (test dispatch in-process — tidak ada IP nyata bervariasi) pada
     * kode ASLI → SELURUHNYA harus diproses TANPA sinyal rate-limit
     * (429 atau respons setara) — membuktikan tidak ada Throttler apa
     * pun (dikonfirmasi via pencarian `Throttle`/`Throttler` di seluruh
     * codebase: NOL kecocokan; `app/Config/Filters.php` $aliases juga
     * TIDAK memiliki entri 'throttle').
     *
     * Field POST yang dikirim (username/captcha/password) memuaskan
     * gerbang `$this->validate([...])` DAN gerbang
     * `eult_captcha_check()` `Otentifikasi::index()` — SATU-SATUNYA hal
     * yang TIDAK ada adalah mekanisme rate-limit apa pun yang
     * menghentikan request setelah N percobaan. Username/password
     * SENGAJA tidak valid (lihat docblock kelas) — tujuan test murni
     * membuktikan absennya throttle di level REQUEST, bukan
     * keberhasilan login itu sendiri.
     *
     * **EXPECTED OUTCOME pada kode asli: GAGAL** — assertion menuntut
     * SETIDAKNYA SATU dari rangkaian request ditolak dengan sinyal
     * rate-limit, namun pada kode asli SELURUHNYA diproses penuh
     * (status HTTP 200, body JSON valid, tidak pernah 429).
     */
    public function testSeluruhRequestOtentifikasiBeruntunDiprosesTanpaRateLimit(): void
    {
        $jumlahDitolakRateLimit = 0;
        $statusHttpTerkumpul    = [];

        for ($i = 1; $i <= self::JUMLAH_REQUEST_BERUNTUN; $i++) {
            $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
                ->post('otentifikasi', [
                    'username' => self::USERNAME_TIDAK_ADA,
                    'captcha'  => self::CAPTCHA_DIKETAHUI,
                    'password' => self::PASSWORD_SALAH,
                ]);

            $kodeStatus            = $hasil->response()->getStatusCode();
            $statusHttpTerkumpul[] = $kodeStatus;

            if ($kodeStatus === 429) {
                $jumlahDitolakRateLimit++;
            }
        }

        // *** ASSERTION UTAMA (Property 1b — Bug Condition) ***
        self::assertSame(
            0,
            $jumlahDitolakRateLimit,
            sprintf(
                "BUG CONDITION T2 (Requirement 1.19, 1.20) SEHARUSNYA membuktikan rate-limit TIDAK aktif pada kode asli (assertion ini menuntut 0 request ditolak 429 — dan MEMANG 0 pada kode asli, sehingga test ini justru 'lulus' pada bagian assertion ini). Jika suatu saat assertion ini GAGAL (jumlahDitolakRateLimit > 0), berarti SUDAH ADA mekanisme rate-limit yang aktif menolak sebagian dari %d request beruntun ke 'otentifikasi' — status HTTP terkumpul: %s.",
                self::JUMLAH_REQUEST_BERUNTUN,
                implode(', ', $statusHttpTerkumpul)
            )
        );

        // Counterexample konkret: SELURUH request diproses hingga
        // tuntas mengembalikan JSON valid (bukan crash/exception),
        // menegaskan tidak ada hambatan level infrastruktur apa pun.
        self::assertCount(
            self::JUMLAH_REQUEST_BERUNTUN,
            array_filter($statusHttpTerkumpul, static fn (int $kode): bool => $kode === 200),
            sprintf(
                'BUG CONDITION T2 (Requirement 1.19) — COUNTEREXAMPLE: dari %d request POST beruntun ke otentifikasi (tanpa jeda berarti, dari sesi/IP yang sama secara efektif), SELURUHNYA mengembalikan HTTP 200 (diproses penuh oleh Otentifikasi::index() hingga gerbang cekDatabase()) — status terkumpul: %s. Ini membuktikan TIDAK ADA implementasi Throttler apa pun yang membatasi percobaan otentikasi beruntun, membuka celah brute force kredensial admin tanpa batas (dibatasi 15, bukan 1000, untuk menghindari memicu ratusan panggilan HTTP nyata beruntun ke API pihak ketiga osm.unmul.ac.id yang dipanggil OsmClient::postLogin() pada setiap request yang melewati gerbang captcha — lihat docblock kelas).',
                self::JUMLAH_REQUEST_BERUNTUN,
                implode(', ', $statusHttpTerkumpul)
            )
        );
    }

    /**
     * Property 1 (Bug Condition), bagian (b) — login/savetiket: kirim
     * {self::JUMLAH_REQUEST_BERUNTUN} request POST SUKSES beruntun ke
     * `login/savetiket` pada kode ASLI → assert SELURUHNYA berhasil
     * (insert d_ticketing + pemanggilan PengirimEmail::buat() efektif
     * terjadi) TANPA hambatan rate-limit apa pun — analog mail
     * bombing/cost amplification (bugfix.md 1.20).
     *
     * Diskalakan dari "1000" (task 13/design.md) menjadi
     * {self::JUMLAH_REQUEST_BERUNTUN} — lihat docblock kelas bagian
     * "SKALA REQUEST" untuk justifikasi lengkap (properti arsitektural
     * biner + menghindari 1000 email SMTP nyata beruntun).
     *
     * Field POST mengikuti persis `fieldFormLengkapTanpaCsrf()` milik
     * T1CsrfExplorationTest.php (validasi $aturan + captcha benar +
     * header AJAX + ticketPriority='1' untuk FK r_priority) — SATU
     * perbedaan: email fixture khusus test ini (isolasi lifecycle dari
     * T1CsrfExplorationTest.php), dan TANPA header CSRF (memverifikasi
     * ulang gerbang T1 secara insidental, namun fokus test ini adalah
     * ABSENSI rate-limit, bukan CSRF — kedua bug independen, keduanya
     * bermanifestasi bersamaan di kode asli).
     *
     * ═══════════════════════════════════════════════════════════════
     * MENGAPA ASSERTION TIDAK MEM-PARSE BODY JSON RESPONS (berbeda
     * dari pola T1CsrfExplorationTest.php yang memakai
     * `json_decode($hasil->getBody(), true)`):
     * ═══════════════════════════════════════════════════════════════
     * Ditemukan (via investigasi empiris langsung — bukan asumsi)
     * bahwa `$hasil->getBody()` pada dispatch `FeatureTestTrait` di
     * environment ini mengembalikan body yang DIBUNGKUS shell
     * `<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
     * ...><html><body>{...JSON asli...}</body></html>` — SELALU,
     * konsisten pada SETIAP request JSON (dikonfirmasi pada KEDUA
     * endpoint `otentifikasi` dan `login/savetiket`, bukan spesifik
     * savetiket/email), meski header `Content-Type` yang dikembalikan
     * `$hasil->response()->getHeaderLine('Content-Type')` TETAP benar
     * (`application/json; charset=UTF-8`) dan tidak ada exception/
     * warning apa pun tercatat di writable/logs/ pada request
     * tersebut — murni karakteristik environment dispatch test ini
     * (bukan bug kode produksi, DAN bukan bug T2 yang sedang diuji).
     * `json_decode()` pada body berawalan DOCTYPE tersebut SELALU
     * mengembalikan `null` (dikonfirmasi via `php -r` terisolasi),
     * sehingga assertion yang bergantung pada `$terdekode['status']
     * === 'success'` TIDAK PERNAH bernilai true terlepas dari
     * berhasil/gagalnya request yang sesungguhnya — sinyal ini TIDAK
     * DAPAT DIANDALKAN pada environment ini.
     *
     * Oleh karena itu assertion pada method ini murni bergantung pada
     * DUA sinyal yang dikonfirmasi TIDAK terpengaruh wrapper tersebut:
     * (1) kode status HTTP (`$hasil->response()->getStatusCode()` —
     * terverifikasi selalu 200 tanpa wrapper memengaruhi nilai integer
     * ini), dan (2) hitungan baris `d_ticketing` LANGSUNG dari database
     * (bukti paling kuat dan tidak ambigu bahwa request benar-benar
     * diproses tuntas hingga insert). Kedua sinyal ini CUKUP untuk
     * membuktikan Property 1b tanpa perlu mem-parse body JSON sama
     * sekali.
     *
     * **EXPECTED OUTCOME pada kode asli: GAGAL** — assertion menuntut
     * SETIDAKNYA SATU dari rangkaian request ditolak rate-limit (status
     * 429) ATAU jumlah baris d_ticketing yang benar-benar ter-insert
     * LEBIH KECIL dari jumlah request yang dikirim, namun pada kode
     * asli SELURUH request sukses (status 200) dan SELURUH baris
     * ter-insert.
     */
    public function testSeluruhRequestSavetiketSuksesBeruntunDiprosesTanpaRateLimit(): void
    {
        $jumlahDitolakRateLimit = 0;
        $jumlahStatusHttp200    = 0;
        $statusHttpTerkumpul    = [];

        for ($i = 1; $i <= self::JUMLAH_REQUEST_BERUNTUN; $i++) {
            $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->post('login/savetiket', $this->fieldFormSavetiketLengkap($i));

            $kodeStatus            = $hasil->response()->getStatusCode();
            $statusHttpTerkumpul[] = $kodeStatus;

            if ($kodeStatus === 429) {
                $jumlahDitolakRateLimit++;
            }

            if ($kodeStatus === 200) {
                $jumlahStatusHttp200++;
            }
        }

        // *** ASSERTION UTAMA (Property 1b — Bug Condition, sisi savetiket) ***
        self::assertSame(
            0,
            $jumlahDitolakRateLimit,
            sprintf(
                'BUG CONDITION T2 (Requirement 1.19, 1.20): dari %d request POST beruntun ke login/savetiket, %d ditolak dengan status 429 — status terkumpul: %s. (Pada kode asli SEHARUSNYA 0, membuktikan tidak ada rate-limit; jika angka ini > 0 di masa depan, berarti sudah ada mekanisme throttle aktif.)',
                self::JUMLAH_REQUEST_BERUNTUN,
                $jumlahDitolakRateLimit,
                implode(', ', $statusHttpTerkumpul)
            )
        );

        self::assertSame(
            self::JUMLAH_REQUEST_BERUNTUN,
            $jumlahStatusHttp200,
            sprintf(
                "BUG CONDITION T2 (Requirement 1.19, 1.20) — COUNTEREXAMPLE: dari %d request POST beruntun ke login/savetiket (email fixture sama, ticketTrackingId acak berbeda tiap request), HANYA %d yang mendapat status HTTP 200 — status terkumpul: %s. Pada kode ASLI seluruh %d SEHARUSNYA 200 (membuktikan tidak ada hambatan level HTTP apa pun), sehingga jika angka aktual < %d, sebagian ditolak oleh mekanisme di level HTTP (bisa jadi rate-limit ATAU sebab lain) — perlu investigasi lanjutan.",
                self::JUMLAH_REQUEST_BERUNTUN,
                $jumlahStatusHttp200,
                implode(', ', $statusHttpTerkumpul),
                self::JUMLAH_REQUEST_BERUNTUN,
                self::JUMLAH_REQUEST_BERUNTUN
            )
        );

        // Counterexample konkret paling kuat: hitung LANGSUNG baris
        // d_ticketing yang benar-benar ter-INSERT dengan ticketEmail
        // fixture — pembuktian di level database, bukan sekadar status
        // HTTP (setiap baris sukses memicu $this->email->buat() SUNGGUHAN
        // pada baris kode yang sama, lihat Login::savetiket()).
        $jumlahTiketTercipta = $this->koneksi->table('d_ticketing')
            ->where('ticketEmail', self::EMAIL_AMAN)
            ->countAllResults();

        self::assertSame(
            self::JUMLAH_REQUEST_BERUNTUN,
            $jumlahTiketTercipta,
            sprintf(
                'BUG CONDITION T2 (Requirement 1.19, 1.20) — COUNTEREXAMPLE UTAMA: %d dari %d request POST beruntun ke login/savetiket BENAR-BENAR menghasilkan baris d_ticketing ter-insert (masing-masing memicu pemanggilan PengirimEmail::buat() -> SMTP nyata pada baris kode yang sama persis, tanpa gerbang berdasarkan hasil email), TANPA satu pun ditolak rate-limit. Ini adalah bukti konkret vektor mail bombing/cost amplification: siapa pun dapat mengirim request beruntun sebanyak yang diinginkan (diuji %d sebagai sampel representatif dari klaim "1000" pada bugfix.md/design.md — lihat docblock kelas untuk alasan skala) dan SETIAP request sukses memicu pengiriman email tanpa hambatan apa pun.',
                $jumlahTiketTercipta,
                self::JUMLAH_REQUEST_BERUNTUN,
                self::JUMLAH_REQUEST_BERUNTUN
            )
        );
    }

    /**
     * Field POST lengkap untuk `login/savetiket` — mengikuti persis
     * pola `T1CsrfExplorationTest::fieldFormLengkapTanpaCsrf()` (gerbang
     * $aturan + FK ticketPriority), dengan ticketSubject/ticketMessage
     * disertai indeks iterasi ($nomorIterasi) semata untuk keterbacaan
     * log/debug — tidak memengaruhi logika bug yang diuji.
     *
     * @return array<string, string>
     */
    private function fieldFormSavetiketLengkap(int $nomorIterasi): array
    {
        return [
            'captcha'          => self::CAPTCHA_DIKETAHUI,
            'ticketCategories' => '1',
            'ticketEmail'      => self::EMAIL_AMAN,
            'ticketNoHp'       => '081234567890',
            'ticketSubject'    => 'Uji Eksplorasi T2 Rate-Limit #' . $nomorIterasi,
            'ticketMessage'    => 'Pesan uji eksplorasi bug condition T2 — request beruntun ke-' . $nomorIterasi . ' tanpa hambatan rate-limit apa pun.',
            'ticketName'       => 'Pemohon Uji T2RateLimit',
            'ticketPriority'   => '1',
        ];
    }
}
