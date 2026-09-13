<?php

namespace App\Models;

/**
 * Model validasi file EULT (porting CI3 Model_validasifile.php).
 * Path upload dialihkan dari ../upload_file/ ke writable/uploads/.
 */
class ModelValidasifile extends ModelMaster
{
    private string $uploadPath;

    private string $quarantinePath;

    private string $manifestFile;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $cacheAktif = null;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $cacheLegacy = null;

    public function __construct()
    {
        parent::__construct();

        $this->uploadPath     = rtrim(WRITEPATH . 'uploads', '/') . '/';
        $this->quarantinePath = $this->uploadPath . '_quarantine/';
        $this->manifestFile   = $this->quarantinePath . 'manifest.json';

        $this->inisiasiDirektori();
    }

    private function inisiasiDirektori(): void
    {
        foreach (['', 'ticketing/', 'chat/', 'qrcode/', '_quarantine/', '_quarantine/ticketing/', '_quarantine/chat/'] as $dir) {
            $lokasi = ($dir === '' ? rtrim($this->uploadPath, '/') : $this->uploadPath . rtrim($dir, '/'));
            if (! is_dir($lokasi)) {
                @mkdir($lokasi, 0755, true);
            }
        }

        if (! is_file($this->manifestFile)) {
            file_put_contents(
                $this->manifestFile,
                json_encode(['quarantined' => (object) [], 'history' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    /**
     * @return array{quarantined: array<string, mixed>, history: array<int, mixed>}
     */
    public function getManifest(): array
    {
        if (! is_file($this->manifestFile)) {
            return ['quarantined' => [], 'history' => []];
        }

        $isi  = file_get_contents($this->manifestFile);
        $data = is_string($isi) ? json_decode($isi, true) : null;

        return is_array($data) ? $data : ['quarantined' => [], 'history' => []];
    }

    private function simpanManifest(array $data): void
    {
        file_put_contents(
            $this->manifestFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return list<string>
     */
    private function pindaiDir(string $dir): array
    {
        $hasil = [];
        if (! is_dir($dir)) {
            return $hasil;
        }

        $daftar = scandir($dir);
        if (! is_array($daftar)) {
            return $hasil;
        }

        foreach ($daftar as $f) {
            if (in_array($f, ['.', '..', '.gitkeep', '.gitignore', 'index.html', '_quarantine'], true)) {
                continue;
            }
            if (is_file($dir . '/' . $f)) {
                $hasil[] = $f;
            }
        }

        return $hasil;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryStats(): array
    {
        $arsipDb  = $this->db->table('d_archive')->where('archiveFile IS NOT NULL AND archiveFile != ""', null, false)->countAllResults();
        $balasanDb = $this->db->table('d_replies')->where('repliesFile IS NOT NULL AND repliesFile != ""', null, false)->countAllResults();
        $legacyDb  = $this->db->tableExists('d_archive11122020')
            ? $this->db->table('d_archive11122020')->where('archiveFile IS NOT NULL AND archiveFile != ""', null, false)->countAllResults()
            : 0;

        $tiketFiles  = $this->pindaiDir($this->uploadPath . 'ticketing');
        $chatFiles   = $this->pindaiDir($this->uploadPath . 'chat');
        $qrFiles     = $this->pindaiDir($this->uploadPath . 'qrcode');

        $arsipAktif  = $this->petaArsipAktif($tiketFiles);
        $arsipLegacy = $this->petaArsipLegacy();

        $valid = 0;
        $legacy = 0;
        $orphan = 0;

        foreach ($tiketFiles as $f) {
            if (isset($arsipAktif[$f])) {
                $valid++;
            } elseif (isset($arsipLegacy[$f])) {
                $legacy++;
            } else {
                $orphan++;
            }
        }

        $manifest   = $this->getManifest();
        $karantina  = is_array($manifest['quarantined'] ?? null) ? count($manifest['quarantined']) : 0;

        return [
            'ticketing_disk_total' => count($tiketFiles),
            'chat_disk_total'      => count($chatFiles),
            'qrcode_disk_total'    => count($qrFiles),
            'valid_count'          => $valid,
            'legacy_count'         => $legacy,
            'orphan_count'         => $orphan,
            'unmatched_total'      => $legacy + $orphan,
            'quarantined_count'    => $karantina,
            'db_archive_count'     => $arsipDb,
            'db_replies_count'     => $balasanDb,
            'db_legacy_count'      => $legacyDb,
        ];
    }

    /**
     * @param list<string> $daftar
     * @return array<string, array<string, mixed>>
     */
    private function petaArsipAktif(array $daftar = []): array
    {
        if ($this->cacheAktif !== null) {
            return $this->cacheAktif;
        }

        $peta = [];

        $prosesChunk = function (array $chunk) use (&$peta): void {
            $baris = $this->db->table('d_archive')
                ->select('archiveFile, archiveTrackingId, archiveId, archiveJenis')
                ->whereIn('archiveFile', $chunk)
                ->get()->getResultArray();
            foreach ($baris as $row) {
                $peta[$row['archiveFile']] = $row;
            }
        };

        if ($daftar !== []) {
            foreach (array_chunk($daftar, 500) as $chunk) {
                $prosesChunk($chunk);
            }
        } else {
            $baris = $this->db->table('d_archive')
                ->select('archiveFile, archiveTrackingId, archiveId, archiveJenis')
                ->where('archiveFile IS NOT NULL AND archiveFile != ""', null, false)
                ->get()->getResultArray();
            foreach ($baris as $row) {
                $peta[$row['archiveFile']] = $row;
            }
        }

        $this->cacheAktif = $peta;

        return $peta;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function petaArsipLegacy(): array
    {
        if ($this->cacheLegacy !== null) {
            return $this->cacheLegacy;
        }

        $peta = [];
        if ($this->db->tableExists('d_archive11122020')) {
            $baris = $this->db->table('d_archive11122020')
                ->select('archiveFile, archiveTrackingId, archiveId')
                ->where('archiveFile IS NOT NULL AND archiveFile != ""', null, false)
                ->get()->getResultArray();
            foreach ($baris as $row) {
                $peta[$row['archiveFile']] = $row;
            }
        }

        $this->cacheLegacy = $peta;

        return $peta;
    }

    /**
     * @return array{draw:int,recordsTotal:int,recordsFiltered:int,data:list<array<string, mixed>>}
     */
    public function getDatatableFiles(int $draw, int $mulai, int $batas, string $cari, int $kolomUrut, string $arahUrut, string $folder = 'ticketing', string $filterStatus = ''): array
    {
        $dir      = $this->uploadPath . $folder;
        $semua    = $this->pindaiDir($dir);
        $total    = count($semua);

        $arsipAktif  = [];
        $arsipLegacy = [];
        $dbBalasan   = [];

        if ($folder === 'ticketing') {
            $arsipAktif  = $this->petaArsipAktif($semua);
            $arsipLegacy = $this->petaArsipLegacy();
        } elseif ($folder === 'chat') {
            $baris = $this->db->table('d_replies')
                ->select('repliesTicketId, repliesFile, repliesBy, repliesDate')
                ->where('repliesFile IS NOT NULL AND repliesFile != ""', null, false)
                ->get()->getResultArray();
            foreach ($baris as $row) {
                $dbBalasan[$row['repliesFile']] = $row;
            }
        }

        $tersaring = [];
        foreach ($semua as $f) {
            $status       = 'ORPHAN';
            $labelStatus  = 'Orphan (Tidak di DB)';
            $kelasBadge   = 'badge-danger';
            $idTracking   = '';
            $infoDb       = null;

            if ($folder === 'ticketing') {
                if (isset($arsipAktif[$f])) {
                    $status      = 'VALID';
                    $labelStatus = 'Valid (d_archive)';
                    $kelasBadge  = 'badge-success';
                    $idTracking  = $arsipAktif[$f]['archiveTrackingId'];
                    $infoDb      = [
                        'archiveId'         => $arsipAktif[$f]['archiveId'],
                        'archiveTrackingId' => $arsipAktif[$f]['archiveTrackingId'],
                        'archiveJenis'      => $arsipAktif[$f]['archiveJenis'] ?? '',
                    ];
                } elseif (isset($arsipLegacy[$f])) {
                    $status      = 'LEGACY_BACKUP';
                    $labelStatus = 'Backup Lama (2020)';
                    $kelasBadge  = 'badge-warning';
                    $idTracking  = $arsipLegacy[$f]['archiveTrackingId'];
                    $infoDb      = [
                        'archiveId'         => $arsipLegacy[$f]['archiveId'],
                        'archiveTrackingId' => $arsipLegacy[$f]['archiveTrackingId'],
                    ];
                }
            } elseif ($folder === 'chat') {
                if (isset($dbBalasan[$f])) {
                    $status      = 'VALID';
                    $labelStatus = 'Valid (d_replies)';
                    $kelasBadge  = 'badge-success';
                    $idTracking  = $dbBalasan[$f]['repliesTicketId'];
                    $infoDb      = [
                        'ticketId' => $dbBalasan[$f]['repliesTicketId'],
                        'by'       => $dbBalasan[$f]['repliesBy'],
                        'date'     => $dbBalasan[$f]['repliesDate'],
                    ];
                }
            } elseif ($folder === 'qrcode') {
                $status      = 'STATIC_QR';
                $labelStatus = 'Static / Asset QR';
                $kelasBadge  = 'badge-info';
            }

            if ($filterStatus !== '' && $status !== $filterStatus) {
                continue;
            }

            if ($cari !== '') {
                if (stripos($f, $cari) === false && stripos($idTracking, $cari) === false && stripos($labelStatus, $cari) === false) {
                    continue;
                }
            }

            $tersaring[] = [
                'filename'     => $f,
                'folder'       => $folder,
                'status'       => $status,
                'status_label' => $labelStatus,
                'badge_class'  => $kelasBadge,
                'tracking_id'  => $idTracking,
                'db_info'      => $infoDb,
            ];
        }

        $jumlahTersaring = count($tersaring);

        if ($kolomUrut === 2) {
            usort($tersaring, static fn ($a, $b) => $arahUrut === 'asc' ? strnatcasecmp($a['filename'], $b['filename']) : strnatcasecmp($b['filename'], $a['filename']));
        } elseif ($kolomUrut === 3) {
            usort($tersaring, static fn ($a, $b) => $arahUrut === 'asc' ? strcmp($a['status'], $b['status']) : strcmp($b['status'], $a['status']));
        }

        $potongan = ($batas > 0) ? array_slice($tersaring, $mulai, $batas) : $tersaring;

        $barisData = [];
        $no        = $mulai + 1;
        foreach ($potongan as $row) {
            $f         = $row['filename'];
            $lokasi    = $dir . '/' . $f;
            $ukuran    = is_file($lokasi) ? filesize($lokasi) : 0;
            $diubah    = is_file($lokasi) ? date('Y-m-d H:i:s', filemtime($lokasi)) : '-';

            $barisData[] = [
                'no'           => $no++,
                'filename'     => $f,
                'folder'       => $row['folder'],
                'status'       => $row['status'],
                'status_label' => $row['status_label'],
                'badge_class'  => $row['badge_class'],
                'tracking_id'  => $row['tracking_id'],
                'filesize'     => $ukuran,
                'filesize_fmt' => $this->formatBytes((int) $ukuran),
                'mtime'        => $diubah,
                'db_info'      => $row['db_info'],
            ];
        }

        return [
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $jumlahTersaring,
            'data'            => $barisData,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getFilesData(string $folder = 'ticketing', string $filterStatus = ''): array
    {
        $dt = $this->getDatatableFiles(1, 0, -1, '', 2, 'asc', $folder, $filterStatus);

        return $dt['data'];
    }

    /**
     * @param list<string> $daftarNama
     * @return array{status:bool,success_count:int,failed_count:int,failed_files:list<array<string, string>>}
     */
    public function quarantineFiles(array $daftarNama, string $folder = 'ticketing', string $pengguna = 'ADMIN'): array
    {
        $manifest = $this->getManifest();
        $sukses   = 0;
        $gagal    = [];

        foreach ($daftarNama as $namaFile) {
            $namaFile = basename($namaFile);
            $asal     = $this->uploadPath . $folder . '/' . $namaFile;
            $dirTujuan = $this->quarantinePath . $folder . '/';
            $tujuan   = $dirTujuan . $namaFile;

            if (! file_exists($asal)) {
                $gagal[] = ['file' => $namaFile, 'error' => 'File tidak ditemukan di disk asal'];
                continue;
            }

            if (! is_dir($dirTujuan)) {
                @mkdir($dirTujuan, 0755, true);
            }

            $ukuran = filesize($asal);
            $sha256 = hash_file('sha256', $asal);

            if (rename($asal, $tujuan)) {
                $sukses++;
                $item = [
                    'filename'        => $namaFile,
                    'folder'          => $folder,
                    'original_path'   => 'writable/uploads/' . $folder . '/' . $namaFile,
                    'quarantine_path' => 'writable/uploads/_quarantine/' . $folder . '/' . $namaFile,
                    'filesize'        => $ukuran,
                    'filesize_fmt'    => $this->formatBytes((int) $ukuran),
                    'sha256'          => $sha256,
                    'quarantined_at'  => date('Y-m-d H:i:s'),
                    'quarantined_by'  => $pengguna,
                ];

                $manifest['quarantined'][$folder . ':' . $namaFile] = $item;
                $manifest['history'][] = [
                    'action'    => 'QUARANTINE',
                    'filename'  => $namaFile,
                    'folder'    => $folder,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user'      => $pengguna,
                ];
            } else {
                $gagal[] = ['file' => $namaFile, 'error' => 'Gagal memindahkan file'];
            }
        }

        $this->simpanManifest($manifest);

        return [
            'status'        => ($sukses > 0),
            'success_count' => $sukses,
            'failed_count'  => count($gagal),
            'failed_files'  => $gagal,
        ];
    }

    /**
     * @param list<string> $daftarNama
     * @return array{status:bool,success_count:int,failed_count:int,failed_files:list<array<string, string>>}
     */
    public function restoreFiles(array $daftarNama, string $folder = 'ticketing', string $pengguna = 'ADMIN'): array
    {
        $manifest = $this->getManifest();
        $sukses   = 0;
        $gagal    = [];

        foreach ($daftarNama as $namaFile) {
            $namaFile = basename($namaFile);
            $asal     = $this->quarantinePath . $folder . '/' . $namaFile;
            $dirTujuan = $this->uploadPath . $folder . '/';
            $tujuan   = $dirTujuan . $namaFile;

            if (! file_exists($asal)) {
                $gagal[] = ['file' => $namaFile, 'error' => 'File tidak ditemukan di folder karantina'];
                continue;
            }

            if (! is_dir($dirTujuan)) {
                @mkdir($dirTujuan, 0755, true);
            }

            if (rename($asal, $tujuan)) {
                $sukses++;
                unset($manifest['quarantined'][$folder . ':' . $namaFile]);
                $manifest['history'][] = [
                    'action'    => 'RESTORE',
                    'filename'  => $namaFile,
                    'folder'    => $folder,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user'      => $pengguna,
                ];
            } else {
                $gagal[] = ['file' => $namaFile, 'error' => 'Gagal memulihkan file'];
            }
        }

        $this->simpanManifest($manifest);

        return [
            'status'        => ($sukses > 0),
            'success_count' => $sukses,
            'failed_count'  => count($gagal),
            'failed_files'  => $gagal,
        ];
    }

    /**
     * @param list<string> $daftarNama
     * @return array{status:bool,success_count:int,failed_count:int,failed_files:list<array<string, string>>}
     */
    public function deletePermanentFiles(array $daftarNama, string $folder = 'ticketing', string $pengguna = 'ADMIN'): array
    {
        $manifest = $this->getManifest();
        $sukses   = 0;
        $gagal    = [];

        foreach ($daftarNama as $namaFile) {
            $namaFile = basename($namaFile);
            $target   = $this->quarantinePath . $folder . '/' . $namaFile;

            if (! file_exists($target)) {
                $gagal[] = ['file' => $namaFile, 'error' => 'File tidak ditemukan di folder karantina'];
                continue;
            }

            if (@unlink($target)) {
                $sukses++;
                unset($manifest['quarantined'][$folder . ':' . $namaFile]);
                $manifest['history'][] = [
                    'action'    => 'DELETE_PERMANENT',
                    'filename'  => $namaFile,
                    'folder'    => $folder,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user'      => $pengguna,
                ];
            } else {
                $gagal[] = ['file' => $namaFile, 'error' => 'Gagal menghapus file'];
            }
        }

        $this->simpanManifest($manifest);

        return [
            'status'        => ($sukses > 0),
            'success_count' => $sukses,
            'failed_count'  => count($gagal),
            'failed_files'  => $gagal,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getQuarantinedList(): array
    {
        $manifest = $this->getManifest();
        $daftar   = [];

        if (! empty($manifest['quarantined'])) {
            foreach ($manifest['quarantined'] as $item) {
                $lokasi                  = $this->uploadPath . '_quarantine/' . $item['folder'] . '/' . $item['filename'];
                $item['exists_on_disk'] = file_exists($lokasi);
                $daftar[]               = $item;
            }
        }

        return $daftar;
    }

    private function formatBytes(int $byte, int $presisi = 2): string
    {
        $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
        $byte   = max($byte, 0);
        $pangkat = floor(($byte ? log($byte) : 0) / log(1024));
        $pangkat = min($pangkat, count($satuan) - 1);
        $byte /= 1024 ** $pangkat;

        return round($byte, $presisi) . ' ' . $satuan[$pangkat];
    }
}
