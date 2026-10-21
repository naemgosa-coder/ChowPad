<?php
/**
 * @var array<int, array<string, mixed>> $quotas
 * @var array{from: string, to: string, label: string} $fiscalYear
 * @var array<int, array<string, mixed>> $leaves
 * @var array<string, mixed> $old
 * @var array<string, string> $errors
 */
$v = static fn (string $key): string => (string) ($old[$key] ?? '');
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
$error = static fn (string $key): string => isset($errors[$key])
    ? '<p class="form-error" id="err-' . e($key) . '">' . icon('circle-alert') . e($errors[$key]) . '</p>' : '';
$days = static fn (float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');
?>
<div class="page-header">
  <div>
    <h1>ใบลา</h1>
    <p>สิทธิ์วันลา<?= e($fiscalYear['label']) ?> (<?= e(thai_date($fiscalYear['from'])) ?> – <?= e(thai_date($fiscalYear['to'])) ?>) นับเฉพาะวันทำการ</p>
  </div>
</div>

<div class="quota-grid mb-4">
  <?php foreach ($quotas as $q): ?>
    <section class="card quota-card" aria-label="<?= e($q['name']) ?>">
      <div>
        <h2 class="text-sm text-muted fw-semibold"><?= e($q['name']) ?></h2>
        <p class="h2 tabular">เหลือ <?= e($days($q['remaining'])) ?> <span class="text-sm text-muted">/ <?= e($q['quota_days']) ?> วัน</span></p>
        <p class="text-caption text-muted">ใช้ไป <?= e($days($q['used'])) ?> วัน<?= $q['pending'] > 0 ? ' · รออนุมัติ ' . e($days($q['pending'])) . ' วัน' : '' ?></p>
      </div>
      <div class="quota-ring" style="--percent: <?= e($q['percent']) ?>" role="img" aria-label="ใช้ไปแล้ว <?= e($q['percent']) ?> เปอร์เซ็นต์">
        <span><?= e($q['percent']) ?>%</span>
      </div>
    </section>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <form class="card" method="post" action="<?= e(url('/leave')) ?>" novalidate data-busy-text="กำลังส่งใบลา…" aria-labelledby="leave-form-title">
      <?= csrf_field() ?>
      <h2 class="card-title mb-3" id="leave-form-title"><?= icon('send') ?>ยื่นใบลา</h2>
      <?php if ($errors): ?>
        <div class="alert alert-danger mb-3" role="alert" tabindex="-1" data-focus-on-load>
          <?= icon('circle-alert') ?><span>ยังส่งใบลาไม่ได้ กรุณาแก้ไข <?= e(count($errors)) ?> รายการด้านล่าง</span>
        </div>
      <?php endif; ?>
      <div class="field">
        <label class="form-label" for="f-leave_type_id">ประเภทการลา <span class="required" aria-hidden="true">*</span></label>
        <select class="form-select" id="f-leave_type_id" name="leave_type_id" required<?= $invalid('leave_type_id') ?>>
          <?php foreach ($quotas as $q): ?>
            <option value="<?= e($q['id']) ?>"<?= $v('leave_type_id') === (string) $q['id'] ? ' selected' : '' ?>><?= e($q['name']) ?> (ยื่นได้อีก <?= e($days($q['available'])) ?> วัน)</option>
          <?php endforeach; ?>
        </select>
        <?= $error('leave_type_id') ?>
      </div>
      <div class="row">
        <div class="col-sm-6 field">
          <label class="form-label" for="f-start_date">วันที่เริ่มลา <span class="required" aria-hidden="true">*</span></label>
          <input class="form-control" type="date" id="f-start_date" name="start_date" value="<?= e($v('start_date')) ?>" required<?= $invalid('start_date') ?>>
          <?= $error('start_date') ?>
        </div>
        <div class="col-sm-6 field">
          <label class="form-label" for="f-end_date">ถึงวันที่ <span class="required" aria-hidden="true">*</span></label>
          <input class="form-control" type="date" id="f-end_date" name="end_date" value="<?= e($v('end_date')) ?>" required<?= $invalid('end_date') ?>>
          <?= $error('end_date') ?>
        </div>
      </div>
      <p class="form-help mt-2">ระบบนับเฉพาะวันทำการ ไม่นับเสาร์-อาทิตย์และวันหยุดราชการ</p>
      <div class="field">
        <label class="form-label" for="f-reason">เหตุผลการลา <span class="required" aria-hidden="true">*</span></label>
        <textarea class="form-control" id="f-reason" name="reason" rows="3" maxlength="1000" required<?= $invalid('reason') ?>><?= e($v('reason')) ?></textarea>
        <?= $error('reason') ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block mt-4"><?= icon('send') ?>ส่งใบลา</button>
    </form>
  </div>

  <section class="col-lg-7 card" aria-labelledby="my-leaves-title">
    <h2 class="card-title mb-3" id="my-leaves-title"><?= icon('history') ?>ประวัติใบลาของฉัน</h2>
    <?php if (!$leaves): ?>
      <div class="empty-state"><?= icon('calendar-minus', 'icon-xl') ?><strong>คุณยังไม่มีใบลา</strong></div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-cards">
          <thead><tr><th scope="col">ประเภท</th><th scope="col">วันที่ลา</th><th scope="col" class="num">จำนวน</th><th scope="col">สถานะ</th><th scope="col"><span class="visually-hidden">การดำเนินการ</span></th></tr></thead>
          <tbody>
            <?php foreach ($leaves as $l): ?>
              <tr>
                <td data-label="ประเภท"><?= e($l['type_name']) ?><br><span class="text-caption text-muted"><?= e($l['reason']) ?></span></td>
                <td data-label="วันที่ลา" class="nowrap"><?= e(thai_date($l['start_date'])) ?><?= $l['end_date'] !== $l['start_date'] ? ' – ' . e(thai_date($l['end_date'])) : '' ?></td>
                <td data-label="จำนวน" class="num"><?= e($days((float) $l['days'])) ?> วัน</td>
                <td data-label="สถานะ"><?= status_badge('leave', $l['status']) ?><?= $l['approver_name'] ? '<div class="text-caption text-muted mt-1">โดย ' . e($l['approver_name']) . '</div>' : '' ?></td>
                <td data-label="">
                  <?php if ($l['status'] === 'pending'): ?>
                    <form method="post" action="<?= e(url('/leave/' . $l['id'] . '/cancel')) ?>"
                          data-confirm="ยกเลิกใบลานี้หรือไม่?" data-confirm-text="<?= e($l['type_name'] . ' ' . thai_date($l['start_date'])) ?>"
                          data-confirm-button="ยกเลิกใบลา" data-confirm-danger="1">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost-danger btn-sm"><?= icon('trash-2', 'icon-sm') ?>ยกเลิก</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
