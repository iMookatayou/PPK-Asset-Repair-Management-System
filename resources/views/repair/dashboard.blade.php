{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Repair Dashboard')

@push('styles')
  @vite(['resources/css/repair/dashboard.css'])
@endpush

@section('content')
@php
  $toast = session('toast');
  if ($toast) { session()->forget('toast'); }

  $type     = $toast['type']     ?? null;
  $message  = $toast['message']  ?? null;
  $timeout  = (int)($toast['timeout'] ?? 3200);

  $firstError = ($errors ?? null)?->first();
  if (!$message && $firstError) { $message = $firstError; $type = $type ?: 'error'; }
  if (!$message && session('error')) { $message = session('error'); $type = $type ?: 'error'; }
  if (!$message && session('status')) { $message = session('status'); $type = $type ?: 'success'; }

  $monthlyTrend = collect($monthlyTrend ?? []);
  $byAssetType  = collect($byAssetType ?? []);
  $byDept       = collect($byDept ?? []);
  $recent       = collect($recent ?? []);

  $kpi = array_merge([
    'lastMonth' => 0,
    'thisMonth' => 0,
    'thisMonthCompleted' => 0,
    'avgResolveHours' => null
  ], $kpi ?? []);

  $stats = $stats ?? [];

  $intVal = fn($v) => is_numeric($v) ? (int)$v : 0;
  $strVal = fn($v, $def='') => is_string($v) && $v!=='' ? $v : $def;

  $trendLabels = $monthlyTrend->map(fn($i) => $strVal($i['ym'] ?? $i->ym ?? ''))->values();
  $trendCounts = $monthlyTrend->map(fn($i) => $intVal($i['cnt'] ?? $i->cnt ?? 0))->values();

  $typeLabels  = $byAssetType->map(fn($i) => $strVal($i['type'] ?? $i->type ?? 'Unspecified', 'Unspecified'))->values();
  $typeCounts  = $byAssetType->map(fn($i) => $intVal($i['cnt'] ?? $i->cnt ?? 0))->values();

  $deptLabels  = $byDept->map(fn($i) => $strVal($i['dept'] ?? $i->dept ?? 'Unspecified', 'Unspecified'))->values();
  $deptCounts  = $byDept->map(fn($i) => $intVal($i['cnt'] ?? $i->cnt ?? 0))->values();

  $totalType    = $typeCounts->sum();
  $topTypeLabel = $typeLabels[0] ?? '-';
  $topTypePct   = $totalType > 0 ? round(($typeCounts[0] ?? 0) / $totalType * 100, 1) : 0;

  $otherStatus = max(0,
    ($stats['total'] ?? 0) - (($stats['pending'] ?? 0) + ($stats['inProgress'] ?? 0) + ($stats['completed'] ?? 0))
  );

  $filtersActive = request()->hasAny(['status', 'from', 'to']);

  $statusTH = [
    'pending'=>'รอดำเนินการ', 'acknowledged'=>'รับทราบแล้ว', 'accepted'=>'รับเรื่องiinG',
    'in_progress'=>'กำลังดำเนินการ', 'on_hold'=>'พักงาน', 'resolved'=>'เสร็จสิ้น',
    'closed'=>'ปิดงาน', 'cancelled'=>'ยกเลิก'
  ];

  $statusPill = fn($s) => match($s) {
    'pending' => 'pill-sky',
    'in_progress' => 'pill-blue',
    'resolved','closed' => 'pill-navy',
    'cancelled' => 'pill-muted',
    default => 'pill-muted'
  };
@endphp

<div class="dash-page">

  {{-- HERO --}}
  <header class="dash-hero">
    <img
      class="hero-map"
      src="{{ asset('images/dashboard/world-map.svg') }}"
      alt=""
      aria-hidden="true"
    />
    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="dash-hero-inner">
      <div class="hero-left">
        <h1 class="hero-title">Repair Dashboard</h1>
        <p class="hero-desc">
          Dashboard subheading or one-liner.<br>
          Updated: {{ now()->format('d F Y') }}
        </p>
      </div>

      <div class="hero-right">
        <div class="hero-top-metric">
          <div class="hero-top-label">Top-level Metric</div>
          <div class="hero-top-value">{{ number_format((int)($stats['total'] ?? 0)) }}</div>
        </div>

        <div class="hero-mini-line" aria-hidden="true">
          <svg viewBox="0 0 220 70" preserveAspectRatio="none" class="hero-mini-svg">
            <path d="M0,50 C22,56 40,20 65,26 C90,32 98,56 120,46 C142,36 150,10 175,18 C198,26 205,44 220,22"/>
          </svg>
          <div class="hero-mini-caption">Requests trend (30 days)</div>
        </div>
      </div>
    </div>
  </header>

  {{-- CONTENT --}}
  <main class="dash-wrap">

    {{-- FILTERS --}}
    <section id="filtersPanel" class="dash-filter {{ $filtersActive ? '' : 'hidden' }}">
      <form method="GET" class="dash-filter-grid">
        <div>
          <label class="dash-label">Status</label>
          <select name="status" class="dash-input">
            <option value="">All</option>
            <option value="pending"     {{ request('status')==='pending'?'selected':'' }}>Pending</option>
            <option value="in_progress" {{ request('status')==='in_progress'?'selected':'' }}>In Progress</option>
            <option value="completed"   {{ request('status')==='completed'?'selected':'' }}>Completed</option>
          </select>
        </div>

        <div>
          <label class="dash-label">From</label>
          <input type="date" name="from" value="{{ request('from') }}" class="dash-input"/>
        </div>

        <div>
          <label class="dash-label">To</label>
          <input type="date" name="to" value="{{ request('to') }}" class="dash-input"/>
        </div>

        <div class="dash-filter-actions">
          <button class="dash-btn primary">Apply</button>
          <a href="{{ url()->current() }}" class="dash-btn ghost">Clear</a>
        </div>
      </form>
    </section>

    {{-- OVERVIEW --}}
    <section class="dash-section">
      <div class="dash-section-head">
        <div class="dash-section-title">Overview Statistics</div>
      </div>

      <div class="dash-grid-2">

        {{-- LEFT: DONUT GROUP --}}
        <section class="card" aria-label="Donut group">
          <div class="card-head">
            <div>
              <div class="card-title">Distribution</div>
              <div class="card-sub">Asset Type & Status (รวมเป็นกลุ่มเดียว)</div>
            </div>
            <div class="card-corner">
              <span class="corner-icon">&nearr;</span>
            </div>
          </div>

          <div class="donut-grid">

            {{-- Asset Type donut --}}
            <div class="donut-wrap">
              <div class="donut-title">Asset Type</div>

              <div class="chart-box h220 donut-box">
                <canvas id="typeDonut"
                  data-labels='@json($typeLabels)'
                  data-values='@json($typeCounts)'></canvas>
              </div>

              <div class="donut-center">
                <div class="donut-big">{{ $topTypePct }}%</div>
                <div class="donut-sub">{{ $topTypeLabel }}</div>
              </div>
            </div>

            {{-- Status donut --}}
            <div class="donut-wrap">
              <div class="donut-title">Status Mix</div>

              <div class="chart-box h220 donut-box">
                <canvas id="statusDonut"
                  data-pending="{{ (int)($stats['pending'] ?? 0) }}"
                  data-progress="{{ (int)($stats['inProgress'] ?? 0) }}"
                  data-completed="{{ (int)($stats['completed'] ?? 0) }}"
                  data-other="{{ (int)$otherStatus }}"></canvas>
              </div>

              <div class="status-metrics-plain">
                <div class="plain-metric">
                  <div class="plain-label">Avg Time</div>
                  <div class="plain-value">
                    {{ $kpi['avgResolveHours'] ? $kpi['avgResolveHours'].' h' : '-' }}
                  </div>
                </div>

                <div class="plain-metric">
                  <div class="plain-label">Cancelled</div>
                  <div class="plain-value is-blue">
                    {{ number_format((int)($stats['cancelled'] ?? 0)) }}
                  </div>
                </div>
              </div>
            </div>

          </div>
        </section>

        {{-- RIGHT: MONTHLY TREND --}}
        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">Monthly Trend</div>
              <div class="card-sub">ปริมาณงานรายเดือน</div>
            </div>
            <div class="card-corner">
              <span class="corner-icon">&nearr;</span>
            </div>
          </div>

          <div class="mini-kpi">
            <div class="mini-item">
              <div class="mini-label">Last Month</div>
              <div class="mini-value">{{ number_format((int)$kpi['lastMonth']) }}</div>
            </div>
            <div class="mini-item">
              <div class="mini-label">This Month</div>
              <div class="mini-value is-blue">{{ number_format((int)$kpi['thisMonth']) }}</div>
            </div>
            <div class="mini-item">
              <div class="mini-label">Completed</div>
              <div class="mini-value is-navy">{{ number_format((int)$kpi['thisMonthCompleted']) }}</div>
            </div>
          </div>

          <div class="chart-box h220">
            <canvas id="trendBar"
              data-labels='@json($trendLabels)'
              data-values='@json($trendCounts)'></canvas>
          </div>
        </section>
      </div>

      {{-- Department --}}
      <div class="dash-grid-2 bottom">
        <section class="card" style="grid-column: 1 / -1;">
          <div class="card-head">
            <div>
              <div class="card-title">Department Volume</div>
              <div class="card-sub">แยกตามแผนก</div>
            </div>
            <div class="card-corner">
              <span class="corner-icon">&nearr;</span>
            </div>
          </div>

          <div class="chart-box h220">
            <canvas id="deptBar"
              data-labels='@json($deptLabels)'
              data-values='@json($deptCounts)'></canvas>
          </div>
        </section>
      </div>
    </section>

    {{-- RECENT --}}
    <section class="dash-section">
      <div class="dash-section-head">
        <div class="dash-section-title">Recent Requests</div>
        <a href="#" class="dash-section-link">View all</a>
      </div>

      <div class="table-wrap">
        <table class="dash-table">
          <thead>
            <tr>
              <th>Requested</th>
              <th>Item / Code</th>
              <th class="hide-sm">Reporter</th>
              <th>Status</th>
              <th class="hide-sm">Owner</th>
              <th>Completed</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recent as $r)
              @php $st = (string)($r['status'] ?? ''); @endphp
              <tr>
                <td>{{ $r['request_date'] ?? '-' }}</td>
                <td>
                  <div style="font-weight:1000;">{{ $r['asset_name'] ?? '-' }}</div>
                  <div class="subcode">{{ $r['asset_id'] ?? '' }}</div>
                </td>
                <td class="hide-sm">{{ $r['reporter'] ?? '-' }}</td>
                <td>
                  <span class="pill {{ $statusPill($st) }}">
                    {{ $statusTH[$st] ?? ucfirst($st) }}
                  </span>
                </td>
                <td class="hide-sm">{{ $r['technician'] ?? '-' }}</td>
                <td>{{ $r['completed_at'] ?? '-' }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="empty">No recent data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

  </main>
</div>

<div class="ui-toast-overlay" aria-live="polite" aria-atomic="true"></div>

<script>
  window.__DASH__ = {
    message: @json($message),
    type: @json($type),
    timeout: @json($timeout),
  };
</script>

@vite(['resources/js/repair/dashboard.js'])
@endsection
