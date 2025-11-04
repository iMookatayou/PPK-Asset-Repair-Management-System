{{-- resources/views/repair/queue.blade.php --}}
@extends('layouts.app')
@section('title', 'Repair Queue — Pending')

@section('content')
@php
  use Illuminate\Support\Str;
  /** @var \Illuminate\Pagination\LengthAwarePaginator $list */
  $q = request('q');
  $status = request('status'); // null|pending|in_progress|completed
@endphp

<div class="mx-auto max-w-7xl py-6 space-y-5">

  {{-- ===== Summary / toolbar (sticky) ===== --}}
  <div class="sticky top-0 z-20 -mt-2 rounded-xl border border-slate-200 bg-white/90 backdrop-blur">
    <div class="flex flex-wrap items-center gap-2 px-5 py-3">
      <div class="mr-auto flex items-center gap-3">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-100 bg-sky-50 text-sky-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M7 8l-2 2 9 9 2-2-9-9zM16 3l5 5-3 3-5-5 3-3z"/>
          </svg>
        </span>
        <div>
          <h1 class="text-base font-semibold text-slate-900">Pending Repair Requests</h1>
          <p class="text-xs text-slate-500">รายการรอรับเข้าคิว / มอบหมาย / เริ่มทำงาน</p>
        </div>
      </div>

      <div class="flex items-center gap-2 text-sm">
        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">
          Total: <b>{{ $stats['total'] ?? $list->total() }}</b>
        </span>
        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1">
          Pending: <b>{{ $stats['pending'] ?? '-' }}</b>
        </span>
        <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1">
          In progress: <b>{{ $stats['in_progress'] ?? '-' }}</b>
        </span>
        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1">
          Completed: <b>{{ $stats['completed'] ?? '-' }}</b>
        </span>
      </div>

      <a href="{{ route('repairs.my_jobs') }}"
         class="ml-2 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        ← My Jobs
      </a>
    </div>

    {{-- Quick filters + Search (core utilities only) --}}
    <div class="border-t border-slate-200 px-5 py-3">
      <form method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-12">
        {{-- Tabs --}}
        <div class="md:col-span-7">
          <div class="flex flex-wrap gap-2">
            @php
              $tabs = [
                ['key'=>null,          'label'=>'All',        'active'=>empty($status),          'cls'=>'slate'],
                ['key'=>'pending',     'label'=>'Pending',    'active'=>$status==='pending',     'cls'=>'amber'],
                ['key'=>'in_progress', 'label'=>'In progress','active'=>$status==='in_progress', 'cls'=>'sky'],
                ['key'=>'completed',   'label'=>'Completed',  'active'=>$status==='completed',   'cls'=>'emerald'],
              ];
            @endphp
            @foreach($tabs as $t)
              @php $href = request()->fullUrlWithQuery(['status' => $t['key'] ?: null]); @endphp
              <a href="{{ $href }}"
                 class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm
                 {{ $t['active']
                    ? 'border-'.$t['cls'].'-200 bg-'.$t['cls'].'-50 text-'.$t['cls'].'-800'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                {{ $t['label'] }}
              </a>
            @endforeach
          </div>
        </div>

        {{-- Search --}}
        <div class="md:col-span-5">
          <div class="flex gap-2">
            <div class="relative grow">
              <input id="q" name="q" value="{{ $q }}"
                     placeholder="ค้นหา: ชื่องาน, รายละเอียด, ผู้แจ้ง, หมายเลขทรัพย์สิน…"
                     class="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </span>
            </div>
            <button type="submit"
                    class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
              ค้นหา
            </button>
            <a href="{{ request()->url() }}"
               class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
              ล้าง
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- ==== Table card (ใหม่) ==== --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="relative overflow-x-auto">
        <table class="min-w-full text-sm border-separate border-spacing-0">
        <thead class="sticky top-0 z-10 bg-white">
            <tr>
            <th class="p-3 text-left font-semibold text-slate-700 sticky top-0 bg-white shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)] w-[40%]">Subject</th>
            <th class="p-3 text-left font-semibold text-slate-700 sticky top-0 bg-white shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)] w-[20%]">Asset</th>
            <th class="p-3 text-left font-semibold text-slate-700 sticky top-0 bg-white shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)] w-[18%]">Reporter</th>
            <th class="p-3 text-left font-semibold text-slate-700 sticky top-0 bg-white shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)] w-[14%]">Reported</th>
            <th class="p-3 text-right font-semibold text-slate-700 sticky top-0 bg-white shadow-[inset_0_-1px_0_0_rgba(0,0,0,0.06)] w-[8%]">Actions</th>
            </tr>
        </thead>

        <tbody>
        @forelse($list as $r)
            <tr class="align-top hover:bg-slate-50/70">
            {{-- Subject --}}
            <td class="p-3 border-t border-slate-100">
                <a href="{{ route('maintenance.requests.show', $r) }}"
                class="block max-w-full truncate font-medium text-slate-900 hover:underline">
                {{ \Illuminate\Support\Str::limit($r->title, 90) }}
                </a>
                @if(!empty($r->description))
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    {{ \Illuminate\Support\Str::limit($r->description, 140) }}
                </p>
                @endif

                <div class="mt-2 flex flex-wrap gap-2">
                @if(!empty($r->priority))
                    @php
                    $pri = strtolower($r->priority);
                    $priCls = match(true) {
                        in_array($pri,['urgent','high']) => 'badge-error',
                        $pri === 'medium' => 'badge-warning',
                        default => 'badge-info',
                    };
                    @endphp
                    <span class="badge {{ $priCls }} badge-sm">{{ ucfirst($pri) }}</span>
                @endif
                @if(!empty($r->category))
                    <span class="badge badge-ghost badge-sm">{{ $r->category }}</span>
                @endif
                </div>
            </td>

            {{-- Asset --}}
            <td class="p-3 border-t border-slate-100">
                <div class="font-medium text-slate-800">#{{ $r->asset_id }}</div>
                <div class="max-w-full truncate text-xs text-slate-500">{{ $r->asset->name ?? '—' }}</div>
                @if(!empty($r->asset?->location))
                <div class="mt-0.5 max-w-full truncate text-[11px] text-slate-400">{{ $r->asset->location }}</div>
                @endif
            </td>

            {{-- Reporter --}}
            <td class="p-3 border-t border-slate-100">
                <div class="max-w-full truncate text-slate-800">{{ $r->reporter->name ?? '—' }}</div>
                @if(!empty($r->reporter?->department))
                <div class="max-w-full truncate text-[11px] text-slate-400">{{ $r->reporter->department }}</div>
                @endif
            </td>

            {{-- Reported --}}
            <td class="p-3 border-t border-slate-100">
                <div class="font-medium text-slate-700">
                {{ optional($r->request_date)->format('Y-m-d H:i') }}
                </div>
                @if($r->request_date)
                <div class="text-[11px] text-slate-400">{{ $r->request_date->diffForHumans() }}</div>
                @endif
            </td>

            {{-- Actions --}}
            <td class="p-3 border-t border-slate-100">
                @can('tech-only')
                <div class="hidden justify-end gap-2 sm:flex">
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                    @csrf <input type="hidden" name="action" value="accept">
                    <button class="btn btn-xs btn-primary">Accept</button>
                    </form>

                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                    @csrf
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="technician_id" value="{{ auth()->id() }}">
                    <button class="btn btn-xs btn-info">Assign</button>
                    </form>

                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                    @csrf <input type="hidden" name="action" value="start">
                    <button class="btn btn-xs btn-success">Start</button>
                    </form>
                </div>

                {{-- Mobile menu --}}
                <div class="relative sm:hidden">
                    <details class="group">
                    <summary class="flex cursor-pointer list-none justify-end">
                        <span class="btn btn-xs">Actions ▾</span>
                    </summary>
                    <div class="absolute right-0 mt-1 w-40 rounded-lg border border-slate-200 bg-white p-2 shadow">
                        <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="block">
                        @csrf <input type="hidden" name="action" value="accept">
                        <button class="btn btn-xs btn-primary w-full">Accept</button>
                        </form>
                        <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="mt-1 block">
                        @csrf
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="technician_id" value="{{ auth()->id() }}">
                        <button class="btn btn-xs btn-info w-full">Assign</button>
                        </form>
                        <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="mt-1 block">
                        @csrf <input type="hidden" name="action" value="start">
                        <button class="btn btn-xs btn-success w-full">Start</button>
                        </form>
                    </div>
                    </details>
                </div>
                @endcan
            </td>
            </tr>
        @empty
            <tr>
            <td colspan="5" class="p-12 text-center text-slate-500">No pending requests</td>
            </tr>
        @endforelse
        </tbody>
        </table>
    </div>

    {{-- Footer / pagination --}}
    <div class="border-t border-slate-200 px-4 py-3">
        <div class="flex items-center justify-between">
        <p class="text-xs text-slate-500">
            Showing <span class="font-medium text-slate-700">{{ $list->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-slate-700">{{ $list->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-slate-700">{{ $list->total() }}</span> results
        </p>
        <div class="shrink-0">{{ $list->appends(request()->query())->links() }}</div>
        </div>
    </div>
    </div>
  </div>
</div>
@endsection
