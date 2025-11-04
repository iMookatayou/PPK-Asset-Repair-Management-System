<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Asset;
use App\Models\MaintenanceRequest as MR;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        // ===== Tunable via .env =====
        $assetCount     = (int) env('DEMO_ASSET_COUNT', 120);
        $techCount      = (int) env('DEMO_TECH_COUNT', 6);
        $staffCount     = (int) env('DEMO_STAFF_COUNT', 18);
        $requestCount   = (int) env('DEMO_SEED_COUNT', 300);
        $chunkSize      = (int) env('DEMO_CHUNK', 500);

        // ===== 1) Users =====
        // Admin หากยังไม่มี
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('Admin123!'),
                'role' => 'admin',
                'department' => 'IT',
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // Technicians / Staff
        $technicians = User::factory($techCount)->create([
            'role' => 'technician',
            'department' => 'IT Support',
        ]);
        $staffs = User::factory($staffCount)->create([
            'role' => 'staff',
            'department' => fake()->randomElement(['ER','OPD','Ward','Admin','Lab']),
        ]);

        $techIds   = $technicians->pluck('id')->all();
        $staffIds  = $staffs->pluck('id')->all();

        // ===== 2) Assets =====
        $assets = Asset::factory($assetCount)->create();
        $assetIds = $assets->pluck('id')->all();

        // เติมฟิลด์ (ถ้ามี)
        $types      = ['เครื่องใช้ไฟฟ้า','อุปกรณ์สำนักงาน','คอมพิวเตอร์','เครื่องมือแพทย์'];
        $categories = ['คอมพิวเตอร์','เครื่องพิมพ์','เครื่องปรับอากาศ','โต๊ะทำงาน','หลอดไฟ','เตียงคนไข้'];
        $locations  = ['ER','OPD','Ward','Admin','IT Room','Lab'];
        $hasType     = Schema::hasColumn('assets','type');
        $hasCategory = Schema::hasColumn('assets','category');
        $hasLocation = Schema::hasColumn('assets','location');

        foreach ($assets as $a) {
            $dirty = false;
            if ($hasType)     { $a->type     = $types[array_rand($types)]; $dirty = true; }
            if ($hasCategory) { $a->category = $categories[array_rand($categories)]; $dirty = true; }
            if ($hasLocation) { $a->location = $locations[array_rand($locations)]; $dirty = true; }
            if ($dirty) $a->save();
        }

        // ===== 3) Maintenance Requests (bulk insert + realistic timelines) =====
        $statuses = [
            MR::STATUS_PENDING,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD,
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED,
        ];
        $priorities = [MR::PRIORITY_LOW, MR::PRIORITY_MEDIUM, MR::PRIORITY_HIGH, MR::PRIORITY_URGENT];

        $hasRemark        = Schema::hasColumn('maintenance_requests', 'remark');
        $hasAssignedDate  = Schema::hasColumn('maintenance_requests', 'assigned_date');
        $hasCompletedDate = Schema::hasColumn('maintenance_requests', 'completed_date'); // legacy
        $hasAcceptedAt    = Schema::hasColumn('maintenance_requests', 'accepted_at');
        $hasStartedAt     = Schema::hasColumn('maintenance_requests', 'started_at');
        $hasOnHoldAt      = Schema::hasColumn('maintenance_requests', 'on_hold_at');
        $hasResolvedAt    = Schema::hasColumn('maintenance_requests', 'resolved_at');
        $hasClosedAt      = Schema::hasColumn('maintenance_requests', 'closed_at');

        $rows = [];
        $now  = Carbon::now();

        $makeTimeline = function (string $status, Carbon $base) use (
            $hasAssignedDate,$hasAcceptedAt,$hasStartedAt,$hasOnHoldAt,$hasResolvedAt,$hasClosedAt,$hasCompletedDate
        ) {
            // ไล่เวลาแบบสมเหตุสมผล
            $assigned   = null; $accepted = null; $started = null; $onHold = null; $resolved = null; $closed = null; $completed = null;

            // โอกาสเป็นงานยังไม่ assign
            $assignedChance = random_int(0, 100) < 75;

            if (in_array($status, [MR::STATUS_ACCEPTED, MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD, MR::STATUS_RESOLVED, MR::STATUS_CLOSED], true)) {
                $assigned = (clone $base)->addDays(random_int(0, 3));
                $accepted = (clone $assigned)->addHours(random_int(0, 36));
            } elseif ($assignedChance && in_array($status, [MR::STATUS_PENDING], true)) {
                // บาง pending มี assign ไว้ก่อน แต่ยังไม่เริ่ม
                if (random_int(0,100) < 20) {
                    $assigned = (clone $base)->addDays(random_int(0, 2));
                    $accepted = (clone $assigned)->addHours(random_int(0, 24));
                }
            }

            if (in_array($status, [MR::STATUS_IN_PROGRESS, MR::STATUS_ON_HOLD, MR::STATUS_RESOLVED, MR::STATUS_CLOSED], true)) {
                $started = ($accepted ?: (clone $base)->addDays(random_int(0, 5)))->addHours(random_int(1, 24));
            }

            if (in_array($status, [MR::STATUS_ON_HOLD, MR::STATUS_RESOLVED, MR::STATUS_CLOSED], true)) {
                $onHold = ($started ?: (clone $base)->addDays(2))->addHours(random_int(2, 48));
            }

            if (in_array($status, [MR::STATUS_RESOLVED, MR::STATUS_CLOSED], true)) {
                $resolved = ($onHold ?: $started ?: (clone $base)->addDays(3))->addHours(random_int(2, 48));
                $completed = $resolved; // legacy mirror
            }

            if ($status === MR::STATUS_CLOSED) {
                $closed = ($resolved ?: (clone $base)->addDays(5))->addHours(random_int(1, 24));
            }

            return [
                $hasAssignedDate  ? $assigned  : null,
                $hasAcceptedAt    ? $accepted  : null,
                $hasStartedAt     ? $started   : null,
                $hasOnHoldAt      ? $onHold    : null,
                $hasResolvedAt    ? $resolved  : null,
                $hasClosedAt      ? $closed    : null,
                $hasCompletedDate ? $completed : null,
            ];
        };

        DB::transaction(function () use (
            $requestCount,$assetIds,$staffIds,$techIds,$priorities,$statuses,
            $now,$chunkSize,$hasRemark,$makeTimeline,&$rows
        ) {
            for ($i = 1; $i <= $requestCount; $i++) {
                $createdAt = (clone $now)->subMonths(random_int(0, 11))->subDays(random_int(0, 28))->setTime(random_int(8,17), random_int(0,59));
                $status    = $statuses[array_rand($statuses)];
                $assetId   = $assetIds[array_rand($assetIds)];
                $reporter  = $staffIds[array_rand($staffIds)];
                $priority  = $priorities[array_rand($priorities)];

                // บาง pending จะยังไม่มี technician
                $techId = null;
                if ($status !== MR::STATUS_PENDING || random_int(0,100) < 40) {
                    $techId = $techIds[array_rand($techIds)];
                }

                [$assigned,$accepted,$started,$onHold,$resolved,$closed,$completedLegacy] =
                    $makeTimeline($status, $createdAt);

                $row = [
                    'asset_id'      => $assetId,
                    'reporter_id'   => $reporter,
                    'technician_id' => $techId,
                    'title'         => 'แจ้งซ่อม #'.$i,
                    'description'   => 'รายละเอียดปัญหาเบื้องต้น',
                    'priority'      => $priority,
                    'status'        => $status,
                    'request_date'  => $createdAt,
                    'assigned_date' => $assigned,
                    'accepted_at'   => $accepted,
                    'started_at'    => $started,
                    'on_hold_at'    => $onHold,
                    'resolved_at'   => $resolved,
                    'closed_at'     => $closed,
                    // legacy mirror เพื่อให้หน้าจอเก่าๆ ยังแสดงได้
                    'completed_date'=> $completedLegacy,
                    'created_at'    => $createdAt,
                    'updated_at'    => ($closed ?? $resolved ?? $onHold ?? $started ?? $accepted ?? $assigned ?? $createdAt),
                ];

                if ($hasRemark) {
                    $row['remark'] = match ($status) {
                        MR::STATUS_PENDING     => null,
                        MR::STATUS_ACCEPTED    => 'รับเข้าคิวแล้ว',
                        MR::STATUS_IN_PROGRESS => 'กำลังดำเนินการ',
                        MR::STATUS_ON_HOLD     => 'รอชิ้นส่วน/ช่างเฉพาะทาง',
                        MR::STATUS_RESOLVED    => 'แก้เสร็จ รอปิดงาน',
                        MR::STATUS_CLOSED      => 'ปิดงานเรียบร้อย',
                        MR::STATUS_CANCELLED   => 'ผู้แจ้งยกเลิก',
                        default                => null,
                    };
                }

                $rows[] = $row;

                if (count($rows) >= $chunkSize) {
                    DB::table('maintenance_requests')->insert($rows);
                    $rows = [];
                }
            }

            if ($rows) {
                DB::table('maintenance_requests')->insert($rows);
            }
        });

        // เล็กน้อย: กระพริบ updated_at ของบางงานให้เป็นปัจจุบันเพื่อกราฟ/Recent
        MR::query()->inRandomOrder()->limit(15)->update(['updated_at' => now()]);
    }
}
