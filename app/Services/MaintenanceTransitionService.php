<?php

namespace App\Services;

use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;
use App\Models\User;
use App\Models\SlaConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MaintenanceTransitionService
{
    /**
     * ดำเนินการอัปเดตสถานะหลักของใบงานซ่อม
     * พร้อมอัปเดต Timestamps ที่เกี่ยวข้อง จัดการช่าง และเก็บประวัติ Log
     */
    public function applyTransition(MR $req, array $data, ?int $actorId = null): MR
    {
        return DB::transaction(function () use ($req, $data, $actorId) {
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
            $allowedNext = MR::ALLOWED_TRANSITIONS;
            $isStatusChange = ($from !== $targetStatus);

            if ($isStatusChange) {
                $nexts = $allowedNext[$from] ?? [];
                if (!in_array($targetStatus, $nexts, true)) {
                    abort(409, 'สถานะไม่ถูกต้อง');
                }

                // บังคับระบุเหตุผลเมื่อพักงาน
                if ($targetStatus === MR::STATUS_ON_HOLD && empty(trim($data['note'] ?? ''))) {
                    abort(409, 'ต้องระบุเหตุผลในการพักงาน');
                }

                // คำนวณเวลาพักงานเมื่อออกจากการพักงาน
                if ($from === MR::STATUS_ON_HOLD && $locked->on_hold_at) {
                    $onHoldAt = Carbon::parse($locked->on_hold_at);
                    $pausedMins = (int) ceil($onHoldAt->diffInMinutes(now()));
                    $locked->paused_duration_minutes = (int) $locked->paused_duration_minutes + $pausedMins;
                    
                    if ($locked->sla_due_date) {
                        $locked->sla_due_date = Carbon::parse($locked->sla_due_date)->addMinutes($pausedMins);
                    }
                }

                $locked->status = $targetStatus;
            }

            $canChangeTech = in_array($locked->status, [MR::STATUS_ACKNOWLEDGED, MR::STATUS_ACCEPTED], true);

            if (
                $canChangeTech &&
                array_key_exists('technician_id', $data) &&
                !empty($data['technician_id']) &&
                (int) $locked->technician_id !== (int) $data['technician_id']
            ) {
                $locked->technician_id = (int) $data['technician_id'];
            }

            // ปรับเปลี่ยนให้: ใครก็ตามที่กด "เริ่มงาน (In Progress) คนแรก" 
            // จะถูกผูกชื่อเป็นช่างหลักชั่วคราว (Auto-assign) หากงานนั้นยังว่างอยู่
            if ($locked->status === MR::STATUS_IN_PROGRESS && empty($locked->technician_id) && $actorId) {
                $locked->technician_id = (int) $actorId;
            }

            $now = now();

            switch ($locked->status) {
                case MR::STATUS_ACKNOWLEDGED:
                    $locked->acknowledged_at ??= $now;
                    break;
                case MR::STATUS_ACCEPTED:
                    if (!$locked->accepted_at) {
                        $locked->accepted_at = $now;
                        $locked->assigned_date ??= $now;
                        
                        $slaTarget = SlaConfig::where('priority_level', $locked->priority ?? SlaConfig::PRIORITY_DEFAULT)
                            ->where('is_active', true)->first() 
                            ?? SlaConfig::where('priority_level', SlaConfig::PRIORITY_DEFAULT)->first();
                        
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

            $newTechId   = (int) ($locked->technician_id ?? 0);
            $techChanged = ($originalTechId !== $newTechId);

            if (($techChanged || $isStatusChange) && $newTechId > 0) {
                $currentTeamIds = $locked->assignments()
                    ->where('status', '!=', MaintenanceAssignment::STATUS_CANCELLED)
                    ->pluck('user_id')
                    ->toArray();
                    
                $currentTeamIds = array_filter($currentTeamIds, fn($id) => $id != $newTechId);
                array_unshift($currentTeamIds, $newTechId);
                
                $this->syncAssignments($locked, array_values($currentTeamIds), $actorId);
            }

            if (class_exists(MaintenanceLog::class)) {
                if ($techChanged) {
                    $locked->loadMissing('technician:id,name');
                }

                $defaultNote = $data['note'] ?? $this->defaultNoteForStatus($locked->status, $actorId, $locked);

                if ($techChanged && $locked->technician) {
                    $defaultNote = trim(($defaultNote ? $defaultNote . ' • ' : '') . 'ช่าง: ' . $locked->technician->name);
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

            return $locked;
        });
    }

    /**
     * ดำเนินการอัปเดตทีมงาน (Assignment) ตามข้อมูลล่าสุดของใบงาน
     */
    public function syncAssignments(MR $req, array $userIds, ?int $actorId = null): void
    {
        if (empty($userIds)) {
            MaintenanceAssignment::where('maintenance_request_id', $req->id)->update([
                'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                'is_lead'         => false,
                'response_status' => MaintenanceAssignment::RESP_PENDING,
                'responded_at'    => null,
                'updated_at'      => now(),
            ]);
            Log::info('[MaintenanceTransitionService] All workers marked as cancelled', ['mr_id' => $req->id]);
            return;
        }

        MaintenanceAssignment::where('maintenance_request_id', $req->id)
            ->whereNotIn('user_id', $userIds)
            ->update([
                'status'          => MaintenanceAssignment::STATUS_CANCELLED,
                'is_lead'         => false,
                'response_status' => MaintenanceAssignment::RESP_PENDING,
                'responded_at'    => null,
                'updated_at'      => now(),
            ]);

        $status = match ($req->status) {
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED => MaintenanceAssignment::STATUS_DONE,
            default           => MaintenanceAssignment::STATUS_IN_PROGRESS,
        };

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

            if (empty($as->assigned_at)) {
                $as->assigned_at = now();
                $as->save();
            }
        }
    }

    protected function defaultNoteForStatus(string $status, ?int $actorId, MR $req): string
    {
        return match ($status) {
            MR::STATUS_ACKNOWLEDGED => 'รับทราบแล้ว',
            MR::STATUS_ACCEPTED     => 'รับเรื่องแล้ว',
            MR::STATUS_IN_PROGRESS  => 'กำลังดำเนินการ',
            MR::STATUS_ON_HOLD      => 'พักงาน/รออะไหล่',
            MR::STATUS_RESOLVED     => 'ซ่อมเสร็จแล้ว',
            MR::STATUS_CLOSED       => 'ปิดงาน',
            MR::STATUS_CANCELLED    => 'ยกเลิก',
            MR::STATUS_REJECTED     => 'ไม่รับเรื่อง',
            default                 => 'อัปเดตสถานะ',
        };
    }
}
