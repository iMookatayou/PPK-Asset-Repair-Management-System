<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminEvaluationSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับข้อมูลการประเมินโดยผู้ดูแลระบบ (Admin Evaluation)
    public function run(): void
    {
        // 1. Cleanup: ล้างข้อมูลเก่าที่เป็น Demo ของกลุ่มเดิมออกให้หมดก่อนเพื่อป้องกันข้อมูลซ้ำซ้อน
        Schema::disableForeignKeyConstraints();
        $oldIds = MaintenanceRequest::whereJsonContains('extra->seed_group', 'admin_eval_12')->pluck('id');
        
        if ($oldIds->isNotEmpty()) {
            DB::table('maintenance_ratings')->whereIn('maintenance_request_id', $oldIds)->delete();
            DB::table('maintenance_assignments')->whereIn('maintenance_request_id', $oldIds)->delete();
            DB::table('maintenance_logs')->whereIn('request_id', $oldIds)->delete();
            DB::table('maintenance_operation_logs')->whereIn('maintenance_request_id', $oldIds)->delete();
            DB::table('maintenance_requests')->whereIn('id', $oldIds)->delete();
        }
        
        // ล้างงานเก่าที่อาจจะค้างจากรอบที่แล้วด้วย (admin_eval_24)
        $oldIds24 = MaintenanceRequest::whereJsonContains('extra->seed_group', 'admin_eval_24')->pluck('id');
        if ($oldIds24->isNotEmpty()) {
            DB::table('maintenance_ratings')->whereIn('maintenance_request_id', $oldIds24)->delete();
            DB::table('maintenance_assignments')->whereIn('maintenance_request_id', $oldIds24)->delete();
            DB::table('maintenance_logs')->whereIn('request_id', $oldIds24)->delete();
            DB::table('maintenance_operation_logs')->whereIn('maintenance_request_id', $oldIds24)->delete();
            DB::table('maintenance_requests')->whereIn('id', $oldIds24)->delete();
        }
        Schema::enableForeignKeyConstraints();

        // ดึง Dev Admin (ID 409) หรือคนแรกที่เป็น admin
        $admin = User::find(409) ?? User::where('role', 'admin')->first();
        $techs = User::technicians()->get();
        
        if (!$admin || $techs->isEmpty()) return;

        $techCount = $techs->count();
        $realisticTasks = [
            'แจ้งซ่อมเครื่องปรับอากาศมีเสียงดังและไม่เย็น',
            'เปลี่ยนหลอดไฟส่องสว่างทางเดินอาคารอำนวยการ',
            'ซ่อมก๊อกน้ำอ่างล้างหน้าอุดตันและรั่วซึม',
            'ตรวจสอบระบบ UPS ห้องเซิร์ฟเวอร์สำรอง',
            'ซ่อมแซมบานพับประตูห้องตรวจ 5',
            'ลงโปรแกรมและอัปเดตแอนตี้ไวรัสเครื่องคอมพิวเตอร์',
            'แจ้งซ่อมเก้าอี้สำนักงานล้อหลุด',
            'ตรวจสอบสายแลนจุดบริการ OPD',
            'ซ่อมปั๊มน้ำอาคารพักพยาบาล',
            'เปลี่ยนไส้กรองเครื่องกรองน้ำดื่มชั้น 3',
            'ซ่อมเครื่องพิมพ์เลเซอร์มีรอยดำ',
            'ตรวจสอบระบบโทรศัพท์ภายในขัดข้อง',
        ];

        $swType = \App\Models\MaintenanceRequestType::where('name', 'like', '%Software%')->first()?->id ?? 1;
        $hwType = \App\Models\MaintenanceRequestType::where('name', 'like', '%Hardware%')->first()?->id ?? 3;
        $deptId = $admin->department_id ?? (\App\Models\Department::first()?->id ?? 1);

        // สร้างแค่ 12 งานสำหรับ Admin คนนี้คนเดียว
        for ($i = 0; $i < 12; $i++) {
            $requestDate = now()->subDays($i + 1)->subHours(rand(1, 10));
            $ackAt       = (clone $requestDate)->addMinutes(rand(5, 30));
            $acceptAt    = (clone $ackAt)->addMinutes(rand(10, 60));
            $startAt     = (clone $acceptAt)->addMinutes(rand(5, 15));
            $resolvedAt  = (clone $startAt)->addHours(rand(1, 4));
            $closedAt    = (clone $resolvedAt)->addHours(rand(1, 2));
            
            $assignedTech = $techs[$i % $techCount];
            $taskTitle = $realisticTasks[$i];
            
            $isSoftware = (str_contains($taskTitle, 'โปรแกรม') || str_contains($taskTitle, 'คอมพิวเตอร์') || str_contains($taskTitle, 'สายแลน') || str_contains($taskTitle, 'โทรศัพท์'));
            $typeId = $isSoftware ? $swType : $hwType;

            $requestNo = MaintenanceRequest::generateLegacyRequestNo();

            $request = MaintenanceRequest::create([
                'request_no'      => $requestNo,
                'reporter_id'     => $admin->id,
                'department_id'   => $deptId,
                'type_id'         => $typeId,
                'title'           => $taskTitle,
                'status'          => MaintenanceRequest::STATUS_CLOSED,
                'request_date'    => $requestDate,
                'acknowledged_at' => $ackAt,
                'accepted_at'     => $acceptAt,
                'started_at'      => $startAt,
                'resolved_at'     => $resolvedAt,
                'closed_at'       => $closedAt,
                'completed_date'  => $closedAt,
                'technician_id'   => $assignedTech->id,
                'location_text'   => 'อาคารอำนวยการ',
                'source'          => 'web',
                'extra'           => [
                    'is_demo'    => true,
                    'seed_group' => 'admin_eval_12'
                ],
            ]);

            MaintenanceAssignment::create([
                'maintenance_request_id' => $request->id,
                'user_id'                => $assignedTech->id,
                'role'                   => 'technician',
                'is_lead'                => true,
                'status'                 => 'done',
                'response_status'        => 'accepted',
                'assigned_at'            => $requestDate,
            ]);

            \App\Models\MaintenanceOperationLog::create([
                'maintenance_request_id' => $request->id,
                'user_id'          => $assignedTech->id,
                'operation_date'   => $resolvedAt,
                'operation_method' => 'service_fee',
                'issue_software'   => $isSoftware ? 1 : 0,
                'issue_hardware'   => !$isSoftware ? 1 : 0,
                'remark'           => 'ดำเนินการเสร็จสมบูรณ์',
            ]);

            $statusFlow = [
                MaintenanceRequest::STATUS_ACKNOWLEDGED => $ackAt,
                MaintenanceRequest::STATUS_ACCEPTED     => $acceptAt,
                MaintenanceRequest::STATUS_IN_PROGRESS  => $startAt,
                MaintenanceRequest::STATUS_RESOLVED     => $resolvedAt,
                MaintenanceRequest::STATUS_CLOSED       => $closedAt,
            ];

            foreach ($statusFlow as $status => $time) {
                MaintenanceLog::create([
                    'request_id' => $request->id,
                    'to_status'  => $status,
                    'user_id'    => $assignedTech->id,
                    'action'     => MaintenanceLog::ACTION_TRANSITION,
                    'created_at' => $time,
                ]);
            }
        }
    }
}
