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

    public function testDashboardRendersKpiCardsAndUrgentTable(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Dashboard Layanan',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'total_masuk' => 12,
            'total_proses' => 5,
            'total_selesai' => 20,
            'total_tindakan' => 3,
            'tiket_urgent' => [
                [
                    'ticketTrackingId' => 'TK-2026-001',
                    'ticketName' => 'Budi Santoso',
                    'unitNama' => 'Fakultas Teknik',
                    'statusNama' => 'Menunggu Disposisi',
                    'statusColor' => 'warning',
                    'ticketCreated' => '2026-09-13 10:00:00',
                ]
            ],
            'tren_mingguan' => [
                'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'],
                'data' => [10, 15, 8, 20, 12]
            ],
        ])->render('pages/home/index');

        $this->assertStringContainsString('Tiket Baru Masuk', $html);
        $this->assertStringContainsString('Perlu Tindakan', $html);
        $this->assertStringContainsString('Tiket Dalam Proses', $html);
        $this->assertStringContainsString('Tiket Selesai', $html);
        $this->assertStringContainsString('TK-2026-001', $html);
    }
}
