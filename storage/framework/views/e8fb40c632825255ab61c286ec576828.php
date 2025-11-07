<?php $__env->startSection('title','Maintenance Requests'); ?>

<?php $__env->startSection('content'); ?>
<?php
  use Illuminate\Support\Str;

  // ====== Badge styles ======
  $statusClass = fn(?string $s) => match(strtolower((string)$s)) {
    'pending'     => 'ring-1 ring-zinc-300 text-zinc-800 bg-white',
    'accepted'    => 'ring-1 ring-indigo-300 text-indigo-800 bg-white',
    'in_progress' => 'ring-1 ring-sky-300 text-sky-800 bg-white',
    'on_hold'     => 'ring-1 ring-amber-300 text-amber-800 bg-white',
    'resolved'    => 'ring-1 ring-emerald-300 text-emerald-800 bg-white',
    'closed'      => 'ring-1 ring-emerald-300 text-emerald-800 bg-white',
    'cancelled'   => 'ring-1 ring-zinc-300 text-zinc-700 bg-zinc-50',
    default       => 'ring-1 ring-zinc-300 text-zinc-700 bg-white',
  };

  $statusLabel = fn(?string $s) => [
    'pending'     => 'รอคิว',
    'accepted'    => 'รับงานแล้ว',
    'in_progress' => 'ระหว่างดำเนินการ',
    'on_hold'     => 'พักไว้',
    'resolved'    => 'แก้ไขแล้ว',
    'closed'      => 'ปิดงาน',
    'cancelled'   => 'ยกเลิก',
  ][strtolower((string)$s)] ?? '-';

  $priorityClass = fn(?string $p) => match(strtolower((string)$p)) {
    'low'    => 'ring-1 ring-zinc-300 text-zinc-700 bg-white',
    'normal' => 'ring-1 ring-sky-300 text-sky-800 bg-white',
    'high'   => 'ring-1 ring-amber-300 text-amber-800 bg-white',
    'urgent' => 'ring-1 ring-rose-300 text-rose-800 bg-white',
    default  => 'ring-1 ring-zinc-300 text-zinc-700 bg-white',
  };

  $priorityLabel = fn(?string $p) => [
    'low'    => 'ต่ำ',
    'normal' => 'ปกติ',
    'high'   => 'สูง',
    'urgent' => 'เร่งด่วน',
  ][strtolower((string)$p)] ?? '-';
?>


<div class="pt-3 md:pt-4"></div>

<div class="w-full px-4 md:px-6 lg:px-8 flex flex-col gap-5">

  
  <div class="rounded-lg border border-zinc-300 bg-white">
    <div class="px-5 py-4">
      <div class="flex flex-wrap items-start justify-between gap-4">
        
        <div class="flex items-start gap-3">
          <div class="grid h-9 w-9 place-items-center rounded-md bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M6 11h12M8 15h8m-6 4h4"/>
            </svg>
          </div>
          <div>
            <h1 class="text-[17px] font-semibold text-zinc-900">Maintenance Requests</h1>
            <p class="text-[13px] text-zinc-600">รายการคำขอบำรุงรักษา • ค้นหา กรอง และตรวจทานคำขอ</p>
          </div>
        </div>

        
        <div class="flex shrink-0 items-center">
          <a href="<?php echo e(route('maintenance.requests.create')); ?>"
             class="inline-flex items-center gap-2 rounded-md border border-emerald-700 bg-emerald-700 px-4 py-2 text-[13px] font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600"
             onclick="showLoader()">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            สร้างคำขอใหม่
          </a>
        </div>
      </div>

      
      <div class="mt-4 h-px bg-zinc-200"></div>

      
      <form method="GET" action="<?php echo e(route('maintenance.requests.index')); ?>"
            class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12" onsubmit="showLoader()">

        
        <div class="md:col-span-6 min-w-0">
          <label for="q" class="mb-1 block text-[12px] text-zinc-600">คำค้นหา</label>
          <div class="relative">
            <input id="q" type="text" name="q" value="<?php echo e($q); ?>"
                   placeholder="เช่น ชื่อเรื่อง, รายละเอียด, อีเมลผู้แจ้ง"
                   class="w-full rounded-md border border-zinc-300 pl-12 pr-3 py-2 text-sm placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-zinc-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M21 21l-4.3-4.3M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </span>
          </div>
        </div>

        
        <div class="md:col-span-3">
          <label for="status" class="mb-1 block text-[12px] text-zinc-600">สถานะ</label>
          <select id="status" name="status"
                  class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">ทั้งหมด</option>
            <?php $__currentLoopData = [
              'pending'     => 'รอคิว',
              'accepted'    => 'รับงานแล้ว',
              'in_progress' => 'ระหว่างดำเนินการ',
              'on_hold'     => 'พักไว้',
              'resolved'    => 'แก้ไขแล้ว',
              'closed'      => 'ปิดงาน',
              'cancelled'   => 'ยกเลิก',
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($k); ?>" <?php if($status===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>

        
        <div class="md:col-span-2">
          <label for="priority" class="mb-1 block text-[12px] text-zinc-600">ความสำคัญ</label>
          <select id="priority" name="priority"
                  class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">
            <option value="">ทั้งหมด</option>
            <?php $__currentLoopData = ['low'=>'ต่ำ','normal'=>'ปกติ','high'=>'สูง','urgent'=>'เร่งด่วน']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($k); ?>" <?php if($priority===$k): echo 'selected'; endif; ?>><?php echo e($v); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>

        
        <div class="md:col-span-1 flex items-end gap-2">
          <button type="submit"
                  class="rounded-md border border-emerald-700 bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">
            กรองข้อมูล
          </button>
          <?php if(request()->hasAny(['q','status','priority'])): ?>
            <a href="<?php echo e(route('maintenance.requests.index')); ?>"
               class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">
              ล้างค่า
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  
  <div class="rounded-lg border border-zinc-300 bg-white overflow-hidden">
    <div class="relative overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-zinc-50">
          <tr class="text-zinc-700 border-b border-zinc-200">
            <th class="p-3 text-left font-medium w-[6%]">#</th>
            <th class="p-3 text-left font-medium w-[30%]">เรื่อง</th>
            <th class="p-3 text-left font-medium w-[18%]">อีเมล</th>
            <th class="p-3 text-left font-medium w-[14%]">หน่วยงาน</th>
            <th class="p-3 text-left font-medium w-[10%]">ความสำคัญ</th>
            <th class="p-3 text-left font-medium w-[10%]">สถานะ</th>
            <th class="p-3 text-right font-medium whitespace-nowrap min-w-[140px]">การดำเนินการ</th>
          </tr>
        </thead>

        <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr class="align-top hover:bg-zinc-50 border-b last:border-0">
            <td class="p-3 text-zinc-700"><?php echo e($row->id); ?></td>

            
            <td class="p-3">
              <a href="<?php echo e(route('maintenance.requests.show', $row)); ?>"
                 class="block max-w-full truncate font-medium text-zinc-900 hover:underline">
                <?php echo e(Str::limit($row->title, 90)); ?>

              </a>
              <?php if($row->description): ?>
                <p class="mt-1 text-xs leading-relaxed text-zinc-600">
                  <?php echo e(Str::limit($row->description, 140)); ?>

                </p>
              <?php endif; ?>

              
              <?php if(!empty($row->category)): ?>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span class="rounded-full bg-white px-2 py-1 text-[11px] ring-1 ring-zinc-300 text-zinc-700">
                    <?php echo e($row->category); ?>

                  </span>
                </div>
              <?php endif; ?>
            </td>

            
            <td class="p-3 text-zinc-700"><?php echo e($row->reporter?->email ?? '-'); ?></td>

            
            <?php
              $deptName = $row->department?->name; // ถ้ามี FK department บน MaintenanceRequest
            ?>
            <td class="p-3 text-zinc-700"><?php echo e($deptName ?? '—'); ?></td>

            <td class="p-3">
              <span class="rounded-full bg-white px-2 py-1 text-[11px] <?php echo e($priorityClass($row->priority ?? null)); ?>">
                <?php echo e($priorityLabel($row->priority ?? null)); ?>

              </span>
            </td>

            <td class="p-3">
              <span class="rounded-full bg-white px-2 py-1 text-[11px] <?php echo e($statusClass($row->status ?? null)); ?>">
                <?php echo e($statusLabel($row->status ?? null)); ?>

              </span>
            </td>

            
            <td class="p-3 text-right whitespace-nowrap">
              <a href="<?php echo e(route('maintenance.requests.show', $row)); ?>"
                 class="inline-flex items-center gap-1 text-emerald-700 hover:underline" onclick="showLoader()">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                </svg>
                <span class="whitespace-nowrap">ดูรายละเอียด</span>
              </a>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr>
            <td colspan="7" class="p-12 text-center text-zinc-600">ไม่พบข้อมูล</td>
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

<?php $__env->startSection('after-content'); ?>
<div id="loaderOverlay" class="loader-overlay">
  <div class="loader-spinner"></div>
</div>
<style>
  .loader-overlay{position:fixed;inset:0;background:rgba(255,255,255,.6);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:99999;visibility:hidden;opacity:0;transition:opacity .2s,visibility .2s}
  .loader-overlay.show{visibility:visible;opacity:1}
  .loader-spinner{width:38px;height:38px;border:4px solid #0E2B51;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite}
  @keyframes spin{to{transform:rotate(360deg)}}
</style>
<script>
  function showLoader(){document.getElementById('loaderOverlay').classList.add('show')}
  function hideLoader(){document.getElementById('loaderOverlay').classList.remove('show')}
  document.addEventListener('DOMContentLoaded', hideLoader);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/maintenance/requests/index.blade.php ENDPATH**/ ?>