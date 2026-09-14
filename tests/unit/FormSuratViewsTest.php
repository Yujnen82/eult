<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi render form yang sebelumnya masih memakai objek CI3
 * ($this->uri / $this->session) sehingga gagal dirender di CI4
 * ("Undefined property: CodeIgniter\View\View::$uri").
 *
 * @internal
 */
final class FormSuratViewsTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Config\Services::reset();
    }

    /**
     * @return array<string, mixed>
     */
    private function dataFormSurat(): array
    {
        return [
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'save_url'    => site_url('ticketing/delivered/'),
            'status_page' => 'Create',
            'datas'       => false,
            'surat'       => ['tsuratNomor' => '001/UN17/2026', 'tsuratPerihal' => 'Keterangan Aktif', 'tsuratIsi' => '<p>Isi</p>', 'tsuratFooter' => '', 'tsuratTujuan' => '', 'tsuratLampiran' => '-'],
            'pejabatTtd'  => [['unitPejabatNIP' => '1980', 'unitPejabatJabatan' => 'Kepala', 'unitPejabatNama' => 'Budi']],
            'kunci'       => 'KUNCI-TERENKRIPSI',
            'user_data'   => 'ADMIN',
            'preview_url' => site_url('ticketing/get_preview'),
            'scripts'     => [],
        ];
    }

    public function testFormSuratMemakaiKunciDariControllerDanMenyertakanCsrf(): void
    {
        $html = view('pages/ticketing/form_surat', $this->dataFormSurat());

        $this->assertStringContainsString('name="suratTrackingId" id="suratTrackingId" value="KUNCI-TERENKRIPSI"', $html);
        $this->assertStringContainsString('action="' . site_url('ticketing/delivered/') . '"', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
    }

    public function testFormSuratKtmMemakaiKunciDariControllerDanMenyertakanCsrf(): void
    {
        $data             = $this->dataFormSurat();
        $data['save_url'] = site_url('ticketing/delivered_ktm/');

        $html = view('pages/ticketing/form_surat_ktm', $data);

        $this->assertStringContainsString('value="KUNCI-TERENKRIPSI"', $html);
        $this->assertStringContainsString('action="' . site_url('ticketing/delivered_ktm/') . '"', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
    }

    public function testFormSubLayananMemakaiKunciTerenkripsiUntukIndukBaru(): void
    {
        $html = view('pages/refkategori/form_add', [
            'page_judul'  => 'Sub Layanan',
            'save_url'    => site_url('refkategori/save_sub/'),
            'status_page' => 'Create',
            'datas'       => false,
            'kunci'       => 'KUNCI-INDUK',
            'scripts'     => [],
        ]);

        $this->assertStringContainsString('name="sCatCategoryId" value="KUNCI-INDUK"', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
    }

    public function testTidakAdaViewYangMemakaiObjekCi3(): void
    {
        $pelanggaran = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH . 'Views', \FilesystemIterator::SKIP_DOTS)) as $berkas) {
            if ($berkas->getExtension() !== 'php') {
                continue;
            }

            $isi = (string) file_get_contents($berkas->getPathname());
            if (preg_match('/\$this->(uri|session|input|db|load)\b/', $isi) === 1) {
                $pelanggaran[] = str_replace(APPPATH, 'app/', $berkas->getPathname());
            }
        }

        $this->assertSame([], $pelanggaran, 'View masih memakai objek CI3 ($this->uri/$this->session/...).');
    }
}
