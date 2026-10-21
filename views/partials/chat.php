<?php

use App\Services\Chat\ClaudeChat;

// ผู้ช่วยตอบคำถาม (public/js/chat.js) แสดงทุกหน้าที่ล็อกอินแล้ว
$ai = ClaudeChat::enabled();
$suggestions = ['ห้องไหนว่างตอนนี้', 'การจองของฉัน', 'วันลาเหลือเท่าไร', 'สถานะแจ้งซ่อมของฉัน'];
if (App\Services\Auth::hasRole(...config('roles.approvers'))) {
    $suggestions[] = 'มีอะไรรออนุมัติ';
}
?>
<div class="chat no-print" data-chat>
  <button type="button" class="chat-fab" data-chat-toggle aria-expanded="false" aria-controls="chat-panel">
    <?= icon('message-circle', 'icon-lg') ?><span class="visually-hidden">เปิดผู้ช่วยตอบคำถาม</span>
  </button>

  <section class="chat-panel" id="chat-panel" role="dialog" aria-labelledby="chat-title" hidden>
    <header class="chat-header">
      <span class="chat-avatar" aria-hidden="true"><?= icon('bot') ?></span>
      <div class="chat-heading">
        <h2 id="chat-title">ผู้ช่วยตอบคำถาม</h2>
        <p><?= $ai ? 'ตอบจากข้อมูลในระบบ · มี AI ช่วยตอบ' : 'ตอบจากข้อมูลในระบบ' ?></p>
      </div>
      <button type="button" class="chat-icon-btn" data-chat-reset aria-label="เริ่มบทสนทนาใหม่" title="เริ่มบทสนทนาใหม่"><?= icon('rotate-cw') ?></button>
      <button type="button" class="chat-icon-btn" data-chat-close aria-label="ปิดผู้ช่วย" title="ปิด"><?= icon('x') ?></button>
    </header>

    <div class="chat-log" data-chat-log role="log" aria-live="polite" aria-label="บทสนทนา"></div>

    <div class="chat-suggestions" data-chat-suggestions>
      <?php foreach ($suggestions as $s): ?>
        <button type="button" class="chat-chip" data-chat-ask="<?= e($s) ?>"><?= e($s) ?></button>
      <?php endforeach; ?>
    </div>

    <form class="chat-form" data-chat-form>
      <label class="visually-hidden" for="chat-input">พิมพ์คำถาม</label>
      <textarea id="chat-input" class="chat-input" rows="1" maxlength="500" placeholder="ถามเกี่ยวกับการจอง งานซ่อม การลา…" required></textarea>
      <button type="submit" class="chat-send" aria-label="ส่งคำถาม"><?= icon('send') ?></button>
    </form>
    <p class="chat-note"><?= $ai ? 'คำตอบจาก AI อาจคลาดเคลื่อน ตรวจสอบกับหน้าข้อมูลจริงก่อนใช้' : 'กด Enter เพื่อส่ง · Shift+Enter ขึ้นบรรทัดใหม่' ?></p>
  </section>
</div>
