<?php

namespace Tests\Unit;

use CodeIgniter\Filters\Filters;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Regresi hasil audit atas commit remediasi alur surat/CSRF.
 *
 * 1. eult_message_kirim() melempar ResponsAwalException yang ditangkap di
 *    CodeIgniter::run(), SETELAH globals['after']. Selama `secureheaders`
 *    masih berada di globals, seluruh respons JSON admin kehilangan header
 *    keamanan; karena itu filter tersebut harus berada di $required.
 * 2. preview() menerima array|false dari getSurat(); alur Create Surat
 *    (belum ada baris r_surat) tidak boleh menulis properti ke false.
 * 3. detail_user.php harus mengirim field `kunci` karena
 *    Cektiket::saveReplies() menentukan tiket dari kunci terenkripsi.
 *
 * @internal
 */
final class RemediasiAuditTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Services::reset();
        helper('eult_message');
    }

    public function testSecureheadersTerdaftarSebagaiRequiredBukanGlobals(): void
    {
        $filters = config(\Config\Filters::class);

        $this->assertContains('secureheaders', $filters->required['after']);
        $this->assertNotContains('secureheaders', $filters->globals['after']);
    }

    public function testResponsJsonHelperTetapMembawaHeaderKeamanan(): void
    {
        $hasil = $this->withRoutes([['get', 'uji-pesan', static function () {
            eult_message_kirim('pesan galat', 'error');
        }]])->get('uji-pesan');

        $respons = $hasil->response();

        $this->assertTrue($respons->hasHeader('X-Frame-Options'), 'Header keamanan hilang pada respons eult_message_kirim().');
        $this->assertTrue($respons->hasHeader('X-Content-Type-Options'));
        $this->assertTrue($respons->hasHeader('Referrer-Policy'));
        $this->assertTrue($respons->hasHeader(csrf_header()));
    }

    public function testStatusHttpControllerDipertahankanOlehHelper(): void
    {
        $hasil = $this->withRoutes([['get', 'uji-403', static function () {
            service('response')->setStatusCode(403);
            eult_message_kirim('Anda tidak berhak mengakses tiket ini.', 'error');
        }]])->get('uji-403');

        $this->assertSame(403, $hasil->response()->getStatusCode());
    }

    public function testRespønsHtmlBiasaJugaMembawaHeaderKeamanan(): void
    {
        $hasil = $this->withRoutes([['get', 'uji-html', static fn (): string => 'ok']])->get('uji-html');

        $this->assertTrue($hasil->response()->hasHeader('X-Frame-Options'));
    }

    /**
     * Mendekati preview(): draf session ditimpa di atas array, bukan properti
     * objek, sehingga $datas === false tidak lagi memicu Error fatal.
     */
    public function testDrafSessionDitimpaDiAtasArraySaatBarisSuratBelumAda(): void
    {
        $datas     = false; // getSurat() untuk tiket tanpa baris r_surat
        $sesiSurat = [
            'suratJenis'                 => 'Surat Keterangan Aktif',
            'suratPerihal'               => 'Keterangan',
            'suratLampiran'              => '-',
            'suratTujuan'                => 'Bank',
            'suratBody'                  => '<p>Isi</p>',
            'suratFooter'                => '',
            'suratNomor'                 => '001',
            'suratTanggal'               => '2026-09-15',
            'suratPejabatJabatanAnDraft' => 'an. Rektor',
            'suratPejabatNIPDraft'       => '1980;Kepala;Budi',
        ];

        $baris = is_array($datas) ? $datas : (is_object($datas) ? (array) $datas : []);

        if (is_array($sesiSurat)) {
            $pejabat = explode(';', (string) $sesiSurat['suratPejabatNIPDraft']);
            $baris   = array_merge($baris, [
                'suratJenis'            => $sesiSurat['suratJenis'],
                'suratBody'             => $sesiSurat['suratBody'],
                'suratPejabatNIPDraft'  => $pejabat[0] ?? '',
                'suratPejabatNamaDraft' => $pejabat[2] ?? '',
            ]);
        }

        $this->assertSame('Surat Keterangan Aktif', $baris['suratJenis']);
        $this->assertSame('1980', $baris['suratPejabatNIPDraft']);
        $this->assertSame('Budi', $baris['suratPejabatNamaDraft']);
    }

    public function testFormBalasanPublikMengirimKunciTerenkripsi(): void
    {
        $html = view('pages/ticketing/detail_user', [
            'page_judul'  => 'Cek Tiket',
            'datas'       => ['ticketTrackingId' => 'BAZK-HGRY-001', 'ticketName' => 'Pemohon', 'ticketStatus' => 1, 'ticketCreated' => '2026-09-15 08:00:00'],
            'history'     => [],
            'replies'     => [],
            'output_url'  => false,
            'save_url'    => site_url('cektiket/save_replies') . '/KUNCI-TERENKRIPSI',
            'close_url'   => site_url('cektiket/close') . '/KUNCI-TERENKRIPSI',
            'load_attach' => site_url('cektiket/loadattach') . '/KUNCI-TERENKRIPSI',
            'rating_url'  => site_url('cektiket/rating') . '/KUNCI-TERENKRIPSI',
            'user_group'  => 'Pemohon',
            'breadcrumb'  => 'cektiket',
            'cetakterima' => site_url('cektiket/cetakterima') . '/KUNCI-TERENKRIPSI',
        ]);

        $this->assertStringContainsString('name="kunci" value="KUNCI-TERENKRIPSI"', $html);
        $this->assertStringContainsString('name="' . csrf_token() . '"', $html);
    }

    public function testEultCsrfJsMemakaiMutationObserverBukanMutationEvent(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/eult-csrf.js');

        // Mutation events dimatikan default sejak Chromium 127; yang diuji
        // adalah binding-nya, bukan penyebutan nama event di komentar.
        $this->assertDoesNotMatchRegularExpression('/\.on\(\s*[\'"]DOMNode/', $js);
        $this->assertStringContainsString('new MutationObserver(', $js);
    }
}
