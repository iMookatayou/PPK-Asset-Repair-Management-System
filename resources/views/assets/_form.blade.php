@php
  /** @var \App\Models\Asset|null $asset */
  $asset = $asset ?? new \App\Models\Asset();

  $line = 'border-slate-200';

  $input = "mt-2 w-full h-11 rounded-md border $line bg-white px-3 py-2 text-sm text-slate-900
            placeholder:text-slate-400
            focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm text-slate-900
               placeholder:text-slate-400
               focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
  $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
  $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
  $subCls    = "text-sm text-slate-500 leading-snug";
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";

  $labelCls  = "block text-sm font-semibold text-slate-700";
  $hintCls   = "ml-2 text-xs text-slate-500 font-medium";

  $v = fn($key, $default='') => old($key, data_get($asset, $key, $default));

@endphp

<div class="relative grid grid-cols-1 lg:grid-cols-2 gap-8 pt-0">
  <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">1</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">ข้อมูลหลัก</div>
        <div class="{{ $subCls }}">รหัส / ชื่อ / ประเภท</div>
      </div>
    </div>

    <label class="{{ $labelCls }}">
      รหัสครุภัณฑ์ <span class="text-rose-600">*</span>
    </label>
    <input type="text" name="asset_code" value="{{ $v('asset_code') }}" class="{{ $input }}" required>

    <label class="{{ $labelCls }} mt-5">
      ชื่อครุภัณฑ์ <span class="text-rose-600">*</span>
    </label>
    <input type="text" name="name" value="{{ $v('name') }}" class="{{ $input }}" required>

    <label class="{{ $labelCls }} mt-5">
      ประเภท (Type) <span class="{{ $hintCls }}">(ไม่บังคับ)</span>
    </label>
    <input type="text" name="type" value="{{ $v('type') }}" class="{{ $input }}">
  </section>

  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">2</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">หมวดหมู่ & หน่วยงาน</div>
        <div class="{{ $subCls }}">จัดกลุ่ม / ระบุเจ้าของ</div>
      </div>
    </div>

    <label class="{{ $labelCls }}">หมวดหมู่</label>
    <select id="category_id" name="category_id"
            class="ts-basic mt-2 w-full @error('category_id') ts-error @enderror">
      <option value="">— เลือกหมวดหมู่ —</option>
      @foreach(($categories ?? collect()) as $cat)
        @php $label = $cat->name ?? $cat->name_th ?? $cat->name_en ?? '—'; @endphp
        <option value="{{ $cat->id }}" @selected((string)$v('category_id') === (string)$cat->id)>{{ $label }}</option>
      @endforeach
    </select>
    @error('category_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

    <label class="{{ $labelCls }} mt-5">หน่วยงาน</label>
    <select id="department_id" name="department_id"
            class="ts-basic mt-2 w-full @error('department_id') ts-error @enderror">
      <option value="">— เลือกหน่วยงาน —</option>
      @foreach(($departments ?? collect()) as $d)
        @php
          $code  = $d->code ?? '';
          $name  = $d->name_th ?: ($d->name_en ?? '');
          $label = trim(($code ? $code.' - ' : '').$name);
        @endphp
        <option value="{{ $d->id }}" @selected((string)$v('department_id') === (string)$d->id)>{{ $label ?: '—' }}</option>
      @endforeach
    </select>
    @error('department_id') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
  </section>
</div>

<div class="border-t {{ $line }} my-8"></div>

<div class="relative grid grid-cols-1 lg:grid-cols-2 gap-8 pt-0">
  <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">3</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">ข้อมูลเพิ่มเติม</div>
        <div class="{{ $subCls }}">จัดซื้อ / คุณลักษณะ</div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label class="{{ $labelCls }}">วันที่ซื้อ</label>
        <input type="date" name="purchase_date"
               value="{{ $v('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}"
               class="{{ $input }}">
      </div>

      <div>
        <label class="{{ $labelCls }}">หมดประกัน</label>
        <input type="date" name="warranty_expire"
               value="{{ $v('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}"
               class="{{ $input }}">
      </div>

      <div>
        <label class="{{ $labelCls }}">ยี่ห้อ</label>
        <input type="text" name="brand" value="{{ $v('brand') }}" class="{{ $input }}">
      </div>

      <div>
        <label class="{{ $labelCls }}">รุ่น</label>
        <input type="text" name="model" value="{{ $v('model') }}" class="{{ $input }}">
      </div>

      <div>
        <label class="{{ $labelCls }}">Serial</label>
        <input type="text" name="serial_number" value="{{ $v('serial_number') }}" class="{{ $input }}">
      </div>

      <div>
        <label class="{{ $labelCls }}">ที่ตั้ง</label>
        <input type="text" name="location" value="{{ $v('location') }}" class="{{ $input }}">
      </div>
    </div>
  </section>

  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">4</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">สถานะ</div>
        <div class="{{ $subCls }}">สถานะปัจจุบันในระบบ</div>
      </div>
    </div>

    @php
      $statuses = ['active' => 'ใช้งาน', 'in_repair' => 'ซ่อม', 'disposed' => 'จำหน่าย'];
      $status = $v('status', $asset->status ?? 'active');
    @endphp

    <label class="{{ $labelCls }}">สถานะ</label>
    <select id="status" name="status"
            class="ts-basic mt-2 w-full @error('status') ts-error @enderror">
      @foreach($statuses as $k => $label)
        <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

    <label class="{{ $labelCls }} mt-5">หมายเหตุ</label>
    <textarea name="note" rows="6" class="{{ $textarea }}">{{ $v('note') }}</textarea>
  </section>
</div>
