<?php

/**
 * สร้างฐานข้อมูล ตาราง และข้อมูลตัวอย่าง
 *
 *   php database/migrate.php           สร้างตารางที่ยังไม่มี และ seed ถ้ายังไม่มีผู้ใช้ในระบบ
 *   php database/migrate.php --fresh   ลบทุกตารางแล้วสร้างใหม่ (ข้อมูลเดิมหายทั้งหมด)
 *
 * ข้อมูลตัวอย่างย้ายมาจากตัว seeder ใน assets/js/app.js ของเวอร์ชัน HTML เดิม
 * บัญชีทดสอบทุกบัญชีใช้รหัสผ่านเท่ากับ username (เช่น admin / admin)
 */

declare(strict_types=1);

use App\Services\Database;

if (PHP_SAPI !== 'cli') {
    exit("ใช้ได้จาก command line เท่านั้น\n");
}

require dirname(__DIR__) . '/config/bootstrap.php';

$fresh = in_array('--fresh', $argv, true);
$c = config('database');

// สร้าง database ถ้ายังไม่มี (ชื่อ database มาจาก .env จึงตรวจรูปแบบก่อนนำไปต่อ SQL)
if (!preg_match('/^\w+$/', $c['database'])) {
    exit("DB_DATABASE ต้องเป็นตัวอักษร ตัวเลข หรือ _ เท่านั้น\n");
}
$server = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], $c['port']),
    $c['username'],
    $c['password'],
    Database::options()
);
$server->exec("CREATE DATABASE IF NOT EXISTS `{$c['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$pdo = Database::connection();

if ($fresh) {
    echo "ลบตารางเดิมทั้งหมด...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec("DROP TABLE `{$table}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

echo "สร้างตาราง...\n";
foreach (array_filter(array_map('trim', explode(';', (string) file_get_contents(__DIR__ . '/schema.sql')))) as $sql) {
    $pdo->exec($sql);
}

// อัปเกรดฐานข้อมูลที่สร้างจากเวอร์ชันก่อน (CREATE TABLE IF NOT EXISTS ไม่เพิ่มคอลัมน์ให้ตารางเดิม)
$upgrades = [
    ['notifications', 'audience', 'ALTER TABLE notifications ADD COLUMN audience VARCHAR(30) NULL AFTER user_id'],
];
foreach ($upgrades as [$table, $column, $sql]) {
    $exists = Database::value(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $column]
    );
    if (!$exists) {
        echo "อัปเกรด: เพิ่มคอลัมน์ {$table}.{$column}\n";
        $pdo->exec($sql);
    }
}
if (!Database::value("SELECT COUNT(*) FROM settings WHERE `key` = 'booking_rules'")) {
    Database::query("INSERT INTO settings (`key`, value) VALUES ('booking_rules', ?)", [json_encode([
        'max_advance_days' => 90, 'max_hours' => 8, 'open_time' => '07:00', 'close_time' => '20:00',
        'auto_approve' => false, 'block_holidays' => true,
    ])]);
}

if ((int) Database::value('SELECT COUNT(*) FROM users') > 0) {
    echo "มีข้อมูลอยู่แล้ว ข้ามการ seed (ใช้ --fresh ถ้าต้องการเริ่มใหม่)\n";
    exit(0);
}

// สุ่มแบบกำหนด seed ไว้ เพื่อให้ข้อมูลตัวอย่างออกมาเหมือนกันทุกครั้ง
mt_srand(2569);
$pick = static fn (array $items): mixed => $items[mt_rand(0, count($items) - 1)];
$day = static fn (int $offset): string => date('Y-m-d', strtotime("{$offset} days"));
$insert = static function (string $table, array $row): void {
    $cols = array_keys($row);
    Database::query(
        sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn ($col) => "`{$col}`", $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        ),
        array_values($row)
    );
};

Database::transaction(static function () use ($pick, $day, $insert): void {
    echo "seed แผนก...\n";
    $departments = [
        ['DEP-01', 'ฝ่ายบริหารงานทั่วไป', 'GEN'],
        ['DEP-02', 'แผนกเทคโนโลยีสารสนเทศ', 'IT'],
        ['DEP-03', 'ฝ่ายโยธาและซ่อมบำรุง', 'MAINT'],
        ['DEP-04', 'ฝ่ายยุทธศาสตร์การพัฒนา', 'STRAT'],
        ['DEP-05', 'สำนักงานจังหวัด', 'PROV'],
        ['DEP-06', 'องค์การบริหารส่วนจังหวัด (อบจ.)', 'PAO'],
        ['DEP-07', 'เทศบาลเมือง', 'MUN'],
    ];
    foreach ($departments as [$id, $name, $code]) {
        $insert('departments', ['id' => $id, 'name' => $name, 'code' => $code]);
    }

    echo "seed ห้องประชุม...\n";
    $rooms = [
        ['RM-001', 'ห้องประชุม ชั้น 3 (ห้องที่ 1)', 'ศาลากลางจังหวัดหลังใหม่', '3', '20-30', 200, 400,
            ['Projector', 'Microphone', 'Sound System', 'LED Display', 'Air Conditioner', 'WiFi'],
            'เปิดใช้งานสำหรับหน่วยงานราชการและองค์กรปกครองส่วนท้องถิ่นในการประชุมสัมมนาทั่วไป', '',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=600'],
        ['RM-002', 'ห้องประชุม ชั้น 4 (ห้องที่ 2 - Conference Room)', 'ศาลากลางจังหวัดหลังใหม่', '4', '30-40', 200, 400,
            ['Conference Camera', 'Conference Microphone', 'Zoom', 'Projector', 'TV', 'WiFi'],
            'ห้องนี้ใช้สำหรับการประชุมทางไกลผ่านระบบวิดีโอ (Video Conference) เป็นหลัก', '',
            'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&q=80&w=600'],
        ['RM-003', 'ห้องประชุม ชั้น 5 (ห้องที่ 3)', 'ศาลากลางจังหวัดหลังใหม่', '5', '100-130', 375, 750,
            ['Professional Sound System', 'Wireless Microphone', 'Stage', 'LED Screen', 'Projector', 'Conference System'],
            'ห้องประชุมขนาดใหญ่ รองรับการจัดงานพิธีการ การอบรมสัมมนาขนาดใหญ่', '',
            'https://images.unsplash.com/photo-1517502884422-41eaaced0168?auto=format&fit=crop&q=80&w=600'],
        ['RM-004', 'ห้องประชุม ชั้น 2 (ห้องที่ 4 - ศาลากลางหลังเก่า)', 'ศาลากลางจังหวัดหลังเก่า', '2', '15-20', 200, 500,
            ['Air Conditioner', 'WiFi'],
            'ห้องประชุมทั่วไปในอาคารศาลากลางหลังเก่า',
            'คำเตือน: ห้องประชุมนี้มีอุปกรณ์ไม่ครบถ้วน ผู้ขอใช้บริการต้องเตรียมคอมพิวเตอร์โน้ตบุ๊ก เครื่องฉายโปรเจกเตอร์ และอุปกรณ์นำเสนอมาด้วยตนเอง',
            'https://images.unsplash.com/photo-1416339306562-f3d12fefd36f?auto=format&fit=crop&q=80&w=600'],
    ];
    $roomByName = [];
    foreach ($rooms as [$id, $name, $building, $floor, $capacity, $gov, $local, $equipment, $rules, $warnings, $image]) {
        $insert('rooms', [
            'id' => $id, 'name' => $name, 'building' => $building, 'floor' => $floor, 'capacity' => $capacity,
            'price_gov' => $gov, 'price_local' => $local, 'equipment' => json_encode($equipment, JSON_UNESCAPED_UNICODE),
            'status' => 'Available', 'rules' => $rules, 'warnings' => $warnings, 'locked' => 0, 'image' => $image,
        ]);
        $roomByName[$name] = ['id' => $id, 'gov' => $gov, 'local' => $local];
    }

    echo "seed ผู้ใช้ (ใช้เวลาสักครู่เพราะต้อง hash รหัสผ่าน)...\n";
    $deptIdByName = array_column(array_map(static fn ($d) => ['id' => $d[0], 'name' => $d[1]], $departments), 'id', 'name');
    $users = [
        ['USR-001', 'อภิสิทธิ์ ชัยชนะ', 'admin', 'admin@provincialhall.go.th', 'Administrator', 'แผนกเทคโนโลยีสารสนเทศ', 'นักวิชาการคอมพิวเตอร์ชำนาญการ',
            'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&q=80&w=100&h=100'],
        ['USR-002', 'ณิชาภัทร สุขใส', 'officer', 'officer@provincialhall.go.th', 'Officer', 'ฝ่ายบริหารงานทั่วไป', 'เจ้าพนักงานธุรการ',
            'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=100&h=100'],
        ['USR-003', 'สมยศ รักษ์ธรรม', 'technician', 'technician@provincialhall.go.th', 'Technician', 'ฝ่ายโยธาและซ่อมบำรุง', 'นายช่างไฟฟ้า',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=100&h=100'],
        ['USR-004', 'วิชัย กิตติคุณ', 'manager', 'manager@provincialhall.go.th', 'Executive', 'สำนักงานจังหวัด', 'หัวหน้าสำนักงานจังหวัด',
            'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=100&h=100'],
        ['USR-005', 'สมชาย แสนดี', 'employee', 'employee@provincialhall.go.th', 'Employee', 'ฝ่ายยุทธศาสตร์การพัฒนา', 'นักวิเคราะห์นโยบายและแผน',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=100&h=100'],
    ];
    $firstNames = ['สุรศักดิ์', 'วิเชียร', 'กิตติเดช', 'มานะ', 'พรหมินทร์', 'ประสงค์', 'อรุณ', 'นพคุณ', 'สายันต์', 'สมเกียรติ', 'ขจรศักดิ์', 'สุทัศน์', 'รุ่งโรจน์', 'ไพโรจน์', 'นเรศ', 'สุชาดา', 'กานดา', 'วิไลพร', 'อุมาพร', 'พัชราภรณ์', 'จิตรา', 'กุหลาบ', 'ทิพย์วรรณ', 'วิภา', 'สุวรรณี'];
    $lastNames = ['ศรีสุข', 'วงษ์สุวรรณ', 'ใจดี', 'รักษาธรรม', 'รุ่งเรือง', 'เลิศดี', 'เจริญชัย', 'สิริโสภา', 'เดชา', 'ประเสริฐศิลป์', 'แก้วมณี', 'ภักดี', 'สวัสดิ์ดี', 'ทองคำ', 'พูนทรัพย์'];
    $staffRoles = ['Employee', 'Employee', 'Employee', 'Executive', 'Officer'];
    $staffDepts = ['ฝ่ายบริหารงานทั่วไป', 'แผนกเทคโนโลยีสารสนเทศ', 'ฝ่ายยุทธศาสตร์การพัฒนา', 'สำนักงานจังหวัด'];
    for ($i = 6; $i <= 100; $i++) {
        $users[] = [sprintf('USR-%03d', $i), $pick($firstNames) . ' ' . $pick($lastNames), "staff{$i}",
            "staff{$i}@provincialhall.go.th", $pick($staffRoles), $pick($staffDepts), null, null];
    }
    // ช่าง 4 คนแรกใช้ชื่อเดียวกับที่ปรากฏในใบงานซ่อมตัวอย่างเดิม
    $techNames = ['กิตติ พึ่งตน', 'มนตรี วันดี', 'สุชาติ รักงาน', 'วิจิตร ช่างไม้'];
    $techSkills = ['เครื่องปรับอากาศ', 'ไฟฟ้า/แสงสว่าง', 'คอมพิวเตอร์', 'เครือข่าย/อินเทอร์เน็ต', 'เฟอร์นิเจอร์/โครงสร้าง', 'น้ำประปา'];
    $techSkill = ['USR-003' => 'ไฟฟ้า/แสงสว่าง'];
    for ($i = 101; $i <= 115; $i++) {
        $id = "USR-{$i}";
        $name = $techNames[$i - 101] ?? $pick($firstNames) . ' ' . $pick($lastNames);
        $users[] = [$id, 'ช่าง' . $name, 'tech' . ($i - 100), 'tech' . ($i - 100) . '@provincialhall.go.th',
            'Technician', 'ฝ่ายโยธาและซ่อมบำรุง', 'นายช่าง', null];
        $techSkill[$id] = $pick($techSkills);
    }
    $technicianIds = [];
    $technicianByName = [];
    foreach ($users as [$id, $name, $username, $email, $role, $dept, $position, $avatar]) {
        $insert('users', [
            'id' => $id, 'username' => $username, 'email' => $email,
            'password_hash' => password_hash($username, PASSWORD_DEFAULT),
            'name' => $name, 'role' => $role, 'department_id' => $deptIdByName[$dept] ?? null,
            'position' => $position, 'phone' => '08' . mt_rand(1, 9) . '-' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999),
            'skill' => $techSkill[$id] ?? null, 'avatar' => $avatar, 'status' => 'Active',
        ]);
        if ($role === 'Technician') {
            $technicianIds[] = $id;
            $technicianByName[preg_replace('/^ช่าง/u', '', $name)] = $id;
        }
    }

    echo "seed การจอง...\n";
    $bookingCount = 0;
    foreach (json_decode((string) file_get_contents(__DIR__ . '/seeds/bookings.json'), true) as $b) {
        $insert('bookings', [
            'id' => $b['id'], 'room_id' => $roomByName[$b['room']]['id'], 'title' => $b['title'],
            'org_type' => $b['orgType'], 'department' => $b['department'], 'organizer' => $b['organizer'],
            'phone' => $b['phone'], 'email' => $b['email'], 'date' => $b['date'],
            'start_time' => $b['startTime'], 'end_time' => $b['endTime'], 'participants' => $b['participants'],
            'equipment' => json_encode($b['equipment'], JSON_UNESCAPED_UNICODE), 'coffee_break' => (int) $b['coffeeBreak'],
            'remarks' => $b['remarks'], 'status' => $b['status'], 'duration_hours' => $b['durationHours'],
            'rental_fee' => $b['rentalFee'],
        ]);
        $bookingCount++;
    }
    $topics = [
        'ประชุมคณะอนุกรรมการทรัพยากรน้ำจังหวัด', 'อบรมการใช้งานระบบตรวจสอบความโปร่งใสหน่วยงานภาครัฐ',
        'การประชุมชี้แจงงบประมาณกองทุนฟื้นฟูเกษตรกร', 'สัมมนาผู้บริหารองค์กรปกครองส่วนท้องถิ่นในการขับเคลื่อน Smart City',
        'ประชุมวิสามัญพิจารณาความพร้อมโครงการจัดตั้งศูนย์บริการนักท่องเที่ยว', 'ประชุมวิเคราะห์ดัชนีชี้วัดความพึงพอใจการให้บริการประชาชน',
        'ประชุมหารือคณะทำงานปรับปรุงภูมิทัศน์เขื่อนห้วยหลวง', 'ประชุมเตรียมความพร้อมกิจกรรมเฉลิมพระเกียรติ',
        'อบรมบุคลากรเทศบาลตำบลด้านวินัยและการรักษาวินัยข้าราชการ', 'สัมมนาเชิงปฏิบัติการพิจารณาคำขอรับการจัดสรรงบประมาณเงินอุดหนุนเฉพาะกิจ',
        'การประชุมพิจารณาจัดตั้งสำนักงานเขตพัฒนาเศรษฐกิจพิเศษ', 'การประชุมคณะกรรมการพัฒนาจังหวัด (ก.บ.จ.) ร่วมกับหอการค้าจังหวัด',
        'ประชุมซักซ้อมความเข้าใจมาตรการประหยัดพลังงานในหน่วยงานภาครัฐ', 'อบรมพัฒนาระบบประเมินผลการเรียนรู้ผ่านสื่อดิจิทัลทางการศึกษา',
    ];
    $orgTypes = ['Government Agency', 'Local Administrative Organization', 'Private Organization'];
    $bookingDepts = ['ฝ่ายบริหารงานทั่วไป', 'แผนกเทคโนโลยีสารสนเทศ', 'ฝ่ายยุทธศาสตร์การพัฒนา', 'สำนักงานจังหวัด', 'องค์การบริหารส่วนจังหวัด (อบจ.)', 'เทศบาลเมือง'];
    $slots = [['09:00', '12:00', 3], ['13:00', '16:00', 3], ['09:30', '11:30', 2], ['14:00', '17:00', 3], ['10:00', '12:00', 2]];
    // เวอร์ชันเดิมไม่สุ่มสถานะ "รออนุมัติ" ให้การจองในอนาคต ที่นี่เพิ่มไว้ เพื่อให้หน้าอนุมัติมีข้อมูลตัวอย่าง
    $statuses = ['Approved', 'Approved', 'Approved', 'Approved', 'Rejected', 'Cancelled', 'Completed', 'Waiting Approval'];
    $taken = [];
    while ($bookingCount < 300) {
        $offset = mt_rand(-90, 15);
        $room = $pick(array_values($roomByName));
        $slot = $pick($slots);
        $date = $day($offset);
        $key = "{$room['id']}|{$date}|" . ($slot[0] < '12:30' ? 'am' : 'pm');
        if (isset($taken[$key])) {
            continue; // กันเวลาชนกันในห้องเดียวกัน
        }
        $taken[$key] = true;

        $orgType = $pick($orgTypes);
        $status = $pick($statuses);
        if ($offset < 0 && in_array($status, ['Approved', 'Waiting Approval'], true)) {
            $status = 'Completed';
        }
        $bookingCount++;
        $insert('bookings', [
            'id' => sprintf('BK-2026-%04d', $bookingCount), 'room_id' => $room['id'], 'title' => $pick($topics),
            'org_type' => $orgType, 'department' => $pick($bookingDepts), 'organizer' => 'ข้าราชการ ศาลากลางจังหวัด',
            'phone' => '081-' . mt_rand(1000000, 9999999), 'email' => 'staff.seed@provincialhall.go.th',
            'date' => $date, 'start_time' => $slot[0], 'end_time' => $slot[1], 'participants' => mt_rand(15, 75),
            'equipment' => json_encode(['Projector', 'Air Conditioner', 'WiFi']), 'coffee_break' => mt_rand(0, 1),
            'remarks' => 'ประชุมสัญจรตามนโยบายจังหวัด', 'status' => $status, 'duration_hours' => $slot[2],
            'rental_fee' => ($orgType === 'Government Agency' ? $room['gov'] : $room['local']) * $slot[2],
            'user_id' => 'USR-' . sprintf('%03d', mt_rand(1, 100)),
        ]);
    }

    echo "seed ใบงานซ่อม...\n";
    $repairCount = 0;
    foreach (json_decode((string) file_get_contents(__DIR__ . '/seeds/maintenance.json'), true) as $r) {
        $insert('repairs', [
            'id' => $r['id'], 'room_id' => $roomByName[$r['room']]['id'], 'date' => $r['date'],
            'category' => $r['category'], 'description' => $r['description'], 'priority' => $r['priority'],
            'technician_id' => $technicianByName[$r['technician']] ?? null, 'status' => $r['status'],
            'before_image' => $r['beforeImage'], 'after_image' => $r['afterImage'], 'repair_notes' => $r['repairNotes'],
            'material_used' => $r['materialUsed'], 'labor_cost' => $r['laborCost'], 'material_cost' => $r['materialCost'],
            'start_time' => $r['startTime'], 'finish_time' => $r['finishTime'], 'reported_by' => 'USR-002',
        ]);
        $repairCount++;
    }
    $categoryThai = [
        'Air Conditioner' => 'แอร์เสีย/ไม่เย็น', 'Electrical' => 'หลอดไฟเสีย/ไฟกะพริบ',
        'Computer' => 'เครื่องคอมพิวเตอร์ประชุมไม่เปิด', 'Network' => 'สปีดเน็ตช้า/WiFi หลุดบ่อย',
        'Projector' => 'โปรเจคเตอร์ไม่มีสัญญาณภาพ', 'Furniture' => 'เก้าอี้ประชุมหัก/โต๊ะชำรุด',
        'Water Supply' => 'ก๊อกน้ำรั่วในห้องน้ำห้องประชุม', 'Cleaning' => 'มีกลิ่นอับ/ต้องการทำความสะอาดใหญ่',
    ];
    $repairStatuses = ['Waiting', 'Accepted', 'In Progress', 'Completed', 'Completed', 'Completed'];
    while ($repairCount < 300) {
        $offset = mt_rand(-80, 10);
        $category = $pick(array_keys($categoryThai));
        $status = $pick($repairStatuses);
        if ($offset < -5) {
            $status = 'Completed';
        }
        $done = $status === 'Completed';
        $repairCount++;
        $insert('repairs', [
            'id' => sprintf('MNT-2026-%04d', $repairCount), 'room_id' => $pick(array_values($roomByName))['id'],
            'date' => $day($offset), 'category' => $category,
            'description' => "{$categoryThai[$category]} รบกวนช่างเข้าตรวจสอบโดยด่วน เพื่อเตรียมจัดงานประชุมสำคัญ",
            'priority' => $pick(['Low', 'Medium', 'High', 'Critical']), 'status' => $status,
            'technician_id' => $status === 'Waiting' ? null : $pick($technicianIds),
            'before_image' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&q=80&w=400',
            'after_image' => $done ? 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=400' : null,
            'repair_notes' => $done ? 'ตรวจสอบและแก้ไขเปลี่ยนอุปกรณ์บอร์ดคอนโทรลเรียบร้อย' : 'ช่างได้รับมอบหมายงานแล้ว อยู่ในคิวดำเนินการ',
            'material_used' => $done ? 'บอร์ดคอนโทรล, น็อตยึดขาตั้ง' : '',
            'labor_cost' => $done ? mt_rand(200, 1000) : 0, 'material_cost' => $done ? mt_rand(100, 1600) : 0,
            'start_time' => $status === 'Waiting' ? null : '10:00', 'finish_time' => $done ? '12:00' : null,
            'reported_by' => 'USR-' . sprintf('%03d', mt_rand(1, 100)),
        ]);
    }

    echo "seed การแจ้งเตือน ตั้งค่า วันหยุด ประเภทการลา...\n";
    $notifications = [
        ['แจ้งซ่อมใหม่ (ด่วนที่สุด)', 'MNT-2026-0002 หลอดภาพโปรเจกเตอร์เสีย ชั้น 2 ศาลากลางหลังเก่า', 'triangle-alert', 'danger', 'maintenance_staff', null, '-1 hour'],
        ['การจองของคุณได้รับอนุมัติแล้ว', 'BK-2026-0001 สำหรับจัดงานสัญจรพัฒนาประจำปี ได้รับการอนุมัติแล้ว', 'circle-check', 'success', null, 'USR-004', '-3 hours'],
        ['ปรับปรุงระบบจองห้องประชุม', 'ระบบจองห้องประชุมเวอร์ชันใหม่เปิดให้ใช้งานแล้ว', 'info', 'primary', null, null, '-1 day'],
    ];
    foreach ($notifications as [$title, $desc, $icon, $color, $audience, $userId, $ago]) {
        $insert('notifications', [
            'title' => $title, 'description' => $desc, 'icon' => $icon, 'color' => $color,
            'audience' => $audience, 'user_id' => $userId, 'created_at' => date('Y-m-d H:i:s', strtotime($ago)),
        ]);
    }

    $insert('settings', ['key' => 'working_hours', 'value' => json_encode(['start' => '08:30', 'end' => '16:30'])]);
    // booking_rules ถูกเพิ่มในส่วนอัปเกรดด้านบนแล้ว

    $holidays = [
        ['2026-01-01', 'วันขึ้นปีใหม่'], ['2026-04-13', 'วันสงกรานต์'], ['2026-04-14', 'วันสงกรานต์'],
        ['2026-04-15', 'วันสงกรานต์'], ['2026-05-01', 'วันแรงงานแห่งชาติ'], ['2026-07-28', 'วันเฉลิมพระชนมพรรษา ร.10'],
    ];
    foreach ($holidays as [$date, $name]) {
        $insert('holidays', ['date' => $date, 'name' => $name, 'type' => 'วันหยุดราชการ']);
    }

    // โควตาตามหน้าตั้งค่าการลาของระบบหลัก (ระบบลงเวลาเดิมใช้ตัวเลขชุดอื่น จึงยึดค่าที่ผู้ดูแลตั้ง)
    $leaveTypes = [
        ['vacation', 'ลาพักผ่อน', 10, 'Supervisor'],
        ['sick', 'ลาป่วย', 30, 'Department Head'],
        ['personal', 'ลากิจ', 45, 'Supervisor'],
        ['maternity', 'ลาคลอด', 90, 'Executive'],
    ];
    $leaveTypeId = [];
    foreach ($leaveTypes as $i => [$code, $name, $quota, $approval]) {
        $insert('leave_types', ['code' => $code, 'name' => $name, 'quota_days' => $quota, 'approval_level' => $approval]);
        $leaveTypeId[$code] = $i + 1;
    }

    echo "seed การลงเวลาและใบลา...\n";
    $holidayDates = array_column($holidays, 0);
    $attendanceUsers = array_map(static fn ($n) => sprintf('USR-%03d', $n), range(1, 20));
    for ($offset = -30; $offset <= -1; $offset++) {
        $ts = strtotime("{$offset} days");
        if ((int) date('N', $ts) >= 6 || in_array(date('Y-m-d', $ts), $holidayDates, true)) {
            continue;
        }
        foreach ($attendanceUsers as $userId) {
            $roll = mt_rand(1, 100);
            [$status, $in, $out, $remarks] = match (true) {
                $roll <= 70 => ['normal', sprintf('08:%02d:%02d', mt_rand(5, 29), mt_rand(0, 59)), sprintf('16:%02d:00', mt_rand(30, 59)), ''],
                $roll <= 85 => ['late', sprintf('08:%02d:%02d', mt_rand(31, 59), mt_rand(0, 59)), sprintf('16:%02d:00', mt_rand(30, 59)), 'การจราจรติดขัด'],
                $roll <= 95 => ['ot', sprintf('08:%02d:00', mt_rand(10, 29)), sprintf('19:%02d:00', mt_rand(0, 30)), 'ปฏิบัติงานล่วงเวลา'],
                default => ['leave', null, null, 'ลาป่วย'],
            };
            $insert('attendance_logs', [
                'user_id' => $userId, 'date' => date('Y-m-d', $ts), 'clock_in' => $in,
                'clock_out' => $out, 'status' => $status, 'remarks' => $remarks,
            ]);
        }
    }
    $leaves = [
        ['USR-005', 'vacation', -60, -56, 5, 'พักผ่อนประจำปี เดินทางต่างจังหวัด', 'approved'],
        ['USR-005', 'personal', -40, -39, 2, 'ติดต่อทำธุระโอนที่ดินต่างจังหวัด', 'approved'],
        ['USR-005', 'sick', -20, -20, 1, 'เป็นไข้หวัดใหญ่และเจ็บคอ มีใบรับรองแพทย์', 'approved'],
        ['USR-006', 'personal', 5, 5, 1, 'ติดต่อราชการที่อำเภอ', 'pending'],
        ['USR-007', 'vacation', 10, 12, 3, 'พาครอบครัวไปเที่ยวพักผ่อน', 'pending'],
    ];
    foreach ($leaves as [$userId, $type, $from, $to, $days, $reason, $status]) {
        $insert('leave_requests', [
            'user_id' => $userId, 'leave_type_id' => $leaveTypeId[$type], 'start_date' => $day($from),
            'end_date' => $day($to), 'days' => $days, 'reason' => $reason, 'status' => $status,
            'approved_by' => $status === 'approved' ? 'USR-001' : null,
        ]);
    }
});

echo "เสร็จแล้ว ล็อกอินด้วย admin / admin\n";
