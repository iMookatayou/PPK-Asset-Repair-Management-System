<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;
use App\Events\MaintenanceRequestCreated;
use App\Models\MaintenanceRating;
use App\Models\MaintenanceOperationLog;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceDepartment;
use App\Models\MaintenanceRequestType;
use App\Models\OperationLog;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Asset;
use App\Models\User;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Carbon\Carbon;
use App\Support\Toast;

class MaintenanceRequestController extends Controller
{
    private function writeTransitionLog(MR $req, ?int $actorId, string $action, string $from, string $to, ?string $note = null): void
    {
        MaintenanceLog::create([
            'request_id'  => $req->id,
            'user_id'     => $actorId,
            'action'      => $action,
            'note'        => $note,
            'from_status' => $from,
            'to_status'   => $to,
        ]);
    }

    private function applyStatusTransition(MR $locked, string $toStatus, ?int $actorId, ?string $note = null, string $action = MaintenanceLog::ACTION_TRANSITION): void
    {
        $this->applyTransition($locked, [
            'status' => $toStatus,
            'note'   => $note,
        ], $actorId);
    }

    public function create()
    {
        return $this->createPage();
    }

    public function indexPage(Request $request)
    {
        $user     = Auth::user();
        $userId   = (int) Auth::id();

        $status   = strtolower(trim($request->string('status')->toString()));
        $priority = strtolower(trim($request->string('priority')->toString()));
        $q        = trim($request->string('q')->toString());
        $assetId  = $request->integer('asset_id');

        // NEW: type filter
        $typeId = $request->input('type_id', ''); // อาจเป็น '', '__null__', หรือ id

        // ---- ใช้ helper ดึงค่าการเรียง + จัดการ session ต่อ user ----
        [$sortBy, $sortDir] = $this->resolveSort($request);

        // NEW: dropdown options (Maintenance Types)
        $types = \App\Models\MaintenanceRequestType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $query = MR::query()

            // เพิ่ม join เพื่อเอา my_response_status มาใช้บนหน้า index (เหมือน myJobsPage)
            ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
                $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->where('ma.user_id', '=', $userId);
            })

            // ต้อง select เองเพราะมี join
            ->select([
                'maintenance_requests.*',
                DB::raw('ma.response_status as my_response_status'),
                DB::raw('ma.responded_at as my_responded_at'),
            ])

            // NEW: eager load type relation
            ->with([
                'type', // <<<< สำคัญ
                'asset',
                'reporter:id,name,email',
                'technician:id,name',
                'attachments' => fn($qq) => $qq
                    ->select('id', 'attachable_id', 'attachable_type', 'file_id', 'original_name', 'is_private', 'order_column')
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
            ->when($q !== '', fn($qb) => $qb->search($q))

            // NEW: filter type_id
            ->when($typeId !== '', function ($qb) use ($typeId) {
                if ($typeId === '__null__') {
                    $qb->whereNull('maintenance_requests.type_id');
                } else {
                    $qb->where('maintenance_requests.type_id', (int) $typeId);
                }
            });

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
                // safety: whitelist allowed sort columns
                $allowed = ['request_no', 'id', 'request_date', 'status', 'priority', 'updated_at', 'created_at'];
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

        return view('maintenance.requests.index', compact(
            'list',
            'types',
            'typeId',
            'status',
            'priority',
            'q',
            'sortBy',
            'sortDir'
        ));
    }

    public function showPage(MR $req)
    {
        Gate::authorize('view', $req);

        $viewer = Auth::user();
        Log::info('Viewed maintenance request', [
            'request_id' => $req->id,
            'user_id'    => $viewer?->id,
            'ip_address' => request()->ip(),
        ]);

        $req->loadMissing([
            'type',
            'asset',
            'department',
            'reporter:id,name,email',
            'technician:id,name',
            'assignments' => function ($query) {
                $query->where('status', '!=', \App\Models\MaintenanceAssignment::STATUS_CANCELLED)
                    ->with('user:id,name,role,profile_photo_path,profile_photo_thumb');
            },
            'attachments' => fn($q) => $q->with('file'),
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
            'operationLog.user:id,name',
        ]);

        $techUsers = $this->suggestTechUsersForRequest($req);

        $types = Cache::remember('maintenance_request_types', 3600, function () {
            return MaintenanceRequestType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']);
        });

        $suggestRole = strtolower(trim((string) $req->type?->default_role_code));

        return view('maintenance.requests.show', [
            'req'         => $req,
            'techUsers'   => $techUsers,
            'types'       => $types,
            'suggestRole' => $suggestRole,
        ]);
    }

    public function createPage()
    {
        $assets = \App\Models\Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']);
        $users  = \App\Models\User::orderBy('name')->get(['id', 'name']);
        $depts  = \App\Models\Department::orderBy('name_th')->get(['id', 'code', 'name_th', 'name_en']);

        $types = \App\Models\MaintenanceRequestType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('maintenance.requests.create', compact('assets', 'users', 'depts', 'types'));
    }

    public function index(Request $request)
    {
        $status   = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $q        = trim($request->string('q')->toString());
        $assetId  = $request->integer('asset_id');

        $user = $request->user();

        [$sortBy, $sortDir] = $this->resolveSort($request);

        $query = MR::query()
            ->with(['asset', 'reporter:id,name,email', 'technician:id,name'])

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

        return view('maintenance.requests.index', compact('list', 'status', 'priority', 'q', 'sortBy', 'sortDir'));
    }

    /**
     * API: รายละเอียดใบงานซ่อม (REST: GET /api/repair-requests/{req})
     */
    public function show(Request $request, MR $req)
    {
        Gate::authorize('view', $req);

        $req->loadMissing([
            'type',
            'asset',
            'department',
            'reporter:id,name,email',
            'technician:id,name',
            'assignments' => function ($query) {
                $query->where('status', '!=', \App\Models\MaintenanceAssignment::STATUS_CANCELLED)
                    ->with('user:id,name,role,profile_photo_path,profile_photo_thumb');
            },
            'attachments.file',
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
            'operationLog.user:id,name',
        ]);

        return response()->json([
            'data' => $req,
        ]);
    }

    public function store(Request $request)
    {
        $maxKb        = (int) config('uploads.max_kb', 10240);
        $allowedMimes = config('uploads.mimes', ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'pdf']);

        $rules = [
            'title'          => ['required', 'string', 'max:255'],
            'priority'       => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'asset_id'       => ['nullable', 'integer', 'exists:assets,id'],
            'department_id'  => ['nullable', 'integer', 'exists:departments,id'],
            'type_id'        => ['nullable', 'integer', 'exists:maintenance_request_types,id'],
            'location_text'  => ['nullable', 'string', 'max:255'],
            'reporter_name'  => ['nullable', 'string', 'max:255'],
            'reporter_phone' => ['nullable', 'string', 'max:30'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'files'          => ['nullable', 'array', 'max:3'],
            'files.*'        => ['file', "max:{$maxKb}", 'mimes:' . implode(',', $allowedMimes)],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('toast', \App\Support\Toast::warning($validator->errors()->first(), 3000));
        }

        $data = $validator->validated();

        $user         = $request->user();
        $actorId      = $user?->id;
        $departmentId = $data['department_id'] ?? null;

        if (empty($departmentId) && !empty($data['asset_id'])) {
            $departmentId = Asset::whereKey($data['asset_id'])->value('department_id');

            Log::info('[MaintenanceRequest::store] auto-fill department_id from asset', [
                'asset_id'      => $data['asset_id'],
                'department_id' => $departmentId,
                'actor_id'      => $actorId,
            ]);
        }

        $req = null;

        DB::transaction(function () use ($data, $request, $user, $actorId, $departmentId, &$req) {

            $req = MR::create([
                'title'          => $data['title'],
                'description'    => $data['description'] ?? null,
                'priority'       => $data['priority'],
                'status'         => 'pending',
                'request_date'   => now(),
                'asset_id'       => $data['asset_id'] ?? null,
                'department_id'  => $departmentId,
                'type_id'        => $data['type_id'] ?? null,
                'location_text'  => $data['location_text'] ?? null,
                'reporter_id'    => $user instanceof \App\Models\User ? $user->id : null,
                'reporter_name'  => $data['reporter_name'] ?? ($user instanceof \App\Models\User ? $user->name : null),
                'reporter_email' => $data['reporter_email'] ?? ($user instanceof \App\Models\User ? $user->email : null),
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'technician_id'  => null,
            ]);

            Log::info('[MaintenanceRequest::store] created', [
                'id'            => $req->id,
                'request_no'    => $req->request_no,
                'asset_id'      => $req->asset_id,
                'department_id' => $req->department_id,
                'priority'      => $req->priority,
                'actor_id'      => $actorId,
            ]);

            if ($request->hasFile('files')) {
                foreach ((array) $request->file('files') as $key => $up) {
                    if (!$up || !$up->isValid()) continue;

                    $disk       = 'public';
                    $storedPath = $up->store("maintenance/{$req->id}", $disk);
                    $sha        = hash_file('sha256', $up->getRealPath());

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
                    $captions = (array) $request->input('captions', []);
                    $caption  = $captions[$key] ?? null;

                    $req->attachments()->create([
                        'file_id'       => $file->id,
                        'original_name' => $up->getClientOriginalName(),
                        'extension'     => $up->getClientOriginalExtension() ?: null,
                        'caption'       => $caption,
                        'uploaded_by'   => $actorId,
                        'source'        => 'web',
                        'is_private'    => false,
                        'order_column'  => 0,
                    ]);

                    Log::info('[MaintenanceRequest::store] attached file', [
                        'request_id'    => $req->id,
                        'file_id'       => $file->id,
                        'original_name' => $up->getClientOriginalName(),
                    ]);
                }
            }
        });

        DB::afterCommit(function () use ($req) {
            if (!$req instanceof MR) return;

            broadcast(new MaintenanceRequestCreated([
                'id'         => $req->id,
                'request_no' => $req->request_no ?? null,
                'title'      => $req->title,
                'priority'   => $req->priority,
                'status'     => $req->status,
                'created_at' => $req->created_at?->toIso8601String(),
            ]));
        });

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $req?->load(['type', 'asset', 'attachments.file']),
                'toast' => Toast::success('สร้างคำขอเรียบร้อย', 1800),
            ], 201);
        }

        return redirect()->route('maintenance.requests.show', $req)
            ->with('toast', Toast::success('สร้างคำขอเรียบร้อย', 1800));
    }

    public function update(Request $request, MR $req)
    {
        Gate::authorize('update', $req);

        $user    = $request->user();
        $actorId = $user instanceof \App\Models\User ? $user->id : null;
        $isTeam  = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

        $maxKb     = config('uploads.max_kb', 10240);
        $mimetypes = implode(',', config('uploads.mimes', ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'pdf']));
        $fileRules = ['file', "max:{$maxKb}", 'mimes:' . $mimetypes];

        $rules = [
            'title'           => ['sometimes', 'required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'asset_id'        => ['nullable', 'integer', 'exists:assets,id'],
            'priority'        => ['nullable', \Illuminate\Validation\Rule::in(['low', 'medium', 'high', 'urgent'])],
            'request_date'    => ['nullable', 'date'],
            'reporter_name'   => ['nullable', 'string', 'max:255'],
            'reporter_phone'  => ['nullable', 'string', 'max:30'],
            'reporter_email'  => ['nullable', 'email', 'max:255'],
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'type_id'         => ['nullable', 'integer', 'exists:maintenance_request_types,id'],
            'location_text'   => ['nullable', 'string', 'max:255'],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
            'cost'            => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'files.*'         => $fileRules,
            'technician_id'   => array_values(array_filter([
                'bail',
                \Illuminate\Validation\Rule::prohibitedIf(!$isTeam),
                'nullable',
                'integer',
                'exists:users,id',
            ])),
            'status' => $isTeam
                ? ['nullable', \Illuminate\Validation\Rule::in(['pending', 'acknowledged', 'accepted', 'in_progress', 'on_hold', 'resolved', 'closed', 'cancelled', 'rejected'])]
                : ['nullable', \Illuminate\Validation\Rule::in(['cancelled'])],
            'operation_date'   => ['nullable', 'date'],
            'operation_method' => ['nullable', \Illuminate\Validation\Rule::in(['requisition', 'service_fee', 'other'])],
            'property_code'    => ['nullable', 'string', 'max:100'],
            'require_precheck' => ['nullable', 'boolean'],
            'remark'           => ['nullable', 'string', 'max:5000'],
            'issue_software'   => ['nullable', 'boolean'],
            'issue_hardware'   => ['nullable', 'boolean'],
            'user_ids'         => ['nullable', 'array'],
            'user_ids.*'       => ['integer', 'exists:users,id'],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('toast', \App\Support\Toast::warning($validator->errors()->first(), 3000));
        }

        $data = $validator->validated();

        DB::transaction(function () use (&$data, $request, $req, $isTeam, $actorId) {

            $originalStatus = $req->status;
            $originalTechId = (int) ($req->technician_id ?? 0);

            $incomingTechId = array_key_exists('technician_id', $data)
                ? (int) ($data['technician_id'] ?? 0)
                : $originalTechId;

            $incomingUserIds = $request->input('user_ids', null);
            $forceUpdateTeam = $request->has('update_team_flag') || $request->has('user_ids');

            if ($forceUpdateTeam && empty($incomingUserIds) && !$request->has('technician_id')) {
                $incomingTechId = 0;
            }

            if (!$isTeam) {
                if (($data['status'] ?? null) === 'cancelled') {
                    if (!in_array($req->status, ['pending', 'accepted'], true) || !empty($req->technician_id)) {
                        unset($data['status']);
                    }
                } else {
                    unset($data['status']);
                }

                if (array_key_exists('type_id', $data) && !($req->status === 'pending' && empty($req->technician_id))) {
                    unset($data['type_id']);
                }

                unset(
                    $data['technician_id'],
                    $data['user_ids'],
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

                $incomingTechId = $originalTechId;
            }

            $req->fill($data);

            // auto-assign เมื่อ accepted
            if ($isTeam && ($data['status'] ?? null) === 'accepted' && empty($req->technician_id) && $actorId) {
                $req->technician_id = $actorId;
                $incomingTechId     = $actorId;
            }

            $req->save();

            // timeline
            if (array_key_exists('status', $data) && $originalStatus !== $req->status) {
                $now = now();
                match ($req->status) {
                    'acknowledged' => $req->acknowledged_at ??= $now,
                    'accepted'    => $req->accepted_at ??= $now,
                    'in_progress' => $req->started_at  ??= $now,
                    'on_hold'     => $req->on_hold_at  ??= $now,
                    'resolved'    => $req->resolved_at ??= $now,
                    'closed'      => [$req->closed_at ??= $now, $req->completed_date ??= $now],
                    default       => null,
                };

                if ($req->status === 'accepted' && empty($req->assigned_date)) {
                    $req->assigned_date = $now;
                }
                $req->save();
            }

            $techChanged   = $isTeam && $originalTechId !== $incomingTechId;
            $statusChanged = array_key_exists('status', $data) && $originalStatus !== $req->status;

            if ($isTeam && ($techChanged || $statusChanged || $forceUpdateTeam)) {
                if ($forceUpdateTeam) {
                    $this->syncAssignments($req, $incomingUserIds ?: [], $actorId);
                } elseif ($techChanged || $statusChanged) {
                    $currentTeamIds = $req->assignments()
                        ->where('status', '!=', \App\Models\MaintenanceAssignment::STATUS_CANCELLED)
                        ->pluck('user_id')
                        ->toArray();
                        
                    if ($incomingTechId > 0) {
                        $currentTeamIds = array_filter($currentTeamIds, fn($id) => $id != $incomingTechId);
                        array_unshift($currentTeamIds, $incomingTechId);
                    }
                    
                    $this->syncAssignments($req, array_values($currentTeamIds), $actorId);
                }
            }

            // log
            if (class_exists(MaintenanceLog::class)) {
                MaintenanceLog::create([
                    'request_id'  => $req->id,
                    'action'      => $statusChanged ? MaintenanceLog::ACTION_TRANSITION : MaintenanceLog::ACTION_UPDATE,
                    'note'        => $statusChanged ? $this->defaultNoteForStatus($req->status, $actorId, $req) : null,
                    'user_id'     => $actorId,
                    'from_status' => $statusChanged ? $originalStatus : null,
                    'to_status'   => $statusChanged ? $req->status : null,
                ]);
            }

            // remove attachments
            $toRemove = array_filter((array) $request->input('remove_attachments', []), fn($v) => is_numeric($v));
            if (!empty($toRemove)) {
                $req->attachments()->whereIn('id', $toRemove)->get()->each(fn($att) => $att->deleteAndCleanup(true));
            }

            // upload attachments
            if ($request->hasFile('files')) {
                foreach ((array) $request->file('files') as $key => $up) {
                    if (!$up || !$up->isValid()) continue;

                    $disk       = 'public';
                    $storedPath = $up->store("maintenance/{$req->id}", $disk);
                    $sha        = hash_file('sha256', $up->getRealPath());

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
                    $captions = (array) $request->input('captions', []);
                    $caption  = $captions[$key] ?? null;

                    $req->attachments()->create([
                        'file_id'       => $file->id,
                        'original_name' => $up->getClientOriginalName(),
                        'extension'     => $up->getClientOriginalExtension() ?: null,
                        'caption'       => $caption,
                        'uploaded_by'   => $actorId,
                        'source'        => 'web',
                        'is_private'    => false,
                        'order_column'  => 0,
                    ]);
                }
            }

            // operation log
            $opKeys = ['operation_date', 'operation_method', 'property_code', 'remark', 'require_precheck', 'issue_software', 'issue_hardware'];
            $hasOp  = !empty(array_intersect_key($data, array_flip($opKeys))) || $req->operationLog()->exists();

            if ($hasOp) {
                $opDate = !empty($data['operation_date']) ? Carbon::parse($data['operation_date'])->toDateString() : null;

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

        $req->load(['attachments.file', 'operationLog']);

        return $this->respondWithToast(
            $request,
            Toast::success('อัปเดตคำขอเรียบร้อย', 1600),
            redirect()->route('maintenance.requests.show', $req),
            ['data' => $req]
        );
    }

    // public function queuePage(Request $request)
    // {
    //     \\Gate::authorize('view-repair-dashboard');
    //     $status = (string) $request->string('status');
    //     $q      = (string) $request->string('q');
    //     $just   = (int) $request->query('just');

    //     $base = MR::query()
    //         ->with(['asset','reporter:id,name,email','technician:id,name'])
    //         ->whereIn('status', ['pending','accepted','in_progress','on_hold']);

    //     $list = (clone $base)
    //         ->when($status, fn($qb) => $qb->where('status', $status))
    //         ->when($q, function ($qb) use ($q) {
    //             $qb->where(function ($w) use ($q) {
    //                 $w->where('title','like',"%{$q}%")
    //                     ->orWhere('description','like',"%{$q}%")
    //                     ->orWhere('request_no','like',"%{$q}%")
    //                     ->orWhereHas('reporter', fn($qr) => $qr->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"))
    //                     ->orWhereHas('asset', fn($qa) => $qa->where('name','like',"%{$q}%")->orWhere('asset_code','like',"%{$q}%"));
    //             });
    //         })
    //         ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
    //         ->orderByDesc('request_date')
    //         ->paginate(20)
    //         ->withQueryString();

    //     $stats = [
    //         'total'       => (clone $base)->count(),
    //         'pending'     => (clone $base)->where('status','pending')->count(),
    //         'in_progress' => (clone $base)->where('status','in_progress')->count(),
    //         'completed'   => MR::query()->whereIn('status', ['resolved','closed'])->count(),
    //     ];

    //     return view('repair.queue', compact('list','stats','just'));
    // }

    // public function myJobsPage(Request $request)
    // {
    //     if (Auth::user()?->role === 'member') {
    //         abort(403, 'Unauthorized Access: Members cannot access the My Jobs page.');
    //     }

    //     $userId = (int) Auth::id();

    //     $filter = $request->string('filter')->toString(); // my | available | all
    //     $status = strtolower($request->string('status')->toString()); // filter by MR.status
    //     $tech   = $request->integer('tech');
    //     $q      = $request->string('q')->toString();
    //     $resp   = strtolower($request->string('resp')->toString()); // ma.response_status

    //     if ($filter === '') $filter = 'all';

    //     // // สถานะที่ "ไม่ให้แสดงใน list"
    //     $excludedList = [
    //         MR::STATUS_CLOSED,
    //         MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //     ];

    //     // // whitelist: สถานะใบงานจริง (เพิ่ม acknowledged)
    //     $validReqStatus = [
    //         MR::STATUS_PENDING,
    //         MR::STATUS_ACKNOWLEDGED,
    //         MR::STATUS_ACCEPTED,
    //         MR::STATUS_IN_PROGRESS,
    //         MR::STATUS_ON_HOLD,
    //         MR::STATUS_RESOLVED,
    //         MR::STATUS_CLOSED,
    //         MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //     ];

    //     if ($status !== '' && !in_array($status, $validReqStatus, true)) {
    //         $status = '';
    //     }

    //     // // whitelist: response_status ของฉัน
    //     $validResp = [
    //         MaintenanceAssignment::RESP_PENDING,
    //         MaintenanceAssignment::RESP_ACCEPTED,
    //         MaintenanceAssignment::RESP_ACKNOWLEDGED,
    //         MaintenanceAssignment::RESP_REJECTED,
    //     ];

    //     if ($resp !== '' && !in_array($resp, $validResp, true)) {
    //         $resp = '';
    //     }

    //     // // base query (ใช้ร่วมกับ list และ stats)
    //     // // join assignment ของ user คนนี้เท่านั้น
    //     $base = MR::query()
    //         ->from('maintenance_requests')
    //         ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
    //             $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
    //                 ->where('ma.user_id', '=', $userId);
    //         });

    //     // // LIST QUERY
    //     $query = (clone $base)
    //         ->select([
    //             'maintenance_requests.id',
    //             'maintenance_requests.request_no',
    //             'maintenance_requests.request_date',
    //             'maintenance_requests.title',
    //             'maintenance_requests.description',
    //             'maintenance_requests.status',
    //             'maintenance_requests.priority',
    //             'maintenance_requests.updated_at',
    //             'maintenance_requests.created_at',
    //             'maintenance_requests.asset_id',
    //             'maintenance_requests.department_id',
    //             'maintenance_requests.location_text',
    //             'maintenance_requests.reporter_id',
    //             'maintenance_requests.reporter_name',
    //             'maintenance_requests.reporter_phone',
    //             'maintenance_requests.technician_id',

    //             DB::raw('ma.response_status as my_response_status'),
    //             DB::raw('ma.responded_at as my_responded_at'),
    //             DB::raw('ma.status as my_assignment_status'),
    //         ])
    //         ->with([
    //             'asset:id,name,asset_code',
    //             'department',
    //             'reporter:id,name,email',
    //             'technician:id,name',
    //         ])
    //         ->when($filter === 'my', function ($qb) use ($userId) {
    //             $qb->where(function ($qq) use ($userId) {
    //                 $qq->where('maintenance_requests.technician_id', $userId)
    //                     ->orWhereNotNull('ma.maintenance_request_id');
    //             });
    //         })
    //         ->when($filter === 'available', function ($qb) {
    //             // // งานที่ "พร้อมรับเรื่อง" จริง = acknowledged + ยังไม่มีช่าง
    //             $qb->whereNull('maintenance_requests.technician_id')
    //                 ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
    //         })
    //         ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
    //         ->when($status !== '', fn($qb) => $qb->where('maintenance_requests.status', $status))
    //         ->when($q !== '', fn($qb) => $qb->search($q))
    //         ->when($status === '', fn($qb) => $qb->whereNotIn('maintenance_requests.status', $excludedList))
    //         ->when($resp !== '', function ($qb) use ($resp) {
    //             if ($resp === MaintenanceAssignment::RESP_PENDING) {
    //                 $qb->where(function ($qq) use ($resp) {
    //                     $qq->whereNull('ma.response_status')
    //                         ->orWhere('ma.response_status', $resp);
    //                 });
    //             } else {
    //                 $qb->where('ma.response_status', $resp);
    //             }
    //         })
    //         ->orderByDesc('maintenance_requests.updated_at');

    //     $jobs = $query->paginate(15)->withQueryString();

    //     // // STATS
    //     $statsRow = (clone $base)
    //         ->selectRaw("
    //             SUM(CASE WHEN maintenance_requests.status = 'pending' THEN 1 ELSE 0 END) as pending,
    //             SUM(CASE WHEN maintenance_requests.status IN ('accepted','in_progress') THEN 1 ELSE 0 END) as in_progress,
    //             SUM(CASE WHEN maintenance_requests.status IN ('resolved','closed') THEN 1 ELSE 0 END) as completed
    //         ")
    //         ->when($filter === 'my', function ($qb) use ($userId) {
    //             $qb->where(function ($qq) use ($userId) {
    //                 $qq->where('maintenance_requests.technician_id', $userId)
    //                     ->orWhereNotNull('ma.maintenance_request_id');
    //             });
    //         })
    //         ->when($filter === 'available', function ($qb) {
    //             $qb->whereNull('maintenance_requests.technician_id')
    //                 ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
    //         })
    //         ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
    //         ->when($q !== '', fn($qb) => $qb->search($q))
    //         ->first();

    //     $stats = [
    //         'pending'     => (int)($statsRow->pending ?? 0),
    //         'in_progress' => (int)($statsRow->in_progress ?? 0),
    //         'completed'   => (int)($statsRow->completed ?? 0),
    //     ];

    //     // // Log สำหรับการตรวจสอบการเข้าถึงหน้า Job List
    //     Log::debug('[MaintenanceRequest::myJobsPage] user access', [
    //         'user_id' => $userId,
    //         'filter'  => $filter,
    //         'status'  => $status,
    //         'total'   => $jobs->total(),
    //     ]);

    //     return view('repair.my-jobs', [
    //         'list'   => $jobs,
    //         'filter' => $filter,
    //         'status' => $status,
    //         'tech'   => $tech,
    //         'q'      => $q,
    //         'resp'   => $resp,
    //         'stats'  => $stats,
    //     ]);
    // }

    public function myJobsPage(Request $request)
    {
        if (Auth::user() instanceof \App\Models\User && Auth::user()->role === 'member') {
            abort(403, 'Unauthorized Access: Members cannot access the My Jobs page.');
        }

        $userId = (int) Auth::id();

        $filter = $request->string('filter')->toString();
        $status = strtolower($request->string('status')->toString());
        $tech   = $request->integer('tech');
        $q      = $request->string('q')->toString();
        $resp   = strtolower($request->string('resp')->toString());

        if ($filter === '') $filter = 'all';

        $excludedList = [
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
        ];

        $validReqStatus = [
            MR::STATUS_PENDING,
            MR::STATUS_ACKNOWLEDGED,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD,
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
        ];

        if ($status !== '' && !in_array($status, $validReqStatus, true)) {
            $status = '';
        }

        $validResp = [
            MaintenanceAssignment::RESP_PENDING,
            MaintenanceAssignment::RESP_ACCEPTED,
            MaintenanceAssignment::RESP_ACKNOWLEDGED,
            MaintenanceAssignment::RESP_REJECTED,
        ];

        if ($resp !== '' && !in_array($resp, $validResp, true)) {
            $resp = '';
        }

        $base = MR::query()
            ->from('maintenance_requests')
            ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
                $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->where('ma.user_id', '=', $userId);
            });

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
                'assignments' => fn($q) => $q->where('status', '!=', 'cancelled'),
                'assignments.user:id,name,profile_photo_thumb',
            ])
            ->when($filter === 'my', function ($qb) use ($userId) {
                $qb->where(function ($qq) use ($userId) {
                    $qq->where('maintenance_requests.technician_id', $userId)
                        ->orWhereNotNull('ma.maintenance_request_id');
                });
            })
            ->when($filter === 'available', function ($qb) {
                $qb->whereNull('maintenance_requests.technician_id')
                    ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
            })
            ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($status !== '', fn($qb) => $qb->where('maintenance_requests.status', $status))
            ->when($q !== '', fn($qb) => $qb->search($q))
            ->when($status === '', fn($qb) => $qb->whereNotIn('maintenance_requests.status', $excludedList))
            ->when($resp !== '', function ($qb) use ($resp) {
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
                    ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
            })
            ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($q !== '', fn($qb) => $qb->search($q))
            ->first();

        $stats = [
            'pending'     => (int) ($statsRow->pending ?? 0),
            'in_progress' => (int) ($statsRow->in_progress ?? 0),
            'completed'   => (int) ($statsRow->completed ?? 0),
        ];

        Log::debug('[MaintenanceRequest::myJobsPage] user access', [
            'user_id' => $userId,
            'filter'  => $filter,
            'status'  => $status,
            'total'   => $jobs->total(),
        ]);

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

    public function myJobs(Request $request)
    {
        $user   = $request->user();
        $userId = (int) $request->user()?->id;

        if ($user instanceof \App\Models\User && $user->role === 'member') {
            return response()->json(['message' => 'Unauthorized Access: Members cannot access My Jobs.'], 403);
        }

        $filter = $request->string('filter')->toString();
        $status = strtolower($request->string('status')->toString());
        $tech   = $request->integer('tech');
        $q      = $request->string('q')->toString();
        $resp   = strtolower($request->string('resp')->toString());

        if ($filter === '') $filter = 'all';

        $excludedList = [
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
        ];

        $validReqStatus = [
            MR::STATUS_PENDING,
            MR::STATUS_ACKNOWLEDGED,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD,
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
        ];

        if ($status !== '' && !in_array($status, $validReqStatus, true)) {
            $status = '';
        }

        $validResp = [
            MaintenanceAssignment::RESP_PENDING,
            MaintenanceAssignment::RESP_ACCEPTED,
            MaintenanceAssignment::RESP_ACKNOWLEDGED,
            MaintenanceAssignment::RESP_REJECTED,
        ];

        if ($resp !== '' && !in_array($resp, $validResp, true)) {
            $resp = '';
        }

        $base = MR::query()
            ->from('maintenance_requests')
            ->leftJoin('maintenance_assignments as ma', function ($join) use ($userId) {
                $join->on('ma.maintenance_request_id', '=', 'maintenance_requests.id')
                    ->where('ma.user_id', '=', $userId);
            });

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
                'assignments' => fn($q) => $q->where('status', '!=', 'cancelled'),
                'assignments.user:id,name,profile_photo_thumb',
            ])
            ->when($filter === 'my', function ($qb) use ($userId) {
                $qb->where(function ($qq) use ($userId) {
                    $qq->where('maintenance_requests.technician_id', $userId)
                        ->orWhereNotNull('ma.maintenance_request_id');
                });
            })
            ->when($filter === 'available', function ($qb) {
                $qb->whereNull('maintenance_requests.technician_id')
                    ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
            })
            ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($status !== '', fn($qb) => $qb->where('maintenance_requests.status', $status))
            ->when($q !== '', fn($qb) => $qb->search($q))
            ->when($status === '', fn($qb) => $qb->whereNotIn('maintenance_requests.status', $excludedList))
            ->when($resp !== '', function ($qb) use ($resp) {
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
                    ->where('maintenance_requests.status', MR::STATUS_ACKNOWLEDGED);
            })
            ->when(!empty($tech), fn($qb) => $qb->where('maintenance_requests.technician_id', $tech))
            ->when($q !== '', fn($qb) => $qb->search($q))
            ->first();

        $stats = [
            'pending'     => (int) ($statsRow->pending ?? 0),
            'in_progress' => (int) ($statsRow->in_progress ?? 0),
            'completed'   => (int) ($statsRow->completed ?? 0),
        ];

        Log::debug('[MaintenanceRequest::myJobs] user access', [
            'user_id' => $userId,
            'filter'  => $filter,
            'status'  => $status,
            'total'   => $jobs->total(),
        ]);

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
                'last_page'    => $jobs->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function acknowledgeCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('acknowledge', $req);

            DB::transaction(function () use ($req, $actorId) {
                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== MR::STATUS_PENDING) {
                    abort(409, 'ต้องอยู่สถานะ "รอรับทราบ" เท่านั้น');
                }

                if (!empty($locked->technician_id)) {
                    abort(409, 'งานนี้ถูกมอบหมายแล้ว');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_ACKNOWLEDGED,
                    $actorId,
                    'รับทราบแล้ว'
                );

                Log::info('[MaintenanceRequest::acknowledgeCase] success', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('รับทราบแล้ว', 1800),
                back(),
                ['message' => 'รับทราบแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์รับทราบรายการนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์รับทราบรายการนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::acknowledgeCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการรับทราบ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function rejectCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     $data = $request->validate([
    //         'remark' => ['nullable', 'string', 'max:2000'],
    //     ]);

    //     $remark = trim((string) ($data['remark'] ?? ''));
    //     if ($remark === '') $remark = 'ช่างไม่รับเรื่อง';

    //     try {
    //         Gate::authorize('reject', $req);

    //         DB::transaction(function () use ($req, $actorId, $remark) {

    //             // // ล็อกข้อมูลใบงานเพื่อป้องกันการเปลี่ยนสถานะซ้อนกัน
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             if (in_array($locked->status, [
    //                 MR::STATUS_RESOLVED,
    //                 MR::STATUS_CLOSED,
    //                 MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //                 MR::STATUS_REJECTED,
    //             ], true)) {
    //                 abort(409, 'งานปิด/ยกเลิก/ไม่รับเรื่องแล้ว');
    //             }

    //             if ($locked->status !== MR::STATUS_ACKNOWLEDGED) {
    //                 abort(409, 'ต้องอยู่สถานะ “รับทราบแล้ว” เท่านั้นจึงจะไม่รับเรื่องได้');
    //             }

    //             $now = now();

    //             // // ดึง Role ของผู้กดเพื่อบันทึกลงใน Assignment
    //             $workerRole = User::query()
    //                 ->whereKey($actorId)
    //                 ->value('role') ?? 'tech';

    //             // // 1) บันทึกประวัติการปฏิเสธ (Reject) ลงในตาราง Assignment ของผู้กด
    //             $assign = MaintenanceAssignment::query()
    //                 ->where('maintenance_request_id', $locked->id)
    //                 ->where('user_id', $actorId)
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (!$assign) {
    //                 MaintenanceAssignment::query()->create([
    //                     'maintenance_request_id' => $locked->id,
    //                     'user_id'                => $actorId,
    //                     'role'                   => $workerRole,
    //                     'is_lead'                => false,
    //                     'assigned_at'            => $now,
    //                     'response_status'        => MaintenanceAssignment::RESP_REJECTED,
    //                     'responded_at'           => $now,
    //                     'remark'                 => $remark,
    //                     'status'                 => MaintenanceAssignment::STATUS_CANCELLED,
    //                 ]);
    //             } else {
    //                 $assign->forceFill([
    //                     'role'            => $assign->role ?: $workerRole,
    //                     'response_status' => MaintenanceAssignment::RESP_REJECTED,
    //                     'responded_at'    => $now,
    //                     'remark'          => $remark,
    //                     'status'          => MaintenanceAssignment::STATUS_CANCELLED,
    //                     'is_lead'         => false,
    //                 ])->save();
    //             }

    //             // // 2) เปลี่ยนสถานะใบงานหลักเป็น Rejected
    //             $from = (string) $locked->status;

    //             $locked->forceFill([
    //                 'status'            => MR::STATUS_REJECTED,
    //                 'status_updated_at' => $now,
    //                 'status_updated_by' => $actorId,
    //             ])->save();

    //             // // 3) ปิดสถานะ Assignment ของคนอื่นๆ ที่เกี่ยวข้องกับใบงานนี้เพื่อป้องกันงานค้าง
    //             MaintenanceAssignment::query()
    //                 ->where('maintenance_request_id', $locked->id)
    //                 ->where('user_id', '!=', $actorId)
    //                 ->update([
    //                     'status'     => MaintenanceAssignment::STATUS_CANCELLED,
    //                     'is_lead'    => false,
    //                     'updated_at' => $now,
    //                 ]);

    //             // // 4) บันทึก Log การเปลี่ยนสถานะ (Transition Log)
    //             $this->writeTransitionLog(
    //                 $locked,
    //                 $actorId,
    //                 MaintenanceLog::ACTION_TRANSITION,
    //                 $from,
    //                 MR::STATUS_REJECTED,
    //                 'ไม่รับเรื่อง: ' . $remark
    //             );

    //             // // Log ความสำเร็จระดับระบบ
    //             Log::info('[MaintenanceRequest::rejectCase] success', [
    //                 'mr_id'    => $locked->id,
    //                 'actor_id' => $actorId,
    //                 'remark'   => $remark
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('บันทึกการไม่รับเรื่องเรียบร้อยแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์ไม่รับเรื่องรายการนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // // บันทึก Error Log เมื่อเกิดปัญหาที่ไม่คาดคิด
    //         Log::error('[MaintenanceRequest::rejectCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการทำรายการ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function rejectCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        $data = $request->validate([
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        $remark = trim((string) ($data['remark'] ?? ''));
        if ($remark === '') $remark = 'ช่างไม่รับเรื่อง';

        try {
            Gate::authorize('reject', $req);

            DB::transaction(function () use ($req, $actorId, $remark) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                    MR::STATUS_REJECTED,
                ], true)) {
                    abort(409, 'งานปิด/ยกเลิก/ไม่รับเรื่องแล้ว');
                }

                if ($locked->status !== MR::STATUS_ACKNOWLEDGED) {
                    abort(409, 'ต้องอยู่สถานะ "รับทราบแล้ว" เท่านั้นจึงจะไม่รับเรื่องได้');
                }

                $now        = now();
                $workerRole = User::query()->whereKey($actorId)->value('role') ?? 'tech';

                $assign = MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', $actorId)
                    ->lockForUpdate()
                    ->first();

                if (!$assign) {
                    MaintenanceAssignment::query()->create([
                        'maintenance_request_id' => $locked->id,
                        'user_id'                => $actorId,
                        'role'                   => $workerRole,
                        'is_lead'                => false,
                        'assigned_at'            => $now,
                        'response_status'        => MaintenanceAssignment::RESP_REJECTED,
                        'responded_at'           => $now,
                        'remark'                 => $remark,
                        'status'                 => MaintenanceAssignment::STATUS_CANCELLED,
                    ]);
                } else {
                    $assign->forceFill([
                        'role'            => $assign->role ?: $workerRole,
                        'response_status' => MaintenanceAssignment::RESP_REJECTED,
                        'responded_at'    => $now,
                        'remark'          => $remark,
                        'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                        'is_lead'         => false,
                    ])->save();
                }

                $from = (string) $locked->status;

                $locked->forceFill([
                    'status'            => MR::STATUS_REJECTED,
                    'status_updated_at' => $now,
                    'status_updated_by' => $actorId,
                ])->save();

                MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', '!=', $actorId)
                    ->update([
                        'status'     => MaintenanceAssignment::STATUS_CANCELLED,
                        'is_lead'    => false,
                        'updated_at' => $now,
                    ]);

                $this->writeTransitionLog(
                    $locked,
                    $actorId,
                    MaintenanceLog::ACTION_TRANSITION,
                    $from,
                    MR::STATUS_REJECTED,
                    'ไม่รับเรื่อง: ' . $remark
                );

                Log::info('[MaintenanceRequest::rejectCase] success', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                    'remark'   => $remark,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('บันทึกการไม่รับเรื่องเรียบร้อยแล้ว', 1800),
                back(),
                ['message' => 'บันทึกการไม่รับเรื่องเรียบร้อยแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์ไม่รับเรื่องรายการนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์ไม่รับเรื่องรายการนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::rejectCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการทำรายการ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function acceptJobQuick(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     $data = $request->validate([
    //         'decision'      => ['required', 'in:accepted,in_progress'],
    //         'technician_id' => ['nullable', 'integer'], // // จะใช้จริงเฉพาะทีมเท่านั้น
    //         'note'          => ['nullable', 'string', 'max:2000'],
    //     ]);

    //     $note = trim((string) ($data['note'] ?? ''));

    //     try {
    //         Gate::authorize('accept', $req);

    //         $user = $request->user();
    //         $isTeam = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

    //         DB::transaction(function () use ($req, $actorId, $data, $note, $isTeam) {

    //             // // ล็อกแถวข้อมูลเพื่อป้องกัน Race Condition
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             if (in_array($locked->status, [
    //                 MR::STATUS_RESOLVED,
    //                 MR::STATUS_CLOSED,
    //                 MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //             ], true)) {
    //                 abort(409, 'งานปิด/ยกเลิกแล้ว ไม่สามารถดำเนินการได้');
    //             }

    //             // // ถ้าไม่ใช่กลุ่มทีม ห้ามเลือกช่างคนอื่น (บังคับเป็นตัวเอง)
    //             $techId = $isTeam
    //                 ? (int) ($data['technician_id'] ?? $actorId)
    //                 : $actorId;

    //             // // ป้องกันการรับงานซ้อนถ้ามีคนรับไปก่อนแล้ว
    //             if (!empty($locked->technician_id) && (int) $locked->technician_id !== $techId) {
    //                 abort(409, 'มีผู้รับงานแล้ว');
    //             }

    //             // // ขั้นตอนที่ 1: เปลี่ยนจาก Pending เป็น Acknowledged (ถ้าจำเป็น)
    //             if ($locked->status === MR::STATUS_PENDING) {
    //                 $this->applyStatusTransition(
    //                     $locked,
    //                     MR::STATUS_ACKNOWLEDGED,
    //                     $actorId,
    //                     'Quick: รับทราบ'
    //                 );
    //             }

    //             // // ขั้นตอนที่ 2: เปลี่ยนเป็น Accepted (ต้องระบุช่างหลัก)
    //             if ($locked->status === MR::STATUS_ACKNOWLEDGED) {
    //                 $locked->technician_id = $techId;

    //                 $this->applyStatusTransition(
    //                     $locked,
    //                     MR::STATUS_ACCEPTED,
    //                     $actorId,
    //                     $note !== '' ? $note : 'Quick: รับเรื่อง'
    //                 );
    //             }

    //             // // ตรวจสอบความถูกต้องของสถานะก่อนไปต่อ
    //             if ($data['decision'] === 'accepted') {
    //                 if ($locked->status !== MR::STATUS_ACCEPTED) {
    //                     abort(409, 'สถานะไม่ถูกต้องสำหรับการรับเรื่องแบบ Quick');
    //                 }
    //             }

    //             // // ขั้นตอนที่ 3: เปลี่ยนเป็น In Progress (ถ้าเลือกเริ่มงานทันที)
    //             if ($data['decision'] === 'in_progress') {
    //                 if ($locked->status !== MR::STATUS_ACCEPTED) {
    //                     abort(409, 'สถานะไม่ถูกต้องสำหรับเริ่มงาน');
    //                 }

    //                 $this->applyStatusTransition(
    //                     $locked,
    //                     MR::STATUS_IN_PROGRESS,
    //                     $actorId,
    //                     $note !== '' ? $note : 'Quick: เริ่มดำเนินการ'
    //                 );
    //             }

    //             // // ขั้นตอนที่ 4: Sync ข้อมูลลงตาราง Assignment
    //             $now = now();

    //             $workerRole = User::query()
    //                 ->whereKey($techId)
    //                 ->value('role') ?? 'tech';

    //             $assign = MaintenanceAssignment::query()
    //                 ->where('maintenance_request_id', $locked->id)
    //                 ->where('user_id', $techId)
    //                 ->lockForUpdate()
    //                 ->first();

    //             // // กำหนดสถานะ Assignment ให้สอดคล้องกับผลลัพธ์
    //             $assignStatus = MaintenanceAssignment::STATUS_IN_PROGRESS;

    //             if (!$assign) {
    //                 MaintenanceAssignment::query()->create([
    //                     'maintenance_request_id' => $locked->id,
    //                     'user_id'                => $techId,
    //                     'role'                   => $workerRole,
    //                     'is_lead'                => true,
    //                     'assigned_at'            => $now,
    //                     'response_status'        => MaintenanceAssignment::RESP_ACCEPTED,
    //                     'responded_at'           => $now,
    //                     'status'                 => $assignStatus,
    //                 ]);
    //             } else {
    //                 $assign->forceFill([
    //                     'role'            => $assign->role ?: $workerRole,
    //                     'response_status' => MaintenanceAssignment::RESP_ACCEPTED,
    //                     'responded_at'    => $now,
    //                     'is_lead'         => true,
    //                     'status'          => $assignStatus,
    //                 ])->save();
    //             }

    //             // // Log ความสำเร็จระดับ Transaction
    //             Log::info('[MaintenanceRequest::acceptJobQuick] success', [
    //                 'mr_id'    => $locked->id,
    //                 'tech_id'  => $techId,
    //                 'decision' => $data['decision']
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('บันทึกเรียบร้อย', 1800));
    //     } catch (\Throwable $e) {
    //         // // Log ข้อผิดพลาดพร้อมรายละเอียด
    //         Log::error('[MaintenanceRequest::acceptJobQuick] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการทำรายการ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function acceptCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            // ตรวจสอบสิทธิ์การกดรับเรื่อง
            Gate::authorize('accept', $req);

            DB::transaction(function () use ($req, $actorId) {

                // ล็อก Record เพื่อความปลอดภัยในการแก้ไขข้อมูลพร้อมกัน (Pessimistic Locking)
                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // งานจบแล้ว/ยกเลิกแล้ว/ไม่รับเรื่องแล้ว ห้ามรับเรื่อง
                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                    MR::STATUS_REJECTED,
                ], true)) {
                    abort(409, 'งานนี้อยู่ในสถานะที่รับเรื่องไม่ได้');
                }

                // Workflow บังคับ: ต้องผ่านการ Acknowledged ก่อนเท่านั้นจึงจะ Accepted ได้
                if ($locked->status !== MR::STATUS_ACKNOWLEDGED) {
                    abort(409, 'ต้องอยู่สถานะ “รับทราบแล้ว” เท่านั้นจึงจะรับเรื่องได้');
                }

                // กันแย่งงาน: ตรวจสอบว่ามีช่างคนอื่นรับหน้าที่ช่างหลัก (Lead) ไปแล้วหรือยัง
                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(409, 'มีผู้รับเรื่องแล้ว');
                }

                // บันทึกตัวผู้กดเป็นช่างหลักของใบงานนี้
                if (empty($locked->technician_id)) {
                    $locked->technician_id = $actorId;
                }

                // บันทึกการเปลี่ยนสถานะและเก็บประวัติ Log
                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_ACCEPTED,
                    $actorId,
                    'รับเรื่องแล้ว'
                );

                // (syncAssignments ถูกจัดการใน applyTransition แล้ว)

                // Log ความสำเร็จในการรับงาน
                Log::info('[MaintenanceRequest::acceptCase] job accepted', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('รับเรื่องแล้ว', 1800),
                back(),
                ['message' => 'รับเรื่องแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // กรณี Gate ไม่ให้ผ่าน
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์รับเรื่องรายการนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์รับเรื่องรายการนี้'],
                403
            );
        } catch (\Throwable $e) {
            // Log ข้อผิดพลาดที่เกิดขึ้น
            Log::error('[MaintenanceRequest::acceptCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            // จัดการข้อความ Error ของ Toast
            $code = (int) $e->getCode();

            // กรณีใช้ abort(409, '...') ตัว HTTP Code บางครั้งอาจไปอยู่ใน getStatusCode()
            // แต่ยังคง Logic เดิมของคุณไว้เพื่อไม่ให้กระทบระบบอื่น
            $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการรับเรื่อง';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg]
            );
        }
    }

    public function acceptJobQuick(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        $data = $request->validate([
            'decision'      => ['required', 'in:accepted,in_progress'],
            'technician_id' => ['nullable', 'integer'],
            'note'          => ['nullable', 'string', 'max:2000'],
        ]);

        $note = trim((string) ($data['note'] ?? ''));

        try {
            Gate::authorize('accept', $req);

            $user   = $request->user();
            $isTeam = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

            DB::transaction(function () use ($req, $actorId, $data, $note, $isTeam) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                ], true)) {
                    abort(409, 'งานปิด/ยกเลิกแล้ว ไม่สามารถดำเนินการได้');
                }

                $techId = $isTeam
                    ? (int) ($data['technician_id'] ?? $actorId)
                    : $actorId;

                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $techId) {
                    abort(409, 'มีผู้รับงานแล้ว');
                }

                if ($locked->status === MR::STATUS_PENDING) {
                    $this->applyStatusTransition(
                        $locked,
                        MR::STATUS_ACKNOWLEDGED,
                        $actorId,
                        'Quick: รับทราบ'
                    );
                }

                if ($locked->status === MR::STATUS_ACKNOWLEDGED) {
                    $locked->technician_id = $techId;

                    $this->applyStatusTransition(
                        $locked,
                        MR::STATUS_ACCEPTED,
                        $actorId,
                        $note !== '' ? $note : 'Quick: รับเรื่อง'
                    );
                }

                if ($data['decision'] === 'accepted') {
                    if ($locked->status !== MR::STATUS_ACCEPTED) {
                        abort(409, 'สถานะไม่ถูกต้องสำหรับการรับเรื่องแบบ Quick');
                    }
                }

                if ($data['decision'] === 'in_progress') {
                    if ($locked->status !== MR::STATUS_ACCEPTED) {
                        abort(409, 'สถานะไม่ถูกต้องสำหรับเริ่มงาน');
                    }

                    $this->applyStatusTransition(
                        $locked,
                        MR::STATUS_IN_PROGRESS,
                        $actorId,
                        $note !== '' ? $note : 'Quick: เริ่มดำเนินการ'
                    );
                }

                $now        = now();
                $workerRole = User::query()->whereKey($techId)->value('role') ?? 'tech';

                $assign = MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->where('user_id', $techId)
                    ->lockForUpdate()
                    ->first();

                $assignStatus = MaintenanceAssignment::STATUS_IN_PROGRESS;

                if (!$assign) {
                    MaintenanceAssignment::query()->create([
                        'maintenance_request_id' => $locked->id,
                        'user_id'                => $techId,
                        'role'                   => $workerRole,
                        'is_lead'                => true,
                        'assigned_at'            => $now,
                        'response_status'        => MaintenanceAssignment::RESP_ACCEPTED,
                        'responded_at'           => $now,
                        'status'                 => $assignStatus,
                    ]);
                } else {
                    $assign->forceFill([
                        'role'            => $assign->role ?: $workerRole,
                        'response_status' => MaintenanceAssignment::RESP_ACCEPTED,
                        'responded_at'    => $now,
                        'is_lead'         => true,
                        'status'          => $assignStatus,
                    ])->save();
                }

                Log::info('[MaintenanceRequest::acceptJobQuick] success', [
                    'mr_id'    => $locked->id,
                    'tech_id'  => $techId,
                    'decision' => $data['decision'],
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('บันทึกเรียบร้อย', 1800),
                back(),
                ['message' => 'บันทึกเรียบร้อย']
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::acceptJobQuick] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการทำรายการ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function startCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     try {
    //         // เช็คสิทธิ์การเริ่มงาน
    //         Gate::authorize('startWork', $req);

    //         DB::transaction(function () use ($req, $actorId) {

    //             // ล็อกข้อมูลแถวนี้ไว้เพื่อป้องกันการเปลี่ยนสถานะพร้อมกัน
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             // ตรวจสอบว่าต้องผ่านขั้นตอนการรับเรื่อง (Accepted) มาก่อนแล้วเท่านั้น
    //             if ($locked->status !== MR::STATUS_ACCEPTED) {
    //                 abort(409, 'ต้องอยู่สถานะรับเรื่องแล้วเท่านั้น');
    //             }

    //             // ตรวจสอบว่าผู้ที่กดเริ่มงานคือช่างหลักที่รับผิดชอบงานนี้
    //             if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
    //                 abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
    //             }

    //             // เปลี่ยนสถานะเป็น In Progress (Helper จัดการ timeline + log ให้)
    //             $this->applyStatusTransition(
    //                 $locked,
    //                 MR::STATUS_IN_PROGRESS,
    //                 $actorId,
    //                 'เริ่มดำเนินการ'
    //             );

    //             // ปรับปรุงข้อมูลในตาราง Assignment ให้สถานะตรงกับใบงานหลัก
    //             $this->syncAssignments($locked, [$actorId], $actorId);

    //             // Log เพื่อยืนยันว่าเริ่มงานสำเร็จในระดับระบบ
    //             Log::info('[MaintenanceRequest::startCase] work started', [
    //                 'mr_id'    => $locked->id,
    //                 'actor_id' => $actorId
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('เริ่มดำเนินการแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         // กรณี Gate ไม่ให้ผ่าน
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์เริ่มงานนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // บันทึก Log ข้อผิดพลาดที่เกิดขึ้นพร้อม Context ข้อมูล
    //         Log::error('[MaintenanceRequest::startCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         // จัดการข้อความ Error ของ Toast
    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการเริ่มดำเนินการ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function startCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('startWork', $req);

            DB::transaction(function () use ($req, $actorId) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== MR::STATUS_ACCEPTED) {
                    abort(409, 'ต้องอยู่สถานะรับเรื่องแล้วเท่านั้น');
                }

                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_IN_PROGRESS,
                    $actorId,
                    'เริ่มดำเนินการ'
                );

                // (syncAssignments ถูกจัดการใน applyTransition แล้ว)

                Log::info('[MaintenanceRequest::startCase] work started', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('เริ่มดำเนินการแล้ว', 1800),
                back(),
                ['message' => 'เริ่มดำเนินการแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์เริ่มงานนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์เริ่มงานนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::startCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการเริ่มดำเนินการ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function holdCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     try {
    //         // เช็คสิทธิ์การกดพักงาน
    //         Gate::authorize('hold', $req);

    //         DB::transaction(function () use ($req, $actorId) {

    //             // ล็อก Record เพื่อป้องกันข้อมูลคลาดเคลื่อนระหว่างทำรายการ
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             // ตรวจสอบสถานะ: พักงานได้เมื่อรับเรื่องแล้วหรือกำลังดำเนินการเท่านั้น
    //             if (!in_array($locked->status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS], true)) {
    //                 abort(409, 'พักงานได้เมื่อรับเรื่องแล้วหรือกำลังดำเนินการเท่านั้น');
    //             }

    //             // ตรวจสอบสิทธิ์ผู้รับผิดชอบงาน (ป้องกัน Policy หลุด)
    //             if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
    //                 abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
    //             }

    //             // เปลี่ยนสถานะเป็น On Hold (Helper จัดการ on_hold_at และ Log เรียบร้อยแล้ว)
    //             $this->applyStatusTransition(
    //                 $locked,
    //                 MR::STATUS_ON_HOLD,
    //                 $actorId,
    //                 'พักไว้ก่อน'
    //             );

    //             // ปรับปรุงข้อมูล Assignment ให้สอดคล้องกับสถานะงานหลัก
    //             $this->syncAssignments($locked, [$actorId], $actorId);

    //             // Log ความสำเร็จในการพักงาน
    //             Log::info('[MaintenanceRequest::holdCase] case on hold', [
    //                 'mr_id'    => $locked->id,
    //                 'actor_id' => $actorId
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('พักงานไว้ก่อนแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         // กรณี Gate ไม่ให้ผ่าน
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์พักงานนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // บันทึกข้อผิดพลาดที่เกิดขึ้นพร้อม Context ข้อมูล
    //         Log::error('[MaintenanceRequest::holdCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         // จัดการข้อความ Error ของ Toast
    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการพักงาน';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function holdCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('hold', $req);

            DB::transaction(function () use ($req, $actorId, $request) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($locked->status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS], true)) {
                    abort(409, 'พักงานได้เมื่อรับเรื่องแล้วหรือกำลังดำเนินการเท่านั้น');
                }

                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_ON_HOLD,
                    $actorId,
                    $request->note ?: 'พักชั่วคราว'
                );

                // (syncAssignments ถูกจัดการใน applyTransition แล้ว)

                Log::info('[MaintenanceRequest::holdCase] case on hold', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('พักชั่วคราวแล้ว', 1800),
                back(),
                ['message' => 'พักชั่วคราวแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์พักงานนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์พักงานนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::holdCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการพักงาน';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function resumeCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     try {
    //         // เช็คสิทธิ์การดำเนินการต่อ
    //         Gate::authorize('resume', $req);

    //         DB::transaction(function () use ($req, $actorId) {

    //             // ล็อก Record เพื่อความถูกต้องของข้อมูลระหว่าง Transaction
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             // ต้องอยู่ในสถานะ "พักไว้ก่อน" เท่านั้นถึงจะกดดำเนินการต่อได้
    //             if ($locked->status !== MR::STATUS_ON_HOLD) {
    //                 abort(409, 'ต้องอยู่สถานะพักไว้ก่อนเท่านั้น');
    //             }

    //             // ตรวจสอบว่าผู้กดคือช่างหลักที่รับผิดชอบงานนี้
    //             if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
    //                 abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
    //             }

    //             // กลับสู่สถานะ In Progress (Helper จัดการประวัติและเวลาให้โดยอัตโนมัติ)
    //             $this->applyStatusTransition(
    //                 $locked,
    //                 MR::STATUS_IN_PROGRESS,
    //                 $actorId,
    //                 'ดำเนินการต่อ'
    //             );

    //             // ซิงค์สถานะในตาราง Assignment ของช่างให้กลับมาเป็น In Progress
    //             $this->syncAssignments($locked, [$actorId], $actorId);

    //             // Log ความสำเร็จในการกลับมาทำรายการต่อ
    //             Log::info('[MaintenanceRequest::resumeCase] case resumed', [
    //                 'mr_id'    => $locked->id,
    //                 'actor_id' => $actorId
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('กลับมาดำเนินการต่อแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         // กรณี Gate ไม่ให้ผ่าน
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์ดำเนินการต่อรายการนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // บันทึกข้อผิดพลาดกรณีเกิด Exception
    //         Log::error('[MaintenanceRequest::resumeCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         // จัดการข้อความ Error ของ Toast
    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการดำเนินการต่อ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function resumeCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('resume', $req);

            DB::transaction(function () use ($req, $actorId) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== MR::STATUS_ON_HOLD) {
                    abort(409, 'ต้องอยู่สถานะพักชั่วคราวเท่านั้น');
                }

                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_IN_PROGRESS,
                    $actorId,
                    'ดำเนินการต่อ'
                );

                // (syncAssignments ถูกจัดการใน applyTransition แล้ว)

                Log::info('[MaintenanceRequest::resumeCase] case resumed', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('กลับมาดำเนินการต่อแล้ว', 1800),
                back(),
                ['message' => 'กลับมาดำเนินการต่อแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์ดำเนินการต่อรายการนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์ดำเนินการต่อรายการนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::resumeCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการดำเนินการต่อ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function resolveCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     try {
    //         Gate::authorize('resolve', $req);

    //         DB::transaction(function () use ($req, $actorId) {

    //             // ล็อกข้อมูลเพื่อป้องกันการเปลี่ยนสถานะซ้ำซ้อนขณะบันทึกปิดงาน
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             if ($locked->status !== MR::STATUS_IN_PROGRESS) {
    //                 abort(409, 'ต้องอยู่สถานะกำลังดำเนินการเท่านั้น');
    //             }

    //             // ตรวจสอบสิทธิ์ช่างผู้รับผิดชอบ (Double Check จาก Policy)
    //             if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
    //                 abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
    //             }

    //             // เปลี่ยนสถานะเป็น Resolved (Helper จะบันทึก resolved_at และ Log ประวัติให้โดยอัตโนมัติ)
    //             $this->applyStatusTransition(
    //                 $locked,
    //                 MR::STATUS_RESOLVED,
    //                 $actorId,
    //                 'ซ่อมเสร็จ'
    //             );

    //             // ปรับสถานะในตาราง Assignment ให้สอดคล้องกับงานที่ทำเสร็จแล้ว
    //             $this->syncAssignments($locked, [$actorId], $actorId);

    //             // Log ความสำเร็จในการซ่อมเสร็จเพื่อใช้เป็น Timeline ในระบบ
    //             Log::info('[MaintenanceRequest::resolveCase] case resolved', [
    //                 'mr_id'    => $locked->id,
    //                 'actor_id' => $actorId
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('บันทึกซ่อมเสร็จแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์บันทึกซ่อมเสร็จรายการนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // บันทึก Error Log กรณีเกิดข้อผิดพลาดทางเทคนิค
    //         Log::error('[MaintenanceRequest::resolveCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการบันทึกซ่อมเสร็จ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function resolveCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('resolve', $req);

            DB::transaction(function () use ($req, $actorId) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== MR::STATUS_IN_PROGRESS) {
                    abort(409, 'ต้องอยู่สถานะกำลังดำเนินการเท่านั้น (หากพักค้างไว้ต้องกดดำเนินการต่อก่อน)');
                }

                if (!empty($locked->technician_id) && (int) $locked->technician_id !== $actorId) {
                    abort(403, 'คุณไม่ใช่ผู้รับผิดชอบงานนี้');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_RESOLVED,
                    $actorId,
                    'ซ่อมเสร็จ'
                );

                // (syncAssignments ถูกจัดการใน applyTransition แล้ว)

                Log::info('[MaintenanceRequest::resolveCase] case resolved', [
                    'mr_id'    => $locked->id,
                    'actor_id' => $actorId,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('บันทึกซ่อมเสร็จแล้ว', 1800),
                back(),
                ['message' => 'บันทึกซ่อมเสร็จแล้ว']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์บันทึกซ่อมเสร็จรายการนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์บันทึกซ่อมเสร็จรายการนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::resolveCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการบันทึกซ่อมเสร็จ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function cancelCase(Request $request, MR $req)
    // {
    //     Log::info('[MaintenanceRequest::cancelCase] ENTER', [
    //         'mr_id'   => $req->id,
    //         'user_id' => Auth::id(),
    //         'status'  => $req->status,
    //     ]);

    //     $data = $request->validate([
    //         'reason' => ['nullable', 'string', 'max:255'],
    //     ]);

    //     $actorId = (int) Auth::id();
    //     $reason = trim((string) ($data['reason'] ?? ''));
    //     if ($reason === '') $reason = 'ยกเลิกใบงาน';

    //     try {
    //         DB::transaction(function () use ($req, $actorId, $reason) {

    //             // ล็อกแถวข้อมูลเพื่อป้องกันการเปลี่ยนสถานะซ้อนกัน
    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             // ตรวจสอบว่าใบงานไม่ได้ถูกปิดหรือยกเลิกไปก่อนหน้าแล้ว
    //             if (in_array($locked->status, [
    //                 MR::STATUS_RESOLVED,
    //                 MR::STATUS_CLOSED,
    //                 MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //             ], true)) {
    //                 abort(409, 'งานนี้อยู่ในสถานะที่ทำรายการไม่ได้');
    //             }

    //             // ตรวจสอบสิทธิ์แยกตามประเภทผู้ใช้งาน (Reporter หรือ Technician)
    //             $byReporter = Gate::check('cancelByReporter', $locked);

    //             if ($byReporter) {
    //                 Gate::authorize('cancelByReporter', $locked);

    //                 $this->applyStatusTransition(
    //                     $locked,
    //                     MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //                     $actorId,
    //                     'ยกเลิกซ่อม: ' . $reason
    //                 );
    //             } else {
    //                 // หากไม่ใช่ผู้แจ้งซ่อม ต้องตรวจสอบสิทธิ์ในฐานะช่าง/แอดมิน
    //                 Gate::authorize('cancelByTech', $locked);

    //                 $this->applyStatusTransition(
    //                     $locked,
    //                     MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
    //                     $actorId,
    //                     'ช่างยกเลิกงาน: ' . $reason
    //                 );
    //             }

    //             // ปิดสถานะ Assignment ของทุกคนเพื่อเคลียร์งานค้างในระบบ
    //             // (ในกรณียกเลิกงาน เราจะไม่อัปเดตด้วย syncAssignments แต่จะอัปเดตตารางตรงๆ แบบนี้ถูกต้องแล้ว)
    //             MaintenanceAssignment::query()
    //                 ->where('maintenance_request_id', $locked->id)
    //                 ->update([
    //                     'status'     => MaintenanceAssignment::STATUS_CANCELLED,
    //                     'is_lead'    => false,
    //                     'updated_at' => now(),
    //                 ]);

    //             Log::info('[MaintenanceRequest::cancelCase] success', [
    //                 'mr_id'       => $locked->id,
    //                 'actor_id'    => $actorId,
    //                 'by_reporter' => $byReporter
    //             ]);
    //         });

    //         return back()->with('toast', Toast::success('ยกเลิกใบงานเรียบร้อย', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         return back()->with('toast', Toast::warning('คุณไม่มีสิทธิ์ยกเลิกใบงานนี้', 2200));
    //     } catch (\Throwable $e) {
    //         // บันทึก Log เมื่อเกิดข้อผิดพลาดในการยกเลิก
    //         Log::error('[MaintenanceRequest::cancelCase] failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการทำรายการ';

    //         return back()->with('toast', Toast::warning($msg, 2200));
    //     }
    // }

    public function cancelCase(Request $request, MR $req)
    {
        Log::info('[MaintenanceRequest::cancelCase] ENTER', [
            'mr_id'   => $req->id,
            'user_id' => Auth::id(),
            'status'  => $req->status,
        ]);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $actorId = (int) Auth::id();
        $reason  = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') $reason = 'ยกเลิกใบงาน';

        try {
            DB::transaction(function () use ($req, $actorId, $reason) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($locked->status, [
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                ], true)) {
                    abort(409, 'งานนี้อยู่ในสถานะที่ทำรายการไม่ได้');
                }

                $byReporter = Gate::check('cancelByReporter', $locked);

                if ($byReporter) {
                    Gate::authorize('cancelByReporter', $locked);

                    $this->applyStatusTransition(
                        $locked,
                        MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                        $actorId,
                        'ยกเลิกซ่อม: ' . $reason
                    );
                } else {
                    Gate::authorize('cancelByTech', $locked);

                    $this->applyStatusTransition(
                        $locked,
                        MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                        $actorId,
                        'ช่างยกเลิกงาน: ' . $reason
                    );
                }

                MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->update([
                        'status'     => MaintenanceAssignment::STATUS_CANCELLED,
                        'is_lead'    => false,
                        'updated_at' => now(),
                    ]);

                Log::info('[MaintenanceRequest::cancelCase] success', [
                    'mr_id'       => $locked->id,
                    'actor_id'    => $actorId,
                    'by_reporter' => $byReporter,
                ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('ยกเลิกใบงานเรียบร้อย', 1800),
                back(),
                ['message' => 'ยกเลิกใบงานเรียบร้อย']
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์ยกเลิกใบงานนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์ยกเลิกใบงานนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::cancelCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการทำรายการ';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    // public function closeCase(Request $request, MR $req)
    // {
    //     $actorId = (int) Auth::id();

    //     try {
    //         Gate::authorize('close', $req);

    //         DB::transaction(function () use ($req, $actorId) {

    //             $locked = MR::query()
    //                 ->whereKey($req->id)
    //                 ->lockForUpdate()
    //                 ->firstOrFail();

    //             if ($locked->status !== MR::STATUS_RESOLVED) {
    //                 abort(409, 'ต้องอยู่สถานะเสร็จสิ้นก่อนจึงปิดงานได้');
    //             }

    //             // กันกรณี policy หลุด (ช่างห้ามปิดงาน)
    //             if ($locked->technician_id && (int) $locked->technician_id === $actorId) {
    //                 abort(403, 'ช่างไม่สามารถปิดงานได้');
    //             }

    //             // applyStatusTransition จะ set closed_at + status_updated_* + log ให้ครบ
    //             $this->applyStatusTransition(
    //                 $locked,
    //                 MR::STATUS_CLOSED,
    //                 $actorId,
    //                 'ปิดงาน'
    //             );

    //             // sync assignment ทุกคนให้จบงาน
    //             MaintenanceAssignment::query()
    //                 ->where('maintenance_request_id', $locked->id)
    //                 ->update([
    //                     'status'     => MaintenanceAssignment::STATUS_DONE,
    //                     'is_lead'    => false,
    //                     'updated_at' => now(),
    //                 ]);
    //         });

    //         return back()->with('toast', \App\Support\Toast::success('ปิดงานแล้ว', 1800));
    //     } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
    //         return back()->with('toast', \App\Support\Toast::warning('คุณไม่มีสิทธิ์ปิดงานนี้', 2200));
    //     } catch (\Throwable $e) {
    //         Log::error('closeCase failed', [
    //             'mr_id'   => $req->id,
    //             'user_id' => $actorId,
    //             'error'   => $e->getMessage(),
    //         ]);

    //         $code = (int) $e->getCode();
    //         $msg = in_array($code, [403, 409, 422], true) && $e->getMessage()
    //             ? $e->getMessage()
    //             : 'เกิดข้อผิดพลาดในการปิดงาน';

    //         return back()->with('toast', \App\Support\Toast::warning($msg, 2200));
    //     }
    // }

    public function closeCase(Request $request, MR $req)
    {
        $actorId = (int) Auth::id();

        try {
            Gate::authorize('close', $req);

            DB::transaction(function () use ($req, $actorId) {

                $locked = MR::query()
                    ->whereKey($req->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== MR::STATUS_RESOLVED) {
                    abort(409, 'ต้องอยู่สถานะเสร็จสิ้นก่อนจึงปิดงานได้');
                }

                if ($locked->technician_id && (int) $locked->technician_id === $actorId) {
                    abort(403, 'ช่างไม่สามารถปิดงานได้');
                }

                $this->applyStatusTransition(
                    $locked,
                    MR::STATUS_CLOSED,
                    $actorId,
                    'ปิดงาน'
                );

                MaintenanceAssignment::query()
                    ->where('maintenance_request_id', $locked->id)
                    ->update([
                        'status'     => MaintenanceAssignment::STATUS_DONE,
                        'is_lead'    => false,
                        'updated_at' => now(),
                    ]);
            });

            return $this->respondWithToast(
                $request,
                Toast::success('ปิดงานแล้ว', 1800),
                back()->with('show_post_close_modal', true),
                ['message' => 'ปิดงานแล้ว', 'show_post_close_modal' => true]
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->respondWithToast(
                $request,
                Toast::warning('คุณไม่มีสิทธิ์ปิดงานนี้', 2200),
                back(),
                ['message' => 'คุณไม่มีสิทธิ์ปิดงานนี้'],
                403
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::closeCase] failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $code = (int) $e->getCode();
            $msg  = in_array($code, [403, 409, 422], true) && $e->getMessage()
                ? $e->getMessage()
                : 'เกิดข้อผิดพลาดในการปิดงาน';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                back(),
                ['message' => $msg],
                $code ?: 500
            );
        }
    }

    public function transition(Request $request, MR $req)
    {
        Gate::authorize('transition', $req);

        $user = $request->user();
        $actorId = $user ? $user->id : null;

        // ตรวจสอบว่าเป็นกลุ่มทีม (Admin, Supervisor, Tech) หรือไม่
        $isTeam = $user && ($user->isAdmin() || $user->isSupervisor() || $user->isTechnician());

        $rules = [
            'status' => $isTeam
                ? ['bail', 'required', Rule::in([
                    MR::STATUS_PENDING,
                    MR::STATUS_ACKNOWLEDGED,
                    MR::STATUS_ACCEPTED,
                    MR::STATUS_IN_PROGRESS,
                    MR::STATUS_ON_HOLD,
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED,
                    MR::STATUS_CANCELLED, MR::STATUS_REJECTED,
                ])]
                : ['prohibited'], // ไม่อนุญาตให้บุคคลทั่วไปเปลี่ยนสถานะเองผ่านช่องทางนี้

            'note' => ['nullable', 'string', 'max:2000'],

            'technician_id' => array_values(array_filter([
                Rule::prohibitedIf(!$isTeam),
                'nullable',
                'integer',
                'exists:users,id',
            ])),
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $fieldsHuman = ['status' => 'สถานะ', 'technician_id' => 'รหัสช่าง', 'note' => 'บันทึก'];

            $bad = collect(array_keys($errors->toArray()))
                ->map(fn($f) => $fieldsHuman[$f] ?? $f)
                ->implode(', ');

            $msg = $bad ? ('ข้อมูลไม่ถูกต้อง: ' . $bad) : 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';

            if (!$request->expectsJson()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('toast', Toast::warning($msg, 2200));
            }

            return response()->json([
                'errors' => $errors,
                'toast'  => Toast::warning($msg, 2200),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $validator->validated();

        // ประมวลผลการเปลี่ยนสถานะ (Logic การเช็ค Flow อยู่ภายใน applyTransition)
        try {
            $req = $this->applyTransition($req, $data, $actorId);

            Log::info('[MaintenanceRequest::transition] manual transition success', [
                'mr_id'    => $req->id,
                'actor_id' => $actorId,
                'to'       => $data['status']
            ]);

            return $this->respondWithToast(
                $request,
                Toast::success('บันทึกสถานะเรียบร้อย', 1800),
                redirect()->back(),
                ['data' => $req]
            );
        } catch (\Throwable $e) {
            Log::error('[MaintenanceRequest::transition] transition failed', [
                'mr_id'   => $req->id,
                'user_id' => $actorId,
                'error'   => $e->getMessage(),
            ]);

            $msg = in_array((int)$e->getCode(), [403, 409, 422]) ? $e->getMessage() : 'ไม่สามารถเปลี่ยนสถานะได้';

            return $this->respondWithToast(
                $request,
                Toast::warning($msg, 2200),
                redirect()->back()
            );
        }
    }

    public function transitionFromBlade(Request $request, MR $req)
    {
        Gate::authorize('transition', $req);

        $actorId = (int) Auth::id();
        $action  = $request->string('action')->toString();

        // ถ้าไม่มี action ระบุมา ให้ส่งไปประมวลผลแบบ manual transition ปกติ
        if (!$action) {
            return $this->transition($request, $req);
        }

        $map = [
            'accept' => MR::STATUS_ACCEPTED,
            'start'  => MR::STATUS_IN_PROGRESS,
        ];

        $status = $map[$action] ?? null;

        if (!$status) {
            return $this->transition($request, $req);
        }

        $note = trim($request->string('note')->toString()) ?: null;

        // ตรวจสอบสถานะปัจจุบันจากฐานข้อมูล (Fresh Data)
        $current = strtolower((string) MR::query()->whereKey($req->id)->value('status'));

        // Guard: ตรวจสอบความถูกต้องของลำดับ Workflow
        if ($action === 'accept' && $current !== MR::STATUS_ACKNOWLEDGED) {
            abort(409, 'สถานะไม่ถูกต้องสำหรับการรับเรื่อง');
        }

        if ($action === 'start' && $current !== MR::STATUS_ACCEPTED) {
            abort(409, 'สถานะไม่ถูกต้องสำหรับการเริ่มงาน');
        }

        // เตรียมข้อมูลสำหรับส่งให้ applyTransition
        $payload = [
            'status' => $status,
            'note'   => $note,
        ];

        // หากเป็นการรับงาน ให้ตั้งตัวเองเป็นช่างผู้รับผิดชอบทันที
        if ($action === 'accept') {
            $payload['technician_id'] = $actorId;
        }

        try {
            $updated = $this->applyTransition($req, $payload, $actorId);

            $toastMessage = match ($action) {
                'accept' => 'รับเรื่องแล้ว',
                'start'  => 'เริ่มดำเนินการแล้ว',
                default  => 'บันทึกสถานะเรียบร้อย',
            };

            // ตัดสินใจเลือกหน้าที่จะ Redirect กลับไป
            $redirect = Route::has('maintenance.requests.show')
                ? redirect()->route('maintenance.requests.show', $updated)
                : redirect()->back();

            Log::info("[MaintenanceRequest::transitionFromBlade] action: {$action} success", [
                'mr_id' => $updated->id,
                'actor' => $actorId
            ]);

            return $this->respondWithToast(
                $request,
                Toast::success($toastMessage, 1800),
                $redirect,
                ['data' => $updated]
            );
        } catch (\Throwable $e) {
            Log::error("[MaintenanceRequest::transitionFromBlade] action: {$action} failed", [
                'mr_id' => $req->id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithToast(
                $request,
                Toast::warning($e->getMessage() ?: 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ', 2200),
                redirect()->back()
            );
        }
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

            $from = strtolower((string) $originalStatus);

            // ลำดับสถานะที่อนุญาต (ใช้ const จาก Model เป็น single source of truth)
            $allowedNext = MR::ALLOWED_TRANSITIONS;

            $isStatusChange = ($from !== $targetStatus);

            if ($isStatusChange) {
                $nexts = $allowedNext[$from] ?? [];
                if (!in_array($targetStatus, $nexts, true)) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }

                // บังคับระบุเหตุผลเมื่อพักงานสำหรับการตรวจสอบ SLA
                if ($targetStatus === MR::STATUS_ON_HOLD && empty(trim($data['note'] ?? ''))) {
                    abort(409, 'ต้องระบุเหตุผลในการพักงาน');
                }

                // คำนวณเวลาที่ถูกพักงานสะสม (Paused Duration) เมื่อออกจากสถานะ on_hold
                if ($from === MR::STATUS_ON_HOLD && $locked->on_hold_at) {
                    $onHoldAt = \Carbon\Carbon::parse($locked->on_hold_at);
                    $pausedMins = (int) ceil($onHoldAt->diffInMinutes(now()));
                    $locked->paused_duration_minutes = (int) $locked->paused_duration_minutes + $pausedMins;
                    
                    if ($locked->sla_due_date) {
                        $locked->sla_due_date = \Carbon\Carbon::parse($locked->sla_due_date)->addMinutes($pausedMins);
                    }
                }

                $locked->status = $targetStatus;
            }

            // เปลี่ยนช่างได้เฉพาะสถานะ acknowledged และ accepted เท่านั้น
            $canChangeTech = in_array($locked->status, [MR::STATUS_ACKNOWLEDGED, MR::STATUS_ACCEPTED], true);

            if (
                $canChangeTech &&
                array_key_exists('technician_id', $data) &&
                !empty($data['technician_id']) &&
                (int) $locked->technician_id !== (int) $data['technician_id']
            ) {
                $locked->technician_id = (int) $data['technician_id'];
            }

            // ตั้งค่าช่างอัตโนมัติหากยังไม่มีเมื่อสถานะเป็น accepted
            if ($locked->status === MR::STATUS_ACCEPTED && empty($locked->technician_id) && $actorId) {
                $locked->technician_id = (int) $actorId;
            }

            $now = now();

            // จัดการ Timestamp รายสถานะ
            switch ($locked->status) {
                case MR::STATUS_ACKNOWLEDGED:
                    $locked->acknowledged_at ??= $now;
                    break;

                case MR::STATUS_ACCEPTED:
                    if (!$locked->accepted_at) {
                        $locked->accepted_at = $now;
                        $locked->assigned_date ??= $now;
                        
                        $slaTarget = \App\Models\SlaConfig::where('priority_level', $locked->priority ?? \App\Models\SlaConfig::PRIORITY_DEFAULT)
                            ->where('is_active', true)->first() 
                            ?? \App\Models\SlaConfig::where('priority_level', \App\Models\SlaConfig::PRIORITY_DEFAULT)->first();
                        
                        if ($slaTarget) {
                            $locked->sla_due_date = $now->copy()->addMinutes($slaTarget->resolution_time_minutes);
                        }
                    }
                    break;

                case MR::STATUS_IN_PROGRESS:
                    $locked->started_at ??= $now;
                    break;

                case MR::STATUS_ON_HOLD:
                    $locked->on_hold_at = $now;
                    break;

                case MR::STATUS_RESOLVED:
                    $locked->resolved_at ??= $now;
                    break;

                case MR::STATUS_CLOSED:
                    $locked->closed_at      ??= $now;
                    $locked->completed_date ??= $now;
                    break;
            }

            if ($isStatusChange) {
                $locked->status_updated_at = $now;
                $locked->status_updated_by = $actorId;
            }

            $locked->save();

            // อัปเดตสถานะเครื่องจักร (Asset) เมื่อเปลี่ยนสถานะใบงาน
            if ($isStatusChange && !empty($locked->asset_id)) {
                if ($locked->status === MR::STATUS_IN_PROGRESS) {
                    \App\Models\Asset::whereKey($locked->asset_id)
                        ->where('status', 'active')
                        ->update(['status' => 'in_repair']);
                    
                    Log::info('[applyTransition] asset set to in_repair', [
                        'asset_id'   => $locked->asset_id,
                        'request_id' => $locked->id,
                        'actor_id'   => $actorId,
                    ]);
                }

                if (in_array($locked->status, [MR::STATUS_RESOLVED, MR::STATUS_CLOSED, MR::STATUS_CANCELLED], true)) {
                    $stillInRepair = MR::where('asset_id', $locked->asset_id)
                        ->where('id', '!=', $locked->id)
                        ->whereIn('status', [MR::STATUS_IN_PROGRESS, MR::STATUS_ACCEPTED, MR::STATUS_ON_HOLD])
                        ->exists();

                    if (!$stillInRepair) {
                        \App\Models\Asset::whereKey($locked->asset_id)
                            ->where('status', 'in_repair')
                            ->update(['status' => 'active']);

                        Log::info('[applyTransition] asset restored to active', [
                            'asset_id'   => $locked->asset_id,
                            'request_id' => $locked->id,
                            'to_status'  => $locked->status,
                            'actor_id'   => $actorId,
                        ]);
                    } else {
                        Log::info('[applyTransition] asset kept in_repair (other MR still active)', [
                            'asset_id'   => $locked->asset_id,
                            'request_id' => $locked->id,
                        ]);
                    }
                }
            }

            $newTechId   = (int) ($locked->technician_id ?? 0);
            $techChanged = ($originalTechId !== $newTechId);

            if (($techChanged || $isStatusChange) && $newTechId > 0) {
                $currentTeamIds = $locked->assignments()
                    ->where('status', '!=', \App\Models\MaintenanceAssignment::STATUS_CANCELLED)
                    ->pluck('user_id')
                    ->toArray();
                    
                $currentTeamIds = array_filter($currentTeamIds, fn($id) => $id != $newTechId);
                array_unshift($currentTeamIds, $newTechId);
                
                $this->syncAssignments($locked, array_values($currentTeamIds), $actorId);
            }

            // บันทึก Log การเปลี่ยนสถานะและช่าง
            if (class_exists(MaintenanceLog::class)) {

                if ($techChanged) {
                    $locked->loadMissing('technician:id,name');
                }

                $defaultNote = $data['note']
                    ?? $this->defaultNoteForStatus($locked->status, $actorId, $locked);

                if ($techChanged && $locked->technician) {
                    $defaultNote = trim(
                        ($defaultNote ? $defaultNote . ' • ' : '') .
                            'ช่าง: ' . $locked->technician->name
                    );
                }

                MaintenanceLog::create([
                    'request_id'  => $locked->id,
                    'action'      => MaintenanceLog::ACTION_TRANSITION,
                    'note'        => $defaultNote ?: null,
                    'user_id'     => $actorId,
                    'from_status' => $originalStatus,
                    'to_status'   => $locked->status,
                ]);
            }

            $req->setRawAttributes($locked->getAttributes(), true);
        });

        return $req->fresh(['technician:id,name']);
    }

    public function uploadAttachmentFromBlade(Request $request, MR $req)
    {
        Gate::authorize('attach', $req);

        // ตรวจสอบจำนวนไฟล์ที่แนบไปแล้ว (จำกัดสูงสุด 3 ไฟล์)
        $currentCount = $req->attachments()->count();
        if ($currentCount >= 3) {
            return $this->respondWithToast(
                $request,
                Toast::warning('สามารถแนบไฟล์ได้สูงสุด 3 ไฟล์ต่อใบงาน', 2500),
                back(),
                ['message' => 'สามารถแนบไฟล์ได้สูงสุด 3 ไฟล์ต่อใบงาน'],
                422
            );
        }

        // เตรียมค่ากำหนดสำหรับการ Validation จาก config
        $maxKb = config('uploads.max_kb', 10240);
        $mimetypes = implode(',', config('uploads.mimetypes', ['image/*', 'application/pdf']));
        $fileRules = ['required', 'file', 'max:' . $maxKb, 'mimetypes:' . $mimetypes];

        $validated = $request->validate([
            'file'       => $fileRules,
            'is_private' => ['nullable', 'boolean'],
            'caption'    => ['nullable', 'string', 'max:255'],
            'alt_text'   => ['nullable', 'string', 'max:255'],
        ]);

        $up = $validated['file'];
        $isPrivate = (bool) ($validated['is_private'] ?? false);
        $disk = $isPrivate ? 'local' : 'public';

        // จัดเก็บไฟล์ลง Disk
        $storedPath = $up->store("maintenance/{$req->id}", $disk);

        // คำนวณ SHA256 Checksum เพื่อป้องกันไฟล์ซ้ำในระดับ Storage
        $stream = fopen($up->getRealPath(), 'r');
        $ctx = hash_init('sha256');
        while (!feof($stream)) {
            $buf = fread($stream, 1024 * 1024);
            if ($buf === false) break;
            hash_update($ctx, $buf);
        }
        fclose($stream);
        $sha = hash_final($ctx);

        // ตรวจสอบหรือสร้างข้อมูลไฟล์ในฐานข้อมูล
        $file = File::firstOrCreate(
            ['checksum_sha256' => $sha],
            [
                'path'      => $storedPath,
                'disk'      => $disk,
                'mime'      => $up->getClientMimeType(),
                'size'      => $up->getSize(),
                'path_hash' => hash('sha256', $storedPath),
                'meta'      => null,
            ]
        );

        // ตรวจสอบว่าไฟล์นี้เคยแนบกับใบงานนี้แล้วหรือไม่ (รวมที่เคยลบแบบ Soft Delete)
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
                Toast::info('ไฟล์นี้ถูกแนบไว้แล้ว (อัปเดตข้อมูลใหม่)', 1600),
                redirect()->back(),
                ['duplicate' => true, 'attachment_id' => $existing->id]
            );
        }

        // สร้างรายการแนบไฟล์ใหม่
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
            Toast::success('อัปโหลดไฟล์แนบแล้ว', 1800),
            redirect()->back(),
            ['data' => $req->fresh('attachments.file')]
        );
    }

    public function destroyAttachment(MR $req, Attachment $attachment)
    {
        Gate::authorize('deleteAttachment', $req);

        // ตรวจสอบความถูกต้องว่าไฟล์แนบนี้เป็นของใบงานนี้จริงหรือไม่ (Polymorphic Guard)
        abort_unless(
            $attachment->attachable_type === MR::class &&
                (int) $attachment->attachable_id === (int) $req->id,
            404
        );

        // เรียกใช้ Method สำหรับการลบข้อมูลและทำความสะอาดไฟล์ใน Storage (ถ้ามี Logic ภายในนั้น)
        $attachment->deleteAndCleanup(true);

        // บันทึก Log การลบไฟล์แนบ
        Log::info('[MaintenanceRequest::destroyAttachment] attachment deleted', [
            'mr_id'         => $req->id,
            'attachment_id' => $attachment->id,
            'user_id'       => Auth::id()
        ]);

        return $this->respondWithToast(
            request(),
            Toast::success('ลบไฟล์แนบแล้ว', 1600),
            redirect()->back(),
            ['deleted' => true]
        );
    }

    /**
     * API: ลบใบงานซ่อม (REST: DELETE /api/repair-requests/{req})
     */
    public function destroy(Request $request, MR $req)
    {
        Gate::authorize('delete', $req);

        $actorId = (int) Auth::id();

        DB::transaction(function () use ($req, $actorId) {
            $id = $req->id;

            $req->delete();

            MaintenanceLog::create([
                'request_id' => $id,
                'user_id'    => $actorId ?: null,
                'action'     => 'delete_request',
                'note'       => 'ลบใบงานซ่อม (soft delete) ผ่าน API',
            ]);
        });

        if (!$request->expectsJson()) {
            return redirect()
                ->route('maintenance.requests.index')
                ->with('toast', Toast::success('ลบใบงานเรียบร้อย', 1600));
        }

        return response()->json([
            'deleted' => true,
            'toast' => Toast::success('ลบใบงานเรียบร้อย', 1600),
        ], Response::HTTP_OK);
    }

    protected function respondWithToast(
        Request $request,
        array $toast,
        $webRedirect,
        array $jsonPayload = [],
        int $status = Response::HTTP_OK
    ) {
        // ดึงค่ามาตรฐานสำหรับ Toast
        $toastData = [
            'type'     => $toast['type']     ?? 'info',
            'message'  => $toast['message']  ?? '',
            'position' => $toast['position'] ?? 'tc',
            'timeout'  => $toast['timeout']  ?? 2000,
            'size'     => $toast['size']     ?? 'sm',
        ];
    
        // กรณีเป็นการเรียกจาก Browser ปกติ
        if (!$request->expectsJson()) {
            return $webRedirect->with('toast', $toastData);
        }
    
        // กรณีเป็นการเรียกผ่าน API หรือ AJAX
        $payload = array_merge($jsonPayload, ['toast' => $toastData]);
    
        return response()->json($payload, $status);
    }
    
    public function edit($id)
    {
        // โหลดข้อมูลหลักพร้อม Relations ที่จำเป็นสำหรับหน้าแก้ไข
        $mr = MR::with([
            'asset',
            'reporter',
            'attachments.file',
            'operationLog',
        ])->findOrFail($id);
    
        Gate::authorize('update', $mr);
    
        // เตรียมข้อมูล Master Data สำหรับ Dropdown ในหน้า View
        $assets = Asset::orderBy('asset_code')->get(['id', 'asset_code', 'name']);
        $users  = User::orderBy('name')->get(['id', 'name']);
        $depts  = Department::orderBy('name_th')->get(['id', 'code', 'name_th', 'name_en']);
    
        // ดึงข้อมูลไฟล์แนบโดยเลือกเฉพาะคอลัมน์ที่จำเป็น
        $attachments = $mr->attachments()
            ->select([
                'id',
                'file_id',
                'original_name',
                'is_private',
                'order_column',
                'attachable_id',
                'attachable_type',
            ])
            ->with(['file:id,path,disk,mime,size'])
            ->get();
    
        $techUsers   = $this->suggestTechUsersForRequest($mr);
        $suggestRole = strtolower(trim((string) $mr->type?->default_role_code));
        
        $types = \App\Models\MaintenanceRequestType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    
        return view('maintenance.requests.edit', compact(
            'mr',
            'assets',
            'users',
            'attachments',
            'depts',
            'techUsers',
            'suggestRole',
            'types'
        ));
    }
    
    protected function defaultNoteForStatus(string $status, ?int $actorId, MR $req): string
    {
        // ดึงชื่อผู้กระทำรายการโดยเลือกเฉพาะคอลัมน์ name เพื่อประหยัด Memory
        $actorName = $actorId
            ? optional(User::select('name')->find($actorId))->name
            : null;
    
        return match ($status) {
            MR::STATUS_PENDING      => 'รอรับทราบ',
            MR::STATUS_ACKNOWLEDGED => $actorName ? "รับทราบโดย {$actorName}" : 'รับทราบแล้ว',
            MR::STATUS_ACCEPTED     => $actorName ? "รับเรื่องโดย {$actorName}" : 'รับเรื่องแล้ว',
            MR::STATUS_IN_PROGRESS  => 'เริ่มดำเนินการซ่อม',
            MR::STATUS_ON_HOLD      => 'พักชั่วคราว',
            MR::STATUS_RESOLVED     => 'แก้ไขเสร็จ รอตรวจรับ',
            MR::STATUS_CLOSED       => 'ปิดงานเรียบร้อย',
            MR::STATUS_CANCELLED    => 'ยกเลิกคำขอ',
            MR::STATUS_REJECTED     => 'ไม่รับเรื่อง',
            default                 => 'อัปเดตสถานะ',
        };
    }
    
    public function printWorkOrder(Request $request, MR $req)
    {
        Gate::authorize('view', $req);
    
        // โหลดข้อมูลทุกส่วนที่ต้องใช้ในใบงาน เพื่อป้องกันปัญหา N+1 ในหน้า PDF
        $req->loadMissing([
            'asset',
            'reporter:id,name,email',
            'technician:id,name',
            'attachments' => fn($qq) => $qq->with('file'),
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
        ]);
    
        // ข้อมูลส่วนหัวของเอกสาร
        $hospital = [
            'name_th'  => 'โรงพยาบาลพระปกเกล้า',
            'name_en'  => 'PHRAPOKKLAO HOSPITAL',
            'subtitle' => 'Maintenance Work Order',
            'logo'     => public_path('images/logoppk1.png'),
        ];
    
        // กำหนดชื่อไฟล์ตามเลขที่ใบงานหรือ ID
        $fileName = sprintf(
            'maintenance-work-order-%s.pdf',
            $req->request_no ?? $req->id
        );
    
        $pdf = Pdf::loadView('maintenance.requests.print', [
            'req'      => $req,
            'hospital' => $hospital,
        ])->setPaper('A4', 'portrait');
    
        Log::info('[MaintenanceRequest::printWorkOrder] PDF generated', [
            'mr_id'      => $req->id,
            'request_no' => $req->request_no,
            'user_id'    => Auth::id(),
        ]);
    
        return $pdf->stream($fileName);
    }
    
    protected function resolveSort(Request $request): array
    {
        $user   = $request->user();
        $userId = $user?->id;
    
        // กำหนด Key สำหรับเก็บค่าใน Session แยกตาม User ID หรือ Guest
        $sessionSortByKey  = $userId ? "maintenance_sort_by_user_{$userId}"  : 'maintenance_sort_by_guest';
        $sessionSortDirKey = $userId ? "maintenance_sort_dir_user_{$userId}" : 'maintenance_sort_dir_guest';
    
        $allowedSorts = ['request_no', 'id', 'request_date'];
    
        $sortByReq  = $request->query('sort_by');
        $sortDirReq = strtolower((string) $request->query('sort_dir'));
    
        // 1. จัดการการเรียงลำดับคอลัมน์
        if (in_array($sortByReq, $allowedSorts, true)) {
            $sortBy = $sortByReq;
            session([$sessionSortByKey => $sortBy]);
        } else {
            $sortBy = session($sessionSortByKey, 'request_no');
        }
    
        // 2. จัดการทิศทางการเรียงลำดับ
        if (in_array($sortDirReq, ['asc', 'desc'], true)) {
            $sortDir = $sortDirReq;
            session([$sessionSortDirKey => $sortDir]);
        } else {
            $sortDir = session($sessionSortDirKey, 'desc');
        }
    
        return [$sortBy, $sortDir];
    }
    
    protected function syncAssignments(MR $req, array $userIds, ?int $actorId = null): void
    {
        // ถ้าส่งค่าว่างมา ให้ยกเลิกทุกคนแทนการลบทิ้ง เพื่อเก็บประวัติ
        if (empty($userIds)) {
            MaintenanceAssignment::where('maintenance_request_id', $req->id)->update([
                'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                'is_lead'         => false,
                'response_status' => MaintenanceAssignment::RESP_PENDING,
                'responded_at'    => null,
                'updated_at'      => now(),
            ]);
            Log::info('[MaintenanceRequest] All workers marked as cancelled (history preserved)', ['mr_id' => $req->id]);
            return;
        }
    
        // ยกเลิกคนเก่าที่ไม่ได้ถูกเลือกในรอบนี้ เพื่อคงประวัติไว้ใน Database
        MaintenanceAssignment::where('maintenance_request_id', $req->id)
            ->whereNotIn('user_id', $userIds)
            ->update([
                'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                'is_lead'         => false,
                'response_status' => MaintenanceAssignment::RESP_PENDING,
                'responded_at'    => null,
                'updated_at'      => now(),
            ]);
    
        // กำหนดสถานะงาน
        $status = match ($req->status) {
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED => MaintenanceAssignment::STATUS_DONE,
            default           => MaintenanceAssignment::STATUS_IN_PROGRESS,
        };
    
        // วนลูปบันทึกหรืออัปเดตคนที่มีรายชื่อ
        foreach ($userIds as $index => $userId) {
            $workerRole = User::query()->whereKey($userId)->value('role') ?? 'technician';
            $isLead     = ($index === 0);
    
            $as = MaintenanceAssignment::updateOrCreate(
                [
                    'maintenance_request_id' => $req->id,
                    'user_id'                => $userId,
                ],
                [
                    'role'    => $workerRole,
                    'is_lead' => $isLead,
                    'status'  => $status,
                ]
            );
    
            // อัปเดตเวลาที่ได้รับมอบหมายถ้ายังไม่มี
            if (empty($as->assigned_at)) {
                $as->assigned_at = now();
                $as->save();
            }
        }
    }
    
    protected function suggestTechUsersForRequest(MR $req): \Illuminate\Support\Collection
    {
        $req->loadMissing(['type']);
        $type = $req->type;
    
        // กำหนดคอลัมน์ที่จำเป็นเพื่อลดภาระการดึงข้อมูลจาก Database
        $selectCols = [
            'id',
            'name',
            'role',
            'department',
            'profile_photo_thumb',
            'profile_photo_path',
        ];
    
        // เตรียม Query พื้นฐานสำหรับกลุ่มทีมงาน
        $base = User::query()
            ->inRoles(User::teamRoles())
            ->with(['roleRef'])
            ->select($selectCols)
            ->orderBy('name');
    
        // หากไม่มีประเภทงานระบุมา ให้คืนค่าช่างทั้งหมดในระบบ
        if (!$type) {
            return $base->get();
        }
    
        $suggested = collect();
    
        // 1) ดึงรายชื่อช่างที่เป็น Default สำหรับงานประเภทนี้
        if (!empty($type->default_user_id)) {
            $u = User::query()
                ->with(['roleRef'])
                ->whereKey((int) $type->default_user_id)
                ->first($selectCols);
    
            if ($u) {
                $suggested->push($u);
            }
        }
    
        // 2) กรองรายชื่อช่างตามหน่วยงานหรือบทบาทที่กำหนดไว้ในประเภทงาน
        $filterQuery = clone $base;
    
        if (!empty($type->default_department_code)) {
            $filterQuery->where('department', trim((string) $type->default_department_code));
        }
    
        if (!empty($type->default_role_code)) {
            $roleCode = strtolower(trim((string) $type->default_role_code));
            $filterQuery->whereRaw('LOWER(role) = ?', [$roleCode]);
        }
    
        $filteredUsers = $filterQuery->get();
    
        // 3) Fallback: ถ้ากรองแล้วไม่เจอใครเลย ให้แสดงรายชื่อทีมทั้งหมดแทน
        if ($filteredUsers->isEmpty()) {
            $filteredUsers = $base->get();
        }
    
        // รวมผลลัพธ์ ตัดรายชื่อที่ซ้ำออก และจัดลำดับ Index ใหม่
        return $suggested
            ->merge($filteredUsers)
            ->unique('id')
            ->values();
    }
    
    public function updateType(Request $request, MR $req)
    {
        // ตรวจสอบสิทธิ์ผ่าน Policy: MaintenanceRequestPolicy@setType
        Gate::authorize('setType', $req);
    
        $validator = Validator::make($request->all(), [
            'type_id' => ['nullable', 'integer', 'exists:maintenance_request_types,id'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)
                ->with('toast', Toast::warning($validator->errors()->first(), 3000));
        }

        $data = $validator->validated();
    
        DB::transaction(function () use ($req, $data, $request) {
            $oldId = (int) ($req->type_id ?? 0);
            $newId = (int) ($data['type_id'] ?? 0);
    
            // หากไม่มีการเปลี่ยนแปลงค่า ไม่ต้องดำเนินการต่อ
            if ($oldId === $newId) {
                return;
            }
    
            $req->type_id = $data['type_id'] ?? null;
            $req->save();
    
            // บันทึกประวัติการเปลี่ยนแปลงลงใน MaintenanceLog
            if (class_exists(MaintenanceLog::class)) {
                MaintenanceLog::create([
                    'request_id'  => $req->id,
                    'action'      => MaintenanceLog::ACTION_UPDATE,
                    'note'        => "เปลี่ยนประเภทใบงาน: {$oldId} -> {$newId}",
                    'user_id'     => $request->user()?->id,
                    'from_status' => null,
                    'to_status'   => null,
                ]);
            }
    
            Log::info('[MaintenanceRequest::updateType] type updated', [
                'mr_id'  => $req->id,
                'old_id' => $oldId,
                'new_id' => $newId,
                'actor'  => $request->user()?->id,
            ]);
        });
    
        if ($request->expectsJson()) {
            return response()->json([
                'data' => $req->fresh(['type']),
                'toast' => Toast::success('อัปเดตประเภทใบงานแล้ว', 1600),
            ], Response::HTTP_OK);
        }
    
        return redirect()->route('maintenance.requests.show', $req)
            ->with('toast', Toast::success('อัปเดตประเภทใบงานแล้ว', 1600));
    }
}