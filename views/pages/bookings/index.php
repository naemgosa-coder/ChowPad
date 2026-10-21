<?php

use App\Models\Booking;
use App\Services\View;

/**
 * @var array<int, array<string, mixed>> $bookings
 * @var array<string, string> $filters
 * @var array<int, array<string, mixed>> $rooms
 * @var int $total @var int $page @var int $pages @var int $perPage
 * @var bool $allBookings
 */
?>
<div class="page-header">
  <div>
    <h1>ประวัติการจองห้องประชุม</h1>
    <p><?= $allBookings ? 'รายการจองห้องประชุมทั้งหมดของศาลากลางจังหวัด' : 'รายการจองห้องประชุมของคุณ' ?></p>
  </div>
  <div class="page-actions no-print">
    <a class="btn btn-secondary" href="<?= e(url('/bookings/export') . query_with(['page' => null])) ?>"><?= icon('file-spreadsheet') ?>ส่งออก Excel (CSV)</a>
    <button type="button" class="btn btn-ghost" data-print><?= icon('printer') ?>พิมพ์ตาราง</button>
  </div>
</div>

<section class="card">
  <form class="filter-bar mb-4 no-print" method="get" action="<?= e(url('/bookings')) ?>" role="search" aria-label="ค้นหาการจอง">
    <div class="field">
      <label class="form-label" for="bk-search">ค้นหา</label>
      <div class="input-icon">
        <?= icon('search') ?>
        <input class="form-control" type="search" id="bk-search" name="search" value="<?= e($filters['search']) ?>" placeholder="หัวข้อ ผู้ประสานงาน หรือเลขที่">
      </div>
    </div>
    <div class="field">
      <label class="form-label" for="bk-room">ห้องประชุม</label>
      <select class="form-select" id="bk-room" name="room" data-autosubmit>
        <option value="">ทุกห้อง</option>
        <?php foreach ($rooms as $room): ?>
          <option value="<?= e($room['id']) ?>"<?= $filters['room'] === $room['id'] ? ' selected' : '' ?>><?= e($room['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label class="form-label" for="bk-status">สถานะ</label>
      <select class="form-select" id="bk-status" name="status" data-autosubmit>
        <option value="">ทุกสถานะ</option>
        <?php foreach (Booking::STATUSES as $status): ?>
          <option value="<?= e($status) ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= e(status_meta('booking', $status)['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-actions">
      <button type="submit" class="btn btn-secondary"><?= icon('filter') ?>ค้นหา</button>
      <?php if (array_filter($filters)): ?>
        <a class="btn btn-ghost" href="<?= e(url('/bookings')) ?>">ล้างตัวกรอง</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (!$bookings): ?>
    <div class="empty-state">
      <?= icon('search-x', 'icon-xl') ?>
      <strong><?= array_filter($filters) ? 'ไม่พบการจองตามเงื่อนไข' : 'คุณยังไม่มีการจอง' ?></strong>
      <?php if (array_filter($filters)): ?>
        <a class="btn btn-secondary" href="<?= e(url('/bookings')) ?>">ล้างตัวกรอง</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= e(url('/bookings/create')) ?>"><?= icon('calendar-plus') ?>จองห้องประชุม</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-cards">
        <thead>
          <tr>
            <th scope="col">เลขที่</th>
            <th scope="col">ห้องประชุม</th>
            <th scope="col">หัวข้อการประชุม</th>
            <th scope="col">วันและเวลา</th>
            <th scope="col">ผู้ประสานงาน</th>
            <th scope="col" class="num">ค่าธรรมเนียม</th>
            <th scope="col">สถานะ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td data-label="เลขที่" class="nowrap"><a href="<?= e(url('/bookings/' . $b['id'])) ?>" class="fw-semibold"><?= e($b['id']) ?></a></td>
              <td data-label="ห้องประชุม"><?= e($b['room_name']) ?></td>
              <td data-label="หัวข้อ"><?= e($b['title']) ?></td>
              <td data-label="วันและเวลา" class="nowrap"><?= e(thai_date($b['date'])) ?><br><span class="text-caption text-muted tabular"><?= e(time_range($b['start_time'], $b['end_time'])) ?> (<?= e((float) $b['duration_hours']) ?> ชม.)</span></td>
              <td data-label="ผู้ประสานงาน"><?= e($b['organizer']) ?></td>
              <td data-label="ค่าธรรมเนียม" class="num nowrap"><?= e(money($b['rental_fee'])) ?></td>
              <td data-label="สถานะ"><?= status_badge('booking', $b['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= View::partial('partials/pagination', compact('page', 'pages', 'total', 'perPage')) ?>
  <?php endif; ?>
</section>
