<?php
/**
 * @var array<string, mixed> $stats
 * @var array<int, array<string, mixed>> $live
 * @var array<int, array<string, mixed>> $todayBookings
 * @var array<int, array<string, mixed>> $recentBookings
 * @var array<int, array<string, mixed>> $recentRepairs
 * @var array<int, array<string, mixed>> $urgentRepairs
 * @var array<string, mixed> $charts
 * @var bool $showMoney
 */
?>
<div class="page-header">
  <div>
    <h1>ภาพรวมระบบ</h1>
    <p>สรุปการใช้ห้องประชุมและงานซ่อมบำรุง ณ <?= e(thai_date(date('Y-m-d'), 'long')) ?></p>
  </div>
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <span class="live-indicator" data-live-indicator data-state="ok">
      <span class="live-dot" aria-hidden="true"></span><span data-live-text>อัปเดตอัตโนมัติ</span>
    </span>
    <span class="badge badge-info tabular" id="dashboard-clock" aria-live="off"><?= icon('clock') ?><?= e(date('H:i')) ?> น.</span>
  </div>
</div>

<div class="alert alert-warning mb-3" data-live-offline hidden role="status">
  <?= icon('triangle-alert') ?>
  <span>ขาดการเชื่อมต่อ ข้อมูลอาจไม่เป็นปัจจุบัน (อัปเดตล่าสุด <span data-live-updated><?= e(date('H:i')) ?></span> น.)</span>
  <button type="button" class="btn btn-secondary btn-sm" data-live-retry>ลองใหม่</button>
</div>

<!-- ตัวเลขสรุป -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card metric-card">
      <div class="metric-head">ห้องประชุมทั้งหมด <span class="metric-icon"><?= icon('door-open') ?></span></div>
      <div class="metric-value"><?= e($stats['rooms']) ?></div>
      <div class="metric-foot text-success"><?= icon('circle-check', 'icon-xs') ?> ว่างขณะนี้ <strong data-stat="rooms-available"><?= e($stats['rooms_available']) ?></strong> ห้อง</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card metric-card">
      <div class="metric-head">การจองวันนี้ <span class="metric-icon metric-icon-success"><?= icon('calendar') ?></span></div>
      <div class="metric-value"><?= e($stats['today_bookings']) ?></div>
      <div class="metric-foot">รออนุมัติทั้งหมด <strong class="text-warning"><?= e($stats['pending_approvals']) ?></strong> รายการ</div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card metric-card">
      <div class="metric-head">กำลังประชุม <span class="metric-icon metric-icon-warning"><?= icon('users') ?></span></div>
      <div class="metric-value" data-stat="in-use"><?= e($stats['in_progress']) ?></div>
      <div class="metric-foot">
        <?php if ($showMoney): ?>
          รายได้วันนี้ <strong class="text-success"><?= e(money($stats['today_revenue'])) ?></strong>
        <?php else: ?>
          ห้องที่มีการประชุมอยู่ขณะนี้
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card metric-card">
      <div class="metric-head">งานซ่อมค้าง <span class="metric-icon metric-icon-danger"><?= icon('wrench') ?></span></div>
      <div class="metric-value"><?= e($stats['repairs_pending']) ?></div>
      <div class="metric-foot">กำลังซ่อม <?= e($stats['repairs_in_progress']) ?> · เสร็จสิ้นแล้ว <span class="text-success"><?= e($stats['repairs_completed']) ?></span> รายการ</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php if ($showMoney): ?>
    <div class="col-md-6">
      <div class="card d-flex align-items-center justify-content-between gap-3">
        <div>
          <div class="text-muted text-sm">รายได้ค่าธรรมเนียมเดือนนี้</div>
          <div class="h2 text-success tabular"><?= e(money($stats['month_revenue'])) ?></div>
        </div>
        <span class="metric-icon metric-icon-success"><?= icon('wallet', 'icon-lg') ?></span>
      </div>
    </div>
  <?php endif; ?>
  <div class="<?= $showMoney ? 'col-md-6' : '' ?>">
    <div class="card d-flex align-items-center justify-content-between gap-3">
      <div>
        <div class="text-muted text-sm">ห้องประชุมที่ใช้งานมากที่สุด</div>
        <div class="h2 text-primary"><?= e($stats['most_used_room'] ?? 'ยังไม่มีข้อมูล') ?></div>
      </div>
      <span class="metric-icon"><?= icon('award', 'icon-lg') ?></span>
    </div>
  </div>
</div>

<!-- สถานะห้องเรียลไทม์ + ตารางวันนี้ -->
<div class="row g-4 mb-4">
  <div class="col-lg-5">
    <section class="card h-100" aria-labelledby="live-rooms-title">
      <div class="card-header">
        <h2 class="card-title" id="live-rooms-title"><?= icon('door-open') ?>สถานะห้องขณะนี้</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/rooms')) ?>">ดูห้องทั้งหมด</a>
      </div>
      <ul class="room-status-list" aria-live="polite">
        <?php foreach ($live as $room): ?>
          <li data-room-status="<?= e($room['id']) ?>">
            <div>
              <div class="fw-semibold"><?= e($room['name']) ?></div>
              <div class="text-caption text-muted" data-room-line><?= e($room['line']) ?></div>
            </div>
            <span class="badge <?= e($room['badge_class']) ?>" data-room-badge><?= icon($room['icon']) ?><?= e($room['label']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>
  <div class="col-lg-7">
    <section class="card h-100" aria-labelledby="today-title">
      <div class="card-header">
        <h2 class="card-title" id="today-title"><?= icon('calendar-days') ?>ตารางการใช้ห้องวันนี้</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/calendar')) ?>">ดูปฏิทิน</a>
      </div>
      <?php if (!$todayBookings): ?>
        <div class="empty-state">
          <?= icon('calendar', 'icon-xl') ?>
          <strong>วันนี้ยังไม่มีการจองห้องประชุม</strong>
          <a class="btn btn-primary" href="<?= e(url('/bookings/create')) ?>"><?= icon('calendar-plus') ?>จองห้องประชุม</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th scope="col">เวลา</th><th scope="col">ห้องประชุม</th><th scope="col">หัวข้อ</th><th scope="col">สถานะ</th></tr></thead>
            <tbody>
              <?php foreach ($todayBookings as $b): ?>
                <tr>
                  <td class="tabular nowrap"><?= e(time_range($b['start_time'], $b['end_time'])) ?></td>
                  <td><?= e($b['room_name']) ?></td>
                  <td><?= e($b['title']) ?></td>
                  <td><?= status_badge('booking', $b['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<!-- กราฟ -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <section class="card h-100" aria-labelledby="chart-monthly-title">
      <h2 class="card-title mb-3" id="chart-monthly-title"><?= icon('chart-column') ?>สถิติการใช้ห้อง<?= $showMoney ? 'และรายได้' : '' ?>รายเดือน</h2>
      <div class="chart-box"><canvas id="chart-monthly" role="img" aria-label="กราฟจำนวนการจองรายเดือนย้อนหลัง 7 เดือน"></canvas></div>
    </section>
  </div>
  <div class="col-lg-4">
    <section class="card h-100" aria-labelledby="chart-usage-title">
      <h2 class="card-title mb-3" id="chart-usage-title"><?= icon('chart-pie') ?>สัดส่วนการใช้ห้อง</h2>
      <div class="chart-box"><canvas id="chart-room-usage" role="img" aria-label="กราฟสัดส่วนจำนวนครั้งที่ใช้แต่ละห้อง"></canvas></div>
    </section>
  </div>
</div>
<script type="application/json" id="dashboard-data"><?= json_encode(
    $charts + ['showMoney' => $showMoney, 'pollSeconds' => config('app.poll_seconds')],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
) ?></script>

<!-- รายการล่าสุด -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <section class="card h-100" aria-labelledby="recent-bookings-title">
      <div class="card-header">
        <h2 class="card-title" id="recent-bookings-title"><?= icon('clipboard-list') ?>รายการจองห้องล่าสุด</h2>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('/bookings')) ?>">ดูทั้งหมด</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th scope="col">ห้องประชุม</th><th scope="col">หัวข้อการประชุม</th><th scope="col">วันและเวลา</th><th scope="col">ผู้จอง</th><th scope="col">สถานะ</th></tr></thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><?= e($b['room_name']) ?></td>
                <td><a href="<?= e(url('/bookings/' . $b['id'])) ?>"><?= e($b['title']) ?></a></td>
                <td class="nowrap"><?= e(thai_date($b['date'])) ?><br><span class="text-caption text-muted tabular"><?= e(time_range($b['start_time'], $b['end_time'])) ?></span></td>
                <td><?= e($b['organizer']) ?></td>
                <td><?= status_badge('booking', $b['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
  <div class="col-lg-4">
    <section class="card h-100" aria-labelledby="urgent-title">
      <h2 class="card-title mb-3" id="urgent-title"><?= icon('triangle-alert') ?>งานซ่อมด่วน</h2>
      <?php if (!$urgentRepairs): ?>
        <div class="empty-state"><?= icon('smile', 'icon-xl') ?><span>ไม่มีงานซ่อมด่วนในขณะนี้</span></div>
      <?php else: ?>
        <ul class="list-unstyled d-flex flex-column gap-3">
          <?php foreach ($urgentRepairs as $r): ?>
            <li>
              <a class="urgent-job" href="<?= e(url('/maintenance/' . $r['id'])) ?>">
                <span class="d-flex justify-content-between gap-2">
                  <?= status_badge('priority', $r['priority']) ?>
                  <span class="text-caption text-muted"><?= e(thai_date($r['date'])) ?></span>
                </span>
                <span class="urgent-job-title"><?= e($r['description']) ?></span>
                <span class="text-caption text-muted d-flex align-items-center gap-1"><?= icon('map-pin', 'icon-xs') ?><?= e($r['room_name']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <section class="card h-100" aria-labelledby="recent-repairs-title">
      <div class="card-header">
        <h2 class="card-title" id="recent-repairs-title"><?= icon('file-text') ?>รายการแจ้งซ่อมล่าสุด</h2>
        <a class="btn btn-secondary btn-sm" href="<?= e(url('/maintenance')) ?>">ดูทั้งหมด</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th scope="col">เลขใบงาน</th><th scope="col">ห้องประชุม</th><th scope="col">หมวดหมู่</th><th scope="col">ความสำคัญ</th><th scope="col">สถานะ</th></tr></thead>
          <tbody>
            <?php foreach ($recentRepairs as $r): ?>
              <tr>
                <td class="nowrap"><a href="<?= e(url('/maintenance/' . $r['id'])) ?>"><?= e($r['id']) ?></a></td>
                <td><?= e($r['room_name']) ?></td>
                <td><?= e(App\Models\Repair::categoryLabel($r['category'])) ?></td>
                <td><?= status_badge('priority', $r['priority']) ?></td>
                <td><?= status_badge('repair', $r['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
  <div class="col-lg-4">
    <section class="card h-100" aria-labelledby="quick-title">
      <h2 class="card-title mb-3" id="quick-title"><?= icon('zap') ?>การดำเนินการด่วน</h2>
      <div class="d-grid gap-2">
        <a class="btn btn-primary" href="<?= e(url('/bookings/create')) ?>"><?= icon('calendar-plus') ?>จองห้องประชุม</a>
        <a class="btn btn-secondary" href="<?= e(url('/maintenance/create')) ?>"><?= icon('wrench') ?>แจ้งอุปกรณ์ชำรุด</a>
        <a class="btn btn-secondary" href="<?= e(url('/calendar')) ?>"><?= icon('calendar') ?>ตรวจสอบปฏิทินห้องว่าง</a>
      </div>
    </section>
  </div>
</div>
