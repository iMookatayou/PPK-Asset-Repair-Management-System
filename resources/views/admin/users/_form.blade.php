@php
  use App\Models\User as UserModel;
  /** @var \App\Models\User|null $user */

  // ถ้า $user เป็น null (กรณีหน้า create) ให้สร้าง instance เปล่า ๆ
  $user = $user instanceof UserModel ? $user : new UserModel();

  $roles      = $roles      ?? UserModel::availableRoles();
  $roleLabels = $roleLabels ?? UserModel::roleLabels();
  $isEdit     = $user->exists;

  // role ที่ควรเลือก
  $currentRole = old('role');
  if ($currentRole === null) {
      $currentRole = $user->role ?: UserModel::ROLE_COMPUTER_OFFICER;
  }

  // base classes
  $CTL = 'mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
          focus:border-emerald-500 focus:ring-emerald-500';
  $SEL = $CTL . ' pr-9 bg-white appearance-none';
@endphp

<div class="grid gap-6 md:grid-cols-2">

  {{-- ชื่อผู้ใช้ --}}
  <div class="space-y-1.5">
    <label for="name" class="block text-sm font-medium text-slate-700">
      ชื่อผู้ใช้ <span class="text-rose-500">*</span>
    </label>
    <input
      id="name"
      name="name"
      type="text"
      class="{{ $CTL }}"
      value="{{ old('name', $user->name) }}"
      required
    >
    @error('name')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- อีเมล --}}
  <div class="space-y-1.5">
    <label for="email" class="block text-sm font-medium text-slate-700">
      อีเมล <span class="text-rose-500">*</span>
    </label>
    <input
      id="email"
      name="email"
      type="email"
      class="{{ $CTL }}"
      value="{{ old('email', $user->email) }}"
      required
    >
    @error('email')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- หน่วยงาน (ถ้ามี) --}}
  <div class="space-y-1.5">
    <label for="department" class="block text-sm font-medium text-slate-700">
      หน่วยงาน (ถ้ามี)
    </label>

    <div class="relative">
      <select
        id="department"
        name="department_id"
        class="{{ $SEL }}"
      >
        <option value="">— ไม่ระบุหน่วยงาน —</option>
        @foreach($departments as $dept)
          <option value="{{ $dept->id }}"
            @selected(old('department_id', $user->department_id) == $dept->id)>
            {{ $dept->code }} — {{ $dept->display_name ?? $dept->name }}
          </option>
        @endforeach
      </select>

      {{-- ลูกศร dropdown --}}
      <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    @error('department_id')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- บทบาท --}}
  <div class="space-y-1.5">
    <label for="role" class="block text-sm font-medium text-slate-700">
      บทบาท <span class="text-rose-500">*</span>
    </label>

    <div class="relative">
      <select
        id="role"
        name="role"
        class="{{ $SEL }}"
        required
      >
        @foreach($roles as $role)
          <option value="{{ $role }}" @selected($currentRole === $role)>
            {{ $roleLabels[$role] ?? $role }}
          </option>
        @endforeach
      </select>

      {{-- ลูกศร dropdown --}}
      <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </div>

    @error('role')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- รหัสผ่าน --}}
  <div class="space-y-1.5">
    <label for="password" class="block text-sm font-medium text-slate-700">
      รหัสผ่าน
      <span class="text-xs font-normal text-slate-500">
        (เว้นว่างหากไม่ต้องการเปลี่ยน)
      </span>
    </label>
    <input
      id="password"
      name="password"
      type="password"
      class="{{ $CTL }}"
      autocomplete="new-password"
    >
    @error('password')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- ยืนยันรหัสผ่าน --}}
  <div class="space-y-1.5">
    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">
      ยืนยันรหัสผ่าน
      <span class="text-xs font-normal text-slate-500">
        (กรอกเมื่อต้องการเปลี่ยนรหัสผ่าน)
      </span>
    </label>
    <input
      id="password_confirmation"
      name="password_confirmation"
      type="password"
      class="{{ $CTL }}"
      autocomplete="new-password"
    >
    @error('password_confirmation')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

</div>
