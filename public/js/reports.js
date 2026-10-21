/* กราฟหน้ารายงานสถิติและรายได้ ข้อมูลจริงจาก #report-data */
(function () {
  'use strict';

  const data = App.readJson('report-data');
  if (!data || typeof Chart === 'undefined') return;

  const css = getComputedStyle(document.documentElement);
  const token = (name) => css.getPropertyValue(name).trim();
  Chart.defaults.font = { family: token('--font-sans'), size: 13 };
  Chart.defaults.color = token('--color-gray-700');
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;
  const grid = { color: token('--color-gray-200') };
  const palette = ['--color-primary-600', '--color-accent-500', '--color-success-700', '--color-warning-700',
    '--color-danger-700', '--color-primary-200', '--color-gray-500', '--color-primary-900'].map(token);

  if (data.type === 'reports') {
    new Chart(document.getElementById('chart-departments'), {
      type: 'bar',
      data: { labels: data.departments, datasets: [{ label: 'จำนวนการจอง (ครั้ง)', data: data.departmentCounts, backgroundColor: token('--color-primary-600'), borderRadius: 4 }] },
      options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } },
      },
    });
    new Chart(document.getElementById('chart-categories'), {
      type: 'doughnut',
      data: { labels: data.categories, datasets: [{ data: data.categoryCounts, backgroundColor: palette, borderColor: token('--color-white'), borderWidth: 2 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: (c) => `${c.label}: ${App.fmt.number(c.parsed)} งาน` } } },
      },
    });
  }

  if (data.type === 'revenue') {
    new Chart(document.getElementById('chart-revenue'), {
      type: 'line',
      data: {
        labels: data.labels,
        datasets: [{
          label: 'รายได้ (บาท)', data: data.values, borderColor: token('--color-success-700'), backgroundColor: token('--color-success-50'),
          fill: true, tension: 0.3, borderWidth: 2, pointRadius: 3,
        }],
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => App.fmt.money(c.parsed.y) } } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: { callback: (v) => App.fmt.number(v) } } },
      },
    });
  }
})();
