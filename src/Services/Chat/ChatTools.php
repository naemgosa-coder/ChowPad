<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Models\Attendance;
use App\Models\Booking;
use App\Models\LeaveRequest;
use App\Models\Repair;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Auth;
use App\Services\BookingService;
use App\Services\Database;
use App\Services\LeaveService;

/**
 * ข้อมูลที่ผู้ช่วยตอบคำถามเข้าถึงได้ ทุกฟังก์ชันอ่านอย่างเดียวและตรวจสิทธิ์ตามบทบาทเหมือนหน้าเว็บ
 * ใช้ร่วมกันทั้งโหมดตอบเอง (ChatRules) และเครื่องมือของ Claude (ClaudeChat)
 */
final class ChatTools
{
    /**
     * คำอธิบายเครื่องมือสำหรับ Claude (ชื่อ => [คำอธิบาย, properties, required])
     *
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string[]}>
     */
    public static function definitions(): array
    {
        $date = ['type' => 'string', 'description' => 'วันที่รูปแบบ YYYY-MM-DD (ค.ศ.)'];
        $time = ['type' => 'string', 'description' => 'เวลา 24 ชั่วโมงรูปแบบ HH:MM'];

        return [
            'room_status_now' => ['สถานะห้องประชุมทุกห้อง ณ ขณะนี้ (ว่าง/กำลังใช้งาน/ระงับใช้งาน) และการจองถัดไปของวันนี้', [], []],
            'find_available_rooms' => [
                'ค้นหาห้องที่ว่างในวันและช่วงเวลาที่ระบุ ระบุจำนวนผู้เข้าร่วมเพื่อตัดห้องที่ความจุไม่พอ',
                ['date' => $date, 'start' => $time, 'end' => $time, 'participants' => ['type' => 'integer', 'description' => 'จำนวนผู้เข้าร่วม (ไม่บังคับ)']],
                ['date', 'start', 'end'],
            ],
            'room_schedule' => [
                'ตารางการใช้ห้องประชุมของวันที่ระบุ (ทุกห้อง หรือเฉพาะห้อง)',
                ['date' => $date, 'room_id' => ['type' => 'string', 'description' => 'รหัสห้อง เช่น RM-001 (ไม่บังคับ)']],
                ['date'],
            ],
            'list_rooms' => ['รายชื่อห้องประชุมทั้งหมด พร้อมอาคาร ชั้น ความจุ อัตราค่าธรรมเนียม อุปกรณ์ และคำเตือน', [], []],
            'my_bookings' => [
                'การจองของผู้ใช้ที่กำลังคุยอยู่',
                ['scope' => ['type' => 'string', 'enum' => ['upcoming', 'recent'], 'description' => 'upcoming = ที่จะถึง, recent = ล่าสุดทั้งหมด']],
                [],
            ],
            'booking_detail' => ['รายละเอียดการจองตามเลขที่ เช่น BK-2026-0001', ['booking_id' => ['type' => 'string']], ['booking_id']],
            'pending_approvals' => ['คำขอจองห้องและใบลาที่รออนุมัติ (เฉพาะผู้มีสิทธิ์อนุมัติ)', [], []],
            'my_repairs' => ['ใบแจ้งซ่อมที่ผู้ใช้แจ้ง หรือสำหรับนายช่างคืองานที่ได้รับมอบหมาย', [], []],
            'repair_detail' => ['รายละเอียดใบงานซ่อมตามเลขที่ เช่น MNT-2026-0001', ['repair_id' => ['type' => 'string']], ['repair_id']],
            'my_leave_balance' => ['สิทธิ์วันลาคงเหลือแต่ละประเภทของผู้ใช้ในปีงบประมาณปัจจุบัน และใบลาล่าสุด', [], []],
            'my_attendance' => [
                'การลงเวลาวันนี้และสรุปการมาทำงานรายเดือนของผู้ใช้',
                ['month' => ['type' => 'string', 'description' => 'เดือนรูปแบบ YYYY-MM (ไม่บังคับ ค่าเริ่มต้นเดือนนี้)']],
                [],
            ],
            'holidays' => ['วันหยุดราชการที่กำลังจะถึง', [], []],
            'booking_rules' => ['กฎการจองห้อง เวลาทำการ และขั้นตอนการใช้งานระบบ', [], []],
            'system_overview' => ['สถิติภาพรวมของระบบ เช่น จำนวนการจอง งานซ่อมค้าง รายได้เดือนนี้ (เฉพาะผู้มีสิทธิ์ดูรายงาน)', [], []],
        ];
    }

    /**
     * เรียกเครื่องมือตามชื่อ คืนข้อมูลเป็น array (แปลงเป็น JSON ก่อนส่งให้ Claude)
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function call(string $name, array $input): array
    {
        if (!array_key_exists($name, self::definitions())) {
            return ['error' => "ไม่รู้จักเครื่องมือ {$name}"];
        }

        return match ($name) {
            'room_status_now' => self::roomStatusNow(),
            'find_available_rooms' => self::findAvailableRooms(
                (string) ($input['date'] ?? ''),
                (string) ($input['start'] ?? ''),
                (string) ($input['end'] ?? ''),
                (int) ($input['participants'] ?? 0)
            ),
            'room_schedule' => self::roomSchedule((string) ($input['date'] ?? date('Y-m-d')), (string) ($input['room_id'] ?? '')),
            'list_rooms' => self::listRooms(),
            'my_bookings' => self::myBookings(($input['scope'] ?? 'upcoming') === 'recent' ? 'recent' : 'upcoming'),
            'booking_detail' => self::bookingDetail((string) ($input['booking_id'] ?? '')),
            'pending_approvals' => self::pendingApprovals(),
            'my_repairs' => self::myRepairs(),
            'repair_detail' => self::repairDetail((string) ($input['repair_id'] ?? '')),
            'my_leave_balance' => self::myLeaveBalance(),
            'my_attendance' => self::myAttendance((string) ($input['month'] ?? '')),
            'holidays' => self::holidays(),
            'booking_rules' => self::bookingRules(),
            'system_overview' => self::systemOverview(),
        };
    }

    /** @return array<string, mixed> */
    public static function roomStatusNow(): array
    {
        return [
            'as_of' => thai_date(date('Y-m-d')) . ' ' . date('H:i') . ' น.',
            'rooms' => array_map(
                static fn ($r) => ['id' => $r['id'], 'name' => $r['name'], 'status' => $r['label'], 'detail' => $r['line']],
                Room::liveStatus()
            ),
        ];
    }

    /** @return array<string, mixed> */
    public static function findAvailableRooms(string $date, string $start, string $end, int $participants = 0): array
    {
        if (!self::isDate($date) || !self::isTime($start) || !self::isTime($end) || $start >= $end) {
            return ['error' => 'ต้องระบุวันที่ (YYYY-MM-DD) เวลาเริ่มและเวลาสิ้นสุด (HH:MM) โดยเวลาสิ้นสุดอยู่หลังเวลาเริ่ม'];
        }
        $busy = Booking::busyRooms($date, $start, $end);
        $rooms = [];
        foreach (Room::all() as $room) {
            $state = match (true) {
                $room['locked'] => 'ระงับใช้งาน',
                isset($busy[$room['id']]) => 'ไม่ว่าง (' . time_range($busy[$room['id']]['start_time'], $busy[$room['id']]['end_time']) . ')',
                $participants > Room::maxCapacity($room) => 'ว่าง แต่ความจุไม่พอ',
                default => 'ว่าง',
            };
            $rooms[] = ['id' => $room['id'], 'name' => $room['name'], 'capacity' => $room['capacity'], 'status' => $state];
        }

        return [
            'when' => thai_date($date, 'long') . ' ' . time_range($start, $end),
            'rooms' => $rooms,
            'book_url' => '/bookings/create?date=' . $date,
        ];
    }

    /** @return array<string, mixed> */
    public static function roomSchedule(string $date, string $roomId = ''): array
    {
        if (!self::isDate($date)) {
            return ['error' => 'วันที่ต้องเป็นรูปแบบ YYYY-MM-DD'];
        }
        $rows = array_filter(Booking::forDate($date), static fn ($b) => $roomId === '' || $b['room_id'] === $roomId);

        return [
            'date' => thai_date($date, 'long'),
            'bookings' => array_values(array_map(static fn ($b) => [
                'time' => time_range($b['start_time'], $b['end_time']),
                'room' => $b['room_name'],
                'title' => $b['title'],
                'status' => status_meta('booking', $b['status'])['label'],
            ], $rows)),
            'calendar_url' => '/calendar' . ($roomId !== '' ? '?room=' . $roomId : ''),
        ];
    }

    /** @return array<string, mixed> */
    public static function listRooms(): array
    {
        return ['rooms' => array_map(static fn ($r) => [
            'id' => $r['id'],
            'name' => $r['name'],
            'location' => "{$r['building']} ชั้น {$r['floor']}",
            'capacity' => $r['capacity'] . ' ที่นั่ง',
            'rate_per_hour' => 'ราชการ ' . money($r['price_gov']) . ' / ทั่วไป ' . money($r['price_local']),
            'equipment' => implode(', ', $r['equipment']),
            'warning' => $r['warnings'] ?: null,
            'locked' => $r['locked'],
        ], Room::all())];
    }

    /** @return array<string, mixed> */
    public static function myBookings(string $scope = 'upcoming'): array
    {
        $where = $scope === 'upcoming'
            ? "AND b.date >= CURDATE() AND b.status IN ('Waiting Approval', 'Approved') ORDER BY b.date, b.start_time"
            : 'ORDER BY b.created_at DESC, b.id DESC';
        $rows = Database::all(
            "SELECT b.id, b.title, b.date, b.start_time, b.end_time, b.status, r.name AS room_name
             FROM bookings b JOIN rooms r ON r.id = b.room_id WHERE b.user_id = ? {$where} LIMIT 10",
            [Auth::id()]
        );

        return ['scope' => $scope === 'upcoming' ? 'การจองที่จะถึง' : 'การจองล่าสุด', 'bookings' => array_map(static fn ($b) => [
            'id' => $b['id'],
            'title' => $b['title'],
            'room' => $b['room_name'],
            'when' => thai_date($b['date']) . ' ' . time_range($b['start_time'], $b['end_time']),
            'status' => status_meta('booking', $b['status'])['label'],
            'url' => '/bookings/' . $b['id'],
        ], $rows)];
    }

    /** @return array<string, mixed> */
    public static function bookingDetail(string $id): array
    {
        $b = Booking::find(strtoupper(trim($id)));
        if ($b === null) {
            return ['error' => "ไม่พบการจองเลขที่ {$id}"];
        }
        if ($b['user_id'] !== Auth::id() && !Auth::hasRole(...config('roles.approvers'))) {
            return ['error' => 'การจองนี้ไม่ใช่ของคุณ จึงดูรายละเอียดไม่ได้'];
        }

        return [
            'id' => $b['id'],
            'title' => $b['title'],
            'room' => $b['room_name'],
            'when' => thai_date($b['date'], 'long') . ' ' . time_range($b['start_time'], $b['end_time']),
            'participants' => (int) $b['participants'],
            'organizer' => $b['organizer'],
            'fee' => money($b['rental_fee']),
            'status' => status_meta('booking', $b['status'])['label'],
            'rejection_reason' => $b['review_note'] ?: null,
            'url' => '/bookings/' . $b['id'],
        ];
    }

    /** @return array<string, mixed> */
    public static function pendingApprovals(): array
    {
        $result = [];
        if (Auth::hasRole(...config('roles.approvers'))) {
            $pending = Booking::pending();
            $result['bookings'] = [
                'count' => count($pending),
                'items' => array_map(static fn ($b) => [
                    'id' => $b['id'],
                    'title' => $b['title'],
                    'room' => $b['room_name'],
                    'when' => thai_date($b['date']) . ' ' . time_range($b['start_time'], $b['end_time']),
                ], array_slice($pending, 0, 10)),
                'url' => '/bookings/approval',
            ];
        }
        if (Auth::hasRole(...config('roles.hr'))) {
            $leaves = LeaveRequest::pending();
            $result['leaves'] = ['count' => count($leaves), 'url' => '/leave/approval'];
        }

        return $result ?: ['error' => 'คุณไม่มีสิทธิ์อนุมัติ ดูสถานะคำขอของตัวเองได้จากการจองของฉันหรือใบลา'];
    }

    /** @return array<string, mixed> */
    public static function myRepairs(): array
    {
        $isTech = Auth::hasRole('Technician');
        $rows = Database::all(
            'SELECT m.id, m.category, m.priority, m.status, m.date, r.name AS room_name FROM repairs m
             JOIN rooms r ON r.id = m.room_id WHERE ' . ($isTech ? 'm.technician_id' : 'm.reported_by') . " = ?
             ORDER BY FIELD(m.status, 'Waiting', 'Accepted', 'In Progress', 'Completed', 'Cancelled'), m.date DESC LIMIT 10",
            [Auth::id()]
        );

        return ['scope' => $isTech ? 'งานที่ได้รับมอบหมาย' : 'ใบแจ้งซ่อมที่คุณแจ้ง', 'repairs' => array_map(static fn ($m) => [
            'id' => $m['id'],
            'room' => $m['room_name'],
            'category' => Repair::categoryLabel($m['category']),
            'priority' => status_meta('priority', $m['priority'])['label'],
            'status' => status_meta('repair', $m['status'])['label'],
            'reported' => thai_date($m['date']),
            'url' => '/maintenance/' . $m['id'],
        ], $rows)];
    }

    /** @return array<string, mixed> */
    public static function repairDetail(string $id): array
    {
        $m = Repair::find(strtoupper(trim($id)));
        if ($m === null) {
            return ['error' => "ไม่พบใบงานเลขที่ {$id}"];
        }
        if ($m['reported_by'] !== Auth::id() && !Auth::hasRole(...config('roles.maintenance_staff'))) {
            return ['error' => 'ใบงานนี้ไม่ได้แจ้งโดยคุณ จึงดูรายละเอียดไม่ได้'];
        }

        return [
            'id' => $m['id'],
            'room' => $m['room_name'],
            'category' => Repair::categoryLabel($m['category']),
            'description' => $m['description'],
            'priority' => status_meta('priority', $m['priority'])['label'],
            'status' => status_meta('repair', $m['status'])['label'],
            'technician' => $m['technician_name'] ?? 'ยังไม่มอบหมาย',
            'reported' => thai_date($m['date']),
            'result' => $m['status'] === 'Completed' ? $m['repair_notes'] : null,
            'url' => '/maintenance/' . $m['id'],
        ];
    }

    /** @return array<string, mixed> */
    public static function myLeaveBalance(): array
    {
        $fy = LeaveService::fiscalYear();

        return [
            'fiscal_year' => $fy['label'],
            'quotas' => array_map(static fn ($q) => [
                'type' => $q['name'],
                'quota_days' => (int) $q['quota_days'],
                'used_days' => num($q['used']),
                'pending_days' => num($q['pending']),
                'remaining_days' => num($q['remaining']),
            ], LeaveService::quotas(Auth::id())),
            'recent_requests' => array_map(static fn ($l) => [
                'type' => $l['type_name'],
                'dates' => thai_date($l['start_date']) . ($l['end_date'] !== $l['start_date'] ? ' – ' . thai_date($l['end_date']) : ''),
                'days' => num($l['days']),
                'status' => status_meta('leave', $l['status'])['label'],
            ], array_slice(LeaveRequest::forUser(Auth::id()), 0, 5)),
            'url' => '/leave',
        ];
    }

    /** @return array<string, mixed> */
    public static function myAttendance(string $month = ''): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $from = "{$month}-01";
        $to = min(date('Y-m-t', strtotime($from)), date('Y-m-d'));
        $today = Attendance::forDay(Auth::id(), date('Y-m-d'));
        $counts = Attendance::countByStatus(Auth::id(), $from, $to);

        return [
            'today' => $today === null ? 'ยังไม่ลงเวลา' : [
                'status' => status_meta('attendance', $today['status'])['label'],
                'clock_in' => $today['clock_in'] ? substr($today['clock_in'], 0, 5) . ' น.' : null,
                'clock_out' => $today['clock_out'] ? substr($today['clock_out'], 0, 5) . ' น.' : null,
            ],
            'month' => thai_date($from, 'month'),
            'summary_days' => [
                'ปกติ' => $counts['normal'] ?? 0,
                'สาย' => $counts['late'] ?? 0,
                'ล่วงเวลา' => $counts['ot'] ?? 0,
                'ลา' => $counts['leave'] ?? 0,
            ],
            'working_hours' => Setting::workingHoursText() . ' น.',
            'url' => '/attendance',
        ];
    }

    /** @return array<string, mixed> */
    public static function holidays(): array
    {
        return ['holidays' => array_map(
            static fn ($h) => ['date' => thai_date($h['date'], 'long'), 'name' => $h['name']],
            Database::all('SELECT date, name FROM holidays WHERE date >= CURDATE() ORDER BY date LIMIT 10')
        )];
    }

    /** @return array<string, mixed> */
    public static function bookingRules(): array
    {
        $r = BookingService::rules();

        return [
            'advance_booking_max_days' => (int) $r['max_advance_days'],
            'max_hours_per_booking' => (float) $r['max_hours'],
            'booking_window' => "{$r['open_time']}–{$r['close_time']} น.",
            'holidays_closed' => (bool) $r['block_holidays'],
            'auto_approve' => (bool) $r['auto_approve'],
            'working_hours' => Setting::workingHoursText() . ' น.',
            'how_to' => [
                'จองห้อง' => 'เมนู จองห้องประชุม (/bookings/create) เลือกวันเวลา กด "ตรวจสอบห้องว่าง" เลือกห้อง กรอกรายละเอียด แล้วกดตรวจสอบและยืนยัน',
                'ยกเลิกการจอง' => 'เปิดรายละเอียดการจองจากประวัติการจอง (/bookings) แล้วกด "ยกเลิกการจอง" (ได้เฉพาะที่ยังไม่ถึงวันใช้ห้อง)',
                'แจ้งซ่อม' => 'เมนู แจ้งซ่อม (/maintenance/create) เลือกห้อง หมวดหมู่ ความเร่งด่วน อธิบายปัญหา และแนบรูปได้',
                'ยื่นใบลา' => 'เมนู ใบลา (/leave) เลือกประเภท วันที่ และเหตุผล ระบบนับเฉพาะวันทำการ',
                'ลงเวลา' => 'เมนู ลงเวลาปฏิบัติงาน (/attendance) กดลงเวลาเข้างานและออกงาน',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function systemOverview(): array
    {
        if (!Auth::hasRole(...config('roles.reports'))) {
            return ['error' => 'ข้อมูลภาพรวมระบบดูได้เฉพาะผู้ดูแลระบบ เจ้าหน้าที่ฝ่ายบริหาร และผู้บริหาร'];
        }
        $repairs = Repair::countByStatus();

        return [
            'today_bookings' => count(Booking::forDate(date('Y-m-d'))),
            'pending_booking_approvals' => (int) Database::value("SELECT COUNT(*) FROM bookings WHERE status = 'Waiting Approval'"),
            'open_repairs' => ($repairs['Waiting'] ?? 0) + ($repairs['Accepted'] ?? 0) + ($repairs['In Progress'] ?? 0),
            'revenue_this_month' => money((float) Database::value(
                "SELECT COALESCE(SUM(rental_fee), 0) FROM bookings WHERE status IN ('Approved', 'Completed') AND date BETWEEN ? AND ?",
                [date('Y-m-01'), date('Y-m-t')]
            )),
            'active_users' => (int) Database::value("SELECT COUNT(*) FROM users WHERE status = 'Active'"),
            'reports_url' => '/reports',
        ];
    }

    private static function isDate(string $value): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $d !== false && $d->format('Y-m-d') === $value;
    }

    private static function isTime(string $value): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value);
    }
}
