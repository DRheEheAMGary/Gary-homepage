/**
 * 登录 / 注册页面交互 + 退出登录
 */
(function () {
  const G = window.GARY || {};
  const api = G.apiBase || 'api/';

  const tabsWrap = document.getElementById('auth-tabs');
  const tabButtons = Array.prototype.slice.call(document.querySelectorAll('[data-auth-tab]'));
  const indicator = document.getElementById('auth-tab-indicator');
  const panes = {
    login: document.getElementById('auth-pane-login'),
    register: document.getElementById('auth-pane-register')
  };
  const tokens = { login: null, register: null };
  const widgetIds = { login: null, register: null };
  let mode = 'login';
  let submitting = false;

  // ==================== Turnstile ====================
  // 跟随站点主题（而非系统主题）
  function siteTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  function renderWidget(name) {
    const el = document.querySelector('[data-turnstile="' + name + '"]');
    if (!el || el.dataset.rendered) return;
    if (!window.turnstile) {
      setTimeout(function () { renderWidget(name); }, 200);
      return;
    }
    el.dataset.rendered = '1';
    try {
      widgetIds[name] = window.turnstile.render(el, {
        sitekey: G.turnstileSiteKey,
        theme: siteTheme(),
        callback: function (t) { tokens[name] = t; updateSubmit(); },
        'expired-callback': function () { tokens[name] = null; updateSubmit(); },
        'error-callback': function () { tokens[name] = null; updateSubmit(); }
      });
    } catch (e) { /* ignore */ }
  }

  // 站点主题切换时重建 Turnstile，使其同步亮暗色
  function rerenderWidgets() {
    if (!window.turnstile) return;
    Object.keys(panes).forEach(function (name) {
      const el = document.querySelector('[data-turnstile="' + name + '"]');
      if (!el || !el.dataset.rendered) return;
      if (widgetIds[name] != null) {
        try { window.turnstile.remove(widgetIds[name]); } catch (e) { /* ignore */ }
      }
      widgetIds[name] = null;
      tokens[name] = null;
      el.dataset.rendered = '';
      setTimeout(function () {
        if (panes[name] && !panes[name].hidden) renderWidget(name);
      }, 0);
    });
    updateSubmit();
  }

  let renderedTheme = siteTheme();
  const themeObserver = new MutationObserver(function () {
    const next = siteTheme();
    if (next === renderedTheme) return; // 有效主题未变化时不重建
    renderedTheme = next;
    rerenderWidgets();
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

  function resetWidget(name) {
    tokens[name] = null;
    if (widgetIds[name] != null && window.turnstile) {
      try { window.turnstile.reset(widgetIds[name]); } catch (e) { /* ignore */ }
    }
    updateSubmit();
  }

  function updateSubmit() {
    const pane = panes[mode];
    if (!pane) return;
    const btn = pane.querySelector('[data-auth-submit]');
    if (btn) btn.disabled = submitting || !tokens[mode];
  }

  function showError(name, msg) {
    const p = panes[name] && panes[name].querySelector('[data-auth-error]');
    if (p) { p.textContent = msg; p.hidden = !msg; }
  }

  function showSuccess(name, msg) {
    const p = panes[name] && panes[name].querySelector('[data-auth-success]');
    if (p) { p.textContent = msg; p.hidden = !msg; }
  }

  function setLoading(name, on) {
    const pane = panes[name];
    if (!pane) return;
    const btn = pane.querySelector('[data-auth-submit]');
    if (!btn) return;
    btn.disabled = on || !tokens[name];
    if (name === 'login') btn.textContent = on ? '登录中...' : '登 录';
    else btn.textContent = on ? '注册中...' : '注 册';
  }

  function moveIndicator() {
    if (!indicator || !tabsWrap) return;
    const activeEl = tabsWrap.querySelector('.auth-tab.active');
    if (!activeEl) { indicator.style.opacity = '0'; return; }
    const cr = tabsWrap.getBoundingClientRect();
    const br = activeEl.getBoundingClientRect();
    indicator.style.transform = 'translateX(' + (br.left - cr.left) + 'px)';
    indicator.style.width = br.width + 'px';
  }

  function switchMode(m) {
    mode = m;
    tabButtons.forEach(function (t) { t.classList.toggle('active', t.dataset.authTab === m); });
    Object.keys(panes).forEach(function (k) {
      if (panes[k]) panes[k].hidden = (k !== m);
    });
    moveIndicator();
    renderWidget(m);
    updateSubmit();
  }

  tabButtons.forEach(function (t) {
    t.addEventListener('click', function () { switchMode(t.dataset.authTab); });
  });

  async function post(action, body) {
    const res = await fetch(api + 'auth.php?action=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json().catch(function () { return { message: '请求失败' }; });
    if (!res.ok) throw new Error(data.message || '请求失败');
    return data;
  }

  // ==================== 登录 ====================
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const fd = new FormData(loginForm);
      const username = String(fd.get('username') || '').trim();
      const password = String(fd.get('password') || '');
      if (!username || !password) { showError('login', '请输入用户名和密码'); return; }
      if (!tokens.login) { showError('login', '请完成人机验证'); return; }
      showError('login', '');
      showSuccess('login', '');
      submitting = true;
      setLoading('login', true);
      try {
        await post('login', { username: username, password: password, turnstile_token: tokens.login });
        window.location.href = G.baseUrl || 'index.php';
      } catch (err) {
        showError('login', err.message || '登录失败，请重试');
        resetWidget('login');
      } finally {
        submitting = false;
        setLoading('login', false);
      }
    });
  }

  // ==================== 注册 ====================
  const regForm = document.getElementById('register-form');
  if (regForm) {
    regForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const fd = new FormData(regForm);
      const username = String(fd.get('username') || '').trim();
      const email = String(fd.get('email') || '').trim();
      const password = String(fd.get('password') || '');
      const password2 = String(fd.get('password2') || '');
      if (!username || !email || !password) { showError('register', '请填写所有字段'); return; }
      if (password !== password2) { showError('register', '两次密码不一致'); return; }
      if (password.length < 6) { showError('register', '密码至少6位'); return; }
      if (!tokens.register) { showError('register', '请完成人机验证'); return; }
      showError('register', '');
      showSuccess('register', '');
      submitting = true;
      setLoading('register', true);
      try {
        const result = await post('register', {
          username: username,
          email: email,
          password: password,
          password2: password2,
          turnstile_token: tokens.register
        });
        if (result.need_verify) {
          showSuccess('register', '注册成功！验证邮件已发送至 ' + email + '，请查收并点击邮件中的链接完成验证');
        } else {
          showSuccess('register', '注册成功！请切换到登录页面进行登录');
        }
        regForm.reset();
        resetWidget('register');
      } catch (err) {
        showError('register', err.message || '注册失败，请重试');
        resetWidget('register');
      } finally {
        submitting = false;
        setLoading('register', false);
      }
    });
  }

  if (panes.login) {
    renderWidget('login');
    moveIndicator();
    updateSubmit();
    window.addEventListener('resize', moveIndicator);
  }

  // ==================== 用户悬浮菜单 + 退出登录 ====================
  const userWrap = document.getElementById('user-wrap');
  const userMenuBtn = document.getElementById('user-menu-btn');
  const userMenu = document.getElementById('user-menu');
  const logoutItem = document.getElementById('user-menu-logout');

  function closeUserMenu() {
    if (userMenu) userMenu.hidden = true;
    if (userWrap) userWrap.classList.remove('open');
    if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'false');
  }

  function openUserMenu() {
    if (userMenu) userMenu.hidden = false;
    if (userWrap) userWrap.classList.add('open');
    if (userMenuBtn) userMenuBtn.setAttribute('aria-expanded', 'true');
  }

  if (userMenuBtn && userMenu) {
    userMenuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (userMenu.hidden) openUserMenu(); else closeUserMenu();
    });
    document.addEventListener('mousedown', function (e) {
      if (userWrap && !userWrap.contains(e.target)) closeUserMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeUserMenu();
    });
  }

  if (logoutItem) {
    logoutItem.addEventListener('click', async function () {
      try { await fetch(api + 'auth.php?action=logout', { method: 'POST' }); } catch (e) { /* ignore */ }
      localStorage.removeItem('daily-checkin-dates');
      localStorage.removeItem('daily-fortune');
      window.location.reload();
    });
  }
})();
