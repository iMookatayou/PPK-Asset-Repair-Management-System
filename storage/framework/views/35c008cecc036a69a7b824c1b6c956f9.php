
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
  'appName'    => 'Phrapokklao - Information Technology Group',
  'subtitle'   => 'Asset Repair Management',
  'logo'       => asset('images/logoppk.png'),
  'showLogout' => Auth::check(),
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
  'appName'    => 'Phrapokklao - Information Technology Group',
  'subtitle'   => 'Asset Repair Management',
  'logo'       => asset('images/logoppk.png'),
  'showLogout' => Auth::check(),
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php $user = Auth::user(); ?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-hero">
  <div class="container-fluid">
    
    <a class="navbar-brand d-flex align-items-center gap-3" href="<?php echo e(url('/')); ?>" data-no-loader>
      <img src="<?php echo e($logo); ?>" alt="Logo" class="brand-logo">
      <span class="d-flex flex-column lh-sm">
        <strong><?php echo e($appName); ?></strong>
        <small class="brand-kicker fw-normal"><?php echo e($subtitle); ?></small>
      </span>
    </a>

    
    <button id="btnSidebar" class="btn btn-outline-light btn-sm d-lg-none me-2" type="button" title="เมนู">
      ☰
    </button>

    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav"
            aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    
    <div class="collapse navbar-collapse" id="topNav">
      <ul class="navbar-nav ms-auto align-items-center gap-lg-3">
        <?php if(auth()->guard()->check()): ?>
          
          <li class="nav-item">
            <a href="#"
               id="profilePopoverBtn"
               class="nav-link d-flex align-items-center gap-2 p-0"
               role="button"
               data-no-loader
               aria-describedby="profilePopover">
              <img src="<?php echo e($user->avatar_url ?? asset('images/default-avatar.png')); ?>" alt="Avatar" class="avatar-img">
              <span class="d-none d-md-inline fw-semibold"><?php echo e($user->name); ?></span>
              <i class="bi bi-caret-down-fill ms-1"></i>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="<?php echo e(route('login')); ?>" class="btn btn-light btn-sm" data-no-loader>เข้าสู่ระบบ</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if(auth()->guard()->check()): ?>
  
  <div id="profilePopoverContent" class="d-none">
    <div class="p-2 ff-sarabun" style="min-width: 260px;">
      <div class="d-flex align-items-center gap-2 mb-2">
        <img src="<?php echo e($user->avatar_url ?? asset('images/default-avatar.png')); ?>" class="rounded-circle" width="40" height="40" alt="Avatar">
        <div>
          <div class="fw-semibold"><?php echo e($user->name); ?></div>
          <div class="small text-muted"><?php echo e($user->email); ?></div>
        </div>
      </div>

      
      <a href="<?php echo e(route('profile.show')); ?>" class="dropdown-item py-2 d-flex align-items-center gap-2" data-no-loader>
        <i class="bi bi-person-lines-fill"></i> โปรไฟล์ของฉัน
      </a>

      <?php if($showLogout): ?>
        <div class="mt-2">
          <form method="POST" action="<?php echo e(route('logout')); ?>" class="mb-0" data-no-loader>
            <?php echo csrf_field(); ?>
            <button class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
              <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<style>
  /* === Navbar Hero Theme === */
  :root {
    --navbar-bg: #0F2D5C;
    --navbar-text: #EAF2FF;
  }
  .navbar-hero {
    background-color: var(--navbar-bg);
    color: var(--navbar-text);
    padding-block: 1rem;
    min-height: 86px;
    box-shadow: 0 10px 30px rgba(0,0,0,.25);
    border-bottom: 1px solid rgba(255,255,255,.12);
    z-index: 2000;
    overflow: visible; /* ให้ Popover แสดงพ้น navbar */
  }
  .navbar-hero .navbar-brand { font-weight: 700; letter-spacing: .2px; font-size: 1.45rem; color: #fff; }
  .brand-kicker { font-size: .82rem; opacity: .9; margin-top: .15rem; }
  .brand-logo { width: 56px; height: 56px; object-fit: contain; }

  .avatar-img {
    width: 40px !important; height: 40px !important; aspect-ratio: 1/1;
    object-fit: cover; border-radius: 50%;
    border: 2px solid rgba(255,255,255,.8); background: transparent !important;
  }

  /* ฟอนต์ Sarabun บังคับใน popover เผื่อ CSS อื่น override */
  .ff-sarabun {
    font-family: 'Sarabun', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    letter-spacing: .2px;
  }

  /* Popover theme & stacking */
  .popover { z-index: 2300; }
  .popover .popover-body { padding: 0; }
</style>

<?php if(auth()->guard()->check()): ?>
  <?php $__env->startPush('scripts'); ?>
  <script>
    (function () {
      // ต้องมี bootstrap.bundle (layout ของคุณโหลดไว้แล้ว)
      const btn = document.getElementById('profilePopoverBtn');
      const tpl = document.getElementById('profilePopoverContent');

      if (!btn || !tpl || !window.bootstrap || !bootstrap.Popover) return;

      const html = tpl.innerHTML;

      // สร้าง Popover ด้วย JS (manual)
      const pop = new bootstrap.Popover(btn, {
        html: true,
        content: html,
        placement: 'bottom',
        trigger: 'manual',
        sanitize: false,
        customClass: 'profile-popover'
      });

      function closeAllPopovers() {
        document.querySelectorAll('.popover.show').forEach(el => el.remove());
      }

      // toggle ด้วยคลิก
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        // ปิดตัวอื่นก่อนเพื่อกันซ้อน
        if (document.querySelector('.popover.show')) { closeAllPopovers(); return; }
        pop.show();
      });

      // คลิกนอกเพื่อปิด
      document.addEventListener('click', (e) => {
        const isBtn = e.target.closest('#profilePopoverBtn');
        const isPop = e.target.closest('.popover');
        if (!isBtn && !isPop) closeAllPopovers();
      }, true);

      // กด Esc ปิด
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllPopovers();
      });

      // คลิกภายใน popover → ปิดหลังทำงาน
      document.addEventListener('click', (e) => {
        const inside = e.target.closest('.popover');
        if (!inside) return;
        const aOrBtn = e.target.closest('a,button');
        if (aOrBtn) setTimeout(() => closeAllPopovers(), 50);
      });
    })();
  </script>
  <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/components/navbar.blade.php ENDPATH**/ ?>