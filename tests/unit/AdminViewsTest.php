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

    public function testTicketDetailWorkstationRendersApplicantAndDocumentPanels(): void
    {
        $renderer = service('renderer');
        $renderer->resetData();
        $html = $renderer->setData([
            'page_judul' => 'Rincian Permohonan Tiket',
            'ticket' => (object)[
                'ticketTrackingId' => 'TK-2026-777',
                'ticketName' => 'Ahmad Fauzi',
                'ticketEmail' => 'ahmad@unmul.ac.id',
                'ticketPhone' => '08123456789',
                'ticketAddress' => 'Samarinda',
                'ticketCreated' => '2026-09-13 09:00:00',
                'ticketSubject' => 'Permohonan Surat Keterangan Pengganti Ijazah',
                'ticketStatus' => 3,
                'statusNama' => 'Dalam Proses',
                'statusColor' => 'info',
                'categoryNama' => 'Layanan Kemahasiswaan',
                'sCatNama' => 'Surat Keterangan Pengganti Ijazah Rusak/Hilang',
                'unitNama' => 'Biro Akademik',
            ],
            'files' => [],
            'history' => [],
            'replies' => [],
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'key' => 'test-key',
        ])->render('pages/ticketing/detail');

        $this->assertStringContainsString('Data Pemohon', $html);
        $this->assertStringContainsString('Dokumen Persyaratan', $html);
        $this->assertStringContainsString('TK-2026-777', $html);
    }

    public function testTicketDetailWorkstationRendersWithLegacyDatasArray(): void
    {
        $renderer = service('renderer');
        $renderer->resetData();
        $html = $renderer->setData([
            'page_judul' => 'Rincian Permohonan Tiket',
            'datas' => [
                'ticketTrackingId' => 'TK-2026-888',
                'ticketName' => 'Dewi Lestari',
                'ticketEmail' => 'dewi@unmul.ac.id',
                'ticketNoHp' => '08129876543',
                'ticketAddress' => 'Samarinda',
                'ticketCreated' => '2026-09-13 11:00:00',
                'ticketSubject' => 'Permohonan Legalisir',
                'ticketStatus' => 2,
                'statusNama' => 'Disposisi',
                'statusColor' => 'warning',
                'categoryNama' => 'Layanan Akademik',
                'sCatNama' => 'Legalisir Ijazah',
                'unitNama' => 'Biro Akademik',
            ],
            'archive_url' => 'http://example.com/ticketing/loadpdf/dokumen.pdf',
            'output_url' => false,
            'history' => [
                (object)[
                    'tglHistory' => '2026-09-13 11:30:00',
                    'detailHistory' => 'Tiket didisposisikan ke Biro Akademik',
                ]
            ],
            'replies' => [
                (object)[
                    'repliesStatus' => 'USER',
                    'repliesDate' => '2026-09-13 11:45:00',
                    'repliesBy' => 'Dewi Lestari',
                    'repliesMessage' => 'Mohon bantuan proses legalisir berkas ijazah saya.',
                    'repliesFile' => '',
                ]
            ],
            'user_group' => ['susrSgroupNama' => 'ADMIN', 'susrProfil' => 'Admin Layanan'],
            'worker' => [],
            'kunci' => 'kunci-tes',
            'mhs' => 'Dewi Lestari - 1801015001 - FKIP',
        ])->render('pages/ticketing/detail');

        $this->assertStringContainsString('Data Pemohon', $html);
        $this->assertStringContainsString('Dokumen Persyaratan', $html);
        $this->assertStringContainsString('TK-2026-888', $html);
        $this->assertStringContainsString('Dewi Lestari', $html);
        $this->assertStringContainsString('Linimasa Disposisi & Riwayat Status', $html);
        $this->assertStringContainsString('Tanggapan & Aksi Tiket', $html);
        $this->assertStringContainsString('dokumen.pdf', $html);
    }

    public function testTicketDetailWorkstationResolvesEncKeyAndAjaxHandlers(): void
    {
        $renderer = service('renderer');
        $renderer->resetData();
        $html = $renderer->setData([
            'page_judul' => 'Rincian Permohonan Tiket',
            // Tanpa 'key' atau 'kunci', menyimulasikan pemanggilan asli dari Ticketing::detail($kunci)
            'datas' => [
                'ticketTrackingId' => 'TK-2026-555',
                'ticketName' => 'Bambang Sudarsono',
                'ticketEmail' => 'bambang@unmul.ac.id',
                'ticketNoHp' => '081122334455',
                'ticketAddress' => 'Samarinda Ulu',
                'ticketCreated' => '2026-09-13 12:00:00',
                'ticketSubject' => 'Permohonan Validasi',
                'ticketStatus' => 1,
                'statusNama' => 'Baru',
                'statusColor' => 'primary',
                'categoryNama' => 'Layanan Umum',
                'sCatNama' => 'Validasi Berkas',
                'unitNama' => 'Biro Umum',
            ],
            'close_url' => 'http://example.com/ticketing/close/KEY-FROM-CLOSE-URL',
            'cetakterima' => 'http://example.com/ticketing/cetakterima/KEY-FROM-CLOSE-URL',
            'user_group' => ['susrSgroupNama' => 'ADMIN', 'susrProfil' => 'Administrator'],
        ])->render('pages/ticketing/detail');

        // Verifikasi $encKey otomatis teresolusi dari $close_url
        $this->assertStringContainsString('KEY-FROM-CLOSE-URL', $html);
        $this->assertStringContainsString('ticketing/terima/KEY-FROM-CLOSE-URL', $html);
        $this->assertStringContainsString('ticketing/assign/KEY-FROM-CLOSE-URL', $html);

        // Verifikasi keberadaan kelas AJAX dan modal aksi dinamis
        $this->assertStringContainsString('btn-ajax-terima', $html);
        $this->assertStringContainsString('btn-ajax-modal', $html);
        $this->assertStringContainsString('modal-action-dialog', $html);
    }

    public function testValidasifileViewRendersSecurityCardsAndManifestTable(): void
    {
        $renderer = service('renderer');
        $renderer->resetData();
        $html = $renderer->setData([
            'page_judul' => 'Validasi & Keamanan Berkas Digital',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
        ])->render('pages/validasifile/index');

        $this->assertStringContainsString('Integritas Berkas', $html);
        $this->assertStringContainsString('Berkas Aman', $html);
        $this->assertStringContainsString('Berkas Dikarantina', $html);
    }

    public function testMasterDataViewsUseConsistentPortletStructure(): void
    {
        $renderer = service('renderer');

        // 1. Refkategori Index
        $renderer->resetData();
        $htmlKategori = $renderer->setData([
            'page_judul' => 'Master Kategori Layanan',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('refkategori/create'),
            'show_url' => base_url('refkategori/show'),
            'edit_url' => base_url('refkategori/edit'),
            's_user_group' => [],
            'datas' => [],
        ])->render('pages/refkategori/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlKategori);
        $this->assertStringContainsString('Tambah Data', $htmlKategori);
        $this->assertStringContainsString('btn-brand', $htmlKategori);
        $this->assertStringContainsString('flaticon2-plus', $htmlKategori);

        // 2. Refsurat Index
        $renderer->resetData();
        $htmlSurat = $renderer->setData([
            'page_judul' => 'Master Format Surat',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('refsurat/create'),
            'update_url' => base_url('refsurat/edit/'),
            'delete_url' => base_url('refsurat/delete/'),
            'datas' => [],
        ])->render('pages/refsurat/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlSurat);
        $this->assertStringContainsString('Tambah Data', $htmlSurat);
        $this->assertStringContainsString('btn-brand', $htmlSurat);
        $this->assertStringContainsString('thead-light', $htmlSurat);
        $this->assertStringContainsString('text-uppercase text-muted font-weight-bold', $htmlSurat);
        $this->assertStringContainsString('Belum ada data yang tersedia', $htmlSurat);

        // 3. Refsyarat Index
        $renderer->resetData();
        $htmlSyarat = $renderer->setData([
            'page_judul' => 'Master Persyaratan Layanan',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('refsyarat/create'),
            'update_url' => base_url('refsyarat/edit/'),
            'delete_url' => base_url('refsyarat/delete/'),
            'datas' => [],
        ])->render('pages/refsyarat/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlSyarat);
        $this->assertStringContainsString('Tambah Data', $htmlSyarat);
        $this->assertStringContainsString('thead-light', $htmlSyarat);
        $this->assertStringContainsString('Belum ada data yang tersedia', $htmlSyarat);

        // 4. Unit Index
        $renderer->resetData();
        $htmlUnit = $renderer->setData([
            'page_judul' => 'Master Unit Kerja',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('unit/create'),
            'update_url' => base_url('unit/edit/'),
            'delete_url' => base_url('unit/delete/'),
            'datas' => [],
        ])->render('pages/unit/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlUnit);
        $this->assertStringContainsString('Tambah Data', $htmlUnit);
        $this->assertStringContainsString('thead-light', $htmlUnit);
        $this->assertStringContainsString('Belum ada data yang tersedia', $htmlUnit);

        // 5. Pengguna Index
        $renderer->resetData();
        $htmlPengguna = $renderer->setData([
            'page_judul' => 'Manajemen Pengguna',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('pengguna/create'),
            'update_url' => base_url('pengguna/edit/'),
            'delete_url' => base_url('pengguna/delete/'),
            'resetpassword_url' => base_url('pengguna/resetpassword/'),
            'datas' => [],
        ])->render('pages/pengguna/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlPengguna);
        $this->assertStringContainsString('Tambah Data', $htmlPengguna);
        $this->assertStringContainsString('thead-light', $htmlPengguna);
        $this->assertStringContainsString('Belum ada data yang tersedia', $htmlPengguna);

        // 6. Hakakses Index
        $renderer->resetData();
        $htmlHakakses = $renderer->setData([
            'page_judul' => 'Manajemen Hak Akses',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('hakakses/create'),
            'update_url' => base_url('hakakses/edit/'),
            'delete_url' => base_url('hakakses/delete/'),
            'datas' => [],
        ])->render('pages/hakakses/index');

        $this->assertStringContainsString('kt-portlet__head-title', $htmlHakakses);
        $this->assertStringContainsString('Tambah Data', $htmlHakakses);
        $this->assertStringContainsString('thead-light', $htmlHakakses);
        $this->assertStringContainsString('Belum ada data yang tersedia', $htmlHakakses);
    }

    public function testMasterDataViewsRenderStandardActionButtonsWithData(): void
    {
        $renderer = service('renderer');

        // Test Unit with data
        $renderer->resetData();
        $htmlUnit = $renderer->setData([
            'page_judul' => 'Master Unit Kerja',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('unit/create'),
            'update_url' => base_url('unit/edit/'),
            'delete_url' => base_url('unit/delete/'),
            'datas' => [
                ['unitId' => 1, 'unitKode' => 'FT', 'unitNama' => 'Fakultas Teknik']
            ],
        ])->render('pages/unit/index');

        $this->assertStringContainsString('btn-label-brand', $htmlUnit);
        $this->assertStringContainsString('btn-label-danger', $htmlUnit);
        $this->assertStringContainsString('ts_remove_row', $htmlUnit);
        $this->assertStringContainsString('Ubah', $htmlUnit);
        $this->assertStringContainsString('Hapus', $htmlUnit);

        // Test Pengguna with data
        $renderer->resetData();
        $htmlPengguna = $renderer->setData([
            'page_judul' => 'Manajemen Pengguna',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('pengguna/create'),
            'update_url' => base_url('pengguna/edit/'),
            'delete_url' => base_url('pengguna/delete/'),
            'resetpassword_url' => base_url('pengguna/resetpassword/'),
            'datas' => [
                [
                    'susrNama' => 'admin_test',
                    'susrSgroupNama' => 'ADMIN',
                    'susrProfil' => 'Admin Utama',
                    'susrLastLogin' => '2026-09-13 10:00:00',
                ]
            ],
        ])->render('pages/pengguna/index');

        $this->assertStringContainsString('btn-label-brand', $htmlPengguna);
        $this->assertStringContainsString('btn-label-danger', $htmlPengguna);
        $this->assertStringContainsString('btn-label-warning', $htmlPengguna);
        $this->assertStringContainsString('ts_remove_row', $htmlPengguna);
        $this->assertStringContainsString('ts_reset_row', $htmlPengguna);
    }

    /**
     * Memastikan modulgroup index dan form menggunakan standar portlet, tombol, dan lokalisasi terpadu.
     */
    public function testModulgroupViewsUseConsistentPortletAndTableStructure(): void
    {
        $renderer = \Config\Services::renderer();

        // 1. Modulgroup Index dengan Data
        $renderer->resetData();
        $htmlIndex = $renderer->setData([
            'page_judul' => 'Modul Group',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('modulgroup/create/'),
            'update_url' => base_url('modulgroup/update/'),
            'delete_url' => base_url('modulgroup/delete/'),
            'datas' => [
                [
                    'susrmdgroupNama' => 'admin',
                    'susrmdgroupDisplay' => 'Administrator',
                    'susrmdgroupIcon' => '<i class="la la-desktop"></i>',
                ]
            ],
        ])->render('pages/modulgroup/index');

        $this->assertStringContainsString('kt-portlet', $htmlIndex);
        $this->assertStringContainsString('btn-brand', $htmlIndex);
        $this->assertStringContainsString('Tambah Data', $htmlIndex);
        $this->assertStringContainsString('thead-light', $htmlIndex);
        $this->assertStringContainsString('btn-label-brand', $htmlIndex);
        $this->assertStringContainsString('btn-label-danger', $htmlIndex);
        $this->assertStringContainsString('ts_remove_row', $htmlIndex);
        $this->assertStringContainsString('Ubah', $htmlIndex);
        $this->assertStringContainsString('Hapus', $htmlIndex);

        // 2. Modulgroup Index saat Data Kosong
        $renderer->resetData();
        $htmlEmpty = $renderer->setData([
            'page_judul' => 'Modul Group',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'create_url' => base_url('modulgroup/create/'),
            'update_url' => base_url('modulgroup/update/'),
            'delete_url' => base_url('modulgroup/delete/'),
            'datas' => false,
        ])->render('pages/modulgroup/index');

        $this->assertStringContainsString('Belum ada data', $htmlEmpty);

        // 3. Modulgroup Form
        $renderer->resetData();
        $htmlForm = $renderer->setData([
            'page_judul' => 'Modul Group',
            'user_group' => ['susrSgroupNama' => 'ADMIN'],
            'save_url' => base_url('modulgroup/save/'),
            'status_page' => 'Create',
            'datas' => false,
        ])->render('pages/modulgroup/form');

        $this->assertStringContainsString('Simpan', $htmlForm);
        $this->assertStringContainsString('Batal', $htmlForm);
        $this->assertStringContainsString('btn-brand', $htmlForm);
    }
}



