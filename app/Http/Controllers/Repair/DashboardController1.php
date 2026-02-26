<?php

namespace App\Http\Controllers\Repair;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController1 extends Controller
{
    public function index(Request $req)
    {
        // ===== detect columns/tables =====
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

        // ===== auth scope (ซ่อนข้อมูลสำหรับคนทั่วไป) =====
        $user = $req->user();
        $isStaff = $user && (
            method_exists($user, 'isAdmin') && $user->isAdmin()
            || method_exists($user, 'isSupervisor') && $user->isSupervisor()
            || method_exists($user, 'isTechnician') && $user->isTechnician()
        );

        // ===== base query =====
        $base = DB::table('maintenance_requests as mr');

        // ===== inputs =====
        $status = (string) $req->query('status', '');
        $from   = $req->query('from');
        $to     = $req->query('to');

        // ผู้ใช้ “ตั้งใจกรอง” ไหม (แม้กรอกวันที่ผิด)
        $filterIntent = ($status !== '' || !empty($from) || !empty($to));
        $hasFilter    = false;  // มีเงื่อนไขที่นำไปใช้จริงอย่างน้อย 1 อัน
        $dateError    = false;

        // ===== Filters =====
        if ($status !== '') {
            // ✅ FIX: completed ใน UI = resolved + closed ใน DB
            if ($status === 'completed') {
                $base->whereIn('mr.status', ['resolved', 'closed']);
            } else {
                $base->where('mr.status', $status);
            }
            $hasFilter = true;
        }

        if ($from) {
            try {
                $col = $hasReqDate ? 'mr.request_date' : ($hasCreatedAt ? 'mr.created_at' : null);
                if ($col) {
                    $base->whereDate($col, '>=', Carbon::parse($from)->toDateString());
                    $hasFilter = true;
                }
            } catch (\Throwable $e) {
                $dateError = true;
            }
        }

        if ($to) {
            try {
                $col = $hasReqDate ? 'mr.request_date' : ($hasCreatedAt ? 'mr.created_at' : null);
                if ($col) {
                    $base->whereDate($col, '<=', Carbon::parse($to)->toDateString());
                    $hasFilter = true;
                }
            } catch (\Throwable $e) {
                $dateError = true;
            }
        }

        // ===== Stats (รวมเป็น 1 query) =====
        $statsRow = (clone $base)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN mr.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN mr.status = 'in_progress' THEN 1 ELSE 0 END) as inProgress,
            SUM(CASE WHEN mr.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed
        ")->first();

        $stats = [
            'total'      => (int) ($statsRow->total ?? 0),
            'pending'    => (int) ($statsRow->pending ?? 0),
            'inProgress' => (int) ($statsRow->inProgress ?? 0),
            'completed'  => (int) ($statsRow->completed ?? 0),
            'monthCost'  => 0.0,
        ];

        // ===== Monthly trend (6 เดือนล่าสุด) =====
        if ($hasReqDate || $hasCreatedAt) {
            $trendCol = $hasReqDate ? 'mr.request_date' : 'mr.created_at';

            $monthlyTrend = (clone $base)
                ->where($trendCol, '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT($trendCol, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->map(fn($r) => [
                    'ym'  => $r->ym,
                    'cnt' => (int) $r->cnt,
                ])
                ->take(6)
                ->values();
        } else {
            $monthlyTrend = collect();
        }

        $totalReq = $stats['total'];

        // ===== By asset type (ทั้งหมด) =====
        if ($hasAssets) {
            $qType = (clone $base)->leftJoin('assets as a', 'a.id', '=', 'mr.asset_id');

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

        $byAssetType = $assetTypes
            ->map(fn($r) => [
                'type' => (string) $r->type,
                'cnt'  => (int) $r->cnt,
            ])
            ->values();

        // ===== By department (ทั้งหมด) =====
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

        // ===== Recent jobs =====
        $recentQ = (clone $base);

        if ($hasReqDate) {
            $recentQ->orderByDesc('mr.request_date');
        } elseif ($hasCreatedAt) {
            $recentQ->orderByDesc('mr.created_at');
        }

        $recentQ->limit(12);

        $selects = ['mr.*'];
        if ($hasReqDate)       $selects[] = DB::raw('mr.request_date   as req_dt');
        if ($hasCreatedAt)     $selects[] = DB::raw('mr.created_at     as created_dt');
        if ($hasCompletedDate) $selects[] = DB::raw('mr.completed_date as comp_dt');
        if ($hasCompletedAt)   $selects[] = DB::raw('mr.completed_at   as completed_dt');

        if ($hasAssets) {
            $recentQ->leftJoin('assets as a', 'a.id', '=', 'mr.asset_id');
            $selects[] = 'a.name as asset_name';
        }

        // ✅ join users เฉพาะ staff (กันข้อมูลหลุด + ลดโหลด)
        $hasUsers = Schema::hasTable('users') && Schema::hasColumn('users','name');
        if ($isStaff && $hasUsers) {
            $recentQ->leftJoin('users as r', 'r.id', '=', 'mr.reporter_id')
                    ->leftJoin('users as t', 't.id', '=', 'mr.technician_id');
            $selects[] = 'r.name as reporter_name';
            $selects[] = 't.name as technician_name';
        }

        $fmt = function ($v) {
            if ($v === null || $v === '') return '-';
            try {
                return Carbon::parse($v)->format('Y-m-d H:i');
            } catch (\Throwable $e) {
                return is_string($v) ? $v : '-';
            }
        };

        $recent = $recentQ->get($selects)->map(function ($r) use ($fmt, $isStaff) {
            $reqRaw  = $r->req_dt   ?? $r->created_dt   ?? null;
            $compRaw = $r->comp_dt  ?? $r->completed_dt ?? null;

            return [
                'request_date' => $fmt($reqRaw),
                'asset_id'     => (int) ($r->asset_id ?? 0),
                'asset_name'   => (string) ($r->asset_name ?? '-'),
                'reporter'     => $isStaff ? (string) ($r->reporter_name ?? '-') : '-',
                'technician'   => $isStaff ? (string) ($r->technician_name ?? '-') : '-',
                'status'       => (string) ($r->status ?? ''),
                'completed_at' => $fmt($compRaw),
            ];
        });

        // ===== Toast =====
        if ($filterIntent) {
            if ($dateError) {
                $req->session()->flash('toast', [
                    'type'     => 'warning',
                    'message'  => 'รูปแบบวันที่ไม่ถูกต้อง (from/to) ระบบใช้เฉพาะเงื่อนไขที่ถูกต้อง',
                    'position' => 'tc',
                    'timeout'  => 3200,
                    'size'     => 'md',
                ]);
            } elseif ($stats['total'] > 0) {
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
