
<?php $__env->startSection('title','Asset Detail'); ?>

<?php
  $badge = [
    'active'    => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'in_repair' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'disposed'  => 'bg-rose-50 text-rose-700 ring-rose-200',
  ][$asset->status] ?? 'bg-zinc-50 text-zinc-700 ring-zinc-200';
?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <a href="<?php echo e(route('assets.index')); ?>" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6" /></svg>
          Back
        </a>
        <span class="rounded-full px-2.5 py-1 text-xs ring-1 <?php echo e($badge); ?>">
          <?php echo e(ucfirst(str_replace('_',' ',$asset->status))); ?>

        </span>
      </div>
      <h1 class="text-xl font-semibold tracking-tight text-zinc-900 flex items-center gap-2">
        <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4" /></svg>
        <?php echo e($asset->name ?? 'Asset #'.$asset->id); ?>

        <span class="text-sm font-medium text-zinc-500">#<?php echo e($asset->asset_code ?? $asset->id); ?></span>
      </h1>
      <p class="text-xs text-zinc-500">Last updated <?php echo e($asset->updated_at?->diffForHumans()); ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <a href="<?php echo e(route('assets.edit', $asset)); ?>" class="inline-flex items-center gap-1 rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
        Edit
      </a>
      <button onclick="window.print()" class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        Print
      </button>
      <form method="POST" action="<?php echo e(route('assets.destroy', $asset)); ?>" class="inline-flex"
            onsubmit="window.dispatchEvent(new CustomEvent('app:toast',{detail:{type:'info',message:'กำลังลบ...'}})); return confirm('Delete this asset?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm text-rose-700 hover:bg-rose-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6v12"/><path d="M16 6v12"/><path d="M5 6l1 14a2 2 0 002 2h8a2 2 0 002-2l1-14"/><path d="M10 6V4h4v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-3">
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 text-sm text-zinc-500">
              <span>Asset Code:</span>
              <span class="font-medium text-zinc-700"><?php echo e($asset->asset_code ?? '—'); ?></span>
            </div>
            <?php if($asset->serial_number): ?>
              <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-medium text-zinc-700">S/N <?php echo e($asset->serial_number); ?></span>
            <?php endif; ?>
          </div>
          <div>
            <div class="text-xs uppercase tracking-wide text-zinc-500">Name</div>
            <div class="text-lg font-semibold text-zinc-900"><?php echo e($asset->name ?? '—'); ?></div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
            <div class="text-[11px] font-medium text-zinc-500">Repair Requests</div>
            <div class="text-xl font-semibold text-zinc-800"><?php echo e($asset->maintenance_requests_count ?? 0); ?></div>
          </div>
          <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center">
            <div class="text-[11px] font-medium text-zinc-500">Attachments</div>
            <div class="text-xl font-semibold text-zinc-800"><?php echo e($asset->attachments_count ?? 0); ?></div>
          </div>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
	  <?php if (isset($component)) { $__componentOriginalb47e110f48a73179e21f117567697c76 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb47e110f48a73179e21f117567697c76 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Department','value' => optional($asset->department)->name ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Department','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->department)->name ?? '—')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Category','value' => optional($asset->categoryRef)->name ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Category','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(optional($asset->categoryRef)->name ?? '—')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.asset.meta','data' => ['label' => 'Updated','value' => $asset->updated_at?->format('Y-m-d H:i')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('asset.meta'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Updated','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($asset->updated_at?->format('Y-m-d H:i'))]); ?>
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
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="font-medium text-zinc-800">Quick Actions</div>
        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
      </div>
      <div class="mt-4 grid gap-2">
        <a href="<?php echo e(route('assets.edit', $asset)); ?>" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>Edit details</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="<?php echo e(url('/maintenance/requests/create?asset_id='.$asset->id)); ?>" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>Create repair request</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="<?php echo e(url('/maintenance/requests?asset_id='.$asset->id)); ?>" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>View repair history</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
        <a href="<?php echo e(url('/api/assets/'.$asset->id.'?pretty=1')); ?>" target="_blank" class="group flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50">
          <span>View JSON</span>
          <svg class="h-4 w-4 text-zinc-400 group-hover:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </a>
      </div>
    </div>
  </div>

  
  <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div class="font-medium text-zinc-800">Recent Repair Logs</div>
        <span class="text-[11px] text-zinc-500"><?php echo e($logs->count()); ?> entries</span>
      </div>
      <div class="divide-y divide-zinc-200">
      <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="flex items-start gap-3 py-2">
          <div class="w-20 shrink-0 text-xs text-zinc-500"><?php echo e($log->created_at?->format('Y-m-d H:i')); ?></div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-zinc-800"><?php echo e(str_replace('_',' ', ucfirst($log->action))); ?></div>
            <?php if($log->note): ?>
              <div class="text-xs text-zinc-600 line-clamp-2"><?php echo e($log->note); ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="py-4 text-sm text-zinc-500">No recent logs.</div>
      <?php endif; ?>
      </div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div class="font-medium text-zinc-800">Attachments</div>
        <span class="text-[11px] text-zinc-500"><?php echo e(($attachments ?? collect())->count()); ?> files</span>
      </div>
      <?php $attList = $attachments ?? collect(); ?>
      <div class="divide-y divide-zinc-200">
      <?php $__empty_1 = true; $__currentLoopData = $attList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="flex items-center justify-between py-2">
          <div class="truncate text-sm max-w-xs"><?php echo e($att->original_name ?? ('Attachment #'.$att->id)); ?></div>
          <?php if(!empty($att->url)): ?>
            <a href="<?php echo e($att->url); ?>" target="_blank" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14L21 3"/><path d="M21 3v6"/><path d="M21 3h-6"/><path d="M13 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-7"/></svg>
              Open
            </a>
          <?php else: ?>
            <span class="text-[11px] text-zinc-500">no link</span>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="py-4 text-sm text-zinc-500">No attachments.</div>
      <?php endif; ?>
      </div>
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