@csrf

<div class="maint-form space-y-6">

  {{-- ชื่อหมวดหมู่ --}}
  <div class="space-y-1.5">
    <label class="block text-sm font-medium text-slate-700">
      ชื่อหมวดหมู่ <span class="text-rose-500">*</span>
    </label>
    <input
      type="text"
      name="name"
      value="{{ old('name', $category->name) }}"
      required
      class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
             focus:border-emerald-500 focus:ring-emerald-500 bg-white"
    >
    @error('name')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- คำอธิบาย --}}
  <div class="space-y-1.5">
    <label class="block text-sm font-medium text-slate-700">
      คำอธิบาย
    </label>
    <textarea
      name="description"
      rows="2"
      class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
             focus:border-emerald-500 focus:ring-emerald-500 bg-white"
    >{{ old('description', $category->description) }}</textarea>
    @error('description')
      <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
    @enderror
  </div>

  {{-- สี + Active --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- รหัสสี --}}
    <div class="space-y-1.5">
      <label class="block text-sm font-medium text-slate-700">
        รหัสสี (Color)
      </label>
      <input
        type="text"
        name="color"
        placeholder="#0E2B51"
        value="{{ old('color', $category->color) }}"
        class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm
               focus:border-emerald-500 focus:ring-emerald-500 bg-white"
      >
      @error('color')
        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
      @enderror
    </div>

    {{-- Active Checkbox --}}
    <div class="space-y-1.5 flex flex-col justify-end">
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input
          type="checkbox"
          name="is_active"
          value="1"
          @checked(old('is_active', $category->is_active))
          class="h-4 w-4 rounded border-slate-300 text-emerald-600
                 focus:ring-emerald-500"
        >
        Active
      </label>
      @error('is_active')
        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
      @enderror
    </div>

  </div>

</div>
