@extends('layouts.app')
@section('title','Edit User')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Edit User</h1>
    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Back</a>
  </div>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error mb-4">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4 max-w-2xl">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="label"><span class="label-text">Name</span></label>
        <input name="name" value="{{ old('name', $user->name) }}" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Email</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">New Password (optional)</span></label>
        <input type="password" name="password" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Confirm Password</span></label>
        <input type="password" name="password_confirmation" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Department</span></label>
        <input name="department" value="{{ old('department', $user->department) }}" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Role</span></label>
        <select name="role" class="select select-bordered w-full" required>
          @foreach ($roles as $r)
            <option value="{{ $r }}" @selected(old('role', $user->role) === $r)>{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="pt-2 flex items-center gap-3">
      <button class="btn btn-primary">Save</button>

      @if (auth()->id() !== $user->id)
        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
              onsubmit="return confirm('Delete this user?');">
          @csrf @method('DELETE')
          <button class="btn btn-error">Delete</button>
        </form>
      @endif
    </div>
  </form>
@endsection
