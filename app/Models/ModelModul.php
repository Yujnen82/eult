<?php

namespace App\Models;

/**
 * Model modul EULT (porting CI3 Model_modul.php).
 */
class ModelModul extends ModelMaster
{
    protected $table      = 's_user_modul_ref';
    protected $primaryKey = 'susrmodulNama';
    protected $returnType = 'array';
    protected $allowedFields = [
        'susrmodulNama', 'susrmodulNamaDisplay', 'susrmodulSusrmdgroupNama',
        'susrmodulIsLogin', 'susrmodulUrut',
    ];

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function semua(): array|false
    {
        $hasil = $this->db->table($this->table)
            ->select('*')
            ->join('s_user_modul_group_ref', 'susrmodulSusrmdgroupNama = susrmdgroupNama', 'LEFT')
            ->orderBy('susrmodulSusrmdgroupNama,susrmodulUrut,susrmodulNama')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    public function byId(array|string $kondisi): array|false
    {
        $baris = $this->db->table($this->table)
            ->select('*')
            ->join('s_user_modul_group_ref', 'susrmodulSusrmdgroupNama = susrmdgroupNama', 'LEFT')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }
}
