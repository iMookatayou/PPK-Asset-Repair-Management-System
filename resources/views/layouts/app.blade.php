{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{{ config('app.name', 'Asset Repair') }}</title>
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

    /* Collapsed/Expanded (desktop) */
    @media (min-width: 1024px){
      .sidebar.collapsed { width:76px !important; }
      .layout.with-collapsed { grid-template-columns: 76px 1fr !important; }
      .layout.with-expanded  { grid-template-columns: 260px 1fr !important; }
      .sidebar { transition: width .25s ease; }
    }

    /* Mobile overlay */
    @media (max-width: 1024px){
      .layout { grid-template-columns: 1fr; }
      .sidebar { position:fixed; inset:0 auto 0 0; width:270px; transform:translateX(-100%); transition:.2s; z-index:50;}
      .sidebar.open { transform:translateX(0); }
      .backdrop{ position:fixed; inset:0; background:#0007; display:none; z-index:40;}
      .backdrop.show{ display:block; }
    }

    /* Hover expand behavior (desktop only) */
    @media (min-width: 1024px) {
      .sidebar.collapsed.hover-expand { width: 260px !important; }
      .sidebar.collapsed.hover-expand .menu-text { display: inline !important; }
      .sidebar.collapsed.hover-expand .menu-item { justify-content: flex-start; gap: .75rem; }
      .sidebar.hover-expand { box-shadow: 4px 0 12px rgba(0,0,0,.4); }
    }

    /* =========================
       Sidebar Menu Row: lock icon column
       (กัน layout เด้งเวลา collapse/expand)
       ========================= */
    .sidebar .menu { padding: .5rem 0; }

    .sidebar .menu-item {
      display: grid;
      grid-template-columns: 48px 1fr;   /* คอลัมน์ไอคอนคงที่ 48px */
      align-items: center;
      gap: .75rem;
      height: 44px;                      /* ล็อกความสูงให้เท่ากันทุกแถว */
      line-height: 1;
      padding: 0 .75rem;
      white-space: nowrap;               /* ป้องกันข้อความตกรอบ */
      overflow: hidden;
      transition: grid-template-columns .25s ease, padding .25s ease;
    }

    .sidebar .menu-item .icon-wrap {
      width: 48px;                       /* ล็อกพื้นที่ไอคอน */
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

    /* ===== เมื่อ collapsed: หดเฉพาะคอลัมน์ข้อความเป็น 0px ไม่ทำให้ความสูงสะดุ้ง ===== */
    @media (min-width: 1024px){
      .sidebar.collapsed .menu-item {
        grid-template-columns: 48px 0px; /* ไอคอนคงที่, ข้อความกว้าง 0 */
        gap: 0;                          /* ไม่มีช่องว่างเพราะคอลัมน์ข้อความเป็น 0 */
        padding-right: .5rem;
        padding-left: .5rem;
      }
      .sidebar.collapsed .menu-item .menu-text {
        opacity: 0;                      /* จางหาย ไม่ reflow */
        pointer-events: none;
      }

      /* Hover-expand: ขยายคอลัมน์ข้อความกลับมาอย่างนุ่มนวล */
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

    /* เราคุม alignment ด้วย grid แล้ว */
    .sidebar.collapsed .menu-item { justify-content: initial; }
    .sidebar.collapsed .menu-text { display: inline; } /* ให้คงอยู่แต่ถูกหดด้วย grid */
  </style>
</head>
<body class="bg-zinc-900 text-zinc-100">

  {{-- Topbar --}}
  <div class="topbar px-4 py-3 flex items-center gap-3">
    {{-- Mobile toggle --}}
    <button id="btnSidebar" class="lg:hidden inline-flex items-center px-2 py-1 rounded border border-zinc-700" aria-controls="side" aria-expanded="false">☰</button>

    <div class="font-semibold">{{ config('app.name','Asset Repair') }}</div>

    <div class="ml-auto flex items-center gap-2 text-sm">
      {{ $header ?? '' }}
    </div>
  </div>

  <div id="layout" class="layout">
    {{-- Sidebar: ใช้ slot ถ้ามี; ไม่งั้นใช้ x-sidebar --}}
    <aside id="side" class="sidebar" aria-label="Sidebar">
      @if (trim($sidebar ?? '') !== '')
        {{ $sidebar }}
      @else
        <x-sidebar />
      @endif
    </aside>

    <div id="backdrop" class="backdrop lg:hidden" aria-hidden="true"></div>

    <main class="content">
      {{ $slot }}
    </main>
  </div>

  <div class="footer text-xs">
    {{ $footer ?? ('© ' . date('Y') . ' ' . config('app.name','Asset Repair') . ' • Build ' . app()->version()) }}
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

    // ===== Desktop collapse/expand (persisted) =====
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

    // First-load: default collapsed on desktop, expanded on mobile
    const saved = localStorage.getItem(KEY);
    if (saved === null) {
      const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
      applyCollapsedState(isDesktop);
      localStorage.setItem(KEY, isDesktop ? '1' : '0');
    } else {
      applyCollapsedState(saved === '1');
    }

    // ===== Hover expand on desktop + push content =====
    let hoverBound = false, hoverTimeout;

    function onEnter(){
      if (side.classList.contains('collapsed')) {
        clearTimeout(hoverTimeout);
        side.classList.add('hover-expand');
        layout.classList.add('with-expanded');      // ดันเนื้อหาออก
        layout.classList.remove('with-collapsed');  // เอา 76px ออกชั่วคราว
      }
    }
    function onLeave(){
      if (side.classList.contains('collapsed')) {
        hoverTimeout = setTimeout(()=>{
          side.classList.remove('hover-expand');
          layout.classList.remove('with-expanded'); // เลิกดัน
          layout.classList.add('with-collapsed');   // กลับไป 76px
        }, 150);
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

    // สลับตามขนาดจอ
    const mql = window.matchMedia('(max-width: 1024px)');
    function handleResize(e){
      if (e.matches){
        // mobile → ไม่ใช้ hover expand
        unbindHover();
        side.classList.remove('hover-expand');
        layout.classList.remove('with-expanded');
      } else {
        // desktop → ใช้ hover expand
        bindHover();
        // ensure collapsed state from storage
        const s = localStorage.getItem(KEY);
        applyCollapsedState(s === '1');
      }
    }
    handleResize(mql);
    mql.addEventListener?.('change', handleResize);
  </script>
</body>
</html>
