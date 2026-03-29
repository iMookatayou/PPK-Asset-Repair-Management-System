<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // เพิ่มการเรียกใช้ Log

class MaintenanceRatingController extends Controller
{
    protected int $ratingDeadlineDays = 7;

    // หน้า "ให้คะแนนงาน"
    public function evaluateList()
    {
        /** @var User $user */
        $user = Auth::user();

        $baseQuery = MaintenanceRequest::query()
            ->where('reporter_id', $user->id)
            ->whereIn('status', [
                MaintenanceRequest::STATUS_RESOLVED,
                MaintenanceRequest::STATUS_CLOSED,
            ]);

        // งานที่ยังไม่ให้คะแนน
        $pendingRequests = (clone $baseQuery)
            ->with(['technician:id,name', 'assignments.user:id,name,role'])
            ->whereDoesntHave('ratings', function ($q) use ($user) {
                $q->where('rater_id', $user->id);
            })
            ->get()
            ->filter(fn(MaintenanceRequest $req) => $this->withinRatingWindow($req))
            ->values();

        // งานที่ให้คะแนนแล้ว
        $ratedRequests = (clone $baseQuery)
            ->with([
                'technician:id,name',
                'ratings' => function ($q) use ($user) {
                    $q->where('rater_id', $user->id);
                },
            ])
            ->whereHas('ratings', function ($q) use ($user) {
                $q->where('rater_id', $user->id);
            })
            ->latest('closed_at')
            ->get();

        Log::info("User ID {$user->id} viewed their evaluation list."); // บันทึก Log การเข้าดูรายการ

        return view('maintenance.rating.evaluate', [
            'pendingRequests' => $pendingRequests,
            'ratedRequests'   => $ratedRequests,
        ]);
    }

    // Dashboard คะแนนของช่าง (รองรับ Sorting)
    public function technicianDashboard(Request $request)
    {
        $sort = $request->get('sort', 'score_desc');
        $from = $request->get('from');
        $to   = $request->get('to');

        // Default to current year if no filter
        $start = $from ? \Carbon\Carbon::parse($from)->startOfDay() : \Carbon\Carbon::now()->startOfYear();
        $end   = $to   ? \Carbon\Carbon::parse($to)->endOfDay()   : \Carbon\Carbon::now()->endOfMonth();

        $query = User::query()
            ->where('role', 'technician')
            ->withAvg(['technicianRatings as technician_ratings_avg_score' => function($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }], 'score')
            ->withCount(['technicianRatings as technician_ratings_count' => function($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }]);

        // Sorting
        $query = match ($sort) {
            'score_asc'  => $query->orderBy('technician_ratings_avg_score', 'asc'),
            'count_desc' => $query->orderBy('technician_ratings_count', 'desc'),
            'count_asc'  => $query->orderBy('technician_ratings_count', 'asc'),
            default      => $query->orderByDesc('technician_ratings_avg_score'),
        };

        $technicians = $query->get(['id', 'name', 'citizen_id']);

        Log::info("Technician Dashboard viewed. Sorting by: {$sort}"); // บันทึก Log การเข้าดู Dashboard พร้อมค่าการเรียง

        return view('maintenance.rating.technicians-dashboard', [
            'technicians' => $technicians,
            'selectedSort' => $sort, // ส่งค่าที่เลือกกลับไปที่ View เพื่อทำ Active State ใน Select
            'chartLabels' => $technicians->pluck('name'),
            'chartAvg'    => $technicians->pluck('technician_ratings_avg_score'),
            'chartCount'  => $technicians->pluck('technician_ratings_count'),
        ]);
    }

    // ฟอร์มให้คะแนน
    public function create(MaintenanceRequest $maintenanceRequest)
    {
        /** @var User $user */
        $user = Auth::user();

        // ส่งตัวแปร $maintenanceRequest เข้าไปเช็คสิทธิ์แทน
        if ($redirect = $this->guardRatingAccess($maintenanceRequest, $user)) {
            return $redirect;
        }

        $technicianId = $this->resolveTechnicianIdForRating($maintenanceRequest);

        // ตอนส่งไปที่หน้า View ยังคงส่งไปในชื่อ 'req' เหมือนเดิม จะได้ไม่ต้องแก้โค้ดหน้าบ้าน
        return view('maintenance.rating.form', [
            'req'          => $maintenanceRequest,
            'technicianId' => $technicianId,
        ]);
    }

    // บันทึกคะแนน
    public function store(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($redirect = $this->guardRatingAccess($maintenanceRequest, $user)) {
            Log::warning("Unauthorized or invalid rating attempt by User ID {$user->id} for Request ID {$maintenanceRequest->id}"); // บันทึก Log กรณีเข้าถึงไม่ถูกต้อง
            return $redirect;
        }

        $data = $this->validateRating($request);

        $technicianId = $this->resolveTechnicianIdForRating($maintenanceRequest);
        if (! $technicianId) {
            Log::error("Failed to store rating: No technician assigned for Request ID {$maintenanceRequest->id}"); // บันทึก Log กรณีไม่พบช่าง
            return redirect()
                ->route('maintenance.requests.show', $maintenanceRequest)
                ->with('toast', [
                    'type'    => 'warning',
                    'message' => 'ยังไม่พบช่างที่เกี่ยวข้องกับงานนี้ จึงยังให้คะแนนไม่ได้',
                ]);
        }

        MaintenanceRating::updateOrCreate(
            [
                'maintenance_request_id' => $maintenanceRequest->id,
                'rater_id'               => $user->id,
            ],
            [
                'technician_id'          => $technicianId,
                'score'                  => (int) $data['score'],
                'comment'                => $data['comment'] ?? null,
            ]
        );

        Log::info("Rating stored: User ID {$user->id} rated Technician ID {$technicianId} with score {$data['score']}"); // บันทึก Log เมื่อบันทึกคะแนนสำเร็จ

        return redirect()
            ->route('maintenance.requests.show', $maintenanceRequest)
            ->with('toast', [
                'type'    => 'success',
                'message' => 'บันทึกคะแนนเรียบร้อย',
            ]);
    }

    // ตรวจสอบสิทธิ์
    protected function guardRatingAccess(MaintenanceRequest $maintenanceRequest, User $user): ?RedirectResponse
    {
        if ((int) $maintenanceRequest->reporter_id !== (int) $user->id) {
            abort(403, 'คุณไม่มีสิทธิ์ให้คะแนนงานนี้');
        }

        if (! in_array($maintenanceRequest->status, [
            MaintenanceRequest::STATUS_RESOLVED,
            MaintenanceRequest::STATUS_CLOSED,
        ], true)) {
            abort(403, 'สามารถให้คะแนนได้เฉพาะงานที่ปิดแล้วเท่านั้น');
        }

        $alreadyRated = MaintenanceRating::query()
            ->where('maintenance_request_id', $maintenanceRequest->id)
            ->where('rater_id', $user->id)
            ->exists();

        if ($alreadyRated) {
            return redirect()
                ->route('maintenance.requests.show', $maintenanceRequest)
                ->with('toast', [
                    'type'    => 'info',
                    'message' => 'งานนี้มีการให้คะแนนไปแล้ว',
                ]);
        }

        if (! $this->withinRatingWindow($maintenanceRequest)) {
            return redirect()
                ->route('maintenance.requests.show', $maintenanceRequest)
                ->with('toast', [
                    'type'    => 'warning',
                    'message' => 'เลยระยะเวลาที่สามารถให้คะแนนงานนี้ได้แล้ว',
                ]);
        }

        if (! $this->resolveTechnicianIdForRating($maintenanceRequest)) {
            return redirect()
                ->route('maintenance.requests.show', $maintenanceRequest)
                ->with('toast', [
                    'type'    => 'warning',
                    'message' => 'ยังไม่มีการมอบหมายช่างในงานนี้ จึงยังให้คะแนนไม่ได้',
                ]);
        }

        return null;
    }

    // การตรวจสอบความถูกต้องของข้อมูล (Validation)
    protected function validateRating(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'score'   => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $validator->after(function ($v) {
            $data    = $v->getData();
            $score   = isset($data['score']) ? (int) $data['score'] : null;
            $comment = trim((string) ($data['comment'] ?? ''));

            if ($score !== null && $score <= 2 && $comment === '') {
                $v->errors()->add('comment', 'ถ้าให้ 1–2 ดาว กรุณาระบุความคิดเห็นเพิ่มเติม');
            }
        });

        return $validator->validate();
    }

    // ตรวจสอบระยะเวลาให้คะแนน
    protected function withinRatingWindow(MaintenanceRequest $maintenanceRequest): bool
    {
        $base = $maintenanceRequest->closed_at
            ?? $maintenanceRequest->resolved_at
            ?? $maintenanceRequest->completed_date;

        if (! $base) return false;

        return $base->isPast() && now()->diffInDays($base) <= $this->ratingDeadlineDays;
    }

    // ค้นหา ID ของช่างที่รับผิดชอบงาน
    protected function resolveTechnicianIdForRating(MaintenanceRequest $maintenanceRequest): ?int
    {
        $assignment = $maintenanceRequest->assignments()
            // อนุญาตให้ทั้ง technician และ admin สามารถรับการประเมินได้
            ->whereHas('user', function ($q) {
                $q->whereIn('role', ['technician', 'admin']);
            })
            ->orderByDesc('is_lead')
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END DESC")
            ->orderByDesc('assigned_at')
            ->first();

        return $assignment?->user_id;
    }

    public function summary(User $user)
    {
        // โหลดข้อมูลพื้นฐานและสถิติ
        $user->loadCount(['technicianRatings', 'technicianAssignments' => function($q) {
            $q->where('status', 'done');
        }])
        ->loadAvg('technicianRatings', 'score');

        // ข้อมูลสถิติการกระจายคะแนน (Score Distribution)
        $scoreDistribution = $user->technicianRatings()
            ->selectRaw('score, count(*) as count')
            ->groupBy('score')
            ->pluck('count', 'score')
            ->toArray();

        // ทั้งหมด 5-1 ดาว
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $scoreDistribution[$i] ?? 0;
            $percent = $user->technician_ratings_count > 0 
                ? ($count / $user->technician_ratings_count) * 100 
                : 0;
            $distribution[$i] = [
                'count' => $count,
                'percent' => $percent
            ];
        }

        // ความคิดเห็นล่าสุด
        $reviews = $user->technicianRatings()
            ->with('rater:id,name,role')
            ->latest()
            ->paginate(10);

        // งานล่าสุดที่ทำเสร็จ
        $recentJobs = $user->technicianAssignments()
            ->with(['maintenanceRequest' => function($q) {
                $q->with('reporter:id,name');
            }])
            ->where('status', 'done')
            ->latest() // ใช้ created_at แทน completed_at ที่ไม่มีในตาราง
            ->take(5)
            ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'id'          => $user->id,
                'name'        => $user->name,
                'avatar_url'  => $user->avatar_thumb_url,
                'role_label'  => $user->role_label,
                'avg_score'   => round((float) $user->technician_ratings_avg_score, 2),
                'total_count' => (int) $user->technician_ratings_count,
            ]);
        }

        return view('maintenance.rating.technician-show', [
            'tech' => $user,
            'distribution' => $distribution,
            'reviews' => $reviews,
            'recentJobs' => $recentJobs
        ]);
    }
}
