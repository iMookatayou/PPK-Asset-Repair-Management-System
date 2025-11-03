@props(['label', 'value' => '—'])

<div class="rounded-lg border p-3">
  <div class="text-xs text-zinc-500">{{ $label }}</div>
  <div class="text-sm font-medium">
    @if(is_string($value) || is_numeric($value))
      {{ $value }}
    @else
      {{ (string) $value }}
    @endif
  </div>
</div>