{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Repair Dashboard')

@push('styles')
    @vite(['resources/css/repair/dashboard.css'])
@endpush

@section('content')
    @php
        $toast = session('toast');
        if ($toast) {
            session()->forget('toast');
        }

        $type = $toast['type'] ?? null;
        $message = $toast['message'] ?? null;
        $timeout = (int) ($toast['timeout'] ?? 3200);

        $firstError = isset($errors) ? $errors->first() : null;
        if (!$message && $firstError) {
            $message = $firstError;
            $type = $type ?: 'error';
        }
        if (!$message && session('error')) {
            $message = session('error');
            $type = $type ?: 'error';
        }
        if (!$message && session('status')) {
            $message = session('status');
            $type = $type ?: 'success';
        }

        $monthlyTrend = collect($monthlyTrend ?? []);
        $byAssetType = collect($byAssetType ?? []);
        $byDept = collect($byDept ?? []);
        $recent = collect($recent ?? []);

        $kpi = array_merge(
            [
                'lastMonth' => 0,
                'thisMonth' => 0,
                'thisMonthCompleted' => 0,
                'avgResolveHours' => null,
            ],
            $kpi ?? [],
        );

        $stats = $stats ?? [];

        $intVal = fn($v) => is_numeric($v) ? (int) $v : 0;
        $strVal = fn($v, $def = '') => is_string($v) && $v !== '' ? $v : $def;

        $trendLabels = $monthlyTrend->map(fn($i) => $strVal($i['ym'] ?? ($i->ym ?? '')))->values();
        $trendCounts = $monthlyTrend->map(fn($i) => $intVal($i['cnt'] ?? ($i->cnt ?? 0)))->values();

        $typeLabels = $byAssetType
            ->map(fn($i) => $strVal($i['type'] ?? ($i->type ?? 'Unspecified'), 'Unspecified'))
            ->values();
        $typeCounts = $byAssetType->map(fn($i) => $intVal($i['cnt'] ?? ($i->cnt ?? 0)))->values();

        $deptLabels = $byDept
            ->map(fn($i) => $strVal($i['dept'] ?? ($i->dept ?? 'Unspecified'), 'Unspecified'))
            ->values();
        $deptCounts = $byDept->map(fn($i) => $intVal($i['cnt'] ?? ($i->cnt ?? 0)))->values();

        $totalType = $typeCounts->sum();
        $topTypeLabel = $typeLabels[0] ?? '-';
        $topTypePct = $totalType > 0 ? round((($typeCounts[0] ?? 0) / $totalType) * 100, 1) : 0;



        $filtersActive = request()->hasAny(['status', 'from', 'to']);

        $statusTH = [
            'pending' => 'รอดำเนินการ',
            'acknowledged' => 'รับทราบแล้ว',
            'accepted' => 'รับเรื่องแล้ว',
            'in_progress' => 'กำลังดำเนินการ',
            'on_hold' => 'พักงาน',
            'resolved' => 'เสร็จสิ้น',
            'closed' => 'ปิดงาน',
            'cancelled' => 'ยกเลิก',
        ];

        $statusPill = fn($s) => match ($s) {
            'pending' => 'pill-sky',
            'in_progress' => 'pill-blue',
            'resolved', 'closed' => 'pill-navy',
            'cancelled' => 'pill-muted',
            default => 'pill-muted',
        };
    @endphp

    <div class="dash-page">

        {{-- HERO --}}
        <header class="dash-hero">
            <img class="hero-map" src="{{ asset('images/dashboard/world-map.svg') }}" alt="" aria-hidden="true" />
            <div class="hero-overlay" aria-hidden="true"></div>

            <div class="dash-hero-inner">
                <div class="hero-left">
                    <h1 class="hero-title">Dashboard</h1>
                    <p class="hero-desc">
                        สรุปภาพรวมและสถิติการแจ้งซ่อมทั้งหมด<br>
                        อัปเดตล่าสุด: {{ now()->format('d F Y') }}
                    </p>
                </div>


            </div>
        </header>

        {{-- CONTENT --}}
        <main class="dash-wrap">

            {{-- QUICK ANALYTICS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total Requests -->
                <div
                    class="bg-white p-4 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-[#00275f]">
                            <span class="material-symbols-outlined">analytics</span>
                        </div>
                        <span
                            class="text-[10px] font-bold px-2 py-1 rounded bg-blue-50 text-[#00275f] uppercase tracking-wider">Total</span>
                    </div>
                    <h3 class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                        ใบแจ้งซ่อมทั้งหมด</h3>
                    <p class="text-2xl font-black text-[#00275f]">{{ number_format((int) ($stats['total'] ?? 0)) }}</p>
                </div>

                <!-- This Month -->
                <div
                    class="bg-white p-4 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <span class="material-symbols-outlined">calendar_month</span>
                        </div>
                        <span
                            class="text-[10px] font-bold px-2 py-1 rounded bg-indigo-50 text-indigo-600 uppercase tracking-wider">Month</span>
                    </div>
                    <h3 class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                        แจ้งซ่อมเดือนนี้</h3>
                    <p class="text-2xl font-black text-[#00275f]">{{ number_format((int) $kpi['thisMonth']) }}</p>
                </div>

                <!-- Pending -->
                <div
                    class="bg-white p-4 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined">pending_actions</span>
                        </div>
                        <span
                            class="text-[10px] font-bold px-2 py-1 rounded bg-amber-50 text-amber-600 uppercase tracking-wider">Active</span>
                    </div>
                    <h3 class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">รอดำเนินการ
                    </h3>
                    <p class="text-2xl font-black text-amber-600">{{ number_format((int) ($stats['pending'] ?? 0)) }}</p>
                </div>

                <!-- Completed This Month -->
                <div
                    class="bg-white p-4 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden group hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-[#006c46]">
                            <span class="material-symbols-outlined">task_alt</span>
                        </div>
                        <span
                            class="text-[10px] font-bold px-2 py-1 rounded bg-emerald-50 text-[#006c46] uppercase tracking-wider">Goal</span>
                    </div>
                    <h3 class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                        เสร็จสิ้นเดือนนี้</h3>
                    <p class="text-2xl font-black text-[#006c46]">{{ number_format((int) $kpi['thisMonthCompleted']) }}</p>
                </div>
            </div>

            {{-- FILTERS --}}
            <section id="filtersPanel" class="dash-filter {{ $filtersActive ? '' : 'hidden' }}">
                <form method="GET" class="dash-filter-grid">
                    <div>
                        <label class="dash-label">สถานะ</label>
                        <select name="status" class="dash-input">
                            <option value="">ทั้งหมด</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>เสร็จสิ้นแล้ว
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="dash-label">เริ่มตั้งแต่วันที่</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="dash-input" />
                    </div>

                    <div>
                        <label class="dash-label">ถึงวันที่</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="dash-input" />
                    </div>

                    <div class="dash-filter-actions col-span-full mt-2 flex flex-wrap items-center gap-3">
                        <button type="submit" class="dash-btn primary px-8">กรองข้อมูล</button>
                        <a href="{{ url()->current() }}" class="dash-btn ghost">ล้างค่า</a>
                        
                        <div class="h-6 w-[1px] bg-slate-200 mx-2"></div>
                        
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-tight">ช่วงเวลาลัด:</span>
                        <a href="{{ url()->current() }}?from={{ now()->subMonths(5)->startOfMonth()->format('Y-m-d') }}" 
                           class="text-[11px] font-bold text-indigo-600 hover:underline">6 เดือนล่าสุด</a>
                        <a href="{{ url()->current() }}?from={{ now()->subMonths(11)->startOfMonth()->format('Y-m-d') }}" 
                           class="text-[11px] font-bold text-indigo-600 hover:underline">12 เดือนล่าสุด</a>
                        <a href="{{ url()->current() }}?from={{ now()->startOfYear()->format('Y-m-d') }}" 
                           class="text-[11px] font-bold text-indigo-600 hover:underline">ปีนี้</a>
                    </div>
                </form>
            </section>

            {{-- OVERVIEW --}}
            <section class="dash-section">

                <div class="dash-grid-2">

                    {{-- LEFT: DONUT GROUP --}}
                    <section
                        class="bg-[#ffffff] p-6 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-[1.15rem] font-bold text-[#00275f]"
                                    style="font-family: 'Manrope', 'Sarabun', sans-serif;">สัดส่วนงาน (Distribution)</h3>
                                <p class="text-xs text-slate-500">แยกตามประเภทครุภัณฑ์และสถานะ</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-xl">pie_chart</span>
                            </div>
                        </div>

                        <div class="donut-grid">

                            {{-- Asset Type donut --}}
                            <div class="donut-wrap">
                                <div class="donut-title">ประเภทครุภัณฑ์</div>

                                <div class="chart-box h220 donut-box">
                                    <canvas id="typeDonut" data-labels='@json($typeLabels)'
                                        data-values='@json($typeCounts)'></canvas>
                                </div>

                                <div class="donut-center">
                                    <div class="donut-big">{{ $topTypePct }}%</div>
                                    <div class="donut-sub">{{ $topTypeLabel }}</div>
                                </div>
                            </div>

                            {{-- Status donut --}}
                            <div class="donut-wrap">
                                <div class="donut-title">สถานะการทำงาน</div>

                                <div class="chart-box h220 donut-box">
                                    <canvas id="statusDonut" data-pending="{{ (int) ($stats['pending'] ?? 0) }}"
                                        data-progress="{{ (int) ($stats['inProgress'] ?? 0) }}"
                                        data-completed="{{ (int) ($stats['completed'] ?? 0) }}"
                                        data-cancelled="{{ (int) ($stats['cancelled'] ?? 0) }}"></canvas>
                                </div>

                                <div class="status-metrics-plain">
                                    <div class="plain-metric">
                                        <div class="plain-label">เวลาเฉลี่ยปิดงาน</div>
                                        <div class="plain-value">
                                            {{ $kpi['avgResolveHours'] ? $kpi['avgResolveHours'] . ' h' : '-' }}
                                        </div>
                                    </div>

                                    <div class="plain-metric">
                                        <div class="plain-label">ยกเลิกงาน</div>
                                        <div class="plain-value is-blue">
                                            {{ number_format((int) ($stats['cancelled'] ?? 0)) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>

                    {{-- RIGHT: MONTHLY TREND (Chart.js) --}}
                    <section
                        class="bg-[#ffffff] p-6 rounded-md shadow-sm border border-[#eceef0] flex flex-col relative overflow-hidden">
                        <div class="flex justify-between items-center mb-4 z-10">
                            <div>
                                <h3 class="text-[1.15rem] font-bold text-[#00275f]"
                                    style="font-family: 'Manrope', 'Sarabun', sans-serif;">Repair Volume Trends</h3>
                                <p class="text-xs text-[#444650]">ภาพรวมปริมาณงานแจ้งซ่อมตามช่วงเวลาที่กำหนด</p>
                            </div>
                            <span
                                class="px-3 py-1.5 rounded-md bg-[#f2f4f6] text-[11px] font-bold text-[#00275f]">Monthly</span>
                        </div>

                        @php
                            $trendLabels = $monthlyTrend
                                ->map(
                                    fn($r) => \Carbon\Carbon::createFromFormat('Y-m', $r['ym'])
                                        ->locale('th')
                                        ->isoFormat('MMM YY'),
                                )
                                ->values()
                                ->toArray();
                            $trendValues = $monthlyTrend->pluck('cnt')->values()->toArray();
                        @endphp

                        <div class="chart-box flex-1" style="min-height: 180px;">
                            <canvas id="trendBar" data-labels='@json($trendLabels)'
                                data-values='@json($trendValues)'></canvas>
                        </div>
                    </section>
                </div>

                {{-- Department --}}
                <div class="mt-6">
                    <section
                        class="bg-[#ffffff] p-6 rounded-md shadow-sm border border-[#eceef0] relative overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-[1.15rem] font-bold text-[#00275f]"
                                    style="font-family: 'Manrope', 'Sarabun', sans-serif;">ปริมาณงานแยกตามแผนก (Workload by
                                    Department)</h3>
                                <p class="text-xs text-slate-500">จำนวนงานแจ้งซ่อมแยกตามความรับผิดชอบของซ่อมแต่ละแผนก</p>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <span class="material-symbols-outlined text-xl">account_tree</span>
                            </div>
                        </div>

                        <div class="chart-box h220">
                            <canvas id="deptBar" data-labels='@json($deptLabels)'
                                data-values='@json($deptCounts)'></canvas>
                        </div>
                    </section>
                </div>
            </section>

            {{-- TECHNICIAN WORKLOAD --}}
            @if($techWorkload->isNotEmpty())
            <section class="bg-white rounded-md shadow-sm border border-[#eceef0] overflow-hidden mt-6">
              <div class="flex justify-between items-center px-6 py-4 border-b border-[#eceef0]">
                <div>
                  <h3 class="font-bold text-[#00275f] text-[1.05rem]" style="font-family: 'Manrope','Sarabun',sans-serif;">
                    ภาระงานช่างแต่ละคน
                  </h3>
                  <p class="text-[11px] text-[#747781] mt-0.5">จำนวนงานที่กำลังดำเนินการอยู่ของช่างแต่ละคน</p>
                </div>
                <span class="text-[11px] font-bold text-[#747781] bg-[#f2f4f6] px-2 py-1 rounded">{{ $techWorkload->count() }} คน</span>
              </div>

              {{-- Horizontal scroll row --}}
              <div class="flex gap-5 px-6 py-5 overflow-x-auto" style="scrollbar-width: thin;">
                @foreach($techWorkload as $tech)
                <div class="flex flex-col items-center gap-1.5 flex-shrink-0">
                  {{-- Avatar --}}
                  <div class="relative">
                    <img src="{{ $tech['avatar'] }}" alt="{{ $tech['name'] }}"
                      class="w-12 h-12 rounded-full object-cover shadow-sm border border-[#eceef0]" />
                    <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black text-white shadow bg-[#00275f]">
                      {{ $tech['total'] }}
                    </span>
                  </div>
                  {{-- Full Name --}}
                  <span class="text-[10px] font-medium text-center text-[#444650] leading-tight whitespace-nowrap">{{ $tech['name'] }}</span>
                  <span class="text-[10px] font-bold text-[#00275f]">{{ $tech['total'] }} งาน</span>
                </div>
                @endforeach
              </div>
            </section>
            @endif



        </main>
    </div>

    <div class="ui-toast-overlay" aria-live="polite" aria-atomic="true"></div>

    <script>
        window.__DASH__ = {
            message: @json($message),
            type: @json($type),
            timeout: @json($timeout),
        };
    </script>

    @vite(['resources/js/repair/dashboard.js'])
@endsection
