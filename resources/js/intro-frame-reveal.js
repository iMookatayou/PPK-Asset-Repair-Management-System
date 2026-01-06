// resources/js/intro-frame-reveal.js
// Frame Assembly: วาดกรอบทีละส่วน → fade dim → ปล่อย UI พร้อมกัน
// รองรับ click / Esc / Space เพื่อ skip

const q = (sel) => document.querySelector(sel);

const DEFAULTS = {
  frameRx: 14,
  frameGap: 10,
  drawMs: 680,
  betweenMs: 130,
  dimFadeMs: 240,
  afterRevealMs: 80,
  keepOverlayMs: 140,
  fadeOutMs: 220,
};

function clamp(n, a, b){ return Math.max(a, Math.min(b, n)); }
function sleep(ms){ return new Promise(res => setTimeout(res, ms)); }

function rectPad(r, pad){
  return {
    x: Math.max(0, r.left - pad),
    y: Math.max(0, r.top - pad),
    w: Math.max(0, r.width + pad * 2),
    h: Math.max(0, r.height + pad * 2),
  };
}

function getRectSafe(el){
  if (!el) return null;
  const r = el.getBoundingClientRect();
  if (!r.width && !r.height) return null;
  return r;
}

function removeAllOverlays(){
  document.querySelectorAll('.intro-frame-overlay').forEach(el => el.remove());
}

function makeOverlay() {
  const wrap = document.createElement('div');
  wrap.className = 'intro-frame-overlay';
  wrap.setAttribute('aria-hidden', 'true');

  const dim = document.createElement('div');
  dim.className = 'dim';
  wrap.appendChild(dim);

  const svgNS = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(svgNS, 'svg');
  svg.setAttribute('viewBox', `0 0 ${window.innerWidth} ${window.innerHeight}`);
  svg.setAttribute('preserveAspectRatio', 'none');

  const g = document.createElementNS(svgNS, 'g');
  svg.appendChild(g);

  wrap.appendChild(svg);
  document.body.appendChild(wrap);

  return { wrap, dim, svg, g };
}

function createRectFrame(g, x, y, w, h, rx){
  const svgNS = 'http://www.w3.org/2000/svg';

  const fill = document.createElementNS(svgNS, 'rect');
  fill.setAttribute('x', x);
  fill.setAttribute('y', y);
  fill.setAttribute('width', w);
  fill.setAttribute('height', h);
  fill.setAttribute('rx', rx);
  fill.setAttribute('ry', rx);
  fill.setAttribute('class', 'frame-fill');
  fill.style.opacity = '0';
  g.appendChild(fill);

  const rect = document.createElementNS(svgNS, 'rect');
  rect.setAttribute('x', x);
  rect.setAttribute('y', y);
  rect.setAttribute('width', w);
  rect.setAttribute('height', h);
  rect.setAttribute('rx', rx);
  rect.setAttribute('ry', rx);
  rect.setAttribute('class', 'frame');

  // ถ้ามี theme var ในระบบ (เช่น --ppk-blue) จะใช้เป็นสีเส้น
  const cssVar = getComputedStyle(document.documentElement).getPropertyValue('--ppk-blue')?.trim();
  if (cssVar) rect.style.stroke = cssVar;

  g.appendChild(rect);
  return { rect, fill };
}

function dashForRect(rectEl){
  const w = parseFloat(rectEl.getAttribute('width') || '0');
  const h = parseFloat(rectEl.getAttribute('height') || '0');
  const len = Math.max(1, 2 * (w + h));
  rectEl.style.strokeDasharray = String(len);
  rectEl.style.strokeDashoffset = String(len);
  return len;
}

async function drawOne(node, ms, skip){
  if (skip.skipped) return;

  const { rect, fill } = node;
  const len = dashForRect(rect);

  const draw = rect.animate(
    [{ strokeDashoffset: len }, { strokeDashoffset: 0 }],
    { duration: ms, easing: 'cubic-bezier(.22,.9,.22,1)', fill: 'forwards' }
  );

  fill.animate(
    [{ opacity: 0 }, { opacity: 1 }],
    { duration: Math.max(180, ms - 160), easing: 'ease-out', fill: 'forwards', delay: 120 }
  );

  try { await draw.finished; } catch (_) {}
}

function buildTargets(){
  // navbar ของคุณใช้ .navbar-pinwheel
  const navbar = q('.navbar-pinwheel') || q('.app-navbar') || q('.navbar-hero');
  const side   = q('#side');
  const main   = q('#main');
  const footer = q('footer') || q('.footer-hero');
  const teamTab = q('#teamTab'); // optional
  return { navbar, side, main, footer, teamTab };
}

/** สร้างตัวควบคุม skip (คลิก/คีย์เพื่อข้าม) */
export function createIntroSkipController(){
  const state = { skipped: false };

  const cancel = () => {
    if (state.skipped) return;
    state.skipped = true;

    document.documentElement.classList.remove('intro-pending');
    removeAllOverlays();
    window.dispatchEvent(new CustomEvent('introReveal:done'));
    cleanup();
  };

  const onPointer = () => cancel();
  const onKey = (e) => {
    if (e.key === 'Escape' || e.key === 'Enter' || e.key === ' ') cancel();
  };

  const cleanup = () => {
    window.removeEventListener('pointerdown', onPointer);
    window.removeEventListener('keydown', onKey);
  };

  window.addEventListener('pointerdown', onPointer);
  window.addEventListener('keydown', onKey);

  return { state, cancel, cleanup };
}

/**
 * เริ่มวาด/ประกอบ UI
 * @param {{skipped:boolean}} skipState - จาก createIntroSkipController().state
 * @param {object} options - ปรับ timing ได้
 */
export async function startIntroFrameReveal(skipState, options = {}) {
  const opt = { ...DEFAULTS, ...options };

  // reduce motion -> ข้าม
  if (window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches) {
    document.documentElement.classList.remove('intro-pending');
    window.dispatchEvent(new CustomEvent('introReveal:done'));
    return;
  }

  await new Promise(requestAnimationFrame);

  const { navbar, side, main, footer, teamTab } = buildTargets();
  const rNav  = getRectSafe(navbar);
  const rSide = getRectSafe(side);
  const rMain = getRectSafe(main);
  const rFoot = getRectSafe(footer);
  const rTeam = getRectSafe(teamTab);

  if (!rNav && !rSide && !rMain && !rFoot) {
    document.documentElement.classList.remove('intro-pending');
    window.dispatchEvent(new CustomEvent('introReveal:done'));
    return;
  }

  const { wrap, dim, svg, g } = makeOverlay();
  svg.setAttribute('viewBox', `0 0 ${window.innerWidth} ${window.innerHeight}`);

  const frames = [];
  const pad = opt.frameGap;

  if (rNav)  frames.push(rectPad(rNav,  pad));
  if (rSide) frames.push(rectPad(rSide, pad));
  if (rMain) frames.push(rectPad(rMain, pad));
  if (rFoot) frames.push(rectPad(rFoot, Math.min(8, pad)));
  if (rTeam) frames.push(rectPad(rTeam, 6));

  const rx = clamp(opt.frameRx, 8, 18);
  const nodes = frames.map(f => createRectFrame(g, f.x, f.y, f.w, f.h, rx));

  for (let i = 0; i < nodes.length; i++){
    if (skipState.skipped) break;
    await drawOne(nodes[i], opt.drawMs, skipState);
    if (skipState.skipped) break;
    await sleep(opt.betweenMs);
  }

  if (!skipState.skipped) {
    dim.animate([{ opacity: 1 }, { opacity: 0 }], { duration: opt.dimFadeMs, easing: 'ease-out', fill:'forwards' });
    await sleep(opt.dimFadeMs + opt.afterRevealMs);
  }

  document.documentElement.classList.remove('intro-pending');

  if (!skipState.skipped) {
    await sleep(opt.keepOverlayMs);
    wrap.animate([{ opacity: 1 }, { opacity: 0 }], { duration: opt.fadeOutMs, easing:'ease-out', fill:'forwards' })
      .onfinish = () => wrap.remove();
  } else {
    wrap.remove();
  }

  window.dispatchEvent(new CustomEvent('introReveal:done'));
}
