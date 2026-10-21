/* dialog ระบุเหตุผลไม่อนุมัติ (แทน SweetAlert input เดิม) */
(function () {
  'use strict';

  const dialog = document.getElementById('reject-dialog');
  if (!dialog) return;
  const form = document.getElementById('reject-form');
  const reason = form.elements.namedItem('reason');
  const error = document.getElementById('reject-error');

  document.addEventListener('click', (e) => {
    const button = e.target.closest('[data-reject]');
    if (!button) return;
    const id = button.dataset.reject;
    form.action = `/bookings/${encodeURIComponent(id)}/reject`;
    form.elements.namedItem('return_to').value = button.dataset.return || '/bookings/approval';
    dialog.querySelector('[data-reject-id]').textContent = id;
    reason.value = '';
    reason.removeAttribute('aria-invalid');
    error.hidden = true;
    dialog.showModal();
    reason.focus();
  });

  form.addEventListener('submit', (e) => {
    if (reason.value.trim() !== '') return;
    e.preventDefault();
    e.stopImmediatePropagation();
    error.hidden = false;
    reason.setAttribute('aria-invalid', 'true');
    reason.setAttribute('aria-describedby', 'reject-error reject-help');
    reason.focus();
  }, true);
})();
