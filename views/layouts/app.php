<?php

use App\Services\View;

/** @var string $content @var string $title */
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?= View::partial('partials/head', ['title' => $title ?? '']) ?>
  <script>
    // คืนสถานะเมนูย่อก่อนวาดหน้า กันหน้ากระพริบ
    try { if (localStorage.getItem('sidebar_collapsed') === '1') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}
  </script>
</head>
<body>
  <a class="skip-link" href="#main">ข้ามไปยังเนื้อหาหลัก</a>
  <?= View::partial('partials/topbar') ?>
  <div class="app-shell">
    <?= View::partial('partials/sidebar') ?>
    <main class="app-main" id="main" tabindex="-1">
      <div class="app-content">
        <?= View::partial('partials/flash') ?>
        <?= $content ?>
      </div>
      <?= View::partial('partials/footer') ?>
    </main>
  </div>
  <?= View::partial('partials/ui') ?>
  <?= View::partial('partials/chat') ?>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
  <script src="<?= e(asset('js/chat.js')) ?>"></script>
  <?php foreach ($scripts ?? [] as $script): ?>
    <script src="<?= e(str_starts_with($script, 'https://') ? $script : asset($script)) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
