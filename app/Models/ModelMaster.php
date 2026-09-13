<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Model dasar EULT (pengganti CI3 application/core/Model_Master.php).
 *
 * Semua model EULT extends class ini agar mendapat helper CRUD generik
 * (ambilSatu/ambilTerakhir/tabelRef/tambah/ganti/ubah/hapus) plus
 * query menu, otorisasi modul, layanan, dan notifikasi — setara
 * Model_Master CI3. Hasil dikembalikan sebagai array (pengganti
 * row()/result() CI3) kecuali dinyatakan lain.
 */
class ModelMaster extends \CodeIgniter\Model
{
    protected $DBGroup = 'default';

    // Tabel generik: properti $table/$primaryKey diisi tiap model turunan.
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected bool $allowEmptyInserts = true;

    /**
     * Koneksi kedua ke db_ult (ref_layanan/jenis/unit).
     */
    protected function dbUlt(): BaseConnection
    {
        return \Config\Database::connect('dbult');
    }

    /**
     * Ambil satu baris (setara get_by_id CI3).
     */
    public function ambilSatu(string $tabel, array|string $kondisi): array|false
    {
        $baris = $this->db->table($tabel)->where($kondisi)->get()->getRowArray();

        return $baris ?? false;
    }

    /**
     * Ambil nilai MAX kolom (setara get_by_last_id CI3).
     */
    public function getByLastId(string $tabel, string $kolom, array|string $kondisi = []): array|false
    {
        $builder = $this->db->table($tabel)->selectMax($kolom, $kolom);
        if (! empty($kondisi)) {
            $builder->where($kondisi);
        }

        $baris = $builder->get()->getRowArray();

        return $baris ?? false;
    }

    /**
     * Ambil tabel referensi (setara get_ref_table CI3).
     */
    public function tabelRef(string $tabel, array|string $kondisi = [], string $urut = ''): array|false
    {
        $builder = $this->db->table($tabel);
        if (! empty($kondisi)) {
            $builder->where($kondisi);
        }
        if ($urut !== '') {
            $builder->orderBy($urut);
        }

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Insert satu baris dalam transaksi (setara insert CI3).
     */
    public function tambah(string $tabel, array $param): bool
    {
        $this->db->transStart();
        $this->db->table($tabel)->insert($param);
        log_message('debug', 'EULT insert {tabel}: {query}', ['tabel' => $tabel, 'query' => $this->db->getLastQuery()]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Insert batch dalam transaksi (setara insert_batch CI3).
     */
    public function tambahBatch(string $tabel, array $param): bool
    {
        $this->db->transStart();
        $this->db->table($tabel)->insertBatch($param);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Replace satu baris dalam transaksi (setara replace CI3).
     */
    public function ganti(string $tabel, array $param): bool
    {
        $this->db->transStart();
        $this->db->table($tabel)->replace($param);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Update dalam transaksi (setara update CI3).
     */
    public function ubah(string $tabel, array $param, array|string $kondisi): bool
    {
        $this->db->transStart();
        $this->db->table($tabel)->where($kondisi)->update($param);
        log_message('debug', 'EULT update {tabel}: {query}', ['tabel' => $tabel, 'query' => $this->db->getLastQuery()]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Update batch dalam transaksi (setara update_batch CI3).
     */
    public function ubahBatch(string $tabel, array $param, string $kolomKunci): bool
    {
        $this->db->transStart();
        $this->db->table($tabel)->updateBatch($param, $kolomKunci);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Hapus baris dalam transaksi (setara delete CI3).
     */
    public function hapus(string $tabel, array|string $kondisi): bool
    {
        try {
            $this->db->transStart();
            $this->db->table($tabel)->where($kondisi)->delete();
            $this->db->transComplete();

            return $this->db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'EULT hapus gagal {tabel}: {pesan}', ['tabel' => $tabel, 'pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Kosongkan tabel dalam transaksi (setara delete_truncate CI3).
     */
    public function hapusTruncate(string $tabel): bool
    {
        try {
            $this->db->transStart();
            $this->db->table($tabel)->truncate();
            $this->db->transComplete();

            return $this->db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'EULT truncate gagal {tabel}: {pesan}', ['tabel' => $tabel, 'pesan' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Menu sidebar per grup pengguna.
     */
    public function getMenuByGroup(string $namaGrup): array|false
    {
        $hasil = $this->db->table('s_user_group_modul')
            ->select('susrmodulNama,susrmodulNamaDisplay,susrmdgroupDisplay,susrmdgroupIcon')
            ->join('s_user_modul_ref', 'sgroupmodulSusrmodulNama=susrmodulNama', 'left')
            ->join('s_user_modul_group_ref', 'susrmodulSusrmdgroupNama=susrmdgroupNama', 'left')
            ->where('sgroupmodulSgroupNama', $namaGrup)
            ->where('sgroupmodulSusrmodulRead', '1')
            ->where('susrmodulSusrmdgroupNama IS NOT NULL', null, false)
            ->orderBy('susrmdgroupDisplay')
            ->orderBy('susrmodulUrut')
            ->orderBy('susrmodulNamaDisplay')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Menu publik tanpa login.
     */
    public function getMenuNonLogin(): array|false
    {
        $hasil = $this->db->table('s_user_modul_ref')
            ->select('susrmodulNama,susrmodulNamaDisplay')
            ->where('susrmodulIsLogin', '0')
            ->orderBy('susrmodulUrut')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Cek hak baca modul per grup (dipakai AuthFilter + BaseController).
     */
    public function otentikasiMenu(string $namaGrup, string $namaModul): array|false
    {
        $hasil = $this->db->table('s_user_group_modul')
            ->select('susrmodulNamaDisplay,susrmdgroupDisplay')
            ->join('s_user_modul_ref', 'sgroupmodulSusrmodulNama=susrmodulNama', 'left')
            ->join('s_user_modul_group_ref', 'susrmodulSusrmdgroupNama=susrmdgroupNama', 'left')
            ->where('sgroupmodulSgroupNama', $namaGrup)
            ->where('sgroupmodulSusrmodulRead', '1')
            ->where('sgroupmodulSusrmodulNama', $namaModul)
            ->limit(1)
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Daftar layanan aktif dari db_ult (join ref_layanan/jenis/unit).
     */
    public function getLayanan(string $khusus = ''): array|false
    {
        $db = $this->dbUlt();

        $builder = $db->table('ref_layanan')
            ->select('jenislayananNama,layananId,layananNama,unitNama')
            ->join('ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('ref_unit', 'layananunitId = unitId', 'LEFT')
            ->where('layananisdisplay', 1)
            ->orderBy('jenislayananId')
            ->orderBy('unitUrut')
            ->orderBy('layananId');

        if (! empty($khusus)) {
            $builder->where("(jenislayananId = '3' OR layananId in('9','12','32','45','78','88','110','27','28','29','146','150','168'))", null, false);
        }

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Notifikasi tiket untuk ADMIN/OPERATOR (replies belum dibaca).
     */
    public function dataNotif(array|string $kondisi = ''): array|false
    {
        $sub1 = $this->db->table('d_disposisi')
            ->select('disposisiTicketId mDisp,MAX(disposisiTanggal) mTgl')
            ->groupBy('disposisiTicketId')
            ->getCompiledSelect();

        $sub2 = $this->db->newQuery()
            ->select('d_disposisi.*')
            ->from('(' . $sub1 . ') as d')
            ->join('d_disposisi', 'mDisp = disposisiTicketId AND mTgl = disposisiTanggal')
            ->getCompiledSelect();

        $builder = $this->db->table('d_ticketing')
            ->select("d_ticketing.*,db_ult.ref_jenis_layanan.*,r_priority.*,r_status.*,sunit.*,d_disposisi.*,d_archive.*,
                    runit.unitNama runitNama,
                    ref_layanan.layananId sCatId,
                    ref_layanan.layananNama sCatNama,
                    ref_layanan.layananunitId sCatCategoryId,
                    ref_layanan.layananisbottomup sCatDisposisi,
                    repliesTicketId,
                    COUNT(repliesTicketId) as jumlah,
                    CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('d_replies', 'ticketTrackingId = repliesTicketId', 'LEFT')
            ->join('db_ult.ref_layanan', 'ticketCategories = layananId', 'LEFT')
            ->join('db_ult.ref_jenis_layanan', 'layananjenisId = jenislayananId', 'LEFT')
            ->join('db_ult.ref_unit runit', 'layananunitId = runit.unitId', 'LEFT')
            ->join('r_priority', 'ticketPriority = priorityId', 'LEFT')
            ->join('r_status', 'ticketStatus = statusId', 'LEFT')
            ->join('d_archive', 'archiveTrackingId = ticketTrackingId', 'LEFT')
            ->join('s_unit sunit', 'ticketAssign = sunit.unitId', 'left')
            ->join('(' . $sub2 . ') d_disposisi', 'ticketTrackingId = disposisiTicketId', 'left')
            ->groupBy('repliesTicketId')
            ->orderBy('disposisiTanggal DESC');

        if (! empty($kondisi)) {
            $builder->where($kondisi);
        }

        $hasil = $builder->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Notifikasi disposisi per unit (untuk role unit/non-admin).
     */
    public function disposisiNotif(array|string $kondisi): array|false
    {
        $sub1 = $this->db->table('d_disposisi')
            ->select('disposisiTicketId mDisp,MAX(disposisiTanggal) mTgl')
            ->groupBy('disposisiTicketId')
            ->getCompiledSelect();

        $sub2 = $this->db->newQuery()
            ->select('d_disposisi.*')
            ->from('(' . $sub1 . ') as d')
            ->join('d_disposisi', 'mDisp = disposisiTicketId AND mTgl = disposisiTanggal')
            ->getCompiledSelect();

        $hasil = $this->db->table('d_ticketing')
            ->select("d_disposisi.*,d_ticketing.*,r_priority.*,r_status.*,s_unit.*,s_user_group_unit.*,db_ult.ref_jenis_layanan.*,
                    d_unit.unitNama dunitNama,
                    runit.unitNama runitNama,
                    ref_layanan.layananId sCatId,
                    ref_layanan.layananNama sCatNama,
                    ref_layanan.layananunitId sCatCategoryId,
                    ref_layanan.layananisbottomup sCatDisposisi,
                    repliesTicketId,
                    COUNT(repliesTicketId) as jumlah,
                    CONCAT(ref_jenis_layanan.jenislayananNama,' (',runit.unitNama ,')') categoryNama", false)
            ->join('d_replies', 'ticketTrackingId = repliesTicketId', 'LEFT')
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
            ->groupBy('repliesTicketId')
            ->orderBy('disposisiTanggal DESC')
            ->get()->getResultArray();

        return $hasil === [] ? false : $hasil;
    }

    /**
     * Koneksi database aktif (dipakai BaseController untuk escape manual).
     */
    public function dbAktif(): \CodeIgniter\Database\BaseConnection
    {
        return $this->db;
    }

    /**
     * Query builder untuk tabel arbitrer (dipakai controller datatables).
     */
    public function tabelBuilder(string $tabel): BaseBuilder
    {
        return $this->db->table($tabel);
    }
}
