{{-- resources/views/components/sidebar.blade.php --}}
@php
  // เปลี่ยนเป็น grid 2 คอลัมน์ + ล็อกความสูงแถว
  $itemBase  = 'menu-item grid grid-cols-[48px_1fr] items-center gap-3 px-3 h-11 rounded-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500';
  $inactive  = 'text-zinc-300 hover:bg-zinc-800';
  $activeBox = 'bg-zinc-800 text-emerald-400';
  $is = fn($p) => request()->routeIs($p);
@endphp

<nav class="px-2 py-3">
  <ul class="space-y-1">

    {{-- Dashboard --}}
    <li>
      <a href="{{ route('repair.dashboard') }}"
         class="{{ $itemBase }} {{ $is('repair.dashboard') ? $activeBox : $inactive }}"
         aria-current="{{ $is('repair.dashboard') ? 'page' : 'false' }}">
        <span class="icon-wrap inline-flex items-center justify-center w-12 h-11">
          <x-app-icon name="bar-chart-3" class="w-4 h-4 shrink-0" />
        </span>
        <span class="menu-text font-medium overflow-hidden text-ellipsis whitespace-nowrap">Dashboard</span>
      </a>
    </li>

    {{-- Repair jobs --}}
    <li>
      <a href="{{ route('maintenance.requests.index') }}"
         class="{{ $itemBase }} {{ $is('maintenance.requests.*') ? $activeBox : $inactive }}"
         aria-current="{{ $is('maintenance.requests.*') ? 'page' : 'false' }}">
        <span class="icon-wrap inline-flex items-center justify-center w-12 h-11">
          <x-app-icon name="wrench" class="w-4 h-4 shrink-0" />
        </span>
        <span class="menu-text font-medium overflow-hidden text-ellipsis whitespace-nowrap">Repair Jobs</span>
      </a>
    </li>

    {{-- Assets --}}
    <li>
      <a href="{{ route('assets.index') }}"
         class="{{ $itemBase }} {{ $is('assets.*') ? $activeBox : $inactive }}"
         aria-current="{{ $is('assets.*') ? 'page' : 'false' }}">
        <span class="icon-wrap inline-flex items-center justify-center w-12 h-11">
          <x-app-icon name="briefcase" class="w-4 h-4 shrink-0" />
        </span>
        <span class="menu-text font-medium overflow-hidden text-ellipsis whitespace-nowrap">Assets</span>
      </a>
    </li>

    {{-- Users --}}
    <li>
      <a href="{{ route('users.index') }}"
         class="{{ $itemBase }} {{ $is('users.*') ? $activeBox : $inactive }}"
         aria-current="{{ $is('users.*') ? 'page' : 'false' }}">
        <span class="icon-wrap inline-flex items-center justify-center w-12 h-11">
          <x-app-icon name="users" class="w-4 h-4 shrink-0" />
        </span>
        <span class="menu-text font-medium overflow-hidden text-ellipsis whitespace-nowrap">Users</span>
      </a>
    </li>

  </ul>
</nav>
