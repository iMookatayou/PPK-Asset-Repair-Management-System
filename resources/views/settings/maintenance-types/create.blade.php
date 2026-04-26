@extends('layouts.app')

@php
    $line = 'border-slate-200';
    $input = "mt-2 w-full h-11 rounded-md border $line bg-white px-3 py-2 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
    $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
    
    $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
    $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600 flex items-center justify-center text-sm font-bold text-white leading-none";
    $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
    $subCls    = "text-sm text-slate-500 leading-snug";
    $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
    $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";
@endphp

@section('title', 'เพิ่มประเภทการแจ้งซ่อม')

@section('page-header')
  <div class="w-full bg-slate-50 border-b {{ $line }}">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl text-emerald-700">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14m7-7H5"/>
              </svg>
            </span>
            <div class="min-w-0">
              <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
                เพิ่มประเภทงานซ่อม
              </h1>
              <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                <span>สร้างประเภทงานซ่อมใหม่ในระบบ</span>
              </div>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
          <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('settings.maintenance-types.index') }}"
             class="inline-flex items-center h-9 gap-2 rounded-md border {{ $line }} bg-white px-4 text-[13px] font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 transition-all">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            กลับ
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 pb-8 pt-0">

    @if ($errors->any())
        @push('scripts')
            <script>
                (function() {
                    const errors = @json($errors->all());
                    errors.forEach(err => {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: err,
                                duration: 3500
                            }
                        }));
                    });
                })();
            </script>
        @endpush
    @endif

    <form method="POST" action="{{ route('settings.maintenance-types.store') }}" class="space-y-8" novalidate onsubmit="let btn = this.querySelector('button[type=\'submit\']'); setTimeout(() => { btn.disabled = true; btn.classList.add('opacity-50', 'cursor-not-allowed'); btn.innerText = 'กำลังบันทึก...'; }, 10);">
      @csrf

      <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 mt-10">
        <div class="space-y-10">
          
          <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

            {{-- Section 1: Basic Info --}}
            <section>
              <div class="{{ $headCls }}">
                <div class="{{ $noCls }}">1</div>
                <div class="{{ $accentWrap }}">
                  <span class="{{ $accentBar }}"></span>
                  <div class="{{ $titleCls }}">ข้อมูลพื้นฐาน</div>
                  <div class="{{ $subCls }}">ระบุชื่อและรายละเอียดประเภทงาน</div>
                </div>
              </div>

              <div class=" space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">
                        ชื่อประเภทงานซ่อม <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="off" class="{{ $input }}" required placeholder="เช่น งานซ่อมคอมพิวเตอร์, งานประปา">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">รายละเอียด / คำอธิบาย</label>
                    <textarea name="description" id="description" rows="4" class="{{ $textarea }}" placeholder="ระบุรายละเอียดเพิ่มเติม...">{{ old('description') }}</textarea>
                </div>
              </div>
            </section>

            {{-- Section 2: Display Settings --}}
            <section>
              <div class="{{ $headCls }}">
                <div class="{{ $noCls }}">2</div>
                <div class="{{ $accentWrap }}">
                  <span class="{{ $accentBar }}"></span>
                  <div class="{{ $titleCls }}">การตั้งค่าแสดงผล</div>
                  <div class="{{ $subCls }}">กำหนดลำดับและสถานะการใช้งาน</div>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-slate-700">ลำดับการแสดงผล</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="{{ $input }}">
                    <p class="mt-1 text-[11px] text-slate-500 font-normal">ตัวเลขน้อยจะแสดงก่อนในรายการเลือก (ค่าเริ่มต้นคือ 0)</p>
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-slate-700">สถานะการใช้งาน</label>
                    <select name="is_active" id="is_active" class="{{ $input }}">
                        <option value="1" @selected(old('is_active', true) == true)>เปิดใช้งาน (Active)</option>
                        <option value="0" @selected(old('is_active', true) == false)>ปิดใช้งาน (Inactive)</option>
                    </select>
                </div>
              </div>
            </section>

            {{-- Section 3: SLA Targets --}}
            <section>
              <div class="{{ $headCls }}">
                <div class="{{ $noCls }}">3</div>
                <div class="{{ $accentWrap }}">
                  <span class="{{ $accentBar }}"></span>
                  <div class="{{ $titleCls }}">เป้าหมายเวลา (SLA Targets)</div>
                  <div class="{{ $subCls }}">กำหนดเป้าหมายเวลาพื้นฐานของประเภทงานนี้</div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                      <label for="default_response_minutes" class="block text-sm font-medium text-slate-700">เวลาตอบกลับพื้นฐาน (นาที)</label>
                      <input type="number" name="default_response_minutes" id="default_response_minutes" value="{{ old('default_response_minutes') }}" min="0" class="{{ $input }}" placeholder="เช่น 60">
                      <p class="mt-1 text-[11px] text-slate-500 font-normal">ใช้เป็นค่าเริ่มต้นในการรับทราบงาน (Acknowledged)</p>
                  </div>
  
                  <div>
                      <label for="default_resolution_minutes" class="block text-sm font-medium text-slate-700">เวลาซ่อมแซมพื้นฐาน (นาที)</label>
                      <input type="number" name="default_resolution_minutes" id="default_resolution_minutes" value="{{ old('default_resolution_minutes') }}" min="0" class="{{ $input }}" placeholder="เช่น 1440">
                      <p class="mt-1 text-[11px] text-slate-500 font-normal">ใช้เป็นค่าเริ่มต้นในการปิดงาน (Resolved)</p>
                  </div>
                </div>
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                  <p class="text-[11px] text-emerald-800 leading-relaxed">
                    <strong>คำแนะนำ:</strong> เวลาที่กำหนดหน้านี้จะเป็นตัวชี้วัด (Target) หลักสำหรับ SLA ของใบงานประเภทนี้ทั้งหมด
                  </p>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-4 border-t {{ $line }}">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('settings.maintenance-types.index') }}"
           class="inline-flex items-center justify-center h-10 px-4 rounded-lg border {{ $line }} bg-white
                  text-sm font-medium text-slate-700 hover:bg-slate-50">
          ยกเลิก
        </a>
        <button type="submit"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600
                       text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200 transition-all">
          บันทึกข้อมูล
        </button>
      </div>
    </form>
  </div>
@endsection
