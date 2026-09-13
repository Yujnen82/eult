<?php

namespace App\Models;

/**
 * Model grup modul EULT (porting CI3 Model_modulgroup.php — kosong).
 */
class ModelModulgroup extends ModelMaster
{
    protected $table      = 's_user_modul_group_ref';
    protected $primaryKey = 'susrmdgroupNama';
    protected $returnType = 'array';
    protected $allowedFields = ['susrmdgroupNama', 'susrmdgroupDisplay', 'susrmdgroupIcon'];
}
