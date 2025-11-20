{{-- resources/views/assets/_fields.blade.php --}}
@php
  /** @var \App\Models\Asset|null $asset */
  $asset       = $asset ?? null;
  $categories  = is_iterable($categories ?? null) ? collect($categories) : collect();
  $departments = is_iterable($departments ?? null) ? collect($departments) : collect();

  // base class ของ input / textarea / select ปกติ
  $CTL = 'mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
          focus:border-emerald-500 focus:ring-emerald-500 bg-white';
@endphp

<div class="maint-form space-y-6">
  {{-- ===========================
       SECTION 1 : ข้อมูลหลัก
  ============================ --}}
  <section>
    <h2 class="text-base font-semibold text-slate-900">ข้อมูลหลักของครุภัณฑ์</h2>
    <p class="text-sm text-slate-500">รหัสครุภัณฑ์ ชื่อ และประเภท</p>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      {{-- รหัสครุภัณฑ์ --}}
      @php $field = 'asset_code'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          รหัสครุภัณฑ์ <span class="text-rose-600">*</span>
        </label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
          required
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- ชื่อครุภัณฑ์ --}}
      @php $field = 'name'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          ชื่อครุภัณฑ์ <span class="text-rose-600">*</span>
        </label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
          required
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- ประเภท (type) --}}
      @php $field = 'type'; @endphp
      <div class="md:col-span-2 space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          ประเภท (Type) <span class="ml-1 text-xs text-slate-500">(ไม่บังคับ)</span>
        </label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  {{-- ===========================
       SECTION 2 : หมวดหมู่ & หน่วยงาน
  ============================ --}}
  <section class="pt-4 border-t border-slate-200">
    <h2 class="text-base font-semibold text-slate-900">หมวดหมู่ และหน่วยงานรับผิดชอบ</h2>
    <p class="text-sm text-slate-500">ใช้สำหรับจัดกลุ่ม และระบุหน่วยงานเจ้าของครุภัณฑ์</p>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      {{-- หมวดหมู่ (category_id) --}}
      @php $field = 'category_id'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          หมวดหมู่
        </label>
        <select
          id="{{ $field }}"
          name="{{ $field }}"
          class="ts-basic w-full @error($field) ts-error @enderror"
          placeholder="ค้นหา / เลือกหมวดหมู่"
        >
          <option value="">— เลือกหมวดหมู่ —</option>
          @foreach($categories as $cat)
            @php $label = $cat->name ?? $cat->name_th ?? $cat->name_en ?? '—'; @endphp
            <option
              value="{{ $cat->id }}"
              @selected(old($field, $asset->$field ?? null) == $cat->id)
            >
              {{ $label }}
            </option>
          @endforeach
        </select>
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      {{-- หน่วยงาน (department_id) --}}
      @php $field = 'department_id'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          หน่วยงาน
        </label>
        @php $selectedDept = old($field, $asset->$field ?? null); @endphp
        <select
          id="{{ $field }}"
          name="{{ $field }}"
          class="ts-basic w-full @error($field) ts-error @enderror"
          placeholder="ค้นหา / เลือกหน่วยงาน"
        >
          <option value="">— เลือกหน่วยงาน —</option>
          @foreach($departments as $d)
            @php
              $code  = $d->code ?? '';
              $name  = $d->name_th ?: $d->name_en ?: '';
              $label = trim(($code ? $code.' - ' : '').$name);
            @endphp
            <option value="{{ $d->id }}" @selected($selectedDept == $d->id)>
              {{ $label ?: '—' }}
            </option>
          @endforeach
        </select>
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  {{-- ===========================
       SECTION 3 : ข้อมูลเพิ่มเติม
  ============================ --}}
  <section class="pt-4 border-t border-slate-200">
    <h2 class="text-base font-semibold text-slate-900">ข้อมูลเพิ่มเติม</h2>
    <p class="text-sm text-slate-500">รายละเอียดด้านการจัดซื้อ และคุณลักษณะของครุภัณฑ์</p>

    {{-- วันที่ซื้อ + หมดประกัน --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      @php $field = 'purchase_date'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          วันที่ซื้อ <span class="ml-1 text-xs text-slate-500">(ไม่บังคับ)</span>
        </label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="date"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      @php $field = 'warranty_expire'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">
          หมดประกัน <span class="ml-1 text-xs text-slate-500">(ไม่บังคับ)</span>
        </label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="date"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- brand + model --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      @php $field = 'brand'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">ยี่ห้อ</label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      @php $field = 'model'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">รุ่น</label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- serial + location --}}
    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
      @php $field = 'serial_number'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">Serial</label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      @php $field = 'location'; @endphp
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">ที่ตั้ง</label>
        <input
          id="{{ $field }}"
          name="{{ $field }}"
          type="text"
          class="{{ $CTL }} @error($field) border-rose-400 ring-rose-200 @enderror"
          value="{{ old($field, $asset->$field ?? '') }}"
        >
        @error($field)
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>
    </div>
  </section>

  {{-- ===========================
       SECTION 4 : สถานะ
  ============================ --}}
  <section class="pt-4 border-t border-slate-200">
    <h2 class="text-base font-semibold text-slate-900">สถานะของครุภัณฑ์</h2>
    <p class="text-sm text-slate-500">เลือกสถานะปัจจุบันของครุภัณฑ์ในระบบ</p>

    <div class="mt-4 space-y-1.5">
      @php
        $field    = 'status';
        $statuses = ['active' => 'ใช้งาน', 'in_repair' => 'ซ่อม', 'disposed' => 'จำหน่าย'];
      @endphp
      <label class="block text-sm font-medium text-slate-700" for="{{ $field }}">สถานะ</label>
      <select
        id="{{ $field }}"
        name="{{ $field }}"
        class="ts-basic w-full @error($field) ts-error @enderror"
        placeholder="เลือกสถานะ"
      >
        @foreach($statuses as $k => $label)
          <option value="{{ $k }}" @selected(old($field, $asset->status ?? 'active') === $k)>
            {{ $label }}
          </option>
        @endforeach
      </select>
      @error($field)
        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
      @enderror
    </div>
  </section>
</div>
