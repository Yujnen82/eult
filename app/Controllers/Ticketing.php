<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Libraries\GeneratorQr;
use App\Libraries\OsmClient;
use App\Libraries\PengirimEmail;
use App\Models\ModelTicketing;
use Mpdf\Mpdf;

/**
 * Controller ticketing inti EULT (porting CI3 Ticketing.php ±1480 baris).
 * Seluruh 27 method aktif diporting; method yang dikomen di CI3
 * (close/reject/getKeperluan/addworker/getqrcode) tidak dibawa (YAGNI).
 */
class Ticketing extends BaseController
{
    protected ?string $template = 'layouts/template';

    protected ?string $pathPage = 'pages/ticketing/';

    protected ?string $pathJs = 'ticketing/';

    protected ?string $judul = 'Ticketing';

    protected ?string $controllerName = 'ticketing';

    /** @var array<string, mixed> */
    private array $pengguna;

    private ModelTicketing $tiket;

    private Enkripsi $enkripsi;

    private OsmClient $osm;

    private PengirimEmail $email;

    private GeneratorQr $qr;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->tiket    = new ModelTicketing();
        $this->enkripsi = new Enkripsi();
        $this->osm      = new OsmClient();
        $this->email    = new PengirimEmail();
        $this->qr       = new GeneratorQr();
        $this->pengguna = $this->sesiLogin() ?? [];
    }

    private function isAjax(): bool
    {
        return $this->request->isAJAX();
    }

    /**
     * Decode kunci tiket dari URL/form dan pastikan pengguna berhak atas
     * tiket tersebut. Mengirim respons JSON 403 (dan menghentikan request)
     * bila kunci tidak valid atau tiket berada di luar cakupan unit pengguna.
     *
     * @return string Nomor tiket (ticketTrackingId) hasil decode.
     */
    private function tiketTerotorisasi(string $kunci): string
    {
        $idTiket = $this->enkripsi->decode($kunci);

        if (! is_string($idTiket) || $idTiket === '' || ! $this->bolehAksesTiket($idTiket)) {
            $this->response->setStatusCode(403);
            eult_message_kirim('Anda tidak berhak mengakses tiket ini.', 'error');
        }

        return $idTiket;
    }

    /**
     * Sama dengan tiketTerotorisasi() untuk nomor tiket mentah yang dikirim
     * dari field tersembunyi form (ticketIdOld, repliesTicketId, nomorTiket).
     */
    private function tiketMentahTerotorisasi(string $idTiket): string
    {
        if ($idTiket === '' || ! $this->bolehAksesTiket($idTiket)) {
            $this->response->setStatusCode(403);
            eult_message_kirim('Anda tidak berhak mengakses tiket ini.', 'error');
        }

        return $idTiket;
    }

    public function index(): string
    {
        $data                 = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']      = [$this->pathJs . 'ticketing'];
        $data['tanggal']      = session()->get('tanggal');
        $data['create_url']   = site_url($this->controllerName . '/create');
        $data['user_group']   = $this->pengguna;
        $data['category']     = $this->tiket->tabelRef('db_ult.ref_unit', '', 'unitUrut');
        $data['status_layanan'] = $this->tiket->tabelRef('r_status');
        $data['show_url']     = site_url($this->controllerName . '/response') . '/';

        return view($this->template, $data);
    }

    /**
     * Nama view template cetak surat (pages/ticketing/cetak/cetak_N). Nilai
     * t_surat.tsuratForm yang kosong/tidak dikenal jatuh ke cetak_1.
     */
    private function templateCetak(?string $form): string
    {
        $form = basename((string) $form);

        if ($form === '' || ! is_file(APPPATH . 'Views/' . $this->pathPage . 'cetak/' . $form . '.php')) {
            return 'cetak_1';
        }

        return $form;
    }

    /**
     * Rentang tanggal "dd/mm/yyyy / dd/mm/yyyy" dari filter -> [awal, akhir] Y-m-d.
     * Format tak terbaca mengembalikan rentang kosong, bukan diam-diam
     * melaporkan hari ini seolah itu rentang yang diminta operator.
     *
     * @return array{0: string, 1: string}
     */
    private function rentangTanggal(string $tanggal): array
    {
        $pecah = explode('/', str_replace(' ', '', $tanggal));
        $awal  = strtotime($pecah[0] ?? '');
        $akhir = strtotime($pecah[1] ?? '');

        if ($awal === false) {
            return ['', ''];
        }

        return [date('Y-m-d', $awal), date('Y-m-d', $akhir ?: $awal)];
    }

    public function response(): string
    {
        if (! $this->validate(['rentangTanggal' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $sesi          = $this->pengguna;
        $tanggal       = (string) $this->request->getPost('rentangTanggal');
        $layanan       = (string) $this->request->getPost('layanan');
        $statusLayanan = (string) ($this->request->getPost('status_layanan') ?? '');

        session()->set('tanggal', $tanggal);
        [$tanggalAwal, $tanggalAkhir] = $this->rentangTanggal($tanggal);

        if ($tanggalAwal === '') {
            eult_message_kirim('Format rentang tanggal tidak dikenali. Pilih ulang periode tanggal.', 'error');
        }

        $kondisi = [
            'ticketCreated >=' => $tanggalAwal . ' 00:00:00',
            'ticketCreated <=' => $tanggalAkhir . ' 23:59:59',
        ];

        if ($sesi['susrSgroupNama'] === 'ADMIN' || strpos($sesi['susrSgroupNama'], 'OPERATOR') !== false) {
            $kondisi['runit.unitId'] = $layanan;
            $datas                   = $this->tiket->dataById($kondisi, $statusLayanan);
        } else {
            $kondisi['sgroupunitSgroupNama'] = $sesi['susrSgroupNama'];
            $datas                           = $this->tiket->disposisiAll($kondisi, $statusLayanan);
        }

        $data                  = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['isProduksi']    = strpos($this->pengguna['susrSgroupNama'], 'PRODUKSI');
        $data['isVerifikator'] = strpos($this->pengguna['susrSgroupNama'], 'VERIFIKATOR');
        $data['sgroup']        = $this->tiket->ambilSatu('s_user_group_unit', ['sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama'], 'sgroupunitIsHome >' => 0]);
        $data['user_group']    = $sesi['susrSgroupNama'];
        $data['export_url']    = site_url($this->controllerName . '/export');
        $data['datas']         = $datas;
        $data['detail_url']    = site_url($this->controllerName . '/detail') . '/';

        return view($this->pathPage . 'response', $data);
    }

    public function getLayanan()
    {
        $id = (string) $this->request->getPost('id');

        $datas = $id === 'true' ? $this->tiket->getLayanan() : $this->tiket->getLayanan($id);

        if ($datas !== false) {
            $kepala = null;
            $opsi   = '<option value="">Pilih Layanan</option>';
            foreach ($datas as $row) {
                if ($row['jenislayananNama'] !== $kepala) {
                    if ($kepala !== null) {
                        $opsi .= '</optgroup>';
                    }
                    $kepala = $row['jenislayananNama'];
                    $opsi .= '<optgroup label="' . esc($kepala) . '">';
                }
                $opsi .= '<option value="' . esc($row['layananId']) . '">' . esc($row['layananNama']) . ' (' . esc($row['unitNama']) . ')</option>';
            }
            if ($kepala !== null) {
                $opsi .= '</optgroup>';
            }

            return $this->response->setBody($opsi);
        }

        return $this->response->setBody('');
    }

    public function getIdentitas()
    {
        $id  = (string) ($this->request->getPost('id') ?? $this->request->getPost('identitas'));
        $mhs = $this->osm->mhsId2($id);

        if (is_object($mhs) && ! empty($mhs->nim)) {
            return $this->response->setJSON([
                'status'  => true,
                'ismhs'   => true,
                'name'    => $mhs->peserta_didik->nama ?? '',
                'datamhs' => ['name' => $mhs->peserta_didik->nama ?? ''],
            ]);
        }

        $pegawai = $this->osm->pegawaiId($id);
        if ($pegawai == true) {
            $nama = is_object($pegawai) ? ($pegawai->nama ?? '') : '';

            return $this->response->setJSON([
                'status'      => true,
                'ismhs'       => false,
                'name'        => $nama,
                'datapegawai' => ['name' => $nama],
            ]);
        }

        // Pemohon umum (NIK tanpa NIM/NIP): kategori layanan publik dibuka,
        // nama diisi manual oleh petugas.
        if (strlen($id) === 16 && ctype_digit($id)) {
            return $this->response->setJSON([
                'status' => true,
                'umum'   => true,
                'name'   => '',
            ]);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Data Tidak Ditemukan. Silakan isi identitas secara manual']);
    }

    public function create()
    {
        if (! $this->isAjax()) {
            return $this->response->setStatusCode(400)->setBody('Bad Request');
        }

        $data = [
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'scripts'     => [$this->pathJs . 'ticketing'],
            'save_url'    => site_url($this->controllerName . '/save') . '/',
            'status_page' => 'Create',
            'datas'       => false,
            's_user'      => $this->tiket->tabelRef('s_user'),
            'r_category'  => $this->tiket->tabelRef('r_category'),
            'user_data'   => $this->pengguna['susrSgroupNama'],
            'r_priority'  => $this->tiket->tabelRef('r_priority'),
        ];

        if ($this->pengguna['susrSgroupNama'] !== 'ADMIN' || $this->pengguna['susrSgroupNama'] !== 'OPERATOR') {
            $pengguna        = $this->tiket->ambilSatu('s_user_group', ['sgroupNama' => $this->pengguna['susrSgroupNama']]);
            $data['keperluan'] = $pengguna !== false ? $this->tiket->tabelRef('r_category_sub', ['sCatCategoryId' => $pengguna['sgroupCategoryId']]) : false;
        }

        return $this->response->setJSON(['response' => view($this->pathPage . 'form', $data)]);
    }

    public function update(string $kunci = '')
    {
        $terbuka         = $this->tiketTerotorisasi($kunci);
        $datas           = $this->tiket->byId(['ticketTrackingId' => $terbuka]);
        $pengguna        = $this->tiket->ambilSatu('s_user_group', ['sgroupNama' => $this->pengguna['susrSgroupNama']]);
        $arsip           = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $terbuka, 'archiveJenis' => 'TIKET']);
        $data            = [
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'save_url'    => site_url($this->controllerName . '/save') . '/',
            'status_page' => 'Update',
            'datas'       => $datas,
            's_user'      => $this->tiket->tabelRef('s_user'),
            'r_category'  => $this->tiket->tabelRef('r_category'),
            'keperluan'   => ($this->pengguna['susrSgroupNama'] !== 'ADMIN' || $this->pengguna['susrSgroupNama'] !== 'OPERATOR') && $pengguna !== false
                ? $this->tiket->tabelRef('r_category_sub', ['sCatCategoryId' => $pengguna['sgroupCategoryId']])
                : null,
            'layanan'     => $this->tiket->getLayanan(),
            'user_data'   => $this->pengguna['susrSgroupNama'],
            'r_priority'  => $this->tiket->tabelRef('r_priority'),
            'archive_url' => $arsip !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $arsip['archiveFile'] : false,
        ];

        return $this->response->setJSON(['response' => view($this->pathPage . 'form', $data)]);
    }

    public function createSurat(string $kunci = '')
    {
        $id    = $this->tiketTerotorisasi($kunci);
        $datas = $this->tiket->ambilSatu('d_ticketing', ['ticketTrackingId' => $id]);
        $surat = $datas !== false ? $this->tiket->ambilSatu('t_surat', ['tsuratLayananId' => $datas['ticketCategories']]) : false;

        return $this->response->setJSON(['response' => view($this->pathPage . 'form_surat', [
            'pejabatTtd'  => $this->tiket->getPejabatTtd($this->pengguna['susrSgroupNama']) ?: [],
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'scripts'     => [$this->pathJs . 'ticketing'],
            'save_url'    => site_url($this->controllerName . '/delivered') . '/',
            'status_page' => 'Create',
            'datas'       => false,
            'surat'       => $surat,
            'kunci'       => $kunci,
            'user_data'   => $this->pengguna['susrSgroupNama'],
            'preview_url' => site_url('ticketing/get_preview'),
        ])]);
    }

    public function editSurat(string $kunci = '')
    {
        $id    = $this->tiketTerotorisasi($kunci);
        $datas = $this->tiket->getSurat(['ticketTrackingId' => $id]);

        return $this->response->setJSON(['response' => view($this->pathPage . 'form_surat', [
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'scripts'     => [$this->pathJs . 'ticketing'],
            'save_url'    => site_url($this->controllerName . '/delivered') . '/',
            'status_page' => 'Update',
            'datas'       => $datas,
            'surat'       => $datas,
            'kunci'       => $kunci,
            'pejabatTtd'  => $this->tiket->getPejabatTtd($this->pengguna['susrSgroupNama']) ?: [],
            'user_data'   => $this->pengguna['susrSgroupNama'],
            'preview_url' => site_url('ticketing/get_preview'),
        ])]);
    }

    public function delivered()
    {
        if (! $this->validate([
            'suratJenis' => 'required', 'suratBody' => 'required',
            'suratNomor' => 'permit_empty', 'suratTanggal' => 'permit_empty',
            'suratPejabatJabatanAnDraft' => 'permit_empty', 'suratPejabatNIPDraft' => 'permit_empty',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idSurat   = $this->tiketTerotorisasi((string) $this->request->getPost('suratTrackingId'));
        $idLama    = $this->enkripsi->decode((string) $this->request->getPost('suratIdOld'));
        $pejabat   = explode(';', (string) $this->request->getPost('suratPejabatNIPDraft'));
        $datas     = $this->tiket->ambilSatu('d_ticketing', ['ticketTrackingId' => $idSurat]);
        $pekerja   = $datas !== false ? $this->tiket->getTicketAssign('s_unit', ['unitId' => $datas['ticketAssign']]) : false;
        $namaUnit  = $pekerja !== false ? $pekerja['unitNama'] . '(' . $pekerja['parentUnitNama'] . ')' : '';

        $param = [
            'suratBody'                  => (string) $this->request->getPost('suratBody'),
            'suratFooter'                => (string) $this->request->getPost('suratFooter'),
            'suratNomor'                 => (string) $this->request->getPost('suratNomor'),
            'suratPerihal'               => (string) $this->request->getPost('suratPerihal'),
            'suratJenis'                 => (string) $this->request->getPost('suratJenis'),
            'suratTanggal'               => (string) $this->request->getPost('suratTanggal'),
            'suratPejabatJabatanAnDraft' => (string) $this->request->getPost('suratPejabatJabatanAnDraft'),
            'suratPejabatNIPDraft'       => $pejabat[0] ?? '',
            'suratPejabatJabatanDraft'   => $pejabat[1] ?? '',
            'suratPejabatNamaDraft'      => $pejabat[2] ?? '',
            'suratTrackingId'            => $idSurat,
        ];

        $lampiran = (string) $this->request->getPost('suratLampiran');
        $tujuan   = (string) $this->request->getPost('suratTujuan');
        if ($lampiran !== '') {
            $param['suratLampiran'] = $lampiran;
        }
        if ($tujuan !== '') {
            $param['suratTujuan'] = $tujuan;
        }

        if (empty($idLama)) {
            $proses = $this->tiket->tambah('r_surat', $param)
                && $this->tiket->ubah('d_ticketing', ['ticketSuratCreated' => date('Y-m-d H:i:s')], ['ticketTrackingId' => $idSurat]);
        } else {
            $proses = $this->tiket->ubah('r_surat', $param, ['suratId' => $idLama])
                && $this->tiket->ubah('d_ticketing', ['ticketStatus' => 3, 'ticketSuratCreated' => date('Y-m-d H:i:s')], ['ticketTrackingId' => $idSurat]);
        }

        if ($proses) {
            eult_save_history('Surat Telah Dibuat Oleh ' . $this->pengguna['susrProfil'] . ' dan Tiket Menunggu Persetujuan Unit ' . $namaUnit, (string) $idSurat);
            eult_message_kirim('Surat Berhasil Disimpan', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function getPreview(?string $segmen = null)
    {
        // Simpan draf ke session; preview/(:any) membacanya lalu menghapusnya.
        if ($this->validate([
            'suratJenis' => 'permit_empty', 'suratPerihal' => 'permit_empty',
            'suratLampiran' => 'permit_empty', 'suratTujuan' => 'permit_empty',
            'suratBody' => 'permit_empty', 'suratFooter' => 'permit_empty',
            'suratNomor' => 'permit_empty', 'suratTanggal' => 'permit_empty',
            'suratPejabatJabatanAnDraft' => 'permit_empty', 'suratPejabatNIPDraft' => 'permit_empty',
        ])) {
            session()->set('sess_surat', [
                'suratJenis'                 => (string) $this->request->getPost('suratJenis'),
                'suratPerihal'               => (string) $this->request->getPost('suratPerihal'),
                'suratLampiran'              => (string) $this->request->getPost('suratLampiran'),
                'suratTujuan'                => (string) $this->request->getPost('suratTujuan'),
                'suratBody'                  => (string) $this->request->getPost('suratBody'),
                'suratFooter'                => (string) $this->request->getPost('suratFooter'),
                'suratNomor'                 => str_repeat('&nbsp;', 5) . (string) $this->request->getPost('suratNomor'),
                'suratTanggal'               => (string) $this->request->getPost('suratTanggal'),
                'suratPejabatJabatanAnDraft' => (string) $this->request->getPost('suratPejabatJabatanAnDraft'),
                'suratPejabatNIPDraft'       => (string) $this->request->getPost('suratPejabatNIPDraft'),
            ]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    public function preview(string $kunci = '')
    {
        $id       = $this->tiketTerotorisasi($kunci);
        $datas    = $this->tiket->getSurat(['ticketTrackingId' => $id]);
        $sesiSurat = session()->get('sess_surat');
        $identitas = $this->tiket->ambilSatu('d_ticketing', ['ticketTrackingId' => $id]);

        $mahasiswa = false;
        if ($identitas !== false) {
            $mentah = $this->osm->mhsId2((string) $identitas['ticketIdentitas']);
            if ($mentah) {
                $mahasiswa = (object) [
                    'nim'              => $mentah->nim ?? '',
                    'name'             => $mentah->peserta_didik->nama ?? '',
                    'ipk'              => $mentah->ipk ?? '',
                    'faculty_name'     => $mentah->program_studi->nama_fakultas ?? '',
                    'departement_name' => $mentah->program_studi->nama ?? '',
                    'degree'           => $mentah->program_studi->jenjang ?? '',
                ];
            }
        }
        $pegawai = $identitas !== false ? $this->osm->pegawaiId((string) $identitas['ticketIdentitas']) : false;

        // getSurat() mengembalikan array|false. Pada alur Create Surat baris
        // r_surat belum ada ($datas === false), jadi draf session HARUS
        // ditimpa di atas array — menulis properti ke false adalah Error
        // fatal di PHP 8.
        $baris = is_array($datas) ? $datas : (is_object($datas) ? (array) $datas : []);

        if ($baris !== []) {
            $pecah = explode('/', (string) ($baris['suratNomor'] ?? ''));
            if (empty($pecah[0])) {
                $baris['suratNomor'] = str_repeat('&nbsp;', 5) . ($baris['suratNomor'] ?? '');
            }
        }

        if (is_array($sesiSurat)) {
            $pejabat = explode(';', (string) $sesiSurat['suratPejabatNIPDraft']);
            $baris   = array_merge($baris, [
                'suratJenis'                 => $sesiSurat['suratJenis'],
                'suratPerihal'               => $sesiSurat['suratPerihal'],
                'suratLampiran'              => $sesiSurat['suratLampiran'],
                'suratTujuan'                => $sesiSurat['suratTujuan'],
                'suratBody'                  => $sesiSurat['suratBody'],
                'suratFooter'                => $sesiSurat['suratFooter'],
                'suratNomor'                 => $sesiSurat['suratNomor'],
                'suratTanggal'               => $sesiSurat['suratTanggal'],
                'suratPejabatJabatanAnDraft' => $sesiSurat['suratPejabatJabatanAnDraft'],
                'suratPejabatNIPDraft'       => $pejabat[0] ?? '',
                'suratPejabatJabatanDraft'   => $pejabat[1] ?? '',
                'suratPejabatNamaDraft'      => $pejabat[2] ?? '',
            ]);
            session()->remove('sess_surat');
        }

        // Template cetak_* mengakses $datas sebagai array; kosong -> false
        // agar percabangan `$datas != FALSE` di template tetap benar.
        $baris = $baris === [] ? false : $baris;
        $form  = $this->templateCetak(is_array($baris) ? ($baris['tsuratForm'] ?? null) : null);
        $mpdf  = ($form === 'cetak_5') ? new Mpdf(['format' => 'Legal-P']) : new Mpdf();
        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML(view($this->pathPage . 'cetak/' . $form, ['datas' => $baris, 'mahasiswa' => $mahasiswa, 'pegawai' => $pegawai, 'judulSurat' => '', 'isiSurat' => '']));

        $kaki = '';
        if (is_array($baris)) {
            $suratFooter = (string) ($baris['suratFooter'] ?? '');
            $kakiSurat   = (string) ($baris['footerSurat'] ?? $suratFooter);
            $kaki        = str_replace('<li>', "<li style='font-size: 8pt;'>", $suratFooter !== '' ? $suratFooter : $kakiSurat);
        }
        $mpdf->SetHTMLFooter($kaki);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="preview_' . str_replace('-', '', (string) $id) . '.pdf"')
            ->setBody($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
    }

    public function assign(string $kunci = '')
    {
        $terbuka   = $this->tiketTerotorisasi($kunci);
        $datas     = $this->tiket->byId(['ticketTrackingId' => $terbuka]);
        $disposisi = $this->tiket->disposisiById(['ticketTrackingId' => $terbuka, 'sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama']]);
        $unit      = $this->tiket->getUnitByHakakses($this->pengguna['susrSgroupNama']);
        $arsip     = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $terbuka, 'archiveJenis' => 'TIKET']);

        return $this->response->setJSON(['response' => view($this->pathPage . 'disposisi', [
            'scripts'            => [$this->pathJs . 'ticketing'],
            'save_url'           => site_url($this->controllerName . '/assigned') . '/',
            'status_page'        => 'Disposisi',
            'datas'              => $datas,
            'disposisi'          => $disposisi,
            'is_home'            => true,
            'unit'               => $unit,
            'loadpdf'            => site_url($this->controllerName . '/loadpdf') . '/',
            'user_group'         => $this->pengguna['susrSgroupNama'],
            'r_priority'         => $this->tiket->tabelRef('r_priority'),
            'page_judul'         => 'Disposisi Ticket to BO',
            '_event'             => 'assign',
            'archive_first_url'  => $arsip !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $arsip['archiveFile'] : false,
        ])]);
    }

    public function assigned()
    {
        if (! $this->validate(['ticketPriority' => 'required', 'ticketAssign' => 'required', 'ticketMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idLama     = $this->tiketMentahTerotorisasi((string) $this->request->getPost('ticketIdOld'));
        $prioritas  = (string) $this->request->getPost('ticketPriority');
        $tujuanUnit = (string) $this->request->getPost('ticketAssign');
        $pesan      = (string) $this->request->getPost('ticketMessage');
        $arsipId    = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', $idLama), ['archiveTrackingId' => $idLama]);

        $paramFile = ['archiveId' => $arsipId, 'archiveTrackingId' => $idLama, 'archiveJenis' => 'ASSIGN'];
        $param     = [
            'ticketAssign'     => $tujuanUnit,
            'ticketAssignedBy' => $this->pengguna['susrProfil'],
            'ticketAssigned'   => date('Y-m-d H:i:s'),
            'ticketStatus'     => 3,
        ];
        $paramDisposisi = [
            'disposisiTicketId'   => $idLama,
            'disposisiMessage'    => $pesan,
            'disposisiTanggal'    => date('Y-m-d H:i:s'),
            'disposisiUser'       => $this->pengguna['susrNama'],
            'disposisiUnit'       => $tujuanUnit,
            'disposisiUserProfil' => $this->pengguna['susrProfil'],
            'disposisiStatus'     => 3,
            'disposisiPriority'   => $prioritas,
        ];

        if ($this->request->getFile('ticketArchiveId') !== null && $this->request->getFile('ticketArchiveId')->getError() !== UPLOAD_ERR_NO_FILE) {
            eult_upload_ticket([
                'url'      => WRITEPATH . 'uploads/ticketing/',
                'type'     => 'pdf',
                'size'     => 15 * 1024,
                'namafile' => 'ASSIGN_' . str_replace('-', '', $idLama) . '_' . date('YmdHis'),
            ], $paramFile);
            $paramDisposisi['disposisiArchiveId'] = $arsipId;
        }

        $pekerja = $this->tiket->getTicketAssign('s_unit', ['unitId' => $tujuanUnit]);
        $proses  = $this->tiket->ubah('d_ticketing', $param, ['ticketTrackingId' => $idLama])
            && $this->tiket->tambah('d_disposisi', $paramDisposisi);

        if ($proses) {
            $namaUnit = $pekerja !== false ? $pekerja['unitNama'] . '(' . $pekerja['parentUnitNama'] . ')' : '';
            eult_save_history('Tiket Telah Diterima Oleh ' . $this->pengguna['susrProfil'] . ' dan Berkas Persyaratan Telah diserahkan Kepada Back Office Unit ' . $namaUnit, $idLama);
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function accept(string $kunci = '')
    {
        $terbuka   = $this->tiketTerotorisasi($kunci);
        $datas     = $this->tiket->byId(['ticketTrackingId' => $terbuka]);
        $disposisi = $this->tiket->disposisiById(['ticketTrackingId' => $terbuka, 'sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama']]);
        $unit      = $this->tiket->getUnitByHakakses($this->pengguna['susrSgroupNama']);
        $sgroup    = $this->tiket->tabelRef('s_user_group_unit', ['sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama'], 'sgroupunitIsHome >' => 0]);
        $arsip     = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $terbuka, 'archiveJenis' => 'TIKET']);

        $isDisposisi = $datas !== false ? ($datas['disposisiIsTrue'] ?? false) : false;
        $unitTiket   = $datas !== false ? ($datas['ticketAssign'] ?? false) : false;

        $idHome = false;
        if ($sgroup !== false) {
            foreach ($sgroup as $row) {
                $idHome[] = $row['sgroupunitUnitId'];
            }
        }

        $isHome = (($unitTiket !== false && $idHome !== false) ? ((in_array($unitTiket, $idHome) && $isDisposisi != 1) ? true : false) : false);

        return $this->response->setJSON(['response' => view($this->pathPage . 'disposisi', [
            'scripts'           => [$this->pathJs . 'ticketing'],
            'save_url'          => site_url($this->controllerName . '/accepted') . '/',
            'status_page'       => 'Disposisi',
            'datas'             => $datas,
            'disposisi'         => $disposisi,
            'unit'              => $unit,
            'sgroup'            => $sgroup,
            'is_home'           => $isHome,
            '_event'            => 'accept',
            'loadpdf'           => site_url($this->controllerName . '/loadpdf') . '/',
            'user_group'        => $this->pengguna['susrSgroupNama'],
            'r_priority'        => $this->tiket->tabelRef('r_priority'),
            'page_judul'        => 'Disposisi Ticket',
            'archive_first_url' => $arsip !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $arsip['archiveFile'] : false,
        ])]);
    }

    public function accepted()
    {
        if (! $this->validate(['ticketPriority' => 'required', 'ticketAssign' => 'required', 'ticketMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idLama     = $this->tiketMentahTerotorisasi((string) $this->request->getPost('ticketIdOld'));
        $prioritas  = (string) $this->request->getPost('ticketPriority');
        $tujuanUnit = (string) $this->request->getPost('ticketAssign');
        $pesan      = (string) $this->request->getPost('ticketMessage');
        $arsipId    = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', $idLama), ['archiveTrackingId' => $idLama]);

        $paramFile = ['archiveId' => $arsipId, 'archiveTrackingId' => $idLama, 'archiveJenis' => 'ACCEPT'];
        $terakhir  = $this->tiket->disposisiLast(['disposisiTicketId' => $idLama, 'disposisiUnit <>' => $tujuanUnit]);

        $param = [
            'ticketAssign'     => $tujuanUnit,
            'ticketAssignedBy' => $this->pengguna['susrProfil'],
            'ticketAssigned'   => date('Y-m-d H:i:s'),
            'ticketStatus'     => 3,
        ];
        $paramDisposisi = [
            'disposisiTicketId'     => $idLama,
            'disposisiMessage'      => $pesan,
            'disposisiTanggal'      => date('Y-m-d H:i:s'),
            'disposisiUser'         => $this->pengguna['susrNama'],
            'disposisiUnit'         => $tujuanUnit,
            'disposisiUserProfil'   => $this->pengguna['susrProfil'],
            'disposisiStatus'       => 3,
            'disposisiPriority'     => $prioritas,
            'disposisiPreviousUnit' => ($terakhir !== false) ? $terakhir['disposisiUnit'] : null,
        ];

        if ($this->request->getFile('ticketArchiveId') !== null && $this->request->getFile('ticketArchiveId')->getError() !== UPLOAD_ERR_NO_FILE) {
            eult_upload_ticket([
                'url'      => WRITEPATH . 'uploads/ticketing/',
                'type'     => 'pdf',
                'size'     => 15 * 1024,
                'namafile' => 'ACCEPT_' . str_replace('-', '', $idLama) . '_' . date('YmdHis'),
            ], $paramFile);
            $paramDisposisi['disposisiArchiveId'] = $arsipId;
        }

        $pekerja     = $this->tiket->getTicketAssign('s_unit', ['unitId' => $tujuanUnit]);
        $pekerjaLama = $this->tiket->getTicketAssign('s_unit', ['unitId' => $paramDisposisi['disposisiPreviousUnit']]);

        $kunciLama = [
            'disposisiTicketId' => $idLama,
            'disposisiUnit'     => (($terakhir !== false) ? $terakhir['disposisiUnit'] : '00'),
            'disposisiTanggal'  => (($terakhir !== false) ? $terakhir['disposisiTanggal'] : '00'),
        ];

        $proses = $this->tiket->ubah('d_ticketing', $param, ['ticketTrackingId' => $idLama])
            && $this->tiket->ubah('d_disposisi', ['disposisiIsTrue' => 1, 'disposisiTanggalAkhir' => date('Y-m-d H:i:s')], $kunciLama)
            && $this->tiket->tambah('d_disposisi', $paramDisposisi);

        if ($proses) {
            eult_save_history('Surat Telah Diparaf Oleh Kepala Unit ' . $pekerjaLama['unitNama'] . '(' . $pekerjaLama['parentUnitNama'] . ') dan Tiket Telah diserahkan Kepada Unit ' . $pekerja['unitNama'] . '(' . $pekerja['parentUnitNama'] . ') oleh ' . $this->pengguna['susrProfil'], $idLama);
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function validasi(string $kunci = '')
    {
        $terbuka   = $this->tiketTerotorisasi($kunci);
        $datas     = $this->tiket->byId(['ticketTrackingId' => $terbuka]);
        $disposisi = $this->tiket->disposisiById(['ticketTrackingId' => $terbuka, 'sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama']]);
        $arsip     = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $terbuka, 'archiveJenis' => 'TIKET']);

        return $this->response->setJSON(['response' => view($this->pathPage . 'tandatangan', [
            'scripts'           => [$this->pathJs . 'ticketing'],
            'save_url'          => site_url($this->controllerName . '/validated') . '/',
            'status_page'       => 'Validasi',
            'datas'             => $datas,
            'disposisi'         => $disposisi,
            '_event'            => 'validasi',
            'loadpdf'           => site_url($this->controllerName . '/loadpdf') . '/',
            'user_group'        => $this->pengguna['susrSgroupNama'],
            'page_judul'        => 'Validasi Ticket',
            'archive_first_url' => $arsip !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $arsip['archiveFile'] : false,
        ])]);
    }

    public function validated()
    {
        if (! $this->validate(['ticketMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idLama    = $this->tiketMentahTerotorisasi((string) $this->request->getPost('ticketIdOld'));
        $terakhir  = $this->tiket->disposisiLast(['disposisiTicketId' => $idLama]);
        $unitAkhir = $terakhir !== false ? $terakhir['disposisiUnit'] : '';
        $pekerja   = $this->tiket->getTicketAssign('s_unit', ['unitId' => $unitAkhir]);
        $tautan    = base_url('validitas') . '/' . $this->enkripsi->encode($idLama);

        $this->qr->generate((string) $tautan, 'TTD_' . $idLama);

        $paramSurat = [
            'suratPejabatNama'    => $pekerja['unitPejabatNama'] ?? '',
            'suratPejabatNIP'     => $pekerja['unitPejabatNIP'] ?? '',
            'suratPejabatJabatan' => $pekerja['unitPejabatJabatan'] ?? '',
        ];

        $param = [
            'ticketValidatedBy' => $this->pengguna['susrProfil'],
            'ticketValidated'   => date('Y-m-d H:i:s'),
            'ticketStatus'      => 6,
            'ticketAssign'      => $unitAkhir,
        ];

        $kunciLama = [
            'disposisiTicketId' => $idLama,
            'disposisiUnit'     => (($terakhir !== false) ? $terakhir['disposisiUnit'] : '00'),
            'disposisiTanggal'  => (($terakhir !== false) ? $terakhir['disposisiTanggal'] : '00'),
        ];

        $proses = $this->tiket->ubah('d_ticketing', $param, ['ticketTrackingId' => $idLama])
            && $this->tiket->ubah('d_disposisi', ['disposisiIsTrue' => 1, 'disposisiTanggalAkhir' => date('Y-m-d H:i:s')], $kunciLama)
            && $this->tiket->ubah('r_surat', $paramSurat, ['suratTrackingId' => $idLama]);

        if ($proses) {
            eult_save_history('Surat Telah Ditandatangani Oleh ' . ($pekerja['unitPejabatNama'] ?? '') . '(' . ($pekerja['unitPejabatJabatan'] ?? '') . ') . Permintaan menunggu persetujuan validasi oleh operator.', $idLama);
            eult_message_kirim('Pekerjaan Berhasil Divalidasi', 'success');
        }

        eult_message_kirim($this->judul . ' Terjadi Kesalahan Silakan Hubungi Administrator', 'danger');
    }

    public function detail(string $kunci = ''): string
    {
        $id    = $this->tiketTerotorisasi($kunci);
        $datas = $this->tiket->byId(['ticketTrackingId' => $id]);

        $arsip  = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $id, 'archiveJenis' => 'TIKET']);
        $output = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $id, 'archiveJenis' => 'OUTPUT']);

        $this->tiket->ubah('d_replies', ['repliesRead' => '1'], ['repliesTicketId' => $id]);

        $namaMhs = false;
        if ($datas !== false) {
            $mhs = $this->osm->mhsId2((string) $datas['ticketIdentitas']);
            if (is_object($mhs) && ! empty($mhs->nim)) {
                $namaMhs = ($mhs->peserta_didik->nama ?? '') . ' - ' . ($mhs->nim ?? '') . ' - ' . ($mhs->program_studi->nama_fakultas ?? '') . ' - ' . ($mhs->program_studi->nama ?? '') . ' - ' . ($mhs->program_studi->jenjang ?? '');
            }
        }

        $data                 = $this->getMaster($this->pathPage . 'detail');
        $data['datas']        = $datas;
        $data['archive_url']  = $arsip !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $arsip['archiveFile'] : false;
        $data['output_url']   = $output !== false ? site_url($this->controllerName . '/loadpdf') . '/' . $output['archiveFile'] : false;
        $data['history']      = $this->tiket->getHistory((string) $id);
        $data['save_url']     = site_url($this->controllerName . '/save_replies') . '/';
        $data['close_url']    = site_url($this->controllerName . '/close') . '/' . $kunci;
        $data['load_attach']  = site_url($this->controllerName . '/loadattach');
        $data['replies']      = $this->tiket->getReplies('d_replies', ['repliesTicketId' => $id]);
        $data['user_group']   = $this->pengguna;
        $data['worker']       = $this->tiket->getWorker(['workerTrackingId' => $id]);
        $data['scripts']      = [$this->pathJs . 'ticketing'];
        $data['cetakterima']  = site_url($this->controllerName . '/cetakterima') . '/' . $kunci;
        $data['mhs']          = $namaMhs;

        return view($this->template, $data);
    }

    public function rating()
    {
        $rating     = $this->request->getPost('rating');
        $nomorTiket = $this->tiketMentahTerotorisasi((string) $this->request->getPost('nomorTiket'));
        $param      = ['ratingNilai' => $rating, 'ratingTicketId' => $nomorTiket];

        $datas = $this->tiket->byId(['ticketTrackingId' => $nomorTiket]);
        $cek   = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => $nomorTiket]);

        $proses = empty($cek)
            ? $this->tiket->tambah('d_rating', $param)
            : $this->tiket->ubah('d_rating', $param, ['ratingTicketId' => $nomorTiket]);

        $output = $this->tiket->tabelBuilder('d_archive')
            ->where('archiveTrackingId', $nomorTiket)
            ->whereIn('archiveJenis', ['OUTPUT', 'TTD'])
            ->get()->getRowArray() ?? false;
        $lampiran = $output !== false ? $output['archiveFile'] : false;

        if ($datas !== false) {
            $this->email->selesai((string) $datas['ticketEmail'], 'Berkas Permintaan EULT UNMUL #' . $nomorTiket, $datas, $lampiran);
        }

        if ($proses) {
            eult_message_kirim('Terimakasih Telah Mengisi IKM, Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon Periksa Email Anda.', 'success');
        }

        eult_message_kirim('Rating gagal disimpan.', 'error');
    }

    public function terima(string $kunci = '')
    {
        if (! $this->isAjax()) {
            return $this->response->setStatusCode(400)->setBody('Bad Request');
        }

        $terbuka = $this->tiketTerotorisasi($kunci);
        $proses  = $this->tiket->ubah('d_ticketing', [
            'ticketIsVerified' => 1,
            'ticketVerified'   => date('Y-m-d H:i:s'),
            'ticketVerifiedBy' => $this->pengguna['susrProfil'],
        ], ['ticketTrackingId' => $terbuka]);

        if ($proses) {
            eult_save_history('Berkas Telah Diverifikasi oleh ' . $this->pengguna['susrProfil'], (string) $terbuka);
            eult_message_kirim($this->judul . ' Berhasil Diverifikasi', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal diverifikasi, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function tolak(string $kunci = '')
    {
        if (! $this->isAjax()) {
            return $this->response->setStatusCode(400)->setBody('Bad Request');
        }

        $terbuka = $this->tiketTerotorisasi($kunci);
        $pesan   = (string) $this->request->getPost('pesan_tolak');
        $proses  = $this->tiket->ubah('d_ticketing', [
            'ticketStatus'     => 8,
            'ticketRejected'   => date('Y-m-d H:i:s'),
            'ticketKetTolak'   => $pesan,
            'ticketRejectedBy' => $this->pengguna['susrProfil'],
        ], ['ticketTrackingId' => $terbuka]);

        if ($proses) {
            eult_save_history('Layanan telah ditolak oleh ' . $this->pengguna['susrProfil'] . '.<br/> Pesan: ' . $pesan, (string) $terbuka);
            $datas = $this->tiket->byId(['ticketTrackingId' => $terbuka]);
            if ($datas !== false) {
                $this->email->selesai((string) $datas['ticketEmail'], 'Berkas Permintaan EULT UNMUL #' . $terbuka, $datas, false);
            }
            eult_message_kirim($this->judul . ' Berhasil ditolak dan Tiket telah Selesai', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal ditolak, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function lastValidated(?string $kunci = null, ?string $mode = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        // Kunci bisa berisi "idTiket;namaBerkas" (lihat cetaksurat) — otorisasi pada id tiketnya.
        $this->tiketMentahTerotorisasi(is_string($terbuka) ? explode(';', $terbuka)[0] : '');

        if ($mode === 'sehari' || $this->request->getPost('pesanvalidasi') !== null) {
            $pesan = (string) $this->request->getPost('pesanvalidasi');

            // Unggahan dijalankan LEBIH DULU karena eult_upload_ticket()
            // memvalidasi tipe/ukuran dan membatalkan request lewat
            // eult_message_kirim(); menaikkan status sebelum gerbang itu
            // akan menandai tiket selesai walau berkasnya ditolak.
            $arsipId   = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', (string) $terbuka), ['archiveTrackingId' => $terbuka]);
            $paramFile = ['archiveId' => $arsipId, 'archiveTrackingId' => $terbuka, 'archiveJenis' => 'OUTPUT'];

            if ($this->request->getFile('ticketArchiveId') !== null && $this->request->getFile('ticketArchiveId')->getError() !== UPLOAD_ERR_NO_FILE) {
                eult_upload_ticket([
                    'url'      => WRITEPATH . 'uploads/ticketing/',
                    'type'     => 'pdf',
                    'size'     => 15 * 1024,
                    'namafile' => 'OUTPUT_' . str_replace('-', '', (string) $terbuka) . '_' . date('YmdHis'),
                ], $paramFile);
            }

            $proses = $this->tiket->ubah('d_ticketing', [
                'ticketStatus'     => 5,
                'ticketIsValidasi' => 1,
                'ticketmValidasi'  => $pesan,
            ], ['ticketTrackingId' => $terbuka]);

            if (! $proses) {
                $galat = $this->tiket->dbAktif()->error();
                eult_message_kirim($this->judul . ' Gagal divalidasi, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
            }

            eult_save_history('Layanan telah diselesaikan oleh ' . $this->pengguna['susrProfil'] . '.<br/> Pesan: ' . $pesan, (string) $terbuka);
        } else {
            $nomorSurat = (string) $this->request->getPost('nomor_surat');
            $gabung     = explode(';', (string) $terbuka);

            if (count($gabung) === 1) {
                $idTiket   = $gabung[0];
                $namaBerkas = 'TTD_' . str_replace('-', '', $idTiket) . '_' . date('YmdHis');
            } else {
                [$idTiket, $namaBerkas] = [$gabung[0], $gabung[1]];
            }

            $proses = $this->tiket->ubah('d_ticketing', ['ticketStatus' => 5, 'ticketIsValidasi' => 1], ['ticketTrackingId' => $idTiket])
                && $this->tiket->ubah('r_surat', ['suratNomor' => $nomorSurat, 'suratNomorTanggal' => date('Y-m-d')], ['suratTrackingId' => $idTiket]);

            if (! $proses) {
                $galat = $this->tiket->dbAktif()->error();
                eult_message_kirim($this->judul . ' Gagal divalidasi, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
            }

            eult_save_history('Layanan telah diselesaikan oleh ' . $this->pengguna['susrProfil'], $idTiket);
            $this->cetaksurat((string) $this->enkripsi->encode($idTiket . ';' . $namaBerkas));
            $terbuka = $idTiket;
        }

        eult_message_kirim($this->judul . ' Berhasil Divalidasi dan Tiket telah Selesai', 'success');
    }

    public function validasiEktm(string $kunci = '')
    {
        $terbuka       = $this->tiketTerotorisasi($kunci);
        $noPemohon     = (string) $this->request->getPost('ns_pemohon');
        $tglPemohon    = (string) $this->request->getPost('ts_pemohon');
        $noSurat       = (string) $this->request->getPost('ns_pengantar');
        $bank          = (string) $this->request->getPost('bank');
        $namaPejabat   = 'Enny Fathurachmi, S.IP., M.Si';
        $nipPejabat    = '197611172002122001';
        $jabatanPejabat = 'Koordinator Unit Layanan Terpadu';

        $proses = $this->tiket->ubah('d_ticketing', ['ticketStatus' => 5, 'ticketIsValidasi' => 1], ['ticketTrackingId' => $terbuka])
            && $this->tiket->tambah('r_surat', [
                'suratNomor'          => $noSurat,
                'suratNomorTanggal'   => date('Y-m-d'),
                'SuratNomorPemohon'   => $noPemohon,
                'suratTanggalPemohon' => $tglPemohon,
                'suratTrackingId'     => $terbuka,
                'suratPejabatNama'    => $namaPejabat,
                'suratPejabatNIP'     => $nipPejabat,
                'suratPejabatJabatan' => $jabatanPejabat,
                'suratBank'           => $bank,
            ]);

        // Gagal simpan harus berhenti DI SINI: riwayat, baris d_archive, QR
        // dan berkas PDF adalah efek samping yang tidak boleh ditulis untuk
        // tiket yang statusnya tidak berubah.
        if (! $proses) {
            $galat = $this->tiket->dbAktif()->error();
            eult_message_kirim($this->judul . ' Gagal divalidasi, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
        }

        eult_save_history('Layanan telah diselesaikan oleh ' . $this->pengguna['susrProfil'], (string) $terbuka);

        $namaBerkas = 'OUTPUT_' . str_replace('-', '', (string) $terbuka) . '_' . date('YmdHis');
        $arsipId    = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', (string) $terbuka), ['archiveTrackingId' => $terbuka]);

        $this->tiket->tambah('d_archive', [
            'archiveId'         => $arsipId,
            'archiveTrackingId' => $terbuka,
            'archiveJenis'      => 'OUTPUT',
            'archiveFile'       => $namaBerkas . '.pdf',
        ]);

        $identitas = $this->tiket->ambilSatu('d_ticketing', ['ticketTrackingId' => $terbuka]);
        $mentah    = $identitas !== false ? $this->osm->mhsId2((string) $identitas['ticketIdentitas']) : false;
        $mahasiswa = $mentah ? (object) [
            'nim'              => $mentah->nim ?? '',
            'name'             => $mentah->peserta_didik->nama ?? '',
            'ipk'              => $mentah->ipk ?? '',
            'faculty_name'     => $mentah->program_studi->nama_fakultas ?? '',
            'departement_name' => $mentah->program_studi->nama ?? '',
            'degree'           => $mentah->program_studi->jenjang ?? '',
        ] : false;

        $this->qr->generate(base_url('validitas') . '/' . $kunci, 'OUTPUT_' . $terbuka);

        $data = [
            'noSuratPemohon'    => $noPemohon,
            'tglSuratPemohon'   => $tglPemohon,
            'noSurat'           => $noSurat,
            'bank'              => $bank,
            'suratPejabatNama'  => $namaPejabat,
            'suratPejabatNIP'   => $nipPejabat,
            'suratPejabatJabatan' => $jabatanPejabat,
            'id'                => $terbuka,
            'mahasiswa'         => $mahasiswa,
            'datas'             => false,
        ];

        $mpdf = new Mpdf();
        $mpdf->WriteHTML(view($this->pathPage . 'cetak/cetak_ektm', $data));

        $namaFakultas = ($mahasiswa && isset($mahasiswa->faculty_name)) ? ucwords(strtolower((string) $mahasiswa->faculty_name)) : '-';
        $mpdf->SetHTMLFooter(' <table cellpadding="0" cellspacing="0">
                          <tr>
                            <td>Tembusan Yth :</td>
                          </tr>
                          <tr>
                            <td>
                                <ol>
                                  <li>Rektor Unmul (Sebagai Laporan);</li>
                                  <li>Wakil Rektor Bidang Akademik Universitas Mulawarman;</li>
                                  <li>Dekan Fakultas ' . $namaFakultas . ' Unmul;</li>
                                  <li>Pimpinan Cabang ' . $bank . ' Samarinda;</li>
                                  <li>Mahasiswa yang bersangkutan.</li>
                                </ol>
                            </td>
                          </tr>
                        </table>');
        $mpdf->Output(WRITEPATH . 'uploads/ticketing/' . $namaBerkas . '.pdf', 'F');

        eult_message_kirim($this->judul . ' Berhasil Divalidasi dan Tiket telah Selesai', 'success');
    }

    public function editSuratKtm(string $kunci = '')
    {
        $id    = $this->tiketTerotorisasi($kunci);
        $datas = $this->tiket->getSurat(['ticketTrackingId' => $id]);

        return $this->response->setJSON(['response' => view($this->pathPage . 'form_surat_ktm', [
            'page_judul'  => 'Tiket Unit Layanan Terpadu',
            'scripts'     => [$this->pathJs . 'ticketing'],
            'save_url'    => site_url($this->controllerName . '/delivered_ktm') . '/',
            'status_page' => 'Update',
            'datas'       => $datas,
            'surat'       => $datas,
            'kunci'       => $kunci,
            'user_data'   => $this->pengguna['susrSgroupNama'],
            'preview_url' => site_url('ticketing/get_preview_ktm') . '/',
        ])]);
    }

    public function saveReplies()
    {
        if (! $this->validate(['repliesMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idTiket = $this->tiketMentahTerotorisasi((string) $this->request->getPost('repliesTicketId'));

        $namaFile = '';
        $berkas   = $this->request->getFile('chatFile');
        if ($berkas !== null && $berkas->getError() !== UPLOAD_ERR_NO_FILE) {
            $pindah = eult_upload_custom([
                'url'      => WRITEPATH . 'uploads/chat/',
                'type'     => 'pdf|jpg|png',
                'size'     => 15 * 1024,
                'namafile' => 'CHAT_' . $idTiket . '_' . date('YmdHis'),
            ], 'chatFile');
            $namaFile = $pindah->getFilename();
        }

        $proses = $this->tiket->tambah('d_replies', [
            'repliesTicketId' => $idTiket,
            'repliesMessage'  => (string) $this->request->getPost('repliesMessage'),
            'repliesStatus'   => $this->pengguna['susrSgroupNama'],
            'repliesDate'     => date('Y-m-d H:i:s'),
            'repliesBy'       => $this->pengguna['susrProfil'],
            'repliesFile'     => $namaFile,
        ]);

        if ($proses) {
            return redirect()->to(site_url($this->controllerName . '/detail/' . $this->enkripsi->encode($idTiket)));
        }

        return $this->detail((string) $this->enkripsi->encode($idTiket));
    }

    public function save()
    {
        if (! $this->validate([
            'ticketCategories' => 'required', 'ticketEmail' => 'required|valid_email',
            'ticketNoHp' => 'required', 'ticketSubject' => 'required', 'ticketMessage' => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idLama = (string) $this->request->getPost('ticketIdOld');

        if ($idLama === '') {
            $kodeAcak = eult_generate_kode();
            $cek      = $this->tiket->getNumber($kodeAcak);
            $idTiket  = empty($cek)
                ? $kodeAcak . '-' . sprintf('%03d', 1)
                : $kodeAcak . '-' . sprintf('%03d', (int) substr((string) $cek['ticketTrackingId'], -3) + 1);
        } else {
            $idTiket = $this->tiketMentahTerotorisasi($idLama);
        }

        $arsipId = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', $idTiket), ['archiveTrackingId' => $idTiket]);

        $paramFile = ['archiveId' => $arsipId, 'archiveTrackingId' => $idTiket, 'archiveJenis' => 'TIKET'];
        $param     = [
            'ticketIdentitas'  => (string) $this->request->getPost('ticketIdentitas'),
            'ticketName'       => (string) $this->request->getPost('ticketName'),
            'ticketCategories' => (string) $this->request->getPost('ticketCategories'),
            'ticketEmail'      => (string) $this->request->getPost('ticketEmail'),
            'ticketNoHp'       => (string) $this->request->getPost('ticketNoHp'),
            'ticketSubject'    => (string) $this->request->getPost('ticketSubject'),
            'ticketPriority'   => (string) $this->request->getPost('ticketPriority'),
            'ticketMessage'    => (string) $this->request->getPost('ticketMessage'),
            'ticketCreated'    => date('Y-m-d H:i:s'),
            'ticketStatus'     => 1,
            'ticketCreatedBy'  => $this->pengguna['susrProfil'],
            'ticketArchiveId'  => $arsipId,
        ];

        if ($idLama === '') {
            $param['ticketTrackingId'] = $idTiket;
            $proses                    = $this->tiket->tambah('d_ticketing', $param);
            eult_save_history('Tiket Telah Dibuat Oleh ' . $this->pengguna['susrProfil'], $idTiket);

            $datas = $this->tiket->byId(['ticketTrackingId' => $idTiket]);
            if ($datas !== false) {
                $this->email->buat((string) $datas['ticketEmail'], 'Tiket EULT UNMUL #' . $idTiket, $datas);
            }
        } else {
            $param['ticketUpdated']   = date('Y-m-d H:i:s');
            $param['ticketUpdatedBy'] = $this->pengguna['susrProfil'];
            $proses                   = $this->tiket->ubah('d_ticketing', $param, ['ticketTrackingId' => $idLama]);
            eult_save_history('Tiket Telah Diubah Oleh ' . $this->pengguna['susrProfil'], $idTiket);
        }

        if ($this->request->getFile('ticketArchiveId') !== null && $this->request->getFile('ticketArchiveId')->getError() !== UPLOAD_ERR_NO_FILE) {
            eult_upload_ticket([
                'url'      => WRITEPATH . 'uploads/ticketing/',
                'type'     => 'pdf',
                'size'     => 20 * 1024,
                'namafile' => 'TIKET_' . str_replace('-', '', $idTiket) . '_' . date('YmdHis'),
            ], $paramFile);
        }

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success', base_url('ticketing/detail') . '/' . $this->enkripsi->encode($idTiket));
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(string $kunci = '')
    {
        if (! $this->isAjax()) {
            return $this->response->setStatusCode(400)->setBody('Bad Request');
        }

        $terbuka = $this->tiketTerotorisasi($kunci);
        $arsip   = $this->tiket->tabelRef('d_archive', ['archiveTrackingId' => $terbuka]);

        if ($arsip !== false) {
            foreach ($arsip as $row) {
                @unlink(WRITEPATH . 'uploads/ticketing/' . basename((string) $row['archiveFile']));
            }
        }

        $proses = $this->tiket->hapus('d_history', ['ticketTrackingIdHistory' => $terbuka])
            && $this->tiket->hapus('d_replies', ['repliesTicketId' => $terbuka])
            && $this->tiket->hapus('d_archive', ['archiveTrackingId' => $terbuka])
            && $this->tiket->hapus('d_disposisi', ['disposisiTicketId' => $terbuka]);

        $proses2 = false;
        if ($proses) {
            $proses2 = $this->tiket->hapus('d_ticketing', ['ticketTrackingId' => $terbuka]);
        }

        if (! empty($proses2)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->tiket->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    /**
     * Memeriksa hak akses staf atas tiket pemilik berkas, memakai
     * mekanisme otorisasi yang sudah dipakai controller ini
     * (grup ADMIN/OPERATOR, disposisi unit, atau unit pemilik tiket).
     */
    private function bolehAksesTiket(string $idTiket): bool
    {
        $grup = (string) ($this->pengguna['susrSgroupNama'] ?? '');

        if ($idTiket === '' || $grup === '') {
            return false;
        }

        if ($grup === 'ADMIN' || strpos($grup, 'OPERATOR') !== false) {
            return true;
        }

        if ($this->tiket->disposisiById(['ticketTrackingId' => $idTiket, 'sgroupunitSgroupNama' => $grup]) !== false) {
            return true;
        }

        $datas     = $this->tiket->byId(['ticketTrackingId' => $idTiket]);
        $unitTiket = $datas !== false ? ($datas['ticketAssign'] ?? false) : false;
        $unitGrup  = $this->tiket->getUnitByHakakses($grup);

        if ($unitTiket === false || $unitGrup === false) {
            return false;
        }

        foreach ($unitGrup as $unit) {
            if ((string) $unit['sgroupunitUnitId'] === (string) $unitTiket) {
                return true;
            }
        }

        return false;
    }

    public function loadattach(string $namaFile = '')
    {
        $namaFile = basename($namaFile);
        $lampiran = $this->tiket->ambilSatu('d_replies', ['repliesFile' => $namaFile]);

        if ($lampiran === false || ! $this->bolehAksesTiket((string) $lampiran['repliesTicketId'])) {
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        $lokasi = WRITEPATH . 'uploads/chat/' . $namaFile;

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $mime = mime_content_type($lokasi) ?: 'application/octet-stream';

        return $this->response->setHeader('Content-Type', $mime)->setBody(file_get_contents($lokasi));
    }

    public function loadpdf(string $namaFile = '')
    {
        $namaFile = basename($namaFile);
        $arsip    = $this->tiket->ambilSatu('d_archive', ['archiveFile' => $namaFile]);

        if ($arsip === false || ! $this->bolehAksesTiket((string) $arsip['archiveTrackingId'])) {
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }

        $lokasi = WRITEPATH . 'uploads/ticketing/' . $namaFile;

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        return $this->response
            ->setHeader('Content-type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaFile . '"')
            ->setBody(file_get_contents($lokasi));
    }

    public function loadimage(string $kunci = '')
    {
        $nama   = basename((string) $this->enkripsi->decode($kunci));
        $lokasi = WRITEPATH . 'uploads/qrcode/' . $nama . '.png';

        if ($nama === '' || ! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $mime = mime_content_type($lokasi) ?: 'image/png';

        return $this->response->setHeader('Content-Type', $mime)->setBody(file_get_contents($lokasi));
    }

    public function export(string $kunci = '')
    {
        $id             = $this->tiketTerotorisasi($kunci);
        [$awal, $akhir] = $this->rentangTanggal((string) session()->get('tanggal'));

        if ($awal === '') {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Rentang tanggal tidak tersedia pada sesi ini.']);
        }

        return $this->response->setJSON(
            $this->tiket->disposisiAll([
                'ticketCreated >='     => $awal . ' 00:00:00',
                'ticketCreated <='     => $akhir . ' 23:59:59',
                'sgroupunitSgroupNama' => $this->pengguna['susrSgroupNama'],
                'disposisiTicketId'    => $id,
            ])
        );
    }

    public function cetakterima(string $kunci = '')
    {
        $id    = $this->tiketTerotorisasi($kunci);
        $datas = $this->tiket->byId(['ticketTrackingId' => $id]);

        $this->qr->generate((string) (site_url($this->controllerName . '/detail') . '/' . $kunci), (string) $id);

        $mpdf = new Mpdf();
        $mpdf->WriteHTML(view($this->pathPage . 'cetak/tanda_terima', [
            'datas'        => $datas,
            'tanda_terima' => site_url('ticketing/tanda_terima'),
        ]));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="tanda_terima_' . str_replace('-', '', (string) $id) . '.pdf"')
            ->setBody($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
    }

    public function cetaksurat(string $kode = '')
    {
        $terbuka   = $this->enkripsi->decode($kode);
        $pecah     = explode(';', (string) $terbuka);
        $pecah[0]  = $this->tiketMentahTerotorisasi($pecah[0]);
        $pecah[1]  = basename((string) ($pecah[1] ?? ''));
        if ($pecah[1] === '') {
            $pecah[1] = 'TTD_' . str_replace('-', '', $pecah[0]) . '_' . date('YmdHis');
        }
        $datas     = $this->tiket->getSurat(['ticketTrackingId' => $pecah[0]]);
        $identitas = $this->tiket->ambilSatu('d_ticketing', ['ticketTrackingId' => $pecah[0]]);

        $mentah = $identitas !== false ? $this->osm->mhsId2((string) $identitas['ticketIdentitas']) : false;
        $mahasiswa = $mentah ? (object) [
            'nim'              => $mentah->nim ?? '',
            'name'             => $mentah->peserta_didik->nama ?? '',
            'ipk'              => $mentah->ipk ?? '',
            'faculty_name'     => $mentah->program_studi->nama_fakultas ?? '',
            'departement_name' => $mentah->program_studi->nama ?? '',
            'degree'           => $mentah->program_studi->jenjang ?? '',
        ] : false;
        $pegawai = $identitas !== false ? $this->osm->pegawaiId((string) $identitas['ticketIdentitas']) : false;

        $arsipId = eult_auto_increment('d_archive', 'archiveId', str_replace('-', '', $pecah[0]), ['archiveTrackingId' => $pecah[0]]);
        $this->tiket->tambah('d_archive', [
            'archiveId'         => $arsipId,
            'archiveTrackingId' => $pecah[0],
            'archiveJenis'      => 'TTD',
            'archiveFile'       => $pecah[1] . '.pdf',
        ]);

        // Template cetak_* mengakses $datas sebagai array.
        $form = $this->templateCetak(is_array($datas) ? ($datas['tsuratForm'] ?? null) : null);
        $mpdf = ($form === 'cetak_5') ? new Mpdf(['format' => 'Legal-P']) : new Mpdf();
        $mpdf->showImageErrors = true;
        $mpdf->WriteHTML(view($this->pathPage . 'cetak/' . $form, ['datas' => $datas, 'mahasiswa' => $mahasiswa, 'pegawai' => $pegawai, 'judulSurat' => '', 'isiSurat' => '']));

        $kaki = '';
        if (is_array($datas)) {
            $suratFooter = (string) ($datas['suratFooter'] ?? '');
            $kakiSurat   = (string) ($datas['footerSurat'] ?? $suratFooter);
            $kaki        = str_replace('<li>', "<li style='font-size: 8pt;'>", $suratFooter !== '' ? $suratFooter : $kakiSurat);
        }
        $mpdf->SetHTMLFooter($kaki);
        $mpdf->Output(WRITEPATH . 'uploads/ticketing/' . $pecah[1] . '.pdf', 'F');
    }

    public function saveSurat()
    {
        return $this->delivered();
    }
}
