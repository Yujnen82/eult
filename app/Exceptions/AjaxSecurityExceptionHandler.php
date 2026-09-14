<?php

namespace App\Exceptions;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use Throwable;

/**
 * Task 18.3 (Klaster 3 / T1): Graceful handler untuk kegagalan CSRF pada
 * request AJAX (bugfix.md 1.17, 2.24).
 *
 * ═══════════════════════════════════════════════════════════════════
 * LATAR BELAKANG — apa yang sebenarnya terjadi pada kode SEBELUM fix
 * ini (diverifikasi empiris via curl ke server dev live, BUKAN asumsi):
 * ═══════════════════════════════════════════════════════════════════
 * Task 18.1 mengaktifkan filter global `csrf`
 * (`CodeIgniter\Filters\CSRF`), yang memanggil `Security::verify()` dan
 * melempar `CodeIgniter\Security\Exceptions\SecurityException` (403,
 * `SecurityException::forDisallowedAction()`) ketika token CSRF
 * hilang/tidak valid — KARENA `Config\Security::$redirect =
 * (ENVIRONMENT === 'production')` dan server dev ini berjalan dengan
 * `CI_ENVIRONMENT=development`, `$redirect` bernilai `false`, sehingga
 * SecurityException benar-benar dilempar (bukan redirect senyap).
 *
 * `CodeIgniter\Debug\Exceptions::exceptionHandler()` menangkap exception
 * tersebut dan memanggil `Config\Exceptions::handler($statusCode,
 * $exception)` untuk memperoleh instance `ExceptionHandlerInterface`,
 * lalu memanggil `->handle(...)` pada instance tersebut — SATU-SATUNYA
 * titik ekstensi resmi CI4 untuk override penanganan exception per-jenis
 * (dikonfirmasi dari pembacaan langsung
 * vendor/codeigniter4/framework/system/Debug/Exceptions.php).
 *
 * Handler BAWAAN (`CodeIgniter\Debug\ExceptionHandler::handle()`) SUDAH
 * memeriksa `! str_contains($request->getHeaderLine('accept'),
 * 'text/html')` untuk memutuskan antara jalur HTML (`production.php`
 * dengan cangkang lengkap `<!doctype html>...Whoops!`) vs jalur non-HTML
 * (via `ResponseTrait::respond()`, `Content-Type: application/json`).
 * NAMUN untuk request AJAX jQuery `$.ajax()` TANPA `dataType: 'json'`
 * eksplisit (persis pola yang dipakai `detail_user.php` untuk submit
 * rating — lihat blok `$.ajax({ type: 'POST', url: ratingUrl, ... })`
 * TANPA `dataType`), browser mengirim `Accept: (wildcard)` — BUKAN
 * `text/html`, sehingga jalur non-HTML tetap terpilih (Content-Type
 * JSON benar), TAPI karena `display_errors` OFF di server ini (kondisi
 * production-like), `$data` yang diformat adalah STRING KOSONG (''),
 * menghasilkan body respons `""` (2 byte, HANYA tanda kutip kosong) —
 * BUKAN halaman HTML penuh seperti diasumsikan semula, namun TETAP
 * TIDAK BERGUNA sama sekali bagi client-side JS yang mengharapkan objek
 * JSON `{status, message, ...}` (dikonfirmasi empiris via curl:
 * `POST cektiket/rating/{kunci-tidak-valid}` dengan header
 * `X-Requested-With: XMLHttpRequest` → HTTP 403, Content-Type
 * `application/json; charset=UTF-8`, body literal `""`). Callback
 * `error: () => { tampilkanToast('Gagal menyimpan penilaian...') }`
 * pada `detail_user.php` HANYA menampilkan pesan generik fallback,
 * kehilangan konteks BAHWA kegagalan ini spesifik disebabkan CSRF
 * (berbeda dari kegagalan validasi/server lain) — inilah bug condition
 * 1.17/2.24 yang diperbaiki handler ini: mengganti body `""` yang tidak
 * berguna dengan objek JSON graceful `{'status': 'danger', 'message':
 * '...'}` (kontrak IDENTIK dengan `Login::savetiket()`/`Login::
 * cektiket()` pada jalur error lain — lihat app/Controllers/Login.php
 * baris ~111-113, `return $this->response->setJSON(['status' =>
 * 'danger', 'message' => ...])`).
 *
 * Untuk request NON-AJAX (browser navigasi form biasa, `Accept:
 * text/html,...`), handler ini TIDAK melakukan apa pun — sepenuhnya
 * mendelegasikan ke `CodeIgniter\Debug\ExceptionHandler` bawaan
 * (halaman HTML error `production.php`/`error_exception.php` tetap
 * tampil PERSIS seperti sebelum fix ini, TIDAK diubah — preservasi
 * Requirement 2.24: "solusi ini SHALL diupayakan tanpa menambahkan URI
 * apa pun ke pengecualian CSRF", TIDAK menyentuh perilaku default sama
 * sekali kecuali kombinasi spesifik AJAX+SecurityException).
 *
 * ═══════════════════════════════════════════════════════════════════
 * DETEKSI AJAX/JSON — DUA sinyal independen (OR), sesuai instruksi
 * task 18.3:
 * ═══════════════════════════════════════════════════════════════════
 * 1. `$request->isAJAX()` — memeriksa header `X-Requested-With:
 *    XMLHttpRequest` (CodeIgniter\HTTP\IncomingRequest::isAJAX()) —
 *    sinyal yang SUDAH dikirim SELURUH pemanggilan `$.ajax()`/jQuery
 *    existing di codebase ini (login/savetiket, login/cektiket,
 *    cektiket/save_replies, cektiket/rating — dikonfirmasi via
 *    pencarian `X-Requested-With` di seluruh app/Views).
 * 2. Header `Accept` mengandung `application/json` — sinyal tambahan
 *    untuk client (mis. panggilan `fetch()` masa depan, atau klien API
 *    non-browser) yang secara eksplisit meminta format JSON tanpa
 *    header `X-Requested-With`.
 *
 * Handler ini HANYA aktif bila SALAH SATU sinyal di atas true DAN
 * exception adalah `SecurityException` — kombinasi kondisi yang identik
 * dengan _Bug_Condition_ pada task 18.3: "request AJAX tanpa token CSRF
 * valid".
 */
final class AjaxSecurityExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @param CodeIgniter\HTTP\CLIRequest|CodeIgniter\HTTP\IncomingRequest $request
     */
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        $mengharapkanJson = method_exists($request, 'isAJAX') && $request->isAJAX();
        $mengharapkanJson = $mengharapkanJson || str_contains(strtolower($request->getHeaderLine('accept')), 'application/json');

        if (! $mengharapkanJson) {
            // Bukan request AJAX/JSON — SEPENUHNYA delegasikan ke
            // handler bawaan CI4, TIDAK ADA perubahan perilaku sama
            // sekali (halaman HTML error existing tetap tampil).
            (new \CodeIgniter\Debug\ExceptionHandler(config('Exceptions')))
                ->handle($exception, $request, $response, $statusCode, $exitCode);

            return;
        }

        try {
            $response->setStatusCode($statusCode);
        } catch (\CodeIgniter\HTTP\Exceptions\HTTPException) {
            $statusCode = 500;
            $response->setStatusCode($statusCode);
        }

        // Kontrak JSON graceful — IDENTIK dengan pola error existing
        // Login::savetiket()/Login::cektiket() ({'status': 'danger',
        // 'message': '...'}), agar client-side JS existing (yang sudah
        // menangani respons berbentuk objek {status, message} pada
        // jalur error lain) dapat menangani respons ini tanpa perubahan
        // tambahan (Preservation Requirement 2.24: "kontrak tidak
        // berubah").
        $response->setJSON([
            'status'  => 'danger',
            'message' => 'Sesi keamanan (CSRF) Anda tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.',
        ])->send();

        if (ENVIRONMENT !== 'testing') {
            // @codeCoverageIgnoreStart
            exit($exitCode);
            // @codeCoverageIgnoreEnd
        }
    }
}
