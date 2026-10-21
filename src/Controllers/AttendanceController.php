<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Services\AttendanceService;
use App\Services\Auth;
use App\Services\Database;
use App\Services\LeaveService;

final class AttendanceController extends Controller
{
    private const TEAM_PER_PAGE = 20;

    public function index(): void
    {
        $user = Auth::user();
        $monthFrom = date('Y-m-01');
        $today = date('Y-m-d');
        $hours = Setting::workingHours();
        $counts = Attendance::countByStatus($user['id'], $monthFrom, $today);
        $quotas = LeaveService::quotas($user['id']);
        $isHr = Auth::hasRole(...config('roles.hr'));

        $this->view('attendance/index', [
            'title' => 'ลงเวลาปฏิบัติงาน',
            'user' => $user,
            'today' => Attendance::forDay($user['id'], $today),
            'hours' => $hours,
            'stats' => [
                'normal' => ($counts['normal'] ?? 0) + ($counts['ot'] ?? 0),
                'late' => $counts['late'] ?? 0,
                'leave_left' => array_sum(array_column($quotas, 'remaining')),
                'ot_hours' => Attendance::overtimeHours($user['id'], $monthFrom, $today, $hours['end']),
            ],
            'recent' => Attendance::history($user['id'], [], 5),
            'team' => $isHr ? Attendance::teamSummary($today) : null,
            'onLeave' => $isHr ? LeaveRequest::onDate($today) : [],
            'scripts' => ['js/attendance.js'],
        ]);
    }

    public function clockIn(): void
    {
        $error = AttendanceService::clockIn(Auth::id(), $this->remarks());
        flash($error ? 'danger' : 'success', $error ?? 'ลงเวลาเข้างานเรียบร้อย เวลา ' . date('H:i') . ' น.');
        redirect('/attendance');
    }

    public function clockOut(): void
    {
        $error = AttendanceService::clockOut(Auth::id(), $this->remarks());
        flash($error ? 'danger' : 'success', $error ?? 'ลงเวลาออกงานเรียบร้อย เวลา ' . date('H:i') . ' น.');
        redirect('/attendance');
    }

    private function remarks(): string
    {
        return mb_substr(trim((string) ($this->input()['remarks'] ?? '')), 0, 255);
    }

    public function history(): void
    {
        $status = (string) ($_GET['status'] ?? '');
        $month = (string) ($_GET['month'] ?? '');
        $filters = [
            'status' => in_array($status, Attendance::STATUSES, true) ? $status : '',
            'month' => preg_match('/^\d{4}-\d{2}$/', $month) ? $month : '',
        ];
        $logs = Attendance::history(Auth::id(), $filters);
        $summary = array_count_values(array_column($logs, 'status'));

        $this->view('attendance/history', [
            'title' => 'ประวัติการลงเวลา',
            'logs' => $logs,
            'filters' => $filters,
            'months' => Attendance::months(Auth::id()),
            'summary' => $summary,
        ]);
    }

    public function reports(): void
    {
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $state = (string) ($_GET['state'] ?? '');
        $state = in_array($state, ['normal', 'late', 'ot', 'leave', 'missing'], true) ? $state : '';
        $department = (string) ($_GET['department'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        [$rows, $total] = Attendance::team($date, $department, $state, $page, self::TEAM_PER_PAGE);

        $this->view('attendance/reports', [
            'title' => 'รายงานการลงเวลา',
            'date' => $date,
            'summary' => Attendance::teamSummary($date),
            'rows' => $rows,
            'filters' => ['state' => $state, 'department' => $department],
            'departments' => Database::all('SELECT id, name FROM departments ORDER BY id'),
            'pendingLeaves' => count(LeaveRequest::pending()),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::TEAM_PER_PAGE)),
            'perPage' => self::TEAM_PER_PAGE,
        ]);
    }
}
