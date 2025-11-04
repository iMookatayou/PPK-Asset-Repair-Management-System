

<?php $__env->startSection('title','Community Chat'); ?>

<?php $__env->startSection('content'); ?>


<style>
  .btn-hard{
    display:inline-flex;align-items:center;justify-content:center;
    height:44px;min-width:110px;padding:0 16px;border-radius:8px;
    font-weight:600;background:#059669;color:#fff; /* emerald-600 */
  }
  .btn-hard:hover{background:#047857;} /* emerald-700 */
  .btn-hard:active{transform:translateY(0.5px);}
  .btn-hard:focus{outline:2px solid #34d399;outline-offset:1px;} /* ring-emerald-400 */
</style>

<div class="max-w-5xl mx-auto py-6 space-y-5">

  
  <div class="rounded-xl border bg-base-100/80 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-base-100/60">
    <div class="px-4 md:px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="size-10 grid place-items-center rounded-lg bg-primary/10 text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 10h8M8 14h5M4 6h16M6 18h8l4 4v-4h2V4a2 2 0 0 0-2-2H6A2 2 0 0 0 4 4v12a2 2 0 0 0 2 2Z"/>
          </svg>
        </div>
        <div>
          <h1 class="text-xl font-semibold leading-tight">Community Chat</h1>
          <p class="text-sm opacity-70">Ask questions, share tips, and learn from others</p>
        </div>
      </div>

      
      <form method="GET"
            class="group flex items-stretch rounded-xl border border-slate-300 bg-white shadow-sm
                   focus-within:ring-2 focus-within:ring-[#0E2B51] w-full md:w-auto">
        <input
          type="text"
          name="q"
          value="<?php echo e(request('q','')); ?>"
          placeholder="Search threads..."
          class="flex-1 px-3 h-10 text-sm bg-transparent outline-none border-0 rounded-l-xl"
          aria-label="Search threads"
        >
        <button type="submit"
                class="px-4 h-10 text-sm font-medium text-white bg-[#0E2B51]
                       rounded-r-xl shadow-sm hover:shadow-md hover:bg-[#0c2342] active:translate-y-[0.5px]">
          Search
        </button>
        <?php if(request('q')): ?>
          <a href="<?php echo e(route('chat.index')); ?>"
             class="ml-2 px-3 h-10 grid place-items-center text-sm text-slate-600 hover:text-slate-800">
            Clear
          </a>
        <?php endif; ?>
      </form>
    </div>
    <div class="h-px bg-gradient-to-r from-transparent via-base-200 to-transparent"></div>
  </div>

  
  <div class="card bg-base-100 border">
    <div class="card-body gap-3">
      <div class="flex items-center justify-between">
        <div class="text-sm text-base-content/70">Create a new thread</div>
      </div>

      <form method="POST" action="<?php echo e(route('chat.store')); ?>" class="space-y-3">
        <?php echo csrf_field(); ?>

        <div class="flex flex-col sm:flex-row gap-3">
          <input
            name="title"
            required
            maxlength="180"
            class="w-full sm:flex-1 rounded-lg border border-slate-300 px-3 h-11 text-sm
                   focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder='Ask anything, e.g. "How to choose a reliable night-shift printer?"'
            value="<?php echo e(old('title')); ?>"
            aria-label="Thread title"
          >

          
          <button type="submit" class="btn-hard" aria-label="Post thread">Post</button>
        </div>

        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <p class="text-sm text-error"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </form>
    </div>
  </div>

  
  <div class="card bg-base-100 border">
    <div class="card-body p-0">
      <div class="px-4 py-3 border-b text-sm font-medium">Latest Threads</div>

      <div class="divide-y">
        <?php $__empty_1 = true; $__currentLoopData = $threads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $th): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <a href="<?php echo e(route('chat.show',$th)); ?>" class="block px-4 py-3 hover:bg-base-200/40">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-medium line-clamp-1"><?php echo e($th->title); ?></div>
                <div class="text-xs opacity-70">
                  by <?php echo e($th->author->name); ?> • <?php echo e($th->created_at->diffForHumans()); ?>

                </div>
              </div>
              <div class="shrink-0 text-base-content/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                </svg>
              </div>
            </div>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <div class="p-8 text-center">
            <div class="mx-auto mb-3 size-10 grid place-items-center rounded-full bg-base-200/60">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M7 8h10M7 12h7M5 4h14a2 2 0 012 2v10l-4 4H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
              </svg>
            </div>
            <div class="font-medium">No threads yet</div>
            <p class="text-sm opacity-70">Be the first to start a conversation.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="p-3">
        <div class="flex justify-center">
          <?php echo e($threads->withQueryString()->links()); ?>

        </div>
      </div>
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/chat/index.blade.php ENDPATH**/ ?>