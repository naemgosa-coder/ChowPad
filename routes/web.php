<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\AttendanceController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\CalendarController;
use App\Controllers\DashboardController;
use App\Controllers\DepartmentController;
use App\Controllers\HealthController;
use App\Controllers\HomeController;
use App\Controllers\LeaveController;
use App\Controllers\MaintenanceController;
use App\Controllers\NotificationController;
use App\Controllers\ProfileController;
use App\Controllers\ReportController;
use App\Controllers\RoomController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Services\Router;

/** @var Router $router */

// ทุก route ต้องล็อกอินเป็นค่าเริ่มต้น ใส่ ['auth' => false] เฉพาะหน้าที่เปิดสาธารณะ
// จำกัดบทบาทด้วย ['roles' => config('roles.admins')] (กลุ่มบทบาทอยู่ใน config/roles.php)
// route ที่มี path ตายตัวต้องอยู่ก่อน route ที่มี {id} ในตำแหน่งเดียวกัน

$router->get('/health', [HealthController::class, 'show'], ['auth' => false]);

$router->get('/', [HomeController::class, 'index'], ['auth' => false]);
$router->get('/login', [AuthController::class, 'showLogin'], ['auth' => false]);
$router->post('/login', [AuthController::class, 'login'], ['auth' => false]);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);

// บริการจอง
$router->get('/rooms', [RoomController::class, 'index']);
$router->get('/calendar', [CalendarController::class, 'index']);
$router->get('/bookings', [BookingController::class, 'index']);
$router->get('/bookings/create', [BookingController::class, 'create']);
$router->get('/bookings/export', [BookingController::class, 'export']);
$router->get('/bookings/approval', [BookingController::class, 'approval'], ['roles' => config('roles.approvers')]);
$router->post('/bookings', [BookingController::class, 'store']);
$router->get('/bookings/{id}', [BookingController::class, 'show']);
$router->post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
$router->post('/bookings/{id}/approve', [BookingController::class, 'approve'], ['roles' => config('roles.approvers')]);
$router->post('/bookings/{id}/reject', [BookingController::class, 'reject'], ['roles' => config('roles.approvers')]);

// งานซ่อมบำรุง
$router->get('/maintenance', [MaintenanceController::class, 'index']);
$router->get('/maintenance/create', [MaintenanceController::class, 'create']);
$router->post('/maintenance', [MaintenanceController::class, 'store']);
$router->get('/maintenance/{id}', [MaintenanceController::class, 'show']);
$router->post('/maintenance/{id}/assign', [MaintenanceController::class, 'assign'], ['roles' => config('roles.maintenance_leads')]);
$router->post('/maintenance/{id}/accept', [MaintenanceController::class, 'accept'], ['roles' => config('roles.technicians')]);
$router->post('/maintenance/{id}/start', [MaintenanceController::class, 'start'], ['roles' => config('roles.technicians')]);
$router->post('/maintenance/{id}/complete', [MaintenanceController::class, 'complete'], ['roles' => config('roles.technicians')]);
$router->get('/technician', [MaintenanceController::class, 'technician'], ['roles' => config('roles.technicians')]);

// ลงเวลาและการลา (แปลงจาก employee-attendance-dashboard)
$router->get('/attendance', [AttendanceController::class, 'index']);
$router->post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
$router->post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
$router->get('/attendance/history', [AttendanceController::class, 'history']);
$router->get('/attendance/reports', [AttendanceController::class, 'reports'], ['roles' => config('roles.hr')]);
$router->get('/leave', [LeaveController::class, 'index']);
$router->post('/leave', [LeaveController::class, 'store']);
$router->get('/leave/approval', [LeaveController::class, 'approval'], ['roles' => config('roles.hr')]);
$router->post('/leave/{id}/cancel', [LeaveController::class, 'cancel']);
$router->post('/leave/{id}/approve', [LeaveController::class, 'approve'], ['roles' => config('roles.hr')]);
$router->post('/leave/{id}/reject', [LeaveController::class, 'reject'], ['roles' => config('roles.hr')]);

// ผู้ใช้ทุกคน
$router->get('/profile', [ProfileController::class, 'show']);
$router->post('/profile', [ProfileController::class, 'update']);
$router->post('/profile/password', [ProfileController::class, 'password']);
$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/read-all', [NotificationController::class, 'readAll']);
$router->post('/notifications/{id}/read', [NotificationController::class, 'read']);
$router->post('/notifications/{id}/delete', [NotificationController::class, 'delete']);

// รายงานและบริหาร
$reports = ['roles' => config('roles.reports')];
$admins = ['roles' => config('roles.admins')];
$router->get('/reports', [ReportController::class, 'reports'], $reports);
$router->get('/revenue', [ReportController::class, 'revenue'], $reports);
$router->get('/revenue/export', [ReportController::class, 'revenueExport'], $reports);
$router->get('/users', [UserController::class, 'index'], $admins);
$router->post('/users', [UserController::class, 'store'], $admins);
$router->post('/users/{id}', [UserController::class, 'update'], $admins);
$router->post('/users/{id}/toggle', [UserController::class, 'toggle'], $admins);
$router->get('/departments', [DepartmentController::class, 'index'], $admins);
$router->post('/departments', [DepartmentController::class, 'store'], $admins);
$router->post('/departments/{id}', [DepartmentController::class, 'update'], $admins);
$router->post('/departments/{id}/delete', [DepartmentController::class, 'delete'], $admins);
$router->get('/audit-logs', [AuditLogController::class, 'index'], $admins);

// ตั้งค่าระบบ
$router->get('/settings', [SettingsController::class, 'index'], $admins);
$router->get('/settings/rooms', [SettingsController::class, 'rooms'], $admins);
$router->post('/settings/rooms', [SettingsController::class, 'roomStore'], $admins);
$router->post('/settings/rooms/{id}', [SettingsController::class, 'roomUpdate'], $admins);
$router->post('/settings/rooms/{id}/delete', [SettingsController::class, 'roomDelete'], $admins);
$router->get('/settings/holidays', [SettingsController::class, 'holidays'], $admins);
$router->post('/settings/holidays', [SettingsController::class, 'holidayStore'], $admins);
$router->post('/settings/holidays/{id}/delete', [SettingsController::class, 'holidayDelete'], $admins);
$router->post('/settings/working-hours', [SettingsController::class, 'workingHours'], $admins);
$router->get('/settings/leave-types', [SettingsController::class, 'leaveTypes'], $admins);
$router->post('/settings/leave-types/{id}', [SettingsController::class, 'leaveTypeUpdate'], $admins);
$router->get('/settings/booking-rules', [SettingsController::class, 'bookingRules'], $admins);
$router->post('/settings/booking-rules', [SettingsController::class, 'bookingRulesUpdate'], $admins);
$router->get('/settings/backup', [SettingsController::class, 'backup'], $admins);
$router->get('/settings/backup/export', [SettingsController::class, 'backupExport'], $admins);
$router->post('/settings/backup/restore', [SettingsController::class, 'backupRestore'], $admins);
$router->get('/settings/system', [SettingsController::class, 'system'], $admins);

// API (JSON)
$router->get('/api/rooms/status', [ApiController::class, 'roomStatus']);
$router->get('/api/rooms/availability', [ApiController::class, 'availability']);
$router->get('/api/calendar/events', [ApiController::class, 'calendarEvents']);
