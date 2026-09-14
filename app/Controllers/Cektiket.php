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
        $output  = $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $nomorTiket, 'archiveJenis' => 'OUTPUT']);

        return view('pages/ticketing/detail_user', [
            'page_judul'  => 'Cek Tiket',
            'datas'       => $datas,
            'history'     => $riwayat,
            'output_url'  => $output !== false ? site_url('cektiket/loadpdf') . '/' . $kunci : false,
            'save_url'    => site_url('cektiket/save_replies') . '/' . $kunci,
            'close_url'   => site_url('cektiket/close') . '/' . $kunci,
            'load_attach' => site_url('cektiket/loadattach') . '/' . $kunci,
            'rating_url'  => site_url('cektiket/rating') . '/' . $kunci,
            'replies'     => $balasan,
            'user_group'  => $datas['ticketName'],
            'breadcrumb'  => 'cektiket',
            'cetakterima' => site_url('cektiket/cetakterima') . '/' . $kunci,
        ]);
    }

    /**
     * Balasan pemohon. Tiket ditentukan dari kunci terenkripsi (URL/field
     * `kunci`), bukan dari nomor tiket mentah, agar pemohon lain tidak bisa
     * menyisipkan balasan atau memperoleh tautan tiket orang lain.
     */
    public function saveReplies(string $kunci = '')
    {
        if ($kunci === '') {
            $kunci = (string) $this->request->getPost('kunci');
        }

        $idTiket = $this->enkripsi->decode($kunci);
        $datas   = (is_string($idTiket) && $idTiket !== '') ? $this->tiket->byId(['ticketTrackingId' => $idTiket]) : false;

        if ($datas === false) {
            $this->response->setStatusCode(403);
            eult_message_kirim('Kunci tiket tidak valid.', 'error');
        }

        if (! $this->validate(['repliesMessage' => 'required'])) {
            eult_message_kirim('Ooops!! Something Wrong!!', 'error');
        }

        $konfig = [
            'url'      => WRITEPATH . 'uploads/chat/',
            'type'     => 'pdf',
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
            return redirect()->to(base_url('cektiket/index/' . $kunci));
        }

        return $this->index($kunci);
    }

    public function rating(string $kunci = '')
    {
        $nomorTiket = $this->enkripsi->decode($kunci);
        $datas      = (is_string($nomorTiket) && $nomorTiket !== '') ? $this->tiket->byId(['ticketTrackingId' => $nomorTiket]) : false;

        if ($datas === false) {
            $this->response->setStatusCode(403);
            eult_message_kirim('Kunci tidak valid.', 'error');
        }

        $rating = $this->request->getPost('rating');
        $param  = ['ratingNilai' => $rating, 'ratingTicketId' => $nomorTiket];

        $cek = $this->tiket->ambilSatu('d_rating', ['ratingTicketId' => $nomorTiket]);

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
            $this->response->setHeader(csrf_header(), csrf_hash());
            eult_message_kirim('Terimakasih Telah Mengisi IKM, Untuk layanan dengan permintaan berkas, berkas telah kami kirimkan via email. Mohon Periksa Email Anda.', 'success');
        }

        eult_message_kirim('Rating gagal disimpan.', 'error');
    }

    public function cetakterima(string $kunci = '')
    {
        $id    = $this->enkripsi->decode($kunci);
        $datas = (is_string($id) && $id !== '') ? $this->tiket->byId(['ticketTrackingId' => $id]) : false;

        if ($datas === false) {
            return $this->response->setStatusCode(404)->setBody('Tiket tidak ditemukan.');
        }

        $mpdf = new Mpdf();
        $mpdf->WriteHTML(view('pages/ticketing/cetak/tanda_terima', [
            'datas'        => $datas,
            'tanda_terima' => site_url('ticketing/tanda_terima'),
        ]));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="tanda_terima_' . str_replace('-', '', (string) $id) . '.pdf"')
            ->setBody($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
    }

    /**
     * Menyajikan PDF output milik tiket yang kuncinya dipegang pemohon.
     * Nama berkas ditentukan dari relasi tiket (bukan dari URL) agar
     * berkas tiket lain tidak bisa diunduh tanpa kunci.
     */
    public function loadpdf(string $kunci = '')
    {
        $nomorTiket = $this->enkripsi->decode($kunci);
        $arsip      = $nomorTiket === false
            ? false
            : $this->tiket->ambilSatu('d_archive', ['archiveTrackingId' => $nomorTiket, 'archiveJenis' => 'OUTPUT']);

        if ($arsip === false) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $lokasi = WRITEPATH . 'uploads/ticketing/' . basename((string) $arsip['archiveFile']);

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        return $this->response
            ->setHeader('Content-type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($lokasi) . '"')
            ->setBody(file_get_contents($lokasi));
    }

    /**
     * Menyajikan lampiran chat hanya bila berkas tersebut benar-benar
     * terasosiasi dengan tiket hasil decode kunci.
     */
    public function loadattach(string $kunci = '', string $namaFile = '')
    {
        $nomorTiket = $this->enkripsi->decode($kunci);
        $namaFile   = basename($namaFile);
        $lampiran   = ($nomorTiket === false || $namaFile === '')
            ? false
            : $this->tiket->ambilSatu('d_replies', ['repliesTicketId' => $nomorTiket, 'repliesFile' => $namaFile]);

        if ($lampiran === false) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $lokasi = WRITEPATH . 'uploads/chat/' . $namaFile;

        if (! is_file($lokasi)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan.');
        }

        $mime = mime_content_type($lokasi) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody(file_get_contents($lokasi));
    }
}
