<?php

namespace App\Models;

/**
 * Model dashboard EULT (porting CI3 Model_home.php).
 */
class ModelHome extends ModelMaster
{
    /**
     * Daftar grup yang dimiliki pengguna (untuk switch hak akses).
     *
     * @return array<int, array<string, mixed>>|false
     */
    public function byId(string $namaPengguna): array|false
    {
        $hasil = $this->db->table('s_user_group_user')
            ->select('*')
            ->join('s_user_group', 'sgroupNama = sgroupSgroupNama', 'LEFT')
            ->where('sgroupSusrNama', $namaPengguna)
            ->orderBy('sgroupSgroupNama')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
