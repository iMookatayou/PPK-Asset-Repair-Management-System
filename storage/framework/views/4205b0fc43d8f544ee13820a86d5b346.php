<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value' => '—']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label', 'value' => '—']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="rounded-lg border p-3">
  <div class="text-xs text-zinc-500"><?php echo e($label); ?></div>
  <div class="text-sm font-medium">
    <?php if(is_string($value) || is_numeric($value)): ?>
      <?php echo e($value); ?>

    <?php else: ?>
      <?php echo e((string) $value); ?>

    <?php endif; ?>
  </div>
</div><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/components/asset/meta.blade.php ENDPATH**/ ?>