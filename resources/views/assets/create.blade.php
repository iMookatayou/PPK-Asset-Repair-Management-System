@extends('layouts.app')

@php
    $line = 'border-slate-200';
@endphp

@section('title', 'เพิ่มทรัพย์สิน')

@push('styles')
    <style>
        /* ให้เนื้อหาหน้า ทะเบียนครุภัณฑ์ ชิดกับ Header */
        .page-create-asset .content {
            padding-top: 0 !important;
            /* ทำให้เนื้อหาชิดกับ Header */
            margin-top: 0 !important;
            /* ปรับให้เนื้อหาชิดสุด */
            position: relative;
            /* บังคับให้เนื้อหาชิดสุด */
            z-index: 1;
            /* ทำให้เนื้อหาขึ้นมาด้านบนสุด */
        }
    </style>
@endpush

@section('page-header')
    <div class="w-full bg-slate-50 border-b {{ $line }}">
        <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                {{-- LEFT --}}
                <div class="min-w-0">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-1 text-emerald-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14m7-7H5" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
                                ทะเบียนครุภัณฑ์
                            </h1>

                            <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                                <span>สร้างครุภัณฑ์ใหม่</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('assets.index') }}"
                        class="inline-flex items-center h-9 gap-2 rounded-md border {{ $line }} bg-white px-4 text-[13px] font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 transition-all">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        กลับ
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('content') <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data"
        data-dirty-check="true" class="space-y-8" novalidate>
        @csrf
        @include('assets._form', [
            'asset' => new \App\Models\Asset(),
            'categories' => $categories ?? collect(),
            'departments' => $departments ?? collect(),
        ])

        <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 pb-10">
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 mt-6 border-t {{ $line }}">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('assets.index') }}"
                    class="inline-flex items-center justify-center gap-2 h-11 px-8 min-w-[180px] rounded-md border {{ $line }} bg-white
                        text-[13px] font-bold text-slate-700 hover:bg-slate-50 transition-all">
                    <span class="material-symbols-outlined text-[17px]">close</span>
                    ยกเลิก
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center overflow-hidden rounded-md bg-emerald-600 text-[13px] font-bold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 transition-all active:scale-95 group h-11 min-w-[180px] shrink-0">
                    <span
                        class="px-2.5 bg-black/10 flex items-center justify-center text-white/90 group-hover:text-white border-r border-white/10 h-full">
                        <span class="material-symbols-outlined text-[17px]">send</span>
                    </span>
                    <span class="px-6 leading-none">
                        บันทึกข้อมูล
                    </span>
                </button>
            </div>
        </div>
    </form>
@endsection
