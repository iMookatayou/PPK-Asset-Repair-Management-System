
<?php
  $toast = session('toast');
  if ($toast) { session()->forget('toast'); }

  $type     = $toast['type']     ?? null;          // success|info|warning|error
  $message  = $toast['message']  ?? null;
  $position = $toast['position'] ?? 'tc';          // tr|tl|br|bl|center|tc|bc
  $timeout  = (int)($toast['timeout'] ?? 3200);

  $lottieGreen = asset('lottie/lock_with_green_tick.json');
  $lottieRed   = asset('lottie/lock_with_red_tick.json');
  $lottieMap = [
    'success' => $lottieGreen,
    'info'    => $lottieGreen,
    'warning' => $lottieGreen,
    'error'   => $lottieRed,
  ];
?>

<style>
  .toast-overlay{position:fixed;inset:0;z-index:100001;pointer-events:none}
  .toast-pos{display:flex;width:100%;height:100%;padding:1rem}
  .toast-pos.tr{align-items:flex-start;justify-content:flex-end}
  .toast-pos.tl{align-items:flex-start;justify-content:flex-start}
  .toast-pos.br{align-items:flex-end;justify-content:flex-end}
  .toast-pos.bl{align-items:flex-end;justify-content:flex-start}
  .toast-pos.center{align-items:center;justify-content:center}
  .toast-pos.tc{align-items:flex-start;justify-content:center;padding-top:calc(var(--topbar-h,0px) + .75rem)}
  .toast-pos.bc{align-items:flex-end;justify-content:center;padding-bottom:.75rem}

  /* แทนที่เดิมของ .toast-card ทั้งบล็อก */
  .toast-card{
    pointer-events:auto;
    width:max-content;
    max-width:min(92vw, 560px);
    min-width:400px;

    background:#fff;
    border-radius:1rem;
    border:1px solid #e5eef7;
    box-shadow:0 14px 48px rgba(15,23,42,.14);

    opacity:0;
    transform:translateY(-6px);
    transition:opacity .22s ease, transform .22s ease;

    display:flex;
    align-items:center;
    gap:.9rem;
    padding:14px 16px 14px 16px;

    position:relative;
    overflow:hidden;
  }

  .toast-card.show{
    opacity:1;
    transform:translateY(0);
  }

  .toast-icon{
    flex:0 0 32px;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .toast-msg{
    font-size:15px;
    color:#0f172a;
    line-height:1.45;
    white-space:normal;
    word-break:break-word;
    flex:1;
  }

  .toast-close{
    border:0;
    background:transparent;
    font-size:16px;
    color:#64748b;
    cursor:pointer;
  }

  .toast-close:hover{
    color:#0f172a;
  }

  /* Progress bar */
  .toast-bar{
    position:absolute;
    bottom:0;
    left:0;
    height:4px;
    width:100%;
    background:#f1f5f9;
  }

  .toast-fill{
    height:4px;
    width:0;
    transition:width linear;
  }

  .fill-success{background:#10b981;}
  .fill-info{background:#3b82f6;}
  .fill-warning{background:#f59e0b;}
  .fill-error{background:#ef4444;}

  @media (max-width:420px){.toast-card{min-width:calc(100vw - 2rem)}}
</style>

<div class="toast-overlay" aria-live="polite" aria-atomic="true"></div>

<script>
(function(){
  const LOTTIE = {
    success: <?php echo json_encode($lottieMap['success'], 15, 512) ?>,
    info:    <?php echo json_encode($lottieMap['info'], 15, 512) ?>,
    warning: <?php echo json_encode($lottieMap['warning'], 15, 512) ?>,
    error:   <?php echo json_encode($lottieMap['error'], 15, 512) ?>,
  };

  function ensurePos(position){
    const overlay = document.querySelector('.toast-overlay');
    overlay.innerHTML = '';
    const posEl = document.createElement('div');
    posEl.className = 'toast-pos ' + position;
    overlay.appendChild(posEl);
    return { overlay, posEl };
  }

  const FORCE_POSITION = 'tc';

  function showToast({type='info', message='', position='tc', timeout=3200} = {}){
    position = FORCE_POSITION || position || 'tc';
    const allowed = ['tr','tl','br','bl','center','tc','bc'];
    if (!allowed.includes(position)) position = 'tc';
    timeout = Number(timeout) || 3200;

    const { posEl } = ensurePos(position);

    const card = document.createElement('section');
    card.className = `toast-card toast-${type}`;
    card.setAttribute('role','status');

    card.innerHTML = `
      <div class="toast-icon">
        <lottie-player src="${LOTTIE[type] || LOTTIE.success}" style="width:32px;height:32px" background="transparent" speed="1" autoplay></lottie-player>
      </div>

      <div class="toast-msg">${message}</div>

      <button class="toast-close">&times;</button>

      <div class="toast-bar"><div class="toast-fill fill-${type}"></div></div>
    `;

    posEl.appendChild(card);

    const fill = card.querySelector('.toast-fill');

    requestAnimationFrame(() => {
      card.classList.add('show');
      requestAnimationFrame(() => {
        fill.style.transition = `width ${timeout}ms linear`;
        fill.style.width = '100%';
      });
    });

    let timer = setTimeout(close, timeout + 60);
    function close(){
      card.classList.remove('show');
      setTimeout(()=> card.remove(), 200);
    }

    card.querySelector('.toast-close').addEventListener('click', close);
    card.addEventListener('mouseenter', () => {
      fill.style.transition = 'none';
      const w = getComputedStyle(fill).width;
      fill.style.width = w;
      clearTimeout(timer);
    });
    card.addEventListener('mouseleave', () => {
      const done = parseFloat(getComputedStyle(fill).width) / card.clientWidth;
      const remainMs = Math.max(0, 1 - done) * timeout;
      requestAnimationFrame(() => {
        fill.style.transition = `width ${remainMs}ms linear`;
        fill.style.width = '100%';
      });
      timer = setTimeout(close, remainMs + 50);
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); }, { once:true });
  }

  window.showToast = showToast;
  window.addEventListener('app:toast', e => showToast(e.detail || {}));

  <?php if($type && $message): ?>
  document.addEventListener('DOMContentLoaded', function(){
    showToast({ type: <?php echo json_encode($type, 15, 512) ?>, message: <?php echo json_encode($message, 15, 512) ?>, position: <?php echo json_encode($position, 15, 512) ?>, timeout: <?php echo json_encode($timeout, 15, 512) ?> });
  });
  <?php endif; ?>
})();
</script>
<?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/components/toast.blade.php ENDPATH**/ ?>