<?php

use App\Models\Attendance;
use App\Services\AttendanceService;

/**
 * @var array<int, array<string, mixed>> $logs
 * @var array<string, string> $filters
 * @var string[] $months
 * @var array<string, int> $summary
 */
?>
<div class="page-header">
  <div>
    <h1>ประวัติการลงเวลา</h1>
    <p>บันทึกเวลาเข้า-ออกงานย้อนหลังของคุณ</p>
  </div>
  <div class="page-actions no-print">
    <button type="button" class="btn btn-ghost" data-print><?= icon('printer') ?>พิมพ์</button>
  </div>
</div>

<section class="card">
  <form class="filter-bar filter-bar-compact mb-4 no-print" method="get" action="<?= e(url('/attendance/history')) ?>" aria-label="กรองประวัติการลงเวลา">
    <div class="field">
      <label class="form-label" for="h-month">เดือน</label>
      <select class="form-select" id="h-month" name="month" data-autosubmit>
        <option value="">ทุกเดือน</option>
        <?php foreach ($months as $m): ?>
          <option value="<?= e($m) ?>"<?= $filters['month'] === $m ? ' selected' : '' ?>><?= e(thai_date($m . '-01', 'month')) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="form-label" for="h-status">สถานะ</label>
      <select class="form-select" id="h-status" name="status" data-autosubmit>
        <option value="">ทุกสถานะ</option>
        <?php foreach (array_diff(Attendance::STATUSES, ['absent']) as $s): ?>
          <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(status_meta('attendance', $s)['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>กรอง</button>
    </div>
  </form>

  <ul class="chip-list list-unstyled mb-3" aria-label="สรุปตามตัวกรอง">
    <li class="chip">ทั้งหมด <?= e(count($logs)) ?> วัน</li>
    <?php foreach ($summary as $status => $n): ?>
      <li class="chip"><?= e(status_meta('attendance', $status)['label']) ?> <?= e($n) ?> วัน</li>
    <?php endforeach; ?>
  </ul>

  <?php if (!$logs): ?>
    <div class="empty-state"><?= icon('search-x', 'icon-xl') ?><strong>ไม่พบบันทึกตามตัวกรอง</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead><tr><th scope="col">วันที่</th><th scope="col">เข้างาน</th><th scope="col">ออกงาน</th><th scope="col">รวมเวลาทำงาน</th><th scope="col">สถานะ</th><th scope="col">หมายเหตุ</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td data-label="วันที่" class="nowrap"><?= e(thai_date($log['date'], 'long')) ?></td>
              <td data-label="เข้างาน" class="tabular"><?= e($log['clock_in'] ? substr($log['clock_in'], 0, 5) : '-') ?></td>
              <td data-label="ออกงาน" class="tabular"><?= e($log['clock_out'] ? substr($log['clock_out'], 0, 5) : '-') ?></td>
              <td data-label="รวม" class="tabular"><?= e(AttendanceService::workedHours($log['clock_in'], $log['clock_out'])) ?></td>
              <td data-label="สถานะ"><?= status_badge('attendance', $log['status']) ?></td>
              <td data-label="หมายเหตุ"><?= e($log['remarks'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
