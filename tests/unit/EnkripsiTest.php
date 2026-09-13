<?php

use App\Libraries\Enkripsi;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Test enkripsi tiket EULT.
 * Prioritas: URL lama (cektiket/xxx, validitas/xxx) dari CI3
 * harus tetap bisa dibuka di CI4.
 *
 * @internal
 */
final class EnkripsiTest extends CIUnitTestCase
{
    private Enkripsi $enkripsi;

    /**
     * Vektor asli hasil CI_Encryption 3.1.13 dengan kunci legacy
     * untuk plaintext 'ABCD-1234-001'.
     */
    private string $vektorCi3 = 'YmJmODY3NmIyMmI3OGQ0NDg4YzhmOTk0NDQyMzcxNjQzZTQzNDkyNDc4ZWRkMmViMTkwMzc3M2JlNGM3YmI0ODcwNGQ0NmRjY2I2MjQ5MTRjZDEyMjdlZWUzZGFiYTRjNjk5ZWJlZmFkZTg5OGNlYThhZjc2ZTY5ZTg2NzM0ZjUyQVI1Y0pSVmFRelJ6c1d4QmxWYm1sRklTbEYyUW02bmdmQXdiTEREN3NNPQ';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enkripsi = new Enkripsi();
    }

    public function testDecodeVektorAsliCi3(): void
    {
        $hasil = $this->enkripsi->decode($this->vektorCi3, 'SuPer_Enc-Key2010');

        $this->assertSame('ABCD-1234-001', $hasil);
    }

    public function testRoundtripEncodeDecode(): void
    {
        $asli  = 'WXYZ-9999-042';
        $kode  = $this->enkripsi->encode($asli);
        $buka  = $this->enkripsi->decode((string) $kode);

        $this->assertSame($asli, $buka);
    }

    public function testHasilEncodeAmanUntukUrl(): void
    {
        $kode = (string) $this->enkripsi->encode('ABCD-1234-001');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $kode);
    }

    public function testDecodeDataRusakGagal(): void
    {
        $this->assertFalse($this->enkripsi->decode('data-rusak-xxx', 'SuPer_Enc-Key2010'));
        $this->assertFalse($this->enkripsi->decode('', 'SuPer_Enc-Key2010'));
    }
}
