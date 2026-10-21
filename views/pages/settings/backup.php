<?php
/** @var array<string, int> $counts @var array<string, string> $errors */
$labels = [
    'departments' => 'แผนก', 'users' => 'ผู้ใช้งาน', 'rooms' => 'ห้องประชุม', 'bookings' => 'การจอง', 'repairs' => 'ใบงานซ่อม',
    'notifications' => 'การแจ้งเตือน', 'notification_states' => 'สถานะการอ่าน', 'audit_logs' => 'บันทึกการใช้งาน',
    'settings' => 'ค่าตั้งค่า', 'holidays' => 'วันหยุด', 'leave_types' => 'ประเภทการลา',
    'attendance_logs' => 'บันทึกเวลา', 'leave_requests' => 'ใบลา',
];
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>สำรองและกู้คืนข้อมูล</h1>
  </div>
</div>

<div class="row g-4">
  <section class="col-lg-7 card" aria-labelledby="counts-title">
    <h2 class="card-title mb-3" id="counts-title"><?= icon('database') ?>ข้อมูลในระบบขณะนี้</h2>
    <ul class="count-grid list-unstyled">
      <?php foreach ($counts as $table => $n): ?>
        <li><span class="h3 tabular"><?= e(number_format($n)) ?></span><span class="text-caption text-muted"><?= e($labels[$table] ?? $table) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <div class="col-lg-5 d-flex flex-column gap-4">
    <section class="card" aria-labelledby="export-title">
      <h2 class="card-title mb-2" id="export-title"><?= icon('download') ?>สำรองข้อมูล</h2>
      <p class="text-sm text-muted mb-3">ดาวน์โหลดข้อมูลทุกตารางเป็นไฟล์ JSON ไฟล์นี้มีข้อมูลส่วนบุคคลและรหัสผ่านที่เข้ารหัสแล้ว ควรเก็บในที่ปลอดภัย</p>
      <a class="btn btn-primary btn-block" href="<?= e(url('/settings/backup/export')) ?>"><?= icon('download') ?>ดาวน์โหลดไฟล์สำรอง</a>
      <p class="form-help mt-2">ไม่รวมไฟล์รูปที่อัปโหลด (อยู่ในโฟลเดอร์ public/uploads) และสำหรับระบบใช้งานจริงควรใช้ mysqldump ร่วมด้วย</p>
    </section>

    <form class="card" method="post" action="<?= e(url('/settings/backup/restore')) ?>" enctype="multipart/form-data" novalidate
          data-confirm="กู้คืนข้อมูลจากไฟล์นี้หรือไม่?" data-confirm-text="ข้อมูลปัจจุบันทั้งหมดจะถูกแทนที่ด้วยข้อมูลในไฟล์ และอาจต้องเข้าสู่ระบบใหม่"
          data-confirm-button="กู้คืนข้อมูล" data-confirm-danger="1" data-busy-text="กำลังกู้คืน…" aria-labelledby="restore-title">
      <?= csrf_field() ?>
      <h2 class="card-title mb-2" id="restore-title"><?= icon('upload') ?>กู้คืนข้อมูล</h2>
      <div class="alert alert-warning mb-3"><?= icon('triangle-alert') ?><span>ข้อมูลปัจจุบันจะถูกแทนที่ทั้งหมด หากกู้คืนไม่สำเร็จ ระบบจะคงข้อมูลเดิมไว้</span></div>
      <div class="field">
        <?= field_label('f-backup', 'ไฟล์สำรอง (.json)') ?>
        <input class="form-control" type="file" id="f-backup" name="backup" accept="application/json,.json" required<?= field_invalid($errors, 'backup') ?>>
        <?= field_error($errors, 'backup') ?>
      </div>
      <button type="submit" class="btn btn-danger btn-block mt-3"><?= icon('upload') ?>กู้คืนข้อมูล</button>
    </form>
  </div>
</div>
