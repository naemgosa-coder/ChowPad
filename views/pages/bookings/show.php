<?php
/** @var array<string, mixed> $booking @var bool $canCancel @var bool $canReview */
$b = $booking;
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/bookings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>กลับไปประวัติการจอง</a></p>
    <h1>การจองเลขที่ <?= e($b['id']) ?></h1>
  </div>
  <div class="page-actions">
    <?= status_badge('booking', $b['status']) ?>
  </div>
</div>

<div class="row g-4">
  <section class="col-lg-8 card" aria-labelledby="detail-title">
    <h2 class="card-title mb-3" id="detail-title"><?= icon('clipboard-list') ?><?= e($b['title']) ?></h2>
    <dl class="detail-list">
      <div><dt>ห้องประชุม</dt><dd><?= e($b['room_name']) ?></dd></div>
      <div><dt>สถานที่</dt><dd><?= e($b['building']) ?> ชั้น <?= e($b['floor']) ?></dd></div>
      <div><dt>วันที่</dt><dd><?= e(thai_date($b['date'], 'long')) ?></dd></div>
      <div><dt>เวลา</dt><dd class="tabular"><?= e(time_range($b['start_time'], $b['end_time'])) ?> (<?= e((float) $b['duration_hours']) ?> ชม.)</dd></div>
      <div><dt>ผู้เข้าร่วม</dt><dd><?= e($b['participants']) ?> คน</dd></div>
      <div><dt>ประเภทหน่วยงาน</dt><dd><?= e(org_type_label($b['org_type'])) ?></dd></div>
      <div><dt>หน่วยงาน</dt><dd><?= e($b['department']) ?></dd></div>
      <div><dt>ผู้ประสานงาน</dt><dd><?= e($b['organizer']) ?></dd></div>
      <div><dt>ติดต่อ</dt><dd><?= e($b['phone']) ?> · <?= e($b['email']) ?></dd></div>
      <div><dt>อุปกรณ์ที่ขอ</dt><dd><?= e($b['equipment'] ? implode(', ', $b['equipment']) : '-') ?><?= $b['coffee_break'] ? ' · จัดเบรกอาหารว่าง' : '' ?></dd></div>
      <div><dt>ค่าธรรมเนียม</dt><dd class="fw-semibold"><?= e(money($b['rental_fee'])) ?></dd></div>
      <div><dt>หมายเหตุ</dt><dd><?= e($b['remarks'] ?: '-') ?></dd></div>
      <?php if ($b['review_note']): ?>
        <div><dt>เหตุผลที่ไม่อนุมัติ</dt><dd class="text-danger"><?= e($b['review_note']) ?></dd></div>
      <?php endif; ?>
    </dl>
  </section>

  <aside class="col-lg-4 d-flex flex-column gap-4">
    <?php if ($canReview): ?>
      <section class="card" aria-labelledby="review-title">
        <h2 class="card-title mb-3" id="review-title"><?= icon('square-check') ?>พิจารณาคำขอ</h2>
        <div class="d-grid gap-2">
          <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/approve')) ?>"
                data-confirm="อนุมัติการจองนี้หรือไม่?" data-confirm-text="การจอง <?= e($b['id']) ?> จะได้รับการยืนยันใช้ห้อง" data-confirm-button="อนุมัติ">
            <?= csrf_field() ?>
            <input type="hidden" name="return_to" value="/bookings/<?= e($b['id']) ?>">
            <button type="submit" class="btn btn-success btn-block"><?= icon('check') ?>อนุมัติ</button>
          </form>
          <button type="button" class="btn btn-ghost-danger btn-block" data-reject="<?= e($b['id']) ?>" data-return="/bookings/<?= e($b['id']) ?>"><?= icon('x') ?>ไม่อนุมัติ</button>
        </div>
      </section>
    <?php endif; ?>
    <?php if ($canCancel): ?>
      <section class="card" aria-labelledby="cancel-title">
        <h2 class="card-title mb-2" id="cancel-title"><?= icon('calendar-minus') ?>ยกเลิกการจอง</h2>
        <p class="text-sm text-muted mb-3">เมื่อยกเลิกแล้ว ช่วงเวลานี้จะว่างให้หน่วยงานอื่นจองได้</p>
        <form method="post" action="<?= e(url('/bookings/' . $b['id'] . '/cancel')) ?>"
              data-confirm="ยกเลิกการจองนี้หรือไม่?" data-confirm-text="การจอง <?= e($b['id']) ?> จะถูกยกเลิกและไม่สามารถกู้คืนได้"
              data-confirm-button="ยกเลิกการจอง" data-confirm-danger="1">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger btn-block"><?= icon('trash-2') ?>ยกเลิกการจอง</button>
        </form>
      </section>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= e(url('/calendar?room=' . urlencode($b['room_id']))) ?>"><?= icon('calendar') ?>ดูตารางห้องนี้</a>
  </aside>
</div>

<?php if ($canReview): ?>
  <?= App\Services\View::partial('partials/reject-dialog') ?>
<?php endif; ?>
