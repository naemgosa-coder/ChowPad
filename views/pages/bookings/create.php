<?php
/**
 * @var array<string, mixed> $old
 * @var array<string, string> $errors
 * @var array<string, mixed>|null $conflict
 * @var array<int, array<string, mixed>> $rooms
 * @var string[] $orgTypes
 * @var array<string, string> $equipment
 * @var array<string, mixed> $rules
 */

$v = static fn (string $key): string => (string) ($old[$key] ?? '');
// attribute aria สำหรับช่องที่มี error และข้อความ error ใต้ช่อง (DESIGN_SYSTEM 7.7)
$invalid = static fn (string $key): string => isset($errors[$key])
    ? ' aria-invalid="true" aria-describedby="err-' . e($key) . '"' : '';
$error = static fn (string $key): string => isset($errors[$key])
    ? '<p class="form-error" id="err-' . e($key) . '">' . icon('circle-alert') . e($errors[$key]) . '</p>' : '';
$label = static fn (string $for, string $text, bool $required = true): string =>
    '<label class="form-label" for="' . e($for) . '">' . e($text)
    . ($required ? ' <span class="required" aria-hidden="true">*</span>' : ' <span class="optional">(ไม่บังคับ)</span>') . '</label>';
?>
<div class="page-header">
  <div>
    <h1>จองห้องประชุม</h1>
    <p>กรอกแบบฟอร์มเพื่อส่งคำขอจองต่อเจ้าหน้าที่ผู้พิจารณา</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <form class="card" method="post" action="<?= e(url('/bookings')) ?>" id="booking-form" novalidate data-busy-text="กำลังส่งคำขอ…"<?= !$errors && !$conflict ? ' data-restore-draft' : '' ?>>
      <?= csrf_field() ?>

      <?php if ($errors): ?>
        <div class="alert alert-danger mb-4" role="alert" tabindex="-1" data-focus-on-load>
          <?= icon('circle-alert') ?>
          <div>
            <strong>ยังส่งคำขอไม่ได้ กรุณาแก้ไข <?= e(count($errors)) ?> รายการ</strong>
            <ul class="mt-1">
              <?php foreach ($errors as $field => $message): ?>
                <li><a href="#f-<?= e($field) ?>"><?= e($message) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($conflict): ?>
        <div class="alert alert-danger mb-4" role="alert" tabindex="-1" data-focus-on-load>
          <?= icon('calendar-minus') ?>
          <div>
            <strong><?= e($conflict['message']) ?></strong>
            <?php if ($conflict['alternatives']): ?>
              <p class="mt-1">ห้องอื่นที่ว่างในเวลาเดียวกัน (ข้อมูลที่กรอกไว้ยังอยู่ครบ):</p>
              <ul class="d-flex flex-wrap gap-2 list-unstyled mt-2">
                <?php foreach ($conflict['alternatives'] as $alt): ?>
                  <li><button type="button" class="btn btn-secondary btn-sm" data-pick-room="<?= e($alt['id']) ?>"><?= e($alt['name']) ?></button></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="mt-1">ไม่มีห้องอื่นว่างในช่วงเวลานี้ กรุณาเลือกเวลาใหม่</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <p class="form-required-note">ช่องที่มี <span class="text-danger">*</span> จำเป็นต้องกรอก</p>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('info') ?>รายละเอียดคำขอและสังกัด</legend>
        <div class="row">
          <div class="col-md-6 field">
            <?= $label('f-org_type', 'ประเภทหน่วยงานผู้จอง') ?>
            <select class="form-select" id="f-org_type" name="org_type" required<?= $invalid('org_type') ?>>
              <option value="">เลือกประเภทหน่วยงาน</option>
              <?php foreach ($orgTypes as $type): ?>
                <option value="<?= e($type) ?>"<?= $v('org_type') === $type ? ' selected' : '' ?>><?= e(org_type_label($type)) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $error('org_type') ?>
          </div>
          <div class="col-md-6 field">
            <?= $label('f-department', 'ชื่อกอง / ฝ่าย / แผนก') ?>
            <input class="form-control" id="f-department" name="department" value="<?= e($v('department')) ?>" maxlength="150" required<?= $invalid('department') ?>>
            <?= $error('department') ?>
          </div>
          <div class="field">
            <?= $label('f-title', 'หัวข้อการประชุม') ?>
            <input class="form-control" id="f-title" name="title" value="<?= e($v('title')) ?>" maxlength="120" required<?= $invalid('title') ?>>
            <?= $error('title') ?>
          </div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('calendar-check') ?>วัน เวลา และห้องประชุม</legend>
        <div class="row">
          <div class="col-md-4 field">
            <?= $label('f-date', 'วันที่ใช้ห้อง') ?>
            <input class="form-control" type="date" id="f-date" name="date" value="<?= e($v('date')) ?>" min="<?= e(date('Y-m-d')) ?>" max="<?= e(date('Y-m-d', strtotime('+' . (int) $rules['max_advance_days'] . ' days'))) ?>" required<?= $invalid('date') ?>>
            <?= $error('date') ?>
          </div>
          <div class="col-md-4 field">
            <?= $label('f-start_time', 'เวลาเริ่ม') ?>
            <input class="form-control" type="time" id="f-start_time" name="start_time" value="<?= e($v('start_time')) ?>" step="900" required<?= $invalid('start_time') ?>>
            <?= $error('start_time') ?>
          </div>
          <div class="col-md-4 field">
            <?= $label('f-end_time', 'เวลาสิ้นสุด') ?>
            <input class="form-control" type="time" id="f-end_time" name="end_time" value="<?= e($v('end_time')) ?>" step="900" required<?= $invalid('end_time') ?>>
            <?= $error('end_time') ?>
          </div>
          <div class="col-md-4 field">
            <?= $label('f-participants', 'จำนวนผู้เข้าร่วม (คน)') ?>
            <input class="form-control" type="number" inputmode="numeric" id="f-participants" name="participants" value="<?= e($v('participants')) ?>" min="1" required<?= $invalid('participants') ?>>
            <?= $error('participants') ?>
            <p class="form-help" id="capacity-warning" hidden></p>
          </div>
          <div class="col-md-8 field">
            <?= $label('f-room_id', 'ห้องประชุม') ?>
            <select class="form-select" id="f-room_id" name="room_id" required<?= $invalid('room_id') ?>>
              <option value="">เลือกห้องประชุม</option>
              <?php foreach ($rooms as $room): ?>
                <option value="<?= e($room['id']) ?>"
                        data-gov="<?= e($room['price_gov']) ?>" data-local="<?= e($room['price_local']) ?>"
                        data-capacity="<?= e($room['capacity']) ?>" data-warning="<?= e($room['warnings']) ?>"
                        <?= $room['locked'] ? 'disabled' : '' ?><?= $v('room_id') === $room['id'] ? ' selected' : '' ?>>
                  <?= e($room['name']) ?> (<?= e($room['building']) ?>)<?= $room['locked'] ? ' · ระงับใช้งาน' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?= $error('room_id') ?>
          </div>
        </div>

        <div class="alert alert-warning mt-3" id="room-warning" hidden>
          <?= icon('octagon-alert') ?><div><strong>คำเตือนความพร้อมห้องประชุม</strong><p data-warning-text></p></div>
        </div>

        <div class="mt-3">
          <button type="button" class="btn btn-secondary" id="btn-check-availability"><?= icon('search-code') ?>ตรวจสอบห้องว่างในช่วงเวลานี้</button>
          <p class="form-help mt-2">ระบบจะแสดงทุกห้องพร้อมสถานะว่างหรือไม่ว่าง กดเลือกห้องที่ว่างได้ทันที</p>
          <div id="availability-result" class="room-picker mt-3" aria-live="polite"></div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('monitor-play') ?>อุปกรณ์และสิ่งอำนวยความสะดวก</legend>
        <div class="row g-2">
          <?php foreach ($equipment as $key => $text): ?>
            <div class="col-sm-6">
              <label class="form-check">
                <input type="checkbox" name="equipment[]" value="<?= e($key) ?>"<?= in_array($key, (array) ($old['equipment'] ?? []), true) ? ' checked' : '' ?>>
                <?= e($text) ?>
              </label>
            </div>
          <?php endforeach; ?>
          <div class="col-sm-6">
            <label class="form-check">
              <input type="checkbox" name="coffee_break" value="1"<?= !empty($old['coffee_break']) ? ' checked' : '' ?>>
              จัดเบรกอาหารว่าง
            </label>
          </div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend class="card-title"><?= icon('user') ?>ผู้ประสานงาน</legend>
        <div class="row">
          <div class="col-md-4 field">
            <?= $label('f-organizer', 'ชื่อผู้ประสานงาน') ?>
            <input class="form-control" id="f-organizer" name="organizer" value="<?= e($v('organizer')) ?>" autocomplete="name" required<?= $invalid('organizer') ?>>
            <?= $error('organizer') ?>
          </div>
          <div class="col-md-4 field">
            <?= $label('f-phone', 'เบอร์โทรศัพท์ติดต่อ') ?>
            <input class="form-control" type="tel" id="f-phone" name="phone" value="<?= e($v('phone')) ?>" autocomplete="tel" required<?= $invalid('phone') ?>>
            <?= $error('phone') ?>
          </div>
          <div class="col-md-4 field">
            <?= $label('f-email', 'อีเมล') ?>
            <input class="form-control" type="email" id="f-email" name="email" value="<?= e($v('email')) ?>" autocomplete="email" required<?= $invalid('email') ?>>
            <?= $error('email') ?>
          </div>
          <div class="field">
            <?= $label('f-remarks', 'หมายเหตุเพิ่มเติม', false) ?>
            <textarea class="form-control" id="f-remarks" name="remarks" maxlength="1000" rows="3"><?= e($v('remarks')) ?></textarea>
            <p class="form-help">เช่น การต่อระบบเครื่องเสียงภายนอก หรือขอเข้าจัดห้องก่อนเวลา</p>
          </div>
        </div>
      </fieldset>

      <div class="form-actions">
        <a class="btn btn-ghost" href="<?= e(url('/bookings')) ?>">ยกเลิก</a>
        <button type="submit" class="btn btn-primary"><?= icon('square-check') ?>ตรวจสอบและยืนยัน</button>
      </div>
    </form>
  </div>

  <aside class="col-lg-4 d-flex flex-column gap-4">
    <section class="card" aria-labelledby="price-title">
      <h2 class="card-title mb-3" id="price-title"><?= icon('wallet') ?>ประมาณการค่าใช้จ่าย</h2>
      <div class="price-box" aria-live="polite">
        <div class="text-sm text-muted">ค่าธรรมเนียมรวม</div>
        <div class="price-value tabular" id="price-total">-</div>
        <div class="text-caption text-muted" id="price-detail">เลือกประเภทหน่วยงาน ห้อง และเวลา เพื่อคำนวณ</div>
      </div>
      <h3 class="text-sm fw-semibold mt-4 mb-2">อัตราค่าธรรมเนียมต่อชั่วโมง</h3>
      <ul class="rate-list list-unstyled">
        <?php foreach ($rooms as $room): ?>
          <li><span><?= e($room['name']) ?></span><span class="tabular nowrap">ราชการ <?= e(number_format($room['price_gov'])) ?> · ทั่วไป <?= e(number_format($room['price_local'])) ?> บาท</span></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <section class="card" aria-labelledby="rules-title">
      <h2 class="card-title mb-3" id="rules-title"><?= icon('shield-alert') ?>ระเบียบการใช้ห้องประชุม</h2>
      <ol class="rules-list text-sm">
        <li>กรุณาส่งคำขอจองล่วงหน้าอย่างน้อย <strong>3 วันทำการ</strong> ก่อนวันใช้งาน</li>
        <li>เจ้าหน้าที่โสตทัศนูปกรณ์จะเตรียมอุปกรณ์ก่อนเริ่มประชุม 30 นาที</li>
        <li>ผู้ใช้ห้องต้องควบคุมเสียงและดูแลรักษาความสะอาด</li>
        <li>เมื่อได้รับการอนุมัติ ระบบจะแจ้งผลในหน้าการแจ้งเตือน</li>
        <li>จองล่วงหน้าได้ไม่เกิน <strong><?= e((int) $rules['max_advance_days']) ?> วัน</strong> ครั้งละไม่เกิน <strong><?= e((float) $rules['max_hours']) ?> ชั่วโมง</strong> ในช่วง <?= e($rules['open_time']) ?>–<?= e($rules['close_time']) ?> น.<?= $rules['block_holidays'] ? ' และไม่เปิดจองวันหยุดราชการ' : '' ?></li>
      </ol>
    </section>
  </aside>
</div>

<dialog class="dialog" id="booking-summary" aria-labelledby="booking-summary-title">
  <div class="dialog-header">
    <h2 id="booking-summary-title">ตรวจสอบข้อมูลก่อนยืนยัน</h2>
    <button type="button" class="icon-btn" data-close aria-label="ปิด"><?= icon('x') ?></button>
  </div>
  <div class="dialog-body">
    <dl class="detail-list" id="booking-summary-list"></dl>
    <div class="alert alert-info mt-3"><?= icon('info') ?><span>การจองนี้ต้องรอผู้ดูแลห้องอนุมัติ ระบบจะแจ้งผลในหน้าการแจ้งเตือน</span></div>
  </div>
  <div class="dialog-footer">
    <button type="button" class="btn btn-ghost" data-close>แก้ไข</button>
    <button type="button" class="btn btn-primary" id="booking-summary-confirm"><?= icon('check') ?>ยืนยันการจอง</button>
  </div>
</dialog>
