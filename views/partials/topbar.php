<?php

use App\Models\Notification;

$user = auth_user();
$unread = Notification::unreadCount($user['id'], $user['role']);
?>
<header class="app-topbar">
  <div class="topbar-left">
    <button type="button" class="topbar-icon-btn" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="true" title="แสดง/ซ่อนเมนู">
      <?= icon('menu', 'icon-lg') ?><span class="visually-hidden">แสดงหรือซ่อนเมนู</span>
    </button>
    <a class="brand" href="<?= e(url('/dashboard')) ?>">
      <span class="brand-mark"><?= icon('building-2', 'icon-lg') ?></span>
      <span class="brand-text">
        <span class="brand-name">ศาลากลางจังหวัด</span>
        <span class="brand-sub">ระบบจองห้องประชุมและแจ้งซ่อม</span>
      </span>
    </a>
  </div>

  <div class="topbar-right">
    <a class="topbar-icon-btn" href="<?= e(url('/notifications')) ?>" title="การแจ้งเตือน">
      <?= icon('bell', 'icon-lg') ?>
      <span class="visually-hidden">การแจ้งเตือน<?= $unread > 0 ? " ยังไม่อ่าน {$unread} รายการ" : '' ?></span>
      <?php if ($unread > 0): ?>
        <span class="topbar-badge" aria-hidden="true"><?= e($unread > 99 ? '99+' : $unread) ?></span>
      <?php endif; ?>
    </a>
    <a class="topbar-user" href="<?= e(url('/profile')) ?>" title="โปรไฟล์ของฉัน">
      <?php if (!empty($user['avatar'])): ?>
        <img class="avatar" src="<?= e($user['avatar']) ?>" alt="" width="36" height="36">
      <?php else: ?>
        <span class="avatar" aria-hidden="true"><?= e(initials($user['name'])) ?></span>
      <?php endif; ?>
      <span class="topbar-user-info">
        <span class="topbar-user-name"><?= e($user['name']) ?></span>
        <span class="topbar-user-role"><?= e(role_label($user['role'])) ?></span>
      </span>
    </a>
  </div>
</header>
