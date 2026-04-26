@extends('layouts.app')
@section('title', 'สร้างผู้ใช้ใหม่')

@section('page-header')
    <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-start justify-between gap-4">

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Create User
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        ระบุข้อมูลผู้ใช้ เลขบัตรประชาชน และกำหนดบทบาทให้ถูกต้อง
                    </p>
                </div>

                {{-- ปุ่มย้อนกลับ (ใส่ไอคอนคืนให้แล้วครับ) --}}
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.users.index') }}"
                    class="inline-flex items-center h-9 gap-2 rounded-md border border-slate-200 bg-white px-4 text-[13px] font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 transition-all">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    กลับ
                </a>

            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

        {{-- Error Display --}}
        @if ($errors->any())
            <div class="mb-8 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
                <p class="font-medium">มีข้อผิดพลาดในการบันทึกข้อมูล:</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.users.store') }}" class="maint-form space-y-8" novalidate
            autocomplete="off">
            @csrf

            {{-- Include Form Fields --}}
            @include('admin.users._form', [
                'user' => null,
                'roles' => $roles,
                'roleLabels' => $roleLabels ?? \App\Models\User::roleLabels(),
                'departments' => $departments,
            ])

            {{-- Action Buttons --}}
            <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 border-t border-slate-200 pt-6">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.users.index') }}"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-all">
                    ยกเลิก
                </a>

                <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
@endsection
