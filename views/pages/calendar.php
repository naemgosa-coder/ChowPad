<?php
/** @var array<int, array<string, mixed>> $rooms @var string $selectedRoom */
?>
<div class="page-header">
  <div>
    <h1>ปฏิทินการจองห้องประชุม</h1>
    <p>ตรวจสอบช่วงเวลาที่ห้องว่างและการจองของแต่ละห้อง</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('/bookings/create')) ?>"><?= icon('calendar-plus') ?>จองห้องประชุม</a>
  </div>
</div>

<div class="row g-4">
  <aside class="col-lg-3 d-flex flex-column gap-4">
    <fieldset class="card">
      <legend class="card-title mb-2"><?= icon('filter') ?>ห้องที่แสดง</legend>
      <div id="calendar-rooms">
        <?php foreach ($rooms as $room): ?>
          <label class="form-check">
            <input type="checkbox" value="<?= e($room['id']) ?>"<?= $selectedRoom === '' || $selectedRoom === $room['id'] ? ' checked' : '' ?>>
            <?= e($room['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <section class="card" aria-labelledby="legend-title">
      <h2 class="card-title mb-3" id="legend-title"><?= icon('info') ?>สัญลักษณ์</h2>
      <ul class="list-unstyled d-flex flex-column gap-2">
        <li><?= status_badge('booking', 'Approved') ?></li>
        <li><?= status_badge('booking', 'Waiting Approval') ?></li>
        <li><?= status_badge('booking', 'Completed') ?></li>
        <li><span class="badge badge-danger"><?= icon('calendar') ?>วันหยุดราชการ</span></li>
      </ul>
    </section>
  </aside>
  <section class="col-lg-9 card" aria-label="ปฏิทิน">
    <div id="calendar" class="calendar"></div>
  </section>
</div>

<dialog class="dialog" id="event-dialog" aria-labelledby="event-dialog-title">
  <div class="dialog-header">
    <h2 id="event-dialog-title"></h2>
    <button type="button" class="icon-btn" data-close aria-label="ปิด"><?= icon('x') ?></button>
  </div>
  <div class="dialog-body">
    <dl class="detail-list" id="event-dialog-body"></dl>
  </div>
  <div class="dialog-footer">
    <button type="button" class="btn btn-ghost" data-close>ปิด</button>
    <a class="btn btn-primary" id="event-dialog-link" href="#" hidden>ดูรายละเอียด</a>
  </div>
</dialog>
