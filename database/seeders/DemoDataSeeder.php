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

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection()->disableQueryLog();

        // ================== CONFIG ==================
        $assetCount   = (int) env('DEMO_ASSET_COUNT', 120);
        $techCount    = (int) env('DEMO_TECH_COUNT', 6);
        $staffCount   = (int) env('DEMO_MEMBER_COUNT', 18);
        $requestCount = (int) env('DEMO_SEED_COUNT', 300);
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

        // ================== 1) Departments ==================
        [$deptCodes, $departmentIds] = $this->seedDepartments();
        $this->ok('Departments ready', [
            'codes' => implode(',', $deptCodes),
            'count' => (string) count($departmentIds),
        ]);

        // ================== 2) Admin ==================
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

        // ================== 3) Technicians + Staffs ==================
        $techDefault = in_array('IT', $deptCodes, true) ? 'IT' : ($deptCodes[0] ?? null);

        $technicians = User::factory()
            ->count($techCount)
            ->state(fn () => [
                'role'       => 'technician',
                'department' => $techDefault,
                'citizen_id' => fake()->unique()->numerify('#############'),
                'email'      => fake()->unique()->safeEmail(),
            ])
            ->create();

        $staffs = User::factory()
            ->count($staffCount)
            ->state(fn () => [
                'role'       => 'member',
                'department' => fake()->randomElement($deptCodes),
                'citizen_id' => fake()->unique()->numerify('#############'),
                'email'      => fake()->unique()->safeEmail(),
            ])
            ->create();

        $techIds  = $technicians->pluck('id')->all();
        $staffIds = $staffs->pluck('id')->all();

        $this->ok('Users seeded', [
            'technicians' => (string) count($techIds),
            'staffs'      => (string) count($staffIds),
        ]);

        // ================== 4) Asset Categories ==================
        $categoryIds = $this->seedAssetCategories();
        $this->ok('Asset categories ready', ['count' => (string) count($categoryIds)]);

        // ================== 5) Assets ==================
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

        // ================== 6) Maintenance Requests + Assignments + Operation Logs ==================
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
        $hasPriority     = $has('priority');
        $hasStatusCol    = $has('status');

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
        $hasStartedAt     = $has('started_at');
        $hasOnHoldAt      = $has('on_hold_at');
        $hasResolvedAt    = $has('resolved_at');
        $hasClosedAt      = $has('closed_at');

        $hasRemark         = $has('remark');
        $hasResolutionNote = $has('resolution_note');
        $hasCost           = $has('cost');
        $hasSource         = $has('source');
        $hasExtra          = $has('extra');

        $hasCreatedAt      = $has('created_at');
        $hasUpdatedAt      = $has('updated_at');

        // statuses/priorities from your model
        $statuses = [
            MR::STATUS_PENDING,
            MR::STATUS_ACKNOWLEDGED,
            MR::STATUS_ACCEPTED,
            MR::STATUS_IN_PROGRESS,
            MR::STATUS_ON_HOLD,
            MR::STATUS_RESOLVED,
            MR::STATUS_CLOSED,
            MR::STATUS_CANCELLED,
        ];

        $priorities = [
            MR::PRIORITY_LOW,
            MR::PRIORITY_MEDIUM,
            MR::PRIORITY_HIGH,
            MR::PRIORITY_URGENT,
        ];

        $now = Carbon::now();

        $makeTimeline = function (string $status, Carbon $base) {
            $assigned = $accepted = $started = $onHold = $resolved = $closed = $completedDate = null;

            if (in_array($status, ['accepted','in_progress','on_hold','resolved','closed'], true)) {
                $assigned = (clone $base)->addDays(random_int(0, 3));
                $accepted = (clone $assigned)->addHours(random_int(0, 36));
            }

            if (in_array($status, ['in_progress','on_hold','resolved','closed'], true)) {
                $started = (clone ($accepted ?? $base))->addHours(random_int(1, 24));
            }

            if ($status === 'on_hold') {
                $onHold = (clone ($started ?? $accepted ?? $base))->addHours(random_int(2, 48));
            }

            if (in_array($status, ['resolved','closed'], true)) {
                $resolved = (clone ($onHold ?? $started ?? $accepted ?? $base))->addHours(random_int(2, 72));
            }

            if ($status === 'closed') {
                $closed = (clone ($resolved ?? $base))->addHours(random_int(1, 24));
                $completedDate = $closed;
            }

            return [$assigned, $accepted, $started, $onHold, $resolved, $closed, $completedDate];
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

        // --- Insert MR in a transaction ---
        $insertedMR = 0;

        DB::transaction(function () use (
            $mrTable,
            $requestCount, $chunkSize, $now,
            $assetIds, $staffIds, $techIds, $departmentIds,
            $statuses, $priorities,
            $makeTimeline, $makeRequestNo,

            $hasAssetId, $hasReporterId, $hasTechnicianId,
            $hasRequestNo, $hasDeptMR,
            $hasTitle, $hasDescription, $hasPriority, $hasStatusCol,
            $hasReporterName, $hasReporterPhone, $hasReporterEmail, $hasReporterPosition,
            $hasLegacyPayload, $hasLocationText,
            $hasRequestDate, $hasAssignedDate, $hasCompletedDate, $hasAcceptedAt, $hasStartedAt, $hasOnHoldAt, $hasResolvedAt, $hasClosedAt,
            $hasRemark, $hasResolutionNote, $hasCost, $hasSource, $hasExtra,
            $hasCreatedAt, $hasUpdatedAt,

            &$insertedMR
        ) {
            $insertCols = [];

            if ($hasAcceptedAt)       $insertCols[] = 'accepted_at';
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
            if ($hasPriority)         $insertCols[] = 'priority';
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
            if ($hasSource)           $insertCols[] = 'source';
            if ($hasStartedAt)        $insertCols[] = 'started_at';
            if ($hasStatusCol)        $insertCols[] = 'status';
            if ($hasTechnicianId)     $insertCols[] = 'technician_id';
            if ($hasTitle)            $insertCols[] = 'title';
            if ($hasUpdatedAt)        $insertCols[] = 'updated_at';

            $rows = [];

            for ($i = 1; $i <= $requestCount; $i++) {
                $createdAt = (clone $now)
                    ->subMonths(random_int(0, 11))
                    ->subDays(random_int(0, 28))
                    ->setTime(random_int(8, 17), random_int(0, 59));

                $status   = $statuses[array_rand($statuses)];
                $priority = $priorities[array_rand($priorities)];
                $assetId  = $assetIds ? $assetIds[array_rand($assetIds)] : null;
                $reporter = $staffIds ? $staffIds[array_rand($staffIds)] : null;

                // ผู้แจ้งภายนอก 10%
                $isExternal = random_int(1, 100) <= 10;
                if ($isExternal) $reporter = null;

                // มีช่างเฉพาะงานที่พ้น acknowledged/pending/cancelled ไปแล้ว
                $techId = null;
                if (!in_array($status, ['pending','acknowledged','cancelled'], true)) {
                    $techId = $techIds ? $techIds[array_rand($techIds)] : null;
                }

                [$assigned,$accepted,$started,$onHold,$resolved,$closed,$completedDate] = $makeTimeline($status, $createdAt);

                $row = array_fill_keys($insertCols, null);

                if ($hasAssetId)      $row['asset_id'] = $assetId;
                if ($hasReporterId)   $row['reporter_id'] = $reporter;
                if ($hasTechnicianId) $row['technician_id'] = $techId;

                if ($hasRequestNo) $row['request_no'] = $makeRequestNo($createdAt);

                if ($hasTitle)       $row['title']       = 'แจ้งซ่อม #'.$i;
                if ($hasDescription) $row['description'] = 'รายละเอียดปัญหาเบื้องต้น';

                if ($hasPriority)  $row['priority'] = $priority;
                if ($hasStatusCol) $row['status']   = $status;

                if ($hasDeptMR && $departmentIds) {
                    $row['department_id'] = $departmentIds[array_rand($departmentIds)];
                }

                if ($hasLocationText) {
                    $row['location_text'] = fake()->randomElement([
                        'ตึก A ชั้น 2',
                        'ตึก B ห้อง IT',
                        'หน้า ER',
                        'Ward 3',
                        'OPD 5',
                    ]);
                }

                if ($isExternal) {
                    if ($hasReporterName)     $row['reporter_name']  = fake()->name();
                    if ($hasReporterPhone)    $row['reporter_phone'] = fake()->numerify('08########');
                    if ($hasReporterEmail)    $row['reporter_email'] = fake()->safeEmail();
                    if ($hasReporterPosition) $row['reporter_position'] = fake()->jobTitle();
                }

                if ($hasLegacyPayload) {
                    $row['legacy_payload'] = json_encode([
                        'ua'       => fake()->randomElement(['Chrome','Edge','Firefox']),
                        'tz'       => 'Asia/Bangkok',
                        'seed'     => true,
                        'external' => $isExternal,
                    ]);
                }

                if ($hasRequestDate)   $row['request_date']   = $createdAt;
                if ($hasAssignedDate)  $row['assigned_date']  = $assigned;
                if ($hasAcceptedAt)    $row['accepted_at']    = $accepted;
                if ($hasStartedAt)     $row['started_at']     = $started;
                if ($hasOnHoldAt)      $row['on_hold_at']     = $onHold;
                if ($hasResolvedAt)    $row['resolved_at']    = $resolved;
                if ($hasClosedAt)      $row['closed_at']      = $closed;
                if ($hasCompletedDate) $row['completed_date'] = $completedDate;

                if ($hasRemark) {
                    $row['remark'] = match ($status) {
                        'pending'       => null,
                        'acknowledged'  => 'รับทราบแล้ว รอช่างรับเรื่อง',
                        'accepted'      => 'รับเรื่องแล้ว',
                        'in_progress'   => 'กำลังดำเนินการ',
                        'on_hold'       => 'รอชิ้นส่วน/ช่างเฉพาะทาง',
                        'resolved'      => 'แก้เสร็จ รอปิดงาน',
                        'closed'        => 'ปิดงานเรียบร้อย',
                        'cancelled'     => 'ผู้แจ้งยกเลิก',
                        default         => null,
                    };
                }

                if ($hasResolutionNote && in_array($status, ['resolved','closed'], true)) {
                    $row['resolution_note'] = fake()->sentence(8);
                }

                if ($hasCost && in_array($status, ['resolved','closed'], true)) {
                    $row['cost'] = fake()->randomFloat(2, 200, 8000);
                }

                if ($hasSource) $row['source'] = 'web';
                if ($hasExtra)  $row['extra']  = null;

                $updatedAt = $closed ?? $resolved ?? $onHold ?? $started ?? $accepted ?? $assigned ?? $createdAt;
                if ($hasCreatedAt) $row['created_at'] = $createdAt;
                if ($hasUpdatedAt) $row['updated_at'] = $updatedAt;

                $rows[] = $row;

                if (count($rows) >= $chunkSize) {
                    DB::table($mrTable)->insert($rows);
                    $insertedMR += count($rows);
                    $rows = [];
                }
            }

            if ($rows) {
                DB::table($mrTable)->insert($rows);
                $insertedMR += count($rows);
            }
        });

        $this->ok('maintenance_requests seeded', [
            'inserted' => (string) $insertedMR,
        ]);

        // ================== 7) Assignments ==================
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
                ->select(
                    'mr.id',
                    'mr.technician_id',
                    'mr.resolution_note',
                    'mr.resolved_at',
                    'mr.closed_at',
                    'mr.started_at',
                    'mr.request_date',
                    'mr.created_at',
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

                $opRows[] = [
                    'maintenance_request_id' => $r->id,
                    'user_id'                => $userId,
                    'operation_date'         => $operationDate,
                    'operation_method'       => $methods[array_rand($methods)],
                    'property_code'          => $hasPropertyCode ? ($r->asset_code ?? null) : null,
                    'require_precheck'       => (bool) random_int(0, 1),
                    'remark'                 => $r->resolution_note ?: fake()->sentence(10),
                    'issue_software'         => (bool) random_int(0, 1),
                    'issue_hardware'         => (bool) random_int(0, 1),
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
        $this->command?->info(str_repeat('=', 72));
        $this->command?->info("▶  {$text}");
        $this->command?->info(str_repeat('=', 72));
    }

    private function infoBlock(string $text): void
    {
        $this->command?->info("ℹ️  {$text}");
    }

    private function ok(string $title, array $meta = []): void
    {
        $suffix = $meta ? ' | ' . $this->formatMeta($meta) : '';
        $this->command?->info("✅ {$title}{$suffix}");
    }

    private function warn(string $text): void
    {
        $this->command?->warn("⚠️  {$text}");
    }

    private function done(string $text): void
    {
        $this->command?->info("🏁 {$text}");
        $this->command?->info(str_repeat('-', 72));
    }

    private function kv(string $k, string $v): void
    {
        $this->command?->line(" - {$k}: {$v}");
    }

    private function lineBreak(): void
    {
        $this->command?->line('');
    }

    private function formatMeta(array $meta): string
    {
        $parts = [];
        foreach ($meta as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        return implode(', ', $parts);
    }

    // ================== seed pieces ==================

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

        if ($hasCode && $hasNameTh && !DB::table('departments')->exists()) {
            $now = now();
            DB::table('departments')->insert([
                ['code'=>'IT','name_th'=>'ฝ่าย IT & Support','name_en'=>'IT & Support','created_at'=>$now,'updated_at'=>$now],
                ['code'=>'ER','name_th'=>'ห้องฉุกเฉิน','name_en'=>'Emergency Room','created_at'=>$now,'updated_at'=>$now],
                ['code'=>'OPD','name_th'=>'ผู้ป่วยนอก','name_en'=>'OPD','created_at'=>$now,'updated_at'=>$now],
                ['code'=>'WARD','name_th'=>'วอร์ดผู้ป่วยใน','name_en'=>'Ward','created_at'=>$now,'updated_at'=>$now],
                ['code'=>'ADMIN','name_th'=>'ฝ่ายธุรการ','name_en'=>'Administration','created_at'=>$now,'updated_at'=>$now],
                ['code'=>'LAB','name_th'=>'ห้องปฏิบัติการ','name_en'=>'Laboratory','created_at'=>$now,'updated_at'=>$now],
            ]);
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
            if ($hasWarranty)     $row['warranty_expire'] = $warrantyAt;

            if ($hasStatus) {
                $roll          = mt_rand(1, 100);
                $row['status'] = $roll <= 75 ? 'active' : ($roll <= 95 ? 'in_repair' : 'disposed');
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
