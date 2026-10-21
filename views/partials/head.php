<?php /** @var string $title */ ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title ?? '') ?> - <?= e(config('app.name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap">
<?php foreach (['tokens', 'base', 'layout', 'components', 'utilities'] as $sheet): ?>
<link rel="stylesheet" href="<?= e(asset("css/{$sheet}.css")) ?>">
<?php endforeach; ?>
