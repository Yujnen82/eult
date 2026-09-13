<?php

use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Test helper upload EULT.
 *
 * Regresi: nama berkas hasil unggah harus mengikuti nama tujuan yang
 * dikonfigurasi (mis. CHAT_<tiket>_<waktu>.pdf), BUKAN nama temporer
 * PHP (/tmp/phpXXXX...) yang dipegang UploadedFile::getFilename().
 *
 * @internal
 */
final class UploadHelperTest extends CIUnitTestCase
{
    private string $tujuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tujuan = sys_get_temp_dir() . '/eult_upload_' . bin2hex(random_bytes(4)) . '/';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tujuan)) {
            foreach (glob($this->tujuan . '*') ?: [] as $berkas) {
                unlink($berkas);
            }
            rmdir($this->tujuan);
        }

        parent::tearDown();
    }

    public function testNamaBerkasHasilUnggahMengikutiNamaKonfigurasi(): void
    {
        // Nama temporer PHP meniru $_FILES tmp_name pada server produksi.
        $temporer = tempnam(sys_get_temp_dir(), 'php');
        file_put_contents($temporer, '%PDF-1.4 uji unggah');

        $berkas = new class($temporer, 'lampiran.pdf', 'application/pdf', null, UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return true;
            }

            public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
            {
                return rename($this->getTempName(), rtrim($targetPath, '/') . '/' . $name);
            }
        };

        $request = $this->getMockBuilder(IncomingRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFile'])
            ->getMock();
        $request->method('getFile')->willReturn($berkas);
        Services::injectMock('request', $request);

        $hasil = eult_upload_custom([
            'url'      => $this->tujuan,
            'type'     => 'pdf',
            'size'     => 1024,
            'namafile' => 'CHAT_UJI_20260913120000',
        ], 'chatFile');

        $this->assertSame('CHAT_UJI_20260913120000.pdf', $hasil->getFilename());
        $this->assertFileExists($this->tujuan . 'CHAT_UJI_20260913120000.pdf');
    }
}
