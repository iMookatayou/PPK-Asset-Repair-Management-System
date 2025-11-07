

<?php $__env->startSection('title', $thread->title); ?>

<?php
  $totalMessages = $messages->count();
  $lastAt = $messages->last()?->created_at ?? $thread->created_at;
?>


<?php $__env->startSection('page-header'); ?>
  
  
  <style>:root{ --app-top: 64px; }</style>

  <div class="sticky z-30 border-b border-slate-200 bg-white/90 backdrop-blur"
       style="top: var(--app-top, 0px)">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
      <div class="py-2.5 sm:py-3">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 text-[11px] text-slate-500">
              <a href="<?php echo e(route('chat.index')); ?>" class="hover:underline">ห้องแชต</a>
              <span>›</span>
              <span class="truncate" title="<?php echo e($thread->title); ?>">รายละเอียด</span>
            </div>
            <h1 class="mt-0.5 truncate text-base font-semibold text-slate-900 sm:text-lg" title="<?php echo e($thread->title); ?>">
              <?php echo e($thread->title); ?>

            </h1>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
              <span>สร้างเมื่อ <?php echo e($thread->created_at->format('Y-m-d H:i')); ?></span>
              <span class="select-none">·</span>
              <span>อัปเดตล่าสุด <?php echo e($lastAt->format('Y-m-d H:i')); ?></span>
              <span class="select-none">·</span>
              <span><?php echo e(number_format($totalMessages)); ?> ข้อความ</span>
              <?php if($thread->is_locked): ?>
                <span class="select-none">·</span>
                <span class="inline-flex items-center rounded border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-amber-700">ล็อกแล้ว</span>
              <?php else: ?>
                <span class="select-none">·</span>
                <span class="inline-flex items-center rounded border border-emerald-300 bg-emerald-50 px-1.5 py-0.5 text-emerald-700">เปิดรับข้อความ</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a href="<?php echo e(route('chat.index')); ?>"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
              <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Back
            </a>
            <button id="btnHeaderRefresh" type="button"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
               title="โหลดข้อความใหม่">
              <svg viewBox="0 0 24 24" class="h-4 w-4"><path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Refresh
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
  
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

    
    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-2">
      <div class="flex items-center gap-2 text-xs text-slate-600">
        <span class="rounded-md bg-slate-100 px-2 py-0.5">ห้องแชต</span>
        <span class="hidden sm:inline">•</span>
        <span class="hidden truncate sm:inline" title="<?php echo e($thread->title); ?>"><?php echo e($thread->title); ?></span>
      </div>
      <button id="btnScrollBottom"
              type="button"
              class="hidden rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
              title="เลื่อนไปข้อความล่าสุด">
        ไปท้ายแชต
      </button>
    </div>

    
    <div class="p-0">
      <div id="chatBox"
           class="overflow-y-auto bg-white p-4"
           style="max-height: calc(100vh - var(--app-top, 0px) - 220px); min-height: 40vh;">
        <?php if($messages->isEmpty()): ?>
          <div class="grid place-items-center py-12">
            <div class="text-center">
              <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-slate-100">
                <svg viewBox="0 0 24 24" class="h-6 w-6 text-slate-500"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V5a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v10Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
              <p class="text-sm font-medium text-slate-800">ยังไม่มีข้อความ</p>
              <p class="mt-1 text-xs text-slate-500">พิมพ์ข้อความแรกเพื่อเริ่มการสนทนา</p>
            </div>
          </div>
        <?php else: ?>
          <div class="space-y-3">
            <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="flex gap-2">
                <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-200 text-xs">
                  <?php echo e(strtoupper(mb_substr($m->user->name,0,1))); ?>

                </div>
                <div>
                  <div class="text-sm">
                    <span class="font-medium"><?php echo e($m->user->name); ?></span>
                    <span class="text-xs text-gray-500">• <?php echo e($m->created_at->format('Y-m-d H:i')); ?></span>
                  </div>
                  <div class="whitespace-pre-line text-[15px] leading-snug"><?php echo e($m->body); ?></div>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    
    <?php if(!$thread->is_locked): ?>
      <form method="POST" action="<?php echo e(route('chat.messages.store',$thread)); ?>"
            class="flex items-start gap-2 border-t border-slate-200 bg-slate-50 px-3 py-3">
        <?php echo csrf_field(); ?>
        <label for="msgInput" class="sr-only">พิมพ์ข้อความ</label>
        <input id="msgInput" name="body" required maxlength="3000" placeholder="พิมพ์ข้อความ..."
               class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 focus:border-[#0E2B51] focus:ring-[#0E2B51]" />
        <button class="rounded-lg bg-[#0E2B51] px-3 py-2 text-white hover:opacity-95">ส่ง</button>
      </form>
    <?php else: ?>
      <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-center text-slate-600">
        กระทู้นี้ถูกล็อก ไม่สามารถส่งข้อความใหม่ได้
      </div>
    <?php endif; ?>
  </div>

  
  <?php if(session('status')): ?>
    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
      <?php echo e(session('status')); ?>

    </div>
  <?php endif; ?>
  <?php if($errors->any()): ?>
    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700">
      <ul class="list-disc pl-5">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>
</div>


<script>
  const box = document.getElementById('chatBox');
  const btnScrollBottom = document.getElementById('btnScrollBottom');
  const btnHeaderRefresh = document.getElementById('btnHeaderRefresh');

  const threadId = <?php echo e($thread->id); ?>;
  let lastId = <?php echo e($messages->last()?->id ?? 0); ?>;
  let autoScroll = true;
  let polling = null;

  function appendMessage(m){
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `
      <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-200 text-xs">
        ${(m.user?.name || '?').slice(0,1).toUpperCase()}
      </div>
      <div>
        <div class="text-sm">
          <span class="font-medium">${m.user?.name || 'Unknown'}</span>
          <span class="text-xs text-gray-500">• ${new Date(m.created_at).toLocaleString()}</span>
        </div>
        <div class="text-[15px] leading-snug whitespace-pre-line"></div>
      </div>`;
    row.querySelector('.leading-snug').textContent = m.body;
    box.appendChild(row);
  }

  async function poll(){
    try{
      const res = await fetch(`<?php echo e(route('chat.messages',$thread)); ?>?after_id=${lastId}`, { headers:{ 'Accept':'application/json' }});
      if(!res.ok) return;
      const data = await res.json();
      if(Array.isArray(data) && data.length){
        data.forEach(m => { appendMessage(m); lastId = Math.max(lastId, m.id); });
        if (autoScroll) {
          box.scrollTop = box.scrollHeight;
        } else if (btnScrollBottom) {
          btnScrollBottom.classList.remove('hidden');
        }
      }
    }catch(e){}
  }

  function updateAutoScrollFlag(){
    const nearBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 30;
    autoScroll = nearBottom;
    if (nearBottom && btnScrollBottom) btnScrollBottom.classList.add('hidden');
  }
  box.addEventListener('scroll', updateAutoScrollFlag);

  if(btnScrollBottom){
    btnScrollBottom.addEventListener('click', () => {
      box.scrollTop = box.scrollHeight;
      autoScroll = true;
      btnScrollBottom.classList.add('hidden');
    });
  }
  if(btnHeaderRefresh){
    btnHeaderRefresh.addEventListener('click', () => { poll(); });
  }

  window.addEventListener('pageshow', () => {
    // ฟิตความสูงครั้งแรกให้เต็ม viewport อย่างพอดี
    box.style.maxHeight = `calc(100vh - (getComputedStyle(document.documentElement).getPropertyValue('--app-top') || '0px') - 220px)`;
    box.scrollTop = box.scrollHeight;
    polling = setInterval(poll, 2000);
  });
  window.addEventListener('pagehide', () => {
    if (polling) clearInterval(polling);
  });

  // ส่งด้วย Ctrl/Cmd + Enter
  const input = document.getElementById('msgInput');
  if (input) {
    input.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        const form = input.closest('form');
        if (form) form.submit();
      }
    });
  }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/chat/show.blade.php ENDPATH**/ ?>