@extends('layouts.app')
@section('title', 'Asset Categories')

@section('content')
    <div class="w-full flex flex-col">
        {{-- Sticky Header --}}
        <div class="sticky top-16 z-20 bg-white/90 backdrop-blur border-b border-slate-200">
            <div class="px-4 md:px-6 lg:px-8 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-[17px] font-semibold text-slate-900">หมวดหมู่ทรัพย์สิน</h1>
                        <p class="text-[13px] text-slate-600">จัดการหมวดหมู่และการจัดกลุ่มของทรัพย์สินในระบบ</p>
                    </div>

                    <a href="{{ route('asset-categories.create') }}" onclick="showLoader()"
                        class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-[13px] font-medium text-white hover:bg-emerald-700 transition-colors ">
                        <i class="fa-solid fa-plus"></i> เพิ่มหมวดหมู่
                    </a>
                </div>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="px-4 md:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($categories as $c)
                    <div class="group bg-white border border-slate-200 rounded-xl p-5 hover: hover:border-emerald-200 transition-all duration-300">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                @if($c->color)
                                    <span class="inline-block w-3 h-10 rounded-full " style="background:{{ $c->color }}"></span>
                                @else
                                    <span class="inline-block w-3 h-10 rounded-full bg-slate-100 "></span>
                                @endif
                                <div>
                                    <h2 class="font-bold text-slate-900 text-[15px] group-hover:text-emerald-700 transition-colors">{{ $c->name }}</h2>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $c->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-50 text-slate-400' }}">
                                        {{ $c->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('asset-categories.edit', $c) }}" 
                                    class="p-2 text-slate-400 hover:text-blue-600 transition-colors" 
                                    title="แก้ไข" data-no-loader>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="{{ route('asset-categories.destroy', $c) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition-colors"
                                            title="ลบ"
                                            onclick="return confirm('ยืนยันการลบหมวดหมู่นี้?')">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <p class="text-[13px] text-slate-600 line-clamp-2 min-h-[40px] leading-relaxed">
                            {{ $c->description ?: 'ไม่มีคำอธิบาย' }}
                        </p>

                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                            <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                <i class="fa-solid fa-calendar-day"></i>
                                <span>สร้างเมื่อ {{ $c->created_at?->format('d/m/Y') }}</span>
                            </div>
                            <a href="{{ route('asset-categories.edit', $c) }}" 
                                class="text-[12px] font-semibold text-emerald-600 hover:text-emerald-700 transition-colors"
                                data-no-loader>
                                รายละเอียด
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-slate-50 border border-dashed border-slate-300 rounded-2xl py-20 text-center">
                        <i class="fa-solid fa-folder-open text-slate-300 text-4xl mb-4"></i>
                        <p class="text-slate-500 font-medium">ยังไม่มีข้อมูลหมวดหมู่ในขณะนี้</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8 mb-12">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
@endsection
