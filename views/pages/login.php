<?php /** @var string|null $error @var string|null $username */ ?>
<div class="login">
  <aside class="login-brand" aria-label="เกี่ยวกับระบบ">
    <div class="login-brand-inner">
      <div class="login-emblem">
        <span class="login-emblem-mark"><?= icon('building-2', 'icon-lg') ?></span>
        <span class="login-emblem-text">
          <span class="login-emblem-org">ศาลากลางจังหวัด</span>
          <span class="login-emblem-sub">ระบบบริการภายในสำนักงาน</span>
        </span>
      </div>

      <div class="login-hero">
        <p class="login-eyebrow">ระบบจองห้องประชุมแบบเรียลไทม์</p>
        <h2 class="login-headline">จองห้อง แจ้งซ่อม ลงเวลา<br>ครบในที่เดียว</h2>
        <p class="login-lead">ตรวจสอบห้องว่างได้ทันที ส่งคำขอและติดตามผลการอนุมัติ โดยไม่ต้องกรอกเอกสาร</p>
      </div>

      <ul class="login-features list-unstyled">
        <li>
          <span class="login-feature-icon"><?= icon('calendar-check') ?></span>
          <span><strong>เห็นห้องว่างแบบเรียลไทม์</strong>สถานะห้องอัปเดตอัตโนมัติ และป้องกันการจองเวลาชนกัน</span>
        </li>
        <li>
          <span class="login-feature-icon"><?= icon('wrench') ?></span>
          <span><strong>แจ้งซ่อมพร้อมรูปถ่าย</strong>ติดตามสถานะงานได้ตั้งแต่ส่งเรื่องจนช่างปิดงาน</span>
        </li>
        <li>
          <span class="login-feature-icon"><?= icon('clock') ?></span>
          <span><strong>ลงเวลาและยื่นใบลาออนไลน์</strong>ตรวจสิทธิ์วันลาคงเหลือตามปีงบประมาณได้ทันที</span>
        </li>
      </ul>

      <p class="login-brand-foot">
        <?= icon('shield-check', 'icon-sm') ?>
        สำหรับบุคลากรที่ได้รับอนุญาตเท่านั้น ทุกการใช้งานมีการบันทึกประวัติ
      </p>
    </div>
  </aside>

  <main class="login-main" id="main">
    <div class="login-panel">
      <header class="login-header">
        <h1 id="login-title">เข้าสู่ระบบ</h1>
        <p>ใช้ชื่อผู้ใช้หรืออีเมลของหน่วยงาน</p>
      </header>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert" id="login-error" tabindex="-1">
          <?= icon('circle-alert') ?><span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= e(url('/login')) ?>" novalidate aria-labelledby="login-title" data-busy-text="กำลังเข้าสู่ระบบ…">
        <?= csrf_field() ?>
        <div class="field">
          <label class="form-label" for="login-username">ชื่อผู้ใช้งานหรืออีเมล</label>
          <div class="input-icon">
            <?= icon('user') ?>
            <input class="form-control form-control-lg" type="text" id="login-username" name="username" value="<?= e($username ?? '') ?>"
                   autocomplete="username" autocapitalize="none" spellcheck="false" required<?= empty($error) ? ' autofocus' : '' ?>
                   <?= !empty($error) ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>>
          </div>
        </div>

        <div class="field">
          <label class="form-label" for="login-password">รหัสผ่าน</label>
          <div class="input-icon input-action">
            <?= icon('lock') ?>
            <input class="form-control form-control-lg" type="password" id="login-password" name="password"
                   autocomplete="current-password" required
                   aria-describedby="caps-warning<?= !empty($error) ? ' login-error' : '' ?>"<?= !empty($error) ? ' aria-invalid="true"' : '' ?>>
            <button type="button" class="input-action-btn" data-toggle-password="login-password" aria-pressed="false" aria-label="แสดงรหัสผ่าน">
              <?= icon('eye') ?>
            </button>
          </div>
          <p class="form-error" id="caps-warning" aria-live="polite" hidden><?= icon('triangle-alert') ?>เปิด Caps Lock อยู่</p>
          <details class="login-forgot">
            <summary>ลืมรหัสผ่าน?</summary>
            <p class="login-forgot-panel"><?= icon('info', 'icon-sm') ?>กรุณาติดต่อผู้ดูแลระบบ แผนกเทคโนโลยีสารสนเทศ เพื่อตั้งรหัสผ่านใหม่</p>
          </details>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('log-in') ?>เข้าสู่ระบบ</button>
      </form>

      <?php if (config('app.env') === 'local'): ?>
        <details class="login-demo">
          <summary>
            <span><?= icon('key', 'icon-sm') ?>บัญชีทดสอบ</span>
            <span class="text-caption text-muted">แสดงเฉพาะเครื่องพัฒนา</span>
          </summary>
          <p class="text-caption text-muted">กดเพื่อกรอกอัตโนมัติ รหัสผ่านเหมือนชื่อผู้ใช้</p>
          <ul class="login-demo-list list-unstyled">
            <?php foreach ([
                ['admin', 'ผู้ดูแลระบบ', 'shield-check'],
                ['officer', 'เจ้าหน้าที่ฝ่ายบริหาร', 'square-check'],
                ['manager', 'ผู้บริหาร', 'briefcase'],
                ['technician', 'นายช่างซ่อมบำรุง', 'wrench'],
                ['employee', 'บุคลากร', 'user'],
            ] as [$demoUser, $demoRole, $demoIcon]): ?>
              <li>
                <button type="button" class="login-demo-account" data-demo-login="<?= e($demoUser) ?>">
                  <?= icon($demoIcon, 'icon-sm') ?>
                  <span><strong><?= e($demoUser) ?></strong><?= e($demoRole) ?></span>
                </button>
              </li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>

      <footer class="login-footer">
        <?= e(config('app.name')) ?> · <?= e((int) date('Y') + 543) ?>
      </footer>
    </div>
  </main>
</div>
