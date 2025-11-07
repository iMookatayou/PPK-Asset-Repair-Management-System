<?php $__env->startSection('title', 'รายละเอียดงานซ่อม #'.$req->id); ?>

<?php $__env->startSection('page-header'); ?>
  <?php
    $status = strtolower((string) $req->status);
    $statusLabel = [
      'pending'     => 'รอคิว',
      'accepted'    => 'รับงานแล้ว',
      'in_progress' => 'ระหว่างดำเนินการ',
      'on_hold'     => 'พักไว้',
      'resolved'    => 'แก้ไขแล้ว',
      'closed'      => 'ปิดงาน',
      'cancelled'   => 'ยกเลิก',
    ][$status] ?? $status;

    $statusTone = match ($status) {
      'pending'     => 'bg-sky-50 text-sky-700 border-sky-200',
      'accepted'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
      'in_progress' => 'bg-sky-50 text-sky-700 border-sky-200',
      'on_hold'     => 'bg-amber-50 text-amber-700 border-amber-200',
      'resolved'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
      'closed'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
      'cancelled'   => 'bg-rose-50 text-rose-700 border-rose-200',
      default       => 'bg-slate-50 text-slate-700 border-slate-200',
    };

    $prio = strtolower((string) $req->priority);
    $prioLabel = [
      'low'    => 'ต่ำ',
      'normal' => 'ปกติ',
      'high'   => 'สูง',
      'urgent' => 'เร่งด่วน',
    ][$prio] ?? ($req->priority ?? '—');

    $prioTone = match ($prio) {
      'low'    => 'bg-white text-zinc-700 border-zinc-300',
      'normal' => 'bg-white text-sky-800 border-sky-300',
      'high'   => 'bg-white text-amber-800 border-amber-300',
      'urgent' => 'bg-white text-rose-800 border-rose-300',
      default  => 'bg-white text-zinc-700 border-zinc-300',
    };
  ?>

  <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex flex-col gap-2">
          <h1 class="text-2xl font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 2l-4.2 4.2a4 4 0 01-5.6 5.6L7 16l-3 1 1-3 4.2-4.2a4 4 0 015.6-5.6L21 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            รายละเอียดงานซ่อม <span id="rid">#<?php echo e($req->id); ?></span>
          </h1>

          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium bg-slate-50 text-slate-700 border-slate-200">
              <?php echo e($req->asset->name ?? $req->asset_id ?? '—'); ?>

            </span>
            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium <?php echo e($statusTone); ?>">
              <?php echo e($statusLabel); ?>

            </span>
            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium <?php echo e($prioTone); ?>">
              <?php echo e($prioLabel); ?>

            </span>
          </div>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-2">
          <button id="copyIdBtn" type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50">
            คัดลอกเลขที่
          </button>
          <button type="button" onclick="window.print()"
                  class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50">
            พิมพ์
          </button>
          <a href="<?php echo e(route('maintenance.requests.index')); ?>"
             class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            กลับ
          </a>
        </div>
      </div>
      <p class="mt-1 text-sm text-slate-600">
        ดูรายละเอียดคำขอซ่อม ติดตามสถานะ และจัดการการดำเนินการที่เกี่ยวข้อง
      </p>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <?php $requestedAt = optional($req->request_date ?? $req->created_at); ?>

  <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

    
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4 text-sm">
          <div>
            <div class="text-slate-500">หมายเลข</div>
            <div class="font-semibold text-slate-900">#<?php echo e($req->id); ?></div>
          </div>
          <div>
            <div class="text-slate-500">ผู้แจ้ง</div>
            <div class="font-semibold text-slate-900"><?php echo e($req->reporter->name ?? $req->reporter_name ?? '-'); ?></div>
          </div>
          <div>
            <div class="text-slate-500">ทรัพย์สิน</div>
            <div class="font-semibold text-slate-900"><?php echo e($req->asset->name ?? $req->asset_id ?? '—'); ?></div>
          </div>
          <div>
            <div class="text-slate-500">สถานที่</div>
            <div class="font-semibold text-slate-900"><?php echo e($req->location ?? '—'); ?></div>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div class="lg:col-span-2">
            <div class="mb-1 text-slate-500">หัวข้อ</div>
            <div class="text-base font-semibold text-slate-900"><?php echo e($req->title); ?></div>

            <div class="my-4 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

            <div class="mb-1 text-slate-500">รายละเอียด</div>
            <div class="prose max-w-none text-slate-800"><?php echo e($req->description ?: '—'); ?></div>
          </div>

          <div>
            <div class="mb-2 text-slate-500">เวลา</div>
            <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="inline-flex items-center gap-2 rounded-md border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-800">
                รับคำขอ:
                <?php if($requestedAt): ?>
                  <time datetime="<?php echo e($requestedAt->toIso8601String()); ?>"><?php echo e($requestedAt->format('Y-m-d H:i')); ?></time>
                <?php else: ?> - <?php endif; ?>
              </div>
              <?php if($req->assigned_date): ?>
                <div class="inline-flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
                  มอบหมาย:
                  <time datetime="<?php echo e($req->assigned_date->toIso8601String()); ?>"><?php echo e($req->assigned_date->format('Y-m-d H:i')); ?></time>
                </div>
              <?php endif; ?>
              <?php if($req->completed_date): ?>
                <div class="inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">
                  เสร็จสิ้น:
                  <time datetime="<?php echo e($req->completed_date->toIso8601String()); ?>"><?php echo e($req->completed_date->format('Y-m-d H:i')); ?></time>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="p-6">
        <h3 class="text-base font-semibold text-slate-900">ดำเนินการ</h3>

        <form method="post"
              action="<?php echo e(route('maintenance.requests.transition', $req)); ?>"
              class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
          <?php echo csrf_field(); ?>

          <div>
            <label for="status" class="mb-1 block text-sm text-slate-700">เปลี่ยนสถานะ</label>
            <select id="status" name="status" required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-emerald-600">
              <?php $__currentLoopData = [
                'pending'     => 'รอคิว',
                'accepted'    => 'รับงานแล้ว',
                'in_progress' => 'ระหว่างดำเนินการ',
                'on_hold'     => 'พักไว้',
                'resolved'    => 'แก้ไขแล้ว',
                'closed'      => 'ปิดงาน',
                'cancelled'   => 'ยกเลิก',
              ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($k); ?>" <?php if(old('status', $req->status) === $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>

          <div id="techWrap">
            <label for="technician_id" class="mb-1 block text-sm text-slate-700">รหัสช่างผู้รับผิดชอบ (ถ้ามี)</label>
            <input id="technician_id" type="number" inputmode="numeric" name="technician_id"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:ring-emerald-600"
                   value="<?php echo e(old('technician_id', $req->technician_id)); ?>" placeholder="เช่น 5">
          </div>

          <div class="md:col-span-2">
            <label for="note" class="mb-1 block text-sm text-slate-700">บันทึกเพิ่มเติม</label>
            <input id="note" type="text" name="note"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:ring-emerald-600"
                   placeholder="(ไม่บังคับ)" value="<?php echo e(old('note')); ?>">
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-2">
            <button type="submit"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
              บันทึกการดำเนินการ
            </button>
            <a href="<?php echo e(route('maintenance.requests.index')); ?>"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
              กลับรายการ
            </a>
          </div>
        </form>
      </div>
    </section>

    
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="p-6">
        <h3 class="text-base font-semibold text-slate-900">ไฟล์แนบ</h3>

        <form method="post" enctype="multipart/form-data"
              action="<?php echo e(route('maintenance.requests.attachments', $req)); ?>"
              class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3" novalidate>
          <?php echo csrf_field(); ?>
          <div>
            <label for="caption" class="mb-1 block text-sm text-slate-700">คำอธิบาย/ชื่อไฟล์ (optional)</label>
            <input id="caption" type="text" name="caption"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                   value="<?php echo e(old('caption')); ?>" placeholder="เช่น รูปก่อนซ่อม / ใบเสนอราคา">
          </div>
          <div>
            <label for="file" class="mb-1 block text-sm text-slate-700">ไฟล์</label>
            <input id="file" type="file" name="file" required
                   accept="image/*,application/pdf"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">รองรับรูปภาพ และ PDF • สูงสุดไฟล์ละ 10MB</p>
          </div>
          <div>
            <label for="alt_text" class="mb-1 block text-sm text-slate-700">Alt text (optional)</label>
            <input id="alt_text" type="text" name="alt_text"
                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                   value="<?php echo e(old('alt_text')); ?>" placeholder="ข้อความอธิบายรูปเพื่อการเข้าถึง">
            <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="is_private" value="1" class="rounded border-slate-300">
              เก็บเป็นไฟล์ส่วนตัว (ไม่แสดงทางเว็บสาธารณะ)
            </label>
          </div>
          <div class="md:col-span-3">
            <button type="submit"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
              อัปโหลดไฟล์
            </button>
          </div>
        </form>

        <?php if(($req->attachments ?? collect())->count()): ?>
          <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
            <?php $__currentLoopData = $req->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $name = $att->original_name ?? basename($att->path ?? '');
                $isPrivate = (bool) ($att->is_private ?? false);
                $canOpen = !$isPrivate && ($att->disk ?? 'public') === 'public' && !empty($att->path);
                $url = $canOpen ? asset('storage/'.$att->path) : '#';
                $isImg = str_starts_with((string) $att->mime, 'image/');
              ?>
              <figure class="overflow-hidden rounded-lg border border-slate-200">
                <?php if($isImg && $canOpen): ?>
                  <a href="<?php echo e($url); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo e($url); ?>" alt="<?php echo e($att->alt_text ?? $name); ?>" class="h-36 w-full object-cover">
                  </a>
                <?php else: ?>
                  <div class="grid h-36 w-full place-items-center text-slate-500">
                    <?php echo e(strtoupper(pathinfo($name, PATHINFO_EXTENSION) ?: 'FILE')); ?>

                  </div>
                <?php endif; ?>
                <figcaption class="flex items-center justify-between gap-2 px-3 py-2 text-xs">
                  <span class="inline-flex items-center rounded-full border <?php echo e($isPrivate ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-700'); ?> px-2 py-0.5 font-medium">
                    <?php echo e($isPrivate ? 'private' : 'public'); ?>

                  </span>
                  <span class="truncate text-slate-600" title="<?php echo e($name); ?>"><?php echo e($name); ?></span>
                  <?php if($canOpen): ?>
                    <a href="<?php echo e($url); ?>" target="_blank" rel="noopener"
                       class="inline-flex items-center rounded-md border border-sky-300 bg-sky-50 px-2 py-1 font-medium text-sky-800 hover:bg-sky-100">
                      เปิด
                    </a>
                  <?php endif; ?>
                </figcaption>
              </figure>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php else: ?>
          <p class="mt-3 text-slate-500">ไม่มีไฟล์แนบ</p>
        <?php endif; ?>
      </div>
    </section>

    
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="p-6">
        <h3 class="text-base font-semibold text-slate-900">ประวัติการดำเนินการ</h3>

        <div class="mt-4 space-y-3">
          <?php $__empty_1 = true; $__currentLoopData = ($req->logs ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $tone = 'bg-slate-400';
              if (($log->to ?? null) === 'resolved' || ($log->to ?? null) === 'closed') $tone = 'bg-emerald-600';
              if (($log->to ?? null) === 'cancelled') $tone = 'bg-rose-600';
              if (($log->to ?? null) === 'in_progress' || ($log->to ?? null) === 'accepted' || ($log->to ?? null) === 'on_hold') $tone = 'bg-amber-600';
            ?>
            <article class="relative border-l-2 border-slate-200 pl-6">
              <span class="absolute -left-1.5 top-2 inline-block h-3 w-3 rounded-full <?php echo e($tone); ?>"></span>
              <header class="flex flex-wrap items-center gap-2 text-sm">
                <strong>#<?php echo e($log->id); ?></strong>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                  <?php echo e(($log->from ?? '—')); ?> → <?php echo e(($log->to ?? '—')); ?>

                </span>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                  <time datetime="<?php echo e($log->created_at->toIso8601String()); ?>"><?php echo e($log->created_at->format('Y-m-d H:i')); ?></time>
                </span>
                <?php if($log->user?->name ?? $log->user_id): ?>
                  <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-700">
                    โดย <?php echo e($log->user->name ?? ('#'.$log->user_id)); ?>

                  </span>
                <?php endif; ?>
              </header>
              <?php if($log->note): ?>
                <p class="mt-1 text-slate-700"><?php echo e($log->note); ?></p>
              <?php endif; ?>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="text-slate-500">ยังไม่มีบันทึก</p>
          <?php endif; ?>
        </div>
      </div>
    </section>

  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  // copy id
  (function(){
    const btn = document.getElementById('copyIdBtn');
    btn?.addEventListener('click', async () => {
      const idText = (document.getElementById('rid')?.textContent || '<?php echo e($req->id); ?>').replace('#','');
      try{
        await navigator.clipboard.writeText(idText);
        const old = btn.textContent;
        btn.classList.add('bg-slate-900','text-white','border-slate-900');
        btn.textContent = 'คัดลอกแล้ว';
        setTimeout(()=> {
          btn.classList.remove('bg-slate-900','text-white','border-slate-900');
          btn.textContent = old;
        }, 1200);
      }catch(e){}
    });
  })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/maintenance/requests/show.blade.php ENDPATH**/ ?>