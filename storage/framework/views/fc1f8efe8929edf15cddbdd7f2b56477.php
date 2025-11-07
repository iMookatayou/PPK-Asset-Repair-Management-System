

<?php $__env->startSection('title', 'Repair Queue — Pending'); ?>

<?php $__env->startSection('content'); ?>
<?php
  use Illuminate\Support\Str;
  /** @var \Illuminate\Pagination\LengthAwarePaginator $list */

  $q      = request('q');
  $status = request('status'); // null|pending|in_progress|completed

  // สรุปตัวเลข (กัน null → 0 เสมอ)
  $stats   = is_array($stats ?? null) ? $stats : [];
  $total   = (int) ($stats['total']       ?? $list->total());
  $pending = (int) ($stats['pending']     ?? 0);
  $doing   = (int) ($stats['in_progress'] ?? 0);
  $done    = (int) ($stats['completed']   ?? 0);

  // ป้ายระดับความเร่งด่วน (โทนเรียบ + เส้นกรอบ)
  $priBadge = function (?string $p) {
    $p = strtolower((string)$p);
    return match (true) {
      in_array($p, ['urgent','high']) => 'ring-1 ring-rose-300 text-rose-800 bg-white',
      $p === 'medium'                 => 'ring-1 ring-amber-300 text-amber-800 bg-white',
      default                         => 'ring-1 ring-emerald-300 text-emerald-800 bg-white',
    };
  };
?>


<div class="pt-3 md:pt-4"></div>

<div class="w-full px-4 md:px-6 lg:px-8 flex flex-col gap-5">

  
  <div class="rounded-lg border border-zinc-300 bg-white">
    <div class="px-5 py-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        
        <div class="flex items-start gap-3">
          <div class="grid h-9 w-9 place-items-center rounded-md bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-300">
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 8l-2 2 9 9 2-2-9-9zM16 3l5 5-3 3-5-5 3-3z"/>
            </svg>
          </div>
          <div>
            <h1 class="text-[17px] font-semibold text-zinc-900">Pending Repair Requests</h1>
            <p class="text-[13px] text-zinc-600">รอรับเข้าคิว / มอบหมาย / เริ่มทำงาน</p>
          </div>
        </div>

        
        <div class="flex flex-wrap items-center gap-2 text-[13px]">
          <span class="inline-flex items-center gap-2 rounded-md border border-zinc-400 bg-white px-3 py-1 text-zinc-900">
            <span class="text-zinc-700">ทั้งหมด</span>
            <strong class="tabular-nums"><?php echo e($total); ?></strong>
          </span>
          <span class="inline-flex items-center gap-2 rounded-md border border-amber-300 bg-white px-3 py-1 text-amber-800">
            <span>รอคิว</span>
            <strong class="tabular-nums"><?php echo e($pending); ?></strong>
          </span>
          <span class="inline-flex items-center gap-2 rounded-md border border-sky-300 bg-white px-3 py-1 text-sky-800">
            <span>ระหว่างดำเนินการ</span>
            <strong class="tabular-nums"><?php echo e($doing); ?></strong>
          </span>
          <span class="inline-flex items-center gap-2 rounded-md border border-emerald-300 bg-white px-3 py-1 text-emerald-800">
            <span>เสร็จสิ้น</span>
            <strong class="tabular-nums"><?php echo e($done); ?></strong>
          </span>

          
          <a href="<?php echo e(route('repairs.my_jobs')); ?>"
             class="ml-2 inline-flex items-center gap-2 rounded-lg border border-indigo-700 bg-indigo-700 px-4 py-2 text-[13px] font-medium text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 2h6a2 2 0 0 1 2 2v2h-2V4H9v2H7V4a2 2 0 0 1 2-2zm3 8h4m-8 0h.01M9 16h6m-8 0h.01M5 8h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"/>
            </svg>
            My Jobs
          </a>
        </div>
      </div>

      
      <div class="mt-4 h-px bg-zinc-200"></div>

      
      <form method="GET" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12">
        
        <div class="md:col-span-4">
          <label for="status" class="mb-1 block text-[12px] text-zinc-600">สถานะ</label>
          <select id="status" name="status"
                  class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-600"
                  onchange="this.form.submit()">
            <option value="" <?php if(empty($status)): echo 'selected'; endif; ?>>ทั้งหมด</option>
            <option value="pending" <?php if($status==='pending'): echo 'selected'; endif; ?>>รอคิว</option>
            <option value="in_progress" <?php if($status==='in_progress'): echo 'selected'; endif; ?>>ระหว่างดำเนินการ</option>
            <option value="completed" <?php if($status==='completed'): echo 'selected'; endif; ?>>เสร็จสิ้น</option>
          </select>
        </div>

        
        <div class="md:col-span-8">
          <label for="q" class="mb-1 block text-[12px] text-zinc-600">คำค้นหา</label>
          <div class="flex gap-2">
            <div class="relative grow">
              <input id="q" name="q" value="<?php echo e($q); ?>"
                     placeholder="เช่น ชื่องาน, รายละเอียด, ผู้แจ้ง, หมายเลขทรัพย์สิน"
                     class="w-full rounded-md border border-zinc-300 pl-9 pr-3 py-2 text-sm placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
              <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </span>
            </div>
            <button type="submit"
                    class="rounded-md border border-emerald-700 bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">
              ค้นหา
            </button>
            <a href="<?php echo e(request()->url()); ?>"
               class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">
              ล้าง
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  
  <div class="rounded-lg border border-zinc-300 bg-white overflow-hidden">
    <div class="relative overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-zinc-50">
          <tr class="text-zinc-700 border-b border-zinc-200">
            <th class="p-3 text-left font-medium w-[40%]">เรื่อง</th>
            <th class="p-3 text-left font-medium w-[20%]">ทรัพย์สิน</th>
            <th class="p-3 text-left font-medium w-[18%]">ผู้แจ้ง</th>
            <th class="p-3 text-left font-medium w-[14%]">วันที่แจ้ง</th>
            <th class="p-3 text-right font-medium w-[8%]">การดำเนินการ</th>
          </tr>
        </thead>

        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr class="align-top hover:bg-zinc-50 border-b last:border-0">
            
            <td class="p-3">
              <a href="<?php echo e(route('maintenance.requests.show', $r)); ?>"
                 class="block max-w-full truncate font-medium text-zinc-900 hover:underline">
                <?php echo e(Str::limit($r->title, 90)); ?>

              </a>
              <?php if(!empty($r->description)): ?>
                <p class="mt-1 text-xs leading-relaxed text-zinc-600">
                  <?php echo e(Str::limit($r->description, 140)); ?>

                </p>
              <?php endif; ?>
              <div class="mt-2 flex flex-wrap gap-2">
                <?php if(!empty($r->priority)): ?>
                  <span class="rounded-full bg-white px-2 py-1 text-[11px] <?php echo e($priBadge($r->priority)); ?>">
                    <?php echo e(ucfirst(strtolower($r->priority))); ?>

                  </span>
                <?php endif; ?>
                <?php if(!empty($r->category)): ?>
                  <span class="rounded-full bg-white px-2 py-1 text-[11px] ring-1 ring-zinc-300 text-zinc-700">
                    <?php echo e($r->category); ?>

                  </span>
                <?php endif; ?>
              </div>
            </td>

            
            <td class="p-3">
              <div class="font-medium text-zinc-900">#<?php echo e($r->asset_id); ?></div>
              <div class="max-w-full truncate text-xs text-zinc-600"><?php echo e($r->asset->name ?? '—'); ?></div>
              <?php if(!empty($r->asset?->location)): ?>
                <div class="mt-0.5 max-w-full truncate text-[11px] text-zinc-500"><?php echo e($r->asset->location); ?></div>
              <?php endif; ?>
            </td>

            
            <td class="p-3">
              <div class="max-w-full truncate text-zinc-900"><?php echo e($r->reporter->name ?? '—'); ?></div>
              <?php
                $deptLabel = $r->reporter->department_name
                              ?? $r->reporter->department
                              ?? null;
              ?>
              <?php if(!empty($deptLabel)): ?>
                <div class="max-w-full truncate text-[11px] text-zinc-500"><?php echo e($deptLabel); ?></div>
              <?php endif; ?>
            </td>

            
            <td class="p-3">
              <div class="font-medium text-zinc-800">
                <?php echo e(optional($r->request_date)->format('Y-m-d H:i') ?? '—'); ?>

              </div>
              <?php if($r->request_date): ?>
                <div class="text-[11px] text-zinc-500"><?php echo e($r->request_date->diffForHumans()); ?></div>
              <?php endif; ?>
            </td>

            
            <td class="p-3">
              <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('tech-only')): ?>
                <div class="hidden justify-end gap-2 sm:flex">
                  <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                    <?php echo csrf_field(); ?> <input type="hidden" name="action" value="accept">
                    <button class="inline-flex items-center rounded-md border border-indigo-700 bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-800">
                      รับงาน
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="technician_id" value="<?php echo e(auth()->id()); ?>">
                    <button class="inline-flex items-center rounded-md border border-sky-700 bg-sky-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-800">
                      มอบหมาย
                    </button>
                  </form>
                  <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>">
                    <?php echo csrf_field(); ?> <input type="hidden" name="action" value="start">
                    <button class="inline-flex items-center rounded-md border border-emerald-700 bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-800">
                      เริ่มงาน
                    </button>
                  </form>
                </div>

                
                <div class="relative sm:hidden text-right">
                  <details class="group inline-block">
                    <summary class="flex cursor-pointer list-none justify-end">
                      <span class="inline-flex items-center rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs">การดำเนินการ ▾</span>
                    </summary>
                    <div class="absolute right-0 mt-1 w-44 rounded-md border border-zinc-300 bg-white p-2 shadow-sm">
                      <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="block">
                        <?php echo csrf_field(); ?> <input type="hidden" name="action" value="accept">
                        <button class="w-full rounded-md bg-indigo-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-800">รับงาน</button>
                      </form>
                      <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="mt-1 block">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="technician_id" value="<?php echo e(auth()->id()); ?>">
                        <button class="w-full rounded-md bg-sky-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-800">มอบหมาย</button>
                      </form>
                      <form method="POST" action="<?php echo e(route('maintenance.requests.transition', $r)); ?>" class="mt-1 block">
                        <?php echo csrf_field(); ?> <input type="hidden" name="action" value="start">
                        <button class="w-full rounded-md bg-emerald-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-800">เริ่มงาน</button>
                      </form>
                    </div>
                  </details>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr>
            <td colspan="5" class="p-12 text-center text-zinc-600">ไม่พบรายการที่รอดำเนินการ</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  
  <div class="mt-4">
    <?php echo e($list->withQueryString()->links()); ?>

  </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/repair/queue.blade.php ENDPATH**/ ?>