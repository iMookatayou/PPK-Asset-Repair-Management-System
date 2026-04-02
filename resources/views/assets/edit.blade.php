@extends('layouts.app')

@php
    $line = 'border-slate-200';

    $headCls = 'flex items-start gap-3 pb-3 min-h-[56px]';
    $noCls =
        'w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600 flex items-center justify-center text-sm font-bold text-white leading-none';
    $titleCls = 'text-base font-semibold text-slate-900 leading-tight';
    $subCls = 'text-sm text-slate-500 leading-snug';
    $accentWrap = 'min-w-0 relative pl-3 pt-[1px]';
    $accentBar = 'absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90';
@endphp

@section('header-wrap-class', 'no-gap')

@section('title', 'ทะเบียนครุภัณฑ์')

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
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
                                ทะเบียนครุภัณฑ์
                                <span
                                    class="ml-2 text-slate-500 text-[13px] sm:text-[14px] font-semibold">#{{ $asset->id }}</span>
                            </h1>

                            <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                                <span>แก้ไขรายละเอียดครุภัณฑ์</span>
                                @if ($asset->updated_at)
                                    <span>
                                        อัปเดต:
                                        <span
                                            class="font-medium text-slate-900">{{ $asset->updated_at->format('Y-m-d H:i') }}</span>
                                    </span>
                                @endif
                                <span>
                                    รหัส: <span class="font-semibold text-slate-900">{{ $asset->asset_code }}</span>
                                </span>
                                <span class="truncate">
                                    ชื่อ: <span class="font-semibold text-slate-900">{{ $asset->name }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT --}}
                <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                    <a href="{{ route('maintenance.requests.create', ['asset_id' => $asset->id]) }}"
                        class="inline-flex items-center h-9 gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-medium text-white hover:bg-emerald-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        สร้างคำขอซ่อมใหม่
                    </a>
                    <a href="javascript:history.back()"
                        class="inline-flex items-center h-9 gap-2 rounded-lg border {{ $line }} bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        กลับ
                    </a>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('content')
        <form method="POST" action="{{ route('assets.update', $asset) }}" enctype="multipart/form-data" class="space-y-8"
            novalidate>
            @csrf
            @method('PUT')

            @include('assets._form', [
                'asset' => $asset,
                'categories' => $categories ?? collect(),
                'departments' => $departments ?? collect(),
            ])

                    </div>
                </section>
            </div>

            <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 pb-8">
                <div class="flex justify-end gap-2 pt-4 border-t {{ $line }}">
                    <a href="javascript:history.back()"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-lg border {{ $line }} bg-white
                      text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        ยกเลิก
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-700
                           text-sm font-medium text-white hover:bg-emerald-800 focus:ring-2 focus:ring-emerald-200 transition-all">
                        บันทึกการแก้ไขข้อมูล
                    </button>
                </div>
            </div>
        </form>
@endsection
