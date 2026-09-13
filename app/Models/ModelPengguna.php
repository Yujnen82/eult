<?php

namespace App\Models;

/**
 * Model pengguna EULT (porting CI3 Model_pengguna.php).
 */
class ModelPengguna extends ModelMaster
{
    protected $table      = 's_user';
    protected $primaryKey = 'susrNama';
    protected $returnType = 'array';
    protected $allowedFields = [
        'susrNama', 'susrPassword', 'susrSgroupNama', 'susrProfil',
        'susrPertanyaan', 'susrJawaban', 'susrAvatar', 'susrRefIndex',
        'susrLastLogin', 'susrCategoryId',
    ];

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function semua(): array|false
    {
        $hasil = $this->db->table($this->table)
            ->select('*')
            ->join('s_user_group', 'susrSgroupNama = sgroupNama', 'LEFT')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    public function byId(array|string $kondisi): array|false
    {
        $baris = $this->db->table($this->table)
            ->select('*')
            ->join('s_user_group', 'susrSgroupNama = sgroupNama', 'LEFT')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }
}
