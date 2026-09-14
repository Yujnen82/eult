<?php

/**
 * Helper tombol aksi tabel ticketing.
 * Porting dari CI3 application/helpers/getaction_helper.php.
 * Logika peran (ADMIN / OPERATOR / DISPOSISI / KASUBBAG) dipertahankan
 * persis; hanya akses CI ($CI->session, model, encryptions) diganti
 * service dan class CI4.
 */

if (! function_exists('eult_tombol_aksi')) {
    /**
     * Mencetak HTML tombol aksi untuk satu baris tiket.
     *
     * @param array{key:string,urut:string|int,jenis:string|null,layanan:string,layananId?:string,suratCreated:bool|string,status:int|string,isVerified:int|string,disposisiIsTrue:bool|string,isSehari:int|string,ishome:bool,isRejected?:int|string} $param
     */
    function eult_tombol_aksi(array $param = []): void
    {
        $sesi           = session()->get('logged_in');
        $susrSgroupNama = is_array($sesi) ? ($sesi['susrSgroupNama'] ?? '') : ($param['user_group'] ?? '');
        $noSurat        = '';

        try {
            $tiket     = new \App\Models\ModelTicketing();
            $enkripsi  = new \App\Libraries\Enkripsi();
            $idSurat   = $enkripsi->decode($param['key']);
            $dataSurat = $tiket->ambilSatu('r_surat', ['suratTrackingId' => (string) $idSurat]);
            $noSurat   = $dataSurat !== false && $dataSurat !== null ? ($dataSurat['suratNomor'] ?? '') : '';
        } catch (\Throwable $e) {
            $noSurat = '';
        }

        $isOperator    = strpos($susrSgroupNama, 'OPERATOR');
        $isProduksi    = strpos($susrSgroupNama, 'KASUBBAG');
        $isDisposisi   = (strpos($susrSgroupNama, 'DISPOSISI') !== false);
        $isVerifikator = strpos($susrSgroupNama, 'KASUBBAG');

        $urlValidasi = $param['jenis'] === 'BOTTOMUP'
            ? (' <a href="' . site_url('ticketing/last_validated') . '/' . $param['key'] . '" data-toggle="modal" title="Validasi Tiket" data-target="#nomor_surat"  data-val="' . $noSurat . '" data-linkkeys="' . site_url('ticketing/last_validated') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">')
            : ($param['layanan'] === '116'
                ? (' <a href="' . site_url('ticketing/validasiEktm') . '/' . $param['key'] . '" data-toggle="modal" title="Validasi Tiket" data-target="#ektm" data-linkkeys="' . site_url('ticketing/validasiEktm') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">')
                : (' <a href="' . site_url('ticketing/last_validated') . '/' . $param['key'] . '" data-toggle="modal" data-target="#pesan_validasi" data-linkkeys="' . site_url('ticketing/last_validated') . '/' . $param['key'] . '/sehari" title="Validasi Tiket" class="btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">'));

        $isSurat    = ((($param['jenis'] ?? null) === 'BOTTOMUP' && $param['suratCreated'] === false) || empty($param['jenis'])) ? true : false;
        $isVerified = ((($param['isVerified'] ?? null) == 1 && ($param['jenis'] ?? null) === 'BOTTOMUP') || empty($param['jenis'])) ? true : false;

        $action = '';
        if ($susrSgroupNama === 'ADMIN') {
            $action .= '
            <a href="' . site_url('ticketing/update') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Edit" id="ts_update_row' . $param['urut'] . '" class="ts_update_row btn btn-sm btn-outline-info btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-pencil-alt"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/delete') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Delete" id="ts_remove_row' . $param['urut'] . '" class="ts_remove_row btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-trash-alt"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/terima') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Verifikasi" id="ts_terima_row' . $param['urut'] . '" class="ts_terima_row btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-check"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" data-toggle="modal" data-target="#pesan_tolak" title="Tolak Permohonan" data-linkkeys="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
            <span>
            <i class="flaticon-cancel"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/assign') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Assign" id="ts_assign_row' . $param['urut'] . '" class="ts_assign_row btn btn-sm btn-outline-brand btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-thumbs-up"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/create_surat') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Buat Surat" id="ts_deliver_row' . $param['urut'] . '" class="ts_deliver_row btn btn-sm btn-outline-secondary btn-elevate btn-circle btn-icon">
            <span>
            <i class="la la-envelope"></i>
            </span>
            </a> <br/>
            <a href="' . site_url('ticketing/edit_surat') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Edit Surat" id="ts_delivered_row' . $param['urut'] . '" class="ts_delivered_row btn btn-sm btn-outline-brand btn-elevate btn-circle btn-icon">
            <span>
            <i class="flaticon-edit"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/preview') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Lihat Surat" id="ts_preview_row' . $param['urut'] . '" class="ts_preview_row btn btn-sm btn-outline-secondary btn-elevate btn-circle btn-icon" target="_blank">
            <span>
            <i class="la la-eye"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/accept') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Paraf dan Disposisi" id="ts_accept_row' . $param['urut'] . '" class="ts_accept_row btn btn-sm btn-outline-warning btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-play"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/validasi') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Tanda Tangan" id="ts_validate_row' . $param['urut'] . '" class="ts_validate_row btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-file-signature"></i>
            </span>
            </a>
            ' . $urlValidasi . '
            <span>
            <i class="la la-check"></i>
            </span>
            </a>
            <a href="' . site_url('ticketing/export') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Export"  class="btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">
            <span>
            <i class="fa fa-file-excel"></i>
            </span>
            </a>
            ';
        } else {
            if ($isOperator !== false) {
                if ($param['status'] == 1) {
                    if ($param['isSehari'] == 2) {
                        $action .= '
                        <a href="' . site_url('ticketing/assign') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Assign" id="ts_assign_row' . $param['urut'] . '" class="ts_assign_row btn btn-sm btn-outline-brand btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="fa fa-thumbs-up"></i>
                        </span>
                        </a>';
                    }
                    $action .= '
                    <a href="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" data-toggle="modal" data-target="#pesan_tolak" title="Tolak Permohonan" data-linkkeys="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
                    <span>
                    <i class="flaticon-cancel"></i>
                    </span>
                    </a>
                    <a href="' . site_url('ticketing/update') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Edit" id="ts_update_row' . $param['urut'] . '" class="ts_update_row btn btn-sm btn-outline-info btn-elevate btn-circle btn-icon">
                    <span>
                    <i class="fa fa-pencil-alt"></i>
                    </span>
                    </a>
                    <a href="' . site_url('ticketing/delete') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Delete" id="ts_remove_row' . $param['urut'] . '" class="ts_remove_row btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
                    <span>
                    <i class="fa fa-trash-alt"></i>
                    </span>
                    </a>';
                }
                if ($param['isSehari'] != 1 && in_array($param['status'], [5, 6], false)) {
                    if (($param['jenis'] ?? null) === 'BOTTOMUP') {
                        $action .= ' <a href="' . site_url('ticketing/preview') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Lihat Surat" id="ts_preview_row' . $param['urut'] . '" class="ts_preview_row btn btn-sm btn-outline-secondary btn-elevate btn-circle btn-icon" target="_blank">
                        <span>
                        <i class="la la-eye"></i>
                        </span>
                        </a>';
                    }
                    if ($param['status'] == 6) {
                        $action .= $urlValidasi . '
                        <span>
                        <i class="la la-check"></i>
                        </span>
                        </a>';
                    }
                }
                if ($param['isSehari'] != 1 && $param['status'] == 3) {
                    $action .= $urlValidasi . '
                        <span>
                        <i class="la la-check"></i>
                        </span>
                        </a>';
                }
            } else {
                if (($isDisposisi !== false || $isVerifikator !== false) && $param['disposisiIsTrue'] == false && $param['isSehari'] == 2 && ((($param['jenis'] ?? null) === 'BOTTOMUP' && $isVerified && $isSurat)) && $param['ishome']) {
                    $action .= ' <a href="' . site_url('ticketing/validasi') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Tanda Tangan" id="ts_validate_row' . $param['urut'] . '" class="ts_validate_row btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="fa fa-file-signature"></i>
                        </span>
                        </a>
                        ';
                }

                if (($isDisposisi !== false) || ($isVerifikator !== false && $isVerified && $isSurat) && $param['isSehari'] == 2) {
                    $action .= ' <a href="' . site_url('ticketing/accept') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Paraf dan Disposisi" id="ts_accept_row' . $param['urut'] . '" class="ts_accept_row btn btn-sm btn-outline-warning btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="fa fa-play"></i>
                        </span>
                        </a>';
                }

                // Verifikasi di kasubbag
                if ($isVerifikator !== false && $param['isVerified'] != 1 && ($param['jenis'] ?? null) === 'BOTTOMUP' && $param['ishome']) {
                    $action .= ' <a href="' . site_url('ticketing/terima') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Verifikasi" id="ts_terima_row' . $param['urut'] . '" class="ts_terima_row btn btn-sm btn-outline-success btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="fa fa-check"></i>
                        </span>
                        </a>
                        <a href="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" data-toggle="modal" data-target="#pesan_tolak" title="Tolak Permohonan" data-linkkeys="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="flaticon-cancel"></i>
                        </span>
                        </a>';
                } elseif ($isVerifikator !== false && $param['isSehari'] == 2 && $param['ishome']) {
                    $action .= '
                        <a href="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" data-toggle="modal" data-target="#pesan_tolak" title="Tolak Permohonan" data-linkkeys="' . site_url('ticketing/tolak') . '/' . $param['key'] . '" class="btn btn-sm btn-outline-danger btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="flaticon-cancel"></i>
                        </span>
                        </a>';
                }

                // Buat surat di kasubbag
                if ($isProduksi !== false && $param['isVerified'] == 1 && $param['suratCreated'] !== false) {
                    $action .= ' <a href="' . site_url('ticketing/create_surat') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Buat Surat" id="ts_deliver_row' . $param['urut'] . '" class="ts_deliver_row btn btn-sm btn-outline-secondary btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="la la-envelope"></i>
                        </span>
                        </a>';
                }

                // Edit Surat di kasubbag
                if ($isProduksi !== false && $param['isVerified'] == 1 && $param['suratCreated'] === false && $param['ishome']) {
                    $action .= ' <a href="' . site_url('ticketing/edit_surat') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Edit Surat" id="ts_delivered_row' . $param['urut'] . '" class="ts_delivered_row btn btn-sm btn-outline-brand btn-elevate btn-circle btn-icon">
                        <span>
                        <i class="flaticon-edit"></i>
                        </span>
                        </a>';
                }

                // Preview Surat
                if ($param['suratCreated'] === false && ($param['jenis'] ?? null) === 'BOTTOMUP') {
                    $action .= ' <a href="' . site_url('ticketing/preview') . '/' . $param['key'] . '" data-toggle="kt-tooltip" title="Lihat Surat" id="ts_preview_row' . $param['urut'] . '" class="ts_preview_row btn btn-sm btn-outline-secondary btn-elevate btn-circle btn-icon" target="_blank">
                        <span>
                        <i class="la la-eye"></i>
                        </span>
                        </a>';
                }
            }

            if ($param['isSehari'] != 2 && $param['status'] != 5 && $param['status'] != 8) {
                $action .= $urlValidasi . '
                <span>
                <i class="la la-check"></i>
                </span>
                </a>';
            }
            if ($param['isSehari'] == 2 && ($param['jenis'] ?? null) !== 'BOTTOMUP' && ($isProduksi !== false)) {
                $action .= $urlValidasi . '
                <span>
                <i class="la la-check"></i>
                </span>
                </a>';
            }
        }
        echo $action;
    }
}

if (! function_exists('getaction')) {
    /**
     * Alias getaction untuk kompatibilitas pemanggilan view antrean tiket.
     *
     * @param array{key:string,urut:string|int,jenis:string|null,layanan:string,layananId?:string,suratCreated:bool|string,status:int|string,isVerified:int|string,disposisiIsTrue:bool|string,isSehari:int|string,ishome:bool,isRejected?:int|string} $param
     */
    function getaction(array $param = []): void
    {
        eult_tombol_aksi($param);
    }
}

