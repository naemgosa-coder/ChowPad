<?php

use App\Services\View;

/** @var string $content @var string $title */
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?= View::partial('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="auth-page">
  <a class="skip-link" href="#main">ข้ามไปยังฟอร์มเข้าสู่ระบบ</a>
  <?= $content ?>
  <?= View::partial('partials/ui') ?>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
