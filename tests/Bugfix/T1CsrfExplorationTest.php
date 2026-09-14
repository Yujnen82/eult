<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 12: Test eksplorasi bug condition T1 — "Filter Keamanan Global
 * Dinonaktifkan" (bugfix.md 1.15, 1.16, 1.17).
 *
 * CRITICAL: Test ini HARUS GAGAL pada kode belum diperbaiki — kegagalan
 * mengonfirmasi bug ada. DO NOT attempt to fix the test or the code
 * ketika gagal.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA `FeatureTestTrait` (in-process), BUKAN cURL-ke-server-live
 * (berbeda dari K1SqlInjectionHttpIntegrationTest.php,
 * T4RatingHttpIntegrationTest.php, CektiketUploadPdfOnlyHttpIntegrationTest.php):
 * ═══════════════════════════════════════════════════════════════════
 * Ketiga test di atas memakai cURL karena jalur yang diuji memanggil
 * `eult_message_kirim()` (app/Helpers/eult_message_helper.php), yang
 * memanggil `response()->send(); exit;` SUNGGUHAN — tidak kompatibel
 * dengan dispatch in-process PHPUnit (lihat docblock kelas-kelas
 * tersebut untuk investigasi lengkap).
 *
 * `Login::savetiket()` (app/Controllers/Login.php) DIBACA LANGSUNG untuk
 * test ini — method tersebut TIDAK PERNAH memanggil `eult_message_kirim()`
 * pada jalur mana pun. SETIAP return statement (validasi gagal, captcha
 * salah, bukan AJAX, insert gagal, insert sukses) memakai
 * `return $this->response->setJSON([...])` — pola return normal CI4 yang
 * TIDAK exit; sama sekali. Ini dikonfirmasi dengan membaca seluruh isi
 * method (tidak ada satu pun panggilan `eult_message_kirim` di
 * Login.php), berbeda dari `Cektiket::rating()` yang memanggil helper
 * tersebut pada jalur sukses maupun error. Oleh karena itu
 * `FeatureTestTrait::call()` (in-process, lihat vendor/codeigniter4/
 * framework/system/Test/FeatureTestTrait.php) AMAN dipakai di sini —
 * tidak ada risiko exit; mematikan proses PHPUnit induk.
 *
 * ═══════════════════════════════════════════════════════════════════
 * PRASYARAT VALIDASI ($aturan) DAN CAPTCHA — bagaimana ditangani:
 * ═══════════════════════════════════════════════════════════════════
 * `savetiket()` memvalidasi (`$aturan`): `captcha`, `ticketCategories`,
 * `ticketEmail` (valid_email), `ticketNoHp`, `ticketSubject`,
 * `ticketMessage` — SEMUA disediakan sebagai field POST pada test ini
 * agar validasi `$this->validate($aturan)` lolos, sehingga eksekusi
 * mencapai titik SATU-SATUNYA yang hilang: token CSRF (yang memang
 * belum ada mekanismenya sama sekali pada kode asli — bukti utama bug
 * T1, bukan gagal validasi lain yang tidak relevan).
 *
 * Setelah validasi `$aturan` lolos, `eult_captcha_check()` dipanggil
 * (app/Helpers/eult_captcha_helper.php) — pada kode SAAT INI, fungsi ini
 * membaca `session()->get('captcha')` (fallback bila cookie
 * `captcha_code` tidak diset — lihat T3, bug independen, TIDAK dipakai
 * di sini). `FeatureTestTrait::withSession([...])` (pola PERSIS yang
 * sudah dipakai K2T4M2PreservationTest.php dan K2M2IdorExplorationTest.php
 * untuk injeksi `logged_in`) dipakai untuk men-seed nilai session
 * `captcha` LANGSUNG ke nilai yang diketahui test ini SENDIRI
 * (`CAPTCHA_DIKETAHUI`), lalu field POST `captcha` mengirim nilai YANG
 * SAMA — ini BUKAN cara "mem-bypass" captcha, melainkan cara test
 * mensimulasikan "pemohon yang benar-benar membaca captcha dari
 * halaman dan mengetik ulang dengan benar" (skenario captcha BENAR),
 * TANPA perlu men-scrape endpoint `login/refresh_captcha` terlebih
 * dahulu (yang secara fungsional identik hasilnya — nilai captcha
 * server-side yang diketahui test — hanya lebih rumit tanpa manfaat
 * tambahan, karena tujuan test ini adalah CSRF, bukan captcha itu
 * sendiri). Ini konsisten dengan tujuan test: mengisolasi SATU-SATUNYA
 * variabel yang hilang (token CSRF) dengan memuaskan SELURUH gerbang
 * lain (validasi field, captcha benar, header AJAX) — persis instruksi
 * task 12.
 *
 * Header `X-Requested-With: XMLHttpRequest` disertakan agar
 * `$this->request->isAJAX()` bernilai true (gerbang terakhir sebelum
 * insert `d_ticketing`).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KEAMANAN SIDE-EFFECT — EMAIL:
 * ═══════════════════════════════════════════════════════════════════
 * Jalur sukses `savetiket()` memanggil `$this->email->buat($email, ...,
 * $datas)` — panggilan SUNGGUHAN ke `PengirimEmail::buat()` →
 * `Services::email()->send()` (CI4 Email service, SMTP nyata
 * dikonfirmasi hidup di environment ini pada task 6). `ticketEmail`
 * yang dikirim test ini memakai domain RFC 2606 `.invalid`
 * (`t1csrf-uji@example.invalid`) — TIDAK PERNAH resolve DNS sungguhan,
 * mengikuti pola PERSIS yang sudah divalidasi
 * K2T4M2PreservationTest.php dan T4RatingHttpIntegrationTest.php. Tidak
 * ada mock/stub pada layer email — kegagalan SMTP (bila domain
 * `.invalid` ditolak sebelum terkirim) tidak memengaruhi assertion
 * utama test ini (insert `d_ticketing` + `$proses` true), karena
 * `savetiket()` memanggil `email->buat()` SETELAH insert dan TIDAK
 * memeriksa return value-nya untuk menentukan respons JSON (lihat
 * Login.php: email->buat() dipanggil, lalu langsung
 * `return $this->response->setJSON(['status' => 'success', ...])`
 * TANPA gerbang berdasarkan hasil email->buat()) — assertion "request
 * diproses PENUH" pada test ini TIDAK bergantung pada apakah SMTP
 * benar-benar mengirim baris demi baris, cukup pada fakta bahwa
 * `$this->email->buat()` DIPANGGIL (baris kode yang sama persis
 * dieksekusi tanpa gerbang CSRF apa pun menghalangi).
 *
 * Tidak memakai upload file lampiran (`ticketArchiveId` tidak dikirim)
 * — `savetiket()` memeriksa `$this->request->getFile('ticketArchiveId')
 * !== null` sebelum memanggil `eult_upload_ticket()`, sehingga tanpa
 * file, tidak ada berkas fisik tercipta di
 * `writable/uploads/ticketing/` — hanya baris database yang perlu
 * dibersihkan.
 *
 * Data uji (`d_ticketing` dengan `ticketTrackingId` yang dihasilkan
 * server, dicari via `ticketEmail` fixture unik) dibersihkan idempoten
 * di setUp() dan tearDown(), mengikuti konvensi test lain di direktori
 * ini.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CATATAN PASCA-FIX (task 18.4) — REDEFINISI CAKUPAN CHECKPOINT
 * (Opsi (b), presedan IDENTIK K1SqlInjectionExplorationTest.php/
 * K1SqlInjectionHttpIntegrationTest.php dan
 * T4RatingOwnershipExplorationTest.php/T4RatingHttpIntegrationTest.php):
 * ═══════════════════════════════════════════════════════════════════
 * Kelas ini TETAP GAGAL (1 Error, 1 Failure dari 3 test) setelah fix
 * 18.1-18.3 diimplementasikan — dikonfirmasi BUKAN kegagalan fix 18.3,
 * melainkan dua alasan struktural/by-design terpisah:
 *
 * (1) `testPostSavetiketTanpaTokenCsrfDitolakDenganResponsErrorDanTidakAdaInsert`
 *     — **Error** (bukan Failure): `CodeIgniter\Security\Exceptions\
 *     SecurityException` yang lolos filter `csrf` global tertangkap
 *     LANGSUNG oleh PHPUnit sebagai test Error saat dispatch in-process
 *     `FeatureTestTrait::call()`, SEBELUM `set_exception_handler()`/
 *     `Config\Exceptions::handler()` (mekanisme
 *     `AjaxSecurityExceptionHandler` dari task 18.3) sempat berjalan —
 *     PERSIS keterbatasan struktural yang sudah didokumentasikan
 *     lengkap pada docblock kelas `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php`
 *     (in-process dispatch TIDAK PERNAH memicu jalur exception handler
 *     top-level yang hanya aktif pada proses server sungguhan). Ini
 *     ANALOG dengan alasan K1/T4 mensyaratkan verifikasi via
 *     cURL-ke-server-live (exit; mentah pada `eult_message_kirim()`
 *     tidak kompatibel in-process) — kategori keterbatasan berbeda
 *     (exception handler global vs exit; mentah), namun akar masalah
 *     yang SAMA: dispatch in-process PHPUnit tidak dapat mereproduksi
 *     titik ekstensi yang hanya aktif pada request HTTP sungguhan.
 *
 * (2) `testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun` —
 *     **Failure** BY DESIGN: assertion negatif ini sengaja membuktikan
 *     KETIDAKHADIRAN `csrf_field()` PRA-FIX — task 18.2 SUDAH
 *     menambahkan `csrf_field()` ke `app/Views/layouts/login.php`,
 *     sehingga assertion "TIDAK mengandung csrf_field(" kini BENAR
 *     seharusnya gagal (kondisi telah dibalik BY DESIGN oleh fix
 *     18.2, bukan regresi). Presedan identik:
 *     `T4RatingOwnershipExplorationTest.php` yang juga tetap
 *     gagal pasca-fix karena membuktikan kondisi pra-fix yang kini
 *     sengaja dibalik.
 *
 * Checkpoint "T1 fixed" (task 18.4) karena itu DIREDEFINISI —
 * mengikuti pola PERSIS K1's task 3.3 dan T4's task 9.2 — SEBAGAI:
 * "request AJAX/non-AJAX tanpa token CSRF valid ditolak graceful oleh
 * server sungguhan (403 + JSON graceful untuk AJAX, 403 + HTML error
 * penuh tak berubah untuk non-AJAX)", diverifikasi
 * `T1CsrfAjaxGracefulHandlerHttpIntegrationTest.php` (LULUS 3/3) —
 * BUKAN lagi test ini. Kelas ini TETAP DIPERTAHANKAN apa adanya
 * (TIDAK ada assertion yang diubah) sebagai dokumentasi/regression-
 * guard historis bahwa bug T1 pernah ada pada kode asli.
 *
 * Requirements: 1.15, 1.16, 1.17 (bugfix.md — Current Behavior/Defect T1)
 */
final class T1CsrfExplorationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, aman dipakai sebagai ticketEmail fixture. */
    private const EMAIL_AMAN = 't1csrf-uji@example.invalid';

    /** Nilai captcha yang di-seed langsung ke session test ini sendiri (lihat docblock kelas). */
    private const CAPTCHA_DIKETAHUI = 'T1OK';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test eksplorasi ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi efek samping (insert d_ticketing) bermakna (bukan DB tests/mock).'
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
        $this->koneksi->table('d_ticketing')->where('ticketEmail', self::EMAIL_AMAN)->delete();
    }

    /**
     * Field POST yang memuaskan SELURUH gerbang `$aturan` (validasi)
     * DAN gerbang captcha `savetiket()` — SATU-SATUNYA hal yang TIDAK
     * disertakan adalah token/header CSRF apa pun (`csrf_field()`
     * hidden input, header `X-CSRF-TOKEN`) — karena tujuan test ini
     * adalah membuktikan bahwa TANPA token tersebut, request tetap
     * diproses penuh pada kode asli.
     *
     * @return array<string, string>
     */
    private function fieldFormLengkapTanpaCsrf(): array
    {
        return [
            'captcha'          => self::CAPTCHA_DIKETAHUI,
            'ticketCategories' => '1',
            'ticketEmail'      => self::EMAIL_AMAN,
            'ticketNoHp'       => '081234567890',
            'ticketSubject'    => 'Uji Eksplorasi T1 CSRF',
            'ticketMessage'    => 'Pesan uji eksplorasi bug condition T1 — POST tanpa token CSRF apa pun.',
            'ticketName'       => 'Pemohon Uji T1Csrf',
            // ticketPriority: field opsional pada $aturan (tidak
            // 'required'), namun kolom d_ticketing.ticketPriority
            // memiliki FK ke r_priority.priorityId (ON UPDATE CASCADE)
            // — jika dikirim string kosong (perilaku savetiket() saat
            // field POST tidak ada sama sekali:
            // `(string) $this->request->getPost('ticketPriority')` →
            // ''), insert GAGAL karena '' tidak match priorityId
            // manapun (ditemukan via investigasi langsung, BUKAN terkait
            // T1/CSRF sama sekali — murni prasyarat data valid agar
            // eksekusi mencapai baris insert). Kirim nilai priorityId
            // nyata (1, dikonfirmasi ada di r_priority) agar gerbang FK
            // ini terpuaskan dan SATU-SATUNYA variabel yang diuji tetap
            // token CSRF.
            'ticketPriority'   => '1',
        ];
    }

    /**
     * Sanity: pastikan belum ada tiket dengan ticketEmail fixture ini
     * sebelum dispatch (memastikan assertion "tiket tercipta" pasca-
     * dispatch benar-benar disebabkan request ini, bukan data lama).
     */
    public function testSanityBelumAdaTiketDenganEmailFixtureSebelumDispatch(): void
    {
        $baris = $this->koneksi->table('d_ticketing')
            ->where('ticketEmail', self::EMAIL_AMAN)
            ->get()->getRowArray();

        self::assertNull($baris, 'Prasyarat test: belum ada tiket dengan ticketEmail fixture ini sebelum dispatch, agar assertion pasca-dispatch bermakna.');
    }

    /**
     * Property 1 (Bug Condition), checkpoint T1: POST ke
     * `login/savetiket` TANPA header/field CSRF apa pun (tidak ada
     * `csrf_field()` hidden input dikirim, tidak ada header
     * `X-CSRF-TOKEN`) pada kode ASLI (filter global `csrf` masih
     * dikomentari di `app/Config/Filters.php`) SHALL — sebagai BUG
     * CONDITION yang harus terbukti — diproses PENUH: validasi lolos,
     * captcha benar diterima, DAN yang PALING PENTING baris baru
     * benar-benar ter-INSERT ke `d_ticketing` (Requirement 1.17).
     *
     * **EXPECTED OUTCOME test ini pada kode asli: GAGAL** — assertion
     * di bawah menuntut request DITOLAK (respons berstatus error CSRF
     * atau tidak ada baris ter-insert), yang TIDAK akan terjadi pada
     * kode asli (request akan berhasil, `status` => 'success', baris
     * ter-insert) — kegagalan assertion inilah yang menjadi
     * counterexample yang membuktikan bug T1 ada.
     */
    public function testPostSavetiketTanpaTokenCsrfDitolakDenganResponsErrorDanTidakAdaInsert(): void
    {
        $hasil = $this->withSession(['captcha' => self::CAPTCHA_DIKETAHUI])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('login/savetiket', $this->fieldFormLengkapTanpaCsrf());

        // *** ASSERTION UTAMA (Property 1 — Bug Condition) ***
        // Pada kode SETELAH fix T1 (filter csrf aktif + request ini
        // tidak menyertakan token), CI4 SHALL menolak request dengan
        // status HTTP error (403 secara default, atau — bila handler
        // graceful task 18.3 sudah diimplementasikan — status 200
        // dengan body JSON {'status': 'danger', ...}, TIDAK PERNAH
        // 'success'). Pada kode ASLI (filter csrf nonaktif), request
        // akan lolos ke logika controller dan MENGHASILKAN status 200
        // dengan body {'status': 'success', ...} — sehingga assertion
        // "status SHALL BUKAN 200-dengan-success" GAGAL pada kode asli,
        // membuktikan bug T1 (tidak ada validasi CSRF apa pun).
        //
        // CATATAN KOREKSI (ditemukan selama investigasi task 13 —
        // T2CaptchaRateLimitExplorationTest.php): `$hasil->getBody()`
        // pada dispatch FeatureTestTrait di environment ini SELALU
        // dibungkus shell `<!DOCTYPE html>...<html><body>{json}</body>
        // </html>` pada SETIAP respons JSON (bukan spesifik endpoint
        // ini), sehingga `json_decode($body, true)` SELALU mengembalikan
        // `null` — murni karakteristik environment dispatch, bukan bug
        // T1 itu sendiri (lihat docblock kelas
        // T2CaptchaRateLimitExplorationTest.php untuk investigasi
        // lengkap). Akibatnya `$statusJson` di atas SELALU `null`,
        // membuat `$statusJson !== 'success'` SELALU `true` — assertion
        // menjadi tidak pernah bisa gagal, terlepas dari status HTTP
        // sesungguhnya. Sinyal `$statusJson` dihapus; assertion ini kini
        // murni bergantung pada kode status HTTP (sinyal yang
        // dikonfirmasi TIDAK terpengaruh wrapper tersebut) — konsisten
        // dengan pola T2CaptchaRateLimitExplorationTest.php.
        $kodeStatus = $hasil->response()->getStatusCode();
        $body       = $hasil->getBody();

        $requestDitolakKarenaCsrf = $kodeStatus >= 400;

        self::assertTrue(
            $requestDitolakKarenaCsrf,
            sprintf(
                "BUG CONDITION T1 (Requirement 1.15, 1.17): POST ke login/savetiket TANPA token/header CSRF apa pun SEHARUSNYA ditolak (filter global 'csrf' non-aktif di app/Config/Filters.php \$globals, dikonfirmasi masih dikomentari), namun request diproses PENUH — status HTTP %d, body mentah: %s. Ini membuktikan TIDAK ADA validasi CSRF sama sekali pada endpoint sensitif ini.",
                $kodeStatus,
                $body
            )
        );

        // *** COUNTEREXAMPLE KONKRET — baris d_ticketing SUNGGUHAN
        // ter-insert meski tidak ada token CSRF apa pun disertakan ***
        $tiketTercipta = $this->koneksi->table('d_ticketing')
            ->where('ticketEmail', self::EMAIL_AMAN)
            ->get()->getRowArray();

        self::assertNull(
            $tiketTercipta,
            sprintf(
                'BUG CONDITION T1 (Requirement 1.17) — COUNTEREXAMPLE: baris d_ticketing BENAR-BENAR ter-INSERT (ticketTrackingId: %s, ticketEmail: %s) MESKIPUN request POST ke login/savetiket TIDAK menyertakan token/header CSRF apa pun. Ini adalah bukti konkret bahwa Cross-Site Request Forgery pada endpoint pembuatan tiket publik sepenuhnya terbuka pada kode saat ini — form manapun (termasuk dari origin/situs manapun) dapat memicu pembuatan tiket dan pengiriman email sungguhan tanpa proteksi CSRF apa pun.',
                $tiketTercipta['ticketTrackingId'] ?? '(unknown)',
                $tiketTercipta['ticketEmail'] ?? '(unknown)'
            )
        );
    }

    /**
     * Property 1 (Bug Condition), pelengkap struktural — pembuktian
     * PROGRAMATIK (bukan visual/manual) Requirement 1.16: form buat
     * tiket di `app/Views/layouts/login.php` TIDAK mengandung
     * `csrf_field()`/`csrf_token()`/`csrf_hash()` apa pun.
     *
     * Merender view produksi yang SAMA persis dengan yang dipakai
     * `Login::index()` (bukan membaca file sebagai teks statis semata
     * — merender memastikan tidak ada kondisi PHP tersembunyi yang
     * secara dinamis menyisipkan token hanya pada kondisi tertentu
     * yang tidak tertangkap pembacaan teks biasa), lalu mem-parse HTML
     * hasil render untuk memastikan TIDAK ADA input hidden bernama
     * `csrf_test_name` (nilai default `Config\Security::$tokenName`)
     * DAN TIDAK ADA string literal `csrf_field(`/`csrf_token(`/
     * `csrf_hash(` pada source PHP view itu sendiri.
     */
    public function testFormLoginTidakMengandungPemanggilanCsrfFieldApaPun(): void
    {
        $pathView = APPPATH . 'Views/layouts/login.php';

        self::assertFileExists($pathView, 'Prasyarat test: file view app/Views/layouts/login.php SHALL ada.');

        $sumberView = (string) file_get_contents($pathView);

        foreach (['csrf_field(', 'csrf_token(', 'csrf_hash('] as $pemanggilan) {
            self::assertStringNotContainsString(
                $pemanggilan,
                $sumberView,
                sprintf(
                    "BUG CONDITION T1 (Requirement 1.16): app/Views/layouts/login.php TIDAK SEHARUSNYA (pada kode asli) mengandung pemanggilan '%s' — namun ditemukan. Jika assertion ini gagal, form SUDAH menyertakan token CSRF (bug 1.16 mungkin sudah diperbaiki sebagian).",
                    $pemanggilan
                )
            );
        }

        // Verifikasi tambahan via render sungguhan + nama field token
        // CSRF default CI4 (Config\Security::$tokenName, bawaan
        // 'csrf_test_name') — memastikan tidak ada input hidden dengan
        // nama tersebut pada HTML hasil render produksi.
        $namaTokenCsrf = (string) config('Security')->tokenName;

        $htmlTerender = view('layouts/login', [
            'captcha'    => 'DUMMY',
            'r_priority' => [],
            'datas'      => false,
        ]);

        self::assertStringNotContainsString(
            'name="' . $namaTokenCsrf . '"',
            $htmlTerender,
            sprintf(
                "BUG CONDITION T1 (Requirement 1.16) — COUNTEREXAMPLE: HTML hasil render app/Views/layouts/login.php TIDAK SEHARUSNYA mengandung input dengan name=\"%s\" (nama token CSRF default CI4) pada kode asli, karena TIDAK ADA form yang memanggil csrf_field() sama sekali. Assertion ini menegaskan ulang temuan 1.16 secara terverifikasi-programatik (bukan sekadar pembacaan visual source).",
                $namaTokenCsrf
            )
        );
    }
}
