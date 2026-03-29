{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'แก้ไขผู้ใช้ #'.$user->id)

@php
    // Logic ตัวอักษรย่อ 2 ตัว
    $getInitials = function($name) {
        $name = trim((string)$name);
        $parts = preg_split('/\s+/u', $name) ?: [];
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $second = mb_substr($parts[1] ?? '', 0, 1);
        return strtoupper($first . $second);
    };
@endphp

@section('page-header')
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">

        {{-- Title & Avatar --}}
        <div class="flex items-start gap-3">
          {{-- Avatar Section: ดึงรูปจริงมาโชว์ ถ้าไม่มีโชว์ตัวย่อวงกลม --}}
          <div class="mt-1 h-12 w-12 shrink-0 overflow-hidden rounded-full border-2 border-white shadow-sm bg-emerald-600">
            @if($user->avatar_url)
              <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
            @else
              <div class="flex h-full w-full items-center justify-center text-white text-sm font-bold uppercase">
                {{ $getInitials($user->name) }}
              </div>
            @endif
          </div>

          <div>
            <h1 class="text-xl font-semibold text-slate-900 flex items-center gap-2">
               Edit User
               <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700 border border-slate-200">
                 #{{ $user->id }}
               </span>
            </h1>
            <p class="mt-1 text-sm text-slate-600">
               แก้ไขข้อมูลบัญชีของ <span class="font-semibold text-slate-800">{{ $user->name }}</span>
            </p>
          </div>
        </div>

        {{-- Back Button --}}
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900">
          <svg class="mr-2 h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
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

    {{-- Form Tag: Action ไปที่ Update, Method PUT --}}
    <form method="POST"
          action="{{ route('admin.users.update', $user) }}"
          class="maint-form space-y-8"
          novalidate
          autocomplete="off">
      @csrf
      @method('PUT')

      {{-- Include Form: เรียกใช้ Input fields ชุดเดียวกับ Create --}}
      @include('admin.users._form', [
          'user'        => $user,
          'roles'       => $roles,
          'roleLabels'  => $roleLabels ?? \App\Models\User::roleLabels(),
          'departments' => $departments,
      ])

      {{-- Action Buttons --}}
      <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
        <a href="{{ route('admin.users.index') }}"
           class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-all">
            ยกเลิก
        </a>

        <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white shadow-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
            อัปเดตข้อมูลผู้ใช้
        </button>
      </div>
    </form>

    {{-- Danger Zone --}}
    <div class="mt-16 rounded-xl border border-rose-100 bg-rose-50/50 p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h3 class="text-base font-semibold text-rose-700 flex items-center gap-2">
             <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
             </svg>
             ลบผู้ใช้
          </h3>
          <p class="mt-1 text-sm text-rose-600">
            การลบผู้ใช้จะไม่สามารถกู้คืนข้อมูลได้ โปรดตรวจสอบให้แน่ใจก่อนดำเนินการ
          </p>
        </div>

        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
              onsubmit="return confirm('ยืนยันการลบผู้ใช้ {{ $user->name }} ? \nการกระทำนี้ไม่สามารถย้อนกลับได้');">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-700 shadow-sm hover:bg-rose-50 hover:border-rose-300 hover:text-rose-800 transition-all">
             <svg class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/>
             </svg>
             ลบผู้ใช้
          </button>
        </form>
      </div>
    </div>

  </div>
@endsection
