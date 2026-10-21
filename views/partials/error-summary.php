<?php
/**
 * กล่องสรุปข้อผิดพลาดด้านบนฟอร์ม ลิงก์ไปยังช่องที่มี id="f-<ชื่อช่อง>" (DESIGN_SYSTEM 7.7)
 *
 * @var array<string, string> $errors
 */
?>
<?php if ($errors): ?>
  <div class="alert alert-danger mb-4" role="alert" tabindex="-1" data-focus-on-load>
    <?= icon('circle-alert') ?>
    <div>
      <strong>ยังบันทึกไม่ได้ กรุณาแก้ไข <?= e(count($errors)) ?> รายการ</strong>
      <ul class="mt-1">
        <?php foreach ($errors as $field => $message): ?>
          <li><a href="#f-<?= e($field) ?>"><?= e($message) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>
