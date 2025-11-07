<?php $__env->startSection('title', 'Edit Profile'); ?>

<?php $__env->startSection('page-header'); ?>
  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Edit Profile
          </h1>
          <p class="mt-1 text-sm text-slate-600">ปรับข้อมูลส่วนตัวของคุณ เช่น ชื่อ อีเมล แผนก และรูปโปรไฟล์</p>
        </div>

        <a href="<?php echo e(route('profile.show')); ?>"
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
  <?php
    // URL รูป (อาจเป็น null ทั้งคู่ได้)
    $avatarMain  = data_get($user, 'avatar_url');
    $avatarThumb = data_get($user, 'avatar_thumb_url');

    // ตัดอักษรย่อจากชื่อผู้ใช้ (2 ตัว)
    $name = trim((string) ($user->name ?? ''));
    $parts = preg_split('/\s+/u', $name) ?: [];
    $initials = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
  ?>

  <div class="mx-auto max-w-3xl py-6 space-y-5">
    <?php if(session('status')): ?>
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">
        <?php echo e(session('status')); ?>

      </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data" class="space-y-5">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>

        
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">รูปโปรไฟล์</label>

          <div class="flex items-center gap-4">
            
            <img id="avatar-preview"
                 <?php if(!$avatarThumb && !$avatarMain): ?> class="hidden h-16 w-16 rounded-full object-cover ring-1 ring-slate-200"
                 <?php else: ?> class="h-16 w-16 rounded-full object-cover ring-1 ring-slate-200" <?php endif; ?>
                 src="<?php echo e($avatarThumb ?: $avatarMain); ?>"
                 
                 srcset="<?php echo e($avatarThumb ?: $avatarMain); ?> 128w, <?php echo e($avatarMain ?: $avatarThumb); ?> 512w"
                 sizes="64px"
                 alt="Profile photo" width="64" height="64"
                 loading="lazy" decoding="async" />

            <div id="avatar-fallback"
                 <?php if($avatarThumb || $avatarMain): ?> class="hidden" <?php endif; ?>
                 class="flex h-16 w-16 items-center justify-center rounded-full bg-violet-500 text-white ring-1 ring-slate-200">
              <span class="text-lg font-semibold"><?php echo e($initials); ?></span>
            </div>

            <div class="flex-1 space-y-2">
              <div>
                <input id="avatar-input" type="file" name="avatar"
                       accept=".jpg,.jpeg,.png,.webp,image/*"
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border file:border-slate-300 file:bg-white file:px-3 file:py-1.5 file:text-sm file:text-slate-700 hover:file:bg-slate-50">
                <p class="mt-1 text-xs text-slate-500">รองรับไฟล์: JPG, JPEG, PNG, WEBP (ไม่เกิน 2MB)</p>
                <?php $__errorArgs = ['avatar'];
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

              <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input id="remove-avatar" type="checkbox" name="remove_avatar" value="1"
                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                ลบรูปโปรไฟล์ปัจจุบัน
              </label>
            </div>
          </div>
        </div>

        
        <div>
          <label for="name" class="block text-sm font-medium text-slate-700">ชื่อ-นามสกุล</label>
          <input id="name" name="name" type="text" value="<?php echo e(old('name', $user->name)); ?>" required
                 class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 
                        focus:border-emerald-600 focus:ring-emerald-600 
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700">อีเมล</label>
          <input id="email" name="email" type="email" value="<?php echo e(old('email', $user->email)); ?>" required
                 class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 
                        focus:border-emerald-600 focus:ring-emerald-600 
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          <p class="mt-1 text-xs text-slate-500">เปลี่ยนอีเมลจะทำให้สถานะยืนยันอีเมลถูกรีเซ็ต</p>
        </div>

        
        <div>
          <label for="department" class="block text-sm font-medium text-slate-700">แผนก</label>
          <select id="department" name="department"
                  class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 
                         focus:border-emerald-600 focus:ring-emerald-600 
                         <?php $__errorArgs = ['department'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-400 ring-rose-200 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <option value="">— เลือกแผนก —</option>
            <?php $__currentLoopData = \App\Models\Department::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($dept->code); ?>" <?php if(old('department', $user->department) == $dept->code): echo 'selected'; endif; ?>>
                <?php echo e($dept->name); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
          <?php $__errorArgs = ['department'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        
        <div class="pt-2">
          <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
            บันทึกการเปลี่ยนแปลง
          </button>
        </div>
      </form>
    </div>
  </div>

  
  <div id="cropper-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="relative mx-auto mt-10 w-[92vw] max-w-2xl rounded-xl bg-white shadow-xl">
      <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
        <h3 class="font-semibold text-slate-900">ครอปรูปโปรไฟล์</h3>
        <button type="button" id="cropper-close" class="rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100">ปิด</button>
      </div>
      <div class="p-4">
        <div class="aspect-square w-full max-h-[60vh] overflow-hidden rounded-lg border">
          <img id="cropper-image" alt="To crop" class="max-w-full block">
        </div>
        <p class="mt-2 text-xs text-slate-500">ลาก/ซูม เพื่อครอปภาพสี่เหลี่ยมจัตุรัส (จะแปลงเป็น 512×512)</p>
      </div>
      <div class="flex items-center justify-end gap-2 px-4 py-3 border-t border-slate-200">
        <button type="button" id="cropper-cancel" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50">ยกเลิก</button>
        <button type="button" id="cropper-apply" class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">ใช้รูปนี้</button>
      </div>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(() => {
  const fileInput   = document.getElementById('avatar-input');
  const previewEl   = document.getElementById('avatar-preview');
  const fallbackEl  = document.getElementById('avatar-fallback');
  const removeBox   = document.getElementById('remove-avatar');
  const modal       = document.getElementById('cropper-modal');
  const imgEl       = document.getElementById('cropper-image');
  const btnClose    = document.getElementById('cropper-close');
  const btnCancel   = document.getElementById('cropper-cancel');
  const btnApply    = document.getElementById('cropper-apply');

  let cropper = null;
  let pendingFileName = null;

  function openModal() { modal.classList.remove('hidden'); }
  function closeModal() {
    modal.classList.add('hidden');
    if (cropper) { cropper.destroy(); cropper = null; }
    imgEl.src = '';
  }

  // เมื่อเลือกไฟล์ -> เปิด cropper modal
  fileInput.addEventListener('change', (e) => {
    const [file] = e.target.files || [];
    if (!file) return;

    if (removeBox) removeBox.checked = false;
    pendingFileName = file.name;

    const reader = new FileReader();
    reader.onload = () => {
      imgEl.src = reader.result;
      openModal();
      imgEl.onload = () => {
        cropper = new Cropper(imgEl, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 1,
          background: false,
          zoomOnWheel: true,
          checkCrossOrigin: false,
        });
      };
    };
    reader.readAsDataURL(file);
  });

  // ปิด/ยกเลิก
  btnClose.addEventListener('click', closeModal);
  btnCancel.addEventListener('click', () => {
    fileInput.value = '';
    pendingFileName = null;
    closeModal();
  });

  // ใช้รูปนี้ -> เซฟเป็น WebP แล้วอัปเดตพรีวิว + ซ่อน fallback
  btnApply.addEventListener('click', () => {
    if (!cropper) return;

    const canvas = cropper.getCroppedCanvas({ width: 512, height: 512 });
    canvas.toBlob((blob) => {
      if (!blob) return;

      const baseName = (pendingFileName || 'avatar').replace(/\.(png|jpe?g|webp|gif|heic)$/i, '');
      const fileName = baseName + '.webp';
      const croppedFile = new File([blob], fileName, { type: 'image/webp', lastModified: Date.now() });

      const dt = new DataTransfer();
      dt.items.add(croppedFile);
      fileInput.files = dt.files;

      const previewReader = new FileReader();
      previewReader.onload = () => {
        previewEl.src = previewReader.result;
        previewEl.classList.remove('hidden');
        if (fallbackEl) fallbackEl.classList.add('hidden');
      };
      previewReader.readAsDataURL(croppedFile);

      closeModal();
    }, 'image/webp', 0.82);
  });

  // ถ้าติ๊ก "ลบรูป" -> เคลียร์ไฟล์ + แสดง fallback
  if (removeBox) {
    removeBox.addEventListener('change', (e) => {
      if (e.target.checked) {
        fileInput.value = '';
        pendingFileName = null;
        previewEl.classList.add('hidden');
        previewEl.removeAttribute('src');
        if (fallbackEl) fallbackEl.classList.remove('hidden');
      }
    });
  }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/profile/edit.blade.php ENDPATH**/ ?>