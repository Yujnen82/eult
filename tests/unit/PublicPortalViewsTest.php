<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Pengujian integrasi tampilan portal publik E-ULT.
 * Memastikan 3 Gateway Card Interaktif (Card-as-Button) beranimasi dan
 * navigasi kembali (in-place stage swap) terpasang dengan standar aksesibilitas tinggi.
 *
 * @internal
 */
final class PublicPortalViewsTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testPublicPortalRendersThreeAnimatedGatewayCards(): void
    {
        $result = $this->get('/');
        $result->assertOK();

        $body = $result->getBody();

        // 1. Verifikasi keberadaan kontainer kartu gerbang layanan
        $this->assertStringContainsString('eult-gateway-cards', $body);

        // 2. Verifikasi 3 Kartu Interaktif Dominan untuk Publik (Card as a Button)
        $this->assertStringContainsString('card-trigger-create', $body);
        $this->assertStringContainsString('Ajukan Tiket Layanan', $body);

        $this->assertStringContainsString('card-trigger-track', $body);
        $this->assertStringContainsString('Lacak Status Tiket', $body);

        $this->assertStringContainsString('card-trigger-whatsapp', $body);
        $this->assertStringContainsString('Chat WhatsApp ULT', $body);
        $this->assertStringContainsString('wa.me/628115809970', $body);
        $this->assertStringContainsString('eult-portal-card--whatsapp', $body);

        // 3. Verifikasi Akses Petugas & Admin yang Dibuat Tidak Dominan (Secondary Staff Banner)
        $this->assertStringContainsString('eult-staff-banner', $body);
        $this->assertStringContainsString('card-trigger-signin', $body);
        $this->assertMatchesRegularExpression('/Akses Masuk Petugas (&|&amp;) Administrator/', $body);

        // 4. Verifikasi aksesibilitas keyboard (role="button", tabindex="0", aria-label)
        $this->assertStringContainsString('role="button"', $body);
        $this->assertStringContainsString('tabindex="0"', $body);
        $this->assertStringContainsString('aria-label=', $body);

        // 5. Verifikasi tombol navigasi kembali ke menu layanan di panggung formulir
        $this->assertStringContainsString('Kembali ke Menu Layanan', $body);
        $this->assertStringContainsString('eult-back-to-gateway', $body);
    }
}
