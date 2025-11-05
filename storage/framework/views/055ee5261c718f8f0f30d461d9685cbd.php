
<?php $__env->startSection('title','Edit User'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Edit User</h1>
    <a href="<?php echo e(route('admin.users.index')); ?>" class="text-zinc-600 hover:underline">Back</a>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <?php if(session('status')): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">
      <?php echo e(session('status')); ?>

    </div>
  <?php endif; ?>
  <?php if($errors->any()): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
      <ul class="list-disc pl-5">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?php echo e(route('admin.users.update', $user)); ?>" class="space-y-4">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <?php echo $__env->make('admin.users._form', ['user' => $user, 'roles' => $roles], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="flex items-center gap-3 pt-2">
      <button class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Update</button>

      <?php if($user->id !== auth()->id()): ?>
        <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>"
              onsubmit="return confirm('Delete this user?');">
          <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
          <button class="rounded-lg bg-rose-600 px-4 py-2 text-white hover:bg-rose-700">Delete</button>
        </form>
      <?php endif; ?>
    </div>
  </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/admin/users/edit.blade.php ENDPATH**/ ?>