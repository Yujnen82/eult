<?php

namespace App\Filters;

use App\Models\ModelMaster;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter auth EULT — pengganti MY_Controller::__construct() CI3.
 *
 * Menolak request tanpa sesi 'logged_in' ke halaman login, dan
 * menolak akses modul tanpa hak baca (otentifikasi_menu) dengan 403.
 * Diterapkan ke rute terproteksi via app/Config/Filters.php.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $sesi = session()->get('logged_in');

        if (! is_array($sesi)) {
            return redirect()->to(site_url('login'));
        }

        // Segmen pertama = nama modul (home, ticketing, ...).
        $segmen = $request->getUri()->getSegments();
        $modul  = strtolower($segmen[0] ?? 'home');

        $model = new ModelMaster();
        $cek   = $model->otentikasiMenu((string) ($sesi['susrSgroupNama'] ?? ''), $modul);

        if ($modul !== 'home' && $cek === false) {
            return service('response')->setStatusCode(403)->setBody(view('layouts/error_page', [
                'page_judul' => 'Akses Ditolak',
            ]));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
