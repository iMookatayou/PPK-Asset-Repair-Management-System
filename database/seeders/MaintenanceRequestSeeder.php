<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceAssignment as MA;
use Carbon\Carbon;

class MaintenanceRequestSeeder extends Seeder
{
    // ตัวช่วยนับลำดับช่างเทคนิคสำหรับการสุ่มมอบหมายงาน
    private $techIndex = 0;

    // เริ่มต้นการรัน Seeder สำหรับข้อมูลใบแจ้งซ่อมเริ่มต้น
    public function run(): void
    {
        // ดึงข้อมูลผู้ใช้งานที่จำเป็น (Admin, Technicians, และ User ทั่วไป)
        $admin    = User::query()->where('role', User::ROLE_ADMIN)->first();
        $techs    = User::technicians()->get();
        $reporter = User::query()->where('role', User::ROLE_MEMBER)->first();

        // ตรวจสอบว่ามีข้อมูลพื้นฐานครบถ้วนหรือไม่
        if (!$admin || $techs->isEmpty() || !$reporter) {
            $this->command?->warn('Seeder ต้องการ admin, technician และ user');
            return;
        }

        // ฟังก์ชันตัวช่วยสร้างหมายเลขใบแจ้งซ่อม (Format: MR-YYMMDD-RANDOM)
        $reqNo = fn () => 'MR-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        
        // รายการสถานะงานที่ยังดำเนินการอยู่ (Active Tickets)
        $activeStatuses = [
            MR::STATUS_PENDING,
            MR::STATUS_ACKNOWLEDGED,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD
        ];

        // 1. Generate evenly distributed Active Tickets
        foreach ($activeStatuses as $status) {
            $this->createRequestVariations($status, $techs, $reqNo);
        }

        // 2. Generate Completed Tickets (Resolved and Closed)
        $completedStatuses = [MR::STATUS_RESOLVED, MR::STATUS_CLOSED];
        foreach ($completedStatuses as $status) {
            $this->createCompletedVariations($status, $techs, $reqNo);
        }

        // 3. Add a couple of Cancelled/Rejected for completeness
        MR::factory()->create(['request_no' => $reqNo(), 'status' => MR::STATUS_CANCELLED]);
        MR::factory()->create(['request_no' => $reqNo(), 'status' => MR::STATUS_REJECTED]);
    }

    private function getNextTechId($techs)
    {
        if ($techs->isEmpty()) return null;
        $tech = $techs[$this->techIndex % $techs->count()];
        $this->techIndex++;
        return $tech->id;
    }

    private function createRequestVariations($status, $techs, $reqNo)
    {
        $slaConditions = [
            'on_time' => now()->addHours(10), // Safe
            'at_risk' => now()->addHours(2),  // Under 4h threshold
            'overdue' => now()->subHours(5)   // Passed
        ];

        foreach ($slaConditions as $condition => $slaDueDate) {
            $techId = ($status == MR::STATUS_PENDING || $status == MR::STATUS_ACKNOWLEDGED) ? null : $this->getNextTechId($techs);
            
            $mr = MR::factory()->create([
                'request_no'    => $reqNo(),
                'status'        => $status,
                'technician_id' => $techId,
                'sla_due_date'  => $slaDueDate,
                
                // Set appropriate progression dates
                'request_date'    => now()->subDays(2),
                'acknowledged_at' => in_array($status, [MR::STATUS_ACKNOWLEDGED, MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD]) ? now()->subDays(1)->subHours(10) : null,
                'accepted_at'     => in_array($status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD]) ? now()->subDays(1)->subHours(8) : null,
                'started_at'      => in_array($status, [MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD]) ? now()->subDays(1) : null,
                'on_hold_at'      => $status == MR::STATUS_ON_HOLD ? now()->subHours(6) : null,
                'paused_duration_minutes' => 0,
            ]);

            // Add corresponding MaintenanceAssignment if it implies assignment
            if ($techId) {
                $responseStatus = MA::RESP_ACCEPTED;
                if ($status == MR::STATUS_ACKNOWLEDGED) $responseStatus = MA::RESP_ACKNOWLEDGED;

                MA::create([
                    'maintenance_request_id' => $mr->id,
                    'user_id'                => $techId,
                    'role'                   => 'tech',
                    'is_lead'                => true,
                    'assigned_at'            => now()->subDays(1)->subHours(9),
                    'response_status'        => $responseStatus,
                    'responded_at'           => now()->subDays(1)->subHours(8),
                    'status'                 => MA::STATUS_IN_PROGRESS,
                ]);
            }
        }
    }

    private function createCompletedVariations($status, $techs, $reqNo)
    {
        $slaConditions = [
            'on_time' => [
                'due' => now()->subDays(1)->addHours(4),
                'resolved' => now()->subDays(1) // Resolved 4 hours before due
            ],
            'overdue' => [
                'due' => now()->subDays(1)->subHours(4),
                'resolved' => now()->subDays(1) // Resolved 4 hours after due
            ],
        ];

        foreach ($slaConditions as $condition => $dates) {
            $techId = $this->getNextTechId($techs);
            $closedAt = $status == MR::STATUS_CLOSED ? $dates['resolved']->copy()->addHours(2) : null;

            $mr = MR::factory()->create([
                'request_no'    => $reqNo(),
                'status'        => $status,
                'technician_id' => $techId,
                'sla_due_date'  => $dates['due'],
                
                'request_date'    => $dates['resolved']->copy()->subDays(2),
                'acknowledged_at' => $dates['resolved']->copy()->subDays(1)->subHours(2),
                'accepted_at'     => $dates['resolved']->copy()->subDays(1)->subHours(1),
                'started_at'      => $dates['resolved']->copy()->subDays(1),
                'resolved_at'     => $dates['resolved'],
                'completed_date'  => $closedAt,
                'closed_at'       => $closedAt,
                
                'paused_duration_minutes' => 0,
            ]);

            MA::create([
                'maintenance_request_id' => $mr->id,
                'user_id'                => $techId,
                'role'                   => 'tech',
                'is_lead'                => true,
                'assigned_at'            => $dates['resolved']->copy()->subDays(1)->subHours(1),
                'response_status'        => MA::RESP_ACCEPTED,
                'responded_at'           => $dates['resolved']->copy()->subDays(1)->subHours(1)->addMinutes(10),
                'status'                 => MA::STATUS_DONE,
            ]);
        }
    }
}
