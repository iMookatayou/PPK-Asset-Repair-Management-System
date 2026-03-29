@extends('layouts.app')

@section('title', 'สรุปใบงานซ่อม #' . $req->id)

@section('page-header')
    @php
        use App\Models\MaintenanceRequest as MR;
        use Carbon\Carbon;

        $line = 'border-slate-200';

        $statusLabels = [
            'pending' => 'รอรับเรื่อง',
            'acknowledged' => 'รับทราบแล้ว',
            'accepted' => 'รับเรื่องแล้ว',
            'in_progress' => 'กำลังดำเนินการ',
            'on_hold' => 'พักชั่วคราว',
            'resolved' => 'เสร็จสิ้น',
            'closed' => 'ปิดงาน',
            'cancelled' => 'ยกเลิก',
        ];

        $status = $req->status;
        $currentStatusTH = $statusLabels[$status] ?? $status;

        $level = 1;
        if ($status === MR::STATUS_ACKNOWLEDGED) {
            $level = 2;
        }
        if ($status === MR::STATUS_ACCEPTED) {
            $level = 3;
        }
        if (in_array($status, [MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD], true)) {
            $level = 4;
        }
        if (in_array($status, [MR::STATUS_RESOLVED, MR::STATUS_CLOSED], true)) {
            $level = 5;
        }
        if ($status === MR::STATUS_CANCELLED) {
            $level = 0;
        }

        $dates = [
            1 => $req->request_date ?? $req->created_at,
            2 => $req->acknowledged_at,
            3 => $req->accepted_at,
            4 => $req->started_at ?? ($level >= 4 ? $req->accepted_at : null),
            5 => $req->resolved_at ?? $req->closed_at,
        ];

        $fmt = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y H:i') : '';

        $widthMap = [1 => '0%', 2 => '25%', 3 => '50%', 4 => '75%', 5 => '100%'];
        $lineWidth = $widthMap[$level] ?? '0%';

        $canAcknowledge = $status === MR::STATUS_PENDING && \Gate::allows('acknowledge', $req);
        $canAccept = $status === MR::STATUS_ACKNOWLEDGED && \Gate::allows('accept', $req);
        $canReject = $status === MR::STATUS_ACKNOWLEDGED && \Gate::allows('reject', $req);
        $canCancel =
            in_array($status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD], true) &&
            (\Gate::allows('cancelByReporter', $req) || \Gate::allows('cancelByTech', $req));
        $canStart = $status === MR::STATUS_ACCEPTED && \Gate::allows('startWork', $req);
        $canHold =
            in_array($status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS], true) && \Gate::allows('hold', $req);
        $canResume = $status === MR::STATUS_ON_HOLD && \Gate::allows('resume', $req);
        $canResolve =
            $status === MR::STATUS_IN_PROGRESS && \Gate::allows('resolve', $req);
        $canClose = $status === MR::STATUS_RESOLVED && \Gate::allows('close', $req);

        $btnBase = 'inline-flex items-center justify-center gap-2
              rounded-lg px-4 h-9
              text-sm font-medium
              transition-all duration-200
              focus:outline-none focus:ring-2 focus:ring-offset-2';
    @endphp

    <style>
        .animate-spin-slow {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .ms {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>

    <div class="w-full bg-slate-50 border-b {{ $line }}">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-4">

            {{-- Row 1: Title + Actions --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center">
                        <img src="/icon/maintenance1.avif" class="h-7 w-7 object-contain"
                            style="filter: invert(24%) sepia(87%) saturate(1469%) hue-rotate(139deg) brightness(91%) contrast(102%);"
                            alt="Icon">
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                            <h1 class="text-[18px] sm:text-[20px] font-semibold text-slate-900 leading-tight">
                                ทะเบียนแจ้งซ่อม
                            </h1>
                            <span class="text-slate-400 text-[13px] font-semibold">
                                #{{ $req->request_no ?? $req->id }}
                            </span>
                        </div>
                        <div class="mt-0.5 text-[12px] text-slate-500 flex flex-wrap gap-x-3">
                            @if ($req->updated_at)
                                <span>อัปเดต: <span
                                        class="font-medium text-slate-700">{{ $req->updated_at->format('Y-m-d H:i') }}</span></span>
                            @endif
                            <span>ผู้รับผิดชอบ: <span
                                    class="font-medium text-slate-700">{{ $req->technician?->name ?? 'ยังไม่มีช่างรับเรื่อง' }}</span></span>
                        </div>
                    </div>
                </div>

                {{-- Action buttons: workflow left, nav right --}}
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    {{-- Workflow buttons --}}
                    @if ($canAcknowledge)
                        <form method="POST" action="{{ route('maintenance.requests.acknowledge', $req->id) }}">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-[#1e3a8a] text-white hover:bg-blue-900 focus:ring-blue-300">
                                <span
                                    class="material-symbols-outlined ms text-[16px] leading-none">approval_delegation</span>
                                รับทราบ
                            </button>
                        </form>
                    @endif

                    @if ($canAccept)
                        <form method="POST" action="{{ route('maintenance.requests.accept', $req->id) }}">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300">
                                <span class="material-symbols-outlined ms text-[16px] leading-none">check</span>
                                รับเรื่อง
                            </button>
                        </form>
                    @endif

                    @if ($canStart)
                        <form method="POST" action="{{ route('maintenance.requests.start', $req->id) }}">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-200">
                                <span
                                    class="material-symbols-outlined ms text-[16px] leading-none">keyboard_arrow_right</span>
                                ดำเนินการ
                            </button>
                        </form>
                    @endif

                    @if ($canHold)
                        <button type="button" id="openHoldModalBtn"
                            class="{{ $btnBase }} bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-200">
                            <span class="material-symbols-outlined ms text-[16px] leading-none">pause_circle</span>
                            พักชั่วคราว
                        </button>
                    @endif

                    @if ($canResume)
                        <form method="POST" action="{{ route('maintenance.requests.resume', $req->id) }}">
                            @csrf
                            <button class="{{ $btnBase }} bg-sky-600 text-white hover:bg-sky-700 focus:ring-sky-200">
                                <span
                                    class="material-symbols-outlined ms text-[16px] leading-none">keyboard_double_arrow_right</span>
                                กลับเข้าดำเนินการ
                            </button>
                        </form>
                    @endif

                    @if ($canResolve)
                        <form method="POST" action="{{ route('maintenance.requests.resolve', $req->id) }}">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-200">
                                <span class="material-symbols-outlined ms text-[16px] leading-none">task_alt</span>
                                ซ่อมเสร็จ
                            </button>
                        </form>
                    @endif

                    @if ($canClose)
                        <form method="POST" action="{{ route('maintenance.requests.close', $req->id) }}">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-slate-700 text-white hover:bg-slate-800 focus:ring-slate-300">
                                <span class="material-symbols-outlined ms text-[16px] leading-none">done_all</span>
                                ปิดงาน
                            </button>
                        </form>
                    @endif

                    @if ($req->status === \App\Models\MaintenanceRequest::STATUS_CLOSED)
                        @can('rate', $req)
                            <a href="{{ route('maintenance.ratings.create', $req->id) }}"
                                class="{{ $btnBase }} bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-200">
                                <span class="material-symbols-outlined ms text-[16px] leading-none">star</span>
                                ประเมินความพึงพอใจ
                            </a>
                        @endcan
                    @endif

                    @if ($canReject)
                        <button type="button" id="openRejectModalBtn"
                            class="{{ $btnBase }} bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-300">
                            <span class="material-symbols-outlined ms text-[16px] leading-none">block</span>
                            ไม่รับเรื่อง
                        </button>
                    @endif

                    @if ($canCancel)
                        <form method="POST" action="{{ route('maintenance.requests.cancel', $req->id) }}"
                            onsubmit="return confirm('ยืนยันการยกเลิก?');">
                            @csrf
                            <button
                                class="{{ $btnBase }} bg-slate-500 text-white hover:bg-slate-600 focus:ring-slate-300">
                                <span class="material-symbols-outlined ms text-[16px] leading-none">cancel</span>
                                ยกเลิกซ่อม
                            </button>
                        </form>
                    @endif

                    {{-- Divider: แสดงเฉพาะเมื่อมี workflow button --}}
                    @if (
                        $canAcknowledge ||
                            $canAccept ||
                            $canStart ||
                            $canHold ||
                            $canResume ||
                            $canResolve ||
                            $canClose ||
                            $canReject ||
                            $canCancel ||
                            $req->status === \App\Models\MaintenanceRequest::STATUS_CLOSED)
                        <div class="h-6 w-px bg-slate-200"></div>
                    @endif

                    {{-- Nav buttons --}}
                    <a href="{{ route('maintenance.requests.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border {{ $line }} bg-white px-3 h-9 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        กลับ
                    </a>

                    <a href="{{ route('maintenance.requests.work-order', $req->id) }}" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border {{ $line }} bg-white px-3 h-9 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined ms text-[16px] leading-none">print</span>
                        พิมพ์ PDF
                    </a>
                </div>
            </div>

            {{-- Row 2: Progress bar --}}
            <div class="w-full px-2 sm:px-4 mt-5">
                <div class="relative w-full">
                    <div class="absolute top-[22px] left-0 w-full h-[6px] bg-slate-200 rounded-full z-0"></div>
                    <div class="absolute top-[22px] left-0 h-[6px] bg-[#1e3a8a] rounded-full z-0 transition-all duration-700 ease-out"
                        style="width: {{ $lineWidth }};"></div>

                    <div class="relative z-10 flex justify-between w-full">
                        @php
                            $steps = [
                                1 => 'แจ้งเรื่อง',
                                2 => 'รับทราบแล้ว',
                                3 => 'รับเรื่องแล้ว',
                                4 => 'กำลังดำเนินการ',
                                5 => 'เสร็จสิ้น',
                            ];
                        @endphp

                        @foreach ($steps as $key => $label)
                            @php
                                $isDone = $level > $key || ($key == 5 && $level >= 5);
                                $isCurrent = $level == $key && $key != 5;
                                $dateVal = $dates[$key] ?? null;
                            @endphp

                            <div class="flex flex-col items-center w-32">
                                <div
                                    class="w-[44px] h-[44px] rounded-full flex items-center justify-center transition-all duration-300
                                    {{ $isDone ? 'bg-[#408a5c] border-4 border-[#408a5c]' : '' }}
                                    {{ $isCurrent ? 'bg-white' : '' }}
                                    {{ !$isDone && !$isCurrent ? 'bg-slate-200 border-4 border-slate-200' : '' }}">
                                    @if ($isDone)
                                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @elseif($isCurrent)
                                        <div class="relative w-full h-full">
                                            <div class="absolute inset-0 rounded-full border-[4px] border-slate-100"></div>
                                            <div
                                                class="absolute inset-0 rounded-full border-[4px] border-t-blue-600 border-r-transparent border-b-transparent border-l-transparent animate-spin-slow">
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-2 text-center">
                                    <p
                                        class="text-[13px] font-bold {{ $isCurrent || $isDone ? 'text-slate-900' : 'text-slate-400' }}">
                                        {{ $label }}
                                    </p>
                                    @if ($isCurrent)
                                        <p class="text-[11px] font-medium text-[#1e3a8a] mt-0.5">(ปัจจุบัน)</p>
                                    @elseif($dateVal)
                                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $fmt($dateVal) }}</p>
                                    @else
                                        <p class="h-[16px]"></p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;

        $line = 'border-slate-200';

        $input = "mt-2 w-full h-10 rounded-md border $line bg-white px-3 py-2 text-sm
            focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
        $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm
              focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

        $headCls = 'flex items-start gap-3 pb-3 min-h-[56px]';
        $noCls = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
        $titleCls = 'text-base font-semibold text-slate-900 leading-tight';
        $subCls = 'text-sm text-slate-500 leading-snug';
        $accentWrap = 'min-w-0 relative pl-3 pt-[1px]';
        $accentBar = 'absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90';

        $select = "w-full h-9 rounded-md border $line bg-white px-3 text-sm
          focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

        $prio = strtolower((string) ($req->priority ?? ''));

        $prioLabel =
            [
                'low' => 'ต่ำ',
                'medium' => 'ปานกลาง',
                'high' => 'เร่งด่วน',
                'urgent' => 'เร่งด่วนมาก',
            ][$prio] ?? '-';

        $prioTextTone = match ($prio) {
            'low' => 'text-slate-600',
            'medium' => 'text-sky-600',
            'high' => 'text-amber-600',
            'urgent' => 'text-rose-600',
            default => 'text-slate-600',
        };

        $assetName = $req->asset?->name ?? ($req->asset_id ? '#' . $req->asset_id : '—');
        $assetCode = $req->asset?->asset_code;
        $location = $req->location_text ?: $req->department?->name_th ?? ($req->department?->name_en ?? '—');

        $assignments = $req->assignments ?? collect();
        $workers = $assignments->map(fn($a) => $a->user)->filter()->unique('id')->values();

        $atts = $req->attachments ?? collect();
        $opLog = $req->operationLog;

        $allWorkers = $techUsers ?? collect();

        $assignStoreUrl = route('maintenance.requests.assignments.store', $req->id);
        $opLogUrl = route('maintenance.requests.operation-log', $req->id);
        $attachUploadUrl = route('maintenance.requests.attachments', $req->id);

        $suggestRole = strtolower(trim((string) optional($req->type)->default_role_code));

        $fallbackRoleLabels = [
            'it' => 'IT',
            'tech' => 'ช่าง',
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
            if ($sort === null) {
                return 9999;
            }
            return (int) $sort;
        });
    @endphp

    <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 pb-8">
        <div class="mt-4 space-y-6">
            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

                <section>
                    <div class="{{ $headCls }}">
                        <div class="{{ $noCls }}">1</div>
                        <div class="{{ $accentWrap }}">
                            <span class="{{ $accentBar }}"></span>
                            <div class="{{ $titleCls }}">ข้อมูลหลัก</div>
                            <div class="{{ $subCls }}">ทรัพย์สิน / หน่วยงาน / สถานที่</div>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-sm font-medium text-slate-700">ทรัพย์สิน</div>
                            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">{{ $assetName }}</div>
                                @if ($assetCode)
                                    <div class="mt-1 text-xs text-slate-500">รหัสครุภัณฑ์: {{ $assetCode }}</div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">หน่วยงาน</div>
                            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">
                                    {{ $req->department?->name_th ?? ($req->department?->name_en ?? '—') }}
                                </div>
                                @if ($req->department?->code)
                                    <div class="mt-1 text-xs text-slate-500">รหัสหน่วยงาน: {{ $req->department->code }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">สถานที่ / ตำแหน่งงาน</div>
                            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">{{ $location }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="{{ $headCls }}">
                        <div class="{{ $noCls }}">2</div>
                        <div class="{{ $accentWrap }}">
                            <span class="{{ $accentBar }}"></span>
                            <div class="{{ $titleCls }}">รายละเอียดปัญหา</div>
                            <div class="{{ $subCls }}">หัวข้อและอาการเสีย</div>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        @php
                            $types = collect($types ?? []);
                            $select = "w-full h-9 rounded-md border $line bg-white px-3 text-sm
                        focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
                        @endphp

                        <div>
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium text-slate-700">ประเภทงาน (Report Type)</div>
                                @cannot('setType', $req)
                                    <span class="text-[12px] text-slate-400">* เฉพาะเจ้าหน้าที่/ช่างเท่านั้น</span>
                                @endcannot
                            </div>

                            @can('setType', $req)
                                <form method="POST" action="{{ route('maintenance.requests.type.update', $req->id) }}"
                                    class="mt-2 flex gap-2 items-center">
                                    @csrf
                                    <select name="type_id" class="{{ $select }}">
                                        <option value="">— ยังไม่ระบุประเภท —</option>
                                        @foreach ($types as $t)
                                            <option value="{{ $t->id }}" @selected((int) $req->type_id === (int) $t->id)>
                                                {{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="inline-flex items-center justify-center h-9 w-9 rounded-md bg-emerald-600 text-white
                               hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-slate-300"
                                        title="บันทึกประเภท">
                                        <span class="material-symbols-outlined ms text-[18px] leading-none">save</span>
                                    </button>
                                </form>
                            @else
                                <div
                                    class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 text-sm text-slate-700 min-h-[44px] flex items-center">
                                    {{ $req->type?->name ?? '— ยังไม่ระบุประเภท —' }}
                                </div>
                            @endcan
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">หัวข้อ <span class="text-rose-600">*</span>
                            </div>
                            <div
                                class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 font-semibold text-slate-900 min-h-[44px]">
                                {{ $req->title ?: '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-sm font-medium text-slate-700">รายละเอียด / อาการเสีย</div>
                            <div
                                class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 text-slate-800 whitespace-pre-line min-h-[120px]">
                                {{ $req->description ?: '—' }}
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="border-t {{ $line }}"></div>

            <section>
                <div class="{{ $headCls }}">
                    <div class="{{ $noCls }}">3</div>
                    <div class="{{ $accentWrap }}">
                        <span class="{{ $accentBar }}"></span>
                        <div class="{{ $titleCls }}">ผู้แจ้ง &amp; ความเร่งด่วน</div>
                        <div class="{{ $subCls }}">ข้อมูลผู้แจ้ง / ระดับความเร่งด่วน</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-sm font-medium text-slate-700">ผู้แจ้ง</div>
                            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
                                <div class="font-semibold text-slate-900">
                                    {{ $req->reporter?->name ?? ($req->reporter_name ?? '-') }}
                                </div>
                                @if (($req->reporter?->email ?? $req->reporter_email) || $req->reporter_phone)
                                    <div class="mt-1 text-xs text-slate-500 space-y-0.5">
                                        @if ($req->reporter?->email ?? $req->reporter_email)
                                            <div>{{ $req->reporter?->email ?? $req->reporter_email }}</div>
                                        @endif
                                        @if ($req->reporter_phone)
                                            <div>โทร. {{ $req->reporter_phone }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-sm font-medium text-slate-700">ระดับความเร่งด่วน</div>
                            <div class="mt-2 text-[15px] font-semibold {{ $prioTextTone }}">
                                {{ $prioLabel }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="border-t {{ $line }}"></div>

            <section>
                <div class="{{ $headCls }}">
                    <div class="{{ $noCls }}">4</div>
                    <div class="{{ $accentWrap }}">
                        <span class="{{ $accentBar }}"></span>
                        <div class="{{ $titleCls }}">ไฟล์แนบ</div>
                        <div class="{{ $subCls }}">รูป / เอกสารประกอบ</div>
                    </div>
                </div>

                @can('attach', $req)
                    <form method="post" enctype="multipart/form-data" action="{{ $attachUploadUrl }}" class="space-y-4"
                        novalidate>
                        @csrf
                        <input id="mr_files_any" type="file" name="files[]" multiple accept="image/*,application/pdf"
                            class="hidden">
                        <input id="mr_files_camera" type="file" name="files[]" accept="image/*" capture="environment"
                            class="hidden">

                        <div class="flex items-center gap-2">
                            <button type="button" id="mr_files_any_btn"
                                class="inline-flex items-center justify-center h-10 px-4 rounded-md border {{ $line }} bg-white
                                       text-sm font-medium text-slate-700 hover:bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <svg class="h-4 w-4 mr-2 text-slate-600" viewBox="0 0 24 24" fill="none"
                                    aria-hidden="true">
                                    <path
                                        d="M21 11.5l-8.5 8.5a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.7-8.7"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                เลือกไฟล์เพิ่ม
                            </button>

                            <button type="button" id="mr_files_camera_btn"
                                class="inline-flex items-center justify-center h-10 w-11 rounded-md border {{ $line }} bg-white
                                       hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path
                                        d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                    <circle cx="12" cy="13" r="4" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-3 p-3 rounded-md bg-amber-50 border border-amber-200">
                            <div class="flex gap-2">
                                <svg class="h-5 w-5 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                                <div class="text-xs text-amber-800">
                                    <p class="font-bold">เงื่อนไขการแนบไฟล์:</p>
                                    <p>จำกัดสูงสุด 3 ไฟล์ต่อใบงาน และสามารถเพิ่ม/ลบได้ตามสิทธิ์และสถานะของใบงานเท่านั้น</p>
                                </div>
                            </div>
                        </div>

                        {{-- Preview list --}}
                        <div id="mr_files_preview" class="mt-3 hidden">
                            <div class="text-xs font-medium text-slate-600">ไฟล์ที่เลือก</div>
                            <div id="mr_files_list"
                                class="mt-2 divide-y divide-slate-200 rounded-md border {{ $line }} bg-white"></div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit"
                                class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200">
                                แนบไฟล์
                            </button>
                        </div>
                    </form>

                    <script>
                        (function() {
                            const anyInput = document.getElementById('mr_files_any');
                            const camInput = document.getElementById('mr_files_camera');
                            const anyBtn = document.getElementById('mr_files_any_btn');
                            const camBtn = document.getElementById('mr_files_camera_btn');
                            const preview = document.getElementById('mr_files_preview');
                            const list = document.getElementById('mr_files_list');
                            if (!anyInput || !camInput || !anyBtn || !camBtn || !preview || !list) return;

                            let filesBag = [];
                            const keyOf = (f) => `${f.name}|${f.size}|${f.type}|${f.lastModified || 0}`;
                            const syncToInputs = () => {
                                const dt = new DataTransfer();
                                filesBag.forEach(f => dt.items.add(f));
                                anyInput.files = dt.files;
                            };
                            const escapeHtml = (str) => String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g,
                                '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                            const render = () => {
                                list.innerHTML = '';
                                if (!filesBag.length) {
                                    preview.classList.add('hidden');
                                    return;
                                }
                                preview.classList.remove('hidden');
                                filesBag.forEach((f, idx) => {
                                    const card = document.createElement('div');
                                    card.className = 'p-3 rounded-lg border border-slate-200 bg-slate-50 space-y-2';

                                    const top = document.createElement('div');
                                    top.className = 'flex items-center justify-between gap-3';

                                    const left = document.createElement('div');
                                    left.className = 'min-w-0';
                                    const name = document.createElement('div');
                                    name.className = 'truncate text-[13px] font-medium text-slate-700';
                                    name.textContent = f.name;
                                    const meta = document.createElement('div');
                                    meta.className = 'text-[11px] text-slate-500';
                                    meta.textContent = (f.type || 'file') + ' • ' + Math.round(f.size / 1024) + ' KB';
                                    left.appendChild(name);
                                    left.appendChild(meta);

                                    const del = document.createElement('button');
                                    del.type = 'button';
                                    del.className = 'shrink-0 text-rose-600 hover:text-rose-700 text-xs font-medium';
                                    del.textContent = 'ลบ';
                                    del.addEventListener('click', () => {
                                        filesBag.splice(idx, 1);
                                        syncToInputs();
                                        render();
                                    });

                                    top.appendChild(left);
                                    top.appendChild(del);
                                    card.appendChild(top);

                                    // Caption Input
                                    const capInput = document.createElement('input');
                                    capInput.type = 'text';
                                    capInput.name = 'captions[]';
                                    capInput.placeholder = 'เพิ่มคำอธิบายภาพ...';
                                    capInput.className =
                                        'w-full h-8 px-2 py-1 text-[12px] rounded border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-100 outline-none transition';
                                    card.appendChild(capInput);

                                    list.appendChild(card);
                                });
                            };
                            const addFiles = (files) => {
                                const arr = Array.from(files || []);
                                if (!arr.length) return;
                                const map = new Map(filesBag.map(f => [keyOf(f), f]));
                                arr.forEach(f => map.set(keyOf(f), f));
                                const currentCount = {{ $atts->count() }};
                                const maxAllowed = Math.max(0, 3 - currentCount);

                                if (map.size > maxAllowed) {
                                    window.dispatchEvent(new CustomEvent('app:toast', {
                                        detail: { type: 'warning', message: 'สามารถแนบไฟล์เพิ่มได้อีก ' + maxAllowed + ' ไฟล์ (สูงสุด 3 ไฟล์ต่อใบงาน)' }
                                    }));
                                }
                                filesBag = Array.from(map.values()).slice(0, maxAllowed);
                                syncToInputs();
                                render();
                            };
                            anyBtn.addEventListener('click', () => anyInput.click());
                            camBtn.addEventListener('click', () => camInput.click());
                            anyInput.addEventListener('change', () => {
                                addFiles(anyInput.files);
                                anyInput.value = '';
                            });
                            camInput.addEventListener('change', () => {
                                addFiles(camInput.files);
                                camInput.value = '';
                            });
                        })();
                    </script>
                @else
                    <div class="rounded-md border {{ $line }} bg-white px-3 py-2 text-sm text-slate-600">
                        คุณไม่มีสิทธิ์แนบไฟล์ในใบงานนี้
                    </div>
                @endcan

                <div class="mt-6 border-t {{ $line }} pt-4">
                    @if ($atts->count())
                        <div class="mb-2 text-sm font-medium text-slate-700">
                            ไฟล์ที่แนบไว้แล้ว ({{ $atts->count() }} ไฟล์)
                        </div>
                        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                            @foreach ($atts as $att)
                                @php
                                    $file = $att->file;
                                    $name = $att->original_name ?? ($file?->path ?? 'file');
                                    $isPrivate = (bool) ($att->is_private ?? false);
                                    $mime = $file?->mime ?? '';
                                    $isImg = $mime && str_starts_with($mime, 'image/');
                                    $publicUrl = null;
                                    if ($file && ($file->disk ?? null) && ($file->path ?? null)) {
                                        try {
                                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                                            $disk = Storage::disk($file->disk);
                                            $publicUrl = $disk->url($file->path);
                                        } catch (\Throwable $e) {
                                            $publicUrl = null;
                                        }
                                    }
                                    $canOpenPrivate = auth()->check() && auth()->user()->can('update', $req);
                                    $canOpen = !$isPrivate || $canOpenPrivate;
                                    $openUrl = $publicUrl;
                                    try {
                                        $openUrl = route('attachments.show', $att);
                                    } catch (\Throwable $e) {
                                        $openUrl = $publicUrl;
                                    }
                                    $deleteUrl = null;
                                    try {
                                        $deleteUrl = route('maintenance.requests.attachments.destroy', [
                                            'maintenanceRequest' => $req->id,
                                            'attachment' => $att->id,
                                        ]);
                                    } catch (\Throwable $e) {
                                        $deleteUrl = null;
                                    }
                                @endphp
                                <figure class="overflow-hidden rounded-lg border {{ $line }} bg-white text-xs">
                                    @if ($isImg && !$isPrivate && $openUrl)
                                        <a href="{{ $openUrl }}" target="_blank" rel="noopener">
                                            <img src="{{ $openUrl }}" alt="{{ $att->alt_text ?? $name }}"
                                                class="h-32 w-full object-cover">
                                        </a>
                                    @else
                                        <div class="grid h-32 w-full place-items-center text-slate-500 text-[13px]">
                                            {{ strtoupper(pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE') }}
                                        </div>
                                    @endif
                                    <figcaption class="px-3 py-2 space-y-2">
                                        <div class="flex items-center justify-between gap-2">
                                            <span
                                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium
                                 {{ $isPrivate ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                                {{ $isPrivate ? 'private' : 'public' }}
                                            </span>
                                            <span class="truncate text-slate-600 text-[12px]"
                                                title="{{ $name }}">{{ $name }}</span>
                                        </div>
                                        @if ($att->caption)
                                            <div class="text-[12px] text-slate-800 font-medium line-clamp-2 italic"
                                                title="{{ $att->caption }}">
                                                "{{ $att->caption }}"
                                            </div>
                                        @endif
                                        <div class="flex items-center justify-between gap-2">
                                            @if ($canOpen && $openUrl)
                                                <a href="{{ $openUrl }}" target="_blank" rel="noopener"
                                                    class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-800 hover:bg-sky-100">
                                                    เปิด
                                                </a>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-md border {{ $line }} bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-500">
                                                    ไม่อนุญาต
                                                </span>
                                            @endif
                                            @can('deleteAttachment', $req)
                                                @if ($deleteUrl)
                                                    <form method="POST" action="{{ $deleteUrl }}"
                                                        onsubmit="return confirm('ยืนยันลบไฟล์แนบนี้?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-700 hover:bg-rose-100">
                                                            ลบ
                                                        </button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">ยังไม่มีไฟล์แนบในใบงานนี้</p>
                    @endif
                </div>
            </section>

            <div class="border-t {{ $line }}"></div>

            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>
                <section>
                    <div class="{{ $headCls }}">
                        <div class="{{ $noCls }}">5</div>
                        <div class="{{ $accentWrap }}">
                            <span class="{{ $accentBar }}"></span>
                            <div class="{{ $titleCls }}">รายงานการปฏิบัติงานและค่าใช้จ่าย <span
                                    class="text-slate-500 text-[13px] font-normal">(บันทึกภายหลังได้)</span></div>
                            <div class="{{ $subCls }}">สำหรับทีมช่าง: ระบุวิธีคิดค่าใช้จ่าย, รพจ.
                                และรายละเอียดประกอบ</div>
                        </div>
                    </div>

                    @can('updateOperationLog', $req)
                        <form method="post" action="{{ $opLogUrl }}" class="space-y-4" novalidate>
                            @csrf
                            <div>
                                <label for="operation_date"
                                    class="block text-sm font-medium text-slate-700">รายการซ่อมสำหรับวันที่</label>
                                <input id="operation_date" type="date" name="operation_date"
                                    value="{{ old('operation_date', optional($opLog?->operation_date)->format('Y-m-d')) }}"
                                    class="{{ $input }}">
                            </div>
                            <div>
                                <div class="block text-sm font-medium text-slate-700">วิธีการปฏิบัติ / การคิดค่าใช้จ่าย</div>
                                @php $method = old('operation_method', $opLog->operation_method ?? null); @endphp
                                <div class="mt-2 space-y-2 text-sm">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="operation_method" value="requisition"
                                            @checked($method === 'requisition')
                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>ตามใบเบิกครุภัณฑ์ / วัสดุ</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="operation_method" value="service_fee"
                                            @checked($method === 'service_fee')
                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>ค่าบริการ / ค่าแรงช่าง</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="radio" name="operation_method" value="other"
                                            @checked($method === 'other')
                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>อื่น ๆ</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label for="property_code" class="block text-sm font-medium text-slate-700">ระบุรพจ.
                                    (รหัสครุภัณฑ์)</label>
                                <input id="property_code" type="text" name="property_code"
                                    value="{{ old('property_code', $opLog->property_code ?? ($assetCode ?? '')) }}"
                                    class="{{ $input }}" placeholder="เช่น 68101068718">
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="require_precheck" value="1" @checked(old('require_precheck', $opLog->require_precheck ?? false))
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                ยืนยันว่าได้แจ้งผู้ใช้งาน / หน่วยงาน และขออนุญาตก่อนปฏิบัติงาน/ปิดเครื่อง
                            </label>
                            <div>
                                <div class="text-sm font-medium text-slate-700">ประเภทงานที่ปฏิบัติ</div>
                                <div class="mt-2 space-y-2 text-sm">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="issue_software" value="1"
                                            @checked(old('issue_software', $opLog->issue_software ?? false))
                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>Software</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="issue_hardware" value="1"
                                            @checked(old('issue_hardware', $opLog->issue_hardware ?? false))
                                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <span>Hardware</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label for="remark" class="block text-sm font-medium text-slate-700">หมายเหตุ /
                                    รายละเอียดประกอบ</label>
                                <textarea id="remark" name="remark" rows="4" class="{{ $textarea }}"
                                    placeholder="เช่น ตรวจเช็คแล้วพบว่า..., ผู้ใช้ทดสอบแล้วเรียบร้อย">{{ old('remark', $opLog->remark ?? '') }}</textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200">
                                    บันทึกรายงานการปฏิบัติงาน
                                </button>
                            </div>
                        </form>
                    @else
                        <div
                            class="rounded-md border {{ $line }} bg-white px-4 py-3 text-sm text-slate-600 shadow-sm italic">
                            <div class="flex items-center gap-2 text-rose-600 font-semibold mb-1">
                                <span class="material-symbols-outlined text-[18px]">lock</span>
                                สิทธิ์การบันทึกรายงานถูกระงับ
                            </div>
                            เนื่องจากใบงานอยู่ในสถานะ "{{ $req->status_label }}"
                            ซึ่งไม่ได้รับอนุญาตให้แก้ไขรายงานการปฏิบัติงานในขณะนี้
                        </div>
                    @endcan

                    @if ($opLog)
                        <p class="mt-3 text-xs text-slate-500">
                            บันทึกล่าสุดโดย {{ $opLog->user?->name ?? 'ไม่ระบุผู้บันทึก' }}
                            เมื่อ {{ $opLog->updated_at?->format('Y-m-d H:i') ?? '-' }}
                        </p>
                    @endif
                </section>

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

                        <div
                            class="rounded-lg border {{ $line }} bg-white max-h-72 overflow-y-auto divide-y divide-slate-200">
                            @if ($workers->isEmpty())
                                <div class="px-4 py-3 text-[13px] text-slate-500">ยังไม่ได้มอบหมายงานให้ทีมช่าง</div>
                            @else
                                @foreach ($workers as $worker)
                                    @php
                                        $assign = $assignments->firstWhere('user_id', $worker->id);
                                        if (
                                            !$assign ||
                                            $assign->status === \App\Models\MaintenanceAssignment::STATUS_CANCELLED
                                        ) {
                                            continue;
                                        }
                                        $isLead = (bool) ($assign->is_lead ?? false);
                                        $avatar = $worker->avatar_thumb_url ?? null;
                                    @endphp
                                    <div class="flex items-center justify-between gap-3 px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div
                                                class="h-9 w-9 flex-shrink-0 overflow-hidden rounded-full border {{ $line }} bg-white">
                                                @if ($avatar)
                                                    <img src="{{ $avatar }}" alt="{{ $worker->name }}"
                                                        class="h-full w-full object-cover">
                                                @else
                                                    <div
                                                        class="grid h-full w-full place-items-center text-[11px] text-slate-500 bg-slate-50">
                                                        —</div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-[14px] font-semibold text-slate-900"
                                                    title="{{ $worker->name }}">
                                                    {{ $worker->name }}
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

                        @can('assign', $req)
                            <div class="flex justify-end">
                                <button type="button" id="openAssignModalBtn"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-[13px] font-semibold
                    text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 shadow-sm transition-all active:scale-95">
                                    <img src="/icon/technical-support.avif" class="w-4 h-4 object-contain brightness-0 invert"
                                        alt="Assign">
                                    มอบหมายทีมช่าง
                                </button>
                            </div>
                        @endcan
                    </div>
                </section>
            </div>
        </div>

        {{-- Assign Modal --}}
        @can('assign', $req)
            <div id="assignModal"
                class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-3 sm:p-4">
                <div
                    class="relative z-[10000] w-full max-w-4xl overflow-hidden rounded-2xl border {{ $line }} bg-white shadow-xl">

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
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ $assignStoreUrl }}">
                        @csrf
                        <input type="hidden" id="assignSuggestRole" value="{{ $suggestRole }}">
                        <input type="hidden" name="update_team_flag" value="1">

                        <div class="grid grid-cols-1 lg:grid-cols-[380px,1fr] lg:h-[65vh]">

                            {{-- Left Sidebar --}}
                            <div
                                class="border-b lg:border-b-0 lg:border-r {{ $line }} bg-slate-50 flex flex-col min-h-0">

                                {{-- Controls --}}
                                <div class="p-5 space-y-4 flex-none">
                                    <div>
                                        <label class="block text-[13px] font-semibold text-slate-700 mb-1.5">ค้นหาชื่อ</label>
                                        <div class="relative">
                                            <span
                                                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="11" cy="11" r="7" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                            </span>
                                            <input id="assignSearch" type="text"
                                                class="w-full rounded-lg border {{ $line }} bg-white pl-9 pr-3 py-2.5 text-[13px]
                                        focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400"
                                                placeholder="พิมพ์ชื่อช่าง...">
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[13px] font-semibold text-slate-700 mb-1.5">กรองตามตำแหน่ง</label>
                                        <select id="assignRoleFilter"
                                            class="w-full rounded-lg border {{ $line }} bg-white px-3 py-2.5 text-[13px]
                                    focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400">
                                            <option value="">— ทั้งหมด —</option>
                                            @foreach ($roleGroupsSorted as $roleCode => $users)
                                                <option value="{{ strtolower((string) $roleCode) }}">
                                                    {{ $roleLabels[$roleCode] ?? ($fallbackRoleLabels[$roleCode] ?? ucfirst((string) $roleCode)) }}
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

                                {{-- Selected List — เติบโตเต็ม sidebar ที่เหลือ --}}
                                <div class="px-5 pb-4 border-t {{ $line }} pt-4 flex flex-col flex-1 min-h-0">
                                    <div class="flex items-center justify-between flex-none mb-2">
                                        <div class="text-[13px] font-semibold text-slate-700">เลือกแล้ว</div>
                                        <div class="text-[13px] text-slate-500" id="assignSelectedMeta">0 คน</div>
                                    </div>
                                    <div id="assignSelectedEmpty" class="text-[13px] text-slate-400 flex-none">
                                        ยังไม่ได้เลือกช่าง
                                    </div>
                                    {{-- แต่ละ item แสดงชื่อเต็ม ไม่ตัด --}}
                                    <div id="assignSelectedList"
                                        class="space-y-1.5 overflow-y-auto pr-1 hidden flex-1 min-h-0"></div>
                                </div>

                                {{-- Footer ปุ่มย้ายมาอยู่ใต้ sidebar --}}
                                <div class="border-t {{ $line }} bg-slate-50 px-5 py-3 flex gap-2">
                                    <button type="button" id="cancelAssignModalBtn"
                                        class="flex-1 py-2.5 text-[13px] font-semibold border {{ $line }} rounded-lg bg-white text-slate-700 hover:bg-slate-100 transition-colors">
                                        ยกเลิก
                                    </button>
                                    <button type="submit"
                                        class="flex-1 py-2.5 text-[13px] font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-200 transition-colors">
                                        บันทึก
                                    </button>
                                </div>

                            </div>

                            {{-- Right List --}}
                            <div class="flex flex-col min-h-0">

                                {{-- Right Header --}}
                                <div
                                    class="px-5 py-3 border-b {{ $line }} bg-white flex items-center justify-between">
                                    <div class="text-[14px] font-semibold text-slate-800">
                                        รายชื่อช่าง
                                        <span id="assignVisibleCount" class="text-slate-500 font-normal">(0)</span>
                                    </div>
                                </div>

                                {{-- Hint --}}
                                <div class="px-5 py-2 border-b {{ $line }} bg-slate-50">
                                    <p class="text-[12px] text-slate-400">ใช้ตัวกรองด้านซ้ายเพื่อค้นหาและเลือกช่างได้รวดเร็ว
                                    </p>
                                </div>

                                {{-- List --}}
                                <div class="flex-1 min-h-0 overflow-y-auto" id="assignListScroll">
                                    @if ($roleGroupsSorted->isEmpty())
                                        <div class="px-5 py-10 text-center text-[14px] text-slate-500">
                                            ไม่พบข้อมูลช่างในระบบ
                                        </div>
                                    @else
                                        @foreach ($roleGroupsSorted as $roleCode => $users)
                                            @php
                                                $roleTitle =
                                                    $roleLabels[$roleCode] ??
                                                    ($fallbackRoleLabels[$roleCode] ?? ucfirst((string) $roleCode));
                                                $roleCount = $users->count();
                                                $roleKey = strtolower((string) $roleCode);
                                            @endphp
                                            <section class="border-b {{ $line }}" data-role-group="1"
                                                data-role-group-code="{{ $roleKey }}">

                                                {{-- Section Header --}}
                                                <div
                                                    class="sticky top-0 z-10 px-5 py-2 bg-slate-100 border-b {{ $line }}">
                                                    <div
                                                        class="text-[12px] font-bold text-slate-600 uppercase tracking-widest">
                                                        {{ $roleTitle }} ({{ $roleCount }})
                                                    </div>
                                                </div>

                                                <div class="divide-y divide-slate-100">
                                                    @foreach ($users as $worker)
                                                        @php
                                                            $roleLabelRow =
                                                                $worker->role_label ??
                                                                ($fallbackRoleLabels[$worker->role ?? 'unknown'] ??
                                                                    ($worker->role ?? 'unknown'));
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
                                                                data-role="{{ (string) $roleCode }}" name="user_ids[]"
                                                                value="{{ $worker->id }}" @checked($workers->contains('id', $worker->id))>

                                                            <div
                                                                class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
                                                                @if ($avatar)
                                                                    <img src="{{ $avatar }}"
                                                                        alt="{{ $worker->name }}"
                                                                        class="h-full w-full object-cover">
                                                                @else
                                                                    <div
                                                                        class="grid h-full w-full place-items-center text-[12px] font-semibold text-slate-500 bg-slate-100 uppercase">
                                                                        {{ mb_substr($worker->name, 0, 1) }}
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="flex-1 min-w-0 truncate">
                                                                <span
                                                                    class="text-[14px] font-semibold text-slate-900">{{ $worker->name }}</span>
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
                    </form>
                </div>
            </div>
        @endcan

        {{-- Hold Modal --}}
        @if ($canHold)
            <div id="holdModal"
                class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
                <div
                    class="relative z-[10000] w-full max-w-xl rounded-2xl border {{ $line }} bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b {{ $line }} px-4 py-3">
                        <div class="text-sm font-semibold text-slate-900">พักชั่วคราว</div>
                        <button type="button" id="closeHoldModalBtn"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('maintenance.requests.hold', $req->id) }}"
                        class="px-4 py-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700">ระบุเหตุผลในการพักชั่วคราว <span
                                    class="text-amber-600">*</span></label>
                            <textarea name="note" rows="4" required
                                class="mt-2 w-full rounded-md border {{ $line }} bg-white px-3 py-2 text-sm
                           focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                placeholder="เช่น รออะไหล่, รอเบิกเครื่องมือ, หรือเหตุผลอื่น ๆ..."></textarea>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="cancelHoldModalBtn"
                                class="px-3 py-2 text-xs border {{ $line }} rounded-md bg-white hover:bg-slate-50">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-3 py-2 text-xs bg-amber-600 text-white rounded-md hover:bg-amber-700 focus:ring-2 focus:ring-amber-200">
                                ยืนยันการพักชั่วคราว
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Post-Close Action Modal --}}
        @if (session('show_post_close_modal'))
            <div id="postCloseModal"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-4">
                <div
                    class="relative z-[10000] w-full max-w-md transform transition-all animate-in fade-in zoom-in duration-300">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                        {{-- Icon Header --}}
                        <div class="bg-slate-50 px-6 py-8 text-center border-b border-slate-100">
                            <div
                                class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-4 shadow-inner">
                                <span class="material-symbols-outlined text-[48px]">check_circle</span>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">ปิดงานเรียบร้อยแล้ว!</h3>
                            <p class="mt-2 text-sm text-slate-500">ขอบคุณที่ใช้บริการครับ คุณต้องการดำเนินการอย่างไรต่อ?
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="p-6 space-y-3">
                            @can('rate', $req)
                                <a href="{{ route('maintenance.ratings.create', $req->id) }}"
                                    class="flex w-full items-center justify-center gap-3 rounded-2xl bg-amber-500 py-4 text-[15px] font-bold text-white shadow-lg shadow-amber-200 hover:bg-amber-600 hover:scale-[1.02] active:scale-[0.98] transition-all">
                                    <span class="material-symbols-outlined text-[20px]">star</span>
                                    ประเมินความพึงพอใจ
                                </a>
                            @endcan

                            <button type="button"
                                onclick="document.getElementById('postCloseModal').remove(); document.body.style.overflow = '';"
                                class="flex w-full items-center justify-center gap-3 rounded-2xl bg-white border-2 border-slate-100 py-4 text-[15px] font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-200 active:scale-[0.98] transition-all">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                                ดูรายละเอียดใบงาน
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.body.style.overflow = 'hidden';
            </script>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            'use strict';

            // --- 1. ประกาศตัวแปร (Selectors) ---
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

                // --- 2. ฟังก์ชันช่วยงาน (Helper Functions) ---

                const getAllRows = () => Array.from(modal.querySelectorAll('.assign-user-row'));
                const getAllCheckboxes = () => Array.from(modal.querySelectorAll('.assign-user-checkbox'));

                // ดึงเฉพาะ Checkbox ของแถวที่กำลังแสดงอยู่ (ไม่โดน Filter ซ่อน)
                const getVisibleCheckboxes = () => getAllRows()
                    .filter(row => row.style.display !== 'none')
                    .map(row => row.querySelector('.assign-user-checkbox'))
                    .filter(Boolean);

                // ป้องกัน XSS
                const escapeHtml = (str) => String(str || '')
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

                // ทำอักษรย่อ (เช่น "สมชาย ดีใจ" -> "ส ด")
                const initials = (name) => {
                    const s = (name || '').trim();
                    if (!s) return '—';
                    const parts = s.split(/\s+/).filter(Boolean);
                    const a = (parts[0] || '').charAt(0);
                    const b = parts.length > 1 ? (parts[parts.length - 1] || '').charAt(0) : '';
                    return (a + b).toUpperCase();
                };

                // --- 3. ฟังก์ชันหลักของระบบ (Core Logic) ---

                // อัปเดตรายชื่อคนที่ถูกเลือกใน Sidebar ด้านซ้าย
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
                        item.className =
                            'flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 animate-in fade-in slide-in-from-left-2 duration-200';
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

                // ระบบกรองชื่อและตำแหน่ง
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

                    // ซ่อนหัวข้อกลุ่ม (Group Header) ถ้าไม่มีลูกทีมในกลุ่มนั้นแสดงอยู่เลย
                    modal.querySelectorAll('[data-role-group]').forEach(group => {
                        const inner = Array.from(group.querySelectorAll('.assign-user-row'));
                        group.style.display = inner.some(x => x.style.display !== 'none') ? '' : 'none';
                    });

                    updateCounts();
                }

                // --- 4. ควบคุมการแสดงผล Modal ---

                function showModal() {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden'; // ล็อค Scroll พื้นหลัง

                    // ถ้ามี Suggest Role ให้เลือกให้ตั้งต้นไว้เลย
                    if (roleFilter && suggestRole && !roleFilter.value) {
                        const hasOption = Array.from(roleFilter.options)
                            .some(opt => (opt.value || '').toLowerCase() === suggestRole);
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

                // --- 5. Event Listeners ---

                open.addEventListener('click', showModal);
                close?.addEventListener('click', hideModal);
                cancel?.addEventListener('click', hideModal);

                // คลิกพื้นหลังเทาๆ ให้ปิด Modal
                modal.addEventListener('click', e => {
                    if (e.target === modal) hideModal();
                });

                // ปุ่มเลือกทั้งหมด (เฉพาะที่เห็นอยู่)
                selectAllBtn?.addEventListener('click', () => {
                    getVisibleCheckboxes().forEach(cb => cb.checked = true);
                    updateCounts();
                });

                // ล้างที่เลือกไว้ทั้งหมด
                clearAllBtn?.addEventListener('click', () => {
                    getAllCheckboxes().forEach(cb => cb.checked = false);
                    updateCounts();
                });

                searchInput?.addEventListener('input', applyFilter);
                roleFilter?.addEventListener('change', applyFilter);

                // เมื่อมีการติ๊ก Checkbox ในลิสต์
                modal.addEventListener('change', e => {
                    if (e.target?.classList?.contains('assign-user-checkbox')) updateCounts();
                });

                // ปุ่มลบออกจาก Sidebar (ตัว Chip)
                selectedListEl?.addEventListener('click', e => {
                    const btn = e.target.closest('.assign-chip-remove');
                    if (!btn) return;
                    const userId = btn.dataset.userId;
                    const cb = modal.querySelector(`.assign-user-checkbox[value="${CSS.escape(userId)}"]`);
                    if (cb) cb.checked = false;
                    updateCounts();
                });

                // จัดการตอนกด Submit
                assignForm?.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="animate-spin mr-2">◌</span> กำลังบันทึก...';
                    }
                    // ปล่อยให้ Form ส่งข้อมูลแบบ Standard POST ไปยัง Action URL
                });

                // รันครั้งแรกเพื่อตั้งค่าเริ่มต้น
                updateCounts();
            }

            // --- 6. Reject Modal Logic (หน้าต่างปฏิเสธงาน) ---
            const rejectModal = document.getElementById('rejectModal');
            const openRejectBtn = document.getElementById('openRejectModalBtn');
            const closeRejectBtn = document.getElementById('closeRejectModalBtn');
            const cancelRejectBtn = document.getElementById('cancelRejectModalBtn');

            if (rejectModal && openRejectBtn) {
                const showReject = () => {
                    rejectModal.classList.remove('hidden');
                    rejectModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const hideReject = () => {
                    rejectModal.classList.add('hidden');
                    rejectModal.classList.remove('flex');
                    document.body.style.overflow = '';
                };
                openRejectBtn.addEventListener('click', showReject);
                closeRejectBtn?.addEventListener('click', hideReject);
                cancelRejectBtn?.addEventListener('click', hideReject);
                rejectModal.addEventListener('click', e => {
                    if (e.target === rejectModal) hideReject();
                });
            }

            // --- 7. Hold Modal Logic ---
            const holdModal = document.getElementById('holdModal');
            const openHoldBtn = document.getElementById('openHoldModalBtn');
            const closeHoldBtn = document.getElementById('closeHoldModalBtn');
            const cancelHoldBtn = document.getElementById('cancelHoldModalBtn');

            if (holdModal && openHoldBtn) {
                const showHold = () => {
                    holdModal.classList.remove('hidden');
                    holdModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const hideHold = () => {
                    holdModal.classList.add('hidden');
                    holdModal.classList.remove('flex');
                    document.body.style.overflow = '';
                };
                openHoldBtn.addEventListener('click', showHold);
                closeHoldBtn?.addEventListener('click', hideHold);
                cancelHoldBtn?.addEventListener('click', hideHold);
                holdModal.addEventListener('click', e => {
                    if (e.target === holdModal) hideHold();
                });
            }
        })();
    </script>
@endpush
