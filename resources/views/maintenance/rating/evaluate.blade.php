@extends('layouts.app')

@section('title', 'ประเมินความพึงพอใจ')

@section('content')
<div class="w-full flex flex-col min-h-screen bg-white" x-data="{ ratingOpen: false, ratingReq: {} }">

    {{-- Header Section --}}
    <div class="sticky top-16 z-20 bg-white border-b border-slate-200">
        <div class="px-4 md:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[32px] text-[#0F2D5C] mt-0.5">rate_review</span>
                    <div>
                        <h1 class="text-[18px] font-semibold text-slate-900 leading-none">Feedback & Evaluation</h1>
                        <p class="mt-1.5 text-[13px] text-slate-600">จัดการการประเมินผลและแสดงความคิดเห็นต่องานซ่อมบำรุง</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 md:px-6 lg:px-8 py-8 flex flex-col gap-10 max-w-5xl mx-auto w-full">

        {{-- Section: งานที่รอการให้คะแนน --}}
        <section>
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="h-5 w-1.5 bg-amber-500 rounded-full"></div>
                    <h2 class="text-[16px] font-bold text-slate-800">งานที่รอการให้คะแนน</h2>
                </div>
                <div class="text-[13px] text-slate-500">
                    ทั้งหมด {{ $pendingRequests->count() }} รายการ
                </div>
            </div>

            @if ($pendingRequests->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 border-2 border-dashed border-slate-100 rounded-2xl">
                    <span class="material-symbols-outlined text-slate-200 text-[56px] mb-3">fact_check</span>
                    <p class="text-[14px] text-slate-400">ตอนนี้ไม่มีงานที่รอการให้คะแนน</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach ($pendingRequests as $req)
                        <div class="group border border-slate-200 rounded-xl p-5 hover:border-[#0F2D5C]/50 transition-all duration-300 shadow-sm hover:shadow-md">
                            <div class="flex flex-wrap items-start justify-between gap-5">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-[11px] font-black tracking-wider text-[#0F2D5C] uppercase bg-slate-100 px-2 py-0.5 rounded">ID #{{ $req->id }}</span>
                                        <span class="text-[12px] text-slate-400">{{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '' }}</span>
                                    </div>
                                    <h3 class="text-[17px] font-bold text-slate-800 mb-3 group-hover:text-[#0F2D5C] transition-colors">
                                        {{ $req->title ?? 'ไม่ระบุหัวข้อ' }}
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 text-[13px] text-slate-600">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px] text-slate-400">location_on</span>
                                            <span>{{ $req->location ?? '-' }}</span>
                                        </div>
                                        @if ($req->technician)
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px] text-slate-400">engineering</span>
                                                <span class="font-medium">ช่าง: {{ $req->technician->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto self-center">
                                    <a href="{{ route('maintenance.requests.show', $req) }}"
                                       class="w-full sm:w-auto text-center px-4 py-2 text-[13px] font-bold text-slate-500 hover:text-slate-800 transition-colors">
                                        ดูรายละเอียด
                                    </a>
                                    {{-- เปลี่ยนเป็นปุ่มเปิด Modal --}}
                                    <button type="button"
                                        @click="ratingOpen = true; ratingReq = { id: '{{ $req->id }}', title: '{{ $req->title }}', action: '{{ route('maintenance.requests.rating.store', $req) }}' }"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-lg bg-[#0F2D5C] text-white text-[13px] font-bold hover:bg-[#1a3d75] shadow-lg shadow-blue-900/10 transition-all active:scale-95">
                                        <span class="material-symbols-outlined text-[18px]">star</span>
                                        ประเมินงานซ่อม
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Section: งานที่เคยให้คะแนนแล้ว --}}
        <section>
            <div class="flex items-center gap-2 mb-5 pt-2">
                <div class="h-5 w-1.5 bg-slate-300 rounded-full"></div>
                <h2 class="text-[16px] font-bold text-slate-800">ประวัติการประเมิน</h2>
            </div>

            @if ($ratedRequests->isEmpty())
                <div class="py-10 border border-slate-100 rounded-xl text-center">
                    <p class="text-[13px] text-slate-400">ยังไม่มีประวัติการให้คะแนน</p>
                </div>
            @else
                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] border-collapse">
                            <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-4 text-left font-bold uppercase tracking-wider text-[11px]">รายละเอียดงาน</th>
                                    <th class="px-6 py-4 text-center font-bold uppercase tracking-wider text-[11px]">คะแนนที่ได้</th>
                                    <th class="px-6 py-4 text-left font-bold uppercase tracking-wider text-[11px]">ความคิดเห็น</th>
                                    <th class="px-6 py-4 text-center font-bold uppercase tracking-wider text-[11px]">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($ratedRequests as $req)
                                    <tr class="hover:bg-slate-50/40 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800 mb-0.5">#{{ $req->id }}</div>
                                            <div class="text-[12px] text-slate-500 truncate max-w-[220px]">{{ $req->title }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if ($req->rating)
                                                <div class="flex flex-col items-center">
                                                    <div class="flex text-yellow-400 gap-0.5 mb-1">
                                                        @for($i=1; $i<=5; $i++)
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="{{ $i <= $req->rating->score ? 'currentColor' : '#e2e8f0' }}">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        @endfor
                                                    </div>
                                                    <span class="font-bold text-slate-700 text-[11px]">{{ $req->rating->score }}/5</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($req->rating && $req->rating->comment)
                                                <p class="text-slate-600 text-[12px] italic leading-relaxed">
                                                    "{{ \Illuminate\Support\Str::limit($req->rating->comment, 80) }}"
                                                </p>
                                            @else
                                                <span class="text-slate-300 text-[11px] italic">ไม่มีข้อเสนอแนะ</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <a href="{{ route('maintenance.requests.show', $req) }}"
                                               class="inline-flex items-center gap-1.5 text-slate-400 hover:text-[#0F2D5C] font-bold transition-colors">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                <span>ดูงาน</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- Rating Popup Modal --}}
    <div x-show="ratingOpen"
         class="fixed inset-0 z-[9999] overflow-y-auto"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="flex items-center justify-center min-h-screen p-4">
            {{-- Backdrop --}}
           <div class="fixed inset-0 bg-slate-900/60" @click="ratingOpen = false"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden transform transition-all"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500">grade</span>
                        <h3 class="text-[16px] font-bold text-slate-800">ประเมินความพึงพอใจ #<span x-text="ratingReq.id"></span></h3>
                    </div>
                    <button @click="ratingOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Form --}}
                <form :action="ratingReq.action" method="POST" class="p-6">
                    @csrf
                    <div class="mb-5">
                        <p class="text-[12px] text-slate-500 mb-1">หัวข้อการแจ้งซ่อม:</p>
                        <p class="text-[15px] font-semibold text-slate-800" x-text="ratingReq.title"></p>
                    </div>

                    {{-- Star Rating --}}
                    <div class="mb-8">
                        <label class="block text-[14px] font-bold text-slate-700 mb-4 text-center">คุณพึงพอใจกับงานนี้แค่ไหน?</label>
                        <div class="flex flex-row-reverse justify-center gap-2">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" id="star{{ $i }}" name="score" value="{{ $i }}" class="hidden peer" required>
                                <label for="star{{ $i }}" class="cursor-pointer text-slate-200 hover:text-amber-400 peer-checked:text-amber-500 transition-all transform hover:scale-110">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </label>
                            @endfor
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-[13px] font-bold text-slate-700 mb-2">ความคิดเห็นเพิ่มเติม (ถ้ามี)</label>
                        <textarea name="comment" rows="3"
                                  class="w-full rounded-xl border-slate-200 text-[13px] focus:ring-[#0F2D5C] focus:border-[#0F2D5C] placeholder-slate-300"
                                  placeholder="แชร์ประสบการณ์การใช้บริการของคุณ..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="ratingOpen = false"
                                class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold text-[14px] hover:bg-slate-200 transition-all">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-[#0F2D5C] text-white rounded-xl font-bold text-[14px] hover:bg-[#1a3d75] shadow-lg shadow-blue-900/20 transition-all">
                            บันทึกคะแนน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
