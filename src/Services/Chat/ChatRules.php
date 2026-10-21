<?php

declare(strict_types=1);

namespace App\Services\Chat;

use App\Services\Auth;

/**
 * ตอบคำถามที่พบบ่อยด้วยการจับคำสำคัญภาษาไทย ใช้ได้โดยไม่ต้องต่ออินเทอร์เน็ตหรือมี API key
 * คืน null เมื่อไม่เข้าใจคำถาม (ChatService จะส่งต่อให้ Claude ถ้าเปิดใช้)
 */
final class ChatRules
{
    /** @return array{reply: string, links: array<int, array{label: string, url: string}>}|null */
    public static function answer(string $question): ?array
    {
        $q = mb_strtolower(preg_replace('/\s+/u', '', $question));

        if (preg_match('/bk-\d{4}-\d{4}/i', $question, $m)) {
            return self::booking(strtoupper($m[0]));
        }
        if (preg_match('/mnt-\d{4}-\d{4}/i', $question, $m)) {
            return self::repair(strtoupper($m[0]));
        }

        $intents = [
            'help' => ['สวัสดี', 'ทำอะไรได้', 'ช่วยอะไร', 'วิธีใช้', 'help', 'hello', 'hi'],
            'available' => ['ห้องว่าง', 'ห้องไหนว่าง', 'ว่างไหม', 'ว่างมั้ย', 'ว่างบ้าง', 'ห้องไหนใช้ได้'],
            'schedule' => ['ตารางห้อง', 'ตารางการใช้', 'ใครใช้ห้อง', 'ประชุมอะไร', 'การจองวันนี้', 'จองวันนี้', 'การจองพรุ่งนี้'],
            'approvals' => ['รออนุมัติ', 'ต้องอนุมัติ', 'อนุมัติค้าง', 'ค้างอนุมัติ'],
            'myBookings' => ['การจองของฉัน', 'จองของฉัน', 'ฉันจอง', 'จองไว้', 'การจองของผม', 'การจองของหนู', 'จองอะไรไว้', 'ประวัติการจอง'],
            'howBook' => ['จองห้องยังไง', 'วิธีจอง', 'จองยังไง', 'อยากจองห้อง', 'จองห้องอย่างไร', 'ยกเลิกการจอง', 'ยกเลิกจอง'],
            'repairs' => ['งานซ่อม', 'แจ้งซ่อม', 'สถานะซ่อม', 'ใบงาน', 'ซ่อมเสร็จ', 'ช่างมา'],
            'leave' => ['วันลา', 'ลาเหลือ', 'สิทธิ์ลา', 'สิทธิลา', 'โควตา', 'ลาได้อีก', 'ลาป่วย', 'ลากิจ', 'ลาพักผ่อน', 'ใบลา'],
            'attendance' => ['ลงเวลา', 'เข้างาน', 'ออกงาน', 'มาสาย', 'มาทำงาน', 'เวลาทำงาน'],
            'holidays' => ['วันหยุด', 'หยุดวันไหน', 'หยุดราชการ'],
            'rules' => ['กฎการจอง', 'จองล่วงหน้า', 'จองได้กี่', 'เวลาทำการ', 'ค่าห้อง', 'ค่าธรรมเนียม', 'ราคาห้อง', 'อัตรา'],
            'rooms' => ['ห้องประชุมมีอะไร', 'มีห้องอะไร', 'รายชื่อห้อง', 'ห้องทั้งหมด', 'ความจุ', 'จุได้กี่คน', 'อุปกรณ์'],
            'overview' => ['ภาพรวม', 'สถิติ', 'รายได้', 'สรุประบบ'],
        ];
        foreach ($intents as $intent => $words) {
            foreach ($words as $word) {
                if (str_contains($q, $word)) {
                    return self::{$intent}($question, $q);
                }
            }
        }

        return null;
    }

    /** วันที่จากคำว่า วันนี้/พรุ่งนี้/มะรืน หรือ วว/ดด(/ปปปป พ.ศ. หรือ ค.ศ.) */
    public static function parseDate(string $question): ?string
    {
        $q = preg_replace('/\s+/u', '', $question);
        if (str_contains($q, 'มะรืน')) {
            return date('Y-m-d', strtotime('+2 days'));
        }
        if (str_contains($q, 'พรุ่งนี้')) {
            return date('Y-m-d', strtotime('+1 day'));
        }
        if (str_contains($q, 'วันนี้')) {
            return date('Y-m-d');
        }
        if (preg_match('#(\d{1,2})/(\d{1,2})(?:/(\d{4}))?#', $question, $m)) {
            $year = isset($m[3]) ? (int) $m[3] : (int) date('Y');
            $year = $year > 2400 ? $year - 543 : $year;
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string}|null ช่วงเวลาจาก "13:00-15:00" หรือ "9.00 ถึง 10.30" */
    public static function parseTimeRange(string $question): ?array
    {
        if (preg_match_all('/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/', $question, $m, PREG_SET_ORDER) && count($m) >= 2) {
            $start = sprintf('%02d:%s', $m[0][1], $m[0][2]);
            $end = sprintf('%02d:%s', $m[1][1], $m[1][2]);

            return $start < $end ? [$start, $end] : null;
        }

        return null;
    }

    private static function link(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url];
    }

    /** @return array{reply: string, links: array<int, array{label: string, url: string}>} */
    private static function reply(string $text, array $links = []): array
    {
        return ['reply' => $text, 'links' => $links];
    }

    private static function help(): array
    {
        $lines = [
            'สวัสดีคุณ' . Auth::user()['name'] . ' ถามข้อมูลในระบบได้ เช่น',
            '• ห้องไหนว่างตอนนี้ / ห้องว่างพรุ่งนี้ 13:00-15:00',
            '• การจองของฉัน หรือพิมพ์เลขที่ เช่น BK-2026-0001',
            '• สถานะแจ้งซ่อมของฉัน หรือเลขใบงาน MNT-…',
            '• วันลาเหลือเท่าไร / ลงเวลาวันนี้แล้วหรือยัง',
            '• วันหยุดที่จะถึง / กฎการจอง / ค่าห้อง',
        ];
        if (Auth::hasRole(...config('roles.approvers'))) {
            $lines[] = '• มีอะไรรออนุมัติ';
        }

        return self::reply(implode("\n", $lines));
    }

    private static function available(string $question): array
    {
        $date = self::parseDate($question);
        $range = self::parseTimeRange($question);

        if ($date !== null && $range !== null) {
            $data = ChatTools::findAvailableRooms($date, $range[0], $range[1]);
            $free = array_filter($data['rooms'], static fn ($r) => $r['status'] === 'ว่าง');
            $lines = ["ห้องประชุม {$data['when']}:"];
            foreach ($data['rooms'] as $r) {
                $lines[] = "• {$r['name']}: {$r['status']}";
            }
            $lines[] = $free ? 'มีห้องว่าง ' . count($free) . ' ห้อง' : 'ไม่มีห้องว่างในช่วงนี้ ลองเปลี่ยนเวลาดู';

            return self::reply(implode("\n", $lines), [self::link('จองห้องวันนั้น', $data['book_url'])]);
        }

        if ($date !== null && $date !== date('Y-m-d')) {
            return self::schedule($question);
        }

        $data = ChatTools::roomStatusNow();
        $lines = ["สถานะห้อง ณ {$data['as_of']}:"];
        foreach ($data['rooms'] as $r) {
            $lines[] = "• {$r['name']}: {$r['status']} ({$r['detail']})";
        }
        $lines[] = 'ถ้าต้องการเช็กช่วงเวลาอื่น พิมพ์เช่น "ห้องว่างพรุ่งนี้ 13:00-15:00"';

        return self::reply(implode("\n", $lines), [self::link('ดูห้องทั้งหมด', '/rooms'), self::link('จองห้องประชุม', '/bookings/create')]);
    }

    private static function schedule(string $question): array
    {
        $date = self::parseDate($question) ?? date('Y-m-d');
        $data = ChatTools::roomSchedule($date);
        if (!$data['bookings']) {
            return self::reply("{$data['date']} ยังไม่มีการจองห้อง ทุกห้องว่างตลอดวัน", [self::link('จองห้องประชุม', '/bookings/create?date=' . $date)]);
        }
        $lines = ["การใช้ห้อง {$data['date']} (" . count($data['bookings']) . ' รายการ):'];
        foreach ($data['bookings'] as $b) {
            $lines[] = "• {$b['time']} {$b['room']} · {$b['title']} ({$b['status']})";
        }

        return self::reply(implode("\n", $lines), [self::link('ดูปฏิทิน', '/calendar')]);
    }

    private static function approvals(): array
    {
        $data = ChatTools::pendingApprovals();
        if (isset($data['error'])) {
            $mine = ChatTools::myBookings('upcoming');
            $waiting = array_filter($mine['bookings'], static fn ($b) => $b['status'] === 'รออนุมัติ');

            return self::reply(
                $waiting ? 'การจองของคุณที่รออนุมัติ ' . count($waiting) . " รายการ:\n" . implode("\n", array_map(static fn ($b) => "• {$b['id']} {$b['title']} ({$b['when']})", $waiting))
                    : 'ไม่มีการจองของคุณที่รออนุมัติ',
                [self::link('ประวัติการจอง', '/bookings')]
            );
        }
        $lines = [];
        $links = [];
        if (isset($data['bookings'])) {
            $lines[] = "คำขอจองห้องรออนุมัติ {$data['bookings']['count']} รายการ";
            foreach (array_slice($data['bookings']['items'], 0, 5) as $b) {
                $lines[] = "• {$b['id']} {$b['room']} {$b['when']}";
            }
            $links[] = self::link('ไปหน้าอนุมัติการจอง', '/bookings/approval');
        }
        if (isset($data['leaves'])) {
            $lines[] = "ใบลารออนุมัติ {$data['leaves']['count']} รายการ";
            $links[] = self::link('ไปหน้าอนุมัติใบลา', '/leave/approval');
        }

        return self::reply(implode("\n", $lines), $links);
    }

    private static function myBookings(): array
    {
        $data = ChatTools::myBookings('upcoming');
        if (!$data['bookings']) {
            return self::reply('คุณไม่มีการจองที่กำลังจะถึง', [self::link('จองห้องประชุม', '/bookings/create'), self::link('ประวัติการจอง', '/bookings')]);
        }
        $lines = ['การจองที่จะถึงของคุณ:'];
        foreach ($data['bookings'] as $b) {
            $lines[] = "• {$b['id']} {$b['title']}\n  {$b['room']} · {$b['when']} · {$b['status']}";
        }

        return self::reply(implode("\n", $lines), [self::link('ประวัติการจองทั้งหมด', '/bookings')]);
    }

    private static function booking(string $id): array
    {
        $b = ChatTools::bookingDetail($id);
        if (isset($b['error'])) {
            return self::reply($b['error']);
        }
        $text = "การจอง {$b['id']}: {$b['title']}\n• ห้อง: {$b['room']}\n• เวลา: {$b['when']}\n• ผู้เข้าร่วม: {$b['participants']} คน\n• ค่าธรรมเนียม: {$b['fee']}\n• สถานะ: {$b['status']}";
        if ($b['rejection_reason']) {
            $text .= "\n• เหตุผลที่ไม่อนุมัติ: {$b['rejection_reason']}";
        }

        return self::reply($text, [self::link('เปิดรายละเอียด', $b['url'])]);
    }

    private static function howBook(): array
    {
        $rules = ChatTools::bookingRules();

        return self::reply(
            "วิธีจองห้อง: {$rules['how_to']['จองห้อง']}\n\nวิธียกเลิก: {$rules['how_to']['ยกเลิกการจอง']}\n\n"
            . "จองล่วงหน้าได้ไม่เกิน {$rules['advance_booking_max_days']} วัน ครั้งละไม่เกิน {$rules['max_hours_per_booking']} ชั่วโมง ช่วง {$rules['booking_window']}",
            [self::link('จองห้องประชุม', '/bookings/create')]
        );
    }

    private static function repairs(): array
    {
        $data = ChatTools::myRepairs();
        if (!$data['repairs']) {
            return self::reply("ไม่มี{$data['scope']}", [self::link('แจ้งซ่อม', '/maintenance/create')]);
        }
        $lines = ["{$data['scope']}:"];
        foreach (array_slice($data['repairs'], 0, 6) as $m) {
            $lines[] = "• {$m['id']} {$m['room']} · {$m['category']}\n  {$m['priority']} · {$m['status']} · แจ้ง {$m['reported']}";
        }

        return self::reply(implode("\n", $lines), [self::link('ใบงานซ่อมทั้งหมด', Auth::hasRole('Technician') ? '/technician' : '/maintenance'), self::link('แจ้งซ่อมใหม่', '/maintenance/create')]);
    }

    private static function repair(string $id): array
    {
        $m = ChatTools::repairDetail($id);
        if (isset($m['error'])) {
            return self::reply($m['error']);
        }
        $text = "ใบงาน {$m['id']} ({$m['room']})\n• ปัญหา: {$m['category']} — {$m['description']}\n• ความเร่งด่วน: {$m['priority']}\n• สถานะ: {$m['status']}\n• ช่าง: {$m['technician']}";
        if ($m['result']) {
            $text .= "\n• ผลการซ่อม: {$m['result']}";
        }

        return self::reply($text, [self::link('เปิดใบงาน', $m['url'])]);
    }

    private static function leave(): array
    {
        $data = ChatTools::myLeaveBalance();
        $lines = ["สิทธิ์วันลาของคุณ ({$data['fiscal_year']}):"];
        foreach ($data['quotas'] as $q) {
            $pending = $q['pending_days'] !== '0' ? " (รออนุมัติ {$q['pending_days']} วัน)" : '';
            $lines[] = "• {$q['type']}: เหลือ {$q['remaining_days']} จาก {$q['quota_days']} วัน{$pending}";
        }

        return self::reply(implode("\n", $lines), [self::link('ยื่นใบลา', '/leave')]);
    }

    private static function attendance(): array
    {
        $data = ChatTools::myAttendance();
        $today = is_array($data['today'])
            ? "วันนี้: {$data['today']['status']}" . ($data['today']['clock_in'] ? " เข้างาน {$data['today']['clock_in']}" : '') . ($data['today']['clock_out'] ? " ออกงาน {$data['today']['clock_out']}" : ' (ยังไม่ลงเวลาออก)')
            : 'วันนี้คุณยังไม่ได้ลงเวลาเข้างาน';
        $s = $data['summary_days'];

        return self::reply(
            "{$today}\nสรุป{$data['month']}: ปกติ {$s['ปกติ']} วัน · สาย {$s['สาย']} วัน · ล่วงเวลา {$s['ล่วงเวลา']} วัน · ลา {$s['ลา']} วัน\nเวลาทำการ {$data['working_hours']}",
            [self::link('ไปหน้าลงเวลา', '/attendance'), self::link('ประวัติการลงเวลา', '/attendance/history')]
        );
    }

    private static function holidays(): array
    {
        $data = ChatTools::holidays();
        if (!$data['holidays']) {
            return self::reply('ยังไม่มีวันหยุดที่จะถึงในระบบ');
        }

        return self::reply("วันหยุดที่จะถึง:\n" . implode("\n", array_map(static fn ($h) => "• {$h['date']} — {$h['name']}", $data['holidays'])));
    }

    private static function rules(): array
    {
        $r = ChatTools::bookingRules();
        $rooms = ChatTools::listRooms()['rooms'];
        $rates = array_map(static fn ($room) => "• {$room['name']}: {$room['rate_per_hour']} ต่อชั่วโมง", $rooms);

        return self::reply(
            "กฎการจอง:\n• จองล่วงหน้าได้ไม่เกิน {$r['advance_booking_max_days']} วัน\n• ครั้งละไม่เกิน {$r['max_hours_per_booking']} ชั่วโมง ในช่วง {$r['booking_window']}\n"
            . ($r['holidays_closed'] ? "• ไม่เปิดจองวันหยุดราชการ\n" : '')
            . ($r['auto_approve'] ? "• ระบบอนุมัติอัตโนมัติเมื่อไม่มีเวลาชน\n" : "• ทุกคำขอต้องรอเจ้าหน้าที่อนุมัติ\n")
            . "• เวลาทำการ {$r['working_hours']}\n\nอัตราค่าธรรมเนียม:\n" . implode("\n", $rates),
            [self::link('จองห้องประชุม', '/bookings/create')]
        );
    }

    private static function rooms(): array
    {
        $lines = ['ห้องประชุมทั้งหมด:'];
        foreach (ChatTools::listRooms()['rooms'] as $r) {
            $lines[] = "• {$r['name']} ({$r['location']}) {$r['capacity']}" . ($r['locked'] ? ' — ระงับใช้งาน' : '') . "\n  อุปกรณ์: {$r['equipment']}";
        }

        return self::reply(implode("\n", $lines), [self::link('ดูห้องประชุม', '/rooms')]);
    }

    private static function overview(): array
    {
        $d = ChatTools::systemOverview();
        if (isset($d['error'])) {
            return self::reply($d['error']);
        }

        return self::reply(
            "ภาพรวมระบบวันนี้:\n• การจองวันนี้ {$d['today_bookings']} รายการ\n• คำขอจองรออนุมัติ {$d['pending_booking_approvals']} รายการ\n• งานซ่อมที่ยังไม่ปิด {$d['open_repairs']} งาน\n• รายได้เดือนนี้ {$d['revenue_this_month']}\n• ผู้ใช้งาน {$d['active_users']} คน",
            [self::link('รายงานสถิติ', '/reports')]
        );
    }
}
