<?php

declare(strict_types=1);

namespace App\Services;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    /** รับ token จากฟอร์ม (_token) หรือจาก header X-CSRF-Token สำหรับ fetch() */
    public static function verify(): bool
    {
        $sent = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return is_string($sent)
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $sent);
    }
}
