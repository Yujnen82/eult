<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder untuk membuat akun admin test sementara.
 * Gunakan HANYA untuk keperluan development/QA — hapus setelah selesai.
 *
 * Jalankan:
 *   php spark db:seed TestAdminSeeder
 *
 * Hapus akun test:
 *   php spark db:seed TestAdminSeeder drop
 */
class TestAdminSeeder extends Seeder
{
    /** Username akun test — tidak boleh tabrakan dengan akun produksi */
    private const USERNAME = 'test.impeccable';

    /** Password plaintext untuk QA — di-hash dengan password_hash() */
    private const PASSWORD = 'Impeccable@2026!';

    /** Group ADMIN agar bisa akses semua halaman admin */
    private const GROUP = 'ADMIN';

    public function run()
    {
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
        $db = \Config\Database::connect();

        // Cek apakah sudah ada
        $sudahAda = $db->table('s_user')
            ->where('susrNama', self::USERNAME)
            ->countAllResults();

        if ($sudahAda > 0) {
            CLI::write('[TestAdminSeeder] Akun test sudah ada: ' . self::USERNAME, 'yellow');

            return;
        }

        $hash = password_hash(self::PASSWORD, PASSWORD_DEFAULT);

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
            CLI::write('  Password : ' . self::PASSWORD, 'green');
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
