{{-- resources/views/repairs/my_jobs.blade.php --}}
@extends('layouts.app')
@section('title','My Repair Jobs')

@section('content')
@php
  use Illuminate\Support\Str;

  $current = request('status');
  $isActive = fn(string $s = null) => ($current === $s) ? 'btn-primary' : 'btn-ghost';

  $statusStyles = [
    'pending'     => 'badge-warning badge-outline',
    'accepted'    => 'badge-info badge-outline',
    'in_progress' => 'badge-info',
    'on_hold'     => 'badge-ghost',
    'resolved'    => 'badge-success',
    'closed'      => 'badge-neutral',
    'cancelled'   => 'badge-neutral badge-outline',
  ];

  $humanize = fn($s) => Str::of($s)->replace('_',' ')->title();
@endphp

<div class="max-w-6xl mx-auto py-6 space-y-5">
  {{-- Filters --}}
  <div class="flex items-center justify-between gap-3">
    <div class="join hidden md:inline-flex">
      <a href="{{ route('repairs.my_jobs', ['status'=>'in_progress']) }}"
         class="btn btn-sm join-item {{ $isActive('in_progress') }}">In Progress</a>
      <a href="{{ route('repairs.my_jobs', ['status'=>'resolved']) }}"
         class="btn btn-sm join-item {{ $isActive('resolved') }}">Resolved</a>
      <a href="{{ route('repairs.my_jobs') }}"
         class="btn btn-sm join-item {{ $isActive(null) }}">All</a>
    </div>

    <div class="md:hidden dropdown dropdown-end">
      <label tabindex="0" class="btn btn-sm btn-ghost">Filter</label>
      <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-40">
        <li><a class="{{ $current === 'in_progress' ? 'active' : '' }}"
               href="{{ route('repairs.my_jobs', ['status'=>'in_progress']) }}">In Progress</a></li>
        <li><a class="{{ $current === 'resolved' ? 'active' : '' }}"
               href="{{ route('repairs.my_jobs', ['status'=>'resolved']) }}">Resolved</a></li>
        <li><a class="{{ is_null($current) ? 'active' : '' }}"
               href="{{ route('repairs.my_jobs') }}">All</a></li>
      </ul>
    </div>
  </div>

  {{-- Desktop Table --}}
  <div class="card bg-base-100 shadow-sm border hidden md:block">
    <div class="overflow-x-auto">
      <table class="table table-sm md:table-md">
        <thead class="sticky top-0 z-10 bg-base-100/95 backdrop-blur supports-[backdrop-filter]:bg-base-100/70">
          <tr class="text-xs text-base-content/70 border-b">
            <th class="w-[30%]">Subject</th>
            <th>Asset</th>
            <th>Reporter</th>
            <th>Status</th>
            <th>Updated</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($list as $r)
          <tr class="hover:bg-base-200/40">
            <td>
              <a href="{{ route('maintenance.requests.show', $r) }}" class="link link-hover font-medium">
                {{ $r->title }}
              </a>
              <div class="text-xs opacity-70">{{ Str::limit($r->description ?? '', 90) }}</div>
            </td>
            <td class="whitespace-nowrap">#{{ $r->asset_id }} — {{ $r->asset->name ?? '-' }}</td>
            <td class="whitespace-nowrap">{{ $r->reporter->name ?? '-' }}</td>
            <td>
              @php $style = $statusStyles[$r->status] ?? 'badge-ghost'; @endphp
              <span class="badge {{ $style }}">{{ $humanize($r->status) }}</span>
            </td>
            <td class="whitespace-nowrap text-sm opacity-80">
              {{ optional($r->updated_at)->format('Y-m-d H:i') }}
            </td>
            <td>
              @can('tech-only')
                <div class="flex justify-end gap-2">
                  @if($r->status==='pending')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="accept">
                      <button class="btn btn-xs md:btn-sm btn-info text-white">Queue</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="btn btn-xs md:btn-sm btn-accent text-white">Start</button>
                    </form>
                  @elseif($r->status==='accepted')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="btn btn-xs md:btn-sm btn-accent text-white">Start</button>
                    </form>
                  @elseif($r->status==='in_progress')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="hold">
                      <button class="btn btn-xs md:btn-sm btn-warning text-white">Hold</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                      @csrf <input type="hidden" name="action" value="resolve">
                      <button class="btn btn-xs md:btn-sm btn-success text-white">Resolve</button>
                    </form>
                  @elseif($r->status==='resolved')
                    @can('admin-only')
                      <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}">
                        @csrf <input type="hidden" name="action" value="close">
                        <button class="btn btn-xs md:btn-sm btn-neutral text-white">Close</button>
                      </form>
                    @endcan
                  @endif
                </div>
              @endcan
            </td>
          </tr>
        @empty
          <tr><td colspan="6">
            <div class="py-10 text-center text-base-content/60">No jobs found.</div>
          </td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-body pt-0">
      <div class="flex justify-center">
        {{ $list->withQueryString()->links() }}
      </div>
    </div>
  </div>

  {{-- Mobile Cards --}}
  <div class="grid grid-cols-1 gap-3 md:hidden">
    @forelse($list as $r)
      <div class="card bg-base-100 border">
        <div class="card-body p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
              <a href="{{ route('maintenance.requests.show', $r) }}" class="link link-hover font-semibold">
                {{ $r->title }}
              </a>
              <p class="text-sm opacity-70">{{ Str::limit($r->description ?? '', 120) }}</p>
            </div>
            @php $style = $statusStyles[$r->status] ?? 'badge-ghost'; @endphp
            <span class="badge {{ $style }}">{{ $humanize($r->status) }}</span>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
            <div class="opacity-70">Asset</div>
            <div>#{{ $r->asset_id }} — {{ $r->asset->name ?? '-' }}</div>
            <div class="opacity-70">Reporter</div>
            <div>{{ $r->reporter->name ?? '-' }}</div>
            <div class="opacity-70">Updated</div>
            <div>{{ optional($r->updated_at)->format('Y-m-d H:i') }}</div>
          </div>

          @can('tech-only')
            <div class="mt-4">
              @if(in_array($r->status, ['pending','accepted','in_progress','resolved']))
                <div class="join join-vertical w-full">
                  @if($r->status==='pending')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                      @csrf <input type="hidden" name="action" value="accept">
                      <button class="btn btn-sm btn-info text-white w-full">Queue</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="btn btn-sm btn-accent text-white w-full">Start</button>
                    </form>
                  @elseif($r->status==='accepted')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                      @csrf <input type="hidden" name="action" value="start">
                      <button class="btn btn-sm btn-accent text-white w-full">Start</button>
                    </form>
                  @elseif($r->status==='in_progress')
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                      @csrf <input type="hidden" name="action" value="hold">
                      <button class="btn btn-sm btn-warning text-white w-full">Hold</button>
                    </form>
                    <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                      @csrf <input type="hidden" name="action" value="resolve">
                      <button class="btn btn-sm btn-success text-white w-full">Resolve</button>
                    </form>
                  @elseif($r->status==='resolved')
                    @can('admin-only')
                      <form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="join-item">
                        @csrf <input type="hidden" name="action" value="close">
                        <button class="btn btn-sm btn-neutral text-white w-full">Close</button>
                      </form>
                    @endcan
                  @endif
                </div>
              @endif
            </div>
          @endcan
        </div>
      </div>
    @empty
      <div class="card bg-base-100 border">
        <div class="card-body items-center text-center">
          <h3 class="font-medium">No jobs found</h3>
          <p class="text-sm opacity-70">Try switching the filter to see more.</p>
        </div>
      </div>
    @endforelse

    <div class="flex justify-center">
      {{ $list->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
