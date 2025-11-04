<?php $__env->startSection('title', 'Asset Repair Dashboard — Compact (daisyUI)'); ?>


<?php $__env->startSection('topbadges'); ?>
  <?php
    $stats = array_replace(['total'=>0,'pending'=>0,'inProgress'=>0,'completed'=>0,'monthCost'=>0], $stats ?? []);
  ?>
  <div class="flex flex-wrap gap-2">
    <span class="badge badge-neutral">Total: <strong class="ml-1"><?php echo e(number_format($stats['total'])); ?></strong></span>
    <span class="badge badge-warning">Pending: <strong class="ml-1"><?php echo e(number_format($stats['pending'])); ?></strong></span>
    <span class="badge badge-info">In&nbsp;progress: <strong class="ml-1"><?php echo e(number_format($stats['inProgress'])); ?></strong></span>
    <span class="badge badge-success">Completed: <strong class="ml-1"><?php echo e(number_format($stats['completed'])); ?></strong></span>
  </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <?php
    // ===== Normalize incoming collections =====
    $monthlyTrend = is_iterable($monthlyTrend ?? null) ? collect($monthlyTrend) : collect();
    $byAssetType  = is_iterable($byAssetType  ?? null) ? collect($byAssetType)  : collect();
    $byDept       = is_iterable($byDept       ?? null) ? collect($byDept)       : collect();
    $recent       = is_iterable($recent       ?? null) ? collect($recent)       : collect();

    $monthlyTrend = $monthlyTrend->take(6)->values();
    $byAssetType  = $byAssetType->take(9)->values();
    $byDept       = $byDept->take(8)->values();

    $intVal   = fn($v)=> is_numeric($v) ? (int)$v : 0;
    $strVal   = fn($v,$f='')=> is_string($v) && $v!=='' ? $v : $f;

    $trendLabels = $monthlyTrend->map(fn($i)=> $strVal(is_array($i)?($i['ym']??''):($i->ym??'')))->all();
    $trendCounts = $monthlyTrend->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

    $typeLabels  = $byAssetType->map(fn($i)=> $strVal(is_array($i)?($i['type']??'Unspecified'):($i->type??'Unspecified'),'Unspecified'))->all();
    $typeCounts  = $byAssetType->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

    $deptLabels  = $byDept->map(fn($i)=> $strVal(is_array($i)?($i['dept']??'Unspecified'):($i->dept??'Unspecified'),'Unspecified'))->all();
    $deptCounts  = $byDept->map(fn($i)=> $intVal(is_array($i)?($i['cnt']??0):($i->cnt??0)) )->all();

    $get = function($row, $key, $fallback='-'){
      if (is_array($row))  return data_get($row, $key, $fallback);
      if (is_object($row)) return data_get((array)$row, $key, $fallback);
      return $fallback;
    };

    // เปิดแผง Filters อัตโนมัติถ้ามีพารามิเตอร์กรอง
    $filtersActive = (string)request('status','') !== '' || (string)request('from','') !== '' || (string)request('to','') !== '';
  ?>

  <div class="py-4">
    <div class="max-w-7xl mx-auto px-3 lg:px-6 space-y-4">

      
      <div class="flex items-center justify-between">

        <div class="flex items-center gap-2">
          <?php if($filtersActive): ?>
            <span class="badge badge-primary badge-sm" title="Filters active">Filters: ON</span>
          <?php endif; ?>
          <button id="filterToggle"
                  class="btn btn-sm btn-outline"
                  type="button"
                  aria-expanded="<?php echo e($filtersActive ? 'true' : 'false'); ?>"
                  aria-controls="filtersPanel">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4h18M6 8h12M9 12h6M11 16h2"/>
            </svg>
            Filters
          </button>
        </div>
      </div>

      
      <div id="filtersPanel" class="<?php echo e($filtersActive ? '' : 'hidden'); ?>">
        <form method="GET" class="card bg-base-100 border border-base-200">
          <div class="card-body gap-4">
            <div class="flex items-center justify-between">
              <h2 class="card-title text-base">Filters</h2>
              <div class="text-xs text-base-content/60">Use filters to narrow results</div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
              <div class="form-control">
                <label for="f_status" class="label py-1">
                  <span class="label-text text-xs">Status</span>
                </label>
                <select id="f_status" name="status" class="select select-bordered select-sm">
                  <option value="">All</option>
                  <option value="pending"     <?php echo e(request('status')==='pending'?'selected':''); ?>>Pending</option>
                  <option value="in_progress" <?php echo e(request('status')==='in_progress'?'selected':''); ?>>In progress</option>
                  <option value="completed"   <?php echo e(request('status')==='completed'?'selected':''); ?>>Completed</option>
                </select>
              </div>
              <div class="form-control">
                <label for="f_from" class="label py-1"><span class="label-text text-xs">From date</span></label>
                <input id="f_from" type="date" name="from" value="<?php echo e(e(request('from',''))); ?>" class="input input-bordered input-sm" />
              </div>
              <div class="form-control">
                <label for="f_to" class="label py-1"><span class="label-text text-xs">To date</span></label>
                <input id="f_to" type="date" name="to" value="<?php echo e(e(request('to',''))); ?>" class="input input-bordered input-sm" />
              </div>
              <div class="md:col-span-2 flex items-end gap-2">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="<?php echo e(route('repair.dashboard')); ?>" class="btn btn-outline btn-sm">Reset</a>
              </div>
            </div>
          </div>
        </form>
      </div>

      
      <div class="stats stats-vertical md:stats-horizontal shadow bg-base-100 border border-base-200 w-full">
        <div class="stat">
          <div class="stat-title">Total</div>
          <div class="stat-value text-base-content"><?php echo e(number_format($stats['total'])); ?></div>
        </div>
        <div class="stat">
          <div class="stat-title text-warning">Pending</div>
          <div class="stat-value text-warning"><?php echo e(number_format($stats['pending'])); ?></div>
        </div>
        <div class="stat">
          <div class="stat-title text-info">In progress</div>
          <div class="stat-value text-info"><?php echo e(number_format($stats['inProgress'])); ?></div>
        </div>
        <div class="stat">
          <div class="stat-title text-success">Completed</div>
          <div class="stat-value text-success"><?php echo e(number_format($stats['completed'])); ?></div>
        </div>
        <div class="stat">
          <div class="stat-title">Monthly cost</div>
          <div class="stat-value"><?php echo e(number_format($stats['monthCost'], 2)); ?></div>
        </div>
      </div>

      
      <div class="card bg-base-100 border border-base-200">
        <div class="card-body">
          <h2 class="card-title text-base">By department (Top 8)</h2>
          <?php if(count($deptLabels) && count($deptCounts)): ?>
            <div class="h-56 lg:h-64">
              <canvas id="deptBar"
                      data-labels='<?php echo json_encode($deptLabels, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'
                      data-values='<?php echo json_encode($deptCounts, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'></canvas>
            </div>
          <?php else: ?>
            <div class="hero min-h-48 bg-base-200 rounded-box">
              <div class="hero-content text-center">
                <p class="text-base-content/60">No data</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        
        <div class="card bg-base-100 border border-base-200">
          <div class="card-body">
            <h2 class="card-title text-base">Monthly trend (6 months)</h2>
            <?php if(count($trendLabels) && count($trendCounts)): ?>
              <div class="h-56 lg:h-64">
                <canvas id="trendChart"
                        data-labels='<?php echo json_encode($trendLabels, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'
                        data-values='<?php echo json_encode($trendCounts, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'></canvas>
              </div>
            <?php else: ?>
              <div class="hero min-h-48 bg-base-200 rounded-box">
                <div class="hero-content text-center">
                  <p class="text-base-content/60">No data</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        
        <div class="card bg-base-100 border border-base-200">
          <div class="card-body">
            <h2 class="card-title text-base">Asset types (Top 8 + others)</h2>
            <?php if(count($typeLabels) && count($typeCounts)): ?>
              <div class="h-56 lg:h-64">
                <canvas id="typePie"
                        data-labels='<?php echo json_encode($typeLabels, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'
                        data-values='<?php echo json_encode($typeCounts, JSON_INVALID_UTF8_SUBSTITUTE, 512) ?>'></canvas>
              </div>
            <?php else: ?>
              <div class="hero min-h-48 bg-base-200 rounded-box">
                <div class="hero-content text-center">
                  <p class="text-base-content/60">No data</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      
      <div class="card bg-base-100 border border-base-200 overflow-hidden">
        <div class="card-body pb-0">
          <div class="flex items-center gap-2">
            <h2 class="card-title text-base">Recent jobs</h2>
            <span class="text-xs text-base-content/60">up to 12 items</span>
          </div>
        </div>
        <div class="overflow-x-auto p-4 pt-2">
          <table class="table table-zebra">
            <thead>
              <tr class="text-xs">
                <th class="uppercase">Reported at</th>
                <th class="uppercase">Asset</th>
                <th class="uppercase">Reporter</th>
                <th class="uppercase">Status</th>
                <th class="uppercase">Assignee</th>
                <th class="uppercase">Completed at</th>
              </tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                  $status = (string) $get($t,'status','');
                  $badgeClass =
                    $status === \App\Models\MaintenanceRequest::STATUS_PENDING     ? 'badge-warning' :
                    ($status === \App\Models\MaintenanceRequest::STATUS_IN_PROGRESS ? 'badge-info' :
                    ($status === \App\Models\MaintenanceRequest::STATUS_COMPLETED   ? 'badge-success' : 'badge-ghost'));
                  $assetId   = $get($t,'asset_id','-');
                  $assetName = $get($t,'asset_name') ?: $get($t,'asset.name','-');
                  $reporter  = $get($t,'reporter')   ?: $get($t,'reporter.name','-');
                  $tech      = $get($t,'technician')  ?: $get($t,'technician.name','-');
                  $reqAt     = $get($t,'request_date','-');
                  $doneAt    = $get($t,'completed_at') ?: $get($t,'completed_date','-');
                ?>
                <tr class="hover">
                  <td><?php echo e(is_string($reqAt) ? $reqAt : optional($reqAt)->format('Y-m-d H:i')); ?></td>
                  <td>#<?php echo e(e((string)$assetId)); ?> — <?php echo e(e((string)$assetName)); ?></td>
                  <td><?php echo e(e((string)$reporter)); ?></td>
                  <td>
                    <span class="badge badge-sm <?php echo e($badgeClass); ?>">
                      <?php echo e(ucfirst(str_replace('_',' ', $status))); ?>

                    </span>
                  </td>
                  <td><?php echo e(e((string)$tech)); ?></td>
                  <td><?php echo e(is_string($doneAt) ? $doneAt : (optional($doneAt)->format('Y-m-d H:i') ?? '-')); ?></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                  <td colspan="6" class="text-center text-base-content/60 py-10">No recent data to display</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  
  <script>
    // Filter toggle
    (function(){
      const btn = document.getElementById('filterToggle');
      const panel = document.getElementById('filtersPanel');
      if(!btn || !panel) return;
      btn.addEventListener('click', () => {
        const isHidden = panel.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', String(!isHidden));
      });
    }());

    // Load Chart.js (with simple fallback) then render all charts
    function loadScript(src){ return new Promise((res, rej)=>{ const s=document.createElement('script'); s.src=src; s.async=true; s.onload=res; s.onerror=()=>rej(); document.head.appendChild(s); }); }
    async function ensureChart(){
      if (window.Chart) return;
      try { await loadScript('https://cdn.jsdelivr.net/npm/chart.js'); }
      catch { await loadScript('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js'); }
    }

    function makeChart(el, type){
      try{
        const labels = JSON.parse(el.dataset.labels || '[]');
        const values = JSON.parse(el.dataset.values || '[]');
        if (!labels.length || !values.length) return;

        // สีตาม theme ให้ดูเรียบแบบเดิม
        const textColor = getComputedStyle(document.documentElement).getPropertyValue('--bc').trim() || '#1f2937';
        const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--b2').trim() || '#e5e7eb';

        const axisStyle = { ticks: { color: textColor }, grid: { color: gridColor } };

        const cfg =
          (type === 'pie') ? {
            type:'pie',
            data:{ labels, datasets:[{ data: values }] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ boxWidth:10, color:textColor } } } }
          } :
          (type === 'bar') ? {
            type:'bar',
            data:{ labels, datasets:[{ data: values }] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y: axisStyle, x: { ...axisStyle, grid:{ display:false } } } }
          } : {
            type:'line',
            data:{ labels, datasets:[{ data: values, tension:.35, pointRadius:2, borderWidth:2 }] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y: axisStyle, x: axisStyle } }
          };

        new Chart(el, cfg);
      }catch(e){ console.warn('[ChartJS] render error', e); }
    }

    function render(id, type){
      const el = document.getElementById(id);
      if (el) makeChart(el, type);
    }

    document.addEventListener('DOMContentLoaded', async ()=>{
      await ensureChart();
      // Only ONE bar chart (deptBar) on top
      render('deptBar', 'bar');
      // Others: line + pie
      render('trendChart', 'line');
      render('typePie', 'pie');
    });
  </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
  <div class="text-xs opacity-60">
    © <?php echo e(date('Y')); ?> <?php echo e(config('app.name','Asset Repair')); ?> — Dashboard
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Developer\development\Asset-Repair-Management-System\resources\views/repair/dashboard.blade.php ENDPATH**/ ?>