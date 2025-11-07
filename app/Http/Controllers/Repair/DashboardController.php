<?php

namespace App\Http\Controllers\Repair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $req)
    {
        // ===== Column existence guards =====
        $hasReqDate       = Schema::hasColumn('maintenance_requests','request_date');
        $hasCreatedAt     = Schema::hasColumn('maintenance_requests','created_at');
        $hasCompletedDate = Schema::hasColumn('maintenance_requests','completed_date');
        $hasCompletedAt   = Schema::hasColumn('maintenance_requests','completed_at');

        $hasAssets  = Schema::hasTable('assets');
        $hasType    = $hasAssets && Schema::hasColumn('assets','type');
        $hasDeptTbl = Schema::hasTable('departments');
        $hasDeptCol = $hasDeptTbl && Schema::hasColumn('departments', 'name');
        $hasDeptId  = $hasAssets && Schema::hasColumn('assets', 'department_id');

        // ===== Base query (ALIAS) =====
        $base = DB::table('maintenance_requests as mr');

        // ===== Filters from query string =====
        $status = (string) $req->query('status', '');
        $from   = $req->query('from');
        $to     = $req->query('to');

        $hasFilter = false;

        if ($status !== '') {
            $base->where('mr.status', $status);
            $hasFilter = true;
        }
        if ($from) {
            try {
                $col = $hasReqDate ? 'mr.request_date' : ($hasCreatedAt ? 'mr.created_at' : null);
                if ($col) {
                    $base->whereDate($col, '>=', Carbon::parse($from)->toDateString());
                    $hasFilter = true;
                }
            } catch (\Throwable $e) {}
        }
        if ($to) {
            try {
                $col = $hasReqDate ? 'mr.request_date' : ($hasCreatedAt ? 'mr.created_at' : null);
                if ($col) {
                    $base->whereDate($col, '<=', Carbon::parse($to)->toDateString());
                    $hasFilter = true;
                }
            } catch (\Throwable $e) {}
        }

        // ===== KPI =====
        $stats = [
            'total'      => (clone $base)->count(),
            'pending'    => (clone $base)->where('mr.status','pending')->count(),
            'inProgress' => (clone $base)->where('mr.status','in_progress')->count(),
            'completed'  => (clone $base)->where('mr.status','completed')->count(),
            'monthCost'  => 0.0,
        ];

        // ===== Trend 6 months =====
        if ($hasReqDate || $hasCreatedAt) {
            $trendCol = $hasReqDate ? 'mr.request_date' : 'mr.created_at';
            $monthlyTrend = (clone $base)
                ->where($trendCol, '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT($trendCol, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->orderBy('ym')
                ->get()
                ->map(fn($r)=> ['ym'=>$r->ym, 'cnt'=>(int)$r->cnt])
                ->take(6)->values();
        } else {
            $monthlyTrend = collect();
        }

        // ===== By Asset Type (Top 8 + others) =====
        $totalReq = $stats['total'];
        if ($hasAssets) {
            $qType = (clone $base)
                ->leftJoin('assets as a','a.id','=','mr.asset_id');

            $topTypes = $hasType
                ? $qType->selectRaw('COALESCE(NULLIF(a.type,""),"ไม่ระบุ") as type, COUNT(*) as cnt')
                       ->groupBy('type')->orderByDesc('cnt')->limit(8)->get()
                : collect([(object)['type'=>'ไม่ระบุ','cnt'=>$totalReq]]);
        } else {
            $topTypes = collect([(object)['type'=>'ไม่ระบุ','cnt'=>$totalReq]]);
        }
        $sumTop = (int)$topTypes->sum('cnt');
        $others = max(0, $totalReq - $sumTop);
        if ($others > 0) $topTypes->push((object)['type'=>'อื่นๆ','cnt'=>$others]);

        $byAssetType = $topTypes
            ->map(fn($r)=> ['type'=>(string)$r->type, 'cnt'=>(int)$r->cnt])
            ->take(9)->values();

        // ===== By Department (Top 8) =====
        if ($hasAssets && $hasDeptTbl && $hasDeptCol && $hasDeptId) {
            $byDept = (clone $base)
                ->leftJoin('assets as a','a.id','=','mr.asset_id')
                ->leftJoin('departments as d','d.id','=','a.department_id')
                ->selectRaw('COALESCE(d.name,"ไม่ระบุ") as dept, COUNT(*) as cnt')
                ->groupBy('dept')->orderByDesc('cnt')->limit(8)->get()
                ->map(fn($r)=> ['dept'=>(string)$r->dept, 'cnt'=>(int)$r->cnt]);
        } else {
            $byDept = $totalReq > 0 ? collect([['dept'=>'ไม่ระบุ','cnt'=>$totalReq]]) : collect();
        }
        $byDept = collect($byDept)->take(8)->values();

        // ===== Recent 12 =====
        $recentQ = (clone $base);
        if     ($hasReqDate)   $recentQ->orderByDesc('mr.request_date');
        elseif ($hasCreatedAt) $recentQ->orderByDesc('mr.created_at');
        $recentQ->limit(12);

        $selects = ['mr.*'];
        if ($hasReqDate)       $selects[] = DB::raw('mr.request_date   as req_dt');
        if ($hasCreatedAt)     $selects[] = DB::raw('mr.created_at     as created_dt');
        if ($hasCompletedDate) $selects[] = DB::raw('mr.completed_date as comp_dt');
        if ($hasCompletedAt)   $selects[] = DB::raw('mr.completed_at   as completed_dt');

        if ($hasAssets) {
            $recentQ->leftJoin('assets as a','a.id','=','mr.asset_id');
            $selects[] = 'a.name as asset_name';
        }
        $hasUsers = Schema::hasTable('users') && Schema::hasColumn('users','name');
        if ($hasUsers) {
            $recentQ->leftJoin('users as r','r.id','=','mr.reporter_id')
                    ->leftJoin('users as t','t.id','=','mr.technician_id');
            $selects[] = 'r.name as reporter_name';
            $selects[] = 't.name as technician_name';
        }

        $fmt = function ($v) {
            if ($v === null || $v === '') return '-';
            try { return Carbon::parse($v)->format('Y-m-d H:i'); }
            catch (\Throwable $e) { return is_string($v) ? $v : '-'; }
        };

        $recent = $recentQ->get($selects)->map(function ($r) use ($fmt) {
            $reqRaw  = $r->req_dt  ?? $r->created_dt  ?? null;
            $compRaw = $r->comp_dt ?? $r->completed_dt ?? null;
            return [
                'request_date' => $fmt($reqRaw),
                'asset_id'     => (int)($r->asset_id ?? 0),
                'asset_name'   => (string)($r->asset_name ?? '-'),
                'reporter'     => (string)($r->reporter_name ?? '-'),
                'technician'   => (string)($r->technician_name ?? '-'),
                'status'       => (string)($r->status ?? ''),
                'completed_at' => $fmt($compRaw),
            ];
        });

        // ===== Toast: แจ้งผลการค้นหา/กรอง =====
        // ยิง toast เฉพาะเมื่อมีการใส่ filter อย่างน้อย 1 อย่าง
        if ($hasFilter) {
            if ($stats['total'] > 0) {
                $req->session()->flash('toast', [
                    'type'     => 'success', 
                    'message'  => "ค้นหาแล้ว: พบ {$stats['total']} รายการ",
                    'position' => 'tc',
                    'timeout'  => 2800,
                    'size'     => 'md',
                ]);
            } else {
                $req->session()->flash('toast', [
                    'type'     => 'warning',
                    'message'  => 'ไม่พบรายการตามเงื่อนไขที่ค้นหา',
                    'position' => 'tc',
                    'timeout'  => 3200,
                    'size'     => 'md',
                ]);
            }
        }

        return view('repair.dashboard',
            compact('stats','monthlyTrend','byAssetType','byDept','recent')
            + [
                'lottieMap' => [
                    'success' => asset('lottie/lock_with_green_tick.json'),
                    'info'    => asset('lottie/lock_with_blue_info.json'),
                    'warning' => asset('lottie/lock_with_yellow_alert.json'),
                    'error'   => asset('lottie/lock_with_red_tick.json'),
                ],
            ]
        );
    }
}
