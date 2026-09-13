<?php

namespace App\Models;

/**
 * Model hak akses unit EULT (porting CI3 Model_hakaksesunit.php).
 */
class ModelHakaksesunit extends ModelMaster
{
    protected $table      = 's_user_group_unit';
    protected $returnType = 'array';
    protected $allowedFields = ['sgroupunitSgroupNama', 'sgroupunitUnitId', 'sgroupunitUnitRead', 'sgroupunitIsHome'];

    /**
     * Matriks unit vs grup (filter opsional prefix kode unit).
     *
     * @return array<int, array<string, mixed>>|false
     */
    public function byId(string $namaGrup, string $kodePrefix = ''): array|false
    {
        $builder = $this->db->table('s_unit')
            ->select('*')
            ->like('unitKode', $kodePrefix, 'after')
            ->join($this->table, "sgroupunitUnitId = unitId AND sgroupunitSgroupNama = '" . $this->db->escapeString($namaGrup) . "'", 'LEFT')
            ->join('s_user_group', 'sgroupunitSgroupNama = sgroupNama', 'LEFT')
            ->where('unitDisplay', 1);

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
