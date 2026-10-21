<?php

use App\Services\View;

/** @var string $content @var string $title */
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?= View::partial('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="auth-body">
  <main id="main" class="w-100"><?= $content ?></main>
  <?= View::partial('partials/ui') ?>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
