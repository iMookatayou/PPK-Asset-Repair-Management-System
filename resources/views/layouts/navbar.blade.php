<header class="h-14 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between px-6">
  <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
    Asset Repair Dashboard
  </h1>

  <div class="flex items-center gap-4">
    <span class="text-sm text-zinc-500 dark:text-zinc-400">
      {{ Auth::user()->name ?? 'Guest' }}
    </span>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="text-sm text-red-600 hover:text-red-700">ออกจากระบบ</button>
    </form>
  </div>
</header>
