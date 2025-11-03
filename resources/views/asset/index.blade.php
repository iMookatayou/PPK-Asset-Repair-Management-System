@extends('layouts.app')
@section('title','Assets')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Assets</h1>
    <a href="{{ route('assets.create') }}"
       class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
      + New Asset
    </a>
  </div>
@endsection

@section('content')
  {{-- Filters --}}
  <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    <div class="md:col-span-2">
      <label class="block text-sm text-zinc-700">Search</label>
      <input type="text" name="q" value="{{ request('q') }}"
             placeholder="Search code / name / serial..."
             class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    </div>
    <div>
      <label class="block text-sm text-zinc-700">Status</label>
      <select name="status" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
        @php $statuses = ['' => 'All','active'=>'Active','in_repair'=>'In Repair','disposed'=>'Disposed']; @endphp
        @foreach($statuses as $k=>$v)
          <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-sm text-zinc-700">Category</label>
      <input type="text" name="category" value="{{ request('category') }}"
             placeholder="e.g. Computer"
             class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    </div>

    <div class="md:col-span-3"></div>
    <div class="flex items-end justify-end gap-2">
      <a href="{{ route('assets.index') }}" class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Reset</a>
      <button class="rounded-lg bg-zinc-900 px-3 py-2 text-white hover:bg-zinc-800">Filter</button>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
    <table class="min-w-full divide-y divide-zinc-200">
      <thead class="bg-zinc-50">
      @php
        $sortBy = request('sort_by','id'); $sortDir = request('sort_dir','desc');
        $th = function($key,$label) use ($sortBy,$sortDir){
          $nextDir = $sortBy===$key && $sortDir==='asc' ? 'desc' : 'asc';
          $q = request()->fullUrlWithQuery(['sort_by'=>$key,'sort_dir'=>$nextDir]);
          $arrow = $sortBy===$key ? ($sortDir==='asc' ? '↑' : '↓') : '';
          return "<a href=\"{$q}\" class=\"inline-flex items-center gap-1\">{$label} <span class=\"text-xs text-zinc-400\">{$arrow}</span></a>";
        };
      @endphp
      <tr class="text-left text-sm text-zinc-600">
        <th class="px-4 py-3">{!! $th('id','#') !!}</th>
        <th class="px-4 py-3">{!! $th('asset_code','Asset Code') !!}</th>
        <th class="px-4 py-3">{!! $th('name','Name') !!}</th>
        <th class="px-4 py-3 hidden md:table-cell">{!! $th('category','Category') !!}</th>
        <th class="px-4 py-3 hidden md:table-cell">Location</th>
        <th class="px-4 py-3">{!! $th('status','Status') !!}</th>
        <th class="px-4 py-3 text-right">Action</th>
      </tr>
      </thead>
      <tbody class="divide-y divide-zinc-100 bg-white text-sm">
      @forelse($assets as $a)
        <tr class="hover:bg-zinc-50">
          <td class="px-4 py-3 text-zinc-500">{{ $a->id }}</td>
          <td class="px-4 py-3 font-medium text-zinc-800">{{ $a->asset_code }}</td>
          <td class="px-4 py-3">
            <a class="text-emerald-700 hover:underline" href="{{ route('assets.show',$a) }}">{{ $a->name }}</a>
            <div class="text-xs text-zinc-500">S/N: {{ $a->serial_number ?? '—' }}</div>
          </td>
          <td class="px-4 py-3 hidden md:table-cell">{{ $a->category ?? '—' }}</td>
          <td class="px-4 py-3 hidden md:table-cell">{{ $a->location ?? '—' }}</td>
          <td class="px-4 py-3">
            @php
              $badge = [
                'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'disposed' => 'bg-rose-50 text-rose-700 ring-rose-200',
              ][$a->status] ?? 'bg-zinc-50 text-zinc-700 ring-zinc-200';
            @endphp
            <span class="rounded-full px-2 py-1 text-xs ring-1 {{ $badge }}">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
          </td>
          <td class="px-4 py-3 text-right">
            <a href="{{ route('assets.edit',$a) }}" class="text-emerald-700 hover:underline">Edit</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No assets found.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $assets->links() }}</div>
@endsection
