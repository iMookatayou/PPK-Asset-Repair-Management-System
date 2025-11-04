
<?php $__env->startSection('title','Edit Asset'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <a href="<?php echo e(route('assets.show', $asset)); ?>" class="text-zinc-600 hover:underline">Back</a>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <form method="POST" action="<?php echo e(route('assets.update', $asset)); ?>" class="rounded-xl border bg-white p-6 space-y-4">
    <?php echo method_field('PUT'); ?>
    <?php echo $__env->make('assets.form', ['asset'=>$asset], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="flex justify-between">
      <form method="POST" action="<?php echo e(route('assets.destroy', $asset)); ?>" onsubmit="return confirm('Delete this asset?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="px-4 py-2 rounded border text-rose-700">Delete</button>
      </form>
      <div class="flex gap-2">
        <a href="<?php echo e(route('assets.show', $asset)); ?>" class="px-4 py-2 rounded border">Cancel</a>
        <button class="px-4 py-2 rounded bg-emerald-600 text-white">Update</button>
      </div>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/edit.blade.php ENDPATH**/ ?>