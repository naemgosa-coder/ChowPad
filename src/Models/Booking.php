<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class Booking
{
    public const STATUSES = ['Waiting Approval', 'Approved', 'Completed', 'Rejected', 'Cancelled'];
    /** สถานะที่ไม่กันเวลาห้อง */
    public const RELEASED = ['Cancelled', 'Rejected'];

    public const EQUIPMENT = [
        'Projector' => 'เครื่องฉายโปรเจกเตอร์',
        'TV' => 'จอโทรทัศน์ LED TV',
        'Microphone' => 'ไมโครโฟนไร้สาย',
        'Zoom' => 'ชุดวิดีโอคอล Zoom',
        'Whiteboard' => 'กระดานไวท์บอร์ด',
    ];

    private const SELECT = 'SELECT b.*, r.name AS room_name, r.building, r.floor
                            FROM bookings b JOIN rooms r ON r.id = b.room_id';

    /** @return array<string, mixed>|null */
    public static function find(string $id): ?array
    {
        $row = Database::first(self::SELECT . ' WHERE b.id = ?', [$id]);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param array{search?: string, room?: string, status?: string} $filters
     * @param string|null $userId จำกัดเฉพาะการจองของผู้ใช้คนนี้ (null = ทั้งหมด)
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    public static function paginate(array $filters, int $page, int $perPage, ?string $userId = null): array
    {
        [$where, $params] = self::filterSql($filters, $userId);
        $total = (int) Database::value("SELECT COUNT(*) FROM bookings b {$where}", $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::all(
            self::SELECT . " {$where} ORDER BY b.created_at DESC, b.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [array_map([self::class, 'hydrate'], $rows), $total];
    }

    /**
     * @param array{search?: string, room?: string, status?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function export(array $filters, ?string $userId = null): array
    {
        [$where, $params] = self::filterSql($filters, $userId);

        return Database::all(self::SELECT . " {$where} ORDER BY b.date DESC, b.start_time", $params);
    }

    /** @return array<int, array<string, mixed>> */
    public static function pending(): array
    {
        return array_map(
            [self::class, 'hydrate'],
            Database::all(self::SELECT . " WHERE b.status = 'Waiting Approval' ORDER BY b.date, b.start_time")
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit): array
    {
        return array_map(
            [self::class, 'hydrate'],
            Database::all(self::SELECT . " ORDER BY b.created_at DESC, b.id DESC LIMIT {$limit}")
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forDate(string $date): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::RELEASED), '?'));

        return array_map([self::class, 'hydrate'], Database::all(
            self::SELECT . " WHERE b.date = ? AND b.status NOT IN ({$placeholders}) ORDER BY b.start_time, r.id",
            [$date, ...self::RELEASED]
        ));
    }

    /**
     * การจองที่ทับช่วงเวลาที่ระบุ (ช่วงซ้อนกันเมื่อ start < end อื่น และ end > start อื่น)
     *
     * @param bool $approvedOnly true = นับเฉพาะที่อนุมัติแล้ว (ใช้ตอนอนุมัติ ซึ่งคำขอที่รออยู่ด้วยกันไม่นับว่าชน)
     * @return array<string, mixed>|null
     */
    public static function findConflict(
        string $roomId,
        string $date,
        string $start,
        string $end,
        ?string $excludeId = null,
        bool $approvedOnly = false
    ): ?array {
        $statuses = $approvedOnly ? ['Approved', 'Completed'] : array_diff(self::STATUSES, self::RELEASED);
        $placeholders = implode(', ', array_fill(0, count($statuses), '?'));

        $row = Database::first(
            "SELECT id, title, department, start_time, end_time FROM bookings
             WHERE room_id = ? AND date = ? AND start_time < ? AND end_time > ?
               AND status IN ({$placeholders}) AND id <> ?
             ORDER BY start_time LIMIT 1",
            [$roomId, $date, $end, $start, ...array_values($statuses), $excludeId ?? '']
        );
        if ($row) {
            $row['start_time'] = substr($row['start_time'], 0, 5);
            $row['end_time'] = substr($row['end_time'], 0, 5);
        }

        return $row;
    }

    /**
     * ห้องที่ไม่ว่างในช่วงเวลานี้
     *
     * @return array<string, array<string, mixed>> room_id => การจองที่ชน
     */
    public static function busyRooms(string $date, string $start, string $end): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::RELEASED), '?'));
        $rows = Database::all(
            "SELECT room_id, title, start_time, end_time FROM bookings
             WHERE date = ? AND start_time < ? AND end_time > ? AND status NOT IN ({$placeholders})
             ORDER BY start_time",
            [$date, $end, $start, ...self::RELEASED]
        );
        $busy = [];
        foreach ($rows as $row) {
            $busy[$row['room_id']] ??= $row;
        }

        return $busy;
    }

    /**
     * @param string[] $roomIds
     * @return array<int, array<string, mixed>>
     */
    public static function between(string $from, string $to, array $roomIds): array
    {
        if (!$roomIds) {
            return [];
        }
        $rooms = implode(', ', array_fill(0, count($roomIds), '?'));
        $released = implode(', ', array_fill(0, count(self::RELEASED), '?'));

        return Database::all(
            self::SELECT . " WHERE b.date BETWEEN ? AND ? AND b.room_id IN ({$rooms})
                             AND b.status NOT IN ({$released}) ORDER BY b.date, b.start_time",
            [$from, $to, ...$roomIds, ...self::RELEASED]
        );
    }

    /** เลขที่ถัดไปรูปแบบ BK-ปี ค.ศ.-ลำดับ 4 หลัก เรียกภายใต้ lock เท่านั้น */
    public static function nextId(): string
    {
        $prefix = 'BK-' . date('Y') . '-';
        $last = Database::value(
            'SELECT MAX(CAST(SUBSTRING(id, ?) AS UNSIGNED)) FROM bookings WHERE id LIKE ?',
            [strlen($prefix) + 1, $prefix . '%']
        );

        return $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @param array<string, mixed> $data */
    public static function insert(array $data): void
    {
        $data['equipment'] = json_encode($data['equipment'] ?? [], JSON_UNESCAPED_UNICODE);
        $cols = array_keys($data);
        Database::query(
            sprintf(
                'INSERT INTO bookings (%s) VALUES (%s)',
                implode(', ', $cols),
                implode(', ', array_fill(0, count($cols), '?'))
            ),
            array_values($data)
        );
    }

    public static function updateStatus(string $id, string $status, ?string $reviewerId = null, ?string $note = null): void
    {
        Database::query(
            'UPDATE bookings SET status = ?, reviewed_by = COALESCE(?, reviewed_by), review_note = COALESCE(?, review_note) WHERE id = ?',
            [$status, $reviewerId, $note, $id]
        );
    }

    /**
     * @param array{search?: string, room?: string, status?: string} $filters
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function filterSql(array $filters, ?string $userId): array
    {
        $where = [];
        $params = [];
        if ($userId !== null) {
            $where[] = 'b.user_id = ?';
            $params[] = $userId;
        }
        if (!empty($filters['room'])) {
            $where[] = 'b.room_id = ?';
            $params[] = $filters['room'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(b.title LIKE ? OR b.organizer LIKE ? OR b.id LIKE ?)';
            array_push($params, ...array_fill(0, 3, '%' . $filters['search'] . '%'));
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $row['equipment'] = json_decode((string) $row['equipment'], true) ?: [];
        $row['start_time'] = substr((string) $row['start_time'], 0, 5);
        $row['end_time'] = substr((string) $row['end_time'], 0, 5);

        return $row;
    }
}
