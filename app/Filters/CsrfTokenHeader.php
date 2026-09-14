<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Menyertakan token CSRF terkini pada header respons request AJAX.
 *
 * Config\Security::$regenerate = true membuat hash berganti setiap kali
 * POST tervalidasi, sehingga submit AJAX berikutnya pada halaman yang sama
 * akan ditolak bila masih memakai token lama. eult-csrf.js membaca header
 * ini dan memperbarui meta tag + hidden input csrf_field() di halaman.
 */
class CsrfTokenHeader implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! method_exists($request, 'isAJAX') || ! $request->isAJAX()) {
            return null;
        }

        $response->setHeader(csrf_header(), csrf_hash());

        return $response;
    }
}
