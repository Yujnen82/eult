<?php

namespace Tests\Bugfix;

use App\Models\ModelMaster;
use App\Models\ModelTicketing;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Task 2 (Klaster 1 — K1): Test PRESERVASI (Property 2) — Query Valid
 * Tetap Berfungsi Normal.
 *
 * PENTING — metodologi observation-first:
 * Test ini mengobservasi perilaku kode BELUM diperbaiki untuk input VALID
 * (nomor tiket yang benar-benar ada, TANPA metacharacter SQL) dan
 * menetapkannya sebagai baseline yang WAJIB dipertahankan identik setelah
 * fix K1 (array binding) diimplementasikan pada task 3. Test ini WAJIB
 * LULUS pada kode belum diperbaiki — kelulusan mengonfirmasi baseline
 * yang harus tidak berubah pasca-fix (bukan mengonfirmasi bug, berbeda
 * dari K1SqlInjectionExplorationTest.php pada task 1).
 *
 * Pendekatan sama dengan task 1: query fragment/model call yang PERSIS
 * meniru titik pemanggilan di controller (byId(), ambilSatu(),
 * eult_auto_increment()) dijalankan langsung terhadap query builder &
 * Model NYATA (BUKAN mock), terkoneksi ke database MySQL nyata
 * `db_newtiket` (lihat tests/bootstrap.php) — TANPA menembak method
 * controller (Cektiket::rating()/Login::savetiket()) secara HTTP penuh,
 * agar tidak memicu side-effect nyata yang tidak perlu (pengiriman email
 * sungguhan via PengirimEmail::selesai()/buat()). Task 1 mengonfirmasi
 * pola non-invasif yang sama ini valid untuk membuktikan/mempertahankan
 * perilaku titik-titik query yang menjadi scope fix K1.
 *
 * Requirements: 3.1, 3.2, 3.3 (bugfix.md)
 */
final class K1SqlInjectionPreservationTest extends CIUnitTestCase
{
    private BaseConnection $koneksi;

    private ModelTicketing $tiket;

    private ModelMaster $master;

    /** Nomor tiket VALID sungguhan, terverifikasi ada di d_ticketing (lihat bugfix.md, task 1 corpus). */
    private const NOMOR_TIKET_VALID = 'QEHO-HTTV-001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');
        $this->tiket   = new ModelTicketing();
        $this->master  = new ModelMaster();
    }

    /**
     * Sanity check: pastikan nomor tiket referensi benar-benar ada di
     * database nyata (bukan mock) sebelum dipakai sebagai baseline —
     * jika baris ini tidak ada, seluruh assertion preservasi di bawah
     * tidak bermakna.
     */
    public function testSanityReferenceTicketExistsInRealDatabase(): void
    {
        self::assertSame('db_newtiket', $this->koneksi->database, 'Test harus tersambung ke database nyata db_newtiket, bukan DB tests/mock.');

        $baris = $this->koneksi->table('d_ticketing')
            ->where(['ticketTrackingId' => self::NOMOR_TIKET_VALID])
            ->get()->getRowArray();

        self::assertNotNull($baris, sprintf('Nomor tiket referensi "%s" WAJIB ada di d_ticketing sungguhan agar baseline preservasi bermakna.', self::NOMOR_TIKET_VALID));
    }

    /**
     * Property 2 (Preservation) — Cektiket::rating() line ~112:
     * $this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'")
     *
     * OBSERVASI pada kode belum diperbaiki: untuk nomorTiket VALID yang
     * benar-benar cocok satu baris (tanpa metacharacter SQL), kondisi
     * string mentah dan kondisi array binding SHALL menghasilkan baris
     * IDENTIK — karena tidak ada metacharacter untuk dieksploitasi,
     * concatenation string biasa berlaku sama seperti literal binding.
     * Ini adalah baseline yang wajib dipertahankan fix task 3 (byId()
     * akan diubah memakai array, namun hasilnya untuk nomorTiket valid
     * SHALL TIDAK berubah).
     */
    public function testRatingByIdRawStringConditionMatchesArrayBindingForValidTicket(): void
    {
        $nomorTiket = self::NOMOR_TIKET_VALID;

        // *** Panggilan PERSIS seperti Cektiket::rating() baris ~112 (kode asli) ***
        $hasilStringMentah = $this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'");

        // Baseline aman (array binding) — akan menjadi bentuk fix pada task 3.
        $hasilArrayBinding = $this->tiket->byId(['ticketTrackingId' => $nomorTiket]);

        self::assertIsArray($hasilStringMentah, 'Nomor tiket valid SHALL mengembalikan baris array (bukan false) pada kondisi string mentah saat ini.');
        self::assertIsArray($hasilArrayBinding, 'Nomor tiket valid SHALL mengembalikan baris array (bukan false) pada kondisi array binding.');

        self::assertSame(
            $nomorTiket,
            $hasilStringMentah['ticketTrackingId'],
            'Baseline: kondisi string mentah SHALL mencocokkan tiket yang benar (bukan tiket lain) untuk nomorTiket valid tanpa metacharacter.'
        );

        // *** BASELINE UTAMA — HARUS TETAP IDENTIK PASCA-FIX (Preservation 3.1) ***
        self::assertSame(
            $hasilArrayBinding,
            $hasilStringMentah,
            'PRESERVATION BASELINE K1: untuk nomorTiket VALID tanpa metacharacter SQL, hasil byId() kondisi string mentah SHALL identik dengan kondisi array binding — baseline ini WAJIB dipertahankan pasca-fix task 3 (Requirements 3.1).'
        );
    }

    /**
     * Property 2 (Preservation) — Cektiket::rating() line ~119:
     * $this->tiket->ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')")
     *
     * OBSERVASI: untuk nomorTiket valid, hasil query lookup archive
     * (baik ditemukan maupun tidak — QEHO-HTTV-001 hanya memiliki
     * archiveJenis='TIKET', BUKAN 'OUTPUT'/'TTD', sehingga hasil yang
     * benar secara faktual adalah false/tidak ditemukan) SHALL identik
     * antara kondisi string mentah dan kondisi array + whereIn setara.
     * Ini menetapkan baseline bahwa lookup archive OUTPUT/TTD untuk
     * tiket valid tanpa arsip OUTPUT/TTD tetap konsisten "tidak
     * ditemukan" pada kedua bentuk kondisi.
     */
    public function testRatingArchiveLookupRawStringConditionMatchesArrayBindingForValidTicket(): void
    {
        $nomorTiket = self::NOMOR_TIKET_VALID;

        // *** Panggilan PERSIS seperti Cektiket::rating() baris ~119 (kode asli) ***
        $kondisiMentahSepertiKodeAsli = "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')";
        $hasilStringMentah            = $this->tiket->ambilSatu('d_archive', $kondisiMentahSepertiKodeAsli);

        // Bentuk aman setara (array kondisi kombinasi whereIn) — akan menjadi
        // bentuk fix pada task 3.1.
        $hasilArrayBinding = $this->koneksi->table('d_archive')
            ->where('archiveTrackingId', $nomorTiket)
            ->whereIn('archiveJenis', ['OUTPUT', 'TTD'])
            ->get()->getRowArray() ?? false;

        // *** BASELINE UTAMA — HARUS TETAP IDENTIK PASCA-FIX (Preservation 3.1) ***
        self::assertSame(
            $hasilArrayBinding,
            $hasilStringMentah,
            'PRESERVATION BASELINE K1: untuk nomorTiket VALID tanpa metacharacter SQL, hasil ambilSatu("d_archive", ...) kondisi string mentah SHALL identik dengan kondisi array+whereIn setara — baseline ini WAJIB dipertahankan pasca-fix task 3 (Requirements 3.1).'
        );
    }

    /**
     * Property 2 (Preservation) — d_rating insert/update logic dari
     * Cektiket::rating() (`empty($cek) ? tambah() : ubah()`).
     *
     * OBSERVASI: pemeriksaan existing rating (`ambilSatu('d_rating',
     * ['ratingTicketId' => $nomorTiket])`, SUDAH memakai array binding
     * di kode asli — bukan bagian scope perbaikan K1 pada baris ini,
     * hanya perlu dipastikan TIDAK TERDAMPAK fix K1 di baris lain pada
     * method yang sama) untuk nomorTiket valid mengembalikan bentuk yang
     * konsisten (false jika belum pernah rating, array jika sudah).
     * Baseline ini dipakai murni sebagai OBSERVASI struktur (bukan
     * assertion nilai spesifik, karena data rating bisa berubah antar
     * run) — bagian penting yang diverifikasi preservasinya adalah
     * BENTUK return value (false|array) tetap konsisten, bukan angka.
     */
    public function testRatingExistingCheckReturnsConsistentShapeForValidTicket(): void
    {
        $nomorTiket = self::NOMOR_TIKET_VALID;

        // *** Panggilan PERSIS seperti Cektiket::rating() (kondisi ratingTicketId — SUDAH array di kode asli) ***
        $cek = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => $nomorTiket]);

        self::assertTrue(
            $cek === false || is_array($cek),
            'PRESERVATION BASELINE K1: pemeriksaan existing rating (ambilSatu d_rating) SHALL selalu mengembalikan false atau array — bentuk ini menentukan cabang tambah()/ubah() pada Cektiket::rating() dan SHALL TIDAK berubah oleh fix K1 pada bagian byId()/ambilSatu(d_archive) di method yang sama (Requirements 3.1).'
        );

        if (is_array($cek)) {
            self::assertSame($nomorTiket, $cek['ratingTicketId'], 'Baris rating yang ditemukan SHALL berasosiasi dengan nomorTiket yang diminta (exact-match, bukan collision), konsisten dengan kondisi array yang sudah dipakai di titik ini.');
        }
    }

    /**
     * Property 2 (Preservation) — Login::savetiket() line ~128:
     * eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'")
     * -> ModelMaster::getByLastId($tabel, $kolom, $kondisi) -> ->where($kondisi)
     *
     * OBSERVASI pada kode belum diperbaiki: untuk idTiket VALID (nomor
     * tiket yang benar-benar ada dan memiliki baris d_archive terkait,
     * tanpa metacharacter SQL), hasil eult_auto_increment() dengan
     * kondisi string mentah SHALL identik dengan hasil bila kondisi
     * yang setara diberikan sebagai array — MAX(archiveId) yang dihitung
     * sama, sehingga archiveId berurutan yang dihasilkan (Requirements
     * 3.2: "archiveId berurutan tetap benar") tidak berubah pasca-fix
     * task 3.2 (yang akan mengubah signature eult_auto_increment()
     * menerima array).
     */
    public function testLoginSavetiketAutoIncrementRawStringConditionMatchesArrayBindingForValidTicket(): void
    {
        $idTiketValid = self::NOMOR_TIKET_VALID;
        $arsipIdContoh = 'K1UJICOBAPRESERVASI';

        // *** Panggilan PERSIS seperti Login::savetiket() baris ~128 (kode asli) ***
        $kondisiMentahSepertiKodeAsli = "archiveTrackingId='" . $idTiketValid . "'";
        $idHasilStringMentah          = eult_auto_increment('d_archive', 'archiveId', $arsipIdContoh, $kondisiMentahSepertiKodeAsli);

        // Nilai MAX(archiveId) yang setara memakai kondisi array (baseline aman,
        // akan menjadi bentuk fix pada task 3.2) — direplikasi manual di sini
        // (bukan memanggil eult_auto_increment() dengan array, karena signature
        // saat ini hanya menerima string) memakai ModelMaster::getByLastId()
        // langsung dengan kondisi array.
        $terakhirArrayBinding = $this->master->getByLastId('d_archive', 'archiveId', ['archiveTrackingId' => $idTiketValid]);
        $idHasilArrayBinding  = (! empty($terakhirArrayBinding) && isset($terakhirArrayBinding['archiveId']))
            ? $arsipIdContoh . sprintf('%04d', (int) substr((string) $terakhirArrayBinding['archiveId'], 12, 4) + 1)
            : $arsipIdContoh . '0001';

        // *** BASELINE UTAMA — HARUS TETAP IDENTIK PASCA-FIX (Preservation 3.2) ***
        self::assertSame(
            $idHasilArrayBinding,
            $idHasilStringMentah,
            'PRESERVATION BASELINE K1: untuk idTiket VALID tanpa metacharacter SQL, hasil eult_auto_increment() (MAX(archiveId) via kondisi string mentah) SHALL identik dengan hasil kondisi array binding setara — archiveId berurutan yang dihasilkan tidak boleh berubah pasca-fix task 3.2 (Requirements 3.2).'
        );
    }

    /**
     * Property 2 (Preservation) — Login::savetiket() line ~168 (readback):
     * $this->tiket->byId("ticketTrackingId = '" . $idTiket . "'")
     *
     * OBSERVASI: readback pasca-insert untuk idTiket VALID SHALL
     * mengembalikan baris IDENTIK antara kondisi string mentah (kode
     * asli) dan kondisi array binding (bentuk fix task 3.2) — baseline
     * ini memastikan data yang dikirim ke PengirimEmail::buat() (isi
     * $datas pada email tiket baru) tidak berubah pasca-fix.
     */
    public function testLoginSavetiketReadbackByIdRawStringConditionMatchesArrayBindingForValidTicket(): void
    {
        $idTiketValid = self::NOMOR_TIKET_VALID;

        // *** Panggilan PERSIS seperti Login::savetiket() baris ~168 (kode asli) ***
        $hasilStringMentah = $this->tiket->byId("ticketTrackingId = '" . $idTiketValid . "'");

        // Bentuk fix task 3.2.
        $hasilArrayBinding = $this->tiket->byId(['ticketTrackingId' => $idTiketValid]);

        self::assertIsArray($hasilStringMentah, 'Readback idTiket valid SHALL mengembalikan baris array pada kondisi string mentah saat ini.');

        // *** BASELINE UTAMA — HARUS TETAP IDENTIK PASCA-FIX (Preservation 3.2) ***
        self::assertSame(
            $hasilArrayBinding,
            $hasilStringMentah,
            'PRESERVATION BASELINE K1: readback byId() pasca-insert (Login::savetiket() baris ~168) untuk idTiket VALID SHALL menghasilkan baris identik antara kondisi string mentah dan array binding — data yang dikirim ke PengirimEmail::buat() tidak boleh berubah pasca-fix (Requirements 3.2).'
        );
    }

    /**
     * Property 2 (Preservation) — Requirements 3.3: method model lain
     * yang TIDAK menerima parameter dari request publik dan memakai
     * escaping manual (`$this->db->escapeString()`) SHALL TIDAK
     * disentuh/berubah oleh scope fix K1.
     *
     * OBSERVASI: `ModelTicketing::getDisposisiById()` memakai kondisi
     * campuran — join dengan string ter-escape manual (`escapeString()`)
     * DAN where() dengan array (`$kondisi` parameter, sudah array di
     * seluruh caller-nya). Baseline ini mencatat bahwa pola escaping
     * manual pada method tersebut TETAP dipertahankan sebagai-adanya
     * (out-of-scope K1), bukan diganti fix K1.
     */
    public function testGetDisposisiByIdManualEscapingBehaviorUnaffectedByK1Scope(): void
    {
        $idTiketValid = self::NOMOR_TIKET_VALID;

        // Memanggil PERSIS seperti caller existing: $kondisi berbentuk array
        // (out-of-scope K1 — method ini bukan target fix K1, hanya dipastikan
        // perilakunya tidak terpengaruh).
        $hasil = $this->tiket->getDisposisiById(['ticketTrackingId' => $idTiketValid]);

        self::assertIsArray($hasil, 'Baseline: getDisposisiById() untuk idTiket valid SHALL tetap mengembalikan baris array seperti perilaku existing, tidak terdampak scope fix K1 (Requirements 3.3).');
        self::assertSame($idTiketValid, $hasil['ticketTrackingId'], 'Baris hasil SHALL tetap berasosiasi dengan ticketTrackingId yang diminta.');

        // Memanggil ulang untuk memastikan hasil DETERMINISTIK (idempotent) —
        // menetapkan baseline yang harus tetap identik pasca-fix K1 pada
        // method lain di file yang sama (Cektiket::rating()/Login::savetiket()
        // yang justru menjadi scope fix, bukan method ini).
        $hasilKedua = $this->tiket->getDisposisiById(['ticketTrackingId' => $idTiketValid]);
        self::assertSame($hasil, $hasilKedua, 'PRESERVATION BASELINE: getDisposisiById() SHALL deterministik dan tidak berubah oleh perubahan lain di ModelTicketing (Requirements 3.3).');
    }
}
