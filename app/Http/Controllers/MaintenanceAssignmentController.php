<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Support\Toast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MaintenanceAssignmentController extends Controller
{
    public function store(Request $request, MaintenanceRequest $req)
    {
        Gate::authorize('assign', $req);

        // ตัด lead_user_id ออกจากการ validate ไปเลย ไม่ใช้แล้ว
        $data = $request->validate([
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $actorId = $request->user()?->id;

        // เตรียมรายชื่อช่าง (คัดเฉพาะที่มีตัวตนและไม่ซ้ำ)
        $userIds = collect($data['user_ids'] ?? [])
            ->filter()
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $workers = User::query()
            ->whereIn('id', $userIds->all())
            ->get(['id', 'name', 'role'])
            ->keyBy('id');

        DB::transaction(function () use ($req, $workers, $userIds, $actorId) {
            $now = now();

            $lockedReq = MaintenanceRequest::query()
                ->whereKey($req->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $lockedReq->assignments()->get()->keyBy('user_id');

            // บังคับเป็น null เสมอ เพราะเราไม่ใช้ระบบช่างรับผิดชอบหลัก (Lead) แล้ว
            $lockedReq->technician_id = null;

            if ($workers->isNotEmpty() && $lockedReq->assigned_date === null) {
                $lockedReq->assigned_date = $now;
            }
            $lockedReq->save();

            foreach ($userIds as $uid) {
                $worker = $workers->get((int) $uid);
                if (!$worker) continue;

                $assignment = $existing->get($worker->id);

                if ($assignment) {
                    // ถ้ามีรายชื่ออยู่แล้ว แต่อาจจะเคยโดนยกเลิกไป ให้ดึงกลับมาใหม่
                    $updateData = [
                        'role'    => $worker->role,
                        'is_lead' => false, // ทุกคนคือช่างเท่ากันหมด ไม่มี Lead
                    ];

                    if ($assignment->status === MaintenanceAssignment::STATUS_CANCELLED) {
                        $updateData['status'] = MaintenanceAssignment::STATUS_IN_PROGRESS;
                        $updateData['response_status'] = MaintenanceAssignment::RESP_PENDING;
                        $updateData['responded_at'] = null;
                    }

                    $assignment->update($updateData);
                } else {
                    // ถ้าเป็นช่างใหม่ที่เพิ่งเพิ่มเข้ามา
                    MaintenanceAssignment::create([
                        'maintenance_request_id' => $lockedReq->id,
                        'user_id'                => $worker->id,
                        'role'                   => $worker->role,
                        'is_lead'                => false,
                        'assigned_at'            => $now,
                        'response_status'        => MaintenanceAssignment::RESP_PENDING,
                        'status'                 => MaintenanceAssignment::STATUS_IN_PROGRESS,
                    ]);
                }
            }

            $keepIds = $userIds->all();
            $lockedReq->assignments()
                ->whereNotIn('user_id', $keepIds)
                ->update([
                    'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                    'is_lead'         => false,
                    'response_status' => MaintenanceAssignment::RESP_PENDING,
                    'responded_at'    => null,
                    'updated_at'      => $now,
                ]);
        });

        Log::info('[MaintenanceAssignment::store] team updated - Lead system removed', [
            'request_id' => $req->id,
            'user_count' => $userIds->count(),
            'actor_id'   => $actorId,
        ]);

        return back()->with('toast', Toast::success('อัปเดตรายชื่อช่างเรียบร้อยแล้ว', 1800));
    }

    public function destroy(MaintenanceAssignment $assignment)
    {
        Gate::authorize('assign', $assignment->maintenanceRequest);

        $actorId = Auth::id();

        // // ป้องกันการยกเลิกงานที่ทำเสร็จไปแล้ว
        if ($assignment->status === MaintenanceAssignment::STATUS_DONE) {
            Log::warning('[MaintenanceAssignment::destroy] attempt to cancel completed work', [
                'assignment_id' => $assignment->id,
                'actor_id'      => $actorId,
            ]);

            return back()->with('toast', Toast::warning('งานนี้ถูกทำเสร็จแล้ว ไม่สามารถยกเลิกได้', 2200));
        }

        $assignment->update([
            'status'          => MaintenanceAssignment::STATUS_CANCELLED,
            'is_lead'         => false,
            'response_status' => MaintenanceAssignment::RESP_PENDING,
            'responded_at'    => null,
        ]);

        Log::info('[MaintenanceAssignment::destroy] assignment cancelled', [
            'assignment_id' => $assignment->id,
            'user_id'       => $assignment->user_id,
            'actor_id'      => $actorId,
        ]);

        return back()->with('toast', Toast::success('ยกเลิกการมอบหมายช่างเรียบร้อยแล้ว', 1800));
    }
}
