<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Libraries\OsmClient;
use App\Libraries\PengirimEmail;
use App\Models\ModelLogin;
use App\Models\ModelTicketing;

/**
 * Controller publik EULT (porting CI3 Login.php).
 * Menangani form login/buat/lacak tiket + AJAX identitas/layanan/syarat.
 */
class Login extends BaseController
{
    protected ?string $judul = 'Login';

    protected ?string $controllerName = 'login';

    protected ?string $modelName = 'ModelTicketing';

    private ModelTicketing $tiket;

    private ModelLogin $masuk;

    private Enkripsi $enkripsi;

    private OsmClient $osm;

    private PengirimEmail $email;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->tiket    = new ModelTicketing();
        $this->masuk    = new ModelLogin();
        $this->enkripsi = new Enkripsi();
        $this->osm      = new OsmClient();
        $this->email    = new PengirimEmail();
    }

    public function index(): string
    {
        $captcha = eult_captcha_generate(4);

        return view('layouts/login', [
            'captcha'    => $captcha,
            'r_priority' => $this->tiket->tabelRef('r_priority'),
            'datas'      => false,
        ]);
    }

    public function cektiket()
    {
        if (! $this->validate(['nomorTiket' => 'required'])) {
            return $this->response->setJSON([
                'status'  => 'danger',
                'message' => 'Nomor tiket wajib diisi.',
            ]);
        }

        $nomorTiket = (string) $this->request->getPost('nomorTiket');
        $datas      = $this->tiket->byId(['ticketTrackingId' => $nomorTiket]);

        if ($datas !== false) {
            $kunci = $this->enkripsi->encode($nomorTiket);

            return $this->response->setJSON([
                'status'       => 'success',
                'message'      => 'Nomor tiket ditemukan, mengalihkan halaman...',
                'redirect_url' => base_url('cektiket/index/') . $kunci,
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'danger',
            'message' => 'Nomor tiket tidak ditemukan.',
        ]);
    }

    public function savetiket()
    {
        $aturan = [
            'captcha'          => 'required',
            'ticketCategories' => 'required',
            'ticketEmail'      => 'required|valid_email',
            'ticketNoHp'       => 'required',
            'ticketSubject'    => 'required',
            'ticketMessage'    => 'required',
        ];

        if (! $this->validate($aturan)) {
            return $this->response->setJSON([
                'status'      => 'danger',
                'message'     => strip_tags(implode(' ', array_values($this->validator->getErrors()))),
                'new_captcha' => eult_captcha_generate(4),
            ]);
        }

        if (! eult_captcha_check((string) $this->request->getPost('captcha'))) {
            return $this->response->setJSON([
                'status'      => 'danger',
                'message'     => 'CAPTCHA yang Anda masukkan tidak valid.',
                'new_captcha' => eult_captcha_generate(4),
            ]);
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'danger',
                'message' => 'Ooops!! Something Wrong!!',
            ]);
        }

        $kodeAcak = eult_generate_kode();
        $cek      = $this->tiket->getNumber($kodeAcak);
        if (empty($cek)) {
            $idTiket = $kodeAcak . '-' . sprintf('%03d', 1);
        } else {
            $idTiket = $kodeAcak . '-' . sprintf('%03d', (int) substr((string) $cek['ticketTrackingId'], -3) + 1);
        }

        $nama     = (string) $this->request->getPost('ticketName');
        $email    = (string) $this->request->getPost('ticketEmail');
        $arsipId  = str_replace('-', '', $idTiket);
        $arsipBaru = eult_auto_increment('d_archive', 'archiveId', $arsipId, "archiveTrackingId='" . $idTiket . "'");

        $konfig = [
            'url'      => WRITEPATH . 'uploads/ticketing/',
            'type'     => 'pdf',
            'size'     => 20 * 1024,
            'namafile' => 'TIKET_' . $arsipId . '_' . date('YmdHis'),
        ];

        $paramFile = [
            'archiveId'         => $arsipBaru,
            'archiveTrackingId' => $idTiket,
            'archiveJenis'      => 'TIKET',
        ];

        $param = [
            'ticketIdentitas'  => (string) $this->request->getPost('ticketIdentitas'),
            'ticketName'       => $nama,
            'ticketCategories' => (string) $this->request->getPost('ticketCategories'),
            'ticketEmail'      => $email,
            'ticketNoHp'       => (string) $this->request->getPost('ticketNoHp'),
            'ticketSubject'    => (string) $this->request->getPost('ticketSubject'),
            'ticketPriority'   => (string) $this->request->getPost('ticketPriority'),
            'ticketMessage'    => (string) $this->request->getPost('ticketMessage'),
            'ticketCreated'    => date('Y-m-d H:i:s'),
            'ticketStatus'     => 1,
            'ticketCreatedBy'  => '',
            'ticketArchiveId'  => $arsipBaru,
            'ticketTrackingId' => $idTiket,
        ];

        $proses = $this->tiket->tambah('d_ticketing', $param);

        if ($proses && $this->request->getFile('ticketArchiveId') !== null && $this->request->getFile('ticketArchiveId')->getError() !== UPLOAD_ERR_NO_FILE) {
            eult_upload_ticket($konfig, $paramFile);
        }

        if ($proses) {
            eult_save_history('Tiket Telah Dibuat Oleh ' . $nama, $idTiket);

            $datas = $this->tiket->byId("ticketTrackingId = '" . $idTiket . "'");
            $this->email->buat($email, 'Tiket EULT UNMUL #' . $idTiket, $datas);

            return $this->response->setJSON([
                'status'      => 'success',
                'message'     => 'Permintaan layanan anda berhasil disimpan, nomor tiket anda adalah ' . $idTiket . '. <br/> Catat nomor tiket anda dan cek progress secara berkala pada menu lacak tiket.',
                'new_captcha' => eult_captcha_generate(4),
            ]);
        }

        $galat = $this->tiket->dbAktif()->error();

        return $this->response->setJSON([
            'status'  => 'danger',
            'message' => 'Permintaan layanan anda gagal disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''),
        ]);
    }

    public function getIdentitas()
    {
        $id  = (string) $this->request->getPost('id');
        $mhs = $this->osm->mhsId2($id);

        if (is_object($mhs) && ! empty($mhs->nim)) {
            return $this->response->setJSON([
                'status'  => true,
                'ismhs'   => true,
                'datamhs' => ['name' => $mhs->peserta_didik->nama ?? ''],
            ]);
        }

        $pegawai = $this->osm->pegawaiId($id);
        if ($pegawai == true) {
            $nama = is_object($pegawai) ? ($pegawai->nama ?? '') : '';

            return $this->response->setJSON([
                'status'      => true,
                'ismhs'       => false,
                'datapegawai' => ['name' => $nama],
            ]);
        }

        // Pemohon umum (NIK tanpa NIM/NIP): kategori layanan publik dibuka,
        // nama diisi manual oleh pemohon karena tidak ada data OSM.
        if (strlen($id) === 16 && ctype_digit($id)) {
            return $this->response->setJSON([
                'status' => true,
                'umum'   => true,
                'name'   => '',
            ]);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Data Tidak Ditemukan']);
    }

    public function getLayanan()
    {
        $id = (string) $this->request->getPost('id');

        $datas = $id === 'true' ? $this->masuk->getLayanan() : $this->masuk->getLayanan($id);

        if ($datas !== false && !empty($datas)) {
            $currentGroup = null;
            $opsi = '<option value="">Pilih Kategori Layanan Kampus</option>';
            foreach ($datas as $row) {
                $group = $row['jenislayananNama'];
                if ($group !== $currentGroup) {
                    if ($currentGroup !== null) {
                        $opsi .= '</optgroup>';
                    }
                    $currentGroup = $group;
                    $opsi .= '<optgroup label="' . esc($currentGroup) . '">';
                }
                $opsi .= '<option value="' . esc($row['layananId']) . '">' . esc($row['layananNama']) . ' (' . esc($row['unitNama']) . ')</option>';
            }
            if ($currentGroup !== null) {
                $opsi .= '</optgroup>';
            }

            return $this->response->setBody($opsi);
        }

        return $this->response->setBody('<option value="">Masukkan nomor identitas terlebih dahulu...</option>');
    }

    public function getSyarat()
    {
        $layananId = (string) $this->request->getPost('id');

        $syarat = $this->masuk->tabelRef('r_berkas_layanan', ['berkasidLayanan' => $layananId]);

        if ($syarat !== false) {
            $daftar = '<h5>Persyaratan File yang harus Upload</h5><ol>';
            foreach ($syarat as $r) {
                $daftar .= '<li>' . $r['berkasNama'] . '</li>';
            }
            $daftar .= '</ol>';
        } else {
            $daftar = 'Layanan tidak memerlukan file untuk diupload';
        }

        return $this->response->setBody($daftar);
    }

    public function refreshCaptcha()
    {
        return $this->response->setJSON(['captcha' => eult_captcha_generate(4)]);
    }
}
