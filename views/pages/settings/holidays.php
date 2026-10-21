<?php
/**
 * @var array<int, array<string, mixed>> $holidays
 * @var array{start: string, end: string} $hours
 * @var array<string, string> $errors
 * @var array<string, string> $old
 */
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>เวลาทำการและวันหยุด</h1>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4 d-flex flex-column gap-4">
    <form class="card" method="post" action="<?= e(url('/settings/working-hours')) ?>" novalidate aria-labelledby="hours-title">
      <?= csrf_field() ?>
      <h2 class="card-title mb-3" id="hours-title"><?= icon('clock') ?>เวลาทำการ</h2>
      <div class="row">
        <div class="col-6 field">
          <?= field_label('f-start', 'เริ่มงาน') ?>
          <input class="form-control" type="time" id="f-start" name="start" value="<?= e($old['start'] ?? $hours['start']) ?>" required<?= field_invalid($errors, 'hours') ?>>
        </div>
        <div class="col-6 field">
          <?= field_label('f-end', 'เลิกงาน') ?>
          <input class="form-control" type="time" id="f-end" name="end" value="<?= e($old['end'] ?? $hours['end']) ?>" required>
        </div>
      </div>
      <?= field_error($errors, 'hours') ?>
      <p class="form-help mt-2">ลงเวลาเข้างานหลังเวลาเริ่มงานนับเป็นสาย และออกงานหลังเวลาเลิกงานเกิน 1 ชั่วโมงนับเป็นล่วงเวลา</p>
      <button type="submit" class="btn btn-primary btn-block mt-3"><?= icon('save') ?>บันทึกเวลาทำการ</button>
    </form>

    <form class="card" method="post" action="<?= e(url('/settings/holidays')) ?>" novalidate aria-labelledby="add-holiday-title">
      <?= csrf_field() ?>
      <h2 class="card-title mb-3" id="add-holiday-title"><?= icon('circle-plus') ?>เพิ่มวันหยุด</h2>
      <div class="field">
        <?= field_label('f-date', 'วันที่') ?>
        <input class="form-control" type="date" id="f-date" name="date" value="<?= e($old['date'] ?? '') ?>" required<?= field_invalid($errors, 'date') ?>>
        <?= field_error($errors, 'date') ?>
      </div>
      <div class="field">
        <?= field_label('f-name', 'ชื่อวันหยุด') ?>
        <input class="form-control" id="f-name" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="เช่น วันปิยมหาราช" required<?= field_invalid($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
      </div>
      <div class="field">
        <?= field_label('f-type', 'ประเภท', false) ?>
        <select class="form-select" id="f-type" name="type">
          <?php foreach (['วันหยุดราชการ', 'วันหยุดชดเชย', 'วันหยุดพิเศษ'] as $t): ?>
            <option<?= ($old['type'] ?? '') === $t ? ' selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-secondary btn-block mt-3"><?= icon('circle-plus') ?>เพิ่มวันหยุด</button>
    </form>
  </div>

  <section class="col-lg-8 card" aria-labelledby="holiday-list-title">
    <h2 class="card-title mb-3" id="holiday-list-title"><?= icon('calendar-days') ?>วันหยุดตั้งแต่ปี <?= e((int) date('Y') + 543) ?></h2>
    <?php if (!$holidays): ?>
      <div class="empty-state"><?= icon('calendar', 'icon-xl') ?><strong>ยังไม่มีวันหยุด</strong></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-cards">
          <thead><tr><th scope="col">วันที่</th><th scope="col">ชื่อวันหยุด</th><th scope="col">ประเภท</th><th scope="col"><span class="visually-hidden">การจัดการ</span></th></tr></thead>
          <tbody>
            <?php foreach ($holidays as $h): ?>
              <tr<?= $h['date'] < date('Y-m-d') ? ' class="text-muted"' : '' ?>>
                <td data-label="วันที่" class="nowrap"><?= e(thai_date($h['date'], 'long')) ?></td>
                <td data-label="ชื่อ"><?= e($h['name']) ?></td>
                <td data-label="ประเภท"><span class="badge badge-danger"><?= icon('calendar') ?><?= e($h['type']) ?></span></td>
                <td data-label="">
                  <form method="post" action="<?= e(url('/settings/holidays/' . $h['id'] . '/delete')) ?>"
                        data-confirm="ลบวันหยุดนี้หรือไม่?" data-confirm-text="<?= e($h['name'] . ' · ' . thai_date($h['date'])) ?>" data-confirm-button="ลบวันหยุด" data-confirm-danger="1">
                    <?= csrf_field() ?>
                    <button type="submit" class="icon-btn" aria-label="ลบวันหยุด <?= e($h['name']) ?>"><?= icon('trash-2', 'icon-sm') ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
