/* อัปเดตสถานะห้องแบบเรียลไทม์ ใช้กับหน้า dashboard และหน้าห้องประชุม (element ที่มี data-room-status) */
(function () {
  'use strict';

  if (!document.querySelector('[data-room-status]')) return;
  const config = App.readJson('dashboard-data') || App.readJson('live-config') || {};

  function apply(data) {
    let available = 0;
    let inUse = 0;
    data.rooms.forEach((room) => {
      if (room.state === 'available') available += 1;
      if (room.state === 'in-use') inUse += 1;

      const el = document.querySelector(`[data-room-status="${CSS.escape(room.id)}"]`);
      if (!el) return;
      const line = el.querySelector('[data-room-line]');
      const badge = el.querySelector('[data-room-badge]');
      const changed = line && line.textContent !== room.line;

      if (line) line.textContent = room.line;
      if (badge) {
        badge.className = `badge ${room.badge_class}`;
        badge.innerHTML = `${App.icon(room.icon)}${App.escapeHtml(room.label)}`;
      }
      // ไฮไลต์ห้องที่สถานะเปลี่ยน โดยไม่ขยับ layout
      if (changed) {
        el.classList.remove('is-updated');
        void el.offsetWidth;
        el.classList.add('is-updated');
      }
    });

    const availEl = document.querySelector('[data-stat="rooms-available"]');
    if (availEl) availEl.textContent = available;
    const inUseEl = document.querySelector('[data-stat="in-use"]');
    if (inUseEl) inUseEl.textContent = inUse;
  }

  App.live({ url: '/api/rooms/status', seconds: config.pollSeconds || 15, onData: apply });
})();
