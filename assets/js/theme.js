/**
 * 主题切换：悬浮菜单选择 自动 / 浅色 / 深色
 * 保留 View Transition 圆形揭开动画
 */
(function () {
  const VALID = ['auto', 'light', 'dark'];
  const ICONS = { auto: 'fa-adjust', light: 'fa-sun', dark: 'fa-moon' };
  const root = document.documentElement;
  const picker = document.getElementById('theme-picker');
  const btn = document.getElementById('theme-toggle');
  const menu = document.getElementById('theme-menu');
  const options = Array.prototype.slice.call(document.querySelectorAll('.theme-option'));
  const mq = window.matchMedia('(prefers-color-scheme: dark)');

  const stored = localStorage.getItem('theme');
  let theme = VALID.includes(stored) ? stored : 'auto';
  let systemDark = mq.matches;

  function isDark() {
    return theme === 'auto' ? systemDark : theme === 'dark';
  }

  function apply() {
    root.setAttribute('data-theme', isDark() ? 'dark' : 'light');
    if (btn) {
      const icon = btn.querySelector('i');
      if (icon) icon.className = 'fa-solid ' + ICONS[theme];
    }
    options.forEach(function (o) {
      o.classList.toggle('active', o.dataset.themeValue === theme);
    });
  }

  function setTheme(next, animate) {
    if (!VALID.includes(next)) return;
    const changed = next !== theme;
    const commit = function () {
      theme = next;
      localStorage.setItem('theme', theme);
      apply();
    };
    if (changed && animate && document.startViewTransition && btn) {
      const rect = btn.getBoundingClientRect();
      root.style.setProperty('--vt-x', (rect.left + rect.width / 2) + 'px');
      root.style.setProperty('--vt-y', (rect.top + rect.height / 2) + 'px');
      document.startViewTransition(commit);
    } else {
      commit();
    }
  }

  function openMenu() {
    if (!menu) return;
    menu.hidden = false;
    if (picker) picker.classList.add('open');
    if (btn) btn.setAttribute('aria-expanded', 'true');
  }

  function closeMenu() {
    if (!menu) return;
    menu.hidden = true;
    if (picker) picker.classList.remove('open');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  if (btn && menu) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (menu.hidden) openMenu(); else closeMenu();
    });

    options.forEach(function (o) {
      o.addEventListener('click', function (e) {
        e.stopPropagation();
        setTheme(o.dataset.themeValue, true);
        closeMenu();
      });
    });

    document.addEventListener('mousedown', function (e) {
      if (picker && !picker.contains(e.target)) closeMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMenu();
    });
  }

  mq.addEventListener('change', function (e) {
    systemDark = e.matches;
    if (theme === 'auto') apply();
  });

  apply();
})();
