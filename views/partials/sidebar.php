<?php

use App\Services\Auth;

$current = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$menu = config('menu');

// เมนูที่ตรงกับ URL ปัจจุบันคือ path ที่ยาวที่สุดที่เป็นต้นทางของ URL (เช่น /bookings/approval ชนะ /bookings)
$active = '';
foreach ($menu as $items) {
    foreach ($items as [$path]) {
        if (($current === $path || str_starts_with($current, $path . '/')) && strlen($path) > strlen($active)) {
            $active = $path;
        }
    }
}
?>
<nav class="app-sidebar" id="app-sidebar" aria-label="เมนูหลัก">
  <div class="sidebar-scroller">
    <?php foreach ($menu as $group => $items): ?>
      <?php $visible = array_filter($items, static fn ($item) => $item[3] === null || Auth::hasRole(...$item[3])); ?>
      <?php if ($visible): ?>
        <div class="sidebar-group">
          <p class="sidebar-group-title"><?= e($group) ?></p>
          <ul class="sidebar-menu">
            <?php foreach ($visible as [$path, $label, $iconName]): ?>
              <li>
                <a class="sidebar-link" href="<?= e(url($path)) ?>" title="<?= e($label) ?>"<?= $path === $active ? ' aria-current="page"' : '' ?>>
                  <?= icon($iconName) ?><span class="sidebar-label"><?= e($label) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="sidebar-footer">
    <form method="post" action="<?= e(url('/logout')) ?>"
          data-confirm="ต้องการออกจากระบบหรือไม่?"
          data-confirm-text="ระบบจะพาคุณกลับไปยังหน้าเข้าสู่ระบบ"
          data-confirm-button="ออกจากระบบ">
      <?= csrf_field() ?>
      <button type="submit" class="sidebar-link" title="ออกจากระบบ">
        <?= icon('log-out') ?><span class="sidebar-label">ออกจากระบบ</span>
      </button>
    </form>
  </div>
</nav>
<div class="sidebar-backdrop" data-sidebar-close hidden></div>
