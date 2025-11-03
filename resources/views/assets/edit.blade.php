@extends('layouts.app')
@section('title','Edit Asset')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Edit Asset</h1>
    <a href="{{ route('assets.show',$asset) }}" class="text-zinc-600 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">
      Back
    </a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('assets.update',$asset) }}" class="space-y-4">
    @csrf @method('PUT')
    @include('asset.form', ['asset' => $asset])

    <div class="flex items-center justify-between">
      <form method="POST" action="{{ route('assets.destroy',$asset) }}" onsubmit="return confirm('Delete this asset?')">
        @csrf @method('DELETE')
        <button class="rounded-lg border border-rose-300 px-4 py-2 text-rose-700 hover:bg-rose-50">Delete</button>
      </form>
      <div class="flex gap-2">
        <a href="{{ route('assets.show',$asset) }}" class="rounded-lg border px-4 py-2 text-zinc-700 hover:bg-zinc-50">Cancel</a>
        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Update</button>
      </div>
    </div>
  </form>
@endsection
