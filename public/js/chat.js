/* ผู้ช่วยตอบคำถาม: หน้าต่างแชตมุมขวาล่าง ส่งคำถามไปที่ /api/chat */
(function () {
  'use strict';

  const root = document.querySelector('[data-chat]');
  if (!root) return;

  const fab = root.querySelector('[data-chat-toggle]');
  const panel = root.querySelector('#chat-panel');
  const log = root.querySelector('[data-chat-log]');
  const form = root.querySelector('[data-chat-form]');
  const input = form.querySelector('textarea');
  const send = form.querySelector('button[type="submit"]');
  const suggestions = root.querySelector('[data-chat-suggestions]');
  const STORE = 'chat_log';
  let busy = false;

  /* ---------- เก็บบทสนทนาในแท็บนี้ ให้เปลี่ยนหน้าแล้วยังเห็นข้อความเดิม ---------- */
  function load() {
    try { return JSON.parse(sessionStorage.getItem(STORE) || '[]'); } catch (e) { return []; }
  }
  function save(items) {
    try { sessionStorage.setItem(STORE, JSON.stringify(items.slice(-30))); } catch (e) { /* ไม่มี storage ก็ใช้ต่อได้ */ }
  }
  let history = load();

  /* ---------- แสดงข้อความ ---------- */
  // ทำ path ภายในระบบ เช่น /bookings/create ให้เป็นลิงก์ (escape ข้อความก่อนเสมอ)
  function format(text) {
    return App.escapeHtml(text)
      .replace(/(^|[\s(（])(\/[a-z][a-z0-9/_-]*(?:\?[a-z0-9=&_-]+)?)/gi, '$1<a href="$2">$2</a>')
      .replace(/\n/g, '<br>');
  }

  function bubble({ role, text, links = [], source = '' }, animate = true) {
    const item = document.createElement('div');
    item.className = `chat-msg chat-msg-${role}${animate ? ' is-new' : ''}`;
    const body = role === 'user' ? App.escapeHtml(text).replace(/\n/g, '<br>') : format(text);
    const actions = links
      .filter((l) => typeof l.url === 'string' && l.url.startsWith('/'))
      .map((l) => `<a class="chat-link" href="${App.escapeHtml(l.url)}">${App.escapeHtml(l.label)}${App.icon('arrow-right', 'icon-xs')}</a>`)
      .join('');
    item.innerHTML = `<div class="chat-bubble">${body}</div>`
      + (actions ? `<div class="chat-links">${actions}</div>` : '')
      + (source === 'ai' ? `<div class="chat-meta">${App.icon('bot', 'icon-xs')}ตอบโดย AI</div>` : '');
    log.appendChild(item);
    log.scrollTop = log.scrollHeight;
  }

  function greet() {
    bubble({
      role: 'bot',
      text: 'สวัสดี ถามข้อมูลในระบบได้เลย เช่น ห้องว่าง การจองของคุณ งานซ่อม วันลา หรือการลงเวลา',
    }, false);
  }

  function render() {
    log.innerHTML = '';
    greet();
    history.forEach((m) => bubble(m, false));
    suggestions.hidden = history.length > 0;
  }

  /* ---------- ส่งคำถาม ---------- */
  async function ask(text) {
    const question = text.trim();
    if (!question || busy) return;
    busy = true;
    send.disabled = true;
    suggestions.hidden = true;

    const user = { role: 'user', text: question };
    history.push(user);
    bubble(user);
    input.value = '';
    resize();

    const typing = document.createElement('div');
    typing.className = 'chat-msg chat-msg-bot';
    typing.innerHTML = '<div class="chat-bubble chat-typing"><span class="spinner" aria-hidden="true"></span> กำลังค้นข้อมูล…</div>';
    log.appendChild(typing);
    log.scrollTop = log.scrollHeight;

    try {
      const data = await App.api('/api/chat', { method: 'POST', body: { message: question } });
      typing.remove();
      const reply = { role: 'bot', text: data.reply, links: data.links || [], source: data.source };
      history.push(reply);
      bubble(reply);
    } catch (e) {
      typing.remove();
      bubble({ role: 'bot', text: e.message || 'ส่งคำถามไม่สำเร็จ กรุณาลองใหม่' });
    } finally {
      save(history);
      busy = false;
      send.disabled = false;
      input.focus();
    }
  }

  /* ---------- เปิด/ปิดหน้าต่าง ---------- */
  function open() {
    panel.hidden = false;
    fab.setAttribute('aria-expanded', 'true');
    root.classList.add('is-open');
    if (!log.children.length) render();
    log.scrollTop = log.scrollHeight;
    input.focus();
  }
  function close() {
    panel.hidden = true;
    fab.setAttribute('aria-expanded', 'false');
    root.classList.remove('is-open');
    fab.focus();
  }

  // ช่องพิมพ์ขยายตามข้อความ สูงสุด 5 บรรทัด
  function resize() {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 132)}px`;
  }

  fab.addEventListener('click', () => (panel.hidden ? open() : close()));
  root.querySelector('[data-chat-close]').addEventListener('click', close);
  root.querySelector('[data-chat-reset]').addEventListener('click', async () => {
    history = [];
    save(history);
    render();
    input.focus();
    try { await App.api('/api/chat/reset', { method: 'POST', body: {} }); } catch (e) { /* ล้างฝั่งหน้าเว็บแล้ว */ }
  });
  suggestions.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-chat-ask]');
    if (chip) ask(chip.dataset.chatAsk);
  });
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    e.stopImmediatePropagation();
    ask(input.value);
  }, true);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
      e.preventDefault();
      ask(input.value);
    }
  });
  input.addEventListener('input', resize);
  panel.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
})();
