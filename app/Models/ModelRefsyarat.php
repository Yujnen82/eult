<?php

namespace App\Models;

/**
 * Model syarat berkas layanan EULT (porting CI3 Model_refsyarat.php).
 */
class ModelRefsyarat extends ModelMaster
{
    protected $table      = 'r_berkas_layanan';
    protected $primaryKey = 'berkasId';
    protected $returnType = 'array';
    protected $allowedFields = ['berkasidLayanan', 'berkasNama', 'berkasKeterangan'];

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function semua(): array|false
    {
        $hasil = $this->db->table($this->table)
            ->select('*')
            ->join('db_ult.ref_layanan', 'berkasidLayanan = layananId', 'LEFT')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    public function byId(array|string $kondisi): array|false
    {
        $baris = $this->db->table($this->table)
            ->select('*')
            ->join('db_ult.ref_layanan', 'berkasidLayanan = layananId', 'LEFT')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }
}
