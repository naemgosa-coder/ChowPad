<?php

use App\Services\RepairService;

/**
 * ปุ่มของนายช่างตามสถานะใบงาน ใช้ทั้งหน้ารายละเอียดและหน้านายช่าง
 *
 * @var array<string, mixed> $repair
 * @var string $returnTo
 */
$id = $repair['id'];
?>
<?php if ($repair['status'] === 'Waiting' && $repair['technician_id'] === null && auth_user()['role'] === 'Technician'): ?>
  <form method="post" action="<?= e(url("/maintenance/{$id}/accept")) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><?= icon('square-check', 'icon-sm') ?>รับใบงาน</button>
  </form>
<?php elseif ($repair['status'] === 'Accepted' && RepairService::isMine($repair)): ?>
  <form method="post" action="<?= e(url("/maintenance/{$id}/start")) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><?= icon('play', 'icon-sm') ?>เริ่มซ่อม</button>
  </form>
<?php elseif ($repair['status'] === 'In Progress' && RepairService::isMine($repair)): ?>
  <button type="button" class="btn btn-success btn-sm" data-complete="<?= e($id) ?>" data-return="<?= e($returnTo) ?>"><?= icon('check', 'icon-sm') ?>ปิดงานซ่อม</button>
<?php elseif ($repair['status'] === 'Completed'): ?>
  <span class="text-sm text-success d-inline-flex align-items-center gap-1"><?= icon('circle-check', 'icon-sm') ?>ซ่อมเสร็จแล้ว</span>
<?php else: ?>
  <span class="text-sm text-muted">รอช่างที่ได้รับมอบหมาย</span>
<?php endif; ?>
