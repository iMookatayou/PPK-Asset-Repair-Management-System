

<?php $__env->startSection('title','Manage Users'); ?>

<?php
  /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
  $roles   = $roles   ?? ['admin','technician','staff'];
  $filters = $filters ?? ['s'=>'','role'=>'','department'=>''];
?>

<?php $__env->startSection('content'); ?>
  
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">Manage Users</h1>
    <a href="<?php echo e(route('admin.users.create')); ?>"
       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
      <span>Create</span>
    </a>
  </div>

  <?php if(session('status')): ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">
      <?php echo e(session('status')); ?>

    </div>
  <?php endif; ?>

  <?php if($errors->any()): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700">
      <ul class="list-disc pl-5">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  
  <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    <input type="text" name="s" value="<?php echo e($filters['s']); ?>"
           placeholder="Search name/email/department"
           class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <select name="role" class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
      <option value="">All roles</option>
      <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($r); ?>" <?php if($filters['role']===$r): echo 'selected'; endif; ?>><?php echo e(ucfirst($r)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <input type="text" name="department" value="<?php echo e($filters['department']); ?>"
           placeholder="Department"
           class="w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <div class="flex gap-2">
      <button class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Filter</button>
      <a href="<?php echo e(route('admin.users.index')); ?>"
         class="rounded-lg border border-zinc-300 px-3 py-2 text-zinc-700 hover:bg-zinc-50">Reset</a>
    </div>
  </form>

  
  <form method="POST" action="<?php echo e(route('admin.users.bulk')); ?>">
    <?php echo csrf_field(); ?>
    <div class="mb-2 flex flex-wrap items-center gap-2">
      <select name="action" class="rounded-lg border border-zinc-300 px-3 py-2">
        <option value="change_role">Change role</option>
        <option value="delete">Delete</option>
      </select>
      <select name="role" class="rounded-lg border border-zinc-300 px-3 py-2">
        <option value="">-- role --</option>
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($r); ?>"><?php echo e(ucfirst($r)); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
      <button type="submit"
              class="rounded-lg bg-amber-500 px-3 py-2 text-white hover:bg-amber-600"
              onclick="return confirm('Confirm bulk action?');">
        Apply
      </button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
      <table class="min-w-full divide-y divide-zinc-200">
        <thead class="bg-zinc-50 text-left text-sm text-zinc-700">
          <tr>
            <th class="px-3 py-2"><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
            <th class="px-3 py-2">Name</th>
            <th class="px-3 py-2">Email</th>
            <th class="px-3 py-2">Department</th>
            <th class="px-3 py-2">Role</th>
            <th class="px-3 py-2">Created</th>
            <th class="px-3 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 text-sm">
          <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td class="px-3 py-2 align-middle">
                <?php if($u->id !== auth()->id()): ?>
                  <input type="checkbox" class="row-check" name="ids[]" value="<?php echo e($u->id); ?>">
                <?php endif; ?>
              </td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                    <?php echo e(strtoupper(mb_substr($u->name,0,1))); ?>

                  </div>
                  <div>
                    <div class="font-medium"><?php echo e($u->name); ?></div>
                    <div class="text-xs text-zinc-500">#<?php echo e($u->id); ?></div>
                  </div>
                </div>
              </td>
              <td class="px-3 py-2"><?php echo e($u->email); ?></td>
              <td class="px-3 py-2"><?php echo e($u->department ?: '-'); ?></td>
              <td class="px-3 py-2">
                <?php
                  $roleCls = $u->role==='admin'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                    : 'bg-zinc-50 text-zinc-700 border-zinc-300';
                ?>
                <span class="rounded-full border px-2 py-0.5 text-xs <?php echo e($roleCls); ?>">
                  <?php echo e(ucfirst($u->role)); ?>

                </span>
              </td>
              <td class="px-3 py-2 whitespace-nowrap"><?php echo e($u->created_at?->format('Y-m-d H:i')); ?></td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-end gap-2">
                  <a href="<?php echo e(route('admin.users.edit', $u)); ?>"
                     class="rounded-md border border-emerald-300 px-2 py-1 text-emerald-700 hover:bg-emerald-50">Edit</a>
                  <?php if($u->id !== auth()->id()): ?>
                    <form method="POST" action="<?php echo e(route('admin.users.destroy', $u)); ?>"
                          onsubmit="return confirm('Delete this user?');">
                      <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                      <button class="rounded-md border border-rose-300 px-2 py-1 text-rose-600 hover:bg-rose-50">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="7" class="px-3 py-6 text-center text-zinc-500">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </form>

  <div class="mt-4"><?php echo e($users->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/admin/users/index.blade.php ENDPATH**/ ?>