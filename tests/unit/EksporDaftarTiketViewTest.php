<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Regresi tombol "Ekspor Excel" pada daftar tiket (tanpa DB).
 *
 * Sebelumnya tombol dipasang sebagai <a href="ticketing/export"> tanpa segmen
 * kunci, padahal rutenya `export/(:any)` — tombol itu 404. Sekarang tombol
 * berupa <button> yang diikat ticketing.js ke tableExport pada
 * #table_ticketing, mengikuti pola laporan.js.
 *
 * @internal
 */
final class EksporDaftarTiketViewTest extends CIUnitTestCase
{
    public function testDaftarTiketTidakLagiMemasangHrefEksporKeEndpointJson(): void
    {
        $html = view('pages/ticketing/response', [
            'page_judul'    => 'Daftar Tiket',
            'datas'         => false,
            'user_group'    => 'ADMIN',
            'detail_url'    => site_url('ticketing/detail') . '/',
            'isProduksi'    => false,
            'isVerifikator' => false,
            'sgroup'        => false,
        ]);

        // Tombol harus <button> (ditangani ticketing.js), bukan tautan ke
        // ticketing/export yang 404 tanpa segmen kunci.
        $this->assertStringContainsString('id="btn-export"', $html);
        $this->assertStringContainsString('<button type="button"', $html);
        $this->assertStringNotContainsString('href="' . site_url('ticketing/export') . '"', $html);
        $this->assertDoesNotMatchRegularExpression('#<a[^>]+id="btn-export"#', $html);
    }

    public function testTicketingJsMengikatTombolEksporKeTableExport(): void
    {
        $js = (string) file_get_contents(FCPATH . 'assets/js/pages/custom/pages/ticketing/ticketing.js');

        $this->assertStringContainsString("'#btn-export'", $js);
        $this->assertStringContainsString('tableExport(', $js);
        $this->assertStringContainsString("$('#table_ticketing')", $js);
    }
}
