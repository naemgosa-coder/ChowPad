<?php

use App\Services\ImageUpload;

/**
 * @var array<string, mixed> $user
 * @var array<string, mixed> $old
 * @var array<string, string> $errors
 * @var array<string, string> $passwordErrors
 * @var array<int, array<string, mixed>> $logins
 */
?>
<div class="page-header">
  <div>
    <h1>โปรไฟล์ของฉัน</h1>
    <p>แก้ไขข้อมูลติดต่อ รูปโปรไฟล์ และรหัสผ่าน</p>
  </div>
</div>

<div class="row g-4">
  <aside class="col-lg-4 d-flex flex-column gap-4">
    <section class="card profile-card" aria-label="ข้อมูลบัญชี">
      <?php if ($user['avatar']): ?>
        <img class="avatar avatar-lg" src="<?= e($user['avatar']) ?>" alt="" width="96" height="96">
      <?php else: ?>
        <span class="avatar avatar-lg" aria-hidden="true"><?= e(initials($user['name'])) ?></span>
      <?php endif; ?>
      <h2 class="h3 mt-3"><?= e($user['name']) ?></h2>
      <p class="text-sm text-muted"><?= e(role_label($user['role'])) ?></p>
      <dl class="detail-list mt-3 text-start">
        <div><dt>รหัสบุคลากร</dt><dd><?= e($user['id']) ?></dd></div>
        <div><dt>ชื่อผู้ใช้</dt><dd><?= e($user['username']) ?></dd></div>
        <div><dt>สังกัด</dt><dd><?= e($user['department_name'] ?? '-') ?></dd></div>
      </dl>
      <p class="form-help mt-2">บทบาทและสังกัดแก้ไขได้โดยผู้ดูแลระบบ</p>
    </section>
    <section class="card" aria-labelledby="logins-title">
      <h2 class="card-title mb-3" id="logins-title"><?= icon('shield-check') ?>การเข้าสู่ระบบล่าสุด</h2>
      <?php if (!$logins): ?>
        <p class="text-sm text-muted">ยังไม่มีประวัติ</p>
      <?php else: ?>
        <ul class="list-unstyled d-flex flex-column gap-2 text-sm">
          <?php foreach ($logins as $l): ?>
            <li class="d-flex justify-content-between gap-2"><span><?= icon('monitor', 'icon-sm') ?> IP <?= e($l['ip_address'] ?? '-') ?></span><span class="text-muted tabular"><?= e(thai_date(substr($l['created_at'], 0, 10))) ?> <?= e(substr($l['created_at'], 11, 5)) ?></span></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </aside>

  <div class="col-lg-8 d-flex flex-column gap-4">
    <form class="card" method="post" action="<?= e(url('/profile')) ?>" enctype="multipart/form-data" novalidate aria-labelledby="info-title">
      <?= csrf_field() ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= e(ImageUpload::MAX_BYTES) ?>">
      <h2 class="card-title mb-3" id="info-title"><?= icon('user') ?>ข้อมูลส่วนตัว</h2>
      <?= App\Services\View::partial('partials/error-summary', ['errors' => $errors]) ?>
      <div class="row">
        <div class="col-md-6 field">
          <?= field_label('f-name', 'ชื่อ-นามสกุล') ?>
          <input class="form-control" id="f-name" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" required<?= field_invalid($errors, 'name') ?>>
          <?= field_error($errors, 'name') ?>
        </div>
        <div class="col-md-6 field">
          <?= field_label('f-email', 'อีเมล') ?>
          <input class="form-control" type="email" id="f-email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required<?= field_invalid($errors, 'email') ?>>
          <?= field_error($errors, 'email') ?>
        </div>
        <div class="col-md-6 field">
          <?= field_label('f-phone', 'เบอร์โทรศัพท์', false) ?>
          <input class="form-control" type="tel" id="f-phone" name="phone" value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel">
        </div>
        <div class="col-md-6 field">
          <?= field_label('f-position', 'ตำแหน่ง', false) ?>
          <input class="form-control" id="f-position" name="position" value="<?= e($old['position'] ?? '') ?>">
        </div>
        <div class="field">
          <span class="form-label" id="avatar-label">รูปโปรไฟล์ <span class="optional">(ไม่บังคับ)</span></span>
          <label class="dropzone" data-dropzone>
            <input type="file" id="f-avatar" name="avatar" accept="image/jpeg,image/png,image/webp" aria-labelledby="avatar-label"<?= field_invalid($errors, 'avatar') ?>>
            <?= icon('cloud-upload', 'icon-lg') ?>
            <span class="fw-semibold">ลากรูปมาวาง หรือคลิกเพื่อเลือกไฟล์</span>
            <span class="text-caption text-muted">JPG, PNG หรือ WebP ไม่เกิน 5 MB</span>
          </label>
          <div class="upload-preview" data-preview hidden>
            <img src="" alt="รูปโปรไฟล์ที่เลือก">
            <button type="button" class="btn btn-ghost btn-sm" data-preview-remove><?= icon('x', 'icon-sm') ?>นำรูปออก</button>
          </div>
          <?= field_error($errors, 'avatar') ?>
        </div>
      </div>
      <div class="form-actions justify-content-end">
        <button type="submit" class="btn btn-primary"><?= icon('save') ?>บันทึกข้อมูล</button>
      </div>
    </form>

    <form class="card" method="post" action="<?= e(url('/profile/password')) ?>" novalidate aria-labelledby="pw-title">
      <?= csrf_field() ?>
      <h2 class="card-title mb-3" id="pw-title"><?= icon('key') ?>เปลี่ยนรหัสผ่าน</h2>
      <div class="row">
        <div class="field">
          <?= field_label('f-current_password', 'รหัสผ่านปัจจุบัน') ?>
          <input class="form-control" type="password" id="f-current_password" name="current_password" autocomplete="current-password" required<?= field_invalid($passwordErrors, 'current_password') ?>>
          <?= field_error($passwordErrors, 'current_password') ?>
        </div>
        <div class="col-md-6 field">
          <?= field_label('f-new_password', 'รหัสผ่านใหม่') ?>
          <input class="form-control" type="password" id="f-new_password" name="new_password" autocomplete="new-password" minlength="8" required aria-describedby="pw-help"<?= field_invalid($passwordErrors, 'new_password') ?>>
          <p class="form-help" id="pw-help">อย่างน้อย 8 ตัวอักษร</p>
          <?= field_error($passwordErrors, 'new_password') ?>
        </div>
        <div class="col-md-6 field">
          <?= field_label('f-confirm_password', 'ยืนยันรหัสผ่านใหม่') ?>
          <input class="form-control" type="password" id="f-confirm_password" name="confirm_password" autocomplete="new-password" required>
        </div>
      </div>
      <div class="form-actions justify-content-end">
        <button type="submit" class="btn btn-secondary"><?= icon('key') ?>เปลี่ยนรหัสผ่าน</button>
      </div>
    </form>
  </div>
</div>
