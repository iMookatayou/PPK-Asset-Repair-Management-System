@extends('layouts.app')

@section('title', 'Settings - Maintenance Types')

@section('content')
@php
  use Illuminate\Support\Facades\Route;

  $search = $search ?? request('search', '');
  $active = isset($active) ? $active : request('active', '');

  $types  = $types ?? collect();

  $total = method_exists($types, 'total')
    ? $types->total()
    : (method_exists($types, 'count') ? $types->count() : 0);

  $primary = '#0F2D5C';

  // ✅ helper: แสดงเป็น "ตัวหนังสือ" สีเขียว/แดง (ไม่มี badge/pill)
  $statusText = function(bool $isActive) {
    if ($isActive) {
      return '<span class="font-semibold text-emerald-700">ใช้งาน</span>';
    }
    return '<span class="font-semibold text-rose-700">ปิดใช้งาน</span>';
  };
@endphp

<div class="w-full flex flex-col">

  {{-- ✅ Sticky Header + Filters (เหมือนหน้า Requests) --}}
  <div class="sticky top-16 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="px-4 md:px-6 lg:px-8 py-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-[17px] font-semibold text-slate-900">ตั้งค่า - ประเภทงานซ่อม</h1>
          <p class="text-[13px] text-slate-600">จัดการประเภทงานซ่อม • เพิ่ม/แก้ไข/ปิดใช้งาน</p>
        </div>

        <div class="flex items-center gap-2">
          <a href="{{ route('maintenance.requests.index') }}"
             class="inline-flex items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50
                    focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/25">
            {{-- ✅ back icon "<" --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
            </svg>
            กลับหน้ารายการใบงาน
          </a>

          <button type="button" id="openCreateBtn"
                  class="inline-flex items-center gap-2 rounded-md bg-[{{ $primary }}] px-4 py-2 text-[13px] font-medium text-white hover:bg-[{{ $primary }}]/90
                         focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/40">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            เพิ่มประเภท
          </button>
        </div>
      </div>

      <form method="GET"
            action="{{ route('settings.maintenance-types.index') }}"
            class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">

        <div class="md:col-span-8 min-w-0">
          <label class="mb-1 block text-[12px] text-slate-600">ค้นหา</label>
          <div class="relative">
            <input name="search" value="{{ $search }}"
                   placeholder="ชื่อ / คำอธิบาย / หน่วยงาน (DEPT) / บทบาท (ROLE)"
                   class="w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 py-2 text-[13px] placeholder:text-slate-400
                          focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </span>
          </div>
        </div>

        <div class="md:col-span-3">
          <label class="mb-1 block text-[12px] text-slate-600">สถานะ</label>
          <select name="active"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            <option value="" @selected($active === '' || $active === null)>ทั้งหมด</option>
            <option value="1" @selected((string)$active === '1')>ใช้งาน</option>
            <option value="0" @selected((string)$active === '0')>ปิดใช้งาน</option>
          </select>
        </div>

        {{-- ✅ ปุ่มสูงเท่ากัน (วงกลม h-11) --}}
        <div class="md:col-span-1 flex items-end justify-end gap-2">
          <a href="{{ route('settings.maintenance-types.index') }}"
             class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900
                    focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/30 focus:ring-offset-1"
             title="รีเซ็ต" aria-label="รีเซ็ต">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </a>

          <button type="submit"
                  class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[{{ $primary }}] text-white hover:bg-[{{ $primary }}]/90
                         focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/45 focus:ring-offset-1"
                  title="ค้นหา" aria-label="ค้นหา">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- alerts (เหลือแค่ errors; success ใช้ toast แล้ว) --}}
  <div class="px-4 md:px-6 lg:px-8">
    @if($errors->any())
      <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3">
        <div class="text-[13px] font-semibold text-rose-700">กรุณาตรวจสอบข้อมูล</div>
        <ul class="mt-2 list-disc pl-5 text-[13px] text-rose-700 space-y-1">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  </div>

  {{-- header row --}}
  <div class="px-4 md:px-6 lg:px-8 py-2 border-b border-slate-200">
    <div class="flex items-center justify-between">
      <div class="text-[13px] font-semibold text-slate-800">รายการประเภทงานซ่อม</div>
      <div class="text-[12px] text-slate-500">ทั้งหมด {{ $total }} รายการ</div>
    </div>
  </div>

  {{-- table --}}
  <div class="overflow-x-auto">
    <table class="min-w-full text-[13px]">
      <thead class="bg-white">
        <tr class="text-slate-600">
          <th class="p-3 text-left font-semibold border-b border-slate-200">ชื่อ</th>
          <th class="p-3 text-left font-semibold border-b border-slate-200">คำอธิบาย</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[90px]">ลำดับ</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[110px]">สถานะ</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[120px]">หน่วยงาน (DEPT)</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[130px]">บทบาท (ROLE)</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[90px]">USER</th>
          <th class="p-3 text-center font-semibold border-b border-slate-200 w-[190px]">จัดการ</th>
        </tr>
      </thead>

      <tbody class="bg-white">
        @forelse($types as $t)
          @php
            $isActive = (bool)($t->is_active ?? false);
          @endphp
          <tr class="border-b border-slate-100 hover:bg-slate-50/60">
            <td class="p-3 font-semibold text-slate-900 whitespace-nowrap">
              {{ $t->name }}
            </td>

            <td class="p-3 text-slate-700">
              {{ $t->description ?: '—' }}
            </td>

            <td class="p-3 text-center text-slate-700">
              {{ (int)($t->sort_order ?? 0) }}
            </td>

            <td class="p-3 text-center">
              {!! $statusText($isActive) !!}
            </td>

            <td class="p-3 text-center text-slate-700">
              {{ $t->default_department_code ?: '—' }}
            </td>

            <td class="p-3 text-center text-slate-700">
              {{ $t->default_role_code ?: '—' }}
            </td>

            <td class="p-3 text-center text-slate-700">
              {{ $t->default_user_id ?: '—' }}
            </td>

            <td class="p-3 text-center whitespace-nowrap">
              <button type="button"
                      class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50
                             focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/25"
                      data-edit="1"
                      data-id="{{ $t->id }}"
                      data-name="{{ e($t->name) }}"
                      data-description="{{ e($t->description ?? '') }}"
                      data-sort_order="{{ (int)($t->sort_order ?? 0) }}"
                      data-is_active="{{ $isActive ? 1 : 0 }}"
                      data-default_department_code="{{ e($t->default_department_code ?? '') }}"
                      data-default_role_code="{{ e($t->default_role_code ?? '') }}"
                      data-default_user_id="{{ e((string)($t->default_user_id ?? '')) }}">
                แก้ไข
              </button>

              <form method="POST"
                    action="{{ route('settings.maintenance-types.destroy', $t->id) }}"
                    class="inline"
                    onsubmit="return confirm('ยืนยันปิดใช้งานประเภทนี้?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-rose-600 px-3 py-1.5 text-[12px] font-medium text-white hover:bg-rose-700
                               focus:outline-none focus:ring-2 focus:ring-rose-400/40">
                  ปิดใช้งาน
                </button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="py-16 text-center text-slate-600">
              <div class="flex flex-col items-center gap-2">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-[13px]">ไม่พบรายการประเภทงาน</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if(method_exists($types, 'links') && $types->hasPages())
    <div class="px-4 md:px-6 lg:px-8 mt-4 mb-6 md:mb-10 lg:mb-12">
      {{ $types->withQueryString()->links() }}
    </div>
  @endif
</div>
@endsection

{{-- ✅ ย้าย modal ไปท้าย body กันโดน z-index ของ navbar/sidebar --}}
@section('after-content')
  {{-- =========================
   | Create Modal
   * ========================= --}}
  <div id="createModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/40 p-3 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
      <div class="w-full max-w-2xl relative z-[10000] rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
          <div class="text-[14px] font-semibold text-slate-900">เพิ่มประเภทงานซ่อม</div>
          <button type="button" id="createCloseBtn"
                  class="h-9 w-9 rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800">
            <svg class="h-4 w-4 mx-auto" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form method="POST" action="{{ route('settings.maintenance-types.store') }}" class="px-5 py-5 space-y-4">
          @csrf

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">ชื่อประเภท <span class="text-rose-600">*</span></label>
              <input name="name" required
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35"
                     placeholder="เช่น Software">
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">คำอธิบาย</label>
              <input name="description"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35"
                     placeholder="เช่น ปัญหาโปรแกรม/ระบบ/ติดตั้ง/อัปเดต">
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">ลำดับ (Sort Order)</label>
              <input name="sort_order" type="number" min="0" value="0"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">สถานะการใช้งาน (Active)</label>
              <select name="is_active"
                      class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                             focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
                <option value="1" selected>ใช้งาน</option>
                <option value="0">ปิดใช้งาน</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">หน่วยงานเริ่มต้น (DEPT default)</label>
              <input name="default_department_code"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35"
                     placeholder="เช่น IT (หน่วยงานไอที)">
              <p class="mt-1 text-[11px] text-slate-500">DEPT = Department (หน่วยงาน) ที่ระบบจะตั้งค่าให้เป็นค่าเริ่มต้น</p>
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">บทบาทเริ่มต้น (ROLE default)</label>
              <input name="default_role_code"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35"
                     placeholder="เช่น technician (ช่าง) / programmer (โปรแกรมเมอร์)">
              <p class="mt-1 text-[11px] text-slate-500">ROLE = Role (บทบาทผู้รับงาน) ที่ระบบตั้งค่าให้โดยอัตโนมัติ</p>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">ผู้ใช้เริ่มต้น (USER ID default)</label>
              <input name="default_user_id" type="number" min="1"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35"
                     placeholder="เช่น 12 (รหัสผู้ใช้ผู้รับผิดชอบเริ่มต้น)">
              <p class="mt-1 text-[11px] text-slate-500">ถ้ากรอก ระบบสามารถผูกประเภทนี้กับ “ผู้รับผิดชอบเริ่มต้น” ได้</p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" id="createCancelBtn"
                    class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
              ยกเลิก
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-[{{ $primary }}] px-4 py-2 text-[13px] font-medium text-white hover:bg-[{{ $primary }}]/90">
              บันทึก
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="editModal" class="fixed inset-0 z-[9999] hidden bg-slate-900/40 p-3 overflow-y-auto">
    <div class="min-h-full flex items-center justify-center">
      <div class="w-full max-w-2xl relative z-[10000] rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
          <div class="text-[14px] font-semibold text-slate-900">แก้ไขประเภทงาน</div>
          <button type="button" id="editCloseBtn"
                  class="h-9 w-9 rounded-full text-slate-500 hover:bg-slate-100 hover:text-slate-800">
            <svg class="h-4 w-4 mx-auto" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form id="editForm" method="POST" action="" class="px-5 py-5 space-y-4">
          @csrf
          @method('PUT')

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">ชื่อประเภท <span class="text-rose-600">*</span></label>
              <input id="e_name" name="name" required
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">คำอธิบาย</label>
              <input id="e_description" name="description"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">ลำดับ (Sort Order)</label>
              <input id="e_sort_order" name="sort_order" type="number" min="0"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">สถานะการใช้งาน (Active)</label>
              <select id="e_is_active" name="is_active"
                      class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                             focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
                <option value="1">ใช้งาน</option>
                <option value="0">ปิดใช้งาน</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">หน่วยงานเริ่มต้น (DEPT default)</label>
              <input id="e_default_department_code" name="default_department_code"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
              <p class="mt-1 text-[11px] text-slate-500">DEPT = Department (หน่วยงาน) ค่าเริ่มต้น</p>
            </div>

            <div>
              <label class="mb-1 block text-[12px] text-slate-600">บทบาทเริ่มต้น (ROLE default)</label>
              <input id="e_default_role_code" name="default_role_code"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
              <p class="mt-1 text-[11px] text-slate-500">ROLE = Role (บทบาทผู้รับงาน) ค่าเริ่มต้น</p>
            </div>

            <div class="sm:col-span-2">
              <label class="mb-1 block text-[12px] text-slate-600">ผู้ใช้เริ่มต้น (USER ID default)</label>
              <input id="e_default_user_id" name="default_user_id" type="number" min="1"
                     class="w-full rounded-md border border-slate-200 px-3 py-2 text-[13px]
                            focus:outline-none focus:ring-2 focus:ring-[{{ $primary }}]/35 focus:border-[{{ $primary }}]/35">
              <p class="mt-1 text-[11px] text-slate-500">กำหนดผู้รับผิดชอบเริ่มต้นของประเภทนี้ (ถ้าต้องการ)</p>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" id="editCancelBtn"
                    class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
              ยกเลิก
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-[{{ $primary }}] px-4 py-2 text-[13px] font-medium text-white hover:bg-[{{ $primary }}]/90">
              บันทึกการแก้ไข
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function () {
  const body = document.body;

  function openModal(el){
    if(!el) return;
    el.classList.remove('hidden');
    el.classList.add('block');
    body.classList.add('overflow-hidden');
  }
  function closeModal(el){
    if(!el) return;
    el.classList.add('hidden');
    el.classList.remove('block');
    body.classList.remove('overflow-hidden');
  }
  function bindBackdropClose(el, closeFn){
    el?.addEventListener('click', (e) => { if(e.target === el) closeFn(); });
  }

  // Create
  const createModal = document.getElementById('createModal');
  const openCreateBtn = document.getElementById('openCreateBtn');
  const createCloseBtn = document.getElementById('createCloseBtn');
  const createCancelBtn = document.getElementById('createCancelBtn');

  const showCreate = () => openModal(createModal);
  const hideCreate = () => closeModal(createModal);

  openCreateBtn?.addEventListener('click', showCreate);
  createCloseBtn?.addEventListener('click', hideCreate);
  createCancelBtn?.addEventListener('click', hideCreate);
  bindBackdropClose(createModal, hideCreate);

  // Edit
  const editModal = document.getElementById('editModal');
  const editCloseBtn = document.getElementById('editCloseBtn');
  const editCancelBtn = document.getElementById('editCancelBtn');
  const editForm = document.getElementById('editForm');

  const eName  = document.getElementById('e_name');
  const eDesc  = document.getElementById('e_description');
  const eSort  = document.getElementById('e_sort_order');
  const eAct   = document.getElementById('e_is_active');
  const eDept  = document.getElementById('e_default_department_code');
  const eRole  = document.getElementById('e_default_role_code');
  const eUser  = document.getElementById('e_default_user_id');

  const showEdit = () => openModal(editModal);
  const hideEdit = () => closeModal(editModal);

  editCloseBtn?.addEventListener('click', hideEdit);
  editCancelBtn?.addEventListener('click', hideEdit);
  bindBackdropClose(editModal, hideEdit);

  // update route template
  const updateTpl = @json(route('settings.maintenance-types.update', ':id'));

  document.querySelectorAll('[data-edit="1"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      if(!id) return;

      editForm.setAttribute('action', String(updateTpl).replace(':id', id));

      eName.value = btn.getAttribute('data-name') || '';
      eDesc.value = btn.getAttribute('data-description') || '';
      eSort.value = btn.getAttribute('data-sort_order') || '0';
      eAct.value  = btn.getAttribute('data-is_active') || '1';
      eDept.value = btn.getAttribute('data-default_department_code') || '';
      eRole.value = btn.getAttribute('data-default_role_code') || '';
      eUser.value = btn.getAttribute('data-default_user_id') || '';

      showEdit();
      setTimeout(() => eName?.focus?.(), 0);
    });
  });

  // ESC close
  document.addEventListener('keydown', (e) => {
    if(e.key !== 'Escape') return;
    if(createModal && !createModal.classList.contains('hidden')) hideCreate();
    if(editModal && !editModal.classList.contains('hidden')) hideEdit();
  });
})();
</script>
@endpush
