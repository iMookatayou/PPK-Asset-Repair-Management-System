@props([
  'name',
  'id' => null,
  'items' => collect(),
  'labelField' => 'name',
  'valueField' => 'id',
  'value' => null,
  'placeholder' => '— ไม่ระบุ —',
])

@php
  $id = $id ?: $name;
  $collection = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);
  $selectedVal = old($name, $value);
  $selectedLabel = '';
  if (!is_null($selectedVal)) {
    $it = $collection->firstWhere($valueField, $selectedVal);
    if ($it) {
      $selectedLabel = data_get($it, $labelField) ?? data_get($it, 'display_name') ?? data_get($it, 'name') ?? '';
    }
  }
  $listId = $id.'-listbox';
@endphp

<div class="relative" data-ss data-name="{{ $name }}">
  <input type="hidden" name="{{ $name }}" value="{{ $selectedVal }}" data-ss-input>

  <div class="mt-1 relative">
    <span class="pointer-events-none absolute left-2 top-1/2 -translate-y-1/2 text-slate-400">
      <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 013.88 9.39l3.11 3.12a.75.75 0 11-1.06 1.06l-3.12-3.11A5.5 5.5 0 118.5 3zm0 1.5a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd" />
      </svg>
    </span>
    <input id="{{ $id }}" type="text" role="combobox" aria-controls="{{ $listId }}" aria-expanded="false"
           placeholder="{{ $placeholder }}" value="{{ $selectedLabel }}"
           data-ss-text
           class="w-full rounded-lg border border-slate-300 bg-white pl-8 pr-10 py-2 focus:border-emerald-600 focus:ring-emerald-600"
    >
    <button type="button" data-ss-toggle class="absolute inset-y-0 right-0 flex items-center px-2 text-slate-400 hover:text-slate-600" tabindex="-1" aria-label="Toggle options">
      <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
      </svg>
    </button>
  </div>

  <div data-ss-panel class="absolute z-50 mt-1 hidden w-full rounded-lg border border-slate-200 bg-white shadow-lg">
    <ul id="{{ $listId }}" role="listbox" data-ss-list class="max-h-64 overflow-auto py-1 text-sm">
      <li data-ss-option data-value="" class="cursor-pointer px-3 py-1.5 hover:bg-slate-50 text-slate-500">{{ $placeholder }}</li>
      @foreach($collection as $it)
        @php
          $val = data_get($it, $valueField);
          $label = data_get($it, $labelField) ?? data_get($it, 'display_name') ?? data_get($it, 'name') ?? (string) $val;
          $isActive = (string) old($name, $value) === (string) $val;
        @endphp
        <li data-ss-option data-value="{{ $val }}" data-label="{{ $label }}"
            class="cursor-pointer px-3 py-1.5 hover:bg-emerald-50 {{ $isActive ? 'bg-emerald-50 text-emerald-700' : '' }}">{{ $label }}</li>
      @endforeach
      <li data-ss-empty class="hidden px-3 py-2 text-slate-500">ไม่พบรายการ</li>
    </ul>
  </div>
</div>
