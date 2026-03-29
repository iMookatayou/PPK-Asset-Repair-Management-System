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
        $hasReqDate       = Schema::hasColumn('maintenance_requests','request_date');
        $hasCreatedAt     = Schema::hasColumn('maintenance_requests','created_at');
        $hasCompletedDate = Schema::hasColumn('maintenance_requests','completed_date');
        $hasCompletedAt   = Schema::hasColumn('maintenance_requests','completed_at');
        $hasMrDeptId      = Schema::hasColumn('maintenance_requests','department_id');

        $hasAssets        = Schema::hasTable('assets');
        $hasType          = $hasAssets && Schema::hasColumn('assets','type');
        $hasAssetDeptId   = $hasAssets && Schema::hasColumn('assets','department_id');

        $hasDeptTbl       = Schema::hasTable('departments');
        $hasDeptNameTh    = $hasDeptTbl && Schema::hasColumn('departments','name_th');
        $hasDeptNameEn    = $hasDeptTbl && Schema::hasColumn('departments','name_en');

        $base = DB::table('maintenance_requests as mr');

        $status = (string) $req->query('status', '');
        $from   = $req->query('from');
        $to     = $req->query('to');

        $hasFilter = false;

        // ----- Filters -----
        if ($status !== '') {
            if ($status === 'completed') {
                $base->whereIn('mr.status', ['resolved','closed']);
            } else {
                $base->where('mr.status', $status);
            }
            $hasFilter = true;
        }

        $dateCol = $hasReqDate ? 'mr.request_date' : ($hasCreatedAt ? 'mr.created_at' : null);

        if ($from && $dateCol) {
            try {
                $base->whereDate($dateCol, '>=', Carbon::parse($from)->toDateString());
                $hasFilter = true;
            } catch (\Throwable $e) {}
        }

        if ($to && $dateCol) {
            try {
                $base->whereDate($dateCol, '<=', Carbon::parse($to)->toDateString());
                $hasFilter = true;
            } catch (\Throwable $e) {}
        }

        // ----- Stats card (Overview) -----
        $stats = [
            'total'      => (clone $base)->count(),
            'pending'    => (clone $base)->where('mr.status','pending')->count(),
            'processing' => (clone $base)->whereIn('mr.status', ['acknowledged','accepted','in_progress', 'on_hold'])->count(),
            'completed'  => (clone $base)->whereIn('mr.status', ['resolved','closed'])->count(),
            'cancelled'  => (clone $base)->whereIn('mr.status', ['cancelled','rejected'])->count(),
            'monthCost'  => 0.0,
        ];

        // สำหรับ UI card ที่เขียนว่า "Active"
        $stats['inProgress'] = $stats['processing'];

        // ----- Monthly trend (Default: 12 months) -----
        if ($dateCol) {
            $trendQuery = (clone $base);
            
            // If no FROM date is filtered, default to last 12 months
            if (!$from) {
                $trendQuery->where($dateCol, '>=', now()->startOfMonth()->subMonths(11));
            }

            $monthlyTrend = $trendQuery
                ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->map(fn($r) => [
                    'ym'  => (string)$r->ym,
                    'cnt' => (int)$r->cnt,
                ])
                ->values();
        } else {
            $monthlyTrend = collect();
        }

        // ----- KPI สำหรับการ์ดบนซ้าย (Last month / This month / Completed-this-month) -----
        $kpi = [
            'lastMonth'          => 0,
            'thisMonth'          => 0,
            'thisMonthCompleted' => 0,
            'avgResolveHours'    => null,
        ];

        if ($dateCol) {
            $startThis = now()->startOfMonth();
            $startLast = (clone $startThis)->subMonth();

            $kpi['thisMonth'] = (clone $base)
                ->whereBetween($dateCol, [$startThis, now()->endOfDay()])
                ->count();

            $kpi['lastMonth'] = (clone $base)
                ->whereBetween($dateCol, [$startLast, (clone $startThis)->subSecond()])
                ->count();

            $kpi['thisMonthCompleted'] = (clone $base)
                ->whereBetween($dateCol, [$startThis, now()->endOfDay()])
                ->whereIn('mr.status', ['resolved','closed'])
                ->count();
        }

        // avgResolveHours (ถ้ามี completed date/at)
        $compCol = $hasCompletedAt ? 'mr.completed_at' : ($hasCompletedDate ? 'mr.completed_date' : null);
        if ($dateCol && $compCol) {
            // ใช้เฉพาะงาน completed
            $rows = (clone $base)
                ->whereIn('mr.status', ['resolved','closed'])
                ->whereNotNull($compCol)
                ->whereNotNull($dateCol)
                ->selectRaw("TIMESTAMPDIFF(MINUTE, $dateCol, $compCol) as diff_min")
                ->limit(3000)
                ->pluck('diff_min');

            if ($rows->count() > 0) {
                $avgMin = (int) round($rows->avg());
                $kpi['avgResolveHours'] = round($avgMin / 60, 1);
            }
        }

        $totalReq = (int)($stats['total'] ?? 0);

        // ==============================
        //  By asset type (ทั้งหมด)
        // ==============================
        if ($hasAssets) {
            $qType = (clone $base)
                ->leftJoin('assets as a', 'a.id', '=', 'mr.asset_id');

            if ($hasType) {
                $assetTypes = $qType
                    ->selectRaw('COALESCE(NULLIF(a.type,""),"ไม่ระบุ") as type, COUNT(*) as cnt')
                    ->groupBy('type')
                    ->orderByDesc('cnt')
                    ->get();
            } else {
                $assetTypes = collect([(object) ['type' => 'ไม่ระบุ', 'cnt' => $totalReq]]);
            }
        } else {
            $assetTypes = collect([(object) ['type' => 'ไม่ระบุ', 'cnt' => $totalReq]]);
        }

        $byAssetType = $assetTypes->map(fn($r) => [
            'type' => (string) $r->type,
            'cnt'  => (int) $r->cnt,
        ])->values();

        // ==============================
        //  By department (ทั้งหมด)
        // ==============================
        if ($hasDeptTbl && ($hasDeptNameTh || $hasDeptNameEn)) {
            $qDept = (clone $base);

            if ($hasAssets) {
                $qDept->leftJoin('assets as a', 'a.id', '=', 'mr.asset_id');
            }

            if ($hasMrDeptId) {
                $qDept->leftJoin('departments as d_mr', 'd_mr.id', '=', 'mr.department_id');
            }

            if ($hasAssetDeptId) {
                $qDept->leftJoin('departments as d_a', 'd_a.id', '=', 'a.department_id');
            }

            $labelSqlParts = [];
            if ($hasDeptNameTh) $labelSqlParts[] = "NULLIF(TRIM(d_mr.name_th),'')";
            if ($hasDeptNameEn) $labelSqlParts[] = "NULLIF(TRIM(d_mr.name_en),'')";
            if ($hasDeptNameTh) $labelSqlParts[] = "NULLIF(TRIM(d_a.name_th),'')";
            if ($hasDeptNameEn) $labelSqlParts[] = "NULLIF(TRIM(d_a.name_en),'')";

            $coalesce = 'COALESCE(' . implode(',', $labelSqlParts) . ", 'ไม่ระบุ')";

            $byDept = $qDept
                ->selectRaw("$coalesce as dept, COUNT(*) as cnt")
                ->groupBy('dept')
                ->orderByDesc('cnt')
                ->get()
                ->map(fn($r) => [
                    'dept' => (string) $r->dept,
                    'cnt'  => (int) $r->cnt,
                ])
                ->values();
        } else {
            $byDept = $totalReq > 0
                ? collect([['dept' => 'ไม่ระบุ', 'cnt' => $totalReq]])
                : collect();
        }

        // ----- Toast เมื่อมีการใช้ตัวกรอง -----
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

        // ----- Technician Workload -----
        $hasTechId = Schema::hasColumn('maintenance_requests', 'technician_id');
        $hasUsers  = Schema::hasTable('users') && Schema::hasColumn('users', 'name');

        if ($hasTechId && $hasUsers) {
            $techRows = DB::table('maintenance_requests as mr')
                ->join('users as t', 't.id', '=', 'mr.technician_id')
                ->whereNotNull('mr.technician_id')
                ->whereNotIn('mr.status', ['resolved', 'closed', 'cancelled'])
                ->selectRaw("t.id as tech_id, COUNT(*) as total")
                ->groupBy('t.id')
                ->orderByDesc('total')
                ->limit(15)
                ->get();

            $techIds = $techRows->pluck('tech_id')->all();
            $users   = \App\Models\User::whereIn('id', $techIds)->get()->keyBy('id');

            $techWorkload = $techRows->map(function ($r) use ($users) {
                $user = $users->get($r->tech_id);
                return [
                    'id'     => (int) $r->tech_id,
                    'name'   => $user ? $user->name : 'Unknown',
                    'total'  => (int) $r->total,
                    'avatar' => $user ? $user->avatar_thumb_url : '',
                ];
            })->values();
        } else {
            $techWorkload = collect();
        }

        return view('repair.dashboard',
            compact('stats','monthlyTrend','byAssetType','byDept','kpi','techWorkload')
        );
    }
}
