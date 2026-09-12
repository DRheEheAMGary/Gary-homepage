/**
 * 歌词打字机
 * 与 React 版 LyricsTypewriter 行为一致
 */
(function () {
  const textEl = document.getElementById('lyrics-text');
  if (!textEl) return;
  const lyrics = (window.GARY && window.GARY.lyrics) || [];
  if (!lyrics.length) return;

  const SHORT_LIMIT = 20;
  let current = lyrics[Math.floor(Math.random() * lyrics.length)];
  let display = '';
  let phase = 'typing';
  let index = 0;
  let timer = null;

  function randomLyric() {
    return lyrics[Math.floor(Math.random() * lyrics.length)];
  }

  function tick() {
    clearTimeout(timer);
    const isShort = current.length <= SHORT_LIMIT;
    const typeSpeed = isShort ? 60 : 80;
    const deleteSpeed = isShort ? 35 : 45;
    const waitTime = isShort ? 2000 : 3000;

    if (phase === 'typing') {
      if (index < current.length) {
        timer = setTimeout(function () {
          display += current[index];
          index++;
          textEl.textContent = display;
          tick();
        }, typeSpeed);
      } else {
        timer = setTimeout(function () {
          phase = 'waiting';
          tick();
        }, waitTime);
      }
    } else if (phase === 'waiting') {
      phase = 'deleting';
      tick();
    } else if (phase === 'deleting') {
      if (index > 0) {
        timer = setTimeout(function () {
          display = display.slice(0, -1);
          index--;
          textEl.textContent = display;
          tick();
        }, deleteSpeed);
      } else {
        current = randomLyric();
        display = '';
        index = 0;
        phase = 'typing';
        textEl.textContent = '';
        tick();
      }
    }
  }

  tick();
})();
