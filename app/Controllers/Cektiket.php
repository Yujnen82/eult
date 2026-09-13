<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Libraries\PengirimEmail;
use App\Models\ModelTicketing;
use Mpdf\Mpdf;

/**
 * Lacak tiket publik EULT (porting CI3 Cektiket.php).
 */
class Cektiket extends BaseController
{
    protected ?string $judul = 'Cek Tiket';

    protected ?string $controllerName = 'cektiket';

    private ModelTicketing $tiket;

    private Enkripsi $enkripsi;

    private PengirimEmail $email;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->tiket    = new ModelTicketing();
        $this->enkripsi = new Enkripsi();
        $this->email    = new PengirimEmail();
    }

    public function index(string $kunci = ''): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $nomorTiket = $this->enkripsi->decode($kunci);

        if (empty($nomorTiket)) {
            return redirect()->to(site_url('login'));
        }

        $datas = $this->tiket->byId(['ticketTrackingId' => $nomorTiket]);

        if ($datas === false) {
            return redirect()->to(site_url('login'));
        }

        $balasan = $this->tiket->getReplies('d_replies', ['repliesTicketId' => $nomorTiket]);
        $riwayat = $this->tiket->getHistory((string) $nomorTiket);
        $output  = $this->tiket->ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND archiveJenis = 'OUTPUT'");

        return view('pages/ticketing/detail_user', [
            'page_judul'  => 'Cek Tiket',
            'datas'       => $datas,
            'history'     => $riwayat,
            'output_url'  => $output !== false ? site_url('cektiket/loadpdf') . '/' . $output['archiveFile'] : false,
            'save_url'    => site_url('cektiket/save_replies') . '/',
            'close_url'   => site_url('cektiket/close') . '/' . $kunci,
            'load_attach' => site_url('cektiket/loadattach'),
            'replies'     => $balasan,
            'user_group'  => $datas['ticketName'],
            'breadcrumb'  => 'cektiket',
            'cetakterima' => site_url('cektiket/cetakterima') . '/' . $kunci,
        ]);
    }

    public function saveReplies()
    {
        if (! $this->validate(['repliesMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $idTiket = (string) $this->request->getPost('repliesTicketId');

        $konfig = [
            'url'      => WRITEPATH . 'uploads/chat/',
            'type'     => 'pdf|jpg|png',
            'size'     => 15 * 1024,
            'namafile' => 'CHAT_' . $idTiket . '_' . date('YmdHis'),
        ];

        $namaFile = '';
        $berkas   = $this->request->getFile('chatFile');
        if ($berkas !== null && $berkas->getError() !== UPLOAD_ERR_NO_FILE) {
            $pindah   = eult_upload_custom($konfig, 'chatFile');
            $namaFile = $pindah->getFilename();
        }

        $proses = $this->tiket->tambah('d_replies', [
            'repliesTicketId' => $idTiket,
            'repliesMessage'  => (string) $this->request->getPost('repliesMessage'),
            'repliesStatus'   => 'USER',
            'repliesDate'     => date('Y-m-d H:i:s'),
            'repliesBy'       => $this->request->getIPAddress(),
            'repliesFile'     => $namaFile,
            'repliesRead'     => '0',
        ]);

        if ($proses) {
            return redirect()->to(base_url('cektiket/index/' . $this->enkripsi->encode($idTiket)));
        }

        return $this->index((string) $this->enkripsi->encode($idTiket));
    }

    public function rating()
    {
        $rating     = $this->request->getPost('rating');
        $nomorTiket = (string) $this->request->getPost('nomorTiket');
        $param      = ['ratingNilai' => $rating, 'ratingTicketId' => $nomorTiket];

        $datas = $this->tiket->byId("ticketTrackingId = '" . $nomorTiket . "'");
        $cek   = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => $nomorTiket]);

        $proses = empty($cek)
            ? $this->tiket->tambah('d_rating', $param)
            : $this->tiket->ubah('d_rating', $param, ['ratingTicketId' => $nomorTiket]);

        $output = $this->tiket->ambilSatu('d_archive', "archiveTrackingId = '" . $nomorTiket . "' AND (archiveJenis = 'OUTPUT' or archiveJenis = 'TTD')");
        $lampiran = $output !== false ? $output['archiveFile'] : false;

        if ($datas !== false) {
            $this->email->selesai((string) $datas['ticketEmail'], 'Berkas Permintaan EULT UNMUL #' . $nomorTiket, $datas, $lampiran);
        }

        if ($proses) {
            eult_message_kirim('Terimakasih Telah Mengisi IKM, Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon Periksa Email Anda.', 'success');
        }
    }

    public function cetakterima(string $kunci = '')
    {
        $id    = $this->enkripsi->decode($kunci);
        $datas = $this->tiket->byId(['ticketTrackingId' => $id]);

        $mpdf = new Mpdf();
        $mpdf->WriteHTML(view('pages/ticketing/cetak/tanda_terima', [
            'datas'        => $datas,
            'tanda_terima' => site_url('ticketing/tanda_terima'),
        ]));
        $mpdf->Output();
        exit;
    }

    public function loadpdf(string $namaFile = '')
    {
        $lokasi = WRITEPATH . 'uploads/ticketing/' . basename($namaFile);

        return $this->response
            ->setHeader('Content-type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($namaFile) . '"')
            ->setBody(file_exists($lokasi) ? file_get_contents($lokasi) : '');
    }

    public function loadattach(string $namaFile = '')
    {
        $lokasi = WRITEPATH . 'uploads/chat/' . basename($namaFile);

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $mime = mime_content_type($lokasi) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody(file_get_contents($lokasi));
    }
}
