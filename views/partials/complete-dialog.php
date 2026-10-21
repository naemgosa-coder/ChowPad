<?php

use App\Services\ImageUpload;

// dialog บันทึกปิดงานซ่อม ใช้คู่กับปุ่ม [data-complete] ใน public/js/maintenance.js
?>
<dialog class="dialog dialog-wide" id="complete-dialog" aria-labelledby="complete-dialog-title">
  <form method="post" action="" enctype="multipart/form-data" novalidate data-busy-text="กำลังบันทึก…">
    <?= csrf_field() ?>
    <input type="hidden" name="return_to" value="">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= e(ImageUpload::MAX_BYTES) ?>">
    <div class="dialog-header">
      <h2 id="complete-dialog-title">รายงานปิดงานซ่อม <span data-complete-id></span></h2>
      <button type="button" class="icon-btn" data-close aria-label="ปิด"><?= icon('x') ?></button>
    </div>
    <div class="dialog-body">
      <div class="row">
        <div class="field">
          <label class="form-label" for="c-notes">รายละเอียดการซ่อม <span class="required" aria-hidden="true">*</span></label>
          <textarea class="form-control" id="c-notes" name="repair_notes" rows="3" maxlength="2000" required></textarea>
        </div>
        <div class="field">
          <label class="form-label" for="c-materials">อะไหล่/วัสดุที่ใช้ <span class="optional">(ไม่บังคับ)</span></label>
          <input class="form-control" id="c-materials" name="material_used" maxlength="1000">
        </div>
        <div class="col-sm-6 field">
          <label class="form-label" for="c-material-cost">ค่าวัสดุ (บาท)</label>
          <input class="form-control" type="number" id="c-material-cost" name="material_cost" min="0" step="0.01" value="0" inputmode="decimal">
        </div>
        <div class="col-sm-6 field">
          <label class="form-label" for="c-labor-cost">ค่าแรง (บาท)</label>
          <input class="form-control" type="number" id="c-labor-cost" name="labor_cost" min="0" step="0.01" value="0" inputmode="decimal">
        </div>
        <div class="field">
          <label class="form-label" for="c-after-image">รูปหลังซ่อม <span class="optional">(ไม่บังคับ)</span></label>
          <input class="form-control" type="file" id="c-after-image" name="after_image" accept="image/jpeg,image/png,image/webp" aria-describedby="c-after-help">
          <p class="form-help" id="c-after-help">JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB</p>
        </div>
      </div>
      <p class="form-error mt-2" data-complete-error hidden></p>
    </div>
    <div class="dialog-footer">
      <button type="button" class="btn btn-ghost" data-close>ยกเลิก</button>
      <button type="submit" class="btn btn-primary"><?= icon('save') ?>บันทึกปิดงาน</button>
    </div>
  </form>
</dialog>
