<?php

declare(strict_types=1);

namespace App\Services;

/** โยนจาก controller เพื่อหยุดและแสดงหน้าข้อผิดพลาดตามรหัส HTTP (403, 404, ...) */
final class HttpException extends \RuntimeException
{
    public function __construct(int $status)
    {
        parent::__construct('HTTP ' . $status, $status);
    }
}
