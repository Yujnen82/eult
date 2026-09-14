<?php

namespace Tests\Unit;

use CodeIgniter\Router\Router;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * Regresi rute alur surat/validasi tiket.
 *
 * Form surat (form_surat.php, form_surat_ktm.php), form tanda tangan
 * (tandatangan.php) dan tombol aksi tabel tiket mengirim POST ke
 * `delivered`, `delivered_ktm`, `get_preview`, `validated`,
 * `last_validated`, `terima`, `tolak`, `validasiEktm`. Sebelumnya rute
 * tersebut hanya terdaftar sebagai GET (atau tidak ada), sehingga submit
 * form berakhir 404. Aksi pengubah status juga tidak boleh tersedia via
 * GET agar selalu melewati filter CSRF global.
 *
 * @internal
 */
final class RuteSuratTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function rutePost(): array
    {
        return [
            'delivered'      => ['post', 'ticketing/delivered', '\App\Controllers\Ticketing::delivered'],
            'delivered/'     => ['post', 'ticketing/delivered/', '\App\Controllers\Ticketing::delivered'],
            'get_preview_k/' => ['post', 'ticketing/get_preview_ktm/', '\App\Controllers\Ticketing::getPreview'],
            'delivered_ktm'  => ['post', 'ticketing/delivered_ktm', '\App\Controllers\Ticketing::delivered'],
            'get_preview'    => ['post', 'ticketing/get_preview', '\App\Controllers\Ticketing::getPreview'],
            'get_preview_km' => ['post', 'ticketing/get_preview_ktm', '\App\Controllers\Ticketing::getPreview'],
            'validated'      => ['post', 'ticketing/validated', '\App\Controllers\Ticketing::validated'],
            'last_validated' => ['post', 'ticketing/last_validated/abc', '\App\Controllers\Ticketing::lastValidated/abc'],
            'last_val_sehr'  => ['post', 'ticketing/last_validated/abc/sehari', '\App\Controllers\Ticketing::lastValidated/abc/sehari'],
            'terima'         => ['post', 'ticketing/terima/abc', '\App\Controllers\Ticketing::terima/abc'],
            'tolak'          => ['post', 'ticketing/tolak/abc', '\App\Controllers\Ticketing::tolak/abc'],
            'validasiEktm'   => ['post', 'ticketing/validasiEktm/abc', '\App\Controllers\Ticketing::validasiEktm/abc'],
            'save_replies'   => ['post', 'cektiket/save_replies/abc', '\App\Controllers\Cektiket::saveReplies/abc'],
            'preview (GET)'  => ['get', 'ticketing/preview/abc', '\App\Controllers\Ticketing::preview/abc'],
        ];
    }

    /**
     * @dataProvider rutePost
     */
    public function testRuteFormSuratTerdaftarDenganMetodeYangBenar(string $metode, string $uri, string $handler): void
    {
        $router = $this->routerUntuk($metode);

        $router->handle($uri);

        $this->assertSame($handler, $router->controllerName() . '::' . $router->methodName() . ($router->params() !== [] ? '/' . implode('/', $router->params()) : ''));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function ruteGetDilarang(): array
    {
        return [
            'terima'         => ['ticketing/terima/abc'],
            'tolak'          => ['ticketing/tolak/abc'],
            'validated'      => ['ticketing/validated/abc'],
            'last_validated' => ['ticketing/last_validated/abc'],
            'validasiEktm'   => ['ticketing/validasiEktm/abc'],
            'delivered'      => ['ticketing/delivered/abc'],
        ];
    }

    /**
     * @dataProvider ruteGetDilarang
     */
    public function testAksiPengubahStatusTidakTersediaViaGet(string $uri): void
    {
        $router = $this->routerUntuk('get');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);

        $router->handle($uri);
    }

    private function routerUntuk(string $metode): Router
    {
        $routes = Services::routes(false);
        $routes->loadRoutes();
        $routes->setHTTPVerb($metode);

        $request = Services::incomingrequest(null, false);
        $request->setMethod($metode);

        return new Router($routes, $request);
    }
}
