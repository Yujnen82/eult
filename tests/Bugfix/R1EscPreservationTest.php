<?php

namespace Tests\Bugfix;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Task 30 (R1): Test PRESERVASI (Property 2) — "Tampilan Teks Wajar dan
 * Link Lampiran Tidak Rusak" (bugfix.md 3.29, 3.30).
 *
 * ═══════════════════════════════════════════════════════════════════
 * METODOLOGI OBSERVATION-FIRST
 * ═══════════════════════════════════════════════════════════════════
 * Berbeda dari test EKSPLORASI R1 (`R1EscBindingAuditExplorationTest`,
 * task 29) yang meng-inject payload XSS untuk mengaudit bahwa escaping
 * AKTIF, test PRESERVASI ini menetapkan BASELINE perilaku BENAR yang
 * SUDAH ada pada kode SAAT INI: karakter HTML WAJAR (`<`, `>`, `&`) di
 * dalam teks balasan NORMAL (BUKAN payload serangan) tetap tampil BENAR
 * secara visual setelah `esc()`, dan link lampiran chat tidak rusak.
 *
 * Perilaku aktual sudah DIOBSERVASI langsung pada kode saat ini sebelum
 * assertion ditulis (render in-process `detail_user.php`):
 *
 *   INPUT  repliesMessage = "Harga 5 < 10 & diskon > 0"
 *   OUTPUT (di dalam <div class="eult-bubble__text">):
 *          Harga 5 &lt; 10 &amp; diskon &gt; 0
 *          → karakter HTML wajar di-entity-encode oleh nl2br(esc($pesan)),
 *            tampil sebagai teks literal (bukan dieksekusi, bukan hilang).
 *
 *   INPUT  repliesFile = "CHAT_ABC_1700000000.pdf" (+ pesan berisi < & >)
 *   OUTPUT: teks pesan (ter-entity-encode) lalu <br> lalu
 *          <a class="eult-attach"
 *             href=".../cektiket/loadattach/CHAT_ABC_1700000000.pdf">
 *             <i class="flaticon-attachment"></i> CHAT_ABC_1700000000.pdf
 *          </a>
 *          → link lampiran UTUH & TERPISAH dari isi pesan (href valid,
 *            teks link muncul), dirender pada bubble/baris yang sama.
 *
 * Test ini WAJIB LULUS pada kode SAAT INI — kelulusan mengonfirmasi
 * baseline yang sudah berfungsi benar dan HARUS tetap terjaga jika task
 * 31 (fix kondisional) menyentuh binding apa pun.
 *
 * ═══════════════════════════════════════════════════════════════════
 * MEKANIKA RENDER (identik pola `R1EscBindingAuditExplorationTest`)
 * ═══════════════════════════════════════════════════════════════════
 * `detail_user.php` view MURNI presentasi (tanpa `exit;`), aman dirender
 * in-process via `Services::renderer()->setData($data, 'raw')
 * ->render('pages/ticketing/detail_user')`. Data dummy lengkap disediakan
 * agar render tidak fatal; key yang diuji di-override per test.
 *
 * ═══════════════════════════════════════════════════════════════════
 * PENDEKATAN PROPERTY-BASED (konvensi repo: @dataProvider sebagai generator)
 * ═══════════════════════════════════════════════════════════════════
 * Repo ini tidak memakai library PBT (Eris/dll). Sesuai konvensi yang
 * sudah ada (`R1EscBindingAuditExplorationTest::provideBindingUserDataTambahan`),
 * property dinyatakan melalui @dataProvider yang meng-generate korpus
 * yang MENGCONSTRAIN ruang input secara cerdas ke domain yang relevan:
 * yakni teks WAJAR yang mengandung metacharacter HTML (`<`, `>`, `&`,
 * `"`, `'`) DALAM konteks kalimat normal — BUKAN payload serangan.
 * Property yang diuji: untuk SETIAP teks wajar demikian, output SHALL
 * (a) tidak kehilangan konten tekstual, dan (b) menampilkan
 * metacharacter dalam bentuk entity-encoded yang benar.
 *
 * Requirements: 3.29, 3.30 (bugfix.md)
 */
final class R1EscPreservationTest extends CIUnitTestCase
{
    /** View name (relatif app/Views) untuk renderer CI4. */
    private const VIEW_NAME = 'pages/ticketing/detail_user';

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset();
    }

    /**
     * Data dummy LENGKAP + wajar untuk seluruh variabel yang dibaca
     * `detail_user.php`, agar render tidak fatal. Key yang diuji
     * di-override oleh masing-masing test.
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
                'ticketStatus'     => 5, // 5 = selesai → tetap merender blok chat
                'statusNama'       => 'Selesai',
                'statusColor'      => 'brand',
                'ratingNilai'      => '4',
                'ratingTicketId'   => '',
            ],
            'history' => [
                ['tglHistory' => '2026-01-01 09:00:00', 'detailHistory' => 'Riwayat uji normal.'],
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
     * Render view dengan satu balasan tunggal berisi pesan & (opsional)
     * lampiran yang diberikan.
     */
    private function renderBalasan(string $pesan, string $berkas = ''): string
    {
        $data            = $this->dataDasar();
        $data['replies'] = [
            [
                'repliesStatus'  => 'USER',
                'repliesMessage' => $pesan,
                'repliesFile'    => $berkas,
                'repliesDate'    => '2026-01-01 09:30:00',
                'repliesBy'      => 'Anda',
            ],
        ];

        return Services::renderer()->setData($data, 'raw')->render(self::VIEW_NAME);
    }

    /**
     * Ekstrak isi elemen <div class="eult-bubble__text"> pertama yang
     * DIRENDER (bukan selector CSS di blok <style>) dari HTML output.
     * Mengembalikan potongan HTML segera setelah tag pembuka div bubble.
     */
    private function ambilBubbleText(string $html): string
    {
        // Selektor CSS `.eult-bubble__text {` muncul di <style>; elemen
        // yang dirender adalah `class="eult-bubble__text">` (diakhiri `>`).
        $needle = 'class="eult-bubble__text">';
        $pos    = strpos($html, $needle);
        self::assertNotFalse(
            $pos,
            'Prasyarat preservasi R1: elemen <div class="eult-bubble__text"> hasil render SHALL ada pada output.'
        );

        return substr($html, $pos + strlen($needle), 700);
    }

    // ══════════════════════════════════════════════════════════════
    // PROPERTY 1 (Requirement 3.29) — TEKS HTML WAJAR TAMPIL BENAR
    // ══════════════════════════════════════════════════════════════

    /**
     * PROPERTY: untuk SETIAP teks balasan WAJAR yang mengandung
     * metacharacter HTML, output SHALL (a) tidak mengandung metacharacter
     * mentah yang belum di-encode dalam konteks yang berbahaya (`<`, `>`),
     * (b) mengandung bentuk entity-encoded yang benar, dan (c) tidak
     * kehilangan potongan kata literal dari konten.
     *
     * @dataProvider provideTeksHtmlWajar
     *
     * @param array<int, string> $entityWajib   substring entity yang WAJIB muncul
     * @param array<int, string> $kataLiteral   kata literal yang WAJIB tetap ada
     */
    public function testTeksHtmlWajarTampilSebagaiTeksLiteralTerEncode(
        string $pesan,
        array $entityWajib,
        array $kataLiteral
    ): void {
        $html   = $this->renderBalasan($pesan);
        $bubble = $this->ambilBubbleText($html);

        // (a) Metacharacter mentah `<`/`>` TIDAK boleh bocor sebagai tag di
        //     dalam isi bubble — pesan wajar tetap tampil sebagai teks.
        self::assertStringNotContainsString(
            '<script',
            $bubble,
            'PRESERVASI R1 (3.29): teks wajar tidak boleh menghasilkan tag mentah di dalam bubble.'
        );

        // (b) Bentuk entity-encoded yang benar WAJIB muncul (bukti esc()
        //     meng-encode metacharacter, bukan menghapus/mengeksekusinya).
        foreach ($entityWajib as $entity) {
            self::assertStringContainsString(
                $entity,
                $bubble,
                sprintf(
                    'PRESERVASI R1 (3.29): metacharacter pada teks wajar "%s" SHALL tampil sebagai entity "%s" (esc() aktif, tidak dieksekusi).',
                    $pesan,
                    $entity
                )
            );
        }

        // (c) Konten tekstual tidak hilang: setiap kata literal tetap ada.
        foreach ($kataLiteral as $kata) {
            self::assertStringContainsString(
                $kata,
                $bubble,
                sprintf(
                    'PRESERVASI R1 (3.29): konten teks "%s" SHALL tidak hilang — kata "%s" harus tetap terbaca setelah esc().',
                    $pesan,
                    $kata
                )
            );
        }
    }

    /**
     * Korpus teks WAJAR (bukan payload serangan) yang mengandung
     * metacharacter HTML dalam konteks kalimat normal. Generator ini
     * mengconstrain ruang input ke domain yang relevan untuk Property
     * preservasi tampilan teks.
     *
     * @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>}>
     */
    public static function provideTeksHtmlWajar(): array
    {
        return [
            // Kasus observasi kanonik (dari observation-first).
            'kurang-dari, lebih-dari, ampersand' => [
                'Harga 5 < 10 & diskon > 0',
                ['&lt;', '&gt;', '&amp;'],
                ['Harga 5', '10', 'diskon', '0'],
            ],
            'ampersand dalam frasa' => [
                'Divisi R&D dan Sales & Marketing',
                ['&amp;'],
                ['Divisi R', 'D dan Sales', 'Marketing'],
            ],
            'kurang-dari saja' => [
                'Nilai a < b untuk semua kasus',
                ['&lt;'],
                ['Nilai a', 'b untuk semua kasus'],
            ],
            'lebih-dari saja' => [
                'Suhu > 30 derajat hari ini',
                ['&gt;'],
                ['Suhu', '30 derajat hari ini'],
            ],
            'tanda kutip ganda & tunggal' => [
                'Dia bilang "halo" dan \'terima kasih\'',
                ['&', ';'], // esc() meng-encode " → &quot; dan ' → &#039;
                ['Dia bilang', 'halo', 'terima kasih'],
            ],
            'campuran seluruh metacharacter' => [
                'if (x < y && y > z) => "ok" \'done\'',
                ['&lt;', '&gt;', '&amp;'],
                ['if (x', 'y', 'z)', 'ok', 'done'],
            ],
            'ekspresi matematika berturut' => [
                'Rentang: 1 < n < 100 & n > 0',
                ['&lt;', '&gt;', '&amp;'],
                ['Rentang:', '1', 'n', '100', '0'],
            ],
        ];
    }

    /**
     * Kasus eksplisit (contoh, bukan generator) — memastikan bentuk
     * entity yang PERSIS seperti hasil observasi muncul utuh.
     */
    public function testKasusObservasiKanonikMenghasilkanEntitasPersisSepertiObservasi(): void
    {
        $html   = $this->renderBalasan('Harga 5 < 10 & diskon > 0');
        $bubble = $this->ambilBubbleText($html);

        self::assertStringContainsString(
            'Harga 5 &lt; 10 &amp; diskon &gt; 0',
            $bubble,
            'PRESERVASI R1 (3.29): rangkaian entity persis "Harga 5 &lt; 10 &amp; diskon &gt; 0" (baseline observasi) SHALL muncul utuh.'
        );
        self::assertStringNotContainsString(
            'Harga 5 < 10 & diskon > 0',
            $bubble,
            'PRESERVASI R1 (3.29): bentuk MENTAH tidak boleh muncul; harus ter-entity-encode.'
        );
    }

    // ══════════════════════════════════════════════════════════════
    // PROPERTY 2 (Requirement 3.30) — LINK LAMPIRAN TIDAK RUSAK
    // ══════════════════════════════════════════════════════════════

    /**
     * PROPERTY: untuk SETIAP nama berkas lampiran WAJAR (pola predictable
     * `CHAT_{id}_{timestamp}.{ext}` / nama file umum), balasan dengan
     * lampiran SHALL merender elemen <a class="eult-attach" href=...>
     * UTUH & TERPISAH dari isi pesan: href berisi endpoint loadattach +
     * nama berkas, dan nama berkas muncul sebagai teks link.
     *
     * @dataProvider provideNamaBerkasWajar
     */
    public function testBalasanDenganLampiranMerenderLinkUtuhTerpisahDariPesan(string $berkas): void
    {
        $pesan  = 'Terlampir dokumen 1 < 2 & seterusnya';
        $html   = $this->renderBalasan($pesan, $berkas);
        $bubble = $this->ambilBubbleText($html);

        // Isi pesan tetap ter-encode & tidak hilang (link terpisah dari pesan).
        self::assertStringContainsString(
            'Terlampir dokumen 1 &lt; 2 &amp; seterusnya',
            $bubble,
            'PRESERVASI R1 (3.30): isi pesan SHALL tetap tampil ter-encode & TERPISAH dari link lampiran.'
        );

        // Elemen anchor lampiran WAJIB hadir dengan class penanda.
        self::assertStringContainsString(
            '<a class="eult-attach"',
            $bubble,
            sprintf('PRESERVASI R1 (3.30): link lampiran <a class="eult-attach"> SHALL hadir untuk berkas "%s".', $berkas)
        );

        // href WAJIB berisi endpoint loadattach + nama berkas (tautan valid).
        self::assertMatchesRegularExpression(
            '#href="[^"]*cektiket/loadattach/' . preg_quote($berkas, '#') . '"#',
            $bubble,
            sprintf('PRESERVASI R1 (3.30): href link lampiran SHALL menautkan ke loadattach/%s (utuh, valid).', $berkas)
        );

        // Nama berkas WAJIB muncul sebagai teks link (bukan hanya di href).
        self::assertStringContainsString(
            '</i> ' . $berkas,
            $bubble,
            sprintf('PRESERVASI R1 (3.30): nama berkas "%s" SHALL muncul sebagai teks link yang terbaca.', $berkas)
        );

        // Anchor lampiran dirender SETELAH isi pesan (terpisah, via <br>).
        $posPesan  = strpos($bubble, 'Terlampir dokumen');
        $posAnchor = strpos($bubble, '<a class="eult-attach"');
        self::assertNotFalse($posPesan);
        self::assertNotFalse($posAnchor);
        self::assertGreaterThan(
            $posPesan,
            $posAnchor,
            'PRESERVASI R1 (3.30): link lampiran SHALL dirender setelah (terpisah dari) isi pesan pada bubble yang sama.'
        );
    }

    /**
     * Baseline penting: balasan TANPA lampiran (repliesFile kosong) TIDAK
     * merender anchor lampiran sama sekali (cabang `$berkas !== ''`).
     */
    public function testBalasanTanpaLampiranTidakMerenderAnchorLampiran(): void
    {
        $html   = $this->renderBalasan('Pesan tanpa lampiran', '');
        $bubble = $this->ambilBubbleText($html);

        self::assertStringContainsString('Pesan tanpa lampiran', $bubble);
        self::assertStringNotContainsString(
            '<a class="eult-attach"',
            $bubble,
            'PRESERVASI R1 (3.30): tanpa repliesFile, anchor lampiran SHALL tidak dirender (baseline cabang kosong).'
        );
    }

    /**
     * Korpus nama berkas lampiran WAJAR (pola predictable existing +
     * nama file umum). Mengconstrain ke ruang nama file valid, bukan
     * payload — sesuai fokus preservasi "link tidak rusak".
     *
     * @return array<string, array{0: string}>
     */
    public static function provideNamaBerkasWajar(): array
    {
        return [
            'pola CHAT predictable'      => ['CHAT_ABC_1700000000.pdf'],
            'pola CHAT id numerik'       => ['CHAT_12345_1699999999.pdf'],
            'nama file sederhana'        => ['lampiran.pdf'],
            'nama dengan angka'          => ['dokumen2026.pdf'],
            'nama dengan tanda hubung'   => ['surat-pengantar-final.pdf'],
        ];
    }
}
