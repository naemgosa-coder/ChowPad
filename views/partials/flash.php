<?php

$messages = pull_flash();
$icons = ['success' => 'circle-check', 'warning' => 'triangle-alert', 'danger' => 'circle-alert', 'info' => 'info'];
?>
<?php if ($messages): ?>
  <div class="flash-stack" role="status">
    <?php foreach ($messages as $flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>">
        <?= icon($icons[$flash['type']] ?? 'info') ?>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
