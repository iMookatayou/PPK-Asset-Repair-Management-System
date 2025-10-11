{{-- resources/views/repair/dashboard.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
      {{ __('Asset Repair Dashboard') }}
    </h2>
  </x-slot>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

      {{-- Summary cards --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <x-stat-card title="งานทั้งหมด" :value="$stats['total']" />
        <x-stat-card title="รอดำเนินการ" :value="$stats['pending']" />
        <x-stat-card title="กำลังซ่อม" :value="$stats['inProgress']" />
        <x-stat-card title="เสร็จแล้ว" :value="$stats['completed']" />
        <x-stat-card title="ค่าใช้จ่ายเดือนนี้" :value="number_format($stats['monthCost'] ?? 0, 2)" hint="THB" />
      </div>

      {{-- Charts --}}
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
          <h3 class="text-lg font-semibold mb-3">แนวโน้มงานซ่อม (รายเดือน)</h3>
          <canvas id="trendChart" height="140"></canvas>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
          <h3 class="text-lg font-semibold mb-3">สัดส่วนตามประเภททรัพย์สิน</h3>
          <canvas id="typePie" height="140"></canvas>
        </div>
      </div>

      <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5">
        <h3 class="text-lg font-semibold mb-3">งานตามแผนก</h3>
        <canvas id="deptBar" height="140"></canvas>
      </div>

      {{-- Recent table --}}
      <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="bg-white dark:bg-zinc-900 p-5">
          <h3 class="text-lg font-semibold">งานล่าสุด</h3>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-zinc-900">
          <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
            <thead class="bg-zinc-50 dark:bg-zinc-950/50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">วันที่แจ้ง</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">ทรัพย์สิน</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">ผู้แจ้ง</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">สถานะ</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">ผู้รับผิดชอบ</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 uppercase">กำหนดเสร็จ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
              @forelse($recent as $t)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                  <td class="px-4 py-2 text-sm">{{ $t->request_date?->format('Y-m-d H:i') }}</td>
                  <td class="px-4 py-2 text-sm">{{ $t->asset?->code }} — {{ $t->asset?->name }}</td>
                  <td class="px-4 py-2 text-sm">{{ $t->reporter?->name ?? '-' }}</td>
                  <td class="px-4 py-2 text-sm">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs
                      @class([
                        'bg-yellow-100 text-yellow-800' => $t->status === \App\Models\MaintenanceRequest::STATUS_PENDING,
                        'bg-blue-100 text-blue-800'     => $t->status === \App\Models\MaintenanceRequest::STATUS_IN_PROGRESS,
                        'bg-green-100 text-green-800'   => $t->status === \App\Models\MaintenanceRequest::STATUS_COMPLETED,
                        'bg-gray-100 text-gray-800'     => $t->status === \App\Models\MaintenanceRequest::STATUS_CANCELLED,
                      ])
                    ">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span>
                  </td>
                  <td class="px-4 py-2 text-sm">{{ $t->technician?->name ?? '-' }}</td>
                  <td class="px-4 py-2 text-sm">{{ optional($t->completed_date)->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
              @empty
                <tr><td class="px-4 py-6 text-center text-sm text-zinc-500" colspan="6">ไม่มีข้อมูล</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const monthlyTrend = @json($monthlyTrend);
      const byAssetType  = @json($byAssetType);
      const byDept       = @json($byDept);

      new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
          labels: monthlyTrend.map(i => i.ym),
          datasets: [{ label: 'จำนวนงาน', data: monthlyTrend.map(i => i.cnt), tension: .3 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      new Chart(document.getElementById('typePie'), {
        type: 'pie',
        data: {
          labels: byAssetType.map(i => i.type ?? 'ไม่ระบุ'),
          datasets: [{ data: byAssetType.map(i => i.cnt) }]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });

      new Chart(document.getElementById('deptBar'), {
        type: 'bar',
        data: {
          labels: byDept.map(i => i.dept ?? 'ไม่ระบุ'),
          datasets: [{ label: 'งานซ่อม', data: byDept.map(i => i.cnt) }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales:{ y:{ beginAtZero:true } } }
      });
    </script>
  @endpush
</x-app-layout>
