// resources/js/repair/dashboard.js
import Chart from 'chart.js/auto';

(function () {
  const parseJSON = (str, def = []) => {
    try { return JSON.parse(str); } catch (e) { return def; }
  };

  const showToast = ({ type = 'info', message = '', timeout = 3200 } = {}) => {
    const overlay = document.querySelector('.ui-toast-overlay');
    if (!overlay || !message) return;

    const el = document.createElement('div');
    const typeClass = ({
      success: 'ui-success',
      error: 'ui-error',
      warning: 'ui-warning',
      info: 'ui-info',
    }[type] || 'ui-info');

    el.className = `ui-toast-card ${typeClass}`;
    el.innerHTML = `
      <div class="ui-toast-msg"></div>
      <button class="ui-toast-x" type="button" aria-label="Close">&times;</button>
    `;
    el.querySelector('.ui-toast-msg').textContent = message;

    const close = () => {
      el.classList.add('ui-toast-out');
      setTimeout(() => el.remove(), 250);
    };

    el.querySelector('.ui-toast-x')?.addEventListener('click', close);
    if (timeout > 0) setTimeout(close, timeout);

    overlay.appendChild(el);
  };

  const safeGet = (id) => document.getElementById(id);

  document.addEventListener('DOMContentLoaded', () => {
    const charts = [];

    const makeChart = (canvas, config) => {
      const chart = new Chart(canvas.getContext('2d'), config);
      charts.push(chart);
      return chart;
    };

    // สีเดิมตามที่คุณใช้
    const BLUE_SOLID = '#2B6CEB';
    const GRAY_SOLID = '#5F6F86';
    const BLUE_SET = ['#0B1F3B', '#15345F', '#2B6CEB', '#19B5FE', '#8ECFFF', '#D9EFFF'];

    const commonOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#15345F', font: { size: 10, weight: '700' } } },
        y: { beginAtZero: true, grid: { display: false }, ticks: { display: false } },
      },
    };

    // Monthly Trend
    const trendCanvas = safeGet('trendBar');
    if (trendCanvas) {
      makeChart(trendCanvas, {
        type: 'bar',
        data: {
          labels: parseJSON(trendCanvas.dataset.labels),
          datasets: [{
            data: parseJSON(trendCanvas.dataset.values),
            backgroundColor: BLUE_SOLID,
            borderRadius: 2,
            barPercentage: 0.62,
          }],
        },
        options: commonOptions,
      });
    }

    // donut factory
    const donutConfig = (labels, data, colors) => ({
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: colors,
          borderWidth: 0,
          hoverOffset: 4,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '74%',
        plugins: { legend: { display: false } },
      },
    });

    // Asset Type donut
    const typeCanvas = safeGet('typeDonut');
    if (typeCanvas) {
      makeChart(typeCanvas, donutConfig(
        parseJSON(typeCanvas.dataset.labels),
        parseJSON(typeCanvas.dataset.values),
        [BLUE_SET[0], BLUE_SET[2], BLUE_SET[3], BLUE_SET[4], BLUE_SET[5], BLUE_SET[1]]
      ));
    }

    // Status donut
    const statusCanvas = safeGet('statusDonut');
    if (statusCanvas) {
      makeChart(statusCanvas, donutConfig(
        ['Pending', 'In Progress', 'Completed', 'Other'],
        [
          Number(statusCanvas.dataset.pending || 0),
          Number(statusCanvas.dataset.progress || 0),
          Number(statusCanvas.dataset.completed || 0),
          Number(statusCanvas.dataset.other || 0),
        ],
        [BLUE_SET[3], BLUE_SET[2], BLUE_SET[0], BLUE_SET[5]]
      ));
    }

    // Department Bar
    const deptCanvas = safeGet('deptBar');
    if (deptCanvas) {
      makeChart(deptCanvas, {
        type: 'bar',
        data: {
          labels: parseJSON(deptCanvas.dataset.labels),
          datasets: [{
            data: parseJSON(deptCanvas.dataset.values),
            backgroundColor: GRAY_SOLID,
            borderRadius: 2,
            barPercentage: 0.62,
          }],
        },
        options: commonOptions,
      });
    }

    // ResizeObserver: responsive จริงทุกกรณี
    const safeResizeAll = () => requestAnimationFrame(() => charts.forEach((c) => c.resize()));
    if ('ResizeObserver' in window) {
      const ro = new ResizeObserver(() => safeResizeAll());
      document.querySelectorAll('.chart-box, .card, .dash-grid-2, .dash-wrap').forEach((el) => ro.observe(el));
    } else {
      window.addEventListener('resize', safeResizeAll);
    }

    // filter toggle (ถ้ามี)
    const btn = safeGet('filterToggle');
    const panel = safeGet('filtersPanel');
    if (btn && panel) {
      btn.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        safeResizeAll();
      });
    }

    // toast (รับค่าจาก Blade ผ่าน window.__DASH__)
    const payload = window.__DASH__ || {};
    if (payload?.message) {
      showToast({
        message: payload.message,
        type: payload.type || 'info',
        timeout: Number(payload.timeout || 3200),
      });
    }
  });
})();
