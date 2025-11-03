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
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="{{ route('assets.index') }}"
         class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Back</a>

      <div class="text-lg font-semibold">
        Asset • <span class="text-zinc-500">#{{ $asset->id }}</span>
      </div>

      <span class="rounded-full px-2.5 py-1 text-xs ring-1 {{ $badge }}">
        {{ ucfirst(str_replace('_',' ',$asset->status)) }}
      </span>
    </div>

    <div class="flex items-center gap-2">
      <a href="{{ route('assets.edit', $asset) }}"
         class="rounded-lg bg-zinc-900 px-3 py-2 text-white hover:bg-zinc-800">Edit</a>

      <button onclick="window.print()"
              class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Print</button>

      <form method="POST" action="{{ route('assets.destroy', $asset) }}"
            onsubmit="return confirm('Delete this asset?')">
        @csrf @method('DELETE')
        <button class="rounded-lg border px-3 py-2 text-rose-700 hover:bg-rose-50">Delete</button>
      </form>
    </div>
  </div>
@endsection

@section('content')
  {{-- Summary --}}
  <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="md:col-span-2 rounded-xl border border-zinc-200 bg-white p-5">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-sm text-zinc-500">Asset Code</div>
          <div class="text-lg font-semibold">{{ $asset->asset_code }}</div>

          <div class="mt-3">
            <div class="text-sm text-zinc-500">Name</div>
            <div class="text-base font-medium">{{ $asset->name }}</div>
            @if($asset->serial_number)
              <div class="text-xs text-zinc-500 mt-0.5">S/N: {{ $asset->serial_number }}</div>
            @endif
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-center">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-zinc-500">Repair Requests</div>
            <div class="text-xl font-semibold">{{ $asset->maintenance_requests_count ?? 0 }}</div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-zinc-500">Attachments</div>
            <div class="text-xl font-semibold">{{ $asset->attachments_count ?? 0 }}</div>
          </div>
        </div>
      </div>

      {{-- Meta grid --}}
      <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-asset.meta label="Dept"      :value="optional($asset->department)->name ?? '—'" />
        <x-asset.meta label="Category"  :value="optional($asset->categoryRef)->name ?? ($asset->category ?? '—')" />
        <x-asset.meta label="Location"  :value="$asset->location ?? '—'" />
        <x-asset.meta label="Type"      :value="$asset->type ?? '—'" />
        <x-asset.meta label="Brand / Model" :value="trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '—'" />
        <x-asset.meta label="Warranty"
                      :value="optional($asset->warranty_expire)?->format('Y-m-d') ?? '—'" />
        <x-asset.meta label="Purchased"
                      :value="optional($asset->purchase_date)?->format('Y-m-d') ?? '—'" />
        <x-asset.meta label="Updated at"
                      :value="$asset->updated_at?->format('Y-m-d H:i')" />
      </div>
    </div>

    {{-- Quick Actions --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="font-medium">Quick actions</div>
      <div class="mt-3 grid gap-2">
        <a href="{{ route('assets.edit', $asset) }}"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Edit details</a>
        <a href="{{ url('/maintenance/requests/create?asset_id='.$asset->id) }}"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Create repair request</a>
        <a href="{{ url('/maintenance/requests?asset_id='.$asset->id) }}"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">View repair history</a>
        <a href="{{ url('/api/assets/'.$asset->id.'?pretty=1') }}"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50" target="_blank">View JSON</a>
      </div>
    </div>
  </div>

  {{-- History & Attachments (placeholder/ready) --}}
  <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="mb-3 font-medium">Recent Repair Logs</div>
      @foreach($attachments as $file)
        @php
          // ชื่อที่จะแสดง
          $displayName = $file->original_name
              ?? $file->filename
              ?? $file->file_name
              ?? $file->name
              ?? ('Attachment #'.$file->id);

          // URL ที่จะเปิด
          $displayUrl = $file->url
              ?? $file->public_url     // เผื่อคุณมีคอลัมน์นี้
              ?? null;                 // ถ้ายังไม่มี ก็ไม่ทำลิงก์
        @endphp

        <div class="flex items-center justify-between border-b py-2">
          <div class="truncate text-sm">{{ $displayName }}</div>

          @if($displayUrl)
            <a href="{{ $displayUrl }}" target="_blank" class="text-emerald-700 hover:underline">Open</a>
          @else
            <span class="text-xs text-zinc-500">no link</span>
          @endif
        </div>
      @endforeach

    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="mb-3 font-medium">Attachments</div>
      @forelse(($attachments ?? []) as $file)
        <div class="flex items-center justify-between border-b py-2">
          <div class="truncate text-sm">{{ $file->original_name }}</div>
          <a href="{{ $file->url }}" target="_blank"
             class="text-emerald-700 hover:underline">Open</a>
        </div>
      @empty
        <div class="text-sm text-zinc-500">No attachments.</div>
      @endforelse
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
