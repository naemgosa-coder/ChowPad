<?php

use App\Services\AttendanceService;

/**
 * @var array<string, mixed> $user
 * @var array<string, mixed>|null $today
 * @var array{start: string, end: string} $hours
 * @var array<string, mixed> $stats
 * @var array<int, array<string, mixed>> $recent
 * @var array<string, int>|null $team
 * @var array<int, array<string, mixed>> $onLeave
 */
$state = match (true) {
    $today === null => ['ยังไม่ลงเวลาเข้างาน', 'badge-neutral', 'clock'],
    $today['status'] === 'leave' => ['ลาหยุดวันนี้', 'badge-neutral', 'calendar-minus'],
    (bool) $today['clock_out'] => ['ออกงานแล้ว', 'badge-success', 'circle-check'],
    default => ['กำลังปฏิบัติงาน', 'badge-info', 'briefcase'],
};
$canIn = $today === null || ($today['status'] !== 'leave' && !$today['clock_in']);
$canOut = $today !== null && $today['clock_in'] && !$today['clock_out'];
?>
<div class="page-header">
  <div>
    <h1>ลงเวลาปฏิบัติงาน</h1>
    <p>เวลาทำการ <?= e($hours['start']) ?>–<?= e($hours['end']) ?> น. · เข้าหลัง <?= e($hours['start']) ?> น. นับเป็นสาย</p>
  </div>
</div>

<div class="row g-4 mb-4">
  <section class="col-lg-4 card profile-card" aria-label="ข้อมูลบุคลากร">
    <?php if (!empty($user['avatar'])): ?>
      <img class="avatar avatar-lg" src="<?= e($user['avatar']) ?>" alt="" width="96" height="96">
    <?php else: ?>
      <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($user['name'])) ?></span>
    <?php endif; ?>
    <h2 class="h3 mt-3"><?= e($user['name']) ?></h2>
    <p class="text-sm text-muted"><?= e($user['id']) ?></p>
    <div class="chip-list justify-content-center mt-2">
      <?php if ($user['position']): ?><span class="chip"><?= icon('briefcase') ?><?= e($user['position']) ?></span><?php endif; ?>
      <span class="chip"><?= icon('network') ?><?= e($user['department_name'] ?? '-') ?></span>
    </div>
    <dl class="detail-list mt-3 text-start">
      <div><dt>บทบาท</dt><dd><?= e(role_label($user['role'])) ?></dd></div>
      <div><dt>เริ่มใช้งานระบบ</dt><dd><?= e(thai_date(substr((string) $user['created_at'], 0, 10))) ?></dd></div>
    </dl>
  </section>

  <section class="col-lg-8 card clock-card" aria-labelledby="clock-title">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2 class="card-title" id="clock-title"><?= icon('clock') ?>บันทึกเวลาวันนี้</h2>
      <span class="badge <?= e($state[1]) ?>"><?= icon($state[2]) ?><?= e($state[0]) ?></span>
    </div>
    <div class="clock-face">
      <div class="clock-time tabular" id="live-clock" aria-hidden="true"
           data-server-epoch="<?= e((int) round(microtime(true) * 1000)) ?>" data-timezone="<?= e(config('app.timezone')) ?>"><?= e(date('H:i:s')) ?></div>
      <div class="text-muted"><?= e(thai_date(date('Y-m-d'), 'long')) ?></div>
      <?php if ($today && $today['clock_in']): ?>
        <p class="text-sm mt-2">
          เข้างาน <strong class="tabular"><?= e(substr($today['clock_in'], 0, 5)) ?> น.</strong>
          <?php if ($today['clock_out']): ?> · ออกงาน <strong class="tabular"><?= e(substr($today['clock_out'], 0, 5)) ?> น.</strong><?php endif; ?>
        </p>
      <?php elseif ($today && $today['status'] === 'leave'): ?>
        <p class="text-sm mt-2"><?= e($today['remarks']) ?></p>
      <?php endif; ?>
    </div>
    <?php if ($canIn || $canOut): ?>
      <form method="post" action="<?= e(url($canIn ? '/attendance/clock-in' : '/attendance/clock-out')) ?>" class="clock-form" data-busy-text="กำลังบันทึก…">
        <?= csrf_field() ?>
        <div class="field">
          <label class="form-label" for="clock-remarks">หมายเหตุหรือสถานที่ <span class="optional">(ไม่บังคับ)</span></label>
          <input class="form-control" id="clock-remarks" name="remarks" maxlength="255" placeholder="เช่น ปฏิบัติงานนอกสถานที่ หรือประชุมที่อำเภอ">
        </div>
        <div class="d-flex gap-3 flex-wrap mt-3">
          <button type="submit" class="btn btn-primary flex-grow-1" formaction="<?= e(url('/attendance/clock-in')) ?>"<?= $canIn ? '' : ' disabled' ?>><?= icon('log-in') ?>ลงเวลาเข้างาน</button>
          <button type="submit" class="btn btn-secondary flex-grow-1" formaction="<?= e(url('/attendance/clock-out')) ?>"<?= $canOut ? '' : ' disabled' ?>><?= icon('log-out') ?>ลงเวลาออกงาน</button>
        </div>
        <?php if (!$canIn): ?><p class="form-help mt-2">ลงเวลาเข้างานแล้ว กดลงเวลาออกงานเมื่อเลิกงาน</p><?php endif; ?>
      </form>
    <?php else: ?>
      <p class="text-sm text-muted text-center mt-3"><?= $today && $today['status'] === 'leave' ? 'วันนี้คุณลาหยุดงาน ไม่ต้องลงเวลา' : 'บันทึกเวลาวันนี้ครบแล้ว' ?></p>
    <?php endif; ?>
  </section>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['มาทำงานปกติ (เดือนนี้)', $stats['normal'] . ' วัน', 'circle-check', 'metric-icon-success'],
      ['เข้างานสาย (เดือนนี้)', $stats['late'] . ' วัน', 'clock', 'metric-icon-warning'],
      ['สิทธิ์วันลาคงเหลือ', rtrim(rtrim(number_format($stats['leave_left'], 1), '0'), '.') . ' วัน', 'calendar-minus', ''],
      ['ชั่วโมงหลังเวลาเลิกงาน (เดือนนี้)', number_format($stats['ot_hours'], 1) . ' ชม.', 'briefcase', 'metric-icon-danger'],
  ] as [$label, $value, $iconName, $tone]): ?>
    <div class="col-6 col-lg-3">
      <div class="card metric-card">
        <div class="metric-head"><?= e($label) ?> <span class="metric-icon <?= e($tone) ?>"><?= icon($iconName) ?></span></div>
        <div class="metric-value"><?= e($value) ?></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($team !== null): ?>
  <section class="card mb-4" aria-labelledby="team-title">
    <div class="card-header">
      <h2 class="card-title" id="team-title"><?= icon('users') ?>ภาพรวมบุคลากรวันนี้</h2>
      <a class="btn btn-secondary btn-sm" href="<?= e(url('/attendance/reports')) ?>">ดูรายงาน</a>
    </div>
    <?php $ratio = $team['total'] ? (int) round($team['present'] / $team['total'] * 100) : 0; ?>
    <div class="d-flex justify-content-between text-sm mb-1">
      <span>มาทำงานแล้ว</span>
      <span class="tabular fw-semibold"><?= e($team['present']) ?> / <?= e($team['total']) ?> คน (<?= e($ratio) ?>%)</span>
    </div>
    <div class="progress" role="progressbar" aria-label="สัดส่วนผู้มาทำงาน" aria-valuenow="<?= e($ratio) ?>" aria-valuemin="0" aria-valuemax="100">
      <span style="width: <?= e($ratio) ?>%"></span>
    </div>
    <div class="row g-3 mt-3">
      <div class="col-md-4"><span class="text-sm text-muted">เข้าสาย</span><div class="h3 text-warning"><?= e($team['late']) ?> คน</div></div>
      <div class="col-md-4"><span class="text-sm text-muted">ยังไม่ลงเวลา</span><div class="h3"><?= e($team['missing']) ?> คน</div></div>
      <div class="col-md-4">
        <span class="text-sm text-muted">ลาหยุดวันนี้</span><div class="h3"><?= e(count($onLeave)) ?> คน</div>
        <?php if ($onLeave): ?>
          <ul class="list-unstyled text-caption text-muted">
            <?php foreach (array_slice($onLeave, 0, 5) as $l): ?><li><?= e($l['user_name']) ?> · <?= e($l['type_name']) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<section class="card" aria-labelledby="recent-title">
  <div class="card-header">
    <h2 class="card-title" id="recent-title"><?= icon('history') ?>บันทึก 5 รายการล่าสุด</h2>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/attendance/history')) ?>">ดูประวัติทั้งหมด<?= icon('arrow-right', 'icon-sm') ?></a>
  </div>
  <?php if (!$recent): ?>
    <div class="empty-state"><?= icon('clock', 'icon-xl') ?><strong>ยังไม่มีบันทึกเวลา</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead><tr><th scope="col">วันที่</th><th scope="col">เข้างาน</th><th scope="col">ออกงาน</th><th scope="col">รวม</th><th scope="col">สถานะ</th><th scope="col">หมายเหตุ</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $log): ?>
            <tr>
              <td data-label="วันที่" class="nowrap"><?= e(thai_date($log['date'])) ?></td>
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
