<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 3.3 (redefinisi cakupan — Opsi (b) yang dipilih user): Test
 * INTEGRASI HTTP end-to-end untuk checkpoint "K1 fixed", menggantikan
 * K1SqlInjectionExplorationTest.php sebagai gate kelulusan checkpoint.
 *
 * LATAR BELAKANG — mengapa test baru ini dibutuhkan (jangan diulang
 * investigasinya, sudah dikonfirmasi sesi sebelumnya):
 * K1SqlInjectionExplorationTest.php (task 1) tetap GAGAL (13/13) setelah
 * fix 3.1/3.2, karena test tersebut memanggil primitif (byId(), ambilSatu(),
 * eult_auto_increment()) LANGSUNG dengan string mentah, melewati controller
 * sepenuhnya. Primitif tersebut SENGAJA dipertahankan menerima
 * `array|string $kondisi` karena 5 caller lain di app/Controllers/Ticketing.php
 * (out-of-scope K1) masih memanggil dengan string. Test task 1 TETAP
 * DIPERTAHANKAN apa adanya sebagai regression-guard bahwa primitif masih
 * string-capable by design — BUKAN gate checkpoint K1 lagi.
 *
 * Checkpoint "K1 fixed" kini didefinisikan sebagai: "tidak ada jalur kode
 * controller produksi yang meneruskan kondisi string ter-konkatenasi/tidak
 * terbind ke query" — diverifikasi test INI dengan dispatch HTTP NYATA
 * ke route publik `POST cektiket/rating` (app/Config/Routes.php:
 * `$routes->post('rating', 'Cektiket::rating')`), mengirim payload SQLi
 * pada field `nomorTiket` — field POST yang benar-benar dibaca langsung
 * dari request pengguna oleh Cektiket::rating() (app/Controllers/Cektiket.php)
 * tanpa decode kunci apa pun.
 *
 * Field `nomorTiket` dipilih (bukan jalur eult_auto_increment() via
 * Login::savetiket()) karena savetiket() menghasilkan idTiket SECARA
 * SERVER-SIDE (eult_generate_kode() + urutan internal) — tidak ada field
 * POST yang memungkinkan klien memasukkan payload SQLi langsung ke
 * $idTiket yang diteruskan ke eult_auto_increment(). Jalur itu sudah
 * diverifikasi aman secara struktural (parameter dihasilkan server, bukan
 * dari input); nomorTiket pada cektiket/rating adalah SATU-SATUNYA field
 * yang benar-benar user-controlled dan mengalir ke kondisi WHERE tanpa
 * decode/validasi apa pun di titik masuknya.
 *
 * ═══════════════════════════════════════════════════════════════════
 * CATATAN TEKNIS KRITIS — mengapa test ini memakai cURL terhadap SERVER
 * DEV LIVE (bukan CodeIgniter\Test\FeatureTestTrait):
 * ═══════════════════════════════════════════════════════════════════
 * PERCOBAAN PERTAMA (dibuang, root cause terkonfirmasi dua kali —
 * didokumentasikan di sini agar tidak diulang oleh siapa pun di masa
 * depan): Cektiket::rating() memanggil eult_message_kirim() pada jalur
 * sukses (`if ($proses) { eult_message_kirim(...) }`), dan helper itu
 * memanggil PHP `exit;` SUNGGUHAN setelah mengirim response (lihat
 * app/Helpers/eult_message_helper.php) — bukan simulasi.
 *
 * - Percobaan 1 (FeatureTestTrait polos): `FeatureTestTrait::call()`
 *   menjalankan `$this->app->...->run()` SECARA IN-PROCESS (dikonfirmasi
 *   dengan membaca vendor/codeigniter4/framework/system/Test/
 *   FeatureTestTrait.php — tidak ada isolasi subprocess apa pun). exit;
 *   tersebut MENGHENTIKAN SELURUH PROSES PHPUnit itu sendiri sebelum
 *   summary/tearDown() sempat berjalan — dikonfirmasi empiris: baris
 *   d_rating uji tersisa di database (harus dibersihkan manual).
 * - Percobaan 2 (@runInSeparateProcess + register_shutdown_function()
 *   untuk assertion): subprocess terisolasi mencegah proses PHPUnit
 *   INDUK ikut mati, namun protokol IPC internal PHPUnit untuk
 *   subprocess-testing mengharapkan child process RETURN NORMAL (bukan
 *   exit; mentah) agar bisa men-serialize hasil kembali ke induk —
 *   ditemukan pesan "Test was run in child process and ended
 *   unexpectedly" (ERROR, bukan pass bersih) meski secara fungsional
 *   tidak ada data rusak. Variasi register_shutdown_function() untuk
 *   menjalankan assertion SEBELUM exit; benar-benar terjadi juga
 *   diselidiki: assertion yang GAGAL di shutdown function dilaporkan
 *   PHPUnit dengan format error normal (tervalidasi via eksperimen
 *   terpisah), TETAPI assertion yang BERHASIL tidak menghasilkan output
 *   laporan apa pun yang bisa dipercaya (proses tetap exit; sebelum
 *   reporter PHPUnit mencetak ringkasan "OK" secara normal) — sinyal
 *   pass/fail jadi tidak dapat diandalkan untuk kasus PASS.
 *
 * KEDUA percobaan gagal pada akar masalah YANG SAMA: exit; mentah pada
 * kode yang diuji secara fundamental tidak kompatibel dengan model
 * in-process PHPUnit, terlepas dari strategi isolasi proses yang dicoba.
 *
 * SOLUSI (pendekatan berbeda secara arsitektural, bukan tambalan): kirim
 * request HTTP yang akan memicu exit; tersebut memakai PHP cURL extension
 * terhadap SERVER DEV LIVE sungguhan (app.baseURL dari .env:
 * https://eult.appdev-papenajam.me/, dikonfirmasi reachable — HTTP 200
 * pada GET /) — koneksi TCP terpisah ke proses PHP-FPM/CLI server yang
 * SAMA SEKALI BUKAN proses PHPUnit ini. exit; pada proses server tersebut
 * hanya mengakhiri REQUEST HTTP itu (perilaku normal PHP-FPM/CLI server
 * untuk SETIAP request, bukan hanya test ini), sama sekali tidak
 * menyentuh proses PHPUnit yang menjalankan test ini. PHPUnit HANYA
 * membaca HASIL cURL (status code, body) dan STATE DATABASE db_newtiket
 * SETELAH request selesai — tidak ada dispatch in-process sama sekali.
 * Ini SEKALIGUS lebih end-to-end dibanding FeatureTestTrait (menembus
 * web server nginx + PHP-FPM sungguhan, bukan simulasi routing CI4).
 *
 * KEAMANAN SIDE-EFFECT — TIDAK ADA EMAIL SUNGGUHAN TERKIRIM:
 * Cektiket::rating() hanya memanggil $this->email->selesai() JIKA
 * `$datas = $this->tiket->byId(['ticketTrackingId' => $nomorTiket])`
 * BUKAN false (Cektiket.php, blok `if ($datas !== false)`). Karena
 * kondisi byId() pada kode yang sudah diperbaiki memakai array binding,
 * payload SQLi (mengandung metacharacter `'`) TIDAK PERNAH cocok exact-
 * match dengan ticketTrackingId literal apa pun (format asli
 * "XXXX-XXXX-NNN" tidak pernah mengandung tanda kutip) — sehingga
 * $datas SELALU false untuk payload ini pada kode yang benar, dan email
 * TIDAK PERNAH terpicu. Ini bukan mock/stub — ini konsekuensi struktural
 * dari array binding yang sedang diverifikasi test ini.
 *
 * SIDE-EFFECT YANG TETAP TERJADI (bukan bug, melekat pada rating() untuk
 * NOMOR APAPUN yang tidak ada — tidak spesifik SQLi): rating() tidak
 * memvalidasi keberadaan tiket sebelum insert ke d_rating (lihat
 * `$proses = empty($cek) ? tambah(...) : ubah(...)`), sehingga SATU baris
 * `d_rating` dengan `ratingTicketId` = payload literal AKAN ter-insert
 * di database LIVE (bukan test DB terisolasi — endpoint ini memang
 * satu-satunya database yang ada, sesuai desain proyek/tests/bootstrap.php).
 * Test ini membersihkannya di setUp() (sebelum) DAN tearDown() (setelah)
 * agar tidak meninggalkan sampah data uji di database live.
 *
 * Requirements: 2.1, 2.2 (bugfix.md — Expected Behavior K1)
 */
final class K1SqlInjectionHttpIntegrationTest extends CIUnitTestCase
{
    private const PAYLOAD_SQLI = "X' OR '1'='1";

    /** Base URL server dev live (app.baseURL, .env) — dikonfirmasi reachable (HTTP 200 pada GET /). */
    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame('db_newtiket', $this->koneksi->database, 'Test HTTP integrasi ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi efek samping bermakna (bukan DB tests/mock).');

        // Idempoten: bersihkan sisa row uji dari run sebelumnya (jika ada,
        // misal proses sebelumnya terganggu) SEBELUM test berjalan.
        $this->bersihkanRowUji();
    }

    protected function tearDown(): void
    {
        $this->bersihkanRowUji();

        parent::tearDown();
    }

    private function bersihkanRowUji(): void
    {
        $this->koneksi->table('d_rating')->where('ratingTicketId', self::PAYLOAD_SQLI)->delete();
    }

    /**
     * Mengirim POST sungguhan ke server dev live memakai PHP cURL
     * extension — koneksi TCP terpisah, BUKAN dispatch in-process PHPUnit
     * (lihat catatan kelas). exit; pada endpoint hanya mengakhiri request
     * HTTP tersebut di proses server, tidak menyentuh proses test ini.
     *
     * @param array<string, string> $post
     *
     * @return array{status: int, body: string}
     */
    private function postKeServerLive(string $path, array $post): array
    {
        $ch = curl_init(self::BASE_URL_LIVE . $path);

        // CATATAN LINGKUNGAN DEV: PHP CLI di lingkungan ini terhubung ke
        // OpenSSL Homebrew (openssl.cafile mengarah ke bundle CA publik
        // standar) yang TIDAK memuat root CA lokal dev (FlyEnv-Root-CA)
        // yang menerbitkan sertifikat eult.appdev-papenajam.me — berbeda
        // dari binary `curl` sistem yang memakai /etc/ssl/certs/
        // ca-certificates.crt (SUDAH memuat CA dev tersebut, dikonfirmasi
        // `curl -v` berhasil verify ok). Arahkan CURLOPT_CAINFO secara
        // EKSPLISIT pada bundle sistem yang sama (bukan menonaktifkan
        // verifikasi SSL sama sekali) agar cURL PHP tetap benar-benar
        // memvalidasi rantai sertifikat server dev — hanya berbeda bundle
        // CA yang dirujuk, bukan verifikasi yang dilemahkan.
        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $opsi = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server dev live tanpa error transport (errno=%d: %s). Pastikan server live reachable sebelum menjalankan test ini.', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'body' => $body];
    }

    /**
     * Sanity: pastikan payload SQLi yang dipakai BUKAN nomor tiket yang
     * benar-benar ada (bila kebetulan match, seluruh assertion "tidak
     * bocor tiket lain" di bawah tidak bermakna).
     */
    public function testSanityPayloadSqliTidakCocokTiketApapunSecaraExactMatch(): void
    {
        $baris = $this->koneksi->table('d_ticketing')
            ->where(['ticketTrackingId' => self::PAYLOAD_SQLI])
            ->get()->getRowArray();

        self::assertNull($baris, 'Payload SQLi uji harus dijamin TIDAK cocok exact-match nomor tiket nyata apa pun, agar test kebocoran di bawah bermakna.');
    }

    /**
     * Property 1 (Expected Behavior, checkpoint K1 — cakupan redefinisi):
     * Dispatch HTTP NYATA (cURL ke server dev live) `POST cektiket/rating`
     * dengan payload SQLi pada `nomorTiket` SHALL diproses dengan aman:
     * tidak ada error 500/SQL error, dan yang PALING PENTING — tidak ada
     * baris d_rating BARU yang tercipta terasosiasi dengan tiket LAIN yang
     * sungguhan ada (yang akan membuktikan tautologi WHERE berhasil
     * "menembus" ke tiket nyata).
     *
     * Sebelum fix (kondisi string mentah di Cektiket::rating() lama):
     * byId() dengan kondisi tautologi akan mengembalikan BARIS PERTAMA
     * APAPUN dari tabel d_ticketing (bukan false) — sehingga email
     * selesai() akan terpicu ke alamat email TIKET NYATA YANG TIDAK
     * BERHUBUNGAN dengan payload. Assertion utama test ini adalah bahwa
     * behavior tersebut TIDAK terjadi pada kode saat ini.
     */
    public function testPostRatingDenganPayloadSqliTidakMembocorkanTiketLain(): void
    {
        // Ambil SATU tiket nyata acak sebagai kontrol negatif — bila
        // tautologi WHERE tembus (bug), kita bisa mendeteksi endpoint
        // "menemukan" tiket ini (atau tiket lain manapun) meski payload
        // yang dikirim jelas bukan nomor tiket tersebut.
        $tiketKontrol = $this->koneksi->table('d_ticketing')
            ->select('ticketTrackingId')
            ->limit(1)
            ->get()->getRowArray();
        self::assertIsArray($tiketKontrol, 'Prasyarat test: d_ticketing SHALL berisi setidaknya satu baris data nyata agar kontrol negatif bermakna.');

        // Pastikan tiket kontrol ini tidak sudah punya rating sebelum
        // dispatch (agar assertion "tidak ada rating baru" pasca-dispatch
        // benar-benar disebabkan oleh request ini, bukan data lama).
        $ratingKontrolSebelum = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => $tiketKontrol['ticketTrackingId']])
            ->get()->getRowArray();

        // *** Dispatch HTTP NYATA melalui cURL ke server dev live ***
        $hasil = $this->postKeServerLive('cektiket/rating', [
            'rating'     => '5',
            'nomorTiket' => self::PAYLOAD_SQLI,
        ]);

        // Tidak boleh ada 500/SQL error akibat metacharacter payload.
        self::assertLessThan(
            500,
            $hasil['status'],
            sprintf('Payload SQLi pada nomorTiket SHALL TIDAK memicu SQL error/500 pada endpoint POST cektiket/rating. Status aktual: %d. Body: %s', $hasil['status'], $hasil['body'])
        );

        // *** ASSERTION UTAMA — kebocoran/tembus tiket lain ***
        // Rating tiket kontrol SHALL TIDAK berubah akibat request dengan
        // payload SQLi ini (baik row baru tercipta atau row lama ter-ubah
        // dengan nilai yang bukan miliknya).
        $ratingKontrolSesudah = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => $tiketKontrol['ticketTrackingId']])
            ->get()->getRowArray();

        self::assertSame(
            $ratingKontrolSebelum,
            $ratingKontrolSesudah,
            'BUG CONDITION K1 (checkpoint HTTP): payload SQLi pada nomorTiket TIDAK BOLEH mengubah/menciptakan rating yang terasosiasi dengan tiket nyata LAIN (tiket kontrol) — ini akan membuktikan tautologi WHERE tembus ke tiket yang tidak diminta.'
        );

        // Baris d_rating baru (jika ada, side-effect struktural yang
        // sudah didokumentasikan pada docblock kelas — bukan bug K1)
        // HANYA boleh terasosiasi dengan payload literal itu sendiri.
        $ratingUntukPayloadLiteral = $this->koneksi->table('d_rating')
            ->where(['ratingTicketId' => self::PAYLOAD_SQLI])
            ->get()->getRowArray();

        if ($ratingUntukPayloadLiteral !== null) {
            self::assertSame(
                self::PAYLOAD_SQLI,
                $ratingUntukPayloadLiteral['ratingTicketId'],
                'Baris d_rating yang ter-insert (side-effect struktural rating() untuk nomor tiket manapun yang tidak ada — bukan bug K1) SHALL berasosiasi HANYA dengan payload literal itu sendiri, bukan tiket lain.'
            );
        }
    }

    /**
     * Property 1 (Expected Behavior) — memastikan TIDAK ADA email
     * sungguhan terkirim akibat payload SQLi (bagian dari "tidak
     * membocorkan data tiket nyata"), diverifikasi TANPA mock/stub
     * lapisan email — memakai fakta struktural bahwa email->selesai()
     * hanya dipanggil bila byId() menemukan baris (Cektiket.php,
     * `if ($datas !== false)`), dan byId(array binding) tidak akan
     * pernah menemukan baris untuk payload yang mengandung metacharacter
     * SQL yang tidak match literal apa pun.
     *
     * Pendekatan ini VALID sebagai bukti "tidak ada email arbitrer
     * terkirim" karena satu-satunya code path yang memicu email di
     * rating() bergantung SEPENUHNYA pada hasil byId() — bila query
     * IDENTIK dengan yang dipakai byId() (diverifikasi langsung terhadap
     * db_newtiket) mengembalikan false/null, maka secara struktural
     * TIDAK ADA cara method rating() memanggil email->selesai(). Ini
     * menghindari kebutuhan memodifikasi PengirimEmail/SMTP config
     * (yang berisiko menimbulkan side-effect nyata bila salah) untuk
     * memverifikasi non-pengiriman.
     */
    public function testPayloadSqliTidakMemicuKondisiPengirimanEmail(): void
    {
        // Query IDENTIK dengan yang dipakai byId() untuk menentukan
        // apakah email akan terpicu (Cektiket.php: `$datas = byId([...])`,
        // lalu `if ($datas !== false) { email->selesai(...) }`).
        $datasSetaraById = $this->koneksi->table('d_ticketing')
            ->where(['ticketTrackingId' => self::PAYLOAD_SQLI])
            ->get()->getRowArray();

        self::assertNull(
            $datasSetaraById,
            'Prasyarat struktural: byId() dengan kondisi array binding TIDAK AKAN menemukan baris untuk payload SQLi ini, sehingga blok `if ($datas !== false)` di Cektiket::rating() tidak akan tereksekusi — email->selesai() TIDAK terpicu (Requirements 2.1, 2.2).'
        );
    }
}
