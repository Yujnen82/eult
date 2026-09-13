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
        $hasil = $this->post('/otentifikasi', [
            'username' => 'tidakada',
            'password' => 'salah',
            'captcha'  => 'XXXX-SALAH',
        ]);

        $hasil->assertOK();
        $hasil->assertJSONFragment(['status' => 'danger']);
    }

    public function testRefreshCaptchaMenghasilkanKode(): void
    {
        $hasil = $this->get('login/refresh_captcha');

        $hasil->assertOK();
        $hasil->assertJSONFragment(['captcha' => session()->get('captcha')]);
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
