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

    public function testTicketingResponseRendersScannableTableAndBadges(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Daftar Tiket Permohonan Layanan',
            'user_group' => 'ADMIN',
            'detail_url' => base_url('ticketing/detail/'),
            'sgroup' => false,
            'datas' => [
                [
                    'ticketTrackingId' => 'TK-2026-999',
                    'ticketName' => 'Siti Rahmawati',
                    'ticketCreated' => '2026-09-13 14:00:00',
                    'categoryNama' => 'Layanan Akademik',
                    'sCatNama' => 'Legalisir Ijazah & Transkrip',
                    'priorityName' => 'Normal',
                    'unitNama' => 'Biro Akademik',
                    'statusNama' => 'Dalam Proses',
                    'statusColor' => 'info',
                    'ticketStatus' => 3,
                    'disposisiIsTrue' => false,
                    'ticketSuratCreated' => 'SRT-01',
                    'sCatDisposisi' => 'TOPDOWN',
                    'ticketAssign' => 1,
                    'ticketIsVerified' => 1,
                    'jenislayananId' => 1,
                    'disposisiIsRejected' => 0,
                    'sCatId' => 10,
                ]
            ],
        ])->render('pages/ticketing/response');

        $this->assertStringContainsString('TK-2026-999', $html);
        $this->assertStringContainsString('Siti Rahmawati', $html);
        $this->assertStringContainsString('kt-badge--unified-info', $html);
    }

    public function testTicketingIndexRendersStatusPillsAndFilterRow(): void
    {
        $renderer = service('renderer');
        $html = $renderer->setData([
            'page_judul' => 'Daftar Tiket Permohonan Layanan',
            'create_url' => base_url('ticketing/create'),
            'show_url' => base_url('ticketing/response'),
            'tanggal' => '01-09-2026 / 30-09-2026',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'category' => [
                ['unitId' => 1, 'unitNama' => 'Biro Akademik']
            ],
            'status_layanan' => [
                ['statusId' => 1, 'statusNama' => 'Baru'],
                ['statusId' => 2, 'statusNama' => 'Disposisi'],
                ['statusId' => 3, 'statusNama' => 'Verifikasi'],
                ['statusId' => 5, 'statusNama' => 'Selesai'],
            ],
        ])->render('pages/ticketing/index');

        $this->assertStringContainsString('Semua', $html);
        $this->assertStringContainsString('Baru', $html);
        $this->assertStringContainsString('Disposisi', $html);
        $this->assertStringContainsString('Verifikasi', $html);
        $this->assertStringContainsString('Selesai', $html);
        $this->assertStringContainsString('rentangTanggal', $html);
        $this->assertStringContainsString('layanan', $html);
    }
}


