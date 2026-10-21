<?php
/** @var array<string, string> $info @var array<string, int> $counts @var string[] $warnings */
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>ข้อมูลระบบ</h1>
  </div>
</div>

<?php foreach ($warnings as $w): ?>
  <div class="alert alert-warning mb-3" role="status"><?= icon('triangle-alert') ?><span><?= e($w) ?></span></div>
<?php endforeach; ?>

<div class="row g-4">
  <section class="col-lg-6 card" aria-labelledby="server-title">
    <h2 class="card-title mb-3" id="server-title"><?= icon('server') ?>เซิร์ฟเวอร์และซอฟต์แวร์</h2>
    <dl class="detail-list">
      <?php foreach ($info as $label => $value): ?>
        <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </section>
  <section class="col-lg-6 card" aria-labelledby="db-title">
    <h2 class="card-title mb-3" id="db-title"><?= icon('database') ?>จำนวนข้อมูลในตาราง</h2>
    <dl class="detail-list">
      <?php foreach ($counts as $table => $n): ?>
        <div><dt><code><?= e($table) ?></code></dt><dd class="tabular"><?= e(number_format($n)) ?> แถว</dd></div>
      <?php endforeach; ?>
    </dl>
  </section>
</div>
