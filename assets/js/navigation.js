/**
 * 页面导航：滚动吸附、Tab 高亮、登录页切换、元素入场动画、回到顶部、复制
 */
(function () {
  const G = window.GARY || {};
  const container = document.getElementById('snap-container');
  const authWrapper = document.getElementById('auth-wrapper');
  const authSection = document.getElementById('auth');
  const tabBar = document.getElementById('tab-bar');
  const indicator = document.getElementById('tab-indicator');
  const loginBtn = document.getElementById('login-btn');
  const tabs = Array.prototype.slice.call(document.querySelectorAll('.tab-item'));
  const sections = Array.prototype.slice.call(document.querySelectorAll('#snap-container .snap-section'));
  const backToTop = document.getElementById('back-to-top');

  let active = 'home';
  let manual = false;
  let manualTimer = null;
  let target = 'home';
  let authOpen = (G.view === 'auth');

  // ==================== 元素入场动画 ====================
  const LIST_WRAPPERS = [
    'links-grid', 'contact-cards', 'game-cards', 'character-grid',
    'tag-list', 'feed-list', 'auth-container', 'auth-form-side', 'auth-form'
  ];
  const PER_ELEM_DELAY = 60;

  function prepareSection(el) {
    const pageEl = el.querySelector('.page');
    if (!pageEl) return;
    const items = [];

    (function collect(parent) {
      Array.prototype.forEach.call(parent.children, function (child) {
        if (LIST_WRAPPERS.some(function (cls) { return child.classList.contains(cls); })) {
          collect(child);
        } else {
          items.push(child);
        }
      });
    })(pageEl);

    items.forEach(function (child, i) {
      child.classList.add('animate-el');
      child.style.animationDelay = (i * PER_ELEM_DELAY) + 'ms';
    });
  }

  const prepared = new WeakSet();
  const timers = new WeakMap();

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      const el = entry.target;

      if (!entry.isIntersecting) {
        el.classList.remove('entered');
        return;
      }

      // 手动滚动期间，非目标页面保持隐藏
      if (manual && el.id !== target) {
        el.classList.remove('entered');
        return;
      }

      if (!prepared.has(el)) {
        prepared.add(el);
        prepareSection(el);
      }

      clearTimeout(timers.get(el));
      timers.set(el, setTimeout(function () { el.classList.add('entered'); }, 60));
    });
  }, { threshold: 0.3 });

  sections.forEach(function (s) { observer.observe(s); });
  if (authSection) observer.observe(authSection);

  // 立即准备并显示出目标页面（不依赖观察器异步回调）
  function reveal(el) {
    if (!el) return;
    if (!prepared.has(el)) {
      prepared.add(el);
      prepareSection(el);
    }
    el.classList.remove('entered');
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { el.classList.add('entered'); });
    });
  }

  // 相对滚动容器计算位置，比 scrollIntoView 更可靠
  function scrollToSection(el) {
    if (!el) return;
    if (!container) {
      el.scrollIntoView({ behavior: 'smooth' });
      return;
    }
    const rect = el.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();
    const top = container.scrollTop + (rect.top - containerRect.top);
    container.scrollTo({ top: top, behavior: 'smooth' });
  }

  // ==================== Tab 指示器 ====================
  function moveIndicator() {
    if (!indicator || !tabBar) return;
    const navRect = tabBar.getBoundingClientRect();

    // 登录页：滑块吸附到顶栏登录按钮（与原 React 版一致）
    if (authOpen && loginBtn) {
      const btnRect = loginBtn.getBoundingClientRect();
      indicator.style.transform = 'translateX(' + (btnRect.left - navRect.left) + 'px)';
      indicator.style.width = btnRect.width + 'px';
      indicator.style.opacity = '1';
      return;
    }

    const activeEl = tabBar.querySelector('.tab-item.active');
    if (!activeEl) {
      indicator.style.opacity = '0';
      return;
    }
    const btnRect = activeEl.getBoundingClientRect();
    indicator.style.transform = 'translateX(' + (btnRect.left - navRect.left) + 'px)';
    indicator.style.width = btnRect.width + 'px';
    indicator.style.opacity = '1';
  }

  function setActive(id) {
    active = id;
    tabs.forEach(function (t) {
      t.classList.toggle('active', t.dataset.section === id);
    });
    moveIndicator();
  }

  // ==================== 主视图 / 登录视图切换 ====================
  function toggleViews(open) {
    if (container) container.hidden = open;
    if (authWrapper) authWrapper.hidden = !open;
    tabs.forEach(function (t) { t.classList.toggle('dimmed', open); });
  }

  function openAuth(push) {
    if (authOpen) return;
    authOpen = true;
    toggleViews(true);
    if (backToTop) backToTop.classList.remove('visible');
    reveal(authSection);
    moveIndicator();
    if (push !== false && window.history && history.pushState) {
      history.pushState({ auth: true }, '', G.baseUrl + 'index.php?view=auth');
    }
  }

  function closeAuth(push, url) {
    if (!authOpen) return;
    authOpen = false;
    toggleViews(false);
    moveIndicator();
    if (push !== false && window.history && history.pushState) {
      history.pushState({ auth: false }, '', url || G.baseUrl);
    }
  }

  if (loginBtn) {
    loginBtn.addEventListener('click', function (e) {
      e.preventDefault();
      openAuth();
    });
  }

  window.addEventListener('popstate', function (e) {
    const wantAuth = !!(e.state && e.state.auth);
    if (wantAuth === authOpen) return;
    if (wantAuth) {
      authOpen = true;
      toggleViews(true);
      reveal(authSection);
      moveIndicator();
    } else {
      authOpen = false;
      toggleViews(false);
      moveIndicator();
    }
  });

  // ==================== 滚动监听 ====================
  if (container) {
    container.addEventListener('scroll', function () {
      if (backToTop) backToTop.classList.toggle('visible', container.scrollTop > 400);
      if (manual || authOpen) return;

      const centerY = container.scrollTop + container.clientHeight / 2;
      const containerRect = container.getBoundingClientRect();
      let closest = 'home';
      let closestDist = Infinity;

      sections.forEach(function (el) {
        const rect = el.getBoundingClientRect();
        const elCenter = rect.top - containerRect.top + rect.height / 2 + container.scrollTop;
        const dist = Math.abs(elCenter - centerY);
        if (dist < closestDist) {
          closestDist = dist;
          closest = el.id;
        }
      });

      if (closest !== active) setActive(closest);
    }, { passive: true });
  }

  // ==================== Tab 点击 ====================
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      const id = tab.dataset.section;
      const el = document.getElementById(id);
      if (!el) return;

      // 从登录页返回：关闭登录视图，滑块从登录按钮滑向目标 tab
      closeAuth(true, G.baseUrl + '#' + id);

      manual = true;
      target = id;
      setActive(id);
      clearTimeout(manualTimer);
      reveal(el);
      scrollToSection(el);
      manualTimer = setTimeout(function () { manual = false; }, 800);
    });
  });

  // ==================== 回到顶部 ====================
  if (backToTop && container) {
    backToTop.addEventListener('click', function () {
      container.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ==================== 复制联系方式 ====================
  Array.prototype.forEach.call(document.querySelectorAll('[data-copy]'), function (card) {
    card.addEventListener('click', function () {
      const value = card.getAttribute('data-copy');
      if (!value || !navigator.clipboard) return;
      navigator.clipboard.writeText(value).then(function () {
        const btn = card.querySelector('[data-copy-btn]');
        if (!btn) return;
        const def = btn.querySelector('.copy-icon-default');
        const done = btn.querySelector('.copy-icon-done');
        if (def) def.hidden = true;
        if (done) done.hidden = false;
        setTimeout(function () {
          if (def) def.hidden = false;
          if (done) done.hidden = true;
        }, 2000);
      });
    });
  });

  // ==================== 初始化 ====================
  // 首帧定位滑块（不播放过渡），避免加载时从左侧滑入的突兀效果
  if (indicator) indicator.classList.add('no-transition');
  setActive(active);
  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      if (indicator) indicator.classList.remove('no-transition');
    });
  });

  if (window.history && history.replaceState) {
    history.replaceState({ auth: authOpen }, '', window.location.href);
  }

  window.addEventListener('resize', moveIndicator);
  window.addEventListener('load', moveIndicator);
  document.addEventListener('DOMContentLoaded', moveIndicator);
  setTimeout(moveIndicator, 300);
})();
