@extends('layouts.app')
@section('title','Edit Asset')

@section('page-header')
  <div class="flex items-center justify-between">
    <a href="{{ route('assets.show', $asset) }}" class="text-zinc-600 hover:underline">Back</a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('assets.update', $asset) }}" class="rounded-xl border bg-white p-6 space-y-4">
    @method('PUT')
    @include('assets.form', ['asset'=>$asset])
    <div class="flex justify-between">
      <form method="POST" action="{{ route('assets.destroy', $asset) }}" onsubmit="return confirm('Delete this asset?')">
        @csrf @method('DELETE')
        <button class="px-4 py-2 rounded border text-rose-700">Delete</button>
      </form>
      <div class="flex gap-2">
        <a href="{{ route('assets.show', $asset) }}" class="px-4 py-2 rounded border">Cancel</a>
        <button class="px-4 py-2 rounded bg-emerald-600 text-white">Update</button>
      </div>
    </div>
  </form>
@endsection
