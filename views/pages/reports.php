<?php
/**
 * @var array<string, mixed> $stats
 * @var array<int, array<string, mixed>> $byRoom
 * @var array<string, mixed> $chart
 */
?>
<div class="page-header">
  <div>
    <h1>รายงานสถิติการใช้งาน</h1>
    <p>สรุปการใช้ห้องประชุมและประสิทธิภาพงานซ่อมบำรุงทั้งหมดในระบบ</p>
  </div>
  <div class="page-actions no-print">
    <button type="button" class="btn btn-ghost" data-print><?= icon('printer') ?>พิมพ์รายงาน</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['การจองที่อนุมัติแล้ว', number_format($stats['bookings']) . ' รายการ', 'calendar-check', 'metric-icon-success'],
      ['ชั่วโมงการใช้ห้องรวม', number_format($stats['hours']) . ' ชม.', 'clock', ''],
      ['อัตราปิดงานซ่อมสำเร็จ', $stats['completion'] . '%', 'wrench', 'metric-icon-warning'],
      ['ค่าใช้จ่ายงานซ่อมรวม', money($stats['repair_cost']), 'calculator', 'metric-icon-danger'],
  ] as [$label, $value, $iconName, $tone]): ?>
    <div class="col-6 col-lg-3">
      <div class="card metric-card">
        <div class="metric-head"><?= e($label) ?> <span class="metric-icon <?= e($tone) ?>"><?= icon($iconName) ?></span></div>
        <div class="metric-value"><?= e($value) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <section class="col-lg-7 card" aria-labelledby="dept-chart-title">
    <h2 class="card-title mb-3" id="dept-chart-title"><?= icon('chart-column') ?>จำนวนการจองแยกตามหน่วยงาน</h2>
    <div class="chart-box"><canvas id="chart-departments" role="img" aria-label="กราฟแท่งจำนวนการจองของแต่ละหน่วยงาน"></canvas></div>
  </section>
  <section class="col-lg-5 card" aria-labelledby="cat-chart-title">
    <h2 class="card-title mb-3" id="cat-chart-title"><?= icon('chart-pie') ?>สัดส่วนประเภทงานแจ้งซ่อม</h2>
    <div class="chart-box"><canvas id="chart-categories" role="img" aria-label="กราฟสัดส่วนหมวดหมู่งานแจ้งซ่อม"></canvas></div>
  </section>
</div>

<section class="card" aria-labelledby="room-table-title">
  <h2 class="card-title mb-3" id="room-table-title"><?= icon('table') ?>การใช้งานแยกตามห้องประชุม</h2>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th scope="col">ห้องประชุม</th><th scope="col" class="num">จำนวนการจอง</th><th scope="col" class="num">ชั่วโมงรวม</th></tr></thead>
      <tbody>
        <?php foreach ($byRoom as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td class="num"><?= e(number_format((int) $r['n'])) ?></td><td class="num"><?= e(num($r['hours'])) ?> ชม.</td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<script type="application/json" id="report-data"><?= json_encode($chart + ['type' => 'reports'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
