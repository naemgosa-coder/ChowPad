<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class LeaveRequest
{
    private const SELECT = 'SELECT l.*, t.code AS type_code, t.name AS type_name, u.name AS user_name, d.name AS department,
                                   ap.name AS approver_name
                            FROM leave_requests l
                            JOIN leave_types t ON t.id = l.leave_type_id
                            JOIN users u ON u.id = l.user_id
                            LEFT JOIN departments d ON d.id = u.department_id
                            LEFT JOIN users ap ON ap.id = l.approved_by';

    /** @return array<int, array<string, mixed>> */
    public static function types(): array
    {
        return Database::all('SELECT * FROM leave_types ORDER BY id');
    }

    /** @return array<string, mixed>|null */
    public static function type(int $id): ?array
    {
        return Database::first('SELECT * FROM leave_types WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE l.id = ?', [$id]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(string $userId): array
    {
        return Database::all(self::SELECT . ' WHERE l.user_id = ? ORDER BY l.start_date DESC, l.id DESC', [$userId]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function pending(): array
    {
        return Database::all(self::SELECT . " WHERE l.status = 'pending' ORDER BY l.start_date, l.id");
    }

    /** @return array<int, array<string, mixed>> ผู้ที่ลาในวันที่ระบุ (อนุมัติแล้ว) */
    public static function onDate(string $date): array
    {
        return Database::all(
            self::SELECT . " WHERE l.status = 'approved' AND ? BETWEEN l.start_date AND l.end_date ORDER BY u.name",
            [$date]
        );
    }

    /**
     * วันลาที่ใช้ไปและที่รออนุมัติ แยกตามประเภท ในช่วงวันที่ (ปีงบประมาณ)
     *
     * @return array<int, array{used: float, pending: float}> leave_type_id => จำนวนวัน
     */
    public static function usage(string $userId, string $from, string $to): array
    {
        $rows = Database::all(
            "SELECT leave_type_id, status, SUM(days) AS days FROM leave_requests
             WHERE user_id = ? AND status IN ('approved', 'pending') AND start_date BETWEEN ? AND ?
             GROUP BY leave_type_id, status",
            [$userId, $from, $to]
        );
        $usage = [];
        foreach ($rows as $row) {
            $key = $row['status'] === 'approved' ? 'used' : 'pending';
            $usage[(int) $row['leave_type_id']] ??= ['used' => 0.0, 'pending' => 0.0];
            $usage[(int) $row['leave_type_id']][$key] = (float) $row['days'];
        }

        return $usage;
    }

    /** ใบลาที่ยังมีผล (รออนุมัติหรืออนุมัติแล้ว) ที่ทับช่วงวันที่ */
    public static function overlaps(string $userId, string $from, string $to): bool
    {
        return (bool) Database::value(
            "SELECT 1 FROM leave_requests WHERE user_id = ? AND status IN ('pending', 'approved')
             AND start_date <= ? AND end_date >= ? LIMIT 1",
            [$userId, $to, $from]
        );
    }
}
