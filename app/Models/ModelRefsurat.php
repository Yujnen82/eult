<?php

namespace App\Models;

/**
 * Model referensi surat EULT (porting CI3 Model_refsurat.php — kosong).
 */
class ModelRefsurat extends ModelMaster
{
    protected $table      = 'r_surat';
    protected $primaryKey = 'suratId';
    protected $returnType = 'array';
    protected $allowedFields = [
        'suratNama', 'suratHeader', 'suratBody', 'suratFooter', 'suratNomor',
        'suratPerihal', 'suratLampiran', 'suratTujuan', 'suratJenis',
        'suratTrackingId', 'suratPejabatNama', 'suratPejabatNIP',
        'suratPejabatJabatan', 'suratNomorTanggal', 'suratPejabatNIPDraft',
        'suratPejabatJabatanDraft', 'suratPejabatNamaDraft',
        'suratPejabatJabatanAnDraft', 'suratTanggal', 'SuratNomorPemohon',
        'suratTanggalPemohon', 'suratBank',
    ];
}
