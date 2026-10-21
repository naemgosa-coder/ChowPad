/* ปฏิทินการจอง (FullCalendar 6) ข้อมูลจาก /api/calendar/events */
(function () {
  'use strict';

  const el = document.getElementById('calendar');
  if (!el || typeof FullCalendar === 'undefined') return;

  const dialog = document.getElementById('event-dialog');
  const roomBoxes = () => [...document.querySelectorAll('#calendar-rooms input:checked')].map((c) => c.value);
  const monthTitle = new Intl.DateTimeFormat('th-TH-u-ca-buddhist', { month: 'long', year: 'numeric' });

  const calendar = new FullCalendar.Calendar(el, {
    locale: 'th',
    initialView: window.matchMedia('(max-width: 767.98px)').matches ? 'listWeek' : 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
    buttonText: { listWeek: 'รายการ' },
    height: 'auto',
    firstDay: 1,
    slotMinTime: '07:00:00',
    slotMaxTime: '20:00:00',
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    dayMaxEvents: 3,
    eventDisplay: 'block',
    nowIndicator: true,

    events(info, success, failure) {
      const params = new URLSearchParams({ start: info.startStr.slice(0, 10), end: info.endStr.slice(0, 10) });
      roomBoxes().forEach((id) => params.append('rooms[]', id));
      App.api(`/api/calendar/events?${params}`).then(success).catch((e) => {
        App.toast('โหลดข้อมูลปฏิทินไม่สำเร็จ กรุณาลองอีกครั้ง', 'danger');
        failure(e);
      });
    },

    // แสดงปีเป็นพุทธศักราช
    datesSet(info) {
      const title = el.querySelector('.fc-toolbar-title');
      if (!title) return;
      const start = info.view.currentStart;
      const end = new Date(info.view.currentEnd.getTime() - 1);
      if (info.view.type === 'dayGridMonth') title.textContent = monthTitle.format(start);
      else if (info.view.type === 'timeGridDay') title.textContent = App.fmt.dateLong(start);
      else title.textContent = `${App.fmt.date(start)} – ${App.fmt.date(end)}`;
    },

    // สถานะต้องมีไอคอนและข้อความประกอบสี
    eventContent(arg) {
      if (arg.event.display === 'background') return true;
      const p = arg.event.extendedProps;
      return {
        html: `<span class="fc-event-inner">${App.icon(p.statusIcon, 'icon-xs')}
          <span class="fc-event-time-text">${App.escapeHtml(arg.timeText)}</span>
          <span class="fc-event-title-text">${App.escapeHtml(arg.event.title)}</span></span>`,
      };
    },

    eventDidMount(arg) {
      if (arg.event.display === 'background') {
        arg.el.title = arg.event.title;
        arg.el.setAttribute('aria-label', `วันหยุด: ${arg.event.title}`);
        return;
      }
      const p = arg.event.extendedProps;
      arg.el.setAttribute('aria-label', `${arg.event.title}, ${p.room}, ${p.time}, ${p.statusLabel}`);
      arg.el.title = `${arg.event.title} · ${p.room} · ${p.statusLabel}`;
    },

    eventClick(info) {
      info.jsEvent.preventDefault();
      if (info.event.display === 'background') return;
      const p = info.event.extendedProps;
      dialog.querySelector('#event-dialog-title').textContent = info.event.title;
      const rows = [
        ['สถานะ', `<span class="badge ${App.escapeHtml(p.statusClass)}">${App.icon(p.statusIcon)}${App.escapeHtml(p.statusLabel)}</span>`, true],
        ['ห้องประชุม', p.room],
        ['สถานที่', p.location],
        ['วันที่', p.date],
        ['เวลา', p.time],
        ['หน่วยงาน', p.department],
        ['ผู้ประสานงาน', p.organizer + (p.phone ? ` (โทร ${p.phone})` : '')],
        ['ผู้เข้าร่วม', `${p.participants} คน`],
      ];
      dialog.querySelector('#event-dialog-body').innerHTML = rows
        .map(([k, v, raw]) => `<div><dt>${k}</dt><dd>${raw ? v : App.escapeHtml(v)}</dd></div>`).join('');
      const link = dialog.querySelector('#event-dialog-link');
      link.hidden = !p.url;
      if (p.url) link.href = p.url;
      dialog.showModal();
    },
  });

  calendar.render();
  document.getElementById('calendar-rooms').addEventListener('change', () => calendar.refetchEvents());
})();
