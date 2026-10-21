<?php

use App\Services\ImageUpload;
use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, int> $usage
 * @var array<string, mixed>|null $editing
 * @var array<string, mixed>|null $form
 * @var array<string, string> $errors
 */
$f = static fn (string $k): string => (string) ($form[$k] ?? '');
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>ตั้งค่าห้องประชุม</h1>
  </div>
  <?php if ($form === null): ?>
    <div class="page-actions"><a class="btn btn-primary" href="<?= e(url('/settings/rooms?new=1')) ?>"><?= icon('circle-plus') ?>เพิ่มห้องประชุม</a></div>
  <?php endif; ?>
</div>

<?php if ($form !== null): ?>
  <form class="card mb-4" method="post" action="<?= e(url($editing ? '/settings/rooms/' . $editing['id'] : '/settings/rooms')) ?>" enctype="multipart/form-data" novalidate aria-labelledby="room-form-title">
    <?= csrf_field() ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= e(ImageUpload::MAX_BYTES) ?>">
    <h2 class="card-title mb-3" id="room-form-title"><?= icon($editing ? 'square-pen' : 'circle-plus') ?><?= $editing ? 'แก้ไข ' . e($editing['id']) : 'เพิ่มห้องประชุม' ?></h2>
    <?= View::partial('partials/error-summary', ['errors' => $errors]) ?>
    <div class="row">
      <div class="col-md-6 field">
        <?= field_label('f-name', 'ชื่อห้องประชุม') ?>
        <input class="form-control" id="f-name" name="name" value="<?= e($f('name')) ?>" required<?= field_invalid($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-building', 'อาคาร') ?>
        <input class="form-control" id="f-building" name="building" value="<?= e($f('building')) ?>" required<?= field_invalid($errors, 'building') ?>>
        <?= field_error($errors, 'building') ?>
      </div>
      <div class="col-md-2 field">
        <?= field_label('f-floor', 'ชั้น') ?>
        <input class="form-control" id="f-floor" name="floor" value="<?= e($f('floor')) ?>" required<?= field_invalid($errors, 'floor') ?>>
        <?= field_error($errors, 'floor') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-capacity', 'ความจุ (ที่นั่ง)') ?>
        <input class="form-control" id="f-capacity" name="capacity" value="<?= e($f('capacity')) ?>" placeholder="เช่น 20-30" required<?= field_invalid($errors, 'capacity') ?>>
        <?= field_error($errors, 'capacity') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-price_gov', 'อัตราหน่วยงานราชการ (บาท/ชม.)') ?>
        <input class="form-control" type="number" min="0" step="1" id="f-price_gov" name="price_gov" value="<?= e($f('price_gov')) ?>" required<?= field_invalid($errors, 'price_gov') ?>>
        <?= field_error($errors, 'price_gov') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-price_local', 'อัตราท้องถิ่น/เอกชน (บาท/ชม.)') ?>
        <input class="form-control" type="number" min="0" step="1" id="f-price_local" name="price_local" value="<?= e($f('price_local')) ?>" required<?= field_invalid($errors, 'price_local') ?>>
        <?= field_error($errors, 'price_local') ?>
      </div>
      <div class="field">
        <?= field_label('f-equipment_text', 'อุปกรณ์ในห้อง', false) ?>
        <input class="form-control" id="f-equipment_text" name="equipment_text" value="<?= e($f('equipment_text')) ?>" aria-describedby="equip-help">
        <p class="form-help" id="equip-help">คั่นแต่ละรายการด้วยเครื่องหมายจุลภาค เช่น Projector, Microphone, WiFi</p>
      </div>
      <div class="col-md-6 field">
        <?= field_label('f-rules', 'ระเบียบการใช้ห้อง', false) ?>
        <textarea class="form-control" id="f-rules" name="rules" rows="3"><?= e($f('rules')) ?></textarea>
      </div>
      <div class="col-md-6 field">
        <?= field_label('f-warnings', 'คำเตือนที่แสดงตอนจอง', false) ?>
        <textarea class="form-control" id="f-warnings" name="warnings" rows="3"><?= e($f('warnings')) ?></textarea>
      </div>
      <div class="col-md-8 field">
        <span class="form-label" id="image-label">รูปห้อง <span class="optional">(ไม่บังคับ<?= $f('image') ? ' · เลือกใหม่เพื่อแทนรูปเดิม' : '' ?>)</span></span>
        <label class="dropzone" data-dropzone>
          <input type="file" id="f-image" name="image" accept="image/jpeg,image/png,image/webp" aria-labelledby="image-label"<?= field_invalid($errors, 'image') ?>>
          <?= icon('cloud-upload', 'icon-lg') ?>
          <span class="fw-semibold">ลากรูปมาวาง หรือคลิกเพื่อเลือกไฟล์</span>
          <span class="text-caption text-muted">JPG, PNG หรือ WebP ไม่เกิน 5 MB</span>
        </label>
        <div class="upload-preview" data-preview hidden>
          <img src="" alt="รูปห้องที่เลือก">
          <button type="button" class="btn btn-ghost btn-sm" data-preview-remove><?= icon('x', 'icon-sm') ?>นำรูปออก</button>
        </div>
        <?= field_error($errors, 'image') ?>
      </div>
      <div class="col-md-4 field">
        <span class="form-label">สถานะห้อง</span>
        <label class="form-check">
          <input type="checkbox" name="locked" value="1"<?= $f('locked') ? ' checked' : '' ?>>
          ระงับการใช้งานชั่วคราว (ปิดรับจอง)
        </label>
        <?php if ($f('image')): ?>
          <img class="room-thumb mt-2" src="<?= e($f('image')) ?>" alt="รูปห้องปัจจุบัน">
        <?php endif; ?>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-ghost" href="<?= e(url('/settings/rooms')) ?>">ยกเลิก</a>
      <button type="submit" class="btn btn-primary"><?= icon('save') ?>บันทึก</button>
    </div>
  </form>
<?php endif; ?>

<section class="card">
  <div class="table-responsive">
    <table class="table table-cards">
      <thead><tr><th scope="col">รหัส</th><th scope="col">ชื่อห้อง</th><th scope="col">ที่ตั้ง</th><th scope="col">ความจุ</th><th scope="col">อัตรา (บาท/ชม.)</th><th scope="col">สถานะ</th><th scope="col">การจัดการ</th></tr></thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td data-label="รหัส" class="nowrap"><?= e($r['id']) ?></td>
            <td data-label="ชื่อห้อง"><?= e($r['name']) ?></td>
            <td data-label="ที่ตั้ง"><?= e($r['building']) ?> ชั้น <?= e($r['floor']) ?></td>
            <td data-label="ความจุ"><?= e($r['capacity']) ?> ที่นั่ง</td>
            <td data-label="อัตรา" class="nowrap">ราชการ <?= e(number_format($r['price_gov'])) ?> · ทั่วไป <?= e(number_format($r['price_local'])) ?></td>
            <td data-label="สถานะ"><?= status_badge('room', $r['locked'] ? 'maintenance' : 'available') ?></td>
            <td data-label="การจัดการ">
              <div class="d-flex gap-2">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/settings/rooms?edit=' . urlencode($r['id']))) ?>"><?= icon('square-pen', 'icon-sm') ?>แก้ไข</a>
                <?php if (empty($usage[$r['id']])): ?>
                  <form method="post" action="<?= e(url('/settings/rooms/' . $r['id'] . '/delete')) ?>"
                        data-confirm="ลบ<?= e($r['name']) ?>หรือไม่?" data-confirm-text="ลบแล้วกู้คืนไม่ได้" data-confirm-button="ลบห้องประชุม" data-confirm-danger="1">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost-danger btn-sm"><?= icon('trash-2', 'icon-sm') ?>ลบ</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="form-help mt-3">ห้องที่มีประวัติการจองลบไม่ได้ เพื่อเก็บประวัติไว้ ให้ใช้ "ระงับการใช้งานชั่วคราว" แทน</p>
</section>
