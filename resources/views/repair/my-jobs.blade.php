{{-- resources/views/chat/index.blade.php --}}
@extends('layouts.app')
@section('title','Community Chat')

@section('content')
<div class="max-w-5xl mx-auto py-6 space-y-5">

  {{-- Header --}}
  <div class="rounded-xl border bg-white/80 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/60">
    <div class="px-4 md:px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="size-10 grid place-items-center rounded-lg bg-indigo-50 text-indigo-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 10h8M8 14h5M4 6h16M6 18h8l4 4v-4h2V4a2 2 0 0 0-2-2H6A2 2 0 0 0 4 4v12a2 2 0 0 0 2 2Z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-semibold leading-tight text-slate-900">Community Chat</h1>
          <p class="text-sm text-slate-600">Ask questions, share tips, and learn from others</p>
        </div>
      </div>

      {{-- Search (pure Tailwind so it works even if DaisyUI isn’t loaded) --}}
      <form method="GET"
            class="group flex items-stretch rounded-xl border border-slate-300 bg-white shadow-sm
                   focus-within:ring-2 focus-within:ring-[#0E2B51] w-full md:w-auto">
        <input
          type="text"
          name="q"
          value="{{ request('q','') }}"
          placeholder="Search threads..."
          class="flex-1 px-3 h-10 text-sm bg-transparent outline-none border-0 rounded-l-xl"
          aria-label="Search threads"
        >
        <button type="submit"
                class="px-4 h-10 text-sm font-medium text-white bg-[#0E2B51]
                       rounded-r-xl shadow-sm hover:shadow-md hover:bg-[#0c2342] active:translate-y-[0.5px]">
          Search
        </button>
        @if(request('q'))
          <a href="{{ route('chat.index') }}"
             class="ml-2 px-3 h-10 grid place-items-center text-sm text-slate-600 hover:text-slate-800">
            Clear
          </a>
        @endif
      </form>
    </div>
    <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>
  </div>

  {{-- Create Thread --}}
  <div class="rounded-xl border bg-white shadow-sm">
    <div class="p-5 space-y-3">
      <div class="flex items-center justify-between">
        <div class="text-sm text-slate-600">Create a new thread</div>
      </div>

      <form method="POST" action="{{ route('chat.store') }}" class="space-y-3">
        @csrf
        <input
          name="title"
          required
          maxlength="180"
          class="w-full rounded-lg border border-slate-300 px-3 h-11 text-sm
                 focus:outline-none focus:ring-2 focus:ring-emerald-500"
          placeholder='Ask anything, e.g. "How to choose a reliable night-shift printer?"'
          value="{{ old('title') }}"
          aria-label="Thread title"
        >
        @error('title')
          <p class="text-sm text-rose-600">{{ $message }}</p>
        @enderror

        <div class="text-right">
          {{-- Pure Tailwind button to avoid DaisyUI dependency issues --}}
          <button type="submit"
                  class="inline-flex items-center justify-center px-4 h-10 rounded-lg
                         bg-emerald-600 text-white font-medium
                         hover:bg-emerald-700 active:translate-y-[0.5px]
                         focus:outline-none focus:ring-2 focus:ring-emerald-500">
            Post
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Threads List --}}
  <div class="rounded-xl border bg-white shadow-sm">
    <div class="p-0">
      <div class="px-4 py-3 border-b text-sm font-medium text-slate-800">Latest Threads</div>

      <div class="divide-y">
        @forelse($threads as $th)
          <a href="{{ route('chat.show',$th) }}"
             class="block px-4 py-3 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-medium text-slate-900 line-clamp-1">{{ $th->title }}</div>
                <div class="text-xs text-slate-500">
                  by {{ $th->author->name }} • {{ $th->created_at->diffForHumans() }}
                </div>
              </div>
              <div class="shrink-0 text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                </svg>
              </div>
            </div>
          </a>
        @empty
          <div class="p-10 text-center">
            <div class="mx-auto mb-3 size-10 grid place-items-center rounded-full bg-slate-100">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M7 8h10M7 12h7M5 4h14a2 2 0 012 2v10l-4 4H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
              </svg>
            </div>
            <div class="font-medium text-slate-800">No threads yet</div>
            <p class="text-sm text-slate-500">Be the first to start a conversation.</p>
          </div>
        @endforelse
      </div>

      <div class="p-3">
        <div class="flex justify-center">
          {{ $threads->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
