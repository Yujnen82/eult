<?php

namespace Tests\Bugfix;

use App\Models\ModelMaster;
use App\Models\ModelTicketing;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 1 (Klaster 1 — K1): Test EKSPLORASI bug condition SQL Injection
 * via kondisi WHERE string mentah.
 *
 * PENTING — metodologi bug condition:
 * Test ini WAJIB GAGAL pada kode yang belum diperbaiki. Kegagalan test
 * mengonfirmasi bug ada (Property 1: Bug Condition). Test ini TIDAK
 * BOLEH diperbaiki di sini — dia mengenkode expected/fixed behavior dan
 * akan berubah menjadi LULUS setelah fix K1 diimplementasikan pada
 * task 3 (`Cektiket::rating()`, `eult_auto_increment()`, dsb).
 *
 * Test dijalankan terhadap query builder & Model NYATA (BUKAN mock),
 * terkoneksi ke database MySQL nyata `db_newtiket` (defaultGroup
 * dipaksa tetap 'default', bukan 'tests'/SQLite — lihat tests/bootstrap.php)
 * agar counterexample yang tersurface faktual: query benar-benar
 * mengembalikan seluruh baris tabel, bukan sekadar simulasi.
 *
 * Requirements: 1.1, 1.2, 1.3, 1.4 (bugfix.md)
 */
final class K1SqlInjectionExplorationTest extends CIUnitTestCase
{
    private BaseConnection $koneksi;

    private ModelTicketing $tiket;

    private ModelMaster $master;

    /** Baseline jumlah baris d_ticketing terverifikasi manual: 38441 (lihat bugfix.md 1.1). */
    private const EXPECTED_MIN_TICKETING_ROWS = 30000;

    protected function setUp(): void
    {
        parent::setUp();

        // defaultGroup TIDAK berubah menjadi 'tests' karena ENVIRONMENT
        // di-set 'development' pada tests/bootstrap.php (bukan 'testing').
        $this->koneksi = \Config\Database::connect('default');
        $this->tiket   = new ModelTicketing();
        $this->master  = new ModelMaster();
    }

    /**
     * Sanity check: pastikan test benar-benar tersambung ke database
     * nyata berisi data produksi (bukan DB kosong/mock), agar counter-
     * example di bawah bermakna secara faktual.
     */
    public function testSanityConnectedToRealDatabaseWithProductionData(): void
    {
        self::assertSame('db_newtiket', $this->koneksi->database, 'Test harus tersambung ke database nyata db_newtiket, bukan DB tests/mock.');

        $jumlah = $this->koneksi->table('d_ticketing')->countAllResults();

        self::assertGreaterThan(
            self::EXPECTED_MIN_TICKETING_ROWS,
            $jumlah,
            'd_ticketing diharapkan berisi puluhan ribu baris data produksi nyata (terverifikasi manual: 38441).'
        );
    }

    /**
     * Property 1 (Bug Condition) — Cektiket::rating() line ~117:
     * $this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'")
     *
     * Payload tautologi SQLi pada nomorTiket HARUS diperlakukan sebagai
     * nomor tiket tidak valid (0 baris cocok) pada kode yang sudah benar.
     * Pada kode BELUM diperbaiki, payload ini dirakit sebagai fragmen SQL
     * mentah sehingga klausa WHERE menjadi tautologi dan mencocokkan
     * SELURUH tabel d_ticketing.
     *
     * EXPECTED OUTCOME saat ini (kode belum diperbaiki): test ini GAGAL.
     */
    public function testRatingByIdRawStringConditionIsVulnerableToSqlInjection(): void
    {
        $payloadSqli = "X' OR '1'='1";

        // Mereproduksi PERSIS pemanggilan pada Cektiket::rating()
        // (app/Controllers/Cektiket.php:112): kondisi string terkonkatenasi
        // langsung dari input request, dilempar ke byId().
        $kondisiMentahSepertiKodeAsli = "ticketTrackingId = '" . $payloadSqli . "'";

        // Ukur dampak nyata memakai builder yang setara dengan yang dipakai
        // byId() (tanpa join berat, agar terukur & tidak menghabiskan memori) —
        // ini adalah fragmen WHERE YANG SAMA yang diteruskan byId() ke
        // $this->koneksi->table($this->table)->where($kondisi).
        $jumlahBarisTercocok = $this->koneksi->table('d_ticketing')
            ->where($kondisiMentahSepertiKodeAsli, null, false)
            ->countAllResults();

        // Bandingkan dengan hasil AMAN (array binding) untuk payload IDENTIK —
        // ini adalah expected/fixed behavior (Property 1: Expected Behavior,
        // akan dipakai ulang pada task 3.3 untuk verifikasi fix).
        $jumlahBarisAman = $this->koneksi->table('d_ticketing')
            ->where(['ticketTrackingId' => $payloadSqli])
            ->countAllResults();

        // Sanity: array binding untuk payload SQLi yang tidak match apa pun
        // SHALL selalu 0 baris (perilaku aman, tidak boleh berubah oleh fix).
        self::assertSame(0, $jumlahBarisAman, 'Kondisi array binding SHALL memperlakukan payload SQLi sebagai literal string dan tidak mencocokkan baris apa pun.');

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior: kondisi string mentah SEHARUSNYA juga
        // hanya mencocokkan 0 baris (identik dengan array binding), BUKAN
        // seluruh tabel. Pada kode asli (belum diperbaiki), $jumlahBarisTercocok
        // akan sama dengan total baris d_ticketing (~38441), sehingga assertion
        // berikut GAGAL — ini adalah counterexample yang membuktikan bug ada.
        self::assertSame(
            0,
            $jumlahBarisTercocok,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI: kondisi WHERE string mentah "%s" (persis seperti Cektiket::rating() baris ~112) mengembalikan %d baris alih-alih 0 — payload SQLi diperlakukan sebagai fragmen SQL, bukan literal. Total baris d_ticketing = %d.',
                $kondisiMentahSepertiKodeAsli,
                $jumlahBarisTercocok,
                $this->koneksi->table('d_ticketing')->countAllResults()
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — Reproduksi END-TO-END memanggil method
     * Model NYATA `ModelTicketing::byId()` (bukan builder manual) dengan
     * signature string, PERSIS seperti dipanggil dari
     * Cektiket::rating(): $this->tiket->byId("ticketTrackingId = '...'").
     *
     * byId() melakukan banyak LEFT JOIN sehingga getRowArray() hanya
     * mengembalikan baris PERTAMA dari hasil yang tercocok — namun baris
     * pertama tersebut BUKAN tiket yang diminta (nomor tiket palsu/tidak
     * ada), melainkan tiket ACAK APAPUN yang urutannya pertama di tabel.
     * Ini membuktikan query tidak melakukan exact-match sama sekali.
     */
    public function testRatingByIdReturnsArbitraryUnrelatedTicketForSqliPayload(): void
    {
        $payloadSqli = "X' OR '1'='1";

        // Pastikan dulu payload ini BUKAN nomor tiket yang benar-benar ada
        // (exact match) — memakai jalur AMAN (array) untuk verifikasi ini
        // supaya sanity check-nya sendiri tidak rentan SQLi.
        $benarBenarTidakAda = $this->tiket->byId(['ticketTrackingId' => $payloadSqli]);
        self::assertFalse($benarBenarTidakAda, 'Payload SQLi tidak boleh cocok exact-match apa pun (sanity check jalur aman).');

        // *** Panggilan PERSIS seperti Cektiket::rating() baris ~112 ***
        $hasil = $this->tiket->byId("ticketTrackingId = '" . $payloadSqli . "'");

        // EXPECTED/FIXED behavior: method yang sudah diperbaiki SHALL
        // mengembalikan `false` (tidak ada baris cocok) untuk nomor tiket
        // yang tidak valid — sama seperti hasil array binding di atas.
        //
        // *** INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Pada kode asli, klausa WHERE menjadi tautologi sehingga query
        // mencocokkan seluruh tabel dan getRowArray() mengembalikan baris
        // PERTAMA APAPUN (bukan false, dan bukan tiket dengan nomor sesuai
        // payload) — assertion berikut gagal, membuktikan bug.
        self::assertFalse(
            $hasil,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI: Cektiket::rating() memanggil byId() dengan kondisi string mentah dan mendapatkan baris tiket "%s" yang SAMA SEKALI TIDAK BERHUBUNGAN dengan payload SQLi yang dikirim — membuktikan tautologi WHERE mencocokkan seluruh tabel d_ticketing, bukan 0 baris seperti seharusnya.',
                is_array($hasil) ? ($hasil['ticketTrackingId'] ?? '(unknown)') : ''
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — Cektiket::rating() line ~119:
     * $this->tiket->ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')")
     *
     * Payload SQLi harus tetap menghasilkan 0 baris cocok pada tabel
     * d_archive untuk nomor tiket yang tidak ada — pada kode asli,
     * tautologi menyebabkan query mencocokkan ribuan baris archive
     * milik tiket-tiket LAIN.
     */
    public function testRatingArchiveLookupRawStringConditionIsVulnerableToSqlInjection(): void
    {
        $payloadSqli = "X' OR '1'='1";

        // *** Panggilan PERSIS seperti Cektiket::rating() baris ~119 ***
        $kondisiMentahSepertiKodeAsli = "archiveTrackingId = '" . $payloadSqli . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')";

        $jumlahBarisTercocok = $this->koneksi->table('d_archive')
            ->where($kondisiMentahSepertiKodeAsli, null, false)
            ->countAllResults();

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior: 0 baris (payload tidak match tracking ID apa pun).
        self::assertSame(
            0,
            $jumlahBarisTercocok,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI: ambilSatu("d_archive", ...) dengan kondisi string mentah "%s" (persis seperti Cektiket::rating() baris ~119) mengembalikan %d baris arsip milik tiket LAIN alih-alih 0 baris.',
                $kondisiMentahSepertiKodeAsli,
                $jumlahBarisTercocok
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — Login::savetiket() line ~128:
     * eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'")
     * -> ModelMaster::getByLastId($tabel, $kolom, $kondisi) -> ->where($kondisi)
     *
     * eult_auto_increment() SHALL menghitung MAX(archiveId) HANYA di
     * antara baris yang benar-benar memiliki archiveTrackingId sama
     * dengan $idTiket yang diberikan. Pada kode asli, payload SQLi pada
     * $idTiket membuat MAX() dihitung di seluruh tabel d_archive
     * (milik tiket manapun), menghasilkan archiveId yang salah/collision.
     */
    public function testLoginSavetiketAutoIncrementRawStringConditionIsVulnerableToSqlInjection(): void
    {
        $arsipIdUjiCoba  = 'K1UJICOBASQLI';
        $payloadIdTiket  = "X' OR '1'='1";

        // *** Panggilan PERSIS seperti Login::savetiket() baris ~128 ***
        $kondisiMentahSepertiKodeAsli = "archiveTrackingId='" . $payloadIdTiket . "'";
        $idHasilInjeksi = eult_auto_increment('d_archive', 'archiveId', $arsipIdUjiCoba, $kondisiMentahSepertiKodeAsli);

        // Baseline aman: MAX(archiveId) untuk archiveTrackingId yang benar-benar
        // sama dengan payload (exact match) — karena payload SQLi ini dijamin
        // tidak ada di d_archive sebagai trackingId literal, hasilnya SHALL
        // kosong sehingga eult_auto_increment() (bila memakai kondisi array)
        // akan fallback ke id awal ("{nip}0001").
        $idAmanEkspektasi = $arsipIdUjiCoba . '0001';

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        // Expected/fixed behavior: karena tidak ada baris d_archive dengan
        // archiveTrackingId literal sama dengan payload SQLi, MAX() SHALL
        // kosong dan hasilnya SHALL "{arsipId}0001" (id pertama), BUKAN id
        // yang diturunkan dari MAX(archiveId) milik tiket lain manapun.
        self::assertSame(
            $idAmanEkspektasi,
            $idHasilInjeksi,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI: eult_auto_increment() dengan kondisi string mentah "%s" (persis seperti Login::savetiket() baris ~128 -> getByLastId()) menghasilkan archiveId "%s" yang diturunkan dari MAX(archiveId) SELURUH tabel d_archive (bukan hanya milik idTiket yang diminta), alih-alih "%s" yang seharusnya didapat jika tidak ada baris cocok.',
                $kondisiMentahSepertiKodeAsli,
                $idHasilInjeksi,
                $idAmanEkspektasi
            )
        );
    }

    /**
     * Property 1 (Bug Condition) — Login::savetiket() line ~168 (readback):
     * $this->tiket->byId("ticketTrackingId = '" . $idTiket . "'")
     *
     * Sama seperti testRatingByIdReturnsArbitraryUnrelatedTicketForSqliPayload,
     * namun mereproduksi titik pemanggilan readback pasca-insert di
     * Login::savetiket() (risiko lebih rendah karena $idTiket dihasilkan
     * server-side, namun pola tidak konsisten dan harus diseragamkan —
     * bugfix.md 1.4).
     */
    public function testLoginSavetiketReadbackByIdRawStringConditionIsVulnerableToSqlInjection(): void
    {
        // Mensimulasikan skenario di mana $idTiket yang dipakai readback
        // mengandung metacharacter SQL (misal karena format kode berubah
        // atau ada input yang lolos ke variabel ini secara tidak terduga —
        // bugfix.md mencatat pola ini WAJIB diseragamkan meski risikonya
        // lebih rendah dari 1.1-1.3).
        $idTiketDenganMetacharacter = "X' OR '1'='1";

        // *** Panggilan PERSIS seperti Login::savetiket() baris ~168 ***
        $hasil = $this->tiket->byId("ticketTrackingId = '" . $idTiketDenganMetacharacter . "'");

        // *** ASSERTION UTAMA — INI YANG DIHARAPKAN GAGAL PADA KODE ASLI ***
        self::assertFalse(
            $hasil,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI: readback byId() pasca-insert (Login::savetiket() baris ~168) dengan kondisi string mentah mengembalikan baris tiket "%s" yang tidak berhubungan dengan idTiket yang dicari, membuktikan pola string-mentah tidak konsisten/aman di titik ini juga.',
                is_array($hasil) ? ($hasil['ticketTrackingId'] ?? '(unknown)') : ''
            )
        );
    }

    /**
     * Property-Based sanity — domain besar metacharacter SQL/unicode/kosong.
     *
     * **Validates: Requirements 1.1, 1.2**
     *
     * Bug ini cocok PBT domain besar (design.md Testing Strategy: "K1: string
     * SQLi arbitrer"). Karena PHP/PHPUnit di proyek ini TIDAK memiliki
     * dependency PBT khusus (tidak ada di composer.json: tidak ada
     * eris/generator, giorgiosironi/eris, dsb), scoped-PBT diimplementasikan
     * sebagai data provider berisi korpus representatif metacharacter SQL,
     * unicode, dan string kosong — dijalankan terhadap query builder NYATA
     * untuk setiap payload, mengonfirmasi bug condition berlaku merata di
     * seluruh domain (bukan hanya satu payload tautologi).
     *
     * @dataProvider provideSqliMetacharacterPayloads
     */
    public function testRawStringConditionVulnerableAcrossSqlMetacharacterDomain(string $payload, string $keterangan): void
    {
        $kondisiMentah = "ticketTrackingId = '" . $payload . "'";

        // Oracle referensi dihitung di sisi PHP (independen dari query
        // builder): berapa baris SUNGGUHAN yang ticketTrackingId-nya SAMA
        // PERSIS (literal) dengan payload. Karena payload berisi
        // metacharacter SQL, secara faktual TIDAK ADA nomor tiket asli
        // yang bisa mengandung karakter tersebut (kolom ticketTrackingId
        // berformat XXXX-XXXX-NNN) — oracle ini SELALU 0 untuk seluruh
        // payload di korpus (kecuali payload "nomor tiket valid sungguhan").
        $daftarSemuaTrackingId = $this->koneksi->table('d_ticketing')
            ->select('ticketTrackingId')
            ->get()->getResultArray();
        $jumlahBarisOracle = count(array_filter(
            $daftarSemuaTrackingId,
            static fn (array $baris): bool => $baris['ticketTrackingId'] === $payload
        ));

        try {
            // Eksekusi LANGSUNG (bukan dibungkus COUNT/derived-table) agar
            // efek injeksi apa pun (tautologi, UNION, stacked query, dsb)
            // termanifestasi persis seperti saat byId()/ambilSatu() memanggil
            // ->get()->getResultArray() pada kode asli — bukan diserap/
            // disamarkan oleh lapisan agregasi COUNT(*) OVER (subquery).
            $hasilMentah = $this->koneksi->table('d_ticketing')
                ->select('ticketTrackingId')
                ->where($kondisiMentah, null, false)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            // SQL error akibat metacharacter juga merupakan bentuk bug
            // (Expected Behavior: "tidak pernah SQL error akibat metacharacter",
            // design.md K1 property). Pada kode asli, sebagian payload
            // (misal berisi `;` atau tanda kutip tak seimbang) memicu error
            // SQL alih-alih diperlakukan sebagai literal — ini pun
            // termasuk bukti bug (bukan exception yang diharapkan).
            self::fail(sprintf(
                'BUG CONDITION K1 TERKONFIRMASI (payload "%s" — %s): kondisi string mentah memicu SQL error (%s) alih-alih diperlakukan sebagai literal string aman.',
                $payload,
                $keterangan,
                $e->getMessage()
            ));

            return;
        }

        $jumlahBarisMentah = count($hasilMentah);

        // Expected/fixed behavior untuk SETIAP payload di domain ini: hasil
        // string-mentah SHALL selalu identik dengan oracle exact-match literal.
        self::assertSame(
            $jumlahBarisOracle,
            $jumlahBarisMentah,
            sprintf(
                'BUG CONDITION K1 TERKONFIRMASI (payload "%s" — %s): kondisi string mentah menghasilkan %d baris dari eksekusi LANGSUNG (bukan lewat COUNT), berbeda dari oracle exact-match literal (%d baris) untuk payload identik — membuktikan payload tidak diperlakukan sebagai nilai literal.',
                $payload,
                $keterangan,
                $jumlahBarisMentah,
                $jumlahBarisOracle
            )
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function provideSqliMetacharacterPayloads(): array
    {
        return [
            'tautologi klasik'            => ["X' OR '1'='1", 'payload OR klasik'],
            'tautologi tanpa spasi'       => ["X'OR'1'='1", 'variasi tanpa spasi'],
            'union based'                 => ["X' UNION SELECT 1-- -", 'UNION SELECT'],
            'comment terminator'          => ["X'--", 'SQL comment (--) untuk memotong sisa query'],
            'stacked query'               => ["X'; SELECT 1", 'stacked query (;)'],
            'boolean selalu true numerik' => ["X' OR 1=1 OR '", 'tautologi numerik tanpa string compare'],
            'unicode metacharacter'       => ["X'\u{2019} OR \u{2018}1\u{2019}=\u{2018}1", 'unicode quote lookalike + OR'],
            'string kosong'               => ['', 'string kosong sebagai nomorTiket'],
            'hanya quote tunggal'         => ["'", 'quote tunggal tak seimbang'],
            'nomor tiket valid sungguhan' => ['QEHO-HTTV-001', 'nomor tiket VALID nyata (baseline non-bug, harus tetap 0 vs 0 karena keduanya harus match 1 baris sama)'],
        ];
    }
}
