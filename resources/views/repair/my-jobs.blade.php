@extends('layouts.app')
@section('title', 'My Jobs')

@section('content')
    @php
        use Illuminate\Support\Str;

        $q = $q ?? request('q');
        $status = $status ?? request('status');
        $filter = $filter ?? (request('filter') ?: 'all');
        $tech = $tech ?? request('tech');
        $resp = $resp ?? request('resp');

        $myJobsRoute = 'repairs.my_jobs';

        $filterLabels = [
            'my' => 'งานของฉัน',
            'available' => 'งานว่าง',
            'all' => 'ทั้งหมด',
        ];

        $statusLabel = fn(?string $s) => [
            'pending' => 'รอรับทราบ',
            'acknowledged' => 'รับทราบแล้ว',
            'accepted' => 'รับเรื่องแล้ว',
            'in_progress' => 'กำลังดำเนินการ',
            'on_hold' => 'พักชั่วคราว',
            'resolved' => 'แก้ไขเสร็จสิ้น',
            'closed' => 'ปิดงาน',
            'cancelled' => 'ยกเลิกซ่อม',
            'rejected' => 'ไม่รับเรื่อง',
        ][strtolower((string) $s)] ?? 'ไม่ระบุ';

        $respLabel = fn(?string $s) => [
            'pending' => 'ยังไม่ตอบรับ',
            'accepted' => 'รับเรื่อง',
            'rejected' => 'ไม่รับเรื่อง',
            'acknowledged' => 'รับทราบแล้ว',
        ][strtolower((string) $s)] ?? 'ไม่ระบุ';

        $priorityLabel = fn(?string $p) => [
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'เร่งด่วน',
            'urgent' => 'เร่งด่วนมาก',
        ][strtolower((string) $p)] ?? 'ไม่ระบุ';

        $statusDot = fn(?string $s) => match (strtolower((string) $s)) {
            'pending' => 'bg-amber-500',
            'acknowledged' => 'bg-sky-500',
            'accepted' => 'bg-indigo-500',
            'in_progress' => 'bg-sky-500',
            'on_hold' => 'bg-slate-400',
            'resolved' => 'bg-emerald-500',
            'closed' => 'bg-zinc-400',
            'cancelled' => 'bg-rose-500',
            'rejected' => 'bg-rose-600',
            default => 'bg-slate-400',
        };

        $statusAccentColor = fn(?string $s) => match (strtolower((string) $s)) {
            'pending' => '#f59e0b',
            'acknowledged' => '#38bdf8',
            'accepted' => '#6366f1',
            'in_progress' => '#3b82f6',
            'on_hold' => '#94a3b8',
            'resolved' => '#10b981',
            'closed' => '#71717a',
            'cancelled' => '#ef4444',
            'rejected' => '#e11d48',
            default => '#cbd5e1',
        };

        $statusBadge = fn(?string $s) => match (strtolower((string) $s)) {
            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
            'acknowledged' => 'bg-sky-50 text-sky-700 border-sky-200',
            'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'in_progress' => 'bg-sky-50 text-sky-700 border-sky-200',
            'on_hold' => 'bg-slate-100 text-slate-600 border-slate-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'closed' => 'bg-zinc-100 text-zinc-600 border-zinc-200',
            'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-100 text-slate-500 border-slate-200',
        };

        $hasActiveFilter =
            ($q ?? '') !== '' ||
            ($status ?? '') !== '' ||
            ($tech ?? '') !== '' ||
            ($filter ?? 'all') !== 'all' ||
            ($resp ?? '') !== '';

        $activeTech = isset($tech) && isset($team) ? $team->firstWhere('id', (int) $tech) : null;

        $statPending = (int) ($stats['pending'] ?? 0);
        $statInProgress = (int) ($stats['in_progress'] ?? 0);
        $statCompleted = (int) ($stats['completed'] ?? 0);

        $getIp = fn($r) => $r->client_ip ?? ($r->ip_address ?? ($r->ip ?? null));
        $getDept = fn($r) => $r->department->name ?? ($r->department->name_th ?? ($r->department->title ?? null));

        $getWorkOrderNo = function ($r) {
            return $r->work_order_no ?? ($r->workOrder->order_no ?? (null ?? ($r->job_no ?? null)));
        };

        $avatarColors = [
            'bg-indigo-500',
            'bg-emerald-500',
            'bg-amber-500',
            'bg-rose-500',
            'bg-sky-500',
            'bg-violet-500',
            'bg-teal-500',
        ];

        $u = auth()->user();
        $uRole = strtolower((string) ($u?->role ?? ''));
        $isAdmin =
            (bool) ($u?->is_admin ?? false) ||
            in_array($uRole, ['admin', 'developer'], true) ||
            (method_exists($u, 'hasRole') && ($u->hasRole('admin') || $u->hasRole('developer')));
    @endphp

    <div class="w-full flex flex-col pb-6 md:pb-8 font-sans text-slate-900" id="myJobsRealtime">

        {{-- Sticky Header --}}
        <div class="sticky top-16 z-20 bg-white border-b border-slate-200">
            <div class="px-4 md:px-6 lg:px-8 py-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-[32px] text-[#0F2D5C] mt-0.5"
                            aria-hidden="true">engineering</span>
                        <div>
                            <h1 class="text-[17px] font-semibold text-slate-900">My Jobs</h1>
                            <p class="text-[13px] text-slate-600">
                                รายการงานซ่อมบำรุงที่ต้องจัดการ
                                @if ($activeTech)
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
                                <div id="donut" class="w-full h-full rounded-full"
                                    style="background: conic-gradient(#e2e8f0 0deg 360deg);"></div>
                                <div class="absolute inset-0 m-auto w-5 h-5 rounded-full bg-white"></div>
                            </div>
                            <span id="donutPct" class="font-bold text-[11px] text-slate-700">0%</span>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route($myJobsRoute) }}"
                    class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end" onsubmit="showLoader()">

                    <div class="md:col-span-4 lg:col-span-3 min-w-0">
                        <label for="q" class="mb-1 block text-[12px] text-slate-600">คำค้นหา</label>
                        <div class="relative">
                            <input id="q" type="text" name="q" value="{{ $q }}"
                                placeholder="ค้นหาเลขใบงาน, เรื่อง, หรือสถานที่..."
                                class="w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 py-2 text-[13px] placeholder:text-slate-400
                                       focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                        d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="md:col-span-4 lg:col-span-2">
                        <label for="filter" class="mb-1 block text-[12px] text-slate-600">ช่วงงาน</label>
                        <select id="filter" name="filter"
                            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                                   focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
                            @foreach ($filterLabels as $key => $label)
                                <option value="{{ $key }}" @selected($filter === $key)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4 lg:col-span-2">
                        <label for="status" class="mb-1 block text-[12px] text-slate-600">สถานะใบงาน</label>
                        <select id="status" name="status"
                            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                                   focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
                            <option value="">ทุกสถานะ</option>
                            @foreach (['pending', 'acknowledged', 'accepted', 'in_progress', 'on_hold', 'resolved', 'closed', 'cancelled', 'rejected'] as $s)
                                <option value="{{ $s }}" @selected($status === $s)>{{ $statusLabel($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-6 lg:col-span-2">
                        <label for="resp" class="mb-1 block text-[12px] text-slate-600">การตอบรับของฉัน</label>
                        <select id="resp" name="resp"
                            class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800
                                   focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35 cursor-pointer">
                            <option value="">ทั้งหมด</option>
                            @foreach (['pending', 'accepted', 'acknowledged', 'rejected'] as $s)
                                <option value="{{ $s }}" @selected(($resp ?? '') === $s)>{{ $respLabel($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-6 lg:col-span-3 flex items-end justify-end gap-2 mt-2 lg:mt-0">
                        @if ($hasActiveFilter)
                            <a href="{{ route($myJobsRoute) }}" onclick="showLoader()"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600
                                       hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/30 transition-transform hover:scale-105"
                                title="ล้างตัวกรอง">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                        <button type="submit"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#0F2D5C] text-white
                                   hover:bg-[#0F2D5C]/90 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/45 transition-transform hover:scale-105 active:scale-95"
                            title="ค้นหา">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>

                    @if ($tech)
                        <input type="hidden" name="tech" value="{{ $tech }}">
                    @endif
                </form>
            </div>
        </div>

        {{-- List Header --}}
        <div class="px-4 md:px-6 lg:px-8 py-2 border-b border-slate-200 bg-slate-50/30">
            <div class="flex items-center justify-between">
                <div class="text-[13px] font-semibold text-slate-800">รายการงาน</div>
                <div class="text-[12px] text-slate-500">ทั้งหมด {{ $list->total() }} รายการ</div>
            </div>
        </div>

        {{-- Job Cards --}}
        <div class="w-full px-4 md:px-6 lg:px-8 relative z-0 mt-4">
            <div class="mx-auto space-y-3 max-w-6xl">

                @forelse($list as $r)
                    @php
                        $ticketNo = $r->request_no ?? ($r->job_no ?? $r->id);
                        $workOrderNo = $getWorkOrderNo($r);
                        $assetName = $r->asset->name ?? null;
                        $assetCode = $r->asset->asset_code ?? null;
                        $deptName = $getDept($r);
                        $location = $r->location_text ?? null;
                        $reporterName = $r->reporter_name ?? ($r->reporter?->name ?? '-');
                        $reporterPhone = $r->reporter_phone ?? null;
                        $createdAtText = optional($r->created_at)->format('d/m/Y H:i');
                        $mrStatus = strtolower((string) ($r->status ?? ''));
                        $mrStatusText = $statusLabel($mrStatus);
                        $priorityText = $priorityLabel($r->priority ?? null);

                        // ดึงทีมช่างจาก assignments
                        $techWorkers = ($r->assignments ?? collect())
                            ->filter(fn($a) => strtolower((string) ($a->status ?? '')) !== 'cancelled')
                            ->map(fn($a) => $a->user)
                            ->filter()
                            ->unique('id')
                            ->values();
                    @endphp

                    <div
                        class="bg-white border border-slate-200 shadow-sm hover:shadow-md transition-shadow rounded-md overflow-hidden flex">
                        {{-- Status accent bar --}}
                        <div class="w-1.5 shrink-0" style="background-color: {{ $statusAccentColor($mrStatus) }}"></div>
                        <div class="flex-1 min-w-0">

                            {{-- Main Row --}}
                            <div class="grid grid-cols-12 items-start divide-x divide-slate-100">

                                {{-- Col 1: Job ID + Status --}}
                                <div class="col-span-3 px-6 py-5">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Job ID
                                    </div>
                                    <div class="text-[16px] font-bold text-[#0F2D5C] font-mono">#{{ $ticketNo }}</div>
                                    @if ($workOrderNo)
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">WO# {{ $workOrderNo }}
                                        </div>
                                    @endif
                                    <div class="mt-2 flex items-center gap-1.5">
                                        <span class="relative inline-flex h-2 w-2 shrink-0">
                                            @if ($mrStatus === 'pending')
                                                <span
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $statusDot($mrStatus) }} opacity-60"></span>
                                            @endif
                                            <span
                                                class="relative inline-flex h-2 w-2 rounded-full {{ $statusDot($mrStatus) }}"></span>
                                        </span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold border {{ $statusBadge($mrStatus) }}">
                                            {{ $mrStatusText }}
                                        </span>
                                    </div>
                                    <div class="mt-1.5">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[11px] font-bold border
                                            {{ match (strtolower((string) ($r->priority ?? ''))) {
                                                'urgent' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'high' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'medium' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                default => 'bg-slate-50 text-slate-500 border-slate-200',
                                            } }}">
                                            {{ $priorityText }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Col 2: Problem Description --}}
                                <div class="col-span-4 px-6 py-5">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
                                        Problem Description</div>
                                    <div class="text-[15px] font-semibold text-slate-800 leading-tight mb-1.5">
                                        {{ $r->title }}</div>
                                    @if ($r->description)
                                        <p class="text-[13px] text-slate-500 leading-relaxed">{{ $r->description }}</p>
                                    @else
                                        <p class="text-[13px] text-slate-400 italic">- ไม่มีรายละเอียด -</p>
                                    @endif
                                    <div class="mt-2 flex items-center gap-3 text-[12px] text-slate-400">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">calendar_month</span>
                                            {{ $createdAtText }}
                                        </span>
                                        @if ($deptName)
                                            <span class="flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">domain</span>
                                                {{ $deptName }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Col 3: Asset Details --}}
                                <div class="col-span-3 px-6 py-5">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Asset
                                        Details</div>
                                    @if ($assetName || $assetCode)
                                        <div class="flex items-start gap-2">
                                            <span
                                                class="material-symbols-outlined text-[17px] text-[#0F2D5C] mt-0.5 shrink-0">inventory_2</span>
                                            <div>
                                                <div class="text-[14px] font-semibold text-slate-800">
                                                    {{ $assetName ?? '-' }}</div>
                                                @if ($assetCode)
                                                    <div class="text-[12px] font-mono text-[#0F2D5C]">{{ $assetCode }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-[13px] text-slate-400 italic">- ไม่ระบุ -</div>
                                    @endif
                                    <div class="mt-2 text-[12px] text-slate-500">
                                        <span class="font-semibold">สถานที่:</span> {{ $location ?? '-' }}
                                    </div>
                                    <div class="mt-2 text-[12px] text-slate-500 space-y-0.5">
                                        <div><span class="font-semibold">ผู้แจ้ง:</span> {{ $reporterName }}</div>
                                        @if ($reporterPhone)
                                            <div><span class="font-semibold">โทร:</span> {{ $reporterPhone }}</div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Col 4: Technician + View Details --}}
                                <div class="col-span-2 px-6 py-5 flex flex-col justify-between">
                                    <div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                            Technician</div>

                                        @if ($techWorkers->isEmpty())
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                                    <span
                                                        class="material-symbols-outlined text-[15px] text-slate-400">person</span>
                                                </div>
                                                <span class="text-[11px] text-slate-400 italic">ยังไม่ได้มอบหมาย</span>
                                            </div>
                                        @else
                                            {{-- Avatar Stack --}}
                                            <div class="flex items-center">
                                                @foreach ($techWorkers->take(3) as $i => $w)
                                                    @php
                                                        $wAvatar = $w->avatar_thumb_url ?? null;
                                                        $ci = abs(crc32($w->name ?? '')) % count($avatarColors);
                                                    @endphp
                                                    <div class="w-8 h-8 rounded-full border-2 border-white shrink-0 overflow-hidden {{ $i > 0 ? '-ml-2' : '' }}"
                                                        title="{{ $w->name }}">
                                                        @if ($wAvatar)
                                                            <img src="{{ $wAvatar }}" alt="{{ $w->name }}"
                                                                class="w-full h-full object-cover">
                                                        @else
                                                            <div
                                                                class="w-full h-full {{ $avatarColors[$ci] }} flex items-center justify-center text-white text-[10px] font-bold">
                                                                {{ mb_strtoupper(mb_substr($w->name ?? '?', 0, 2)) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                @if ($techWorkers->count() > 3)
                                                    <div
                                                        class="w-8 h-8 rounded-full border-2 border-white bg-slate-200 -ml-2 flex items-center justify-center shrink-0">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-600">+{{ $techWorkers->count() - 3 }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- รายชื่อช่างทั้งหมด --}}
                                            <div class="mt-1.5 space-y-0.5">
                                                @foreach ($techWorkers as $w)
                                                    <div class="text-[12px] font-semibold text-slate-700 leading-tight">
                                                        {{ $w->name }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- View Details --}}
                                    <div class="mt-4">
                                        <a href="{{ route('maintenance.requests.show', $r) }}" onclick="showLoader()"
                                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#0F2D5C] text-white text-[13px] font-semibold hover:bg-[#0a2045] transition-colors whitespace-nowrap">
                                            <span class="material-symbols-outlined text-[16px] shrink-0">visibility</span>
                                            <span>View Details</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Bar --}}
                            @php $hasActions = false; @endphp
                            @can('acknowledge', $r)
                                @if ($mrStatus === 'pending')
                                    @php $hasActions = true; @endphp
                                @endif
                            @endcan
                            @can('accept', $r)
                                @if ($mrStatus === 'acknowledged')
                                    @php $hasActions = true; @endphp
                                @endif
                            @endcan
                            @can('reject', $r)
                                @if ($mrStatus === 'acknowledged')
                                    @php $hasActions = true; @endphp
                                @endif
                            @endcan

                            @if ($hasActions)
                                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center gap-2">
                                    @can('acknowledge', $r)
                                        @if ($mrStatus === 'pending')
                                            <button type="button"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F2D5C] text-white text-[13px] font-semibold hover:bg-[#0A2045] transition-colors"
                                                onclick="submitAcknowledge('{{ $r->id }}', '{{ $ticketNo }}')">
                                                <span class="material-symbols-outlined text-[16px]">approval_delegation</span>
                                                รับทราบ
                                            </button>
                                        @endif
                                    @endcan
                                    @can('accept', $r)
                                        @if ($mrStatus === 'acknowledged')
                                            <button type="button"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-[#0F2D5C] text-white text-[13px] font-semibold hover:bg-[#0A2045] transition-colors"
                                                onclick="submitAccept('{{ $r->id }}', '{{ $ticketNo }}')">
                                                <span class="material-symbols-outlined text-[16px]">check</span>
                                                รับเรื่อง
                                            </button>
                                        @endif
                                    @endcan
                                    @can('reject', $r)
                                        @if ($mrStatus === 'acknowledged')
                                            <button type="button"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-[13px] font-semibold hover:bg-rose-700 transition-colors"
                                                onclick="submitReject('{{ $r->id }}', '{{ $ticketNo }}')">
                                                <span class="material-symbols-outlined text-[16px]">block</span>
                                                ไม่รับเรื่อง
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            @endif

                            {{-- Hidden Forms --}}
                            <form id="ackForm-{{ $r->id }}" method="POST"
                                action="{{ route('maintenance.requests.acknowledge', $r) }}" class="hidden">@csrf</form>
                            <form id="acceptForm-{{ $r->id }}" method="POST"
                                action="{{ route('maintenance.requests.accept', $r) }}" class="hidden">@csrf</form>
                            <form id="rejectForm-{{ $r->id }}" method="POST"
                                action="{{ route('maintenance.requests.reject', $r) }}" class="hidden">
                                @csrf
                                <input type="hidden" name="remark" id="rejectRemark-{{ $r->id }}"
                                    value="">
                            </form>

                        </div> {{-- end flex-1 --}}
                    </div> {{-- end card --}}

                @empty
                    <div class="bg-white border border-slate-200 rounded-md p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-4 text-sm text-slate-500">ไม่พบรายการงานตามเงื่อนไขที่เลือก</p>
                    </div>
                @endforelse

            </div>

            @if ($list->hasPages())
                <div class="mt-4 mb-6 md:mb-10 lg:mb-12">
                    {{ $list->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <div id="loaderOverlay" class="loader-overlay">
        <div class="loader-spinner"></div>
    </div>
@endsection
