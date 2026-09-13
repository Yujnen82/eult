<?php

namespace App\Models;

/**
 * Model keanggotaan grup pengguna EULT (porting CI3 Model_hakaksespengguna.php).
 */
class ModelHakaksespengguna extends ModelMaster
{
    protected $table      = 's_user_group_user';
    protected $returnType = 'array';
    protected $allowedFields = ['sgroupSusrNama', 'sgroupSgroupNama'];

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function byId(string $namaPengguna): array|false
    {
        $hasil = $this->db->table('s_user_group')
            ->select('*')
            ->join($this->table, "sgroupNama = sgroupSgroupNama AND sgroupSusrNama='" . $this->db->escapeString($namaPengguna) . "'", 'LEFT')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
