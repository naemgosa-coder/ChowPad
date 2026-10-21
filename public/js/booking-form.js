/* ฟอร์มจองห้อง: คำนวณราคา ตรวจห้องว่าง สรุปก่อนยืนยัน และบันทึกร่างอัตโนมัติ (DESIGN_SYSTEM 7.7, 7.8) */
(function () {
  'use strict';

  const form = document.getElementById('booking-form');
  if (!form) return;

  const $ = (name) => form.elements.namedItem(name);
  const roomSelect = $('room_id');
  const DRAFT_KEY = 'booking_draft';

  /* ---------- ร่างอัตโนมัติใน sessionStorage ---------- */
  function saveDraft() {
    const data = {};
    new FormData(form).forEach((value, key) => {
      if (key === '_token') return;
      if (key.endsWith('[]')) (data[key] ||= []).push(value); else data[key] = value;
    });
    try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(data)); } catch (e) { /* ไม่มี storage ก็ข้ามไป */ }
  }

  function restoreDraft() {
    let data = null;
    try { data = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { data = null; }
    if (!data) return;
    Object.entries(data).forEach(([key, value]) => {
      if (key === 'equipment[]') {
        form.querySelectorAll('input[name="equipment[]"]').forEach((cb) => { cb.checked = value.includes(cb.value); });
      } else if (key === 'coffee_break') {
        $('coffee_break').checked = true;
      } else if ($(key) && value !== '' && !(key === 'room_id' && roomSelect.value)) {
        $(key).value = value;
      }
    });
    App.toast('กู้คืนข้อมูลที่กรอกไว้ก่อนหน้าแล้ว', 'info');
  }

  /* ---------- ราคาและคำเตือนห้อง ---------- */
  function selectedRoom() {
    return roomSelect.selectedOptions[0]?.value ? roomSelect.selectedOptions[0] : null;
  }

  function minutesBetween(start, end) {
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    return (eh * 60 + em) - (sh * 60 + sm);
  }

  function updatePrice() {
    const total = document.getElementById('price-total');
    const detail = document.getElementById('price-detail');
    const room = selectedRoom();
    const org = $('org_type').value;
    const start = $('start_time').value;
    const end = $('end_time').value;

    if (!room || !org || !start || !end || start >= end) {
      total.textContent = '-';
      detail.textContent = 'เลือกประเภทหน่วยงาน ห้อง และเวลา เพื่อคำนวณ';
      return null;
    }
    // สูตรเดียวกับฝั่งเซิร์ฟเวอร์ (BookingService::price)
    const hours = Math.ceil(Math.round((minutesBetween(start, end) / 60) * 10000) / 10000 * 10) / 10;
    const rate = Number(org === 'Government Agency' ? room.dataset.gov : room.dataset.local);
    const fee = Math.ceil(rate * hours);
    total.textContent = App.fmt.money(fee);
    detail.textContent = `${hours} ชั่วโมง × ${App.fmt.money(rate)}/ชม.`;
    return { hours, rate, fee };
  }

  function updateRoomWarnings() {
    const room = selectedRoom();
    const warningBox = document.getElementById('room-warning');
    const text = room?.dataset.warning || '';
    warningBox.hidden = !text;
    warningBox.querySelector('[data-warning-text]').textContent = text;

    const capWarn = document.getElementById('capacity-warning');
    const max = Math.max(0, ...((room?.dataset.capacity || '').match(/\d+/g) || [0]).map(Number));
    const people = Number($('participants').value);
    const over = room && people > max && max > 0;
    capWarn.hidden = !over;
    capWarn.className = over ? 'form-error' : 'form-help';
    capWarn.innerHTML = over ? `${App.icon('triangle-alert')}เกินความจุห้อง (${App.escapeHtml(room.dataset.capacity)} ที่นั่ง)` : '';
  }

  /* ---------- ตรวจห้องว่าง (ยกมาจาก meeting-room-maintenance-dashboard) ---------- */
  const stateText = {
    available: ['ว่าง', 'badge--available', 'circle-check'],
    busy: ['ไม่ว่าง', 'badge--booked', 'circle-x'],
    locked: ['ระงับใช้งาน', 'badge--maintenance', 'wrench'],
    'too-small': ['ความจุไม่พอ', 'badge-warning', 'users'],
  };

  async function checkAvailability() {
    const result = document.getElementById('availability-result');
    const date = $('date').value;
    const start = $('start_time').value;
    const end = $('end_time').value;
    if (!date || !start || !end) {
      result.innerHTML = `<p class="form-error">${App.icon('circle-alert')}กรุณาเลือกวันที่ เวลาเริ่ม และเวลาสิ้นสุดก่อน</p>`;
      return;
    }
    result.innerHTML = '<span class="skeleton" style="height:96px"></span>';
    try {
      const params = new URLSearchParams({ date, start, end, participants: $('participants').value || 0 });
      const data = await App.api(`/api/rooms/availability?${params}`);
      const free = data.rooms.filter((r) => r.state === 'available').length;
      result.innerHTML = `<p class="text-sm mb-2">พบ ${free} ห้องที่ว่างในช่วง ${App.fmt.timeRange(start, end)}</p>
        <div class="room-picker-grid">${data.rooms.map((r) => {
          const [label, cls, icon] = stateText[r.state];
          const disabled = r.state === 'busy' || r.state === 'locked';
          return `<button type="button" class="room-option" data-pick-room="${App.escapeHtml(r.id)}"
              aria-pressed="${r.id === roomSelect.value}" ${disabled ? 'disabled' : ''}>
            <span class="d-flex justify-content-between gap-2 align-items-start">
              <strong>${App.escapeHtml(r.name)}</strong>
              <span class="badge ${cls}">${App.icon(icon)}${label}</span>
            </span>
            <span class="text-caption text-muted">${App.escapeHtml(r.building)} ชั้น ${App.escapeHtml(r.floor)} · ${App.escapeHtml(r.capacity)} ที่นั่ง</span>
            ${r.conflict ? `<span class="text-caption text-danger">มีผู้จองแล้ว ${App.escapeHtml(r.conflict)}</span>` : ''}
          </button>`;
        }).join('')}</div>`;
    } catch (e) {
      result.innerHTML = `<div class="alert alert-danger">${App.icon('circle-alert')}<span>${App.escapeHtml(e.message)}</span></div>`;
    }
  }

  function pickRoom(id) {
    roomSelect.value = id;
    roomSelect.dispatchEvent(new Event('change', { bubbles: true }));
    document.querySelectorAll('[data-pick-room]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.pickRoom === id)));
    App.toast(`เลือก${roomSelect.selectedOptions[0].textContent.trim()}แล้ว`, 'success');
  }

  /* ---------- สรุปก่อนยืนยัน ---------- */
  function showSummary() {
    const dialog = document.getElementById('booking-summary');
    const price = updatePrice();
    const rows = [
      ['ห้องประชุม', roomSelect.selectedOptions[0].textContent.trim()],
      ['วันที่', App.fmt.dateLong($('date').value)],
      ['เวลา', App.fmt.timeRange($('start_time').value, $('end_time').value) + (price ? ` (${price.hours} ชม.)` : '')],
      ['หัวข้อ', $('title').value],
      ['ผู้เข้าร่วม', `${$('participants').value} คน`],
      ['ผู้ประสานงาน', `${$('organizer').value} · ${$('phone').value}`],
      ['ค่าธรรมเนียมโดยประมาณ', price ? App.fmt.money(price.fee) : '-'],
    ];
    document.getElementById('booking-summary-list').innerHTML = rows
      .map(([k, v]) => `<div><dt>${k}</dt><dd>${App.escapeHtml(v)}</dd></div>`).join('');
    dialog.showModal();
  }

  /* ---------- ผูก event ---------- */
  ['org_type', 'start_time', 'end_time'].forEach((n) => $(n).addEventListener('change', updatePrice));
  roomSelect.addEventListener('change', () => { updatePrice(); updateRoomWarnings(); });
  $('participants').addEventListener('input', updateRoomWarnings);
  document.getElementById('btn-check-availability').addEventListener('click', checkAvailability);
  document.addEventListener('click', (e) => {
    const pick = e.target.closest('[data-pick-room]');
    if (pick && !pick.disabled) pickRoom(pick.dataset.pickRoom);
  });
  form.addEventListener('input', saveDraft);
  form.addEventListener('change', saveDraft);

  form.addEventListener('submit', (e) => {
    if (form.dataset.confirmed === '1') return;
    // ข้อมูลไม่ครบให้เซิร์ฟเวอร์ตอบกลับพร้อมข้อความผิดพลาดรายช่อง
    if (!form.checkValidity()) return;
    e.preventDefault();
    showSummary();
  });
  document.getElementById('booking-summary-confirm').addEventListener('click', () => {
    form.dataset.confirmed = '1';
    try { sessionStorage.removeItem(DRAFT_KEY); } catch (e) { /* ข้าม */ }
    document.getElementById('booking-summary').close();
    form.requestSubmit();
  });

  if (form.hasAttribute('data-restore-draft')) restoreDraft();
  updatePrice();
  updateRoomWarnings();
})();
