@extends('layouts.app')

@section('title', 'สรุปใบงานซ่อม #'.$req->id)

@section('page-header')
@php
  use App\Models\MaintenanceRequest as MR;
  use Carbon\Carbon;

  // ===== UI tokens (ให้เหมือน edit) =====
  $line = 'border-slate-200';

  // 1) แปลงสถานะเป็นภาษาไทย
  $statusLabels = [
      'pending'      => 'รอรับเรื่อง',
      'acknowledged' => 'รับทราบแล้ว',
      'accepted'     => 'รับเรื่องแล้ว',
      'in_progress'  => 'กำลังดำเนินการ',
      'resolved'     => 'เสร็จสิ้น',
      'closed'       => 'ปิดงาน',
      'cancelled'    => 'ยกเลิก',
  ];
  $currentStatusTH = $statusLabels[$req->status] ?? $req->status;

  // 2) Timeline level mapping
  $status = $req->status;
  $level = 1;
  if ($status === MR::STATUS_ACKNOWLEDGED) $level = 2;
  if ($status === MR::STATUS_ACCEPTED)     $level = 3;
  if ($status === MR::STATUS_IN_PROGRESS)  $level = 4;
  if (in_array($status, [MR::STATUS_RESOLVED, MR::STATUS_CLOSED])) $level = 5;
  if ($status === MR::STATUS_CANCELLED)    $level = 0;

  // Dates
  $dates = [
      1 => $req->request_date ?? $req->created_at,
      2 => $req->acknowledged_at,
      3 => $req->accepted_at,
      4 => $req->in_progress_at ?? ($level >= 4 ? $req->accepted_at : null),
      5 => $req->resolved_at ?? $req->closed_at,
  ];

  $fmt = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y H:i') : '';

  // Progress Bar Width
  $widthMap = [1 => '0%', 2 => '25%', 3 => '50%', 4 => '75%', 5 => '100%'];
  $lineWidth = $widthMap[$level] ?? '0%';

  // Permissions (ป้องกัน Error)
  $canAcknowledge = ($status === MR::STATUS_PENDING) && \Gate::allows('acknowledge', $req);
  $canAccept      = ($status === MR::STATUS_ACKNOWLEDGED) && \Gate::allows('accept', $req);
  $canReject      = ($status === MR::STATUS_ACKNOWLEDGED) && \Gate::allows('accept', $req);
  $canCancel      = in_array($status, [MR::STATUS_PENDING, MR::STATUS_ACKNOWLEDGED, MR::STATUS_ACCEPTED]);

  // ===== Action button base (ทำให้ปุ่มเล็กลง) =====
  $btnBase = 'inline-flex items-center justify-center gap-2
              rounded-md px-4 py-1.5
              text-[13px] font-medium
              transition
              focus:outline-none focus:ring-2 focus:ring-offset-1';
@endphp

<style>
  /* Animation หมุนติ้วๆ สำหรับสถานะปัจจุบัน */
  .animate-spin-slow { animation: spin 2s linear infinite; }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="w-full bg-slate-50 border-b {{ $line }}">
  <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

      {{-- LEFT (เหมือน edit) --}}
      <div class="min-w-0">
        <div class="flex items-start gap-3">
          {{-- ไอคอน: ไม่มีพื้นหลัง/ไม่มีกรอบ --}}
          <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl text-emerald-700">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M9 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M7 3h7l3 3v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>

          <div class="min-w-0">
            <h1 class="text-[20px] sm:text-[22px] font-semibold text-slate-900 leading-tight">
              Maintenance Summary
              <span class="ml-2 text-slate-500 text-[13px] sm:text-[14px] font-semibold">
                #{{ $req->request_no ?? $req->id }}
              </span>
            </h1>

            <div class="mt-1 text-xs sm:text-[13px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1">
              <span>สถานะปัจจุบัน:
                <span class="font-semibold text-slate-900">{{ $currentStatusTH }}</span>
              </span>

              @if($req->updated_at)
                <span>อัปเดต:
                  <span class="font-medium text-slate-900">{{ $req->updated_at->format('Y-m-d H:i') }}</span>
                </span>
              @endif

              <span>ผู้รับผิดชอบหลัก:
                <span class="font-semibold text-slate-900">
                  {{ $req->technician?->name ?? 'ยังไม่มีช่างรับงาน' }}
                </span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
        <a href="{{ route('maintenance.requests.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back
        </a>

        <button type="button" onclick="window.print()"
          class="inline-flex items-center gap-2 rounded-lg border {{ $line }} bg-white px-4 py-2 text-[13px] font-medium text-slate-700 hover:bg-slate-50">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 8V4h10v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M7 17h10v3H7v-3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Print
        </button>
      </div>

    </div>

    {{-- TIMELINE AREA --}}
    <div class="w-full px-2 sm:px-4 mt-6 mb-2">
      <div class="relative w-full">
        <div class="absolute top-[22px] left-0 w-full h-[6px] bg-slate-200 rounded-full z-0"></div>
        <div class="absolute top-[22px] left-0 h-[6px] bg-[#1e3a8a] rounded-full z-0 transition-all duration-700 ease-out"
             style="width: {{ $lineWidth }};"></div>

        <div class="relative z-10 flex justify-between w-full">
          @php
            $steps = [
              1 => 'แจ้งเรื่อง',
              2 => 'รับทราบแล้ว',
              3 => 'รับเรื่องแล้ว',
              4 => 'กำลังดำเนินการ',
              5 => 'เสร็จสิ้น'
            ];
          @endphp

          @foreach($steps as $key => $label)
            @php
              $isDone    = $level > $key;
              $isCurrent = $level == $key;
              $dateVal   = $dates[$key] ?? null;
            @endphp

            <div class="flex flex-col items-center w-32">
              <div class="w-[44px] h-[44px] rounded-full flex items-center justify-center transition-all duration-300
                {{ $isDone ? 'bg-[#408a5c] border-4 border-[#408a5c]' : '' }}
                {{ $isCurrent ? 'bg-white' : '' }}
                {{ !$isDone && !$isCurrent ? 'bg-slate-200 border-4 border-slate-200' : '' }}
              ">
                @if($isDone)
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                @elseif($isCurrent)
                  <div class="relative w-full h-full">
                    <div class="absolute inset-0 rounded-full border-[4px] border-slate-100"></div>
                    <div class="absolute inset-0 rounded-full border-[4px] border-t-blue-600 border-r-transparent border-b-transparent border-l-transparent animate-spin-slow"></div>
                  </div>
                @endif
              </div>

              <div class="mt-3 text-center">
                <p class="text-[14px] font-bold {{ $isCurrent || $isDone ? 'text-slate-900' : 'text-slate-400' }}">
                  {{ $label }}
                </p>
                @if($isCurrent)
                  <p class="text-[12px] font-medium text-[#1e3a8a] mt-0.5">(ปัจจุบัน)</p>
                @elseif($dateVal)
                  <p class="text-[12px] text-slate-500 mt-0.5">({{ $fmt($dateVal) }})</p>
                @else
                  <p class="h-[18px]"></p>
                @endif
              </div>
            </div>
          @endforeach
        </div>

      </div>
    </div>

    {{-- ACTION BUTTONS (ปรับให้เล็กลงตามที่ต้องการ) --}}
    <div class="flex justify-end gap-2 mt-6 pt-5 border-t border-slate-100">

      @if($canAcknowledge)
        <form method="POST" action="{{ route('maintenance.requests.acknowledge', $req->id) }}">
          @csrf
          <button class="{{ $btnBase }} bg-[#1e3a8a] text-white hover:bg-blue-900 focus:ring-blue-300">
            รับทราบ
          </button>
        </form>
      @endif

      @if($canAccept)
        <form method="POST" action="{{ route('maintenance.requests.accept', $req->id) }}">
          @csrf
          <button class="{{ $btnBase }} bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300">
            รับเรื่อง / ดำเนินการ
          </button>
        </form>
      @endif

      @if($canReject)
        <button type="button" class="{{ $btnBase }} bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-300">
          ไม่รับเรื่อง
        </button>
      @endif

      @if($canCancel)
        <form method="POST" action="{{ route('maintenance.requests.cancel', $req->id) }}"
              onsubmit="return confirm('ยืนยันการยกเลิก?');">
          @csrf
          <button class="{{ $btnBase }} bg-slate-600 text-white hover:bg-slate-700 focus:ring-slate-300">
            ยกเลิกซ่อม
          </button>
        </form>
      @endif

    </div>

  </div>
</div>
@endsection

@section('content')
@php
  use Illuminate\Support\Facades\Storage;

  $line = "border-slate-200";

  $input = "mt-2 w-full h-11 rounded-md border $line bg-white px-3 py-2 text-sm
            focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";
  $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm
              focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
  $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
  $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
  $subCls    = "text-sm text-slate-500 leading-snug";
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";

  // FIX: ให้ $prioLabel / $prioTextTone มีค่าใน section นี้ (กัน Undefined variable)
  $prio = strtolower((string) ($req->priority ?? ''));

  $prioLabel = [
    'low'    => 'ต่ำ',
    'medium' => 'ปานกลาง',
    'high'   => 'สูง',
    'urgent' => 'เร่งด่วน',
  ][$prio] ?? '-';

  $prioTextTone = match ($prio) {
    'low'    => 'text-slate-600',
    'medium' => 'text-sky-600',
    'high'   => 'text-amber-600',
    'urgent' => 'text-rose-600',
    default  => 'text-slate-600',
  };

  $assetName = $req->asset?->name ?? ($req->asset_id ? '#'.$req->asset_id : '—');
  $assetCode = $req->asset?->asset_code;
  $location  = $req->location_text ?: ($req->department?->name_th ?? $req->department?->name_en ?? '—');

  $assignments = $req->assignments ?? collect();
  $workers = $assignments->map(fn($a) => $a->user)->filter()->unique('id')->values();

  $atts  = ($req->attachments ?? collect());
  $opLog = $req->operationLog;

  $allWorkers = $techUsers ?? collect();

  $assignStoreUrl  = route('maintenance.requests.assignments.store', $req->id);
  $opLogUrl        = route('maintenance.requests.operation-log', $req->id);
  $attachUploadUrl = route('maintenance.requests.attachments', $req->id);
@endphp

{{-- ✅ ด้านล่างคงของเดิมทั้งหมด --}}
<div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8 pb-8">
  <div class="mt-6 space-y-10">

    {{-- 1-2 --}}
    <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10">
      <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">1</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ข้อมูลหลัก</div>
            <div class="{{ $subCls }}">ทรัพย์สิน / หน่วยงาน / สถานที่</div>
          </div>
        </div>

        <div class="space-y-4 text-sm">
          <div>
            <div class="text-sm font-medium text-slate-700">ทรัพย์สิน</div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
              <div class="font-semibold text-slate-900">{{ $assetName }}</div>
              @if($assetCode)
                <div class="mt-1 text-xs text-slate-500">รหัสครุภัณฑ์: {{ $assetCode }}</div>
              @endif
            </div>
          </div>

          <div>
            <div class="text-sm font-medium text-slate-700">หน่วยงาน</div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
              <div class="font-semibold text-slate-900">
                {{ $req->department?->name_th ?? $req->department?->name_en ?? '—' }}
              </div>
              @if($req->department?->code)
                <div class="mt-1 text-xs text-slate-500">รหัสหน่วยงาน: {{ $req->department->code }}</div>
              @endif
            </div>
          </div>

          <div>
            <div class="text-sm font-medium text-slate-700">สถานที่ / ตำแหน่งงาน</div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
              <div class="font-semibold text-slate-900">{{ $location }}</div>
            </div>
          </div>
        </div>
      </section>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">2</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">รายละเอียดปัญหา</div>
            <div class="{{ $subCls }}">หัวข้อและอาการเสีย</div>
          </div>
        </div>

        <div class="space-y-4 text-sm">
          <div>
            <div class="text-sm font-medium text-slate-700">หัวข้อ <span class="text-rose-600">*</span></div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 font-semibold text-slate-900 min-h-[44px]">
              {{ $req->title ?: '-' }}
            </div>
          </div>

          <div>
            <div class="text-sm font-medium text-slate-700">รายละเอียด / อาการเสีย</div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2 text-slate-800 whitespace-pre-line min-h-[120px]">
              {{ $req->description ?: '—' }}
            </div>
          </div>
        </div>
      </section>
    </div>

    <div class="border-t {{ $line }}"></div>

    {{-- 3 --}}
    <section>
      <div class="{{ $headCls }}">
        <div class="{{ $noCls }}">3</div>
        <div class="{{ $accentWrap }}">
          <span class="{{ $accentBar }}"></span>
          <div class="{{ $titleCls }}">ผู้แจ้ง &amp; ความสำคัญ</div>
          <div class="{{ $subCls }}">ข้อมูลผู้แจ้ง + ระดับความสำคัญ</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4 text-sm">
          <div>
            <div class="text-sm font-medium text-slate-700">ผู้แจ้ง</div>
            <div class="mt-2 rounded-md border {{ $line }} bg-white px-3 py-2">
              <div class="font-semibold text-slate-900">
                {{ $req->reporter?->name ?? $req->reporter_name ?? '-' }}
              </div>
              @if(($req->reporter?->email ?? $req->reporter_email) || ($req->reporter_phone))
                <div class="mt-1 text-xs text-slate-500 space-y-0.5">
                  @if(($req->reporter?->email ?? $req->reporter_email)) <div>{{ $req->reporter?->email ?? $req->reporter_email }}</div> @endif
                  @if(($req->reporter_phone)) <div>โทร. {{ $req->reporter_phone }}</div> @endif
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="space-y-4 text-sm">
          <div>
            <div class="text-sm font-medium text-slate-700">ระดับความสำคัญ</div>
            <div class="mt-2 text-[15px] font-semibold {{ $prioTextTone }}">
              {{ $prioLabel }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="border-t {{ $line }}"></div>

    {{-- 4 (เต็มแถว) --}}
    <section>
      <div class="{{ $headCls }}">
        <div class="{{ $noCls }}">4</div>
        <div class="{{ $accentWrap }}">
          <span class="{{ $accentBar }}"></span>
          <div class="{{ $titleCls }}">ไฟล์แนบ</div>
          <div class="{{ $subCls }}">รูป / เอกสารประกอบ</div>
        </div>
      </div>

      @can('attach', $req)
        <form method="post" enctype="multipart/form-data" action="{{ $attachUploadUrl }}" class="space-y-4" novalidate>
          @csrf
          <div>
            <label for="caption" class="block text-sm font-medium text-slate-700">คำอธิบายไฟล์</label>
            <input id="caption" type="text" name="caption" class="{{ $input }}"
                   value="{{ old('caption') }}" placeholder="เช่น รูปก่อนซ่อม / รูปหลังซ่อม / ใบเสนอราคา">
          </div>

          <div>
            <label for="file" class="block text-sm font-medium text-slate-700">เลือกไฟล์ <span class="text-rose-600">*</span></label>
            <input id="file" type="file" name="file" required accept="image/*,application/pdf"
                   class="mt-2 block w-full rounded-md border {{ $line }} bg-white px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">รองรับรูปภาพ และ PDF • สูงสุดไฟล์ละ 10MB</p>
          </div>

          <div>
            <label for="alt_text" class="block text-sm font-medium text-slate-700">Alt text (เพื่อการเข้าถึง)</label>
            <input id="alt_text" type="text" name="alt_text" class="{{ $input }}"
                   value="{{ old('alt_text') }}" placeholder="ข้อความอธิบายรูปภาพ">
            <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="is_private" value="1" class="h-4 w-4 rounded border-slate-300">
              เก็บเป็นไฟล์ส่วนตัว
            </label>
          </div>

          <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center rounded-lg border {{ $line }} bg-white px-4 py-2 text-xs sm:text-[13px] font-semibold text-slate-800 hover:bg-slate-50">
              อัปโหลดไฟล์
            </button>
          </div>
        </form>
      @else
        <div class="rounded-md border {{ $line }} bg-white px-3 py-2 text-sm text-slate-600">
          คุณไม่มีสิทธิ์แนบไฟล์ในใบงานนี้
        </div>
      @endcan

      <div class="mt-6 border-t {{ $line }} pt-4">
        @if($atts->count())
          <div class="mb-2 text-sm font-medium text-slate-700">
            ไฟล์ที่แนบไว้แล้ว ({{ $atts->count() }} ไฟล์)
          </div>

          <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            @foreach($atts as $att)
              @php
                $file = $att->file;
                $name = $att->original_name ?? ($file?->path ?? 'file');
                $isPrivate = (bool) ($att->is_private ?? false);
                $mime = $file?->mime ?? '';
                $isImg = $mime && str_starts_with($mime, 'image/');

                $publicUrl = null;
                if ($file && ($file->disk ?? null) && ($file->path ?? null)) {
                  try { $publicUrl = Storage::disk($file->disk)->url($file->path); } catch (\Throwable $e) { $publicUrl = null; }
                }

                $canOpenPrivate = auth()->check() && auth()->user()->can('update', $req);
                $canOpen = !$isPrivate || $canOpenPrivate;

                $openUrl = $publicUrl;
                try { $openUrl = route('attachments.show', $att); } catch (\Throwable $e) { $openUrl = $publicUrl; }

                $deleteUrl = null;
                try {
                  $deleteUrl = route('maintenance.requests.attachments.destroy', [
                    'maintenanceRequest' => $req->id,
                    'attachment' => $att->id,
                  ]);
                } catch (\Throwable $e) { $deleteUrl = null; }
              @endphp

              <figure class="overflow-hidden rounded-lg border {{ $line }} bg-white text-xs">
                @if($isImg && !$isPrivate && $openUrl)
                  <a href="{{ $openUrl }}" target="_blank" rel="noopener">
                    <img src="{{ $openUrl }}" alt="{{ $att->alt_text ?? $name }}" class="h-32 w-full object-cover">
                  </a>
                @else
                  <div class="grid h-32 w-full place-items-center text-slate-500 text-[13px]">
                    {{ strtoupper(pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE') }}
                  </div>
                @endif

                <figcaption class="px-3 py-2 space-y-2">
                  <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium
                                 {{ $isPrivate ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                      {{ $isPrivate ? 'private' : 'public' }}
                    </span>
                    <span class="truncate text-slate-600 text-[12px]" title="{{ $name }}">{{ $name }}</span>
                  </div>

                  <div class="flex items-center justify-between gap-2">
                    @if($canOpen && $openUrl)
                      <a href="{{ $openUrl }}" target="_blank" rel="noopener"
                         class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-800 hover:bg-sky-100">
                        เปิด
                      </a>
                    @else
                      <span class="inline-flex items-center rounded-md border {{ $line }} bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-500">
                        ไม่อนุญาต
                      </span>
                    @endif

                    @can('deleteAttachment', $req)
                      @if($deleteUrl)
                        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('ยืนยันลบไฟล์แนบนี้?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                                  class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-medium text-rose-700 hover:bg-rose-100">
                            ลบ
                          </button>
                        </form>
                      @endif
                    @endcan
                  </div>
                </figcaption>
              </figure>
            @endforeach
          </div>
        @else
          <p class="text-sm text-slate-500">ยังไม่มีไฟล์แนบในใบงานนี้</p>
        @endif
      </div>
    </section>

    <div class="border-t {{ $line }}"></div>

    {{-- ✅ 5 ซ้าย / 6 ขวา --}}
    <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10">
      <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

      {{-- SECTION 5 --}}
      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">5</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">รายงานการปฏิบัติงานและค่าใช้จ่าย</div>
            <div class="{{ $subCls }}">สำหรับทีมช่าง: ระบุวิธีคิดค่าใช้จ่าย, รพจ. และรายละเอียดประกอบ</div>
          </div>
        </div>

        @can('update', $req)
          <form method="post" action="{{ $opLogUrl }}" class="space-y-4" novalidate>
            @csrf

            <div>
              <label for="operation_date" class="block text-sm font-medium text-slate-700">รายการซ่อมสำหรับวันที่</label>
              <input id="operation_date" type="date" name="operation_date"
                     value="{{ old('operation_date', optional($opLog?->operation_date)->format('Y-m-d')) }}"
                     class="{{ $input }}">
            </div>

            <div>
              <div class="block text-sm font-medium text-slate-700">วิธีการปฏิบัติ / การคิดค่าใช้จ่าย</div>
              @php $method = old('operation_method', $opLog->operation_method ?? null); @endphp
              <div class="mt-2 space-y-2 text-sm">
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="requisition" @checked($method === 'requisition')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>ตามใบเบิกครุภัณฑ์ / วัสดุ</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="service_fee" @checked($method === 'service_fee')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>ค่าบริการ / ค่าแรงช่าง</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="radio" name="operation_method" value="other" @checked($method === 'other')
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>อื่น ๆ</span>
                </label>
              </div>
            </div>

            <div>
              <label for="property_code" class="block text-sm font-medium text-slate-700">ระบุรพจ. (รหัสครุภัณฑ์)</label>
              <input id="property_code" type="text" name="property_code"
                     value="{{ old('property_code', $opLog->property_code ?? ($assetCode ?? '')) }}"
                     class="{{ $input }}" placeholder="เช่น 68101068718">
            </div>

            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="require_precheck" value="1"
                     @checked(old('require_precheck', $opLog->require_precheck ?? false))
                     class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
              ยืนยันว่าได้แจ้งผู้ใช้งาน / หน่วยงาน และขออนุญาตก่อนปฏิบัติงาน/ปิดเครื่อง
            </label>

            <div>
              <div class="text-sm font-medium text-slate-700">ประเภทงานที่ปฏิบัติ</div>
              <div class="mt-2 space-y-2 text-sm">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="issue_software" value="1"
                         @checked(old('issue_software', $opLog->issue_software ?? false))
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>Software</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="issue_hardware" value="1"
                         @checked(old('issue_hardware', $opLog->issue_hardware ?? false))
                         class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                  <span>Hardware</span>
                </label>
              </div>
            </div>

            <div>
              <label for="remark" class="block text-sm font-medium text-slate-700">หมายเหตุ / รายละเอียดประกอบ</label>
              <textarea id="remark" name="remark" rows="4" class="{{ $textarea }}"
                        placeholder="เช่น ตรวจเช็คแล้วพบว่า..., ผู้ใช้ทดสอบแล้วเรียบร้อย">{{ old('remark', $opLog->remark ?? '') }}</textarea>
            </div>

            <div class="flex justify-end">
              <button type="submit"
                      class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-xs sm:text-[13px] font-semibold text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200">
                บันทึกรายงานการปฏิบัติงาน
              </button>
            </div>
          </form>
        @else
          <div class="rounded-md border {{ $line }} bg-white px-3 py-2 text-sm text-slate-600">
            คุณไม่มีสิทธิ์แก้ไขรายงานการปฏิบัติงาน (Operation Log)
          </div>
        @endcan

        @if($opLog)
          <p class="mt-3 text-xs text-slate-500">
            บันทึกล่าสุดโดย {{ $opLog->user?->name ?? 'ไม่ระบุผู้บันทึก' }}
            เมื่อ {{ $opLog->updated_at?->format('Y-m-d H:i') ?? '-' }}
          </p>
        @endif
      </section>

      {{-- SECTION 6 --}}
      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">6</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ทีมช่างที่รับผิดชอบ</div>
            <div class="{{ $subCls }}">ผู้ปฏิบัติงาน</div>
          </div>
        </div>

        <div class="space-y-4 text-sm">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-slate-700">รายชื่อทีมช่าง</div>
            <div class="text-xs text-slate-500">{{ $workers->count() }} คน</div>
          </div>

          <div class="rounded-md border {{ $line }} bg-white max-h-72 overflow-y-auto divide-y divide-slate-200">
            @if($workers->isEmpty())
              <div class="px-3 py-2 text-xs text-slate-500">ยังไม่ได้มอบหมายงานให้ทีมช่าง</div>
            @else
              @foreach($workers as $worker)
                @php
                  $assign  = $assignments->firstWhere('user_id', $worker->id);
                  $aStatus = $assign?->status;

                  $badgeTone = 'bg-slate-50 text-slate-700 border-slate-200';
                  $badgeText = 'ไม่ระบุ';

                  if ($aStatus === \App\Models\MaintenanceAssignment::STATUS_IN_PROGRESS) {
                    $badgeTone = 'bg-sky-50 text-sky-800 border-sky-200';
                    $badgeText = 'กำลังดำเนินการ';
                  } elseif ($aStatus === \App\Models\MaintenanceAssignment::STATUS_DONE) {
                    $badgeTone = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                    $badgeText = 'ทำเสร็จแล้ว';
                  } elseif ($aStatus === \App\Models\MaintenanceAssignment::STATUS_CANCELLED) {
                    $badgeTone = 'bg-rose-50 text-rose-800 border-rose-200';
                    $badgeText = 'ยกเลิก';
                  }

                  $isLead = (bool) ($assign?->is_lead ?? false);
                @endphp

                <div class="flex items-center justify-between gap-2 px-3 py-2">
                  <div class="flex min-w-0 items-center gap-2">
                    <div class="h-8 w-8 flex-shrink-0 overflow-hidden rounded-full border {{ $line }} bg-white">
                      <img src="{{ $worker->avatar_thumb_url }}" alt="{{ $worker->name }}" class="h-full w-full object-cover">
                    </div>
                    <div class="min-w-0">
                      <div class="truncate text-sm font-semibold text-slate-900">
                        {{ $worker->name }}
                        @if($isLead)
                          <span class="ml-1 inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">Lead</span>
                        @endif
                      </div>
                      <div class="truncate text-xs text-slate-500">{{ $worker->role_label ?? $worker->role }}</div>
                    </div>
                  </div>

                  <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-medium {{ $badgeTone }}">
                    {{ $badgeText }}
                  </span>
                </div>
              @endforeach
            @endif
          </div>
        </div>
      </section>
    </div>

  </div>

  {{-- MODAL --}}
    @can('assign', $req)
      <div id="assignModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-900/40">
        <div class="w-full max-w-xl rounded-2xl border {{ $line }} bg-white shadow-xl">
          <div class="flex items-center justify-between border-b {{ $line }} px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="2"/>
                </svg>
              </span>
              <div>
                <div class="text-sm font-semibold text-slate-900">มอบหมาย / แก้ไขทีมช่าง</div>
                <p class="text-xs sm:text-[13px] text-slate-500">เลือกผู้ปฏิบัติงานที่สามารถรับผิดชอบงานนี้</p>
              </div>
            </div>
            <button type="button" id="closeAssignModalBtn"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
              <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>

          <form method="POST" action="{{ $assignStoreUrl }}" class="px-4 py-3 space-y-3">
            @csrf

            <div class="rounded-md border {{ $line }} bg-slate-50 px-3 py-2">
              <div class="text-xs font-medium text-slate-700 mb-2">รายชื่อทีมช่าง</div>
              <div class="max-h-72 space-y-1 overflow-y-auto">
                @foreach($allWorkers as $worker)
                  <label class="flex items-center gap-2 text-xs">
                    <input type="checkbox" name="user_ids[]" value="{{ $worker->id }}"
                           @checked($workers->contains('id', $worker->id))>
                    <span>{{ $worker->name }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <div>
              <label class="text-xs font-medium text-slate-700">หัวหน้าทีม</label>
              <select name="lead_user_id" class="mt-2 w-full rounded-md border {{ $line }} bg-white px-3 py-2 text-xs">
                <option value="">— ไม่ระบุ —</option>
                @foreach($allWorkers as $worker)
                  <option value="{{ $worker->id }}" @selected((int)$req->technician_id === (int)$worker->id)>
                    {{ $worker->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="flex justify-end gap-2 pt-3">
              <button type="button" id="cancelAssignModalBtn" class="px-3 py-2 text-xs border {{ $line }} rounded-md bg-white hover:bg-slate-50">
                ยกเลิก
              </button>
              <button type="submit" class="px-3 py-2 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-200">
                บันทึกการมอบหมาย
              </button>
            </div>
          </form>
        </div>
      </div>
    @endcan
  </div>

  @if($canReject)
    <div id="rejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40">
      <div class="w-full max-w-xl rounded-2xl border {{ $line }} bg-white shadow-xl">
        <div class="flex items-center justify-between border-b {{ $line }} px-4 py-3">
          <div class="text-sm font-semibold text-slate-900">ไม่รับเรื่อง</div>

          <button type="button" id="closeRejectModalBtn"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>
        </div>

        <form method="POST" action="{{ route('maintenance.requests.reject', $req->id) }}" class="px-4 py-4 space-y-4">
          @csrf

          <div>
            <label class="block text-sm font-medium text-slate-700">เหตุผล <span class="text-rose-600">*</span></label>
            <textarea name="reject_reason" rows="4" required
                      class="mt-2 w-full rounded-md border {{ $line }} bg-white px-3 py-2 text-sm
                             focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                      placeholder="ระบุเหตุผลที่ไม่รับเรื่อง"></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button type="button" id="cancelRejectModalBtn"
                    class="px-3 py-2 text-xs border {{ $line }} rounded-md bg-white hover:bg-slate-50">
              ยกเลิก
            </button>
            <button type="submit"
                    class="px-3 py-2 text-xs bg-rose-600 text-white rounded-md hover:bg-rose-700 focus:ring-2 focus:ring-rose-200">
              ยืนยันไม่รับเรื่อง
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
  const modal  = document.getElementById('rejectModal');
  const open   = document.getElementById('openRejectModalBtn');
  const close  = document.getElementById('closeRejectModalBtn');
  const cancel = document.getElementById('cancelRejectModalBtn');

  if (!modal || !open) return;

  function show() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
  function hide() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  open.addEventListener('click', show);
  close?.addEventListener('click', hide);
  cancel?.addEventListener('click', hide);

  // ✅ คลิกพื้นหลังปิด
  modal.addEventListener('click', function (e) {
    if (e.target === modal) hide();
  });
})();
</script>
@endpush
