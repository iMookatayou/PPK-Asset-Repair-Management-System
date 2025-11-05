@extends('layouts.app')
@section('title','Create User')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Create User</h1>
    <a href="{{ route('admin.users.index') }}" class="text-zinc-600 hover:underline">Back</a>
  </div>
@endsection

@section('content')
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
    @csrf
    @include('admin.users._form', ['user' => null, 'roles' => $roles])
    <div class="pt-2">
      <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
    </div>
  </form>
@endsection
