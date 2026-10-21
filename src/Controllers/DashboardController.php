<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Repair;
use App\Models\Room;
use App\Services\Auth;
use App\Services\Database;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $today = date('Y-m-d');
        $now = date('H:i');
        $todayBookings = Booking::forDate($today);
        $live = Room::liveStatus();
        $repairs = Repair::countByStatus();

        $billable = "status IN ('Approved', 'Completed')";
        $stats = [
            'rooms' => count($live),
            'rooms_available' => count(array_filter($live, static fn ($r) => $r['state'] === 'available')),
            'today_bookings' => count($todayBookings),
            'pending_approvals' => (int) Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'Waiting Approval'"),
            'in_progress' => count(array_filter(
                $todayBookings,
                static fn ($b) => $b['status'] === 'Approved' && $b['start_time'] <= $now && $b['end_time'] > $now
            )),
            'today_revenue' => array_sum(array_map(
                static fn ($b) => in_array($b['status'], ['Approved', 'Completed'], true) ? (float) $b['rental_fee'] : 0,
                $todayBookings
            )),
            'month_revenue' => (float) Database::value(
                "SELECT COALESCE(SUM(rental_fee), 0) FROM bookings WHERE {$billable} AND date BETWEEN ? AND ?",
                [date('Y-m-01'), date('Y-m-t')]
            ),
            'repairs_pending' => ($repairs['Waiting'] ?? 0) + ($repairs['Accepted'] ?? 0),
            'repairs_in_progress' => $repairs['In Progress'] ?? 0,
            'repairs_completed' => $repairs['Completed'] ?? 0,
            'most_used_room' => Database::value(
                "SELECT r.name FROM bookings b JOIN rooms r ON r.id = b.room_id WHERE b.{$billable}
                 GROUP BY r.id, r.name ORDER BY COUNT(*) DESC LIMIT 1"
            ),
        ];

        $this->view('dashboard', [
            'title' => 'ภาพรวมระบบ',
            'stats' => $stats,
            'live' => $live,
            'todayBookings' => $todayBookings,
            'recentBookings' => Booking::recent(5),
            'recentRepairs' => Repair::recent(5),
            'urgentRepairs' => Repair::urgent(4),
            'charts' => $this->chartData($billable),
            'showMoney' => Auth::hasRole(...config('roles.reports')),
            'scripts' => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js',
                'js/dashboard.js',
                'js/live-rooms.js',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function chartData(string $billable): array
    {
        $months = [];
        for ($i = 6; $i >= 0; $i--) {
            $months[date('Y-m', strtotime("first day of -{$i} month"))] = ['bookings' => 0, 'revenue' => 0.0];
        }
        $rows = Database::all(
            "SELECT DATE_FORMAT(date, '%Y-%m') AS ym, COUNT(*) AS n, SUM(rental_fee) AS revenue
             FROM bookings WHERE {$billable} AND date >= ? GROUP BY ym",
            [array_key_first($months) . '-01']
        );
        foreach ($rows as $row) {
            if (isset($months[$row['ym']])) {
                $months[$row['ym']] = ['bookings' => (int) $row['n'], 'revenue' => (float) $row['revenue']];
            }
        }
        $shortMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $usage = Database::all(
            "SELECT r.name, COUNT(b.id) AS n FROM rooms r
             LEFT JOIN bookings b ON b.room_id = r.id AND b.{$billable}
             GROUP BY r.id, r.name ORDER BY r.id"
        );

        return [
            'months' => array_map(
                static fn ($ym) => $shortMonths[(int) substr($ym, 5, 2)] . ' ' . substr((string) ((int) substr($ym, 0, 4) + 543), 2),
                array_keys($months)
            ),
            'bookings' => array_column($months, 'bookings'),
            'revenue' => array_column($months, 'revenue'),
            'roomNames' => array_column($usage, 'name'),
            'roomUsage' => array_map('intval', array_column($usage, 'n')),
        ];
    }
}
