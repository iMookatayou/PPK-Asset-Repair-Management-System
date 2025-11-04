@extends('layouts.app')
@section('title','Create User')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Create User</h1>
    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Back</a>
  </div>
@endsection

@section('content')
  @if ($errors->any())
    <div class="alert alert-error mb-4">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 max-w-2xl">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="label"><span class="label-text">Name</span></label>
        <input name="name" value="{{ old('name') }}" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Email</span></label>
        <input type="email" name="email" value="{{ old('email') }}" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Password</span></label>
        <input type="password" name="password" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Confirm Password</span></label>
        <input type="password" name="password_confirmation" required class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Department</span></label>
        <input name="department" value="{{ old('department') }}" class="input input-bordered w-full" />
      </div>
      <div>
        <label class="label"><span class="label-text">Role</span></label>
        <select name="role" class="select select-bordered w-full" required>
          <option value="">— choose —</option>
          @foreach ($roles as $r)
            <option value="{{ $r }}" @selected(old('role')===$r)>{{ ucfirst($r) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="pt-2">
      <button class="btn btn-primary">Create</button>
    </div>
  </form>
@endsection
