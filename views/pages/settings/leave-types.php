<?php
/** @var array<int, array<string, mixed>> $types */
$levels = ['Supervisor' => 'หัวหน้างาน', 'Department Head' => 'ผู้อำนวยการกอง', 'Executive' => 'ผู้บริหารจังหวัด'];
?>
<div class="page-header">
  <div>
    <p><a href="<?= e(url('/settings')) ?>"><?= icon('chevron-left', 'icon-sm') ?>ตั้งค่าระบบ</a></p>
    <h1>ประเภทและโควตาการลา</h1>
    <p>โควตานับต่อปีงบประมาณ (1 ต.ค. – 30 ก.ย.) เฉพาะวันทำการ</p>
  </div>
</div>

<section class="card">
  <div class="table-responsive">
    <table class="table table-cards">
      <thead><tr><th scope="col">ประเภทการลา</th><th scope="col">โควตาต่อปี (วัน)</th><th scope="col">ระดับผู้อนุมัติ</th><th scope="col"><span class="visually-hidden">บันทึก</span></th></tr></thead>
      <tbody>
        <?php foreach ($types as $t): ?>
          <tr>
            <td data-label="ประเภท" class="fw-semibold"><?= e($t['name']) ?></td>
            <td data-label="โควตา">
              <label class="visually-hidden" for="q-<?= e($t['id']) ?>">โควตา<?= e($t['name']) ?></label>
              <input class="form-control quota-input" type="number" min="0" max="365" id="q-<?= e($t['id']) ?>" name="quota_days" value="<?= e($t['quota_days']) ?>" form="lt-<?= e($t['id']) ?>" required>
            </td>
            <td data-label="ผู้อนุมัติ">
              <label class="visually-hidden" for="a-<?= e($t['id']) ?>">ผู้อนุมัติ<?= e($t['name']) ?></label>
              <select class="form-select" id="a-<?= e($t['id']) ?>" name="approval_level" form="lt-<?= e($t['id']) ?>">
                <?php foreach ($levels as $value => $label): ?>
                  <option value="<?= e($value) ?>"<?= $t['approval_level'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td data-label="">
              <form method="post" action="<?= e(url('/settings/leave-types/' . $t['id'])) ?>" id="lt-<?= e($t['id']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-sm"><?= icon('save', 'icon-sm') ?>บันทึก</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="form-help mt-3">ระดับผู้อนุมัติใช้เป็นข้อมูลประกอบ ปัจจุบันผู้ที่อนุมัติใบลาได้คือผู้ดูแลระบบ เจ้าหน้าที่ฝ่ายบริหาร และผู้บริหาร</p>
</section>
