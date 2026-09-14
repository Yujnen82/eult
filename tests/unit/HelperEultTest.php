<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test helper EULT (captcha, kode, tanggal, waktu, password).
 *
 * @internal
 */
final class HelperEultTest extends CIUnitTestCase
{
    public function testCaptchaGenerateDanCek(): void
    {
        $kode = eult_captcha_generate(4);

        $this->assertSame(4, strlen($kode));
        $this->assertTrue(eult_captcha_check($kode));
        $this->assertTrue(eult_captcha_check(strtolower($kode)));
        $this->assertFalse(eult_captcha_check('SALAH'));
    }

    public function testGenerateKodeFormatTiket(): void
    {
        $kode = eult_generate_kode();

        $this->assertMatchesRegularExpression('/^[A-Z]{4}-[A-Z]{4}$/', $kode);
    }

    public function testTanggalIndo(): void
    {
        $this->assertSame('13 September 2026', eult_tanggal_indo('2026-09-13'));
        $this->assertFalse(eult_tanggal_indo('0000-00-00'));
        $this->assertSame('Senin', eult_hari_indo('Mon'));
        $this->assertSame('Tidak di ketahui', eult_hari_indo('Xyz'));
    }

    public function testWaktuLalu(): void
    {
        $hasil = eult_waktu_lalu(date('Y-m-d H:i:s', time() - 10));

        $this->assertStringEndsWith('ago', $hasil);
    }

    public function testGeneratePasswordAcakMinimal12Karakter(): void
    {
        $sandi = eult_generate_password(6);

        // Permintaan di bawah 12 karakter dinaikkan ke minimum agar password
        // sementara tidak dapat ditebak (sebelumnya 6 digit angka unik).
        $this->assertSame(12, strlen($sandi));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $sandi);
        $this->assertSame(16, strlen(eult_generate_password(16)));
        $this->assertNotSame(eult_generate_password(), eult_generate_password());
    }
}
