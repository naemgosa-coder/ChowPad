<?php // dialog ระบุเหตุผลไม่อนุมัติ ใช้คู่กับปุ่ม [data-reject] และ public/js/approval.js ?>
<dialog class="dialog" id="reject-dialog" aria-labelledby="reject-dialog-title">
  <form method="post" action="" id="reject-form" novalidate data-busy-text="กำลังบันทึก…">
    <?= csrf_field() ?>
    <input type="hidden" name="return_to" value="">
    <div class="dialog-header">
      <h2 id="reject-dialog-title">ไม่อนุมัติการจอง <span data-reject-id></span></h2>
      <button type="button" class="icon-btn" data-close aria-label="ปิด"><?= icon('x') ?></button>
    </div>
    <div class="dialog-body">
      <div class="field">
        <label class="form-label" for="reject-reason">เหตุผลที่ไม่อนุมัติ <span class="required" aria-hidden="true">*</span></label>
        <textarea class="form-control" id="reject-reason" name="reason" maxlength="500" rows="3" required aria-describedby="reject-help"></textarea>
        <p class="form-help" id="reject-help">ผู้จองจะเห็นเหตุผลนี้ เช่น ห้องไม่เหมาะกับจำนวนผู้เข้าร่วม หรือมีภารกิจเร่งด่วนของผู้บริหาร</p>
        <p class="form-error" id="reject-error" hidden><?= icon('circle-alert') ?>กรุณาระบุเหตุผลที่ไม่อนุมัติ</p>
      </div>
    </div>
    <div class="dialog-footer">
      <button type="button" class="btn btn-ghost" data-close>ยกเลิก</button>
      <button type="submit" class="btn btn-danger"><?= icon('x') ?>ยืนยันไม่อนุมัติ</button>
    </div>
  </form>
</dialog>
