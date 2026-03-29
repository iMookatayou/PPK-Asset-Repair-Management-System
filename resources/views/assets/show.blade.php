{{-- resources/views/assets/show.blade.php --}}
@extends('layouts.app')

@section('title', 'รายละเอียดครุภัณฑ์ #'.($asset->asset_code ?? $asset->id))

@section('main-class','tight-0')
@section('header-wrap-class','tight-gap')

@php
  $line = 'border-slate-200';

  // ===== UI tokens (match edit scale) =====
  $headCls   = "flex items-start gap-3 pb-4 min-h-[56px]"; // ปรับให้ขนาดเท่ากับ Maintenance
  $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
  $titleCls  = "text-sm font-semibold text-slate-900 leading-tight"; // ขนาดเล็กลง
  $subCls    = "text-sm text-slate-500 leading-snug";  // ขนาดเล็กลง
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-8 rounded-full bg-emerald-600/90"; // ขนาดเล็กลง

  $labelCls  = "text-sm font-semibold text-slate-700";  // ปรับให้เล็กลง
  $valueBox  = "mt-2 rounded-lg border $line bg-white px-4 py-2"; // ปรับให้เล็กลง
  $valueCls  = "text-sm font-semibold text-slate-900"; // ขนาดเล็กลง
  $mutedCls  = "text-[12px] text-slate-500"; // ปรับขนาดเล็กลง

  // ===== Status =====
  $status = strtolower((string) $asset->status);

  $statusLabel = [
    'active'    => 'ใช้งานปกติ',
    'in_repair' => 'อยู่ระหว่างซ่อม',
    'disposed'  => 'จำหน่ายออก',
  ][$status] ?? ($asset->status ?? 'ไม่ระบุ');

  $statusTone = match ($status) {
    'active'    => 'bg-emerald-50 text-emerald-900 border-emerald-200 ring-emerald-100',
    'in_repair' => 'bg-amber-50 text-amber-900 border-amber-200 ring-amber-100',
    'disposed'  => 'bg-rose-50 text-rose-900 border-rose-200 ring-rose-100',
    default     => 'bg-slate-50 text-slate-800 border-slate-200 ring-slate-100',
  };

  // ===== Names =====
  $deptName = optional($asset->department)->name_th
      ?? optional($asset->department)->name_en
      ?? 'ไม่ระบุหน่วยงาน';

  $categoryName = optional($asset->categoryRef)->name ?? 'ไม่ระบุหมวดหมู่';

  // ===== Counts =====
  $reqCount = $asset->maintenance_requests_count ?? 0;
  $attCount = $asset->attachments_count ?? 0;

  $logs    = $logs ?? collect();
  $attList = $attachments ?? collect();

  $assetTitle = $asset->name ?? '—';
  $assetCode  = $asset->asset_code ?? ('#'.$asset->id);

  // ===== Hero image logic =====
  $isImage = function ($att) {
    $mime = strtolower((string)($att->mime_type ?? $att->mime ?? ''));
    $name = strtolower((string)($att->original_name ?? ''));
    return str_starts_with($mime, 'image/')
      || str_ends_with($name, '.png') || str_ends_with($name, '.jpg') || str_ends_with($name, '.jpeg') || str_ends_with($name, '.webp');
  };

  $heroAtt = $attList->first(fn($a) => $isImage($a));
  $heroUrl = $heroAtt->url ?? null;

  $typeKey = strtolower(trim((string)($asset->type ?? '')));
  $catKey  = strtolower(trim((string)($categoryName ?? '')));

  $pickEquipmentImage = function($typeKey, $catKey) {
    $k = $typeKey ?: $catKey;

    $map = [
      'imaging'        => 'imaging.svg',
      'x-ray'          => 'imaging.svg',
      'ct'             => 'imaging.svg',
      'mri'            => 'imaging.svg',
      'ultrasound'     => 'ultrasound.svg',
      'monitor'        => 'monitor.svg',
      'vital'          => 'monitor.svg',
      'ventilator'     => 'ventilator.svg',
      'respir'         => 'ventilator.svg',
      'lab'            => 'lab.svg',
      'analyzer'       => 'lab.svg',
      'infusion'       => 'infusion.svg',
      'pump'           => 'infusion.svg',
      'it'             => 'it.svg',
      'computer'       => 'it.svg',
      'printer'        => 'it.svg',
      'network'        => 'it.svg',
    ];

    foreach ($map as $needle => $file) {
      if ($k && str_contains($k, $needle)) return $file;
    }
    return 'default.svg';
  };

  $fallbackFile = $pickEquipmentImage($typeKey, $catKey);
  $fallbackUrl  = asset('images/equipment/'.$fallbackFile);
  $heroFinal = $heroUrl ?: $fallbackUrl;

  // ===== Misc =====
  $brandModel = trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: '—';
  $purchaseDate = optional($asset->purchase_date)->format('Y-m-d') ?? '—';
  $warrantyExpire = optional($asset->warranty_expire)->format('Y-m-d') ?? '—';

  // header button style (match edit scale a bit)
  $btnBase = "inline-flex items-center gap-2 rounded-xl border $line bg-white px-4 py-2 text-[13px]
              font-medium text-slate-700 hover:bg-slate-50 whitespace-nowrap";  // ปรับให้ขนาดเล็กลง
@endphp


@section('page-header')
  <div class="w-full bg-slate-50 border-b {{ $line }}">
    <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 py-4">
      <div class="flex flex-col gap-3">

        {{-- ROW 1 --}}
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

          {{-- LEFT --}}
          <div class="min-w-0">
            <div class="flex items-start gap-3">

              <span class="mt-0.5 inline-flex items-center justify-center text-emerald-700">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 7h16M4 12h10M4 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>

              <div class="min-w-0">
                <h1 class="text-[22px] sm:text-[24px] font-semibold text-slate-900 leading-tight">
                  Summary of Equipment Information
                  <span class="ml-2 text-slate-500 text-[14px] sm:text-[15px] font-semibold">{{ $assetCode }}</span>
                </h1>

                {{-- chips --}}
                <div class="mt-2 flex flex-wrap items-center gap-2 text-[13px] sm:text-[14px]">
                  <span class="inline-flex items-center gap-2 rounded-full border px-3 py-2 shadow-sm ring-1 {{ $statusTone }}">
                    <span class="text-slate-600">สถานะ</span>
                    <span class="font-semibold">{{ $statusLabel }}</span>
                  </span>

                  <span class="inline-flex items-center gap-2 rounded-full border {{ $line }} bg-white px-3 py-2 text-slate-700">
                    <span class="text-slate-500">คำขอซ่อม</span>
                    <span class="font-semibold text-slate-900">{{ $reqCount }}</span>
                  </span>

                  <span class="inline-flex items-center gap-2 rounded-full border {{ $line }} bg-white px-3 py-2 text-slate-700">
                    <span class="text-slate-500">ไฟล์แนบ</span>
                    <span class="font-semibold text-slate-900">{{ $attCount }}</span>
                  </span>
                </div>

                {{-- meta --}}
                <div class="mt-2 text-[13px] sm:text-[14px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
                  <span>ชื่อ: <span class="font-semibold text-slate-900">{{ $assetTitle }}</span></span>
                  <span>สร้าง: <span class="font-medium text-slate-900">{{ $asset->created_at?->format('Y-m-d H:i') ?? '—' }}</span></span>
                  <span>อัปเดต: <span class="font-medium text-slate-900">{{ $asset->updated_at?->format('Y-m-d H:i') ?? '—' }}</span></span>
                </div>
              </div>
            </div>
          </div>

          {{-- RIGHT buttons --}}
          <div class="flex flex-wrap items-center justify-start lg:justify-end gap-2">
            <a href="{{ route('assets.edit', $asset) }}" class="{{ $btnBase }}">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 21h4l10-10-4-4L4 17v4zM13 7l4 4"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              แก้ไขข้อมูล
            </a>

            <a href="{{ route('assets.print', $asset) }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white
                        px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
                <span class="material-symbols-outlined ms text-[18px] leading-none">print</span>
                พิมพ์ PDF
            </a>


            <a href="{{ route('assets.index') }}" class="{{ $btnBase }}">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              กลับ
            </a>
          </div>
        </div>

        @php
          $assetKey = $asset->getKey();
          $createMrUrl = route('maintenance.requests.create', ['asset_id' => $assetKey]);
          $mrListRoute = route('maintenance.requests.index',  ['asset_id' => $assetKey]);
        @endphp

        <div class="flex flex-wrap items-center justify-start lg:justify-end gap-2">
          <a href="{{ $createMrUrl }}" class="{{ $btnBase }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            สร้างคำขอซ่อมใหม่
          </a>

          <a href="{{ $mrListRoute }}" class="{{ $btnBase }}" onclick="showLoader()">
            ดูประวัติ
          </a>
        </div>

      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 pb-8">
    <div class="mt-0 space-y-8">

      {{-- 1-2 --}}
      <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

        {{-- SECTION 1 --}}
        <section>
          <div class="{{ $headCls }}">
            <div class="{{ $noCls }}">1</div>
            <div class="{{ $accentWrap }}">
              <span class="{{ $accentBar }}"></span>
              <div class="{{ $titleCls }}">ข้อมูลครุภัณฑ์</div>
              <div class="{{ $subCls }}">ทะเบียน / รายละเอียดหลัก</div>
            </div>
          </div>

          <div class="space-y-5">

            <div>
              <div class="{{ $labelCls }}">ชื่อครุภัณฑ์</div>
              <div class="{{ $valueBox }}">
                <div class="{{ $valueCls }}">{{ $asset->name ?? '—' }}</div>
                <div class="mt-1 {{ $mutedCls }}">
                  รหัสครุภัณฑ์: <span class="font-semibold text-slate-900">{{ $assetCode }}</span>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <div class="{{ $labelCls }}">หมวดหมู่</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $categoryName }}</div>
              </div>

              <div>
                <div class="{{ $labelCls }}">หน่วยงานเจ้าของ</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $deptName }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <div class="{{ $labelCls }}">ที่ตั้ง / สถานที่ใช้งาน</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $asset->location ?? '—' }}</div>
              </div>

              <div>
                <div class="{{ $labelCls }}">ประเภท (Type)</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $asset->type ?? '—' }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <div class="{{ $labelCls }}">ยี่ห้อ / รุ่น</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $brandModel }}</div>
              </div>

              <div>
                <div class="{{ $labelCls }}">Serial Number</div>
                <div class="{{ $valueBox }} {{ $valueCls }}">{{ $asset->serial_number ?? '—' }}</div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2 text-[13px] sm:text-[14px] pt-1">
              <span class="inline-flex items-center gap-2 rounded-full border {{ $line }} bg-white px-3 py-2 text-slate-700">
                <span class="text-slate-500">วันที่ซื้อ</span>
                <span class="font-semibold text-slate-900">{{ $purchaseDate }}</span>
              </span>
              <span class="inline-flex items-center gap-2 rounded-full border {{ $line }} bg-white px-3 py-2 text-slate-700">
                <span class="text-slate-500">หมดประกัน</span>
                <span class="font-semibold text-slate-900">{{ $warrantyExpire }}</span>
              </span>
            </div>

            @if(!empty($asset->note))
              <div>
                <div class="{{ $labelCls }}">หมายเหตุ</div>
                <div class="{{ $valueBox }} text-slate-800 whitespace-pre-line min-h-[120px] text-base">
                  {{ $asset->note }}
                </div>
              </div>
            @endif

          </div>
        </section>

        {{-- SECTION 2 --}}
        <section>
          <div class="{{ $headCls }}">
            <div class="{{ $noCls }}">2</div>
            <div class="{{ $accentWrap }}">
              <span class="{{ $accentBar }}"></span>
              <div class="{{ $titleCls }}">รูปครุภัณฑ์</div>
              <div class="{{ $subCls }}">ภาพอ้างอิง (อัตโนมัติจากไฟล์แนบ หรือ fallback ตามประเภท)</div>
            </div>
          </div>

          <div class="space-y-4">
            <figure class="overflow-hidden rounded-xl border {{ $line }} bg-white">
              <div class="aspect-[16/10] bg-slate-50">
                <img src="{{ $heroFinal }}" alt="{{ $assetTitle }}" class="h-full w-full object-cover">
              </div>
              <figcaption class="border-t {{ $line }} px-4 py-3">
                <div class="flex items-center justify-between gap-2">
                  <div class="min-w-0">
                    <div class="truncate text-base font-semibold text-slate-900" title="{{ $assetTitle }}">{{ $assetTitle }}</div>
                    <div class="mt-0.5 {{ $mutedCls }}">
                      {{ $heroUrl ? 'แหล่งรูป: ไฟล์แนบ (อัตโนมัติ)' : ('แหล่งรูป: fallback • '.$fallbackFile) }}
                    </div>
                  </div>
                  <span class="inline-flex items-center rounded-full border px-3 py-1 text-[12px] font-semibold shadow-sm ring-1 {{ $statusTone }}">
                    {{ $statusLabel }}
                  </span>
                </div>
              </figcaption>
            </figure>

            <div class="rounded-lg border {{ $line }} bg-white px-4 py-3">
              <div class="{{ $mutedCls }}">สรุป</div>
              <div class="mt-1 text-base text-slate-800">
                หมวดหมู่: <span class="font-semibold text-slate-900">{{ $categoryName }}</span> •
                หน่วยงาน: <span class="font-semibold text-slate-900">{{ $deptName }}</span>
              </div>
              <div class="mt-1 {{ $mutedCls }}">
                ถ้าต้องการรูปจริงของครุภัณฑ์ แนะนำแนบ “รูปภาพ” ไว้ในไฟล์แนบ ระบบจะดึงรูปนั้นขึ้นมาเป็นรูปหลักอัตโนมัติ
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="border-t {{ $line }}"></div>

      {{-- SECTION 3 --}}
      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">3</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">บันทึกการซ่อมล่าสุด</div>
            <div class="{{ $subCls }}">ไทม์ไลน์การดำเนินการ (เรียงจากล่าสุด)</div>
          </div>
        </div>

        @if($logs->isEmpty())
          <p class="text-base text-slate-500">ยังไม่มีบันทึกการซ่อมสำหรับครุภัณฑ์นี้</p>
        @else
          <div class="relative mt-1">
            <div class="absolute left-4 top-0 bottom-0 w-px bg-slate-200"></div>

            <ol class="space-y-4">
              @foreach($logs as $log)
                @php
                  $act = strtolower((string) $log->action);
                  $dotTone = match ($act) {
                    'resolved', 'closed'   => 'bg-emerald-500 ring-emerald-100',
                    'on_hold'              => 'bg-amber-500 ring-amber-100',
                    'started'              => 'bg-sky-500 ring-sky-100',
                    'accepted', 'assigned' => 'bg-indigo-500 ring-indigo-100',
                    default                => 'bg-slate-400 ring-slate-200',
                  };
                @endphp

                <li class="relative pl-10">
                  <span class="absolute left-2 top-2 h-3.5 w-3.5 rounded-full border-2 border-white shadow-sm ring-4 {{ $dotTone }}"></span>

                  <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between">
                    <div class="{{ $mutedCls }}">
                      {{ $log->created_at?->format('Y-m-d') ?? '—' }}
                      @if($log->created_at)
                        <span class="ml-1 text-[12px] text-slate-400">{{ $log->created_at->format('H:i') }}</span>
                      @endif
                    </div>
                    @if($loop->first)
                      <span class="text-[12px] uppercase tracking-wide text-emerald-600 font-semibold">รายการล่าสุด</span>
                    @endif
                  </div>

                  <div class="mt-0.5 text-base font-semibold text-slate-900">
                    {{ ucfirst(str_replace('_',' ', (string)$log->action)) }}
                  </div>

                  @if($log->note)
                    <div class="mt-0.5 text-[14px] leading-relaxed text-slate-600 whitespace-pre-line">
                      {{ $log->note }}
                    </div>
                  @endif
                </li>
              @endforeach
            </ol>
          </div>
        @endif
      </section>

      <div class="border-t {{ $line }}"></div>

      {{-- SECTION 4 --}}
      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">4</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ไฟล์แนบของครุภัณฑ์</div>
            <div class="{{ $subCls }}">รูป / เอกสารประกอบ</div>
          </div>
        </div>

        @if($attList->count())
          <div class="mb-2 text-base font-medium text-slate-700">
            ไฟล์ที่แนบไว้แล้ว ({{ $attList->count() }} ไฟล์)
          </div>

          <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            @foreach($attList as $att)
              @php
                $name = $att->original_name ?? ('Attachment #'.$att->id);
                $openUrl = $att->url ?? null;
                $mime = strtolower((string)($att->mime_type ?? $att->mime ?? ''));
                $isImg = $mime ? str_starts_with($mime, 'image/') : false;
                $ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE');
              @endphp

              <figure class="overflow-hidden rounded-lg border {{ $line }} bg-white text-[12px]">
                @if($isImg && $openUrl)
                  <a href="{{ $openUrl }}" target="_blank" rel="noopener">
                    <img src="{{ $openUrl }}" alt="{{ $name }}" class="h-32 w-full object-cover">
                  </a>
                @else
                  <div class="grid h-32 w-full place-items-center text-slate-500 text-[13px]">
                    {{ $ext }}
                  </div>
                @endif

                <figcaption class="px-3 py-2 space-y-2">
                  <div class="truncate text-slate-600 text-[12px]" title="{{ $name }}">{{ $name }}</div>

                  <div class="flex items-center justify-between gap-2">
                    @if($openUrl)
                      <a href="{{ $openUrl }}" target="_blank" rel="noopener"
                         class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-800 hover:bg-sky-100">
                        เปิด
                      </a>
                    @else
                      <span class="inline-flex items-center rounded-md border {{ $line }} bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-500">
                        ไม่มีลิงก์
                      </span>
                    @endif

                    <span class="text-[11px] text-slate-400">
                      {{ $att->created_at?->format('Y-m-d') ?? '' }}
                    </span>
                  </div>
                </figcaption>
              </figure>
            @endforeach
          </div>
        @else
          <p class="text-base text-slate-500">ยังไม่มีไฟล์แนบสำหรับครุภัณฑ์นี้</p>
        @endif
      </section>

    </div>
  </div>

  <style>
    @media print {
      nav, aside, .no-print { display:none !important; }
      main { padding:0 !important; }
      body { background:white !important; }
    }
  </style>
@endsection
