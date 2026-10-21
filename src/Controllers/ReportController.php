<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Repair;
use App\Services\Database;

/** รายงานสถิติและรายได้ (ตัวเลขจริงจากฐานข้อมูล แทนค่าจำลองในเวอร์ชันเดิม) */
final class ReportController extends Controller
{
    private const BILLABLE = "status IN ('Approved', 'Completed')";
    private const CHART_JS = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js';

    public function reports(): void
    {
        $repairs = Database::first(
            "SELECT COUNT(*) AS total, SUM(status = 'Completed') AS done,
                    COALESCE(SUM(CASE WHEN status = 'Completed' THEN labor_cost + material_cost END), 0) AS cost
             FROM repairs WHERE status <> 'Cancelled'"
        );
        $bookings = Database::first(
            'SELECT COUNT(*) AS n, COALESCE(SUM(duration_hours), 0) AS hours FROM bookings WHERE ' . self::BILLABLE
        );
        $byDepartment = Database::all(
            'SELECT department AS label, COUNT(*) AS n FROM bookings WHERE ' . self::BILLABLE . ' GROUP BY department ORDER BY n DESC'
        );
        $byCategory = array_map(
            static fn ($r) => ['label' => Repair::categoryLabel($r['category']), 'n' => (int) $r['n']],
            Database::all("SELECT category, COUNT(*) AS n FROM repairs WHERE status <> 'Cancelled' GROUP BY category ORDER BY n DESC")
        );
        $byRoom = Database::all(
            'SELECT r.name, COUNT(b.id) AS n, COALESCE(SUM(b.duration_hours), 0) AS hours
             FROM rooms r LEFT JOIN bookings b ON b.room_id = r.id AND b.' . self::BILLABLE . '
             GROUP BY r.id, r.name ORDER BY r.id'
        );

        $this->view('reports', [
            'title' => 'รายงานสถิติ',
            'stats' => [
                'bookings' => (int) $bookings['n'],
                'hours' => (float) $bookings['hours'],
                'completion' => $repairs['total'] ? (int) round($repairs['done'] / $repairs['total'] * 100) : 0,
                'repair_cost' => (float) $repairs['cost'],
            ],
            'byRoom' => $byRoom,
            'chart' => [
                'departments' => array_column($byDepartment, 'label'),
                'departmentCounts' => array_map('intval', array_column($byDepartment, 'n')),
                'categories' => array_column($byCategory, 'label'),
                'categoryCounts' => array_column($byCategory, 'n'),
            ],
            'scripts' => [self::CHART_JS, 'js/reports.js'],
        ]);
    }

    public function revenue(): void
    {
        $totals = Database::first(
            "SELECT COALESCE(SUM(rental_fee), 0) AS total,
                    COALESCE(SUM(CASE WHEN org_type = 'Government Agency' THEN rental_fee END), 0) AS gov
             FROM bookings WHERE " . self::BILLABLE
        );

        // 12 เดือนล่าสุด เดือนที่ไม่มีรายได้แสดงเป็น 0
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[date('Y-m', strtotime("first day of -{$i} month"))] = 0.0;
        }
        $rows = Database::all(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(rental_fee) AS total FROM bookings
             WHERE " . self::BILLABLE . ' AND date >= ? GROUP BY ym',
            [array_key_first($months) . '-01']
        );
        foreach ($rows as $row) {
            if (isset($months[$row['ym']])) {
                $months[$row['ym']] = (float) $row['total'];
            }
        }

        $this->view('revenue', [
            'title' => 'รายได้',
            'totals' => ['total' => (float) $totals['total'], 'gov' => (float) $totals['gov'], 'other' => (float) $totals['total'] - (float) $totals['gov']],
            'byRoom' => Database::all(
                'SELECT r.name, COALESCE(SUM(b.rental_fee), 0) AS total, COUNT(b.id) AS n
                 FROM rooms r LEFT JOIN bookings b ON b.room_id = r.id AND b.' . self::BILLABLE . '
                 GROUP BY r.id, r.name ORDER BY total DESC'
            ),
            'recent' => Database::all(
                'SELECT b.*, r.name AS room_name FROM bookings b JOIN rooms r ON r.id = b.room_id
                 WHERE b.' . self::BILLABLE . ' ORDER BY b.date DESC, b.start_time DESC LIMIT 15'
            ),
            'chart' => [
                'labels' => array_map(static fn ($ym) => thai_date($ym . '-01', 'month'), array_keys($months)),
                'values' => array_values($months),
            ],
            'scripts' => [self::CHART_JS, 'js/reports.js'],
        ]);
    }

    public function revenueExport(): void
    {
        $rows = Database::all(
            'SELECT b.id, b.title, r.name AS room_name, b.org_type, b.department, b.date, b.duration_hours, b.rental_fee
             FROM bookings b JOIN rooms r ON r.id = b.room_id WHERE b.' . self::BILLABLE . ' ORDER BY b.date DESC'
        );
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="revenue-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['เลขที่', 'หัวข้อ', 'ห้องประชุม', 'ประเภทหน่วยงาน', 'หน่วยงาน', 'วันที่', 'ชั่วโมง', 'รายได้ (บาท)']);
        foreach ($rows as $b) {
            fputcsv($out, array_map(static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'{$v}" : (string) $v, [
                $b['id'], $b['title'], $b['room_name'], org_type_label($b['org_type']), $b['department'],
                thai_date($b['date']), (float) $b['duration_hours'], $b['rental_fee'],
            ]));
        }
        fclose($out);
    }
}
