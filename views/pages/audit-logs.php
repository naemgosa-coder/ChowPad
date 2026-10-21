<?php

use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $logs
 * @var string[] $actions
 * @var array<string, string> $filters
 * @var int $total @var int $page @var int $pages @var int $perPage
 */
?>
<div class="page-header">
  <div>
    <h1>บันทึกการใช้งาน</h1>
    <p>ประวัติการกระทำในระบบทั้งหมด (Audit Log) เรียงจากล่าสุด</p>
  </div>
</div>

<section class="card">
  <form class="filter-bar filter-bar-compact mb-4" method="get" action="<?= e(url('/audit-logs')) ?>" role="search" aria-label="ค้นหาบันทึกการใช้งาน">
    <div class="field">
      <label class="form-label" for="al-search">ค้นหา</label>
      <input class="form-control" type="search" id="al-search" name="search" value="<?= e($filters['search']) ?>" placeholder="ผู้ใช้ รายละเอียด หรือ IP">
    </div>
    <div class="field">
      <label class="form-label" for="al-action">การกระทำ</label>
      <select class="form-select" id="al-action" name="action" data-autosubmit>
        <option value="">ทั้งหมด</option>
        <?php foreach ($actions as $a): ?>
          <option value="<?= e($a) ?>"<?= $filters['action'] === $a ? ' selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div></div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>ค้นหา</button>
    </div>
  </form>

  <?php if (!$logs): ?>
    <div class="empty-state"><?= icon('search-x', 'icon-xl') ?><strong>ไม่พบบันทึกตามเงื่อนไข</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead><tr><th scope="col">เวลา</th><th scope="col">ผู้ใช้</th><th scope="col">การกระทำ</th><th scope="col">รายละเอียด</th><th scope="col">IP</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td data-label="เวลา" class="nowrap tabular"><?= e(thai_date(substr($l['created_at'], 0, 10))) ?> <?= e(substr($l['created_at'], 11, 8)) ?></td>
              <td data-label="ผู้ใช้"><?= e($l['username']) ?></td>
              <td data-label="การกระทำ"><span class="badge <?= str_contains($l['action'], 'Fail') || str_starts_with($l['action'], 'Delete') ? 'badge-danger' : 'badge-neutral' ?>"><?= e($l['action']) ?></span></td>
              <td data-label="รายละเอียด"><?= e($l['details']) ?></td>
              <td data-label="IP" class="tabular"><?= e($l['ip_address'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::partial('partials/pagination', compact('page', 'pages', 'total', 'perPage')) ?>
  <?php endif; ?>
</section>
