/**
 * 主题切换：auto → light → dark
 * 与 React 版 useTheme 行为一致，支持 View Transition API
 */
(function () {
  const VALID = ['auto', 'light', 'dark'];
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');
  const mq = window.matchMedia('(prefers-color-scheme: dark)');

  let theme = VALID.includes(localStorage.getItem('theme')) ? localStorage.getItem('theme') : 'auto';
  let systemDark = mq.matches;

  function isDark() {
    return theme === 'auto' ? systemDark : theme === 'dark';
  }

  function apply() {
    root.setAttribute('data-theme', isDark() ? 'dark' : 'light');
    if (!btn) return;
    const icon = btn.querySelector('i');
    const modeLabel = theme === 'auto' ? '自动（跟随系统）' : isDark() ? '深色模式' : '浅色模式';
    const nextLabel = theme === 'auto' ? '浅色' : theme === 'light' ? '深色' : '自动';
    btn.title = modeLabel + ' — 点击切换到' + nextLabel + '模式';
    if (icon) {
      icon.className = 'fa-solid ' + (theme === 'auto' ? 'fa-adjust' : isDark() ? 'fa-moon' : 'fa-sun');
    }
  }

  function toggle() {
    theme = theme === 'auto' ? 'light' : theme === 'light' ? 'dark' : 'auto';
    localStorage.setItem('theme', theme);
    apply();
  }

  mq.addEventListener('change', function (e) {
    systemDark = e.matches;
    apply();
  });

  if (btn) {
    btn.addEventListener('click', function () {
      if (document.startViewTransition) {
        const rect = btn.getBoundingClientRect();
        root.style.setProperty('--vt-x', (rect.left + rect.width / 2) + 'px');
        root.style.setProperty('--vt-y', (rect.top + rect.height / 2) + 'px');
        document.startViewTransition(toggle);
      } else {
        toggle();
      }
    });
  }

  apply();
})();
