<?php

use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $departments
 * @var array<string, mixed>|null $editing
 * @var array<string, mixed>|null $form
 * @var array<string, string> $errors
 */
?>
<div class="page-header">
  <div>
    <h1>แผนก / กอง</h1>
    <p>โครงสร้างหน่วยงานและจำนวนบุคลากรในสังกัด</p>
  </div>
  <?php if ($form === null): ?>
    <div class="page-actions"><a class="btn btn-primary" href="<?= e(url('/departments?new=1')) ?>"><?= icon('circle-plus') ?>เพิ่มแผนก</a></div>
  <?php endif; ?>
</div>

<?php if ($form !== null): ?>
  <form class="card mb-4" method="post" action="<?= e(url($editing ? '/departments/' . $editing['id'] : '/departments')) ?>" novalidate aria-labelledby="dept-form-title">
    <?= csrf_field() ?>
    <h2 class="card-title mb-3" id="dept-form-title"><?= icon('network') ?><?= $editing ? 'แก้ไข ' . e($editing['id']) : 'เพิ่มแผนก / กอง' ?></h2>
    <?= View::partial('partials/error-summary', ['errors' => $errors]) ?>
    <div class="row">
      <div class="col-md-8 field">
        <?= field_label('f-name', 'ชื่อกอง / ฝ่าย / แผนก') ?>
        <input class="form-control" id="f-name" name="name" value="<?= e($form['name'] ?? '') ?>" required<?= field_invalid($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-code', 'รหัสย่อ') ?>
        <input class="form-control" id="f-code" name="code" value="<?= e($form['code'] ?? '') ?>" maxlength="20" required aria-describedby="code-help"<?= field_invalid($errors, 'code') ?>>
        <p class="form-help" id="code-help">ภาษาอังกฤษหรือตัวเลข เช่น GEN, IT</p>
        <?= field_error($errors, 'code') ?>
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-ghost" href="<?= e(url('/departments')) ?>">ยกเลิก</a>
      <button type="submit" class="btn btn-primary"><?= icon('save') ?>บันทึก</button>
    </div>
  </form>
<?php endif; ?>

<section class="card">
  <div class="table-responsive">
    <table class="table table-cards">
      <thead><tr><th scope="col">รหัส</th><th scope="col">รหัสย่อ</th><th scope="col">ชื่อกอง / ฝ่าย</th><th scope="col" class="num">บุคลากร (ใช้งาน)</th><th scope="col">การจัดการ</th></tr></thead>
      <tbody>
        <?php foreach ($departments as $d): ?>
          <tr>
            <td data-label="รหัส" class="nowrap"><?= e($d['id']) ?></td>
            <td data-label="รหัสย่อ"><span class="chip"><?= e($d['code']) ?></span></td>
            <td data-label="ชื่อ"><a href="<?= e(url('/users?department=' . urlencode($d['id']))) ?>"><?= e($d['name']) ?></a></td>
            <td data-label="บุคลากร" class="num"><?= e((int) $d['members']) ?> คน<?= (int) $d['members'] !== (int) $d['active_members'] ? ' <span class="text-muted">(' . e((int) $d['active_members']) . ')</span>' : '' ?></td>
            <td data-label="การจัดการ">
              <div class="d-flex gap-2">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/departments?edit=' . urlencode($d['id']))) ?>"><?= icon('square-pen', 'icon-sm') ?>แก้ไข</a>
                <?php if ((int) $d['members'] === 0): ?>
                  <form method="post" action="<?= e(url('/departments/' . $d['id'] . '/delete')) ?>"
                        data-confirm="ลบ<?= e($d['name']) ?>หรือไม่?" data-confirm-text="ลบแล้วกู้คืนไม่ได้" data-confirm-button="ลบแผนก" data-confirm-danger="1">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost-danger btn-sm"><?= icon('trash-2', 'icon-sm') ?>ลบ</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
