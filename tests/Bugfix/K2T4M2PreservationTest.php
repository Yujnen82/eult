<?php

namespace Tests\Bugfix;

use App\Libraries\Enkripsi;
use App\Models\ModelTicketing;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 7 — Test properti preservasi K2/T4/M2: "Pemilik Sah Tetap Dapat
 * Mengunduh dan Rating" (bugfix.md 3.4, 3.5, 3.6, 3.7, 3.21, 3.22, 3.25,
 * 3.26).
 *
 * ═══════════════════════════════════════════════════════════════════
 * PEMBAGIAN SCOPE (dua bagian berbeda status per K2M2IdorExplorationTest
 * dan T4RatingOwnershipExplorationTest — dibaca sebelum menafsirkan):
 * ═══════════════════════════════════════════════════════════════════
 *
 * BAGIAN A — K2/M2 (Requirements 3.4, 3.5, 3.6, 3.7, 3.25, 3.26):
 * SUDAH DIPERBAIKI (commit 11c1f49 untuk Cektiket::loadpdf/loadattach
 * dan Ticketing::loadpdf/loadattach; route shadowing Validitas::loadpdf
 * diperbaiki pada task 8.3 — lihat K2M2IdorExplorationTest.php untuk
 * detail temuan dan perbaikannya). Task ini TIDAK menduplikasi
 * fixture/skenario tersebut — bagian A di bawah menambahkan test
 * preservasi eksplisit yang men-dispatch request HTTP sungguhan
 * sebagai PEMILIK SAH (bukan staf admin) untuk `Cektiket::loadpdf()`,
 * `Cektiket::loadattach()`, `Validitas::index()` DAN
 * `Validitas::loadpdf()` (testA5, jalur sukses 200+PDF end-to-end,
 * ditambahkan task 8.3 setelah route shadowing diperbaiki), DAN
 * mengulang kontrol positif M2 admin (`Ticketing::loadpdf`/
 * `loadattach`) sebagai baseline eksplisit milik task 7 — agar file
 * test INI SENDIRI (bukan bergantung membaca file lain) menjadi
 * regression guard yang di-re-run setelah T4 fix (task 9) mengubah
 * `Cektiket.php` (file yang SAMA dengan loadpdf()/loadattach() K2),
 * untuk memastikan perubahan T4 tidak mematahkan method tetangga di
 * kelas yang sama.
 *
 * BAGIAN B — T4 rating() (Requirements 3.21, 3.22): BELUM DIPERBAIKI
 * (fix di task 9). Bagian ini adalah BASELINE BARU yang belum
 * dikodifikasi di mana pun — membuktikan bahwa untuk tiket yang
 * LEGITIMATE (nomorTiket benar-benar berasosiasi, terlepas dari
 * kunci — karena rating() SAAT INI tidak memeriksa kunci sama sekali,
 * lihat T4RatingOwnershipExplorationTest.php), urutan operasi produksi
 * `ambilSatu(d_rating)` → `tambah()`/`ubah()` → lookup arsip
 * OUTPUT/TTD via `tabelBuilder()` SEMUANYA bekerja benar. Ini WAJIB
 * tetap benar setelah T4 fix (task 9) menambahkan langkah decode kunci
 * DI DEPAN operasi-operasi ini — fix T4 HANYA boleh mengganti SUMBER
 * `$nomorTiket` (dari POST mentah menjadi hasil decode), TIDAK BOLEH
 * mengubah logika tambah()/ubah()/lookup arsip itu sendiri.
 *
 * KEAMANAN EMAIL (mengikuti pola T4RatingOwnershipExplorationTest.php
 * DAN instruksi task ini secara eksplisit — BERBEDA dari task 6 yang
 * sengaja memakai tiket NYATA milik orang nyata untuk membuktikan
 * real-world impact): task 6 butuh tiket nyata karena esensi bug T4
 * adalah "penyerang bisa menebak NOMOR TIKET ORANG LAIN". Task INI
 * (preservasi) tidak perlu membuktikan dampak dunia nyata — hanya perlu
 * baseline yang aman & repeatable. Test ini memakai TIKET UJI BUATAN
 * (mengikuti konvensi penamaan `ZZK2M2*` milik K2M2IdorExplorationTest,
 * di sini `ZZK2T4M2-0000-00A`) dengan `ticketEmail` PALSU/AMAN
 * (`k2t4m2-uji@example.invalid` — domain `.invalid` dicadangkan RFC
 * 2606 khusus untuk keperluan ini, TIDAK PERNAH me-resolve DNS
 * sungguhan) — SATU tiket ini dipakai untuk KEDUA bukti (rating
 * tersimpan DAN gerbang email true), TIDAK PERNAH melalui HTTP
 * controller (yang akan memanggil `eult_message_kirim()` → exit; +
 * `PengirimEmail::selesai()` sungguhan) — persis pola pembuktian
 * struktural yang sudah dipakai T4RatingOwnershipExplorationTest.php
 * dan K1SqlInjectionHttpIntegrationTest.php.
 *
 * Seluruh data fixture (d_ticketing, d_archive, d_rating untuk tiket
 * uji ini) dibersihkan di setUp() (idempoten) dan tearDown().
 *
 * Requirements: 3.4, 3.5, 3.6, 3.7, 3.21, 3.22, 3.25, 3.26 (bugfix.md)
 */
final class K2T4M2PreservationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Tiket uji buatan (bukan tiket nyata) — bagian A (K2) dan bagian B (T4). */
    private const TIKET = 'ZZK2T4M2-0000-00A';

    /** Domain RFC 2606 `.invalid` — tidak pernah resolve DNS sungguhan, aman dipakai sebagai ticketEmail fixture. */
    private const EMAIL_AMAN = 'k2t4m2-uji@example.invalid';

    private const FILE_OUTPUT = 'TIKET_ZZK2T4M2A01_20260914090000.pdf';

    private const FILE_CHAT = 'CHAT_ZZK2T4M2-0000-00A_20260914090000.pdf';

    private const ISI_OUTPUT = '%PDF-1.4 EULT-K2T4M2-UJI-OUTPUT';

    private const ISI_CHAT = '%PDF-1.4 EULT-K2T4M2-UJI-CHAT';

    private Enkripsi $enkripsi;

    private BaseConnection $koneksi;

    private ModelTicketing $tiket;

    private string $kunci;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enkripsi = new Enkripsi();
        $this->koneksi  = \Config\Database::connect('default');
        $this->tiket    = new ModelTicketing();
        $this->kunci    = (string) $this->enkripsi->encode(self::TIKET);

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test preservasi ini WAJIB tersambung ke database nyata db_newtiket agar baseline bermakna (bukan DB tests/mock).'
        );

        $this->bersihkanData();

        file_put_contents(WRITEPATH . 'uploads/ticketing/' . self::FILE_OUTPUT, self::ISI_OUTPUT);
        file_put_contents(WRITEPATH . 'uploads/chat/' . self::FILE_CHAT, self::ISI_CHAT);

        $this->koneksi->table('d_ticketing')->insert([
            'ticketTrackingId' => self::TIKET,
            'ticketEmail'      => self::EMAIL_AMAN,
            'ticketName'       => 'Pemilik Sah Uji K2T4M2',
            'ticketCreated'    => '2026-09-14 09:00:00',
        ]);

        $this->koneksi->table('d_archive')->insert([
            'archiveId'         => 'ZZK2T4M2A01',
            'archiveFile'       => self::FILE_OUTPUT,
            'archiveJenis'      => 'OUTPUT',
            'archiveTrackingId' => self::TIKET,
        ]);

        $this->koneksi->table('d_replies')->insert([
            'repliesTicketId' => self::TIKET,
            'repliesMessage'  => 'uji preservasi k2t4m2',
            'repliesDate'     => '2026-09-14 09:00:00',
            'repliesStatus'   => 'USER',
            'repliesBy'       => 'uji',
            'repliesFile'     => self::FILE_CHAT,
            'repliesRead'     => '1',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            WRITEPATH . 'uploads/ticketing/' . self::FILE_OUTPUT,
            WRITEPATH . 'uploads/chat/' . self::FILE_CHAT,
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
        $this->koneksi->table('d_rating')->where('ratingTicketId', self::TIKET)->delete();
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET)->delete();
        $this->koneksi->table('d_archive')->where('archiveId', 'ZZK2T4M2A01')->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET)->delete();
    }

    /**
     * @return array<string, string>
     */
    private function sesiAdmin(): array
    {
        return [
            'susrNama'           => 'staf_uji_k2t4m2',
            'susrSgroupNama'     => 'ADMIN',
            'susrSgroupNama_ori' => 'ADMIN',
            'susrProfil'         => 'Staf Uji K2T4M2',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // BAGIAN A — Preservasi K2/M2 (Requirements 3.4, 3.5, 3.6, 3.7,
    // 3.25, 3.26). Method ini SUDAH diperbaiki (task 5/K2M2Idor...);
    // observasi di sini adalah baseline eksplisit milik task 7, dengan
    // fixture independen (tidak menduplikasi K2M2IdorExplorationTest).
    // ═══════════════════════════════════════════════════════════════

    /**
     * Requirement 3.4, 3.7: pemegang kunci sah mengakses link output
     * PDF dari `Cektiket::index()` (`$output_url`) → file tersaji
     * dengan Content-Type/Content-Disposition yang benar.
     */
    public function testA1_PemilikSahMengunduhOutputPdfViaCektiketLoadpdf(): void
    {
        $hasil = $this->get('cektiket/loadpdf/' . $this->kunci);

        $hasil->assertStatus(200);
        self::assertStringContainsString(self::ISI_OUTPUT, $hasil->getBody());
        self::assertSame('application/pdf', $hasil->response()->getHeaderLine('Content-type'));
        self::assertStringContainsString(
            'inline; filename="' . self::FILE_OUTPUT . '"',
            $hasil->response()->getHeaderLine('Content-Disposition')
        );
    }

    /**
     * Requirement 3.5, 3.7: pemegang kunci sah mengklik link lampiran
     * chat (`$load_attach`) dari riwayat balasan miliknya sendiri →
     * lampiran tersaji dengan mime-type yang benar.
     */
    public function testA2_PemilikSahMengunduhLampiranChatMiliknyaSendiri(): void
    {
        $hasil = $this->get('cektiket/loadattach/' . $this->kunci . '/' . self::FILE_CHAT);

        $hasil->assertStatus(200);
        self::assertStringContainsString(self::ISI_CHAT, $hasil->getBody());
        self::assertStringContainsString('pdf', strtolower($hasil->response()->getHeaderLine('Content-Type')));
    }

    /**
     * Requirement 3.6: `Validitas::index($kunci)` dengan kunci valid
     * hasil scan QR tetap menampilkan halaman validasi.
     */
    public function testA3_PemilikSahMengaksesHalamanValiditasIndex(): void
    {
        $hasil = $this->get('validitas/' . $this->kunci);

        $hasil->assertStatus(200);
        self::assertStringContainsString('pages/validitas/index.php', $hasil->getBody());
        self::assertStringContainsString(self::TIKET, $hasil->getBody());
    }

    /**
     * Requirement 3.6, 3.7 (pelengkap eksplisit task 8.3 — mengisi
     * gap yang sebelumnya hanya direferensikan di docblock kelas
     * sebagai "testB3_PemilikSahAksesValiditasLoadpdf" tapi belum
     * pernah diimplementasikan): pemegang kunci VALID hasil scan QR
     * yang mengklik link download PDF dari halaman validasi
     * (`loadpdf_url` yang dirender `Validitas::index()`, memakai
     * `$kunci` yang SAMA dengan halaman itu sendiri) SHALL benar-benar
     * menerima PDF sungguhan dari `Validitas::loadpdf()` — jalur
     * SUKSES end-to-end (bukan hanya jalur tolak 404 yang sudah
     * dicakup K2M2IdorExplorationTest), dibuktikan dengan fixture
     * independen tiket ini sendiri (ZZK2T4M2-0000-00A, archiveJenis
     * OUTPUT).
     */
    public function testA5_PemilikSahMengunduhPdfViaValiditasLoadpdf(): void
    {
        $hasil = $this->get('validitas/loadpdf/' . $this->kunci);

        $hasil->assertStatus(200);
        self::assertStringNotContainsString(
            'pages/validitas/index.php',
            $hasil->getBody(),
            'PDF yang tersaji SHALL bukan body halaman index — mengonfirmasi request benar-benar ditangani Validitas::loadpdf(), bukan ter-shadow ke index.'
        );
        self::assertStringContainsString(self::ISI_OUTPUT, $hasil->getBody());
        self::assertSame('application/pdf', $hasil->response()->getHeaderLine('Content-type'));
        self::assertStringContainsString(
            'inline; filename="' . self::FILE_OUTPUT . '"',
            $hasil->response()->getHeaderLine('Content-Disposition')
        );
    }

    /**
     * Requirement 3.25, 3.26 (M2): staf ADMIN dengan disposisi/akses
     * penuh tetap dapat mengakses `Ticketing::loadpdf()`/`loadattach()`
     * tanpa gangguan — kontrol positif eksplisit milik task 7 (fixture
     * independen dari K2M2IdorExplorationTest, memakai file/tiket
     * ZZK2T4M2 sendiri).
     */
    public function testA4_StafAdminTetapMengaksesTicketingLoadpdfDanLoadattach(): void
    {
        $sesiAdmin = $this->withSession(['logged_in' => $this->sesiAdmin()]);

        $hasilPdf = $sesiAdmin->get('ticketing/loadpdf/' . self::FILE_OUTPUT);
        $hasilPdf->assertStatus(200);
        self::assertStringContainsString(self::ISI_OUTPUT, $hasilPdf->getBody());

        $hasilAttach = $this->withSession(['logged_in' => $this->sesiAdmin()])
            ->get('ticketing/loadattach/' . self::FILE_CHAT);
        $hasilAttach->assertStatus(200);
        self::assertStringContainsString(self::ISI_CHAT, $hasilAttach->getBody());
    }

    // ═══════════════════════════════════════════════════════════════
    // BAGIAN B — Baseline T4 rating() (Requirements 3.21, 3.22). BELUM
    // diperbaiki (fix di task 9) — bagian ini BUKAN regression guard
    // atas fix yang sudah ada, melainkan BASELINE yang HARUS tetap
    // benar SETELAH task 9 menambahkan langkah decode kunci di depan
    // operasi model yang sama.
    // ═══════════════════════════════════════════════════════════════

    /**
     * Requirement 3.21 (bagian 1/3): rating BARU (belum pernah ada di
     * d_rating) untuk tiket yang legitimate tersimpan lewat urutan
     * operasi produksi `ambilSatu(d_rating)` kosong → `tambah()` —
     * PERSIS urutan yang dieksekusi `Cektiket::rating()`
     * (Cektiket.php:106-116), dipanggil langsung terhadap model
     * produksi (bukan query pengganti), TANPA melalui pembungkus HTTP
     * (yang akan memanggil `eult_message_kirim()`/exit; dan
     * `PengirimEmail::selesai()` sungguhan — lihat docblock kelas).
     */
    public function testB1_RatingBaruTersimpanUntukTiketLegitimateViaTambah(): void
    {
        $param = ['ratingNilai' => '5', 'ratingTicketId' => self::TIKET];

        $cek = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => self::TIKET]);
        self::assertFalse($cek, 'Prasyarat: belum ada rating existing untuk tiket uji ini (memastikan skenario ini benar-benar menguji cabang tambah(), bukan ubah()).');

        $proses = $this->tiket->tambah('d_rating', $param);

        self::assertTrue(
            $proses,
            'Baseline T4 (3.21): tambah() d_rating produksi SHALL berhasil untuk tiket legitimate — logika empty($cek) ? tambah() : ubah() SHALL tidak berubah oleh fix T4 (task 9), yang hanya mengubah SUMBER $nomorTiket (dari POST mentah menjadi hasil decode kunci), bukan operasi model ini sendiri.'
        );

        $baris = $this->koneksi->table('d_rating')->where(['ratingTicketId' => self::TIKET])->get()->getRowArray();
        self::assertIsArray($baris);
        self::assertSame(5.0, (float) $baris['ratingNilai']);
    }

    /**
     * Requirement 3.21 (bagian 2/3): rating YANG SUDAH ADA untuk tiket
     * legitimate ter-update lewat cabang `ubah()` — melengkapi B1
     * (yang menguji cabang tambah()) dengan cabang sebaliknya, agar
     * KEDUA sisi logika `empty($cek) ? tambah() : ubah()` tercakup
     * sebagai baseline (persis permintaan task: "logika empty($cek) ?
     * tambah() : ubah() dipertahankan").
     */
    public function testB2_RatingExistingTerupdateUntukTiketLegitimateViaUbah(): void
    {
        // Seed rating awal (mensimulasikan rating() dipanggil sekali
        // sebelumnya untuk tiket yang sama).
        self::assertTrue($this->tiket->tambah('d_rating', ['ratingNilai' => '3', 'ratingTicketId' => self::TIKET]));

        $paramBaru = ['ratingNilai' => '4', 'ratingTicketId' => self::TIKET];

        $cek = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => self::TIKET]);
        self::assertIsArray($cek, 'Prasyarat: rating existing harus ada agar skenario ini benar-benar menguji cabang ubah().');

        $proses = $this->tiket->ubah('d_rating', $paramBaru, ['ratingTicketId' => self::TIKET]);

        self::assertTrue(
            $proses,
            'Baseline T4 (3.21): ubah() d_rating produksi SHALL berhasil untuk rating existing milik tiket legitimate — cabang update logika empty($cek) ? tambah() : ubah() SHALL tidak berubah oleh fix T4.'
        );

        $baris = $this->koneksi->table('d_rating')->where(['ratingTicketId' => self::TIKET])->get()->getRowArray();
        self::assertIsArray($baris);
        self::assertSame(4.0, (float) $baris['ratingNilai'], 'Rating SHALL benar-benar ter-update (bukan insert baris kedua) — ratingTicketId adalah PRIMARY KEY pada d_rating.');
    }

    /**
     * Requirement 3.21 (bagian 3/3): lookup arsip OUTPUT/TTD via
     * `tabelBuilder()` — PERSIS query yang dieksekusi
     * `Cektiket::rating()` (Cektiket.php:118-122) untuk menentukan
     * `$lampiran` yang akan dilampirkan ke email `selesai()` — SHALL
     * menemukan berkas OUTPUT yang benar-benar berasosiasi dengan
     * tiket legitimate ini.
     */
    public function testB3_LookupArsipOutputTtdViaTabelBuilderMenemukanLampiranYangBenar(): void
    {
        $output = $this->tiket->tabelBuilder('d_archive')
            ->where('archiveTrackingId', self::TIKET)
            ->whereIn('archiveJenis', ['OUTPUT', 'TTD'])
            ->get()->getRowArray() ?? false;

        self::assertIsArray(
            $output,
            'Baseline T4 (3.21): lookup arsip OUTPUT/TTD produksi SHALL menemukan berkas yang benar-benar berasosiasi dengan tiket legitimate ini — query tabelBuilder() ini SHALL tidak berubah oleh fix T4.'
        );
        self::assertSame(self::FILE_OUTPUT, $output['archiveFile']);

        $lampiran = $output['archiveFile'];
        self::assertNotFalse($lampiran, 'Requirement 3.21: "email selesai() beserta lampiran output/TTD jika ada" — lampiran SHALL ter-resolve ke nama file yang benar untuk tiket legitimate.');
    }

    /**
     * Requirement 3.21 (bagian 4/4, gerbang email): membuktikan bahwa
     * gerbang `if ($datas !== false)` SEBELUM `email->selesai(...)`
     * dipanggil (Cektiket.php:120) BERNILAI TRUE untuk tiket legitimate
     * — memanggil `byId()` produksi yang SAMA persis dengan yang
     * dipakai `rating()` pada baris yang sama, TANPA benar-benar
     * memicu pengiriman (lihat docblock kelas: pola pembuktian
     * struktural yang sama dipakai T4RatingOwnershipExplorationTest
     * dan K1SqlInjectionHttpIntegrationTest, hanya arah kesimpulan
     * berlawanan — di sana membuktikan bug/gerbang true untuk tiket
     * yang SEHARUSNYA tidak boleh, di sini membuktikan preservasi/
     * gerbang true untuk tiket yang MEMANG legitimate/boleh).
     *
     * `ticketEmail` yang ter-resolve SHALL berupa alamat aman
     * (`.invalid`) — TIDAK PERNAH alamat sungguhan.
     */
    public function testB4_GerbangPengirimanEmailSelesaiTerpenuhiDenganAlamatAman(): void
    {
        $datas = $this->tiket->byId(['ticketTrackingId' => self::TIKET]);

        self::assertIsArray(
            $datas,
            'Baseline T4 (3.21): byId() produksi SHALL menemukan tiket legitimate ini — gerbang `if ($datas !== false)` SHALL true, sehingga email->selesai() SECARA FAKTUAL akan terpicu bila rating() dipanggil via HTTP untuk tiket ini (perilaku yang HARUS dipertahankan, dengan atau tanpa langkah decode kunci tambahan dari fix T4).'
        );
        self::assertSame(
            self::EMAIL_AMAN,
            $datas['ticketEmail'],
            'ticketEmail yang akan menerima email selesai() SHALL berupa alamat uji aman (.invalid) — bukan alamat sungguhan milik siapa pun, karena test ini TIDAK PERNAH benar-benar mengirim email (lihat docblock kelas).'
        );
    }

    /**
     * Requirement 3.22: pesan sukses (`eult_message_kirim(...)` dengan
     * tipe `success`) muncul ketika `$proses` (hasil tambah()/ubah())
     * bernilai true — membuktikan PRASYARAT gerbang tersebut (bukan
     * memanggil eult_message_kirim() itu sendiri, karena fungsi tersebut
     * `exit;` — lihat app/Helpers/eult_message_helper.php — sehingga
     * tidak bisa dipanggil langsung dari dalam test PHPUnit tanpa
     * menghentikan proses test). `$proses` sudah dibuktikan true pada
     * B1/B2 di atas untuk KEDUA cabang tambah()/ubah(); test ini
     * menegaskan ulang kombinasi keduanya sebagai satu observasi
     * eksplisit untuk requirement 3.22.
     */
    public function testB5_PrasyaratPesanSuksesTerpenuhiSetelahTambahDanUbahBerhasil(): void
    {
        // Cabang tambah() (rating baru).
        $prosesTambah = $this->tiket->tambah('d_rating', ['ratingNilai' => '5', 'ratingTicketId' => self::TIKET]);
        self::assertTrue(
            $prosesTambah,
            'Requirement 3.22 (prasyarat cabang tambah): $proses SHALL true agar eult_message_kirim(..., \'success\') dipanggil oleh Cektiket::rating() — Cektiket.php:124 `if ($proses) { eult_message_kirim(...) }`.'
        );

        // Cabang ubah() (rating existing, memakai baris yang baru dibuat di atas).
        $prosesUbah = $this->tiket->ubah('d_rating', ['ratingNilai' => '4', 'ratingTicketId' => self::TIKET], ['ratingTicketId' => self::TIKET]);
        self::assertTrue(
            $prosesUbah,
            'Requirement 3.22 (prasyarat cabang ubah): $proses SHALL true agar eult_message_kirim(..., \'success\') dipanggil — cabang update SHALL tidak berubah oleh fix T4.'
        );
    }
}
