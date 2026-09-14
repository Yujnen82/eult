<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\ResponsableInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Menghentikan alur controller dengan respons siap kirim (pengganti `exit` pada
 * porting CI3 message_helper). CodeIgniter::run() menangkap
 * ResponsableInterface dan mengirim respons ini apa adanya, sehingga filter
 * `after` (secureheaders, toolbar) tetap berjalan dan alur dapat diuji
 * in-process oleh FeatureTestTrait tanpa mematikan proses PHPUnit.
 */
final class ResponsAwalException extends RuntimeException implements ResponsableInterface
{
    public function __construct(private readonly ResponseInterface $respons)
    {
        parent::__construct('Respons dikirim lebih awal (sebelum controller selesai).');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->respons;
    }
}
