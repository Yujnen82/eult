<?php

namespace App\Models;

/**
 * Model kategori layanan EULT (porting CI3 Model_refkategori.php).
 */
class ModelRefkategori extends ModelMaster
{
    protected $table      = 'r_category';
    protected $primaryKey = 'categoryId';
    protected $returnType = 'array';
    protected $allowedFields = ['categoryNama'];

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function semua(): array|false
    {
        return $this->tabelRef($this->table);
    }

    public function byId(array|string $kondisi): array|false
    {
        return $this->ambilSatu($this->table, $kondisi);
    }
}
