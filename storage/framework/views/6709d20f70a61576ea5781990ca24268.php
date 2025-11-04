
<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Users</h1>
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">Create</a>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <?php if(session('status')): ?>
    <div class="alert alert-success mb-4"><?php echo e(session('status')); ?></div>
  <?php endif; ?>

  
  <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
    <input type="text" name="s" value="<?php echo e($filters['s'] ?? ''); ?>" placeholder="Search name/email/department"
           class="input input-bordered w-full" />
    <select name="role" class="select select-bordered">
      <option value="">All roles</option>
      <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($r); ?>" <?php if(($filters['role'] ?? '') === $r): echo 'selected'; endif; ?>><?php echo e(ucfirst($r)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <input type="text" name="department" value="<?php echo e($filters['department'] ?? ''); ?>" placeholder="Department"
           class="input input-bordered w-full" />
    <button class="btn btn-outline">Filter</button>
  </form>

  <form method="POST" action="<?php echo e(route('admin.users.bulk')); ?>" class="mb-3">
    <?php echo csrf_field(); ?>
    <div class="flex flex-col md:flex-row md:items-center md:gap-3 gap-2 mb-2">
      <div class="join">
        <select name="action" class="select select-bordered join-item" required>
          <option value="">Bulk action…</option>
          <option value="change_role">Change role</option>
          <option value="delete">Delete</option>
        </select>
        <select name="role" class="select select-bordered join-item">
          <option value="">— role —</option>
          <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($r); ?>"><?php echo e(ucfirst($r)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <button class="btn btn-warning">Apply</button>
    </div>

    <div class="overflow-x-auto rounded-lg border">
      <table class="table">
        <thead>
        <tr>
          <th class="w-10"><input type="checkbox" class="checkbox" onclick="document.querySelectorAll('.ck-user').forEach(c=>c.checked=this.checked)" /></th>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Department</th>
          <th>Role</th>
          <th>Created</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td>
              <?php if(auth()->id() !== $u->id): ?>
                <input type="checkbox" name="ids[]" value="<?php echo e($u->id); ?>" class="checkbox ck-user" />
              <?php endif; ?>
            </td>
            <td><?php echo e($u->id); ?></td>
            <td class="font-medium"><?php echo e($u->name); ?></td>
            <td><?php echo e($u->email); ?></td>
            <td><?php echo e($u->department ?? '—'); ?></td>
            <td>
              <span class="badge"><?php echo e(ucfirst($u->role)); ?></span>
            </td>
            <td><?php echo e($u->created_at?->format('Y-m-d')); ?></td>
            <td class="text-right space-x-2">
              <a href="<?php echo e(route('admin.users.edit', $u)); ?>" class="btn btn-xs">Edit</a>
              <?php if(auth()->id() !== $u->id): ?>
                <form action="<?php echo e(route('admin.users.destroy', $u)); ?>" method="POST" class="inline"
                      onsubmit="return confirm('Delete this user?');">
                  <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                  <button class="btn btn-xs btn-error">Delete</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="8" class="text-center text-zinc-500 py-8">No users found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <div class="mt-4"><?php echo e($users->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/users/index.blade.php ENDPATH**/ ?>