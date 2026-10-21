<?php

declare(strict_types=1);

// เมนู sidebar: [path, ข้อความ, ไอคอน, บทบาทที่เห็น (null = ทุกคน)]
return [
    'หลัก' => [
        ['/dashboard', 'ภาพรวม', 'layout-dashboard', null],
    ],
    'บริการจอง' => [
        ['/rooms', 'ห้องประชุม', 'door-open', null],
        ['/bookings/create', 'จองห้องประชุม', 'calendar-plus', null],
        ['/calendar', 'ปฏิทินการจอง', 'calendar', null],
        ['/bookings', 'ประวัติการจอง', 'clipboard-list', null],
        ['/bookings/approval', 'อนุมัติการจอง', 'square-check', config('roles.approvers')],
    ],
    'งานซ่อมบำรุง' => [
        ['/maintenance/create', 'แจ้งซ่อม', 'wrench', null],
        ['/maintenance', 'ใบงานซ่อมบำรุง', 'file-text', null],
        ['/technician', 'สำหรับนายช่าง', 'user-cog', config('roles.technicians')],
    ],
    'ลงเวลาและการลา' => [
        ['/attendance', 'ลงเวลาปฏิบัติงาน', 'clock', null],
        ['/attendance/history', 'ประวัติการลงเวลา', 'history', null],
        ['/leave', 'ใบลา', 'calendar-minus', null],
        ['/leave/approval', 'อนุมัติใบลา', 'check-check', config('roles.hr')],
        ['/attendance/reports', 'รายงานการลงเวลา', 'chart-pie', config('roles.hr')],
    ],
    'รายงานและบริหาร' => [
        ['/revenue', 'รายได้', 'chart-line', config('roles.reports')],
        ['/reports', 'รายงานสถิติ', 'chart-column', config('roles.reports')],
        ['/departments', 'แผนก/กอง', 'network', config('roles.admins')],
        ['/users', 'ผู้ใช้งาน', 'users', config('roles.admins')],
    ],
    'ระบบ' => [
        ['/notifications', 'การแจ้งเตือน', 'bell', null],
        ['/audit-logs', 'บันทึกการใช้งาน', 'shield-check', config('roles.admins')],
        ['/settings', 'ตั้งค่าระบบ', 'settings', config('roles.admins')],
    ],
];
