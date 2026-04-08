<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceAssignment as MA;

class MaintenanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::query()->where('role', 'admin')->first();
        $techs    = User::query()->where('role', 'tech')->take(3)->get();
        $reporter = User::query()->where('role', 'user')->first();

        if (!$admin || $techs->count() < 2 || !$reporter) {
            $this->command?->warn('Seeder ต้องการ admin, tech อย่างน้อย 2 คน และ user');
            return;
        }

        [$tech1, $tech2] = [$techs[0], $techs[1]];

        $reqNo = fn () => 'MR-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));

        MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_PENDING,
            'technician_id' => null,
        ]);

        $mrAck = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_ACKNOWLEDGED,
            'technician_id' => null,
            'acknowledged_at' => now()->subHours(4),
            'sla_due_date' => now()->addDays(7),
        ]);

        MA::create([
            'maintenance_request_id' => $mrAck->id,
            'user_id'                => $tech1->id,
            'role'                   => 'tech',
            'is_lead'                => false,
            'assigned_at'            => now()->subHours(3),

            'response_status'        => MA::RESP_ACKNOWLEDGED,
            'responded_at'           => now()->subHours(2),
            'remark'                 => null,

            'status'                 => MA::STATUS_IN_PROGRESS,
        ]);

        $mrReject = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_ACKNOWLEDGED,
            'technician_id' => null,
            'acknowledged_at' => now()->subHours(7),
            'sla_due_date' => now()->addDays(7),
        ]);

        MA::create([
            'maintenance_request_id' => $mrReject->id,
            'user_id'                => $tech2->id,
            'role'                   => 'tech',
            'is_lead'                => false,
            'assigned_at'            => now()->subHours(6),

            'response_status'        => MA::RESP_REJECTED,
            'responded_at'           => now()->subHours(5),
            'remark'                 => 'ภาระงานเต็มและไม่อยู่เวรในช่วงเวลาที่แจ้ง',

            'status'                 => MA::STATUS_CANCELLED,
        ]);

        $mrAccepted = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_ACCEPTED,
            'technician_id' => null, // ไม่มีช่างรับผิดชอบหลักเมื่องานอยู่ในสถานะ รับเรื่อง
            'acknowledged_at' => now()->subHours(9),
            'accepted_at' => now()->subHours(8),
            'sla_due_date' => now()->addDays(7),
        ]);

        $mrInProgress = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_IN_PROGRESS,
            'technician_id' => $tech1->id,
            'acknowledged_at' => now()->subDays(2),
            'accepted_at' => now()->subDay(),
            'started_at' => now()->subHours(12),
            'sla_due_date' => now()->addDays(5),
        ]);

        MA::create([
            'maintenance_request_id' => $mrInProgress->id,
            'user_id'                => $tech1->id,
            'role'                   => 'tech',
            'is_lead'                => true,
            'assigned_at'            => now()->subDay(),

            'response_status'        => MA::RESP_ACCEPTED,
            'responded_at'           => now()->subDay()->addMinutes(10),
            'remark'                 => null,

            'status'                 => MA::STATUS_IN_PROGRESS,
        ]);

        $mrOnHold = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_ON_HOLD,
            'technician_id' => $tech1->id,
            'acknowledged_at' => now()->subDays(3),
            'accepted_at' => now()->subDays(2),
            'started_at' => now()->subDays(1),
            'on_hold_at' => now()->subHours(5),
            'sla_due_date' => now()->addDays(4),
            'paused_duration_minutes' => 0, // currently pausing
        ]);

        MA::create([
            'maintenance_request_id' => $mrOnHold->id,
            'user_id'                => $tech1->id,
            'role'                   => 'tech',
            'is_lead'                => true,
            'assigned_at'            => now()->subDays(2),

            'response_status'        => MA::RESP_ACCEPTED,
            'responded_at'           => now()->subDays(2)->addMinutes(15),
            'remark'                 => 'รออะไหล่จากศูนย์',

            'status'                 => MA::STATUS_IN_PROGRESS,
        ]);

        $mrResolved = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_RESOLVED,
            'technician_id' => $tech2->id,
            'acknowledged_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(4),
            'started_at' => now()->subDays(3),
            'on_hold_at' => now()->subDays(2), // was on hold
            'resolved_at' => now()->subHours(2), // resolved recently
            'sla_due_date' => now()->addDays(2),
            'paused_duration_minutes' => 1440, // 24 hours of total paused time
        ]);

        MA::create([
            'maintenance_request_id' => $mrResolved->id,
            'user_id'                => $tech2->id,
            'role'                   => 'tech',
            'is_lead'                => true,
            'assigned_at'            => now()->subDays(4),

            'response_status'        => MA::RESP_ACCEPTED,
            'responded_at'           => now()->subDays(4)->addMinutes(30),
            'remark'                 => null,

            'status'                 => MA::STATUS_IN_PROGRESS,
        ]);

        MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_CANCELLED,
            'technician_id' => null,
        ]);
    }
}
