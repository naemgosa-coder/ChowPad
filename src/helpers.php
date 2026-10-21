<?php

declare(strict_types=1);

use App\Services\Auth;
use App\Services\Csrf;
use App\Services\Env;

function env(string $key, ?string $default = null): ?string
{
    return Env::get($key, $default);
}

/** อ่านค่า config แบบ "ไฟล์.คีย์" เช่น config('app.name') */
function config(string $key, mixed $default = null): mixed
{
    static $cache = [];
    $parts = explode('.', $key);
    $file = array_shift($parts);
    $cache[$file] ??= require BASE_PATH . "/config/{$file}.php";

    $value = $cache[$file];
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

/** escape ทุกค่าที่พิมพ์ลง HTML */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function url(string $path = '/'): string
{
    return '/' . ltrim($path, '/');
}

/** ใส่ ?v=เวลาแก้ไขไฟล์ เพื่อให้เบราว์เซอร์โหลด CSS/JS ใหม่เมื่อไฟล์เปลี่ยน */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/' . $path;

    return '/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 302);
    exit;
}

/** @return array<string, mixed>|null */
function auth_user(): ?array
{
    return Auth::user();
}

/** ไอคอนจาก sprite ในเครื่อง (public/images/icons.svg) ถ้าไอคอนสื่อความหมายเองให้ส่ง $label */
function icon(string $name, string $class = '', ?string $label = null): string
{
    $a11y = $label === null ? 'aria-hidden="true"' : 'role="img" aria-label="' . e($label) . '"';

    return '<svg class="icon ' . e($class) . '" ' . $a11y . ' focusable="false">'
        . '<use href="/images/icons.svg#' . e($name) . '"></use></svg>';
}

function role_label(?string $role): string
{
    return [
        'Administrator' => 'ผู้ดูแลระบบ',
        'Officer' => 'เจ้าหน้าที่ฝ่ายบริหาร',
        'Technician' => 'นายช่างซ่อมบำรุง',
        'Executive' => 'ผู้บริหาร',
        'Employee' => 'บุคลากร',
    ][$role] ?? (string) $role;
}

/** อักษรย่อจากชื่อ ใช้แทนรูปโปรไฟล์ที่ไม่มี */
function initials(string $name): string
{
    return mb_substr(trim(preg_replace('/^(นาย|นางสาว|นาง|ช่าง)/u', '', $name)), 0, 1);
}

/**
 * วันที่แบบไทย พ.ศ. เช่น "21 ต.ค. 2569" (short) หรือ "วันอังคารที่ 21 ตุลาคม 2569" (long)
 */
function thai_date(string|\DateTimeInterface|null $date, string $format = 'short'): string
{
    if ($date === null || $date === '') {
        return '-';
    }
    $d = $date instanceof \DateTimeInterface ? $date : new \DateTimeImmutable($date);
    $short = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $long = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $month = (int) $d->format('n');
    $year = (int) $d->format('Y') + 543;

    return match ($format) {
        'long' => 'วัน' . $days[(int) $d->format('w')] . 'ที่ ' . $d->format('j') . " {$long[$month]} {$year}",
        'month' => "{$long[$month]} {$year}",
        default => $d->format('j') . " {$short[$month]} {$year}",
    };
}

/** ช่วงเวลา "09:00–10:30 น." */
function time_range(?string $start, ?string $end): string
{
    return substr((string) $start, 0, 5) . '–' . substr((string) $end, 0, 5) . ' น.';
}

/**
 * ข้อความ สี และไอคอนของสถานะ (สถานะต้องสื่อด้วย สี + ไอคอน + ข้อความ เสมอ)
 *
 * @return array{label: string, class: string, icon: string}
 */
function status_meta(string $type, ?string $status): array
{
    $map = [
        'booking' => [
            'Waiting Approval' => ['รออนุมัติ', 'badge-warning', 'clock'],
            'Approved' => ['อนุมัติแล้ว', 'badge-success', 'circle-check'],
            'Rejected' => ['ไม่อนุมัติ', 'badge-danger', 'circle-x'],
            'Cancelled' => ['ยกเลิกแล้ว', 'badge-neutral', 'x'],
            'Completed' => ['ใช้งานแล้ว', 'badge-info', 'check-check'],
        ],
        'repair' => [
            'Waiting' => ['รอคิวช่าง', 'badge-warning', 'clock'],
            'Accepted' => ['รับงานแล้ว', 'badge-info', 'user-check'],
            'In Progress' => ['กำลังซ่อม', 'badge-info', 'wrench'],
            'Completed' => ['เสร็จสิ้น', 'badge-success', 'circle-check'],
            'Cancelled' => ['ยกเลิกแล้ว', 'badge-neutral', 'x'],
        ],
        'priority' => [
            'Critical' => ['ด่วนที่สุด', 'badge-danger', 'octagon-alert'],
            'High' => ['ด่วน', 'badge-warning', 'triangle-alert'],
            'Medium' => ['ปานกลาง', 'badge-info', 'info'],
            'Low' => ['ปกติ', 'badge-neutral', 'circle-check'],
        ],
        'attendance' => [
            'normal' => ['ปกติ', 'badge-success', 'circle-check'],
            'late' => ['สาย', 'badge-warning', 'clock'],
            'ot' => ['ล่วงเวลา', 'badge-info', 'zap'],
            'leave' => ['ลา', 'badge-neutral', 'calendar-minus'],
            'absent' => ['ขาดงาน', 'badge-danger', 'user-x'],
            'missing' => ['ยังไม่ลงเวลา', 'badge-neutral', 'clock'],
        ],
        'leave' => [
            'pending' => ['รออนุมัติ', 'badge-warning', 'clock'],
            'approved' => ['อนุมัติแล้ว', 'badge-success', 'circle-check'],
            'rejected' => ['ไม่อนุมัติ', 'badge-danger', 'circle-x'],
        ],
        'room' => [
            'available' => ['ว่าง', 'badge--available', 'circle-check'],
            'in-use' => ['กำลังใช้งาน', 'badge--in-use', 'users'],
            'maintenance' => ['ระงับใช้งาน', 'badge--maintenance', 'wrench'],
        ],
    ];
    [$label, $class, $iconName] = $map[$type][$status] ?? [(string) $status, 'badge-neutral', 'info'];

    return ['label' => $label, 'class' => $class, 'icon' => $iconName];
}

function status_badge(string $type, ?string $status): string
{
    $m = status_meta($type, $status);

    return '<span class="badge ' . e($m['class']) . '">' . icon($m['icon']) . e($m['label']) . '</span>';
}

function org_type_label(?string $type): string
{
    return [
        'Government Agency' => 'หน่วยงานราชการ',
        'Local Administrative Organization' => 'องค์กรปกครองส่วนท้องถิ่น / รัฐวิสาหกิจ',
        'Private Organization' => 'หน่วยงานเอกชน / บุคคลทั่วไป',
    ][$type] ?? (string) $type;
}

function money(float|int|string|null $amount): string
{
    return number_format((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2) . ' บาท';
}

/** query string ของหน้าปัจจุบันโดยแทนค่าบางคีย์ (ใช้กับลิงก์แบ่งหน้า) */
function query_with(array $changes): string
{
    $query = array_filter(array_merge($_GET, $changes), static fn ($v) => $v !== null && $v !== '');

    return $query ? '?' . http_build_query($query) : '';
}

/** attribute aria ของช่องที่มีข้อผิดพลาด (คู่กับ field_error) */
function field_invalid(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
}

/** ข้อความผิดพลาดใต้ช่อง (DESIGN_SYSTEM 7.7) */
function field_error(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<p class="form-error" id="err-' . e($key) . '">' . icon('circle-alert') . e($errors[$key]) . '</p>'
        : '';
}

/** label พร้อมเครื่องหมายช่องบังคับหรือ "(ไม่บังคับ)" */
function field_label(string $for, string $text, bool $required = true): string
{
    return '<label class="form-label" for="' . e($for) . '">' . e($text)
        . ($required ? ' <span class="required" aria-hidden="true">*</span>' : ' <span class="optional">(ไม่บังคับ)</span>')
        . '</label>';
}

/** ตัวเลขแบบตัดศูนย์ท้าย เช่น 5.0 → "5", 2.5 → "2.5" */
function num(float|int|string|null $value, int $decimals = 1): string
{
    return rtrim(rtrim(number_format((float) $value, $decimals), '0'), '.');
}

/** เก็บข้อความแจ้งเตือนไว้แสดงในหน้าถัดไป */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int, array{type: string, message: string}> */
function pull_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $messages;
}
