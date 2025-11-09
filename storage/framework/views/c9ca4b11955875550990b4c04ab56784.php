

<?php $__env->startSection('title','Edit Maintenance'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Edit Maintenance
          </h1>
          <p class="mt-1 text-sm text-slate-600">
            แก้ไขคำขอซ่อม — ปรับข้อมูลให้ถูกต้องและบันทึกการเปลี่ยนแปลง
          </p>
        </div>

        <div class="flex items-center gap-2">
          <a href="<?php echo e(route('maintenance.requests.show', $mr)); ?>"
             class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back
          </a>
          <a href="<?php echo e(route('maintenance.requests.index')); ?>"
             class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            List
          </a>
        </div>
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
          action="<?php echo e(route('maintenance.requests.update', $mr)); ?>"
          enctype="multipart/form-data"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          novalidate
          aria-label="แบบฟอร์มแก้ไขคำขอซ่อม">
      <?php echo csrf_field(); ?>
      <?php echo method_field('PUT'); ?>

      <div class="space-y-6">
        
        <section>
          <h2 class="text-base font-semibold text-slate-900">ข้อมูลหลัก</h2>
          <p class="text-sm text-slate-500">เลือกทรัพย์สิน และ (ถ้าจำเป็น) ผู้แจ้ง</p>

          <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">

            
            <?php $field='asset_id'; $assetList = is_iterable($assets ?? null) ? collect($assets) : collect(); ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                ทรัพย์สิน
              </label>
              <?php if (isset($component)) { $__componentOriginal65b27876dc3636d5d043082d984d84ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65b27876dc3636d5d043082d984d84ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.search-select','data' => ['name' => 'asset_id','id' => 'asset_id','items' => $assetList->map(fn($a)=> (object)['id'=>$a->id,'name'=>($a->asset_code ?? '—').' — '.($a->name ?? '')]),'labelField' => 'name','valueField' => 'id','value' => old('asset_id', $mr->asset_id),'placeholder' => '— เลือกทรัพย์สิน —']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('search-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'asset_id','id' => 'asset_id','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($assetList->map(fn($a)=> (object)['id'=>$a->id,'name'=>($a->asset_code ?? '—').' — '.($a->name ?? '')])),'label-field' => 'name','value-field' => 'id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('asset_id', $mr->asset_id)),'placeholder' => '— เลือกทรัพย์สิน —']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $attributes = $__attributesOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__attributesOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $component = $__componentOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__componentOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
              <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p id="<?php echo e($field); ?>_error" class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <?php $field='reporter_id'; $userList = is_iterable($users ?? null) ? collect($users) : collect(); ?>
            <div>
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
                ผู้แจ้ง (ถ้าทำเรื่องแทน)
              </label>
              <?php if (isset($component)) { $__componentOriginal65b27876dc3636d5d043082d984d84ad = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal65b27876dc3636d5d043082d984d84ad = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.search-select','data' => ['name' => 'reporter_id','id' => 'reporter_id','items' => $userList->map(fn($u)=> (object)['id'=>$u->id,'name'=>$u->name]),'labelField' => 'name','valueField' => 'id','value' => old('reporter_id', $mr->reporter_id ?? auth()->id()),'placeholder' => '— ใช้ผู้ใช้งานปัจจุบัน —']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('search-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'reporter_id','id' => 'reporter_id','items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($userList->map(fn($u)=> (object)['id'=>$u->id,'name'=>$u->name])),'label-field' => 'name','value-field' => 'id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('reporter_id', $mr->reporter_id ?? auth()->id())),'placeholder' => '— ใช้ผู้ใช้งานปัจจุบัน —']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $attributes = $__attributesOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__attributesOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal65b27876dc3636d5d043082d984d84ad)): ?>
<?php $component = $__componentOriginal65b27876dc3636d5d043082d984d84ad; ?>
<?php unset($__componentOriginal65b27876dc3636d5d043082d984d84ad); ?>
<?php endif; ?>
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
                     maxlength="150"
                     class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                     value="<?php echo e(old($field, $mr->title)); ?>">
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
              <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">รายละเอียด <span class="ml-1 text-xs text-slate-500">(ไม่บังคับ)</span></label>
              <textarea id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" rows="5"
                        placeholder="อาการ เกิดเมื่อไร มีรูป/ลิงก์ประกอบ ฯลฯ"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-600 focus:ring-emerald-600 <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old($field, $mr->description)); ?></textarea>
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
              // ให้ตรงกับ validation: low|medium|high|urgent
              $priorities=['low'=>'ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'ด่วน'];
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
                  <option value="<?php echo e($k); ?>" <?php if(old($field, $mr->priority ?? 'medium') === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
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
                     value="<?php echo e(old($field, optional($mr->request_date)->format('Y-m-d'))); ?>">
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
          <p class="text-sm text-slate-500">ดู/ลบไฟล์แนบเดิม และอัปโหลดไฟล์ใหม่ (สูงสุดไฟล์ละ 10MB)</p>

          
          <?php $existing = is_iterable($attachments ?? null) ? $attachments : []; ?>
          <?php if(count($existing)): ?>
            <div class="mt-3 rounded-lg border border-slate-200 divide-y divide-slate-200">
              <?php $__currentLoopData = $existing; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $f = optional($att->file);
                  $path = $f->path ?? '';
                  $mime = $f->mime ?? 'file';
                  $size = $f->size ?? null;
                ?>
                <div class="flex items-center justify-between px-3 py-2">
                  <div class="min-w-0">
                    <p class="truncate text-sm text-slate-800">
                      <?php echo e($att->original_name ?? basename($path)); ?>

                    </p>
                    <p class="text-xs text-slate-500">
                      <?php echo e($mime); ?> <?php if($size): ?> • <?php echo e(number_format($size/1024, 0)); ?> KB <?php endif; ?>
                    </p>
                  </div>
                  <label class="inline-flex items-center gap-2 text-sm text-rose-700">
                    <input type="checkbox" name="remove_attachments[]"
                           value="<?php echo e($att->id); ?>"
                           class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-600">
                    ลบไฟล์นี้
                  </label>
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php else: ?>
            <p class="mt-2 text-sm text-slate-500">ยังไม่มีไฟล์แนบ</p>
          <?php endif; ?>

          
          <div class="mt-4">
            <?php $field='files'; ?>
            <label for="<?php echo e($field); ?>" class="block text-sm font-medium text-slate-700">
              เพิ่มไฟล์ (Images / PDF)
            </label>
            <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>[]" type="file" multiple
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
        <a href="<?php echo e(route('maintenance.requests.show', $mr)); ?>"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-700 hover:bg-slate-50">
          ยกเลิก
        </a>
        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 font-medium text-white hover:bg-emerald-700">
          บันทึกการแก้ไข
        </button>
      </div>
    </form>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/maintenance/requests/edit.blade.php ENDPATH**/ ?>