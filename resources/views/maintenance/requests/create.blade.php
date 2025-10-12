@extends('layouts.app')

@section('title','Create Maintenance')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Create Maintenance Request</h1>
    <a href="{{ route('maintenance.requests.index') }}"
       class="text-zinc-600 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">
      Back
    </a>
  </div>
@endsection

@section('content')
  {{-- Global error summary (optional but good UX) --}}
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
      <p class="font-medium">There were some problems with your submission:</p>
      <ul class="list-disc pl-5 mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST"
        action="{{ route('maintenance.requests.store') }}"
        class="space-y-4"
        aria-label="Create maintenance request form">
    @csrf

    {{-- ใช้ partial ที่ออกแบบให้รับ $req (nullable), $assets, $users --}}
    @include('maintenance._form', [
      'req'    => null,
      'assets' => $assets,
      'users'  => $users,
    ])

    <div class="pt-2">
      <button type="submit"
              class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700
                     focus:outline-none focus:ring-2 focus:ring-emerald-500">
        Save
      </button>
    </div>
  </form>
@endsection
