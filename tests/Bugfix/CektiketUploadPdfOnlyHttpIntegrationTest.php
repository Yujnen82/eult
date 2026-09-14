<?php

namespace Tests\Bugfix;

use App\Libraries\Enkripsi;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Regression test: lampiran percakapan pada halaman lacak tiket publik
 * (Cektiket::saveReplies()) HANYA menerima berkas PDF.
 *
 * Mengapa unggahan diuji lewat HTTP live (cURL), bukan FeatureTestTrait:
 * jalur penolakan tipe berkas berakhir di eult_message_kirim()
 * (app/Helpers/eult_message_helper.php) yang memanggil `exit;` SUNGGUHAN —
 * menghentikan seluruh proses PHPUnit bila dijalankan in-process (pola
 * sama dengan K1SqlInjectionHttpIntegrationTest). Selain itu
 * FeatureTestTrait pada versi framework ini tidak menyediakan simulasi
 * unggahan berkas ($_FILES).
 *
 * @internal
 */
final class CektiketUploadPdfOnlyHttpIntegrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const BASE_URL_LIVE = 'https://eult.appdev-papenajam.me/';

    private const TIKET_UJI = 'ZZTEST-UPLOAD-001';

    /** JPEG 1x1 piksel valid — mime terdeteksi image/jpeg dari isi berkas, bukan dari ekstensi. */
    private const JPEG_1PIKSEL_B64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==';

    private const KONTEN_PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Count 0 /Kids [] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

    private BaseConnection $koneksi;

    private Enkripsi $enkripsi;

    /** @var list<string> */
    private array $berkasSementara = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->koneksi  = \Config\Database::connect('default');
        $this->enkripsi = new Enkripsi();

        self::assertSame('db_newtiket', $this->koneksi->database, 'Test HTTP integrasi ini WAJIB tersambung ke database nyata db_newtiket agar verifikasi efek samping bermakna.');

        $this->bersihkanArtefak();
    }

    protected function tearDown(): void
    {
        $this->bersihkanArtefak();

        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }
        $this->berkasSementara = [];

        parent::tearDown();
    }

    /**
     * Menghapus baris d_replies dan berkas chat uji (idempoten).
     */
    private function bersihkanArtefak(): void
    {
        $this->koneksi->table('d_replies')->where('repliesTicketId', self::TIKET_UJI)->delete();

        foreach ($this->berkasChatTersimpan() as $berkas) {
            unlink($berkas);
        }
    }

    /**
     * @return list<string>
     */
    private function berkasChatTersimpan(): array
    {
        return glob(WRITEPATH . 'uploads/chat/CHAT_' . self::TIKET_UJI . '_*') ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function barisBalasan(): ?array
    {
        return $this->koneksi->table('d_replies')
            ->where('repliesTicketId', self::TIKET_UJI)
            ->get()->getRowArray();
    }

    public function testLampiranJpgDitolakTanpaEfekSamping(): void
    {
        $hasil = $this->postBerkasLive('cektiket/save_replies', [
            'repliesMessage'  => 'uji tolak lampiran jpg',
            'repliesTicketId' => self::TIKET_UJI,
        ], 'lampiran.jpg', 'image/jpeg', $this->kontenJpeg());

        self::assertSame(200, $hasil['status'], 'Penolakan tipe berkas SHALL memakai respons JSON eult_message_kirim() (HTTP 200). Body: ' . $hasil['body']);

        $json = json_decode($hasil['body'], true);

        self::assertIsArray($json, 'Respons penolakan SHALL berupa JSON. Body: ' . $hasil['body']);
        self::assertSame('error', $json['status'] ?? null);
        self::assertSame(
            'Tipe file tidak diizinkan. Hanya: pdf',
            $json['message'] ?? null,
            'Daftar tipe berkas yang diizinkan SHALL hanya berisi pdf (bukan pdf|jpg|png).'
        );

        self::assertNull($this->barisBalasan(), 'Berkas JPG yang ditolak SHALL TIDAK membuat baris d_replies.');
        self::assertSame([], $this->berkasChatTersimpan(), 'Berkas JPG yang ditolak SHALL TIDAK tersimpan di writable/uploads/chat.');
    }

    public function testLampiranPdfDiterimaDanTersimpan(): void
    {
        $hasil = $this->postBerkasLive('cektiket/save_replies', [
            'repliesMessage'  => 'uji terima lampiran pdf',
            'repliesTicketId' => self::TIKET_UJI,
        ], 'lampiran.pdf', 'application/pdf', self::KONTEN_PDF);

        // 303 See Other: status redirect CI4 untuk request POST (Post/Redirect/Get).
        self::assertSame(303, $hasil['status'], 'Berkas PDF yang sah SHALL diproses dan dialihkan (redirect) ke halaman tiket. Body: ' . $hasil['body']);

        $baris = $this->barisBalasan();

        self::assertIsArray($baris, 'Berkas PDF yang sah SHALL membuat baris d_replies.');
        self::assertSame(self::TIKET_UJI, (string) $baris['repliesTicketId']);

        $namaFile = (string) ($baris['repliesFile'] ?? '');

        self::assertMatchesRegularExpression(
            '/^CHAT_' . preg_quote(self::TIKET_UJI, '/') . '_\d{14}\.pdf$/',
            $namaFile,
            'Nama berkas tersimpan SHALL mengikuti pola CHAT_<tiket>_<waktu>.pdf.'
        );
        self::assertFileExists(WRITEPATH . 'uploads/chat/' . $namaFile);
    }

    public function testHalamanLacakHanyaMenawarkanLampiranPdf(): void
    {
        $nomorTiket = $this->koneksi->table('d_ticketing')
            ->select('ticketTrackingId')
            ->limit(1)
            ->get()->getRowArray();

        self::assertIsArray($nomorTiket, 'Prasyarat test: d_ticketing SHALL berisi minimal satu tiket nyata agar halaman cektiket dapat dirender.');

        $kunci = (string) $this->enkripsi->encode((string) $nomorTiket['ticketTrackingId']);

        $hasil = $this->get('cektiket/index/' . $kunci);

        $hasil->assertStatus(200);

        $body = $hasil->getBody();

        self::assertStringContainsString('accept=".pdf,application/pdf"', $body, 'Input berkas SHALL hanya menerima PDF.');
        self::assertStringContainsString('Lampirkan PDF (opsional', $body, 'Label berkas SHALL menyebut PDF saja.');
        self::assertStringNotContainsString('image/jpeg', $body, 'Input berkas SHALL TIDAK lagi menyebut JPG.');
        self::assertStringNotContainsString('image/png', $body, 'Input berkas SHALL TIDAK lagi menyebut PNG.');
    }

    private function kontenJpeg(): string
    {
        $isi = base64_decode(self::JPEG_1PIKSEL_B64, true);

        self::assertIsString($isi, 'Fixture JPEG uji SHALL valid.');

        return $isi;
    }

    /**
     * @param array<string, string> $post
     *
     * @return array{status: int, body: string}
     */
    private function postBerkasLive(string $path, array $post, string $namaBerkas, string $mime, string $konten): array
    {
        $temporer = tempnam(sys_get_temp_dir(), 'eult_uji_');
        file_put_contents($temporer, $konten);
        $this->berkasSementara[] = $temporer;

        self::assertSame(
            $mime,
            mime_content_type($temporer),
            'Sanity fixture: mime berkas uji SHALL benar-benar terdeteksi sesuai tipe yang dideklarasikan agar penolakan/penetapan diuji atas tipe yang dimaksud.'
        );

        $post['chatFile'] = new \CURLFile($temporer, $mime, $namaBerkas);

        return $this->kirimLive($path, $post);
    }

    /**
     * Mengambil token CSRF SUNGGUHAN dari server dev live via GET,
     * pola IDENTIK dengan
     * T4RatingHttpIntegrationTest::ambilTokenCsrfDariServerLive()
     * (task 18.2 mengaktifkan filter csrf pada Klaster 3 task 18.1 —
     * dispatch POST cektiket/save_replies tanpa token+cookie kini
     * ditolak 403 oleh server, dikonfirmasi empiris sebelum perubahan
     * method ini).
     *
     * @return array{jar: string, token: string, hash: string}
     */
    private function ambilTokenCsrfDariServerLive(): array
    {
        $jarKuki = tempnam(sys_get_temp_dir(), 'eult_csrf_jar_');

        $ch = curl_init(self::BASE_URL_LIVE . 'login');

        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        $opsi = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_COOKIEJAR      => $jarKuki,
        ];

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        // WAJIB: curl_close() SAJA tidak reliable memaksa penulisan
        // cookie jar ke disk pada build libcurl/PHP di environment ini
        // (dikonfirmasi empiris — lihat
        // T4RatingHttpIntegrationTest::ambilTokenCsrfDariServerLive()
        // untuk investigasi lengkap). CURLOPT_COOKIELIST 'FLUSH' memaksa
        // penulisan cookie in-memory ke file CURLOPT_COOKIEJAR SEBELUM
        // handle ditutup.
        curl_setopt($ch, CURLOPT_COOKIELIST, 'FLUSH');
        curl_close($ch);

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil mengambil token CSRF dari server dev live (GET login) tanpa error transport (errno=%d: %s).', $errno, $error));

        $isiJar = (string) file_get_contents($jarKuki);

        self::assertMatchesRegularExpression(
            '/csrf_cookie_name\s+([0-9a-f]+)/',
            $isiJar,
            'Prasyarat: cookie csrf_cookie_name SHALL ada pada cookie jar setelah GET login (Config\\Security::$csrfProtection = \'cookie\').'
        );

        preg_match('/csrf_cookie_name\s+([0-9a-f]+)/', $isiJar, $tangkapan);

        return ['jar' => $jarKuki, 'token' => 'csrf_test_name', 'hash' => $tangkapan[1]];
    }

    /**
     * @param array<string, mixed>|null $post
     *
     * @return array{status: int, body: string}
     */
    private function kirimLive(string $path, ?array $post = null): array
    {
        $jarKukiUntukDihapus = null;

        if ($post !== null) {
            $csrf                 = $this->ambilTokenCsrfDariServerLive();
            $post[$csrf['token']] = $csrf['hash'];
            $jarKukiUntukDihapus  = $csrf['jar'];
        }

        $ch = curl_init(self::BASE_URL_LIVE . $path);

        $opsi = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($post !== null) {
            $opsi[CURLOPT_POST]       = true;
            $opsi[CURLOPT_POSTFIELDS] = $post;
            // WAJIB: kirim ULANG cookie csrf_cookie_name (+ ci_session)
            // yang sama dari GET login pada ambilTokenCsrfDariServerLive()
            // — server memvalidasi token yang di-POST terhadap hash yang
            // di-restore dari cookie YANG MASUK BERSAMA request POST ini
            // sendiri (Security::restoreHash()).
            $opsi[CURLOPT_COOKIEFILE] = $jarKukiUntukDihapus;
        }

        // PHP CLI di lingkungan ini memakai bundle OpenSSL yang TIDAK memuat
        // root CA dev FlyEnv; arahkan eksplisit ke bundle CA sistem (pola
        // sama dengan K1SqlInjectionHttpIntegrationTest) agar verifikasi
        // rantai sertifikat server dev tetap aktif.
        $bundleCaSistem = '/etc/ssl/certs/ca-certificates.crt';

        if (is_file($bundleCaSistem)) {
            $opsi[CURLOPT_CAINFO] = $bundleCaSistem;
        }

        curl_setopt_array($ch, $opsi);

        $body   = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($jarKukiUntukDihapus !== null && is_file($jarKukiUntukDihapus)) {
            unlink($jarKukiUntukDihapus);
        }

        self::assertSame(0, $errno, sprintf('cURL SHALL berhasil terhubung ke server dev live tanpa error transport (errno=%d: %s). Pastikan server live reachable sebelum menjalankan test ini.', $errno, $error));
        self::assertIsString($body, 'cURL SHALL mengembalikan body response sebagai string.');

        return ['status' => $status, 'body' => $body];
    }
}
