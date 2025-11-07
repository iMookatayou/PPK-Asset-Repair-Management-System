<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest as MR;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceRequestController extends Controller
{
    /** ========== PAGES (Blade) ========== */
    public function indexPage(Request $request)
    {
        $status   = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $q        = $request->string('q')->toString();

        $list = MR::query()
            ->with([
                'asset',
                'reporter:id,name',
                'technician:id,name',
                'attachments' => fn($qq) => $qq->select(
                    'id','attachable_id','attachable_type','original_name','path','disk','mime','size','is_private','order_column'
                ),
            ])
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($priority, fn ($qb) => $qb->where('priority', $priority))
            ->when($q, function ($w) use ($q) {
                $w->where(fn ($ww) =>
                    $ww->where('title','like',"%{$q}%")
                       ->orWhere('description','like',"%{$q}%")
                );
            })
            ->orderByDesc('request_date')
            ->paginate(20)
            ->withQueryString();

        // ⬅️ view อยู่ที่ resources/views/maintenance/requests/index.blade.php
        return view('maintenance.requests.index', compact('list','status','priority','q'));
    }

    public function queuePage(Request $request)
    {
        $list = MR::query()
            ->with(['asset','reporter:id,name','technician:id,name'])
            ->whereIn('status', ['pending','accepted','in_progress','on_hold'])
            ->orderBy('priority')
            ->orderByDesc('request_date')
            ->paginate(20);

        // ⬅️ ไฟล์คุณอยู่ที่ resources/views/repair/queue.blade.php
        return view('repair.queue', compact('list'));
    }

    public function myJobsPage(Request $request)
    {
        $userId = Auth::id();

        $list = MR::query()
            ->with(['asset','reporter:id,name','technician:id,name'])
            ->where('technician_id', $userId)
            ->orderByDesc('updated_at')
            ->paginate(20);

        // ⬅️ ไฟล์ชื่อ my-jobs.blade.php (มีขีดกลาง)
        return view('repair.my-jobs', compact('list'));
    }

    public function showPage(MR $req)
    {
        $req->loadMissing([
            'asset',
            'reporter:id,name',
            'technician:id,name',
            'attachments',
            'logs.user:id,name',
        ]);

        // ⬅️ resources/views/maintenance/requests/show.blade.php
        return view('maintenance.requests.show', compact('req'));
    }

    public function createPage()
    {
        // ⬅️ resources/views/maintenance/requests/create.blade.php
        return view('maintenance.requests.create');
    }

    /** ========== API & Form handlers ========== */
    public function index(Request $request)
    {
        $status   = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $q        = $request->string('q')->toString();

        $list = MR::query()
            ->with(['asset','reporter:id,name','technician:id,name'])
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($priority, fn ($qb) => $qb->where('priority', $priority))
            ->when($q, fn ($w) =>
                $w->where(fn ($ww) =>
                    $ww->where('title','like',"%{$q}%")
                       ->orWhere('description','like',"%{$q}%")
                )
            )
            ->orderByDesc('request_date')
            ->paginate(20)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data'  => $list,
                'toast' => [
                    'type' => 'info', 'message' => 'โหลดรายการคำขอบำรุงรักษาแล้ว',
                    'position' => 'tc','timeout' => 1200,'size' => 'sm',
                ],
            ]);
        }

        // ⬅️ ให้หน้าเว็บใช้ view เดียวกับ indexPage()
        return view('maintenance.requests.index', compact('list','status','priority','q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required','string','max:255'],
            'description'  => ['nullable','string','max:5000'],
            'asset_id'     => ['nullable','integer','exists:assets,id'],
            'priority'     => ['required', Rule::in(['low','normal','high','urgent'])],
            'request_date' => ['nullable','date'],
            'reporter_id'  => ['nullable','integer','exists:users,id'],
            'files.*'      => ['file','max:10240','mimetypes:image/*,application/pdf'],
        ]);

        $actorId = $data['reporter_id'] ?? optional($request->user())->id;

        $req = DB::transaction(function () use ($data, $request, $actorId) {
            /** @var \App\Models\MaintenanceRequest $req */
            $req = MR::create([
                'title'        => $data['title'],
                'description'  => $data['description'] ?? null,
                'asset_id'     => $data['asset_id'] ?? null,
                'priority'     => $data['priority'],
                'status'       => 'pending',
                'request_date' => $data['request_date'] ?? now(),
                'reporter_id'  => $actorId,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $disk = 'public';
                    $path = $file->store("maintenance/{$req->id}", $disk);
                    $req->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'extension'     => $file->getClientOriginalExtension(),
                        'path'          => $path,
                        'disk'          => $disk,
                        'mime'          => $file->getMimeType(),
                        'size'          => $file->getSize(),
                        'uploaded_by'   => $actorId,
                        'source'        => 'web',
                        'is_private'    => false,
                        'order_column'  => 0,
                    ]);
                }
            }

            return $req->fresh(['attachments']);
        });

        return $this->respondWithToast(
            $request,
            ['message'=>'สร้างคำขอเรียบร้อย','type'=>'success','position'=>'tc','timeout'=>1800,'size'=>'sm'],
            redirect()->route('maintenance.requests.show', ['req' => $req->id]),
            ['data' => $req],
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, MR $req)
    {
        $data = $request->validate([
            'title'        => ['sometimes','required','string','max:255'],
            'description'  => ['nullable','string','max:5000'],
            'asset_id'     => ['nullable','integer','exists:assets,id'],
            'priority'     => ['nullable','string', Rule::in(['low','normal','high','urgent'])],
            'status'       => ['nullable','string', Rule::in(['pending','accepted','in_progress','on_hold','resolved','closed','cancelled'])],
            'request_date' => ['nullable','date'],
            'files.*'      => ['file','max:10240','mimetypes:image/*,application/pdf'],
        ]);

        DB::transaction(function () use ($data, $request, $req) {
            $req->fill($data)->save();

            if ($request->hasFile('files')) {
                $actorId = optional($request->user())->id;
                foreach ($request->file('files') as $file) {
                    $disk = 'public';
                    $path = $file->store("maintenance/{$req->id}", $disk);
                    $req->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'extension'     => $file->getClientOriginalExtension(),
                        'path'          => $path,
                        'disk'          => $disk,
                        'mime'          => $file->getMimeType(),
                        'size'          => $file->getSize(),
                        'uploaded_by'   => $actorId,
                        'source'        => 'web',
                        'is_private'    => false,
                        'order_column'  => 0,
                    ]);
                }
            }
        });

        $req->load('attachments');

        return $this->respondWithToast(
            $request,
            ['message'=>'อัปเดตคำขอเรียบร้อย','type'=>'success','position'=>'br','timeout'=>1600,'size'=>'sm'],
            redirect()->route('maintenance.requests.show', ['req' => $req->id]),
            ['data' => $req]
        );
    }

    public function transition(Request $request, MR $req)
    {
        $data = $request->validate([
            'status'        => ['required', Rule::in(['pending','accepted','in_progress','on_hold','resolved','closed','cancelled'])],
            'note'          => ['nullable','string','max:2000'],
            'technician_id' => ['nullable','integer','exists:users,id'],
        ]);

        DB::transaction(function () use ($req, $data) {
            $before = $req->status;

            $req->status = $data['status'];
            if (!empty($data['technician_id'])) {
                $req->technician_id = $data['technician_id'];
            }
            $req->save();

            if (class_exists(\App\Models\MaintenanceLog::class)) {
                \App\Models\MaintenanceLog::create([
                    'maintenance_request_id' => $req->id,
                    'action' => 'transition',
                    'from'   => $before,
                    'to'     => $req->status,
                    'note'   => $data['note'] ?? null,
                    'user_id'=> optional(Auth::user())->id,
                ]);
            }
        });

        $req->load(['technician:id,name']);

        return $this->respondWithToast(
            $request,
            ['message'=>'บันทึกสถานะเรียบร้อย','type'=>'success','position'=>'tc','timeout'=>1800,'size'=>'sm'],
            redirect()->back(),
            ['data' => $req]
        );
    }

    public function transitionFromBlade(Request $request, MR $req)
    { return $this->transition($request, $req); }

    public function uploadAttachmentFromBlade(Request $request, MR $req)
    {
        $validated = $request->validate([
            'file'       => ['required','file','max:10240','mimetypes:image/*,application/pdf'],
            'is_private' => ['nullable','boolean'],
            'caption'    => ['nullable','string','max:255'],
            'alt_text'   => ['nullable','string','max:255'],
        ]);

        $file = $validated['file'];
        $isPrivate = (bool) ($validated['is_private'] ?? false);
        $disk = $isPrivate ? 'local' : 'public';
        $path = $file->store("maintenance/{$req->id}", $disk);

        $req->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'extension'     => $file->getClientOriginalExtension(),
            'path'          => $path,
            'disk'          => $disk,
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => optional($request->user())->id,
            'source'        => 'web',
            'is_private'    => $isPrivate,
            'caption'       => $validated['caption'] ?? null,
            'alt_text'      => $validated['alt_text'] ?? null,
            'order_column'  => 0,
        ]);

        return $this->respondWithToast(
            $request,
            ['message'=>'อัปโหลดไฟล์แนบแล้ว','type'=>'success','position'=>'br','timeout'=>1800,'size'=>'sm'],
            redirect()->back(),
            ['data' => $req->fresh('attachments')]
        );
    }

    public function destroyAttachment(MR $req, Attachment $attachment)
    {
        abort_unless(
            $attachment->attachable_type === MR::class &&
            (int) $attachment->attachable_id === (int) $req->id,
            404
        );

        $attachment->deleteWithFile();

        return $this->respondWithToast(
            request(),
            ['message'=>'ลบไฟล์แนบแล้ว','type'=>'success','position'=>'br','timeout'=>1600,'size'=>'sm'],
            redirect()->back(),
            ['deleted' => true]
        );
    }

    /** ========== Utilities ========== */
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
}
