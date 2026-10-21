<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\Room;
use App\Models\Setting;
use App\Services\BookingService;
use App\Services\Database;
use App\Services\ImageUpload;

/** หน้าตั้งค่าระบบทั้งหมด (เฉพาะผู้ดูแลระบบ กำหนดใน routes/web.php) */
final class SettingsController extends Controller
{
    /** ตารางที่สำรอง/กู้คืน เรียงตามลำดับที่ foreign key ต้องการ */
    private const BACKUP_TABLES = [
        'departments', 'users', 'rooms', 'bookings', 'repairs', 'notifications', 'notification_states',
        'audit_logs', 'settings', 'holidays', 'leave_types', 'attendance_logs', 'leave_requests',
    ];
    private const BACKUP_MAX_BYTES = 20 * 1024 * 1024;

    public function index(): void
    {
        $this->view('settings/index', ['title' => 'ตั้งค่าระบบ']);
    }

    /* ---------------- ห้องประชุม ---------------- */

    public function rooms(): void
    {
        $edit = isset($_GET['edit']) ? (Room::find((string) $_GET['edit']) ?? $this->abort(404)) : null;
        $form = $edit ? ['equipment_text' => implode(', ', $edit['equipment'])] + $edit : (isset($_GET['new']) ? ['price_gov' => 0, 'price_local' => 0] : null);
        $this->renderRooms($edit, $form);
    }

    /**
     * @param array<string, mixed>|null $editing
     * @param array<string, mixed>|null $form
     * @param array<string, string> $errors
     */
    private function renderRooms(?array $editing, ?array $form, array $errors = []): void
    {
        $this->view('settings/rooms', [
            'title' => 'ตั้งค่าห้องประชุม',
            'rooms' => Room::all(),
            'usage' => array_column(Database::all('SELECT room_id, COUNT(*) AS n FROM bookings GROUP BY room_id'), 'n', 'room_id'),
            'editing' => $editing,
            'form' => $form,
            'errors' => $errors,
            'scripts' => ['js/maintenance.js'],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    private function validateRoom(array $input): array
    {
        $s = static fn (string $k, int $max) => mb_substr(trim((string) ($input[$k] ?? '')), 0, $max);
        $equipment = array_values(array_unique(array_filter(array_map('trim', explode(',', $s('equipment_text', 1000))))));
        $data = [
            'name' => $s('name', 150),
            'building' => $s('building', 150),
            'floor' => $s('floor', 10),
            'capacity' => $s('capacity', 20),
            'price_gov' => $s('price_gov', 12),
            'price_local' => $s('price_local', 12),
            'equipment_text' => implode(', ', $equipment),
            'rules' => $s('rules', 2000),
            'warnings' => $s('warnings', 2000),
            'locked' => !empty($input['locked']) ? 1 : 0,
        ];
        $errors = [];
        foreach (['name' => 'ชื่อห้องประชุม', 'building' => 'อาคาร', 'floor' => 'ชั้น'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[$field] = "กรุณากรอก{$label}";
            }
        }
        if (!preg_match('/^\d+(\s*-\s*\d+)?$/', $data['capacity'])) {
            $errors['capacity'] = 'กรอกความจุเป็นตัวเลข หรือช่วง เช่น 20-30';
        }
        foreach (['price_gov' => 'อัตราหน่วยงานราชการ', 'price_local' => 'อัตราหน่วยงานอื่น'] as $field => $label) {
            if (!is_numeric($data[$field]) || (float) $data[$field] < 0) {
                $errors[$field] = "กรุณากรอก{$label}เป็นตัวเลขตั้งแต่ 0 ขึ้นไป";
            }
        }

        return ['data' => $data, 'errors' => $errors];
    }

    /** @param array<string, mixed> $data */
    private function roomColumns(array $data): array
    {
        $cols = $data;
        $cols['equipment'] = json_encode(
            array_values(array_filter(array_map('trim', explode(',', $data['equipment_text'])))),
            JSON_UNESCAPED_UNICODE
        );
        $cols['capacity'] = preg_replace('/\s+/', '', $data['capacity']);
        unset($cols['equipment_text']);

        return $cols;
    }

    public function roomStore(): void
    {
        $result = $this->validateRoom($this->input());
        $upload = $result['errors'] ? ['path' => null, 'error' => null] : ImageUpload::store($_FILES['image'] ?? null, 'rooms');
        if ($upload['error']) {
            $result['errors']['image'] = $upload['error'];
        }
        if ($result['errors']) {
            http_response_code(422);
            $this->renderRooms(null, $result['data'], $result['errors']);
            return;
        }

        $last = (int) Database::value("SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) FROM rooms WHERE id LIKE 'RM-%'");
        $id = 'RM-' . str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
        $cols = $this->roomColumns($result['data']) + ['id' => $id, 'image' => $upload['path'], 'status' => 'Available'];
        Database::query(
            sprintf('INSERT INTO rooms (%s) VALUES (%s)', implode(', ', array_keys($cols)), implode(', ', array_fill(0, count($cols), '?'))),
            array_values($cols)
        );
        AuditLog::record('Create Room', "เพิ่มห้องประชุม {$id} {$cols['name']}");
        flash('success', "เพิ่ม{$cols['name']}แล้ว");
        redirect('/settings/rooms');
    }

    public function roomUpdate(string $id): void
    {
        $room = Room::find($id) ?? $this->abort(404);
        $result = $this->validateRoom($this->input());
        $upload = $result['errors'] ? ['path' => null, 'error' => null] : ImageUpload::store($_FILES['image'] ?? null, 'rooms');
        if ($upload['error']) {
            $result['errors']['image'] = $upload['error'];
        }
        if ($result['errors']) {
            http_response_code(422);
            $this->renderRooms($room, $result['data'] + ['image' => $room['image']], $result['errors']);
            return;
        }

        $cols = $this->roomColumns($result['data']);
        if ($upload['path']) {
            $cols['image'] = $upload['path'];
        }
        $set = implode(', ', array_map(static fn ($c) => "{$c} = ?", array_keys($cols)));
        Database::query("UPDATE rooms SET {$set} WHERE id = ?", [...array_values($cols), $id]);
        AuditLog::record('Update Room', "แก้ไขห้องประชุม {$id}" . ($cols['locked'] !== (int) $room['locked'] ? ($cols['locked'] ? ' (ระงับการใช้งาน)' : ' (เปิดใช้งาน)') : ''));
        flash('success', "บันทึก{$cols['name']}แล้ว");
        redirect('/settings/rooms');
    }

    public function roomDelete(string $id): void
    {
        $room = Room::find($id) ?? $this->abort(404);
        $used = Database::value('SELECT (SELECT COUNT(*) FROM bookings WHERE room_id = ?) + (SELECT COUNT(*) FROM repairs WHERE room_id = ?)', [$id, $id]);
        if ($used > 0) {
            flash('danger', "ลบ{$room['name']}ไม่ได้ เพราะมีประวัติการจองหรือใบงานซ่อมอ้างถึง ใช้การระงับการใช้งานแทน");
            redirect('/settings/rooms');
        }
        Database::query('DELETE FROM rooms WHERE id = ?', [$id]);
        AuditLog::record('Delete Room', "ลบห้องประชุม {$id} {$room['name']}");
        flash('success', "ลบ{$room['name']}แล้ว");
        redirect('/settings/rooms');
    }

    /* ---------------- วันหยุดและเวลาทำการ ---------------- */

    public function holidays(array $errors = [], array $old = []): void
    {
        $this->view('settings/holidays', [
            'title' => 'เวลาทำการและวันหยุด',
            'holidays' => Database::all('SELECT * FROM holidays WHERE date >= ? ORDER BY date', [date('Y') . '-01-01']),
            'hours' => Setting::workingHours(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function holidayStore(): void
    {
        $input = $this->input();
        $old = [
            'date' => (string) ($input['date'] ?? ''),
            'name' => mb_substr(trim((string) ($input['name'] ?? '')), 0, 150),
            'type' => mb_substr(trim((string) ($input['type'] ?? 'วันหยุดราชการ')), 0, 50) ?: 'วันหยุดราชการ',
        ];
        $errors = [];
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $old['date']);
        if (!$d || $d->format('Y-m-d') !== $old['date']) {
            $errors['date'] = 'กรุณาเลือกวันที่';
        } elseif (Database::value('SELECT 1 FROM holidays WHERE date = ?', [$old['date']])) {
            $errors['date'] = 'วันนี้อยู่ในรายการวันหยุดแล้ว';
        }
        if ($old['name'] === '') {
            $errors['name'] = 'กรุณากรอกชื่อวันหยุด';
        }
        if ($errors) {
            http_response_code(422);
            $this->holidays($errors, $old);
            return;
        }
        Database::query('INSERT INTO holidays (date, name, type) VALUES (?, ?, ?)', [$old['date'], $old['name'], $old['type']]);
        AuditLog::record('Create Holiday', 'เพิ่มวันหยุด ' . thai_date($old['date']) . " {$old['name']}");
        flash('success', "เพิ่มวันหยุด{$old['name']}แล้ว");
        redirect('/settings/holidays');
    }

    public function holidayDelete(string $id): void
    {
        $holiday = Database::first('SELECT * FROM holidays WHERE id = ?', [(int) $id]) ?? $this->abort(404);
        Database::query('DELETE FROM holidays WHERE id = ?', [$holiday['id']]);
        AuditLog::record('Delete Holiday', 'ลบวันหยุด ' . thai_date($holiday['date']) . " {$holiday['name']}");
        flash('success', "ลบวันหยุด{$holiday['name']}แล้ว");
        redirect('/settings/holidays');
    }

    public function workingHours(): void
    {
        $input = $this->input();
        $start = (string) ($input['start'] ?? '');
        $end = (string) ($input['end'] ?? '');
        $valid = static fn ($t) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
        if (!$valid($start) || !$valid($end) || $start >= $end) {
            http_response_code(422);
            $this->holidays(['hours' => 'กรุณาเลือกเวลาเริ่มและเลิกงาน โดยเวลาเลิกงานต้องอยู่หลังเวลาเริ่มงาน'], ['start' => $start, 'end' => $end]);
            return;
        }
        Setting::set('working_hours', ['start' => $start, 'end' => $end]);
        AuditLog::record('Update Working Hours', "ตั้งเวลาทำการ {$start}–{$end} น.");
        flash('success', "บันทึกเวลาทำการ {$start}–{$end} น. แล้ว");
        redirect('/settings/holidays');
    }

    /* ---------------- ประเภทการลา ---------------- */

    public function leaveTypes(): void
    {
        $this->view('settings/leave-types', [
            'title' => 'ประเภทและโควตาการลา',
            'types' => LeaveRequest::types(),
        ]);
    }

    public function leaveTypeUpdate(string $id): void
    {
        $type = LeaveRequest::type((int) $id) ?? $this->abort(404);
        $input = $this->input();
        $quota = filter_var($input['quota_days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
        $approval = (string) ($input['approval_level'] ?? '');
        if ($quota === false || !in_array($approval, ['Supervisor', 'Department Head', 'Executive'], true)) {
            flash('danger', 'กรุณากรอกโควตาเป็นจำนวนวัน 0–365 และเลือกระดับผู้อนุมัติ');
            redirect('/settings/leave-types');
        }
        Database::query('UPDATE leave_types SET quota_days = ?, approval_level = ? WHERE id = ?', [$quota, $approval, $type['id']]);
        AuditLog::record('Update Leave Type', "ตั้งโควตา{$type['name']} {$quota} วัน");
        flash('success', "บันทึกโควตา{$type['name']} {$quota} วันแล้ว");
        redirect('/settings/leave-types');
    }

    /* ---------------- กฎการจอง (ยกมาจาก meeting-room-maintenance-dashboard) ---------------- */

    public function bookingRules(array $errors = [], ?array $old = null): void
    {
        $this->view('settings/booking-rules', [
            'title' => 'กฎการจองห้องประชุม',
            'rules' => $old ?? BookingService::rules(),
            'errors' => $errors,
        ]);
    }

    public function bookingRulesUpdate(): void
    {
        $input = $this->input();
        $rules = [
            'max_advance_days' => filter_var($input['max_advance_days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 730]]),
            'max_hours' => filter_var($input['max_hours'] ?? null, FILTER_VALIDATE_FLOAT),
            'open_time' => (string) ($input['open_time'] ?? ''),
            'close_time' => (string) ($input['close_time'] ?? ''),
            'auto_approve' => !empty($input['auto_approve']),
            'block_holidays' => !empty($input['block_holidays']),
        ];
        $errors = [];
        if ($rules['max_advance_days'] === false) {
            $errors['max_advance_days'] = 'กรอกจำนวนวัน 1–730';
        }
        if ($rules['max_hours'] === false || $rules['max_hours'] < 0.5 || $rules['max_hours'] > 24) {
            $errors['max_hours'] = 'กรอกจำนวนชั่วโมง 0.5–24';
        }
        $valid = static fn ($t) => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
        if (!$valid($rules['open_time']) || !$valid($rules['close_time']) || $rules['open_time'] >= $rules['close_time']) {
            $errors['open_time'] = 'เวลาปิดรับจองต้องอยู่หลังเวลาเปิดรับจอง';
        }
        if ($errors) {
            http_response_code(422);
            $this->bookingRules($errors, $rules);
            return;
        }
        Setting::set('booking_rules', $rules);
        AuditLog::record('Update Booking Rules', "ล่วงหน้า {$rules['max_advance_days']} วัน, สูงสุด {$rules['max_hours']} ชม., {$rules['open_time']}–{$rules['close_time']}, อนุมัติอัตโนมัติ: " . ($rules['auto_approve'] ? 'เปิด' : 'ปิด'));
        flash('success', 'บันทึกกฎการจองแล้ว');
        redirect('/settings/booking-rules');
    }

    /* ---------------- สำรองและกู้คืน ---------------- */

    public function backup(array $errors = []): void
    {
        if ($errors) {
            http_response_code(422);
        }
        $this->view('settings/backup', [
            'title' => 'สำรองและกู้คืนข้อมูล',
            'counts' => $this->tableCounts(),
            'errors' => $errors,
        ]);
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        $counts = [];
        foreach (self::BACKUP_TABLES as $table) {
            $counts[$table] = (int) Database::value("SELECT COUNT(*) FROM `{$table}`");
        }

        return $counts;
    }

    public function backupExport(): void
    {
        $data = ['app' => 'chowpad', 'version' => 1, 'created_at' => date('c'), 'tables' => []];
        foreach (self::BACKUP_TABLES as $table) {
            $data['tables'][$table] = Database::all("SELECT * FROM `{$table}`");
        }
        AuditLog::record('Export Backup', 'ดาวน์โหลดไฟล์สำรองข้อมูล');

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="chowpad-backup-' . date('Ymd-His') . '.json"');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function backupRestore(): void
    {
        $file = $_FILES['backup'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $this->backup(['backup' => 'กรุณาเลือกไฟล์สำรองข้อมูล (.json)']);
            return;
        }
        if ($file['size'] > self::BACKUP_MAX_BYTES) {
            $this->backup(['backup' => 'ไฟล์สำรองข้อมูลต้องมีขนาดไม่เกิน 20 MB']);
            return;
        }
        $data = json_decode((string) file_get_contents($file['tmp_name']), true);
        if (
            !is_array($data) || ($data['app'] ?? '') !== 'chowpad' || !is_array($data['tables'] ?? null)
            || array_diff(self::BACKUP_TABLES, array_keys($data['tables']))
        ) {
            $this->backup(['backup' => 'ไฟล์นี้ไม่ใช่ไฟล์สำรองข้อมูลของระบบนี้ หรือข้อมูลไม่ครบ']);
            return;
        }

        $pdo = Database::connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            Database::transaction(function () use ($data): void {
                foreach (self::BACKUP_TABLES as $table) {
                    // ใช้เฉพาะคอลัมน์ที่มีจริงในตาราง กันชื่อคอลัมน์แปลกปลอมจากไฟล์
                    $columns = Database::query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_COLUMN);
                    Database::query("DELETE FROM `{$table}`");
                    foreach ($data['tables'][$table] as $row) {
                        if (!is_array($row)) {
                            throw new \UnexpectedValueException("ข้อมูลตาราง {$table} ไม่ถูกต้อง");
                        }
                        $row = array_intersect_key($row, array_flip($columns));
                        if (!$row) {
                            continue;
                        }
                        Database::query(
                            sprintf(
                                'INSERT INTO `%s` (%s) VALUES (%s)',
                                $table,
                                implode(', ', array_map(static fn ($c) => "`{$c}`", array_keys($row))),
                                implode(', ', array_fill(0, count($row), '?'))
                            ),
                            array_values($row)
                        );
                    }
                }
            });
        } catch (\UnexpectedValueException | \PDOException $e) {
            error_log((string) $e);
            $this->backup(['backup' => 'กู้คืนไม่สำเร็จ ข้อมูลเดิมยังอยู่ครบ (ไฟล์มีข้อมูลที่ไม่ตรงกับโครงสร้างตาราง)']);
            return;
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        AuditLog::record('Restore Backup', 'กู้คืนข้อมูลจากไฟล์สำรองวันที่ ' . ($data['created_at'] ?? '-'));
        flash('success', 'กู้คืนข้อมูลเรียบร้อยแล้ว');
        redirect('/settings/backup');
    }

    /* ---------------- ข้อมูลระบบ ---------------- */

    public function system(): void
    {
        $dbSize = (float) Database::value(
            'SELECT COALESCE(SUM(data_length + index_length), 0) FROM information_schema.TABLES WHERE table_schema = DATABASE()'
        );
        $uploads = 0;
        foreach (glob(BASE_PATH . '/public/uploads/*/*') ?: [] as $f) {
            $uploads += filesize($f);
        }

        $this->view('settings/system', [
            'title' => 'ข้อมูลระบบ',
            'info' => [
                'PHP' => PHP_VERSION . ' (' . PHP_SAPI . ')',
                'ฐานข้อมูล' => 'MySQL ' . Database::value('SELECT VERSION()'),
                'ชื่อฐานข้อมูล' => config('database.database'),
                'สภาพแวดล้อม' => config('app.env') . (config('app.debug') ? ' · โหมดดีบักเปิดอยู่' : ''),
                'เขตเวลา' => config('app.timezone') . ' (' . date('H:i') . ' น.)',
                'ขนาดฐานข้อมูล' => number_format($dbSize / 1048576, 2) . ' MB',
                'ไฟล์ที่อัปโหลด' => number_format($uploads / 1048576, 2) . ' MB',
                'จำกัดขนาดอัปโหลด' => ini_get('upload_max_filesize') . ' (ระบบรับรูปไม่เกิน 5 MB)',
            ],
            'counts' => $this->tableCounts(),
            'warnings' => array_filter([
                config('app.debug') && config('app.env') !== 'local' ? 'เปิดโหมดดีบักบนเครื่องที่ไม่ใช่เครื่องพัฒนา ควรตั้ง APP_DEBUG=false' : null,
                config('database.password') === '' && config('app.env') !== 'local' ? 'ฐานข้อมูลไม่มีรหัสผ่าน' : null,
            ]),
        ]);
    }
}
