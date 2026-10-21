<?php // ส่วนประกอบที่ public/js/app.js ใช้: พื้นที่ toast และ dialog ยืนยัน (แทน SweetAlert2 เดิม) ?>
<div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="false"></div>

<dialog class="dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-text">
  <form method="dialog">
    <div class="dialog-header">
      <h2 id="confirm-dialog-title"></h2>
      <button type="submit" value="cancel" class="icon-btn" aria-label="ปิด"><?= icon('x') ?></button>
    </div>
    <div class="dialog-body">
      <p id="confirm-dialog-text"></p>
    </div>
    <div class="dialog-footer">
      <button type="submit" value="cancel" class="btn btn-ghost" data-confirm-cancel autofocus>ยกเลิก</button>
      <button type="submit" value="ok" class="btn btn-primary" data-confirm-ok>ยืนยัน</button>
    </div>
  </form>
</dialog>
