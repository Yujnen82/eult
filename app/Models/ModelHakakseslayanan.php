<?php

namespace App\Models;

/**
 * Model hak akses layanan EULT (porting CI3 Model_hakakseslayanan.php).
 */
class ModelHakakseslayanan extends ModelMaster
{
    protected $table      = 's_group_category';
    protected $returnType = 'array';

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function byId(string $namaGrup): array|false
    {
        $hasil = $this->db->table('r_category_sub')
            ->select('*')
            ->join($this->table, "sCatId=sgroupSubCategoryId AND sgroupSgroupId='" . $this->db->escapeString($namaGrup) . "'", 'LEFT')
            ->join('r_category', 'categoryId = sCatCategoryId', 'LEFT')
            ->join('s_user_group', 'sGroupNama = sgroupSgroupId', 'LEFT')
            ->orderBy('categoryId,sCatId', 'asc')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
