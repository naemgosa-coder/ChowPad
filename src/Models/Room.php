<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Database;

final class Room
{
    /** สถานะการจองที่ถือว่าใช้ห้องอยู่จริง */
    private const OCCUPYING = ['Approved', 'Completed'];

    /**
     * @param array{search?: string, building?: string, status?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function all(array $filters = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE ? OR building LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['building'])) {
            $where[] = 'building = ?';
            $params[] = $filters['building'];
        }
        if (($filters['status'] ?? '') === 'Available') {
            $where[] = 'locked = 0';
        } elseif (($filters['status'] ?? '') === 'Locked') {
            $where[] = 'locked = 1';
        }

        $sql = 'SELECT * FROM rooms' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id';

        return array_map([self::class, 'hydrate'], Database::all($sql, $params));
    }

    /** @return array<string, mixed>|null */
    public static function find(string $id): ?array
    {
        $row = Database::first('SELECT * FROM rooms WHERE id = ?', [$id]);

        return $row ? self::hydrate($row) : null;
    }

    /** @return string[] */
    public static function buildings(): array
    {
        return Database::query('SELECT DISTINCT building FROM rooms ORDER BY building')->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * สถานะห้อง ณ เวลาปัจจุบัน พร้อมข้อความบอกสถานะถัดไป (DESIGN_SYSTEM 7.2)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function liveStatus(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $time = $now->format('H:i:s');
        $placeholders = implode(', ', array_fill(0, count(self::OCCUPYING), '?'));
        $todays = Database::all(
            "SELECT id, room_id, title, start_time, end_time FROM bookings
             WHERE date = ? AND status IN ({$placeholders}) AND end_time > ?
             ORDER BY start_time",
            [$now->format('Y-m-d'), ...self::OCCUPYING, $time]
        );

        $byRoom = [];
        foreach ($todays as $b) {
            $byRoom[$b['room_id']][] = $b;
        }

        $result = [];
        foreach (self::all() as $room) {
            $current = null;
            $next = null;
            foreach ($byRoom[$room['id']] ?? [] as $b) {
                if ($b['start_time'] <= $time) {
                    $current ??= $b;
                } elseif ($next === null) {
                    $next = $b;
                }
            }

            if ($room['locked']) {
                $state = 'maintenance';
                $line = 'ปิดใช้งานชั่วคราว ไม่สามารถจองได้';
            } elseif ($current) {
                $state = 'in-use';
                $line = 'ใช้งานอยู่ ว่างอีกครั้ง ' . substr($current['end_time'], 0, 5) . ' น.';
            } else {
                $state = 'available';
                $line = $next
                    ? 'ว่างถึง ' . substr($next['start_time'], 0, 5) . ' น. · ถัดไป: ' . $next['title']
                    : 'ว่างตลอดวันที่เหลือ';
            }

            $meta = status_meta('room', $state);
            $result[] = [
                'id' => $room['id'],
                'name' => $room['name'],
                'state' => $state,
                'label' => $meta['label'],
                'badge_class' => $meta['class'],
                'icon' => $meta['icon'],
                'line' => $line,
                'current' => $current ? ['id' => $current['id'], 'title' => $current['title']] : null,
            ];
        }

        return $result;
    }

    /** ความจุสูงสุดจากข้อความเช่น "20-30" */
    public static function maxCapacity(array $room): int
    {
        preg_match_all('/\d+/', (string) $room['capacity'], $m);

        return $m[0] ? (int) max($m[0]) : 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $row['equipment'] = json_decode((string) $row['equipment'], true) ?: [];
        $row['locked'] = (bool) $row['locked'];
        $row['price_gov'] = (float) $row['price_gov'];
        $row['price_local'] = (float) $row['price_local'];

        return $row;
    }
}
