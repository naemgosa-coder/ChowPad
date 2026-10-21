<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Repair;
use App\Models\Room;

/** ขั้นตอนใบงานซ่อม: Waiting → Accepted (มอบหมาย/รับงาน) → In Progress → Completed */
final class RepairService
{
    /**
     * @param array<string, mixed> $input
     * @return array{data: array<string, mixed>, errors: array<string, string>, room: array<string, mixed>|null}
     */
    public static function validate(array $input): array
    {
        $data = [
            'room_id' => mb_substr(trim((string) ($input['room_id'] ?? '')), 0, 10),
            'category' => (string) ($input['category'] ?? ''),
            'priority' => (string) ($input['priority'] ?? 'Low'),
            'description' => mb_substr(trim((string) ($input['description'] ?? '')), 0, 2000),
        ];
        $errors = [];

        $room = $data['room_id'] !== '' ? Room::find($data['room_id']) : null;
        if ($room === null) {
            $errors['room_id'] = 'กรุณาเลือกห้องประชุมที่พบปัญหา';
        }
        if (!array_key_exists($data['category'], Repair::CATEGORIES)) {
            $errors['category'] = 'กรุณาเลือกหมวดหมู่อุปกรณ์ที่ชำรุด';
        }
        if (!in_array($data['priority'], Repair::PRIORITIES, true)) {
            $errors['priority'] = 'กรุณาเลือกระดับความเร่งด่วน';
        }
        if (mb_strlen($data['description']) < 5) {
            $errors['description'] = 'กรุณาอธิบายปัญหาอย่างน้อย 5 ตัวอักษร';
        }

        return ['data' => $data, 'errors' => $errors, 'room' => $room];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $room
     */
    public static function create(array $data, array $room, ?string $beforeImage): string
    {
        if ((int) Database::value("SELECT GET_LOCK('chowpad_repair', 10)") !== 1) {
            throw new \RuntimeException('ระบบไม่ว่าง กรุณาลองใหม่อีกครั้ง');
        }
        try {
            $id = Repair::nextId();
            Repair::insert($data + [
                'id' => $id,
                'date' => date('Y-m-d'),
                'status' => 'Waiting',
                'reported_by' => Auth::id(),
                'before_image' => $beforeImage,
            ]);
        } finally {
            Database::value("SELECT RELEASE_LOCK('chowpad_repair')");
        }

        $priority = status_meta('priority', $data['priority'])['label'];
        AuditLog::record('Create Maintenance', "แจ้งซ่อมเลขที่ {$id} ห้อง {$room['name']} ({$priority})");
        Notification::push(
            null,
            $data['priority'] === 'Critical' ? 'แจ้งซ่อมใหม่ (ด่วนที่สุด)' : 'มีรายการแจ้งซ่อมใหม่',
            "{$id}: {$room['name']} · " . Repair::categoryLabel($data['category']),
            'wrench',
            in_array($data['priority'], ['Critical', 'High'], true) ? 'danger' : 'warning',
            'maintenance_staff'
        );

        return $id;
    }

    /** @param array<string, mixed> $repair */
    public static function assign(array $repair, string $technicianId): ?string
    {
        $tech = Database::first("SELECT id, name FROM users WHERE id = ? AND role = 'Technician' AND status = 'Active'", [$technicianId]);
        if ($tech === null) {
            return 'กรุณาเลือกนายช่างจากรายการ';
        }
        if (!Repair::transition($repair['id'], 'Waiting', ['technician_id' => $tech['id'], 'status' => 'Accepted'])) {
            return 'ใบงานนี้ถูกมอบหมายหรือรับงานไปแล้ว';
        }

        AuditLog::record('Assign Maintenance', "มอบหมาย {$tech['name']} ดูแลใบงาน {$repair['id']}");
        Notification::push($tech['id'], 'คุณได้รับใบงานซ่อมบำรุงใหม่', "{$repair['id']}: {$repair['room_name']} · {$repair['description']}", 'wrench', 'primary');

        return null;
    }

    /** @param array<string, mixed> $repair */
    public static function accept(array $repair): ?string
    {
        if (!Repair::transition($repair['id'], 'Waiting', ['technician_id' => Auth::id(), 'status' => 'Accepted'])) {
            return 'ใบงานนี้มีผู้รับไปแล้ว';
        }
        AuditLog::record('Accept Maintenance Job', "รับใบงานซ่อม {$repair['id']}");

        return null;
    }

    /** @param array<string, mixed> $repair */
    public static function start(array $repair): ?string
    {
        if (!self::isMine($repair)) {
            return 'ใบงานนี้ไม่ได้มอบหมายให้คุณ';
        }
        if (!Repair::transition($repair['id'], 'Accepted', ['status' => 'In Progress', 'start_time' => date('H:i:s')])) {
            return 'ไม่สามารถเริ่มงานได้ เพราะสถานะใบงานเปลี่ยนไปแล้ว';
        }
        AuditLog::record('Start Repair Job', "เริ่มซ่อมใบงาน {$repair['id']}");

        return null;
    }

    /**
     * @param array<string, mixed> $repair
     * @param array<string, mixed> $input
     * @return array<string, string> ข้อผิดพลาดรายช่อง (ว่าง = สำเร็จ)
     */
    public static function complete(array $repair, array $input, ?array $afterFile): array
    {
        if (!self::isMine($repair)) {
            return ['_' => 'ใบงานนี้ไม่ได้มอบหมายให้คุณ'];
        }
        $notes = mb_substr(trim((string) ($input['repair_notes'] ?? '')), 0, 2000);
        $materials = mb_substr(trim((string) ($input['material_used'] ?? '')), 0, 1000);
        $labor = filter_var($input['labor_cost'] ?? 0, FILTER_VALIDATE_FLOAT);
        $material = filter_var($input['material_cost'] ?? 0, FILTER_VALIDATE_FLOAT);

        $errors = [];
        if ($notes === '') {
            $errors['repair_notes'] = 'กรุณาบันทึกรายละเอียดการซ่อม';
        }
        if ($labor === false || $labor < 0 || $labor > 1_000_000) {
            $errors['labor_cost'] = 'กรุณากรอกค่าแรงเป็นตัวเลขตั้งแต่ 0 ขึ้นไป';
        }
        if ($material === false || $material < 0 || $material > 1_000_000) {
            $errors['material_cost'] = 'กรุณากรอกค่าวัสดุเป็นตัวเลขตั้งแต่ 0 ขึ้นไป';
        }
        if ($errors) {
            return $errors;
        }

        $upload = ImageUpload::store($afterFile, 'repairs');
        if ($upload['error']) {
            return ['after_image' => $upload['error']];
        }

        $ok = Repair::transition($repair['id'], 'In Progress', [
            'status' => 'Completed',
            'repair_notes' => $notes,
            'material_used' => $materials,
            'labor_cost' => $labor,
            'material_cost' => $material,
            'after_image' => $upload['path'],
            'finish_time' => date('H:i:s'),
        ]);
        if (!$ok) {
            return ['_' => 'ไม่สามารถปิดงานได้ เพราะสถานะใบงานเปลี่ยนไปแล้ว'];
        }

        AuditLog::record('Close Repair Job', "ปิดใบงานซ่อม {$repair['id']} ค่าใช้จ่ายรวม " . money($labor + $material));
        if ($repair['reported_by']) {
            Notification::push($repair['reported_by'], 'งานซ่อมที่คุณแจ้งเสร็จแล้ว', "{$repair['id']}: {$repair['room_name']} ได้รับการแก้ไขเรียบร้อย", 'circle-check', 'success');
        }

        return [];
    }

    /** ช่างที่ได้รับมอบหมาย หรือผู้ดูแลระบบ */
    public static function isMine(array $repair): bool
    {
        return $repair['technician_id'] === Auth::id() || Auth::hasRole('Administrator');
    }
}
