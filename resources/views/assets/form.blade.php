@php
  /** @var \App\Models\Asset|null $asset */
  $statuses = ['active' => 'Active', 'in_repair' => 'In Repair', 'disposed' => 'Disposed'];
@endphp

@if ($errors->any())
  <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
    <p class="font-medium">There were some problems with your submission:</p>
    <ul class="mt-2 list-disc pl-5">
      @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
  <div>
    <label class="block text-sm font-medium text-zinc-700">Asset Code <span class="text-rose-600">*</span></label>
    <input name="asset_code" value="{{ old('asset_code', $asset->asset_code ?? '') }}"
           required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>
  <div>
    <label class="block text-sm font-medium text-zinc-700">Name <span class="text-rose-600">*</span></label>
    <input name="name" value="{{ old('name', $asset->name ?? '') }}" required
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Category</label>
    <input name="category" value="{{ old('category', $asset->category ?? '') }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>
  <div>
    <label class="block text-sm font-medium text-zinc-700">Brand</label>
    <input name="brand" value="{{ old('brand', $asset->brand ?? '') }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Model</label>
    <input name="model" value="{{ old('model', $asset->model ?? '') }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>
  <div>
    <label class="block text-sm font-medium text-zinc-700">Serial No.</label>
    <input name="serial_number" value="{{ old('serial_number', $asset->serial_number ?? '') }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm font-medium text-zinc-700">Location</label>
    <input name="location" value="{{ old('location', $asset->location ?? '') }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Purchase Date</label>
    <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($asset->purchase_date ?? null)->format('Y-m-d')) }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>
  <div>
    <label class="block text-sm font-medium text-zinc-700">Warranty Expire</label>
    <input type="date" name="warranty_expire" value="{{ old('warranty_expire', optional($asset->warranty_expire ?? null)->format('Y-m-d')) }}"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Status</label>
    <select name="status" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
      @foreach($statuses as $k=>$v)
        <option value="{{ $k }}" @selected(old('status', $asset->status ?? 'active') === $k)>{{ $v }}</option>
      @endforeach
    </select>
  </div>
</div>
