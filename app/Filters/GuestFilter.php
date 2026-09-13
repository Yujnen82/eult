<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter tamu EULT — pengalihan sebaliknya dari AuthFilter.
 * Pengguna yang sudah login dan membuka halaman login diarahkan
 * ke dashboard (setara redirect('home') di Login::__construct CI3).
 */
class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_array(session()->get('logged_in'))) {
            return redirect()->to(site_url('home'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
