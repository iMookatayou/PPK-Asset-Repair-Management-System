<?php /** @var \App\Models\Asset|null $asset */ ?>

<div class="grid gap-4">
  <div>
    <label class="block text-sm font-medium text-slate-700" for="asset_code">รหัสครุภัณฑ์</label>
    <input id="asset_code" name="asset_code" type="text"
           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
           value="<?php echo e(old('asset_code', $asset->asset_code ?? '')); ?>" required>
    <?php $__errorArgs = ['asset_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700" for="name">ชื่อ</label>
    <input id="name" name="name" type="text"
           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600"
           value="<?php echo e(old('name', $asset->name ?? '')); ?>" required>
    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="type">ประเภท (type)</label>
      <input id="type" name="type" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('type', $asset->type ?? '')); ?>">
      <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="hidden">
      <input type="text" disabled aria-hidden="true">
    </div>
  </div>

  
  <?php if(isset($categories)): ?>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="category_id">หมวดหมู่</label>
      <?php if (isset($component)) { $__componentOriginal65b27876dc3636d5d043082d984d84ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65b27876dc3636d5d043082d984d84ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.search-select','data' => ['name' => 'category_id','id' => 'category_id','items' => $categories,'labelField' => 'name','valueField' => 'id','value' => old('category_id', $asset->category_id ?? null),'placeholder' => '— ไม่ระบุ —']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('search-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'category_id','id' => 'category_id','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($categories),'label-field' => 'name','value-field' => 'id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('category_id', $asset->category_id ?? null)),'placeholder' => '— ไม่ระบุ —']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $attributes = $__attributesOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__attributesOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $component = $__componentOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__componentOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
      <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
  <?php endif; ?>

  <?php if(isset($departments)): ?>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="department_id">หน่วยงาน</label>
      <?php $hasDepartments = ($departments instanceof \Illuminate\Support\Collection) ? $departments->isNotEmpty() : count($departments ?? []) > 0; ?>
      <?php if($hasDepartments): ?>
        <?php if (isset($component)) { $__componentOriginal65b27876dc3636d5d043082d984d84ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65b27876dc3636d5d043082d984d84ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.search-select','data' => ['name' => 'department_id','id' => 'department_id','items' => $departments,'labelField' => 'display_name','valueField' => 'id','value' => old('department_id', $asset->department_id ?? null),'placeholder' => '— ไม่ระบุ —']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('search-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'department_id','id' => 'department_id','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($departments),'label-field' => 'display_name','value-field' => 'id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('department_id', $asset->department_id ?? null)),'placeholder' => '— ไม่ระบุ —']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $attributes = $__attributesOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__attributesOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $component = $__componentOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__componentOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
      <?php else: ?>
        <div class="mt-1 w-full rounded-lg border px-3 py-2 text-slate-400">ยังไม่มีข้อมูลหน่วยงาน (กรุณา seed)</div>
      <?php endif; ?>
      <?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="purchase_date">วันที่ซื้อ</label>
      <input id="purchase_date" name="purchase_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('purchase_date', optional($asset->purchase_date ?? null)?->format('Y-m-d'))); ?>">
      <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="warranty_expire">หมดประกัน</label>
      <input id="warranty_expire" name="warranty_expire" type="date" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('warranty_expire', optional($asset->warranty_expire ?? null)?->format('Y-m-d'))); ?>">
      <?php $__errorArgs = ['warranty_expire'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="brand">ยี่ห้อ</label>
      <input id="brand" name="brand" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('brand', $asset->brand ?? '')); ?>">
      <?php $__errorArgs = ['brand'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="model">รุ่น</label>
      <input id="model" name="model" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('model', $asset->model ?? '')); ?>">
      <?php $__errorArgs = ['model'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium text-slate-700" for="serial_number">Serial</label>
      <input id="serial_number" name="serial_number" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('serial_number', $asset->serial_number ?? '')); ?>">
      <?php $__errorArgs = ['serial_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700" for="location">ที่ตั้ง</label>
      <input id="location" name="location" type="text" class="mt-1 w-full rounded-lg border px-3 py-2"
             value="<?php echo e(old('location', $asset->location ?? '')); ?>">
      <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
  </div>

  <div>
    <label class="block text-sm font-medium text-slate-700" for="status">สถานะ</label>
    <select id="status" name="status" class="mt-1 w-full rounded-lg border px-3 py-2">
      <?php $statuses = ['active'=>'ใช้งาน','in_repair'=>'ซ่อม','disposed'=>'จำหน่าย']; ?>
      <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($k); ?>" <?php if(old('status', $asset->status ?? 'active') === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>
</div>
<?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/_fields.blade.php ENDPATH**/ ?>