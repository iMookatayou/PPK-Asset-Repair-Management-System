@props(['header' => ''])

<div class="topbar px-5 py-3 flex items-center justify-between bg-gradient-to-r from-red-700 via-red-600 to-red-500 text-white shadow-md border-b border-red-900/50">
  {{-- Left Section: Logo + Name --}}
  <div class="flex items-center gap-3">
    {{-- Logo Laravel --}}
    <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel Logo"
         class="w-8 h-8 drop-shadow-md animate-pulse-slow" />

    <div class="text-lg font-bold tracking-wide">
      {{ config('app.name', 'Asset Repair') }}
    </div>
  </div>

  {{-- Center Section: Optional Header --}}
  @if($header)
    <div class="hidden md:flex items-center gap-3 text-sm font-medium text-white/90">
      {!! $header !!}
    </div>
  @endif

  {{-- Right Section: Buttons / User --}}
  <div class="flex items-center gap-3 text-sm">
    {{-- Sidebar toggle (mobile) --}}
    <button id="btnSidebar"
            class="lg:hidden inline-flex items-center px-2 py-1 rounded-md border border-white/40 bg-white/10 hover:bg-white/20 transition-colors duration-200"
            aria-controls="side" aria-expanded="false">
      ☰
    </button>

    {{-- User Avatar + Name --}}
    <div class="flex items-center gap-2 bg-white/10 px-3 py-1.5 rounded-full hover:bg-white/20 transition-all duration-200">
      <img src="https://ui-avatars.com/api/?name=Admin&background=fff&color=dc2626"
           alt="User Avatar" class="w-6 h-6 rounded-full border border-white/30 shadow-sm" />
      <span class="hidden sm:inline">Admin</span>
    </div>

    {{-- Logout Button --}}
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit"
              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-white/10 hover:bg-white/20 border border-white/30 transition-colors duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1" />
        </svg>
        <span class="hidden sm:inline">ออกจากระบบ</span>
      </button>
    </form>
  </div>
</div>
