<?php
/** @var array<string, mixed> $rules @var array<string, string> $errors */
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>กฎการจองห้องประชุม</h1>
    <p>ระบบตรวจกฎเหล่านี้ทุกครั้งที่มีการส่งคำขอจอง</p>
  </div>
</div>

<form class="card" method="post" action="<?= e(url('/settings/booking-rules')) ?>" novalidate>
  <?= csrf_field() ?>
  <?= App\Services\View::partial('partials/error-summary', ['errors' => $errors]) ?>
  <div class="row">
    <div class="col-md-6 field">
      <?= field_label('f-max_advance_days', 'จองล่วงหน้าได้สูงสุด (วัน)') ?>
      <input class="form-control" type="number" min="1" max="730" id="f-max_advance_days" name="max_advance_days" value="<?= e($rules['max_advance_days']) ?>" required<?= field_invalid($errors, 'max_advance_days') ?>>
      <?= field_error($errors, 'max_advance_days') ?>
    </div>
    <div class="col-md-6 field">
      <?= field_label('f-max_hours', 'ระยะเวลาสูงสุดต่อการจอง 1 ครั้ง (ชั่วโมง)') ?>
      <input class="form-control" type="number" min="0.5" max="24" step="0.5" id="f-max_hours" name="max_hours" value="<?= e($rules['max_hours']) ?>" required<?= field_invalid($errors, 'max_hours') ?>>
      <?= field_error($errors, 'max_hours') ?>
    </div>
    <div class="col-md-6 field">
      <?= field_label('f-open_time', 'เปิดรับจองตั้งแต่') ?>
      <input class="form-control" type="time" id="f-open_time" name="open_time" value="<?= e($rules['open_time']) ?>" required<?= field_invalid($errors, 'open_time') ?>>
      <?= field_error($errors, 'open_time') ?>
    </div>
    <div class="col-md-6 field">
      <?= field_label('f-close_time', 'ถึงเวลา') ?>
      <input class="form-control" type="time" id="f-close_time" name="close_time" value="<?= e($rules['close_time']) ?>" required>
    </div>
  </div>
  <fieldset class="form-section mt-4">
    <legend class="form-label">ตัวเลือก</legend>
    <label class="form-check"><input type="checkbox" name="block_holidays" value="1"<?= $rules['block_holidays'] ? ' checked' : '' ?>> ไม่เปิดให้จองในวันหยุดราชการ</label>
    <label class="form-check"><input type="checkbox" name="auto_approve" value="1"<?= $rules['auto_approve'] ? ' checked' : '' ?> aria-describedby="auto-help"> อนุมัติการจองอัตโนมัติเมื่อไม่มีช่วงเวลาทับซ้อน</label>
    <p class="form-help" id="auto-help">เมื่อเปิด คำขอที่ผ่านกฎทั้งหมดจะได้สถานะ "อนุมัติแล้ว" ทันทีโดยไม่ต้องรอเจ้าหน้าที่</p>
  </fieldset>
  <div class="form-actions justify-content-end">
    <button type="submit" class="btn btn-primary"><?= icon('save') ?>บันทึกกฎการจอง</button>
  </div>
</form>
