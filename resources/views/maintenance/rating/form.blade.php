@extends('layouts.app')

@section('title', 'ให้คะแนนงานซ่อม #' . $req->id)

@section('content')
<style>
    /* Star Rating System */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .star-rating input {
        display: none;
    }
    .star-rating label {
        cursor: pointer;
        color: #d1d5db;
        transition: all 0.2s ease-in-out;
    }
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #f59e0b; 
        transform: scale(1.1);
    }
</style>

<div class="max-w-xl mx-auto py-8 px-4">
    {{-- หัวข้อ --}}
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-gray-800 mb-1">
            ให้คะแนนงานซ่อม #{{ $req->id }}
        </h1>
        <p class="text-sm text-gray-600">
            กรุณาให้คะแนนความพึงพอใจต่อการดำเนินงานและระบุความคิดเห็นเพิ่มเติมหากมี
        </p>
    </div>

    {{-- แสดง error --}}
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold mb-1">ไม่สามารถบันทึกข้อมูลได้</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- กล่องฟอร์ม --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form method="POST" action="{{ route('maintenance.requests.rating.store', $req) }}">
            @csrf

            {{-- สรุปข้อมูลงาน --}}
            <div class="mb-8 border-b border-gray-100 pb-5">
                <div class="grid grid-cols-1 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">งานซ่อมหมายเลข:</span>
                        <span class="font-medium text-gray-800 ml-1">#{{ $req->id }}</span>
                    </div>
                    @if($req->title)
                    <div>
                        <span class="text-gray-500">หัวข้อ:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ $req->title }}</span>
                    </div>
                    @endif
                    @if($req->technician && $req->technician->name)
                    <div class="mt-1">
                        <span class="text-gray-500">ช่างผู้ดูแล:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 ml-1">
                            {{ $req->technician->name }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ส่วนการให้ดาว --}}
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-3">
                    คะแนนความพึงพอใจ <span class="text-red-500">*</span>
                </label>

                <div class="star-rating">
                    @for ($i = 5; $i >= 1; $i--)
                        <input
                            type="radio"
                            id="star{{ $i }}"
                            name="score"
                            value="{{ $i }}"
                            @checked(old('score') == $i)
                            required
                        >
                        <label for="star{{ $i }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </label>
                    @endfor
                </div>

                <div class="flex justify-between w-full max-w-[280px] mt-2 text-[10px] text-gray-400 font-medium uppercase tracking-wider">
                    <span>ควรปรับปรุง</span>
                    <span>ยอดเยี่ยม</span>
                </div>

                @error('score')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ความคิดเห็นเพิ่มเติม --}}
            <div class="mb-8">
                <label for="comment" class="block text-sm font-bold text-gray-700 mb-2">
                    ความคิดเห็นเพิ่มเติม
                </label>
                <textarea
                    id="comment"
                    name="comment"
                    rows="4"
                    class="block w-full rounded-xl border-gray-200 text-sm p-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-300"
                    placeholder="แชร์ความประทับใจ หรือข้อเสนอแนะในการซ่อมครั้งนี้..."
                >{{ old('comment') }}</textarea>
                <p class="mt-2 text-[11px] text-gray-500 italic">
                    * หากให้คะแนน 1-2 ดาว โปรดระบุเหตุผลเพิ่มเติมเพื่อการปรับปรุงบริการ
                </p>
                @error('comment')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ปุ่มดำเนินการ --}}
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <button type="submit"
                        class="w-full sm:w-2/3 inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-lg shadow-blue-200">
                    บันทึกการประเมิน
                </button>

                <a href="{{ route('maintenance.requests.show', $req) }}"
                   class="w-full sm:w-1/3 inline-flex items-center justify-center px-6 py-3.5 rounded-xl border border-gray-200 bg-white text-sm font-bold text-gray-600 hover:bg-gray-50 transition-all">
                    ย้อนกลับ
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
