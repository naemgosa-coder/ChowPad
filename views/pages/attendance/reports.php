<?php

use App\Services\View;

/**
 * @var string $date
 * @var array<string, int> $summary
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, string> $filters
 * @var array<int, array<string, mixed>> $departments
 * @var int $pendingLeaves
 * @var int $total @var int $page @var int $pages @var int $perPage
 */
?>
<div class="page-header">
  <div>
    <h1>รายงานการลงเวลา</h1>
    <p>สถานะการมาทำงานของบุคลากรทั้งหมด ประจำ<?= e(thai_date($date, 'long')) ?></p>
  </div>
  <div class="page-actions no-print">
    <?php if ($pendingLeaves): ?>
      <a class="btn btn-secondary" href="<?= e(url('/leave/approval')) ?>"><?= icon('check-check') ?>ใบลารออนุมัติ <?= e($pendingLeaves) ?> รายการ</a>
    <?php endif; ?>
    <button type="button" class="btn btn-ghost" data-print><?= icon('printer') ?>พิมพ์</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['มาทำงาน', $summary['present'], 'users', 'metric-icon-success', 'normal'],
      ['เข้าสาย', $summary['late'], 'clock', 'metric-icon-warning', 'late'],
      ['ลาหยุด', $summary['leave'], 'calendar-minus', '', 'leave'],
      ['ยังไม่ลงเวลา', $summary['missing'], 'user-x', 'metric-icon-danger', 'missing'],
  ] as [$label, $value, $iconName, $tone, $state]): ?>
    <div class="col-6 col-lg-3">
      <a class="card metric-card metric-link" href="<?= e(query_with(['state' => $state, 'page' => null])) ?>">
        <div class="metric-head"><?= e($label) ?> <span class="metric-icon <?= e($tone) ?>"><?= icon($iconName) ?></span></div>
        <div class="metric-value"><?= e($value) ?> <span class="text-sm text-muted">/ <?= e($summary['total']) ?></span></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<section class="card">
  <form class="filter-bar filter-bar-compact mb-4 no-print" method="get" action="<?= e(url('/attendance/reports')) ?>" aria-label="กรองรายงาน">
    <div class="field">
      <label class="form-label" for="r-date">วันที่</label>
      <input class="form-control" type="date" id="r-date" name="date" value="<?= e($date) ?>" max="<?= e(date('Y-m-d')) ?>" data-autosubmit>
    </div>
    <div class="field">
      <label class="form-label" for="r-dept">แผนก</label>
      <select class="form-select" id="r-dept" name="department" data-autosubmit>
        <option value="">ทุกแผนก</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= e($d['id']) ?>"<?= $filters['department'] === $d['id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="form-label" for="r-state">สถานะ</label>
      <select class="form-select" id="r-state" name="state" data-autosubmit>
        <option value="">ทุกสถานะ</option>
        <?php foreach (['normal', 'late', 'ot', 'leave', 'missing'] as $s): ?>
          <option value="<?= e($s) ?>"<?= $filters['state'] === $s ? ' selected' : '' ?>><?= e(status_meta('attendance', $s)['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>กรอง</button>
    </div>
  </form>

  <?php if (!$rows): ?>
    <div class="empty-state"><?= icon('search-x', 'icon-xl') ?><strong>ไม่พบบุคลากรตามตัวกรอง</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead><tr><th scope="col">รหัส</th><th scope="col">ชื่อ</th><th scope="col">แผนก</th><th scope="col">เข้างาน</th><th scope="col">ออกงาน</th><th scope="col">สถานะ</th><th scope="col">หมายเหตุ</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="รหัส" class="nowrap"><?= e($row['id']) ?></td>
              <td data-label="ชื่อ"><?= e($row['name']) ?></td>
              <td data-label="แผนก"><?= e($row['department'] ?? '-') ?></td>
              <td data-label="เข้างาน" class="tabular"><?= e($row['clock_in'] ? substr($row['clock_in'], 0, 5) : '-') ?></td>
              <td data-label="ออกงาน" class="tabular"><?= e($row['clock_out'] ? substr($row['clock_out'], 0, 5) : '-') ?></td>
              <td data-label="สถานะ"><?= status_badge('attendance', $row['status'] ?? 'missing') ?></td>
              <td data-label="หมายเหตุ"><?= e($row['remarks'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::partial('partials/pagination', compact('page', 'pages', 'total', 'perPage')) ?>
  <?php endif; ?>
</section>
