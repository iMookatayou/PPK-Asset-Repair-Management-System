{{-- resources/views/assets/_form.blade.php --}}
<form method="POST" action="{{ $action }}" class="space-y-5" onsubmit="AssetForm.setBusy(true)">
  @csrf
  @if(($method ?? 'POST') !== 'POST')
    @method($method)
  @endif

  <div class="rounded-xl border bg-white shadow-sm">
    <div class="p-5">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Asset Code --}}
        <div>
          <label for="asset_code" class="block text-sm text-zinc-700">Asset Code <span class="text-rose-600">*</span></label>
          <input id="asset_code" name="asset_code" required
                 value="{{ old('asset_code', $asset->asset_code ?? '') }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('asset_code') border-rose-400 ring-rose-200 @enderror"
                 aria-invalid="@error('asset_code') true @else false @enderror"
                 aria-describedby="@error('asset_code') asset_code_error @enderror">
          @error('asset_code')
            <p id="asset_code_error" class="text-sm text-rose-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        {{-- Name --}}
        <div>
          <label for="name" class="block text-sm text-zinc-700">Name <span class="text-rose-600">*</span></label>
          <input id="name" name="name" required
                 value="{{ old('name', $asset->name ?? '') }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('name') border-rose-400 ring-rose-200 @enderror"
                 aria-invalid="@error('name') true @else false @enderror"
                 aria-describedby="@error('name') name_error @enderror">
          @error('name') <p id="name_error" class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Department --}}
        <div>
          <label for="department_id" class="block text-sm text-zinc-700">Department</label>
          <select id="department_id" name="department_id"
                  class="mt-1 w-full rounded-lg border px-3 py-2
                         focus:outline-none focus:ring-2 focus:ring-emerald-500
                         @error('department_id') border-rose-400 ring-rose-200 @enderror"
                  aria-invalid="@error('department_id') true @else false @enderror"
                  aria-describedby="@error('department_id') department_id_error @enderror">
            <option value="">—</option>
            @foreach($departments as $d)
              <option value="{{ $d->id }}" @selected(old('department_id', $asset->department_id ?? null) == $d->id)>{{ $d->name }}</option>
            @endforeach
          </select>
          @error('department_id') <p id="department_id_error" class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Category --}}
        <div>
          <label for="category_id" class="block text-sm text-zinc-700">Category</label>
          <select id="category_id" name="category_id"
                  class="mt-1 w-full rounded-lg border px-3 py-2
                         focus:outline-none focus:ring-2 focus:ring-emerald-500
                         @error('category_id') border-rose-400 ring-rose-200 @enderror"
                  aria-invalid="@error('category_id') true @else false @enderror"
                  aria-describedby="@error('category_id') category_id_error @enderror">
            <option value="">—</option>
            @foreach($categories as $c)
              <option value="{{ $c->id }}" @selected(old('category_id', $asset->category_id ?? null) == $c->id)>{{ $c->name }}</option>
            @endforeach
          </select>
          @error('category_id') <p id="category_id_error" class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Location --}}
        <div>
          <label for="location" class="block text-sm text-zinc-700">Location</label>
          <input id="location" name="location"
                 value="{{ old('location', $asset->location ?? '') }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('location') border-rose-400 ring-rose-200 @enderror">
          @error('location') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Status --}}
        <div>
          <label for="status" class="block text-sm text-zinc-700">Status</label>
          @php $statuses = ['active'=>'Active','in_repair'=>'In Repair','disposed'=>'Disposed']; @endphp
          <select id="status" name="status"
                  class="mt-1 w-full rounded-lg border px-3 py-2
                         focus:outline-none focus:ring-2 focus:ring-emerald-500
                         @error('status') border-rose-400 ring-rose-200 @enderror">
            <option value="">—</option>
            @foreach($statuses as $k => $v)
              <option value="{{ $k }}" @selected(old('status', $asset->status ?? '') === $k)>{{ $v }}</option>
            @endforeach
          </select>
          @error('status') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Serial Number --}}
        <div>
          <label for="serial_number" class="block text-sm text-zinc-700">Serial Number</label>
          <input id="serial_number" name="serial_number"
                 value="{{ old('serial_number', $asset->serial_number ?? '') }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('serial_number') border-rose-400 ring-rose-200 @enderror">
          @error('serial_number') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Purchase Date --}}
        <div>
          <label for="purchase_date" class="block text-sm text-zinc-700">Purchase Date</label>
          <input id="purchase_date" type="date" name="purchase_date"
                 value="{{ old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d')) }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('purchase_date') border-rose-400 ring-rose-200 @enderror">
          @error('purchase_date') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Warranty Expire --}}
        <div>
          <label for="warranty_expire" class="block text-sm text-zinc-700">Warranty Expire</label>
          <input id="warranty_expire" type="date" name="warranty_expire"
                 value="{{ old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d')) }}"
                 class="mt-1 w-full rounded-lg border px-3 py-2
                        focus:outline-none focus:ring-2 focus:ring-emerald-500
                        @error('warranty_expire') border-rose-400 ring-rose-200 @enderror">
          @error('warranty_expire') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Type / Brand / Model --}}
        <div class="md:col-span-2">
          <label class="block text-sm text-zinc-700">Type / Brand / Model</label>
          <div class="mt-1 grid grid-cols-1 md:grid-cols-3 gap-3">
            <input name="type"  placeholder="Type"
                   value="{{ old('type', $asset->type ?? '') }}"
                   class="rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500
                          @error('type') border-rose-400 ring-rose-200 @enderror">
            <input name="brand" placeholder="Brand"
                   value="{{ old('brand', $asset->brand ?? '') }}"
                   class="rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500
                          @error('brand') border-rose-400 ring-rose-200 @enderror">
            <input name="model" placeholder="Model"
                   value="{{ old('model', $asset->model ?? '') }}"
                   class="rounded-lg border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500
                          @error('model') border-rose-400 ring-rose-200 @enderror">
          </div>
          @error('type')  <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
          @error('brand') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
          @error('model') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
        </div>
      </div>
    </div>

    <div class="px-5 py-3 border-t flex items-center justify-between">
      <p class="text-xs text-zinc-500">Fields marked with <span class="text-rose-600">*</span> are required.</p>

      <div class="flex items-center gap-2">
        <a href="{{ url()->previous() }}"
           class="inline-flex items-center rounded-lg px-4 py-2 border border-zinc-300 hover:bg-zinc-50">
          Cancel
        </a>

        {{-- Submit with mini spinner --}}
        <button type="submit" id="assetSubmitBtn"
                class="btn-with-spinner inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white
                       hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                aria-busy="false">
          <svg class="mini-spinner mr-2" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-dasharray="56.5" stroke-dashoffset="10"></circle>
          </svg>
          <span>{{ ($method ?? 'POST') === 'POST' ? 'Create' : 'Save changes' }}</span>
        </button>
      </div>
    </div>
  </div>
</form>

{{-- inline styles for the tiny spinner (same pattern as your auth layout) --}}
<style>
  .btn-with-spinner { position: relative; }
  .btn-with-spinner[aria-busy="true"] { pointer-events: none; opacity: .9; }
  .btn-with-spinner .mini-spinner { display: none; animation: auth-spin .7s linear infinite; }
  .btn-with-spinner[aria-busy="true"] .mini-spinner { display: inline-block; }
  @keyframes auth-spin { to { transform: rotate(360deg); } }
</style>

<script>
  const AssetForm = {
    setBusy(busy){
      const btn = document.getElementById('assetSubmitBtn');
      if(!btn) return;
      btn.setAttribute('aria-busy', busy ? 'true' : 'false');
    }
  };
</script>
