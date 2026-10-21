<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

/**
 * การแจ้งเตือนมี 2 แบบ: ถึงผู้ใช้คนเดียว (user_id) หรือถึงกลุ่มบทบาท (audience = คีย์ใน config/roles.php, NULL = ทุกคน)
 * สถานะอ่านแล้ว/ลบเก็บแยกรายคนใน notification_states
 */
final class Notification
{
    /** @param string|null $audience กลุ่มบทบาทใน config/roles.php ใช้เมื่อ $userId เป็น null */
    public static function push(
        ?string $userId,
        string $title,
        string $description,
        string $icon = 'info',
        string $color = 'primary',
        ?string $audience = null
    ): void {
        Database::query(
            'INSERT INTO notifications (user_id, audience, title, description, icon, color) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $userId === null ? $audience : null, $title, $description, $icon, $color]
        );
    }

    /**
     * เงื่อนไข SQL ของการแจ้งเตือนที่ผู้ใช้คนนี้มองเห็น (ใช้กับ alias n และ s)
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function visibleTo(string $userId, string $role): array
    {
        $groups = array_keys(array_filter(config('roles'), static fn ($roles) => in_array($role, $roles, true)));
        $in = $groups ? implode(', ', array_fill(0, count($groups), '?')) : 'NULL';

        return [
            "(n.user_id = ? OR (n.user_id IS NULL AND (n.audience IS NULL OR n.audience IN ({$in})))) AND s.deleted_at IS NULL",
            [$userId, ...$groups],
        ];
    }

    private const FROM = 'FROM notifications n LEFT JOIN notification_states s ON s.notification_id = n.id AND s.user_id = ?';

    public static function unreadCount(string $userId, string $role): int
    {
        [$where, $params] = self::visibleTo($userId, $role);

        return (int) Database::value(
            'SELECT COUNT(*) ' . self::FROM . " WHERE {$where} AND s.read_at IS NULL AND n.is_read = 0",
            [$userId, ...$params]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(string $userId, string $role, int $limit = 100): array
    {
        [$where, $params] = self::visibleTo($userId, $role);

        return Database::all(
            'SELECT n.*, (s.read_at IS NOT NULL OR n.is_read = 1) AS is_seen ' . self::FROM
            . " WHERE {$where} ORDER BY n.created_at DESC, n.id DESC LIMIT {$limit}",
            [$userId, ...$params]
        );
    }

    /** ทำเครื่องหมายอ่านแล้ว เฉพาะรายการที่ผู้ใช้มองเห็น (null = ทั้งหมด) */
    public static function markRead(string $userId, string $role, ?int $id = null): void
    {
        self::upsertState($userId, $role, $id, 'read_at');
    }

    public static function hide(string $userId, string $role, int $id): void
    {
        self::upsertState($userId, $role, $id, 'deleted_at');
    }

    private static function upsertState(string $userId, string $role, ?int $id, string $column): void
    {
        [$where, $params] = self::visibleTo($userId, $role);
        $idSql = $id === null ? '' : ' AND n.id = ?';
        Database::query(
            "INSERT INTO notification_states (notification_id, user_id, {$column})
             SELECT n.id, ?, NOW() " . self::FROM . " WHERE {$where}{$idSql}
             ON DUPLICATE KEY UPDATE {$column} = COALESCE(notification_states.{$column}, NOW())",
            [$userId, $userId, ...$params, ...($id === null ? [] : [$id])]
        );
    }
}
