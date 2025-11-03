
<?php $__env->startSection('title','Edit Asset'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Edit Asset</h1>
    <a href="<?php echo e(route('assets.show',$asset)); ?>" class="text-zinc-600 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded">
      Back
    </a>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <form method="POST" action="<?php echo e(route('assets.update',$asset)); ?>" class="space-y-4">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <?php echo $__env->make('asset.form', ['asset' => $asset], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="flex items-center justify-between">
      <form method="POST" action="<?php echo e(route('assets.destroy',$asset)); ?>" onsubmit="return confirm('Delete this asset?')">
        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
        <button class="rounded-lg border border-rose-300 px-4 py-2 text-rose-700 hover:bg-rose-50">Delete</button>
      </form>
      <div class="flex gap-2">
        <a href="<?php echo e(route('assets.show',$asset)); ?>" class="rounded-lg border px-4 py-2 text-zinc-700 hover:bg-zinc-50">Cancel</a>
        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Update</button>
      </div>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/edit.blade.php ENDPATH**/ ?>