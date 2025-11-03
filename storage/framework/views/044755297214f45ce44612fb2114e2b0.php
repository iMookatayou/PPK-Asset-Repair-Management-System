
<?php $__env->startSection('title','Community Chat'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto space-y-4">
  <form method="GET" class="flex gap-2">
    <input name="q" value="<?php echo e(e(request('q',''))); ?>" placeholder="ค้นหากระทู้..."
           class="w-full rounded-lg border border-slate-300 px-3 py-2">
    <button class="rounded-lg px-3 py-2 bg-[#0E2B51] text-white">Search</button>
  </form>

  <form method="POST" action="<?php echo e(route('chat.store')); ?>" class="section-card p-4 space-y-2">
    <?php echo csrf_field(); ?>
    <label class="text-sm text-slate-600">สร้างกระทู้ใหม่</label>
    <input name="title" required maxlength="180" class="w-full rounded-lg border px-3 py-2"
           placeholder="ถามอะไรก็ได้ เช่น “วิธีเลือกเครื่องพิมพ์สำหรับงานเวรคืน”">
    <div class="text-right">
      <button class="rounded-lg bg-emerald-600 text-white px-3 py-2">โพสต์</button>
    </div>
  </form>

  <div class="section-card">
    <div class="section-head p-3 border-b">กระทู้ล่าสุด</div>
    <div class="divide-y">
      <?php $__empty_1 = true; $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $th): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <a href="<?php echo e(route('chat.show',$th)); ?>" class="block px-4 py-3 hover:bg-gray-50">
          <div class="font-medium"><?php echo e($th->title); ?></div>
          <div class="text-xs text-gray-500">โดย <?php echo e($th->author->name); ?> • <?php echo e($th->created_at->diffForHumans()); ?></div>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="p-6 text-gray-500 text-center">ยังไม่มีกระทู้</div>
      <?php endif; ?>
    </div>
    <div class="p-3"><?php echo e($threads->withQueryString()->links()); ?></div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/chat/index.blade.php ENDPATH**/ ?>