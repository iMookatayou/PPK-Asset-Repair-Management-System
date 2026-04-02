{{-- resources/views/assets/_fields.blade.php --}}
@php
  /** @var \App\Models\Asset|null $asset */
  $asset       = $asset ?? null;
  $categories  = is_iterable($categories ?? null) ? collect($categories) : collect();
  $departments = is_iterable($departments ?? null) ? collect($departments) : collect();

  // Base class for inputs/selects to match Maintenance form
  $CTL = 'mt-1.5 block w-full h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm
          focus:border-emerald-500 focus:ring-emerald-500 bg-white transition-all';

  // Styling for Step/Accent Headers
  $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
  $noCls     = "w-8 h-8 shrink-0 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold text-white leading-none shadow-sm";
  $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
  $subCls    = "text-sm text-slate-500 leading-snug";
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";

  $gridCls   = "grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2";
@endphp

<div class="asset-form space-y-10 pb-8">

  {{-- SECTION 1 : ข้อมูลหลัก (Primary Data) --}}
  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">1</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">ข้อมูลหลักของครุภัณฑ์</div>
        <div class="{{ $subCls }}">ชื่อ รหัส และการเชื่อมต่อ HIS (เลข รพจ)</div>
      </div>
    </div>

    <div class="{{ $gridCls }} mt-4">
      {{-- ชื่อครุภัณฑ์ --}}
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="name">
          ชื่อครุภัณฑ์ <span class="text-rose-600 font-bold">*</span>
        </label>
        <input id="name" name="name" type="text" class="{{ $CTL }} @error('name') border-rose-400 ring-rose-200 @enderror" value="{{ old('name', $asset->name ?? '') }}" required>
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>

      {{-- รหัสครุภัณฑ์ --}}
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="asset_code">
          รหัสครุภัณฑ์ <span class="text-rose-600 font-bold">*</span>
        </label>
        <input id="asset_code" name="asset_code" type="text" class="{{ $CTL }} @error('asset_code') border-rose-400 ring-rose-200 @enderror" value="{{ old('asset_code', $asset->asset_code ?? '') }}" required>
        @error('asset_code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>

      {{-- เลข รพจ (HIS ID) --}}
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700" for="his_asset_id">
          เลข รพจ (HIS ID)
          <span class="ml-1 text-xs text-slate-400 font-normal">(ดึงข้อมูลจาก HIS อัตโนมัติ)</span>
        </label>
        <div class="flex gap-2 mt-1.5">
          <input id="his_asset_id" name="his_asset_id" type="text" class="{{ $CTL }} mt-0 flex-1 @error('his_asset_id') border-rose-400 ring-rose-200 @enderror" value="{{ old('his_asset_id', $asset->his_asset_id ?? '') }}" placeholder="เช่น RPJ-001234">
          <button type="button" id="btn-fetch-his" class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-5 text-sm font-medium text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 transition-colors whitespace-nowrap h-11">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            ดึงข้อมูล HIS
          </button>
        </div>
        @error('his_asset_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        <p id="his-fetch-status" class="hidden mt-1 text-xs"></p>
      </div>

      {{-- เบอร์โทรภายใน (ตามป้ายเหลือง) --}}
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="internal_phone">
          เบอร์โทรภายใน <span class="ml-1 text-xs text-slate-400 font-normal">(ตามป้ายเหลือง)</span>
        </label>
        <input id="internal_phone" name="internal_phone" type="text" class="{{ $CTL }} @error('internal_phone') border-rose-400 ring-rose-200 @enderror" value="{{ old('internal_phone', $asset->internal_phone ?? '') }}" placeholder="เช่น 02-xxx-xxxx หรือเบอร์ภายใน">
        @error('internal_phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>

      {{-- ประเภททรัพย์สิน (Type) --}}
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="type">
          ประเภท <span class="ml-1 text-xs text-slate-400 font-normal">(Medical, IT, Office, etc.)</span>
        </label>
        <input id="type" name="type" type="text" class="{{ $CTL }} @error('type') border-rose-400 ring-rose-200 @enderror" value="{{ old('type', $asset->type ?? '') }}" placeholder="ระบุประเภทครุภัณฑ์">
        @error('type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>
    </div>
  </section>

  {{-- SECTION 2 : รายละเอียดทางเทคนิค (Technical Info) --}}
  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">2</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">รายละเอียดทางเทคนิค</div>
        <div class="{{ $subCls }}">ยี่ห้อ รุ่น Serial และสถานที่ตั้ง</div>
      </div>
    </div>

    <div class="{{ $gridCls }} mt-4">
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="brand">ยี่ห้อ</label>
        <input id="brand" name="brand" type="text" class="{{ $CTL }}" value="{{ old('brand', $asset->brand ?? '') }}">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="model">รุ่น</label>
        <input id="model" name="model" type="text" class="{{ $CTL }}" value="{{ old('model', $asset->model ?? '') }}">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="serial_number">Serial Number</label>
        <input id="serial_number" name="serial_number" type="text" class="{{ $CTL }}" value="{{ old('serial_number', $asset->serial_number ?? '') }}">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="location">ที่ตั้ง / ห้อง / สถานที่ใช้งาน</label>
        <input id="location" name="location" type="text" class="{{ $CTL }}" value="{{ old('location', $asset->location ?? '') }}">
      </div>
    </div>
  </section>

  {{-- SECTION 3 : การจัดกลุ่มและหน่วยงาน (Grouping & Org) --}}
  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">3</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">หมวดหมู่ และหน่วยงาน</div>
        <div class="{{ $subCls }}">ใช้สำหรับจัดกลุ่ม และระบุหน่วยงานเจ้าของ</div>
      </div>
    </div>

    <div class="{{ $gridCls }} mt-4">
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="category_id">หมวดหมู่</label>
        <select id="category_id" name="category_id" class="ts-basic w-full mt-1.5 h-11 @error('category_id') ts-error @enderror" placeholder="เลือกหมวดหมู่">
          <option value="">— เลือกหมวดหมู่ —</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected(old('category_id', $asset->category_id ?? null) == $cat->id)>
              {{ $cat->name ?? $cat->name_th ?? '—' }}
            </option>
          @endforeach
        </select>
        @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>

      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="department_id">หน่วยงานเจ้าของ</label>
        <select id="department_id" name="department_id" class="ts-basic w-full mt-1.5 h-11 @error('department_id') ts-error @enderror" placeholder="เลือกหน่วยงาน">
          <option value="">— เลือกหน่วยงาน —</option>
          @foreach($departments as $d)
            <option value="{{ $d->id }}" @selected(old('department_id', $asset->department_id ?? null) == $d->id)>
              {{ ($d->code ? $d->code.' - ' : '').($d->name_th ?: $d->name_en) }}
            </option>
          @endforeach
        </select>
        @error('department_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700" for="status">สถานะปัจจุบัน</label>
        <select id="status" name="status" class="ts-basic w-full mt-1.5 h-11" placeholder="เลือกสถานะ">
          @foreach(\App\Models\Asset::statusLabels() as $k => $lbl)
            <option value="{{ $k }}" @selected(old('status', $asset->status ?? 'active') === $k)>{{ $lbl }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </section>

  {{-- SECTION 4 : ผู้ขายและราคา (Vendor Info) --}}
  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">4</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">ข้อมูลผู้ขาย และราคา</div>
        <div class="{{ $subCls }}">ราคาจัดซื้อ และรายละเอียดติดต่อผู้ขาย</div>
      </div>
    </div>

    <div class="{{ $gridCls }} mt-4">
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="vendor_name">ชื่อผู้ขาย / ตัวแทนจำหน่าย</label>
        <input id="vendor_name" name="vendor_name" type="text" class="{{ $CTL }}" value="{{ old('vendor_name', $asset->vendor_name ?? '') }}" placeholder="เช่น บริษัท ตัวอย่าง จำกัด">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="vendor_phone">เบอร์โทรศัพท์ผู้ขาย</label>
        <input id="vendor_phone" name="vendor_phone" type="text" class="{{ $CTL }}" value="{{ old('vendor_phone', $asset->vendor_phone ?? '') }}" placeholder="08x-xxx-xxxx">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700" for="price">ราคาจัดซื้อ (บาท)</label>
        <div class="relative mt-1.5">
          <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400 text-sm">฿</span>
          <input id="price" name="price" type="number" step="0.01" min="0" class="{{ $CTL }} mt-0 pl-8" value="{{ old('price', $asset->price ?? '') }}" placeholder="0.00">
        </div>
      </div>
    </div>
  </section>

  {{-- SECTION 5 : วันที่และประกัน (Dates & Warranty) --}}
  <section>
    <div class="{{ $headCls }}">
      <div class="{{ $noCls }}">5</div>
      <div class="{{ $accentWrap }}">
        <span class="{{ $accentBar }}"></span>
        <div class="{{ $titleCls }}">วันที่จัดซื้อ และการรับประกัน</div>
        <div class="{{ $subCls }}">วันที่เริ่มใช้งาน และระยะเวลารับประกัน</div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-3 mt-4">
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="purchase_date">วันที่ซื้อ</label>
        <input id="purchase_date" name="purchase_date" type="date" class="{{ $CTL }}" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="warranty_start">เริ่มรับประกัน</label>
        <input id="warranty_start" name="warranty_start" type="date" class="{{ $CTL }}" value="{{ old('warranty_start', optional($asset->warranty_start ?? null)?->format('Y-m-d')) }}">
      </div>
      <div class="div">
        <label class="block text-sm font-medium text-slate-700" for="warranty_expire">สิ้นสุดรับประกัน</label>
        <input id="warranty_expire" name="warranty_expire" type="date" class="{{ $CTL }}" value="{{ old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}">
      </div>
    </div>
  </section>

  {{-- หมวดหมู่หมายเหตุ --}}
  <section class="pt-2">
    <label class="block text-sm font-medium text-slate-700" for="note">หมายเหตุเพิ่มเติม</label>
    <textarea id="note" name="note" rows="3" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500 bg-white min-h-[80px]" placeholder="ข้อมูลเพิ่มเติมเกี่ยวกับครุภัณฑ์...">{{ old('note', $asset->note ?? '') }}</textarea>
  </section>

</div>

{{-- HIS Fetch Logic remains identical, optimized for new IDs if needed --}}
<script>
(function () {
  const btn       = document.getElementById('btn-fetch-his');
  const hisInput  = document.getElementById('his_asset_id');
  const statusEl  = document.getElementById('his-fetch-status');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  if (!btn || !hisInput) return;

  function showToast(msg, type = 'info') {
    if (typeof window.showAppToast === 'function') {
      window.showAppToast(msg, type);
      return;
    }
    statusEl.textContent  = msg;
    statusEl.className    = 'mt-1 text-xs ' + (type === 'error' || type === 'warning' ? 'text-amber-600' : 'text-emerald-600');
    statusEl.classList.remove('hidden');
    setTimeout(() => statusEl.classList.add('hidden'), 3000);
  }

  const fieldMap = {
    name:           'name',
    brand:          'brand',
    model:          'model',
    serial_number:  'serial_number',
    vendor_name:    'vendor_name',
    vendor_phone:   'vendor_phone',
    internal_phone: 'internal_phone',
    price:          'price',
    warranty_start: 'warranty_start',
    warranty_expire:'warranty_expire',
    type:           'type'
  };

  btn.addEventListener('click', async function () {
    const hisId = hisInput.value.trim();
    if (!hisId) {
      showToast('กรุณาระบุเลข รพจ ก่อน', 'warning');
      hisInput.focus();
      return;
    }

    btn.disabled    = true;
    const oldHtml   = btn.innerHTML;
    btn.textContent = 'กำลังดึง...';

    try {
      const url = `/assets/fetch-his?his_id=${encodeURIComponent(hisId)}`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });

      if (res.status >= 500) { showToast('เซิร์ฟเวอร์ขัดข้อง กรุณาลองใหม่', 'error'); return; }
      const json = await res.json();
      if (!res.ok || json.status !== 'found') { showToast('ไม่พบข้อมูล HIS (เลขนี้อาจไม่มีในฐานข้อมูล รพจ)', 'warning'); return; }

      const data = json.data ?? {};
      Object.entries(fieldMap).forEach(([key, id]) => {
        if (data[key] == null) return;
        const el = document.getElementById(id);
        if (el) el.value = data[key];
      });

      showToast('ดึงข้อมูล HIS สำเร็จ', 'success');
    } catch (err) {
      showToast('ไม่สามารถเชื่อมต่อฐานข้อมูลได้', 'error');
      console.error(err);
    } finally {
      btn.disabled  = false;
      btn.innerHTML = oldHtml;
    }
  });
})();
</script>
