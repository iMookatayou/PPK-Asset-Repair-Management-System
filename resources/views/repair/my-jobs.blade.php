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

  $myJobsRoute = 'repairs.my_jobs';

  $filterLabels = [
    'my'        => 'งานของฉัน',
    'available' => 'งานว่าง',
    'all'       => 'ทั้งหมด',
  ];

  $statusLabel = fn(?string $s) => [
    'pending'       => 'รอรับทราบ',
    'acknowledged'  => 'รับทราบแล้ว',
    'accepted'      => 'รับเรื่องแล้ว',
    'in_progress'   => 'กำลังดำเนินการ',
    'on_hold'       => 'พักไว้ชั่วคราว',
    'resolved'      => 'แก้ไขเสร็จสิ้น',
    'closed'        => 'ปิดงาน',
    'cancelled'     => 'ยกเลิกซ่อม',
    'rejected'      => 'ไม่รับเรื่อง',
  ][strtolower((string)$s)] ?? 'ไม่ระบุ';

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

  $statusDot = fn(?string $s) => match(strtolower((string)$s)) {
    'pending'       => 'bg-amber-500',
    'acknowledged'  => 'bg-sky-500',
    'accepted'      => 'bg-indigo-500',
    'in_progress'   => 'bg-sky-500',
    'on_hold'       => 'bg-slate-400',
    'resolved'      => 'bg-emerald-500',
    'closed'        => 'bg-zinc-400',
    'cancelled'     => 'bg-rose-500',
    'rejected'      => 'bg-rose-600',
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

  $u = auth()->user();
  $uRole = strtolower((string)($u?->role ?? ''));
  $isAdmin =
    (bool)($u?->is_admin ?? false)
    || in_array($uRole, ['admin','developer'], true)
    || (method_exists($u, 'hasRole') && ($u->hasRole('admin') || $u->hasRole('developer')));

  $soundDefaultUrl = asset('sounds/new-request.mp3');
  $soundUrgentUrl  = asset('sounds/urgent.mp3');
@endphp

<div class="w-full flex flex-col pb-6 md:pb-8 font-sans text-slate-900"
     id="myJobsRealtime"
     data-sound-default="{{ $soundDefaultUrl }}"
     data-sound-urgent="{{ $soundUrgentUrl }}">

  <div class="sticky top-16 z-20 bg-white border-b border-slate-200">
    <div class="px-4 md:px-6 lg:px-8 py-4">

      <div class="flex flex-wrap items-start justify-between gap-4">

        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-[32px] text-[#0F2D5C] mt-0.5" aria-hidden="true">engineering</span>
          <div>
            <h1 class="text-[17px] font-semibold text-slate-900">My Jobs</h1>
            <p class="text-[13px] text-slate-600">
              รายการงานซ่อมบำรุงที่ต้องจัดการ
              @if($activeTech)
                <span class="text-slate-500">• ของ {{ $activeTech->name }}</span>
              @endif
            </p>
          </div>
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

           <div class="flex items-center gap-2 pl-3 border-l border-slate-200 ml-1">
             <button type="button"
                     id="notifyToggleBtn"
                     class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600
                            hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/30 focus:ring-offset-1
                            transition-transform hover:scale-105 active:scale-95"
                     title="แจ้งเตือน (กดเพื่อเปิดเสียง)"
                     aria-label="แจ้งเตือน">
               <span class="material-symbols-outlined ms text-[20px]" aria-hidden="true">notifications</span>
               <span id="notifyStatusDot"
                     class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-slate-300 ring-2 ring-white"></span>
             </button>
             <span id="notifyText" class="text-[12px] text-slate-500 select-none">ปิดเสียง</span>
           </div>
        </div>
      </div>

      <form method="GET"
            action="{{ route($myJobsRoute) }}"
            class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end"
            onsubmit="showLoader()">

        <div class="md:col-span-4 lg:col-span-3 min-w-0">
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

        <div class="md:col-span-4 lg:col-span-2">
          <label for="filter" class="mb-1 block text-[12px] text-slate-600">ช่วงงาน</label>
          <select id="filter" name="filter"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
            @foreach($filterLabels as $key => $label)
              <option value="{{ $key }}" @selected($filter===$key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-4 lg:col-span-2">
          <label for="status" class="mb-1 block text-[12px] text-slate-600">สถานะใบงาน</label>
          <select id="status" name="status"
                  class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                         focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
            <option value="">ทุกสถานะ</option>
            @foreach(['pending','acknowledged','accepted','in_progress','on_hold','resolved','closed','cancelled','rejected'] as $s)
              <option value="{{ $s }}" @selected($status===$s)>{{ $statusLabel($s) }}</option>
            @endforeach
          </select>
        </div>

        <div class="md:col-span-6 lg:col-span-2">
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

        <div class="md:col-span-6 lg:col-span-3 flex items-end justify-end gap-2 mt-2 lg:mt-0">
          @if($hasActiveFilter)
            <a href="{{ route($myJobsRoute) }}"
               onclick="showLoader()"
               class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600
                      hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/30 transition-transform hover:scale-105"
               title="ล้างตัวกรอง">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </a>
          @endif

          <button type="submit"
                  class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#0F2D5C] text-white
                         hover:bg-[#0F2D5C]/90 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/45 transition-transform hover:scale-105 active:scale-95"
                  title="ค้นหา">
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
    <div class="mx-auto space-y-4 max-w-6xl">

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

          $mrStatus = strtolower((string)($r->status ?? ''));
          $mrStatusText = $statusLabel($mrStatus);

          $priorityText = $priorityLabel($r->priority ?? null);

          $displayText = $mrStatusText;
          $displayDot  = $statusDot($mrStatus);
        @endphp

        <div class="bg-white border border-slate-200 border-l-[4px] border-l-[#0F2D5C] rounded-none shadow-sm relative group hover:shadow-md transition-shadow">

          <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-start justify-between gap-4 bg-slate-50/50">
            <div class="flex flex-col gap-1.5 overflow-hidden min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-mono text-[13px] font-bold text-slate-600 bg-slate-200 px-2 py-0.5 rounded-none border border-slate-300">#{{ $ticketNo }}</span>
                @if($workOrderNo)
                  <span class="font-mono text-[13px] font-bold text-[#0F2D5C] bg-blue-50 px-2 py-0.5 rounded-none border border-blue-200">WO# {{ $workOrderNo }}</span>
                @endif
              </div>
              <h3 class="text-[16px] font-bold text-slate-800 truncate" title="{{ $r->title }}">{{ $r->title }}</h3>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <span class="inline-flex items-center gap-2 text-[13px] font-medium text-slate-700">
                <span class="relative inline-flex h-2.5 w-2.5">
                  @if($mrStatus === 'pending')
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-60"></span>
                  @endif
                  <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $displayDot }}"></span>
                </span>
                {{ $displayText }}
              </span>

              <span class="inline-flex items-center px-3 py-1 rounded border {{ $priorityClass($r->priority) }} text-[12px] font-bold tracking-wide">
                {{ $priorityText }}
              </span>
            </div>
          </div>

          <div class="px-5 py-2 border-b border-slate-100 bg-white flex flex-wrap gap-x-6 gap-y-2 text-[13px] text-slate-600">
            <div class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-slate-400">calendar_month</span>
              <span class="font-bold text-slate-700">วันแจ้ง:</span> {{ $createdAtText }}
            </div>
            <div class="flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-slate-400">domain</span>
              <span class="font-bold text-slate-700">หน่วยงาน:</span> {{ $deptName ?? '-' }}
            </div>
          </div>

          <div class="px-5 py-3 border-b border-slate-100">
            <div class="text-[12px] font-bold text-rose-700 mb-1">รายละเอียดปัญหา</div>
            @if($r->description)
              <div class="text-[14px] text-slate-700 bg-white border border-slate-200 p-2.5 rounded-none">
                {{ $r->description }}
              </div>
            @else
              <div class="text-[13px] text-slate-400 italic">- ไม่มีรายละเอียด -</div>
            @endif
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 bg-white">
            <div class="p-5 border-b md:border-b-0 md:border-r border-slate-100">
              <h4 class="text-[12px] font-bold text-slate-400 mb-2 uppercase tracking-wide">สถานที่ / ทรัพย์สิน</h4>
              <div class="text-[13.5px] text-slate-700 space-y-1">
                <div><span class="font-semibold text-slate-500 w-16 inline-block">สถานที่:</span> <span title="{{ $location }}">{{ $location ?? '-' }}</span></div>
                <div class="pt-1">
                  <span class="font-semibold text-slate-500 w-16 inline-block">ทรัพย์สิน:</span>
                  @if($assetCode)
                    <span class="font-mono font-bold text-[#0F2D5C]">{{ $assetCode }}</span> <span class="text-slate-300">—</span>
                  @endif
                  {{ $assetName ?? '-' }}
                </div>
              </div>
            </div>
            <div class="p-5">
              <h4 class="text-[12px] font-bold text-slate-400 mb-2 uppercase tracking-wide">ผู้แจ้ง / ติดต่อ</h4>
              <div class="text-[13.5px] text-slate-700 space-y-1">
                <div><span class="font-semibold text-slate-500 w-12 inline-block">ชื่อ:</span> {{ $reporterName }}</div>
                <div><span class="font-semibold text-slate-500 w-12 inline-block">โทร:</span> {{ $reporterPhone ?? '-' }}</div>
                <div class="text-[11px] text-slate-400 font-mono mt-1 pt-1.5 border-t border-slate-50 inline-block">IP: {{ $ip ?? '-' }}</div>
              </div>
            </div>
          </div>

          <div class="px-5 py-3 bg-slate-50 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200">
            <div class="flex flex-wrap items-center gap-2">
              @can('acknowledge', $r)
                @if($mrStatus === 'pending')
                  <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0F2D5C] text-white text-[13px] font-bold border border-[#0A2045] rounded-none hover:bg-[#0A2045] transition-colors shadow-sm"
                          onclick="submitAcknowledge('{{ $r->id }}', '{{ $ticketNo }}')" title="รับทราบ">
                    <span class="material-symbols-outlined text-[18px]">approval_delegation</span>
                    รับทราบ
                  </button>
                @endif
              @endcan

              @can('accept', $r)
                @if($mrStatus === 'acknowledged')
                  <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0F2D5C] text-white text-[13px] font-bold border border-[#0A2045] rounded-none hover:bg-[#0A2045] transition-colors shadow-sm"
                          onclick="submitAccept('{{ $r->id }}', '{{ $ticketNo }}')" title="รับเรื่อง">
                    <span class="material-symbols-outlined text-[18px]">check</span>
                    รับเรื่อง
                  </button>
                @endif
              @endcan

              @can('reject', $r)
                @if($mrStatus === 'acknowledged')
                  <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 text-white text-[13px] font-bold border border-rose-700 rounded-none hover:bg-rose-700 transition-colors shadow-sm"
                          onclick="submitReject('{{ $r->id }}', '{{ $ticketNo }}')" title="ไม่รับเรื่อง">
                    <span class="material-symbols-outlined text-[18px]">block</span>
                    ไม่รับเรื่อง
                  </button>
                @endif
              @endcan
            </div>

            <div>
              <a href="{{ route('maintenance.requests.show', $r) }}"
                 onclick="showLoader()"
                 class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-slate-700 text-[13px] font-bold border border-slate-300 rounded-none hover:bg-slate-100 transition-colors shadow-sm" title="ดูรายละเอียด">
                <span class="material-symbols-outlined text-[18px]">visibility</span>
                <span>รายละเอียด</span>
              </a>
            </div>
          </div>

          <form id="ackForm-{{ $r->id }}" method="POST"
                action="{{ route('maintenance.requests.acknowledge', $r) }}" class="hidden">
            @csrf
          </form>

          <form id="acceptForm-{{ $r->id }}" method="POST"
                action="{{ route('maintenance.requests.accept', $r) }}" class="hidden">
            @csrf
          </form>

          <form id="rejectForm-{{ $r->id }}" method="POST"
                action="{{ route('maintenance.requests.reject', $r) }}" class="hidden">
            @csrf
            <input type="hidden" name="remark" id="rejectRemark-{{ $r->id }}" value="">
          </form>

        </div>
      @empty
        <div class="bg-white border border-slate-200 rounded-none p-12 text-center">
          <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <p class="mt-4 text-sm text-slate-500">ไม่พบรายการงานตามเงื่อนไขที่เลือก</p>
        </div>
      @endforelse

    </div>

    @if($list->hasPages())
      <div class="mt-4 mb-6 md:mb-10 lg:mb-12">
        {{ $list->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>

<audio id="notifySound" preload="auto">
  <source src="/sounds/new-request.mp3" type="audio/mpeg">
</audio>

<div id="loaderOverlay" class="loader-overlay"><div class="loader-spinner"></div></div>
@endsection
