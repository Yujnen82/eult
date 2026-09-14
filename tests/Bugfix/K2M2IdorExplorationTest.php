<?php

namespace Tests\Bugfix;

use App\Libraries\Enkripsi;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 5 — Test eksplorasi bug condition K2/M2: IDOR pada Endpoint
 * Download Berkas (Cektiket::loadpdf/loadattach, Validitas::loadpdf,
 * Ticketing::loadpdf/loadattach).
 *
 * ═══════════════════════════════════════════════════════════════════
 * TEMUAN PENTING — dibaca SEBELUM menjalankan/menafsirkan test ini:
 * ═══════════════════════════════════════════════════════════════════
 * Berbeda dengan K1 (task 1), verifikasi independen terhadap kode
 * SAAT INI (bukan asumsi) menunjukkan KEEMPAT method target SUDAH
 * diperbaiki — dikonfirmasi via git log (`git log -- app/Controllers/
 * Cektiket.php app/Controllers/Validitas.php app/Controllers/Ticketing.php`):
 * commit 11c1f49 "fix(security): implement ownership and access control
 * for file endpoints and upload helper" (Mon Sep 14 2026), yang SUDAH
 * TERCOMMIT di git history — bukan bagian dari perubahan working-tree
 * belum-commit milik task K1 (task 1-4) di sesi ini. Commit tersebut
 * juga menambahkan tests/unit/AksesBerkasTest.php (219 baris) yang
 * SUDAH meng-cover 4 dari 5 skenario yang diminta task ini
 * (Cektiket::loadpdf, Cektiket::loadattach, Ticketing::loadpdf,
 * Ticketing::loadattach — dikonfirmasi lulus 8/8 assertion saat
 * dijalankan ulang terhadap kode saat ini). Test tersebut TIDAK
 * meng-cover Validitas::loadpdf() maupun payload path traversal
 * eksplisit — keduanya diuji di sini.
 *
 * Verifikasi independen dilakukan dengan MEMBACA source code keempat
 * method (bukan sekadar percaya klaim), DAN dengan benar-benar
 * men-dispatch request HTTP (via FeatureTestTrait, in-process CI4
 * routing — bukan asumsi statis) untuk tiap skenario di bawah:
 *
 * 1. Cektiket::loadpdf(string $kunci = '') — men-decode $kunci via
 *    Enkripsi::decode(), query d_archive dengan
 *    ['archiveTrackingId' => $id, 'archiveJenis' => 'OUTPUT'] (array
 *    binding, pola K1), 404 bila tidak ketemu. TIDAK ADA lagi
 *    parameter nama-file-mentah pada signature.
 * 2. Cektiket::loadattach(string $kunci = '', string $namaFile = '') —
 *    men-decode $kunci, basename() $namaFile, validasi pasangan
 *    terhadap d_replies (repliesTicketId + repliesFile), 404 bila
 *    tidak match.
 * 3. Validitas::loadpdf(string $kunci = '') — men-decode $kunci, query
 *    d_archive dengan archiveTrackingId + archiveJenis (TTD lalu
 *    fallback OUTPUT), 404 bila tidak ketemu.
 * 4. Ticketing::loadpdf(string $namaFile = '') DAN
 *    Ticketing::loadattach(string $namaFile = '') — KEDUANYA memanggil
 *    private method bolehAksesTiket(string $idTiket): bool SEBELUM
 *    serve (baris 1121-1160-an app/Controllers/Ticketing.php).
 *    bolehAksesTiket() meloloskan grup ADMIN/OPERATOR* tanpa batasan
 *    tambahan, atau staf lain HANYA bila disposisiById() cocok ATAU
 *    unit staf cocok ticketAssign tiket pemilik berkas — 403 bila
 *    tidak cocok. INI SECARA SPESIFIK MENJAWAB ketidakpastian yang
 *    diminta diverifikasi: Ticketing::loadpdf() MEMILIKI ownership
 *    check yang sama seperti loadattach(), bukan hanya salah satu.
 *
 * KONSEKUENSI UNTUK METODOLOGI BUG-CONDITION: task ini menginstruksikan
 * "Test ini HARUS GAGAL pada kode belum diperbaiki" — namun kode SAAT
 * INI TIDAK LAGI "belum diperbaiki" untuk KETIGA method
 * (Cektiket::loadpdf/loadattach, Ticketing::loadpdf/loadattach).
 * Mengikuti instruksi eksplisit (jangan memaksa test gagal secara
 * artifisial, jangan melemahkan assertion, jangan modifikasi controller
 * untuk reintroduce vulnerability), test untuk KETIGA method tersebut
 * mengenkode HASIL SEBENARNYA yang teramati: seluruh percobaan
 * serangan DITOLAK (404/403), bukan "tersaji 200".
 *
 * SAAT DITULIS PERTAMA KALI (task 5) — verifikasi independen menemukan
 * SATU BUG NYATA YANG BERBEDA (bukan pola K2/M2 "IDOR karena tidak ada
 * validasi kepemilikan", melainkan ROUTE SHADOWING): `Validitas::loadpdf()`
 * method itu sendiri SUDAH benar (memvalidasi kunci dengan pola yang
 * sama seperti 3 method lain), TAPI TIDAK PERNAH TEREKSEKUSI SAMA SEKALI
 * via HTTP karena route `validitas/(:any)` → Validitas::index terdaftar
 * LEBIH DULU daripada `validitas/loadpdf/(:any)` → Validitas::loadpdf
 * pada group route yang sama (app/Config/Routes.php). Temuan ini
 * dilaporkan ke orchestrator sebagai bug terpisah, di luar scope task 5.
 *
 * DIPERBAIKI PADA TASK 8.3: urutan pendaftaran route ditukar
 * (`loadpdf/(:any)` kini didaftarkan sebelum `(:any)` pada
 * app/Config/Routes.php) — dikonfirmasi ulang secara independen pada
 * task 8.3 via pembacaan source fresh dan dispatch HTTP sungguhan.
 * testSkenario3_RouteShadowingValiditasLoadpdfSudahDiperbaiki() dan
 * testSkenario3b_KunciValidBerhasilMengunduhPdfSetelahRouteDiperbaiki()
 * di bawah kini membuktikan JALUR SUKSES (kunci valid → 200 + PDF asli)
 * DAN jalur tolak (nama file mentah → 404 dari loadpdf() itu sendiri,
 * bukan lagi ter-shadow ke index).
 *
 * Bila di masa depan salah satu method ini di-regresi (ownership check
 * dihapus/dilewati, atau urutan route ditukar balik), test ini akan
 * mulai GAGAL — berfungsi sebagai regression guard.
 *
 * @internal
 */
final class K2M2IdorExplorationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const TIKET_A = 'ZZK2M2-0000-00A';
    private const TIKET_B = 'ZZK2M2-0000-00B';

    private const FILE_OUTPUT_A  = 'TIKET_ZZK2M2A0001_20260913120000.pdf';
    private const FILE_TTD_A     = 'TIKET_ZZK2M2A0002_20260913120005.pdf';
    private const FILE_CHAT_A    = 'CHAT_ZZK2M2-0000-00A_20260913120000.pdf';
    private const FILE_CHAT_B    = 'CHAT_ZZK2M2-0000-00B_20260913120001.pdf';

    private const ISI_OUTPUT_A = '%PDF-1.4 EULT-K2M2-UJI-OUTPUT-A';
    private const ISI_TTD_A    = '%PDF-1.4 EULT-K2M2-UJI-TTD-A';
    private const ISI_CHAT_A   = '%PDF-1.4 EULT-K2M2-UJI-CHAT-A';
    private const ISI_CHAT_B   = '%PDF-1.4 EULT-K2M2-UJI-CHAT-B';

    private Enkripsi $enkripsi;

    private BaseConnection $koneksi;

    private string $kunciA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enkripsi = new Enkripsi();
        $this->koneksi  = \Config\Database::connect('default');
        $this->kunciA   = (string) $this->enkripsi->encode(self::TIKET_A);

        self::assertSame('db_newtiket', $this->koneksi->database, 'Test ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi ownership check bermakna (bukan DB tests/mock).');

        $this->bersihkanData();

        file_put_contents(WRITEPATH . 'uploads/ticketing/' . self::FILE_OUTPUT_A, self::ISI_OUTPUT_A);
        file_put_contents(WRITEPATH . 'uploads/ticketing/' . self::FILE_TTD_A, self::ISI_TTD_A);
        file_put_contents(WRITEPATH . 'uploads/chat/' . self::FILE_CHAT_A, self::ISI_CHAT_A);
        file_put_contents(WRITEPATH . 'uploads/chat/' . self::FILE_CHAT_B, self::ISI_CHAT_B);

        $this->koneksi->table('d_ticketing')->insert(['ticketTrackingId' => self::TIKET_A]);
        $this->koneksi->table('d_ticketing')->insert(['ticketTrackingId' => self::TIKET_B]);

        $this->koneksi->table('d_archive')->insert([
            'archiveId'         => 'ZZK2M2A0001',
            'archiveFile'       => self::FILE_OUTPUT_A,
            'archiveJenis'      => 'OUTPUT',
            'archiveTrackingId' => self::TIKET_A,
        ]);
        $this->koneksi->table('d_archive')->insert([
            'archiveId'         => 'ZZK2M2A0002',
            'archiveFile'       => self::FILE_TTD_A,
            'archiveJenis'      => 'TTD',
            'archiveTrackingId' => self::TIKET_A,
        ]);

        $this->koneksi->table('d_replies')->insert([
            'repliesTicketId' => self::TIKET_A,
            'repliesMessage'  => 'uji k2m2 a',
            'repliesDate'     => '2026-09-13 12:00:00',
            'repliesStatus'   => 'USER',
            'repliesBy'       => 'uji',
            'repliesFile'     => self::FILE_CHAT_A,
            'repliesRead'     => '1',
        ]);
        $this->koneksi->table('d_replies')->insert([
            'repliesTicketId' => self::TIKET_B,
            'repliesMessage'  => 'uji k2m2 b (pemohon lain)',
            'repliesDate'     => '2026-09-13 12:00:01',
            'repliesStatus'   => 'USER',
            'repliesBy'       => 'uji',
            'repliesFile'     => self::FILE_CHAT_B,
            'repliesRead'     => '1',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            WRITEPATH . 'uploads/ticketing/' . self::FILE_OUTPUT_A,
            WRITEPATH . 'uploads/ticketing/' . self::FILE_TTD_A,
            WRITEPATH . 'uploads/chat/' . self::FILE_CHAT_A,
            WRITEPATH . 'uploads/chat/' . self::FILE_CHAT_B,
        ] as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        $this->bersihkanData();

        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET_A)->delete();
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET_B)->delete();
        $this->koneksi->table('d_archive')->where('archiveId', 'ZZK2M2A0001')->delete();
        $this->koneksi->table('d_archive')->where('archiveId', 'ZZK2M2A0002')->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET_A)->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET_B)->delete();
    }

    /**
     * @return array<string, string>
     */
    private function sesi(string $grup): array
    {
        return [
            'susrNama'           => 'staf_uji_k2m2',
            'susrSgroupNama'     => $grup,
            'susrSgroupNama_ori' => $grup,
            'susrProfil'         => 'Staf Uji K2M2',
        ];
    }

    /**
     * Grup staf yang boleh membuka modul ticketing namun bukan
     * ADMIN/OPERATOR — dipakai sebagai "unit A" tanpa disposisi/unit
     * assignment atas TIKET_A, agar mewakili "staf non-admin unit lain".
     */
    private function grupTanpaHakTiket(): string
    {
        $baris = $this->koneksi->table('s_user_group_modul')
            ->select('sgroupmodulSgroupNama')
            ->where('sgroupmodulSusrmodulNama', 'ticketing')
            ->where('sgroupmodulSusrmodulRead', '1')
            ->where('sgroupmodulSgroupNama !=', 'ADMIN')
            ->notLike('sgroupmodulSgroupNama', 'OPERATOR')
            ->limit(1)
            ->get()->getRowArray();

        return (string) ($baris['sgroupmodulSgroupNama'] ?? '');
    }

    /**
     * Skenario 1 (task 5): `GET cektiket/loadpdf/{namaFileTebakan}` TANPA
     * memegang kunci terenkripsi apa pun.
     *
     * Payload serangan: nama file mentah OUTPUT PDF (yang predictable,
     * `TIKET_{archiveId}_{timestamp}.pdf`, sesuai catatan task) langsung
     * di path — TANPA kunci apa pun.
     *
     * HASIL TERAMATI (bukan asumsi): route `cektiket/loadpdf/(:any)` kini
     * memetakan segment ke $kunci (bukan $namaFile), dan
     * Cektiket::loadpdf() men-decode segment tersebut sebagai kunci
     * terenkripsi. Nama file mentah BUKAN kunci hasil Enkripsi::encode()
     * yang valid → decode() mengembalikan false → query d_archive dengan
     * kondisi archiveTrackingId=false tidak match → 404. File TIDAK
     * tersaji.
     */
    public function testSkenario1_LoadpdfTanpaKunciTidakMenyajikanFile(): void
    {
        $hasil = $this->get('cektiket/loadpdf/' . self::FILE_OUTPUT_A);

        // BUKAN assertion "200 + konten tersaji" seperti diminta task
        // (yang mengasumsikan kode belum diperbaiki) — hasil sebenarnya
        // pada kode SAAT INI adalah 404, ditolak.
        $hasil->assertStatus(404);
        self::assertStringNotContainsString(
            self::ISI_OUTPUT_A,
            $hasil->getBody(),
            'COUNTEREXAMPLE TIDAK ditemukan: nama file mentah tanpa kunci terenkripsi TIDAK berhasil menyajikan konten OUTPUT PDF pada kode saat ini (berbeda dari asumsi task).'
        );
    }

    /**
     * Skenario 2 (task 5): `GET cektiket/loadattach/{namaFileTebakan}`
     * milik pemohon LAIN (TIKET_B) diakses menggunakan kunci milik
     * TIKET_A (memegang kunci sah untuk tiket sendiri, tapi mencoba
     * menebak/mengganti nama file milik tiket lain — cross-ticket IDOR).
     *
     * HASIL TERAMATI: Cektiket::loadattach() memvalidasi PASANGAN
     * (kunci→repliesTicketId hasil decode) DAN ($namaFile) terhadap
     * d_replies — FILE_CHAT_B terasosiasi dengan TIKET_B, bukan TIKET_A.
     * Query dengan kondisi ['repliesTicketId' => TIKET_A hasil decode,
     * 'repliesFile' => FILE_CHAT_B] tidak match apa pun → 404.
     */
    public function testSkenario2_LoadattachFileMilikPemohonLainTidakTersaji(): void
    {
        $hasil = $this->get('cektiket/loadattach/' . $this->kunciA . '/' . self::FILE_CHAT_B);

        $hasil->assertStatus(404);
        self::assertStringNotContainsString(
            self::ISI_CHAT_B,
            $hasil->getBody(),
            'COUNTEREXAMPLE TIDAK ditemukan: kunci sah milik TIKET_A TIDAK dapat dipakai mengunduh lampiran chat milik TIKET_B (pemohon lain) pada kode saat ini.'
        );
    }

    /**
     * Skenario tambahan (regresi rute lama): mengakses loadattach TANPA
     * kunci apa pun (pola pra-fix, satu segment nama file saja) — rute
     * tersebut sudah tidak terdaftar lagi.
     */
    public function testSkenario2b_LoadattachTanpaKunciRuteTidakTerdaftar(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->get('cektiket/loadattach/' . self::FILE_CHAT_A);
    }

    /**
     * Skenario 3 (task 5): `GET validitas/loadpdf/{namaFileTebakan}`
     * dengan nama file mentah (bukan kunci terenkripsi apa pun).
     *
     * HASIL TERAMATI: Validitas::loadpdf(string $kunci = '') men-decode
     * segment sebagai kunci. Nama file mentah bukan hasil encode() yang
     * valid → decode() false → kedua query d_archive (TTD lalu fallback
     * OUTPUT) tidak dieksekusi dengan trackingId bermakna → 404.
     */
    /**
     * Skenario 3 (task 5, diperbarui pasca-fix task 8.3): `GET
     * validitas/loadpdf/{namaFileTebakan}` — BUG ROUTE SHADOWING YANG
     * DITEMUKAN DI SINI SUDAH DIPERBAIKI.
     *
     * TEMUAN AWAL (didokumentasikan saat test ini pertama ditulis, task
     * 5): method Validitas::loadpdf() itu sendiri BENAR (memvalidasi
     * kunci, 404 bila tidak match), NAMUN tidak pernah tereksekusi via
     * HTTP karena route group `validitas` (app/Config/Routes.php)
     * mendaftarkan `(:any)` → Validitas::index SEBELUM
     * `loadpdf/(:any)` → Validitas::loadpdf, sehingga `(:any)`
     * menangkap SELURUH path (termasuk `loadpdf/xxx`) sebagai satu
     * nilai `$kunci` untuk Validitas::index().
     *
     * FIX YANG DITERAPKAN (bagian dari scope task 8.3, sesuai desain
     * task 5 checkpoint & task 8.3 "Route validitas/loadpdf/(:any)
     * tetap satu segment"): urutan pendaftaran route ditukar —
     * `loadpdf/(:any)` kini didaftarkan SEBELUM `(:any)` pada
     * app/Config/Routes.php:
     *
     *   $routes->group('validitas', static function ($routes) {
     *       $routes->get('loadpdf/(:any)', 'Validitas::loadpdf/$1'); // kini didaftarkan lebih dulu
     *       $routes->get('(:any)', 'Validitas::index/$1');           // hanya menangkap prefix lain
     *   });
     *
     * PERILAKU SAAT INI (diverifikasi ulang via dispatch HTTP
     * sungguhan, bukan asumsi): `validitas/loadpdf/*` kini benar-benar
     * mencapai Validitas::loadpdf(). Nama file mentah (bukan hasil
     * Enkripsi::encode()) → decode() gagal → 404 dari loadpdf() itu
     * sendiri (body teks polos "File tidak ditemukan.", BUKAN body
     * halaman index). Kunci VALID hasil encode() milik tiket yang
     * benar-benar memiliki archiveJenis TTD/OUTPUT → loadpdf() men-serve
     * PDF sungguhan (200, Content-Type application/pdf) — dibuktikan
     * eksplisit pada testSkenario3b_KunciValidBerhasilMengunduhPdfSetelahRouteDiperbaiki()
     * di bawah, memakai fixture FILE_TTD_A yang sudah tersedia di
     * setUp() kelas ini.
     *
     * Assertion di bawah kini mengenkode PERILAKU YANG SUDAH DIPERBAIKI
     * sebagai regression guard — bila route shadowing ini kelak
     * ter-regresi (urutan ditukar balik), test ini akan gagal.
     */
    public function testSkenario3_RouteShadowingValiditasLoadpdfSudahDiperbaiki(): void
    {
        $hasilNamaFileMentah = $this->get('validitas/loadpdf/' . self::FILE_TTD_A);

        self::assertSame(
            404,
            $hasilNamaFileMentah->response()->getStatusCode(),
            'REGRESI TERDETEKSI bila ini gagal: request ke validitas/loadpdf/* dengan nama file mentah (bukan kunci valid) SHALL mencapai Validitas::loadpdf() itu sendiri (404 dari decode() gagal), BUKAN ter-shadow lagi ke Validitas::index (yang akan mengembalikan 200).'
        );
        self::assertStringNotContainsString(
            'pages/validitas/index.php',
            $hasilNamaFileMentah->getBody(),
            'Mengonfirmasi body yang dirender BUKAN view index (yang akan menandakan route shadowing kembali terjadi) — melainkan body 404 polos dari loadpdf() itu sendiri.'
        );
    }

    /**
     * Skenario 3b (pelengkap eksplisit task 8.3): kunci VALID hasil
     * scan QR yang benar-benar berasosiasi dengan archiveJenis TTD kini
     * BERHASIL mencapai Validitas::loadpdf() dan menerima PDF
     * sungguhan — membuktikan fix route shadowing tidak hanya menolak
     * payload serangan (skenario 3 di atas) tapi juga benar-benar
     * MEMULIHKAN jalur sukses untuk pemegang kunci sah (bukan sekadar
     * "tidak lagi 200 palsu dari index", tapi "sekarang 200 PDF asli
     * dari loadpdf()").
     */
    public function testSkenario3b_KunciValidBerhasilMengunduhPdfSetelahRouteDiperbaiki(): void
    {
        $hasil = $this->get('validitas/loadpdf/' . $this->kunciA);

        $hasil->assertStatus(200);
        self::assertStringNotContainsString(
            'pages/validitas/index.php',
            $hasil->getBody(),
            'PDF yang tersaji SHALL bukan body halaman index — mengonfirmasi request ini benar-benar ditangani loadpdf(), bukan ter-shadow.'
        );
        self::assertStringContainsString(
            self::ISI_TTD_A,
            $hasil->getBody(),
            'Konten PDF TTD_A yang sebenarnya SHALL tersaji untuk kunci valid milik TIKET_A.'
        );
        self::assertSame('application/pdf', $hasil->response()->getHeaderLine('Content-type'));
    }

    /**
     * Skenario 4 (task 5): sebagai staf NON-ADMIN unit A (tanpa
     * disposisi/unit assignment atas TIKET_A), `GET ticketing/loadpdf/
     * {namaFileUnitB}` — nama file OUTPUT PDF milik TIKET_A yang bukan
     * unit staf tersebut (mewakili "unit B" dari perspektif staf ini).
     *
     * HASIL TERAMATI: Ticketing::loadpdf() memanggil bolehAksesTiket()
     * SEBELUM serve. Staf dengan grup non-ADMIN/OPERATOR dan tanpa
     * disposisiById() match maupun unit match ticketAssign TIKET_A →
     * bolehAksesTiket() mengembalikan false → 403. File TIDAK tersaji.
     *
     * Ini secara langsung menjawab hal yang diminta diverifikasi:
     * Ticketing::loadpdf() MEMILIKI pengecekan ownership yang sama
     * seperti loadattach() (bukan hanya salah satu method).
     */
    public function testSkenario4_StafNonAdminUnitLainDitolakLoadpdf(): void
    {
        $grup = $this->grupTanpaHakTiket();

        if ($grup === '') {
            self::markTestSkipped('Tidak ada grup staf uji (non-ADMIN/OPERATOR dengan akses modul ticketing) pada data referensi — tidak dapat menyusun skenario staf unit lain.');
        }

        $hasil = $this->withSession(['logged_in' => $this->sesi($grup)])
            ->get('ticketing/loadpdf/' . self::FILE_OUTPUT_A);

        $hasil->assertStatus(403);
        self::assertStringNotContainsString(
            self::ISI_OUTPUT_A,
            $hasil->getBody(),
            'COUNTEREXAMPLE TIDAK ditemukan: staf non-admin tanpa hak atas TIKET_A TIDAK berhasil mengakses ticketing/loadpdf untuk berkas TIKET_A tanpa ownership check pada kode saat ini.'
        );
    }

    /**
     * Skenario 4b (pelengkap M2, disebut task sebagai bagian goal):
     * variant loadattach dengan staf non-admin unit lain — melengkapi
     * cakupan yang sudah ada di AksesBerkasTest namun memakai fixture
     * K2M2 milik test ini agar independen.
     */
    public function testSkenario4b_StafNonAdminUnitLainDitolakLoadattach(): void
    {
        $grup = $this->grupTanpaHakTiket();

        if ($grup === '') {
            self::markTestSkipped('Tidak ada grup staf uji (non-ADMIN/OPERATOR dengan akses modul ticketing) pada data referensi — tidak dapat menyusun skenario staf unit lain.');
        }

        $hasil = $this->withSession(['logged_in' => $this->sesi($grup)])
            ->get('ticketing/loadattach/' . self::FILE_CHAT_A);

        $hasil->assertStatus(403);
        self::assertStringNotContainsString(
            self::ISI_CHAT_A,
            $hasil->getBody(),
            'COUNTEREXAMPLE TIDAK ditemukan: staf non-admin tanpa hak atas TIKET_A TIDAK berhasil mengakses ticketing/loadattach untuk lampiran TIKET_A tanpa ownership check pada kode saat ini.'
        );
    }

    /**
     * Kontrol positif: staf ADMIN tetap dapat mengakses berkas TANPA
     * batasan ownership tambahan (baseline yang harus tetap berfungsi,
     * dipakai memastikan 403 di atas BUKAN karena kesalahan fixture/
     * routing, melainkan benar-benar gerbang ownership check).
     */
    public function testKontrolPositif_AdminTetapMengaksesLoadpdfDanLoadattach(): void
    {
        $sesiAdmin = $this->withSession(['logged_in' => $this->sesi('ADMIN')]);

        $hasilPdf = $sesiAdmin->get('ticketing/loadpdf/' . self::FILE_OUTPUT_A);
        $hasilPdf->assertStatus(200);
        self::assertStringContainsString(self::ISI_OUTPUT_A, $hasilPdf->getBody());

        $hasilAttach = $this->withSession(['logged_in' => $this->sesi('ADMIN')])
            ->get('ticketing/loadattach/' . self::FILE_CHAT_A);
        $hasilAttach->assertStatus(200);
        self::assertStringContainsString(self::ISI_CHAT_A, $hasilAttach->getBody());
    }

    /**
     * Skenario 5 (task 5): path traversal (`../../.env` style) tetap
     * diblokir basename() pada KEEMPAT method — diuji terhadap masing-
     * masing endpoint dengan kunci/kondisi yang secara struktural TIDAK
     * bisa lolos ke tahap basename() manapun ATAU (bila lolos ke tahap
     * itu) basename() menetralkan payload traversal.
     *
     * Cektiket::loadpdf()/Validitas::loadpdf() TIDAK LAGI menerima nama
     * file mentah sama sekali (parameter kini $kunci, dicocokkan lewat
     * decode()+query, bukan langsung dipakai sebagai path) — sehingga
     * payload traversal diperlakukan sebagai kunci tidak valid → 404,
     * tanpa basename() perlu "menyelamatkan" apa pun pada jalur ini.
     * Cektiket::loadattach()/Ticketing::loadpdf()/loadattach() secara
     * eksplisit memanggil basename($namaFile) SEBELUM query DB.
     */
    /**
     * Skenario 5 (task 5): path traversal (`../../.env` style) tetap
     * diblokir basename() pada KEEMPAT method — diuji terhadap masing-
     * masing endpoint.
     *
     * TEMUAN TEKNIS (bukan bug — perilaku framework CI4 sendiri):
     * segmen `..` pada URI dinormalisasi/ditolak oleh router CI4
     * SEBELUM mencapai controller apa pun (dikonfirmasi:
     * `cektiket/loadpdf/../../.env` menghasilkan
     * `PageNotFoundException: Can't find a route for 'GET: .env'` —
     * router mengevaluasi `.env` sebagai path root, bukan meneruskan
     * string literal `../../.env` ke controller). Ini SATU LAPIS
     * pertahanan TAMBAHAN di atas basename() pada level controller —
     * bukan pengganti, keduanya independen. Test ini menangkap
     * exception tersebut sebagai bukti traversal diblokir SEBELUM
     * mencapai basename(), lalu tetap memverifikasi endpoint yang
     * TIDAK memicu PageNotFoundException (Ticketing::loadpdf/loadattach,
     * yang memakai pola `(.*)` — menerima payload literal, DI SANA
     * basename() controller yang menjadi lapisan pemblokir).
     */
    public function testSkenario5_PathTraversalDiblokirPadaSeluruhEndpoint(): void
    {
        $payloadTraversal = '../../.env';

        // cektiket/loadpdf, cektiket/loadattach, validitas/loadpdf
        // memakai pola `(.*)`/`([^/]+)` yang, dikombinasikan dengan
        // normalisasi URI CI4, menolak `..` SEBELUM routing selesai —
        // ditangkap sebagai PageNotFoundException di sini (lapisan
        // pertahanan framework, terpisah dari basename() controller).
        try {
            $hasil = $this->get('cektiket/loadpdf/' . rawurlencode($payloadTraversal));
            self::assertNotSame(200, $hasil->response()->getStatusCode());
            self::assertStringNotContainsString('DB_PASSWORD', $hasil->getBody());
        } catch (PageNotFoundException $e) {
            self::assertStringContainsString('.env', $e->getMessage(), 'Traversal SHALL ditolak oleh router (bukti tidak reachable), bukan dieksekusi sebagai path file.');
        }

        try {
            $hasil = $this->get('cektiket/loadattach/' . $this->kunciA . '/' . rawurlencode($payloadTraversal));
            self::assertNotSame(200, $hasil->response()->getStatusCode());
            self::assertStringNotContainsString('DB_PASSWORD', $hasil->getBody());
        } catch (PageNotFoundException $e) {
            self::assertStringContainsString('.env', $e->getMessage());
        }

        try {
            $hasil = $this->get('validitas/loadpdf/' . rawurlencode($payloadTraversal));
            // Catatan (pasca-fix route shadowing task 8.3): request ini
            // kini benar-benar mencapai Validitas::loadpdf() — payload
            // traversal bukan kunci valid hasil encode() → decode()
            // gagal → 404 dari loadpdf() itu sendiri, BUKAN lagi
            // ter-route ke Validitas::index. Yang penting diverifikasi
            // di sini HANYA bahwa isi .env TIDAK pernah muncul di body.
            self::assertStringNotContainsString('DB_PASSWORD', $hasil->getBody());
        } catch (PageNotFoundException $e) {
            self::assertStringContainsString('.env', $e->getMessage());
        }

        // Ticketing::loadpdf/loadattach memakai pola `(.*)` DENGAN
        // filter 'auth' — di sinilah basename() controller (bukan
        // router) yang menjadi lapisan pemblokir utama yang diuji.
        $sesiAdmin = $this->withSession(['logged_in' => $this->sesi('ADMIN')]);

        try {
            $hasilTicketingLoadpdf = $sesiAdmin->get('ticketing/loadpdf/' . rawurlencode($payloadTraversal));
            self::assertNotSame(200, $hasilTicketingLoadpdf->response()->getStatusCode());
            self::assertStringNotContainsString('DB_PASSWORD', $hasilTicketingLoadpdf->getBody());
        } catch (PageNotFoundException $e) {
            self::assertStringContainsString('.env', $e->getMessage());
        }

        try {
            $hasilTicketingLoadattach = $this->withSession(['logged_in' => $this->sesi('ADMIN')])
                ->get('ticketing/loadattach/' . rawurlencode($payloadTraversal));
            self::assertNotSame(200, $hasilTicketingLoadattach->response()->getStatusCode());
            self::assertStringNotContainsString('DB_PASSWORD', $hasilTicketingLoadattach->getBody());
        } catch (PageNotFoundException $e) {
            self::assertStringContainsString('.env', $e->getMessage());
        }
    }
}
