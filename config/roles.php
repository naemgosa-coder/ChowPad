<?php

declare(strict_types=1);

// กลุ่มบทบาทที่ใช้ร่วมกันระหว่าง routes/web.php และเมนู (config/menu.php)
// เวอร์ชัน HTML เดิมไม่มีการจำกัดสิทธิ์ ค่าเหล่านี้กำหนดใหม่ตามหน้าที่ของแต่ละบทบาท
return [
    'admins' => ['Administrator'],
    'approvers' => ['Administrator', 'Officer', 'Executive'],
    'maintenance_staff' => ['Administrator', 'Officer', 'Technician', 'Executive'],
    'maintenance_leads' => ['Administrator', 'Officer'],
    'technicians' => ['Administrator', 'Technician'],
    'reports' => ['Administrator', 'Officer', 'Executive'],
    'hr' => ['Administrator', 'Officer', 'Executive'],
];
