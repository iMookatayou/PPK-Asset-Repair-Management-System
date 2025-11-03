@extends('layouts.app')
@section('title','Create Asset')

@section('page-header')
  <div class="flex items-center justify-between">
    <a href="{{ route('assets.index') }}" class="text-zinc-600 hover:underline">Back</a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('assets.store') }}" class="rounded-xl border bg-white p-6 space-y-4">
    @include('assets.form', ['asset'=>null])
    <div class="flex justify-end gap-2">
      <a href="{{ route('assets.index') }}" class="px-4 py-2 rounded border">Cancel</a>
      <button class="px-4 py-2 rounded bg-emerald-600 text-white">Save</button>
    </div>
  </form>
@endsection
