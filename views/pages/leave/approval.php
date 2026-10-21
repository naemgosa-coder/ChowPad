<?php
/**
 * @var array<int, array<string, mixed>> $leaves
 * @var array<string, array<int, float>> $remaining
 */
$days = static fn (float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');
?>
<div class="page-header">
  <div>
    <h1>อนุมัติใบลา</h1>
    <p>ใบลาที่รอพิจารณา <?= e(count($leaves)) ?> รายการ เมื่ออนุมัติแล้วระบบจะลงบันทึกวันลาในตารางเวลาให้อัตโนมัติ</p>
  </div>
</div>

<section class="card">
  <?php if (!$leaves): ?>
    <div class="empty-state"><?= icon('circle-check', 'icon-xl') ?><strong>ไม่มีใบลารออนุมัติ</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead>
          <tr><th scope="col">ผู้ยื่น</th><th scope="col">ประเภท</th><th scope="col">ช่วงวันที่</th><th scope="col" class="num">จำนวน</th><th scope="col">เหตุผล</th><th scope="col">การดำเนินการ</th></tr>
        </thead>
        <tbody>
          <?php foreach ($leaves as $l): ?>
            <?php $left = $remaining[$l['user_id']][$l['leave_type_id']] ?? 0; ?>
            <tr>
              <td data-label="ผู้ยื่น"><?= e($l['user_name']) ?><br><span class="text-caption text-muted"><?= e($l['department'] ?? '-') ?></span></td>
              <td data-label="ประเภท"><?= e($l['type_name']) ?><br><span class="text-caption <?= $left < (float) $l['days'] ? 'text-danger' : 'text-muted' ?>">สิทธิ์คงเหลือ <?= e($days($left)) ?> วัน</span></td>
              <td data-label="ช่วงวันที่" class="nowrap"><?= e(thai_date($l['start_date'])) ?><?= $l['end_date'] !== $l['start_date'] ? ' – ' . e(thai_date($l['end_date'])) : '' ?></td>
              <td data-label="จำนวน" class="num"><?= e($days((float) $l['days'])) ?> วัน</td>
              <td data-label="เหตุผล"><?= e($l['reason']) ?></td>
              <td data-label="การดำเนินการ">
                <?php if ($l['user_id'] === auth_user()['id']): ?>
                  <span class="text-sm text-muted">ใบลาของคุณ ต้องให้ผู้อื่นพิจารณา</span>
                <?php else: ?>
                  <div class="d-flex gap-2">
                    <form method="post" action="<?= e(url('/leave/' . $l['id'] . '/approve')) ?>"
                          data-confirm="อนุมัติใบลาของ <?= e($l['user_name']) ?> หรือไม่?" data-confirm-text="<?= e($l['type_name'] . ' ' . $days((float) $l['days']) . ' วัน') ?>" data-confirm-button="อนุมัติ">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-success btn-sm"><?= icon('check', 'icon-sm') ?>อนุมัติ</button>
                    </form>
                    <form method="post" action="<?= e(url('/leave/' . $l['id'] . '/reject')) ?>"
                          data-confirm="ไม่อนุมัติใบลาของ <?= e($l['user_name']) ?> หรือไม่?" data-confirm-text="ผู้ยื่นจะได้รับการแจ้งเตือนผลการพิจารณา" data-confirm-button="ไม่อนุมัติ" data-confirm-danger="1">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost-danger btn-sm"><?= icon('x', 'icon-sm') ?>ไม่อนุมัติ</button>
                    </form>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
