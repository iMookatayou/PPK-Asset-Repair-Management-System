{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', config('app.name', 'Asset Repair'))</title>
  @vite(['resources/css/app.css','resources/js/app.js'])

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

  {{-- ======= TOPBAR: DASHBOARD + BADGES ======= --}}
  <div class="topbar px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 bg-[#0b1422] border-b border-zinc-800">
    <div class="flex items-center gap-3 flex-wrap">
      <a href="{{ url('/') }}" class="flex items-center gap-2 text-zinc-100 no-underline">
        <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel" class="w-7 h-7 opacity-90">
        <span class="font-semibold text-[15px] tracking-wide">
          {{ config('app.name','Asset Repair Dashboard') }}
        </span>
      </a>

      {{-- Stats badges --}}
      <div class="flex items-center gap-1.5 text-[13px] font-medium">
        @yield('topbadges')
      </div>
    </div>
  </div>

  {{-- ======= LAYOUT GRID ======= --}}
  <div id="layout" class="layout">
    {{-- Sidebar --}}
    <aside id="side" class="sidebar" aria-label="Sidebar">
      @hasSection('sidebar')
        @yield('sidebar')
      @else
        <x-sidebar />
      @endif
    </aside>

    <div id="backdrop" class="backdrop lg:hidden" aria-hidden="true"></div>

    {{-- Main content area --}}
    <main class="content">
      @hasSection('page-header')
        <div class="mb-4">@yield('page-header')</div>
      @endif

      @if (session('ok'))
        <div class="mb-4 p-3 rounded bg-emerald-900/40 text-emerald-100">
          {{ session('ok') }}
        </div>
      @endif

      @yield('content')
    </main>
  </div>

  {{-- Footer --}}
  <div class="footer text-xs">
    @hasSection('footer')
      @yield('footer')
    @else
      © {{ date('Y') }} {{ config('app.name','Asset Repair Dashboard') }} • Build {{ app()->version() }}
    @endif
  </div>

  {{-- Scripts --}}
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
