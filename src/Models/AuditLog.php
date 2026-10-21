<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Auth;
use App\Services\Database;

final class AuditLog
{
    /** @param array<string, mixed>|null $user ใส่เมื่อยังไม่ได้ล็อกอิน (เช่น ตอนล็อกอินไม่สำเร็จ) */
    public static function record(string $action, string $details, ?array $user = null, ?string $username = null): void
    {
        $user ??= Auth::user();
        Database::query(
            'INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)',
            [$user['id'] ?? null, $user['name'] ?? $username ?? 'ระบบ', $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    }

    public static function recentFailedLogins(string $ip, int $minutes): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM audit_logs
             WHERE action = 'LoginFailed' AND ip_address = ? AND created_at > (NOW() - INTERVAL ? MINUTE)",
            [$ip, $minutes]
        );
    }
}
