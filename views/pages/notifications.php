<?php
/** @var array<int, array<string, mixed>> $notifications */
$unread = count(array_filter($notifications, static fn ($n) => !$n['is_seen']));
$tones = ['danger' => 'danger', 'warning' => 'warning', 'success' => 'success'];
?>
<div class="page-header">
  <div>
    <h1>การแจ้งเตือน</h1>
    <p>ยังไม่อ่าน <?= e($unread) ?> รายการ จากทั้งหมด <?= e(count($notifications)) ?> รายการ</p>
  </div>
  <?php if ($unread): ?>
    <form method="post" action="<?= e(url('/notifications/read-all')) ?>" class="page-actions">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary"><?= icon('check-check') ?>อ่านทั้งหมดแล้ว</button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$notifications): ?>
  <div class="card empty-state"><?= icon('bell-off', 'icon-xl') ?><strong>ไม่มีการแจ้งเตือน</strong></div>
<?php else: ?>
  <ul class="notification-list list-unstyled">
    <?php foreach ($notifications as $n): ?>
      <li class="card notification<?= $n['is_seen'] ? ' is-read' : '' ?> notification-<?= e($tones[$n['color']] ?? 'info') ?>">
        <span class="metric-icon metric-icon-<?= e($tones[$n['color']] ?? 'info') ?>"><?= icon($n['icon'] ?: 'info') ?></span>
        <div class="notification-body">
          <h2 class="text-sm fw-semibold">
            <?php if (!$n['is_seen']): ?><span class="visually-hidden">ยังไม่อ่าน:</span><?php endif; ?>
            <?= e($n['title']) ?>
          </h2>
          <p class="text-sm"><?= e($n['description']) ?></p>
          <p class="text-caption text-muted"><?= e(thai_date(substr($n['created_at'], 0, 10))) ?> · <?= e(substr($n['created_at'], 11, 5)) ?> น.</p>
        </div>
        <div class="notification-actions">
          <?php if (!$n['is_seen']): ?>
            <form method="post" action="<?= e(url('/notifications/' . $n['id'] . '/read')) ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-ghost btn-sm"><?= icon('check', 'icon-sm') ?>อ่านแล้ว</button>
            </form>
          <?php else: ?>
            <span class="text-caption text-muted d-inline-flex align-items-center gap-1"><?= icon('check', 'icon-xs') ?>อ่านแล้ว</span>
          <?php endif; ?>
          <form method="post" action="<?= e(url('/notifications/' . $n['id'] . '/delete')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="icon-btn" aria-label="ลบการแจ้งเตือน <?= e($n['title']) ?>"><?= icon('trash-2', 'icon-sm') ?></button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
