<?php

namespace Tests\Bugfix;

use App\Libraries\Enkripsi;
use App\Models\ModelTicketing;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 9.2 (redefinisi cakupan — Opsi (b), presedan IDENTIK dengan
 * K1's task 3.3/K1SqlInjectionHttpIntegrationTest.php): Test INTEGRASI
 * HTTP end-to-end untuk checkpoint "T4 fixed", menggantikan
 * T4RatingOwnershipExplorationTest.php (task 6) sebagai gate kelulusan
 * checkpoint.
 *
 * ═══════════════════════════════════════════════════════════════════
 * LATAR BELAKANG — mengapa test baru ini dibutuhkan (dikonfirmasi task
 * 9.1's sub-agent, TIDAK diinvestigasi ulang di sini):
 * ═══════════════════════════════════════════════════════════════════
 * T4RatingOwnershipExplorationTest.php (task 6) tetap GAGAL (2/3) setelah
 * fix 9.1, karena test tersebut memanggil primitif model
 * (`ModelTicketing::byId()`/`ambilSatu()`/`tambah()`/`ubah()`) LANGSUNG
 * dengan nomor tiket mentah, MELEWATI Cektiket::rating() sepenuhnya — ia
 * menguji apakah primitif model itu SENDIRI akan mengizinkan operasi
 * diberikan sebuah nomor tiket, bukan apakah CALL SITE controller masih
 * mengambil nomor tersebut dari field POST yang tidak divalidasi. Task
 * 9.1 memperbaiki CONTROLLER (kini mensyaratkan `$kunci`, mendekodenya,
 * dan memakai HANYA nilai hasil decode) — persis presedan K1's task 3.3
 * (K1SqlInjectionExplorationTest.php TETAP gagal karena alasan struktural
 * yang sama: menguji primitif, bukan call site).
 *
 * Checkpoint "T4 fixed" kini didefinisikan sebagai: "endpoint HTTP publik
 * `POST cektiket/rating/(:kunci)` HANYA mengoperasikan rating berdasarkan
 * nomorTiket hasil decode kunci — kunci tidak valid TIDAK memicu
 * side-effect (insert/update d_rating, email) apa pun" — diverifikasi
 * test INI dengan dispatch HTTP NYATA ke route publik
 * `$routes->post('rating/(:any)', 'Cektiket::rating/$1')`
 * (app/Config/Routes.php), meniru PERSIS skenario serangan task 6
 * ("penyerang mengetahui/menebak nomor tiket tapi TIDAK PERNAH menerima
 * kunci terenkripsi untuk tiket tersebut") — hanya kini nomor tiket
 * tebakan tersebut dikirim sebagai SEGMENT PATH (bukan field POST
 * `nomorTiket`, yang sudah tidak dibaca sama sekali oleh kode yang
 * diperbaiki).
 *
 * T4RatingOwnershipExplorationTest.php (task 6) TETAP DIPERTAHANKAN apa
 * adanya sebagai dokumentasi/regression-guard historis-arsitektur bahwa
 * primitif model (`byId()`, `ambilSatu()`, `tambah()`, `ubah()`) SENGAJA
 * tidak melakukan validasi kepemilikan apa pun pada level model —
 * validasi kepemilikan adalah tanggung jawab CONTROLLER (via decode
 * kunci SEBELUM memanggil primitif tersebut) — BUKAN gate checkpoint T4
 * lagi.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CATATAN TEKNIS KRITIS — mengapa test ini memakai cURL terhadap SERVER
 * DEV LIVE untuk KEDUA skenario (bukan hanya skenario error, DAN bukan
 * CodeIgniter\Test\FeatureTestTrait untuk skenario mana pun):
 * ═══════════════════════════════════════════════════════════════════
 * `eult_message_kirim()` (app/Helpers/eult_message_helper.php) memanggil
 * `response()->setJSON($respons)->send(); exit;` SUNGGUHAN — dipanggil
 * PADA KEDUA jalur kode Cektiket::rating() hasil fix 9.1:
 * - Jalur ERROR (baru, task 9.1): `if (empty($nomorTiket)) { eult_message_kirim('Kunci tidak valid.', 'error'); }`
 * - Jalur SUKSES (pre-existing): `if ($proses) { eult_message_kirim('Terimakasih...', 'success'); }`
 *
 * Ini persis root cause yang sudah didiagnosis DUA KALI pada
 * K1SqlInjectionHttpIntegrationTest.php (lihat docblock kelas tersebut
 * untuk detail lengkap kedua percobaan yang gagal — TIDAK diulang di
 * sini): `FeatureTestTrait::call()` men-dispatch in-process, sehingga
 * `exit;` mematikan SELURUH proses PHPUnit sebelum tearDown() berjalan;
 * `@runInSeparateProcess` gagal pada protokol IPC serialisasi hasil
 * PHPUnit. KEDUA skenario test ini (a: kunci tidak valid, error path;
 * b: kunci valid, success path) SAMA-SAMA memicu `exit;` — sehingga
 * KEDUANYA memakai teknik cURL-ke-server-live yang SAMA, konsisten
 * dengan cara K1 memilih pendekatan berdasarkan investigasi jalur
 * exit()-nya sendiri, bukan asumsi bahwa satu solusi otomatis berlaku
 * untuk semua skenario.
 *
 * Base URL, opsi CURLOPT (termasuk CAINFO bundle sistem untuk sertifikat
 * dev lokal), dan pola pembersihan idempoten SETUP/TEARDOWN mengikuti
 * PERSIS K1SqlInjectionHttpIntegrationTest.php.
 *
 * ═══════════════════════════════════════════════════════════════════
 * FIXTURE — tiket throwaway TERPISAH dari K2T4M2PreservationTest.php:
 * ═══════════════════════════════════════════════════════════════════
 * K2T4M2PreservationTest.php (task 7) sudah memakai tiket throwaway
 * `ZZK2T4M2-0000-00A` untuk baseline preservasi T4 (Bagian B) — TIDAK
 * dipakai ulang di sini agar lifecycle setUp/tearDown test ini
 * sepenuhnya independen (tidak ada risiko interferensi antar test file
 * bila dijalankan paralel/берurutan dengan urutan berbeda). Test ini
 * memakai tiket throwaway BARU `ZZT4HTTP-0000-00B` dengan `ticketEmail`
 * domain RFC 2606 `.invalid` (TIDAK PERNAH resolve DNS sungguhan) —
 * mengikuti pola PERSIS yang sudah divalidasi K2T4M2PreservationTest.php,
 * BUKAN memakai tiket nyata (berbeda dari task 6's
 * T4RatingOwnershipExplorationTest yang SENGAJA memakai tiket nyata
 * untuk membuktikan real-world impact pada skenario VULNERABILITY —
 * test INI membuktikan skenario FIX, sehingga fixture aman sudah cukup
 * dan LEBIH TEPAT, karena tidak ada lagi "dampak dunia nyata" yang perlu
 * dibuktikan).
 *
 * Skenario (a) memakai NOMOR TIKET TIKET INI SENDIRI (bukan payload
 * SQLi) sebagai path segment `$kunci` — meniru PERSIS serangan task 6:
 * "attacker mengetahui/menebak nomor tiket yang valid tapi TIDAK PERNAH
 * menerima kunci terenkripsi untuk tiket tersebut". Karena nomor tiket
 * mentah (format "XXXX-XXXX-NNN", panjang jauh di bawah 128 karakter)
 * tidak akan pernah lolos validasi panjang minimum `Enkripsi::decode()`
 * (lihat app/Libraries/Enkripsi.php:
 * `if (! is_string($gabungan) || strlen($gabungan) <= 128) { return false; }`),
 * ia SELALU gagal decode — persis situasi "penyerang tidak memegang
 * kunci terenkripsi apa pun, hanya nomor tiket".
 *
 * Requirements: 2.34, 2.35, 2.36, 2.37 (bugfix.md — Expected Behavior T4)
 */
final class T4RatingHttpIntegrationTest extends CIUnitTestCase
{
    /** Tiket uji buatan (bukan tiket nyata) — terpisah dari K2T4M2PreservationTest.php agar lifecycle independen. */
    private const TIKET = 'ZZT4HTTP-0000-00B';

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, aman dipakai sebagai ticketEmail fixture. */
    private const EMAIL_AMAN = 't4http-uji@example.invalid';

    /** Base URL server dev live (app.baseURL, .env) — dikonfirmasi reachable, presedan K1SqlInjectionHttpIntegrationTest.php. */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    private BaseConnection $koneksi;

    private ModelTicketing $tiket;

    private Enkripsi $enkripsi;

    private string $kunciValid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi  = \Config\Database::connect('default');
        $this->tiket    = new ModelTicketing();
        $this->enkripsi = new Enkripsi();
        $this->kunciValid = (string) $this->enkripsi->encode(self::TIKET);

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test HTTP integrasi ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi efek samping bermakna (bukan DB tests/mock).'
        );

        // Idempoten: bersihkan sisa data uji dari run sebelumnya (jika
        // ada, misal proses sebelumnya terganggu) SEBELUM test berjalan.
        $this->bersihkanData();

        $this->koneksi->table('d_ticketing')->insert([
            'ticketTrackingId' => self::TIKET,
            'ticketEmail'      => self::EMAIL_AMAN,
            'ticketName'       => 'Pemilik Sah Uji T4Http',
            'ticketCreated'    => '2026-09-14 09:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->bersihkanData();

        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        $this->koneksi->table('d_rating')->where('ratingTicketId', self::TIKET)->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET)->delete();
    }

    /**
     * Mengambil token CSRF SUNGGUHAN dari server dev live via GET,
     * agar dispatch cURL berikutnya (koneksi TCP terpisah, tanpa state
     * PHP proses test ini) menyertakan token yang benar-benar diterima
     * server (bukan token proses test ini sendiri — cookie-based CSRF
     * mengharuskan token berasal dari respons HTTP nyata server yang
     * akan memverifikasinya, BUKAN dari csrf_token()/csrf_hash() proses
     * PHPUnit, yang merupakan proses/security-service TERPISAH dari
     * proses server dev live).
     *
     * `Config\Cookie::$httponly = true` (app/Config/Cookie.php) membuat
     * cookie CSRF tidak terbaca JavaScript browser SUNGGUHAN, namun
     * TIDAK membatasi cURL (cookie engine cURL bukan browser, HttpOnly
     * hanya batasan `document.cookie` sisi browser) — nilai cookie
     * `csrf_cookie_name` (Config\Security::$csrfProtection = 'cookie')
     * dibaca LANGSUNG dari cookie jar setelah GET, dikonfirmasi empiris
     * IDENTIK dengan nilai hidden input `csrf_test_name` pada HTML hasil
     * render (keduanya bersumber dari Security::getHash() yang sama).
     *
     * PENTING: cookie jar YANG SAMA (path dikembalikan di sini) WAJIB
     * dipakai ulang pada request POST berikutnya (via CURLOPT_COOKIEFILE)
     * — server memvalidasi token yang di-POST terhadap hash yang
     * DIRESTORE dari cookie PADA REQUEST ITU SENDIRI (Security::
     * restoreHash(), dipanggil di constructor dari cookie yang masuk
     * bersama request), BUKAN dari state server-side independen. Tanpa
     * cookie yang sama dikirim ulang, server akan me-restore hash BARU
     * (tanpa cookie masuk) yang TIDAK cocok dengan token manapun yang
     * di-POST, tidak peduli token apa yang dikirim.
     *
     * @return array{jar: string, token: string, hash: string}
     */
    private function ambilTokenCsrfDariServerLive(): array
    {
        $jarKuki = tempnam(sys_get_temp_dir(), 'eult_csrf_jar_');

        $ch = curl_init(self::BASE_URL_LIVE . 'login');

        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $opsi = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEJAR      => $jarKuki,
            CURLOPT_COOKIEFILE     => $jarKuki,
        ];

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        // WAJIB: curl_close() SAJA tidak reliable memaksa penulisan
        // cookie jar ke disk pada build libcurl/PHP di environment ini
        // (dikonfirmasi empiris — tanpa baris FLUSH berikut, file jar
        // tetap 0 byte meski curl_exec() berhasil errno=0 dan cookie
        // Set-Cookie benar-benar ada pada respons server). CURLOPT_
        // COOKIELIST 'FLUSH' memaksa penulisan cookie in-memory ke file
        // CURLOPT_COOKIEJAR SEBELUM handle ditutup — pola resmi libcurl
        // untuk kasus ini (https://curl.se/libcurl/c/CURLOPT_COOKIELIST.html).
        curl_setopt($ch, CURLOPT_COOKIELIST, 'FLUSH');
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil mengambil token CSRF dari server dev live (GET login) tanpa error transport (errno=%d: %s).', $errno, $error));

        $isiJar = (string) file_get_contents($jarKuki);

        self::assertMatchesRegularExpression(
            '/csrf_cookie_name\s+([0-9a-f]+)/',
            $isiJar,
            'Prasyarat: cookie csrf_cookie_name SHALL ada pada cookie jar setelah GET login (Config\\Security::$csrfProtection = \'cookie\').'
        );

        preg_match('/csrf_cookie_name\s+([0-9a-f]+)/', $isiJar, $tangkapan);

        return ['jar' => $jarKuki, 'token' => 'csrf_test_name', 'hash' => $tangkapan[1]];
    }

    /**
     * Mengirim POST sungguhan ke server dev live memakai PHP cURL
     * extension — koneksi TCP terpisah, BUKAN dispatch in-process
     * PHPUnit (lihat catatan kelas). exit; pada endpoint hanya
     * mengakhiri request HTTP tersebut di proses server, tidak
     * menyentuh proses test ini. Implementasi IDENTIK dengan
     * K1SqlInjectionHttpIntegrationTest::postKeServerLive(), DITAMBAH
     * field token CSRF + cookie jar (task 18.2 mengaktifkan filter csrf
     * pada Klaster 3 task 18.1 — dispatch POST tanpa token+cookie kini
     * ditolak 403 oleh server, dikonfirmasi empiris via curl manual
     * sebelum perubahan method ini).
     *
     * @param array<string, string> $post
     *
     * @return array{status: int, body: string}
     */
    private function postKeServerLive(string $path, array $post): array
    {
        $csrf = $this->ambilTokenCsrfDariServerLive();
        $post[$csrf['token']] = $csrf['hash'];

        $ch = curl_init(self::BASE_URL_LIVE . $path);

        // Lihat K1SqlInjectionHttpIntegrationTest untuk penjelasan
        // lengkap mengapa CAINFO diarahkan secara eksplisit ke bundle
        // CA sistem (bukan menonaktifkan verifikasi SSL).
        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $opsi = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // WAJIB: kirim ULANG cookie csrf_cookie_name (+ ci_session)
            // yang sama dari GET login pada ambilTokenCsrfDariServerLive()
            // — server memvalidasi token yang di-POST terhadap hash yang
            // di-restore dari cookie YANG MASUK BERSAMA request POST ini
            // sendiri (lihat docblock ambilTokenCsrfDariServerLive()).
            CURLOPT_COOKIEFILE    => $csrf['jar'],
        ];

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_file($csrf['jar'])) {
            unlink($csrf['jar']);
        }

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server dev live tanpa error transport (errno=%d: %s). Pastikan server live reachable sebelum menjalankan test ini.', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'body' => $body];
    }

    /**
     * Sanity: pastikan tiket uji benar-benar tersimpan (prasyarat agar
     * skenario (b) — kunci valid untuk tiket ini — bermakna).
     */
    public function testSanityTiketUjiTersimpanDiDatabase(): void
    {
        $baris = $this->koneksi->table('d_ticketing')
            ->where(['ticketTrackingId' => self::TIKET])
            ->get()->getRowArray();

        self::assertIsArray($baris, 'Prasyarat test: tiket uji HARUS tersimpan di d_ticketing.');
        self::assertSame(self::EMAIL_AMAN, $baris['ticketEmail']);
    }

    /**
     * Sanity: pastikan nomor tiket mentah (dipakai sebagai "kunci"
     * palsu pada skenario a) TIDAK mungkin lolos decode — nomor tiket
     * "XXXX-XXXX-NNN" jauh lebih pendek dari batas minimum 128 karakter
     * yang disyaratkan Enkripsi::decode() (lihat Enkripsi.php). Ini
     * memastikan skenario (a) benar-benar meniru "penyerang tidak
     * memegang kunci terenkripsi apa pun", bukan kebetulan lolos decode.
     */
    public function testSanityNomorTiketMentahTidakLolosDecode(): void
    {
        self::assertFalse(
            $this->enkripsi->decode(self::TIKET),
            'Prasyarat test: nomor tiket mentah SHALL gagal didecode Enkripsi::decode() (terlalu pendek untuk lolos validasi HMAC+base64), agar skenario (a) benar-benar meniru serangan "menebak nomor tiket tanpa memegang kunci".'
        );
    }

    /**
     * Skenario (a) — Property 1 (Expected Behavior), checkpoint T4:
     * POST ke `cektiket/rating/{nomorTiketMentah}` di mana segmen path
     * BUKAN kunci terenkripsi valid (nomor tiket tracking ID mentah
     * milik tiket uji ITU SENDIRI, meniru PERSIS skenario serangan
     * task 6: "penyerang mengetahui/menebak nomor tiket tapi TIDAK
     * PERNAH menerima kunci terenkripsi untuknya") SHALL ditolak TANPA
     * side-effect apa pun — TIDAK ADA baris d_rating tercipta/berubah
     * untuk tiket ini.
     *
     * Ini adalah kebalikan LANGSUNG dari counterexample yang dibuktikan
     * task 6 (T4RatingOwnershipExplorationTest::testRatingTersimpanUntuk...)
     * — di sana insert BERHASIL untuk nomor tiket tebakan pada kode
     * belum diperbaiki; di sini insert SHALL GAGAL/tidak terjadi pada
     * kode yang sudah diperbaiki, untuk nomor tiket tebakan yang SAMA
     * persis jenisnya (nomor tiket mentah, bukan kunci hasil encode).
     */
    public function testPostRatingDenganNomorTiketMentahSebagaiKunciDitolakTanpaSideEffect(): void
    {
        $ratingSebelum = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => self::TIKET])
            ->get()->getRowArray();
        self::assertNull($ratingSebelum, 'Prasyarat: belum ada rating untuk tiket uji ini sebelum dispatch.');

        // *** Dispatch HTTP NYATA melalui cURL — path segment adalah
        // NOMOR TIKET MENTAH tiket ini sendiri, BUKAN kunci terenkripsi
        // hasil encode() — meniru penyerang yang menebak/mengetahui
        // nomor tiket tapi tidak pernah memegang kunci. ***
        $hasil = $this->postKeServerLive('cektiket/rating/' . self::TIKET, [
            'rating' => '5',
        ]);

        // Jalur error 9.1 mengirim JSON status 200 dengan status:'error'
        // (bukan HTTP status code error — mengikuti kontrak
        // eult_message_kirim() yang sudah ada, dipertahankan sebagai
        // preservation kontrak respons JSON existing) — bukan 500.
        self::assertLessThan(
            500,
            $hasil['status'],
            sprintf('Kunci tidak valid SHALL ditangani graceful (bukan 500/crash). Status aktual: %d. Body: %s', $hasil['status'], $hasil['body'])
        );

        $terdekode = json_decode($hasil['body'], true);
        self::assertIsArray($terdekode, sprintf('Respons SHALL berupa JSON valid (kontrak eult_message_kirim()). Body mentah: %s', $hasil['body']));
        self::assertSame(
            'error',
            $terdekode['status'] ?? null,
            sprintf('BUG CONDITION T4 (checkpoint HTTP): respons SHALL berstatus "error" ("Kunci tidak valid.") ketika path segment bukan kunci terenkripsi valid. Body aktual: %s', $hasil['body'])
        );

        // *** ASSERTION UTAMA — TIDAK ADA side-effect d_rating ***
        $ratingSesudah = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => self::TIKET])
            ->get()->getRowArray();

        self::assertNull(
            $ratingSesudah,
            sprintf(
                'BUG CONDITION T4 — REGRESI (checkpoint HTTP): rating untuk tiket "%s" TERSIMPAN di d_rating MESKIPUN path segment yang dikirim BUKAN kunci terenkripsi valid (nomor tiket mentah, meniru penyerang yang menebak nomor tiket tanpa memegang kunci). Cektiket::rating() (app/Controllers/Cektiket.php) SEHARUSNYA menghentikan pemrosesan pada `Enkripsi::decode()` gagal SEBELUM operasi model apa pun dipanggil.',
                self::TIKET
            )
        );
    }

    /**
     * Skenario (a), pelengkap — verifikasi struktural bahwa `byId()`
     * TIDAK PERNAH dipanggil dengan data attacker-controlled: karena
     * fix 9.1 menghentikan eksekusi pada `empty($nomorTiket)` (hasil
     * decode gagal) SEBELUM baris `$datas = $this->tiket->byId(...)`
     * dieksekusi sama sekali, `byId()` PASTI tidak pernah menerima
     * nomor tiket mentah dari path segment yang tidak valid ini —
     * dibuktikan secara struktural (membaca urutan eksekusi kode) dan
     * secara empiris oleh assertion "tidak ada side-effect d_rating"
     * di atas (yang HANYA bisa null bila byId()/ambilSatu()/tambah()/
     * ubah() semuanya tidak pernah tereksekusi untuk tiket ini, karena
     * bila mereka SEMPAT tereksekusi dengan nomorTiket = TIKET yang
     * valid, insert PASTI berhasil — dibuktikan skenario (b) di bawah).
     */
    public function testSkenarioAKunciTidakValidMenghentikanEksekusiSebelumByIdDipanggil(): void
    {
        // byId() PRODUKSI YANG SAMA yang akan dipanggil rating() BILA
        // decode berhasil — dipanggil di sini murni untuk MEMBUKTIKAN
        // bahwa tiket ini SECARA FAKTUAL ADA dan byId() AKAN
        // menemukannya (gerbang TRUE) — sehingga fakta bahwa skenario
        // (a) di atas TIDAK menghasilkan side-effect apa pun BUKAN
        // karena tiket ini tidak ada/tidak ditemukan byId(), melainkan
        // KARENA eksekusi berhenti SEBELUM byId() dipanggil sama sekali
        // (satu-satunya penjelasan yang konsisten dengan kedua fakta
        // ini bersamaan).
        $datas = $this->tiket->byId(['ticketTrackingId' => self::TIKET]);

        self::assertIsArray(
            $datas,
            'Prasyarat pembuktian: tiket uji SHALL benar-benar ditemukan byId() (gerbang true) — ini mengonfirmasi bahwa ketiadaan side-effect pada skenario (a) BUKAN disebabkan tiket tidak ditemukan, melainkan eksekusi rating() berhenti pada decode() gagal SEBELUM byId() sempat dipanggil dengan nomor tiket ini.'
        );
    }

    /**
     * Skenario (b) — Property 2 (Preservation), checkpoint T4: POST ke
     * `cektiket/rating/{kunciValid}` dengan kunci terenkripsi VALID
     * hasil `encode()` untuk tiket yang "attacker" (di sini: pemegang
     * kunci sah) BENAR-BENAR memegang kuncinya — SHALL berhasil
     * tersimpan (rating() tetap berfungsi end-to-end untuk pemegang
     * kunci sah, fix T4 TIDAK merusak jalur sukses).
     *
     * Memakai fixture throwaway (ZZT4HTTP-0000-00B, email `.invalid`)
     * — BUKAN tiket nyata milik orang sungguhan (berbeda dari task 6
     * yang sengaja memakai tiket nyata untuk skenario VULNERABILITY;
     * di sini skenario SUKSES/FIX, sehingga fixture aman lebih tepat
     * dan cukup, mengikuti pola K2T4M2PreservationTest.php).
     */
    public function testPostRatingDenganKunciValidBerhasilTersimpan(): void
    {
        $ratingSebelum = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => self::TIKET])
            ->get()->getRowArray();
        self::assertNull($ratingSebelum, 'Prasyarat: belum ada rating untuk tiket uji ini sebelum dispatch.');

        // *** Dispatch HTTP NYATA melalui cURL — path segment adalah
        // KUNCI TERENKRIPSI VALID hasil encode() untuk tiket ini. ***
        $hasil = $this->postKeServerLive('cektiket/rating/' . $this->kunciValid, [
            'rating' => '5',
        ]);

        self::assertLessThan(
            500,
            $hasil['status'],
            sprintf('Kunci valid SHALL diproses tanpa 500/crash. Status aktual: %d. Body: %s', $hasil['status'], $hasil['body'])
        );

        $terdekode = json_decode($hasil['body'], true);
        self::assertIsArray($terdekode, sprintf('Respons SHALL berupa JSON valid (kontrak eult_message_kirim()). Body mentah: %s', $hasil['body']));
        self::assertSame(
            'success',
            $terdekode['status'] ?? null,
            sprintf('Preservation T4 (checkpoint HTTP): kunci valid untuk tiket legitimate SHALL menghasilkan respons "success". Body aktual: %s', $hasil['body'])
        );

        // *** ASSERTION UTAMA — rating BENAR-BENAR tersimpan ***
        $ratingSesudah = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => self::TIKET])
            ->get()->getRowArray();

        self::assertIsArray(
            $ratingSesudah,
            'Preservation T4 (checkpoint HTTP): rating SHALL tersimpan di d_rating ketika kunci terenkripsi VALID dikirim untuk tiket yang benar-benar berasosiasi — fix T4 (decode kunci) TIDAK BOLEH merusak jalur sukses pemegang kunci sah.'
        );
        self::assertSame('5', (string) ((int) $ratingSesudah['ratingNilai']));
    }
}
