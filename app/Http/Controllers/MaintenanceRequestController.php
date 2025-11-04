<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MaintenanceRequestController extends Controller
{
    /**
     * API: GET /api/repair-requests
     * Filters: ?status=&priority=&q=
     */
    public function index()
    {
        $status   = request('status');
        $priority = request('priority');
        $q        = request('q');

        $list = MR::with(['asset','reporter','technician'])
            ->when($status, fn($qb)=>$qb->where('status',$status))
            ->when($priority, fn($qb)=>$qb->where('priority',$priority))
            ->when($q, fn($qb)=>$qb->where(function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhere('description','like',"%$q%");
            }))
            ->orderByDesc('request_date')
            ->paginate(20);

        return response()->json($list);
    }

    /**
     * API: POST /api/repair-requests
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id'     => 'required|exists:assets,id',
            'reporter_id'  => 'required|exists:users,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'priority'     => ['required', Rule::in([
                MR::PRIORITY_LOW,
                MR::PRIORITY_MEDIUM,
                MR::PRIORITY_HIGH,
                MR::PRIORITY_URGENT,
            ])],
        ]);

        $req = DB::transaction(function () use ($data, $request) {
            $req = MR::create($data + [
                'status'       => MR::STATUS_PENDING,
                'request_date' => now(),
            ]);

            MaintenanceLog::create([
                'request_id' => $req->id,
                'user_id'    => $request->user()->id ?? $req->reporter_id,
                'action'     => 'create_request',
                'note'       => $req->title,
            ]);

            return $req;
        });

        return response()->json(['message'=>'created','data'=>$req], 201);
    }

    /**
     * API: GET /api/repair-requests/{req}
     */
    public function show(MR $req)
    {
        return response()->json(
            $req->load(['asset','reporter','technician','attachments','logs' => fn($q)=>$q->latest()])
        );
    }

    /**
     * API: PUT/PATCH /api/repair-requests/{req}
     * (generic update — รองรับฟิลด์สถานะ/เวลาใหม่)
     */
    public function update(Request $request, MR $req)
    {
        $data = $request->validate([
            'status'         => ['sometimes', Rule::in([
                MR::STATUS_PENDING,
                MR::STATUS_ACCEPTED,
                MR::STATUS_IN_PROGRESS,
                MR::STATUS_ON_HOLD,
                MR::STATUS_RESOLVED,
                MR::STATUS_CLOSED,
                MR::STATUS_CANCELLED,
                MR::STATUS_COMPLETED, // legacy mapping → resolved
            ])],
            'technician_id'  => 'nullable|exists:users,id',
            'remark'         => 'nullable|string',
            'assigned_date'  => 'nullable|date',
            'completed_date' => 'nullable|date',
            'accepted_at'    => 'nullable|date',
            'started_at'     => 'nullable|date',
            'on_hold_at'     => 'nullable|date',
            'resolved_at'    => 'nullable|date',
            'closed_at'      => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $req, $data) {
            // auto-accept เมื่อมี technician ครั้งแรก
            if (array_key_exists('technician_id', $data) && $data['technician_id'] && !$req->assigned_date) {
                $data['assigned_date'] = $data['assigned_date'] ?? now();
                $data['accepted_at']   = $data['accepted_at']   ?? now();
                $data['status']        = $data['status']        ?? MR::STATUS_ACCEPTED;
            }

            // map resolved/closed ให้ลง timestamp อัตโนมัติ
            if (($data['status'] ?? null) === MR::STATUS_RESOLVED && empty($data['resolved_at'])) {
                $data['resolved_at'] = now();
            }
            if (($data['status'] ?? null) === MR::STATUS_CLOSED && empty($data['closed_at'])) {
                $data['closed_at'] = now();
            }

            // legacy: completed → resolved
            if (($data['status'] ?? null) === MR::STATUS_COMPLETED && empty($data['resolved_at'])) {
                $data['status']      = MR::STATUS_RESOLVED;
                $data['resolved_at'] = now();
            }

            $req->update($data);

            MaintenanceLog::create([
                'request_id' => $req->id,
                'user_id'    => $request->user()->id ?? null,
                'action'     => 'update_request',
                'note'       => $req->status,
            ]);
        });

        return response()->json(['message'=>'updated']);
    }

    /**
     * API: POST /api/repair-requests/{req}/transition
     * body: action(assign|accept|start|hold|resolve|close|cancel|reassign), technician_id?, remark?
     */
    public function transition(Request $request, MR $req)
    {
        $data = $request->validate([
            'action'        => 'required|in:assign,accept,start,hold,resolve,close,cancel,reassign',
            'technician_id' => 'nullable|exists:users,id',
            'remark'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $req, $data) {
            $action = $data['action'];
            $note   = $data['remark'] ?? null;

            if (in_array($req->status, [MR::STATUS_CLOSED, MR::STATUS_CANCELLED], true) && $action !== 'cancel') {
                abort(422, 'Request already closed.');
            }

            switch ($action) {
                case 'assign':
                    if (empty($data['technician_id'])) abort(422, 'technician_id is required for assign');
                    $req->update([
                        'technician_id' => $data['technician_id'],
                        'status'        => MR::STATUS_ACCEPTED,
                        'assigned_date' => $req->assigned_date ?? now(),
                        'accepted_at'   => $req->accepted_at ?? now(),
                        'remark'        => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'assign_technician', (string)$data['technician_id']);
                    break;

                case 'accept':
                    if (!in_array($req->status, [MR::STATUS_PENDING, MR::STATUS_ACCEPTED], true)) {
                        abort(422, 'Invalid state to accept');
                    }
                    $req->update([
                        'status'      => MR::STATUS_ACCEPTED,
                        'accepted_at' => $req->accepted_at ?? now(),
                        'remark'      => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'accept_request', $note);
                    break;

                case 'start':
                    if (!in_array($req->status, [MR::STATUS_ACCEPTED, MR::STATUS_PENDING, MR::STATUS_ON_HOLD], true)) {
                        abort(422, 'Invalid state to start');
                    }
                    $req->update([
                        'status'     => MR::STATUS_IN_PROGRESS,
                        'started_at' => $req->started_at ?? now(),
                        'on_hold_at' => null,
                        'remark'     => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'start_request', $note);
                    break;

                case 'hold':
                    if (!in_array($req->status, [MR::STATUS_IN_PROGRESS, MR::STATUS_ACCEPTED], true)) {
                        abort(422, 'Invalid state to hold');
                    }
                    $req->update([
                        'status'     => MR::STATUS_ON_HOLD,
                        'on_hold_at' => now(),
                        'remark'     => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'hold_request', $note);
                    break;

                case 'resolve':
                    if ($req->status !== MR::STATUS_IN_PROGRESS) {
                        abort(422, 'Invalid state to resolve');
                    }
                    $req->update([
                        'status'      => MR::STATUS_RESOLVED,
                        'resolved_at' => $req->resolved_at ?? now(),
                        'remark'      => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'resolve_request', $note);
                    break;

                case 'close':
                    if (!in_array($req->status, [MR::STATUS_RESOLVED, MR::STATUS_COMPLETED], true)) {
                        abort(422, 'Invalid state to close');
                    }
                    $req->update([
                        'status'    => MR::STATUS_CLOSED,
                        'closed_at' => $req->closed_at ?? now(),
                        'remark'    => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'close_request', $note);
                    break;

                case 'cancel':
                    $req->update([
                        'status' => MR::STATUS_CANCELLED,
                        'remark' => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'cancel_request', $note);
                    break;

                case 'reassign':
                    if (empty($data['technician_id'])) abort(422, 'technician_id is required for reassign');
                    if (in_array($req->status, [MR::STATUS_CLOSED, MR::STATUS_CANCELLED], true)) {
                        abort(422, 'Request already closed.');
                    }
                    $req->update([
                        'technician_id' => $data['technician_id'],
                        'status'        => MR::STATUS_ACCEPTED,
                        'accepted_at'   => $req->accepted_at ?? now(),
                        'remark'        => $note ?: $req->remark,
                    ]);
                    $this->log($req->id, $request->user()->id ?? null, 'reassign_technician', (string)$data['technician_id']);
                    break;
            }
        });

        return response()->json(['message'=>'transition_ok','data'=>$req->fresh(['asset','reporter','technician'])]);
    }

    // ====== หน้า List (Blade) ======
    public function indexPage(Request $request)
    {
        $status   = $request->query('status');
        $priority = $request->query('priority');
        $q        = $request->query('q');

        $list = MR::with(['asset','reporter','technician'])
            ->when($status, fn($qb)=>$qb->where('status',$status))
            ->when($priority, fn($qb)=>$qb->where('priority',$priority))
            ->when($q, fn($qb)=>$qb->where(function($w) use ($q){
                $w->where('title','like',"%$q%")
                  ->orWhere('description','like',"%$q%");
            }))
            ->orderByRaw('COALESCE(request_date, created_at) DESC')
            ->paginate(20);

        return view('maintenance.requests.index', compact('list'));
    }

    // ====== หน้า Show (Blade) ======
    public function showPage(MR $req)
    {
        $req->load(['asset','reporter','technician','attachments','logs'=>fn($q)=>$q->latest()]);
        return view('maintenance.requests.show', compact('req'));
    }

    // ====== transition จากหน้า Blade (redirect back) ======
    public function transitionFromBlade(Request $request, MR $req)
    {
        $this->transition($request, $req);
        return back()->with('ok','บันทึกแล้ว');
    }

    // ====== upload จากหน้า Blade (redirect back) ======
    public function uploadAttachmentFromBlade(Request $request, MR $req)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['before','after','other'])],
            'file' => ['required','file','max:10240'],
        ]);

        $file   = $request->file('file');
        $path   = $file->storePublicly("maintenance/{$req->id}", ['disk' => 'public']);

        $req->attachments()->create([
            'type'          => $validated['type'] ?? 'other',
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return back()->with('ok','อัปโหลดไฟล์แล้ว');
    }

    // ====== แผงช่าง: งานของฉัน ======
    public function myJobsPage(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['technician','admin'], true), 403);

        $status = $request->query('status'); // optional
        $list = MR::with(['asset','reporter','technician'])
            ->where('technician_id', $user->id)
            ->when($status, fn($q)=>$q->where('status',$status))
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('repair.my-jobs', compact('list','status'));
    }

    // ====== แผงช่าง: คิวรอรับ (pending) ======
    public function queuePage(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['technician','admin'], true), 403);

        // รับพารามิเตอร์ (ถ้าอยากให้มีแท็บเปลี่ยนสถานะได้ในหน้าเดียวกัน)
        $status = (string) $request->query('status', 'pending'); // default 'pending'
        $q      = (string) $request->query('q', '');

        // ฐาน query ร่วม (เอาไว้นับสถิติด้วย จะได้ logic เดียวกัน)
        $base = MR::query()
            ->with(['asset','reporter'])
            ->when($q !== '', function ($qq) use ($q) {
                $like = "%{$q}%";
                $qq->where(function ($w) use ($like) {
                    $w->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('asset_id', 'like', $like);
                });
            });

        // รายการตาม "กลุ่มสถานะ"
        $list = (clone $base)
            ->when($status === 'pending',     fn($qq) => $qq->pendingGroup())
            ->when($status === 'in_progress', fn($qq) => $qq->inProgressGroup())
            ->when($status === 'completed',   fn($qq) => $qq->completedGroup())
            ->orderByRaw('COALESCE(request_date, created_at) DESC')
            ->paginate(20)
            ->withQueryString();

        // ตัวเลขสรุป (เอาไปโชว์ใน pills ด้านบน)
        $stats = [
            'total'       => (clone $base)->count(),
            'pending'     => (clone $base)->pendingGroup()->count(),
            'in_progress' => (clone $base)->inProgressGroup()->count(),
            'completed'   => (clone $base)->completedGroup()->count(),
        ];

        // ส่ง $status และ $stats ไปที่ view ด้วย (หน้าที่คุณใช้คอมโพเนนต์ pills ไว้แล้ว)
        return view('repair.queue', compact('list','status','stats','q'));
    }

    // ====== helper log ======
    private function log(int $requestId, ?int $userId, string $action, ?string $note = null): void
    {
        MaintenanceLog::create([
            'request_id' => $requestId,
            'user_id'    => $userId,
            'action'     => $action,
            'note'       => $note,
        ]);
    }

    // ====== หน้า create (Blade) ======
    public function createPage()
    {
        $assets = \App\Models\Asset::orderBy('name')->get();
        $users  = \App\Models\User::whereIn('role',['technician','admin'])->orderBy('name')->get();

        return view('maintenance.requests.create', compact('assets','users'));
    }
}
