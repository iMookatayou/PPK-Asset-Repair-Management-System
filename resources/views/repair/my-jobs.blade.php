{{-- resources/views/repairs/my_jobs.blade.php --}}
@extends('layouts.app')
@section('title','My Repair Jobs')

@section('content')
@php
  use Illuminate\Support\Str;

  $q        = request('q');
  $status   = request('status'); // null|pending|accepted|in_progress|on_hold|resolved|closed|cancelled
  $sort     = request('sort','updated_desc'); // updated_desc|updated_asc|created_desc|created_asc
  $perPage  = (int) request('per_page', 20);

  $tabClass = function (?string $s) use ($status) {
    $active = $status === $s || (is_null($s) && is_null($status));
    return $active
      ? 'bg-indigo-600 text-white shadow-sm'
      : 'bg-white text-slate-700 hover:bg-slate-50';
  };

  // Badges (Tailwind only)
  $statusClasses = [
    'pending'     => 'text-amber-700 bg-amber-50 ring-1 ring-amber-200',
    'accepted'    => 'text-sky-700 bg-sky-50 ring-1 ring-sky-200',
    'in_progress' => 'text-sky-800 bg-sky-100',
    'on_hold'     => 'text-slate-700 bg-slate-50 ring-1 ring-slate-200',
    'resolved'    => 'text-emerald-800 bg-emerald-100',
    'closed'      => 'text-slate-800 bg-slate-200',
    'cancelled'   => 'text-slate-600 bg-white ring-1 ring-slate-300',
  ];
  $human = fn($s) => Str::of($s)->replace('_',' ')->title();
@endphp

<div class="mx-auto max-w-7xl py-6 space-y-6">
  {{-- Header --}}
  <div class="flex flex-wrap items-center gap-3">
    <div class="mr-auto">
      <h1 class="text-xl font-semibold text-slate-900">My Repair Jobs</h1>
      <p class="text-sm text-slate-500">งานที่รับผิดชอบทั้งหมดของฉัน</p>
    </div>
    <a href="{{ route('maintenance.requests.index') }}"
       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
      All Requests
    </a>
  </div>

  {{-- Toolbar --}}
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:gap-4">
      {{-- Tabs (desktop) --}}
      <div class="hidden md:flex items-center gap-2">
        <a href="{{ route('repairs.my_jobs', ['status'=>'in_progress'] + request()->except('page','status')) }}"
           class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $tabClass('in_progress') }}">
          In Progress
        </a>
        <a href="{{ route('repairs.my_jobs', ['status'=>'resolved'] + request()->except('page','status')) }}"
           class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $tabClass('resolved') }}">
          Resolved
        </a>
        <a href="{{ route('repairs.my_jobs', request()->except('page','status')) }}"
           class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $tabClass(null) }}">
          All
        </a>
      </div>

      {{-- Mobile status select --}}
      <form method="GET" class="md:hidden w-full">
        @foreach(request()->except(['status','page']) as $k => $v)
          <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <select name="status"
                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
          <option value="" @selected(!$status)>All</option>
          <option value="in_progress" @selected($status==='in_progress')>In Progress</option>
          <option value="resolved" @selected($status==='resolved')>Resolved</option>
        </select>
      </form>

      {{-- Search + sort + per_page --}}
      <form method="GET" class="flex w-full flex-col gap-3 md:ml-auto md:w-auto md:flex-row md:items-center">
        @foreach(request()->except(['q','sort','per_page','page']) as $k => $v)
          <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach

        <div class="relative md:w-80">
          <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none">
            <path d="m21 21-4.35-4.35M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="q" value="{{ $q }}"
                 placeholder="Search subject, asset, reporter…"
                 class="w-full rounded-lg border border-slate-300 bg-white px-9 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500"/>
        </div>

        <select name="sort"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
          <option value="updated_desc" @selected($sort==='updated_desc')>Updated ↓</option>
          <option value="updated_asc"  @selected($sort==='updated_asc')>Updated ↑</option>
          <option value="created_desc" @selected($sort==='created_desc')>Created ↓</option>
          <option value="created_asc"  @selected($sort==='created_asc')>Created ↑</option>
        </select>

        <select name="per_page"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-indigo-500">
          @foreach([10,20,50,100] as $n)
            <option value="{{ $n }}" @selected($perPage===$n)>{{ $n }}/page</option>
          @endforeach
        </select>

        <div class="flex items-center gap-2">
          <button type="submit"
                  class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            Apply
          </button>
          @if($q || $sort!=='updated_desc' || $perPage!==20 || $status)
            <a href="{{ route('repairs.my_jobs') }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
              Reset
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Desktop table --}}
  <div class="hidden md:block rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full table-auto text-left">
        <thead class="sticky top-0 z-10 bg-white/90 backdrop-blur">
          <tr class="text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
            <th class="px-4 py-3 w-[34%]">Subject</th>
            <th class="px-4 py-3 w-[22%]">Asset</th>
            <th class="px-4 py-3 w-[16%]">Reporter</th>
            <th class="px-4 py-3 w-[12%]">Status</th>
            <th class="px-4 py-3 w-[12%]">Updated</th>
            <th class="px-4 py-3 w-[4%]"></th>
          </tr>
        </thead>
        <tbody class="text-sm text-slate-800">
        @forelse($list as $r)
          <tr class="border-b border-slate-100 hover:bg-slate-50/60 align-top">
            <td class="px-4 py-3">
              <a href="{{ route('maintenance.requests.show', $r) }}"
                 class="font-medium text-slate-900 hover:underline">
                {{ $r->title }}
              </a>
              <div class="mt-0.5 text-xs text-slate-500">{{ Str::limit($r->description ?? '', 100) }}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">
              #{{ $r->asset_id }} — {{ $r->asset->name ?? '-' }}
              @if($r->location ?? null)
                <div class="text-xs text-slate-500">{{ $r->location }}</div>
              @endif
            </td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $r->reporter->name ?? '-' }}</td>
            <td class="px-4 py-3">
              @php $cls = $statusClasses[$r->status] ?? 'text-slate-700 bg-slate-50 ring-1 ring-slate-200'; @endphp
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
                {{ $human($r->status) }}
              </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-slate-600">
              {{ optional($r->updated_at)->format('Y-m-d H:i') }}
            </td>
            <td class="px-4 py-3">
              @can('tech-only')
                {{-- Tailwind-only small actions (inline) --}}
                <div class="flex justify-end gap-1.5">
                  @if($r->status==='pending')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="accept">
                      <button class="rounded-md bg-sky-600 px-2 py-1 text-xs font-medium text-white hover:bg-sky-700">Queue</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="rounded-md bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-700">Start</button>
                    </form>
                  @elseif($r->status==='accepted')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="rounded-md bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-700">Start</button>
                    </form>
                  @elseif($r->status==='in_progress')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="hold">
                      <button class="rounded-md bg-amber-600 px-2 py-1 text-xs font-medium text-white hover:bg-amber-700">Hold</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="resolve">
                      <button class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-medium text-white hover:bg-emerald-700">Resolve</button>
                    </form>
                  @elseif($r->status==='resolved')
                    @can('admin-only')
                      <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                        @csrf <input type="hidden" name="action" value="close">
                        <button class="rounded-md bg-slate-700 px-2 py-1 text-xs font-medium text-white hover:bg-slate-800">Close</button>
                      </form>
                    @endcan
                  @endif
                </div>
              @endcan
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-10">
              <div class="text-center text-slate-500">
                <div class="mb-2 text-sm">No jobs found.</div>
                <a href="{{ route('repairs.my_jobs') }}"
                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                  Clear filters
                </a>
              </div>
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3">
      <div class="flex justify-center">
        {{-- Laravel Tailwind pagination --}}
        {{ $list->withQueryString()->links() }}
      </div>
    </div>
  </div>

  {{-- Mobile cards --}}
  <div class="grid grid-cols-1 gap-3 md:hidden">
    @forelse($list as $r)
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <a href="{{ route('maintenance.requests.show', $r) }}"
                 class="font-semibold text-slate-900 hover:underline">
                {{ $r->title }}
              </a>
              <p class="mt-1 text-sm text-slate-600">{{ Str::limit($r->description ?? '', 120) }}</p>
            </div>
            @php $cls = $statusClasses[$r->status] ?? 'text-slate-700 bg-slate-50 ring-1 ring-slate-200'; @endphp
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cls }}">
              {{ $human($r->status) }}
            </span>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
            <div class="text-slate-500">Asset</div>
            <div class="text-slate-800">#{{ $r->asset_id }} — {{ $r->asset->name ?? '-' }}</div>
            <div class="text-slate-500">Reporter</div>
            <div class="text-slate-800">{{ $r->reporter->name ?? '-' }}</div>
            <div class="text-slate-500">Updated</div>
            <div class="text-slate-800">{{ optional($r->updated_at)->format('Y-m-d H:i') }}</div>
          </div>

          @can('tech-only')
            <div class="mt-4 space-y-2">
              @if($r->status==='pending')
                <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                  @csrf <input type="hidden" name="action" value="accept">
                  <button class="w-full rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                    Queue
                  </button>
                </form>
                <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                  @csrf <input type="hidden" name="action" value="start">
                  <button class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Start
                  </button>
                </form>
              @elseif($r->status==='accepted')
                <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                  @csrf <input type="hidden" name="action" value="start">
                  <button class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Start
                  </button>
                </form>
              @elseif($r->status==='in_progress')
                <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                  @csrf <input type="hidden" name="action" value="hold">
                  <button class="w-full rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">
                    Hold
                  </button>
                </form>
                <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                  @csrf <input type="hidden" name="action" value="resolve">
                  <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Resolve
                  </button>
                </form>
              @elseif($r->status==='resolved')
                @can('admin-only')
                  <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                    @csrf <input type="hidden" name="action" value="close">
                    <button class="w-full rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                      Close
                    </button>
                  </form>
                @endcan
              @endif
            </div>
          @endcan
        </div>
      </div>
    @empty
      <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-500">
        <div class="mb-2 text-sm">No jobs found</div>
        <a href="{{ route('repairs.my_jobs') }}"
           class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
          Clear filters
        </a>
      </div>
    @endforelse

    <div class="flex justify-center">
      {{ $list->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
