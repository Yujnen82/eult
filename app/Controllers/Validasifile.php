<?php

namespace App\Controllers;

use App\Models\ModelValidasifile;

/**
 * Dashboard keamanan file upload (porting CI3 Validasifile.php).
 */
class Validasifile extends BaseController
{
    protected ?string $judul = 'Validasi File';

    protected ?string $controllerName = 'validasifile';

    protected ?string $pathPage = 'pages/validasifile/';

    private ModelValidasifile $berkas;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->berkas = new ModelValidasifile();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['page_judul'] = $this->judul;
        $data['scripts']    = ['validasifile'];
        $data['stats']      = $this->berkas->getSummaryStats();
        $data['manifest']   = $this->berkas->getManifest();

        return view($this->template, $data);
    }

    public function getStatsAjax()
    {
        return $this->response->setJSON(['status' => true, 'data' => $this->berkas->getSummaryStats()]);
    }

    public function getDataAjax()
    {
        $cari  = $this->request->getPost('search');
        $order = $this->request->getPost('order');

        return $this->response->setJSON($this->berkas->getDatatableFiles(
            (int) ($this->request->getPost('draw') ?? 1),
            (int) ($this->request->getPost('start') ?? 0),
            (int) ($this->request->getPost('length') ?? 25),
            is_array($cari) && isset($cari['value']) ? trim((string) $cari['value']) : '',
            is_array($order) && isset($order[0]['column']) ? (int) $order[0]['column'] : 2,
            is_array($order) && isset($order[0]['dir']) ? strtolower((string) $order[0]['dir']) : 'asc',
            (string) ($this->request->getPost('folder') ?: 'ticketing'),
            (string) ($this->request->getPost('status') ?? '')
        ));
    }

    public function getQuarantineAjax()
    {
        $daftar = $this->berkas->getQuarantinedList();

        return $this->response->setJSON([
            'draw'            => 1,
            'recordsTotal'    => count($daftar),
            'recordsFiltered' => count($daftar),
            'data'            => $daftar,
        ]);
    }

    public function getManifestAjax()
    {
        return $this->response->setJSON(['status' => true, 'data' => $this->berkas->getManifest()]);
    }

    public function prosesKarantina()
    {
        $sesi   = $this->sesiLogin() ?? [];
        $pengguna = $sesi['susrNama'] ?? 'ADMIN';
        $mode   = (string) $this->request->getPost('mode');
        $folder = (string) ($this->request->getPost('folder') ?: 'ticketing');
        $daftar = $this->request->getPost('files');

        if ($mode === 'all_unmatched') {
            $semua  = $this->berkas->getFilesData($folder);
            $daftar = [];
            foreach ($semua as $item) {
                if ($item['status'] === 'ORPHAN' || $item['status'] === 'LEGACY_BACKUP') {
                    $daftar[] = $item['filename'];
                }
            }
        }

        if (empty($daftar) || ! is_array($daftar)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada file yang dipilih untuk dikarantina.']);
        }

        $hasil = $this->berkas->quarantineFiles($daftar, $folder, (string) $pengguna);

        return $this->response->setJSON($hasil['status']
            ? ['status' => true, 'message' => "Berhasil memindahkan {$hasil['success_count']} file ke folder karantina (_quarantine/).", 'details' => $hasil]
            : ['status' => false, 'message' => 'Gagal memindahkan file ke karantina.', 'details' => $hasil]);
    }

    public function prosesRestore()
    {
        $sesi   = $this->sesiLogin() ?? [];
        $pengguna = $sesi['susrNama'] ?? 'ADMIN';
        $mode   = (string) $this->request->getPost('mode');
        $folder = (string) ($this->request->getPost('folder') ?: 'ticketing');
        $daftar = $this->request->getPost('files');

        if ($mode === 'all') {
            $karantina = $this->berkas->getQuarantinedList();
            $daftar    = [];
            foreach ($karantina as $q) {
                if ($q['folder'] === $folder) {
                    $daftar[] = $q['filename'];
                }
            }
        }

        if (empty($daftar) || ! is_array($daftar)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada file yang dipilih untuk dipulihkan.']);
        }

        $hasil = $this->berkas->restoreFiles($daftar, $folder, (string) $pengguna);

        return $this->response->setJSON($hasil['status']
            ? ['status' => true, 'message' => "Berhasil memulihkan {$hasil['success_count']} file kembali ke folder upload asal.", 'details' => $hasil]
            : ['status' => false, 'message' => 'Gagal memulihkan file.', 'details' => $hasil]);
    }

    public function prosesDelete()
    {
        $sesi   = $this->sesiLogin() ?? [];
        $pengguna = $sesi['susrNama'] ?? 'ADMIN';
        $folder = (string) ($this->request->getPost('folder') ?: 'ticketing');
        $daftar = $this->request->getPost('files');

        if (empty($daftar) || ! is_array($daftar)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tidak ada file yang dipilih untuk dihapus.']);
        }

        $hasil = $this->berkas->deletePermanentFiles($daftar, $folder, (string) $pengguna);

        return $this->response->setJSON($hasil['status']
            ? ['status' => true, 'message' => "Berhasil menghapus {$hasil['success_count']} file secara permanen dari karantina.", 'details' => $hasil]
            : ['status' => false, 'message' => 'Gagal menghapus file.', 'details' => $hasil]);
    }

    /**
     * Alias rute lama: validasifile/restore dan validasifile/delete.
     */
    public function restore()
    {
        return $this->prosesRestore();
    }

    public function delete()
    {
        return $this->prosesDelete();
    }

    public function preview(string $folder = 'ticketing', string $namaFile = '', string $karantina = '0')
    {
        $folder   = basename($folder);
        $namaFile = basename($namaFile);

        if ($namaFile === '') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $lokasi = WRITEPATH . 'uploads/' . ($karantina === '1' ? '_quarantine/' : '') . $folder . '/' . $namaFile;

        if (! is_file($lokasi)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tipe = [
            'pdf' => 'application/pdf', 'png' => 'image/png',
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        ][strtolower(pathinfo($namaFile, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $tipe)
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaFile . '"')
            ->setHeader('Content-Length', (string) filesize($lokasi))
            ->setBody(file_get_contents($lokasi));
    }
}
