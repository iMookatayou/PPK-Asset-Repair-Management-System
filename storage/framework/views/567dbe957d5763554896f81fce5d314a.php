
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'Sign in'); ?> • PPK Hospital System</title>
  <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

  <style>
    /* ===== Button mini spinner (เฉพาะปุ่ม) ===== */
    .btn-with-spinner { position: relative; }
    .btn-with-spinner[aria-busy="true"] { pointer-events: none; opacity: .9; }
    .btn-with-spinner .mini-spinner {
      width: 16px; height: 16px;
      border: 2px solid currentColor;
      border-top-color: transparent;
      border-radius: 50%;
      animation: auth-spin .7s linear infinite;
      display: none;
      vertical-align: -2px;
    }
    .btn-with-spinner[aria-busy="true"] .mini-spinner { display: inline-block; }

    @keyframes auth-spin { to { transform: rotate(360deg); } }

    /* ===== Optional: overlay ทั้งหน้า (ปิดไว้ก่อน) ===== */
    .auth-overlay {
      position: fixed; inset: 0;
      background: rgba(255,255,255,.6);
      backdrop-filter: blur(2px);
      display: flex; align-items: center; justify-content: center;
      z-index: 9999;
      visibility: hidden; opacity: 0;
      transition: opacity .2s ease, visibility .2s;
    }
    .auth-overlay.show { visibility: visible; opacity: 1; }
    .auth-overlay .spinner {
      width: 38px; height: 38px;
      border: 4px solid #0E2B51;
      border-top-color: transparent;
      border-radius: 50%;
      animation: auth-spin .7s linear infinite;
    }
  </style>
</head>
<body class="h-full bg-[#0E2B51] bg-gradient-to-b from-[#0E2B51] to-[#123860] text-slate-800">

  <main class="min-h-screen flex items-center justify-center p-5">
    <div class="w-full max-w-md">

      
      <div class="rounded-2xl border border-slate-200 shadow-[0_10px_32px_-12px_rgba(2,6,23,0.45)] overflow-hidden">

        
        <div class="px-8 pt-8 pb-5 text-center bg-white">
          <div class="mx-auto w-20 h-20 rounded-full bg-white ring-1 ring-slate-200 shadow flex items-center justify-center mb-4">
            <img src="<?php echo e(asset('images/logoppk.png')); ?>" class="w-16 h-16 object-contain" alt="PPK Logo">
          </div>
          <h1 class="text-[18px] font-semibold text-slate-800"><?php echo $__env->yieldContent('title', 'Sign in'); ?></h1>
          <p class="text-xs text-slate-600 tracking-wide">Hospital Information Service</p>
        </div>

        
        <div class="h-px bg-slate-200"></div>

        
        <div class="px-8 py-6 bg-white">
          <?php echo $__env->yieldContent('content'); ?>
        </div>
      </div>

      <p class="text-center text-[11px] text-slate-200 mt-6">
        &copy; <?php echo e(date('Y')); ?> PPK Hospital IT
      </p>
    </div>
  </main>

  
  <div id="authOverlay" class="auth-overlay" aria-hidden="true">
    <div class="spinner"></div>
  </div>

  <script>
    (function () {
      // หา form หลักในคอนเทนต์ auth
      const form = document.querySelector('main form');
      if (!form) return;

      // หา submit button (รองรับทั้ง <button> และ <input type="submit">)
      let submitBtn = form.querySelector('button[type="submit"], [type="submit"]');
      if (!submitBtn) return;

      // เปิดใช้งาน mini spinner บนปุ่ม (ถ้ายังไม่มี)
      submitBtn.classList.add('btn-with-spinner');
      if (!submitBtn.querySelector('.mini-spinner')) {
        const spin = document.createElement('span');
        spin.className = 'mini-spinner';
        spin.setAttribute('aria-hidden', 'true');

        // แทรกไว้หน้าข้อความปุ่ม พร้อมเว้นช่องไฟ
        submitBtn.prepend(spin);
        if (submitBtn.textContent.trim().length > 0) {
          submitBtn.insertBefore(document.createTextNode(' '), spin.nextSibling);
        }
      }

      // เมื่อ submit: แสดง spinner บนปุ่ม + กันกดซ้ำ
      form.addEventListener('submit', function () {
        // ถ้าฟอร์มมี data-no-loader ให้ข้าม
        if (form.hasAttribute('data-no-loader')) return;

        submitBtn.setAttribute('aria-busy', 'true');
        submitBtn.disabled = true;

        // ถ้าต้องการ overlay ทั้งหน้า ให้ปลดคอมเมนต์บรรทัดด้านล่าง
        // document.getElementById('authOverlay')?.classList.add('show');
      }, { once: true });
    })();
  </script>

  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>
    <?php if (isset($component)) { $__componentOriginal7cfab914afdd05940201ca0b2cbc009b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $attributes = $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $component = $__componentOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>

      <?php if(session('toast')): ?>
        <script>
          const t = <?php echo json_encode(session('toast'), 15, 512) ?>;
          t.position = 'tc'; // บังคับบนกึ่งกลางจากฝั่ง layout อีกชั้น
          if (window.showToast) { window.showToast(t); }
          else { window.dispatchEvent(new CustomEvent('app:toast', { detail: t })); }
        </script>
      <?php endif; ?>
</body>
</html><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/layouts/auth.blade.php ENDPATH**/ ?>