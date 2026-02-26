@extends('layouts.app')

@section('title', 'Technician Ratings')

@php
    $avg = round($technicians->avg('technician_ratings_avg_score'), 2);
    $sumReviews = $technicians->sum('technician_ratings_count');
    $totalTech = $technicians->count();
    $percent = ($avg > 0) ? ($avg / 5) * 100 : 0;

    $chartLabels = $technicians->pluck('name');
    $chartScores = $technicians->pluck('technician_ratings_avg_score')->map(fn($v) => round($v, 2));

    $levelLabel = fn($score) => $score >= 4.5 ? 'ดีมาก' : ($score >= 4.0 ? 'ดี' : ($score >= 3.0 ? 'ปานกลาง' : 'ควรปรับปรุง'));

    $levelTextClass = fn($score) => match(true) {
        $score >= 4.0 => 'text-emerald-700',
        $score >= 3.0 => 'text-amber-700',
        default       => 'text-rose-700',
    };

    // แก้ไขฟังก์ชันสร้างตัวอักษรย่อให้เหมือนหน้า Profile (ตัด 2 ตัวแรก)
    $getInitials = function($name) {
        $name = trim((string)$name);
        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);
        return strtoupper($first . $second);
    };
@endphp

@section('content')
<div class="w-full flex flex-col">

    <div class="sticky top-16 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
        <div class="px-4 md:px-6 lg:px-8 py-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <span class="material-symbols-outlined text-[32px] text-[#0F2D5C] mt-0.5" aria-hidden="true">analytics</span>
                    <div>
                        <h1 class="text-[17px] font-semibold text-slate-900 leading-none">Technician Ratings</h1>
                        <p class="mt-1 text-[13px] text-slate-600">รายงานผลการประเมินและสถิติประสิทธิภาพการทำงานรายบุคคล</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[13px]">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-medium">ช่างทั้งหมด:</span>
                        <span class="font-semibold text-slate-900">{{ $totalTech }}</span>
                    </div>
                    <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
                        <span class="text-slate-500 font-medium">คะแนนเฉลี่ย:</span>
                        <span class="font-semibold text-emerald-700">{{ number_format($avg, 2) }}</span>
                    </div>
                    <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
                        <span class="text-slate-500 font-medium">ประเมินรวม:</span>
                        <span class="font-semibold text-indigo-700">{{ number_format($sumReviews) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                <div class="md:col-span-8 min-w-0">
                    <label for="techSearch" class="mb-1 block text-[12px] text-slate-600">ค้นหาพนักงาน</label>
                    <div class="relative">
                        <input id="techSearch" type="text" placeholder="กรอกชื่อช่างที่ต้องการค้นหา..." class="w-full rounded-md border border-slate-200 bg-white pl-10 pr-3 py-2 text-[13px] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35">
                        <span class="absolute inset-y-0 left-0 flex w-9 items-center justify-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="md:col-span-4">
                    <label for="sortSelector" class="mb-1 block text-[12px] text-slate-600">เรียงลำดับข้อมูล</label>
                    <select id="sortSelector" class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0F2D5C]/35 focus:border-[#0F2D5C]/35">
                        <option value="score_desc" {{ request('sort') == 'score_desc' ? 'selected' : '' }}>คะแนนเฉลี่ยสูงสุด</option>
                        <option value="score_asc" {{ request('sort') == 'score_asc' ? 'selected' : '' }}>คะแนนเฉลี่ยเริ่มต้น</option>
                        <option value="count_desc" {{ request('sort') == 'count_desc' ? 'selected' : '' }}>จำนวนการประเมินสูงสุด</option>
                        <option value="count_asc" {{ request('sort') == 'count_asc' ? 'selected' : '' }}>จำนวนการประเมินเริ่มต้น</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($technicians->count())
    <div class="px-4 md:px-6 lg:px-8 py-6 bg-slate-50/50 border-b border-slate-200">
        <div class="text-[13px] font-semibold text-slate-800 mb-4">สรุปคะแนนประเมินภาพรวม</div>
        <div style="height:280px">
            <canvas id="techRatingChart"></canvas>
        </div>
    </div>
    @endif

    <div class="px-4 md:px-6 lg:px-8 py-2 border-b border-slate-200 bg-white">
        <div class="flex items-center justify-between">
            <div class="text-[13px] font-semibold text-slate-800">รายละเอียดคะแนนรายบุคคล</div>
            <div class="text-[12px] text-slate-500">แสดงผล {{ $totalTech }} รายการ</div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-[13px] border-collapse">
            <thead class="bg-white text-slate-600">
                <tr>
                    <th class="p-3 text-center font-semibold w-[8%] border-b border-slate-200">รูปถ่าย</th>
                    <th class="p-3 text-center font-semibold w-[6%] border-b border-slate-200">ลำดับ</th>
                    <th class="p-3 text-left font-semibold w-[26%] border-b border-slate-200">ชื่อ–สกุลพนักงาน</th>
                    <th class="p-3 text-center font-semibold w-[10%] border-b border-slate-200">คะแนนเฉลี่ย</th>
                    <th class="p-3 text-center font-semibold w-[18%] border-b border-slate-200">รูปแบบดาว</th>
                    <th class="p-3 text-center font-semibold w-[10%] border-b border-slate-200">จำนวนครั้ง</th>
                    <th class="p-3 text-center font-semibold w-[10%] border-b border-slate-200">ระดับ</th>
                    <th class="p-3 text-center font-semibold border-b border-slate-200">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($technicians as $i => $t)
                    @php
                        $avgScore = round($t->technician_ratings_avg_score, 2);
                        $roundStar = round($avgScore);

                        // ดึง Avatar Logic ให้เหมือนหน้า Profile
                        $avatarMain  = data_get($t, 'avatar_url');
                        $avatarThumb = data_get($t, 'avatar_thumb_url');
                    @endphp
                    <tr class="align-top border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                        <td class="p-3 align-middle text-center">
                            <div class="flex justify-center">
                                @if($avatarThumb || $avatarMain)
                                    <img src="{{ $avatarThumb ?: $avatarMain }}" alt="{{ $t->name }}" class="h-9 w-9 rounded-full object-cover border border-slate-200 shadow-sm">
                                @else
                                    {{-- Default Avatar สีเขียว (Logic เดียวกับ Profile) --}}
                                    <div class="h-9 w-9 rounded-full bg-emerald-600 flex items-center justify-center border border-emerald-700 shadow-sm">
                                        <span class="text-white text-[13px] font-bold leading-none">
                                            {{ $getInitials($t->name) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="p-3 align-middle text-center text-slate-500 font-medium">{{ $i+1 }}</td>
                        <td class="p-3 align-middle">
                            <span class="font-semibold text-slate-900">{{ $t->name }}</span>
                        </td>
                        <td class="p-3 align-middle text-center font-bold text-slate-900">
                            {{ number_format($avgScore, 2) }}
                        </td>
                        <td class="p-3 align-middle text-center">
                            <div class="flex justify-center items-center gap-0.5">
                                @for($s=1; $s<=5; $s++)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $s <= $roundStar ? 'text-yellow-400' : 'text-slate-200' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                        </td>
                        <td class="p-3 align-middle text-center text-slate-700">
                            {{ number_format($t->technician_ratings_count) }}
                        </td>
                        <td class="p-3 align-middle text-center">
                            <span class="font-semibold {{ $levelTextClass($avgScore) }}">
                                {{ $levelLabel($avgScore) }}
                            </span>
                        </td>
                        <td class="p-3 align-middle text-center">
                            <a href="#" class="inline-flex items-center gap-1.5 rounded-md border border-indigo-300 bg-white px-3 py-1.5 font-medium text-indigo-700 hover:bg-indigo-50 justify-center min-w-[110px]" onclick="showLoader()">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                                </svg>
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-16 text-center text-slate-500">ไม่พบข้อมูลคะแนนการประเมินในระบบ</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="loaderOverlay" class="loader-overlay">
    <div class="loader-spinner"></div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .loader-overlay{position:fixed;inset:0;background:rgba(255,255,255,.6);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:99999;visibility:hidden;opacity:0;transition:opacity .2s,visibility .2s}
    .loader-overlay.show{visibility:visible;opacity:1}
    .loader-spinner{width:38px;height:38px;border:4px solid #0F2D5C;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
</style>

<script>
    function showLoader(){ document.getElementById('loaderOverlay')?.classList.add('show') }
    function hideLoader(){ document.getElementById('loaderOverlay')?.classList.remove('show') }
    document.addEventListener('DOMContentLoaded', hideLoader);

    document.addEventListener("DOMContentLoaded", function () {
        // Sorting
        const sortSel = document.getElementById('sortSelector');
        if(sortSel) {
            sortSel.addEventListener('change', function() {
                showLoader();
                const url = new URL(window.location.href);
                url.searchParams.set('sort', this.value);
                window.location.href = url.toString();
            });
        }

        // Search
        document.getElementById('techSearch').addEventListener('keyup', function() {
            let val = this.value.toUpperCase();
            let rows = document.querySelector("tbody").rows;
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].cells.length < 3) continue; // ข้ามแถว "ไม่พบข้อมูล"
                let name = rows[i].cells[2].textContent.toUpperCase();
                rows[i].style.display = name.indexOf(val) > -1 ? "" : "none";
            }
        });

        // Chart
        const ctx = document.getElementById('techRatingChart');
        if (!ctx) return;
        const dataVals = @json($chartScores);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    data: dataVals,
                    backgroundColor: 'rgba(15, 45, 92, 0.85)',
                    borderRadius: 4,
                    barPercentage: dataVals.length <= 5 ? 0.35 : 0.8,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 5, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0F2D5C', callbacks: { label: (c) => ` คะแนนเฉลี่ย: ${c.parsed.y.toFixed(2)}` } }
                }
            }
        });
    });
</script>
@endsection
