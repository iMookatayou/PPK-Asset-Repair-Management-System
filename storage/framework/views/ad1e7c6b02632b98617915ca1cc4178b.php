<?php
  $roles = $roles ?? ['admin','technician','staff'];
?>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
  <div>
    <label class="block text-sm font-medium text-zinc-700">Name <span class="text-rose-600">*</span></label>
    <input type="text" name="name" value="<?php echo e(old('name', $user->name ?? '')); ?>" required
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Email <span class="text-rose-600">*</span></label>
    <input type="email" name="email" value="<?php echo e(old('email', $user->email ?? '')); ?>" required
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">
      Password <?php echo e(isset($user) ? '(leave blank to keep)' : ''); ?> <?php echo e(isset($user) ? '' : ' *'); ?>

    </label>
    <input type="password" name="password"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Confirm Password</label>
    <input type="password" name="password_confirmation"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Department</label>
    <input type="text" name="department" value="<?php echo e(old('department', $user->department ?? '')); ?>"
           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
    <?php $__errorArgs = ['department'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>

  <div>
    <label class="block text-sm font-medium text-zinc-700">Role <span class="text-rose-600">*</span></label>
    <select name="role" required
            class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
      <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($r); ?>" <?php if(old('role', $user->role ?? '') === $r): echo 'selected'; endif; ?>><?php echo e(ucfirst($r)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>
</div>
<?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/admin/users/_form.blade.php ENDPATH**/ ?>