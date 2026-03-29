{{-- resources/views/admin/users/_form.blade.php --}}

@php
  use App\Models\User as UserModel;

  $user = isset($user) && $user instanceof UserModel ? $user : new UserModel();
  $roles = $roles ?? UserModel::availableRoles(); // หรือตาม Logic เดิมของคุณ
  $roleLabels = $roleLabels ?? [];
  $departments = $departments ?? collect();
  $isEdit = $user->exists;

  // ดึง Role ปัจจุบัน (ถ้ามี) หรือ Default
  $currentRole = old('role', $user->role ?? null);
@endphp

<div class="space-y-10"> {{-- เว้นระยะห่างระหว่างแต่ละ Section --}}

  {{-- ===========================================
       SECTION 1: ข้อมูลส่วนตัว
       =========================================== --}}
  <div>
    {{-- Header 1 --}}
    <div class="flex items-center gap-3 mb-6">
      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">
        1
      </span>
      <div>
        <h3 class="text-lg font-semibold text-slate-900 leading-none">ข้อมูลส่วนตัว</h3>
        <p class="text-sm text-slate-500 mt-1">ชื่อผู้ใช้, เลขบัตรประชาชน และอีเมลติดต่อ</p>
      </div>
    </div>

    {{-- Inputs Grid --}}
    <div class="grid gap-6 md:grid-cols-2 ml-0 sm:ml-11">

      {{-- ชื่อผู้ใช้ --}}
      <div class="space-y-1.5">
        <label for="name" class="block text-sm font-medium text-slate-700">
          ชื่อผู้ใช้ <span class="text-rose-500">*</span>
        </label>
        <input id="name" name="name" type="text"
          value="{{ old('name', $user->name) }}" required
          class="w-full form-input transition-all">
      </div>

      {{-- เลขบัตรประชาชน --}}
      <div class="space-y-1.5">
        <label for="citizen_id" class="block text-sm font-medium text-slate-700">
          เลขบัตรประชาชน <span class="text-rose-500">*</span>
        </label>
        <input id="citizen_id" name="citizen_id" type="text" inputmode="numeric" maxlength="13"
          value="{{ old('citizen_id', $user->citizen_id) }}" required
          class="w-full form-input transition-all">
      </div>

      {{-- อีเมล (ปรับให้เต็มแถว เพื่อความสวยงาม) --}}
      <div class="space-y-1.5 md:col-span-2">
        <label for="email" class="block text-sm font-medium text-slate-700">
          อีเมล (ถ้ามี)
        </label>
        <input id="email" name="email" type="email"
          value="{{ old('email', $user->email) }}"
          class="w-full form-input transition-all">
      </div>
    </div>
  </div>

  {{-- ===========================================
       SECTION 2: หน่วยงานและสิทธิ์ (แก้ให้เป็นแถวเดียว)
       =========================================== --}}
  <div>
    {{-- Header 2 --}}
    <div class="flex items-center gap-3 mb-6">
      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">
        2
      </span>
      <div>
        <h3 class="text-lg font-semibold text-slate-900 leading-none">หน่วยงานและสิทธิ์</h3>
        <p class="text-sm text-slate-500 mt-1">สังกัดหน่วยงานและกำหนดบทบาทเข้าใช้งาน</p>
      </div>
    </div>

    {{-- Inputs Grid --}}
    <div class="grid gap-6 md:grid-cols-2 ml-0 sm:ml-11">

      {{-- ✅ หน่วยงาน: ใส่ md:col-span-2 เพื่อให้กว้างเต็มแถว (แก้ปัญหาชื่อยาวแล้วปัดบรรทัด) --}}
      <div class="space-y-1.5 md:col-span-2">
        <label for="department_id" class="block text-sm font-medium text-slate-700">
          หน่วยงาน (ถ้ามี)
        </label>
        <div class="relative mt-1">
          <select id="department_id" name="department_id" placeholder="— เลือกหน่วยงาน —" autocomplete="off" class="w-full">
            <option value="">— ไม่ระบุหน่วยงาน —</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}" @selected(old('department_id', $user->department_id) == $dept->id)>
                {{ $dept->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- ✅ บทบาท: ใส่ md:col-span-2 ให้เต็มแถวเหมือนกัน เพื่อความสมดุล --}}
      <div class="space-y-1.5 md:col-span-2">
        <label for="role" class="block text-sm font-medium text-slate-700">
          บทบาท <span class="text-rose-500">*</span>
        </label>
        <div class="relative mt-1">
          <select id="role" name="role" required placeholder="— เลือกบทบาท —" autocomplete="off" class="w-full">
            <option value="">— เลือกบทบาท —</option>
            @foreach($roles as $roleKey => $roleValue)
              {{-- ปรับ Logic ตรงนี้ให้เข้ากับตัวแปรที่คุณส่งมา (Array Key หรือ Value) --}}
              <option value="{{ $roleKey }}" @selected($currentRole == $roleKey)>
                 {{ $roleLabels[$roleKey] ?? $roleValue }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

    </div>
  </div>

  {{-- ===========================================
       SECTION 3: ความปลอดภัย
       =========================================== --}}
  <div>
    {{-- Header 3 --}}
    <div class="flex items-center gap-3 mb-6">
      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">
        3
      </span>
      <div>
        <h3 class="text-lg font-semibold text-slate-900 leading-none">ความปลอดภัย</h3>
        <p class="text-sm text-slate-500 mt-1">กำหนดรหัสผ่านสำหรับเข้าสู่ระบบ</p>
      </div>
    </div>

    {{-- Inputs Grid --}}
    <div class="grid gap-6 md:grid-cols-2 ml-0 sm:ml-11">

      {{-- รหัสผ่าน --}}
      <div class="space-y-1.5">
        <label for="password" class="block text-sm font-medium text-slate-700">
          รหัสผ่าน @if(!$isEdit) <span class="text-xs font-normal text-slate-500">(ขั้นต่ำ 8 ตัว)</span> @endif
        </label>
        <input id="password" name="password" type="password" autocomplete="new-password"
          @if(!$isEdit) required @endif
          class="w-full form-input transition-all">
      </div>

      {{-- ยืนยันรหัสผ่าน --}}
      <div class="space-y-1.5">
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">
          ยืนยันรหัสผ่าน
        </label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
          @if(!$isEdit) required @endif
          class="w-full form-input transition-all">
      </div>
    </div>
  </div>

</div>

{{-- ===========================================
     CSS & JS Included
     =========================================== --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<style>
  /* === Input Styling === */
  .maint-form .form-input {
    height: 48px;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    padding: 0.5rem 0.75rem;
    font-size: 0.95rem;
    background-color: white;
    width: 100%;
  }
  .maint-form .form-input:focus {
    border-color: #059669;
    outline: 2px solid transparent;
    box-shadow: 0 0 0 2px #059669;
  }

  /* === TomSelect Styling (Fix Text Wrap + Full Width) === */

  /* 1. บังคับ Wrapper ให้เต็ม 100% ของ Grid */
  .maint-form .ts-wrapper {
    width: 100% !important;
    display: block;
  }

  /* 2. Style ตัว Input Box */
  .maint-form .ts-control {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    min-height: 48px;
    padding: 0 0.75rem !important;
    display: flex;
    align-items: center;
    background-color: white;
    box-shadow: none;
    font-size: 0.95rem;
    width: 100%; /* บังคับกว้างเต็ม */
  }
  .maint-form .ts-control.focus {
    border-color: #059669;
    box-shadow: 0 0 0 2px #059669;
    z-index: 10;
  }

  /* 3. Style Dropdown List */
  .maint-form .ts-dropdown {
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.1);
    z-index: 999;
    margin-top: 4px;
    width: 100% !important; /* บังคับ Dropdown กว้างเท่าตัว Input */
  }

  /* 4. ✅ แก้ปัญหาข้อความ 2 บรรทัด: บังคับให้อยู่บรรทัดเดียว (Truncate) */
  .maint-form .ts-dropdown .option,
  .maint-form .ts-control .item {
    white-space: nowrap;       /* ห้ามขึ้นบรรทัดใหม่ */
    overflow: hidden;          /* ถ้าเกินให้ซ่อน */
    text-overflow: ellipsis;   /* แสดง ... ต่อท้าย */
    max-width: 100%;           /* ไม่ให้เกินกล่อง */
  }

  select[hidden] { display: none !important; }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    function initTomSelect(selector) {
      const el = document.querySelector(selector);
      if (!el || el.tomselect) return;

      new TomSelect(selector, {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        plugins: ['no_backspace_delete'],
        onDropdownOpen: function() {
           // ย้ำอีกรอบเพื่อให้มั่นใจว่า dropdown กว้างเต็ม
           this.dropdown.style.width = '100%';
        }
      });
    }

    initTomSelect('#department_id');
    initTomSelect('#role');
  });
</script>
