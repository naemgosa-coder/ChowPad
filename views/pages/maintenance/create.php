<?php

use App\Models\Repair;
use App\Services\ImageUpload;

/**
 * @var array<string, mixed> $old
 * @var array<string, string> $errors
 * @var array<int, array<string, mixed>> $rooms
 */
$v = static fn (string $key): string => (string) ($old[$key] ?? '');
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
$error = static fn (string $key): string => isset($errors[$key])
    ? '<p class="form-error" id="err-' . e($key) . '">' . icon('circle-alert') . e($errors[$key]) . '</p>' : '';
?>
<div class="page-header">
  <div>
    <h1>แจ้งซ่อมอุปกรณ์</h1>
    <p>กรอกรายละเอียดเพื่อแจ้งทีมช่างและฝ่ายโยธาให้เข้าตรวจสอบ</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <form class="card" method="post" action="<?= e(url('/maintenance')) ?>" enctype="multipart/form-data" novalidate data-busy-text="กำลังส่งใบแจ้งซ่อม…">
      <?= csrf_field() ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= e(ImageUpload::MAX_BYTES) ?>">

      <?php if ($errors): ?>
        <div class="alert alert-danger mb-4" role="alert" tabindex="-1" data-focus-on-load>
          <?= icon('circle-alert') ?>
          <div>
            <strong>ยังส่งใบแจ้งซ่อมไม่ได้ กรุณาแก้ไข <?= e(count($errors)) ?> รายการ</strong>
            <ul class="mt-1">
              <?php foreach ($errors as $field => $message): ?>
                <li><a href="#f-<?= e($field) ?>"><?= e($message) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <p class="form-required-note">ช่องที่มี <span class="text-danger">*</span> จำเป็นต้องกรอก</p>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('map-pin') ?>ตำแหน่งและปัญหาหลัก</legend>
        <div class="row">
          <div class="col-md-6 field">
            <label class="form-label" for="f-room_id">ห้องประชุมที่พบปัญหา <span class="required" aria-hidden="true">*</span></label>
            <select class="form-select" id="f-room_id" name="room_id" required<?= $invalid('room_id') ?>>
              <option value="">เลือกห้องประชุม</option>
              <?php foreach ($rooms as $room): ?>
                <option value="<?= e($room['id']) ?>"<?= $v('room_id') === $room['id'] ? ' selected' : '' ?>><?= e($room['name']) ?> (<?= e($room['building']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <?= $error('room_id') ?>
          </div>
          <div class="col-md-6 field">
            <label class="form-label" for="f-category">หมวดหมู่อุปกรณ์ที่ชำรุด <span class="required" aria-hidden="true">*</span></label>
            <select class="form-select" id="f-category" name="category" required<?= $invalid('category') ?>>
              <option value="">เลือกปัญหาหลัก</option>
              <?php foreach (Repair::CATEGORIES as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $v('category') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $error('category') ?>
          </div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('triangle-alert') ?>ระดับความเร่งด่วน <span class="required" aria-hidden="true">*</span></legend>
        <div class="choice-grid" id="f-priority">
          <?php foreach (Repair::PRIORITIES as $priority): ?>
            <?php $meta = status_meta('priority', $priority); ?>
            <label class="choice-card choice-<?= e(strtolower($priority)) ?>">
              <input type="radio" name="priority" value="<?= e($priority) ?>"<?= $v('priority') === $priority ? ' checked' : '' ?>>
              <?= icon($meta['icon']) ?>
              <span><?= e($meta['label']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="form-help mt-2">"ด่วนที่สุด" ใช้เมื่อปัญหาส่งผลให้เปิดการประชุมไม่ได้</p>
        <?= $error('priority') ?>
      </fieldset>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('file-text') ?>รายละเอียดปัญหา</legend>
        <div class="field">
          <label class="form-label" for="f-description">อธิบายอาการที่พบ <span class="required" aria-hidden="true">*</span></label>
          <textarea class="form-control" id="f-description" name="description" rows="4" maxlength="2000" required<?= $invalid('description') ?>><?= e($v('description')) ?></textarea>
          <p class="form-help">เช่น แอร์ด้านหลังขวามือเสียงดังและลมเย็นออกน้อย หรือหลอดไฟกะพริบ 2 จุดเหนือโพเดียม</p>
          <?= $error('description') ?>
        </div>
        <div class="field">
          <span class="form-label" id="before-image-label">รูปถ่ายจุดชำรุด <span class="optional">(ไม่บังคับ)</span></span>
          <label class="dropzone" data-dropzone>
            <input type="file" id="f-before_image" name="before_image" accept="image/jpeg,image/png,image/webp"
                   aria-labelledby="before-image-label" aria-describedby="before-image-help"<?= isset($errors['before_image']) ? ' aria-invalid="true"' : '' ?>>
            <?= icon('cloud-upload', 'icon-lg') ?>
            <span class="fw-semibold">ลากรูปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</span>
            <span class="text-caption text-muted" id="before-image-help">JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB · ระบบจะลบข้อมูลตำแหน่งในรูปออกให้</span>
          </label>
          <div class="upload-preview" data-preview hidden>
            <img src="" alt="รูปที่เลือก">
            <button type="button" class="btn btn-ghost btn-sm" data-preview-remove><?= icon('x', 'icon-sm') ?>นำรูปออก</button>
          </div>
          <?= $error('before_image') ?>
          <?php if ($errors): ?>
            <p class="form-help">หากเลือกรูปไว้ กรุณาเลือกใหม่อีกครั้ง</p>
          <?php endif; ?>
        </div>
      </fieldset>

      <div class="form-actions">
        <a class="btn btn-ghost" href="<?= e(url('/maintenance')) ?>">ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><?= icon('send') ?>ส่งใบแจ้งซ่อม</button>
      </div>
    </form>
  </div>

  <aside class="col-lg-4">
    <section class="card" aria-labelledby="process-title">
      <h2 class="card-title mb-3" id="process-title"><?= icon('info') ?>ขั้นตอนการแก้ไข</h2>
      <ol class="steps">
        <li><strong>ส่งใบแจ้งซ่อม</strong><span>ใบงานเข้าคิวของทีมช่างทันที</span></li>
        <li><strong>มอบหมายงาน</strong><span>หัวหน้าฝ่ายมอบหมาย หรือนายช่างกดรับงานเอง</span></li>
        <li><strong>ซ่อมและปิดงาน</strong><span>นายช่างบันทึกผล ค่าใช้จ่าย และรูปหลังซ่อม แล้วระบบแจ้งกลับถึงคุณ</span></li>
      </ol>
    </section>
  </aside>
</div>
