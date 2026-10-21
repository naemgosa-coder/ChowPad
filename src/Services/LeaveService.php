<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\Notification;

final class LeaveService
{
    /**
     * ปีงบประมาณ (1 ต.ค. – 30 ก.ย.) ที่วันที่นี้อยู่ ใช้คิดโควตาวันลา
     *
     * @return array{from: string, to: string, label: string}
     */
    public static function fiscalYear(?string $date = null): array
    {
        $d = new \DateTimeImmutable($date ?? 'today');
        $endYear = (int) $d->format('n') >= 10 ? (int) $d->format('Y') + 1 : (int) $d->format('Y');

        return [
            'from' => ($endYear - 1) . '-10-01',
            'to' => $endYear . '-09-30',
            'label' => 'ปีงบประมาณ ' . ($endYear + 543),
        ];
    }

    /**
     * วันทำการในช่วงวันที่ (ไม่นับเสาร์-อาทิตย์และวันหยุดราชการในระบบ)
     *
     * @return string[]
     */
    public static function workingDays(string $from, string $to): array
    {
        $holidays = array_flip(Database::query(
            'SELECT date FROM holidays WHERE date BETWEEN ? AND ?',
            [$from, $to]
        )->fetchAll(\PDO::FETCH_COLUMN));

        $days = [];
        $period = new \DatePeriod(new \DateTimeImmutable($from), new \DateInterval('P1D'), (new \DateTimeImmutable($to))->modify('+1 day'));
        foreach ($period as $day) {
            $iso = $day->format('Y-m-d');
            if ((int) $day->format('N') < 6 && !isset($holidays[$iso])) {
                $days[] = $iso;
            }
        }

        return $days;
    }

    /**
     * โควตาแต่ละประเภทของผู้ใช้ในปีงบประมาณปัจจุบัน
     *
     * @return array<int, array<string, mixed>>
     */
    public static function quotas(string $userId): array
    {
        $fy = self::fiscalYear();
        $usage = LeaveRequest::usage($userId, $fy['from'], $fy['to']);

        return array_map(static function (array $type) use ($usage): array {
            $u = $usage[(int) $type['id']] ?? ['used' => 0.0, 'pending' => 0.0];
            $quota = (float) $type['quota_days'];

            return $type + [
                'used' => $u['used'],
                'pending' => $u['pending'],
                'remaining' => max(0, $quota - $u['used']),
                'available' => max(0, $quota - $u['used'] - $u['pending']),
                'percent' => $quota > 0 ? (int) round(min(1, $u['used'] / $quota) * 100) : 0,
            ];
        }, LeaveRequest::types());
    }

    /**
     * @param array<string, mixed> $input
     * @return array{errors: array<string, string>, data: array<string, mixed>}
     */
    public static function request(string $userId, array $input): array
    {
        $data = [
            'leave_type_id' => (int) ($input['leave_type_id'] ?? 0),
            'start_date' => (string) ($input['start_date'] ?? ''),
            'end_date' => (string) ($input['end_date'] ?? ''),
            'reason' => mb_substr(trim((string) ($input['reason'] ?? '')), 0, 1000),
        ];
        $errors = [];
        $type = LeaveRequest::type($data['leave_type_id']);
        if ($type === null) {
            $errors['leave_type_id'] = 'กรุณาเลือกประเภทการลา';
        }
        foreach (['start_date' => 'วันที่เริ่มลา', 'end_date' => 'วันที่สิ้นสุด'] as $field => $label) {
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $data[$field]);
            if (!$d || $d->format('Y-m-d') !== $data[$field]) {
                $errors[$field] = "กรุณาเลือก{$label}";
            }
        }
        if (!isset($errors['start_date'], $errors['end_date']) && $data['end_date'] < $data['start_date']) {
            $errors['end_date'] = 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มลา';
        }
        if ($data['reason'] === '') {
            $errors['reason'] = 'กรุณาระบุเหตุผลการลา';
        }
        if ($errors) {
            return ['errors' => $errors, 'data' => $data];
        }

        $days = count(self::workingDays($data['start_date'], $data['end_date']));
        if ($days === 0) {
            $errors['end_date'] = 'ช่วงวันที่เลือกไม่มีวันทำการ (เป็นวันหยุดทั้งหมด)';
        } elseif (self::fiscalYear($data['start_date'])['from'] !== self::fiscalYear($data['end_date'])['from']) {
            $errors['end_date'] = 'ใบลาหนึ่งใบต้องอยู่ในปีงบประมาณเดียวกัน กรุณาแยกเป็นสองใบ';
        } elseif (LeaveRequest::overlaps($userId, $data['start_date'], $data['end_date'])) {
            $errors['start_date'] = 'ช่วงวันที่นี้ทับกับใบลาที่คุณยื่นไว้แล้ว';
        } else {
            $fy = self::fiscalYear($data['start_date']);
            $u = LeaveRequest::usage($userId, $fy['from'], $fy['to'])[$type['id']] ?? ['used' => 0, 'pending' => 0];
            $available = (float) $type['quota_days'] - $u['used'] - $u['pending'];
            if ($days > $available) {
                $errors['leave_type_id'] = "จำนวนวันลา ({$days} วันทำการ) เกินสิทธิ์{$type['name']}ที่เหลือ ("
                    . rtrim(rtrim(number_format(max(0, $available), 1), '0'), '.') . ' วัน รวมใบที่รออนุมัติ)';
            }
        }
        if ($errors) {
            return ['errors' => $errors, 'data' => $data];
        }

        Database::query(
            'INSERT INTO leave_requests (user_id, leave_type_id, start_date, end_date, days, reason) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $type['id'], $data['start_date'], $data['end_date'], $days, $data['reason']]
        );
        AuditLog::record('Request Leave', "ยื่นใบ{$type['name']} " . thai_date($data['start_date']) . ' – ' . thai_date($data['end_date']) . " ({$days} วัน)");
        Notification::push(null, 'มีใบลารออนุมัติ', Auth::user()['name'] . " ยื่นใบ{$type['name']} {$days} วัน", 'calendar-minus', 'warning', 'hr');

        return ['errors' => [], 'data' => $data];
    }

    /** @param array<string, mixed> $leave */
    public static function approve(array $leave): ?string
    {
        if ($leave['status'] !== 'pending') {
            return 'ใบลานี้ได้รับการพิจารณาไปแล้ว';
        }
        if ($leave['user_id'] === Auth::id()) {
            return 'ไม่สามารถอนุมัติใบลาของตัวเองได้';
        }

        Database::transaction(static function () use ($leave): void {
            Database::query("UPDATE leave_requests SET status = 'approved', approved_by = ? WHERE id = ? AND status = 'pending'", [Auth::id(), $leave['id']]);
            // ลงบันทึกวันลาในตารางเวลา ยกเว้นวันที่ลงเวลาเข้างานไปแล้ว
            foreach (self::workingDays($leave['start_date'], $leave['end_date']) as $day) {
                Database::query(
                    "INSERT INTO attendance_logs (user_id, date, status, remarks) VALUES (?, ?, 'leave', ?)
                     ON DUPLICATE KEY UPDATE status = IF(clock_in IS NULL, 'leave', status),
                                             remarks = IF(clock_in IS NULL, VALUES(remarks), remarks)",
                    [$leave['user_id'], $day, "{$leave['type_name']}: {$leave['reason']}"]
                );
            }
        });

        AuditLog::record('Approve Leave', "อนุมัติใบ{$leave['type_name']}ของ {$leave['user_name']} (" . (float) $leave['days'] . ' วัน)');
        Notification::push($leave['user_id'], 'ใบลาของคุณได้รับอนุมัติ', "{$leave['type_name']} " . thai_date($leave['start_date']) . ' – ' . thai_date($leave['end_date']), 'circle-check', 'success');

        return null;
    }

    /** @param array<string, mixed> $leave */
    public static function reject(array $leave): ?string
    {
        if ($leave['status'] !== 'pending') {
            return 'ใบลานี้ได้รับการพิจารณาไปแล้ว';
        }
        if ($leave['user_id'] === Auth::id()) {
            return 'ไม่สามารถพิจารณาใบลาของตัวเองได้';
        }

        Database::query("UPDATE leave_requests SET status = 'rejected', approved_by = ? WHERE id = ? AND status = 'pending'", [Auth::id(), $leave['id']]);
        AuditLog::record('Reject Leave', "ไม่อนุมัติใบ{$leave['type_name']}ของ {$leave['user_name']}");
        Notification::push($leave['user_id'], 'ใบลาของคุณไม่ได้รับอนุมัติ', "{$leave['type_name']} " . thai_date($leave['start_date']) . ' – ' . thai_date($leave['end_date']), 'circle-x', 'danger');

        return null;
    }

    /** ผู้ยื่นยกเลิกใบลาที่ยังรออนุมัติได้ (เหมือนระบบเดิม) */
    public static function cancel(array $leave): ?string
    {
        if ($leave['user_id'] !== Auth::id() || $leave['status'] !== 'pending') {
            return 'ยกเลิกได้เฉพาะใบลาของคุณที่ยังรออนุมัติ';
        }
        Database::query("DELETE FROM leave_requests WHERE id = ? AND status = 'pending'", [$leave['id']]);
        AuditLog::record('Cancel Leave', "ยกเลิกใบ{$leave['type_name']} " . thai_date($leave['start_date']));

        return null;
    }
}
