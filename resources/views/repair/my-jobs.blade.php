{{-- resources/views/repairs/my_jobs.blade.php --}}
@extends('layouts.app')
@section('title','My Jobs')

@section('content')
@php
  use Illuminate\Support\Str;

  $q      = $q ?? request('q');
  $status = $status ?? request('status');
  $filter = $filter ?? (request('filter') ?: 'all');
  $tech   = $tech ?? request('tech');
  $resp   = $resp ?? request('resp');

  $filterLabels = [
    'my'        => 'งานของฉัน',
    'available' => 'งานว่าง',
    'all'       => 'ทั้งหมด',
  ];

  /**
   * ✅ สถานะใบงานจริง (MR.status) เท่านั้น — คำไทยตามสเปค
   */
  $statusLabel = fn(?string $s) => [
    'pending'       => 'รอรับทราบ',
    'acknowledged'  => 'รับทราบแล้ว',
    'accepted'      => 'รับเรื่องแล้ว',
    'in_progress'   => 'กำลังดำเนินการ',
    'on_hold'       => 'พักไว้ชั่วคราว',
    'resolved'      => 'แก้ไขเสร็จสิ้น',
    'closed'        => 'ปิดงาน',
    'cancelled'     => 'ยกเลิกซ่อม',
  ][strtolower((string)$s)] ?? 'ไม่ระบุ';

  /**
   * (ยังคงไว้ได้) สถานะของฉัน (response_status) — ใช้เฉพาะ filter/ข้อมูลประกอบ
   * แต่ห้ามเอามาทับสถานะหลักบนการ์ด
   */
  $respLabel = fn(?string $s) => [
    'pending'      => 'ยังไม่ตอบรับ',
    'accepted'     => 'รับเรื่อง',
    'rejected'     => 'ไม่รับเรื่อง',
    'acknowledged' => 'รับทราบแล้ว',
  ][strtolower((string)$s)] ?? 'ไม่ระบุ';

  $priorityLabel = fn(?string $p) => [
    'low'    => 'ต่ำ',
    'medium' => 'ปานกลาง',
    'high'   => 'สูง',
    'urgent' => 'เร่งด่วน',
  ][strtolower((string)$p)] ?? 'ไม่ระบุ';

  // ✅ dot ใช้ MR.status เท่านั้น
  $statusDot = fn(?string $s) => match(strtolower((string)$s)) {
    'pending'       => 'bg-amber-500',
    'acknowledged'  => 'bg-sky-500',
    'accepted'      => 'bg-indigo-500',
    'in_progress'   => 'bg-sky-500',
    'on_hold'       => 'bg-slate-400',
    'resolved'      => 'bg-emerald-500',
    'closed'        => 'bg-zinc-400',
    'cancelled'     => 'bg-rose-500',
    default         => 'bg-slate-400',
  };

  $priorityClass = fn(?string $p) => match(strtolower((string)$p)) {
    'urgent' => 'text-rose-700 bg-rose-50 border-rose-200',
    'high'   => 'text-amber-700 bg-amber-50 border-amber-200',
    'medium' => 'text-blue-700 bg-blue-50 border-blue-200',
    'low'    => 'text-slate-600 bg-slate-50 border-slate-200',
    default  => 'text-gray-500 bg-gray-50 border-gray-200',
  };

  $hasActiveFilter =
    (($q ?? '') !== '') ||
    (($status ?? '') !== '') ||
    (($tech ?? '') !== '') ||
    (($filter ?? 'all') !== 'all') ||
    (($resp ?? '') !== '');

  $activeTech = isset($tech) && isset($team) ? $team->firstWhere('id', (int)$tech) : null;

  $statPending    = (int)($stats['pending'] ?? 0);
  $statInProgress = (int)($stats['in_progress'] ?? 0);
  $statCompleted  = (int)($stats['completed'] ?? 0);

  $getIp = fn($r) => $r->client_ip ?? $r->ip_address ?? $r->ip ?? null;
  $getDept = fn($r) => $r->department->name ?? ($r->department->name_th ?? ($r->department->title ?? null));

  $getWorkOrderNo = function($r) {
    return $r->work_order_no
        ?? ($r->workOrder->order_no ?? null)
        ?? ($r->job_no ?? null)
        ?? null;
  };

  // ✅ ตรวจ admin/dev
  $u = auth()->user();
  $uRole = strtolower((string)($u?->role ?? ''));
  $isAdmin =
    (bool)($u?->is_admin ?? false)
    || in_array($uRole, ['admin','developer'], true)
    || (method_exists($u, 'hasRole') && ($u->hasRole('admin') || $u->hasRole('developer')));
@endphp

<div class="pt-6 md:pt-8 lg:pt-10"></div>

<div class="w-full flex flex-col pb-14 font-sans text-slate-900">

  <div class="sticky top-[6rem] z-20 bg-white/90 backdrop-blur border-b border-slate-200 transition-all">
    <div class="px-4 md:px-6 lg:px-8 py-4">

      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-[17px] font-semibold text-slate-900">My Jobs</h1>
          <p class="text-[13px] text-slate-600">
            รายการงานซ่อมบำรุงที่ต้องจัดการ
            @if($activeTech)
              <span class="text-slate-500">• ของ {{ $activeTech->name }}</span>
            @endif
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px]">
           <div class="flex items-center gap-2">
             <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
             <span class="text-slate-600">รอรับทราบ:</span>
             <span id="stat-pending" class="font-bold text-slate-900">{{ $statPending }}</span>
           </div>
           <div class="flex items-center gap-2">
             <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
             <span class="text-slate-600">กำลังทำ:</span>
             <span id="stat-in-progress" class="font-bold text-slate-900">{{ $statInProgress }}</span>
           </div>
           <div class="flex items-center gap-2">
             <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
             <span class="text-slate-600">เสร็จ:</span>
             <span id="stat-completed" class="font-bold text-slate-900">{{ $statCompleted }}</span>
           </div>

           <div class="flex items-center gap-2 pl-3 border-l border-slate-200 ml-1">
             <div class="relative w-8 h-8">
               <div id="donut" class="w-full h-full rounded-full" style="background: conic-gradient(#e2e8f0 0deg 360deg);"></div>
               <div class="absolute inset-0 m-auto w-5 h-5 rounded-full bg-white"></div>
             </div>
             <span id="donutPct" class="font-bold text-[11px] text-slate-700">0%</span>
           </div>
        </div>
      </div>

      <form method="GET"
            action="{{ route('repairs.my_jobs') }}"
            class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end"
            onsubmit="showLoader()">

        <div class="md:col-span-5 min-w-0">
          <label for="q" class="mb-1 block text-[12px] text-slate-600">คำค้นหา</label>
          <div class="relative">
            <input id="q" type="text" name="q" value="{{ $q }}"
                   placeholder="ค้นหาเลขใบงาน, เรื่อง, หรือสถานที่..."
                   class="w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 py-2 text-[13px] placeholder:text-slate-400
                          focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </span>
          </div>
        </div>

        <div class="md:col-span-2">
          <label for="filter" class="mb-1 block text-[12px] text-slate-600">ช่วงงาน</label>
          <select id="filter" name="filter"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
            @foreach($filterLabels as $key => $label)
              <option value="{{ $key }}" @selected($filter===$key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-3">
          <label for="status" class="mb-1 block text-[12px] text-slate-600">สถานะใบงาน</label>
          <select id="status" name="status"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
            <option value="">ทุกสถานะ</option>
            @foreach(['pending','acknowledged','accepted','in_progress','on_hold','resolved','closed','cancelled'] as $s)
              <option value="{{ $s }}" @selected($status===$s)>{{ $statusLabel($s) }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-2">
          <label for="resp" class="mb-1 block text-[12px] text-slate-600">การตอบรับของฉัน</label>
          <select id="resp" name="resp"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
            <option value="">ทั้งหมด</option>
            @foreach(['pending','accepted','acknowledged','rejected'] as $s)
              <option value="{{ $s }}" @selected(($resp ?? '')===$s)>{{ $respLabel($s) }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-12 flex items-end justify-end gap-2">
          @if($hasActiveFilter)
            <a href="{{ route('repairs.my_jobs') }}"
               onclick="showLoader()"
               class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600
                      hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/30 focus:ring-offset-1 transition-transform hover:scale-105"
               title="ล้างตัวกรอง" aria-label="ล้างตัวกรอง">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </a>
          @endif

          <button type="submit"
                  class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#0F2D5C] text-white
                         hover:bg-[#0F2D5C]/90 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/45 focus:ring-offset-1 transition-transform hover:scale-105 active:scale-95"
                  title="ค้นหา" aria-label="ค้นหา">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </button>
        </div>

        @if($tech)
          <input type="hidden" name="tech" value="{{ $tech }}">
        @endif
      </form>
    </div>
  </div>

  <div class="px-4 md:px-6 lg:px-8 py-2 border-b border-slate-200 bg-slate-50/30">
    <div class="flex items-center justify-between">
      <div class="text-[13px] font-semibold text-slate-800">รายการงาน</div>
      <div class="text-[12px] text-slate-500">ทั้งหมด {{ $list->total() }} รายการ</div>
    </div>
  </div>

  <div class="w-full px-4 md:px-6 lg:px-8 relative z-0 mt-6">
    <div class="mj-container mx-auto space-y-4">

      @forelse($list as $r)
        @php
          $ticketNo = $r->request_no ?? $r->job_no ?? $r->id;
          $workOrderNo = $getWorkOrderNo($r);

          $assetName = $r->asset->name ?? null;
          $assetCode = $r->asset->asset_code ?? null;
          $deptName = $getDept($r);
          $location = $r->location_text ?? null;

          $reporterName  = $r->reporter_name ?? $r->reporter?->name ?? '-';
          $reporterPhone = $r->reporter_phone ?? null;
          $ip = $getIp($r);

          $createdAtText = optional($r->created_at)->format('d/m/Y H:i');

          // ✅ สถานะใบงานจริง (MR.status)
          $mrStatus = strtolower((string)($r->status ?? ''));
          $mrStatusText = $statusLabel($mrStatus);

          $priorityText = $priorityLabel($r->priority ?? null);

          // (ยังคงคำนวณไว้ได้สำหรับ filter/ข้อมูลประกอบ แต่ไม่ใช้ทับสถานะหลัก)
          $myResp = strtolower((string)($r->my_response_status ?? 'pending'));
          $myRespText = $respLabel($myResp);
          $myRespAt = $r->my_responded_at ? \Carbon\Carbon::parse($r->my_responded_at)->format('d/m/Y H:i') : null;

          // ✅ ปุ่มรับทราบ: เฉพาะ MR.status = pending เท่านั้น
          $canAcknowledge = ($mrStatus === 'pending');

          // ✅ แสดงสถานะบนการ์ด “อันเดียว” จาก MR.status เท่านั้น
          $displayText = $mrStatusText;
          $displayDot  = $statusDot($mrStatus);
        @endphp

        <div class="mj-card group">
          <div class="mj-card__header">
            <div class="flex items-center gap-3 overflow-hidden min-w-0">
              <span class="mj-ticket-plain">#{{ $ticketNo }}</span>
              @if($workOrderNo)
                <span class="mj-wo">WO# {{ $workOrderNo }}</span>
              @endif
              <h3 class="mj-title truncate" title="{{ $r->title }}">{{ $r->title }}</h3>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              {{-- ✅ เหลือ “สถานะเดียว” จาก MR.status --}}
              <span class="mj-pill">
                <span class="relative inline-flex h-3 w-3">
                  @if($mrStatus === 'pending')
                    <span class="mj-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-60"></span>
                  @endif
                  <span class="relative inline-flex h-3 w-3 rounded-full {{ $displayDot }}"></span>
                </span>
                {{ $displayText }}
              </span>

              <span class="mj-pill border {{ $priorityClass($r->priority) }} font-bold uppercase">
                {{ $priorityText }}
              </span>
            </div>
          </div>

          <div class="mj-card__sub">
            <div class="mj-sub-item">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span class="font-bold">วันแจ้ง:</span> {{ $createdAtText }}
            </div>

            <div class="mj-sub-item border-l border-slate-200 pl-4">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
              </svg>
              <span class="font-bold">หน่วยงาน:</span> {{ $deptName ?? '-' }}
            </div>
          </div>

          <div class="mj-problem-wrap">
            <h4 class="mj-cell__head mj-problem-head text-rose-700">รายละเอียดปัญหา</h4>
            @if($r->description)
              <div class="mj-problem" title="{{ $r->description }}">{{ $r->description }}</div>
            @else
              <div class="mj-problem mj-problem--empty">- ไม่มีรายละเอียด -</div>
            @endif
          </div>

          <div class="mj-card__grid">
            <div class="mj-cell">
              <h4 class="mj-cell__head text-slate-400">สถานที่ / ทรัพย์สิน</h4>
              <div class="mj-kv">
                <div class="truncate">
                  <span class="mj-k">สถานที่:</span>
                  <span class="mj-v" title="{{ $location }}">{{ $location ?? '-' }}</span>
                </div>
                <div class="truncate mt-1">
                  <span class="mj-k">ทรัพย์สิน:</span>
                  <span class="mj-v">
                    @if($assetCode)
                      <span class="font-mono text-xs font-bold text-[#0F2D5C]">{{ $assetCode }}</span> <span class="text-slate-300">—</span>
                    @endif
                    {{ $assetName ?? '-' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="mj-cell">
              <h4 class="mj-cell__head text-slate-400">ผู้แจ้ง / ติดต่อ</h4>
              <div class="mj-kv">
                <div class="truncate"><span class="mj-k">ชื่อ:</span> <span class="mj-v">{{ $reporterName }}</span></div>
                <div class="truncate mt-1"><span class="mj-k">โทร:</span> <span class="mj-v">{{ $reporterPhone ?? '-' }}</span></div>
                <div class="truncate mt-2 text-[10px] text-slate-400 font-mono">IP: {{ $ip ?? '-' }}</div>
              </div>
            </div>
          </div>

          <div class="mj-card__footer">
            <div class="mj-footer-left">
              @if($canAcknowledge)
                <button type="button"
                        class="mj-ack-btn"
                        onclick="submitAcknowledge('{{ $r->id }}', '{{ $ticketNo }}')"
                        title="รับทราบ">
                  <span class="mj-ack-ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path d="M9 12l2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  รับทราบ
                </button>
              @endif
            </div>

            <div class="mj-footer-right">
              <a href="{{ route('maintenance.requests.show', $r) }}" onclick="showLoader()" class="mj-detail-btn" title="ดูรายละเอียด">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <span class="hidden md:inline">รายละเอียด</span>
              </a>
            </div>
          </div>
        </div>

        {{-- ✅ รับทราบยิงไป acknowledge เท่านั้น --}}
        <form id="ackForm-{{ $r->id }}"
              method="POST"
              action="{{ route('maintenance.requests.acknowledge', $r) }}"
              class="hidden">
          @csrf
        </form>

      @empty
        <div class="bg-white border border-slate-200 rounded-md p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="mt-4 text-sm text-slate-500">ไม่พบรายการงานตามเงื่อนไขที่เลือก</p>
        </div>
      @endforelse

    </div>

    @if($list->hasPages())
      <div class="mt-8 mb-10">
        {{ $list->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>

<div id="loaderOverlay" class="loader-overlay"><div class="loader-spinner"></div></div>
@endsection

@push('styles')
<style>
  .loader-overlay{position:fixed;inset:0;background:rgba(255,255,255,.65);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:99999;visibility:hidden;opacity:0;transition:opacity .2s,visibility .2s}
  .loader-overlay.show{visibility:visible;opacity:1}
  .loader-spinner{width:48px;height:48px;border:5px solid #0F2D5C;border-top-color:transparent;border-radius:50%;animation:spin .8s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}

  .mj-container{max-width:1200px;}
  @media (max-width:1280px){.mj-container{max-width:1080px;}}
  @media (max-width:1024px){.mj-container{max-width:100%;}}

  .mj-card{background:#fff;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,.05);transition:box-shadow .15s ease,transform .15s ease;}
  .mj-card:hover{box-shadow:0 10px 15px -3px rgba(0,0,0,.08);transform:translateY(-2px);}

  .mj-card__header{padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:10px;border-bottom:1px solid #f1f5f9;}
  .mj-card__sub{padding:8px 14px;display:flex;flex-wrap:wrap;gap:12px;border-bottom:1px solid #f8fafc;color:#475569;font-size:13px;background:#fcfcfd;}
  .mj-sub-item{display:flex;align-items:center;gap:6px}

  .mj-title{font-weight:800;font-size:15px;color:#1e293b}
  .mj-ticket-plain{font-family:ui-monospace,SFMono-Regular,monospace;font-weight:900;font-size:13px;color:#334155;white-space:nowrap;}
  .mj-wo{background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;padding:1px 8px;font-size:11px;font-family:ui-monospace,monospace;font-weight:700;border-radius:4px;white-space:nowrap;}

  .mj-pill{height:28px;padding:0 10px;border-radius:4px;display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;background:#fff;font-size:12px;font-weight:800;color:#334155;box-shadow:0 2px 4px rgba(0,0,0,.03);white-space:nowrap;}
  @keyframes mjPing{0%{transform:scale(1);opacity:.6}80%{transform:scale(2.5);opacity:0}100%{transform:scale(2.5);opacity:0}}
  .mj-ping{animation:mjPing 1.5s cubic-bezier(0,0,0.2,1) infinite}

  .mj-detail-btn{height:34px;padding:0 12px;border-radius:4px;display:inline-flex;align-items:center;gap:6px;border:1px solid #cbd5e1;background:#fff;color:#475569;font-weight:700;font-size:13px;transition:all .15s ease;box-shadow:0 2px 4px rgba(0,0,0,.03);text-decoration:none;}
  .mj-detail-btn:hover{background:#f8fafc;border-color:#94a3b8;color:#1e293b;transform:translateY(-1px);box-shadow:0 4px 6px rgba(0,0,0,.05);}
  .mj-detail-btn:active{transform:translateY(0);box-shadow:none;background:#f1f5f9;}

  .mj-problem-wrap{padding:10px 14px 8px 14px;}
  .mj-problem-head{margin-bottom:4px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#64748b;}
  .mj-problem{border:1px solid #fda4af;border-left:4px solid #e11d48;background:#fff1f2;padding:8px 10px;border-radius:4px;color:#9f1239;font-size:13px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .mj-problem--empty{color:#9f1239;font-style:italic;background:#fff1f2;padding:6px 10px;border-radius:4px;font-size:12px;}

  .mj-card__grid{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid #f1f5f9;}
  @media (max-width:1024px){.mj-card__grid{grid-template-columns:1fr}}
  .mj-cell{padding:10px 14px;border-right:1px solid #f1f5f9}
  .mj-cell:last-child{border-right:none}
  @media (max-width:1024px){.mj-cell{border-right:none;border-top:1px solid #f1f5f9}.mj-cell:first-child{border-top:none}}
  .mj-cell__head{font-size:11px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;margin-bottom:4px;color:#64748b;}
  .mj-kv{font-size:13px;color:#1e293b}
  .mj-k{color:#64748b;font-size:12px;font-weight:700;margin-right:4px}
  .mj-v{font-weight:700}

  .mj-card__footer{padding:10px 14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:10px;background:#fcfcfd;}
  .mj-footer-left{display:flex;align-items:center;gap:10px;min-width:0;flex-wrap:wrap}
  .mj-footer-right{display:flex;align-items:center;gap:10px;flex-shrink:0}

  .mj-ack-btn{height:34px;padding:0 12px;border-radius:4px;display:inline-flex;align-items:center;gap:8px;font-weight:900;font-size:13px;color:#0b4a6f;background:#e0f2fe;border:1px solid #bae6fd;box-shadow:0 2px 4px rgba(0,0,0,.04);transition:all .2s ease;}
  .mj-ack-btn:hover{background:#bae6fd;border-color:#7dd3fc;transform:translateY(-1px);}
  .mj-ack-btn:active{transform:translateY(0);}
  .mj-ack-ic{width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;}
  .mj-ack-ic svg{width:18px;height:18px;stroke-width:2.5;}
  .mj-ack-badge{height:34px;padding:0 12px;border-radius:4px;display:inline-flex;align-items:center;gap:8px;font-weight:900;font-size:13px;color:#075985;background:#e0f2fe;border:1px solid #bae6fd;}
</style>
@endpush

@push('scripts')
<script>
  function showLoader(){ document.getElementById('loaderOverlay')?.classList.add('show') }
  function hideLoader(){ document.getElementById('loaderOverlay')?.classList.remove('show') }

  function submitAcknowledge(id, ticketNo) {
    const ok = confirm(`ยืนยัน “รับทราบ” งาน #${ticketNo} ?`);
    if(!ok) return;
    showLoader();
    const form = document.getElementById(`ackForm-${id}`);
    if(form) form.submit();
  }

  function renderDonut(){
    const pending = parseInt((document.getElementById('stat-pending')?.textContent||'0').trim(), 10) || 0;
    const inprog  = parseInt((document.getElementById('stat-in-progress')?.textContent||'0').trim(), 10) || 0;
    const comp    = parseInt((document.getElementById('stat-completed')?.textContent||'0').trim(), 10) || 0;
    const total = pending + inprog + comp;
    const donut = document.getElementById('donut');
    const pctEl = document.getElementById('donutPct');
    if(!donut || !pctEl) return;
    const completedPct = total > 0 ? Math.round((comp / total) * 100) : 0;
    pctEl.textContent = `${completedPct}%`;
    const degPending = total > 0 ? (pending / total) * 360 : 0;
    const degInprog  = total > 0 ? (inprog  / total) * 360 : 0;
    const degComp    = total > 0 ? (comp    / total) * 360 : 0;
    const a0 = 0;
    const a1 = a0 + degPending;
    const a2 = a1 + degInprog;
    const a3 = a2 + degComp;
    donut.style.background = `conic-gradient(#f59e0b ${a0}deg ${a1}deg,#0ea5e9 ${a1}deg ${a2}deg,#10b981 ${a2}deg ${a3}deg,#e2e8f0 ${a3}deg 360deg)`;
  }

  document.addEventListener('DOMContentLoaded', () => { hideLoader(); renderDonut(); });
</script>
@endpush
