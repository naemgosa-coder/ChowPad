<?php

use App\Services\View;

/** @var array<int, array<string, mixed>> $jobs @var bool $isAdmin */
$user = auth_user();
$open = array_filter($jobs, static fn ($j) => in_array($j['status'], ['Waiting', 'Accepted', 'In Progress'], true));
?>
<div class="page-header">
  <div>
    <h1>พอร์ทัลนายช่าง</h1>
    <p>จัดการคิวใบงาน อัปเดตสถานะ และรายงานผลการซ่อมพร้อมค่าใช้จ่าย</p>
  </div>
</div>

<div class="card d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
  <div class="d-flex align-items-center gap-3">
    <span class="avatar" aria-hidden="true"><?= e(initials($user['name'])) ?></span>
    <div>
      <div class="fw-semibold"><?= e($user['name']) ?></div>
      <div class="text-caption text-muted"><?= e($isAdmin ? 'ผู้ดูแลระบบ · เห็นใบงานที่ยังไม่ปิดทั้งหมด' : ($user['skill'] ? 'ความชำนาญ: ' . $user['skill'] : role_label($user['role']))) ?></div>
    </div>
  </div>
  <span class="badge badge-warning"><?= icon('clock') ?>งานที่ยังไม่ปิด <?= e(count($open)) ?> รายการ</span>
</div>

<section class="card" aria-labelledby="jobs-title">
  <h2 class="card-title mb-3" id="jobs-title"><?= icon('wrench') ?><?= $isAdmin ? 'ใบงานที่ยังไม่ปิด' : 'ใบงานของคุณและงานที่ยังไม่มีผู้รับ' ?></h2>
  <?php if (!$jobs): ?>
    <div class="empty-state"><?= icon('smile', 'icon-xl') ?><strong>ไม่มีใบงานค้างในขณะนี้</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead>
          <tr>
            <th scope="col">เลขใบงาน</th>
            <th scope="col">สถานที่</th>
            <th scope="col">อาการเสีย</th>
            <th scope="col">ความเร่งด่วน</th>
            <th scope="col">สถานะ</th>
            <th scope="col">การดำเนินการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($jobs as $job): ?>
            <tr>
              <td data-label="เลขใบงาน" class="nowrap"><a class="fw-semibold" href="<?= e(url('/maintenance/' . $job['id'])) ?>"><?= e($job['id']) ?></a></td>
              <td data-label="สถานที่"><?= e($job['room_name']) ?></td>
              <td data-label="อาการเสีย"><span class="truncate-2"><?= e($job['description']) ?></span></td>
              <td data-label="ความเร่งด่วน"><?= status_badge('priority', $job['priority']) ?></td>
              <td data-label="สถานะ"><?= status_badge('repair', $job['status']) ?><?= $isAdmin && $job['technician_name'] ? '<div class="text-caption text-muted mt-1">' . e($job['technician_name']) . '</div>' : '' ?></td>
              <td data-label="การดำเนินการ"><?= View::partial('partials/repair-actions', ['repair' => $job, 'returnTo' => '/technician']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?= View::partial('partials/complete-dialog') ?>
