<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Seeder untuk membuat akun admin test sementara.
 * Gunakan HANYA untuk keperluan development/QA — hapus setelah selesai.
 *
 * Password diambil dari environment variable EULT_TEST_ADMIN_PASSWORD
 * (minimal 12 karakter) sehingga tidak ada kredensial yang ter-commit.
 * Seeder ini menolak berjalan pada ENVIRONMENT=production.
 *
 * Jalankan:
 *   EULT_TEST_ADMIN_PASSWORD='<rahasia>' php spark db:seed TestAdminSeeder
 *
 * Hapus akun test:
 *   php spark db:seed TestAdminSeeder drop
 */
class TestAdminSeeder extends Seeder
{
    /** Username akun test — tidak boleh tabrakan dengan akun produksi */
    private const USERNAME = 'test.impeccable';

    /** Group ADMIN agar bisa akses semua halaman admin */
    private const GROUP = 'ADMIN';

    public function run()
    {
        if (ENVIRONMENT === 'production') {
            CLI::error('[TestAdminSeeder] Tidak boleh dijalankan pada environment production.');

            return;
        }

        // Cek apakah argumen drop dikirim via argv
        $isDrop = in_array('drop', $_SERVER['argv'] ?? [], true);

        if ($isDrop) {
            $this->hapusAkunTest();

            return;
        }

        $this->buatAkunTest();
    }

    private function buatAkunTest(): void
    {
        $sandi = (string) (getenv('EULT_TEST_ADMIN_PASSWORD') ?: env('EULT_TEST_ADMIN_PASSWORD', ''));

        if (strlen($sandi) < 12) {
            CLI::error('[TestAdminSeeder] Set EULT_TEST_ADMIN_PASSWORD (min. 12 karakter) sebelum menjalankan seeder.');

            return;
        }

        $db = \Config\Database::connect();

        // Cek apakah sudah ada
        $sudahAda = $db->table('s_user')
            ->where('susrNama', self::USERNAME)
            ->countAllResults();

        if ($sudahAda > 0) {
            CLI::write('[TestAdminSeeder] Akun test sudah ada: ' . self::USERNAME, 'yellow');

            return;
        }

        $hash = password_hash($sandi, PASSWORD_DEFAULT);

        $berhasil = $db->table('s_user')->insert([
            'susrNama'        => self::USERNAME,
            'susrPassword'    => $hash,
            'susrSgroupNama'  => self::GROUP,
            'susrProfil'      => 'TEST',
            'susrCategoryId'  => null,
            'susrLastLogin'   => null,
        ]);

        if ($berhasil) {
            CLI::write('[TestAdminSeeder] ✅ Akun test berhasil dibuat:', 'green');
            CLI::write('  Username : ' . self::USERNAME, 'green');
            CLI::write('  Password : (sesuai EULT_TEST_ADMIN_PASSWORD)', 'green');
            CLI::write('  Group    : ' . self::GROUP, 'green');
            CLI::write('[TestAdminSeeder] ⚠️  HAPUS akun ini setelah QA selesai!', 'red');
        } else {
            CLI::write('[TestAdminSeeder] ❌ Gagal membuat akun test.', 'red');
        }
    }

    private function hapusAkunTest(): void
    {
        $db = \Config\Database::connect();

        $db->table('s_user')
            ->where('susrNama', self::USERNAME)
            ->delete();

        CLI::write('[TestAdminSeeder] 🗑️  Akun test berhasil dihapus: ' . self::USERNAME, 'green');
    }
}
