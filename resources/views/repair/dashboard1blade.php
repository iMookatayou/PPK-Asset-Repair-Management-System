@extends('layouts.app')

@section('title', 'Asset Repair Dashboard — Hospital Theme')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endsection

@section('content')
@php
  $toast = session('toast');
  if ($toast) { session()->forget('toast'); }

  $type     = $toast['type']     ?? null;
  $message  = $toast['message']  ?? null;
  $position = $toast['position'] ?? 'tc';
  $timeout  = (int)($toast['timeout'] ?? 3200);
  $size     = $toast['size']     ?? 'lg';

  $firstError = ($errors ?? null)?->first();
  if (!$message && $firstError) { $message = $firstError; $type = $type ?: 'error'; }
  if (!$message && session('error')) { $message = session('error'); $type = $type ?: 'error'; }
  if (!$message && session('status')) { $message = session('status'); $type = $type ?: 'success'; }

  $monthlyTrend = is_iterable($monthlyTrend ?? null) ? collect($monthlyTrend) : collect();
  $byAssetType  = is_iterable($byAssetType  ?? null) ? collect($byAssetType)  : collect();
  $byDept       = is_iterable($byDept       ?? null) ? collect($byDept)       : collect();
  $recent       = is_iterable($recent       ?? null) ? collect($recent)       : collect();

  $monthlyTrend = $monthlyTrend->values();
  $byAssetType  = $byAssetType->values();
  $byDept       = $byDept->values();

  $intVal   = fn($v)=> is_numeric($v) ? (int)$v : 0;
  $strVal   = fn($v,$f='')=> is_string($v) && $v!=='' ? $v : $f;

  $trendLabels = $monthlyTrend->map(fn($i)=> $strVal(is_array($i)?($i['ym']??''):($i->ym??'')))->all();
  $trendCounts = $monthlyTrend->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

  $typeLabels  = $byAssetType->map(fn($i)=> $strVal(is_array($i)?($i['type']??'Unspecified'):($i->type??'Unspecified'),'Unspecified'))->all();
  $typeCounts  = $byAssetType->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

  $deptLabels  = $byDept->map(fn($i)=> $strVal(is_array($i)?($i['dept']??'Unspecified'):($i->dept??'Unspecified'),'Unspecified'))->all();
  $deptCounts  = $byDept->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

  $get = function($row, $key, $fallback='-'){
    if (is_array($row))  return data_get($row, $key, $fallback);
    if (is_object($row)) return data_get((array)$row, $key, $fallback);
    return $fallback;
  };

  $filtersActive = (string)request('status','') !== '' || (string)request('from','') !== '' || (string)request('to','') !== '';

  $line = 'border-slate-200';
  $NAVY = '#0b1f3b';

  $statusPillClass = function (string $status): string {
    return match ($status) {
      \App\Models\MaintenanceRequest::STATUS_PENDING     => 'bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200',
      \App\Models\MaintenanceRequest::STATUS_IN_PROGRESS => 'bg-blue-50 text-blue-900 ring-1 ring-inset ring-blue-200',
      \App\Models\MaintenanceRequest::STATUS_COMPLETED   => 'bg-slate-100 text-slate-800 ring-1 ring-inset ring-slate-200',
      default                                            => 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-200',
    };
  };
@endphp

{{-- HEADER (Sticky Dashboard Header) --}}
<div id="dashStickyHeader" class="sticky z-30"
     style="top: calc(var(--topbar-h, 4rem) + var(--dash-sticky-top, 8px));">
  <div class="w-full bg-slate-50 border-b {{ $line }}">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        {{-- LEFT --}}
        <div class="min-w-0">
          <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl" style="color: {{ $NAVY }}">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M9 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M7 3h7l3 3v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>

            <div class="min-w-0">
              <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
                Asset Repair Dashboard
                <span class="ml-2 text-slate-500 text-[13px] sm:text-[14px] font-semibold">
                  — รายงานภาพรวม
                </span>
              </h1>

              <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                <span>วันที่ออกรายงาน:
                  <span class="font-semibold text-slate-900">{{ now()->format('d/m/Y') }}</span>
                </span>
                <span>ตัวกรอง:
                  <span class="font-semibold text-slate-900">{{ $filtersActive ? 'กำลังใช้งาน' : 'ไม่ใช้งาน' }}</span>
                </span>
                <span class="hidden md:inline">
                  จัดทำโดย <span class="font-semibold text-slate-900">กลุ่มงานเทคโนโลยีสารสนเทศ</span>
                </span>
              </div>

              <p class="mt-2 text-xs text-slate-600 max-w-3xl hidden md:block">
                แสดงภาพรวมภาระงานซ่อมบำรุงครุภัณฑ์ แยกตามสถานะ หน่วยงาน และช่วงเวลา เพื่อใช้ประกอบการติดตามงานและรายงานต่อผู้บริหาร
              </p>
            </div>
          </div>
        </div>

        {{-- RIGHT --}}
        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
          @if ($filtersActive)
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-sky-50 text-sky-800 ring-1 ring-inset ring-sky-200">
              ใช้ตัวกรองอยู่
            </span>
          @endif

          <button id="filterToggle" type="button"
                  class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50"
                  aria-expanded="{{ $filtersActive ? 'true' : 'false' }}"
                  aria-controls="filtersPanel">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 4h18M6 8h12M9 12h6M11 16h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            ตัวกรองข้อมูล
          </button>

          <a href="{{ route('repair.dashboard') }}"
             class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 6v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M6 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            ล้างตัวกรอง
          </a>
        </div>
      </div>

      {{-- FILTER PANEL --}}
      <div id="filtersPanel" class="mt-4 border-t border-dashed {{ $line }} pt-4 {{ $filtersActive ? '' : 'hidden' }}">
        <div class="flex flex-col gap-1">
          <h2 class="text-xs font-semibold text-slate-900">ตัวกรองข้อมูลภาพรวมงานซ่อม</h2>
          <p class="text-[11px] text-slate-600 max-w-xl">เลือกช่วงเวลาและสถานะ เพื่อดูเฉพาะงานซ่อมที่สนใจ</p>
        </div>

        <form method="GET" class="mt-3">
          <div class="grid grid-cols-2 gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
              <label for="f_status" class="block text-xs font-medium text-slate-800">สถานะงานซ่อม</label>
              <select id="f_status" name="status"
                      class="mt-1 w-full rounded-lg border {{ $line }} bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-600 focus:ring-2 focus:ring-sky-600/15">
                <option value="">ทั้งหมด</option>
                <option value="pending"     {{ request('status')==='pending'?'selected':'' }}>รอดำเนินการ</option>
                <option value="in_progress" {{ request('status')==='in_progress'?'selected':'' }}>กำลังดำเนินการ</option>
                <option value="completed"   {{ request('status')==='completed'?'selected':'' }}>เสร็จสิ้น</option>
              </select>
            </div>

            <div>
              <label for="f_from" class="block text-xs font-medium text-slate-800">จากวันที่ (From)</label>
              <input id="f_from" type="date" name="from" value="{{ e(request('from','')) }}"
                     class="mt-1 w-full rounded-lg border {{ $line }} bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-600 focus:ring-2 focus:ring-sky-600/15">
            </div>

            <div>
              <label for="f_to" class="block text-xs font-medium text-slate-800">ถึงวันที่ (To)</label>
              <input id="f_to" type="date" name="to" value="{{ e(request('to','')) }}"
                     class="mt-1 w-full rounded-lg border {{ $line }} bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-600 focus:ring-2 focus:ring-sky-600/15">
            </div>

            <div class="md:col-span-2 flex items-end gap-2 justify-end">
              <button class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-white hover:opacity-95 focus:ring-2"
                      style="background: {{ $NAVY }};">
                ค้นหาตามเงื่อนไข
              </button>
              <a href="{{ route('repair.dashboard') }}"
                 class="inline-flex items-center rounded-lg border {{ $line }} bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                ล้างตัวกรอง
              </a>
            </div>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

{{-- BODY --}}
<div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  {{-- ✅ SECTION 1: ใช้ .dash-anchor ที่จัดระยะใน CSS ไว้แล้ว --}}
  <div id="section-1" class="dash-anchor space-y-4">

    {{-- หัวข้อ Section --}}
    <div class="flex flex-col gap-1">
      <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700 w-fit">
        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] text-white" style="background: {{ $NAVY }}">
          1
        </span>
        <span>ส่วนที่ 1 • ภาพรวมสถิติ</span>
      </div>
      <h2 class="text-sm sm:text-base font-semibold text-slate-900 break-words">ภาพรวมจำนวนงานซ่อมในระบบ</h2>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        แสดงจำนวนงานซ่อมทั้งหมดและจำแนกตามสถานะหลัก เพื่อเห็นปริมาณงานในระบบโดยรวม
      </p>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <article class="relative overflow-hidden rounded-xl border {{ $line }} bg-white shadow-sm hover:shadow-md transition-shadow">
        <div class="absolute inset-y-0 left-0 w-1.5 bg-slate-700"></div>
        <div class="flex items-start justify-between gap-3 px-4 pt-3 pb-1 pl-5">
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold tracking-[0.12em] text-slate-500 uppercase truncate">TOTAL JOBS</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 leading-tight truncate">{{ number_format($stats['total'] ?? 0) }}</p>
          </div>
          <div class="shrink-0 rounded-full border {{ $line }} bg-slate-50 p-2">
            <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M4 4h16v4H4z" /><path d="M4 10h10v4H4z" /><path d="M4 16h7v4H4z" />
            </svg>
          </div>
        </div>
        <div class="px-4 pb-3 pt-1 pl-5">
          <p class="text-[11px] text-slate-500 truncate">งานซ่อมทั้งหมดในระบบ</p>
        </div>
      </article>

      <article class="relative overflow-hidden rounded-xl border {{ $line }} bg-white shadow-sm hover:shadow-md transition-shadow">
        <div class="absolute inset-y-0 left-0 w-1.5 bg-sky-500"></div>
        <div class="flex items-start justify-between gap-3 px-4 pt-3 pb-1 pl-5">
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold tracking-[0.12em] text-sky-700 uppercase truncate">PENDING</p>
            <p class="mt-1 text-3xl font-semibold text-sky-800 leading-tight truncate">{{ number_format($stats['pending'] ?? 0) }}</p>
          </div>
          <div class="shrink-0 rounded-full border {{ $line }} bg-sky-50 p-2">
            <svg class="h-5 w-5 text-sky-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
        </div>
        <div class="px-4 pb-3 pt-1 pl-5">
          <p class="text-[11px] text-slate-600 truncate">รอรับเรื่อง/รอดำเนินการ</p>
        </div>
      </article>

      <article class="relative overflow-hidden rounded-xl border {{ $line }} bg-white shadow-sm hover:shadow-md transition-shadow">
        <div class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $NAVY }}"></div>
        <div class="flex items-start justify-between gap-3 px-4 pt-3 pb-1 pl-5">
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold tracking-[0.12em] text-slate-700 uppercase truncate">IN PROGRESS</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 leading-tight truncate">{{ number_format($stats['inProgress'] ?? 0) }}</p>
          </div>
          <div class="shrink-0 rounded-full border {{ $line }} bg-slate-50 p-2">
            <svg class="h-5 w-5" style="color: {{ $NAVY }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M3 12h4l3 7 4-14 3 7h4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
        </div>
        <div class="px-4 pb-3 pt-1 pl-5">
          <p class="text-[11px] text-slate-600 truncate">กำลังดำเนินการแก้ไข</p>
        </div>
      </article>

      <article class="relative overflow-hidden rounded-xl border {{ $line }} bg-white shadow-sm hover:shadow-md transition-shadow">
        <div class="absolute inset-y-0 left-0 w-1.5 bg-slate-900"></div>
        <div class="flex items-start justify-between gap-3 px-4 pt-3 pb-1 pl-5">
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold tracking-[0.12em] text-slate-700 uppercase truncate">COMPLETED</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 leading-tight truncate">{{ number_format($stats['completed'] ?? 0) }}</p>
          </div>
          <div class="shrink-0 rounded-full border {{ $line }} bg-slate-50 p-2">
            <svg class="h-5 w-5 text-slate-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="12" r="9" /><path d="M8.5 12.5L11 15l4.5-5.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </div>
        </div>
        <div class="px-4 pb-3 pt-1 pl-5">
          <p class="text-[11px] text-slate-600 truncate">ดำเนินการเสร็จสิ้น</p>
        </div>
      </article>
    </div>

    {{-- Chart 1 --}}
    <div class="rounded-xl border {{ $line }} bg-white/80 px-4 py-4 sm:px-5 sm:py-4 relative z-0">
      <div>
        <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-700">
          กราฟที่ 1 — ภาพรวมสถานะงานซ่อม
        </div>
        <h3 class="mt-2 text-sm sm:text-base font-semibold text-slate-900">
          เทียบจำนวนงานซ่อมตามสถานะ (Pending / In Progress / Completed / Total)
        </h3>
        <p class="mt-0.5 text-xs text-slate-500 max-w-xl">กราฟแท่งแนวนอนโทนฟ้า/น้ำเงิน</p>
      </div>

      <div class="mt-4 h-56 w-full relative">
        <canvas id="statusTrend" class="block w-full h-full"
          data-pending="{{ $stats['pending'] ?? 0 }}"
          data-progress="{{ $stats['inProgress'] ?? 0 }}"
          data-completed="{{ $stats['completed'] ?? 0 }}"
          data-total="{{ $stats['total'] ?? 0 }}"
        ></canvas>
      </div>
    </div>
  </div>

  <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

  {{-- ส่วนที่ 2 --}}
  <div id="section-2" class="dash-anchor space-y-4">
    <div class="flex flex-col gap-1">
      <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] text-white" style="background: {{ $NAVY }}">2</span>
        <span>ส่วนที่ 2 • กราฟและการวิเคราะห์</span>
      </div>
      <h2 class="text-sm sm:text-base font-semibold text-slate-900">กราฟภาพรวมงานซ่อมตามหน่วยงาน ช่วงเวลา และหมวดหมู่ครุภัณฑ์</h2>
    </div>

    <div class="rounded-xl border {{ $line }} bg-white/80 px-4 py-4 sm:px-5 sm:py-4">
      <div>
        <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-700">
          กราฟที่ 2 — จำนวนงานซ่อมตามหน่วยงาน
        </div>
        <h3 class="mt-2 text-sm sm:text-base font-semibold text-slate-900">จำนวนงานซ่อมแยกตามหน่วยงาน (ทั้งหมด)</h3>
      </div>

      @if (count($deptLabels) && count($deptCounts))
        <div class="mt-4 h-72 w-full">
          <canvas id="deptBar" class="block w-full h-full"
            data-labels='@json($deptLabels, JSON_INVALID_UTF8_SUBSTITUTE)'
            data-values='@json($deptCounts, JSON_INVALID_UTF8_SUBSTITUTE)'></canvas>
        </div>
      @else
        <div class="mt-4 grid h-40 place-items-center text-slate-400 text-sm">ยังไม่มีข้อมูลสำหรับสร้างกราฟ</div>
      @endif
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
      <div class="lg:col-span-2 rounded-xl border {{ $line }} bg-white/80 px-4 py-4 sm:px-5 sm:py-4">
        <div>
          <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-700">
            กราฟที่ 3 — แนวโน้มจำนวนงานซ่อม
          </div>
          <h3 class="mt-2 text-sm sm:text-base font-semibold text-slate-900">แนวโน้มจำนวนงานซ่อมตามเดือนที่มีข้อมูล</h3>
        </div>

        @if (count($trendLabels) && count($trendCounts))
          <div class="mt-4 h-80 w-full">
            <canvas id="trendChart" class="block w-full h-full"
              data-labels='@json($trendLabels, JSON_INVALID_UTF8_SUBSTITUTE)'
              data-values='@json($trendCounts, JSON_INVALID_UTF8_SUBSTITUTE)'></canvas>
          </div>
        @else
          <div class="mt-4 grid h-40 place-items-center text-slate-400 text-sm">ยังไม่มีข้อมูลสำหรับสร้างกราฟ</div>
        @endif
      </div>

      <div class="rounded-xl border {{ $line }} bg-white/80 px-4 py-4 sm:px-5 sm:py-4">
        <div>
          <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-700">
            กราฟที่ 4 — สัดส่วนหมวดหมู่ครุภัณฑ์
          </div>
          <h3 class="mt-2 text-sm sm:text-base font-semibold text-slate-900">สัดส่วนหมวดหมู่ครุภัณฑ์ที่ถูกแจ้งซ่อม (ทั้งหมด)</h3>
        </div>

        @if (count($typeLabels) && count($typeCounts))
          <div class="mt-4 h-80 w-full">
            <canvas id="typePie" class="block w-full h-full"
              data-labels='@json($typeLabels, JSON_INVALID_UTF8_SUBSTITUTE)'
              data-values='@json($typeCounts, JSON_INVALID_UTF8_SUBSTITUTE)'></canvas>
          </div>
        @else
          <div class="mt-4 grid h-40 place-items-center text-slate-400 text-sm">ยังไม่มีข้อมูลสำหรับสร้างกราฟ</div>
        @endif
      </div>
    </div>
  </div>

  <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

  {{-- ส่วนที่ 3 --}}
  <div id="section-3" class="dash-anchor space-y-3">
    <div class="flex flex-col gap-1">
      <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] text-white" style="background: {{ $NAVY }}">3</span>
        <span>ส่วนที่ 3 • รายการแจ้งซ่อมล่าสุด</span>
      </div>
      <h2 class="text-sm sm:text-base font-semibold text-slate-900">รายการงานซ่อมล่าสุด (Recent Jobs)</h2>
    </div>

    <div class="overflow-x-auto rounded-xl border {{ $line }} bg-white/80">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-xs uppercase text-slate-500 border-b border-slate-100 bg-slate-50/80">
            <th class="py-2.5 pr-3 pl-3 text-left">วันที่แจ้ง</th>
            <th class="py-2.5 pr-3 text-left">ครุภัณฑ์</th>
            <th class="py-2.5 pr-3 text-left">ผู้แจ้ง</th>
            <th class="py-2.5 pr-3 text-left">สถานะ</th>
            <th class="py-2.5 pr-3 text-left">ผู้รับผิดชอบ</th>
            <th class="py-2.5 pr-3 text-left">วันที่เสร็จ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($recent as $t)
            @php
              $status = (string) $get($t,'status','');
              $pill   = $statusPillClass($status);
              $assetId   = $get($t,'asset_id','-');
              $assetName = $get($t,'asset_name') ?: $get($t,'asset.name','-');
              $reporter  = $get($t,'reporter')   ?: $get($t,'reporter.name','-');
              $tech      = $get($t,'technician') ?: $get($t,'technician.name','-');
              $reqAt     = $get($t,'request_date','-');
              $doneAt    = $get($t,'completed_at') ?: $get($t,'completed_date','-');
            @endphp
            <tr class="hover:bg-slate-50">
              <td class="py-2.5 pr-3 pl-3 text-slate-800">
                {{ is_string($reqAt) ? e($reqAt) : optional($reqAt)->format('Y-m-d H:i') }}
              </td>
              <td class="py-2.5 pr-3 text-slate-800">
                #{{ e((string)$assetId) }} — {{ e((string)$assetName) }}
              </td>
              <td class="py-2.5 pr-3 text-slate-800">{{ e((string)$reporter) }}</td>
              <td class="py-2.5 pr-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pill }}">
                  {{ ucfirst(str_replace('_',' ', $status)) }}
                </span>
              </td>
              <td class="py-2.5 pr-3 text-slate-800">{{ e((string)$tech) }}</td>
              <td class="py-2.5 pr-3 text-slate-800">
                {{ is_string($doneAt) ? e($doneAt) : (optional($doneAt)->format('Y-m-d H:i') ?? '-') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-10 text-center text-slate-400 text-sm">ยังไม่มีข้อมูลรายการแจ้งซ่อมล่าสุดให้แสดง</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Toast Container --}}
<div class="toast-overlay" aria-live="polite" aria-atomic="true"></div>

@php
  $lottieMap = $lottieMap ?? [
    'success' => asset('lottie/lock_with_green_tick.json'),
    'info'    => asset('lottie/lock_with_blue_info.json'),
    'warning' => asset('lottie/lock_with_yellow_alert.json'),
    'error'   => asset('lottie/lock_with_red_tick.json'),
  ];
@endphp

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>

<script>
(function(){
  /**
   * ✅ คำนวณความสูง Topbar/Header เพื่อปรับ CSS Variable (ทำงานร่วมกับ CSS ไฟล์ใหม่)
   */
  function getTopbarHeight() {
    const candidates = [
      '#topbar', '[data-topbar]', '.topbar', 'header[role="banner"]', 'header.fixed', 'header.sticky', 'header'
    ];
    for (const sel of candidates) {
      const el = document.querySelector(sel);
      if (!el) continue;
      const cs = window.getComputedStyle(el);
      const isFixedLike = (cs.position === 'fixed' || cs.position === 'sticky');
      const h = Math.round(el.getBoundingClientRect().height || 0);
      if (h >= 40 && isFixedLike) return h;
    }
    return 64; // default
  }

  function getDashStickyHeight(){
    const el = document.getElementById('dashStickyHeader');
    if (!el) return 180;
    return Math.round(el.getBoundingClientRect().height || 180);
  }

  function getDashStickyTop(){
    const v = getComputedStyle(document.documentElement).getPropertyValue('--dash-sticky-top').trim();
    const n = Number(String(v).replace('px','').trim());
    return Number.isFinite(n) && n >= 0 ? n : 8;
  }

  function setAnchorVars(){
    const topbarH = getTopbarHeight();
    const dashH   = getDashStickyHeight();

    // อัปเดต CSS Variable แบบ Real-time
    document.documentElement.style.setProperty('--topbar-h', topbarH + 'px');
    document.documentElement.style.setProperty('--dash-sticky-h', dashH + 'px');
  }

  function scrollToHashSmart(){
    const hash = window.location.hash;
    if (!hash) return;
    const target = document.querySelector(hash);
    if (!target) return;

    const topbarH    = getTopbarHeight();
    const dashH      = getDashStickyHeight();
    const dashTopGap = getDashStickyTop();
    const gap        = 12;

    const offset = topbarH + dashTopGap + dashH + gap;
    const y = window.scrollY + target.getBoundingClientRect().top - offset;
    window.scrollTo({ top: Math.max(0, y), behavior: 'instant' });
  }

  document.addEventListener('DOMContentLoaded', () => {
    setAnchorVars();
    requestAnimationFrame(() => setAnchorVars());
    setTimeout(() => {
      setAnchorVars();
      scrollToHashSmart();
    }, 120);
    window.addEventListener('resize', () => { setAnchorVars(); }, { passive:true });
  });

  // Toggle filter
  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('filterToggle');
    const panel = document.getElementById('filtersPanel');
    if (btn && panel) {
      if (!@json($filtersActive)) panel.classList.add('hidden');
      btn.addEventListener('click', () => {
        const hidden = panel.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
        requestAnimationFrame(() => setAnchorVars());
        setTimeout(() => setAnchorVars(), 120);
      });
    }
  });

  // --------- Charts (เหมือนเดิม) ---------
  function waitFor(condFn, {tries=50, interval=60} = {}) {
    return new Promise((resolve, reject) => {
      const t = setInterval(() => {
        if (condFn()) { clearInterval(t); resolve(true); }
        else if (--tries <= 0) { clearInterval(t); reject(new Error('waitFor timeout')); }
      }, interval);
    });
  }

  function parseData(el){
    try {
      const labels = JSON.parse(el.dataset.labels || '[]');
      const values = JSON.parse(el.dataset.values || '[]');
      return { labels, values };
    } catch(e) { return { labels: [], values: [] }; }
  }

  const NAVY = '#0b1f3b';
  const BLUE = '#2563eb';
  const SKY  = '#0ea5e9';
  const WHITE = '#ffffff';
  const palette = [NAVY, BLUE, SKY, '#1d4ed8', '#38bdf8', '#60a5fa', '#93c5fd', '#334155', '#0f172a'];
  const CHART_INSTANCES = {};

  function makeBarChart(el){
    const { labels, values } = parseData(el);
    if (!labels.length || !values.length) return;
    const id = el.id || 'deptBar';
    if (CHART_INSTANCES[id]) CHART_INSTANCES[id].destroy();
    const bars = ['rgba(11,31,59,0.55)','rgba(37,99,235,0.45)','rgba(14,165,233,0.38)','rgba(148,163,184,0.35)'];
    CHART_INSTANCES[id] = new Chart(el.getContext('2d'), {
      type: 'bar',
      data: { labels, datasets: [{ label: 'จำนวนงานซ่อม', data: values, backgroundColor: labels.map((_,i)=> bars[i % bars.length]), borderColor: 'rgba(148,163,184,0.45)', borderWidth: 1, borderRadius: 8, maxBarThickness: 32 }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display:false }, ticks: { color:'#475569' } }, y: { beginAtZero:true, ticks: { color:'#475569', precision:0 }, grid: { color:'rgba(148,163,184,0.18)' } } }, plugins: { legend: { display:false }, tooltip: { mode:'index', intersect:false } } }
    });
  }

  function makeTrendBarChart(el){
    const { labels, values } = parseData(el);
    if (!labels.length || !values.length) return;
    if (CHART_INSTANCES[el.id]) CHART_INSTANCES[el.id].destroy();
    const ctx = el.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, el.clientHeight || 260);
    gradient.addColorStop(0, 'rgba(37,99,235,0.92)');
    gradient.addColorStop(1, 'rgba(191,219,254,0.12)');
    CHART_INSTANCES[el.id] = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'จำนวนงานซ่อม / เดือน', data: values, backgroundColor: gradient, borderColor: 'rgba(29,78,216,0.9)', borderWidth: 1.3, borderRadius: 6, maxBarThickness: 40 }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false }, ticks: { color: '#64748b' } }, y: { beginAtZero: true, ticks: { color: '#64748b' }, grid: { color: 'rgba(148,163,184,0.22)' } } }, plugins: { legend: { display: true, position: 'top' } } }
    });
  }

  function makePieChart(el){
    const { labels, values } = parseData(el);
    if (!labels.length || !values.length) return;
    const id = el.id || 'typePie';
    if (CHART_INSTANCES[id]) CHART_INSTANCES[id].destroy();
    const total = values.reduce((a,b)=> a + (Number(b)||0), 0);
    CHART_INSTANCES[id] = new Chart(el.getContext('2d'), {
      type: 'doughnut',
      data: { labels, datasets: [{ data: values, backgroundColor: labels.map((_,i)=> palette[i % palette.length]), borderWidth: 2, borderColor: WHITE, borderRadius: 4 }] },
      options: { responsive:true, maintainAspectRatio:false, cutout: '60%', plugins:{ legend:{ position:'right', labels:{ color:'#334155', usePointStyle:true, boxWidth:10 } }, tooltip: { backgroundColor: 'rgba(15,23,42,0.92)', titleColor: '#e5e7eb', bodyColor: '#f9fafb', callbacks: { label: (ctx) => { const value = ctx.parsed; const percent = total ? (value / total * 100).toFixed(1) : 0; return ` ${ctx.label}: ${value} งาน (${percent}%)`; } } } } }
    });
  }

  function makeStatusTrend(el) {
    const pending = Number(el.dataset.pending || 0), progress = Number(el.dataset.progress || 0), completed = Number(el.dataset.completed || 0), total = Number(el.dataset.total || 0);
    const ctx = el.getContext('2d');
    if (CHART_INSTANCES['statusTrend']) CHART_INSTANCES['statusTrend'].destroy();
    CHART_INSTANCES['statusTrend'] = new Chart(ctx, {
      type: 'bar',
      data: { labels: ['Pending', 'In Progress', 'Completed', 'Total'], datasets: [{ label: 'จำนวนงานซ่อม', data: [pending, progress, completed, total], backgroundColor: ['rgba(14,165,233,0.35)', 'rgba(37,99,235,0.40)', 'rgba(11,31,59,0.55)', 'rgba(11,31,59,0.92)'], borderRadius: 8, borderSkipped: false, maxBarThickness: 36 }] },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { beginAtZero: true, ticks: { color: '#475569' }, grid: { color: 'rgba(148,163,184,0.25)' } }, y: { ticks: { color: '#475569' }, grid: { display: false } } }, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(15,23,42,0.92)', titleColor: '#e5e7eb', bodyColor: '#f9fafb', callbacks: { label: (ctx) => ` ${ctx.parsed.x} งาน` } } } }
    });
  }

  async function renderCharts(){
    try { await waitFor(()=> window.Chart?.registry); } catch(_) { return; }
    const deptBar = document.getElementById('deptBar'), trendChart = document.getElementById('trendChart'), typePie = document.getElementById('typePie'), statusTrend = document.getElementById('statusTrend');
    if (statusTrend) makeStatusTrend(statusTrend);
    if (deptBar) makeBarChart(deptBar);
    if (trendChart) makeTrendBarChart(trendChart);
    if (typePie) makePieChart(typePie);
  }

  // --------- Toast (เหมือนเดิม) ---------
  const LOTTIE = { success: @json($lottieMap['success'] ?? null), info: @json($lottieMap['info'] ?? null), warning: @json($lottieMap['warning'] ?? null), error: @json($lottieMap['error'] ?? null) };
  const SVG = {
    success: '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>',
    info:    '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
    warning: '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    error:   '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg>',
  };

  function lottieReady(){ if (window.customElements && window.customElements.whenDefined) return window.customElements.whenDefined('lottie-player').catch(()=>{}); return Promise.resolve(); }
  function makeIconEl(type){
    const wrap = document.createElement('div'); wrap.className = 'toast-icon'; const src = LOTTIE[type]; const canUseLottie = !!src && window.customElements && !!window.customElements.get('lottie-player');
    if (canUseLottie) { wrap.innerHTML = `<lottie-player src="${src}" style="width:var(--toast-icon);height:var(--toast-icon)" background="transparent" speed="1" autoplay></lottie-player>`; setTimeout(() => { const lp = wrap.querySelector('lottie-player'); if (!lp || !lp.clientWidth) wrap.innerHTML = `<div style="width:var(--toast-icon);height:var(--toast-icon);display:grid;place-items:center;color:${NAVY};">${SVG[type] ?? ''}</div>`; }, 800); } else { wrap.innerHTML = `<div style="width:var(--toast-icon);height:var(--toast-icon);display:grid;place-items:center;color:${NAVY};">${SVG[type] ?? ''}</div>`; }
    return wrap;
  }
  function ensurePos(position){ const overlay = document.querySelector('.toast-overlay'); overlay.innerHTML = ''; const posEl = document.createElement('div'); posEl.className = 'toast-pos ' + position; overlay.appendChild(posEl); return { posEl }; }
  function showToast({type='info', message='', position='tc', timeout=3200, size='lg'} = {}){
    const allowed = ['tr','tl','br','bl','center','tc','bc']; if (!allowed.includes(position)) position = 'tc'; timeout = Number(timeout) || 3200;
    const { posEl } = ensurePos(position); const card = document.createElement('section'); const sizeClass = (['sm','md','lg'].includes(size) ? `toast--${size}` : 'toast--lg'); card.className = `toast-card ${sizeClass} toast-${type}`; card.setAttribute('role','status');
    const icon = makeIconEl(type); const msg = document.createElement('div'); msg.className = 'toast-msg'; msg.textContent = message ?? ''; const btn = document.createElement('button'); btn.className = 'toast-close'; btn.setAttribute('aria-label','Close'); btn.innerHTML = '&times;';
    const bar = document.createElement('div'); bar.className = 'toast-bar'; const fill = document.createElement('div'); fill.className = `toast-fill fill-${type}`; bar.appendChild(fill); card.append(icon, msg, btn, bar); posEl.appendChild(card);
    requestAnimationFrame(() => { card.classList.add('show'); fill.style.transitionDuration = timeout + 'ms'; requestAnimationFrame(() => { fill.style.width = '100%'; }); });
    let closed = false; function close() { if (closed) return; closed = true; card.classList.remove('show'); setTimeout(() => { card.remove(); }, 220); }
    btn.addEventListener('click', close); const autoTimer = setTimeout(close, timeout); card.addEventListener('mouseenter', () => { clearTimeout(autoTimer); });
  }

  window.showToast = showToast; window.AppToast = { show: showToast };

  document.addEventListener('DOMContentLoaded', () => {
    renderCharts();
    const hasMessage = @json((bool) $message);
    if (hasMessage) { lottieReady().finally(() => { showToast({ type: @json($type ?? 'info'), message: @json($message ?? ''), position: @json($position ?? 'tc'), timeout: @json($timeout ?? 3200), size: @json($size ?? 'lg'), }); }); }
  });
})();
</script>

@endsection

@section('footer')
  <div class="text-xs text-slate-500">
    © {{ date('Y') }} {{ config('app.name','Asset Repair') }} — Asset Repair Dashboard
  </div>
@endsection
