@extends('layouts.app')
@section('title','Create Asset')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Create Asset</h1>
    <a href="{{ route('assets.index') }}" class="text-zinc-600 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">
      Back
    </a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('assets.store') }}" class="space-y-4">
    @csrf
    @include('asset.form', ['asset' => null])

    <div class="flex justify-end gap-2">
      <a href="{{ route('assets.index') }}" class="rounded-lg border px-4 py-2 text-zinc-700 hover:bg-zinc-50">Cancel</a>
      <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
    </div>
  </form>
@endsection
