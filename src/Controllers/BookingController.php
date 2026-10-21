<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\Auth;
use App\Services\BookingService;

final class BookingController extends Controller
{
    private const PER_PAGE = 10;

    /** ผู้อนุมัติเห็นทุกรายการ ผู้ใช้ทั่วไปเห็นเฉพาะการจองของตัวเอง */
    private function scopeUserId(): ?string
    {
        return Auth::hasRole(...config('roles.approvers')) ? null : Auth::id();
    }

    /** @return array{search: string, room: string, status: string} */
    private function filters(): array
    {
        $status = (string) ($_GET['status'] ?? '');

        return [
            'search' => mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100),
            'room' => (string) ($_GET['room'] ?? ''),
            'status' => in_array($status, Booking::STATUSES, true) ? $status : '',
        ];
    }

    public function index(): void
    {
        $filters = $this->filters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        [$bookings, $total] = Booking::paginate($filters, $page, self::PER_PAGE, $this->scopeUserId());

        $this->view('bookings/index', [
            'title' => 'ประวัติการจอง',
            'bookings' => $bookings,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'rooms' => Room::all(),
            'allBookings' => $this->scopeUserId() === null,
        ]);
    }

    /** ส่งออก CSV ตามตัวกรองปัจจุบัน (เปิดใน Excel ได้ภาษาไทยถูกต้องเพราะมี BOM) */
    public function export(): void
    {
        $rows = Booking::export($this->filters(), $this->scopeUserId());
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bookings-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['เลขที่', 'ห้องประชุม', 'หัวข้อ', 'วันที่', 'เวลา', 'หน่วยงาน', 'ผู้ประสานงาน', 'ผู้เข้าร่วม', 'ค่าธรรมเนียม (บาท)', 'สถานะ']);
        foreach ($rows as $b) {
            fputcsv($out, array_map([$this, 'csvSafe'], [
                $b['id'], $b['room_name'], $b['title'], thai_date($b['date']), time_range($b['start_time'], $b['end_time']),
                $b['department'], $b['organizer'], $b['participants'], $b['rental_fee'], status_meta('booking', $b['status'])['label'],
            ]));
        }
        fclose($out);
    }

    /** กันสูตร Excel (CSV injection) จากข้อความที่ผู้ใช้กรอก */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    public function create(): void
    {
        $user = Auth::user();
        $old = [
            'room_id' => (string) ($_GET['room'] ?? ''),
            'date' => (string) ($_GET['date'] ?? ''),
            'department' => $user['department_name'] ?? '',
            'organizer' => $user['name'],
            'phone' => $user['phone'] ?? '',
            'email' => $user['email'],
            'equipment' => [],
        ];
        $this->renderForm($old);
    }

    public function store(): void
    {
        $input = $this->input();
        $result = BookingService::validate($input);
        $old = $result['data'];

        if ($result['errors']) {
            http_response_code(422);
            $this->renderForm($old, $result['errors']);
            return;
        }

        $created = BookingService::create($result['data'], $result['room'], Auth::user());
        if ($created['conflict']) {
            http_response_code(409);
            $c = $created['conflict'];
            $this->renderForm($old, [], [
                'message' => "ช่วงเวลา {$c['start_time']}–{$c['end_time']} น. ของห้องนี้มีผู้จองแล้ว (เรื่อง \"{$c['title']}\")",
                'alternatives' => $this->alternativeRooms($old),
            ]);
            return;
        }

        flash('success', !empty($created['approved'])
            ? "จองห้องเลขที่ {$created['id']} สำเร็จ ระบบอนุมัติอัตโนมัติแล้ว"
            : "ส่งคำขอจองเลขที่ {$created['id']} เรียบร้อยแล้ว รอผู้ดูแลห้องพิจารณาอนุมัติ");
        redirect('/bookings/' . $created['id']);
    }

    /**
     * ห้องอื่นที่ว่างในเวลาเดียวกัน (DESIGN_SYSTEM 7.12)
     *
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    private function alternativeRooms(array $data): array
    {
        $busy = Booking::busyRooms($data['date'], $data['start_time'], $data['end_time']);

        return array_slice(array_values(array_filter(
            Room::all(),
            static fn ($r) => !$r['locked'] && !isset($busy[$r['id']]) && $r['id'] !== $data['room_id']
        )), 0, 3);
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $conflict
     */
    private function renderForm(array $old, array $errors = [], ?array $conflict = null): void
    {
        $this->view('bookings/create', [
            'title' => 'จองห้องประชุม',
            'old' => $old,
            'errors' => $errors,
            'conflict' => $conflict,
            'rooms' => Room::all(),
            'orgTypes' => BookingService::ORG_TYPES,
            'equipment' => Booking::EQUIPMENT,
            'rules' => BookingService::rules(),
            'scripts' => ['js/booking-form.js'],
        ]);
    }

    public function show(string $id): void
    {
        $booking = $this->findAccessible($id);
        $this->view('bookings/show', [
            'title' => 'รายละเอียดการจอง ' . $booking['id'],
            'booking' => $booking,
            'canCancel' => BookingService::canCancel($booking),
            'canReview' => $booking['status'] === 'Waiting Approval' && Auth::hasRole(...config('roles.approvers')),
            'scripts' => ['js/approval.js'],
        ]);
    }

    public function cancel(string $id): void
    {
        $booking = $this->findAccessible($id);
        $error = BookingService::cancel($booking);
        flash($error ? 'danger' : 'success', $error ?? "ยกเลิกการจองเลขที่ {$id} แล้ว");
        redirect('/bookings/' . $id);
    }

    public function approval(): void
    {
        $this->view('bookings/approval', [
            'title' => 'อนุมัติการจอง',
            'bookings' => Booking::pending(),
            'scripts' => ['js/approval.js'],
        ]);
    }

    public function approve(string $id): void
    {
        $booking = $this->findOrFail($id);
        $error = BookingService::approve($booking);
        flash($error ? 'danger' : 'success', $error ?? "อนุมัติการจองเลขที่ {$id} แล้ว");
        redirect($this->back('/bookings/approval'));
    }

    public function reject(string $id): void
    {
        $booking = $this->findOrFail($id);
        $reason = mb_substr(trim((string) ($this->input()['reason'] ?? '')), 0, 500);
        $error = BookingService::reject($booking, $reason);
        flash($error ? 'danger' : 'success', $error ?? "บันทึกผลไม่อนุมัติการจองเลขที่ {$id} แล้ว");
        redirect($this->back('/bookings/approval'));
    }

    /** กลับไปหน้าที่ส่งฟอร์มมา เฉพาะ path ภายในระบบ */
    private function back(string $fallback): string
    {
        $path = (string) ($this->input()['return_to'] ?? '');

        return preg_match('#^/[a-z0-9/_-]*$#i', $path) ? $path : $fallback;
    }

    /** @return array<string, mixed> */
    private function findOrFail(string $id): array
    {
        $booking = Booking::find($id);
        if ($booking === null) {
            $this->abort(404);
        }

        return $booking;
    }

    /** @return array<string, mixed> */
    private function findAccessible(string $id): array
    {
        $booking = $this->findOrFail($id);
        if ($this->scopeUserId() !== null && $booking['user_id'] !== Auth::id()) {
            $this->abort(403);
        }

        return $booking;
    }
}
