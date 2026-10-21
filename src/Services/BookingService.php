<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Room;
use App\Models\Setting;

final class BookingService
{
    public const ORG_TYPES = ['Government Agency', 'Local Administrative Organization', 'Private Organization'];

    public const DEFAULT_RULES = [
        'max_advance_days' => 90,
        'max_hours' => 8,
        'open_time' => '07:00',
        'close_time' => '20:00',
        'auto_approve' => false,
        'block_holidays' => true,
    ];

    /**
     * กฎการจอง (ยกมาจากหน้าตั้งค่าของ meeting-room-maintenance-dashboard)
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return (Setting::get('booking_rules', []) ?: []) + self::DEFAULT_RULES;
    }

    /**
     * ค่าธรรมเนียม: ชั่วโมงปัดขึ้นทศนิยม 1 ตำแหน่ง × อัตรา แล้วปัดขึ้นเป็นบาท (สูตรเดียวกับเวอร์ชันเดิม)
     *
     * @param array<string, mixed> $room
     * @return array{hours: float, rate: float, fee: float}
     */
    public static function price(array $room, string $orgType, string $start, string $end): array
    {
        $minutes = (strtotime("1970-01-01 {$end}") - strtotime("1970-01-01 {$start}")) / 60;
        $hours = ceil(round($minutes / 60, 4) * 10) / 10;
        $rate = $orgType === 'Government Agency' ? $room['price_gov'] : $room['price_local'];

        return ['hours' => $hours, 'rate' => $rate, 'fee' => ceil($rate * $hours)];
    }

    /**
     * ตรวจข้อมูลฟอร์มจอง คืนข้อมูลที่ทำความสะอาดแล้ว ข้อผิดพลาดรายช่อง และคำเตือน (ไม่บล็อกการจอง)
     *
     * @param array<string, mixed> $input
     * @return array{data: array<string, mixed>, errors: array<string, string>, warnings: string[], room: array<string, mixed>|null}
     */
    public static function validate(array $input): array
    {
        $s = static fn (string $key, int $max = 255): string => mb_substr(trim((string) ($input[$key] ?? '')), 0, $max);
        $data = [
            'org_type' => $s('org_type'),
            'department' => $s('department', 150),
            'title' => $s('title', 120),
            'room_id' => $s('room_id', 10),
            'date' => $s('date', 10),
            'start_time' => $s('start_time', 5),
            'end_time' => $s('end_time', 5),
            'participants' => (int) ($input['participants'] ?? 0),
            'equipment' => array_values(array_intersect(array_keys(Booking::EQUIPMENT), (array) ($input['equipment'] ?? []))),
            'coffee_break' => !empty($input['coffee_break']) ? 1 : 0,
            'organizer' => $s('organizer', 150),
            'phone' => $s('phone', 30),
            'email' => $s('email', 150),
            'remarks' => $s('remarks', 1000),
        ];

        $errors = [];
        $warnings = [];
        $required = [
            'org_type' => 'กรุณาเลือกประเภทหน่วยงานผู้จอง',
            'department' => 'กรุณากรอกชื่อกอง / ฝ่าย / แผนก',
            'title' => 'กรุณากรอกหัวข้อการประชุม',
            'room_id' => 'กรุณาเลือกห้องประชุม',
            'date' => 'กรุณาเลือกวันที่ใช้ห้อง',
            'start_time' => 'กรุณาเลือกเวลาเริ่ม',
            'end_time' => 'กรุณาเลือกเวลาสิ้นสุด',
            'organizer' => 'กรุณากรอกชื่อผู้ประสานงาน',
            'phone' => 'กรุณากรอกเบอร์โทรศัพท์ติดต่อ',
            'email' => 'กรุณากรอกอีเมลผู้ประสานงาน',
        ];
        foreach ($required as $field => $message) {
            if ($data[$field] === '') {
                $errors[$field] = $message;
            }
        }

        if ($data['org_type'] !== '' && !in_array($data['org_type'], self::ORG_TYPES, true)) {
            $errors['org_type'] = 'กรุณาเลือกประเภทหน่วยงานจากรายการ';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'กรุณากรอกอีเมลให้ถูกรูปแบบ เช่น name@example.go.th';
        }
        if ($data['participants'] < 1) {
            $errors['participants'] = 'กรุณากรอกจำนวนผู้เข้าร่วมเป็นตัวเลขตั้งแต่ 1 ขึ้นไป';
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['date']);
        if ($data['date'] !== '' && ($date === false || $date->format('Y-m-d') !== $data['date'])) {
            $errors['date'] = 'กรุณาเลือกวันที่ให้ถูกต้อง';
        } elseif ($date && $data['date'] < date('Y-m-d')) {
            $errors['date'] = 'ไม่สามารถจองย้อนหลังได้ กรุณาเลือกวันนี้หรือวันถัดไป';
        }
        foreach (['start_time', 'end_time'] as $field) {
            if ($data[$field] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $data[$field])) {
                $errors[$field] = 'กรุณาเลือกเวลาให้ถูกรูปแบบ';
            }
        }
        if (
            !isset($errors['start_time'], $errors['end_time']) && $data['start_time'] !== '' && $data['end_time'] !== ''
            && $data['start_time'] >= $data['end_time']
        ) {
            $errors['end_time'] = 'เวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม';
        }
        if (
            !isset($errors['date'], $errors['start_time']) && $data['date'] === date('Y-m-d')
            && $data['start_time'] !== '' && $data['start_time'] < date('H:i')
        ) {
            $errors['start_time'] = 'เวลาเริ่มผ่านไปแล้ว กรุณาเลือกเวลาใหม่';
        }

        $rules = self::rules();
        if (!isset($errors['date']) && $date) {
            $limit = date('Y-m-d', strtotime('+' . (int) $rules['max_advance_days'] . ' days'));
            if ($data['date'] > $limit) {
                $errors['date'] = 'จองล่วงหน้าได้ไม่เกิน ' . (int) $rules['max_advance_days'] . ' วัน (ถึง ' . thai_date($limit) . ')';
            } elseif ($rules['block_holidays'] && ($holiday = Database::value('SELECT name FROM holidays WHERE date = ?', [$data['date']]))) {
                $errors['date'] = "วันที่เลือกเป็นวันหยุดราชการ ({$holiday}) กรุณาเลือกวันอื่น";
            }
        }
        if (
            !isset($errors['start_time'], $errors['end_time']) && $data['start_time'] !== '' && $data['end_time'] !== ''
            && $data['start_time'] < $data['end_time']
        ) {
            if ($data['start_time'] < $rules['open_time'] || $data['end_time'] > $rules['close_time']) {
                $errors['start_time'] = "เปิดให้จองเฉพาะช่วง {$rules['open_time']}–{$rules['close_time']} น.";
            } elseif ((strtotime("1970-01-01 {$data['end_time']}") - strtotime("1970-01-01 {$data['start_time']}")) / 3600 > (float) $rules['max_hours']) {
                $errors['end_time'] = 'จองต่อเนื่องได้ไม่เกิน ' . (float) $rules['max_hours'] . ' ชั่วโมงต่อครั้ง';
            }
        }

        $room = $data['room_id'] !== '' ? Room::find($data['room_id']) : null;
        if ($data['room_id'] !== '' && $room === null) {
            $errors['room_id'] = 'ไม่พบห้องประชุมที่เลือก';
        } elseif ($room && $room['locked']) {
            $errors['room_id'] = 'ห้องนี้ถูกระงับใช้งานชั่วคราว กรุณาเลือกห้องอื่น';
        } elseif ($room && $data['participants'] > Room::maxCapacity($room)) {
            $warnings[] = 'จำนวนผู้เข้าร่วมเกินความจุห้อง (' . $room['capacity'] . ' ที่นั่ง)';
        }

        return ['data' => $data, 'errors' => $errors, 'warnings' => $warnings, 'room' => $room];
    }

    /**
     * บันทึกคำขอจอง ใช้ named lock ให้การตรวจเวลาชนกับการออกเลขที่ทำทีละคำขอ
     *
     * @param array<string, mixed> $data ข้อมูลที่ผ่าน validate() แล้ว
     * @param array<string, mixed> $room
     * @param array<string, mixed> $user
     * @return array{id: string|null, conflict: array<string, mixed>|null, approved?: bool}
     */
    public static function create(array $data, array $room, array $user): array
    {
        if ((int) Database::value("SELECT GET_LOCK('chowpad_booking', 10)") !== 1) {
            throw new \RuntimeException('ระบบไม่ว่าง กรุณาลองใหม่อีกครั้ง');
        }
        try {
            $conflict = Booking::findConflict($room['id'], $data['date'], $data['start_time'], $data['end_time']);
            if ($conflict) {
                return ['id' => null, 'conflict' => $conflict];
            }

            $price = self::price($room, $data['org_type'], $data['start_time'], $data['end_time']);
            $autoApprove = (bool) self::rules()['auto_approve'];
            $id = Booking::nextId();
            Booking::insert($data + [
                'id' => $id,
                'user_id' => $user['id'],
                'status' => $autoApprove ? 'Approved' : 'Waiting Approval',
                'duration_hours' => $price['hours'],
                'rental_fee' => $price['fee'],
            ]);
        } finally {
            Database::value("SELECT RELEASE_LOCK('chowpad_booking')");
        }

        AuditLog::record('Create Booking', "สร้างคำขอจองห้อง {$room['name']} หัวข้อ \"{$data['title']}\" เลขที่ {$id}"
            . ($autoApprove ? ' (อนุมัติอัตโนมัติ)' : ''));
        if (!$autoApprove) {
            Notification::push(
                null,
                'คำขอจองห้องประชุมใหม่รออนุมัติ',
                "{$id}: {$room['name']} หัวข้อ \"{$data['title']}\" โดยคุณ{$data['organizer']}",
                'calendar',
                'warning',
                'approvers'
            );
        }

        return ['id' => $id, 'conflict' => null, 'approved' => $autoApprove];
    }

    /**
     * @param array<string, mixed> $booking
     * @return string|null ข้อความผิดพลาด หรือ null ถ้าสำเร็จ
     */
    public static function approve(array $booking): ?string
    {
        if ($booking['status'] !== 'Waiting Approval') {
            return 'รายการนี้ได้รับการพิจารณาไปแล้ว';
        }
        $conflict = Booking::findConflict(
            $booking['room_id'],
            $booking['date'],
            $booking['start_time'],
            $booking['end_time'],
            $booking['id'],
            true
        );
        if ($conflict) {
            return "ช่วงเวลานี้ชนกับการจอง {$conflict['id']} ({$conflict['start_time']}–{$conflict['end_time']} น.) ที่อนุมัติไปแล้ว";
        }

        Booking::updateStatus($booking['id'], 'Approved', Auth::id());
        AuditLog::record('Approve Booking', "อนุมัติคำขอจองห้องเลขที่ {$booking['id']}");
        Notification::push(
            $booking['user_id'],
            'การจองของคุณได้รับอนุมัติแล้ว',
            "{$booking['id']} {$booking['room_name']} วันที่ " . thai_date($booking['date']) . ' ' . time_range($booking['start_time'], $booking['end_time']),
            'circle-check',
            'success'
        );

        return null;
    }

    /** @param array<string, mixed> $booking */
    public static function reject(array $booking, string $reason): ?string
    {
        if ($booking['status'] !== 'Waiting Approval') {
            return 'รายการนี้ได้รับการพิจารณาไปแล้ว';
        }
        if (trim($reason) === '') {
            return 'กรุณาระบุเหตุผลที่ไม่อนุมัติ';
        }

        Booking::updateStatus($booking['id'], 'Rejected', Auth::id(), $reason);
        AuditLog::record('Reject Booking', "ไม่อนุมัติคำขอจองเลขที่ {$booking['id']} (เหตุผล: {$reason})");
        Notification::push($booking['user_id'], 'การจองของคุณไม่ได้รับอนุมัติ', "{$booking['id']}: {$reason}", 'circle-x', 'danger');

        return null;
    }

    /** @param array<string, mixed> $booking */
    public static function canCancel(array $booking): bool
    {
        $isOwnerOrStaff = $booking['user_id'] === Auth::id() || Auth::hasRole(...config('roles.approvers'));

        return $isOwnerOrStaff
            && in_array($booking['status'], ['Waiting Approval', 'Approved'], true)
            && $booking['date'] >= date('Y-m-d');
    }

    /** @param array<string, mixed> $booking */
    public static function cancel(array $booking): ?string
    {
        if (!self::canCancel($booking)) {
            return 'ไม่สามารถยกเลิกการจองนี้ได้';
        }

        Booking::updateStatus($booking['id'], 'Cancelled');
        AuditLog::record('Cancel Booking', "ยกเลิกการจองเลขที่ {$booking['id']}");

        return null;
    }
}
