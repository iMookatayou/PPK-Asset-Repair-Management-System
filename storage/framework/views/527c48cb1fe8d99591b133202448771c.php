
<?php $__env->startSection('title','Asset Detail'); ?>

<?php
  $badge = [
    'active'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'disposed'  => 'bg-rose-50 text-rose-700 ring-rose-200',
  ][$asset->status] ?? 'bg-zinc-50 text-zinc-700 ring-zinc-200';
?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?php echo e(route('assets.index')); ?>"
         class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Back</a>

      <div class="text-lg font-semibold">
        Asset • <span class="text-zinc-500">#<?php echo e($asset->id); ?></span>
      </div>

      <span class="rounded-full px-2.5 py-1 text-xs ring-1 <?php echo e($badge); ?>">
        <?php echo e(ucfirst(str_replace('_',' ',$asset->status))); ?>

      </span>
    </div>

    <div class="flex items-center gap-2">
      <a href="<?php echo e(route('assets.edit', $asset)); ?>"
         class="rounded-lg bg-zinc-900 px-3 py-2 text-white hover:bg-zinc-800">Edit</a>

      <button onclick="window.print()"
              class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Print</button>

      <form method="POST" action="<?php echo e(route('assets.destroy', $asset)); ?>"
            onsubmit="return confirm('Delete this asset?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="rounded-lg border px-3 py-2 text-rose-700 hover:bg-rose-50">Delete</button>
      </form>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  
  <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="md:col-span-2 rounded-xl border border-zinc-200 bg-white p-5">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-sm text-zinc-500">Asset Code</div>
          <div class="text-lg font-semibold"><?php echo e($asset->asset_code); ?></div>

          <div class="mt-3">
            <div class="text-sm text-zinc-500">Name</div>
            <div class="text-base font-medium"><?php echo e($asset->name); ?></div>
            <?php if($asset->serial_number): ?>
              <div class="text-xs text-zinc-500 mt-0.5">S/N: <?php echo e($asset->serial_number); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 text-center">
          <div class="rounded-xl border p-3">
            <div class="text-xs text-zinc-500">Repair Requests</div>
            <div class="text-xl font-semibold"><?php echo e($asset->maintenance_requests_count ?? 0); ?></div>
          </div>
          <div class="rounded-xl border p-3">
            <div class="text-xs text-zinc-500">Attachments</div>
            <div class="text-xl font-semibold"><?php echo e($asset->attachments_count ?? 0); ?></div>
          </div>
        </div>
      </div>

      
      <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Dept','value' => optional($asset->department)->name ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Dept','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->department)->name ?? '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Category','value' => optional($asset->categoryRef)->name ?? ($asset->category ?? '—')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Category','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->categoryRef)->name ?? ($asset->category ?? '—'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Location','value' => $asset->location ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Location','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset->location ?? '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Type','value' => $asset->type ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Type','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset->type ?? '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Brand / Model','value' => trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Brand / Model','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Warranty','value' => optional($asset->warranty_expire)?->format('Y-m-d') ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Warranty','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->warranty_expire)?->format('Y-m-d') ?? '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Purchased','value' => optional($asset->purchase_date)?->format('Y-m-d') ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Purchased','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->purchase_date)?->format('Y-m-d') ?? '—')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Updated at','value' => $asset->updated_at?->format('Y-m-d H:i')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Updated at','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset->updated_at?->format('Y-m-d H:i'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $attributes = $__attributesOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__attributesOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb47e110f48a73179e21f117567697c76)): ?>
<?php $component = $__componentOriginalb47e110f48a73179e21f117567697c76; ?>
<?php unset($__componentOriginalb47e110f48a73179e21f117567697c76); ?>
<?php endif; ?>
      </div>
    </div>

    
    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="font-medium">Quick actions</div>
      <div class="mt-3 grid gap-2">
        <a href="<?php echo e(route('assets.edit', $asset)); ?>"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Edit details</a>
        <a href="<?php echo e(url('/maintenance/requests/create?asset_id='.$asset->id)); ?>"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">Create repair request</a>
        <a href="<?php echo e(url('/maintenance/requests?asset_id='.$asset->id)); ?>"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50">View repair history</a>
        <a href="<?php echo e(url('/api/assets/'.$asset->id.'?pretty=1')); ?>"
           class="rounded-lg border px-3 py-2 text-zinc-700 hover:bg-zinc-50" target="_blank">View JSON</a>
      </div>
    </div>
  </div>

  
  <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="mb-3 font-medium">Recent Repair Logs</div>
      <?php $__currentLoopData = $attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          // ชื่อที่จะแสดง
          $displayName = $file->original_name
              ?? $file->filename
              ?? $file->file_name
              ?? $file->name
              ?? ('Attachment #'.$file->id);

          // URL ที่จะเปิด
          $displayUrl = $file->url
              ?? $file->public_url     // เผื่อคุณมีคอลัมน์นี้
              ?? null;                 // ถ้ายังไม่มี ก็ไม่ทำลิงก์
        ?>

        <div class="flex items-center justify-between border-b py-2">
          <div class="truncate text-sm"><?php echo e($displayName); ?></div>

          <?php if($displayUrl): ?>
            <a href="<?php echo e($displayUrl); ?>" target="_blank" class="text-emerald-700 hover:underline">Open</a>
          <?php else: ?>
            <span class="text-xs text-zinc-500">no link</span>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5">
      <div class="mb-3 font-medium">Attachments</div>
      <?php $__empty_1 = true; $__currentLoopData = ($attachments ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="flex items-center justify-between border-b py-2">
          <div class="truncate text-sm"><?php echo e($file->original_name); ?></div>
          <a href="<?php echo e($file->url); ?>" target="_blank"
             class="text-emerald-700 hover:underline">Open</a>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-sm text-zinc-500">No attachments.</div>
      <?php endif; ?>
    </div>
  </div>

  
  <style>
    @media print {
      nav, aside, .no-print, .btn, .rounded-lg.border { display:none !important; }
      main { padding:0 !important; }
    }
  </style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/show.blade.php ENDPATH**/ ?>