/**
 * GitHub 贡献图自定义 tooltip
 */
(function () {
  const days = document.querySelectorAll('.gh-day[data-date]');
  if (!days.length) return;

  const tip = document.createElement('div');
  tip.className = 'gh-tooltip';
  document.body.appendChild(tip);

  function show(el) {
    const date = el.getAttribute('data-date');
    const count = el.getAttribute('data-count') || '0';
    tip.textContent = date + ' 共计 ' + count + ' 次贡献';
    tip.classList.add('visible');

    const r = el.getBoundingClientRect();
    const tr = tip.getBoundingClientRect();
    let left = r.left + r.width / 2 - tr.width / 2;
    left = Math.max(8, Math.min(window.innerWidth - tr.width - 8, left));
    let top = r.top - tr.height - 8;
    if (top < 8) top = r.bottom + 8;

    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
  }

  function hide() {
    tip.classList.remove('visible');
  }

  Array.prototype.forEach.call(days, function (d) {
    d.addEventListener('mouseenter', function () { show(d); });
    d.addEventListener('mouseleave', hide);
  });

  window.addEventListener('scroll', hide, true);
})();
