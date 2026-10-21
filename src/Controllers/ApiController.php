<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\Auth;
use App\Services\Database;

/** JSON endpoints ที่หน้าเว็บเรียกผ่าน App.api() */
final class ApiController extends Controller
{
    /** สถานะห้องปัจจุบัน หน้า dashboard และหน้าห้องประชุมดึงซ้ำทุก REALTIME_POLL_SECONDS */
    public function roomStatus(): void
    {
        $this->json(['updated_at' => date('H:i'), 'rooms' => Room::liveStatus()]);
    }

    /** ห้องทั้งหมดพร้อมบอกว่าว่างในช่วงเวลาที่ระบุหรือไม่ (ใช้ในฟอร์มจอง) */
    public function availability(): void
    {
        $date = (string) ($_GET['date'] ?? '');
        $start = (string) ($_GET['start'] ?? '');
        $end = (string) ($_GET['end'] ?? '');
        $participants = (int) ($_GET['participants'] ?? 0);

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $start)
            || !preg_match('/^\d{2}:\d{2}$/', $end) || $start >= $end
        ) {
            $this->json(['error' => 'กรุณาเลือกวันที่ เวลาเริ่ม และเวลาสิ้นสุดให้ถูกต้อง'], 422);
            return;
        }

        $busy = Booking::busyRooms($date, $start, $end);
        $rooms = array_map(static function (array $room) use ($busy, $participants): array {
            $conflict = $busy[$room['id']] ?? null;
            $state = match (true) {
                $room['locked'] => 'locked',
                $conflict !== null => 'busy',
                $participants > Room::maxCapacity($room) => 'too-small',
                default => 'available',
            };

            return [
                'id' => $room['id'],
                'name' => $room['name'],
                'building' => $room['building'],
                'floor' => $room['floor'],
                'capacity' => $room['capacity'],
                'equipment' => $room['equipment'],
                'state' => $state,
                'conflict' => $conflict ? time_range($conflict['start_time'], $conflict['end_time']) : null,
            ];
        }, Room::all());

        $this->json(['rooms' => $rooms]);
    }

    /** เหตุการณ์สำหรับ FullCalendar */
    public function calendarEvents(): void
    {
        $from = substr((string) ($_GET['start'] ?? ''), 0, 10);
        $to = substr((string) ($_GET['end'] ?? ''), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $this->json(['error' => 'ช่วงวันที่ไม่ถูกต้อง'], 422);
            return;
        }
        $roomIds = array_values(array_filter((array) ($_GET['rooms'] ?? []), 'is_string'));
        $isStaff = Auth::hasRole(...config('roles.approvers'));

        $events = array_map(static function (array $b) use ($isStaff): array {
            $meta = status_meta('booking', $b['status']);

            return [
                'id' => $b['id'],
                'title' => $b['title'],
                'start' => $b['date'] . 'T' . $b['start_time'],
                'end' => $b['date'] . 'T' . $b['end_time'],
                'classNames' => ['event-' . strtolower(str_replace(' ', '-', $b['status']))],
                'extendedProps' => [
                    'room' => $b['room_name'],
                    'location' => "{$b['building']} ชั้น {$b['floor']}",
                    'date' => thai_date($b['date'], 'long'),
                    'time' => time_range($b['start_time'], $b['end_time']),
                    'department' => $b['department'],
                    'organizer' => $b['organizer'],
                    'phone' => $isStaff ? $b['phone'] : null,
                    'participants' => (int) $b['participants'],
                    'statusLabel' => $meta['label'],
                    'statusClass' => $meta['class'],
                    'statusIcon' => $meta['icon'],
                    'mine' => $b['user_id'] === Auth::id(),
                    'url' => $isStaff || $b['user_id'] === Auth::id() ? '/bookings/' . $b['id'] : null,
                ],
            ];
        }, Booking::between($from, $to, $roomIds));

        foreach (Database::all('SELECT date, name FROM holidays WHERE date BETWEEN ? AND ?', [$from, $to]) as $h) {
            $events[] = [
                'start' => $h['date'],
                'allDay' => true,
                'display' => 'background',
                'classNames' => ['event-holiday'],
                'title' => $h['name'],
            ];
        }

        $this->json($events);
    }
}
