@csrf
<div class="space-y-3">
  <div>
    <label class="block text-sm font-medium text-zinc-700">ชื่อหมวดหมู่</label>
    <input type="text" name="name" value="{{ old('name', $category->name) }}" required
           class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">คำอธิบาย</label>
    <textarea name="description" rows="2"
              class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">{{ old('description', $category->description) }}</textarea>
  </div>

  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-sm font-medium text-zinc-700">รหัสสี (Color)</label>
      <input type="text" name="color" placeholder="#0E2B51"
             value="{{ old('color', $category->color) }}"
             class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
    </div>
    <div class="flex items-center mt-6">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="mr-2">
      <label class="text-sm text-zinc-700">Active</label>
    </div>
  </div>
</div>
