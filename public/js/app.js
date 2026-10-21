/* public/js/app.js — แกนกลางฝั่งหน้าเว็บที่ทุกหน้าใช้ร่วมกัน (แทน EnterpriseApp + SweetAlert2 เดิม) */
(function () {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const desktop = window.matchMedia('(min-width: 1024px)');
  const root = document.documentElement;

  /* ---------- ไอคอนและการ escape สำหรับ HTML ที่สร้างด้วย JS ---------- */
  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function icon(name, cls = '') {
    return `<svg class="icon ${cls}" aria-hidden="true" focusable="false"><use href="/images/icons.svg#${escapeHtml(name)}"></use></svg>`;
  }

  /* ---------- วันที่และตัวเลขแบบไทย (DESIGN_SYSTEM 7.6, 9) ---------- */
  const dateShort = new Intl.DateTimeFormat('th-TH-u-ca-buddhist', { day: 'numeric', month: 'short', year: 'numeric' });
  const dateLong = new Intl.DateTimeFormat('th-TH-u-ca-buddhist', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const timeFmt = new Intl.DateTimeFormat('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false });
  const moneyFmt = new Intl.NumberFormat('th-TH', { maximumFractionDigits: 2 });

  // วันที่ ISO แบบ "2026-10-21" ต้องตีความเป็นเวลาท้องถิ่น ไม่ใช่ UTC
  function toDate(value) {
    if (value instanceof Date) return value;
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value ?? '');
    return m ? new Date(+m[1], +m[2] - 1, +m[3]) : new Date(value);
  }

  const fmt = {
    date: (v) => (v ? dateShort.format(toDate(v)) : '-'),
    dateLong: (v) => (v ? dateLong.format(toDate(v)) : '-'),
    time: (v) => `${v instanceof Date ? timeFmt.format(v) : String(v ?? '').slice(0, 5)} น.`,
    timeRange: (a, b) => `${String(a ?? '').slice(0, 5)}–${String(b ?? '').slice(0, 5)} น.`,
    money: (v) => `${moneyFmt.format(Number(v) || 0)} บาท`,
    number: (v) => moneyFmt.format(Number(v) || 0),
  };

  /* ---------- เรียก API พร้อม CSRF token ---------- */
  async function api(url, { method = 'GET', body } = {}) {
    const res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-CSRF-Token': csrfToken,
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    const data = await res.json().catch(() => ({}));
    if (res.status === 401) {
      window.location.href = '/login';
    }
    if (!res.ok) {
      const error = new Error(data.error || 'ดำเนินการไม่สำเร็จ กรุณาลองอีกครั้ง');
      error.status = res.status;
      error.data = data;
      throw error;
    }
    return data;
  }

  /* ---------- Toast (7.10) ---------- */
  const toastIcons = { success: 'circle-check', warning: 'triangle-alert', danger: 'circle-alert', info: 'info' };

  function toast(message, type = 'info') {
    const region = document.getElementById('toast-region');
    if (!region) return;
    while (region.children.length >= 3) region.firstElementChild.remove();

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    el.innerHTML = `${icon(toastIcons[type] || 'info')}<div class="toast-message">${escapeHtml(message)}</div>
      <button type="button" class="icon-btn" aria-label="ปิด">${icon('x', 'icon-sm')}</button>`;
    el.querySelector('button').addEventListener('click', () => el.remove());
    region.appendChild(el);

    // ข้อความ error ไม่หายเอง ส่วนแบบอื่นหายใน 6 วินาที และหยุดนับเมื่อ hover/focus
    if (type === 'danger') return;
    let timer;
    const start = () => { timer = setTimeout(() => el.remove(), 6000); };
    const stop = () => clearTimeout(timer);
    el.addEventListener('mouseenter', stop);
    el.addEventListener('mouseleave', start);
    el.addEventListener('focusin', stop);
    el.addEventListener('focusout', start);
    start();
  }

  /* ---------- Dialog ยืนยัน (7.9) ---------- */
  function confirmDialog({ title, text = '', confirmText = 'ยืนยัน', cancelText = 'ยกเลิก', danger = false }) {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') {
      return Promise.resolve(window.confirm(`${title}\n${text}`));
    }
    const opener = document.activeElement;
    dialog.querySelector('#confirm-dialog-title').textContent = title;
    dialog.querySelector('#confirm-dialog-text').textContent = text;
    const ok = dialog.querySelector('[data-confirm-ok]');
    ok.textContent = confirmText;
    ok.className = `btn ${danger ? 'btn-danger' : 'btn-primary'}`;
    dialog.querySelector('[data-confirm-cancel]').textContent = cancelText;

    return new Promise((resolve) => {
      dialog.returnValue = '';
      dialog.addEventListener('close', () => {
        resolve(dialog.returnValue === 'ok');
        opener?.focus?.();
      }, { once: true });
      dialog.showModal();
      // focus เริ่มต้นที่ปุ่มที่ปลอดภัย
      dialog.querySelector('[data-confirm-cancel]').focus();
    });
  }

  /* ---------- ฟอร์ม: ยืนยันก่อนส่ง และกันการกดซ้ำ ---------- */
  function setBusy(form, submitter) {
    const button = submitter || form.querySelector('button[type="submit"]:not([formnovalidate])');
    if (!button) return;
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
    button.innerHTML = `<span class="spinner" aria-hidden="true"></span> ${escapeHtml(form.dataset.busyText || 'กำลังบันทึก…')}`;
  }

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method === 'dialog') return;

    if (form.dataset.confirm && form.dataset.confirmed !== '1') {
      event.preventDefault();
      const ok = await confirmDialog({
        title: form.dataset.confirm,
        text: form.dataset.confirmText,
        confirmText: form.dataset.confirmButton,
        danger: form.dataset.confirmDanger === '1',
      });
      if (ok) {
        form.dataset.confirmed = '1';
        setBusy(form);
        form.submit();
      }
      return;
    }
    if (!event.defaultPrevented && form.method === 'post') setBusy(form, event.submitter);
  });

  /* ---------- Sidebar ---------- */
  function initSidebar() {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-close]');
    if (!toggle) return;

    const syncExpanded = () => {
      const expanded = desktop.matches ? !root.classList.contains('sidebar-collapsed') : root.classList.contains('sidebar-open');
      toggle.setAttribute('aria-expanded', String(expanded));
      if (backdrop) backdrop.hidden = desktop.matches || !expanded;
    };
    const closeDrawer = () => { root.classList.remove('sidebar-open'); syncExpanded(); };

    toggle.addEventListener('click', () => {
      if (desktop.matches) {
        const collapsed = root.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) { /* ไม่มี storage ก็ใช้งานต่อได้ */ }
      } else {
        root.classList.toggle('sidebar-open');
      }
      syncExpanded();
    });
    backdrop?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && root.classList.contains('sidebar-open')) {
        closeDrawer();
        toggle.focus();
      }
    });
    desktop.addEventListener('change', closeDrawer);
    syncExpanded();
  }

  /* ---------- หน้า login: ปุ่มกรอกบัญชีทดสอบ ---------- */
  function initDemoLogin() {
    document.querySelectorAll('[data-demo-login]').forEach((button) => {
      button.addEventListener('click', () => {
        document.getElementById('login-username').value = button.dataset.demoLogin;
        document.getElementById('login-password').value = button.dataset.demoLogin;
        document.querySelector('form[action="/login"] button[type="submit"]').focus();
      });
    });
    document.getElementById('login-error')?.focus();
  }

  /* ---------- ดึงข้อมูลซ้ำแบบเรียลไทม์ พร้อมตัวบอกสถานะการเชื่อมต่อ (7.11) ---------- */
  function live({ url, seconds = 15, onData }) {
    const indicator = document.querySelector('[data-live-indicator]');
    const text = indicator?.querySelector('[data-live-text]');
    const banner = document.querySelector('[data-live-offline]');
    const updated = banner?.querySelector('[data-live-updated]');
    let failures = 0;
    let timer;

    const setState = (state) => {
      if (indicator) indicator.dataset.state = state;
      if (text) text.textContent = { ok: 'อัปเดตอัตโนมัติ', retry: 'กำลังเชื่อมต่อใหม่…', offline: 'ขาดการเชื่อมต่อ' }[state];
      if (banner) banner.hidden = state !== 'offline';
    };

    async function tick() {
      clearTimeout(timer);
      try {
        const data = await api(url);
        failures = 0;
        setState('ok');
        if (updated) updated.textContent = timeFmt.format(new Date());
        onData(data);
      } catch (e) {
        failures += 1;
        setState(failures >= 2 ? 'offline' : 'retry');
      }
      if (!document.hidden) timer = setTimeout(tick, seconds * 1000);
    }

    banner?.querySelector('[data-live-retry]')?.addEventListener('click', tick);
    // หยุดดึงเมื่อแท็บถูกซ่อน และดึงทันทีเมื่อกลับมา
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) clearTimeout(timer); else tick();
    });
    timer = setTimeout(tick, seconds * 1000);
    return { refresh: tick };
  }

  /* ---------- พฤติกรรมทั่วไปที่ประกาศผ่าน data-attribute ---------- */
  document.addEventListener('change', (e) => {
    if (e.target.matches('[data-autosubmit]')) e.target.form?.requestSubmit();
  });
  document.addEventListener('click', (e) => {
    const closer = e.target.closest('[data-close]');
    if (closer) closer.closest('dialog')?.close();
    if (e.target.closest('[data-print]')) window.print();
  });
  // ย้าย focus ไปที่กล่องสรุปข้อผิดพลาดหลังส่งฟอร์มไม่ผ่าน
  document.querySelector('[data-focus-on-load]')?.focus();

  function readJson(id) {
    try { return JSON.parse(document.getElementById(id)?.textContent || 'null'); } catch (e) { return null; }
  }

  initSidebar();
  initDemoLogin();

  window.App = { api, toast, confirm: confirmDialog, icon, escapeHtml, fmt, toDate, live, readJson };
})();
