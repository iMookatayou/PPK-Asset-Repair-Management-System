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
            'technician_id' => $tech1->id,
        ]);

        MA::create([
            'maintenance_request_id' => $mrAccepted->id,
            'user_id'                => $tech1->id,
            'role'                   => 'tech',
            'is_lead'                => true,
            'assigned_at'            => now()->subHours(8),

            'response_status'        => MA::RESP_ACCEPTED,
            'responded_at'           => now()->subHours(7),
            'remark'                 => null,

            'status'                 => MA::STATUS_IN_PROGRESS,
        ]);

        $mrInProgress = MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_IN_PROGRESS,
            'technician_id' => $tech1->id,
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

        MR::factory()->create([
            'request_no'    => $reqNo(),
            'status'        => MR::STATUS_CANCELLED,
            'technician_id' => null,
        ]);
    }
}
