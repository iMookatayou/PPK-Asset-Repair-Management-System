<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluationTestDataSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับสร้างข้อมูลทดสอบระบบประเมินผล (Evaluation UI Testing)
    public function run(): void
    {
        // ดึงรายชื่อพนักงานจำลอง (Demo Staff) เพื่อนำมาใช้เป็นผู้แจ้งซ่อมในการทดสอบ UI
        $targetUsers = User::where('role', 'member')->where('email', 'like', 'demo.staff%')->get();
        
        if ($targetUsers->isEmpty()) {
            return;
        }

        // Find all available technicians to distribute tasks
        $techs = User::technicians()->get();
        if ($techs->isEmpty()) {
            $techs = collect([User::first()]);
        }
        $techCount = $techs->count();
        $techIndex = 0;

        // Cache departments for fast lookup
        $departments = Department::all()->pluck('id', 'code')->toArray();

        $maintenanceTasks = [
            ['title' => 'แจ้งซ่อมเครื่องปรับอากาศมีเสียงดังและไม่เย็น', 'desc' => 'เครื่องปรับอากาศในห้องทำงานมีเสียงดังผิดปกติและลมที่ออกมาไม่เย็น'],
            ['title' => 'เปลี่ยนหลอดไฟส่องสว่างทางเดิน', 'desc' => 'หลอดไฟบริเวณทางเดินชั้น 3 ดับจำนวน 2 ดวง ต้องการให้เปลี่ยนใหม่'],
            ['title' => 'ซ่อมก๊อกน้ำอ่างล้างหน้าอุดตันและรั่วซึม', 'desc' => 'อ่างล้างหน้าในห้องน้ำชายมีน้ำรั่วซึมตลอดเวลาและน้ำไหลค่อย'],
            ['title' => 'ตรวจสอบระบบเครื่องสำรองไฟฟ้า (UPS)', 'desc' => 'เครื่อง UPS ส่งเสียงร้องเตือนสถานะแบตเตอรี่เสื่อมสภาพ'],
            ['title' => 'ซ่อมแซมบานพับประตูห้องประชุม', 'desc' => 'ประตูห้องประชุมใหญ่ปิดไม่สนิทเนื่องจากบานพับชำรุด'],
            ['title' => 'ลงโปรแกรมและไดรเวอร์เครื่องพิมพ์ใหม่', 'desc' => 'เครื่องคอมพิวเตอร์ไม่สามารถเชื่อมต่อกับเครื่องพิมพ์ส่วนกลางได้'],
            ['title' => 'แจ้งซ่อมเก้าอี้สำนักงานล้อเลื่อนชำรุด', 'desc' => 'ล้อเลื่อนของเก้าอี้สำนักงานแตกหัก 1 ข้าง ไม่สามารถใช้งานได้'],
            ['title' => 'ตรวจสอบปลั๊กไฟและระบบไฟฟ้าขัดข้อง', 'desc' => 'ปลั๊กไฟบริเวณมุมห้องทำงานไม่มีกระแสไฟจ่ายออกมา'],
            ['title' => 'ทำความสะอาดแผ่นกรองอากาศเครื่องปรับอากาศ', 'desc' => 'ถึงรอบการบำรุงรักษาตามระยะเวลา (PM) เพื่อทำความสะอาด'],
            ['title' => 'ซ่อมแซมสีผนังและฝ้าเพดานถลอก', 'desc' => 'พบรอยด่างและสีหลุดล่อนบริเวณผนังห้องรับรอง'],
            ['title' => 'แจ้งซ่อมเครื่องสแกนเอกสารขัดข้อง', 'desc' => 'เครื่องสแกนดึงกระดาษซ้อนกันและภาพที่ได้มีเส้นพาดกลาง'],
            ['title' => 'ตรวจสอบระบบเครือข่ายอินเทอร์เน็ตหลุดบ่อย', 'desc' => 'สัญญาณ Wi-Fi ในพื้นที่ไม่เสถียรและหลุดบ่อยเป็นระยะ'],
            ['title' => 'ซ่อมแซมโต๊ะทำงานไม้บวมเนื่องจากถูกน้ำ', 'desc' => 'หน้าท็อปโต๊ะทำงานบวมพองจากการวางแก้วน้ำเป็นเวลานาน'],
            ['title' => 'แจ้งซ่อมโทรศัพท์สายในมีเสียงรบกวน', 'desc' => 'โทรศัพท์สำนักงานมีเสียงซ่ารบกวนตลอดเวลาที่สนทนา'],
            ['title' => 'ตรวจสอบและเติมน้ำยาแอร์ประจำไตรมาส', 'desc' => 'ตรวจสอบประสิทธิภาพการทำความเย็นและเติมน้ำยาแอร์ตามรอบ'],
        ];

        $statuses = [MaintenanceRequest::STATUS_RESOLVED, MaintenanceRequest::STATUS_CLOSED];

        foreach ($targetUsers as $user) {
            $deptId = null;
            if ($user->department && isset($departments[$user->department])) {
                $deptId = $departments[$user->department];
            } else {
                $deptId = array_values($departments)[0] ?? null;
            }

            // Set count: 2 requests per demo staff
            $count = 2;
            $isDemo = true;

            $softwareTypeId = DB::table('maintenance_request_types')->where('name', 'like', '%Software%')->value('id');
            $hardwareTypeId = DB::table('maintenance_request_types')->where('name', 'like', '%Hardware%')->value('id');
            $fallbackTypeId = DB::table('maintenance_request_types')->first()?->id;

            // Create unrated requests (Realistic Tasks)
            for ($i = 0; $i < $count; $i++) {
                $status = MaintenanceRequest::STATUS_CLOSED;
                
                $requestDate = now()->subDays($i + 1)->subHours(rand(1, 12));
                $resolvedAt  = (clone $requestDate)->addHours(rand(2, 24));
                $closedAt    = (clone $resolvedAt)->addHours(rand(1, 5));
                
                $task = $maintenanceTasks[$i % count($maintenanceTasks)];
                
                // Determine sensible type based on title
                $typeId = $fallbackTypeId;
                if (str_contains($task['title'], 'โปรแกรม') || str_contains($task['title'], 'อินเทอร์เน็ต') || str_contains($task['title'], 'เครือข่าย')) {
                    $typeId = $softwareTypeId ?? $fallbackTypeId;
                } else {
                    $typeId = $hardwareTypeId ?? $fallbackTypeId;
                }

                // Round-robin technician assignment
                $assignedTech = $techs[$techIndex % $techCount];
                $techIndex++;

                // Format: [YY][Type][Running] -> 69108xxxx (8 is for test data)
                $requestNo = "69108" . str_pad($user->id, 2, '0', STR_PAD_LEFT) . str_pad($i, 2, '0', STR_PAD_LEFT);

                // --- SLA Metadata Logic ---
                $respTarget = ($typeId == $softwareTypeId) ? 60 : 120; // 1h for software, 2h for hardware
                $resTarget = ($typeId == $softwareTypeId) ? 1440 : 2880; // 24h for software, 48h for hardware

                $responseDueDate = (clone $requestDate)->addMinutes($respTarget);
                $slaDueDate = (clone $requestDate)->addMinutes($resTarget);
                
                // Simulate some compliance/breach (90% compliant)
                $isCompliant = rand(1, 100) <= 90;
                if (!$isCompliant) {
                    $resolvedAt = (clone $slaDueDate)->addHours(rand(2, 48));
                }

                $acknowledgedAt = (clone $requestDate)->addMinutes(rand(5, $respTarget - 5));
                $acceptedAt = (clone $acknowledgedAt)->addMinutes(rand(5, 30));

                $request = MaintenanceRequest::updateOrCreate(
                    ['request_no' => $requestNo],
                    [
                        'reporter_id'     => $user->id,
                        'title'           => $task['title'],
                        'description'     => $task['desc'],
                        'status'          => $status,
                        'request_date'    => $requestDate,
                        'acknowledged_at' => $acknowledgedAt,
                        'accepted_at'     => $acceptedAt,
                        'resolved_at'     => $resolvedAt,
                        'closed_at'       => $closedAt,
                        'completed_date'  => $closedAt ?? $resolvedAt,
                        'response_due_date' => $responseDueDate,
                        'sla_due_date'      => $slaDueDate,
                        'technician_id'   => $assignedTech->id,
                        'type_id'         => $typeId,
                        'department_id'   => $deptId,
                        'location_text'   => 'ตึกเทคโนโลยี ชั้น ' . rand(1, 5),
                        'source'          => 'web',
                        'extra'           => ['is_demo' => $isDemo, 'is_manual_protected' => !$isDemo],
                    ]
                );

                // DO NOT Create Rating for Admin tasks (Keep them in the 'Pending Evaluation' list - 12 items total)
                // This ensures they show up on the left side of the Evaluate Ratings page


                // Create Assignment (Critical for rating logic)
                MaintenanceAssignment::updateOrCreate(
                    [
                        'maintenance_request_id' => $request->id,
                        'user_id'                => $assignedTech->id,
                    ],
                    [
                        'role'                   => 'technician',
                        'is_lead'                => true,
                        'status'                 => 'done',
                        'response_status'        => 'accepted',
                        'assigned_at'            => (clone $resolvedAt)->subDays(2),
                    ]
                );

                // Create Log
                MaintenanceLog::create([
                    'request_id' => $request->id,
                    'user_id'    => $assignedTech->id,
                    'action'     => MaintenanceLog::ACTION_TRANSITION,
                    'note'       => "ทดสอบ: ซ่อมบำรุงเสร็จสิ้น (" . ($status === 'closed' ? 'ปิดงานสำเร็จ' : 'ดำเนินการเป็นที่เรียบร้อย') . ")",
                    'created_at' => $resolvedAt,
                ]);
            }
        }
    }

    private function generateRequestNo(Carbon $date, int $index, int $userId): string
    {
        $beYear = $date->year + 543;
        $yy = substr((string) $beYear, -2);
        // Combine userId and index to ensure uniqueness
        $random = str_pad(($userId % 10) . $index . rand(0, 999), 5, '0', STR_PAD_LEFT);
        return "{$yy}10{$random}";
    }
}
