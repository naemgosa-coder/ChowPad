<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\Setting;

/**
 * ลงเวลาเข้า-ออกงาน ใช้เวลาของเซิร์ฟเวอร์เสมอ (ผู้ใช้แก้เวลาในเครื่องตัวเองไม่ได้)
 * สาย = เข้าหลังเวลาเริ่มงาน, OT = ออกหลังเวลาเลิกงานเกิน 1 ชั่วโมง (ตามเกณฑ์ของระบบลงเวลาเดิม)
 */
final class AttendanceService
{
    private const OT_AFTER_MINUTES = 60;

    public static function clockIn(string $userId, string $remarks): ?string
    {
        $today = date('Y-m-d');
        $existing = Attendance::forDay($userId, $today);
        if ($existing && $existing['status'] === 'leave') {
            return 'วันนี้คุณมีใบลาที่อนุมัติแล้ว จึงลงเวลาเข้างานไม่ได้';
        }
        if ($existing && $existing['clock_in']) {
            return 'คุณลงเวลาเข้างานวันนี้ไปแล้ว';
        }

        $now = date('H:i:s');
        $status = substr($now, 0, 5) > Setting::workingHours()['start'] ? 'late' : 'normal';
        Database::query(
            'INSERT INTO attendance_logs (user_id, date, clock_in, status, remarks) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE clock_in = VALUES(clock_in), status = VALUES(status), remarks = VALUES(remarks)',
            [$userId, $today, $now, $status, $remarks !== '' ? $remarks : null]
        );
        AuditLog::record('Clock In', 'ลงเวลาเข้างาน ' . substr($now, 0, 5) . ' น.' . ($status === 'late' ? ' (สาย)' : ''));

        return null;
    }

    public static function clockOut(string $userId, string $remarks): ?string
    {
        $today = date('Y-m-d');
        $existing = Attendance::forDay($userId, $today);
        if (!$existing || !$existing['clock_in']) {
            return 'กรุณาลงเวลาเข้างานก่อน';
        }
        if ($existing['clock_out']) {
            return 'คุณลงเวลาออกงานวันนี้ไปแล้ว';
        }

        $now = date('H:i:s');
        $otFrom = date('H:i', strtotime(Setting::workingHours()['end'] . ' +' . self::OT_AFTER_MINUTES . ' minutes'));
        // เข้าสายแล้วยังคงสถานะสายไว้ (เหมือนระบบเดิม)
        $status = $existing['status'] !== 'late' && substr($now, 0, 5) > $otFrom ? 'ot' : $existing['status'];
        Database::query(
            'UPDATE attendance_logs SET clock_out = ?, status = ?, remarks = COALESCE(NULLIF(?, \'\'), remarks)
             WHERE id = ? AND clock_out IS NULL',
            [$now, $status, $remarks, $existing['id']]
        );
        AuditLog::record('Clock Out', 'ลงเวลาออกงาน ' . substr($now, 0, 5) . ' น.');

        return null;
    }

    /** ชั่วโมงทำงานจากเวลาเข้า-ออก เช่น "8.5 ชม." */
    public static function workedHours(?string $in, ?string $out): string
    {
        if (!$in || !$out) {
            return '-';
        }
        $hours = (strtotime("1970-01-01 {$out}") - strtotime("1970-01-01 {$in}")) / 3600;

        return number_format(max(0, $hours), 1) . ' ชม.';
    }
}
