@php
  use Illuminate\Support\Facades\Route;

  // Helper สำหรับเช็ค Active Route
  $is = fn(...$p) => request()->routeIs($p);

  // Helper สำหรับสร้าง URL (กัน Error ถ้าไม่มีชื่อ Route)
  $rl = fn(string $name, string $fallback = '#') => Route::has($name) ? route($name) : $fallback;

  // Base Classes สำหรับความสวยงาม
  $itemBase = 'menu-item group relative';
  $linkBase = 'flex items-center h-12 px-6 gap-4 text-base font-medium transition-all duration-200 ease-in-out';
  $off      = 'text-zinc-600 hover:bg-slate-50 hover:text-[#0F2D5C]';
  $on       = 'bg-slate-100/80 text-[#0F2D5C] font-semibold';
  $textBase = 'menu-text truncate py-1 whitespace-nowrap flex-1';

  // เส้นสีน้ำเงินด้านข้างเมื่อ Active
  $strip = fn(bool $active) =>
      'absolute left-0 top-1/2 -translate-y-1/2 w-[4px] h-8 rounded-r bg-[#0F2D5C] transition-all duration-300 ease-out origin-left ' .
      ($active ? 'opacity-100 scale-y-100' : 'opacity-0 scale-y-50');
@endphp

<style>
    /* ซ่อน Scrollbar แต่ยังเลื่อนได้ */
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* เอฟเฟกต์ตอนกดปุ่มในมือถือ */
    .btn-close-trigger:active { transform: scale(0.9); transition: 0.1s; }

    /* Mobile: ปุ่มสัมผัสใหญ่พอ (min 44x44) + overscroll behavior */
    @media (max-width: 1024px) {
        .sidebar .menu-item,
        .sidebar-nav .menu-item { min-height: 48px; }
        .sidebar-nav { -webkit-overflow-scrolling: touch; overscroll-behavior: contain; }
        .btn-close-trigger { min-width: 44px; min-height: 44px; }
    }
</style>

<div class="flex flex-col h-full bg-white border-r border-zinc-100 shadow-sm">

  {{-- ปุ่มกากบาท (X) สำหรับ Mobile เท่านั้น - วางไว้ด้านบนสุดขวา --}}
  <div class="lg:hidden flex justify-end p-4 border-b border-zinc-50 flex-shrink-0" style="padding-top: max(1rem, env(safe-area-inset-top));">
      <button type="button" onclick="closeSide()"
              class="btn-close-trigger w-11 h-11 min-w-[44px] min-h-[44px] flex items-center justify-center rounded-full text-zinc-400 hover:bg-red-50 hover:text-red-500 active:scale-95 transition-all"
              aria-label="ปิดเมนู">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
      </button>
  </div>

  {{-- Navigation Section --}}
  <nav class="sidebar-nav flex-1 py-4 overflow-y-auto overscroll-contain no-scrollbar">

    {{-- Section: Overview --}}
    <div class="px-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
      Overview
    </div>

    @php $active = $is('repair.dashboard'); @endphp
    <a href="{{ $rl('repair.dashboard') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 3v18h18"/>
          <rect x="7" y="10" width="3" height="7" rx="1"/>
          <rect x="12" y="6" width="3" height="11" rx="1"/>
          <rect x="17" y="13" width="3" height="4" rx="1"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Dashboard</span>
    </a>

    {{-- Section: Operations --}}
    <div class="px-6 mt-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
      Operations
    </div>

    @php
      $active = $is('maintenance.requests.index','maintenance.requests.show','maintenance.requests.create','maintenance.requests.edit');
    @endphp
    <a href="{{ $rl('maintenance.requests.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2Z"/>
          <path d="M12 6V18M6 12H18"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Requests</span>
    </a>

    @can('view-my-jobs')
      @php $active = $is('repairs.my_jobs'); @endphp
      <a href="{{ $rl('repairs.my_jobs') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
        <span class="{{ $strip($active) }}"></span>
        <span class="icon-wrap flex-shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <rect x="8" y="4" width="8" height="4" rx="1"/>
            <path d="M9 12h6M9 16h6"/>
            <rect x="4" y="4" width="16" height="18" rx="2"/>
          </svg>
        </span>
        <span class="{{ $textBase }}">Jobs</span>
      </a>
    @endcan

    @php $active = $is('assets.*'); @endphp
    <a href="{{ $rl('assets.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="7" width="20" height="14" rx="2"/>
          <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>
          <path d="M2 13h20"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Assets</span>
    </a>

    @php $active = $is('chat.*'); @endphp
    <a href="{{ $rl('chat.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a4 4 0 0 1-4 4H7l-4 4V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Livechat</span>
    </a>

    {{-- Section: Settings --}}
    @can('maintenance-type-manage')
      <div class="px-6 mt-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
        Settings
      </div>

      @php $active = $is('settings.maintenance-types.*'); @endphp
      <a href="{{ $rl('settings.maintenance-types.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
        <span class="{{ $strip($active) }}"></span>
        <span class="icon-wrap flex-shrink-0">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
            <path d="M19.4 15a7.8 7.8 0 0 0 .1-1 7.8 7.8 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a7.6 7.6 0 0 0-1.7-1l-.3-2.6H9l-.3 2.6a7.6 7.6 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a7.8 7.8 0 0 0-.1 1 7.8 7.8 0 0 0 .1 1l-2 1.5 2 3.5 2.4-1a7.6 7.6 0 0 0 1.7 1l.3 2.6h6l.3-2.6a7.6 7.6 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5Z"/>
          </svg>
        </span>
        <span class="{{ $textBase }}">Maintenance Types</span>
      </a>

      @php $active = $is('settings.notifications.*'); @endphp
      <a href="{{ $rl('settings.notifications.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
        <span class="{{ $strip($active) }}"></span>
        <span class="icon-wrap flex-shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </span>
        <span class="{{ $textBase }}">Notifications</span>
      </a>
    @endcan

    {{-- Section: Feedback --}}
    <div class="px-6 mt-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
      Feedback
    </div>

    @php $active = $is('maintenance.requests.rating.evaluate'); @endphp
    <a href="{{ $rl('maintenance.requests.rating.evaluate') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <rect x="6" y="4" width="12" height="16" rx="2"/>
          <path d="M9 4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6H9V4.5z"/>
          <path d="M9 11h6"/>
          <path d="M9 14h3"/>
          <path d="m11 18 1-2 1 2"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Evaluate</span>
    </a>

    @php $active = $is('maintenance.requests.rating.technicians'); @endphp
    <a href="{{ $rl('maintenance.requests.rating.technicians') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 21h18"/>
          <rect x="5" y="10" width="3" height="7" rx="1"/>
          <rect x="10.5" y="7" width="3" height="10" rx="1"/>
          <rect x="16" y="4" width="3" height="13" rx="1"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Technician Ratings</span>
    </a>

    {{-- Section: Account --}}
    <div class="px-6 mt-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
      Account
    </div>

    @php $active = $is('profile.*'); @endphp
    <a href="{{ $rl('profile.show') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
      <span class="{{ $strip($active) }}"></span>
      <span class="icon-wrap flex-shrink-0">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <circle cx="9" cy="7" r="4"/>
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </span>
      <span class="{{ $textBase }}">Profile</span>
    </a>

    {{-- Section: Administration --}}
    @can('manage-users')
      <div class="px-6 mt-6 mb-2 text-[11px] font-bold uppercase tracking-[0.15em] text-zinc-400/80">
        Administration
      </div>

      @php $active = $is('admin.users.*'); @endphp
      <a href="{{ $rl('admin.users.index') }}" class="{{ $itemBase }} {{ $linkBase }} {{ $active ? $on : $off }}">
        <span class="{{ $strip($active) }}"></span>
        <span class="icon-wrap flex-shrink-0">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            <path d="M9 11l2 2 4-4"/>
          </svg>
        </span>
        <span class="{{ $textBase }}">Manage Users</span>
      </a>
    @endcan

    {{-- Spacer ท้ายเมนู --}}
    <div class="h-10"></div>
  </nav>
</div>
