@extends('layouts.app')
@section('title','โปรไฟล์ของฉัน')

@section('page-header')
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">โปรไฟล์ของฉัน</h1>
    <div class="space-x-2">
      <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-primary">แก้ไขโปรไฟล์</a>
      <a href="{{ route('password.request') }}" class="btn btn-sm btn-outline">เปลี่ยนรหัสผ่าน</a>
    </div>
  </div>
@endsection

@section('content')
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- กล่องข้อมูลผู้ใช้ --}}
    <div class="lg:col-span-1">
      <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <div class="flex items-center gap-4">
          <div class="avatar placeholder">
            <div class="bg-emerald-100 text-emerald-700 rounded-full w-16">
              <span class="text-xl font-semibold">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
            </div>
          </div>
          <div>
            <div class="text-lg font-semibold">{{ $user->name }}</div>
            <div class="text-zinc-500 text-sm">{{ $user->email }}</div>
            @if($user->email_verified_at)
              <div class="badge badge-success badge-sm mt-1">ยืนยันอีเมลแล้ว</div>
            @else
              <div class="badge badge-ghost badge-sm mt-1">ยังไม่ยืนยันอีเมล</div>
            @endif
          </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="rounded-lg bg-zinc-50 p-3">
            <dt class="text-xs text-zinc-500">ลงทะเบียนเมื่อ</dt>
            <dd class="text-sm font-medium">{{ $user->created_at?->format('d/m/Y H:i') }}</dd>
          </div>
          <div class="rounded-lg bg-zinc-50 p-3">
            <dt class="text-xs text-zinc-500">อัปเดตล่าสุด</dt>
            <dd class="text-sm font-medium">{{ $user->updated_at?->format('d/m/Y H:i') }}</dd>
          </div>

          @if(!empty($user->phone))
            <div class="rounded-lg bg-zinc-50 p-3 sm:col-span-2">
              <dt class="text-xs text-zinc-500">โทรศัพท์</dt>
              <dd class="text-sm font-medium">{{ $user->phone }}</dd>
            </div>
          @endif

          @if(!empty($user->department) || !empty($user->position))
            <div class="rounded-lg bg-zinc-50 p-3 sm:col-span-2">
              <dt class="text-xs text-zinc-500">หน่วยงาน/ตำแหน่ง</dt>
              <dd class="text-sm font-medium">
                {{ $user->department ?? '-' }}
                @if($user->department && $user->position) • @endif
                {{ $user->position ?? '' }}
              </dd>
            </div>
          @endif
        </dl>
      </div>
    </div>

    {{-- พื้นที่ต่อยอดใส่สถิติ/รายการล่าสุดภายหลัง --}}
    <div class="lg:col-span-2 space-y-6">
      <div class="rounded-2xl border border-zinc-200 bg-white p-5">
        <h2 class="font-semibold">สรุปการใช้งาน</h2>
        <p class="text-sm text-zinc-600 mt-2">
          (เตรียมพื้นที่ไว้ใส่ “งานของฉัน” หรือ “รายการที่ฉันแจ้ง” ภายหลัง)
        </p>
      </div>
    </div>
  </div>
@endsection
