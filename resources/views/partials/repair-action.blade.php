@php
  /** @var \App\Models\MaintenanceRequest $r */
  $action = $action ?? 'accept';
  $label = $label ?? ucfirst($action);
  $class = $class ?? '';
  $includeSelf = $includeSelf ?? false;
@endphp

<form method="POST" action="{{ route('maintenance.requests.transition', $r) }}" class="inline">
  @csrf
  <input type="hidden" name="action" value="{{ $action }}">
  @if($includeSelf)
    <input type="hidden" name="technician_id" value="{{ auth()->id() }}">
  @endif
  <button type="submit"
          class="rounded-lg px-3 py-1.5 text-xs font-medium text-white focus:outline-none focus:ring-2 {{ $class }}">
    {{ $label }}
  </button>
</form>
