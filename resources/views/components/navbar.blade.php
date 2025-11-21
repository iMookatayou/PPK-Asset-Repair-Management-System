{{-- resources/views/components/navbar.blade.php --}}
@props([
  'hospitalName' => 'โรงพยาบาลพระปกเกล้า PHRAPOKKLAO HOSPITAL',
  'appName'      => 'PPK Information Technology Group',
  'subtitle'     => 'Asset Repair Management',
  'logo'         => asset('images/logoppk.png'),
  'showLogout'   => Auth::check(),
])

@php $user = Auth::user(); @endphp

<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-hero">
  <div class="container-xl">
    <div class="d-flex align-items-stretch w-100">

      {{-- LEFT LOGO + TEXT --}}
      <a class="navbar-brand d-flex align-items-center gap-3 brand-block" href="{{ url('/') }}" data-no-loader>
        <div class="brand-logo-wrap d-flex align-items-center justify-content-center flex-shrink-0">
          <img src="{{ $logo }}" alt="Logo" class="brand-logo">
        </div>

        <div class="d-flex flex-column lh-sm brand-text ff-sarabun">
          {{-- บรรทัดชื่อโรงพยาบาล (ไทย+อังกฤษ) --}}
          <div class="brand-title-wrap">
            <span class="brand-hospital-th">
              {{ $hospitalName }}
            </span>
          </div>

          {{-- แถบข้อมูลระบบ (พยายามรวม 2 บรรทัดให้เป็นแถวเดียวกัน) --}}
          <div class="brand-meta-wrap">
            <span class="brand-app-en">
              {{ $appName }}
            </span>

            <span class="brand-subtitle">
              {{ $subtitle }}
            </span>
          </div>
        </div>
      </a>

      <div class="flex-grow-1"></div>

      {{-- MOBILE BUTTONS --}}
      <div class="d-flex align-items-center gap-2 d-lg-none me-2">
        <button id="btnSidebar" class="btn btn-outline-light btn-sm" type="button">☰</button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#topNav" aria-controls="topNav"
                aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>

      {{-- RIGHT SIDE --}}
      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav ms-auto align-items-center gap-lg-3">
          @auth
            {{-- AVATAR BUTTON --}}
            <li class="nav-item">
              <a href="#"
                 id="profilePopoverBtn"
                 class="nav-link d-flex align-items-center gap-2 p-0 profile-trigger"
                 role="button"
                 data-no-loader
                 aria-describedby="profilePopover">
                <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                     alt="Avatar" class="avatar-img">
                <span class="d-none d-md-inline fw-semibold ff-sarabun">{{ $user->name }}</span>
                <i class="bi bi-caret-down-fill ms-1"></i>
              </a>
            </li>
          @else
            <li class="nav-item">
              <a href="{{ route('login') }}" class="btn btn-light btn-sm ff-sarabun" data-no-loader>
                เข้าสู่ระบบ
              </a>
            </li>
          @endauth
        </ul>
      </div>
    </div>
  </div>
</nav>

@auth
{{-- Profile Popover Template --}}
<div id="profilePopoverContent" class="d-none">
  <div class="p-2 ff-sarabun" style="min-width: 260px;">
    <div class="d-flex align-items-center gap-2 mb-2">
      <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
           class="rounded-circle" width="40" height="40" alt="Avatar">
      <div>
        <div class="fw-semibold">{{ $user->name }}</div>
        <div class="small text-muted">{{ $user->email }}</div>
      </div>
    </div>

    <a href="{{ route('profile.show') }}" class="dropdown-item py-2 d-flex align-items-center gap-2" data-no-loader>
      <i class="bi bi-person-lines-fill"></i> โปรไฟล์ของฉัน
    </a>

    @if($showLogout)
      <div class="mt-2">
        <form method="POST" action="{{ route('logout') }}" class="mb-0" data-no-loader>
          @csrf
          <button class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
          </button>
        </form>
      </div>
    @endif
  </div>
</div>
@endauth

<style>
  :root {
    --navbar-bg-grad: linear-gradient(90deg, #0a1f46 0%, #0F2D5C 40%, #16407f 100%);
    --navbar-text: #EAF2FF;
  }

  .navbar-hero {
    background: var(--navbar-bg-grad);
    color: var(--navbar-text);
    padding-block: 0.85rem;
    min-height: 86px;
    box-shadow: 0 10px 30px rgba(0,0,0,.25);
    border-bottom: none !important;
  }

  /* LOGO */
  .brand-logo-wrap {
    width: 60px;
    height: 60px;
    border-radius: 50%;
  }

  .brand-logo {
    width: 70px;
    height: 70px;
    object-fit: contain;
  }

  .brand-block {
    position: relative;
  }

  .brand-text {
    position: relative;
    min-width: 0;
    padding-left: 1.25rem;
  }

  /* เส้นนำสายตาแนวตั้งเพิ่มมิติ */
  .brand-text::before {
    content: "";
    position: absolute;
    left: 0;
    top: 8%;
    bottom: 8%;
    width: 2px;
    border-radius: 999px;
    background: linear-gradient(to bottom, rgba(255,255,255,.95), rgba(255,255,255,.05));
    box-shadow: 0 0 8px rgba(0,0,0,.4);
    opacity: .9;
  }

  /* ชื่อโรงพยาบาล (ไทย + ENG) */
  .brand-title-wrap {
    position: relative;
  }

  .brand-hospital-th {
    font-size: 1.24rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: .02em;
    text-shadow:
      0 1px 3px rgba(0,0,0,.55),
      0 0 22px rgba(0,0,0,.35);
    white-space: nowrap;
  }

  /* block ข้างล่าง (รวม 2 บรรทัดให้เป็นแถวเดียวในจอใหญ่) */
  .brand-meta-wrap {
    margin-top: 6px;
    padding-top: 4px;
    border-top: 1px solid rgba(234,242,255,.28);
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    column-gap: .75rem;
    row-gap: .08rem;
  }

  /* PPK Information Technology Group */
  .brand-app-en {
    font-size: 0.88rem;
    font-weight: 500;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: rgba(234,242,255,.95);
    white-space: nowrap;
  }

  .brand-dot {
    font-size: 0.9rem;
    opacity: .8;
    color: rgba(234,242,255,.7);
  }

  /* Subtitle */
  .brand-subtitle {
    font-size: 0.8rem;
    color: rgba(234,242,255,.85);
    white-space: nowrap;
  }

  /* AVATAR */
  .avatar-img {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.85);
    object-fit: cover;
  }

  .ff-sarabun {
    font-family: 'Sarabun', system-ui, -apple-system, BlinkMacSystemFont,
                 "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
  }

  .popover { z-index: 2300; }
  .popover .popover-body { padding: 0; }

  @media (max-width: 1199.98px) {
    .brand-hospital-th {
      font-size: 1.12rem;
      white-space: normal;
    }
  }

  @media (max-width: 991.98px) {
    .brand-text {
      padding-left: 0.9rem;
    }

    .brand-text::before {
      left: 0;
      top: 10%;
      bottom: 10%;
      opacity: .75;
    }

    .brand-hospital-th {
      font-size: 1.02rem;
    }

    .brand-app-en {
      font-size: 0.82rem;
      letter-spacing: .12em;
      white-space: normal;
    }

    .brand-subtitle {
      font-size: 0.76rem;
      white-space: normal;
    }
  }
</style>

@auth
@push('scripts')
<script>
  (function () {
    const btn = document.getElementById('profilePopoverBtn');
    const tpl = document.getElementById('profilePopoverContent');

    if (!btn || !tpl || !window.bootstrap) return;

    const pop = new bootstrap.Popover(btn, {
      html: true,
      content: tpl.innerHTML,
      placement: 'bottom',
      trigger: 'manual',
      sanitize: false
    });

    function closeAll() {
      document.querySelectorAll('.popover.show').forEach(e => e.remove());
    }

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelector('.popover.show') ? closeAll() : pop.show();
    });

    document.addEventListener('click', (e) => {
      const isBtn = e.target.closest('#profilePopoverBtn');
      const isPop = e.target.closest('.popover');
      if (!isBtn && !isPop) closeAll();
    }, true);
  })();
</script>
@endpush
@endauth
