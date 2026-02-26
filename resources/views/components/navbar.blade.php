@props([
    'logo'         => asset('images/logoppk.png'),
    'bannerText'   => null,
    'bannerAction' => null,
    'bannerLabel'  => null,
    'showLogout'   => Auth::check(),
])

@php
    $user = Auth::user();
    $breadcrumbs = \App\Support\Breadcrumb::generate();
@endphp

<nav class="navbar navbar-expand-lg navbar-pinwheel shadow-sm fixed-top">
    {{-- Brand Block --}}
    <div class="nav-brand-block d-flex align-items-center justify-content-between px-3">
        <button type="button" onclick="openSide()"
                class="d-lg-none btn text-white p-0 me-2 sidebar-hamburger flex align-items-center justify-content-center"
                style="font-size: 1.8rem; line-height: 1; border: none; background: transparent; min-width: 44px; min-height: 44px;"
                aria-label="เปิดเมนู">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
            <img id="sidebarLogo" src="{{ $logo }}" alt="Logo" class="brand-logo h-10 w-auto flex-shrink-0" />
            <div class="d-flex flex-column leading-tight d-none d-lg-flex">
                <span class="brand-en brand-title text-white">PHRAPOKKLAO</span>
                <span class="brand-sub text-slate-200 text-truncate">โรงพยาบาลพระปกเกล้า</span>
            </div>
        </div>
    </div>

    {{-- Main Content Section --}}
    <div class="container-fluid px-4 h-100 d-flex align-items-center nav-main">
        <div class="nav-left d-none d-md-flex align-items-center gap-2">
            <div class="brand-en nav-system-title">
                Asset Repair Management System
            </div>
        </div>

        {{-- Breadcrumbs & Banner --}}
        <div class="nav-center d-none d-md-flex align-items-center flex-grow-1 px-4">
            @if(empty($bannerText) && count($breadcrumbs) > 0)
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-custom m-0 ff-sarabun align-items-center">
                        @foreach($breadcrumbs as $item)
                            <li class="breadcrumb-item {{ $item['active'] ? 'active' : '' }}">
                                @if($item['url'] && !$item['active'])
                                    <a href="{{ $item['url'] }}" class="text-decoration-none nav-breadcrumb-link">
                                        {{ $item['label'] }}
                                    </a>
                                @else
                                    <span class="nav-breadcrumb-active">
                                        {{ $item['label'] }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif

            @if($bannerText)
                <span class="nav-banner-text me-3">{{ $bannerText }}</span>
                @if($bannerAction && $bannerLabel)
                    <a href="{{ $bannerAction }}" class="btn btn-sm nav-banner-btn">
                        {{ $bannerLabel }}
                    </a>
                @endif
            @endif
        </div>

        {{-- Right Section: My Jobs & Profile --}}
        <div class="nav-right ms-auto d-flex align-items-center gap-3">
            @auth
                {{-- แก้ไข: เพิ่มเงื่อนไขไม่ให้ Member เห็นปุ่ม My Jobs --}}
                @if($user->role !== 'member')
                    <a id="navMyJobsIcon"
                       href="{{ route('repairs.my_jobs') }}"
                       class="nav-icon-btn d-none d-md-inline-flex"
                       data-no-loader
                       aria-label="ไปหน้า My Jobs"
                       title="My Jobs">
                        <i class="bi bi-clipboard2-check" aria-hidden="true"></i>
                        <span id="navMyJobsDot" class="nav-dot d-none" aria-hidden="true">
                            <span class="nav-dot__ping"></span>
                            <span class="nav-dot__core"></span>
                        </span>
                    </a>
                @endif
            @endauth

            {{-- Profile Dropdown (Desktop) --}}
            <div class="d-none d-md-block">
                @auth
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-0"
                               href="#" id="profileDropdown"
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="ff-sarabun nav-username d-none d-lg-inline">
                                    {{ $user->name }}
                                </span>
                                <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                                     class="avatar-img" alt="Avatar">
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow-sm profile-dropdown-menu">
                                <li class="px-3 pt-2 pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                                             width="42" height="42" class="rounded-circle">
                                        <div class="ff-sarabun">
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a href="{{ route('profile.show') }}"
                                       class="dropdown-item ff-sarabun d-flex align-items-center gap-2">
                                        <i class="bi bi-person-lines-fill"></i>
                                        โปรไฟล์ของฉัน
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li class="px-3 pb-2">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button class="btn btn-outline-danger btn-sm w-100 ff-sarabun">
                                            <i class="bi bi-box-arrow-right me-1"></i> ออกจากระบบ
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm ff-sarabun px-3">
                        เข้าสู่ระบบ
                    </a>
                @endguest
            </div>

            {{-- Profile Toggle for Mobile --}}
            <div class="d-md-none">
                <button class="btn btn-link p-0 border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileOffcanvas">
                    @auth
                        <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}" class="avatar-img" alt="Avatar">
                    @endauth
                    @guest
                        <span class="navbar-toggler-icon custom-toggler"></span>
                    @endguest
                </button>
            </div>
        </div>
    </div>
</nav>

{{-- Mobile Menu Offcanvas --}}
<div class="offcanvas offcanvas-end d-md-none" tabindex="-1" id="mobileOffcanvas" aria-labelledby="mobileOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title ff-sarabun fw-bold" id="mobileOffcanvasLabel">เมนูการใช้งาน</h5>
        <button type="button" class="btn-close text-reset" onclick="closeSide()" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3">
        @if(count($breadcrumbs) > 0)
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0 ff-sarabun small">
                        @foreach($breadcrumbs as $item)
                            <li class="breadcrumb-item {{ $item['active'] ? 'active' : '' }}">
                                @if($item['url'] && !$item['active'])
                                    <a href="{{ $item['url'] }}" class="text-decoration-none text-muted">{{ $item['label'] }}</a>
                                @else
                                    <span class="fw-semibold">{{ $item['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        @endif

        @auth
            {{-- แก้ไข: เพิ่มเงื่อนไขไม่ให้ Member เห็นปุ่ม My Jobs ใน Mobile --}}
            @if($user->role !== 'member')
                <a id="navMyJobsMobile"
                   href="{{ route('repairs.my_jobs') }}"
                   class="btn btn-outline-primary btn-sm w-100 ff-sarabun mb-4 d-flex align-items-center justify-content-between py-2"
                   data-no-loader>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-clipboard2-check"></i>
                        <span>งานของฉัน (My Jobs)</span>
                    </span>
                    <span id="navMyJobsMobileDot" class="nav-dot nav-dot--mobile d-none">
                        <span class="nav-dot__ping"></span>
                        <span class="nav-dot__core"></span>
                    </span>
                </a>
            @endif

            <div class="mobile-profile-card">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}" width="45" height="45" class="rounded-circle border" alt="Avatar">
                    <div class="ff-sarabun">
                        <div class="fw-bold" style="font-size: 1rem;">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="{{ route('profile.show') }}" class="btn btn-light btn-sm ff-sarabun text-start px-3">
                        <i class="bi bi-person me-2"></i> โปรไฟล์ของฉัน
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button class="btn btn-danger btn-sm ff-sarabun w-100 py-2">
                            <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
                        </button>
                    </form>
                </div>
            </div>
        @endauth

        @guest
            <div class="d-grid gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm ff-sarabun py-2">เข้าสู่ระบบ</a>
            </div>
        @endguest
    </div>
</div>

<style>
/* CSS เหมือนเดิม ไม่มีการเปลี่ยนแปลง */
@import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');

:root{
    --nav-height: 80px;
    --ppk-blue: #0F2D5C;
    --ppk-blue-2: #133A73;
    --ppk-border: #e2e8f0;
    --ppk-text: #0f172a;
    --ppk-muted: #64748b;
    --ppk-soft: rgba(15,45,92,.08);
    --side-w: 260px;
}

.ff-sarabun{ font-family: 'Sarabun', sans-serif !important; }
.brand-en{ font-family: 'Sarabun', sans-serif; }

.navbar-pinwheel{
    height: var(--nav-height);
    background: #ffffff;
    padding: 0;
    border-bottom: 1px solid var(--ppk-border);
    z-index: 1030;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02) !important;
    left: 0;
    width: 100%;
}

.nav-brand-block{
    width: var(--side-w);
    height: var(--nav-height);
    background: var(--ppk-blue);
    border-right: 1px solid rgba(15,45,92,0.12);
    flex: 0 0 auto;
}

.brand-logo{ height: 48px; width: auto; }
.brand-title{
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0.12em;
    line-height: 1.2;
}
.brand-sub{ font-size: 12px; opacity: 0.9; }

.nav-main{ height: var(--nav-height); flex: 1 1 auto; }

.nav-system-title{
    font-size: .85rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--ppk-text);
    white-space: nowrap;
}

.breadcrumb-custom {
    --bs-breadcrumb-divider: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%2394a3b8'/%3E%3C/svg%3E");
    --bs-breadcrumb-item-padding-x: 0.6rem;
}

.nav-breadcrumb-link {
    color: var(--ppk-muted);
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.nav-breadcrumb-link:hover {
    color: var(--ppk-blue);
    text-decoration: none;
}

.nav-breadcrumb-active {
    color: var(--ppk-blue);
    font-size: 0.9rem;
    font-weight: 700;
}

.nav-banner-text{ font-size: .9rem; color: var(--ppk-muted); }
.nav-banner-btn{
    border-radius: 999px;
    padding-inline: 1rem;
    font-size: .8rem;
    background-color: var(--ppk-blue);
    border-color: var(--ppk-blue);
    color: #ffffff;
}

.avatar-img{
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f1f5f9;
}

.profile-dropdown-menu{
    min-width: 260px;
    border-radius: 12px;
    padding: 0.5rem;
    border: 1px solid rgba(15,45,92,.08);
}

.profile-dropdown-menu .dropdown-item{
    border-radius: 8px;
    padding: 8px 12px;
}

.mobile-profile-card{
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e7eb;
    background-color: #f8fafc;
}

.nav-icon-btn{
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(15,45,92,.05);
    color: var(--ppk-blue);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.nav-icon-btn:hover { background: rgba(15,45,92,.1); }

.nav-dot{
    position: absolute;
    right: -2px;
    top: -2px;
    width: 12px;
    height: 12px;
}

.nav-dot__core{
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: #dc2626;
    border: 2px solid #ffffff;
}

.nav-dot__ping{
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(220,38,38,.4);
    animation: navPing 1.5s infinite;
}

@keyframes navPing{
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(2.5); opacity: 0; }
}

@media (max-width: 992px){
    .nav-brand-block{ width: auto; min-width: 70px; border-right: none; }
    .brand-logo { height: 40px; }
    :root { --nav-height: 70px; }
    .navbar-pinwheel { height: 70px; }
    .sidebar-hamburger:active { opacity: 0.8; transform: scale(0.95); }
}
</style>
