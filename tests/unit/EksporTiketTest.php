<?php

use App\Libraries\Enkripsi;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regresi endpoint ekspor satu tiket (butuh MySQL: suite Database).
 *
 * Sebelumnya `ticketing/export` dipasang sebagai href tombol "Ekspor Excel"
 * pada daftar tiket tanpa segmen kunci, padahal rutenya `export/(:any)` —
 * tombol itu 404. Endpoint-nya sendiri mengembalikan JSON mentah (bahkan
 * `false` saat kosong), jadi walau dirutekan benar hasilnya bukan berkas
 * spreadsheet. Sekarang: daftar diekspor klien-side oleh ticketing.js, dan
 * `export/(:any)` mengunduh CSV rekap satu tiket.
 *
 * @internal
 */
final class EksporTiketTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const TIKET = 'ZZEXP-0000-001';

    private Enkripsi $enkripsi;

    private BaseConnection $koneksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enkripsi = new Enkripsi();
        $this->koneksi  = \Config\Database::connect('default');

        $this->bersihkanData();
        $this->koneksi->table('d_ticketing')->insert([
            'ticketTrackingId' => self::TIKET,
            'ticketName'       => 'Pemohon Uji',
            'ticketEmail'      => 'pemohon.uji@example.com',
            'ticketCreated'    => '2026-09-14 08:30:00',
        ]);
    }

    protected function tearDown(): void
    {
        $this->bersihkanData();
        parent::tearDown();
    }

    private function bersihkanData(): void
    {
        $this->koneksi->table('d_disposisi')->where('disposisiTicketId', self::TIKET)->delete();
        $this->koneksi->table('d_ticketing')->where('ticketTrackingId', self::TIKET)->delete();
    }

    /**
     * @return array<string, string>
     */
    private function sesi(string $grup = 'ADMIN'): array
    {
        return [
            'susrNama'           => 'admin_uji',
            'susrSgroupNama'     => $grup,
            'susrSgroupNama_ori' => $grup,
            'susrProfil'         => 'Admin Uji',
        ];
    }

    public function testEksporSatuTiketMengunduhCsvBukanJson(): void
    {
        $kunci = (string) $this->enkripsi->encode(self::TIKET);

        $this->koneksi->table('d_disposisi')->insert([
            'disposisiTicketId' => self::TIKET,
            'disposisiMessage'  => 'Diteruskan ke unit',
            'disposisiTanggal'  => '2026-09-14 09:00:00',
            'disposisiUnit'     => '00',
            'disposisiUser'     => 'admin_uji',
        ]);

        $hasil = $this->withSession(['logged_in' => $this->sesi()])
            ->get('ticketing/export/' . $kunci);

        $respons = $hasil->response();
        $isi     = (string) $respons->getBody();

        $this->assertSame(200, $respons->getStatusCode());
        $this->assertStringContainsString('text/csv', (string) $respons->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $respons->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('.csv', (string) $respons->getHeaderLine('Content-Disposition'));

        // BOM UTF-8 agar Excel tidak salah membaca karakter non-ASCII.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $isi);
        $this->assertStringContainsString('Nomor Tiket', $isi);
        $this->assertStringContainsString(self::TIKET, $isi);
        $this->assertStringContainsString('Pemohon Uji', $isi);

        // Bukan lagi payload JSON.
        $this->assertStringNotContainsString('{"', $isi);
    }

    public function testEksporTiketTanpaDisposisiTetapTereksporUntukAdmin(): void
    {
        $kunci = (string) $this->enkripsi->encode(self::TIKET);

        // ADMIN memakai dataById() (sama seperti response()), yang tidak
        // bergantung pada baris d_disposisi maupun pemetaan
        // s_user_group_unit — ADMIN tidak punya baris pemetaan tersebut.
        $hasil = $this->withSession(['logged_in' => $this->sesi()])
            ->get('ticketing/export/' . $kunci);

        $isi = (string) $hasil->response()->getBody();

        $this->assertSame(200, $hasil->response()->getStatusCode());
        $this->assertStringContainsString(self::TIKET, $isi);
        // Bukan lagi body JSON "false" seperti perilaku lama.
        $this->assertStringNotContainsString('false', $isi);
    }

    public function testNilaiDariInputPublikTidakMenjadiFormulaSpreadsheet(): void
    {
        // ticketName berasal langsung dari form tiket publik (Login::savetiket),
        // lalu dibuka admin di Excel. Awalan = + - @ harus dinetralkan.
        $this->koneksi->table('d_ticketing')
            ->where('ticketTrackingId', self::TIKET)
            ->update(['ticketName' => '=cmd|\'/c calc\'!A1']);

        $kunci = (string) $this->enkripsi->encode(self::TIKET);

        $hasil = $this->withSession(['logged_in' => $this->sesi()])
            ->get('ticketing/export/' . $kunci);

        $isi      = preg_replace('/^\xEF\xBB\xBF/', '', (string) $hasil->response()->getBody());
        $pengurai = fopen('php://temp', 'r+');
        fwrite($pengurai, (string) $isi);
        rewind($pengurai);
        $baris = [];
        while (($r = fgetcsv($pengurai)) !== false) {
            $baris[] = $r;
        }
        fclose($pengurai);

        $sel = $baris[1][1] ?? '';

        $this->assertSame('\'=cmd|\'/c calc\'!A1', $sel, 'Awalan formula harus dinetralkan kutip tunggal.');
        $this->assertStringStartsNotWith('=', $sel);
    }

    public function testNilaiWajarTidakIkutDiubah(): void
    {
        $kunci = (string) $this->enkripsi->encode(self::TIKET);

        $hasil = $this->withSession(['logged_in' => $this->sesi()])
            ->get('ticketing/export/' . $kunci);

        $isi      = preg_replace('/^\xEF\xBB\xBF/', '', (string) $hasil->response()->getBody());
        $pengurai = fopen('php://temp', 'r+');
        fwrite($pengurai, (string) $isi);
        rewind($pengurai);
        $baris = [];
        while (($r = fgetcsv($pengurai)) !== false) {
            $baris[] = $r;
        }
        fclose($pengurai);

        // Nama biasa dan tanggal tidak boleh kejatuhan kutip tunggal.
        $this->assertSame('Pemohon Uji', $baris[1][1] ?? '');
        $this->assertSame('2026-09-14 08:30:00', $baris[1][7] ?? '');
    }

    public function testEksporTiketTidakDikenalMengembalikan404BukanJsonFalse(): void
    {
        $kunci = (string) $this->enkripsi->encode('ZZEXP-TIDAK-ADA');

        $hasil = $this->withSession(['logged_in' => $this->sesi()])
            ->get('ticketing/export/' . $kunci);

        // disposisiAll()/dataById() mengembalikan false untuk hasil kosong;
        // dulu nilai itu dikirim apa adanya sebagai body JSON "false".
        $this->assertSame(404, $hasil->response()->getStatusCode());
        $this->assertStringNotContainsString('false', (string) $hasil->response()->getBody());
    }
}
