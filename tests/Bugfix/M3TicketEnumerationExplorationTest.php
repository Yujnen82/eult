<?php

namespace Tests\Bugfix;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Task 16 — Test eksplorasi bug condition M3: "Enumerasi Nomor Tiket
 * Tanpa Rate Limit" (bugfix.md 1.29, 1.30; design.md bagian M3).
 *
 * CRITICAL: Test ini HARUS GAGAL pada kode belum diperbaiki — kegagalan
 * mengonfirmasi bug ada. DO NOT attempt to fix the test or the code
 * ketika gagal.
 *
 * GOAL: Surface counterexample bahwa `Login::cektiket()`
 * (app/Controllers/Login.php:56-81, route `POST login/cektiket`)
 * memproses SELURUH request enumerasi nomor tiket beruntun TANPA
 * hambatan rate-limit apa pun, DAN pesan "Nomor tiket ditemukan..."
 * vs "Nomor tiket tidak ditemukan." tetap dapat dibedakan sepanjang
 * rangkaian tersebut — inilah oracle konkret yang memungkinkan
 * penyerang membedakan nomor tiket valid vs tidak valid secara masif
 * (bugfix.md 1.29, 1.30). M3 adalah derivatif eksplisit dari T2 (task
 * 13) — akar masalah SAMA (tidak ada `Config/Throttle.php`, tidak ada
 * filter rate-limiting apa pun di codebase), diterapkan pada endpoint
 * berbeda.
 *
 * ═══════════════════════════════════════════════════════════════════
 * KONFIRMASI PERILAKU `Login::cektiket()` SAAT INI (dibaca ulang
 * langsung dari app/Controllers/Login.php, BUKAN paraphrase — file ini
 * juga disentuh task 3.1/K1 lebih awal dalam sesi ini, sehingga
 * diverifikasi ulang state terkininya):
 * ═══════════════════════════════════════════════════════════════════
 * ```
 * public function cektiket()
 * {
 *     if (! $this->validate(['nomorTiket' => 'required'])) {
 *         return $this->response->setJSON([
 *             'status'  => 'danger',
 *             'message' => 'Nomor tiket wajib diisi.',
 *         ]);
 *     }
 *
 *     $nomorTiket = (string) $this->request->getPost('nomorTiket');
 *     $datas      = $this->tiket->byId(['ticketTrackingId' => $nomorTiket]);
 *
 *     if ($datas !== false) {
 *         $kunci = $this->enkripsi->encode($nomorTiket);
 *
 *         return $this->response->setJSON([
 *             'status'       => 'success',
 *             'message'      => 'Nomor tiket ditemukan, mengalihkan halaman...',
 *             'redirect_url' => base_url('cektiket/index/') . $kunci,
 *         ]);
 *     }
 *
 *     return $this->response->setJSON([
 *         'status'  => 'danger',
 *         'message' => 'Nomor tiket tidak ditemukan.',
 *     ]);
 * }
 * ```
 * Sudah memakai `byId(['ticketTrackingId' => $nomorTiket])` (array
 * binding, hasil fix K1/task 3.1 — bukan lagi string mentah), NAMUN
 * ini TIDAK relevan dengan bug M3: M3 murni soal ABSENSI rate-limit
 * pada endpoint ini, bukan soal SQL injection (K1 sudah tertutup
 * terpisah). Setiap return statement memakai
 * `return $this->response->setJSON([...])` — TIDAK ADA satu pun
 * panggilan `eult_message_kirim()` (app/Helpers/eult_message_helper.php,
 * yang memanggil `response()->send(); exit;` sungguhan) di method ini.
 * Method ini juga TIDAK melakukan insert/update/delete apa pun — HANYA
 * `byId()` (SELECT read-only) — sehingga TIDAK ADA efek samping
 * database yang perlu cleanup.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MENGAPA `FeatureTestTrait` (in-process) — BUKAN cURL-ke-server-live:
 * ═══════════════════════════════════════════════════════════════════
 * Berdasarkan konfirmasi di atas, `cektiket()` AMAN didispatch
 * in-process via `FeatureTestTrait::post()` — sama seperti
 * `T2CaptchaRateLimitExplorationTest::testSeluruhRequestOtentifikasi
 * BeruntunDiprosesTanpaRateLimit()`/
 * `testSeluruhRequestSavetiketSuksesBeruntunDiprosesTanpaRateLimit()`,
 * berbeda dari `Cektiket::rating()`/`Cektiket::loadpdf()` yang memanggil
 * `eult_message_kirim()` pada jalur tertentu (lihat docblock
 * K1SqlInjectionHttpIntegrationTest.php) dan karenanya membutuhkan
 * dispatch HTTP terpisah ke server live.
 *
 * ═══════════════════════════════════════════════════════════════════
 * SKALA REQUEST — MENGAPA 15 (BUKAN 100/10.000) — REUSE JUSTIFIKASI
 * T2CaptchaRateLimitExplorationTest SECARA IDENTIK:
 * ═══════════════════════════════════════════════════════════════════
 * bugfix.md 1.30 dan task 16 menyebut "100 (atau 10.000 sesuai skenario
 * bugfix.md)" sebagai angka ilustratif skenario serangan enumerasi
 * massal, BUKAN ambang batas yang harus benar-benar direproduksi
 * literal di test suite otomatis. Bug condition M3 berbagi akar
 * masalah ARSITEKTURAL yang identik dengan T2 (task 13): tidak ada
 * `Config/Throttle.php` di codebase (dikonfirmasi ulang via pencarian
 * `Throttle`/`Throttler` di seluruh `app/` sebelum test ini ditulis —
 * NOL kecocokan selain lodash-style `throttle()` pada vendor JS plugin
 * tinymce yang tidak terkait), `app/Config/Filters.php` TIDAK memiliki
 * alias `'throttle'` apa pun terdaftar di `$aliases`, dan `login/
 * cektiket` TIDAK muncul di `$globals`/`$filters` mana pun. Fakta ini
 * BINER (filter throttle ada dan aktif, ATAU tidak ada sama sekali) —
 * bukan properti statistik yang butuh sampel besar: jika filter
 * throttle benar-benar ada dengan limit realistis apa pun (bahkan yang
 * sangat longgar), ia AKAN menolak salah satu dari 15 request beruntun
 * dalam window singkat. Tidak satu pun ditolak pada 15 request sudah
 * cukup membuktikan TIDAK ADA throttle sama sekali — mengirim 100/
 * 10.000 tidak mengungkap fakta baru yang berbeda dari mengirim 15,
 * karena tidak ada mekanisme apa pun yang bisa "menyerah setelah N
 * request tapi lolos di bawah N" jika N tidak ada. Endpoint ini juga
 * TIDAK memicu efek samping pihak ketiga (tidak ada email/API eksternal
 * di jalur `cektiket()` — hanya SELECT read-only), sehingga tidak ada
 * pertimbangan biaya/risiko tambahan seperti pada T2 (SMTP/OSM) — angka
 * 15 dipertahankan semata untuk KONSISTENSI dengan precedent T2 (per
 * instruksi task 16), bukan karena ada batasan biaya nyata di endpoint
 * ini secara spesifik.
 *
 * ═══════════════════════════════════════════════════════════════════
 * PEMBACAAN SINYAL "DITEMUKAN"/"TIDAK DITEMUKAN" DARI RESPONS — KUIRK
 * PEMBUNGKUS BODY JSON (identik dengan temuan T2/task 13):
 * ═══════════════════════════════════════════════════════════════════
 * Diverifikasi ulang secara empiris (bukan asumsi) pada endpoint ini
 * secara spesifik bahwa `$hasil->getBody()` pada dispatch
 * `FeatureTestTrait` di environment ini mengembalikan body yang
 * DIBUNGKUS shell `<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0
 * Transitional//EN" ...><html><body>{...JSON asli...}</body></html>`
 * — `json_decode()` pada body tersebut SELALU mengembalikan `null`.
 * Oleh karena itu deteksi sinyal "ditemukan" vs "tidak ditemukan"
 * TIDAK memakai `json_decode()` sama sekali — memakai PENCARIAN
 * SUBSTRING LANGSUNG pada string body mentah untuk potongan pesan
 * literal method ini: `'Nomor tiket ditemukan'` (jalur `$datas !==
 * false`) vs `'Nomor tiket tidak ditemukan.'` (jalur sebaliknya).
 * Kedua substring ini SALING EKSKLUSIF dan tidak overlap satu sama
 * lain sebagai substring (dikonfirmasi dengan membaca literal pesan
 * di atas), sehingga pencarian `str_contains()` pada body mentah
 * adalah sinyal yang RELIABLE tanpa perlu parsing JSON — konsisten
 * dengan pendekatan T2 yang bergantung pada kode status HTTP + bukti
 * langsung dari database, bukan body JSON terparse.
 *
 * Requirements: 1.29, 1.30 (bugfix.md — Current Behavior/Defect M3)
 *
 * @internal
 */
final class M3TicketEnumerationExplorationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /** Jumlah request beruntun — reuse skala T2CaptchaRateLimitExplorationTest, lihat docblock kelas untuk justifikasi. */
    private const JUMLAH_REQUEST_BERUNTUN = 15;

    /**
     * Nomor tiket VALID sungguhan, terverifikasi ada di d_ticketing
     * (sama seperti dipakai K1SqlInjectionPreservationTest.php dan
     * T4RatingOwnershipExplorationTest.php — ticketEmail =
     * rizkyfkemala@gmail.com, dibaca langsung dari database live
     * db_newtiket pada sesi verifikasi sebelumnya).
     */
    private const NOMOR_TIKET_VALID = 'QEHO-HTTV-001';

    /** Potongan pesan literal jalur "ditemukan" — persis app/Controllers/Login.php::cektiket(). */
    private const PESAN_DITEMUKAN = 'Nomor tiket ditemukan';

    /** Potongan pesan literal jalur "tidak ditemukan" — persis app/Controllers/Login.php::cektiket(). */
    private const PESAN_TIDAK_DITEMUKAN = 'Nomor tiket tidak ditemukan.';

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi = \Config\Database::connect('default');

        self::assertSame(
            'db_newtiket',
            $this->koneksi->database,
            'Test eksplorasi ini WAJIB tersambung ke database nyata db_newtiket agar nomor tiket valid yang dipakai benar-benar merepresentasikan record nyata.'
        );

        self::assertNotFalse(
            $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::NOMOR_TIKET_VALID)->get()->getFirstRow('array'),
            sprintf(
                'Prasyarat test: nomor tiket %s SHALL benar-benar ada di d_ticketing agar sinyal "ditemukan" dapat diverifikasi terhadap record nyata.',
                self::NOMOR_TIKET_VALID
            )
        );
    }

    /**
     * `Login::cektiket()` HANYA melakukan `byId()` (SELECT read-only)
     * — TIDAK ADA insert/update/delete pada jalur mana pun (dikonfirmasi
     * via pembacaan penuh method di docblock kelas). Method setUp()
     * di atas hanya melakukan SELECT (pengecekan prasyarat), sehingga
     * TIDAK diperlukan tearDown()/cleanup apa pun — tidak ada baris
     * baru yang tercipta oleh test ini.
     */

    /**
     * Property 1 (Bug Condition) — M3: kirim
     * {self::JUMLAH_REQUEST_BERUNTUN} request POST beruntun ke
     * `login/cektiket` (mix nomor tiket VALID diketahui dan nomor tiket
     * TIDAK ADA/tebakan) pada kode ASLI → assert PERILAKU YANG
     * SEHARUSNYA (setelah fix task 19 memasang rate-limit yang sama
     * seperti T2 pada endpoint ini):
     * (a) [ASSERTION UTAMA] SETIDAKNYA SATU dari rangkaian request
     *     SEHARUSNYA ditolak dengan sinyal rate-limit (429 atau respons
     *     setara) — persis anchor task 19.3 ("request ke-(N+1)
     *     mengembalikan 429") — namun pada kode ASLI 0 yang ditolak,
     *     sehingga assertion ini GAGAL sekarang (mengonfirmasi bug ada)
     *     dan akan LULUS begitu fix M3/T2 terpasang,
     * (b) [bukti diagnostik pendukung, BUKAN assertion utama] sinyal
     *     "ditemukan" vs "tidak ditemukan" tetap DAPAT DIBEDAKAN secara
     *     konsisten sepanjang rangkaian 15 request PADA KODE ASLI (yang
     *     memang tidak punya hambatan apa pun) — ini adalah oracle
     *     konkret yang memperkuat pembuktian bahwa enumerasi
     *     benar-benar dapat dieksploitasi pada skala ini selama tidak
     *     ada rate-limit, bukan sekadar "request diproses" tanpa makna.
     *
     * Rangkaian request MEMBAGI GANJIL/GENAP: indeks ganjil mengirim
     * {self::NOMOR_TIKET_VALID} (harus konsisten "ditemukan" di
     * SETIAP kemunculan), indeks genap mengirim nomor tebakan acak
     * unik per-iterasi (format sama dengan pola existing
     * `XXXX-XXXX-NNN`, hampir pasti TIDAK match record apa pun —
     * harus konsisten "tidak ditemukan" di SETIAP kemunculan). Pola
     * berselang-seling ini secara eksplisit menguji bahwa oracle tidak
     * "rusak"/tidak konsisten akibat rate-limit PARSIAL (skenario di
     * mana request masih lolos status 200 namun pesannya mulai
     * generik/berubah setelah N request — tidak terjadi pada kode asli
     * karena tidak ada mekanisme apa pun yang mengubah perilaku setelah
     * N request, namun assertion diagnostik (b) tetap memverifikasi hal
     * tersebut secara eksplisit alih-alih berasumsi).
     *
     * **EXPECTED OUTCOME pada kode asli: GAGAL** — assertion utama (a)
     * menuntut SETIDAKNYA SATU dari rangkaian request ditolak
     * rate-limit (sinyal konkret bahwa suatu mekanisme throttle SUDAH
     * aktif membatasi enumerasi, sejalan dengan task 19.3: "request
     * ke-(N+1) mengembalikan 429"), namun pada kode asli SELURUH
     * {self::JUMLAH_REQUEST_BERUNTUN} request diproses penuh (200)
     * TANPA satu pun ditolak — membuktikan enumerasi nomor tiket dapat
     * dilakukan secara masif tanpa hambatan apa pun.
     */
    public function testEnumerasiNomorTiketBeruntunDiprosesTanpaRateLimitDanSinyalTetapTerbedakan(): void
    {
        $jumlahDitolakRateLimit           = 0;
        $jumlahStatusHttp200              = 0;
        $jumlahSinyalDitemukanBenar       = 0;
        $jumlahSinyalTidakDitemukanBenar  = 0;
        $statusHttpTerkumpul              = [];
        $ringkasanPerIterasi              = [];

        for ($i = 1; $i <= self::JUMLAH_REQUEST_BERUNTUN; $i++) {
            $ganjil       = ($i % 2) === 1;
            $nomorDikirim = $ganjil
                ? self::NOMOR_TIKET_VALID
                : sprintf('ZZZZ-TEBK-%03d-ENUM%d', $i, $i);

            $hasil = $this->post('login/cektiket', [
                'nomorTiket' => $nomorDikirim,
            ]);

            $kodeStatus            = $hasil->response()->getStatusCode();
            $statusHttpTerkumpul[] = $kodeStatus;
            $bodiMentah            = (string) $hasil->getBody();

            if ($kodeStatus === 429) {
                $jumlahDitolakRateLimit++;
            }

            if ($kodeStatus === 200) {
                $jumlahStatusHttp200++;
            }

            $mengandungDitemukan     = str_contains($bodiMentah, self::PESAN_DITEMUKAN);
            $mengandungTidakDitemukan = str_contains($bodiMentah, self::PESAN_TIDAK_DITEMUKAN);

            if ($ganjil && $mengandungDitemukan && ! $mengandungTidakDitemukan) {
                $jumlahSinyalDitemukanBenar++;
            }

            if (! $ganjil && $mengandungTidakDitemukan && ! $mengandungDitemukan) {
                $jumlahSinyalTidakDitemukanBenar++;
            }

            $ringkasanPerIterasi[] = sprintf(
                '#%d[nomor=%s,status=%d,ditemukan=%s,tidakDitemukan=%s]',
                $i,
                $nomorDikirim,
                $kodeStatus,
                $mengandungDitemukan ? '1' : '0',
                $mengandungTidakDitemukan ? '1' : '0'
            );
        }

        $jumlahIterasiGanjil = (int) ceil(self::JUMLAH_REQUEST_BERUNTUN / 2);
        $jumlahIterasiGenap  = self::JUMLAH_REQUEST_BERUNTUN - $jumlahIterasiGanjil;

        // *** ASSERTION UTAMA (a) — Property 1, Bug Condition M3 ***
        // Assert PERILAKU YANG SEHARUSNYA (setelah fix task 19 memasang
        // rate-limit yang sama seperti T2 pada login/cektiket): SETIDAKNYA
        // SATU dari rangkaian request beruntun ini SEHARUSNYA ditolak
        // (429) begitu throttle aktif — persis anchor task 19.3
        // ("request ke-(N+1) mengembalikan 429"). Pada kode ASLI belum
        // diperbaiki, jumlahDitolakRateLimit MEMANG 0 (tidak ada throttle
        // apa pun) — sehingga assertion GreaterThan(0) ini GAGAL sekarang,
        // mengonfirmasi bug ada, dan akan LULUS begitu fix M3/T2 terpasang.
        self::assertGreaterThan(
            0,
            $jumlahDitolakRateLimit,
            sprintf(
                "BUG CONDITION M3 (Requirement 1.30) — COUNTEREXAMPLE: dari %d request POST beruntun ke login/cektiket, 0 yang ditolak dengan sinyal rate-limit (429) — rincian per-iterasi: %s. SETELAH fix (task 19, rate-limit yang sama seperti T2), SETIDAKNYA SATU dari rangkaian ini SEHARUSNYA ditolak 429 begitu ambang batas request/menit terlampaui; pada kode asli TIDAK SATU PUN ditolak, membuktikan enumerasi nomor tiket dapat dilakukan secara masif tanpa hambatan rate-limit apa pun.",
                self::JUMLAH_REQUEST_BERUNTUN,
                implode(', ', $ringkasanPerIterasi)
            )
        );

        self::assertSame(
            self::JUMLAH_REQUEST_BERUNTUN,
            $jumlahStatusHttp200,
            sprintf(
                'BUG CONDITION M3 (Requirement 1.30) — COUNTEREXAMPLE: dari %d request POST beruntun ke login/cektiket, HANYA %d yang mendapat status HTTP 200 — status terkumpul: %s. Pada kode ASLI seluruh %d SEHARUSNYA 200 (tidak ada hambatan level HTTP apa pun) — assertion ini dipertahankan sebagai bukti diagnostik pendukung (bukan assertion utama bug condition; lihat assertion GreaterThan di atas untuk sinyal utama yang membedakan sebelum/setelah fix), mengonfirmasi request benar-benar diproses tuntas hingga level HTTP, bukan gagal karena sebab lain.',
                self::JUMLAH_REQUEST_BERUNTUN,
                $jumlahStatusHttp200,
                implode(', ', $statusHttpTerkumpul),
                self::JUMLAH_REQUEST_BERUNTUN
            )
        );

        // *** ASSERTION PENDUKUNG (b) — Property 1, Bug Condition M3 ***
        // BUKAN assertion utama bug condition (lihat assertGreaterThan
        // di atas untuk sinyal utama) — dipertahankan sebagai bukti
        // diagnostik bahwa oracle ditemukan/tidak-ditemukan BENAR-BENAR
        // dapat dibedakan secara konsisten pada SETIAP request sepanjang
        // rangkaian ini SELAMA tidak ada throttle yang menghalangi (pada
        // kode asli: konsisten karena TIDAK ADA hambatan apa pun; setelah
        // fix: sebagian request akan ditolak 429 SEBELUM mencapai oracle
        // ini sama sekali — lihat assertion utama (a)). Nilai diagnostik
        // ganjil/genap dan ringkasan per-iterasi dipertahankan apa adanya
        // sesuai instruksi — hanya label/framing yang diperbarui di sini.
        self::assertSame(
            $jumlahIterasiGanjil,
            $jumlahSinyalDitemukanBenar,
            sprintf(
                "BUG CONDITION M3 (Requirement 1.29, 1.30) — bukti diagnostik pendukung (sinyal 'ditemukan'): dari %d request dengan nomor tiket VALID (%s), HANYA %d yang mengembalikan pesan 'Nomor tiket ditemukan...' secara bersih (tanpa pesan 'tidak ditemukan' ikut muncul) — rincian per-iterasi: %s. Pada kode ASLI seluruh %d konsisten menampilkan sinyal 'ditemukan' (karena tidak ada hambatan rate-limit apa pun) — mengonfirmasi oracle pembeda valid/tidak-valid benar-benar usable pada skala ini, memperkuat assertion utama (a) bahwa enumerasi sungguh dapat dieksploitasi.",
                $jumlahIterasiGanjil,
                self::NOMOR_TIKET_VALID,
                $jumlahSinyalDitemukanBenar,
                implode(', ', $ringkasanPerIterasi),
                $jumlahIterasiGanjil,
                self::JUMLAH_REQUEST_BERUNTUN
            )
        );

        self::assertSame(
            $jumlahIterasiGenap,
            $jumlahSinyalTidakDitemukanBenar,
            sprintf(
                "BUG CONDITION M3 (Requirement 1.29, 1.30) — bukti diagnostik pendukung (sinyal 'tidak ditemukan'): dari %d request dengan nomor tiket TEBAKAN/TIDAK ADA, HANYA %d yang mengembalikan pesan 'Nomor tiket tidak ditemukan.' secara bersih (tanpa pesan 'ditemukan' ikut muncul) — rincian per-iterasi: %s. Pada kode ASLI seluruh %d konsisten menampilkan sinyal 'tidak ditemukan' (karena tidak ada rate-limit apa pun menghalangi), memperkuat assertion utama (a): penyerang dapat membedakan nomor tiket tidak valid secara reliable sepanjang %d request beruntun TANPA hambatan (bugfix.md 1.30: 'penyerang dapat melakukan enumerasi nomor tiket valid secara masif').",
                $jumlahIterasiGenap,
                $jumlahSinyalTidakDitemukanBenar,
                implode(', ', $ringkasanPerIterasi),
                $jumlahIterasiGenap,
                self::JUMLAH_REQUEST_BERUNTUN
            )
        );
    }
}
