<?php

namespace App\Libraries;

use Config\Services;

/**
 * Pengirim email EULT (porting CI3 application/libraries/Sendemail.php).
 * Konfigurasi SMTP diambil dari .env (EULT_MAIL_*) via Config\Email,
 * bukan password hardcoded seperti di CI3.
 */
class PengirimEmail
{
    /**
     * Konfigurasi email efektif: Config\Email ditimpa .env bila ada.
     *
     * @return array<string, mixed>
     */
    private function konfigurasi(): array
    {
        $config  = config('Email');
        $baca    = static fn (string $kunci, string $bawaan = ''): string => ($v = env($kunci)) !== null && $v !== '' ? (string) $v : $bawaan;

        return [
            'dari'  => $baca('EULT_MAIL_FROM', $config->fromEmail),
            'nama'  => $baca('EULT_MAIL_NAME', $config->fromName),
        ];
    }

    /**
     * Mengirim email HTML generik.
     */
    public function kirimText(string $email, string $subjek, string $pesan): bool
    {
        $emailService = Services::email();
        $konfig       = $this->konfigurasi();

        $emailService->setFrom($konfig['dari'], $konfig['nama']);
        $emailService->setTo($email);
        $emailService->setSubject($subjek);
        $emailService->setMessage($pesan);

        return $emailService->send();
    }

    /**
     * Mengirim email + lampiran dari folder ticketing.
     */
    public function kirimLampiran(string $email, string $subjek, string $pesan, string|false $lampiran): bool
    {
        $emailService = Services::email();
        $konfig       = $this->konfigurasi();

        $emailService->setFrom($konfig['dari'], $konfig['nama']);
        $emailService->setTo($email);
        $emailService->setSubject($subjek);

        if ($lampiran !== false && $lampiran !== '') {
            $berkas = WRITEPATH . 'uploads/ticketing/' . $lampiran;
            if (is_file($berkas)) {
                $emailService->attach($berkas);
            }
        }

        $emailService->setMessage($pesan);

        return $emailService->send();
    }

    /**
     * Email tiket baru (memakai view layouts/template_surat_create).
     */
    public function buat(string $email, string $subjek, mixed $param): bool
    {
        return $this->kirimText($email, $subjek, view('layouts/template_surat_create', ['datas' => $param]));
    }

    /**
     * Email tiket selesai + lampiran (view layouts/template_surat_validated).
     */
    public function selesai(string $email, string $subjek, mixed $param, string|false $lampiran): bool
    {
        return $this->kirimLampiran($email, $subjek, view('layouts/template_surat_validated', ['datas' => $param]), $lampiran);
    }
}
