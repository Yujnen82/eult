<?php

namespace App\Controllers;

use App\Models\ModelMaster;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController EULT — pengganti CI3 application/core/MY_Controller.php.
 *
 * Menyediakan properti konvensi CI3 ($_template, $_path_page, $_judul, dst)
 * plus getMaster() setara get_master(): menu sidebar, breadcrumb +
 * otorisasi modul, info sesi, dan notifikasi tiket.
 */
abstract class BaseController extends \CodeIgniter\Controller
{
    protected ?string $template = 'layouts/template';

    protected ?string $pathPage = null;

    protected ?string $pathJs = null;

    protected ?string $judul = null;

    protected ?string $controllerName = null;

    protected ?string $modelName = 'ModelTicketing';

    protected ?string $pageIndex = 'index';

    protected ModelMaster $modelMaster;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->modelMaster = new ModelMaster();
    }

    /**
     * Data sesi login aktif (setara $this->session->userdata('logged_in')).
     *
     * @return array<string, mixed>|null
     */
    protected function sesiLogin(): ?array
    {
        $sesi = session()->get('logged_in');

        return is_array($sesi) ? $sesi : null;
    }

    /**
     * Bangun data master layout: menu, breadcrumb, info pengguna, notifikasi.
     * Setara MY_Controller::get_master($pages) di CI3.
     *
     * @return array<string, mixed>
     */
    protected function getMaster(string $pages): array
    {
        $sesi = $this->sesiLogin() ?? [];

        $namaGrup = (string) ($sesi['susrSgroupNama'] ?? '');
        $menu     = $this->modelMaster->getMenuByGroup($namaGrup);

        $data = [];

        if ($this->controllerName === 'home') {
            $data['breadcrumb'] = (object) ['susrmdgroupDisplay' => 'Dashboard', 'susrmodulNamaDisplay' => 'Dashboard'];
        } else {
            $segmen  = $this->request->getUri()->getSegments();
            $currMod = strtolower($segmen[0] ?? ($this->controllerName ?? ''));
            $cek     = $this->modelMaster->otentikasiMenu($namaGrup, $currMod);

            if ($cek === false) {
                $pages = 'layouts/error_page';
            } else {
                $data['page']       = $pages;
                $data['breadcrumb'] = (object) $cek[0];
            }
        }

        $data['uri']            = $this->request->getUri()->getSegments();
        $data['page']           = $pages;
        $data['susrNama']       = $sesi['susrNama'] ?? '';
        $data['susrSgroupNama'] = $sesi['susrSgroupNama_ori'] ?? $namaGrup;
        $data['susrProfil']     = $sesi['susrProfil'] ?? '';
        $data['menus']          = $menu;
        $data['page_judul']     = $this->judul;

        $isAdminOperator = $namaGrup === 'ADMIN' || strpos($namaGrup, 'OPERATOR') !== false;

        $data['notiftiket'] = $isAdminOperator
            ? $this->modelMaster->dataNotif('repliesRead = 0')
            : $this->modelMaster->disposisiNotif('`repliesRead` = 0 AND `sgroupunitSgroupNama` = ' . $this->modelMaster->dbAktif()->escape($namaGrup));

        $data['detail_url'] = site_url('ticketing/detail') . '/';

        return $data;
    }
}
