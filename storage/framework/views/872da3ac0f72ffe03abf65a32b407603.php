
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $__env->yieldContent('title', config('app.name', 'Asset Repair')); ?></title>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?>

  <style>
    /* =========================
       Base Layout
       ========================= */
    .layout { display:grid; grid-template-columns: 260px 1fr; min-height:100dvh; transition:grid-template-columns .2s ease; }
    .sidebar { background:#0b1422; border-right:1px solid #1f2937; width:260px; transition:width .2s ease; }
    .topbar  { background:#0b1422; border-bottom:1px solid #1f2937; position:sticky; top:0; z-index:30; }
    .content { padding:1rem; }
    .footer  { border-top:1px solid #1f2937; padding:.75rem 1rem; color:#9ca3af; }

    @media (min-width: 1024px){
      .sidebar.collapsed { width:76px !important; }
      .layout.with-collapsed { grid-template-columns: 76px 1fr !important; }
      .layout.with-expanded  { grid-template-columns: 260px 1fr !important; }
    }

    @media (max-width: 1024px){
      .layout { grid-template-columns: 1fr; }
      .sidebar { position:fixed; inset:0 auto 0 0; width:270px; transform:translateX(-100%); transition:.2s; z-index:50;}
      .sidebar.open { transform:translateX(0); }
      .backdrop{ position:fixed; inset:0; background:#0007; display:none; z-index:40;}
      .backdrop.show{ display:block; }
    }

    @media (min-width: 1024px) {
      .sidebar.collapsed.hover-expand { width: 260px !important; }
      .sidebar.collapsed.hover-expand .menu-text { display: inline !important; }
      .sidebar.collapsed.hover-expand .menu-item { justify-content: flex-start; gap: .75rem; }
      .sidebar.hover-expand { box-shadow: 4px 0 12px rgba(0,0,0,.4); }
    }

    .sidebar .menu { padding: .5rem 0; }
    .sidebar .menu-item {
      display: grid;
      grid-template-columns: 48px 1fr;
      align-items: center;
      gap: .75rem;
      height: 44px;
      line-height: 1;
      padding: 0 .75rem;
      white-space: nowrap;
      overflow: hidden;
      transition: grid-template-columns .25s ease, padding .25s ease;
    }
    .sidebar .menu-item .icon-wrap {
      width: 48px;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .sidebar .menu-item .menu-text {
      overflow: hidden;
      text-overflow: ellipsis;
      opacity: 1;
      transition: opacity .18s ease;
    }

    @media (min-width: 1024px){
      .sidebar.collapsed .menu-item {
        grid-template-columns: 48px 0px;
        gap: 0;
        padding-right: .5rem;
        padding-left: .5rem;
      }
      .sidebar.collapsed .menu-item .menu-text {
        opacity: 0;
        pointer-events: none;
      }
      .sidebar.collapsed.hover-expand .menu-item {
        grid-template-columns: 48px 1fr;
        gap: .75rem;
        padding-left: .75rem;
        padding-right: .75rem;
      }
      .sidebar.collapsed.hover-expand .menu-item .menu-text {
        opacity: 1;
      }
    }

    .topbar--frost{
      background: linear-gradient(90deg, #0b1422 0%, #122136 55%, #0b1422 100%);
      border-bottom: 1px solid rgba(31,41,55,.8);
      position: sticky; top: 0; z-index: 30;
      backdrop-filter: saturate(130%) blur(6px);
      box-shadow:
          0 1px 0 rgba(0,0,0,.35),
          0 8px 24px rgba(0,0,0,.25);
    }

    .nav-brand{ display:inline-flex; align-items:center; gap:.6rem; text-decoration:none; }
    .brand-logo{
      width: 28px; height: 28px; border-radius:.5rem;
      filter: drop-shadow(0 0 10px rgba(239,68,68,.30))
              drop-shadow(0 0 18px rgba(239,68,68,.18));
      animation: glowPulse 3.6s ease-in-out infinite;
    }
    .nav-title{ font-weight:700; letter-spacing:.2px; color:#e5e7eb; }
    @keyframes glowPulse{
      0%,100%{ filter: drop-shadow(0 0 6px rgba(239,68,68,.25)) drop-shadow(0 0 16px rgba(239,68,68,.12)); }
      50%    { filter: drop-shadow(0 0 14px rgba(239,68,68,.45)) drop-shadow(0 0 26px rgba(239,68,68,.25)); }
    }
  </style>
</head>

<body class="bg-zinc-900 text-zinc-100">

  
    <div class="topbar px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 bg-[#0b1422] border-b border-zinc-800">
    
    <div class="flex items-center gap-3 flex-wrap">
        <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-2 text-zinc-100 no-underline">
        <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" class="w-7 h-7 opacity-90">
        <span class="font-semibold text-[15px] tracking-wide">
            <?php echo e(config('app.name','Asset Repair Dashboard')); ?>

        </span>
        </a>

        
        <div class="flex items-center gap-1.5 text-[13px] font-medium">
        <?php echo $__env->yieldContent('topbadges'); ?>
        </div>
    </div>

    
    <div class="flex items-center gap-2 ml-auto">
        <button id="btnSidebar"
                class="lg:hidden inline-flex items-center px-2 py-1 rounded-md border border-white/20 text-zinc-100/90 bg-white/5 hover:bg-white/10 transition-colors"
                aria-controls="side" aria-expanded="false" title="เมนู">
        ☰
        </button>

        
        <a href="<?php echo e(route('login')); ?>"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/20 border border-white/20 text-zinc-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
        </svg>
        <span class="hidden md:inline">Logout</span>
        </a>
    </div>
    </div>

  
  <div id="layout" class="layout">
    
    <aside id="side" class="sidebar" aria-label="Sidebar">
      <?php if (! empty(trim($__env->yieldContent('sidebar')))): ?>
        <?php echo $__env->yieldContent('sidebar'); ?>
      <?php else: ?>
        <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
      <?php endif; ?>
    </aside>

    <div id="backdrop" class="backdrop lg:hidden" aria-hidden="true"></div>

    
    <main class="content">
      <?php if (! empty(trim($__env->yieldContent('page-header')))): ?>
        <div class="mb-4"><?php echo $__env->yieldContent('page-header'); ?></div>
      <?php endif; ?>

      <?php if(session('ok')): ?>
        <div class="mb-4 p-3 rounded bg-emerald-900/40 text-emerald-100">
          <?php echo e(session('ok')); ?>

        </div>
      <?php endif; ?>

      <?php echo $__env->yieldContent('content'); ?>
    </main>
  </div>

  
  <div class="footer text-xs">
    <?php if (! empty(trim($__env->yieldContent('footer')))): ?>
      <?php echo $__env->yieldContent('footer'); ?>
    <?php else: ?>
      © <?php echo e(date('Y')); ?> <?php echo e(config('app.name','Asset Repair Dashboard')); ?> • Build <?php echo e(app()->version()); ?>

    <?php endif; ?>
  </div>

  
  <script>
    // ===== Mobile sidebar open/close =====
    const btn = document.getElementById('btnSidebar');
    const side = document.getElementById('side');
    const bd   = document.getElementById('backdrop');
    function closeSide(){ side.classList.remove('open'); bd.classList.remove('show'); btn?.setAttribute('aria-expanded','false'); }
    function openSide(){ side.classList.add('open'); bd.classList.add('show'); btn?.setAttribute('aria-expanded','true'); }
    btn && btn.addEventListener('click', ()=> side.classList.contains('open') ? closeSide() : openSide());
    bd && bd.addEventListener('click', closeSide);

    // ===== Desktop collapse/expand persist =====
    const KEY = 'app.sidebar.collapsed';
    const layout = document.getElementById('layout');
    function applyCollapsedState(collapsed){
      if (collapsed){
        side.classList.add('collapsed');
        layout.classList.add('with-collapsed');
        layout.classList.remove('with-expanded');
      } else {
        side.classList.remove('collapsed','hover-expand');
        layout.classList.remove('with-collapsed','with-expanded');
      }
    }

    const saved = localStorage.getItem(KEY);
    if (saved === null) {
      const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
      applyCollapsedState(isDesktop);
      localStorage.setItem(KEY, isDesktop ? '1' : '0');
    } else {
      applyCollapsedState(saved === '1');
    }

    // ===== Hover expand =====
    let hoverBound = false, hoverTimeout;
    function onEnter(){
      if (side.classList.contains('collapsed')) {
        clearTimeout(hoverTimeout);
        side.classList.add('hover-expand');
        layout.classList.add('with-expanded');
        layout.classList.remove('with-collapsed');
      }
    }
    function onLeave(){
      if (side.classList.contains('collapsed')) {
        hoverTimeout = setTimeout(()=>{
          side.classList.remove('hover-expand');
          layout.classList.remove('with-expanded');
          layout.classList.add('with-collapsed');
        },150);
      }
    }
    function bindHover(){
      if (hoverBound) return;
      side.addEventListener('mouseenter', onEnter);
      side.addEventListener('mouseleave', onLeave);
      hoverBound = true;
    }
    function unbindHover(){
      if (!hoverBound) return;
      side.removeEventListener('mouseenter', onEnter);
      side.removeEventListener('mouseleave', onLeave);
      hoverBound = false;
      side.classList.remove('hover-expand');
      layout.classList.remove('with-expanded');
    }

    const mql = window.matchMedia('(max-width: 1024px)');
    function handleResize(e){
      if (e.matches){
        unbindHover();
        side.classList.remove('hover-expand');
        layout.classList.remove('with-expanded');
      } else {
        bindHover();
        const s = localStorage.getItem(KEY);
        applyCollapsedState(s === '1');
      }
    }
    handleResize(mql);
    mql.addEventListener?.('change', handleResize);
  </script>
</body>
</html>
<?php /**PATH /Users/fenyb_/Documents/Asset-Repair-Management-System/resources/views/layouts/app.blade.php ENDPATH**/ ?>