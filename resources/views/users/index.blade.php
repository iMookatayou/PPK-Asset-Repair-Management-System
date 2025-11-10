@extends('layouts.app')
@section('title', 'Users')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Create</a>
  </div>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
    <input type="text" name="s" value="{{ $filters['s'] ?? '' }}" placeholder="Search name/email/department"
           class="input input-bordered w-full" />
    <select name="role" class="select select-bordered">
      <option value="">All roles</option>
      @foreach ($roles as $r)
        <option value="{{ $r }}" @selected(($filters['role'] ?? '') === $r)>{{ ucfirst($r) }}</option>
      @endforeach
    </select>
    <input type="text" name="department" value="{{ $filters['department'] ?? '' }}" placeholder="Department"
           class="input input-bordered w-full" />
    <button class="btn btn-outline">Filter</button>
  </form>

  <form method="POST" action="{{ route('admin.users.bulk') }}" class="mb-3">
    @csrf
    <div class="flex flex-col md:flex-row md:items-center md:gap-3 gap-2 mb-2">
      <div class="join">
        <select name="action" class="select select-bordered join-item" required>
          <option value="">Bulk action…</option>
          <option value="change_role">Change role</option>
          <option value="delete">Delete</option>
        </select>
        <select name="role" class="select select-bordered join-item">
          <option value="">— role —</option>
          @foreach ($roles as $r)
            <option value="{{ $r }}">{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>
      <button class="btn btn-warning">Apply</button>
    </div>

    <div class="overflow-x-auto rounded-lg border">
      <table class="table">
        <thead>
        <tr>
          <th class="w-10"><input type="checkbox" class="checkbox" onclick="document.querySelectorAll('.ck-user').forEach(c=>c.checked=this.checked)" /></th>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Department</th>
          <th>Role</th>
          <th>Created</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($users as $u)
          <tr>
            <td>
              @if (auth()->id() !== $u->id)
                <input type="checkbox" name="ids[]" value="{{ $u->id }}" class="checkbox ck-user" />
              @endif
            </td>
            <td>{{ $u->id }}</td>
            <td class="font-medium">{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->department ?? '—' }}</td>
            <td>
              <span class="badge">{{ ucfirst($u->role) }}</span>
            </td>
            <td>{{ $u->created_at?->format('Y-m-d') }}</td>
            <td class="text-right space-x-2">
              <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-xs">Edit</a>
              @if (auth()->id() !== $u->id)
                <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline"
                      onsubmit="return confirm('Delete this user?');">
                  @csrf @method('DELETE')
                  <button class="btn btn-xs btn-error">Delete</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-zinc-500 py-8">No users found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </form>

  <div class="mt-4">{{ $users->links() }}</div>
@endsection
