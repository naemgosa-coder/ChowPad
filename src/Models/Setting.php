<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class Setting
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Database::value('SELECT value FROM settings WHERE `key` = ?', [$key]);

        return $value === null ? $default : json_decode((string) $value, true);
    }

    public static function set(string $key, mixed $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE)]
        );
    }

    /** @return array{start: string, end: string} เวลาทำการ HH:MM */
    public static function workingHours(): array
    {
        return self::get('working_hours', []) + ['start' => '08:30', 'end' => '16:30'];
    }

    /** เวลาทำการแบบข้อความ "08:30–16:30" (MySQL เรียงคีย์ JSON ใหม่ จึงห้ามใช้ implode กับผลของ workingHours) */
    public static function workingHoursText(): string
    {
        $h = self::workingHours();

        return "{$h['start']}–{$h['end']}";
    }
}
