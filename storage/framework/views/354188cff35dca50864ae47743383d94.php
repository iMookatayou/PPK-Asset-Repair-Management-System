
<?php $__env->startSection('title','Create Asset'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <a href="<?php echo e(route('assets.index')); ?>" class="text-zinc-600 hover:underline">Back</a>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <form method="POST" action="<?php echo e(route('assets.store')); ?>" class="rounded-xl border bg-white p-6 space-y-4">
    <?php echo $__env->make('assets.form', ['asset'=>null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="flex justify-end gap-2">
      <a href="<?php echo e(route('assets.index')); ?>" class="px-4 py-2 rounded border">Cancel</a>
      <button class="px-4 py-2 rounded bg-emerald-600 text-white">Save</button>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/create.blade.php ENDPATH**/ ?>