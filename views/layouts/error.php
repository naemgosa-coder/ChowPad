<?php

use App\Services\View;

/** @var string $content @var string $title */
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?= View::partial('partials/head', ['title' => $title ?? '']) ?>
</head>
<body class="page-center">
  <main id="main"><?= $content ?></main>
</body>
</html>
