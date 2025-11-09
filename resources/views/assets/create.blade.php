@extends('layouts.app')
@section('title','Create Asset')

@section('page-header')
  {{-- Header โทนอ่อนพร้อมไอคอน --}}
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            {{-- ไอคอนเพิ่มข้อมูล (ไม่ต้องใช้ lib เพิ่ม) --}}
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Create Asset
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            เพิ่มครุภัณฑ์ใหม่เข้าสู่ระบบ — โปรดระบุข้อมูลให้ครบถ้วนเพื่อความถูกต้องในการจัดเก็บ
          </p>
        </div>

        <a href="{{ route('assets.index') }}"
           class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 transition">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    {{-- Error -> Toast (แทนที่แผงสรุป) --}}
    @if ($errors->any())
      @push('scripts')
      <script>
        (function(){
          const msgs = @json($errors->all());
          const msg  = msgs.length ? ('มีข้อผิดพลาดในการบันทึก: ' + msgs.join(' • ')) : 'มีข้อผิดพลาดในการบันทึกข้อมูล';
          if (window.showToast) {
            window.showToast({ type:'error', message: msg, position:'uc', timeout: 3600, size:'lg' });
          } else {
            // fallback: dispatch app:toast event
            window.dispatchEvent(new CustomEvent('app:toast',{ detail:{ type:'error', message: msg, position:'uc', timeout:3600, size:'lg' } }));
          }
        })();
      </script>
      @endpush
    @endif

    <form method="POST" action="{{ route('assets.store') }}"
          onsubmit="window.dispatchEvent(new CustomEvent('app:toast',{detail:{type:'info',message:'กำลังบันทึก...'}}))"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      @csrf

      {{-- ใช้ partial เดียวกับหน้าแก้ไข เพื่อความสม่ำเสมอของฟิลด์ทั้งหมด --}}
      @include('assets._fields', [
        'asset' => new \App\Models\Asset(),
        'categories' => $categories ?? null,
        'departments' => $departments ?? null
      ])

      {{-- Actions --}}
      <div class="pt-2 flex justify-end gap-2">
        <a href="{{ route('assets.index') }}"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50">
          Cancel
        </a>
        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
          Save
        </button>
      </div>
    </form>
  </div>
@endsection
