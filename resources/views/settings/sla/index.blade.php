@extends('layouts.app')

@section('title', 'Settings - SLA')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap');

        .font-manrope {
            font-family: 'Manrope', sans-serif;
        }

        .font-inter {
            font-family: 'Inter', sans-serif;
        }

        .ms-icon {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .ms-icon-filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>

    <div class="w-full flex flex-col font-inter text-[#191c1e]">
        <div class="w-full p-4 md:p-6 max-w-[1664px] mx-auto">

            {{-- Header & Stats --}}
            <header class="flex flex-col gap-4 mb-6">
                <div class="flex justify-between items-end flex-wrap gap-4">
                    <div>
                        <h1 class="text-[17px] md:text-lg font-bold text-[#00275f] font-manrope tracking-tight">SLA Performance
                            Dashboard</h1>
                        <p class="text-[13px] text-[#444650] mt-1">
                            สรุปประสิทธิภาพ@if(request('from') || request('to')) ช่วง {{ request('from') ?? '...' }} ถึง {{ request('to') ?? 'วันนี้' }} @elseในรอบปี {{ now()->year }} @endif
                        </p>
                    </div>
                    <div class="flex gap-2" x-data="{ showSignModal: false }">
                        <button type="button" @click="showSignModal = true"
                            class="px-3 py-1.5 md:px-4 md:py-2 bg-white border border-[#d8dadc] rounded-lg text-[13px] font-bold text-[#00275f] flex items-center gap-1.5 hover:bg-[#f2f4f6] transition-colors shadow-sm">
                            <span class="material-symbols-outlined ms-icon text-[18px]">print</span>
                            รายงานสรุป
                        </button>
                        <button
                            class="px-3 py-1.5 md:px-4 md:py-2 bg-[#00275f] text-white rounded-lg text-[13px] font-bold shadow-md hover:bg-[#1a3d7c] transition-all"
                            onclick="window.location.reload()">
                            <span class="material-symbols-outlined ms-icon text-[16px] align-middle mr-1">refresh</span>
                            ล่าสุด
                        </button>

                        {{-- Signature Modal Teleport --}}

                        <template x-teleport="body">
                            <div x-show="showSignModal"
                                class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md"
                                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                style="display: none;">

                                <div class="bg-[#ffffff] rounded-xl shadow-2xl w-full max-w-lg overflow-hidden"
                                    @click.away="showSignModal = false" x-data="{
                                        pad: null,
                                        initPad() {
                                            const canvas = this.$refs.canvas;
                                            if (!canvas) return;
                                    
                                            if (canvas.offsetWidth === 0) {
                                                setTimeout(() => this.initPad(), 50);
                                                return;
                                            }
                                    
                                            const ratio = Math.max(window.devicePixelRatio || 1, 1);
                                            canvas.width = canvas.offsetWidth * ratio;
                                            canvas.height = canvas.offsetHeight * ratio;
                                            canvas.getContext('2d').scale(ratio, ratio);
                                    
                                            if (typeof SignaturePad === 'undefined') {
                                                console.error('SignaturePad is not defined');
                                                return;
                                            }
                                    
                                            this.pad = new SignaturePad(canvas, {
                                                backgroundColor: 'rgba(255, 255, 255, 0)',
                                                penColor: '#00275f',
                                                minWidth: 1.5,
                                                maxWidth: 4
                                            });
                                        },
                                        clearPad() {
                                            this.pad && this.pad.clear();
                                        },
                                        submitReport() {
                                            if (!this.pad || this.pad.isEmpty()) {
                                                alert('กรุณาลงนามก่อนพิมพ์รายงาน');
                                                return;
                                            }
                                            const dataUrl = this.pad.toDataURL('image/png');
                                            document.getElementById('sig-input').value = dataUrl;
                                            document.getElementById('report-form').submit();
                                            showSignModal = false;
                                        }
                                    }" x-init="$watch('showSignModal', value => { if (value) setTimeout(() => initPad(), 200) })">
                                    <div
                                        class="px-6 py-4 border-b border-[#eceef0] flex items-center justify-between bg-[#f7f9fb]">
                                        <h3 class="font-bold text-[#00275f] font-manrope">ลงนามรับรองรายงาน (Signature)</h3>
                                        <button @click="showSignModal = false"
                                            class="text-[#747781] hover:text-[#444650] transition-colors">
                                            <span class="material-symbols-outlined ms-icon">close</span>
                                        </button>
                                    </div>
                                    <div class="p-6">
                                        <div class="mb-4 text-[13px] text-[#444650] font-medium">
                                            กรุณาลงชื่อในช่องว่างด้านล่างเพื่อใช้ประกอบในรายงาน</div>

                                        <div
                                            class="relative bg-white border-2 border-dashed border-[#c4c6d2] rounded-xl overflow-hidden shadow-inner">
                                            <canvas x-ref="canvas"
                                                class="w-full h-48 cursor-crosshair touch-none bg-white"></canvas>

                                            <div class="absolute bottom-3 right-3 flex gap-2">
                                                <button type="button" @click="clearPad()"
                                                    class="w-8 h-8 flex items-center justify-center bg-white border border-[#c4c6d2] rounded-lg text-[#747781] hover:text-[#ba1a1a] hover:border-[#ffdad6] hover:bg-[#ffdad6]/20 transition-all shadow-sm">
                                                    <span
                                                        class="material-symbols-outlined ms-icon text-[18px]">delete</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-6 flex gap-3">
                                            <button type="button" @click="showSignModal = false"
                                                class="flex-1 px-4 py-2.5 border border-[#c4c6d2] rounded-xl text-[14px] font-bold text-[#444650] hover:bg-[#f2f4f6] transition-colors">
                                                ยกเลิก
                                            </button>
                                            <button type="button" @click="submitReport()"
                                                class="flex-1 px-4 py-2.5 bg-[#00275f] text-white rounded-xl text-[14px] font-bold hover:bg-[#1a3d7c] transition-colors shadow-md">
                                                ยืนยันและพิมพ์รายงาน
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <form id="report-form" action="{{ route('settings.sla.report') }}" method="POST" target="_blank"
                            style="display: none;">
                            @csrf
                            <input type="hidden" name="signature" id="sig-input">
                        </form>
                    </div>
                </div>

                {{-- Period Selector --}}
                <div class="bg-[#f7f9fb] border border-[#eceef0] rounded-xl p-4">
                    <form method="GET" class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-[12px] font-bold text-[#444650]">จาก:</label>
                            <input type="date" name="from" value="{{ request('from') }}"
                                class="text-[13px] border border-[#d8dadc] rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#00275f]/10">
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-[12px] font-bold text-[#444650]">ถึง:</label>
                            <input type="date" name="to" value="{{ request('to') }}"
                                class="text-[13px] border border-[#d8dadc] rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#00275f]/10">
                        </div>
                        <button type="submit"
                            class="px-6 py-1.5 bg-[#00275f] text-white rounded-lg text-[13px] font-bold hover:bg-[#1a3d7c] transition-all shadow-sm">
                            แสดงผล
                        </button>

                        <div class="h-6 w-[1px] bg-[#d8dadc] mx-2 hidden md:block"></div>

                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-bold text-[#747781] uppercase tracking-wider">ลัด:</span>
                            <a href="{{ url()->current() }}?from={{ now()->subMonths(5)->startOfMonth()->format('Y-m-d') }}"
                                class="text-[12px] font-bold text-[#00275f] hover:underline">6 เดือน</a>
                            <a href="{{ url()->current() }}?from={{ now()->subMonths(11)->startOfMonth()->format('Y-m-d') }}"
                                class="text-[12px] font-bold text-[#00275f] hover:underline">12 เดือน</a>
                            <a href="{{ url()->current() }}"
                                class="text-[12px] font-bold text-[#00275f] hover:underline">ปีนี้</a>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Stat 1 -->
                    <div class="bg-white border border-[#eceef0] rounded-md p-4 shadow-sm">
                        <p class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                            เวลาตอบกลับเฉลี่ย</p>
                        <div class="flex items-baseline gap-1">
                            <span
                                class="text-2xl font-extrabold text-[#00275f]">{{ number_format($dashboard['avg_response_hours'], 1) }}<span
                                    class="text-sm font-bold">h</span></span>
                        </div>
                    </div>
                    <!-- Stat 2 -->
                    <div class="bg-white border border-[#eceef0] rounded-md p-4 shadow-sm">
                        <p class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                            เวลารับเรื่องเฉลี่ย</p>
                        <div class="flex items-baseline gap-1">
                            <span
                                class="text-2xl font-extrabold text-[#00275f]">{{ number_format($dashboard['avg_acceptance_hours'], 1) }}<span
                                    class="text-sm font-bold">h</span></span>
                        </div>
                    </div>
                    <!-- Stat 3 -->
                    <div class="bg-white border border-[#eceef0] rounded-md p-4 shadow-sm">
                        <p class="text-[#444650] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                            เวลาแก้ไขเสร็จเฉลี่ย</p>
                        <div class="flex items-baseline gap-1">
                            <span
                                class="text-2xl font-extrabold text-[#00275f]">{{ number_format($dashboard['avg_resolution_hours'], 1) }}<span
                                    class="text-sm font-bold">h</span></span>
                        </div>
                    </div>
                    <!-- Stat 4 -->
                    <div class="bg-white border border-[#eceef0] rounded-md p-4 shadow-sm ring-1 ring-[#006c46]/10">
                        <p class="text-[#006c46] text-[11px] md:text-xs font-bold uppercase tracking-wider mb-1">
                            อัตราการบรรลุ SLA</p>
                        <div class="flex items-baseline gap-1">
                            <span
                                class="text-2xl font-extrabold text-[#006c46]">{{ number_format($dashboard['compliance_rate'], 1) }}%</span>
                            <span
                                class="material-symbols-outlined text-[#006c46] ms-icon-filled text-[18px]">verified</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

                {{-- Main Trend Chart --}}
                <div class="col-span-1 md:col-span-12 lg:col-span-8 p-4 flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-[15px] font-bold text-[#00275f] font-manrope">SLA Performance Trends</h2>
                        <div class="flex gap-3">
                            <span class="flex items-center gap-1.5 text-[11px] font-bold text-[#444650]">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#00275f]"></span> เวลาแก้ไข (ชม.)
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-bold text-[#444650]">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#006c46]"></span> บรรลุ SLA (%)
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 w-full min-h-[240px]">
                        <canvas id="slaTrendChart" data-labels='@json($chartData['trend']['labels'] ?? [])'
                            data-resolution='@json($chartData['trend']['resolution'] ?? [])'
                            data-compliance='@json($chartData['trend']['compliance'] ?? [])'></canvas>
                    </div>
                </div>

                {{-- Right Sidebar Charts --}}
                <div class="col-span-1 md:col-span-12 lg:col-span-4 flex flex-col gap-4 md:flex-row lg:flex-col">
                    <div class="p-4 flex-1 flex flex-col items-center">
                        <h2 class="text-[15px] font-bold text-[#00275f] font-manrope w-full text-left mb-2">
                            @if(request('from') || request('to')) สัดส่วนในช่วงที่เลือก @else สัดส่วนในรอบปี {{ now()->year }} @endif</h2>
                        <div class="w-full h-[180px] flex items-center justify-center">
                            <canvas id="slaDistChart" data-labels='@json($chartData['distribution']['labels'] ?? [])'
                                data-values='@json($chartData['distribution']['data'] ?? [])'></canvas>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h2 class="text-[15px] font-bold text-[#00275f] font-manrope mb-2">งานที่เกินเวลาแยกตามแผนก</h2>
                        <div class="flex-1 w-full min-h-[180px]">
                            <canvas id="slaDeptChart" data-labels='@json($chartData['department']['labels'] ?? [])'
                                data-values='@json($chartData['department']['data'] ?? [])'></canvas>
                        </div>
                    </div>
                </div>

                {{-- Critical Monitoring & SLA Config Matrix --}}
                <div class="col-span-1 md:col-span-12 grid grid-cols-1 xl:grid-cols-2 gap-6 mt-2">

                    {{-- Critical Priority Monitoring --}}
                    <section class="flex flex-col h-[400px]" x-data="{ searchGlobal: '', limit: 20 }">
                        <div
                            class="p-4 border-b border-[#eceef0] flex flex-wrap gap-3 items-center justify-between shrink-0">
                            <h2 class="text-[15px] font-bold text-[#00275f] font-manrope flex items-center gap-1.5">
                                Critical Priority Monitoring
                            </h2>

                            {{-- Search Input --}}
                            <div class="relative w-full sm:w-64"
                                x-show="{{ count($breachedTickets) + count($atRiskTickets) > 0 ? 'true' : 'false' }}">
                                <input type="text" x-model="searchGlobal"
                                    placeholder="ค้นหา เลขใบงาน, เรื่อง, ช่าง..."
                                    class="w-full text-[13px] py-1.5 border border-[#c4c6d2] rounded-lg bg-transparent focus:outline-none focus:ring-2 focus:ring-[#00275f]/20 focus:border-[#00275f]/50"
                                    style="padding-left: 44px !important;">
                                <div
                                    class="absolute left-0 top-0 bottom-0 w-11 flex items-center justify-center pointer-events-none">
                                    <span
                                        class="material-symbols-outlined text-[18px] text-[#747781] ms-icon">search</span>
                                </div>
                            </div>
                        </div>

                        <div class="divide-y divide-[#eceef0] overflow-y-auto max-h-full custom-scrollbar flex-1">
                            @if (count($breachedTickets) == 0 && count($atRiskTickets) == 0)
                                <div
                                    class="p-6 text-center flex flex-col items-center justify-center h-full gap-2 opacity-75">
                                    <span
                                        class="material-symbols-outlined text-4xl text-[#006c46] ms-icon">check_circle</span>
                                    <p class="text-[#444650] font-medium text-[13px]">
                                        ไม่มีงานที่ถึงกำหนดหรือเกินเวลาในขณะนี้</p>
                                </div>
                            @else
                                {{-- Breached Tickets --}}
                                @php $combinedIndex = 0; @endphp
                                @foreach ($breachedTickets as $t)
                                    <div class="p-4 flex items-center justify-between group hover:bg-[#eceef0]/50 transition-colors"
                                        x-show="searchGlobal === '' || 
                                            '{{ strtolower($t->request_no) }}'.includes(searchGlobal.toLowerCase()) || 
                                            '{{ strtolower($t->title) }}'.includes(searchGlobal.toLowerCase()) || 
                                            '{{ strtolower($t->technician?->name ?? '') }}'.includes(searchGlobal.toLowerCase())"
                                        @if ($combinedIndex >= 20) x-cloak x-show="searchGlobal !== ''" @endif>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div
                                                class="w-10 h-10 bg-[#ffdad6] shrink-0 rounded-full flex items-center justify-center text-[#ba1a1a]">
                                                <span class="material-symbols-outlined ms-icon text-[20px]">warning</span>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('maintenance.requests.show', $t->id) }}"
                                                    class="font-bold text-[13px] text-[#00275f] hover:underline truncate block">
                                                    {{ $t->title }}
                                                </a>
                                                <p class="text-[11px] text-[#444650] mt-0.5 truncate">
                                                    #{{ $t->request_no }} • ช่าง:
                                                    {{ $t->technician?->name ?? 'ยังไม่ระบุ' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 ml-3">
                                            <p
                                                class="text-[9px] items-center justify-end font-bold text-[#ba1a1a] uppercase mb-0.5">
                                                เกินเวลาที่กำหนด</p>
                                            <p class="text-[15px] font-black text-[#ba1a1a]">
                                                {{ $t->sla_due_date->format('d/m H:i') }}</p>
                                        </div>
                                    </div>
                                    @php $combinedIndex++; @endphp
                                @endforeach

                                {{-- At Risk Tickets --}}
                                @foreach ($atRiskTickets as $t)
                                    <div class="p-4 flex items-center justify-between group hover:bg-[#eceef0]/50 transition-colors"
                                        x-show="searchGlobal === '' || 
                                            '{{ strtolower($t->request_no) }}'.includes(searchGlobal.toLowerCase()) || 
                                            '{{ strtolower($t->title) }}'.includes(searchGlobal.toLowerCase()) || 
                                            '{{ strtolower($t->technician?->name ?? '') }}'.includes(searchGlobal.toLowerCase())"
                                        @if ($combinedIndex >= 20) x-cloak x-show="searchGlobal !== ''" @endif>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div
                                                class="w-10 h-10 bg-[#d9e2ff] shrink-0 rounded-full flex items-center justify-center text-[#00275f]">
                                                <span class="material-symbols-outlined ms-icon text-[20px]">schedule</span>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('maintenance.requests.show', $t->id) }}"
                                                    class="font-bold text-[13px] text-[#00275f] hover:underline truncate block">
                                                    {{ $t->title }}
                                                </a>
                                                <p class="text-[11px] text-[#444650] mt-0.5 truncate">
                                                    #{{ $t->request_no }} • ช่าง:
                                                    {{ $t->technician?->name ?? 'ยังไม่ระบุ' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 ml-3">
                                            <p
                                                class="text-[9px] items-center justify-end font-bold text-[#00275f] uppercase mb-0.5 flex gap-1 justify-end">
                                                ระวังครบกำหนด</p>
                                            <div class="px-2 py-0.5 bg-[#d9e2ff]/50 rounded-full inline-block">
                                                <p class="text-[15px] font-black text-[#00275f]">
                                                    {{ $t->sla_due_date->format('H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @php $combinedIndex++; @endphp
                                @endforeach

                                @if ($combinedIndex > 20)
                                    <div class="p-3 bg-transparent text-center" x-show="searchGlobal === ''">
                                        <p class="text-[11px] text-[#747781] italic font-medium">แสดงผล 20
                                            รายการล่าสุดจากทั้งหมด {{ $combinedIndex }} รายการ</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </section>

                    {{-- SLA Configuration Matrix --}}
                    <section class="flex flex-col h-[400px] overflow-hidden">
                        <div class="p-4 border-b border-[#eceef0] flex justify-between items-center gap-3 shrink-0">
                            <h2 class="text-[15px] font-bold text-[#00275f] font-manrope">SLA Configuration Matrix</h2>
                        </div>

                        <div class="overflow-y-auto max-h-full custom-scrollbar flex-1">
                            <table class="w-full text-left">
                                <thead
                                    class="bg-transparent text-[9px] font-black uppercase tracking-widest text-[#444650] sticky top-0 z-10 border-b border-[#eceef0]">
                                    <tr>
                                        <th class="px-4 py-3 whitespace-nowrap">Priority Level</th>
                                        <th class="px-4 py-3 whitespace-nowrap text-center">Response Target</th>
                                        <th class="px-4 py-3 whitespace-nowrap text-center">Repair Target</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#eceef0]">
                                    @foreach ($configs as $config)
                                        <tr class="hover:bg-[#eceef0]/30 transition-colors">
                                            <td class="px-4 py-3">
                                                @php
                                                    $pillBg = '#e6e8ea';
                                                    $pillText = '#444650';
                                                    if ($config->priority_level === 'urgent') {
                                                        $pillBg = '#ffdad6';
                                                        $pillText = '#93000a';
                                                    } elseif ($config->priority_level === 'high') {
                                                        $pillBg = '#1a3d7c';
                                                        $pillText = '#ffffff';
                                                    } elseif ($config->priority_level === 'medium') {
                                                        $pillBg = '#e6e8ea';
                                                        $pillText = '#444650';
                                                    }
                                                @endphp
                                                <span
                                                    class="px-2.5 py-1 rounded-full text-[9px] font-extrabold tracking-wider uppercase inline-block whitespace-nowrap border border-[#c4c6d2]/50"
                                                    style="background-color: {{ $pillBg }}; color: {{ $pillText }};">
                                                    {{ $config->name }}
                                                </span>
                                            </td>

                                            <td colspan="3" class="p-0">
                                                <form action="{{ route('settings.sla.update', $config->id) }}"
                                                    method="POST"
                                                    class="grid grid-cols-1 md:grid-cols-3 w-full h-full items-center">
                                                    @csrf
                                                    @method('PUT')

                                                    <div
                                                        class="px-3 py-3 md:border-r border-[#eceef0] flex items-center justify-center gap-2 text-center h-full border-b md:border-b-0">
                                                        <input type="number" name="response_time_minutes"
                                                            value="{{ $config->response_time_minutes }}"
                                                            class="w-16 rounded border border-[#c4c6d2] px-1.5 py-1 text-center text-[13px] font-black text-[#00275f] focus:outline-none focus:ring-1 focus:ring-[#00275f]/30">
                                                        <div class="text-left w-10">
                                                            <span
                                                                class="font-bold text-[#00275f] text-[11px] block leading-none">Min</span>
                                                            <span
                                                                class="text-[9px] text-[#747781] font-medium leading-none mt-1 inline-block">~{{ number_format($config->response_time_minutes / 60, 1) }}h</span>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-3 py-3 md:border-r border-[#eceef0] flex items-center justify-center gap-2 text-center h-full border-b md:border-b-0">
                                                        <input type="number" name="resolution_time_minutes"
                                                            value="{{ $config->resolution_time_minutes }}"
                                                            class="w-16 rounded border border-[#c4c6d2] px-1.5 py-1 text-center text-[13px] font-black text-[#00275f] focus:outline-none focus:ring-1 focus:ring-[#00275f]/30">
                                                        <div class="text-left w-10">
                                                            <span
                                                                class="font-bold text-[#00275f] text-[11px] block leading-none">Min</span>
                                                            <span
                                                                class="text-[9px] text-[#747781] font-medium leading-none mt-1 inline-block">~{{ number_format($config->resolution_time_minutes / 60, 1) }}h</span>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-3 py-3 text-center h-full flex items-center justify-center">
                                                        <button type="submit"
                                                            class="inline-flex items-center justify-center rounded border border-[#d8dadc] shadow-sm px-3 py-1.5 text-[11px] font-bold text-[#00275f] hover:bg-[#e6e8ea] active:scale-95 transition-all w-full md:w-auto mx-auto group">
                                                            <span
                                                                class="material-symbols-outlined ms-icon-filled text-[15px] mr-1 text-[#006c46] group-hover:scale-110 transition-transform">check_circle</span>
                                                            บันทึก
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/settings/sla/dashboard.js'])
@endsection
