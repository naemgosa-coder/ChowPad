<?php

declare(strict_types=1);

namespace App\Services;

/**
 * อ่านไฟล์ .env แบบ KEY=VALUE โดยไม่ต้องพึ่ง library ภายนอก
 */
final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
                $value = $m[2];
            }

            // ค่าจาก environment จริงของเครื่องมีลำดับสูงกว่าไฟล์ .env
            if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false ? $default : $value;
    }
}
