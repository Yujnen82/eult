<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Halaman utama = form login + buat/lacak tiket (setara default_controller=login CI3)
$routes->get('/', 'Login::index');

// Auth publik
$routes->group('login', static function ($routes) {
    $routes->get('/', 'Login::index');
    $routes->post('cektiket', 'Login::cektiket');
    $routes->post('savetiket', 'Login::savetiket');
    $routes->post('getIdentitas', 'Login::getIdentitas');
    $routes->post('getLayanan', 'Login::getLayanan');
    $routes->post('getSyarat', 'Login::getSyarat');
    $routes->get('refresh_captcha', 'Login::refreshCaptcha');
    $routes->get('captcha_image', 'Login::captchaImage');
});

$routes->group('otentifikasi', static function ($routes) {
    $routes->post('/', 'Otentifikasi::index');
    $routes->post('index', 'Otentifikasi::index');
});

// Lacak tiket publik (tautan terenkripsi dari email)
$routes->group('cektiket', static function ($routes) {
    $routes->get('index/(:any)', 'Cektiket::index/$1');
    $routes->post('save_replies', 'Cektiket::saveReplies');
    $routes->post('save_replies/(:any)', 'Cektiket::saveReplies/$1');
    $routes->post('rating/(:any)', 'Cektiket::rating/$1');
    $routes->get('cetakterima/(:any)', 'Cektiket::cetakterima/$1');
    $routes->get('loadpdf/(:any)', 'Cektiket::loadpdf/$1');
    $routes->get('loadattach/(:segment)/(:segment)', 'Cektiket::loadattach/$1/$2');
});

// Validasi surat publik via QR (setara $route['validitas/(:any)'] CI3)
$routes->group('validitas', static function ($routes) {
    $routes->get('loadpdf/(:any)', 'Validitas::loadpdf/$1');
    $routes->get('(:any)', 'Validitas::index/$1');
});

// Area terproteksi (dijaga filter 'auth')
$routes->group('home', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Home::index');
    $routes->get('index', 'Home::index');
    $routes->get('logout', 'Home::logout');
    $routes->get('ubahpass', 'Home::ubahpass');
    $routes->post('prosesubahpassword', 'Home::prosesubahpassword');
    $routes->get('ubahhakakses', 'Home::ubahhakakses');
    $routes->post('prosesubahhakakses', 'Home::prosesubahhakakses');
});

$routes->group('ticketing', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Ticketing::index');
    $routes->post('response', 'Ticketing::response');
    $routes->get('create', 'Ticketing::create');
    $routes->get('update/(:any)', 'Ticketing::update/$1');
    $routes->post('save', 'Ticketing::save');
    $routes->post('delete/(:any)', 'Ticketing::delete/$1');
    $routes->get('detail/(:any)', 'Ticketing::detail/$1');
    $routes->post('save_replies', 'Ticketing::saveReplies');
    $routes->post('rating', 'Ticketing::rating');
    $routes->get('create_surat/(:any)', 'Ticketing::createSurat/$1');
    $routes->get('edit_surat/(:any)', 'Ticketing::editSurat/$1');
    // Form surat (form_surat.php / form_surat_ktm.php) mengirim POST ke
    // save_url = delivered/ dan delivered_ktm/; tombol Preview mengirim
    // POST ke get_preview lalu membuka preview/(:any) via GET.
    $routes->post('save_surat', 'Ticketing::saveSurat');
    $routes->post('delivered', 'Ticketing::delivered');
    $routes->post('delivered_ktm', 'Ticketing::delivered');
    $routes->get('preview/(:any)', 'Ticketing::preview/$1');
    $routes->post('get_preview', 'Ticketing::getPreview');
    $routes->post('get_preview_ktm', 'Ticketing::getPreview');
    $routes->get('cetaksurat/(:any)', 'Ticketing::cetaksurat/$1');
    $routes->get('assign/(:any)', 'Ticketing::assign/$1');
    $routes->post('assigned', 'Ticketing::assigned');
    $routes->get('accept/(:any)', 'Ticketing::accept/$1');
    $routes->post('accepted', 'Ticketing::accepted');
    // Aksi pengubah status tiket hanya lewat POST agar dilindungi filter CSRF.
    $routes->post('terima/(:any)', 'Ticketing::terima/$1');
    $routes->post('tolak/(:any)', 'Ticketing::tolak/$1');
    $routes->get('validasi/(:any)', 'Ticketing::validasi/$1');
    $routes->post('validated', 'Ticketing::validated');
    $routes->post('last_validated/(:any)', 'Ticketing::lastValidated/$1');
    $routes->post('validasiEktm/(:any)', 'Ticketing::validasiEktm/$1');
    $routes->get('edit_surat_ktm/(:any)', 'Ticketing::editSuratKtm/$1');
    $routes->get('loadattach/(:any)', 'Ticketing::loadattach/$1');
    $routes->get('loadpdf/(:any)', 'Ticketing::loadpdf/$1');
    $routes->get('loadimage/(:any)', 'Ticketing::loadimage/$1');
    $routes->get('export/(:any)', 'Ticketing::export/$1');
    $routes->get('cetakterima/(:any)', 'Ticketing::cetakterima/$1');
    $routes->post('getLayanan', 'Ticketing::getLayanan');
    $routes->post('getIdentitas', 'Ticketing::getIdentitas');
});

$routes->group('validasifile', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Validasifile::index');
    $routes->post('get_stats_ajax', 'Validasifile::getStatsAjax');
    $routes->post('get_data_ajax', 'Validasifile::getDataAjax');
    $routes->post('get_quarantine_ajax', 'Validasifile::getQuarantineAjax');
    $routes->post('get_manifest_ajax', 'Validasifile::getManifestAjax');
    $routes->post('proses_karantina', 'Validasifile::prosesKarantina');
    $routes->post('proses_restore', 'Validasifile::prosesRestore');
    $routes->post('proses_delete', 'Validasifile::prosesDelete');
    $routes->post('restore', 'Validasifile::restore');
    $routes->post('delete', 'Validasifile::delete');
    $routes->get('preview/(:segment)/(:any)/(:segment)', 'Validasifile::preview/$1/$2/$3');
    $routes->get('preview/(:segment)/(:any)', 'Validasifile::preview/$1/$2');
});

$routes->group('laporan', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Laporan::index');
    $routes->post('response', 'Laporan::response');
});

$routes->group('laporanlayanan', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Laporanlayanan::index');
    $routes->post('response', 'Laporanlayanan::response');
});

// Master referensi
$routes->group('refkategori', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Refkategori::index');
    $routes->get('create', 'Refkategori::create');
    $routes->post('edit', 'Refkategori::edit');
    $routes->post('show', 'Refkategori::show');
    $routes->get('add_sub/(:any)', 'Refkategori::addSub/$1');
    $routes->get('edit_sub/(:any)', 'Refkategori::editSub/$1');
    $routes->post('save_sub', 'Refkategori::saveSub');
    $routes->get('update/(:any)', 'Refkategori::update/$1');
    $routes->post('save', 'Refkategori::save');
    $routes->post('delete/(:any)', 'Refkategori::delete/$1');
});

$routes->group('refsurat', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Refsurat::index');
    $routes->get('create', 'Refsurat::create');
    $routes->get('update/(:any)', 'Refsurat::update/$1');
    $routes->post('save', 'Refsurat::save');
    $routes->post('delete/(:any)', 'Refsurat::delete/$1');
    $routes->get('loadpdf/(:any)', 'Refsurat::loadpdf/$1');
});

$routes->group('refsyarat', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Refsyarat::index');
    $routes->get('create', 'Refsyarat::create');
    $routes->get('update/(:any)', 'Refsyarat::update/$1');
    $routes->post('save', 'Refsyarat::save');
    $routes->post('delete/(:any)', 'Refsyarat::delete/$1');
});

$routes->group('unit', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Unit::index');
    $routes->get('create', 'Unit::create');
    $routes->get('update/(:any)', 'Unit::update/$1');
    $routes->post('save', 'Unit::save');
    $routes->post('delete/(:any)', 'Unit::delete/$1');
});

$routes->group('modul', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Modul::index');
    $routes->get('create', 'Modul::create');
    $routes->get('update/(:any)', 'Modul::update/$1');
    $routes->post('save', 'Modul::save');
    $routes->post('delete/(:any)', 'Modul::delete/$1');
});

$routes->group('modulgroup', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Modulgroup::index');
    $routes->get('create', 'Modulgroup::create');
    $routes->get('update/(:any)', 'Modulgroup::update/$1');
    $routes->post('save', 'Modulgroup::save');
    $routes->post('delete/(:any)', 'Modulgroup::delete/$1');
});

// RBAC
$routes->group('hakakses', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakakses::index');
    $routes->get('create', 'Hakakses::create');
    $routes->get('update/(:any)', 'Hakakses::update/$1');
    $routes->post('save', 'Hakakses::save');
    $routes->post('delete/(:any)', 'Hakakses::delete/$1');
});

$routes->group('hakaksesmodul', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakaksesmodul::index');
    $routes->post('response', 'Hakaksesmodul::response');
    $routes->post('save', 'Hakaksesmodul::save');
});

$routes->group('hakaksesunit', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakaksesunit::index');
    $routes->post('response', 'Hakaksesunit::response');
    $routes->post('save', 'Hakaksesunit::save');
});

$routes->group('hakakseslayanan', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakakseslayanan::index');
    $routes->post('response', 'Hakakseslayanan::response');
    $routes->post('save', 'Hakakseslayanan::save');
});

$routes->group('hakaksespengguna', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakaksespengguna::index');
    $routes->post('response', 'Hakaksespengguna::response');
    $routes->post('save', 'Hakaksespengguna::save');
});

$routes->group('hakaksesuser', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Hakaksesuser::index');
});

$routes->group('pengguna', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Pengguna::index');
    $routes->get('create', 'Pengguna::create');
    $routes->get('update/(:any)', 'Pengguna::update/$1');
    $routes->post('save', 'Pengguna::save');
    $routes->post('delete/(:any)', 'Pengguna::delete/$1');
    $routes->post('resetpassword', 'Pengguna::resetpassword');
    $routes->post('resetpassword/(:any)', 'Pengguna::resetpassword/$1');
});
