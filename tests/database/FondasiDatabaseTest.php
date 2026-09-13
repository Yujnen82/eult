<?php

use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\ExampleSeeder;

/**
 * Test fondasi database EULT: koneksi default + dbult + tabel inti ada.
 */
final class FondasiDatabaseTest extends \CodeIgniter\Test\CIUnitTestCase
{
    public function testKoneksiDefaultDanDbult(): void
    {
        $default = \Config\Database::connect('default');
        $default->initialize();
        $this->assertNotFalse($default->connID);

        $dbult = \Config\Database::connect('dbult');
        $dbult->initialize();
        $this->assertNotFalse($dbult->connID);
    }

    public function testTabelIntiAda(): void
    {
        $db = \Config\Database::connect('default');

        foreach (['d_ticketing', 's_user', 's_user_group', 's_user_group_modul', 's_user_modul_ref'] as $tabel) {
            $this->assertTrue($db->tableExists($tabel), "Tabel {$tabel} harus ada");
        }

        $this->assertTrue(\Config\Database::connect('dbult')->tableExists('ref_layanan'), 'Tabel ref_layanan harus ada di db_ult');
    }

    public function testModelMasterMenuDanLayanan(): void
    {
        $model = new \App\Models\ModelMaster();

        $menu = $model->getMenuByGroup('ADMIN');
        $this->assertTrue($menu === false || is_array($menu));

        $layanan = $model->getLayanan();
        $this->assertTrue($layanan === false || is_array($layanan));
    }
}
