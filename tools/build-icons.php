<?php

/**
 * สร้าง public/images/icons.svg (SVG sprite) จาก Lucide เฉพาะไอคอนที่ระบบใช้
 * เพิ่มไอคอน: ใส่ชื่อลงใน $icons แล้วรัน `php tools/build-icons.php` (ต้องต่ออินเทอร์เน็ต)
 * รายชื่อไอคอนดูได้ที่ https://lucide.dev/icons
 */

declare(strict_types=1);

const LUCIDE_VERSION = '0.460.0';

$icons = [
    'accessibility', 'arrow-right', 'award', 'bell', 'bell-off', 'bot', 'briefcase', 'message-circle','building', 'building-2',
    'calculator', 'calendar', 'calendar-check', 'calendar-days', 'calendar-minus', 'calendar-plus',
    'calendar-range', 'camera', 'chart-bar', 'chart-column', 'chart-line', 'chart-pie', 'check',
    'check-check', 'chevron-down', 'chevron-left', 'chevron-right', 'circle-alert', 'circle-check',
    'circle-help', 'circle-plus', 'circle-play', 'circle-x', 'clipboard-list', 'clock', 'cpu', 'database',
    'door-open', 'download', 'eye', 'eye-off','file-spreadsheet', 'file-text', 'filter', 'gauge', 'history', 'image',
    'info', 'key', 'layers', 'layout-dashboard', 'link', 'loader-circle', 'lock', 'log-in', 'log-out',
    'map-pin', 'menu', 'mic', 'monitor', 'monitor-play', 'network', 'octagon-alert', 'pencil', 'play',
    'plus', 'presentation', 'printer', 'projector', 'rotate-cw', 'save', 'search', 'search-code', 'search-x',
    'send', 'server', 'settings', 'settings-2', 'shield', 'shield-alert', 'shield-check', 'sliders-horizontal',
    'smile', 'square-check', 'square-pen', 'table', 'tag', 'text-quote', 'trash-2', 'triangle-alert', 'tv',
    'upload', 'cloud-upload', 'user', 'user-check', 'user-cog', 'user-plus', 'user-x', 'users', 'video',
    'wallet', 'wifi', 'wrench', 'x', 'zap',
];

$symbols = [];
$missing = [];
foreach (array_unique($icons) as $name) {
    $svg = @file_get_contents('https://unpkg.com/lucide-static@' . LUCIDE_VERSION . "/icons/{$name}.svg");
    if ($svg === false || !preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $m)) {
        $missing[] = $name;
        continue;
    }
    $body = preg_replace('/\s+/', ' ', trim($m[1]));
    $symbols[] = "<symbol id=\"{$name}\" viewBox=\"0 0 24 24\">{$body}</symbol>";
}

if ($missing) {
    fwrite(STDERR, 'ไม่พบไอคอน: ' . implode(', ', $missing) . "\n");
    exit(1);
}

$sprite = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" '
    . "stroke-linecap=\"round\" stroke-linejoin=\"round\">\n" . implode("\n", $symbols) . "\n</svg>\n";
file_put_contents(dirname(__DIR__) . '/public/images/icons.svg', $sprite);
echo 'สร้าง sprite แล้ว ' . count($symbols) . " ไอคอน\n";
