{{-- resources/views/assets/_form.blade.php --}}

@php
  /** @var \App\Models\Asset|null $asset */
  $isEdit = isset($asset) && $asset?->exists;

  // กำหนด action และ method ให้เอง เพื่อไม่ต้องพึ่งตัวแปรจากภายนอก
  $action = $isEdit
      ? route('assets.update', $asset)
      : route('assets.store');

  // method ที่ใช้กับ <form> ให้เป็น POST เสมอ แล้วค่อย spoof เป็น PUT ตอนแก้ไข
  $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5" onsubmit="AssetForm.setBusy(true)">
  @csrf
  @if($method !== 'POST')
    @method($method)
  @endif

  {{-- === ตัวอย่างฟิลด์ === --}}
  <div class="rounded-xl border bg-white shadow-sm p-5 space-y-4">
    <div>
      <label for="name" class="block text-sm font-medium text-slate-700">ชื่อครุภัณฑ์</label>
      <input
        type="text" id="name" name="name"
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
        value="{{ old('name', $asset->name ?? '') }}" required
      >
      @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
      <label for="code" class="block text-sm font-medium text-slate-700">รหัส</label>
      <input
        type="text" id="code" name="code"
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
        value="{{ old('code', $asset->code ?? '') }}"
      >
      @error('code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- เพิ่มฟิลด์อื่นๆ ตามต้องการ --}}
  </div>

  <div class="flex items-center gap-3">
    <button type="submit"
            class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
      {{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' }}
    </button>

    <a href="{{ route('assets.index') }}"
       class="rounded-lg border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-50">
      ยกเลิก
    </a>
  </div>
</form>
