@extends('layouts.app')
@section('title','Asset Detail')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Asset #{{ $asset->asset_code }}</h1>
    <div class="flex items-center gap-2">
      <a href="{{ route('assets.edit',$asset) }}" class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Edit</a>
      <a href="{{ route('assets.index') }}" class="text-zinc-600 hover:underline">Back</a>
    </div>
  </div>
@endsection

@section('content')
  <div class="rounded-xl border border-zinc-200 bg-white p-4">
    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
      <div>
        <dt class="text-zinc-500">Name</dt>
        <dd class="mt-1 font-medium text-zinc-900">{{ $asset->name }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Status</dt>
        <dd class="mt-1">
          @php
            $badge = [
              'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
              'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
              'disposed' => 'bg-rose-50 text-rose-700 ring-rose-200',
            ][$asset->status] ?? 'bg-zinc-50 text-zinc-700 ring-zinc-200';
          @endphp
          <span class="rounded-full px-2 py-1 text-xs ring-1 {{ $badge }}">{{ ucfirst(str_replace('_',' ',$asset->status)) }}</span>
        </dd>
      </div>
      <div>
        <dt class="text-zinc-500">Category</dt>
        <dd class="mt-1">{{ $asset->category ?? '—' }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Brand / Model</dt>
        <dd class="mt-1">{{ $asset->brand ?? '—' }} {{ $asset->model ? '· '.$asset->model : '' }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Serial Number</dt>
        <dd class="mt-1">{{ $asset->serial_number ?? '—' }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Location</dt>
        <dd class="mt-1">{{ $asset->location ?? '—' }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Purchase Date</dt>
        <dd class="mt-1">{{ optional($asset->purchase_date)->format('d M Y') ?? '—' }}</dd>
      </div>
      <div>
        <dt class="text-zinc-500">Warranty Expire</dt>
        <dd class="mt-1">{{ optional($asset->warranty_expire)->format('d M Y') ?? '—' }}</dd>
      </div>
    </dl>
  </div>
@endsection
