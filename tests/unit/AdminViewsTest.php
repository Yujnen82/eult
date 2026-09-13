<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pengujian dasar kesiapan view admin E-ULT v2.
 */
class AdminViewsTest extends CIUnitTestCase
{
    public function testHomeIndexViewExistsAndRenders(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Dashboard',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'total_masuk' => 12,
            'total_proses' => 5,
            'total_selesai' => 20,
            'total_tindakan' => 3,
            'tiket_urgent' => [],
            'tren_mingguan' => [],
        ])->render('pages/home/index');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('kt-portlet', $html);
    }
}
