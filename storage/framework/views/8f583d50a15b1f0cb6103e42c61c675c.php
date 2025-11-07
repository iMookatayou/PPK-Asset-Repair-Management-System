<?php $__env->startSection('title','Create Maintenance'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Create Maintenance
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            สร้างคำขอซ่อมใหม่ — ระบุทรัพย์สิน หัวข้อ และรายละเอียดให้ครบถ้วน
          </p>
        </div>

        <a href="<?php echo e(route('maintenance.requests.index')); ?>"
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
      <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
        <p class="font-medium">มีข้อผิดพลาดในการบันทึกข้อมูล:</p>
        <ul class="mt-2 list-disc pl-5 text-sm">
          <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li><?php echo e($error); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST"
          action="<?php echo e(route('maintenance.requests.store')); ?>"
          enctype="multipart/form-data"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          novalidate
          aria-label="แบบฟอร์มสร้างคำขอซ่อม">
      <?php echo csrf_field(); ?>

      <div class="space-y-6">
        
        <section>
          <h2 class="text-base font-semibold text-slate-900">ข้อมูลหลัก</h2>
          <p class="text-sm text-slate-500">เลือกทรัพย์สิน และ (ถ้าจำเป็น) ผู้แจ้ง</p>

          <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">

            
            <?php $field='asset_id'; ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                ทรัพย์สิน
              </label>
              <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                      <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-describedby="<?php echo e($field); ?>_error" aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                <option value="">— เลือกทรัพย์สิน —</option>
                <?php $assetList = is_iterable($assets ?? null) ? $assets : []; ?>
                <?php $__currentLoopData = $assetList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($a->id); ?>" <?php if(old($field) == $a->id): echo 'selected'; endif; ?>>
                    <?php echo e($a->code ?? '—'); ?> — <?php echo e($a->name); ?>

                  </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p id="<?php echo e($field); ?>_error" class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php $field='reporter_id'; ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                ผู้แจ้ง (ถ้าทำเรื่องแทน)
              </label>
              <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                      <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-describedby="<?php echo e($field); ?>_error" aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                <option value="">— ใช้ผู้ใช้งานปัจจุบัน —</option>
                <?php $userList = is_iterable($users ?? null) ? $users : []; ?>
                <?php $__currentLoopData = $userList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($u->id); ?>" <?php if(old($field, auth()->id()) == $u->id): echo 'selected'; endif; ?>>
                    <?php echo e($u->name); ?>

                  </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p id="<?php echo e($field); ?>_error" class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
          </div>
        </section>

        
        <section class="pt-4 border-t border-slate-200">
          <h2 class="text-base font-semibold text-slate-900">รายละเอียดปัญหา</h2>
          <p class="text-sm text-slate-500">สรุปหัวข้อและอาการ เพื่อการคัดแยกที่รวดเร็ว</p>

          <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            
            <?php $field='title'; ?>
            <div class="md:col-span-2">
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                หัวข้อ <span class="text-rose-600">*</span>
              </label>
              <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" type="text" required autocomplete="off"
                     placeholder="สรุปสั้น ๆ ชัดเจน (เช่น แอร์รั่วน้ำ ห้อง 302)"
                     class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old($field)); ?>">
              <p class="mt-1 text-xs text-slate-500">ไม่เกิน 150 ตัวอักษร</p>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php $field='description'; ?>
            <div class="md:col-span-2">
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">รายละเอียด</label>
              <textarea id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" rows="5"
                        placeholder="อาการ เกิดเมื่อไร มีรูป/ลิงก์ประกอบ ฯลฯ"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old($field)); ?></textarea>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
          </div>
        </section>

        
        <section class="pt-4 border-t border-slate-200">
          <h2 class="text-base font-semibold text-slate-900">ความสำคัญ</h2>
          <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <?php
              $field='priority';
              $priorities=['low'=>'ต่ำ','normal'=>'ปกติ','high'=>'สูง','urgent'=>'ด่วน'];
            ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                ระดับความสำคัญ <span class="text-rose-600">*</span>
              </label>
              <select id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" required
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                <?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($k); ?>" <?php if(old($field, 'normal') === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php $field='request_date'; ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                วันที่แจ้ง
              </label>
              <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" type="date"
                     class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old($field)); ?>">
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
          </div>
        </section>

        
        <section class="pt-4 border-t border-slate-200">
          <h2 class="text-base font-semibold text-slate-900">ไฟล์แนบ</h2>
          <p class="text-sm text-slate-500">รองรับรูปภาพและ PDF (สูงสุดไฟล์ละ 10MB)</p>

          <div class="mt-3">
            <?php $field='files'; ?>
            <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
              เลือกไฟล์ (Images / PDF)
            </label>
            <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>[]"
                   type="file" multiple
                   accept="image/*,application/pdf"
                   class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-slate-700 hover:file:bg-slate-200 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field.'.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   aria-describedby="<?php echo e($field); ?>_help">
            <p id="<?php echo e($field); ?>_help" class="mt-1 text-xs text-slate-500">
              ประเภทที่อนุญาต: รูปภาพทุกชนิด, PDF • ขนาดไม่เกิน 10MB ต่อไฟล์
            </p>
            <?php $__errorArgs = [$field.'.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
              <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>
        </section>
      </div>

      
      <div class="mt-6 flex justify-end gap-2">
        <a href="<?php echo e(route('maintenance.requests.index')); ?>"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50">
          ยกเลิก
        </a>
        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
          บันทึก
        </button>
      </div>
    </form>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/maintenance/requests/create.blade.php ENDPATH**/ ?>