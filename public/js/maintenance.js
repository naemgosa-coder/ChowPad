/* งานซ่อม: แนบรูปแบบลากวาง (ยกมาจาก maintenance dashboard) และ dialog ปิดงานของนายช่าง */
(function () {
  'use strict';

  const MAX_BYTES = 5 * 1024 * 1024;
  const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

  /* ---------- แนบรูปพร้อมพรีวิว ---------- */
  document.querySelectorAll('[data-dropzone]').forEach((zone) => {
    const input = zone.querySelector('input[type="file"]');
    const preview = zone.parentElement.querySelector('[data-preview]');
    const img = preview?.querySelector('img');
    let objectUrl = null;

    function clear() {
      input.value = '';
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = null;
      if (preview) preview.hidden = true;
      zone.hidden = false;
    }

    function show(file) {
      if (!file) return clear();
      if (!TYPES.includes(file.type)) {
        App.toast('รองรับเฉพาะไฟล์รูป JPG, PNG หรือ WebP', 'danger');
        return clear();
      }
      if (file.size > MAX_BYTES) {
        App.toast('ไฟล์รูปต้องมีขนาดไม่เกิน 5 MB', 'danger');
        return clear();
      }
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = URL.createObjectURL(file);
      img.src = objectUrl;
      preview.hidden = false;
      zone.hidden = true;
    }

    input.addEventListener('change', () => show(input.files[0]));
    ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (e) => {
      e.preventDefault();
      zone.classList.add('is-dragover');
    }));
    ['dragleave', 'drop'].forEach((type) => zone.addEventListener(type, () => zone.classList.remove('is-dragover')));
    zone.addEventListener('drop', (e) => {
      e.preventDefault();
      if (!e.dataTransfer.files.length) return;
      input.files = e.dataTransfer.files;
      show(input.files[0]);
    });
    preview?.querySelector('[data-preview-remove]').addEventListener('click', () => {
      clear();
      input.focus();
    });
  });

  /* ---------- dialog ปิดงานซ่อม ---------- */
  const dialog = document.getElementById('complete-dialog');
  if (!dialog) return;
  const form = dialog.querySelector('form');
  const error = dialog.querySelector('[data-complete-error]');

  document.addEventListener('click', (e) => {
    const button = e.target.closest('[data-complete]');
    if (!button) return;
    const id = button.dataset.complete;
    form.reset();
    form.action = `/maintenance/${encodeURIComponent(id)}/complete`;
    form.elements.namedItem('return_to').value = button.dataset.return || '/technician';
    dialog.querySelector('[data-complete-id]').textContent = id;
    error.hidden = true;
    dialog.showModal();
    form.elements.namedItem('repair_notes').focus();
  });

  form.addEventListener('submit', (e) => {
    const notes = form.elements.namedItem('repair_notes');
    const file = form.elements.namedItem('after_image').files[0];
    let message = '';
    if (notes.value.trim() === '') message = 'กรุณาบันทึกรายละเอียดการซ่อม';
    else if (file && (!TYPES.includes(file.type) || file.size > MAX_BYTES)) message = 'รูปหลังซ่อมต้องเป็น JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB';
    if (!message) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    error.innerHTML = `${App.icon('circle-alert')}${App.escapeHtml(message)}`;
    error.hidden = false;
  }, true);
})();
