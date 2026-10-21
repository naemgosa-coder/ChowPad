<?php

use App\Services\Auth;
use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $users
 * @var array<string, string> $filters
 * @var array<int, array<string, mixed>> $departments
 * @var array<string, mixed>|null $editing
 * @var array<string, mixed>|null $form
 * @var array<string, string> $errors
 * @var int $total @var int $page @var int $pages @var int $perPage
 */
$f = static fn (string $k): string => (string) ($form[$k] ?? '');
?>
<div class="page-header">
  <div>
    <h1>ผู้ใช้งาน</h1>
    <p>จัดการบัญชี บทบาท และสังกัดของบุคลากร (<?= e(number_format($total)) ?> บัญชีตามตัวกรอง)</p>
  </div>
  <?php if ($form === null): ?>
    <div class="page-actions"><a class="btn btn-primary" href="<?= e(url('/users?new=1')) ?>"><?= icon('user-plus') ?>เพิ่มผู้ใช้งาน</a></div>
  <?php endif; ?>
</div>

<?php if ($form !== null): ?>
  <form class="card mb-4" method="post" action="<?= e(url($editing ? '/users/' . $editing['id'] : '/users')) ?>" novalidate aria-labelledby="user-form-title">
    <?= csrf_field() ?>
    <h2 class="card-title mb-3" id="user-form-title"><?= icon($editing ? 'square-pen' : 'user-plus') ?><?= $editing ? 'แก้ไขบัญชี ' . e($editing['id']) : 'เพิ่มผู้ใช้งานใหม่' ?></h2>
    <?= View::partial('partials/error-summary', ['errors' => $errors]) ?>
    <div class="row">
      <div class="col-md-4 field">
        <?= field_label('f-name', 'ชื่อ-นามสกุล') ?>
        <input class="form-control" id="f-name" name="name" value="<?= e($f('name')) ?>" required<?= field_invalid($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-username', 'ชื่อผู้ใช้ (ใช้เข้าสู่ระบบ)') ?>
        <input class="form-control" id="f-username" name="username" value="<?= e($f('username')) ?>" autocomplete="off" autocapitalize="none" required<?= field_invalid($errors, 'username') ?>>
        <?= field_error($errors, 'username') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-email', 'อีเมล') ?>
        <input class="form-control" type="email" id="f-email" name="email" value="<?= e($f('email')) ?>" required<?= field_invalid($errors, 'email') ?>>
        <?= field_error($errors, 'email') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-role', 'บทบาท') ?>
        <select class="form-select" id="f-role" name="role" required<?= field_invalid($errors, 'role') ?>>
          <?php foreach (Auth::ROLES as $role): ?>
            <option value="<?= e($role) ?>"<?= ($f('role') ?: 'Employee') === $role ? ' selected' : '' ?>><?= e(role_label($role)) ?></option>
          <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'role') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-department_id', 'สังกัด', false) ?>
        <select class="form-select" id="f-department_id" name="department_id"<?= field_invalid($errors, 'department_id') ?>>
          <option value="">ไม่ระบุ</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?= e($d['id']) ?>"<?= $f('department_id') === $d['id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?= field_error($errors, 'department_id') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-position', 'ตำแหน่ง', false) ?>
        <input class="form-control" id="f-position" name="position" value="<?= e($f('position')) ?>">
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-phone', 'เบอร์โทรศัพท์', false) ?>
        <input class="form-control" type="tel" id="f-phone" name="phone" value="<?= e($f('phone')) ?>">
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-skill', 'ความชำนาญ (สำหรับนายช่าง)', false) ?>
        <input class="form-control" id="f-skill" name="skill" value="<?= e($f('skill')) ?>">
      </div>
      <div class="col-md-4"></div>
      <div class="col-md-4 field">
        <?= field_label('f-password', $editing ? 'ตั้งรหัสผ่านใหม่' : 'รหัสผ่านเริ่มต้น', !$editing) ?>
        <input class="form-control" type="password" id="f-password" name="password" autocomplete="new-password" minlength="8" aria-describedby="pw-help"<?= field_invalid($errors, 'password') ?>>
        <p class="form-help" id="pw-help"><?= $editing ? 'เว้นว่างถ้าไม่ต้องการเปลี่ยน · ' : '' ?>อย่างน้อย 8 ตัวอักษร</p>
        <?= field_error($errors, 'password') ?>
      </div>
      <div class="col-md-4 field">
        <?= field_label('f-password_confirm', 'ยืนยันรหัสผ่าน', !$editing) ?>
        <input class="form-control" type="password" id="f-password_confirm" name="password_confirm" autocomplete="new-password">
      </div>
    </div>
    <div class="form-actions">
      <a class="btn btn-ghost" href="<?= e(url('/users')) ?>">ยกเลิก</a>
      <button type="submit" class="btn btn-primary"><?= icon('save') ?><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้งาน' ?></button>
    </div>
  </form>
<?php endif; ?>

<section class="card">
  <form class="filter-bar mb-4" method="get" action="<?= e(url('/users')) ?>" role="search" aria-label="ค้นหาผู้ใช้งาน">
    <div class="field">
      <label class="form-label" for="u-search">ค้นหา</label>
      <input class="form-control" type="search" id="u-search" name="search" value="<?= e($filters['search']) ?>" placeholder="ชื่อ ชื่อผู้ใช้ รหัส หรืออีเมล">
    </div>
    <div class="field">
      <label class="form-label" for="u-role">บทบาท</label>
      <select class="form-select" id="u-role" name="role" data-autosubmit>
        <option value="">ทุกบทบาท</option>
        <?php foreach (Auth::ROLES as $role): ?>
          <option value="<?= e($role) ?>"<?= $filters['role'] === $role ? ' selected' : '' ?>><?= e(role_label($role)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="form-label" for="u-dept">สังกัด</label>
      <select class="form-select" id="u-dept" name="department" data-autosubmit>
        <option value="">ทุกสังกัด</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= e($d['id']) ?>"<?= $filters['department'] === $d['id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>ค้นหา</button>
    </div>
  </form>

  <?php if (!$users): ?>
    <div class="empty-state"><?= icon('search-x', 'icon-xl') ?><strong>ไม่พบผู้ใช้งานตามเงื่อนไข</strong></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead><tr><th scope="col">รหัส</th><th scope="col">ชื่อ-นามสกุล</th><th scope="col">ชื่อผู้ใช้</th><th scope="col">บทบาท</th><th scope="col">สังกัด</th><th scope="col">อีเมล</th><th scope="col">สถานะ</th><th scope="col">การจัดการ</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td data-label="รหัส" class="nowrap"><?= e($u['id']) ?></td>
              <td data-label="ชื่อ"><?= e($u['name']) ?></td>
              <td data-label="ชื่อผู้ใช้"><?= e($u['username']) ?></td>
              <td data-label="บทบาท"><?= e(role_label($u['role'])) ?></td>
              <td data-label="สังกัด"><?= e($u['department_name'] ?? '-') ?></td>
              <td data-label="อีเมล"><?= e($u['email']) ?></td>
              <td data-label="สถานะ"><?= $u['status'] === 'Active'
                  ? '<span class="badge badge-success">' . icon('circle-check') . 'ใช้งาน</span>'
                  : '<span class="badge badge-neutral">' . icon('user-x') . 'ระงับ</span>' ?></td>
              <td data-label="การจัดการ">
                <div class="d-flex gap-2">
                  <a class="btn btn-secondary btn-sm" href="<?= e(url('/users') . query_with(['edit' => $u['id'], 'new' => null])) ?>"><?= icon('square-pen', 'icon-sm') ?>แก้ไข</a>
                  <?php if ($u['id'] !== Auth::id()): ?>
                    <form method="post" action="<?= e(url('/users/' . $u['id'] . '/toggle')) ?>"
                          data-confirm="<?= $u['status'] === 'Active' ? 'ระงับบัญชีนี้หรือไม่?' : 'เปิดใช้บัญชีนี้หรือไม่?' ?>"
                          data-confirm-text="<?= e($u['name']) ?> <?= $u['status'] === 'Active' ? 'จะเข้าสู่ระบบไม่ได้จนกว่าจะเปิดใช้อีกครั้ง' : 'จะเข้าสู่ระบบได้อีกครั้ง' ?>"
                          data-confirm-button="<?= $u['status'] === 'Active' ? 'ระงับบัญชี' : 'เปิดใช้บัญชี' ?>"<?= $u['status'] === 'Active' ? ' data-confirm-danger="1"' : '' ?>>
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-sm <?= $u['status'] === 'Active' ? 'btn-ghost-danger' : 'btn-ghost' ?>"><?= icon($u['status'] === 'Active' ? 'user-x' : 'user-check', 'icon-sm') ?><?= $u['status'] === 'Active' ? 'ระงับ' : 'เปิดใช้' ?></button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::partial('partials/pagination', compact('page', 'pages', 'total', 'perPage')) ?>
  <?php endif; ?>
</section>
