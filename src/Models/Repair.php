<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class Repair
{
    public const STATUSES = ['Waiting', 'Accepted', 'In Progress', 'Completed', 'Cancelled'];
    public const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];
    public const CATEGORIES = [
        'Air Conditioner' => 'เครื่องปรับอากาศ (แอร์ไม่เย็น/มีน้ำรั่ว)',
        'Electrical' => 'ระบบไฟฟ้า/หลอดแสงสว่างเสีย',
        'Computer' => 'คอมพิวเตอร์สำหรับการประชุมเปิดไม่ติด',
        'Network' => 'ระบบเครือข่าย/WiFi เชื่อมต่อไม่ได้',
        'Projector' => 'เครื่องฉายโปรเจกเตอร์/ไม่มีสัญญาณภาพ',
        'Furniture' => 'เฟอร์นิเจอร์ โต๊ะ เก้าอี้ ชำรุดเสียหาย',
        'Water Supply' => 'น้ำประปาในห้องน้ำไม่ไหล/รั่วซึม',
        'Cleaning' => 'ต้องการการทำความสะอาดพิเศษ/กลิ่นอับ',
    ];

    private const SELECT = 'SELECT m.*, r.name AS room_name, r.building, r.floor,
                                   t.name AS technician_name, rp.name AS reporter_name
                            FROM repairs m
                            JOIN rooms r ON r.id = m.room_id
                            LEFT JOIN users t ON t.id = m.technician_id
                            LEFT JOIN users rp ON rp.id = m.reported_by';

    /** งานค้างขึ้นก่อน แล้วเรียงวันที่ใหม่สุด (ลำดับเดียวกับ maintenance dashboard เดิม) */
    private const ORDER = "FIELD(m.status, 'Waiting', 'Accepted', 'In Progress', 'Completed', 'Cancelled'), m.date DESC, m.id DESC";

    public static function categoryLabel(string $key): string
    {
        return self::CATEGORIES[$key] ?? $key;
    }

    /** @return array<string, mixed>|null */
    public static function find(string $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE m.id = ?', [$id]);
    }

    /**
     * @param array{search?: string, priority?: string, status?: string} $filters
     * @param string|null $reporterId จำกัดเฉพาะงานที่ผู้ใช้คนนี้แจ้ง (null = ทั้งหมด)
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    public static function paginate(array $filters, int $page, int $perPage, ?string $reporterId = null): array
    {
        $where = [];
        $params = [];
        if ($reporterId !== null) {
            $where[] = 'm.reported_by = ?';
            $params[] = $reporterId;
        }
        if (!empty($filters['priority'])) {
            $where[] = 'm.priority = ?';
            $params[] = $filters['priority'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'm.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(m.id LIKE ? OR m.description LIKE ? OR r.name LIKE ? OR t.name LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $filters['search'] . '%'));
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $from = 'FROM repairs m JOIN rooms r ON r.id = m.room_id LEFT JOIN users t ON t.id = m.technician_id';

        $total = (int) Database::value("SELECT COUNT(*) {$from}{$sqlWhere}", $params);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::all(self::SELECT . "{$sqlWhere} ORDER BY " . self::ORDER . " LIMIT {$perPage} OFFSET {$offset}", $params);

        return [$rows, $total];
    }

    /**
     * งานของช่าง: งานที่ได้รับมอบหมาย และงานที่ยังไม่มีผู้รับ
     *
     * @param string|null $technicianId null = ทุกงานที่ยังไม่ปิด (มุมมองผู้ดูแลระบบ)
     * @return array<int, array<string, mixed>>
     */
    public static function forTechnician(?string $technicianId): array
    {
        if ($technicianId === null) {
            return Database::all(self::SELECT . " WHERE m.status IN ('Waiting', 'Accepted', 'In Progress') ORDER BY " . self::ORDER);
        }

        return Database::all(
            self::SELECT . " WHERE (m.technician_id = ? AND m.status <> 'Cancelled')
                                OR (m.status = 'Waiting' AND m.technician_id IS NULL)
                             ORDER BY " . self::ORDER . ' LIMIT 100',
            [$technicianId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit): array
    {
        return Database::all(self::SELECT . " ORDER BY m.created_at DESC, m.id DESC LIMIT {$limit}");
    }

    /**
     * งานด่วน (Critical/High) ที่ยังไม่เสร็จ เรียงด่วนที่สุดก่อน
     *
     * @return array<int, array<string, mixed>>
     */
    public static function urgent(int $limit): array
    {
        return Database::all(
            self::SELECT . " WHERE m.status NOT IN ('Completed', 'Cancelled') AND m.priority IN ('Critical', 'High')
                             ORDER BY FIELD(m.priority, 'Critical', 'High'), m.date DESC LIMIT {$limit}"
        );
    }

    /** @return array<string, int> status => จำนวน */
    public static function countByStatus(): array
    {
        $rows = Database::all('SELECT status, COUNT(*) AS n FROM repairs GROUP BY status');

        return array_map('intval', array_column($rows, 'n', 'status'));
    }

    /**
     * นายช่างทั้งหมด พร้อมจำนวนงานที่ค้างอยู่ (ใช้ตอนมอบหมายงาน)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function technicians(): array
    {
        return Database::all(
            "SELECT u.id, u.name, u.skill,
                    (SELECT COUNT(*) FROM repairs x WHERE x.technician_id = u.id AND x.status IN ('Accepted', 'In Progress')) AS open_jobs
             FROM users u WHERE u.role = 'Technician' AND u.status = 'Active' ORDER BY u.name"
        );
    }

    /** เลขที่ถัดไปรูปแบบ MNT-ปี ค.ศ.-ลำดับ 4 หลัก เรียกภายใต้ lock เท่านั้น */
    public static function nextId(): string
    {
        $prefix = 'MNT-' . date('Y') . '-';
        $last = Database::value(
            'SELECT MAX(CAST(SUBSTRING(id, ?) AS UNSIGNED)) FROM repairs WHERE id LIKE ?',
            [strlen($prefix) + 1, $prefix . '%']
        );

        return $prefix . str_pad((string) ((int) $last + 1), 4, '0', STR_PAD_LEFT);
    }

    /** @param array<string, mixed> $data */
    public static function insert(array $data): void
    {
        $cols = array_keys($data);
        Database::query(
            sprintf('INSERT INTO repairs (%s) VALUES (%s)', implode(', ', $cols), implode(', ', array_fill(0, count($cols), '?'))),
            array_values($data)
        );
    }

    /**
     * อัปเดตเฉพาะเมื่อสถานะปัจจุบันตรงกับที่คาดไว้ กันสองคนกดพร้อมกัน
     *
     * @param array<string, mixed> $data
     * @return bool false เมื่อสถานะถูกเปลี่ยนไปก่อนแล้ว
     */
    public static function transition(string $id, string $fromStatus, array $data): bool
    {
        $set = implode(', ', array_map(static fn ($col) => "{$col} = ?", array_keys($data)));

        return Database::query(
            "UPDATE repairs SET {$set} WHERE id = ? AND status = ?",
            [...array_values($data), $id, $fromStatus]
        )->rowCount() === 1;
    }
}
