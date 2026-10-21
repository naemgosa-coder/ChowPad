<?php

use App\Models\Repair;
use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $repairs
 * @var array<string, string> $filters
 * @var int $total @var int $page @var int $pages @var int $perPage
 * @var bool $allRepairs
 */
?>
<div class="page-header">
  <div>
    <h1>ใบงานซ่อมบำรุง</h1>
    <p><?= $allRepairs ? 'ใบงานซ่อมบำรุงทั้งหมด งานค้างแสดงก่อน' : 'รายการแจ้งซ่อมที่คุณส่ง' ?></p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('/maintenance/create')) ?>"><?= icon('circle-plus') ?>แจ้งซ่อม</a>
  </div>
</div>

<section class="card">
  <form class="filter-bar mb-4" method="get" action="<?= e(url('/maintenance')) ?>" role="search" aria-label="ค้นหาใบงานซ่อม">
    <div class="field">
      <label class="form-label" for="mt-search">ค้นหา</label>
      <div class="input-icon">
        <?= icon('search') ?>
        <input class="form-control" type="search" id="mt-search" name="search" value="<?= e($filters['search']) ?>" placeholder="เลขใบงาน รายละเอียด ห้อง หรือชื่อช่าง">
      </div>
    </div>
    <div class="field">
      <label class="form-label" for="mt-priority">ความเร่งด่วน</label>
      <select class="form-select" id="mt-priority" name="priority" data-autosubmit>
        <option value="">ทุกระดับ</option>
        <?php foreach (Repair::PRIORITIES as $p): ?>
          <option value="<?= e($p) ?>"<?= $filters['priority'] === $p ? ' selected' : '' ?>><?= e(status_meta('priority', $p)['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="form-label" for="mt-status">สถานะ</label>
      <select class="form-select" id="mt-status" name="status" data-autosubmit>
        <option value="">ทุกสถานะ</option>
        <?php foreach (Repair::STATUSES as $s): ?>
          <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(status_meta('repair', $s)['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>ค้นหา</button>
      <?php if (array_filter($filters)): ?>
        <a class="btn btn-ghost" href="<?= e(url('/maintenance')) ?>">ล้างตัวกรอง</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (!$repairs): ?>
    <div class="empty-state">
      <?= icon('wrench', 'icon-xl') ?>
      <strong><?= array_filter($filters) ? 'ไม่พบใบงานตามเงื่อนไข' : 'ยังไม่มีรายการแจ้งซ่อม' ?></strong>
      <a class="btn btn-primary" href="<?= e(url('/maintenance/create')) ?>"><?= icon('wrench') ?>แจ้งซ่อม</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead>
          <tr>
            <th scope="col">เลขใบงาน</th>
            <th scope="col">วันที่แจ้ง</th>
            <th scope="col">ห้องประชุม</th>
            <th scope="col">หมวดหมู่</th>
            <th scope="col">ผู้แจ้ง</th>
            <th scope="col">ช่างผู้รับผิดชอบ</th>
            <th scope="col">ความเร่งด่วน</th>
            <th scope="col">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($repairs as $r): ?>
            <tr>
              <td data-label="เลขใบงาน" class="nowrap"><a class="fw-semibold" href="<?= e(url('/maintenance/' . $r['id'])) ?>"><?= e($r['id']) ?></a></td>
              <td data-label="วันที่แจ้ง" class="nowrap"><?= e(thai_date($r['date'])) ?></td>
              <td data-label="ห้องประชุม"><?= e($r['room_name']) ?></td>
              <td data-label="หมวดหมู่"><?= e(Repair::categoryLabel($r['category'])) ?></td>
              <td data-label="ผู้แจ้ง"><?= e($r['reporter_name'] ?? '-') ?></td>
              <td data-label="ช่าง"><?= $r['technician_name'] ? e($r['technician_name']) : '<span class="text-muted">ยังไม่มอบหมาย</span>' ?></td>
              <td data-label="ความเร่งด่วน"><?= status_badge('priority', $r['priority']) ?></td>
              <td data-label="สถานะ"><?= status_badge('repair', $r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::partial('partials/pagination', compact('page', 'pages', 'total', 'perPage')) ?>
  <?php endif; ?>
</section>
