<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class Attendance
{
    public const STATUSES = ['normal', 'late', 'ot', 'leave', 'absent'];

    /** @return array<string, mixed>|null */
    public static function forDay(string $userId, string $date): ?array
    {
        return Database::first('SELECT * FROM attendance_logs WHERE user_id = ? AND date = ?', [$userId, $date]);
    }

    /**
     * @param array{status?: string, month?: string} $filters month = YYYY-MM
     * @return array<int, array<string, mixed>>
     */
    public static function history(string $userId, array $filters = [], int $limit = 400): array
    {
        $where = ['user_id = ?'];
        $params = [$userId];
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['month'])) {
            $where[] = "DATE_FORMAT(date, '%Y-%m') = ?";
            $params[] = $filters['month'];
        }

        return Database::all(
            'SELECT * FROM attendance_logs WHERE ' . implode(' AND ', $where) . " ORDER BY date DESC LIMIT {$limit}",
            $params
        );
    }

    /** @return string[] เดือน (YYYY-MM) ที่ผู้ใช้มีบันทึก ใหม่สุดก่อน */
    public static function months(string $userId): array
    {
        return Database::query(
            "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') AS ym FROM attendance_logs WHERE user_id = ? ORDER BY ym DESC",
            [$userId]
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    /** @return array<string, int> status => จำนวนวัน ในช่วงวันที่ */
    public static function countByStatus(string $userId, string $from, string $to): array
    {
        $rows = Database::all(
            'SELECT status, COUNT(*) AS n FROM attendance_logs WHERE user_id = ? AND date BETWEEN ? AND ? GROUP BY status',
            [$userId, $from, $to]
        );

        return array_map('intval', array_column($rows, 'n', 'status'));
    }

    /** ชั่วโมงหลังเวลาเลิกงานที่สะสมในช่วงวันที่ */
    public static function overtimeHours(string $userId, string $from, string $to, string $endOfDay): float
    {
        return (float) Database::value(
            "SELECT COALESCE(SUM(GREATEST(TIME_TO_SEC(TIMEDIFF(clock_out, ?)), 0)), 0) / 3600
             FROM attendance_logs WHERE user_id = ? AND date BETWEEN ? AND ? AND clock_out IS NOT NULL",
            [$endOfDay . ':00', $userId, $from, $to]
        );
    }

    /**
     * การลงเวลาของทุกคนในวันที่ระบุ (ผู้ที่ไม่มีบันทึกได้ status = null)
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    public static function team(string $date, string $departmentId, string $state, int $page, int $perPage): array
    {
        $where = ["u.status = 'Active'"];
        $params = [$date];
        if ($departmentId !== '') {
            $where[] = 'u.department_id = ?';
            $params[] = $departmentId;
        }
        if ($state === 'missing') {
            $where[] = 'a.id IS NULL';
        } elseif ($state !== '') {
            $where[] = 'a.status = ?';
            $params[] = $state;
        }
        $from = 'FROM users u LEFT JOIN attendance_logs a ON a.user_id = u.id AND a.date = ?
                 LEFT JOIN departments d ON d.id = u.department_id WHERE ' . implode(' AND ', $where);

        $total = (int) Database::value("SELECT COUNT(*) {$from}", $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::all(
            "SELECT u.id, u.name, u.position, d.name AS department, a.clock_in, a.clock_out, a.status, a.remarks
             {$from} ORDER BY a.clock_in IS NULL, a.clock_in, u.id LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [$rows, $total];
    }

    /** @return array<string, int> สรุปทั้งองค์กรของวันที่ระบุ: present, late, ot, leave, missing, total */
    public static function teamSummary(string $date): array
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total,
                    SUM(a.status IN ('normal', 'late', 'ot')) AS present,
                    SUM(a.status = 'late') AS late,
                    SUM(a.status = 'leave') AS on_leave,
                    SUM(a.id IS NULL) AS missing
             FROM users u LEFT JOIN attendance_logs a ON a.user_id = u.id AND a.date = ?
             WHERE u.status = 'Active'",
            [$date]
        );

        return [
            'total' => (int) $row['total'],
            'present' => (int) $row['present'],
            'late' => (int) $row['late'],
            'leave' => (int) $row['on_leave'],
            'missing' => (int) $row['missing'],
        ];
    }
}
