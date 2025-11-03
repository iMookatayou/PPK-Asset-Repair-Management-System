@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm text-zinc-700">Asset Code *</label>
    <input name="asset_code" value="{{ old('asset_code', $asset->asset_code ?? '') }}" required
           class="mt-1 w-full rounded-lg border px-3 py-2">
    @error('asset_code') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Name *</label>
    <input name="name" value="{{ old('name', $asset->name ?? '') }}" required
           class="mt-1 w-full rounded-lg border px-3 py-2">
    @error('name') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Department</label>
    <select name="department_id" class="mt-1 w-full rounded-lg border px-3 py-2">
      <option value="">—</option>
      @foreach($departments as $d)
        <option value="{{ $d->id }}" @selected(old('department_id', $asset->department_id ?? null)==$d->id)>{{ $d->name }}</option>
      @endforeach
    </select>
    @error('department_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Category</label>
    <select name="category_id" class="mt-1 w-full rounded-lg border px-3 py-2">
      <option value="">—</option>
      @foreach($categories as $c)
        <option value="{{ $c->id }}" @selected(old('category_id', $asset->category_id ?? null)==$c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
    @error('category_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Location</label>
    <input name="location" value="{{ old('location', $asset->location ?? '') }}"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Status</label>
    <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2">
      @php $statuses = ['active'=>'Active','in_repair'=>'In Repair','disposed'=>'Disposed']; @endphp
      <option value="">—</option>
      @foreach($statuses as $k=>$v)
        <option value="{{ $k }}" @selected(old('status', $asset->status ?? '')===$k)>{{ $v }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Serial Number</label>
    <input name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Purchase Date</label>
    <input type="date" name="purchase_date"
           value="{{ old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Warranty Expire</label>
    <input type="date" name="warranty_expire"
           value="{{ old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm text-zinc-700">Type / Brand / Model</label>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <input name="type"  placeholder="Type"  value="{{ old('type', $asset->type ?? '') }}"  class="rounded-lg border px-3 py-2">
      <input name="brand" placeholder="Brand" value="{{ old('brand', $asset->brand ?? '') }}" class="rounded-lg border px-3 py-2">
      <input name="model" placeholder="Model" value="{{ old('model', $asset->model ?? '') }}" class="rounded-lg border px-3 py-2">
    </div>
  </div>
</div>
