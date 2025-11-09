@php /** @var \App\Models\Asset|null $asset */ @endphp

<div class="grid gap-4">
  <div>
    <label class="block text-sm font-medium text-slate-700" for="asset_code">รหัสครุภัณฑ์</label>
    <input id="asset_code" name="asset_code" type="text"
           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
           value="{{ old('asset_code', $asset->asset_code ?? '') }}" required>
    @error('asset_code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700" for="name">ชื่อ</label>
    <input id="name" name="name" type="text"
           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
           value="{{ old('name', $asset->name ?? '') }}" required>
    @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="type">ประเภท (type)</label>
      <input id="type" name="type" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('type', $asset->type ?? '') }}">
      @error('type') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- legacy category string removed; use select below for category_id --}}
    <div class="hidden">
      <input type="text" disabled aria-hidden="true">
    </div>
  </div>

  {{-- category_id + department_id (ถ้า controller ส่ง $categories, $departments มา) --}}
  @isset($categories)
    <div>
      <label class="block text-sm font-medium text-slate-700" for="category_id">หมวดหมู่</label>
      <x-search-select name="category_id" id="category_id"
        :items="$categories"
        label-field="name" value-field="id"
        :value="old('category_id', $asset->category_id ?? null)"
        placeholder="— ไม่ระบุ —" />
      @error('category_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
  @endisset

  @isset($departments)
    <div>
      <label class="block text-sm font-medium text-slate-700" for="department_id">หน่วยงาน</label>
      @php $hasDepartments = ($departments instanceof \Illuminate\Support\Collection) ? $departments->isNotEmpty() : count($departments ?? []) > 0; @endphp
      @if($hasDepartments)
        <x-search-select name="department_id" id="department_id"
          :items="$departments"
          label-field="display_name" value-field="id"
          :value="old('department_id', $asset->department_id ?? null)"
          placeholder="— ไม่ระบุ —" />
      @else
        <div class="mt-1 w-full rounded-lg border px-3 py-2 text-slate-400">ยังไม่มีข้อมูลหน่วยงาน (กรุณา seed)</div>
      @endif
      @error('department_id') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
  @endisset

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="purchase_date">วันที่ซื้อ</label>
      <input id="purchase_date" name="purchase_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}">
      @error('purchase_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="warranty_expire">หมดประกัน</label>
      <input id="warranty_expire" name="warranty_expire" type="date" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}">
      @error('warranty_expire') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="brand">ยี่ห้อ</label>
      <input id="brand" name="brand" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('brand', $asset->brand ?? '') }}">
      @error('brand') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="model">รุ่น</label>
      <input id="model" name="model" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('model', $asset->model ?? '') }}">
      @error('model') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="serial_number">Serial</label>
      <input id="serial_number" name="serial_number" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('serial_number', $asset->serial_number ?? '') }}">
      @error('serial_number') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="location">ที่ตั้ง</label>
      <input id="location" name="location" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="{{ old('location', $asset->location ?? '') }}">
      @error('location') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700" for="status">สถานะ</label>
    <select id="status" name="status" class="mt-1 w-full rounded-lg border px-3 py-2">
      @php $statuses = ['active'=>'ใช้งาน','in_repair'=>'ซ่อม','disposed'=>'จำหน่าย']; @endphp
      @foreach($statuses as $k=>$label)
        <option value="{{ $k }}" @selected(old('status', $asset->status ?? 'active') === $k)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
  </div>
</div>
