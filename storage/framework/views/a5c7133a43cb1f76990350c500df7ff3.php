

<?php $__env->startSection('title','My Repair Jobs'); ?>

<?php $__env->startSection('content'); ?>
<?php
  use Illuminate\Support\Str;

  $current = request('status');
  $isActive = fn(string $s = null) => ($current === $s) ? 'btn-primary' : 'btn-ghost';

  $statusStyles = [
    'pending'     => 'badge-warning badge-outline',
    'accepted'    => 'badge-info badge-outline',
    'in_progress' => 'badge-info',
    'on_hold'     => 'badge-ghost',
    'resolved'    => 'badge-success',
    'closed'      => 'badge-neutral',
    'cancelled'   => 'badge-neutral badge-outline',
  ];

  $humanize = fn($s) => Str::of($s)->replace('_',' ')->title();
?>

<div class="max-w-6xl mx-auto py-6 space-y-5">
  
  <div class="flex items-center justify-between gap-3">

    <div class="join hidden md:inline-flex">
      <a href="<?php echo e(route('repairs.my_jobs', ['status'=>'in_progress'])); ?>"
         class="btn btn-sm join-item <?php echo e($isActive('in_progress')); ?>">
        In Progress
      </a>
      <a href="<?php echo e(route('repairs.my_jobs', ['status'=>'resolved'])); ?>"
         class="btn btn-sm join-item <?php echo e($isActive('resolved')); ?>">
        Resolved
      </a>
      <a href="<?php echo e(route('repairs.my_jobs')); ?>"
         class="btn btn-sm join-item <?php echo e($isActive(null)); ?>">
        All
      </a>
    </div>

    
    <div class="md:hidden dropdown dropdown-end">
      <label tabindex="0" class="btn btn-sm btn-ghost">Filter</label>
      <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-40">
        <li><a class="<?php echo e($current === 'in_progress' ? 'active' : ''); ?>"
               href="<?php echo e(route('repairs.my_jobs', ['status'=>'in_progress'])); ?>">In Progress</a></li>
        <li><a class="<?php echo e($current === 'resolved' ? 'active' : ''); ?>"
               href="<?php echo e(route('repairs.my_jobs', ['status'=>'resolved'])); ?>">Resolved</a></li>
        <li><a class="<?php echo e(is_null($current) ? 'active' : ''); ?>"
               href="<?php echo e(route('repairs.my_jobs')); ?>">All</a></li>
      </ul>
    </div>
  </div>

  
  <div class="card bg-base-100 shadow-sm border hidden md:block">
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr class="text-xs text-base-content/70">
            <th class="w-[30%]">Subject</th>
            <th>Asset</th>
            <th>Reporter</th>
            <th>Status</th>
            <th>Updated</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td>
              <a href="<?php echo e(route('maintenance.requests.show', $r)); ?>" class="link link-hover font-medium">
                <?php echo e($r->title); ?>

              </a>
              <div class="text-xs opacity-70"><?php echo e(Str::limit($r->description ?? '', 90)); ?></div>
            </td>
            <td class="whitespace-nowrap">
              #<?php echo e($r->asset_id); ?> — <?php echo e($r->asset->name ?? '-'); ?>

            </td>
            <td class="whitespace-nowrap">
              <?php echo e($r->reporter->name ?? '-'); ?>

            </td>
            <td>
              <?php
                $style = $statusStyles[$r->status] ?? 'badge-ghost';
              ?>
              <span class="badge <?php echo e($style); ?>">
                <?php echo e($humanize($r->status)); ?>

              </span>
            </td>
            <td class="whitespace-nowrap text-sm opacity-80">
              <?php echo e(optional($r->updated_at)->format('Y-m-d H:i')); ?>

            </td>
            <td>
              <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('tech-only')): ?>
                <div class="flex justify-end gap-2">
                  <?php if($r->status==='pending'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="accept">
                      <button class="btn btn-xs md:btn-sm btn-info text-white">Queue</button>
                    </form>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="start">
                      <button class="btn btn-xs md:btn-sm btn-accent text-white">Start</button>
                    </form>
                  <?php elseif($r->status==='accepted'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="start">
                      <button class="btn btn-xs md:btn-sm btn-accent text-white">Start</button>
                    </form>
                  <?php elseif($r->status==='in_progress'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="hold">
                      <button class="btn btn-xs md:btn-sm btn-warning text-white">Hold</button>
                    </form>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="resolve">
                      <button class="btn btn-xs md:btn-sm btn-success text-white">Resolve</button>
                    </form>
                  <?php elseif($r->status==='resolved'): ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('admin-only')): ?>
                      <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-xs md:btn-sm btn-neutral text-white">Close</button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr>
            <td colspan="6">
              <div class="py-10 text-center text-base-content/60">
                No jobs found.
              </div>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body pt-0">
      <div class="flex justify-center">
        <?php echo e($list->withQueryString()->links()); ?>

      </div>
    </div>
  </div>

  
  <div class="grid grid-cols-1 gap-3 md:hidden">
    <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <div class="card bg-base-100 border">
        <div class="card-body p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
              <a href="<?php echo e(route('maintenance.requests.show', $r)); ?>"
                 class="link link-hover font-semibold">
                <?php echo e($r->title); ?>

              </a>
              <p class="text-sm opacity-70">
                <?php echo e(Str::limit($r->description ?? '', 120)); ?>

              </p>
            </div>
            <?php $style = $statusStyles[$r->status] ?? 'badge-ghost'; ?>
            <span class="badge <?php echo e($style); ?>"><?php echo e($humanize($r->status)); ?></span>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
            <div class="opacity-70">Asset</div>
            <div>#<?php echo e($r->asset_id); ?> — <?php echo e($r->asset->name ?? '-'); ?></div>

            <div class="opacity-70">Reporter</div>
            <div><?php echo e($r->reporter->name ?? '-'); ?></div>

            <div class="opacity-70">Updated</div>
            <div><?php echo e(optional($r->updated_at)->format('Y-m-d H:i')); ?></div>
          </div>

          <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('tech-only')): ?>
            <div class="mt-4">
              <?php if(in_array($r->status, ['pending','accepted','in_progress','resolved'])): ?>
                <div class="join join-vertical w-full">
                  <?php if($r->status==='pending'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="accept">
                      <button class="btn btn-sm btn-info text-white w-full">Queue</button>
                    </form>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="start">
                      <button class="btn btn-sm btn-accent text-white w-full">Start</button>
                    </form>
                  <?php elseif($r->status==='accepted'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="start">
                      <button class="btn btn-sm btn-accent text-white w-full">Start</button>
                    </form>
                  <?php elseif($r->status==='in_progress'): ?>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="hold">
                      <button class="btn btn-sm btn-warning text-white w-full">Hold</button>
                    </form>
                    <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="resolve">
                      <button class="btn btn-sm btn-success text-white w-full">Resolve</button>
                    </form>
                  <?php elseif($r->status==='resolved'): ?>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('admin-only')): ?>
                      <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="join-item">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-sm btn-neutral text-white w-full">Close</button>
                      </form>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <div class="card bg-base-100 border">
        <div class="card-body items-center text-center">
          <h3 class="font-medium">No jobs found</h3>
          <p class="text-sm opacity-70">Try switching the filter to see more.</p>
        </div>
      </div>
    <?php endif; ?>

    <div class="flex justify-center">
      <?php echo e($list->withQueryString()->links()); ?>

    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/repair/my-jobs.blade.php ENDPATH**/ ?>