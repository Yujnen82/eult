<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Task 29: Test eksplorasi/AUDIT bug condition R1 — "Audit Binding View
 * Tanpa Escaping" (bugfix.md 1.31, 1.32, 1.33).
 *
 * ═══════════════════════════════════════════════════════════════════
 * KARAKTERISTIK KHUSUS R1 — BERBEDA DARI 11 BUG LAIN DI DIREKTORI INI:
 * ═══════════════════════════════════════════════════════════════════
 * Berbeda dari `K1SqlInjectionExplorationTest`, `T1CsrfExplorationTest`,
 * dll. yang HARUS GAGAL pada kode belum diperbaiki (kegagalan
 * mengonfirmasi bug), test R1 ini adalah AUDIT FORMAL status escaping
 * pada kode SAAT INI dan DIHARAPKAN LULUS untuk 3 binding utama
 * (`repliesMessage`, `detailHistory`, `ticketTrackingId`) —
 * investigasi kode aktual (design.md bagian "Catatan Verifikasi R1")
 * mengonfirmasi ketiganya SUDAH terbungkus `esc()`.
 *
 * Requirement 1.31/1.32/1.33 mengutip nomor baris (141/152/203/246/257)
 * yang SUDAH TIDAK RELEVAN dengan kode `detail_user.php` saat ini
 * (baris-baris tersebut kini berisi CSS di dalam blok <style>, BUKAN
 * output dinamis) — karena itu audit ini TIDAK berpatokan pada nomor
 * baris requirement, melainkan pada BINDING AKTUAL yang ditemukan saat
 * membaca seluruh file.
 *
 * GOAL: Memverifikasi status escaping SELURUH binding di
 * `app/Views/pages/ticketing/detail_user.php` yang berasal (langsung
 * atau tidak langsung) dari input pengguna publik — BUKAN hanya 3
 * binding yang disebut requirement. Bila audit menemukan binding LAIN
 * tanpa `esc()`, itu counterexample nyata yang harus diperbaiki di
 * task 31.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MEKANIKA RENDER — MENGAPA `service('renderer')->render()` IN-PROCESS
 * (AMAN, TIDAK ADA `exit;`):
 * ═══════════════════════════════════════════════════════════════════
 * `detail_user.php` adalah view MURNI presentasi (tidak memanggil
 * controller/helper yang `exit;`). Ia hanya memanggil helper
 * `eult_tanggal`/`eult_waktu` (`helper([...])` di baris pertama view),
 * `base_url()`, `site_url()`, `esc()`, `csrf_meta()`, `csrf_field()`,
 * `csrf_header()` — semuanya read-only dan tersedia di lingkungan
 * PHPUnit CI4. Dirender via `service('renderer')->setData([...])
 * ->render('pages/ticketing/detail_user')` PERSIS pola
 * `Tests\Unit\AdminViewsTest` yang sudah ada (yang merender
 * `pages/ticketing/detail`, `pages/home/index`, dll dengan cara sama)
 * — TIDAK ADA mekanisme dispatch baru diperkenalkan di sini.
 *
 * View melakukan cast defensif atas seluruh data (`(string) ($datas[..]
 * ?? '')`, `is_array()` guard) sehingga cukup diberi payload pada
 * key yang diaudit; key lain diisi dummy wajar agar render tidak fatal.
 *
 * ═══════════════════════════════════════════════════════════════════
 * DUA LAPIS VERIFIKASI:
 * ═══════════════════════════════════════════════════════════════════
 * 1) VERIFIKASI VIA RENDER (behavioral): inject payload XSS ke tiap
 *    binding user-data, render view, assert output ter-escape
 *    (mengandung entity `&lt;script&gt;`, TIDAK mengandung
 *    `<script>alert(1)</script>` mentah). Ini bukti fungsional bahwa
 *    `esc()` benar-benar aktif pada jalur render, bukan sekadar ada
 *    di source.
 * 2) AUDIT STATIS (struktural): scan source view untuk memastikan
 *    TIDAK ADA binding user-data yang di-echo tanpa `esc()` — menangkap
 *    binding yang mungkin tidak ter-cover render (mis. cabang kondisi
 *    tertentu). Menandai jelas temuan sebagai counterexample bila ada.
 *
 * Requirements: 1.31, 1.32, 1.33 (bugfix.md)
 */
final class R1EscBindingAuditExplorationTest extends CIUnitTestCase
{
    /** Path absolut view yang diaudit. */
    private const VIEW_PATH = FCPATH . '../app/Views/pages/ticketing/detail_user.php';

    /** View name (relatif app/Views) untuk renderer CI4. */
    private const VIEW_NAME = 'pages/ticketing/detail_user';

    /**
     * Payload XSS kanonik yang di-inject. Bentuk mentah `<script>alert(1)
     * </script>` HARUS hilang dari output bila `esc()` aktif; bentuk
     * ter-escape `&lt;script&gt;alert(1)&lt;/script&gt;` HARUS muncul.
     */
    private const PAYLOAD_XSS = '<script>alert(1)</script>';

    /** Bentuk ter-escape yang diharapkan muncul di output (esc() mode html default). */
    private const PAYLOAD_ESCAPED = '&lt;script&gt;alert(1)&lt;/script&gt;';

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset();

        self::assertFileExists(
            self::VIEW_PATH,
            'Prasyarat audit R1: file view app/Views/pages/ticketing/detail_user.php SHALL ada untuk diaudit.'
        );
    }

    /**
     * Data dummy LENGKAP + wajar untuk seluruh variabel yang dibaca
     * `detail_user.php`, agar render tidak fatal. Key yang sedang
     * diaudit di-override oleh masing-masing test.
     *
     * @return array<string, mixed>
     */
    private function dataDasar(): array
    {
        return [
            'datas' => [
                'ticketTrackingId' => 'TK-2026-000',
                'ticketName'       => 'Pemohon Uji',
                'sCatNama'         => 'Layanan Uji',
                'categoryNama'     => 'Unit Uji',
                'ticketCreated'    => '2026-01-01 08:00:00',
                'ticketStatus'     => 5, // 5 = selesai → merender blok rating (input value)
                'statusNama'       => 'Selesai',
                'statusColor'      => 'brand',
                'ratingNilai'      => '4',
                'ratingTicketId'   => '', // kosong → rating belum diisi (blok input aktif)
            ],
            'history' => [
                [
                    'tglHistory'    => '2026-01-01 09:00:00',
                    'detailHistory' => 'Riwayat uji normal.',
                ],
            ],
            'replies' => [
                [
                    'repliesStatus'  => 'USER',
                    'repliesMessage' => 'Pesan uji normal.',
                    'repliesFile'    => '',
                    'repliesDate'    => '2026-01-01 09:30:00',
                    'repliesBy'      => 'Anda',
                ],
            ],
            'output_url'  => site_url('cektiket/loadpdf/kunci-uji'),
            'save_url'    => site_url('cektiket/save_replies/'),
            'close_url'   => '#',
            'load_attach' => site_url('cektiket/loadattach'),
            'rating_url'  => site_url('cektiket/rating'),
            'cetakterima' => site_url('cektiket/cetakterima/kunci-uji'),
        ];
    }

    /**
     * Render view dengan override pada `datas`/`history`/`replies`.
     *
     * @param array<string, mixed> $override
     */
    private function renderDenganOverride(array $override): string
    {
        $data = $this->dataDasar();

        foreach ($override as $key => $value) {
            if (in_array($key, ['datas', 'history', 'replies'], true) && is_array($value)) {
                // Merge dangkal untuk datas (assoc); history/replies diganti utuh.
                $data[$key] = $key === 'datas' ? array_merge($data[$key], $value) : $value;

                continue;
            }
            $data[$key] = $value;
        }

        return Services::renderer()->setData($data, 'raw')->render(self::VIEW_NAME);
    }

    /**
     * Helper assertion escaping standar untuk satu binding.
     */
    private function assertBindingTerEscape(string $html, string $namaBinding, string $requirement): void
    {
        self::assertStringNotContainsString(
            self::PAYLOAD_XSS,
            $html,
            sprintf(
                'AUDIT R1 (%s) — COUNTEREXAMPLE: binding "%s" pada detail_user.php merender payload XSS MENTAH "%s" tanpa escaping. Ini binding tidak aman yang WAJIB dibungkus esc() pada task 31.',
                $requirement,
                $namaBinding,
                self::PAYLOAD_XSS
            )
        );
        self::assertStringContainsString(
            self::PAYLOAD_ESCAPED,
            $html,
            sprintf(
                'AUDIT R1 (%s): binding "%s" SHALL merender payload dalam bentuk entity-encoded "%s" (bukti esc() aktif). Bila entity ini tidak muncul, kemungkinan payload tidak ter-render pada cabang yang diuji.',
                $requirement,
                $namaBinding,
                self::PAYLOAD_ESCAPED
            )
        );
    }

    // ══════════════════════════════════════════════════════════════
    // LAPIS 1 — VERIFIKASI VIA RENDER (behavioral)
    // ══════════════════════════════════════════════════════════════

    /**
     * Binding utama 1 (Requirement 1.31): repliesMessage.
     * Dirender via `nl2br(esc($pesan))` — DIHARAPKAN LULUS.
     */
    public function testRepliesMessageTerEscapePadaRender(): void
    {
        $html = $this->renderDenganOverride([
            'replies' => [
                [
                    'repliesStatus'  => 'USER',
                    'repliesMessage' => self::PAYLOAD_XSS,
                    'repliesFile'    => '',
                    'repliesDate'    => '2026-01-01 09:30:00',
                    'repliesBy'      => 'Anda',
                ],
            ],
        ]);

        $this->assertBindingTerEscape($html, 'repliesMessage', 'Requirement 1.31');
    }

    /**
     * Binding utama 2 (Requirement 1.32): detailHistory.
     * Dirender via `esc($value['detailHistory'] ?? '')` — DIHARAPKAN LULUS.
     */
    public function testDetailHistoryTerEscapePadaRender(): void
    {
        $html = $this->renderDenganOverride([
            'history' => [
                [
                    'tglHistory'    => '2026-01-01 09:00:00',
                    'detailHistory' => self::PAYLOAD_XSS,
                ],
            ],
        ]);

        $this->assertBindingTerEscape($html, 'detailHistory', 'Requirement 1.32');
    }

    /**
     * Binding utama 3 (Requirement 1.33): ticketTrackingId.
     * Dirender via `esc($trackingId)` (konteks HTML) dan
     * `esc($trackingId, 'attr')` (konteks atribut) — DIHARAPKAN LULUS.
     */
    public function testTicketTrackingIdTerEscapePadaRender(): void
    {
        $html = $this->renderDenganOverride([
            'datas' => [
                'ticketTrackingId' => self::PAYLOAD_XSS,
            ],
        ]);

        // Konteks HTML body: payload mentah tidak boleh muncul.
        self::assertStringNotContainsString(
            self::PAYLOAD_XSS,
            $html,
            'AUDIT R1 (Requirement 1.33) — COUNTEREXAMPLE: ticketTrackingId merender payload XSS MENTAH tanpa escaping. WAJIB dibungkus esc() pada task 31.'
        );
        // esc() html menghasilkan &lt;script&gt;; esc(...,'attr') pada value
        // atribut menghasilkan &lt;script&gt; juga untuk < dan >. Salah satu
        // bentuk entity WAJIB hadir sebagai bukti escaping.
        self::assertStringContainsString(
            '&lt;script&gt;',
            $html,
            'AUDIT R1 (Requirement 1.33): ticketTrackingId SHALL ter-escape (entity &lt;script&gt; hadir) pada konteks HTML maupun atribut.'
        );
    }

    /**
     * Binding tambahan yang DITEMUKAN saat audit menyeluruh
     * (di luar 3 yang disebut requirement) — semuanya juga berasal
     * dari data pengguna dan HARUS ter-escape.
     *
     * @dataProvider provideBindingUserDataTambahan
     */
    public function testBindingUserDataTambahanTerEscape(string $namaBinding, array $override): void
    {
        $html = $this->renderDenganOverride($override);

        self::assertStringNotContainsString(
            self::PAYLOAD_XSS,
            $html,
            sprintf(
                'AUDIT R1 (Requirement 1.33, defense-in-depth) — COUNTEREXAMPLE: binding tambahan "%s" merender payload XSS MENTAH tanpa escaping. WAJIB dibungkus esc() pada task 31.',
                $namaBinding
            )
        );
        self::assertStringContainsString(
            '&lt;script&gt;',
            $html,
            sprintf(
                'AUDIT R1 (Requirement 1.33): binding tambahan "%s" SHALL ter-escape (entity &lt;script&gt; hadir).',
                $namaBinding
            )
        );
    }

    /**
     * Korpus binding user-data tambahan yang ditemukan pada audit
     * menyeluruh detail_user.php (bukan hanya 3 dari requirement).
     *
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function provideBindingUserDataTambahan(): array
    {
        return [
            // ticketName → $namaPemohon (dirender di meta credential & chat head)
            'ticketName (namaPemohon)' => ['ticketName', [
                'datas' => ['ticketName' => self::PAYLOAD_XSS],
            ]],
            // sCatNama → $layananUtama (dirender di meta & field layanan)
            'sCatNama (layananUtama)' => ['sCatNama', [
                'datas' => ['sCatNama' => self::PAYLOAD_XSS],
            ]],
            // categoryNama → $layananUnit (dirender bersama layananUtama)
            'categoryNama (layananUnit)' => ['categoryNama', [
                'datas' => ['categoryNama' => self::PAYLOAD_XSS],
            ]],
            // repliesFile → $berkas (nama file lampiran chat, dirender di href & teks link)
            'repliesFile (berkas)' => ['repliesFile', [
                'replies' => [
                    [
                        'repliesStatus'  => 'USER',
                        'repliesMessage' => 'ada lampiran',
                        'repliesFile'    => self::PAYLOAD_XSS,
                        'repliesDate'    => '2026-01-01 09:30:00',
                        'repliesBy'      => 'Anda',
                    ],
                ],
            ]],
            // repliesBy → $pengirim (nama pengirim balasan)
            'repliesBy (pengirim)' => ['repliesBy', [
                'replies' => [
                    [
                        'repliesStatus'  => 'STAFF',
                        'repliesMessage' => 'balasan petugas',
                        'repliesFile'    => '',
                        'repliesDate'    => '2026-01-01 09:30:00',
                        'repliesBy'      => self::PAYLOAD_XSS,
                    ],
                ],
            ]],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // LAPIS 2 — AUDIT STATIS (struktural)
    // ══════════════════════════════════════════════════════════════

    /**
     * Audit statis menyeluruh: memastikan TIDAK ADA echo binding
     * user-data yang keluar tanpa `esc()`. Scan setiap tag echo pendek
     * (bentuk short-echo PHP) pada source view; untuk tiap echo yang
     * me-referensi variabel/subskrip user-data yang diketahui, assert
     * bahwa echo tersebut membungkusnya dengan `esc(`.
     *
     * Ini menangkap binding yang mungkin tidak ter-cover render pada
     * cabang kondisi tertentu, dan berfungsi sebagai jaring pengaman
     * struktural atas hasil audit behavioral di atas.
     */
    public function testAuditStatisSeluruhEchoUserDataMemakaiEsc(): void
    {
        $sumber = (string) file_get_contents(self::VIEW_PATH);

        // Token/variabel PHP yang membawa data pengguna (langsung/tidak
        // langsung) di dalam view. $trackingId..$layananUnit adalah alias
        // lokal yang di-assign dari $datas[...] di header view; $pesan/
        // $berkas/$pengirim adalah alias per-iterasi balasan; subskrip
        // 'detailHistory'/'repliesMessage'/'repliesFile'/'repliesBy'
        // di-echo langsung pada beberapa titik.
        $tokenUserData = [
            '$trackingId',
            '$namaPemohon',
            '$layananUtama',
            '$layananUnit',
            '$statusNama',
            '$pesan',
            '$berkas',
            '$pengirim',
            "'detailHistory'",
            "'repliesMessage'",
            "'repliesFile'",
            "'repliesBy'",
        ];

        // Ambil seluruh echo pendek short-echo (non-greedy).
        $tagBuka  = '<' . '?=';
        $tagTutup = '?' . '>';
        preg_match_all('/' . preg_quote($tagBuka, '/') . '(.*?)' . preg_quote($tagTutup, '/') . '/s', $sumber, $matches);
        $ekspresiEcho = $matches[1] ?? [];

        self::assertNotEmpty($ekspresiEcho, 'Prasyarat audit statis: SHALL ada minimal satu echo short-echo pada view.');

        $temuanTidakAman = [];

        foreach ($ekspresiEcho as $ekspresi) {
            foreach ($tokenUserData as $token) {
                if (! str_contains($ekspresi, $token)) {
                    continue;
                }

                // Echo yang menyentuh token user-data WAJIB memanggil esc(
                // di dalam ekspresi yang sama (esc(...) atau nl2br(esc(...))).
                if (! str_contains($ekspresi, 'esc(')) {
                    $temuanTidakAman[] = sprintf('token %s pada echo: %s%s%s', $token, $tagBuka, trim($ekspresi), $tagTutup);
                }
            }
        }

        self::assertSame(
            [],
            $temuanTidakAman,
            "AUDIT R1 STATIS (Requirement 1.31/1.32/1.33) — COUNTEREXAMPLE: ditemukan echo binding user-data TANPA esc() pada detail_user.php:\n"
            . implode("\n", $temuanTidakAman)
            . "\nSetiap temuan ini WAJIB dibungkus esc() (konteks html/attr/js sesuai posisi) pada task 31."
        );
    }
}
