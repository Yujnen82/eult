<?php

use App\Libraries\Enkripsi;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Test kontrol akses berkas publik (cektiket/loadpdf & cektiket/loadattach).
 *
 * Regresi IDOR: berkas hanya boleh disajikan bila kunci tiket yang diminta
 * benar-benar memiliki berkas tersebut. Nama berkas mentah pada URL tidak
 * lagi cukup untuk mengunduh.
 *
 * @internal
 */
final class AksesBerkasTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const TIKET_A = 'ZZTEST-0000-001';
    private const TIKET_B = 'ZZTEST-0000-002';
    private const FILE_A  = 'CHAT_ZZTEST-0000-001_20260913120000.pdf';
    private const FILE_B  = 'CHAT_ZZTEST-0000-002_20260913120001.pdf';
    private const FILE_PDF = 'ZZTESTOUT_20260913120000.pdf';
    private const ISI_A   = '%PDF-1.4 EULT-UJI-A';
    private const ISI_B   = '%PDF-1.4 EULT-UJI-B';

    private Enkripsi $enkripsi;

    private BaseConnection $koneksi;

    private string $kunciA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enkripsi = new Enkripsi();
        $this->koneksi  = \Config\Database::connect('default');
        $this->kunciA   = (string) $this->enkripsi->encode(self::TIKET_A);

        $this->bersihkanData();

        // Berkas uji di disk (jalur chat & ticketing sama seperti produksi).
        file_put_contents(WRITEPATH . 'uploads/chat/' . self::FILE_A, self::ISI_A);
        file_put_contents(WRITEPATH . 'uploads/chat/' . self::FILE_B, self::ISI_B);
        file_put_contents(WRITEPATH . 'uploads/ticketing/' . self::FILE_PDF, self::ISI_A);

        // Lampiran chat milik masing-masing tiket.
        $this->koneksi->table('d_replies')->insert([
            'repliesTicketId' => self::TIKET_A,
            'repliesMessage'  => 'uji a',
            'repliesDate'     => '2026-09-13 12:00:00',
            'repliesStatus'   => 'USER',
            'repliesBy'       => 'uji',
            'repliesFile'     => self::FILE_A,
            'repliesRead'     => '1',
        ]);
        $this->koneksi->table('d_replies')->insert([
            'repliesTicketId' => self::TIKET_B,
            'repliesMessage'  => 'uji b',
            'repliesDate'     => '2026-09-13 12:00:01',
            'repliesStatus'   => 'USER',
            'repliesBy'       => 'uji',
            'repliesFile'     => self::FILE_B,
            'repliesRead'     => '1',
        ]);

        // Berkas output PDF milik tiket A (d_archive ber-FK ke d_ticketing).
        $this->koneksi->table('d_ticketing')->insert(['ticketTrackingId' => self::TIKET_A]);
        $this->koneksi->table('d_archive')->insert([
            'archiveId'         => 'ZZTESTOUT0001',
            'archiveFile'       => self::FILE_PDF,
            'archiveJenis'      => 'OUTPUT',
            'archiveTrackingId' => self::TIKET_A,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            WRITEPATH . 'uploads/chat/' . self::FILE_A,
            WRITEPATH . 'uploads/chat/' . self::FILE_B,
            WRITEPATH . 'uploads/ticketing/' . self::FILE_PDF,
        ] as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        $this->bersihkanData();

        parent::tearDown();
    }

    /**
     * Menghapus data uji (idempotent agar sisa run sebelumnya tidak
     * memicu galat primary key pada setUp berikutnya).
     */
    private function bersihkanData(): void
    {
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET_A)->delete();
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET_B)->delete();
        $this->koneksi->table('d_archive')->where('archiveId', 'ZZTESTOUT0001')->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET_A)->delete();
    }

    public function testLoadpdfMenolakNamaBerkasMentah(): void
    {
        $hasil = $this->get('cektiket/loadpdf/' . self::FILE_PDF);

        $hasil->assertStatus(404);
        $this->assertStringNotContainsString(self::ISI_A, $hasil->getBody());
    }

    public function testLoadattachMenolakBerkasTanpaKunci(): void
    {
        // Rute tanpa segmen kunci tidak lagi tersedia.
        $this->expectException(PageNotFoundException::class);

        $this->get('cektiket/loadattach/' . self::FILE_A);
    }

    public function testLoadattachMenolakBerkasMilikTiketLain(): void
    {
        $hasil = $this->get('cektiket/loadattach/' . $this->kunciA . '/' . self::FILE_B);

        $hasil->assertStatus(404);
        $this->assertStringNotContainsString(self::ISI_B, $hasil->getBody());
    }

    public function testLoadpdfMenyajikanBerkasMilikTiketYangKuncinyaSah(): void
    {
        $hasil = $this->get('cektiket/loadpdf/' . $this->kunciA);

        $hasil->assertStatus(200);
        $this->assertStringContainsString(self::ISI_A, $hasil->getBody());
    }

    public function testLoadattachMenyajikanLampiranMilikTiketYangKuncinyaSah(): void
    {
        $hasil = $this->get('cektiket/loadattach/' . $this->kunciA . '/' . self::FILE_A);

        $hasil->assertStatus(200);
        $this->assertStringContainsString(self::ISI_A, $hasil->getBody());
    }

    public function testAdminLoadpdfMenyajikanBerkasUntukAdmin(): void
    {
        $hasil = $this->withSession(['logged_in' => $this->sesi('ADMIN')])
            ->get('ticketing/loadpdf/' . self::FILE_PDF);

        $hasil->assertStatus(200);
        $this->assertStringContainsString(self::ISI_A, $hasil->getBody());
    }

    public function testAdminLoadpdfMenolakStafTanpaHakAtasTiket(): void
    {
        $grup = $this->grupTanpaHakTiket();

        if ($grup === '') {
            $this->markTestSkipped('Tidak ada grup staf uji pada data referensi.');
        }

        $hasil = $this->withSession(['logged_in' => $this->sesi($grup)])
            ->get('ticketing/loadpdf/' . self::FILE_PDF);

        $hasil->assertStatus(403);
        $this->assertStringNotContainsString(self::ISI_A, $hasil->getBody());
    }

    public function testAdminLoadattachMenolakStafTanpaHakAtasTiket(): void
    {
        $grup = $this->grupTanpaHakTiket();

        if ($grup === '') {
            $this->markTestSkipped('Tidak ada grup staf uji pada data referensi.');
        }

        $hasil = $this->withSession(['logged_in' => $this->sesi($grup)])
            ->get('ticketing/loadattach/' . self::FILE_A);

        $hasil->assertStatus(403);
        $this->assertStringNotContainsString(self::ISI_A, $hasil->getBody());
    }

    /**
     * @return array<string, string>
     */
    private function sesi(string $grup): array
    {
        return [
            'susrNama'           => 'staf_uji',
            'susrSgroupNama'     => $grup,
            'susrSgroupNama_ori' => $grup,
            'susrProfil'         => 'Staf Uji',
        ];
    }

    /**
     * Grup staf yang boleh membuka modul ticketing namun bukan
     * ADMIN/OPERATOR, sehingga tidak otomatis berhak atas semua tiket.
     */
    private function grupTanpaHakTiket(): string
    {
        $baris = \Config\Database::connect('default')->table('s_user_group_modul')
            ->select('sgroupmodulSgroupNama')
            ->where('sgroupmodulSusrmodulNama', 'ticketing')
            ->where('sgroupmodulSusrmodulRead', '1')
            ->where('sgroupmodulSgroupNama !=', 'ADMIN')
            ->notLike('sgroupmodulSgroupNama', 'OPERATOR')
            ->limit(1)
            ->get()->getRowArray();

        return (string) ($baris['sgroupmodulSgroupNama'] ?? '');
    }
}
