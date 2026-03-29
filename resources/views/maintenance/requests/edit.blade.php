{{-- resources/views/maintenance/requests/edit.blade.php --}}
@extends('layouts.app')

@php
  use App\Models\MaintenanceRequest;

  /** @var \App\Models\MaintenanceRequest $mr */
  $mr = $mr instanceof MaintenanceRequest ? $mr : new MaintenanceRequest();

  $opLog = $mr->operationLog;

  $user = auth()->user();
  $isTeam = $user && (
      $user->isAdmin() ||
      $user->isSupervisor() ||
      $user->isTechnician()
  );

  // ===== UI tokens (โทนเดียวกับ show) =====
  $line = 'border-slate-200';

  $input = "mt-2 w-full h-11 rounded-md border $line bg-white px-3 py-2 text-sm
            focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm
              focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
  $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
  $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
  $subCls    = "text-sm text-slate-500 leading-snug";
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";

  // ===== team (read-only) =====
  $assignments = $mr->assignments ?? collect();
  $workers = $assignments->reject(fn($a) => $a->status === \App\Models\MaintenanceAssignment::STATUS_CANCELLED)
                         ->map(fn($a) => $a->user)->filter()->unique('id')->values();

  // ===== Technician Selection (เหมือนหน้า show) =====
  $allWorkers = $techUsers ?? collect();
  $fallbackRoleLabels = [
      'admin' => 'ผู้ดูแลระบบ',
      'it_support' => 'ไอทีซัพพอร์ต',
      'network' => 'เครือข่าย',
      'programmer' => 'โปรแกรมเมอร์',
      'technician' => 'ช่าง',
      'engineer' => 'วิศวกร',
      'supervisor' => 'หัวหน้างาน',
      'unknown' => 'อื่น ๆ',
  ];

  $roleGroups = $allWorkers->filter()->groupBy(fn($u) => (string) ($u->role ?? 'unknown'));

  $roleLabels = [];
  foreach ($allWorkers as $u) {
      $code = (string) ($u->role ?? 'unknown');
      if (!isset($roleLabels[$code])) {
          $roleLabels[$code] = $u->role_label ?? ($fallbackRoleLabels[$code] ?? ucfirst($code));
      }
  }

  $roleGroupsSorted = $roleGroups->sortBy(function ($users, $roleCode) {
      $first = $users->first();
      $sort = $first?->roleRef?->sort_order;
      return $sort === null ? 9999 : (int) $sort;
  });
@endphp

@section('title','Edit Maintenance #'.$mr->id)

@section('page-header')
  <div class="w-full bg-slate-50 border-b {{ $line }}">
    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        {{-- LEFT --}}
        <div class="min-w-0">
          <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl text-emerald-700">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>

            <div class="min-w-0">
              <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
                ทะเบียนแจ้งซ่อม
                <span class="ml-2 text-slate-500 text-[13px] sm:text-[14px] font-semibold">#{{ $mr->id }}</span>
              </h1>

              <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                <span>แก้ไขข้อมูลใบงานแจ้งซ่อม</span>
                @if($mr->updated_at)
                  <span>อัปเดต: <span class="font-medium text-slate-900">{{ $mr->updated_at->format('Y-m-d H:i') }}</span></span>
                @endif
                <span>ผู้รับผิดชอบหลัก:
                  <span class="font-semibold text-slate-900">{{ $mr->technician?->name ?? 'ยังไม่มีช่างรับงาน' }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        {{-- RIGHT --}}
        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
          <a href="{{ route('maintenance.requests.index') }}"
             class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            กลับ
          </a>
        </div>

      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 pb-8 pt-6">

    @cannot('update', $mr)
      <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
        คุณไม่มีสิทธิ์แก้ไขใบงานนี้
      </div>
      @return
    @endcannot


    {{-- ===== FORM หลัก (แก้ข้อมูลคำขอ: 1-4) ===== --}}
    <form method="POST"
          action="{{ route('maintenance.requests.update', $mr) }}"
          enctype="multipart/form-data"
          class="space-y-8"
          novalidate>
      @csrf
      @method('PUT')

      @include('maintenance.requests._form', [
        'req'         => $mr,
        'assets'      => $assets      ?? collect(),
        'depts'       => $depts       ?? collect(),
        'attachments' => $attachments ?? [],
      ])


    </form>

    {{-- ===== งานช่าง: 5 ซ้าย | 6 ขวา ===== --}}
    @if($isTeam)
      <div class="mt-10 border-t {{ $line }}"></div>

      <div class="mt-10 relative grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

        {{-- LEFT: SECTION 5 --}}
        <section>
          <div class="{{ $headCls }}">
            <div class="{{ $noCls }}">5</div>
            <div class="{{ $accentWrap }}">
              <span class="{{ $accentBar }}"></span>
              <div class="{{ $titleCls }}">รายงานการปฏิบัติงานและค่าใช้จ่าย</div>
              <div class="{{ $subCls }}">สำหรับทีมช่าง: ระบุวิธีคิดค่าใช้จ่าย, รพจ. และรายละเอียดประกอบ</div>
            </div>
          </div>

          <form method="POST"
                action="{{ route('maintenance.requests.operation-log', ['maintenanceRequest' => $mr]) }}"
                class="space-y-4"
                novalidate>
            @csrf

            <div>
              <label class="block text-sm font-medium text-slate-700">รายการซ่อมสำหรับวันที่</label>
              <input type="date" name="operation_date"
                     value="{{ old('operation_date', optional($opLog?->operation_date)->format('Y-m-d')) }}"
                     class="{{ $input }}">
            </div>

            <div>
              <div class="block text-sm font-medium text-slate-700">วิธีการปฏิบัติ / การคิดค่าใช้จ่าย</div>
              @php $method = old('operation_method', $opLog->operation_method ?? null); @endphp
              <div class="mt-2 space-y-2 text-sm">
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="requisition" @checked($method==='requisition')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>ตามใบเบิกครุภัณฑ์ / วัสดุ</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="service_fee" @checked($method==='service_fee')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>ค่าบริการ / ค่าแรงช่าง</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="other" @checked($method==='other')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>อื่น ๆ</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">ระบุรพจ. (รหัสครุภัณฑ์)</label>
              <input type="text" name="property_code"
                     value="{{ old('property_code', $opLog->property_code ?? ($mr->asset?->asset_code ?? '')) }}"
                     class="{{ $input }}"
                     placeholder="เช่น 68101068718">
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="require_precheck" value="1"
                     @checked(old('require_precheck', $opLog->require_precheck ?? false))
                     class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
              ยืนยันว่าได้แจ้งผู้ใช้งาน / หน่วยงาน และขออนุญาตก่อนปฏิบัติงาน/ปิดเครื่อง
            </label>

            <div>
              <div class="text-sm font-medium text-slate-700">ประเภทงานที่ปฏิบัติ</div>
              <div class="mt-2 flex items-center gap-6 text-sm text-slate-700">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="issue_software" value="1"
                         @checked(old('issue_software', $opLog->issue_software ?? false))
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  Software
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="issue_hardware" value="1"
                         @checked(old('issue_hardware', $opLog->issue_hardware ?? false))
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  Hardware
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">หมายเหตุ / รายละเอียดประกอบ</label>
              <textarea name="remark" rows="4" class="{{ $textarea }}"
                        placeholder="เช่น ตรวจเช็คแล้วพบว่า..., ผู้ใช้ทดสอบแล้วเรียบร้อย">{{ old('remark', $opLog->remark ?? '') }}</textarea>
            </div>

            <div class="pt-2">
              <button type="submit"
                      class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600
                             text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200">
                บันทึกการปฏิบัติงาน
              </button>
            </div>

            @if($opLog)
              <p class="text-xs text-slate-500">
                บันทึกล่าสุดโดย {{ $opLog->user?->name ?? '-' }} · {{ $opLog->updated_at?->format('Y-m-d H:i') }}
              </p>
            @endif
          </form>
        </section>

        {{-- RIGHT: SECTION 6 --}}
        <section>
          <div class="{{ $headCls }}">
            <div class="{{ $noCls }}">6</div>
            <div class="{{ $accentWrap }}">
              <span class="{{ $accentBar }}"></span>
              <div class="{{ $titleCls }}">ทีมช่างที่รับผิดชอบ</div>
              <div class="{{ $subCls }}">ผู้ปฏิบัติงาน</div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="text-[14px] font-semibold text-slate-800">รายชื่อทีมช่าง</div>
              <div class="text-[12px] text-slate-500">{{ $workers->count() }} คน</div>
            </div>

            <div class="rounded-lg border {{ $line }} bg-white max-h-72 overflow-y-auto divide-y divide-slate-200">
              @if ($workers->isEmpty())
                <div class="px-4 py-3 text-[13px] text-slate-500">ยังไม่ได้มอบหมายงานให้ทีมช่าง</div>
              @else
                @foreach ($workers as $worker)
                  @php
                    $assign = $assignments->firstWhere('user_id', $worker->id);
                    if (!$assign || $assign->status === \App\Models\MaintenanceAssignment::STATUS_CANCELLED) {
                      continue;
                    }
                    $isLead = (bool) ($assign->is_lead ?? false);
                    $avatar = $worker->avatar_thumb_url ?? null;
                  @endphp
                  <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                      <div class="h-9 w-9 flex-shrink-0 overflow-hidden rounded-full border {{ $line }} bg-white">
                        @if ($avatar)
                          <img src="{{ $avatar }}" alt="{{ $worker->name }}" class="h-full w-full object-cover">
                        @else
                          <div class="grid h-full w-full place-items-center text-[11px] text-slate-500 bg-slate-50">—</div>
                        @endif
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="truncate text-[14px] font-semibold text-slate-900" title="{{ $worker->name }}">
                          {{ $worker->name }}
                          @if ($isLead)
                            <span class="ml-2 inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">
                              Lead
                            </span>
                          @endif
                        </div>
                        <div class="truncate text-[12px] text-slate-500">
                          {{ $worker->role_label ?? ($fallbackRoleLabels[$worker->role ?? 'unknown'] ?? ($worker->role ?? 'unknown')) }}
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              @endif
            </div>

            @can('assign', $mr)
              <div class="flex justify-end">
                <button type="button" id="openAssignModalBtn"
                  class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-[13px] font-semibold
                  text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 shadow-sm transition-all active:scale-95">
                  <img src="/icon/technical-support.avif" class="w-4 h-4 object-contain brightness-0 invert" alt="Assign">
                  มอบหมายทีมช่าง
                </button>
              </div>
            @endcan
          </div>
        </section>

      </div>
    @endif

  </div>

  {{-- Assign Modal --}}
  @can('assign', $mr)
      <div id="assignModal"
          class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-3 sm:p-4">
          <div class="relative z-[10000] w-full max-w-4xl overflow-hidden rounded-2xl border {{ $line }} bg-white shadow-xl">

              {{-- Modal Header --}}
              <div class="flex items-center justify-between border-b {{ $line }} px-6 py-4">
                  <div class="flex items-start gap-3 min-w-0">
                      <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center text-indigo-700">
                          <img src="/icon/technical-support.avif" class="h-9 w-9 object-contain" alt="Icon">
                      </span>
                      <div class="min-w-0">
                          <div class="text-[16px] font-semibold text-slate-900 leading-tight">มอบหมายทีมช่าง</div>
                          <p class="text-[13px] text-slate-500">ค้นหาและเลือกช่างที่ต้องการ</p>
                      </div>
                  </div>
                  <button type="button" id="closeAssignModalBtn"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                          <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                      </svg>
                  </button>
              </div>

              <form method="POST" action="{{ route('maintenance.requests.assignments.store', $mr) }}">
                  @csrf
                  <input type="hidden" id="assignSuggestRole" value="{{ $suggestRole }}">
                  <input type="hidden" name="update_team_flag" value="1">

                  <div class="grid grid-cols-1 lg:grid-cols-[380px,1fr] lg:h-[65vh]">

                      {{-- Left Sidebar --}}
                      <div class="border-b lg:border-b-0 lg:border-r {{ $line }} bg-slate-50 flex flex-col min-h-0">

                          {{-- Controls --}}
                          <div class="p-5 space-y-4 flex-none">
                              <div>
                                  <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">ค้นหาชื่อ</label>
                                  <div class="relative">
                                      <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                                              <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                          </svg>
                                      </span>
                                      <input id="assignSearch" type="text"
                                          class="w-full rounded-lg border {{ $line }} bg-white pl-9 pr-3 py-2.5 text-[13px]
                                          focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400"
                                          placeholder="พิมพ์ชื่อช่าง...">
                                  </div>
                              </div>

                              <div>
                                  <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">กรองตามตำแหน่ง</label>
                                  <select id="assignRoleFilter"
                                      class="w-full rounded-lg border {{ $line }} bg-white px-3 py-2.5 text-[13px]
                                      focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400">
                                      <option value="">— ทั้งหมด —</option>
                                      @foreach ($roleGroupsSorted as $roleCode => $roleUsers)
                                          <option value="{{ strtolower((string) $roleCode) }}">
                                              {{ $roleLabels[$roleCode] ?? ucfirst((string) $roleCode) }}
                                          </option>
                                      @endforeach
                                  </select>
                                  <div id="assignSuggestHint" class="mt-1.5 text-[12px] text-indigo-600 hidden">
                                      ตัวกรองถูกตั้งค่าตามประเภทงานโดยอัตโนมัติ
                                  </div>
                              </div>

                              <div class="grid grid-cols-2 gap-2">
                                  <button type="button" id="assignSelectAllBtn"
                                      class="inline-flex items-center justify-center rounded-lg border {{ $line }} bg-white px-3 py-2
                                      text-[12px] font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                                      เลือกทั้งหมด
                                  </button>
                                  <button type="button" id="assignClearAllBtn"
                                      class="inline-flex items-center justify-center rounded-lg border {{ $line }} bg-white px-3 py-2
                                      text-[12px] font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                                      ล้างการเลือก
                                  </button>
                              </div>
                          </div>

                          {{-- Selected List --}}
                          <div class="px-5 pb-4 border-t {{ $line }} pt-4 flex flex-col flex-1 min-h-0">
                              <div class="flex items-center justify-between flex-none mb-2">
                                  <div class="text-[13px] font-semibold text-slate-700">เลือกแล้ว</div>
                                  <div class="text-[13px] text-slate-500" id="assignSelectedMeta">0 คน</div>
                              </div>
                              <div id="assignSelectedEmpty" class="text-[13px] text-slate-400 flex-none">
                                  ยังไม่ได้เลือกช่าง
                              </div>
                              <div id="assignSelectedList" class="space-y-1.5 overflow-y-auto pr-1 hidden flex-1 min-h-0"></div>
                          </div>
                      </div>

                      {{-- Right Main List --}}
                      <div class="flex flex-col min-h-0 bg-white">
                          <div class="flex items-center justify-between border-b {{ $line }} px-5 py-3 flex-none bg-slate-50/50">
                              <div class="text-[14px] font-semibold text-slate-800">
                                  รายชื่อช่าง
                                  <span id="assignVisibleCount" class="text-slate-500 font-normal">(0)</span>
                              </div>
                          </div>

                          <div class="flex-1 min-h-0 overflow-y-auto" id="assignListScroll">
                              @if ($roleGroupsSorted->isEmpty())
                                  <div class="px-5 py-10 text-center text-[14px] text-slate-500">
                                      ไม่พบข้อมูลช่างในระบบ
                                  </div>
                              @else
                                  @foreach ($roleGroupsSorted as $roleCode => $groupUsers)
                                      @php
                                          $roleTitle = $roleLabels[$roleCode] ?? ucfirst((string) $roleCode);
                                          $roleCount = $groupUsers->count();
                                          $roleKey = strtolower((string) $roleCode);
                                      @endphp
                                      <section class="border-b {{ $line }}" data-role-group="1" data-role-group-code="{{ $roleKey }}">
                                          <div class="sticky top-0 z-10 px-5 py-2 bg-slate-100 border-b {{ $line }}">
                                              <div class="text-[12px] font-bold text-slate-600 uppercase tracking-widest">
                                                  {{ $roleTitle }} ({{ $roleCount }})
                                              </div>
                                          </div>
                                          <div class="divide-y divide-slate-100">
                                              @foreach ($groupUsers as $worker)
                                                  @php
                                                      $roleLabelRow = $worker->role_label ?? ($worker->role ?? 'unknown');
                                                      $avatar = $worker->avatar_thumb_url ?? null;
                                                  @endphp
                                                  <label
                                                      class="assign-user-row flex items-center gap-3 px-5 py-2.5 hover:bg-indigo-50/30 cursor-pointer transition-colors"
                                                      data-role="{{ $roleKey }}"
                                                      data-name="{{ strtolower((string) $worker->name) }}"
                                                      data-display-name="{{ $worker->name }}"
                                                      data-role-label="{{ $roleLabelRow }}">

                                                      <input type="checkbox"
                                                          class="assign-user-checkbox h-4 w-4 flex-shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                          data-role="{{ (string) $roleCode }}"
                                                          name="user_ids[]"
                                                          value="{{ $worker->id }}"
                                                          @checked($workers->contains('id', $worker->id))>

                                                      <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
                                                          @if ($avatar)
                                                              <img src="{{ $avatar }}" alt="{{ $worker->name }}" class="h-full w-full object-cover">
                                                          @else
                                                              <div class="grid h-full w-full place-items-center text-[12px] font-semibold text-slate-500 bg-slate-100 uppercase">
                                                                  {{ mb_substr($worker->name, 0, 1) }}
                                                              </div>
                                                          @endif
                                                      </div>

                                                      <div class="flex-1 min-w-0 truncate">
                                                          <span class="text-[14px] font-semibold text-slate-900 truncate block" title="{{ $worker->name }}">
                                                              {{ $worker->name }}
                                                          </span>
                                                      </div>
                                                  </label>
                                              @endforeach
                                          </div>
                                      </section>
                                  @endforeach
                              @endif
                          </div>
                      </div>
                  </div>

                  {{-- Modal Footer --}}
                  <div class="flex items-center justify-end gap-3 border-t {{ $line }} px-6 py-4 bg-slate-50">
                      <button type="button" id="cancelAssignModalBtn"
                          class="rounded-lg border {{ $line }} bg-white px-4 py-2 text-[14px] font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                          ยกเลิก
                      </button>
                      <button type="submit"
                          class="rounded-lg bg-indigo-600 px-6 py-2 text-[14px] font-bold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 shadow-md transition-all active:scale-95">
                          บันทึกการมอบหมาย
                      </button>
                  </div>
              </form>
          </div>
      </div>
  @endcan

@endsection

@push('scripts')
    <script>
        (function() {
            'use strict';

            const modal = document.getElementById('assignModal');
            const open = document.getElementById('openAssignModalBtn');
            const close = document.getElementById('closeAssignModalBtn');
            const cancel = document.getElementById('cancelAssignModalBtn');

            if (modal && open) {
                const searchInput = document.getElementById('assignSearch');
                const roleFilter = document.getElementById('assignRoleFilter');
                const suggestRole = (document.getElementById('assignSuggestRole')?.value || '').trim().toLowerCase();
                const visibleCountEl = document.getElementById('assignVisibleCount');
                const hintEl = document.getElementById('assignSuggestHint');
                const selectAllBtn = document.getElementById('assignSelectAllBtn');
                const clearAllBtn = document.getElementById('assignClearAllBtn');
                const selectedMetaEl = document.getElementById('assignSelectedMeta');
                const selectedEmptyEl = document.getElementById('assignSelectedEmpty');
                const selectedListEl = document.getElementById('assignSelectedList');
                const assignForm = modal.querySelector('form');

                const getAllRows = () => Array.from(modal.querySelectorAll('.assign-user-row'));
                const getAllCheckboxes = () => Array.from(modal.querySelectorAll('.assign-user-checkbox'));
                const getVisibleCheckboxes = () => getAllRows()
                    .filter(row => row.style.display !== 'none')
                    .map(row => row.querySelector('.assign-user-checkbox'))
                    .filter(Boolean);

                const escapeHtml = (str) => String(str || '')
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

                const initials = (name) => {
                    const s = (name || '').trim();
                    if (!s) return '—';
                    const parts = s.split(/\s+/).filter(Boolean);
                    const a = (parts[0] || '').charAt(0);
                    const b = parts.length > 1 ? (parts[parts.length - 1] || '').charAt(0) : '';
                    return (a + b).toUpperCase();
                };

                function updateSelectedList() {
                    if (!selectedListEl || !selectedEmptyEl || !selectedMetaEl) return;
                    const checked = getAllCheckboxes().filter(cb => cb.checked);
                    selectedMetaEl.textContent = checked.length + ' คน';
                    selectedListEl.innerHTML = '';

                    if (checked.length === 0) {
                        selectedEmptyEl.classList.remove('hidden');
                        selectedListEl.classList.add('hidden');
                        return;
                    }

                    selectedEmptyEl.classList.add('hidden');
                    selectedListEl.classList.remove('hidden');

                    checked.forEach(cb => {
                        const row = cb.closest('.assign-user-row');
                        const displayName = row?.getAttribute('data-display-name') || '';
                        const roleLabel = row?.getAttribute('data-role-label') || '';
                        const ini = initials(displayName);
                        const userId = cb.value;

                        const item = document.createElement('div');
                        item.className = 'flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 animate-in fade-in slide-in-from-left-2 duration-200';
                        item.dataset.userId = userId;

                        item.innerHTML = `
                        <div class="h-8 w-8 rounded-full bg-slate-800 text-white grid place-items-center text-[11px] font-bold flex-shrink-0 shadow-sm">
                            ${escapeHtml(ini)}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[12px] font-bold text-slate-900 leading-tight" title="${escapeHtml(displayName)}">${escapeHtml(displayName)}</div>
                            <div class="truncate text-[10px] text-slate-500 uppercase tracking-tighter" title="${escapeHtml(roleLabel)}">${escapeHtml(roleLabel)}</div>
                        </div>
                        <button type="button" class="assign-chip-remove flex-shrink-0 inline-flex items-center justify-center h-6 w-6 rounded-full text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-colors" data-user-id="${escapeHtml(userId)}" title="ลบออก">
                            <svg class="h-3.5 w-3.5 pointer-events-none" viewBox="0 0 24 24" fill="none">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </button>
                    `;
                        selectedListEl.appendChild(item);
                    });
                }

                function updateCounts() {
                    const visible = getAllRows().filter(r => r.style.display !== 'none').length;
                    if (visibleCountEl) visibleCountEl.textContent = `(${visible})`;
                    updateSelectedList();
                }

                function applyFilter() {
                    const q = (searchInput?.value || '').trim().toLowerCase();
                    const r = (roleFilter?.value || '').trim().toLowerCase();

                    getAllRows().forEach(row => {
                        const name = (row.getAttribute('data-name') || '').toLowerCase();
                        const role = (row.getAttribute('data-role') || '').toLowerCase();
                        const okName = !q || name.includes(q);
                        const okRole = !r || role === r;
                        row.style.display = (okName && okRole) ? '' : 'none';
                    });

                    modal.querySelectorAll('[data-role-group]').forEach(group => {
                        const inner = Array.from(group.querySelectorAll('.assign-user-row'));
                        group.style.display = inner.some(x => x.style.display !== 'none') ? '' : 'none';
                    });
                    updateCounts();
                }

                function showModal() {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    if (roleFilter && suggestRole && !roleFilter.value) {
                        const hasOption = Array.from(roleFilter.options).some(opt => (opt.value || '').toLowerCase() === suggestRole);
                        if (hasOption) {
                            roleFilter.value = suggestRole;
                            hintEl?.classList.remove('hidden');
                        }
                    }
                    applyFilter();
                }

                function hideModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                }

                open.addEventListener('click', showModal);
                close?.addEventListener('click', hideModal);
                cancel?.addEventListener('click', hideModal);
                modal.addEventListener('click', e => { if (e.target === modal) hideModal(); });

                selectAllBtn?.addEventListener('click', () => {
                    getVisibleCheckboxes().forEach(cb => cb.checked = true);
                    updateCounts();
                });

                clearAllBtn?.addEventListener('click', () => {
                    getAllCheckboxes().forEach(cb => cb.checked = false);
                    updateCounts();
                });

                searchInput?.addEventListener('input', applyFilter);
                roleFilter?.addEventListener('change', applyFilter);
                modal.addEventListener('change', e => { if (e.target?.classList?.contains('assign-user-checkbox')) updateCounts(); });

                selectedListEl?.addEventListener('click', e => {
                    const btn = e.target.closest('.assign-chip-remove');
                    if (!btn) return;
                    const userId = btn.dataset.userId;
                    const cb = modal.querySelector(`.assign-user-checkbox[value="${CSS.escape(userId)}"]`);
                    if (cb) cb.checked = false;
                    updateCounts();
                });

                assignForm?.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="animate-spin mr-2">◌</span> กำลังบันทึก...';
                    }
                });

                updateCounts();
            }
        })();
    </script>
@endpush
