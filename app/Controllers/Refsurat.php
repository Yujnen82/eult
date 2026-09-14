<?php

namespace App\Controllers;

use App\Libraries\Enkripsi;
use App\Models\ModelRefsurat;
use Mpdf\Mpdf;

/**
 * Referensi surat (porting CI3 Refsurat.php).
 */
class Refsurat extends BaseController
{
    protected ?string $judul = 'Refsurat';

    protected ?string $controllerName = 'refsurat';

    protected ?string $pathPage = 'pages/refsurat/';

    private ModelRefsurat $surat;

    private Enkripsi $enkripsi;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->surat    = new ModelRefsurat();
        $this->enkripsi = new Enkripsi();
    }

    public function index(): string
    {
        $data               = $this->getMaster($this->pathPage . $this->pageIndex);
        $data['scripts']    = [];
        $data['datas']      = $this->surat->tabelRef('r_category');
        $data['create_url'] = site_url($this->controllerName . '/create') . '/';
        $data['update_url'] = site_url($this->controllerName . '/update') . '/';
        $data['delete_url'] = site_url($this->controllerName . '/delete') . '/';

        return view($this->template, $data);
    }

    public function create(): string
    {
        $data                = $this->getMaster($this->pathPage . 'form');
        $data['scripts']     = [];
        $data['save_url']    = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Create';
        $data['datas']       = false;

        return view($this->template, $data);
    }

    public function update(string $kunci = ''): string
    {
        $terbuka             = $this->enkripsi->decode($kunci);
        $data                = $this->getMaster($this->pathPage . 'form');
        $data['scripts']     = [];
        $data['save_url']    = site_url($this->controllerName . '/save') . '/';
        $data['status_page'] = 'Update';
        $data['datas']       = $this->surat->ambilSatu('r_category', ['categoryId' => $terbuka]);

        return view($this->template, $data);
    }

    public function save()
    {
        $idLama = (string) $this->request->getPost('categoryIdOld');

        $param  = ['categoryNama' => (string) $this->request->getPost('categoryNama')];
        $proses = $idLama === ''
            ? $this->surat->tambah('r_category', $param)
            : $this->surat->ubah('r_category', $param, ['categoryId' => $idLama]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Disimpan', 'success');
        }

        $galat = $this->surat->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Disimpan, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function delete(?string $kunci = null)
    {
        $kunci ??= (string) $this->request->getPost('key');
        $terbuka = $this->enkripsi->decode($kunci);
        $proses  = $this->surat->hapus('r_category', ['categoryId' => $terbuka]);

        if (! empty($proses)) {
            eult_message_kirim($this->judul . ' Berhasil Dihapus', 'success');
        }

        $galat = $this->surat->dbAktif()->error();
        eult_message_kirim($this->judul . ' Gagal Dihapus, ' . ($galat['code'] ?? '') . ': ' . ($galat['message'] ?? ''), 'error');
    }

    public function loadpdf()
    {
        $mpdf = new Mpdf();
        $mpdf->SetHTMLHeader('<div style="text-align: right; font-weight: bold;">My document</div>');
        $mpdf->SetHTMLFooter('<table style="font-size:9pt"><tbody>
            <tr><td>1.</td><td>Dekan Fakultas Ilmu Sosial dan Ilmu Politik Unmul&nbsp;</td></tr>
            <tr><td>2.</td><td>Koordinator Program Studi Psikologi Fakultas ISIPOL UNMUL</td></tr>
            <tr><td>3.</td><td>Sub Bagian Registrasi dan Statistik BAK Unmul</td></tr>
            <tr><td>4.</td><td>Operator PDDikti Program Studi Psikologi Fakultas ISIPOL Unmul</td></tr>
            <tr><td>5.</td><td>Mahasiswa yang Bersangkutan</td></tr>
        </tbody></table>');
        $mpdf->WriteHTML('Hello World');

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="contoh_surat.pdf"')
            ->setBody($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN));
    }
}
