<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\User;
use App\Models\MaintenanceRequest as MR;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceLog;

class DemoDataSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder ขนาดใหญ่สำหรับสร้างข้อมูลจำลองแบบครบวงจร (Dashboard Demo)
    public function run(): void
    {
        // การทำความสะอาดข้อมูล Demo เก่าที่มีอยู่เดิม
        Schema::disableForeignKeyConstraints();
        
        $demoIds = MaintenanceRequest::where(function($q) {
            $q->whereJsonContains('extra->is_demo', true)
              ->orWhereJsonContains('extra->is_demo', 1);
        })->pluck('id');

        DB::table('maintenance_ratings')->whereIn('maintenance_request_id', $demoIds)->delete();
        DB::table('maintenance_assignments')->whereIn('maintenance_request_id', $demoIds)->delete();
        DB::table('maintenance_logs')->whereIn('request_id', $demoIds)->delete();
        DB::table('maintenance_operation_logs')->whereIn('maintenance_request_id', $demoIds)->delete();
        DB::table('maintenance_requests')->whereIn('id', $demoIds)->delete();
        
        Schema::enableForeignKeyConstraints();

        // Configuration
        DB::connection()->disableQueryLog();

        // CONFIG
        $assetCount   = (int) env('DEMO_ASSET_COUNT', 250);
        $techCount    = (int) env('DEMO_TECH_COUNT', 15);
        $staffCount   = (int) env('DEMO_MEMBER_COUNT', 25);
        $requestCount = 300;
        $chunkSize    = (int) env('DEMO_CHUNK', 500);

        $adminCitizenId = env('DEMO_ADMIN_CITIZEN_ID', '1000000000001');
        $adminEmail     = env('DEMO_ADMIN_EMAIL', 'admin@example.com');
        $adminPassword  = env('DEMO_ADMIN_PASSWORD', 'Admin123!');

        $this->headline('DemoDataSeeder RUNNING');
        $this->kv('ASSETS', (string)$assetCount);
        $this->kv('TECHS', (string)$techCount);
        $this->kv('STAFFS', (string)$staffCount);
        $this->kv('REQUESTS', (string)$requestCount);
        $this->kv('CHUNK', (string)$chunkSize);
        $this->lineBreak();

        // Departments
        [$deptCodes, $departmentIds] = $this->seedDepartments();
        $this->ok('Departments ready', [
            'codes' => implode(',', $deptCodes),
            'count' => (string) count($departmentIds),
        ]);

        // Admin
        $admin = User::firstOrCreate(
            ['citizen_id' => $adminCitizenId],
            [
                'name'              => 'System Admin',
                'citizen_id'        => $adminCitizenId,
                'email'             => $adminEmail,
                'password'          => Hash::make($adminPassword),
                'role'              => 'admin',
                'department'        => in_array('IT', $deptCodes, true) ? 'IT' : ($deptCodes[0] ?? null),
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
            ]
        );

        $this->ok('Admin ready', [
            'citizen_id' => (string) $admin->citizen_id,
            'email'      => (string) ($admin->email ?? '-'),
        ]);

        // reset faker unique counter
        fake()->unique(true);

        // Technicians + Staffs
        $techDefault = in_array('IT', $deptCodes, true) ? 'IT' : ($deptCodes[0] ?? null);

        $techRoles = [
            User::ROLE_IT_SUPPORT,
            User::ROLE_NETWORK,
            User::ROLE_DEVELOPER
        ];
        
        $roleNames = [
            User::ROLE_IT_SUPPORT => 'IT Support',
            User::ROLE_NETWORK    => 'Network Engineer',
            User::ROLE_DEVELOPER  => 'Programmer'
        ];

        $technicians = collect();
        for ($i = 0; $i < $techCount; $i++) {
            $role = $techRoles[$i % count($techRoles)];
            $idStr = sprintf('%02d', $i + 1);
            $email = "demo.tech.{$idStr}@ppk.hospital";
            
            // Seed faker for stable real-looking names
            fake()->seed(100 + $i);
            
            $technicians->push(User::updateOrCreate(
                ['email' => $email],
                [
                    'role'       => $role,
                    'name'       => fake()->name(),
                    'department' => $techDefault,
                    'citizen_id' => '11000' . sprintf('%08d', $i + 100),
                    'password'   => Hash::make('Tech123!'),
                ]
            ));
        }

        $staffs = collect();
        for ($i = 0; $i < $staffCount; $i++) {
            $idStr = sprintf('%02d', $i + 1);
            $email = "demo.staff.{$idStr}@ppk.hospital";
            
            // Seed faker for stable real-looking names
            fake()->seed(200 + $i);
            
            $staffs->push(User::updateOrCreate(
                ['email' => $email],
                [
                    'role'       => 'member',
                    'name'       => fake()->name(),
                    'department' => fake()->randomElement($deptCodes),
                    'citizen_id' => '12000' . sprintf('%08d', $i + 100),
                    'password'   => Hash::make('Staff123!'),
                ]
            ));
        }

        $techIds  = $technicians->pluck('id')->all();
        $staffIds = $staffs->pluck('id')->all();

        $this->ok('Users seeded', [
            'technicians' => (string) count($techIds),
            'staffs'      => (string) count($staffIds),
        ]);

        // Asset Categories
        $categoryIds = $this->seedAssetCategories();
        $this->ok('Asset categories ready', ['count' => (string) count($categoryIds)]);

        // Assets
        $assetsInserted = $this->seedAssetsIfEmpty(
            assetCount: $assetCount,
            chunkSize: $chunkSize,
            departmentIds: $departmentIds,
            categoryIds: $categoryIds
        );

        $assetIds = Schema::hasTable('assets') ? DB::table('assets')->pluck('id')->all() : [];
        $this->ok('Assets ready', [
            'inserted' => (string) $assetsInserted,
            'total'    => (string) count($assetIds),
        ]);

        // Maintenance Requests + Assignments + Operation Logs
        if (!Schema::hasTable('maintenance_requests')) {
            $this->warn('maintenance_requests table not found, skip maintenance seeding.');
            $this->done('DemoDataSeeder DONE (partial)');
            return;
        }

        $this->infoBlock('Seeding maintenance_requests / maintenance_assignments / maintenance_operation_logs ...');

        $mrTable = 'maintenance_requests';

        // detect columns (schema-safe)
        $has = fn(string $col) => Schema::hasColumn($mrTable, $col);

        $hasAssetId      = $has('asset_id');
        $hasReporterId   = $has('reporter_id');
        $hasTechnicianId = $has('technician_id');

        $hasRequestNo    = $has('request_no');
        $hasDeptMR       = $has('department_id');

        $hasTitle        = $has('title');
        $hasDescription  = $has('description');
        $hasStatusCol    = $has('status');
        $hasTypeId       = $has('type_id');

        $typeIds = [];
        if ($hasTypeId) {
            $typeIds = DB::table('maintenance_request_types')->where('is_active', true)->pluck('id')->all();
        }

        // CLEANUP (Surgical - Safe for Manual Data)
        // ลบเฉพาะข้อมูล Demo เท่านั้น ไม่แตะต้องข้อมูลที่ Admin สร้างไว้เอง
        Schema::disableForeignKeyConstraints();
        
        // 1. ลบ Assignments ที่ผูกกับงาน Demo
        DB::table('maintenance_assignments')->whereExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('maintenance_requests')
                  ->whereColumn('maintenance_requests.id', 'maintenance_assignments.maintenance_request_id')
                  ->where(function($q) {
                      $q->whereJsonContains('extra->is_demo', true)
                        ->orWhereJsonContains('extra->is_demo', 1);
                  });
        })->delete();

        // 2. ลบ Ratings ที่ผูกกับงาน Demo
        DB::table('maintenance_ratings')->whereExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('maintenance_requests')
                  ->whereColumn('maintenance_requests.id', 'maintenance_ratings.maintenance_request_id')
                  ->where(function($q) {
                      $q->whereJsonContains('extra->is_demo', true)
                        ->orWhereJsonContains('extra->is_demo', 1);
                  });
        })->delete();

        // 3. ลบ Logs ที่ผูกกับงาน Demo
        DB::table('maintenance_logs')->whereExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('maintenance_requests')
                  ->whereColumn('maintenance_requests.id', 'maintenance_logs.request_id')
                  ->where(function($q) {
                      $q->whereJsonContains('extra->is_demo', true)
                        ->orWhereJsonContains('extra->is_demo', 1);
                  });
        })->delete();

        DB::table('maintenance_operation_logs')->whereExists(function($query) {
            $query->select(DB::raw(1))
                  ->from('maintenance_requests')
                  ->whereColumn('maintenance_requests.id', 'maintenance_operation_logs.maintenance_request_id')
                  ->where(function($q) {
                      $q->whereJsonContains('extra->is_demo', true)
                        ->orWhereJsonContains('extra->is_demo', 1);
                  });
        })->delete();

        // 4. ลบใบแจ้งซ่อมที่เป็น Demo เท่านั้น
        DB::table('maintenance_requests')->where(function($q) {
            $q->whereJsonContains('extra->is_demo', true)
              ->orWhereJsonContains('extra->is_demo', 1);
        })->delete();

        Schema::enableForeignKeyConstraints();

        // Reset asset statuses
        if (Schema::hasTable('assets')) {
            DB::table('assets')->update(['status' => 'active']);
        }

        $hasReporterName     = $has('reporter_name');
        $hasReporterPhone    = $has('reporter_phone');
        $hasReporterEmail    = $has('reporter_email');
        $hasReporterPosition = $has('reporter_position');

        $hasLegacyPayload = $has('legacy_payload'); // model casts array
        $hasLocationText  = $has('location_text');

        $hasRequestDate   = $has('request_date');
        $hasAssignedDate  = $has('assigned_date');
        $hasCompletedDate = $has('completed_date');
        $hasAcceptedAt    = $has('accepted_at');
        $hasAcknowledgedAt = $has('acknowledged_at');
        $hasPausedDuration = $has('paused_duration_minutes');
        $hasStartedAt     = $has('started_at');
        $hasOnHoldAt      = $has('on_hold_at');
        $hasResolvedAt    = $has('resolved_at');
        $hasClosedAt      = $has('closed_at');
        $hasSlaDueDate    = $has('sla_due_date');

        $hasRemark         = $has('remark');
        $hasResolutionNote = $has('resolution_note');
        $hasCost           = $has('cost');
        $hasSource         = $has('source');
        $hasExtra          = $has('extra');

        $hasCreatedAt      = $has('created_at');
        $hasUpdatedAt      = $has('updated_at');

        $now = Carbon::now();

        // 8) Fetch Maintenance Type SLA targets
        $typeSlas = DB::table('maintenance_request_types')
            ->select('id', 'default_response_minutes', 'default_resolution_minutes')
            ->get()
            ->keyBy('id');

        $makeTimeline = function (string $status, Carbon $base, ?int $typeId = null) use ($typeSlas) {
            $assigned = $acknowledged = $accepted = $started = $onHold = $resolved = $closed = $completedDate = $slaDueDate = $responseDueDate = null;
            $pausedDuration = 0;

            // Get SLA targets from Type (Primary Source)
            $typeTarget = $typeId ? $typeSlas->get($typeId) : null;
            $resTarget = $typeTarget ? (int)$typeTarget->default_resolution_minutes : 2880; // default 48h
            $respTarget = $typeTarget ? (int)$typeTarget->default_response_minutes : 120; // default 2h

            // Initial calculation (same as MR model logic)
            $responseDueDate = (clone $base)->addMinutes($respTarget);
            $slaDueDate = (clone $base)->addMinutes($resTarget);

            if (in_array($status, ['acknowledged','accepted','in_progress','on_hold','resolved','closed'], true)) {
                $assigned = (clone $base)->addMinutes(random_int(10, 120));
                
                // Response Time Compliance: 85% compliant, 15% breached
                $isRespCompliant = random_int(1, 100) <= 85;
                if ($isRespCompliant) {
                    $acknowledged = (clone $assigned)->addMinutes(random_int(5, max(6, $respTarget - 5)));
                } else {
                    $acknowledged = (clone $assigned)->addMinutes($respTarget + random_int(10, 180));
                }
            }

            if (in_array($status, ['accepted','in_progress','on_hold','resolved','closed'], true)) {
                $accepted = (clone $acknowledged ?? $assigned ?? $base)->addMinutes(random_int(30, 240));
            }

            if (in_array($status, ['in_progress','on_hold','resolved','closed'], true)) {
                $started = (clone ($accepted ?? $base))->addMinutes(random_int(15, 120));
            }

            // On Hold logic
            $hasOnHold = ($status === 'on_hold' || (in_array($status, ['resolved','closed'], true) && random_int(1, 100) <= 30));
            if ($hasOnHold) {
                $onHold = (clone ($started ?? $accepted ?? $base))->addHours(random_int(1, 24));
                $pausedDuration = random_int(120, 2880); // 2h to 48h
                
                // Extend SLA due date
                if ($slaDueDate) {
                    $slaDueDate->addMinutes($pausedDuration);
                }
            }

            if (in_array($status, ['resolved','closed'], true)) {
                // Resolution Time Compliance: 85% compliant, 15% breached
                $isResCompliant = random_int(1, 100) <= 85;
                $resBase = $onHold ? (clone $onHold)->addMinutes($pausedDuration) : (clone ($started ?? $accepted ?? $base));
                
                if ($isResCompliant) {
                    $maxMins = (int) $resBase->diffInMinutes($slaDueDate ?? $resBase->copy()->addMinutes(60));
                    $resolved = (clone $resBase)->addMinutes(random_int(30, max(31, $maxMins - 10)));
                } else {
                    $resolved = (clone ($slaDueDate ?? $resBase))->addMinutes(random_int(60, 1440));
                }
            }

            if ($status === 'closed') {
                $closed = (clone ($resolved ?? $base))->addHours(random_int(1, 48));
                $completedDate = $closed;
            }

            return [$assigned, $acknowledged, $accepted, $started, $onHold, $resolved, $closed, $completedDate, $pausedDuration, $slaDueDate, $responseDueDate];
        };

        // request_no generator (กันชน)
        $existingSet  = [];
        $yearCounters = [];
        if ($hasRequestNo) {
            $existing = DB::table($mrTable)->pluck('request_no')->filter()->all();
            foreach ($existing as $no) {
                $existingSet[$no] = true;
                if (preg_match('/^(\d{2})(\d{2})(\d{5})$/', (string) $no, $m)) {
                    $yy   = $m[1];
                    $type = $m[2];
                    $seq  = (int) $m[3];
                    $key  = $yy.$type;
                    $yearCounters[$key] = max($yearCounters[$key] ?? 0, $seq);
                }
            }
        }
        $usedInRun = [];
        $makeRequestNo = function (Carbon $date) use (&$yearCounters, &$existingSet, &$usedInRun) {
            $beYear = $date->year + 543;
            $yy     = substr((string) $beYear, -2);
            $type   = '10';

            $key     = $yy.$type;
            $lastSeq = $yearCounters[$key] ?? 0;

            do {
                $lastSeq++;
                $candidate = $yy.$type.sprintf('%05d', $lastSeq);
            } while (isset($existingSet[$candidate]) || isset($usedInRun[$candidate]));

            $yearCounters[$key]       = $lastSeq;
            $existingSet[$candidate]  = true;
            $usedInRun[$candidate]    = true;

            return $candidate;
        };

        // Insert MR in a transaction
        $insertedMR = 0;
        $insertedLogs = 0;

        DB::transaction(function () use (
            $mrTable,
            $requestCount, $chunkSize, $now,
            $assetIds, $staffIds, $techIds, $departmentIds,
            $makeTimeline, $makeRequestNo,

            $hasAssetId, $hasReporterId, $hasTechnicianId,
            $hasRequestNo, $hasDeptMR,
            $hasTitle, $hasDescription, $hasStatusCol,
            $hasReporterName, $hasReporterPhone, $hasReporterEmail, $hasReporterPosition,
            $hasLegacyPayload, $hasLocationText,
            $hasRequestDate, $hasAssignedDate, $hasCompletedDate, $hasAcceptedAt, $hasAcknowledgedAt, $hasPausedDuration, $hasStartedAt, $hasOnHoldAt, $hasResolvedAt, $hasClosedAt, $hasSlaDueDate,
            $hasRemark, $hasResolutionNote, $hasCost, $hasSource, $hasExtra,
            $hasCreatedAt, $hasUpdatedAt, $hasTypeId, $typeIds,

            &$insertedMR, &$insertedLogs
        ) {
            $insertCols = [];

            if ($hasAcceptedAt)       $insertCols[] = 'accepted_at';
            if ($hasAcknowledgedAt)   $insertCols[] = 'acknowledged_at';
            if ($hasAssetId)          $insertCols[] = 'asset_id';
            if ($hasAssignedDate)     $insertCols[] = 'assigned_date';
            if ($hasClosedAt)         $insertCols[] = 'closed_at';
            if ($hasCompletedDate)    $insertCols[] = 'completed_date';
            if ($hasCost)             $insertCols[] = 'cost';
            if ($hasCreatedAt)        $insertCols[] = 'created_at';
            if ($hasDeptMR)           $insertCols[] = 'department_id';
            if ($hasDescription)      $insertCols[] = 'description';
            if ($hasExtra)            $insertCols[] = 'extra';
            if ($hasLegacyPayload)    $insertCols[] = 'legacy_payload';
            if ($hasLocationText)     $insertCols[] = 'location_text';
            if ($hasOnHoldAt)         $insertCols[] = 'on_hold_at';
            if ($hasPausedDuration)   $insertCols[] = 'paused_duration_minutes';
            if ($hasRemark)           $insertCols[] = 'remark';
            if ($hasReporterEmail)    $insertCols[] = 'reporter_email';
            if ($hasReporterId)       $insertCols[] = 'reporter_id';
            if ($hasReporterName)     $insertCols[] = 'reporter_name';
            if ($hasReporterPhone)    $insertCols[] = 'reporter_phone';
            if ($hasReporterPosition) $insertCols[] = 'reporter_position';
            if ($hasRequestDate)      $insertCols[] = 'request_date';
            if ($hasRequestNo)        $insertCols[] = 'request_no';
            if ($hasResolutionNote)   $insertCols[] = 'resolution_note';
            if ($hasResolvedAt)       $insertCols[] = 'resolved_at';
            if ($hasSlaDueDate)       $insertCols[] = 'sla_due_date';
            if ($hasSource)           $insertCols[] = 'source';
            if ($hasStartedAt)        $insertCols[] = 'started_at';
            if ($hasStatusCol)        $insertCols[] = 'status';
            if ($hasTechnicianId)     $insertCols[] = 'technician_id';
            if ($hasTitle)            $insertCols[] = 'title';
            if ($hasTypeId)           $insertCols[] = 'type_id';
            if ($hasUpdatedAt)        $insertCols[] = 'updated_at';
            if (Schema::hasColumn('maintenance_requests', 'response_due_date')) {
                $insertCols[] = 'response_due_date';
            }

            for ($i = 1; $i <= $requestCount; $i++) {
                // เฉลี่ยสถานะให้เท่ากันทุกช่อง (300 / 7 = ประมาณ 42-43 งานต่อสถานะ)
                $allStatuses = [
                    MR::STATUS_PENDING,
                    MR::STATUS_ACKNOWLEDGED,
                    MR::STATUS_ACCEPTED,
                    MR::STATUS_IN_PROGRESS,
                    MR::STATUS_ON_HOLD,
                    MR::STATUS_RESOLVED,
                    MR::STATUS_CLOSED
                ];
                $status = $allStatuses[($i - 1) % count($allStatuses)];

                $isActive = in_array($status, [
                    MR::STATUS_PENDING,
                    MR::STATUS_ACKNOWLEDGED,
                    MR::STATUS_ACCEPTED,
                    MR::STATUS_IN_PROGRESS,
                    MR::STATUS_ON_HOLD
                ], true);

                if ($isActive) {
                    $isOld = random_int(1, 100) <= 20;
                    if ($isOld) {
                        $createdAt = (clone $now)->subDays(random_int(15, 35))->setTime(random_int(8, 17), random_int(0, 51));
                    } else {
                        $createdAt = (clone $now)->subDays(random_int(0, 14))->setTime(random_int(8, 17), random_int(0, 51));
                    }
                } else {
                    // กระจายงานเดือนย้อนหลังให้ครอบคลุม dashboard 12 เดือนแน่นอน
                    // $monthSub = 1 ถึง 11 เพื่อให้เดือนปัจจุบันไม่มีงานที่ปิดแล้ว (Completed = 0)
                    $monthSub = ($i % 11) + 1; 
                    $createdAt = (clone $now)->subMonths($monthSub)->subDays(random_int(0, 28))->setTime(random_int(8, 17), random_int(0, 59));
                    
                    // เพิ่มเติมงานที่เก่ากว่า 12 เดือนเล็กน้อย (13-18 เดือน)
                    if (random_int(1, 100) <= 15) {
                        $createdAt = (clone $now)->subMonths(random_int(13, 18))->subDays(random_int(0, 28))->setTime(random_int(8, 17), random_int(0, 59));
                    }
                }
                
                // Guarantee first 20 assets have 3-5 requests each
                if ($i <= 80 && count($assetIds) >= 20) {
                    $assetId = $assetIds[($i - 1) % 20];
                } else {
                    $assetId = $assetIds ? $assetIds[array_rand($assetIds)] : null;
                }
                
                $reporter = $staffIds ? $staffIds[array_rand($staffIds)] : null;
                $isExternal = random_int(1, 100) <= 10;
                if ($isExternal) $reporter = null;

                $techId = null;
                if (!in_array($status, ['pending','acknowledged','cancelled','rejected'], true)) {
                    // แจกจ่ายงานแบบเท่าๆ กัน (Round-robin) 
                    // รวมสถานะ 'accepted' ด้วยเพราะต้องมีผู้รับผิดชอบงานแล้ว
                    $techId = $techIds ? $techIds[($i - 1) % count($techIds)] : null;
                }

                $typeIdToUse = null;
                if ($hasTypeId && !empty($typeIds)) {
                    $shouldHaveType = true;
                    if (in_array($status, [MR::STATUS_PENDING, MR::STATUS_ACKNOWLEDGED], true)) {
                        $shouldHaveType = (random_int(1, 100) <= 20);
                    } elseif ($status === MR::STATUS_ACCEPTED) {
                        $shouldHaveType = (random_int(1, 100) <= 70);
                    }
                    if ($shouldHaveType) {
                        $typeIdToUse = $typeIds[array_rand($typeIds)];
                    }
                }

                [$assigned,$acknowledged,$accepted,$started,$onHold,$resolved,$closed,$completedDate,$pausedDuration,$slaDueDate,$responseDueDate] = $makeTimeline($status, $createdAt, $typeIdToUse);

                $row = array_fill_keys($insertCols, null);
                if ($hasTypeId)       $row['type_id'] = $typeIdToUse;
                if ($hasAssetId)      $row['asset_id'] = $assetId;
                if ($hasReporterId)   $row['reporter_id'] = $reporter;
                if ($hasTechnicianId) $row['technician_id'] = $techId;
                if ($hasRequestNo)    $row['request_no'] = $makeRequestNo($createdAt);
                if ($hasTitle)        $row['title']       = 'แจ้งซ่อมไอเทม #'.$i;
                if ($hasDescription)  $row['description'] = 'รายละเอียดการแจ้งซ่อม: ปัญหาที่พบจากการใช้งานเบื้องต้น';
                if ($hasStatusCol)    $row['status']   = $status;
                
                if ($hasDeptMR && $departmentIds) $row['department_id'] = $departmentIds[array_rand($departmentIds)];
                if ($hasLocationText) $row['location_text'] = fake()->randomElement(['ตึก A ชั้น 2', 'ตึก B ห้อง IT', 'หน้า ER', 'Ward 3', 'OPD 5']);
                if ($isExternal) {
                    if ($hasReporterName)     $row['reporter_name']  = fake()->name();
                    if ($hasReporterPhone)    $row['reporter_phone'] = fake()->numerify('08########');
                    if ($hasReporterEmail)    $row['reporter_email'] = fake()->safeEmail();
                    if ($hasReporterPosition) $row['reporter_position'] = fake()->jobTitle();
                }
                if ($hasLegacyPayload) $row['legacy_payload'] = json_encode(['ua'=>fake()->randomElement(['Chrome','Edge','Firefox']),'tz'=>'Asia/Bangkok','seed'=>true,'external'=>$isExternal]);
                if ($hasRequestDate)   $row['request_date']   = $createdAt;
                if ($hasAssignedDate)  $row['assigned_date']  = $assigned;
                if ($hasAcknowledgedAt)$row['acknowledged_at']= $acknowledged;
                if ($hasAcceptedAt)    $row['accepted_at']    = $accepted;
                if ($hasStartedAt)     $row['started_at']     = $started;
                if ($hasOnHoldAt)      $row['on_hold_at']     = $onHold;
                if ($hasResolvedAt)    $row['resolved_at']    = $resolved;
                if ($hasClosedAt)      $row['closed_at']      = $closed;
                if ($hasCompletedDate) $row['completed_date'] = $completedDate;
                if ($hasPausedDuration)$row['paused_duration_minutes'] = $pausedDuration;
                if ($hasSlaDueDate)    $row['sla_due_date'] = $slaDueDate;
                if (in_array('response_due_date', $insertCols)) $row['response_due_date'] = $responseDueDate;

                if ($hasRemark) {
                    $row['remark'] = match ($status) {
                        'pending'       => null,
                        'acknowledged'  => 'รับทราบแล้ว รอเจ้าหน้าที่รับเรื่อง',
                        'accepted'      => 'รับเรื่องแล้ว กำลังเตรียมเครื่องมือ',
                        'in_progress'   => 'กำลังดำเนินการซ่อมบำรุง',
                        'on_hold'       => 'รออะไหล่ทดแทน',
                        'resolved'      => 'แก้ไขเรียบร้อยแล้ว ทดสอบการใช้งานผ่าน',
                        'closed'        => 'ผู้แจ้งตรวจสอบและปิดงานเรียบร้อย',
                        default         => null,
                    };
                }
                if ($hasResolutionNote && in_array($status, ['resolved','closed'], true)) $row['resolution_note'] = fake()->sentence(8);
                if ($hasCost && in_array($status, ['resolved','closed'], true)) $row['cost'] = fake()->randomFloat(2, 200, 8000);
                if ($hasSource) $row['source'] = 'web';
                if ($hasExtra) $row['extra'] = json_encode(['is_demo' => true]);
                $updatedAt = $closed ?? $resolved ?? $onHold ?? $started ?? $accepted ?? $assigned ?? $createdAt;
                if ($hasCreatedAt) $row['created_at'] = $createdAt;
                if ($hasUpdatedAt) $row['updated_at'] = $updatedAt;

                $requestId = DB::table($mrTable)->insertGetId($row);
                $insertedMR++;

                // Sync asset status: if request is active, set asset to in_repair
                if ($hasAssetId && $assetId && in_array($status, [
                    MR::STATUS_PENDING,
                    MR::STATUS_ACKNOWLEDGED,
                    MR::STATUS_ACCEPTED,
                    MR::STATUS_IN_PROGRESS,
                    MR::STATUS_ON_HOLD
                ], true)) {
                    DB::table('assets')->where('id', $assetId)->update(['status' => 'in_repair']);
                }

                // --- Generate Activity Logs (Step 8) ---
                $logEntries = [];
                $adminId = User::where('role', 'admin')->value('id') ?: ($staffIds[0] ?? null);

                // 1. Creation Log
                $logEntries[] = [
                    'request_id' => $requestId,
                    'user_id'    => $reporter ?: $adminId,
                    'action'     => \App\Models\MaintenanceLog::ACTION_CREATE,
                    'note'       => 'สร้างใบแจ้งซ่อมใหม่เข้าระบบ',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                // 2. Transition Logs
                if ($acknowledged) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                        'note'       => '[pending -> acknowledged] รับทราบรายการแจ้งซ่อม',
                        'created_at' => $acknowledged,
                        'updated_at' => $acknowledged,
                    ];
                }
                if ($accepted) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $techId ?: $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                        'note'       => '[acknowledged -> accepted] เจ้าหน้าที่รับเรื่องและกำลังเข้าดำเนินการ',
                        'created_at' => $accepted,
                        'updated_at' => $accepted,
                    ];
                }
                if ($started) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $techId ?: $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_START,
                        'note'       => '[accepted -> in_progress] เริ่มดำเนินการซ่อมบำรุง',
                        'created_at' => $started,
                        'updated_at' => $started,
                    ];
                }
                if ($onHold) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $techId ?: $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                        'note'       => '[in_progress -> on_hold] หยุดการซ่อมบำรุงชั่วคราว: รออะไหล่',
                        'created_at' => $onHold,
                        'updated_at' => $onHold,
                    ];
                }
                if ($resolved) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $techId ?: $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                        'note'       => '[in_progress -> resolved] ซ่อมบำรุงเสร็จสิ้น ทดสอบการใช้งานปกติ',
                        'created_at' => $resolved,
                        'updated_at' => $resolved,
                    ];
                }
                if ($closed) {
                    $logEntries[] = [
                        'request_id' => $requestId,
                        'user_id'    => $reporter ?: $adminId,
                        'action'     => \App\Models\MaintenanceLog::ACTION_TRANSITION,
                        'note'       => '[resolved -> closed] ผู้แจ้งตรวจสอบและปิดงาน',
                        'created_at' => $closed,
                        'updated_at' => $closed,
                    ];
                }

                DB::table('maintenance_logs')->insert($logEntries);
                $insertedLogs += count($logEntries);
            }
        });

        $this->ok('maintenance_requests seeded', [
            'inserted' => (string) $insertedMR,
            'logs'     => (string) $insertedLogs,
        ]);

        // Assignments
        $asgInserted = 0;

        if (Schema::hasTable('maintenance_assignments')) {
            $hasAsgRemark = Schema::hasColumn('maintenance_assignments', 'remark');

            $updateCols = [
                'role',
                'is_lead',
                'assigned_at',
                'status',
                'response_status',
                'responded_at',
                'updated_at',
            ];
            if ($hasAsgRemark) $updateCols[] = 'remark';

            // 7.1 lead assignment from technician_id
            $reqs = DB::table('maintenance_requests')
                ->select('id','technician_id','status','assigned_date','accepted_at','request_date','created_at')
                ->whereNotNull('technician_id')
                ->get();

            $nowTs = now();
            $asgRows = [];

            foreach ($reqs as $r) {
                $assignedAt = $r->assigned_date ?? $r->accepted_at ?? $r->request_date ?? $r->created_at ?? $nowTs;

                $row = [
                    'maintenance_request_id' => $r->id,
                    'user_id'                => $r->technician_id,
                    'role'                   => 'technician',
                    'is_lead'                => true,
                    'assigned_at'            => $assignedAt,

                    // assignment status
                    'status' => match ($r->status) {
                        'resolved','closed' => 'done',
                        'cancelled'         => 'cancelled',
                        default             => 'in_progress',
                    },

                    // response status
                    'response_status' => match ($r->status) {
                        'cancelled' => 'pending',
                        default     => 'accepted',
                    },
                    'responded_at' => match ($r->status) {
                        'cancelled' => null,
                        default     => $assignedAt,
                    },

                    'created_at' => $nowTs,
                    'updated_at' => $nowTs,
                ];

                if ($hasAsgRemark) $row['remark'] = null;

                $asgRows[] = $row;

                if (count($asgRows) >= 1000) {
                    DB::table('maintenance_assignments')->upsert(
                        $asgRows,
                        ['maintenance_request_id','user_id'],
                        $updateCols
                    );
                    $asgInserted += count($asgRows);
                    $asgRows = [];
                }
            }

            if ($asgRows) {
                DB::table('maintenance_assignments')->upsert(
                    $asgRows,
                    ['maintenance_request_id','user_id'],
                    $updateCols
                );
                $asgInserted += count($asgRows);
            }

            // 7.2 acknowledged sample for MyJob (acknowledged/rejected)
            $ackReqs = DB::table('maintenance_requests')
                ->select('id','status','assigned_date','accepted_at','request_date','created_at')
                ->where('status', MR::STATUS_ACKNOWLEDGED)
                ->inRandomOrder()
                ->limit((int) max(10, floor($requestCount * 0.15)))
                ->get();

            $ackRows = [];
            $rejectEvery = 4;
            $idx = 0;

            foreach ($ackReqs as $r) {
                $idx++;
                $assignedAt = $r->assigned_date ?? $r->accepted_at ?? $r->request_date ?? $r->created_at ?? $nowTs;

                $techId = $techIds ? $techIds[array_rand($techIds)] : null;
                if (!$techId) continue;

                $isReject = ($idx % $rejectEvery === 0);

                $row = [
                    'maintenance_request_id' => $r->id,
                    'user_id'                => $techId,
                    'role'                   => 'technician',
                    'is_lead'                => false,
                    'assigned_at'            => $assignedAt,

                    'status'          => $isReject ? 'cancelled' : 'assigned',

                    'response_status' => $isReject ? 'rejected' : 'acknowledged',
                    'responded_at'    => $assignedAt,

                    'created_at'      => $nowTs,
                    'updated_at'      => $nowTs,
                ];

                if ($hasAsgRemark) {
                    $row['remark'] = $isReject
                        ? 'ภาระงานเต็ม/ไม่อยู่เวร/ไม่เชี่ยวชาญงานประเภทนี้'
                        : null;
                }

                $ackRows[] = $row;

                if (count($ackRows) >= 1000) {
                    DB::table('maintenance_assignments')->upsert(
                        $ackRows,
                        ['maintenance_request_id','user_id'],
                        $updateCols
                    );
                    $asgInserted += count($ackRows);
                    $ackRows = [];
                }
            }

            if ($ackRows) {
                DB::table('maintenance_assignments')->upsert(
                    $ackRows,
                    ['maintenance_request_id','user_id'],
                    $updateCols
                );
                $asgInserted += count($ackRows);
            }

            $this->ok('maintenance_assignments upserted', [
                'rows' => (string) $asgInserted,
            ]);
        } else {
            $this->warn('maintenance_assignments table not found, skip assignments.');
        }

        $opUpserted = 0;

        if (Schema::hasTable('maintenance_operation_logs')) {
            $hasPropertyCode = Schema::hasColumn('maintenance_operation_logs', 'property_code');

            $target = DB::table('maintenance_requests as mr')
                ->leftJoin('assets as a', 'a.id', '=', 'mr.asset_id')
                ->leftJoin('maintenance_request_types as mrt', 'mrt.id', '=', 'mr.type_id')
                ->select(
                    'mr.id',
                    'mr.technician_id',
                    'mr.resolution_note',
                    'mr.resolved_at',
                    'mr.closed_at',
                    'mr.started_at',
                    'mr.request_date',
                    'mr.created_at',
                    'mrt.name as type_name',
                    'a.asset_code as asset_code'
                )
                ->whereIn('mr.status', [MR::STATUS_RESOLVED, MR::STATUS_CLOSED])
                ->inRandomOrder()
                ->limit((int) floor($requestCount * 0.6))
                ->get();

            $nowTs   = now();
            $methods = ['requisition','service_fee','other'];
            $opRows  = [];

            foreach ($target as $r) {
                $userId = $r->technician_id ?: (!empty($techIds) ? $techIds[array_rand($techIds)] : null);
                if (!$userId) continue;

                $base = $r->resolved_at ?? $r->closed_at ?? $r->started_at ?? $r->request_date ?? $r->created_at ?? $nowTs;
                $operationDate = Carbon::parse($base)->startOfDay();

                $isSoftware = (str_contains(strtolower($r->type_name ?? ''), 'software'));
                
                $opRows[] = [
                    'maintenance_request_id' => $r->id,
                    'user_id'                => $userId,
                    'operation_date'         => $operationDate,
                    'operation_method'       => $methods[array_rand($methods)],
                    'property_code'          => $hasPropertyCode ? ($r->asset_code ?? null) : null,
                    'require_precheck'       => (int) random_int(0, 1),
                    'remark'                 => $r->resolution_note ?: fake()->sentence(10),
                    'issue_software'         => $isSoftware ? 1 : 0,
                    'issue_hardware'         => $isSoftware ? 0 : 1,
                    'created_at'             => $nowTs,
                    'updated_at'             => $nowTs,
                ];
            }

            if ($opRows) {
                DB::table('maintenance_operation_logs')->upsert(
                    $opRows,
                    ['maintenance_request_id'],
                    [
                        'user_id',
                        'operation_date',
                        'operation_method',
                        'property_code',
                        'require_precheck',
                        'remark',
                        'issue_software',
                        'issue_hardware',
                        'updated_at',
                    ]
                );
                $opUpserted = count($opRows);
            }

            $this->ok('maintenance_operation_logs upserted', [
                'rows' => (string) $opUpserted,
            ]);
        } else {
            $this->warn('maintenance_operation_logs table not found, skip operation logs.');
        }

        $this->done('DemoDataSeeder DONE');
    }



    private function headline(string $text): void
    {
    }

    private function infoBlock(string $text): void
    {
    }

    private function ok(string $title, array $meta = []): void
    {
    }

    private function warn(string $text): void
    {
    }

    private function done(string $text): void
    {
    }

    private function kv(string $k, string $v): void
    {
    }

    private function lineBreak(): void
    {
    }

    private function formatMeta(array $meta): string
    {
        $parts = [];
        foreach ($meta as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        return implode(', ', $parts);
    }

    // seed pieces
    private function seedDepartments(): array
    {
        $deptCodes = ['IT','ER','OPD','WARD','ADMIN','LAB'];
        $departmentIds = [];

        if (!Schema::hasTable('departments')) {
            return [$deptCodes, $departmentIds];
        }

        $hasCode   = Schema::hasColumn('departments', 'code');
        $hasNameTh = Schema::hasColumn('departments', 'name_th');
        $hasNameEn = Schema::hasColumn('departments', 'name_en');

        if ($hasCode && $hasNameTh) {
            $now = now();
            $depts = [
                ['code'=>'IT','name_th'=>'กลุ่มงานเทคโนโลยีสารสนเทศ','name_en'=>'Digital Health Technology'],
                ['code'=>'ER','name_th'=>'ห้องฉุกเฉิน','name_en'=>'Emergency Room'],
                ['code'=>'OPD','name_th'=>'ผู้ป่วยนอก','name_en'=>'OPD'],
                ['code'=>'WARD','name_th'=>'วอร์ดผู้ป่วยใน','name_en'=>'Ward'],
                ['code'=>'ADMIN','name_th'=>'ฝ่ายธุรการ','name_en'=>'Administration'],
                ['code'=>'LAB','name_th'=>'ห้องปฏิบัติการ','name_en'=>'Laboratory'],
            ];

            foreach ($depts as $d) {
                DB::table('departments')->updateOrInsert(
                    ['code' => $d['code']],
                    array_merge($d, ['created_at'=>$now, 'updated_at'=>$now])
                );
            }
        }

        if ($hasCode) {
            $codes = DB::table('departments')->pluck('code')->filter()->values()->all();
            if ($codes) $deptCodes = $codes;
        }

        if (Schema::hasColumn('departments', 'id')) {
            $departmentIds = DB::table('departments')->pluck('id')->all();
        }

        return [$deptCodes, $departmentIds];
    }

    private function seedAssetCategories(): array
    {
        if (!Schema::hasTable('asset_categories')) {
            return [];
        }

        $hasSlug = Schema::hasColumn('asset_categories', 'slug');

        if (!DB::table('asset_categories')->exists()) {
            $catNames = ['คอมพิวเตอร์','เครื่องพิมพ์','เครื่องปรับอากาศ','โต๊ะทำงาน','หลอดไฟ','เตียงคนไข้'];
            $rows     = [];
            $now      = now();

            $existingSlugs = $hasSlug ? DB::table('asset_categories')->pluck('slug')->filter()->all() : [];
            $slugSet = array_fill_keys($existingSlugs, true);

            $makeSlug = function (string $name) use (&$slugSet) {
                $base = Str::slug($name, '-');
                if ($base === '' || $base === null) {
                    $base = trim(preg_replace('/[^a-z0-9]+/i', '-', mb_strtolower($name)), '-');
                }
                if ($base === '' || $base === null) {
                    $base = 'cat-'.substr(md5($name.microtime(true)), 0, 6);
                }
                $slug = $base;
                $i    = 2;
                while (isset($slugSet[$slug])) {
                    $slug = $base.'-'.$i;
                    $i++;
                }
                $slugSet[$slug] = true;
                return $slug;
            };

            foreach ($catNames as $name) {
                $row = ['name'=>$name,'created_at'=>$now,'updated_at'=>$now];
                if ($hasSlug) $row['slug'] = $makeSlug($name);
                $rows[] = $row;
            }

            DB::table('asset_categories')->insert($rows);
        }

        return DB::table('asset_categories')->pluck('id')->all();
    }

    private function seedAssetsIfEmpty(int $assetCount, int $chunkSize, array $departmentIds, array $categoryIds): int
    {
        if (!Schema::hasTable('assets')) {
            return 0;
        }

        if (DB::table('assets')->exists()) {
            return 0;
        }

        $types     = ['เครื่องใช้ไฟฟ้า','อุปกรณ์สำนักงาน','คอมพิวเตอร์','เครื่องมือแพทย์'];
        $brands    = ['HP','Dell','Acer','Lenovo','Brother','Mitsubishi','Daikin'];
        $locations = ['ER','OPD','Ward','Admin','IT Room','Lab'];

        $hasType         = Schema::hasColumn('assets', 'type');
        $hasBrand        = Schema::hasColumn('assets', 'brand');
        $hasModel        = Schema::hasColumn('assets', 'model');
        $hasSerial       = Schema::hasColumn('assets', 'serial_number');
        $hasLocation     = Schema::hasColumn('assets', 'location');
        $hasDeptId       = Schema::hasColumn('assets', 'department_id');
        $hasCategoryId   = Schema::hasColumn('assets', 'category_id');
        $hasPurchaseDate = Schema::hasColumn('assets', 'purchase_date');
        $hasWarranty     = Schema::hasColumn('assets', 'warranty_expire');
        $hasStatus       = Schema::hasColumn('assets', 'status');
        $hasAssetCode    = Schema::hasColumn('assets', 'asset_code');
        $hasName         = Schema::hasColumn('assets', 'name');
        $hasVendorName   = Schema::hasColumn('assets', 'vendor_name');
        $hasVendorPhone  = Schema::hasColumn('assets', 'vendor_phone');
        $hasPrice        = Schema::hasColumn('assets', 'price');
        $hasWarrantyStart = Schema::hasColumn('assets', 'warranty_start');
        $hasInternalPhone = Schema::hasColumn('assets', 'internal_phone');

        $assetRows = [];
        $nowTs     = now();
        $usedCodes = [];
        $usedSNs   = [];
        $inserted  = 0;

        for ($i = 1; $i <= $assetCount; $i++) {
            do { $code = 'ASSET-'.random_int(10000, 99999); } while (isset($usedCodes[$code]));
            $usedCodes[$code] = true;

            do { $sn = 'SN'.random_int(10000000, 99999999); } while (isset($usedSNs[$sn]));
            $usedSNs[$sn] = true;

            $purchaseAt = Carbon::now()->subMonths(random_int(6, 48))->startOfDay();
            $warrantyAt = (clone $purchaseAt)->addMonths(random_int(12, 48));

            $row = ['created_at'=>$nowTs,'updated_at'=>$nowTs];

            if ($hasAssetCode)    $row['asset_code']      = $code;
            if ($hasName)         $row['name']            = fake()->words(2, true);
            if ($hasType)         $row['type']            = $types[array_rand($types)];
            if ($hasBrand)        $row['brand']           = $brands[array_rand($brands)];
            if ($hasModel)        $row['model']           = strtoupper(fake()->bothify('??-###'));
            if ($hasSerial)       $row['serial_number']   = $sn;
            if ($hasLocation)     $row['location']        = $locations[array_rand($locations)];
            if ($hasDeptId && $departmentIds)   $row['department_id'] = $departmentIds[array_rand($departmentIds)];
            if ($hasCategoryId && $categoryIds) $row['category_id']   = $categoryIds[array_rand($categoryIds)];
            if ($hasPurchaseDate) $row['purchase_date']   = $purchaseAt;
            if ($hasWarrantyStart) $row['warranty_start'] = (clone $purchaseAt)->addDays(random_int(1, 14));
            if ($hasWarranty)     $row['warranty_expire'] = $warrantyAt;

            if ($hasVendorName)   $row['vendor_name']     = fake()->company();
            if ($hasInternalPhone) $row['internal_phone']  = '02-' . random_int(100, 999) . '-' . random_int(1000, 9999);
            if ($hasVendorPhone)  $row['vendor_phone']    = '08' . random_int(1, 9) . '-' . random_int(100, 999) . '-' . random_int(1000, 9999);
            if ($hasPrice)        $row['price']           = fake()->randomFloat(2, 5000, 500000);

            if ($hasStatus) {
                $row['status'] = 'active'; // Default to active, let maintenance seeders update to in_repair if needed
            }

            $assetRows[] = $row;

            if (count($assetRows) >= $chunkSize) {
                DB::table('assets')->insert($assetRows);
                $inserted += count($assetRows);
                $assetRows = [];
            }
        }

        if ($assetRows) {
            DB::table('assets')->insert($assetRows);
            $inserted += count($assetRows);
        }

        return $inserted;
    }
}
