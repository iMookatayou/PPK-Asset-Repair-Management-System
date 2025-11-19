{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.app')
@section('title','สร้างผู้ใช้ใหม่')

@section('page-header')
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Create User
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            ระบุข้อมูลผู้ใช้และกำหนดบทบาทให้ถูกต้อง
          </p>
        </div>

        <a href="{{ route('admin.users.index') }}"
           class="maint-btn maint-btn-outline">
          <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back
        </a>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

    @if ($errors->any())
      <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
        <p class="font-medium">มีข้อผิดพลาดในการบันทึกข้อมูล:</p>
        <ul class="mt-2 list-disc pl-5 text-sm">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST"
          action="{{ route('admin.users.store') }}"
          class="maint-form rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6"
          novalidate
          aria-label="แบบฟอร์มสร้างผู้ใช้ใหม่">
      @csrf

      @include('admin.users._form', [
          'user'        => null,
          'roles'       => $roles,
          'departments' => $departments,
      ])

      <div class="mt-2 flex justify-end gap-2">
        <a href="{{ route('admin.users.index') }}"
           class="maint-btn maint-btn-outline">
          ยกเลิก
        </a>
        <button type="submit"
                class="maint-btn maint-btn-primary">
          บันทึก
        </button>
      </div>
    </form>
  </div>
@endsection

<style>
  .maint-form input[type="text"],
  .maint-form input[type="email"],
  .maint-form input[type="password"],
  .maint-form input[type="date"],
  .maint-form input[type="number"],
  .maint-form select:not([multiple]) {
    height: 44px;
    border-radius: 0.75rem;
    box-sizing: border-box;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
  }
  .maint-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 1rem;
    height: 44px;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 500;
    border: 1px solid rgb(148,163,184);
    background-color: #ffffff;
    color: rgb(51,65,85);
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    text-decoration: none;
    gap: 0.25rem;
  }

  .maint-btn svg {
    flex-shrink: 0;
  }

  .maint-btn:hover {
    background-color: rgb(248,250,252);
  }

  .maint-btn-outline { }

  .maint-btn-primary {
    border-color: rgb(5,150,105);
    background-color: rgb(5,150,105);
    color: #ffffff;
  }

  .maint-btn-primary:hover {
    background-color: rgb(4,120,87);
    border-color: rgb(4,120,87);
  }
</style>
