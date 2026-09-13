<?php

/**
 * Helper upload EULT.
 * Porting dari CI3 application/helpers/uploadfile_helper.php dan
 * uploadcustom_helper.php. CI3 memakai library upload internal;
 * di CI4 dipakai $request->getFile() + validasi manual agar
 * perilaku (tipe, ukuran, nama file) tetap sama.
 */

if (! function_exists('eult_upload_ticket')) {
    /**
     * Mengunggah lampiran tiket (field 'ticketArchiveId') lalu
     * mencatatnya ke d_archive via ModelMaster.
     *
     * @param array{url:string,type:string,size:int,namafile:string} $konfig
     */
    function eult_upload_ticket(array $konfig, array $paramArsip): bool
    {
        $request = service('request');
        $berkas  = $request->getFile('ticketArchiveId');

        if ($berkas === null || $berkas->getError() === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        if (! $berkas->isValid()) {
            eult_message_kirim(strip_tags($berkas->getErrorString()), 'error');
        }

        $tujuan = rtrim($konfig['url'], '/') . '/';
        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        // Validasi ekstensi sesuai allowed_types CI3 (mis. 'pdf' atau 'pdf|jpg|png')
        $diizinkan = explode('|', strtolower($konfig['type']));
        $ekstensi  = strtolower($berkas->getExtension());
        if (! in_array($ekstensi, $diizinkan, true)) {
            eult_message_kirim('Tipe file tidak diizinkan. Hanya: ' . $konfig['type'], 'error');
        }

        // Validasi ukuran (CI3 max_size dalam KB)
        if ($berkas->getSizeByUnit('kb') > $konfig['size']) {
            eult_message_kirim('Ukuran file melebihi batas ' . round($konfig['size'] / 1024) . ' MB.', 'error');
        }

        $namaBaru                    = $konfig['namafile'] . '.' . $ekstensi;
        $berkas->move($tujuan, $namaBaru, true);
        $paramArsip['archiveFile'] = $namaBaru;

        $model = new \App\Models\ModelMaster();

        return $model->tambah('d_archive', $paramArsip);
    }
}

if (! function_exists('eult_upload_custom')) {
    /**
     * Unggah generik untuk field arbitrer (porting uploadcustom CI3).
     * Mengembalikan instance File yang sudah dipindah.
     *
     * @param array{url:string,type:string,size:int,namafile:string} $konfig
     */
    function eult_upload_custom(array $konfig, string $namaField): \CodeIgniter\Files\File
    {
        $request = service('request');
        $berkas  = $request->getFile($namaField);

        if ($berkas === null || ! $berkas->isValid()) {
            $pesan = $berkas instanceof \CodeIgniter\HTTP\Files\UploadedFile
                ? $berkas->getErrorString()
                : 'File tidak ditemukan.';
            eult_message_kirim(strip_tags($pesan), 'error');
        }

        $tujuan = rtrim($konfig['url'], '/') . '/';
        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        $diizinkan = explode('|', strtolower($konfig['type']));
        $ekstensi  = strtolower($berkas->getExtension());
        if (! in_array($ekstensi, $diizinkan, true)) {
            eult_message_kirim('Tipe file tidak diizinkan. Hanya: ' . $konfig['type'], 'error');
        }

        if ($berkas->getSizeByUnit('kb') > $konfig['size']) {
            eult_message_kirim('Ukuran file melebihi batas ' . round($konfig['size'] / 1024) . ' MB.', 'error');
        }

        $berkas->move($tujuan, $konfig['namafile'] . '.' . $ekstensi, true);

        return $berkas;
    }
}
