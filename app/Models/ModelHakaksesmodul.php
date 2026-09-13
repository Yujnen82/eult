<?php

namespace App\Models;

/**
 * Model hak akses modul EULT (porting CI3 Model_hakaksesmodul.php).
 */
class ModelHakaksesmodul extends ModelMaster
{
    protected $table      = 's_user_group_modul';
    protected $returnType = 'array';
    protected $allowedFields = ['sgroupmodulSgroupNama', 'sgroupmodulSusrmodulNama', 'sgroupmodulSusrmodulRead'];

    /**
     * Matriks modul vs grup (untuk form centang hak akses).
     *
     * @return array<int, array<string, mixed>>|false
     */
    public function byId(string $namaGrup): array|false
    {
        $hasil = $this->db->table('s_user_modul_ref')
            ->select('*')
            ->join($this->table, "sgroupmodulSusrmodulNama = susrmodulNama AND sgroupmodulSgroupNama='" . $this->db->escapeString($namaGrup) . "'", 'LEFT')
            ->join('s_user_group', 'sgroupmodulSgroupNama = sgroupNama', 'LEFT')
            ->join('s_user_modul_group_ref', 'susrmdgroupNama = susrmodulSusrmdgroupNama', 'LEFT')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
