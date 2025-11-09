
<?php $__env->startSection('title','Create Asset'); ?>

<?php $__env->startSection('page-header'); ?>
  
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Create Asset
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            เพิ่มครุภัณฑ์ใหม่เข้าสู่ระบบ — โปรดระบุข้อมูลให้ครบถ้วนเพื่อความถูกต้องในการจัดเก็บ
          </p>
        </div>

        <a href="<?php echo e(route('assets.index')); ?>"
           class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 transition">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back
        </a>
      </div>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    
    <?php if($errors->any()): ?>
      <?php $__env->startPush('scripts'); ?>
      <script>
        (function(){
          const msgs = <?php echo json_encode($errors->all(), 15, 512) ?>;
          const msg  = msgs.length ? ('มีข้อผิดพลาดในการบันทึก: ' + msgs.join(' • ')) : 'มีข้อผิดพลาดในการบันทึกข้อมูล';
          if (window.showToast) {
            window.showToast({ type:'error', message: msg, position:'uc', timeout: 3600, size:'lg' });
          } else {
            // fallback: dispatch app:toast event
            window.dispatchEvent(new CustomEvent('app:toast',{ detail:{ type:'error', message: msg, position:'uc', timeout:3600, size:'lg' } }));
          }
        })();
      </script>
      <?php $__env->stopPush(); ?>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('assets.store')); ?>"
          onsubmit="window.dispatchEvent(new CustomEvent('app:toast',{detail:{type:'info',message:'กำลังบันทึก...'}}))"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
      <?php echo csrf_field(); ?>

      
      <?php echo $__env->make('assets._fields', [
        'asset' => new \App\Models\Asset(),
        'categories' => $categories ?? null,
        'departments' => $departments ?? null
      ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

      
      <div class="pt-2 flex justify-end gap-2">
        <a href="<?php echo e(route('assets.index')); ?>"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50">
          Cancel
        </a>
        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
          Save
        </button>
      </div>
    </form>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/assets/create.blade.php ENDPATH**/ ?>