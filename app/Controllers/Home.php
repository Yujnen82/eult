<?php

namespace App\Controllers;

use App\Models\ModelHome;

/**
 * Dashboard + manajemen sesi pengguna (porting CI3 Home.php).
 */
class Home extends BaseController
{
    protected ?string $judul = 'Dashboard';

    protected ?string $controllerName = 'home';

    protected ?string $pathPage = 'pages/home/';

    private ModelHome $beranda;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->beranda = new ModelHome();
    }

    public function index(): string
    {
        $data            = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts'] = [];

        // Ambil data agregat tiket untuk KPI dashboard
        $db = \Config\Database::connect();

        $totalMasuk    = 0;
        $totalProses   = 0;
        $totalSelesai  = 0;
        $totalTindakan = 0;
        $urgentTickets = [];

        try {
            // Deteksi skema tabel database (kompatibilitas nama tabel konseptual dan riil)
            $ticketTable = $db->tableExists('ticket') ? 'ticket' : ($db->tableExists('d_ticketing') ? 'd_ticketing' : null);
            $unitTable   = $db->tableExists('unit') ? 'unit' : ($db->tableExists('s_unit') ? 's_unit' : null);
            $statusTable = $db->tableExists('ticket_status') ? 'ticket_status' : ($db->tableExists('r_status') ? 'r_status' : null);

            if ($ticketTable !== null) {
                $totalMasuk    = $db->table($ticketTable)->where('ticketStatus', 1)->countAllResults();
                $totalProses   = $db->table($ticketTable)->whereIn('ticketStatus', [2, 3, 4])->countAllResults();
                $totalSelesai  = $db->table($ticketTable)->where('ticketStatus', 5)->countAllResults();
                $totalTindakan = $db->table($ticketTable)->whereIn('ticketStatus', [1, 2])->countAllResults();

                $builder = $db->table($ticketTable)
                    ->select("{$ticketTable}.ticketTrackingId, {$ticketTable}.ticketName, {$ticketTable}.ticketCreated, {$ticketTable}.ticketStatus");

                if ($unitTable !== null) {
                    $builder->select("{$unitTable}.unitNama")
                        ->join($unitTable, "{$unitTable}.unitId = {$ticketTable}.ticketAssign", 'left');
                }

                if ($statusTable !== null) {
                    $builder->select("{$statusTable}.statusNama, {$statusTable}.statusColor")
                        ->join($statusTable, "{$statusTable}.statusId = {$ticketTable}.ticketStatus", 'left');
                }

                $urgentTickets = $builder
                    ->whereIn("{$ticketTable}.ticketStatus", [1, 2])
                    ->orderBy("{$ticketTable}.ticketCreated", 'DESC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();
            }
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memuat agregat KPI dashboard: ' . $e->getMessage());
        }

        $data['total_masuk']    = $totalMasuk;
        $data['total_proses']   = $totalProses;
        $data['total_selesai']  = $totalSelesai;
        $data['total_tindakan'] = $totalTindakan;
        $data['tiket_urgent']   = $urgentTickets;
        $data['tren_mingguan']  = [
            'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'],
            'data'   => [0, 0, 0, 0, 0],
        ];

        return view($this->template, $data);
    }

    public function logout()
    {
        session()->remove('logged_in');
        session()->destroy();

        return redirect()->to(site_url('home'));
    }

    public function ubahpass(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $sesi = $this->sesiLogin() ?? [];

        if (($sesi['susrSgroupNama_ori'] ?? 'USER') === 'USER') {
            return redirect()->to(site_url('login'));
        }

        $data             = $this->getMaster($this->pathPage . 'ubahpassword');
        $data['save_url'] = site_url($this->controllerName . '/prosesubahpassword');
        $data['judul']    = 'Ubah Password';
        $data['scripts']  = [];

        return view($this->template, $data);
    }

    public function ubahhakakses(): string
    {
        $sesi = $this->sesiLogin() ?? [];

        $data             = $this->getMaster($this->pathPage . 'ubahhakakses');
        $data['save_url'] = site_url($this->controllerName . '/prosesubahhakakses');
        $data['judul']    = 'Ubah Hak Akses';
        $data['hakakses'] = $this->beranda->byId((string) ($sesi['susrNama'] ?? ''));
        $data['scripts']  = [];

        return view($this->template, $data);
    }

    public function prosesubahpassword()
    {
        $sesi = $this->sesiLogin() ?? [];

        if (! $this->validate([
            'susrPasswordOld'         => 'required',
            'susrPasswordNew'         => 'required',
            'susrPasswordNewConfirm'  => 'required',
        ])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $lama     = (string) $this->request->getPost('susrPasswordOld');
        $baru     = (string) $this->request->getPost('susrPasswordNew');
        $konfirm  = (string) $this->request->getPost('susrPasswordNewConfirm');
        $nama     = (string) ($sesi['susrNama'] ?? '');

        $cek = $this->beranda->ambilSatu('s_user', ['susrNama' => $nama]);

        if ($cek === false) {
            eult_message_kirim('Username/Password Lama Salah', 'error');
        }

        if (! password_verify($lama, (string) $cek['susrPassword'])) {
            eult_message_kirim('Username dan Password Lama Salah', 'error');
        }

        if ($baru !== $konfirm) {
            eult_message_kirim('Password Baru Tidak Sama Dengan Konfirmasi', 'error');
        }

        $proses = $this->beranda->ubah('s_user', ['susrPassword' => password_hash($baru, PASSWORD_DEFAULT)], ['susrNama' => $nama]);

        eult_message_kirim($proses ? 'Password Berhasil Diubah' : 'Password Gagal diubah', $proses ? 'success' : 'error');
    }

    public function prosesubahhakakses()
    {
        $sesi = $this->sesiLogin() ?? [];

        if (! $this->validate(['hakakses' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        if (($sesi['susrSgroupNama_ori'] ?? 'USER') === 'USER') {
            return redirect()->to(site_url('login'));
        }

        session()->set('logged_in', [
            'susrNama'           => $sesi['susrNama'],
            'susrSgroupNama'     => (string) $this->request->getPost('hakakses'),
            'susrSgroupNama_ori' => $sesi['susrSgroupNama_ori'],
            'susrProfil'         => $sesi['susrProfil'],
        ]);

        eult_message_kirim('Hak Akses Berhasil Diubah', 'success');
    }
}
