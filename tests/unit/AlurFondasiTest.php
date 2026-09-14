<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Test alur HTTP fondasi: halaman publik terbuka, area auth ditolak,
 * login gagal memberi JSON danger, dan captcha refresh jalan.
 *
 * @internal
 */
final class AlurFondasiTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHalamanLoginTerbuka(): void
    {
        $hasil = $this->get('/');

        $hasil->assertOK();
        $hasil->assertSee('Unit Layanan Terpadu');
    }

    public function testAreaTerproteksiMengarahKeLogin(): void
    {
        foreach (['/home', '/ticketing', '/laporan', '/pengguna', '/validasifile'] as $url) {
            $hasil = $this->get($url);
            $hasil->assertRedirectTo(site_url('login'));
        }
    }

    public function testLoginCaptchaSalahDitolak(): void
    {
        // Filter CSRF global aktif: POST tanpa token ditolak SecurityException
        // sebelum controller; sertakan token seperti form login sungguhan.
        \Config\Services::injectMock('request', $this->setupRequest('POST', 'otentifikasi'));
        \Config\Services::resetSingle('security');

        $hasil = $this->post('/otentifikasi', [
            'username'   => 'tidakada',
            'password'   => 'salah',
            'captcha'    => 'XXXX-SALAH',
            csrf_token() => csrf_hash(),
        ]);

        $hasil->assertOK();
        $hasil->assertJSONFragment(['status' => 'danger']);
    }

    public function testRefreshCaptchaMenghasilkanKode(): void
    {
        $hasil = $this->get('login/refresh_captcha');

        $hasil->assertOK();

        // T2 (Requirement 2.28): endpoint refresh TIDAK LAGI mengirim
        // string captcha plaintext — respons berisi URL gambar (dengan
        // nonce cache-busting, sehingga tidak exact-match statis) yang
        // membaca ulang session()->get('captcha') saat diakses.
        $json = json_decode($hasil->getJSON(), true);
        self::assertIsArray($json);
        self::assertArrayHasKey('captcha_image_url', $json);
        self::assertStringStartsWith(base_url('login/captcha_image'), (string) $json['captcha_image_url']);
    }

    public function testHomeDashboardTerbukaDenganSesiLogin(): void
    {
        $hasil = $this->withSession([
            'logged_in' => [
                'susrNama'           => 'admin_test',
                'susrSgroupNama'     => 'ADMIN',
                'susrSgroupNama_ori' => 'ADMIN',
                'susrProfil'         => 'Administrator Test',
            ],
        ])->get('/home');

        $hasil->assertOK();
        $hasil->assertSee('Tiket Baru Masuk');
        $hasil->assertSee('Perlu Tindakan');
        $hasil->assertSee('Tiket Dalam Proses');
        $hasil->assertSee('Tiket Selesai');
    }
}
