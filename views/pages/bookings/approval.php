<?php

use App\Services\View;

/** @var array<int, array<string, mixed>> $bookings */
?>
<div class="page-header">
  <div>
    <h1>พิจารณาอนุมัติคำขอจองห้องประชุม</h1>
    <p>ตรวจสอบและอนุมัติหรือไม่อนุมัติคำขอจองจากหน่วยงานต่าง ๆ (<?= e(count($bookings)) ?> รายการรอพิจารณา)</p>
  </div>
</div>

<section class="card">
  <?php if (!$bookings): ?>
    <div class="empty-state">
      <?= icon('circle-check', 'icon-xl') ?>
      <strong>ไม่มีคำขอรออนุมัติ</strong>
      <span>คำขอใหม่จะปรากฏที่นี่</span>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead>
          <tr>
            <th scope="col">เลขที่</th>
            <th scope="col">ห้องประชุม</th>
            <th scope="col">หัวข้อการประชุม</th>
            <th scope="col">วันและเวลา</th>
            <th scope="col">หน่วยงาน / ผู้จอง</th>
            <th scope="col" class="num">ค่าธรรมเนียม</th>
            <th scope="col">การดำเนินการ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td data-label="เลขที่" class="nowrap"><a href="<?= e(url('/bookings/' . $b['id'])) ?>" class="fw-semibold"><?= e($b['id']) ?></a></td>
              <td data-label="ห้องประชุม"><?= e($b['room_name']) ?></td>
              <td data-label="หัวข้อ"><?= e($b['title']) ?></td>
              <td data-label="วันและเวลา" class="nowrap"><?= e(thai_date($b['date'])) ?><br><span class="text-caption text-muted tabular"><?= e(time_range($b['start_time'], $b['end_time'])) ?></span></td>
              <td data-label="หน่วยงาน"><?= e($b['department']) ?><br><span class="text-caption text-muted"><?= e($b['organizer']) ?></span></td>
              <td data-label="ค่าธรรมเนียม" class="num nowrap"><?= e(money($b['rental_fee'])) ?></td>
              <td data-label="การดำเนินการ">
                <div class="d-flex gap-2">
                  <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/approve')) ?>"
                        data-confirm="อนุมัติการจอง <?= e($b['id']) ?> หรือไม่?" data-confirm-text="<?= e($b['title']) ?> · <?= e(thai_date($b['date'])) ?> <?= e(time_range($b['start_time'], $b['end_time'])) ?>" data-confirm-button="อนุมัติ">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success btn-sm"><?= icon('check', 'icon-sm') ?>อนุมัติ</button>
                  </form>
                  <button type="button" class="btn btn-ghost-danger btn-sm" data-reject="<?= e($b['id']) ?>" data-return="/bookings/approval"><?= icon('x', 'icon-sm') ?>ไม่อนุมัติ</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?= View::partial('partials/reject-dialog') ?>
