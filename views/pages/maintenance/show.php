<?php

use App\Models\Repair;
use App\Services\View;

/**
 * @var array<string, mixed> $repair
 * @var bool $canAssign
 * @var bool $canWork
 * @var array<int, array<string, mixed>> $technicians
 */
$r = $repair;
$cost = (float) $r['labor_cost'] + (float) $r['material_cost'];
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/maintenance')) ?>"><?= icon('chevron-left', 'icon-sm') ?>กลับไปใบงานซ่อมบำรุง</a></p>
    <h1>ใบงานซ่อมเลขที่ <?= e($r['id']) ?></h1>
  </div>
  <div class="page-actions">
    <?= status_badge('priority', $r['priority']) ?>
    <?= status_badge('repair', $r['status']) ?>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8 d-flex flex-column gap-4">
    <section class="card" aria-labelledby="detail-title">
      <h2 class="card-title mb-3" id="detail-title"><?= icon('wrench') ?><?= e(Repair::categoryLabel($r['category'])) ?></h2>
      <dl class="detail-list">
        <div><dt>รายละเอียดปัญหา</dt><dd><?= nl2br(e($r['description'])) ?></dd></div>
        <div><dt>สถานที่</dt><dd><?= e($r['room_name']) ?> · <?= e($r['building']) ?> ชั้น <?= e($r['floor']) ?></dd></div>
        <div><dt>วันที่แจ้ง</dt><dd><?= e(thai_date($r['date'], 'long')) ?></dd></div>
        <div><dt>ผู้แจ้ง</dt><dd><?= e($r['reporter_name'] ?? '-') ?></dd></div>
        <div><dt>ช่างผู้รับผิดชอบ</dt><dd><?= e($r['technician_name'] ?? 'ยังไม่มอบหมาย') ?></dd></div>
        <?php if ($r['start_time']): ?>
          <div><dt>เวลาดำเนินการ</dt><dd class="tabular">เริ่ม <?= e(substr($r['start_time'], 0, 5)) ?> น.<?= $r['finish_time'] ? ' · เสร็จ ' . e(substr($r['finish_time'], 0, 5)) . ' น.' : '' ?></dd></div>
        <?php endif; ?>
        <?php if ($r['status'] === 'Completed'): ?>
          <div><dt>ผลการซ่อม</dt><dd><?= nl2br(e($r['repair_notes'] ?: '-')) ?></dd></div>
          <div><dt>วัสดุที่ใช้</dt><dd><?= e($r['material_used'] ?: '-') ?></dd></div>
          <div><dt>ค่าใช้จ่าย</dt><dd class="tabular">ค่าวัสดุ <?= e(money($r['material_cost'])) ?> · ค่าแรง <?= e(money($r['labor_cost'])) ?> · <strong>รวม <?= e(money($cost)) ?></strong></dd></div>
        <?php elseif ($r['repair_notes']): ?>
          <div><dt>บันทึกของช่าง</dt><dd><?= nl2br(e($r['repair_notes'])) ?></dd></div>
        <?php endif; ?>
      </dl>
    </section>

    <section class="card" aria-labelledby="photo-title">
      <h2 class="card-title mb-3" id="photo-title"><?= icon('camera') ?>รูปถ่าย</h2>
      <div class="row">
        <?php foreach (['before_image' => 'ก่อนซ่อม', 'after_image' => 'หลังซ่อม'] as $field => $label): ?>
          <figure class="col-md-6 photo">
            <?php if ($r[$field]): ?>
              <a href="<?= e($r[$field]) ?>" target="_blank" rel="noopener">
                <img src="<?= e($r[$field]) ?>" alt="รูป<?= e($label) ?> ใบงาน <?= e($r['id']) ?>" loading="lazy">
              </a>
            <?php else: ?>
              <div class="photo-empty"><?= icon('image', 'icon-lg') ?><span>ไม่มีรูป<?= e($label) ?></span></div>
            <?php endif; ?>
            <figcaption><?= e($label) ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <aside class="col-lg-4 d-flex flex-column gap-4">
    <?php if ($canAssign): ?>
      <section class="card" aria-labelledby="assign-title">
        <h2 class="card-title mb-3" id="assign-title"><?= icon('user-cog') ?>มอบหมายนายช่าง</h2>
        <form method="post" action="<?= e(url('/maintenance/' . $r['id'] . '/assign')) ?>" data-busy-text="กำลังมอบหมาย…">
          <?= csrf_field() ?>
          <div class="field">
            <label class="form-label" for="assign-tech">นายช่าง</label>
            <select class="form-select" id="assign-tech" name="technician_id" required>
              <option value="">เลือกนายช่าง</option>
              <?php foreach ($technicians as $t): ?>
                <option value="<?= e($t['id']) ?>"><?= e($t['name']) ?><?= $t['skill'] ? ' · ' . e($t['skill']) : '' ?> (งานค้าง <?= e($t['open_jobs']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <p class="form-help">ตัวเลขในวงเล็บคืองานที่ช่างกำลังทำอยู่</p>
          </div>
          <button type="submit" class="btn btn-primary btn-block mt-3"><?= icon('send') ?>มอบหมายงาน</button>
        </form>
      </section>
    <?php endif; ?>
    <?php if ($canWork): ?>
      <section class="card" aria-labelledby="work-title">
        <h2 class="card-title mb-3" id="work-title"><?= icon('wrench') ?>สำหรับนายช่าง</h2>
        <?= View::partial('partials/repair-actions', ['repair' => $r, 'returnTo' => '/maintenance/' . $r['id']]) ?>
      </section>
    <?php endif; ?>
    <a class="btn btn-secondary" href="<?= e(url('/maintenance/create?room=' . urlencode($r['room_id']))) ?>"><?= icon('circle-plus') ?>แจ้งซ่อมห้องนี้เพิ่ม</a>
  </aside>
</div>

<?php if ($canWork): ?>
  <?= View::partial('partials/complete-dialog') ?>
<?php endif; ?>
