<!doctype html>
<html lang="th" data-theme="govclean">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  {{-- CSRF token สำหรับ JS ที่อาจต้องดึงค่าไปใช้กับ fetch / AJAX --}}
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="theme-color" content="#0E2B51">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <title>@yield('title', config('app.name', 'Asset Repair'))</title>

  @yield('head')

  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('styles')
  @stack('head')

  <style>
    @font-face {
      font-family: 'Sarabun';
      font-style: normal;
      font-weight: 400;
      src: url('{{ asset('fonts/Sarabun-Regular.woff2') }}') format('woff2'),
           url('{{ asset('fonts/Sarabun-Regular.woff') }}') format('woff');
    }
    @font-face {
      font-family: 'Sarabun';
      font-style: normal;
      font-weight: 500;
      src: url('{{ asset('fonts/Sarabun-Medium.woff2') }}') format('woff2'),
           url('{{ asset('fonts/Sarabun-Medium.woff') }}') format('woff');
    }
    @font-face {
      font-family: 'Sarabun';
      font-style: normal;
      font-weight: 600;
      src: url('{{ asset('fonts/Sarabun-SemiBold.woff2') }}') format('woff2'),
           url('{{ asset('fonts/Sarabun-SemiBold.woff') }}') format('woff');
    }
    @font-face {
      font-family: 'Sarabun';
      font-style: normal;
      font-weight: 700;
      src: url('{{ asset('fonts/Sarabun-Bold.woff2') }}') format('woff2'),
           url('{{ asset('fonts/Sarabun-Bold.woff') }}') format('woff');
    }

    html, body {
      font-family: 'Sarabun', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
                   Roboto, "Helvetica Neue", Arial, sans-serif;
      font-weight: 400;
      letter-spacing: 0.2px;
    }

    :root{
      color-scheme: light;
      --nav-h: 86px;    
    }

    body{ padding-top: var(--nav-h); }

    .sticky-under-topbar{ position: sticky; top: calc(var(--nav-h) + .5rem); z-index: 10; }

    @media (max-width: 992px){
      :root{ --nav-h: 72px; }
    }

    .layout{
      display:grid;
      grid-template-columns: 260px 1fr;
      min-height:100vh;
      transition:grid-template-columns .2s ease;
      background: hsl(var(--b2));
      color: hsl(var(--bc));
    }
    .content{ padding:1rem; }
    .sidebar{
      background:#ffffff;
      border-right:1px solid hsl(var(--b2));
      width:260px;           
      transition:width .2s ease;
    }

    @media (min-width:1024px){
      .sidebar.compact{ width:180px !important; }
      .layout.with-compact{ grid-template-columns:180px 1fr !important; }
    }
    @media (min-width:1024px){
      .sidebar.collapsed{ width:76px !important; }
      .layout.with-collapsed{ grid-template-columns:76px 1fr !important; }
      .layout.with-expanded{ grid-template-columns:260px 1fr !important; }
    }

    @media (max-width:1024px){
      .layout{ grid-template-columns:1fr; }
      .sidebar{
        position:fixed; inset:var(--nav-h) auto 0 0; width:270px; 
        transform:translateX(-100%); transition:.2s; z-index:50;
        box-shadow: 4px 0 24px rgba(0,0,0,.06);
      }
      .sidebar.open{ transform:translateX(0); }
      .backdrop{ position:fixed; inset:var(--nav-h) 0 0 0; background:rgba(0,0,0,.45); display:none; z-index:40; }
      .backdrop.show{ display:block; }
    }

    @media (min-width:1024px){
      .sidebar.collapsed.hover-expand{ width:260px !important; }
      .sidebar.collapsed.hover-expand .menu-text{ display:inline !important; }
      .sidebar.collapsed.hover-expand .menu-item{ justify-content:flex-start; gap:.75rem; }
      .sidebar.hover-expand{ box-shadow: 4px 0 12px rgba(0,0,0,.06); }
    }

    .sidebar .menu{ padding:.5rem 0; }
    .sidebar .menu-item{
      display:grid; grid-template-columns:48px 1fr; align-items:center;
      gap:.75rem; height:44px; line-height:1; padding:0 .75rem;
      white-space:nowrap; overflow:hidden;
      transition: grid-template-columns .25s ease, padding .25s ease, background .15s ease;
      color:hsl(var(--bc));
    }
    .sidebar .menu-item:hover{ background:hsl(var(--b2)); }
    .sidebar .menu-item .icon-wrap{
      width:48px; height:44px; display:inline-flex; align-items:center; justify-content:center;
      color: color-mix(in srgb, hsl(var(--bc)) 60%, transparent);
      position:relative;
    }
    .sidebar .menu-item .menu-text{ overflow:hidden; text-overflow:ellipsis; opacity:1; transition:opacity .18s ease; }

    @media (min-width:1024px){
      .sidebar.collapsed .menu-item{ grid-template-columns:48px 0px; gap:0; padding-inline:.5rem; }
      .sidebar.collapsed .menu-item .menu-text{ opacity:0; pointer-events:none; }

      .sidebar.compact .menu-item{ grid-template-columns:48px 1fr; padding-inline:.5rem; }
      .sidebar.compact .menu-item .menu-text{ font-size: .92rem; }
    }

    .footer{ background:#0E2B51; display:flex; align-items:center; justify-content:center; min-height:44px; padding:.75rem 1rem; font-weight:600; font-size:.875rem; color:#fff!important; }

    .loader-overlay{ position:fixed; inset:0; background:rgba(255,255,255,.6); backdrop-filter:blur(2px); display:flex; align-items:center; justify-content:center; z-index:99999; visibility:hidden; opacity:0; transition:opacity .2s ease, visibility .2s; }
    .loader-overlay.show{ visibility:visible; opacity:1; }
    .loader-spinner{ width:38px; height:38px; border:4px solid #0E2B51; border-top-color:transparent; border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin{ to{ transform:rotate(360deg) } }

    /* --- เสริมให้ navbar/dropdown ไม่ถูกคลิปซ้อน --- */
    .app-navbar, .navbar-hero { z-index: 2000; } /* x-navbar fixed-top */
    .dropdown-menu { z-index: 2100; }
  </style>
</head>

<body class="bg-base-200 text-base-content">
  @if (View::hasSection('navbar'))
    @yield('navbar')
  @else
    <x-navbar
      :appName="config('app.name', 'Phrapokklao - Information Technology Group')"
      subtitle="Asset Repair Management"
      logo="{{ asset('/images/logoppk.png') }}"
      :showLogout="Auth::check()"
    />
  @endif

  <div id="layout" class="layout" role="presentation">
    <aside id="side" class="sidebar" aria-label="Sidebar navigation">
      @hasSection('sidebar')
        @yield('sidebar')
      @else
        <x-sidebar />
      @endif
    </aside>

    <div id="backdrop" class="backdrop lg:hidden" aria-hidden="true"></div>

    <main id="main" class="content" role="main" tabindex="-1">
      @hasSection('page-header')
        <div class="mb-4 sticky-under-topbar">@yield('page-header')</div>
      @endif

      @if (session('ok'))
        <div class="mb-4 p-3 rounded border border-emerald-200 bg-emerald-50 text-emerald-800">
          {{ session('ok') }}
        </div>
      @endif

      @yield('content')
    </main>
  </div>

  <x-footer />

  {{-- ===== Core JS ===== --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  {{-- ===== Sidebar & Loader ===== --}}
  <script>
    const btn = document.getElementById('btnSidebar');
    const side = document.getElementById('side');
    const bd   = document.getElementById('backdrop');

    function closeSide(){ side.classList.remove('open'); bd.classList.remove('show'); btn?.setAttribute('aria-expanded','false'); }
    function openSide(){ side.classList.add('open'); bd.classList.add('show'); btn?.setAttribute('aria-expanded','true'); }

    btn && btn.addEventListener('click', ()=> side.classList.contains('open') ? closeSide() : openSide());
    bd && bd.addEventListener('click', closeSide);

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

    window.Loader = {
      show(){ document.getElementById('loaderOverlay')?.classList.add('show') },
      hide(){ document.getElementById('loaderOverlay')?.classList.remove('show') }
    };

    document.addEventListener('DOMContentLoaded', () => Loader.hide());
    document.addEventListener('click', (e) => {
      // อย่าโชว์ Loader หากคลิกอยู่ใน Chat Widget (ลด side-effects ระหว่าง polling)
      if (e.target.closest('#chatWidgetRoot')) return;
      if (e.defaultPrevented) return;
      const a = e.target.closest('a'); if (!a) return;
      const href = a.getAttribute('href') || '';
      const noLoader = a.hasAttribute('data-no-loader') || a.getAttribute('target');
      const isAnchorSamePage = href.startsWith('#');
      if (!noLoader && href && !isAnchorSamePage) Loader.show();
    });
    document.addEventListener('submit', (e) => {
      const form = e.target;
      // อย่าโชว์ Loader หากมีการ preventDefault ใน handler อื่น (เช่น client-side validate)
      if (e.defaultPrevented) return;
      if (form instanceof HTMLFormElement && !form.hasAttribute('data-no-loader')) Loader.show();
    });
    window.addEventListener('beforeunload', () => Loader.show());
  </script>

  {{-- ===== Init Bootstrap Dropdowns (หลังโหลด bundle แล้ว) ===== --}}
  <script>
    (function () {
      if (!window.bootstrap || !window.bootstrap.Dropdown) return;
      document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (el) {
        if (!bootstrap.Dropdown.getInstance(el)) {
          new bootstrap.Dropdown(el, { autoClose: 'outside' });
        }
      });
    })();
  </script>

  @stack('scripts')

  <div id="loaderOverlay" class="loader-overlay" aria-hidden="true">
    <div class="loader-spinner" role="status" aria-label="กำลังโหลด"></div>
  </div>

  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>
  <x-toast />

  @includeWhen(Auth::check(), 'partials.chat-fab')

  {{-- NOTE: เดิมมีการ @push styles/scripts สำหรับ CropperJS หลังจาก @stack ถูก render แล้วใน layout เดียวกัน
    ทำให้ไฟล์ไม่ถูกโหลดจริง หากต้องใช้ Cropper ให้ include ภายในหน้าเฉพาะที่ต้องใช้แทน
    (ตัวอย่างเช่น resources/views/profile/edit.blade.php มีการ include ไว้อย่างถูกที่แล้ว) --}}
</body>
</html>
