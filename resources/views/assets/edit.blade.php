{{-- resources/views/assets/edit.blade.php --}}
@extends('layouts.app')
@section('title','Edit Asset')

@section('page-header')
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 20h4l10-10-4-4L4 16v4zM13 7l4 4M4 20l4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Edit Asset
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            แก้ไขข้อมูลครุภัณฑ์:
            <span class="font-medium text-slate-800">{{ $asset->asset_code }}</span>
            <span class="text-slate-400">—</span>
            <span class="italic text-slate-500">{{ $asset->name }}</span>
          </p>
        </div>

        {{-- ปุ่ม Back ใช้สไตล์เดียวกับ Create Asset --}}
        <a href="{{ route('assets.index') }}"
           class="asset-btn asset-btn-outline"
           aria-label="Back to list">
          <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          กลับ
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    @if ($errors->any())
      @push('scripts')
      <script>
        (function(){
          const msgs = @json($errors->all());
          const msg  = msgs.length ? ('มีข้อผิดพลาดในการบันทึก: ' + msgs.join(' • ')) : 'มีข้อผิดพลาดในการบันทึกข้อมูล';
          if (window.showToast) {
            window.showToast({ type:'error', message: msg, position:'uc', timeout: 3600, size:'lg' });
          } else {
            window.dispatchEvent(new CustomEvent('app:toast',{
              detail:{ type:'error', message: msg, position:'uc', timeout:3600, size:'lg' }
            }));
          }
        })();
      </script>
      @endpush
    @endif

    <form method="POST"
          action="{{ route('assets.update', $asset) }}"
          onsubmit="window.dispatchEvent(new CustomEvent('app:toast',{detail:{type:'info',message:'กำลังบันทึก...'}}))"
          class="asset-form rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          novalidate
          aria-label="แบบฟอร์มแก้ไขครุภัณฑ์">
      @csrf
      @method('PUT')

      @include('assets._fields', [
        'asset'       => $asset,
        'categories'  => $categories ?? null,
        'departments' => $departments ?? null,
      ])

      <div class="mt-6 flex justify-end gap-2">
        <a href="{{ route('assets.index') }}"
           class="asset-btn asset-btn-outline">
          ยกเลิก
        </a>
        <button type="submit"
                class="asset-btn asset-btn-primary">
          อัปเดต
        </button>
      </div>
    </form>
  </div>
@endsection

{{-- ===========================
     Tom Select + Styling
     ใช้กับ: #category_id, #department_id, #status
=========================== --}}
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
  /* ขนาด input / select */
  .asset-form input[type="text"],
  .asset-form input[type="email"],
  .asset-form input[type="password"],
  .asset-form input[type="date"],
  .asset-form input[type="number"],
  .asset-form select:not([multiple]) {
    height: 44px;
    border-radius: 0.75rem;
    box-sizing: border-box;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
  }

  /* ปุ่ม */
  .asset-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 1rem;
    height: 44px;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 500;
    border: 1px solid rgb(148,163,184);
    background-color: #ffffff;
    color: rgb(51,65,85);
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    text-decoration: none;
    gap: 0.25rem;
  }

  .asset-btn svg { flex-shrink: 0; }

  .asset-btn:hover {
    background-color: rgb(248,250,252);
  }

  .asset-btn-primary {
    border-color: rgb(5,150,105);
    background-color: rgb(5,150,105);
    color: #ffffff;
  }

  .asset-btn-primary:hover {
    background-color: rgb(4,120,87);
    border-color: rgb(4,120,87);
  }

  /* ===== TomSelect ===== */
  .asset-form .ts-wrapper.ts-basic {
    border: none !important;
    padding: 0 !important;
    box-shadow: none !important;
    background: transparent;
  }

  .asset-form .ts-wrapper.ts-basic .ts-control {
    position: relative;             /* สำคัญ: ให้ icon absolute อ้างอิงได้ */
    border-radius: 0.75rem;
    border: 1px solid rgb(226,232,240);
    padding: 0 0.75rem;
    box-shadow: none;
    min-height: 44px;
    background-color: #fff;
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    line-height: 1.25rem;
    white-space: nowrap;
    overflow: hidden;
  }

  .asset-form .ts-wrapper.ts-basic .ts-control .item {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
  }

  /* เผื่อที่ให้ไอคอน */
  .asset-form .ts-wrapper.ts-basic.ts-with-icon .ts-control {
    padding-left: 2.6rem;
  }

  .asset-form .ts-wrapper.ts-basic .ts-control input {
    font-size: 0.875rem;
    line-height: 1.25rem;
    min-width: 0;
    flex: 1 1 auto;
  }

  .asset-form .ts-wrapper.ts-basic .ts-control.focus {
    border-color: rgb(5,150,105);
    box-shadow: none;
  }

  .asset-form .ts-wrapper.ts-basic .ts-dropdown {
    border-radius: 0.5rem;
    border-color: rgb(226,232,240);
    box-shadow: 0 10px 15px -3px rgba(15,23,42,0.15);
    z-index: 50;
    font-size: 0.875rem;
    line-height: 1.25rem;
  }

  .asset-form .ts-wrapper.ts-basic.ts-error .ts-control {
    border-color: rgb(248,113,113) !important;
  }

  /* icon แว่นขยาย อยู่ข้างใน .ts-control */
  .asset-form .ts-wrapper.ts-with-icon .ts-control .ts-select-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    color: rgb(148,163,184);
  }

  .asset-form .ts-wrapper.ts-with-icon .ts-control .ts-select-icon svg {
    width: 16px;
    height: 16px;
  }

  /* ซ่อน select เดิม */
  .asset-form select.ts-hidden-accessible {
    display: none !important;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {

    function initTomSelectWithIcon(selector, placeholderText) {
      const el = document.querySelector(selector);
      if (!el) return;

      const ts = new TomSelect(selector, {
        create: false,
        allowEmptyOption: true,
        maxOptions: 500,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: placeholderText,
        searchField: ['text'],
      });

      const wrapper = ts.wrapper;
      if (!wrapper) return;

      wrapper.classList.add('ts-with-icon');

      const control = wrapper.querySelector('.ts-control');
      if (!control) return;

      // ใส่ไอคอนเข้าไปใน .ts-control เลย
      const icon = document.createElement('span');
      icon.className = 'ts-select-icon';
      icon.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="11" cy="11" r="5" stroke="currentColor" stroke-width="2"></circle>
          <path d="M15 15l4 4" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      `;
      control.insertBefore(icon, control.firstChild);
    }

    initTomSelectWithIcon('#category_id',   '— เลือกหมวดหมู่ —');
    initTomSelectWithIcon('#department_id', '— เลือกหน่วยงาน —');
    initTomSelectWithIcon('#status',        '— เลือกสถานะ —');
  });
</script>
