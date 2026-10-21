/* หน้า dashboard: นาฬิกาและกราฟ (ข้อมูลจริงจากฐานข้อมูล ส่งมาใน #dashboard-data) */
(function () {
  'use strict';

  const data = App.readJson('dashboard-data');
  if (!data) return;

  const clock = document.getElementById('dashboard-clock');
  if (clock) {
    const update = () => { clock.innerHTML = `${App.icon('clock')}${App.fmt.time(new Date())}`; };
    setInterval(update, 30000);
  }

  if (typeof Chart === 'undefined') return;

  // สีของกราฟอ่านจาก design token เพื่อไม่ใส่ค่าสีซ้ำใน JS
  const css = getComputedStyle(document.documentElement);
  const token = (name) => css.getPropertyValue(name).trim();
  const font = { family: token('--font-sans'), size: 13 };
  Chart.defaults.font = font;
  Chart.defaults.color = token('--color-gray-700');
  const grid = { color: token('--color-gray-200') };
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) Chart.defaults.animation = false;

  const datasets = [{
    type: 'bar',
    label: 'จำนวนการจอง (ครั้ง)',
    data: data.bookings,
    backgroundColor: token('--color-primary-600'),
    borderRadius: 4,
    yAxisID: 'y',
  }];
  if (data.showMoney) {
    datasets.push({
      type: 'line',
      label: 'รายได้ (บาท)',
      data: data.revenue,
      borderColor: token('--color-success-700'),
      backgroundColor: token('--color-success-700'),
      borderWidth: 2,
      pointRadius: 3,
      tension: 0.3,
      yAxisID: 'y1',
    });
  }

  new Chart(document.getElementById('chart-monthly'), {
    data: { labels: data.months, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom' },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${App.fmt.number(ctx.parsed.y)}`,
          },
        },
      },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid, ticks: { precision: 0 }, title: { display: true, text: 'ครั้ง' } },
        ...(data.showMoney ? { y1: { beginAtZero: true, position: 'right', grid: { display: false }, title: { display: true, text: 'บาท' } } } : {}),
      },
    },
  });

  new Chart(document.getElementById('chart-room-usage'), {
    type: 'doughnut',
    data: {
      labels: data.roomNames,
      datasets: [{
        data: data.roomUsage,
        backgroundColor: ['--color-primary-600', '--color-accent-500', '--color-success-700', '--color-primary-200'].map(token),
        borderColor: token('--color-white'),
        borderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 14 } },
        tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${App.fmt.number(ctx.parsed)} ครั้ง` } },
      },
    },
  });
})();
