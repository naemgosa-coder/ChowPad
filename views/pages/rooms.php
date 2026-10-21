<?php
/**
 * @var array<string, string> $filters
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, array<string, mixed>> $live
 * @var string[] $buildings
 */
?>
<div class="page-header">
  <div>
    <h1>ห้องประชุมศาลากลางจังหวัด</h1>
    <p>เลือกดูห้องประชุมตามอาคารและความจุ เพื่อจองใช้สถานที่จัดกิจกรรม</p>
  </div>
  <div class="page-actions">
    <span class="live-indicator" data-live-indicator data-state="ok">
      <span class="live-dot" aria-hidden="true"></span><span data-live-text>อัปเดตอัตโนมัติ</span>
    </span>
    <a class="btn btn-primary" href="<?= e(url('/bookings/create')) ?>"><?= icon('circle-plus') ?>จองห้องประชุม</a>
  </div>
</div>

<form class="card filter-bar mb-4" method="get" action="<?= e(url('/rooms')) ?>" role="search" aria-label="ค้นหาห้องประชุม">
  <div class="field">
    <label class="form-label" for="room-search">ค้นหา</label>
    <div class="input-icon">
      <?= icon('search') ?>
      <input class="form-control" type="search" id="room-search" name="search" value="<?= e($filters['search']) ?>" placeholder="ชื่อห้องหรืออาคาร">
    </div>
  </div>
  <div class="field">
    <label class="form-label" for="room-building">อาคาร</label>
    <select class="form-select" id="room-building" name="building" data-autosubmit>
      <option value="">ทุกอาคาร</option>
      <?php foreach ($buildings as $b): ?>
        <option value="<?= e($b) ?>"<?= $filters['building'] === $b ? ' selected' : '' ?>><?= e($b) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label class="form-label" for="room-status">สถานะห้อง</label>
    <select class="form-select" id="room-status" name="status" data-autosubmit>
      <option value="">ทุกสถานะ</option>
      <option value="Available"<?= $filters['status'] === 'Available' ? ' selected' : '' ?>>เปิดให้จอง</option>
      <option value="Locked"<?= $filters['status'] === 'Locked' ? ' selected' : '' ?>>ระงับใช้งานชั่วคราว</option>
    </select>
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>ค้นหา</button>
    <?php if (array_filter($filters)): ?>
      <a class="btn btn-ghost" href="<?= e(url('/rooms')) ?>">ล้างตัวกรอง</a>
    <?php endif; ?>
  </div>
</form>

<p class="text-sm text-muted mb-3" aria-live="polite">พบ <?= e(count($rooms)) ?> ห้อง</p>

<?php if (!$rooms): ?>
  <div class="card empty-state">
    <?= icon('search-x', 'icon-xl') ?>
    <strong>ไม่พบห้องประชุมตามเงื่อนไข</strong>
    <span>ลองเปลี่ยนคำค้นหาหรืออาคาร</span>
    <a class="btn btn-secondary" href="<?= e(url('/rooms')) ?>">ล้างตัวกรอง</a>
  </div>
<?php else: ?>
  <div class="room-grid">
    <?php foreach ($rooms as $room): ?>
      <?php $status = $live[$room['id']]; ?>
      <article class="card room-card<?= $room['locked'] ? ' is-locked' : '' ?>" data-room-status="<?= e($room['id']) ?>" aria-labelledby="room-<?= e($room['id']) ?>">
        <?php if ($room['image']): ?>
          <img class="room-image" src="<?= e($room['image']) ?>" alt="<?= e($room['name'] . ' ความจุ ' . $room['capacity'] . ' ที่นั่ง') ?>" width="600" height="338" loading="lazy"
               onerror="this.replaceWith(document.getElementById('room-image-fallback').content.cloneNode(true))">
        <?php else: ?>
          <div class="room-image room-image-placeholder"><?= icon('presentation', 'icon-xl') ?></div>
        <?php endif; ?>
        <div class="room-body">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <h2 class="h3" id="room-<?= e($room['id']) ?>"><?= e($room['name']) ?></h2>
            <span class="badge <?= e($status['badge_class']) ?>" data-room-badge><?= icon($status['icon']) ?><?= e($status['label']) ?></span>
          </div>
          <ul class="room-meta list-unstyled">
            <li><?= icon('map-pin', 'icon-sm') ?><?= e($room['building']) ?> ชั้น <?= e($room['floor']) ?></li>
            <li><?= icon('users', 'icon-sm') ?>ความจุ <?= e($room['capacity']) ?> ที่นั่ง</li>
            <li><?= icon('tag', 'icon-sm') ?>ราชการ <?= e(money($room['price_gov'])) ?>/ชม. · ทั่วไป <?= e(money($room['price_local'])) ?>/ชม.</li>
          </ul>
          <ul class="chip-list list-unstyled" aria-label="อุปกรณ์ในห้อง">
            <?php foreach ($room['equipment'] as $item): ?>
              <li class="chip"><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($room['warnings']): ?>
            <div class="alert alert-warning"><?= icon('triangle-alert') ?><span><?= e($room['warnings']) ?></span></div>
          <?php endif; ?>
          <p class="room-live text-caption" data-room-line><?= e($status['line']) ?></p>
          <div class="room-actions">
            <a class="btn btn-ghost" href="<?= e(url('/calendar?room=' . urlencode($room['id']))) ?>"><?= icon('calendar') ?>ดูตาราง</a>
            <?php if ($room['locked']): ?>
              <button type="button" class="btn" disabled>ระงับใช้งานชั่วคราว</button>
            <?php else: ?>
              <a class="btn btn-primary" href="<?= e(url('/bookings/create?room=' . urlencode($room['id']))) ?>">จองห้องนี้<?= icon('arrow-right') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<template id="room-image-fallback"><div class="room-image room-image-placeholder"><?= icon('presentation', 'icon-xl') ?></div></template>
<script type="application/json" id="live-config"><?= json_encode(['pollSeconds' => config('app.poll_seconds')]) ?></script>
