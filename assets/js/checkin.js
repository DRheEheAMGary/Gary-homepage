/**
 * 每日打卡
 * 与 React 版 DailyCheckIn 逻辑一致，云端接口改为 PHP
 */
(function () {
  const G = window.GARY || {};
  const api = G.apiBase || 'api/';
  const user = G.user || null;

  const wrapper = document.getElementById('checkin-wrapper');
  if (!wrapper) return;

  const popup = document.getElementById('checkin-popup');
  const grid = document.getElementById('checkin-grid');
  const monthLabel = document.getElementById('checkin-month-label');
  const statsEl = document.getElementById('checkin-stats');
  const btn = document.getElementById('checkin-btn');
  const btnLabel = document.getElementById('checkin-btn-label');
  const fortuneEl = document.getElementById('checkin-fortune');

  const STORAGE_KEY = 'daily-checkin-dates';
  const FORTUNE_KEY = 'daily-fortune';
  const FORTUNES = ['大凶', '凶', '中平', '小吉', '中吉', '大吉'];

  const now = new Date();
  let currentYear = now.getFullYear();
  let currentMonth = now.getMonth();
  let checkedDates = [];
  let fortune = null;
  let animating = false;
  let showPopup = false;
  let synced = false;

  function getToday() {
    const d = new Date();
    return getDateStr(d.getFullYear(), d.getMonth(), d.getDate());
  }

  function getDateStr(year, month, day) {
    return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
  }

  function loadCheckedDates() {
    try {
      const arr = JSON.parse(localStorage.getItem(STORAGE_KEY));
      return Array.isArray(arr) ? arr : [];
    } catch (e) { return []; }
  }

  function saveCheckedDates(dates) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(dates));
  }

  function calcStreak(set) {
    let streak = 0;
    const d = new Date();
    while (true) {
      const key = getDateStr(d.getFullYear(), d.getMonth(), d.getDate());
      if (set.has(key)) {
        streak++;
        d.setDate(d.getDate() - 1);
      } else break;
    }
    return streak;
  }

  function generateFortune() {
    return {
      value: Math.floor(Math.random() * 101),
      luck: FORTUNES[Math.floor(Math.random() * FORTUNES.length)]
    };
  }

  // 人品值颜色：红(0) → 黄(33) → 蓝(66) → 绿(100)
  function getValueColor(v) {
    const stops = [
      { p: 0, r: 239, g: 68, b: 68 },
      { p: 33, r: 245, g: 158, b: 11 },
      { p: 66, r: 59, g: 130, b: 246 },
      { p: 100, r: 34, g: 197, b: 94 }
    ];
    for (let i = 1; i < stops.length; i++) {
      if (v <= stops[i].p) {
        const a = stops[i - 1];
        const b = stops[i];
        const t = (v - a.p) / (b.p - a.p);
        return 'rgb(' + Math.round(a.r + (b.r - a.r) * t) + ',' +
          Math.round(a.g + (b.g - a.g) * t) + ',' +
          Math.round(a.b + (b.b - a.b) * t) + ')';
      }
    }
    return 'rgb(34,197,94)';
  }

  function getLuckColor(luck) {
    if (luck.indexOf('凶') !== -1) return 'var(--text)';
    if (luck === '中平') return '#22c55e';
    if (luck.indexOf('吉') !== -1) return '#ef4444';
    return 'var(--text)';
  }

  // ==================== 未登录：禁用 ====================
  if (!user) {
    if (btn) btn.title = '未登录';
    return;
  }

  checkedDates = loadCheckedDates();
  try {
    const raw = localStorage.getItem(FORTUNE_KEY);
    if (raw) {
      const data = JSON.parse(raw);
      if (data.date === getToday()) fortune = data;
    }
  } catch (e) { /* ignore */ }

  // ==================== 云端接口 ====================
  async function apiGet(action) {
    const r = await fetch(api + 'checkin.php?action=' + action);
    if (!r.ok) throw new Error('request failed');
    return r.json();
  }

  async function apiPost(action, body) {
    const r = await fetch(api + 'checkin.php?action=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    if (!r.ok) throw new Error('request failed');
    return r.json();
  }

  async function syncCloud() {
    try {
      const dates = await apiGet('dates');
      if (Array.isArray(dates) && dates.length) {
        checkedDates = dates;
        saveCheckedDates(dates);
      }
      const f = await apiGet('fortune');
      if (f && typeof f === 'object' && f.luck) {
        fortune = f;
        localStorage.setItem(FORTUNE_KEY, JSON.stringify(Object.assign({ date: getToday() }, f)));
      }
    } catch (e) { /* ignore */ }
    synced = true;
    render();
  }

  // ==================== 渲染 ====================
  function render() {
    renderCalendar();
    renderStats();
    renderButton();
    renderFortune();
  }

  function renderCalendar() {
    const todayStr = getToday();
    const set = new Set(checkedDates);
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    grid.innerHTML = '';
    for (let i = 0; i < firstDay; i++) {
      const empty = document.createElement('div');
      empty.className = 'checkin-cell empty';
      grid.appendChild(empty);
    }
    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = getDateStr(currentYear, currentMonth, d);
      const isToday = dateStr === todayStr;
      const isChecked = set.has(dateStr);

      const cell = document.createElement('div');
      cell.className = 'checkin-cell' + (isChecked ? ' checked' : '') + (isToday ? ' today' : '');
      cell.title = isToday ? (isChecked ? '已打卡 ✓' : '点击打卡') : dateStr;

      const num = document.createElement('span');
      num.className = 'checkin-day-num';
      num.textContent = d;
      cell.appendChild(num);

      if (isChecked) {
        const dot = document.createElement('span');
        dot.className = 'checkin-dot';
        dot.textContent = '✓';
        cell.appendChild(dot);
      }
      if (isToday) cell.addEventListener('click', toggleToday);
      grid.appendChild(cell);
    }

    const monthCount = checkedDates.filter(function (x) {
      const p = x.split('-').map(Number);
      return p[0] === currentYear && p[1] === currentMonth + 1;
    }).length;
    monthLabel.innerHTML = currentYear + '年 ' + (currentMonth + 1) +
      '月<span class="checkin-month-count">（已打卡 ' + monthCount + ' 天）</span>';
  }

  function renderStats() {
    const streak = calcStreak(new Set(checkedDates));
    statsEl.innerHTML = '';
    if (streak > 0) {
      const s = document.createElement('span');
      s.className = 'checkin-streak';
      s.title = '连续打卡天数';
      s.innerHTML = '<i class="fa-solid fa-fire checkin-streak-icon"></i>' + streak + ' 天';
      statsEl.appendChild(s);
    }
    if (streak >= 7) {
      const t = document.createElement('span');
      t.className = 'checkin-trophy';
      t.title = '连续7天达成!';
      t.innerHTML = '<i class="fa-solid fa-trophy"></i>';
      statsEl.appendChild(t);
    }
  }

  function renderButton() {
    const set = new Set(checkedDates);
    const streak = calcStreak(set);
    const isChecked = set.has(getToday());

    btn.classList.toggle('checked', isChecked);
    btn.classList.toggle('unauth', !user);
    btnLabel.textContent = '打卡';
    btn.title = isChecked ? '今日已打卡（点击查看日历）' : '点击打卡';

    const old = btn.querySelector('.checkin-btn-streak');
    if (old) old.remove();
    if (streak > 0) {
      const badge = document.createElement('span');
      badge.className = 'checkin-btn-streak';
      badge.innerHTML = '<i class="fa-solid fa-fire"></i> ' + streak;
      btn.appendChild(badge);
    }
  }

  function renderFortune() {
    fortuneEl.innerHTML = '';
    const set = new Set(checkedDates);
    if (set.has(getToday()) && fortune) {
      const value = document.createElement('span');
      value.className = 'checkin-fortune-value';
      value.style.color = getValueColor(fortune.value);
      value.textContent = '今日人品值 ' + fortune.value;

      const luck = document.createElement('span');
      luck.className = 'checkin-fortune-luck';
      luck.style.color = getLuckColor(fortune.luck);
      luck.textContent = '今日运势：' + fortune.luck;

      fortuneEl.appendChild(value);
      fortuneEl.appendChild(luck);
    }
  }

  // ==================== 打卡 ====================
  function toggleToday() {
    if (animating) return;
    const set = new Set(checkedDates);
    const todayStr = getToday();

    if (set.has(todayStr)) {
      showPopup = !showPopup;
      popup.classList.toggle('visible', showPopup);
      return;
    }

    animating = true;
    btn.classList.add('animating');
    setTimeout(function () { animating = false; btn.classList.remove('animating'); }, 600);

    const newFortune = generateFortune();
    fortune = newFortune;

    apiPost('dates', { date: todayStr }).catch(function () {});
    apiPost('fortune', newFortune).catch(function () {});

    checkedDates = checkedDates.concat([todayStr]);
    saveCheckedDates(checkedDates);
    localStorage.setItem(FORTUNE_KEY, JSON.stringify(Object.assign({ date: todayStr }, newFortune)));

    render();
  }

  if (btn) btn.addEventListener('click', toggleToday);

  // 点击弹窗外区域关闭
  document.addEventListener('click', function (e) {
    if (showPopup && !wrapper.contains(e.target)) {
      showPopup = false;
      popup.classList.remove('visible');
    }
  });

  // 月份导航
  const prevBtn = document.getElementById('checkin-prev');
  const nextBtn = document.getElementById('checkin-next');
  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      if (currentMonth === 0) { currentYear--; currentMonth = 11; }
      else currentMonth--;
      renderCalendar();
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (currentMonth === 11) { currentYear++; currentMonth = 0; }
      else currentMonth++;
      renderCalendar();
    });
  }

  // 跨天自动刷新
  const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1).getTime() - now.getTime();
  setTimeout(function () {
    checkedDates = loadCheckedDates();
    fortune = null;
    localStorage.removeItem(FORTUNE_KEY);
    render();
  }, midnight + 1000);

  render();
  if (!synced) syncCloud();
})();
