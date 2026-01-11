<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;
use App\Models\Attachment;
use App\Models\User;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class MaintenanceRequestController extends Controller
{
    public function create()
    {
        return $this->createPage();
    }

    public function indexPage(Request $request)
    {
        $user     = Auth::user();
        $userId   = (int) Auth::id();

        $status   = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $q        = trim($request->string('q')->toString());
        $assetId  = $request->integer('asset_id');

        // ---- ใช้ helper ดึงค่าการเรียง + จัดการ session ต่อ user ----
        [$sortBy, $sortDir] = $this->resolveSort($request);

        $query = MR::query()

            // ✅ เพิ่ม join เพื่อเอา my_response_status มาใช้บนหน้า index (เหมือน myJobsPage)
            ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
                $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->where('ma.user_id', '=', $userId);
            })

            // ✅ ต้อง select เองเพราะมี join
            ->select([
                'maintenance_requests.*',
                DB::raw('ma.response_status as my_response_status'),
                DB::raw('ma.responded_at as my_responded_at'),
            ])

            ->with([
                'asset',
                'reporter:id,name,email',
                'technician:id,name',
                'attachments' => fn($qq) => $qq
                    ->select('id','attachable_id','attachable_type','file_id','original_name','is_private','order_column')
                    ->with(['file:id,path,disk,mime,size']),
            ])

            // จำกัดเฉพาะผู้ใช้ระดับ Member ให้เห็นงานที่ตนแจ้งเท่านั้น
            ->when(
                ($user && !$user->isAdmin() && !$user->isSupervisor() && !$user->isTechnician()),
                fn($qb) => $qb->where('maintenance_requests.reporter_id', $user->id)
            )

            // filter อื่น ๆ
            ->when($assetId, fn($qb) => $qb->where('maintenance_requests.asset_id', $assetId))
            ->when($status, fn($qb) => $qb->where('maintenance_requests.status', $status))
            ->when($priority, fn($qb) => $qb->where('maintenance_requests.priority', $priority))
            ->when($q !== '', fn($qb) => $qb->search($q));

        if ($q !== '') {
            // กันผลสลับแถว + ทำให้ผลคงที่
            $query->orderByDesc('maintenance_requests.id');
        } else {
            if ($sortBy === 'request_no') {
                $dir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

                // 1) เอา request_no ว่าง/NULL ไปท้ายเสมอ
                $query->orderByRaw("CASE WHEN maintenance_requests.request_no IS NULL OR maintenance_requests.request_no = '' THEN 1 ELSE 0 END ASC");

                // 2) เรียง request_no ตามทิศทางที่เลือก
                $query->orderBy('maintenance_requests.request_no', $dir);

                // 3) tie-breaker กันสลับแถว
                $query->orderBy('maintenance_requests.id', $dir);
            } else {
                $allowed = ['request_no', 'id', 'request_date'];
                if (!in_array($sortBy, $allowed, true)) {
                    $sortBy = 'request_no';
                }
                $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

                $query->orderBy('maintenance_requests.' . $sortBy, $sortDir);
            }
        }

        $list = $query
            ->paginate(20)
            ->withQueryString();

        return view('maintenance.requests.index', compact('list','status','priority','q','sortBy','sortDir'));
    }

    public function showPage(MR $req)
    {
        \Gate::authorize('view', $req);

        $req->loadMissing([
            'asset',
            'department',
            'reporter:id,name,email',
            'technician:id,name',

            'assignments.user:id,name,role,profile_photo_path,profile_photo_thumb',

            'attachments' => fn($q) => $q->with('file'),
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
            'operationLog.user:id,name',
        ]);

        $techUsers = \App\Models\User::query()
            ->inRoles(\App\Models\User::teamRoles())
            ->orderBy('name')
            ->get(['id','name']);

        return view('maintenance.requests.show', [
            'req' => $req,
            'techUsers' => $techUsers,
        ]);
    }

    public function createPage()
    {
        $assets = \App\Models\Asset::orderBy('asset_code')->get(['id','asset_code','name']);
        $users  = \App\Models\User::orderBy('name')->get(['id','name']);
        $depts  = \App\Models\Department::orderBy('name_th')->get(['id','code','name_th','name_en']);

        return view('maintenance.requests.create', compact('assets','users','depts'));
    }

    public function index(Request $request)
    {
        $status   = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $q        = trim($request->string('q')->toString());
        $assetId  = $request->integer('asset_id');

        $user = $request->user();

        // ---- ใช้ helper ดึงค่าการเรียง + จัดการ session ต่อ user ----
        [$sortBy, $sortDir] = $this->resolveSort($request);

        $query = MR::query()
            ->with(['asset','reporter:id,name,email','technician:id,name'])

            // API/Web: บังคับ filter เหมือนกัน สำหรับ Member เท่านั้น
            ->when(
                ($user && !$user->isAdmin() && !$user->isSupervisor() && !$user->isTechnician()),
                fn($qb) => $qb->where('reporter_id', $user->id)
            )

            ->when($assetId, fn($qb) => $qb->where('asset_id', $assetId))
            ->when($status, fn($qb) => $qb->where('status', $status))
            ->when($priority, fn($qb) => $qb->where('priority', $priority))

            ->when($q !== '', fn($qb) => $qb->search($q));

        if ($q !== '') {
            // กันผลสลับแถว + ทำให้ผลคงที่
            $query->orderByDesc('id');
        } else {
            if ($sortBy === 'request_no') {
                $dir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

                // 1) เอา request_no ว่าง/NULL ไปท้ายเสมอ
                $query->orderByRaw("CASE WHEN request_no IS NULL OR request_no = '' THEN 1 ELSE 0 END ASC");

                // 2) เรียง request_no ตามทิศทางที่เลือก
                $query->orderBy('request_no', $dir);

                // 3) tie-breaker กันสลับแถว
                $query->orderBy('id', $dir);
            } else {
                // safety
                $allowed = ['request_no', 'id', 'request_date'];
                if (!in_array($sortBy, $allowed, true)) {
                    $sortBy = 'request_no';
                }
                $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

                $query->orderBy($sortBy, $sortDir);
            }
        }

        $list = $query
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $list->items(),
                'meta' => [
                    'current_page' => $list->currentPage(),
                    'per_page'     => $list->perPage(),
                    'total'        => $list->total(),
                    'last_page'    => $list->lastPage(),
                ],
                'toast' => [
                    'type' => 'info',
                    'message' => 'โหลดรายการคำขอบำรุงรักษาแล้ว',
                    'position' => 'tc',
                    'timeout' => 1200,
                    'size' => 'sm',
                ],
            ]);
        }

        return view('maintenance.requests.index', compact('list','status','priority','q','sortBy','sortDir'));
    }

    public function store(Request $request)
    {
        $rules = [
            'title'        => ['required','string','max:255'],
            'priority'     => ['required', Rule::in(['low','medium','high','urgent'])],

            'asset_id'      => ['nullable','integer','exists:assets,id'],
            'department_id' => ['nullable','integer','exists:departments,id'],
            'location_text' => ['nullable','string','max:255'],

            'reporter_name'  => ['nullable','string','max:255'],
            'reporter_phone' => ['nullable','string','max:30'],
            'reporter_email' => ['nullable','email','max:255'],

            'description'   => ['nullable','string','max:5000'],
        ];

        $data = Validator::make($request->all(), $rules)->validate();
        $user = $request->user();

        $req = MR::create([
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'priority'     => $data['priority'],
            'status'       => 'pending',
            'request_date' => now(),

            'asset_id'      => $data['asset_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'location_text' => $data['location_text'] ?? null,

            // ผู้แจ้ง
            'reporter_id'    => $user?->id,
            'reporter_name'  => $data['reporter_name'] ?? $user?->name,
            'reporter_email' => $data['reporter_email'] ?? $user?->email,
            'reporter_phone' => $data['reporter_phone'] ?? null,

            'technician_id' => null,
        ]);

        return redirect()->route('maintenance.requests.show', $req);
    }

    public function update(Request $request, MR $req)
    {
        \Gate::authorize('update', $req);

        $user    = $request->user();
        $actorId = $user?->id;
        $isTeam  = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

        $maxKb     = config('uploads.max_kb', 10240);
        $mimetypes = implode(',', config('uploads.mimetypes', ['image/*','application/pdf']));
        $fileRules = ['file', 'max:'.$maxKb, 'mimetypes:'.$mimetypes];

        $rules = [
            'title'        => ['sometimes','required','string','max:255'],
            'description'  => ['nullable','string','max:5000'],
            'asset_id'     => ['nullable','integer','exists:assets,id'],
            'priority'     => ['nullable', Rule::in(['low','medium','high','urgent'])],
            'request_date' => ['nullable','date'],

            'reporter_name'  => ['nullable','string','max:255'],
            'reporter_phone' => ['nullable','string','max:30'],
            'reporter_email' => ['nullable','email','max:255'],

            'department_id'   => ['nullable','integer','exists:departments,id'],
            'location_text'   => ['nullable','string','max:255'],
            'resolution_note' => ['nullable','string','max:5000'],
            'cost'            => ['nullable','numeric','min:0','max:99999999.99'],
            'files.*'         => $fileRules,

            'technician_id' => array_values(array_filter([
                'bail',
                Rule::prohibitedIf(!$isTeam),
                'nullable',
                'integer',
                'exists:users,id',
            ])),

            'status' => $isTeam
                ? ['nullable', Rule::in(['pending','accepted','in_progress','on_hold','resolved','closed','cancelled'])]
                : ['nullable', Rule::in(['cancelled'])],

            // ---- operation log ----
            'operation_date'   => ['nullable','date'],
            'operation_method' => ['nullable', Rule::in(['requisition','service_fee','other'])],
            'property_code'    => ['nullable','string','max:100'],
            'require_precheck' => ['nullable','boolean'],
            'remark'           => ['nullable','string','max:5000'],
            'issue_software'   => ['nullable','boolean'],
            'issue_hardware'   => ['nullable','boolean'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('toast', \App\Support\Toast::warning('ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง', 2600));
        }

        $data = $validator->validated();

        DB::transaction(function () use ($data, $request, $req, $isTeam, $actorId) {

            $originalStatus = $req->status;
            $originalTechId = (int) ($req->technician_id ?? 0);

            // ✅ สมาชิกทั่วไป: จำกัดสิทธิ์เหมือนเดิม
            if (!$isTeam) {
                if (($data['status'] ?? null) === 'cancelled') {
                    if (!in_array($req->status, ['pending','accepted'], true) || !empty($req->technician_id)) {
                        unset($data['status']);
                    }
                } else {
                    unset($data['status']);
                }

                unset(
                    $data['technician_id'],
                    $data['cost'],
                    $data['resolution_note'],
                    $data['operation_date'],
                    $data['operation_method'],
                    $data['property_code'],
                    $data['require_precheck'],
                    $data['remark'],
                    $data['issue_software'],
                    $data['issue_hardware']
                );
            }

            // fill fields
            $req->fill($data);

            // auto-assign เมื่อ accepted
            if (
                $isTeam &&
                (($data['status'] ?? null) === 'accepted') &&
                empty($req->technician_id) &&
                $actorId
            ) {
                $req->technician_id = $actorId;
            }

            $req->save();

            /* ---------- timeline ---------- */
            if (array_key_exists('status', $data) && $originalStatus !== $req->status) {
                $now = now();
                match ($req->status) {
                    'accepted'    => $req->accepted_at ??= $now,
                    'in_progress' => $req->started_at  ??= $now,
                    'on_hold'     => $req->on_hold_at  ??= $now,
                    'resolved'    => $req->resolved_at ??= $now,
                    'closed'      => [
                        $req->closed_at      ??= $now,
                        $req->completed_date ??= $now,
                    ],
                    default => null,
                };
                if ($req->status === 'accepted' && empty($req->assigned_date)) {
                    $req->assigned_date = $now;
                }
                $req->save();
            }

            /* ---------- assignment ---------- */
            $newTechId     = (int) ($req->technician_id ?? 0);
            $techChanged   = $isTeam && $originalTechId !== $newTechId;
            $statusChanged = array_key_exists('status', $data) && $originalStatus !== $req->status;

            if ($isTeam && ($techChanged || $statusChanged) && $newTechId > 0) {
                $this->syncAssignment($req, $newTechId, $actorId, true);
            }

            /* ---------- log ---------- */
            if (class_exists(\App\Models\MaintenanceLog::class)) {
                \App\Models\MaintenanceLog::create([
                    'request_id'  => $req->id,
                    'action'      => ($statusChanged
                        ? \App\Models\MaintenanceLog::ACTION_TRANSITION
                        : \App\Models\MaintenanceLog::ACTION_UPDATE),
                    'note'        => $statusChanged
                        ? $this->defaultNoteForStatus($req->status, $actorId, $req)
                        : null,
                    'user_id'     => $actorId,
                    'from_status' => $statusChanged ? $originalStatus : null,
                    'to_status'   => $statusChanged ? $req->status : null,
                ]);
            }

            /* ---------- remove attachments ---------- */
            $toRemove = array_filter(
                (array) $request->input('remove_attachments', []),
                fn($v) => is_numeric($v)
            );

            if (!empty($toRemove)) {
                $req->attachments()->whereIn('id', $toRemove)->get()
                    ->each(fn($att) => $att->deleteAndCleanup(true));
            }

            /* ---------- upload attachments ---------- */
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $up) {
                    $disk = 'public';
                    $storedPath = $up->store("maintenance/{$req->id}", $disk);

                    $sha = hash_file('sha256', $up->getRealPath());

                    $file = File::firstOrCreate(
                        ['checksum_sha256' => $sha],
                        [
                            'path'      => $storedPath,
                            'disk'      => $disk,
                            'mime'      => $up->getClientMimeType(),
                            'size'      => $up->getSize(),
                            'path_hash' => hash('sha256', $storedPath),
                        ]
                    );

                    $existing = $req->attachments()->withTrashed()->where('file_id', $file->id)->first();
                    if ($existing) {
                        if ($existing->trashed()) $existing->restore();
                        continue;
                    }

                    $req->attachments()->create([
                        'file_id'       => $file->id,
                        'original_name' => $up->getClientOriginalName(),
                        'extension'     => $up->getClientOriginalExtension() ?: null,
                        'uploaded_by'   => $actorId,
                        'source'        => 'web',
                        'is_private'    => false,
                        'order_column'  => 0,
                    ]);
                }
            }

            //operation log
            $hasOp =
                array_key_exists('operation_date', $data) ||
                array_key_exists('operation_method', $data) ||
                array_key_exists('property_code', $data) ||
                array_key_exists('remark', $data) ||
                array_key_exists('require_precheck', $data) ||
                array_key_exists('issue_software', $data) ||
                array_key_exists('issue_hardware', $data) ||
                $req->operationLog()->exists();

            if ($hasOp) {
                $opDate = $data['operation_date'] ?? null;
                if (!empty($opDate)) {
                    $opDate = \Carbon\Carbon::parse($opDate)->toDateString();
                }

                $req->operationLog()->updateOrCreate(
                    ['maintenance_request_id' => $req->id],
                    [
                        'operation_date'   => $opDate,
                        'operation_method' => $data['operation_method'] ?? null,
                        'property_code'    => $data['property_code'] ?? null,
                        'require_precheck' => (bool) ($data['require_precheck'] ?? false),
                        'remark'           => $data['remark'] ?? null,
                        'issue_software'   => (bool) ($data['issue_software'] ?? false),
                        'issue_hardware'   => (bool) ($data['issue_hardware'] ?? false),
                        'user_id'          => $actorId,
                    ]
                );
            }
        });

        $req->load(['attachments.file','operationLog']);

        return $this->respondWithToast(
            $request,
            \App\Support\Toast::success('อัปเดตคำขอเรียบร้อย', 1600),
            redirect()->route('maintenance.requests.show', $req),
            ['data' => $req]
        );
    }

    public function queuePage(Request $request)
    {
        \Gate::authorize('view-repair-dashboard');
        $status = (string) $request->string('status');
        $q      = (string) $request->string('q');
        $just   = (int) $request->query('just');

        $base = MR::query()
            ->with(['asset','reporter:id,name,email','technician:id,name'])
            ->whereIn('status', ['pending','accepted','in_progress','on_hold']);

        $list = (clone $base)
            ->when($status, fn($qb) => $qb->where('status', $status))
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('title','like',"%{$q}%")
                        ->orWhere('description','like',"%{$q}%")
                        ->orWhere('request_no','like',"%{$q}%")
                        ->orWhereHas('reporter', fn($qr) => $qr->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"))
                        ->orWhereHas('asset', fn($qa) => $qa->where('name','like',"%{$q}%")->orWhere('asset_code','like',"%{$q}%"));
                });
            })
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->orderByDesc('request_date')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->where('status','pending')->count(),
            'in_progress' => (clone $base)->where('status','in_progress')->count(),
            'completed'   => MR::query()->whereIn('status', ['resolved','closed'])->count(),
        ];

        return view('repair.queue', compact('list','stats','just'));
    }

    public function myJobsPage(Request $request)
    {
        $userId = (int) Auth::id();

        $filter = $request->string('filter')->toString(); // my | available | all
        $status = strtolower($request->string('status')->toString()); // ✅ filter by MR.status จริง
        $tech   = $request->integer('tech');              // technician_id
        $q      = $request->string('q')->toString();      // keyword search
        $resp   = strtolower($request->string('resp')->toString());   // ma.response_status

        if ($filter === '') $filter = 'all';

        // ✅ สถานะที่ "ไม่ให้แสดงใน list" (ตามเดิม)
        $excludedList = [
            MR::STATUS_CLOSED,
            MR::STATUS_RESOLVED,
            MR::STATUS_CANCELLED,
        ];

        // ✅ whitelist: สถานะใบงานจริงเท่านั้น
        $validReqStatus = [
            MR::STATUS_PENDING,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD,
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED,
        ];
        if ($status !== '' && !in_array($status, $validReqStatus, true)) {
            $status = '';
        }

        // ✅ whitelist: response_status ของฉัน
        $validResp = [
            MaintenanceAssignment::RESP_PENDING,
            MaintenanceAssignment::RESP_ACCEPTED,
            MaintenanceAssignment::RESP_ACKNOWLEDGED,
            MaintenanceAssignment::RESP_REJECTED,
        ];
        if ($resp !== '' && !in_array($resp, $validResp, true)) {
            $resp = '';
        }

        /**
         * ✅ base query (ใช้ร่วมกับ list และ stats)
         * join assignment ของ user คนนี้เท่านั้น (1 แถวต่อ MR)
         */
        $base = MR::query()
            ->from('maintenance_requests')
            ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
                $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->where('ma.user_id', '=', $userId);
            });

        /**
         * ✅ LIST QUERY
         */
        $query = (clone $base)
            ->select([
                'maintenance_requests.id',
                'maintenance_requests.request_no',
                'maintenance_requests.request_date',
                'maintenance_requests.title',
                'maintenance_requests.description',
                'maintenance_requests.status',
                'maintenance_requests.priority',
                'maintenance_requests.updated_at',
                'maintenance_requests.created_at',
                'maintenance_requests.asset_id',
                'maintenance_requests.department_id',
                'maintenance_requests.location_text',
                'maintenance_requests.reporter_id',
                'maintenance_requests.reporter_name',
                'maintenance_requests.reporter_phone',
                'maintenance_requests.technician_id',

                DB::raw('ma.response_status as my_response_status'),
                DB::raw('ma.responded_at as my_responded_at'),
                DB::raw('ma.status as my_assignment_status'),
            ])
            ->with([
                'asset:id,name,asset_code',
                'department',
                'reporter:id,name,email',
                'technician:id,name',
            ])
            ->when($filter === 'my', function ($qb) use ($userId) {
                // ✅ งานของฉัน: เป็นช่างหลัก หรือ มี assignment ของฉัน
                $qb->where(function ($qq) use ($userId) {
                    $qq->where('maintenance_requests.technician_id', $userId)
                    ->orWhereNotNull('ma.maintenance_request_id');
                });
            })
            ->when($filter === 'available', function ($qb) {
                $qb->whereNull('maintenance_requests.technician_id')
                ->where('maintenance_requests.status', MR::STATUS_PENDING);
            })
            ->when(!empty($tech), fn ($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($status !== '', function ($qb) use ($status) {
                // ✅ filter สถานะใบงานจาก MR.status เท่านั้น
                $qb->where('maintenance_requests.status', $status);
            })
            ->when($q !== '', fn ($qb) => $qb->search($q))
            ->whereNotIn('maintenance_requests.status', $excludedList)
            ->when($resp !== '', function ($qb) use ($resp) {
                // ✅ resp=pending ต้องรวมเคส "ยังไม่มี ma แถว" ด้วย
                if ($resp === MaintenanceAssignment::RESP_PENDING) {
                    $qb->where(function ($qq) use ($resp) {
                        $qq->whereNull('ma.response_status')
                        ->orWhere('ma.response_status', $resp);
                    });
                } else {
                    $qb->where('ma.response_status', $resp);
                }
            })
            ->orderByDesc('maintenance_requests.updated_at');

        $jobs = $query->paginate(15)->withQueryString();

        /**
         * ✅ STATS
         * - pending: status = pending
         * - in_progress: status IN ('accepted','in_progress') (คงพฤติกรรมเดิมของคุณ)
         * - completed: resolved + closed
         */
        $statsRow = (clone $base)
            ->selectRaw("
                SUM(CASE WHEN maintenance_requests.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN maintenance_requests.status IN ('accepted','in_progress') THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN maintenance_requests.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed
            ")
            ->when($filter === 'my', function ($qb) use ($userId) {
                $qb->where(function ($qq) use ($userId) {
                    $qq->where('maintenance_requests.technician_id', $userId)
                    ->orWhereNotNull('ma.maintenance_request_id');
                });
            })
            ->when($filter === 'available', function ($qb) {
                $qb->whereNull('maintenance_requests.technician_id')
                ->where('maintenance_requests.status', MR::STATUS_PENDING);
            })
            ->when(!empty($tech), fn ($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($q !== '', fn ($qb) => $qb->search($q))
            ->first();

        $stats = [
            'pending'     => (int)($statsRow->pending ?? 0),
            'in_progress' => (int)($statsRow->in_progress ?? 0),
            'completed'   => (int)($statsRow->completed ?? 0),
        ];

        // ✅ ให้ตรงกับ route('repairs.my_jobs') + ไฟล์ view ที่คุณส่งมา
        return view('repair.my-jobs', [
            'list'   => $jobs,
            'filter' => $filter,
            'status' => $status,
            'tech'   => $tech,
            'q'      => $q,
            'resp'   => $resp,
            'stats'  => $stats,
        ]);
    }

    public function acceptCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            \Gate::authorize('accept', $req);

            DB::transaction(function () use ($req, $actorId) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // งานปิด/ยกเลิกแล้ว -> กันไว้ (แต่ตามสเปคให้ 409 ถ้า flow ไม่ตรง)
                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED,
                ], true)) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }

                // acceptCase ต้องทำได้แค่ acknowledged -> accepted
                if ($locked->status !== MR::STATUS_ACKNOWLEDGED) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }

                // กันแย่ง: ถ้ามีช่างหลักแล้ว และไม่ใช่เรา
                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(409, 'มีผู้รับเรื่องแล้ว');
                }

                $locked->technician_id = $actorId;
                $locked->status = MR::STATUS_ACCEPTED;
                $locked->accepted_at = $locked->accepted_at ?? now();
                $locked->save();

                // (งานนี้ไม่บังคับ) ถ้าจะ sync assignment จริง ๆ ค่อยทำใน "มอบหมาย" ไม่ใช่สถานะหลัก
            });

            return back()->with('success', 'รับเรื่องแล้ว');
        } catch (\Throwable $e) {
            \Log::error('acceptCase failed', [
                'mr_id' => $req->id,
                'user_id' => $actorId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        // เหตุผลไม่บังคับ
        $data = $request->validate([
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        // default ถ้าไม่กรอก
        $remark = trim((string)($data['remark'] ?? ''));
        if ($remark === '') {
            $remark = 'ช่างไม่รับเรื่อง/คืนงานเข้าคิว';
        }

        try {
            \Gate::authorize('accept', $req);

            DB::transaction(function () use ($req, $actorId, $remark) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // งานปิดแล้ว ห้ามไม่รับเรื่อง
                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED,
                ], true)) {
                    abort(422, 'งานปิด/ยกเลิกแล้ว ไม่สามารถไม่รับเรื่องได้');
                }

                // ต้องรับทราบแล้วเท่านั้น
                if ($locked->status !== MR::STATUS_ACKNOWLEDGED) {
                    abort(422, 'ต้องอยู่สถานะ “รับทราบแล้ว” เท่านั้นจึงจะไม่รับเรื่องได้');
                }

                $now = now();

                // ปรับ/สร้าง assignment ของคนกด
                $assign = MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', $actorId)
                    ->lockForUpdate()
                    ->first();

                if (!$assign) {
                    MaintenanceAssignment::query()->create([
                        'maintenance_request_id' => $locked->id,
                        'user_id'                => $actorId,
                        'role'                   => 'tech',
                        'is_lead'                => false,
                        'assigned_at'            => $now,

                        'response_status'        => MaintenanceAssignment::RESP_REJECTED,
                        'responded_at'           => $now,
                        'remark'                 => $remark,

                        'status'                 => MaintenanceAssignment::STATUS_CANCELLED,
                    ]);
                } else {
                    $assign->forceFill([
                        'response_status' => MaintenanceAssignment::RESP_REJECTED,
                        'responded_at'    => $now,
                        'remark'          => $remark,
                        'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                    ])->save();
                }

                // ✅ ทำให้ “สถานะเปลี่ยน + ปุ่มหาย”
                // คืนงานเข้าคิว = pending + ไม่มีช่าง + เคลียร์ timeline หลัก
                $locked->update([
                    'status'        => MR::STATUS_PENDING,
                    'technician_id' => null,
                    'remark'        => $remark,

                    'accepted_at'   => null,
                    'started_at'    => null,
                    'on_hold_at'    => null,

                    'updated_at'    => $now,
                ]);

                // ✅ (แนะนำ) ปิด assignment อื่น ๆ ของใบงานนี้ด้วยกันข้อมูลค้าง
                MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', '!=', $actorId)
                    ->update([
                        'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                        'is_lead'         => false,
                        'response_status' => MaintenanceAssignment::RESP_PENDING,
                        'responded_at'    => null,
                        'updated_at'      => $now,
                    ]);

                // ✅ audit log (ถ้ามีโมเดลนี้ในโปรเจกต์เหมือน cancelCase)
                if (class_exists(\App\Models\MaintenanceLog::class)) {
                    \App\Models\MaintenanceLog::create([
                        'request_id' => $locked->id,
                        'action'     => 'rejected_by_tech',
                        'note'       => 'ไม่รับเรื่อง/คืนงานเข้าคิว: ' . $remark,
                        'user_id'    => $actorId,
                    ]);
                }
            });

            return back()->with('success', 'บันทึกการไม่รับเรื่องเรียบร้อยแล้ว');

        } catch (AuthorizationException $e) {
            return back()->with('error', 'คุณไม่มีสิทธิ์ไม่รับเรื่องรายการนี้');
        } catch (\Throwable $e) {
            Log::error('rejectCase failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelCase(Request $request, MR $req)
    {
        // ✅ ดักให้เห็นแน่ ๆ ว่าเข้า function
        Log::info('ENTER cancelCase', [
            'mr_id'   => $req->id,
            'user_id' => Auth::id(),
            'status'  => $req->status,
        ]);

        // ✅ แก้ให้กดปุ่มได้เลย: reason ไม่บังคับ
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $actorId = (int) Auth::id();

        // ✅ ถ้าไม่ส่ง reason มา ให้ค่าเริ่มต้น
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'ช่างไม่รับเรื่อง/คืนงานเข้าคิว';
        }

        try {
            DB::transaction(function () use ($req, $actorId, $reason) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // งานจบแล้ว/ถูกยกเลิกแล้ว ห้ามทำซ้ำ
                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED,
                ], true)) {
                    abort(409, 'งานนี้อยู่ในสถานะที่ทำรายการไม่ได้');
                }

                $now = now();

                /**
                 * 1) ผู้แจ้ง / แอดมิน = ยกเลิกใบงานจริง
                 */
                if (\Gate::check('cancelByReporter', $locked)) {

                    $this->applyTransition(
                        $locked,
                        [
                            'status' => MR::STATUS_CANCELLED,
                            'note'   => 'ยกเลิกซ่อม: ' . $reason,
                        ],
                        $actorId
                    );

                    // ปิด assignments ทั้งหมดของใบงานนี้ (กันข้อมูลค้าง)
                    MaintenanceAssignment::query()
                        ->where('maintenance_request_id', $locked->id)
                        ->update([
                            'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                            'is_lead'         => false,
                            'response_status' => MaintenanceAssignment::RESP_PENDING,
                            'responded_at'    => null,
                            'updated_at'      => $now,
                        ]);

                    // log audit
                    MaintenanceLog::create([
                        'request_id' => $locked->id,
                        'action'     => 'cancelled_by_reporter',
                        'note'       => 'ยกเลิกซ่อม: ' . $reason,
                        'user_id'    => $actorId,
                    ]);

                    return;
                }

                /**
                 * 2) ช่าง = คืนงานเข้าคิว (return to pool)
                 */
                \Gate::authorize('cancelByTech', $locked);

                // คืนงานเข้าคิว = pending + ไม่มีช่าง
                $locked->update([
                    'status'        => MR::STATUS_PENDING,
                    'technician_id' => null,
                    'remark'        => $reason,

                    // เคลียร์ timeline ที่สะท้อนการรับ/เริ่มงาน เพื่อไม่ให้ข้อมูลหลอก
                    'accepted_at'   => null,
                    'started_at'    => null,
                    'on_hold_at'    => null,

                    'updated_at'    => $now,
                ]);

                // ปิด assignment ของช่างคนที่คืนงาน + reset การตอบรับ
                MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', $actorId)
                    ->update([
                        'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                        'is_lead'         => false,
                        'response_status' => MaintenanceAssignment::RESP_PENDING,
                        'responded_at'    => null,
                        'updated_at'      => $now,
                    ]);

                // log audit
                MaintenanceLog::create([
                    'request_id' => $locked->id,
                    'action'     => 'returned_to_pool',
                    'note'       => 'คืนงานเข้าคิว: ' . $reason,
                    'user_id'    => $actorId,
                ]);
            });

        } catch (\Throwable $e) {

            Log::error('cancelCase failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'code'    => (int) $e->getCode(),
                'error'   => $e->getMessage(),
            ]);

            $msg = ((int) $e->getCode() === 409)
                ? ($e->getMessage() ?: 'งานนี้อยู่ในสถานะที่ทำรายการไม่ได้')
                : 'เกิดข้อผิดพลาดในการทำรายการ';

            return back()->with('toast', \App\Support\Toast::warning($msg, 2200));
        }

        Log::info('cancelCase success', [
            'mr_id'   => $req->id,
            'user_id' => $actorId,
        ]);

        return back()->with('toast', \App\Support\Toast::success('ทำรายการเรียบร้อย', 1800));
    }

    public function acceptJobQuick(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        $data = $request->validate([
            'decision'      => ['required', 'in:accepted,in_progress'],
            'technician_id' => ['nullable', 'integer'],
        ]);

        try {
            \Gate::authorize('accept', $req);

            DB::transaction(function () use ($req, $actorId, $data) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED,
                ], true)) {
                    abort(422, 'งานปิด/ยกเลิกแล้ว ไม่สามารถดำเนินการได้');
                }

                $techId = (int)($data['technician_id'] ?? $actorId);

                // กัน override ช่างหลัก ถ้าไม่ใช่คนเดิม
                if (!empty($locked->technician_id) && (int)$locked->technician_id !== $techId) {
                    abort(409, 'มีผู้รับงานแล้ว');
                }

                // ตั้งช่างหลัก
                $locked->technician_id = $techId;
                $locked->accepted_at   = $locked->accepted_at ?? now();

                if ($data['decision'] === 'accepted') {
                    if ($locked->status === MR::STATUS_PENDING) {
                        $locked->status = MR::STATUS_ACCEPTED;
                    }
                } else {
                    // ✅ เริ่มทำ = ใบงาน in_progress
                    $locked->status     = MR::STATUS_IN_PROGRESS;
                    $locked->started_at = $locked->started_at ?? now(); // ถ้ามีคอลัมน์นี้
                }

                $locked->save();

                // ✅ สร้าง/อัปเดต assignment ให้ช่างที่เลือกเสมอ
                $assign = MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', $techId)
                    ->lockForUpdate()
                    ->first();

                if (!$assign) {
                    MaintenanceAssignment::query()->create([
                        'maintenance_request_id' => $locked->id,
                        'user_id'               => $techId,
                        'role'                  => 'tech',
                        'is_lead'               => true,
                        'assigned_at'           => now(),

                        'response_status'       => MaintenanceAssignment::RESP_ACCEPTED,
                        'responded_at'          => now(),

                        'status'                => $data['decision'] === 'in_progress'
                            ? MaintenanceAssignment::STATUS_IN_PROGRESS
                            : null,
                    ]);
                } else {
                    $assign->response_status = MaintenanceAssignment::RESP_ACCEPTED;
                    $assign->responded_at    = now();

                    if ($data['decision'] === 'in_progress') {
                        $assign->status = MaintenanceAssignment::STATUS_IN_PROGRESS;
                    } else {
                        // รับงานอย่างเดียว = ยังไม่เริ่มทำ
                        if ($assign->status !== MaintenanceAssignment::STATUS_IN_PROGRESS) {
                            $assign->status = null;
                        }
                    }

                    $assign->save();
                }
            });

            return back()->with('success', 'บันทึกเรียบร้อย');
        } catch (\Throwable $e) {
            \Log::error('acceptJobQuick failed', [
                'mr_id' => $req->id,
                'user_id' => $actorId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function acknowledgeCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            \Gate::authorize('acknowledge', $req);

            DB::transaction(function () use ($req) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // ✅ acknowledgeCase ต้องทำได้แค่ pending -> acknowledged
                if ($locked->status !== MR::STATUS_PENDING) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }

                $locked->status = MR::STATUS_ACKNOWLEDGED;
                $locked->save();

                // ✅ งานนี้ไม่ต้องแตะ assignment เพื่อไม่ให้ UI/สถานะซ้ำซ้อน
            });

            return back()->with('success', 'รับทราบแล้ว');
        } catch (\Throwable $e) {
            \Log::error('acknowledgeCase failed', [
                'mr_id' => $req->id,
                'user_id' => $actorId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function transition(Request $request, MR $req)
    {
        \Gate::authorize('transition', $req);

        $user = $request->user();
        $actorId = optional($user)->id;

        $isTeam = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

        $rules = [
            'status' => $isTeam
                ? ['bail','required', Rule::in([
                    'pending',
                    'acknowledged',
                    'accepted',
                    'in_progress',
                    'on_hold',
                    'resolved',
                    'closed',
                    'cancelled',
                ])]
                : ['prohibited'],

            'note' => ['nullable','string','max:2000'],

            'technician_id' => array_values(array_filter([
                Rule::prohibitedIf(!$isTeam),
                'nullable','integer','exists:users,id',
            ])),
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $fieldsHuman = ['status' => 'สถานะ','technician_id' => 'รหัสช่าง','note'=>'บันทึก'];
            $bad = collect(array_keys($errors->toArray()))
                ->map(fn($f) => $fieldsHuman[$f] ?? $f)
                ->implode(', ');
            $msg = $bad ? ('ข้อมูลไม่ถูกต้อง: '.$bad) : 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';

            if (!$request->expectsJson()) {
                return redirect()->back()->withErrors($validator)->withInput()
                    ->with('toast', \App\Support\Toast::warning($msg, 2200));
            }

            return response()->json([
                'errors' => $errors,
                'toast'  => \App\Support\Toast::warning($msg, 2200),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // flow guard + 409 อยู่ใน applyTransition() แล้ว
        $req = $this->applyTransition($req, $data, $actorId);

        return $this->respondWithToast(
            $request,
            \App\Support\Toast::success('บันทึกสถานะเรียบร้อย', 1800),
            redirect()->back(),
            ['data' => $req]
        );
    }

    public function transitionFromBlade(Request $request, MR $req)
    {
        \Gate::authorize('transition', $req);

        $actorId = (int) optional(Auth::user())->id;
        $action  = (string) $request->string('action');

        if (!$action) {
            return $this->transition($request, $req);
        }

        // flow ใหม่:
        // accept: acknowledged -> accepted เท่านั้น
        // assign: ไม่ใช่การเปลี่ยนสถานะหลัก (อย่า map เป็น accepted ตรง ๆ) แต่ยังคง fallback ไป transition()
        // start: accepted -> in_progress (คงไว้ได้)
        $map = [
            'accept' => MR::STATUS_ACCEPTED,
            'start'  => MR::STATUS_IN_PROGRESS,
        ];

        $status = $map[$action] ?? null;

        // ถ้า action ที่ไม่อยู่ใน map ให้ใช้ transition() เดิม (กันกระทบระบบอื่น)
        if (!$status) {
            return $this->transition($request, $req);
        }

        // กัน flow ผิดแบบชัดเจน (409)
        $current = strtolower((string) ($req->status ?? ''));

        if ($action === 'accept') {
            // ต้องเป็น acknowledged เท่านั้น
            if ($current !== MR::STATUS_ACKNOWLEDGED) {
                abort(409, 'สถานะไม่ถูกต้อง');
            }
        }

        if ($action === 'start') {
            // แนะนำ: start ต้องมาจาก accepted เท่านั้น (ถ้าระบบเดิมอนุญาต pending/accepted ก็จะพัง flow ใหม่)
            if ($current !== MR::STATUS_ACCEPTED) {
                abort(409, 'สถานะไม่ถูกต้อง');
            }
        }

        $payload = [
            'status' => $status,
            'note'   => $request->string('note')->toString(),
        ];

        // accept: ตั้งช่างหลัก + accepted_at
        if ($action === 'accept') {
            // ตามสเปค acceptCase จะ set technician_id + accepted_at
            // แต่ transitionFromBlade ก็ต้องไม่ทำให้ผิด flow จึง set เฉพาะตอน accept และตามสิทธิ์ transition
            $payload['technician_id'] = $actorId;
            $payload['accepted_at']   = now(); // applyTransition ต้องรองรับ field นี้ หรือจะปล่อยให้ acceptCase จัดการ
        }

        // start: ตั้ง started_at (ถ้า applyTransition รองรับ)
        if ($action === 'start') {
            $payload['started_at'] = now();
        }

        $updated = $this->applyTransition($req, $payload, $actorId);

        $toastMessage = match ($action) {
            'accept' => 'รับเรื่องแล้ว',
            'start'  => 'เริ่มดำเนินการแล้ว',
            default  => 'บันทึกสถานะเรียบร้อย',
        };

        return $this->respondWithToast(
            $request,
            \App\Support\Toast::success($toastMessage, 1800),
            redirect()->route('repairs.queue', ['just' => $updated->id]),
            ['data' => $updated]
        );
    }

    protected function applyTransition(MR $req, array $data, ?int $actorId = null): MR
    {
        DB::transaction(function () use ($req, $data, $actorId) {

            $locked = MR::query()
                ->whereKey($req->id)
                ->lockForUpdate()
                ->firstOrFail();

            $originalStatus = (string) $locked->status;
            $originalTechId = (int) ($locked->technician_id ?? 0);

            $targetStatus = strtolower((string) ($data['status'] ?? ''));
            if ($targetStatus === '') {
                abort(409, 'สถานะไม่ถูกต้อง');
            }

            // กัน flow ตามสเปค (single source of truth = MR.status)
            // pending -> acknowledged -> accepted -> in_progress -> resolved/closed/cancelled
            $from = strtolower((string) $originalStatus);

            $allowedNext = [
                'pending'      => ['acknowledged', 'cancelled'],
                'acknowledged' => ['accepted', 'cancelled'],
                'accepted'     => ['in_progress', 'cancelled'],
                'in_progress'  => ['resolved', 'closed', 'cancelled', 'on_hold'],
                'on_hold'      => ['in_progress', 'cancelled'],
                'resolved'     => ['closed'],
                'closed'       => [],
                'cancelled'    => [],
            ];

            // ถ้าสถานะปลายทางเท่ากับเดิม อนุญาตเฉพาะกรณีเปลี่ยน technician/note (ไม่ถือว่า transition)
            $isStatusChange = ($from !== $targetStatus);

            if ($isStatusChange) {
                $nexts = $allowedNext[$from] ?? [];
                if (!in_array($targetStatus, $nexts, true)) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }
            }

            // เปลี่ยนสถานะ (ถ้ามี)
            if ($isStatusChange) {
                $locked->status = $targetStatus;
            }

            // เปลี่ยนช่างจาก payload (เฉพาะผ่าน policy แล้ว)
            if (!empty($data['technician_id']) && (int) $locked->technician_id !== (int) $data['technician_id']) {
                $locked->technician_id = (int) $data['technician_id'];
            }

            // accepted แต่ยังไม่มีช่าง -> ตั้งเป็นผู้กดรับเรื่อง (actor)
            if ($locked->status === MR::STATUS_ACCEPTED && empty($locked->technician_id) && $actorId) {
                $locked->technician_id = (int) $actorId;
            }

            // ---- timeline ----
            $now = now();
            switch ($locked->status) {
                case MR::STATUS_ACKNOWLEDGED:
                    // ✅ งานนี้ไม่บังคับมี timestamp acknowledged_at (ส่วนใหญ่ไม่มี column)
                    break;

                case MR::STATUS_ACCEPTED:
                    if (empty($locked->accepted_at)) $locked->accepted_at = $now;
                    if (empty($locked->assigned_date)) $locked->assigned_date = $now;
                    break;

                case MR::STATUS_IN_PROGRESS:
                    if (empty($locked->started_at)) $locked->started_at = $now;
                    break;

                case MR::STATUS_ON_HOLD:
                    if (empty($locked->on_hold_at)) $locked->on_hold_at = $now;
                    break;

                case MR::STATUS_RESOLVED:
                    if (empty($locked->resolved_at)) $locked->resolved_at = $now;
                    break;

                case MR::STATUS_CLOSED:
                    if (empty($locked->closed_at)) $locked->closed_at = $now;
                    if (empty($locked->completed_date)) $locked->completed_date = $now;
                    break;
            }

            $locked->save();

            $newTechId = (int) ($locked->technician_id ?? 0);
            $techChanged = ($originalTechId !== $newTechId);

            // ✅ syncAssignment เป็นรอง (คงเดิม) — แต่ไม่ให้มันเป็นสถานะหลัก
            if (($techChanged || $isStatusChange) && $newTechId > 0) {
                $this->syncAssignment($locked, $newTechId, $actorId, true);
            }

            // log
            if (class_exists(\App\Models\MaintenanceLog::class)) {
                $defaultNote = $data['note']
                    ?? $this->defaultNoteForStatus($locked->status, $actorId, $locked);

                if ($techChanged && $locked->technician) {
                    $defaultNote = trim(
                        ($defaultNote ? $defaultNote.' • ' : '') .
                        'ช่าง: '.$locked->technician->name
                    );
                }

                \App\Models\MaintenanceLog::create([
                    'request_id'  => $locked->id,
                    'action'      => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                    'note'        => $defaultNote ?: null,
                    'user_id'     => $actorId,
                    'from_status' => $originalStatus,
                    'to_status'   => $locked->status,
                ]);
            }

            // sync กลับเข้า $req ที่ถูกส่งเข้ามา (เพื่อ return fresh)
            $req->setRawAttributes($locked->getAttributes(), true);
        });

        return $req->fresh(['technician:id,name']);
    }

    public function uploadAttachmentFromBlade(Request $request, MR $req)
    {
        \Gate::authorize('attach', $req);
        $maxKb = config('uploads.max_kb', 10240);
        $mimetypes = implode(',', config('uploads.mimetypes', ['image/*','application/pdf']));
        $fileRules = ['required','file','max:'.$maxKb,'mimetypes:'.$mimetypes];

        $validated = $request->validate([
            'file'       => $fileRules,
            'is_private' => ['nullable','boolean'],
            'caption'    => ['nullable','string','max:255'],
            'alt_text'   => ['nullable','string','max:255'],
        ]);

        $up = $validated['file'];
        $isPrivate = (bool) ($validated['is_private'] ?? false);
        $disk = $isPrivate ? 'local' : 'public';
        $storedPath = $up->store("maintenance/{$req->id}", $disk);

        $stream = fopen($up->getRealPath(), 'r');
        $ctx = hash_init('sha256');
        while (!feof($stream)) {
            $buf = fread($stream, 1024 * 1024);
            if ($buf === false) break;
            hash_update($ctx, $buf);
        }
        fclose($stream);
        $sha = hash_final($ctx);

        $file = File::firstOrCreate(
            ['checksum_sha256' => $sha],
            [
                'path'       => $storedPath,
                'disk'       => $disk,
                'mime'       => $up->getClientMimeType(),
                'size'       => $up->getSize(),
                'path_hash'  => hash('sha256', $storedPath),
                'meta'       => null,
            ]
        );

        $existing = $req->attachments()->withTrashed()->where('file_id', $file->id)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill([
                'original_name' => $up->getClientOriginalName(),
                'extension'     => $up->getClientOriginalExtension() ?: $existing->extension,
                'uploaded_by'   => optional($request->user())->id,
                'is_private'    => $isPrivate,
                'caption'       => $validated['caption'] ?? $existing->caption,
                'alt_text'      => $validated['alt_text'] ?? $existing->alt_text,
            ])->save();

            return $this->respondWithToast(
                $request,
                \App\Support\Toast::info('ไฟล์นี้ถูกแนบไว้แล้ว (อัปเดตข้อมูลใหม่)', 1600),
                redirect()->back(),
                ['duplicate' => true, 'attachment_id' => $existing->id]
            );
        }

        $req->attachments()->create([
            'file_id'       => $file->id,
            'original_name' => $up->getClientOriginalName(),
            'extension'     => $up->getClientOriginalExtension() ?: null,
            'uploaded_by'   => optional($request->user())->id,
            'source'        => 'web',
            'is_private'    => $isPrivate,
            'caption'       => $validated['caption'] ?? null,
            'alt_text'      => $validated['alt_text'] ?? null,
            'order_column'  => 0,
        ]);

        return $this->respondWithToast(
            $request,
            \App\Support\Toast::success('อัปโหลดไฟล์แนบแล้ว', 1800),
            redirect()->back(),
            ['data' => $req->fresh('attachments.file')]
        );
    }

    public function destroyAttachment(MR $req, Attachment $attachment)
    {
        \Gate::authorize('deleteAttachment', $req);
        abort_unless(
            $attachment->attachable_type === MR::class &&
            (int) $attachment->attachable_id === (int) $req->id,
            404
        );

        $attachment->deleteAndCleanup(true);

        return $this->respondWithToast(
            request(),
            \App\Support\Toast::success('ลบไฟล์แนบแล้ว', 1600),
            redirect()->back(),
            ['deleted' => true]
        );
    }

    protected function respondWithToast(
        Request $request,
        array $toast,
        $webRedirect,
        array $jsonPayload = [],
        int $status = Response::HTTP_OK
    ) {
        if (!$request->expectsJson()) {
            return $webRedirect->with('toast', [
                'type'     => $toast['type']    ?? 'info',
                'message'  => $toast['message'] ?? '',
                'position' => $toast['position'] ?? 'tc',
                'timeout'  => $toast['timeout']  ?? 2000,
                'size'     => $toast['size']     ?? 'sm',
            ]);
        }

        $payload = array_merge($jsonPayload, [
            'toast' => [
                'type'     => $toast['type']    ?? 'info',
                'message'  => $toast['message'] ?? '',
                'position' => $toast['position'] ?? 'tc',
                'timeout'  => $toast['timeout']  ?? 2000,
                'size'     => $toast['size']     ?? 'sm',
            ],
        ]);

        return response()->json($payload, $status);
    }

    public function edit($id)
    {
        $mr = \App\Models\MaintenanceRequest::with([
                'asset',
                'reporter',
                'attachments.file',
                'operationLog',
            ])
            ->findOrFail($id);

        \Gate::authorize('update', $mr);

        $assets = \App\Models\Asset::orderBy('asset_code')->get(['id','asset_code','name']);
        $users  = \App\Models\User::orderBy('name')->get(['id','name']);
        $depts  = \App\Models\Department::orderBy('name_th')->get(['id','code','name_th','name_en']);

        $attachments = $mr->attachments()
            ->select(['id','file_id','original_name','is_private','order_column','attachable_id','attachable_type'])
            ->with(['file:id,path,disk,mime,size'])
            ->get();

        return view('maintenance.requests.edit', compact('mr','assets','users','attachments','depts'));
    }

    protected function defaultNoteForStatus(string $status, ?int $actorId, MR $req): string
    {
        $actorName = optional(\App\Models\User::find($actorId))->name;

        return match ($status) {
            'pending'       => 'ตั้งคิวงานใหม่',

            // ✅ เพิ่มตาม flow ใหม่
            'acknowledged'  => $actorName ? ('รับทราบโดย '.$actorName) : 'รับทราบแล้ว',

            // ✅ ห้ามใช้คำว่า “รับงานแล้ว” -> เปลี่ยนเป็น “รับเรื่องแล้ว”
            'accepted'      => $actorName ? ('รับเรื่องโดย '.$actorName) : 'รับเรื่องแล้ว',

            'in_progress'   => 'เริ่มดำเนินการซ่อม',
            'on_hold'       => 'พักงานชั่วคราว',
            'resolved'      => 'แก้ไขเสร็จ รอตรวจรับ',
            'closed'        => 'ปิดงานเรียบร้อย',
            'cancelled'     => 'ยกเลิกคำขอ',
            default         => 'อัปเดตสถานะ',
        };
    }

    public function printWorkOrder(Request $request, MR $req)
    {
        \Gate::authorize('view', $req);

        $req->loadMissing([
            'asset',
            'reporter:id,name,email',
            'technician:id,name',
            'attachments' => fn($qq) => $qq->with('file'),
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
        ]);

        $hospital = [
            'name_th'  => 'โรงพยาบาลพระปกเกล้า',
            'name_en'  => 'PHRAPOKKLAO HOSPITAL',
            'subtitle' => 'Maintenance Work Order',
            'logo'     => public_path('images/logoppk1.png'),
        ];

        $fileName = sprintf(
            'maintenance-work-order-%s.pdf',
            $req->request_no ?? $req->id
        );

        $pdf = Pdf::loadView('maintenance.requests.print', [
                'req'      => $req,
                'hospital' => $hospital,
            ])
            ->setPaper('A4', 'portrait');

        return $pdf->stream($fileName);
    }

    protected function resolveSort(Request $request): array
    {
        $user   = $request->user();
        $userId = $user?->id;

        $sessionSortByKey  = $userId ? "maintenance_sort_by_user_{$userId}"  : 'maintenance_sort_by_guest';
        $sessionSortDirKey = $userId ? "maintenance_sort_dir_user_{$userId}" : 'maintenance_sort_dir_guest';

        $allowedSorts = ['request_no', 'id', 'request_date'];

        $sortByReq  = $request->query('sort_by');
        $sortDirReq = strtolower((string) $request->query('sort_dir'));

        // sort_by
        if (in_array($sortByReq, $allowedSorts, true)) {
            $sortBy = $sortByReq;
            session([$sessionSortByKey => $sortBy]);
        } else {
            $sortBy = session($sessionSortByKey, 'request_no');
        }

        // sort_dir
        if (in_array($sortDirReq, ['asc','desc'], true)) {
            $sortDir = $sortDirReq;
            session([$sessionSortDirKey => $sortDir]);
        } else {
            $sortDir = session($sessionSortDirKey, 'desc');
        }

        return [$sortBy, $sortDir];
    }

    protected function syncAssignment(MR $req, int $userId, ?int $actorId = null, bool $isLead = true): void
    {
        $status = match ($req->status) {
            'resolved', 'closed' => MaintenanceAssignment::STATUS_DONE,
            'cancelled'          => MaintenanceAssignment::STATUS_CANCELLED,
            default              => MaintenanceAssignment::STATUS_IN_PROGRESS,
        };

        $workerRole = \App\Models\User::query()->whereKey($userId)->value('role') ?? 'technician';

        $as = MaintenanceAssignment::updateOrCreate(
            ['maintenance_request_id' => $req->id, 'user_id' => $userId],
            [
                'role'    => $workerRole,
                'is_lead' => $isLead,
                'status'  => $status,
            ]
        );

        // ไม่รีเซ็ต assigned_at ทุกครั้ง (ตั้งครั้งแรกเท่านั้น)
        if (empty($as->assigned_at)) {
            $as->assigned_at = now();
        }

        $as->save();
    }


}
