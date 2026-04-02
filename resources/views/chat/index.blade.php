@extends('layouts.app')
@section('title','Community Chat')

@section('content')
@php
  use Illuminate\Support\Str;
  $q = request('q');
@endphp

<div class="w-full flex flex-col">

  {{-- Sticky Header + Filters (มาตรฐานเดียวกับหน้าอื่น) --}}
  <div class="sticky top-16 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="px-4 md:px-6 lg:px-8 py-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-[17px] font-semibold text-slate-900">Community Chat</h1>
          <p class="text-[13px] text-slate-600">พื้นที่แลกเปลี่ยนและสอบถามข้อมูลภายในองค์กร • ค้นหาและพูดคุยแนวทางแก้ไข</p>
        </div>

        <button type="button"
                id="btnCreateThread"
                class="inline-flex items-center gap-2 rounded-md bg-[#0F2D5C] px-4 py-2 text-[13px] font-medium text-white hover:bg-[#0F2D5C]/90 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/40"
                title="สร้างกระทู้ใหม่">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          สร้างกระทู้ใหม่
        </button>
      </div>

      <div class="mt-4">
        <form method="GET" action="{{ route('chat.index') }}" class="flex items-end gap-2 max-w-md" onsubmit="showLoader()">
          <div class="flex-1">
            <label class="mb-1 block text-[12px] text-slate-600">คำค้นหา</label>
            <div class="relative">
              <input name="q" value="{{ $q }}"
                     class="w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 py-2 text-[13px] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35"
                     placeholder="พิมพ์ชื่อกระทู้ที่ต้องการค้นหา...">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </span>
            </div>
          </div>

          {{-- Search Button Icon --}}
          <button type="submit"
                  class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#0F2D5C] text-white hover:bg-[#0F2D5C]/90 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/45 focus:ring-offset-1"
                  title="ค้นหา" aria-label="ค้นหา">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </button>

          @if($q)
            <a href="{{ route('chat.index') }}"
               onclick="showLoader()"
               class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/30 focus:ring-offset-1"
               title="ล้างการค้นหา">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </a>
          @endif
        </form>
      </div>
    </div>
  </div>

  {{-- List Header (Full Width) --}}
  <div class="px-4 md:px-6 lg:px-8 py-3 border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between">
      <div class="text-[13px] font-bold text-slate-800 uppercase tracking-wide">รายการกระทู้ล่าสุด</div>
      <div class="text-[12px] text-slate-500 font-medium">ทั้งหมด {{ number_format($threads->total()) }} รายการ</div>
    </div>
  </div>

  {{-- List Body (Full Width) --}}
  <div class="divide-y divide-slate-100 bg-white shadow-sm">
    @forelse($threads as $th)
      <div class="group relative hover:bg-slate-50/80 transition-colors">
        <a href="{{ route('chat.show', $th) }}" class="block px-4 md:px-6 lg:px-8 py-4" onclick="showLoader()">
          <div class="flex flex-col md:flex-row md:items-center gap-4">
            
            {{-- Status & Meta --}}
            <div class="flex items-center gap-3 md:w-32 shrink-0">
              @if($th->is_locked)
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-600 border border-amber-200 uppercase">LOCKED</span>
              @else
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-tighter">ACTIVE</span>
              @endif

              <div class="flex items-center gap-1 text-slate-400 text-[11px] font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span>{{ $th->messages_count ?? 0 }}</span>
              </div>
            </div>

            {{-- Content --}}
            <div class="min-w-0 flex-1">
              <h3 class="truncate text-[15px] font-semibold text-slate-900 group-hover:text-[#0F2D5C] group-hover:underline">
                {{ $th->title }}
              </h3>
              <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-slate-500">
                <span class="font-medium text-slate-700 underline underline-offset-2">{{ $th->author->name ?? 'Unknown user' }}</span>
                <span>•</span>
                <span>อัปเดตล่าสุด: {{ $th->updated_at->diffForHumans() }}</span>
              </div>
            </div>

            {{-- Action Arrow --}}
            <div class="hidden md:block text-slate-200 group-hover:text-[#0F2D5C] transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </div>

          </div>
        </a>
      </div>
    @empty
      <div class="py-24 text-center text-slate-400 text-[13px] bg-white">
        <svg viewBox="0 0 24 24" class="mx-auto h-12 w-12 text-slate-200 mb-2" fill="none" stroke="currentColor"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v10Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        ไม่พบข้อมูลกระทู้ในขณะนี้
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  @if($threads->hasPages())
    <div class="px-4 md:px-6 lg:px-8 mt-6 mb-10">
      {{ $threads->withQueryString()->links() }}
    </div>
  @endif
</div>

<form id="hidden-create-thread" method="POST" action="{{ route('chat.store') }}" class="hidden">
  @csrf
  <input type="hidden" name="title" id="final-thread-title">
</form>

<script>
  // ใช้ปุ่มสร้างกระทู้ใหม่ให้ตรงพฤติกรรมเดิม
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnCreateThread')?.addEventListener('click', function(e) {
      e.preventDefault();
      
      // ขอหัวข้อกระทู้ผ่าน prompt เนื่องจากตัวเลือก input แถวที่ 2 ถูกนำออกตามความต้องการของ User
      const inputVal = window.prompt('กรุณากรอกหัวข้อกระทู้ที่ต้องการสร้าง / Please enter thread title:');
 
      if(inputVal === null) return; // กดยกเลิก
      
      const trimmedVal = inputVal.trim();
      if(!trimmedVal) {
        alert('กรุณากรอกหัวข้อกระทู้ที่ต้องการสร้าง');
        return;
      }
 
      document.getElementById('final-thread-title').value = trimmedVal;
      showLoader();
      document.getElementById('hidden-create-thread').submit();
    });
  });

  function showLoader(){ document.getElementById('loaderOverlay')?.classList.add('show') }
  function hideLoader(){ document.getElementById('loaderOverlay')?.classList.remove('show') }
  document.addEventListener('DOMContentLoaded', hideLoader);
</script>
@endsection

@section('after-content')
<div id="loaderOverlay" class="loader-overlay">
  <div class="loader-spinner"></div>
</div>

<style>
  .loader-overlay{position:fixed;inset:0;background:rgba(255,255,255,.6);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:99999;visibility:hidden;opacity:0;transition:opacity .2s,visibility .2s}
  .loader-overlay.show{visibility:visible;opacity:1}
  .loader-spinner{width:36px;height:36px;border:3.5px solid #0F2D5C;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
</style>
@endsection
