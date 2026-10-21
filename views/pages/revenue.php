<?php
/**
 * @var array<string, float> $totals
 * @var array<int, array<string, mixed>> $byRoom
 * @var array<int, array<string, mixed>> $recent
 * @var array<string, mixed> $chart
 */
?>
<div class="page-header">
  <div>
    <h1>รายงานรายได้ค่าธรรมเนียม</h1>
    <p>รายได้จากการใช้ห้องประชุม (นับเฉพาะการจองที่อนุมัติแล้วหรือใช้งานแล้ว)</p>
  </div>
  <div class="page-actions no-print">
    <a class="btn btn-secondary" href="<?= e(url('/revenue/export')) ?>"><?= icon('file-spreadsheet') ?>ส่งออก Excel (CSV)</a>
    <button type="button" class="btn btn-ghost" data-print><?= icon('printer') ?>พิมพ์</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['รายได้สะสมทั้งหมด', $totals['total'], 'wallet', 'metric-icon-success'],
      ['จากหน่วยงานราชการ', $totals['gov'], 'building-2', ''],
      ['จากท้องถิ่น / เอกชน / บุคคลทั่วไป', $totals['other'], 'users', 'metric-icon-warning'],
  ] as [$label, $value, $iconName, $tone]): ?>
    <div class="col-md-4">
      <div class="card metric-card">
        <div class="metric-head"><?= e($label) ?> <span class="metric-icon <?= e($tone) ?>"><?= icon($iconName) ?></span></div>
        <div class="metric-value"><?= e(money($value)) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <section class="col-lg-8 card" aria-labelledby="rev-chart-title">
    <h2 class="card-title mb-3" id="rev-chart-title"><?= icon('chart-line') ?>รายได้รายเดือน 12 เดือนล่าสุด</h2>
    <div class="chart-box"><canvas id="chart-revenue" role="img" aria-label="กราฟเส้นรายได้รายเดือน 12 เดือนล่าสุด"></canvas></div>
  </section>
  <section class="col-lg-4 card" aria-labelledby="rev-room-title">
    <h2 class="card-title mb-3" id="rev-room-title"><?= icon('door-open') ?>รายได้แยกตามห้อง</h2>
    <ul class="rate-list list-unstyled">
      <?php foreach ($byRoom as $r): ?>
        <li><span><?= e($r['name']) ?><br><span class="text-muted"><?= e(number_format((int) $r['n'])) ?> ครั้ง</span></span><strong class="tabular nowrap"><?= e(money($r['total'])) ?></strong></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<section class="card" aria-labelledby="rev-table-title">
  <h2 class="card-title mb-3" id="rev-table-title"><?= icon('table') ?>รายการรายรับล่าสุด 15 รายการ</h2>
  <div class="table-responsive">
    <table class="table table-cards">
      <thead><tr><th scope="col">เลขที่</th><th scope="col">หัวข้อ</th><th scope="col">ห้องประชุม</th><th scope="col">ประเภทหน่วยงาน</th><th scope="col">หน่วยงาน</th><th scope="col">วันที่ใช้</th><th scope="col" class="num">รายรับ</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $b): ?>
          <tr>
            <td data-label="เลขที่" class="nowrap"><a href="<?= e(url('/bookings/' . $b['id'])) ?>"><?= e($b['id']) ?></a></td>
            <td data-label="หัวข้อ"><?= e($b['title']) ?></td>
            <td data-label="ห้อง"><?= e($b['room_name']) ?></td>
            <td data-label="ประเภท"><span class="badge <?= $b['org_type'] === 'Government Agency' ? 'badge-info' : 'badge-neutral' ?>"><?= e($b['org_type'] === 'Government Agency' ? 'ราชการ' : 'ท้องถิ่น/ทั่วไป') ?></span></td>
            <td data-label="หน่วยงาน"><?= e($b['department']) ?></td>
            <td data-label="วันที่" class="nowrap"><?= e(thai_date($b['date'])) ?></td>
            <td data-label="รายรับ" class="num nowrap"><?= e(money($b['rental_fee'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<script type="application/json" id="report-data"><?= json_encode($chart + ['type' => 'revenue'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
