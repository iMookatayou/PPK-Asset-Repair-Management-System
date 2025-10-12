{{-- resources/views/maintenance/_form.blade.php --}}
@php
  /** @var \App\Models\MaintenanceRequest|null $req */
  $priorities = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  {{-- Asset --}}
  <div>
    <label for="asset_id" class="block text-sm font-medium text-zinc-700">
      Asset <span class="text-rose-600" aria-hidden="true">*</span>
    </label>
    <select
      id="asset_id"
      name="asset_id"
      required
      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500
             @error('asset_id') border-rose-400 ring-rose-200 @enderror"
      aria-invalid="@error('asset_id') true @else false @enderror"
      aria-describedby="@error('asset_id') asset_id_error @enderror"
    >
      <option value="" disabled {{ old('asset_id', $req->asset_id ?? '') === '' ? 'selected' : '' }} hidden>-- Choose Asset --</option>
      @foreach ($assets as $a)
        <option value="{{ $a->id }}" @selected((string)old('asset_id', (string)($req->asset_id ?? '')) === (string)$a->id)>
          #{{ $a->id }} — {{ $a->name ?? $a->model ?? 'Asset' }}
        </option>
      @endforeach
    </select>
    @error('asset_id')
      <p id="asset_id_error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
  </div>

  {{-- Reporter --}}
  <div>
    <label for="reporter_id" class="block text-sm font-medium text-zinc-700">
      Reporter <span class="text-rose-600" aria-hidden="true">*</span>
    </label>
    <select
      id="reporter_id"
      name="reporter_id"
      required
      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500
             @error('reporter_id') border-rose-400 ring-rose-200 @enderror"
      aria-invalid="@error('reporter_id') true @else false @enderror"
      aria-describedby="@error('reporter_id') reporter_id_error @enderror"
    >
      @php $defaultReporter = old('reporter_id', (string)($req->reporter_id ?? auth()->id())); @endphp
      <option value="" disabled {{ $defaultReporter === '' ? 'selected' : '' }} hidden>-- Reporter --</option>
      @foreach ($users as $u)
        <option value="{{ $u->id }}" @selected((string)$defaultReporter === (string)$u->id)>{{ $u->name }}</option>
      @endforeach
    </select>
    @error('reporter_id')
      <p id="reporter_id_error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
  </div>

  {{-- Title --}}
  <div class="md:col-span-2">
    <label for="title" class="block text-sm font-medium text-zinc-700">
      Title <span class="text-rose-600" aria-hidden="true">*</span>
    </label>
    <input
      id="title"
      name="title"
      type="text"
      required
      autocomplete="off"
      spellcheck="false"
      value="{{ old('title', $req->title ?? '') }}"
      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500
             @error('title') border-rose-400 ring-rose-200 @enderror"
      aria-invalid="@error('title') true @else false @enderror"
      aria-describedby="@error('title') title_error @enderror"
    />
    @error('title')
      <p id="title_error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
  </div>

  {{-- Description --}}
  <div class="md:col-span-2">
    <label for="description" class="block text-sm font-medium text-zinc-700">Description</label>
    <textarea
      id="description"
      name="description"
      rows="3"
      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500
             @error('description') border-rose-400 ring-rose-200 @enderror"
      aria-invalid="@error('description') true @else false @enderror"
      aria-describedby="@error('description') description_error @enderror"
    >{{ old('description', $req->description ?? '') }}</textarea>
    @error('description')
      <p id="description_error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
  </div>

  {{-- Priority --}}
  <div>
    <label for="priority" class="block text-sm font-medium text-zinc-700">
      Priority <span class="text-rose-600" aria-hidden="true">*</span>
    </label>
    <select
      id="priority"
      name="priority"
      required
      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500
             @error('priority') border-rose-400 ring-rose-200 @enderror"
      aria-invalid="@error('priority') true @else false @enderror"
      aria-describedby="@error('priority') priority_error @enderror"
    >
      @php $priorityValue = old('priority', $req->priority ?? 'medium'); @endphp
      @foreach ($priorities as $k => $label)
        <option value="{{ $k }}" @selected($priorityValue === $k)>{{ $label }}</option>
      @endforeach
    </select>
    @error('priority')
      <p id="priority_error" class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
  </div>
</div>
