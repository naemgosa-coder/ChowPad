<?php /** @var int $status @var string $message */ ?>
<section class="card error-card">
  <p class="error-code"><?= e($status) ?></p>
  <h1 class="h2"><?= e($message) ?></h1>
  <a class="btn btn-primary" href="<?= e(url('/')) ?>">กลับหน้าหลัก</a>
</section>
