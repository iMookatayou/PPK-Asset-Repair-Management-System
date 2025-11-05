{{-- resources/views/admin/users/index.blade.php --}}
@extends('layouts.app')
@section('title','Manage Users')

@php
  /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
  $roles   = $roles   ?? ['admin','technician','staff'];
  $filters = $filters ?? ['s'=>'','role'=>'','department'=>''];
@endphp

@section('content')
  {{-- Page header (ย้ายมาไว้ใน content เพื่อให้แสดงแน่นอน) --}}
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">Manage Users</h1>
    <a href="{{ route('admin.users.create') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
      <span>Create</span>
    </a>
  </div>

  @if (session('status'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Filters --}}
  <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    <input type="text" name="s" value="{{ $filters['s'] }}"
           placeholder="Search name/email/department"
           class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <select name="role" class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
      <option value="">All roles</option>
      @foreach ($roles as $r)
        <option value="{{ $r }}" @selected($filters['role']===$r)>{{ ucfirst($r) }}</option>
      @endforeach
    </select>
    <input type="text" name="department" value="{{ $filters['department'] }}"
           placeholder="Department"
           class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <div class="flex gap-2">
      <button class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Filter</button>
      <a href="{{ route('admin.users.index') }}"
         class="rounded-lg border border-zinc-300 px-3 py-2 text-zinc-700 hover:bg-zinc-50">Reset</a>
    </div>
  </form>

  {{-- Bulk actions --}}
  <form method="POST" action="{{ route('admin.users.bulk') }}">
    @csrf
    <div class="mb-2 flex flex-wrap items-center gap-2">
      <select name="action" class="rounded-lg border border-zinc-300 px-3 py-2">
        <option value="change_role">Change role</option>
        <option value="delete">Delete</option>
      </select>
      <select name="role" class="rounded-lg border border-zinc-300 px-3 py-2">
        <option value="">-- role --</option>
        @foreach ($roles as $r)
          <option value="{{ $r }}">{{ ucfirst($r) }}</option>
        @endforeach
      </select>
      <button type="submit"
              class="rounded-lg bg-amber-500 px-3 py-2 text-white hover:bg-amber-600"
              onclick="return confirm('Confirm bulk action?');">
        Apply
      </button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
      <table class="min-w-full divide-y divide-zinc-200">
        <thead class="bg-zinc-50 text-left text-sm text-zinc-700">
          <tr>
            <th class="px-3 py-2"><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Email</th>
            <th class="px-3 py-2">Department</th>
            <th class="px-3 py-2">Role</th>
            <th class="px-3 py-2">Created</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 text-sm">
          @forelse ($users as $u)
            <tr>
              <td class="px-3 py-2 align-middle">
                @if($u->id !== auth()->id())
                  <input type="checkbox" class="row-check" name="ids[]" value="{{ $u->id }}">
                @endif
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                    {{ strtoupper(mb_substr($u->name,0,1)) }}
                  </div>
                  <div>
                    <div class="font-medium">{{ $u->name }}</div>
                    <div class="text-xs text-zinc-500">#{{ $u->id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-3 py-2">{{ $u->email }}</td>
              <td class="px-3 py-2">{{ $u->department ?: '-' }}</td>
              <td class="px-3 py-2">
                @php
                  $roleCls = $u->role==='admin'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                    : 'bg-zinc-50 text-zinc-700 border-zinc-300';
                @endphp
                <span class="rounded-full border px-2 py-0.5 text-xs {{ $roleCls }}">
                  {{ ucfirst($u->role) }}
                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap">{{ $u->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-end gap-2">
                  <a href="{{ route('admin.users.edit', $u) }}"
                     class="rounded-md border border-emerald-300 px-2 py-1 text-emerald-700 hover:bg-emerald-50">Edit</a>
                  @if($u->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                          onsubmit="return confirm('Delete this user?');">
                      @csrf @method('DELETE')
                      <button class="rounded-md border border-rose-300 px-2 py-1 text-rose-600 hover:bg-rose-50">Delete</button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-3 py-6 text-center text-zinc-500">No users found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </form>

  <div class="mt-4">{{ $users->links() }}</div>
@endsection
