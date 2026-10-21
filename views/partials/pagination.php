<?php
/** @var int $page @var int $pages @var int $total @var int $perPage */
$from = $total ? ($page - 1) * $perPage + 1 : 0;
$to = min($page * $perPage, $total);
// แสดงเลขหน้ารอบหน้าปัจจุบัน แทนการแสดงทุกหน้า (ของเดิมแสดง 30 ปุ่ม)
$window = array_unique(array_filter(
    [1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $pages],
    static fn ($p) => $p >= 1 && $p <= $pages
));
sort($window);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
  <p class="text-sm text-muted">
    <?= $total ? 'แสดง ' . e($from) . '–' . e($to) . ' จากทั้งหมด ' . e(number_format($total)) . ' รายการ' : 'ไม่มีข้อมูล' ?>
  </p>
  <?php if ($pages > 1): ?>
    <nav aria-label="เลือกหน้า">
      <ul class="pagination">
        <?php if ($page > 1): ?>
          <li><a class="page-link" href="<?= e(query_with(['page' => $page - 1])) ?>"><?= icon('chevron-left', 'icon-sm') ?><span class="visually-hidden">หน้าก่อนหน้า</span></a></li>
        <?php endif; ?>
        <?php $prev = 0; foreach ($window as $p): ?>
          <?php if ($p - $prev > 1): ?><li><span class="page-link" aria-hidden="true">…</span></li><?php endif; ?>
          <li><a class="page-link" href="<?= e(query_with(['page' => $p])) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= e($p) ?></a></li>
        <?php $prev = $p; endforeach; ?>
        <?php if ($page < $pages): ?>
          <li><a class="page-link" href="<?= e(query_with(['page' => $page + 1])) ?>"><?= icon('chevron-right', 'icon-sm') ?><span class="visually-hidden">หน้าถัดไป</span></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>
