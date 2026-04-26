<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceLog;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MaintenanceLogController extends Controller
{
    public function index(MaintenanceRequest $req)
    {
        Gate::authorize('view', $req);

        $logs = $req->logs()
            ->select([
                'id',
                'request_id',
                'user_id',
                'action',
                'note',
                'from_status',
                'to_status',
                'created_at'
            ])
            ->with(['user:id,name'])
            ->latest('created_at')
            ->paginate(20);

        Log::info('[MaintenanceLog::index] listed logs', [
            'request_id' => $req->id,
            'total'      => $logs->total(),
            'actor_id'   => request()->user()?->id,
        ]);

        return response()->json($logs);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'request_id'  => ['required', 'integer', 'exists:maintenance_requests,id'],
            'action'      => ['required', 'string', 'max:100', Rule::in([
                MaintenanceLog::ACTION_UPDATE,
                MaintenanceLog::ACTION_TRANSITION,
            ])],
            'note'        => ['nullable', 'string', 'max:2000'],
            'from_status' => ['nullable', 'string', 'max:50'],
            'to_status'   => ['nullable', 'string', 'max:50'],
        ]);

        $req = MaintenanceRequest::findOrFail($data['request_id']);
        Gate::authorize('update', $req);

        $actorId = $request->user()?->id;

        $log = MaintenanceLog::create([
            'request_id'  => $req->id,
            'user_id'     => $actorId,
            'action'      => $data['action'],
            'note'        => $data['note'] ?? null,
            'from_status' => $data['from_status'] ?? null,
            'to_status'   => $data['to_status'] ?? null,
            'created_at'  => now(),
        ]);

        Log::info('[MaintenanceLog::store] manual log created', [
            'log_id'      => $log->id,
            'request_id'  => $req->id,
            'action'      => $log->action,
            'from_status' => $log->from_status,
            'to_status'   => $log->to_status,
            'actor_id'    => $actorId,
        ]);

        return response()->json([
            'message' => 'created',
            'data'    => $log->load('user:id,name'),
        ], 201);
    }

    public function show(MaintenanceLog $maintenanceLog)
    {
        // // ตรวจสอบความสัมพันธ์กับ Request ก่อน Authorize
        $maintenanceLog->loadMissing('request');

        if (!$maintenanceLog->request) {
            abort(404, 'ไม่พบใบงานที่เกี่ยวข้องกับ Log นี้');
        }

        Gate::authorize('view', $maintenanceLog->request);

        Log::info('[MaintenanceLog::show] log viewed', [
            'log_id'     => $maintenanceLog->id,
            'request_id' => $maintenanceLog->request_id,
            'action'     => $maintenanceLog->action,
            'actor_id'   => request()->user()?->id,
        ]);

        return response()->json(
            $maintenanceLog->load(['user:id,name'])
        );
    }
}
