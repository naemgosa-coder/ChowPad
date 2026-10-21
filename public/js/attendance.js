/* หน้าลงเวลา: นาฬิกาเดินแบบเรียลไทม์ ตามเวลาเซิร์ฟเวอร์ (เวลาที่บันทึกจริงมาจากเซิร์ฟเวอร์เสมอ) */
(function () {
  'use strict';

  const clock = document.getElementById('live-clock');
  if (!clock) return;
  // ชดเชยกรณีนาฬิกาเครื่องผู้ใช้ไม่ตรงกับเซิร์ฟเวอร์
  const offset = Number(clock.dataset.serverEpoch) - Date.now();
  const format = new Intl.DateTimeFormat('th-TH', {
    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: clock.dataset.timezone,
  });
  const tick = () => { clock.textContent = format.format(new Date(Date.now() + offset)); };
  tick();
  setInterval(tick, 1000);
})();
