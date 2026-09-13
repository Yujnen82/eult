<?php

namespace App\Models;

/**
 * Model grup hak akses EULT (porting CI3 Model_hakakses.php — kosong).
 */
class ModelHakakses extends ModelMaster
{
    protected $table      = 's_user_group';
    protected $primaryKey = 'sgroupNama';
    protected $returnType = 'array';
    protected $allowedFields = ['sgroupNama', 'sgroupKeterangan', 'sgroupUnit', 'sgroupCategoryId', 'sgroupUrut'];
}
