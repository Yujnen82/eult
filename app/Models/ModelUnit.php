<?php

namespace App\Models;

/**
 * Model unit kerja EULT (porting CI3 Model_unit.php — kosong).
 */
class ModelUnit extends ModelMaster
{
    protected $table      = 's_unit';
    protected $primaryKey = 'unitId';
    protected $returnType = 'array';
    protected $allowedFields = [
        'unitKode', 'unitNama', 'unitSgroupNama', 'unitPejabatNIP',
        'unitPejabatGol', 'unitPejabatJabatan', 'unitPejabatNama', 'unitDisplay',
    ];
}
