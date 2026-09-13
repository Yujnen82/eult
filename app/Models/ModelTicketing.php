<?php

namespace App\Models;

/**
 * Model tiket utama EULT (porting CI3 Model_ticketing.php).
 * CI3 punya duplikat Model_ticketingg.php dengan nama class sama —
 * hanya SATU yang diporting (file ini). Hasil sebagai array.
 */
class ModelTicketing extends ModelMaster
{
    protected $table      = 'd_ticketing';
    protected $primaryKey = 'ticketTrackingId';
    protected $returnType = 'array';
    protected $allowedFields = [
        'ticketTrackingId', 'ticketName', 'ticketEmail', 'ticketNoHp',
        'ticketCategories', 'ticketPriority', 'ticketSubject', 'ticketMessage',
        'ticketCreated', 'ticketAssigned', 'ticketUpdated', 'ticketClosed',
        'ticketAssignedBy', 'ticketUpdatedBy', 'ticketClosedBy', 'ticketAcceptedBy',
        'ticketStatus', 'ticketArchiveId', 'ticketCustomer', 'ticketIsValidasi',
        'ticketValidated', 'ticketAccepted', 'ticketValidatedBy', 'ticketIsChange',
        'ticketAssign', 'ticketAccept', 'ticketIdentitas', 'ticketSuratCreated',
        'ticketCreatedBy', 'ticketIsVerified', 'ticketVerifiedBy', 'ticketVerified',
        'ticketSuratId', 'ticketRejected', 'ticketKetTolak', 'ticketKetSuratTolak',
        'ticketSuratTolakBy', 'ticketRejectedBy', 'ticketmValidasi',
    ];

    public function getNumber(string $awalan): array|false
    {
        $baris = $this->db->table('d_ticketing')
            ->select('ticketTrackingId')
            ->where("LEFT(ticketTrackingId,9) = '" . $this->db->escapeString($awalan) . "'", null, false)
            ->orderBy('ticketTrackingId', 'desc')
            ->get()->getRowArray();

        return $baris ?? false;
    }

    public function getSurat(array|string $kondisi): array|false
    {
        $baris = $this->db->table('d_ticketing')
            ->select('*')
            ->join('r_surat', 'ticketTrackingId=suratTrackingId', 'left')
            ->join('t_surat', 'ticketCategories=tsuratLayananId', 'left')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }

    public function getUser(array|string $kondisi): array|false
    {
        $hasil = $this->db->table('s_user')
            ->select('*')
            ->join('s_user_group', 'susrSgroupNama = sgroupNama', 'left')
            ->where($kondisi)
            ->get()->getResultArray();

        return count($hasil) === 1 ? $hasil[0] : false;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getHistory(string $tiket): array|false
    {
        $hasil = $this->db->table('d_history h')
            ->select('h.*,t.ticketName')
            ->join('d_ticketing t', 'h.ticketTrackingIdHistory = t.ticketTrackingId', 'left')
            ->where('h.ticketTrackingIdHistory', $tiket)
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getDataWorker(array|string $kondisi): array|false
    {
        $hasil = $this->db->table('s_user')
            ->select('*')
            ->join('r_category', 'categorysGroupNama = susrSgroupNama', 'left')
            ->where($kondisi)
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Pohon unit untuk dropdown disposisi (2 level).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDisposisi(): array
    {
        $induk = $this->db->table('s_unit')
            ->select('unitKode, unitNama')
            ->where("LENGTH(unitKode) = '2'", null, false)
            ->get()->getResultArray();

        $final = [];
        foreach ($induk as $v) {
            $anak = $this->db->table('s_unit')
                ->select("unitId,unitKode,unitNama, CASE WHEN LENGTH(unitKode) = 4 THEN 'danger' WHEN LENGTH(unitKode) = 6 THEN 'warning' ELSE 'success' END color", false)
                ->where("LEFT(unitKode,2) = '" . $this->db->escapeString($v['unitKode']) . "' AND LENGTH(unitKode) > 2", null, false)
                ->orderBy('unitKode,unitId', 'asc')
                ->get()->getResultArray();

            if ($anak !== []) {
                $v['children'] = $anak;
            }
            $final[] = $v;
        }

        return $final;
    }

    public function getDisposisiById(array $kondisi): array|false
    {
        $idTiket = $kondisi['ticketTrackingId'] ?? '';
        $baris   = $this->db->table('d_ticketing')
            ->select('*')
            ->join('d_disposisi', "disposisiUnit = ticketAssign AND disposisiTicketId = '" . $this->db->escapeString($idTiket) . "'", 'left')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }

    public function getTicketAssign(string $tabel, array|string $kondisi): array|false
    {
        $sub = $this->db->table($tabel)
            ->select('unitNama parentUnitNama,unitKode parentUnitKode')
            ->where('LENGTH(unitKode) = 2', null, false)
            ->getCompiledSelect();

        $baris = $this->db->newQuery()
            ->select('unitNama,parentUnitNama, unitPejabatNama,unitPejabatNIP,unitPejabatJabatan')
            ->from('s_unit a')
            ->join('(' . $sub . ') b', "a.unitKode LIKE CONCAT(LEFT(b.parentUnitKode,2),'%')", 'left')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }

    private function subDisposisiTerakhir(): string
    {
        $sub1 = $this->db->table('d_disposisi')
            ->select('disposisiTicketId mDisp,MAX(disposisiTanggal) mTgl')
            ->groupBy('disposisiTicketId')
            ->getCompiledSelect();

        return $this->db->newQuery()
            ->select('d_disposisi.*')
            ->from('(' . $sub1 . ') as d')
            ->join('d_disposisi', 'mDisp = disposisiTicketId AND mTgl = disposisiTanggal')
            ->getCompiledSelect();
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function dataById(array|string $kondisi = '', string $status = ''): array|false
    {
        $sub2 = $this->subDisposisiTerakhir();

        $builder = $this->db->table($this->table)
            ->select($this->table . ".*,db_ult.ref_jenis_layanan.*,r_priority.*,r_status.*,sunit.*,d_disposisi.*,
                    runit.unitNama runitNama,
                    ref_layanan.layananId sCatId,
                    ref_layanan.layananNama sCatNama,
                    ref_layanan.layananunitId sCatCategoryId,
                    ref_layanan.layananisbottomup sCatDisposisi,
                    CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('db_ult.ref_layanan', 'ticketCategories = layananId', 'LEFT')
            ->join('db_ult.ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('db_ult.ref_unit runit', 'layananunitId = runit.unitId', 'LEFT')
            ->join('r_priority', 'ticketPriority = priorityId', 'LEFT')
            ->join('r_status', 'ticketStatus = statusId', 'LEFT')
            ->join('s_unit sunit', 'ticketAssign = sunit.unitId', 'left')
            ->join('(' . $sub2 . ') d_disposisi', 'ticketTrackingId = disposisiTicketId', 'left')
            ->orderBy('disposisiTanggal DESC');

        if (! empty($kondisi)) {
            $builder->where($kondisi);
        }
        if ($status !== '') {
            $builder->where('statusId', $status);
        }

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function userHelper(): array|false
    {
        $hasil = $this->db->table('d_ticketing')
            ->select('ticketAssign')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getWorker(array|string $kondisi): array|false
    {
        $hasil = $this->db->table('d_worker')
            ->select('*')
            ->join('s_user', 'workerUser = susrNama', 'left')
            ->where($kondisi)
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    public function byId(array|string $kondisi): array|false
    {
        $sub2 = $this->subDisposisiTerakhir();

        $baris = $this->db->table($this->table)
            ->select($this->table . ".*,s_user.*,r_priority.*,r_status.*,a.*,d_rating.*,db_ult.ref_jenis_layanan.*,d_disposisi.*,d_archive.*,
                        runit.unitNama runitNama,
                        ref_layanan.layananId sCatId,
                        ref_layanan.layananNama sCatNama,
                        ref_layanan.layananunitId sCatCategoryId,
                        ref_layanan.layananisbottomup sCatDisposisi,
                        CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('s_user', 'ticketAssign = susrNama', 'LEFT')
            ->join('db_ult.ref_layanan', 'ticketCategories = layananId', 'LEFT')
            ->join('db_ult.ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('db_ult.ref_unit runit', 'layananunitId = runit.unitId', 'LEFT')
            ->join('r_priority', 'ticketPriority = priorityId', 'LEFT')
            ->join('r_status', 'ticketStatus = statusId', 'LEFT')
            ->join('s_unit a', 'ticketAssign = a.unitId', 'left')
            ->join('d_rating', 'ticketTrackingId=ratingTicketId', 'left')
            ->join('d_archive', 'archiveTrackingId = ticketTrackingId AND (archiveJenis="TTD" or archiveJenis="OUTPUT")', 'LEFT')
            ->join('(' . $sub2 . ') d_disposisi', 'ticketTrackingId = disposisiTicketId', 'left')
            ->where($kondisi)
            ->get()->getRowArray();

        return $baris ?? false;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function disposisiById(array|string $kondisi): array|false
    {
        $hasil = $this->db->table('d_disposisi')
            ->select("d_disposisi.*,d_ticketing.*,r_priority.*,r_status.*,s_unit.*,s_user_group_unit.*,d_archive.*,db_ult.ref_jenis_layanan.*,
                    d_unit.unitNama dunitNama,
                    runit.unitNama runitNama,
                    ref_layanan.layananId sCatId,
                    ref_layanan.layananNama sCatNama,
                    ref_layanan.layananunitId sCatCategoryId,
                    ref_layanan.layananisbottomup sCatDisposisi,
                    CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('d_ticketing', 'ticketTrackingId = disposisiTicketId', 'left')
            ->join('db_ult.ref_layanan', 'ticketCategories = layananId', 'LEFT')
            ->join('db_ult.ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('db_ult.ref_unit runit', 'layananunitId = runit.unitId', 'LEFT')
            ->join('r_priority', 'disposisiPriority = priorityId', 'LEFT')
            ->join('r_status', 'disposisiStatus = statusId', 'LEFT')
            ->join('s_unit', 'ticketAssign = s_unit.unitId', 'left')
            ->join('s_unit d_unit', 'disposisiUnit = d_unit.unitId', 'left')
            ->join('s_user_group_unit', 'sgroupunitUnitId = disposisiUnit', 'left')
            ->join('d_archive', 'disposisiArchiveId = archiveId', 'left')
            ->where($kondisi)
            ->orderBy('disposisiTanggal')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function disposisiAll(array|string $kondisi, string $status = ''): array|false
    {
        $sub2 = $this->subDisposisiTerakhir();

        $builder = $this->db->table('d_ticketing')
            ->select("d_disposisi.*,d_ticketing.*,r_priority.*,r_status.*,s_unit.*,s_user_group_unit.*,db_ult.ref_jenis_layanan.*,
                    d_unit.unitNama dunitNama,
                    runit.unitNama runitNama,
                    ref_layanan.layananId sCatId,
                    ref_layanan.layananNama sCatNama,
                    ref_layanan.layananunitId sCatCategoryId,
                    ref_layanan.layananisbottomup sCatDisposisi,
                    CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('db_ult.ref_layanan', 'ticketCategories = layananId', 'LEFT')
            ->join('db_ult.ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('db_ult.ref_unit runit', 'layananunitId = runit.unitId', 'LEFT')
            ->join('s_unit', 'ticketAssign = s_unit.unitId', 'left')
            ->join('(' . $sub2 . ') d_disposisi', 'ticketTrackingId = disposisiTicketId', 'left')
            ->join('r_priority', 'ticketPriority = priorityId', 'LEFT')
            ->join('r_status', 'ticketStatus = statusId', 'LEFT')
            ->join('s_user_group_unit', 'sgroupunitUnitId = disposisiUnit', 'left')
            ->join('s_unit d_unit', 'disposisiUnit = d_unit.unitId', 'left')
            ->where($kondisi)
            ->orderBy('disposisiTanggal DESC');

        if ($status !== '') {
            $builder->where('statusId', $status);
        }

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    public function disposisiLast(array|string $kondisi): array|false
    {
        $sub = $this->db->table('d_disposisi')
            ->select('disposisiTicketId mDisp,MAX(disposisiTanggal) mTgl')
            ->where($kondisi)
            ->groupBy('disposisiTicketId')
            ->getCompiledSelect();

        $baris = $this->db->newQuery()
            ->select('d_disposisi.*')
            ->from('(' . $sub . ') as d')
            ->join('d_disposisi', 'mDisp = disposisiTicketId AND mTgl = disposisiTanggal')
            ->get()->getRowArray();

        return $baris ?? false;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getReplies(string $tabel, array|string $kondisi): array|false
    {
        $hasil = $this->db->table($tabel)
            ->select('*')
            ->where($kondisi)
            ->orderBy('repliesDate', 'DESC')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getUnitByHakakses(string $hakakses): array|false
    {
        $hasil = $this->db->table('s_user_group_unit')
            ->select('child.*,s_user_group_unit.*,parent.unitNama AS parentNama')
            ->join('s_unit child', 'sgroupunitUnitId = child.unitId', 'LEFT')
            ->join('s_unit parent', 'LEFT(child.unitKode,2) = parent.unitKode', 'LEFT')
            ->where('sgroupunitSgroupNama', $hakakses)
            ->orderBy('unitKode')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * @return array<int, array<string, mixed>>|false
     */
    public function getPejabatTtd(string $id): array|false
    {
        $hasil = $this->db->table('s_unit')
            ->select('unitPejabatNIP,unitPejabatJabatan,unitPejabatNama')
            ->join('s_user_group_unit', 'sgroupunitUnitId=unitId', 'LEFT')
            ->where('sgroupunitSgroupNama', $id)
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }
}
