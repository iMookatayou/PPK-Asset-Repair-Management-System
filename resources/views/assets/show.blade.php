@extends('layouts.app')
@section('title','Asset Detail')

@php
  $badge = [
    'active'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'disposed'  => 'bg-rose-50 text-rose-700 ring-rose-200',
  ][$asset->status] ?? 'bg-zinc-50 text-zinc-700 ring-zinc-200';
@endphp

@section('page-header')
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <a href="{{ route('assets.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6" /></svg>
          Back
        </a>
        <span class="rounded-full px-2.5 py-1 text-xs ring-1 {{ $badge }}">
          {{ ucfirst(str_replace('_',' ',$asset->status)) }}
        </span>
      </div>
      <h1 class="text-xl font-semibold tracking-tight text-zinc-900 flex items-center gap-2">
        <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4" /></svg>
        {{ $asset->name ?? 'Asset #'.$asset->id }}
        <span class="text-sm font-medium text-zinc-500">#{{ $asset->asset_code ?? $asset->id }}</span>
      </h1>
      <p class="text-xs text-zinc-500">Last updated {{ $asset->updated_at?->diffForHumans() }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="{{ route('assets.edit', $asset) }}" class="inline-flex items-center gap-1 rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
        Edit
      </a>
      <button onclick="window.print()" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        Print
      </button>
      <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="inline-flex"
            onsubmit="window.dispatchEvent(new CustomEvent('app:toast',{detail:{type:'info',message:'กำลังลบ...'}})); return confirm('Delete this asset?')">
        @csrf @method('DELETE')
        <button class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6v12"/><path d="M16 6v12"/><path d="M5 6l1 14a2 2 0 002 2h8a2 2 0 002-2l1-14"/><path d="M10 6V4h4v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
@endsection

@section('content')
  {{-- Summary --}}
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-3">
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 text-sm text-zinc-500">
              <span>Asset Code:</span>
              <span class="font-medium text-zinc-700">{{ $asset->asset_code ?? '—' }}</span>
            </div>
            @if($asset->serial_number)
              <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-medium text-zinc-700">S/N {{ $asset->serial_number }}</span>
            @endif
          </div>
          <div>
            <div class="text-xs uppercase tracking-wide text-zinc-500">Name</div>
            <div class="text-lg font-semibold text-zinc-900">{{ $asset->name ?? '—' }}</div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
            <div class="text-[11px] font-medium text-zinc-500">Repair Requests</div>
            <div class="text-xl font-semibold text-zinc-800">{{ $asset->maintenance_requests_count ?? 0 }}</div>
          </div>
          <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
            <div class="text-[11px] font-medium text-zinc-500">Attachments</div>
            <div class="text-xl font-semibold text-zinc-800">{{ $asset->attachments_count ?? 0 }}</div>
          </div>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
	  <x-asset.meta label="Department" :value="optional($asset->department)->name ?? '—'" />
	  <x-asset.meta label="Category"  :value="optional($asset->categoryRef)->name ?? '—'" />
        <x-asset.meta label="Location"  :value="$asset->location ?? '—'" />
        <x-asset.meta label="Type"      :value="$asset->type ?? '—'" />
        <x-asset.meta label="Brand / Model" :value="trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '—'" />
        <x-asset.meta label="Warranty" :value="optional($asset->warranty_expire)?->format('Y-m-d') ?? '—'" />
        <x-asset.meta label="Purchased" :value="optional($asset->purchase_date)?->format('Y-m-d') ?? '—'" />
        <x-asset.meta label="Updated" :value="$asset->updated_at?->format('Y-m-d H:i')" />
      </div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="font-medium text-zinc-800">Quick Actions</div>
        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
      </div>
      <div class="mt-4 grid gap-2">
        <a href="{{ route('assets.edit', $asset) }}" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>Edit details</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ url('/maintenance/requests/create?asset_id='.$asset->id) }}" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>Create repair request</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ url('/maintenance/requests?asset_id='.$asset->id) }}" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>View repair history</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="{{ url('/api/assets/'.$asset->id.'?pretty=1') }}" target="_blank" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>View JSON</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>
    </div>
  </div>

  {{-- History & Attachments (placeholder/ready) --}}
  <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div class="font-medium text-zinc-800">Recent Repair Logs</div>
        <span class="text-[11px] text-zinc-500">{{ $logs->count() }} entries</span>
      </div>
      <div class="divide-y divide-zinc-200">
      @forelse($logs as $log)
        <div class="flex items-start gap-3 py-2">
          <div class="w-20 shrink-0 text-xs text-zinc-500">{{ $log->created_at?->format('Y-m-d H:i') }}</div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-zinc-800">{{ str_replace('_',' ', ucfirst($log->action)) }}</div>
            @if($log->note)
              <div class="text-xs text-zinc-600 line-clamp-2">{{ $log->note }}</div>
            @endif
          </div>
        </div>
      @empty
        <div class="py-4 text-sm text-zinc-500">No recent logs.</div>
      @endforelse
      </div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div class="font-medium text-zinc-800">Attachments</div>
        <span class="text-[11px] text-zinc-500">{{ ($attachments ?? collect())->count() }} files</span>
      </div>
      @php $attList = $attachments ?? collect(); @endphp
      <div class="divide-y divide-zinc-200">
      @forelse($attList as $att)
        <div class="flex items-center justify-between py-2">
          <div class="truncate text-sm max-w-xs">{{ $att->original_name ?? ('Attachment #'.$att->id) }}</div>
          @if(!empty($att->url))
            <a href="{{ $att->url }}" target="_blank" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14L21 3"/><path d="M21 3v6"/><path d="M21 3h-6"/><path d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-7"/></svg>
              Open
            </a>
          @else
            <span class="text-[11px] text-zinc-500">no link</span>
          @endif
        </div>
      @empty
        <div class="py-4 text-sm text-zinc-500">No attachments.</div>
      @endforelse
      </div>
    </div>
  </div>

  {{-- Print only: simple styles --}}
  <style>
    @media print {
      nav, aside, .no-print, .btn, .rounded-lg.border { display:none !important; }
      main { padding:0 !important; }
    }
  </style>
@endsection
