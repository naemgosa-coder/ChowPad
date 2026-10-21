<div class="page-header">
  <div>
    <h1>ตั้งค่าระบบ</h1>
    <p>จัดการข้อมูลพื้นฐานของระบบจองห้องประชุม แจ้งซ่อม และลงเวลา</p>
  </div>
</div>

<div class="settings-grid">
  <?php foreach ([
      ['/settings/rooms', 'door-open', 'ห้องประชุม', 'เพิ่ม แก้ไข หรือลบห้องประชุม อาคาร ชั้น ความจุ อัตราค่าธรรมเนียม อุปกรณ์ และระงับการใช้งานห้องชั่วคราว'],
      ['/settings/booking-rules', 'sliders-horizontal', 'กฎการจอง', 'จองล่วงหน้าสูงสุด ระยะเวลาต่อครั้ง ช่วงเวลาเปิดรับจอง การปิดจองวันหยุด และการอนุมัติอัตโนมัติ'],
      ['/settings/holidays', 'calendar-days', 'เวลาทำการและวันหยุด', 'กำหนดเวลาทำการ (ใช้คิดการมาสาย) และวันหยุดราชการ (ไม่นับเป็นวันลาและปิดการจอง)'],
      ['/settings/leave-types', 'calendar-minus', 'โควตาการลา', 'กำหนดจำนวนวันลาสูงสุดต่อปีงบประมาณ และระดับผู้อนุมัติของแต่ละประเภทการลา'],
      ['/settings/backup', 'database', 'สำรองและกู้คืนข้อมูล', 'ดาวน์โหลดสำเนาข้อมูลทั้งหมดเป็นไฟล์ JSON และกู้คืนจากไฟล์สำรอง'],
      ['/settings/system', 'server', 'ข้อมูลระบบ', 'เวอร์ชันซอฟต์แวร์ ขนาดฐานข้อมูล จำนวนข้อมูลในแต่ละตาราง และคำเตือนการตั้งค่า'],
  ] as [$href, $iconName, $title, $desc]): ?>
    <a class="card settings-card" href="<?= e(url($href)) ?>">
      <span class="metric-icon"><?= icon($iconName, 'icon-lg') ?></span>
      <span class="h3"><?= e($title) ?></span>
      <span class="text-sm text-muted"><?= e($desc) ?></span>
      <span class="settings-card-link"><?= e('จัดการ' . $title) ?><?= icon('arrow-right', 'icon-sm') ?></span>
    </a>
  <?php endforeach; ?>
</div>
