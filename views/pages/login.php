<?php /** @var string|null $error @var string|null $username */ ?>
<div class="auth-grid">
  <section class="card auth-card" aria-labelledby="login-title">
    <div class="auth-brand">
      <span class="brand-mark"><?= icon('building-2', 'icon-lg') ?></span>
      <h1 id="login-title" class="h2">ศาลากลางจังหวัด</h1>
      <p>ระบบจองห้องประชุมและบริการซ่อมบำรุง</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger mb-3" role="alert" id="login-error" tabindex="-1">
        <?= icon('circle-alert') ?><span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate data-busy-text="กำลังเข้าสู่ระบบ…">
      <?= csrf_field() ?>
      <div class="field">
        <label class="form-label" for="login-username">ชื่อผู้ใช้งานหรืออีเมล</label>
        <div class="input-icon">
          <?= icon('user') ?>
          <input class="form-control" type="text" id="login-username" name="username" value="<?= e($username ?? '') ?>"
                 autocomplete="username" required autofocus
                 <?= !empty($error) ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>>
        </div>
      </div>
      <div class="field">
        <label class="form-label" for="login-password">รหัสผ่าน</label>
        <div class="input-icon">
          <?= icon('lock') ?>
          <input class="form-control" type="password" id="login-password" name="password"
                 autocomplete="current-password" required
                 <?= !empty($error) ? 'aria-invalid="true" aria-describedby="login-error"' : '' ?>>
        </div>
      </div>

      <details class="auth-help mt-2">
        <summary>ลืมรหัสผ่าน?</summary>
        <p>กรุณาติดต่อผู้ดูแลระบบจังหวัด ณ แผนกเทคโนโลยีสารสนเทศ เพื่อตั้งรหัสผ่านใหม่</p>
      </details>

      <button type="submit" class="btn btn-primary btn-block mt-3">
        <?= icon('log-in') ?> เข้าสู่ระบบ
      </button>
    </form>

    <?php if (config('app.env') === 'local'): ?>
      <div class="demo-accounts">
        <p class="demo-accounts-title">บัญชีทดสอบ (แสดงเฉพาะเครื่องพัฒนา) รหัสผ่านเหมือนชื่อผู้ใช้</p>
        <div class="row g-2">
          <?php foreach ([
              ['admin', 'ผู้ดูแลระบบ'],
              ['officer', 'เจ้าหน้าที่ฝ่ายบริหาร'],
              ['technician', 'นายช่างซ่อมบำรุง'],
              ['manager', 'ผู้บริหาร'],
              ['employee', 'บุคลากร'],
          ] as [$demoUser, $demoRole]): ?>
            <div class="col-6">
              <button type="button" class="demo-account" data-demo-login="<?= e($demoUser) ?>">
                <strong><?= e($demoUser) ?></strong><span><?= e($demoRole) ?></span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <aside class="card auth-aside d-none d-lg-block" aria-hidden="true">
    <svg width="240" height="200" viewBox="0 0 240 200">
      <rect x="10" y="30" width="220" height="150" rx="12" style="fill:var(--color-primary-800)"/>
      <rect x="30" y="50" width="180" height="110" rx="8" style="fill:var(--color-primary-900)"/>
      <path d="M 50 130 L 80 90 L 110 110 L 140 70 L 170 100 L 190 60" fill="none" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" style="stroke:var(--color-accent-500)"/>
      <circle cx="80" cy="90" r="5" style="fill:var(--color-success-200)"/>
      <circle cx="140" cy="70" r="5" style="fill:var(--color-warning-200)"/>
      <circle cx="190" cy="60" r="6" style="fill:var(--color-white)"/>
    </svg>
    <h2>ระบบบริหารงานยุคดิจิทัล</h2>
    <p>จองห้องประชุม ตรวจสอบตารางการใช้ห้อง และแจ้งซ่อมแซมวัสดุอุปกรณ์อย่างรวดเร็ว ผ่านกระบวนการอนุมัติและจัดสรรคิวงานในที่เดียว</p>
  </aside>
</div>
