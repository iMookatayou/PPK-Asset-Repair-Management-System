<?php echo csrf_field(); ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm text-zinc-700">Asset Code *</label>
    <input name="asset_code" value="<?php echo e(old('asset_code', $asset->asset_code ?? '')); ?>" required
           class="mt-1 w-full rounded-lg border px-3 py-2">
    <?php $__errorArgs = ['asset_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Name *</label>
    <input name="name" value="<?php echo e(old('name', $asset->name ?? '')); ?>" required
           class="mt-1 w-full rounded-lg border px-3 py-2">
    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Department</label>
    <select name="department_id" class="mt-1 w-full rounded-lg border px-3 py-2">
      <option value="">—</option>
      <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($d->id); ?>" <?php if(old('department_id', $asset->department_id ?? null)==$d->id): echo 'selected'; endif; ?>><?php echo e($d->name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Category</label>
    <select name="category_id" class="mt-1 w-full rounded-lg border px-3 py-2">
      <option value="">—</option>
      <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($c->id); ?>" <?php if(old('category_id', $asset->category_id ?? null)==$c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-rose-600 mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Location</label>
    <input name="location" value="<?php echo e(old('location', $asset->location ?? '')); ?>"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Status</label>
    <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2">
      <?php $statuses = ['active'=>'Active','in_repair'=>'In Repair','disposed'=>'Disposed']; ?>
      <option value="">—</option>
      <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($k); ?>" <?php if(old('status', $asset->status ?? '')===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Serial Number</label>
    <input name="serial_number" value="<?php echo e(old('serial_number', $asset->serial_number ?? '')); ?>"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Purchase Date</label>
    <input type="date" name="purchase_date"
           value="<?php echo e(old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d'))); ?>"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div>
    <label class="block text-sm text-zinc-700">Warranty Expire</label>
    <input type="date" name="warranty_expire"
           value="<?php echo e(old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d'))); ?>"
           class="mt-1 w-full rounded-lg border px-3 py-2">
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm text-zinc-700">Type / Brand / Model</label>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <input name="type"  placeholder="Type"  value="<?php echo e(old('type', $asset->type ?? '')); ?>"  class="rounded-lg border px-3 py-2">
      <input name="brand" placeholder="Brand" value="<?php echo e(old('brand', $asset->brand ?? '')); ?>" class="rounded-lg border px-3 py-2">
      <input name="model" placeholder="Model" value="<?php echo e(old('model', $asset->model ?? '')); ?>" class="rounded-lg border px-3 py-2">
    </div>
  </div>
</div>
<?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/form.blade.php ENDPATH**/ ?>