<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Models\MaintenanceRequest;

/**
 * สร้างไทม์ไลน์ (maintenance_logs) ให้กับทุก Maintenance Request
 * - ดึงเวลาจากคอลัมน์หลักของ request แล้วแปลงเป็น action
 * - กันซ้ำโดยตรวจสอบ request_id + action + created_at
 */
class MaintenanceLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ดึงทั้งหมดทีละก้อน เผื่อโปรเจ็กต์จริงมีข้อมูลเยอะ
            MaintenanceRequest::query()
                ->orderBy('id')
                ->chunk(200, function ($requests) {
                    foreach ($requests as $req) {
                        $this->seedLogsForRequest($req);
                    }
                });
        });
    }

    protected function seedLogsForRequest(MaintenanceRequest $req): void
    {
        // helper สำหรับสร้างแถวแบบกันซ้ำ
        $createIfNotExists = function (array $row) {
            $exists = DB::table('maintenance_logs')->where([
                'request_id' => $row['request_id'],
                'action'     => $row['action'],
                'created_at' => $row['created_at'],
            ])->exists();

            if (!$exists) {
                DB::table('maintenance_logs')->insert($row);
            }
        };

        // 1) Created (always)
        $createIfNotExists([
            'request_id' => $req->id,
            'user_id'    => $req->reporter_id, // คนแจ้ง
            'action'     => 'created',
            'note'       => $this->makeNote('สร้างคำขอซ่อม', [
                'title'    => $req->title,
                'priority' => $req->priority,
                'source'   => $req->source,
            ]),
            'created_at' => $req->request_date ?? $req->created_at,
        ]);

        // 2) Assigned
        if ($req->assigned_date && $req->technician_id) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'assigned',
                'note'       => 'มอบหมายงานให้ช่างแล้ว',
                'created_at' => $req->assigned_date,
            ]);
        }

        // 3) Accepted
        if ($req->accepted_at && $req->technician_id) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'accepted',
                'note'       => 'ช่างรับงาน',
                'created_at' => $req->accepted_at,
            ]);
        }

        // 4) Started
        if ($req->started_at && $req->technician_id) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'started',
                'note'       => 'เริ่มดำเนินการตรวจเช็ค/ซ่อม',
                'created_at' => $req->started_at,
            ]);
        }

        // 5) On hold
        if ($req->on_hold_at && $req->technician_id) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'on_hold',
                'note'       => 'พักงานชั่วคราว (รออะไหล่/รอผู้ใช้งาน)',
                'created_at' => $req->on_hold_at,
            ]);
        }

        // 6) Resolved
        if ($req->resolved_at && $req->technician_id) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'resolved',
                'note'       => $this->makeNote('แก้ไขเสร็จสิ้น (รอปิดงาน)', [
                    'resolution' => $req->resolution_note,
                    'cost'       => $req->cost,
                ]),
                'created_at' => $req->resolved_at,
            ]);
        }

        // 7) Closed
        if ($req->closed_at) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id, // หรือผู้อนุมัติปิดงาน ถ้ามี
                'action'     => 'closed',
                'note'       => 'ปิดงานเรียบร้อย',
                'created_at' => $req->closed_at,
            ]);
        }

        // (ทางเลือก) ถ้าไม่มี timestamps อย่าง started/accepted
        // แต่อยู่ในสถานะ in_progress → เติม comment หนึ่งบรรทัด เพื่อให้เห็นการเคลื่อนไหว
        if ($req->status === 'in_progress' && !$req->started_at) {
            $createIfNotExists([
                'request_id' => $req->id,
                'user_id'    => $req->technician_id,
                'action'     => 'comment',
                'note'       => 'อัปเดตสถานะ: อยู่ระหว่างตรวจเช็คอาการ',
                'created_at' => ($req->assigned_date ?? $req->request_date ?? now())->addMinutes(30),
            ]);
        }
    }

    protected function makeNote(string $title, array $kv = []): string
    {
        $kv = Arr::where($kv, fn($v) => filled($v));
        if (empty($kv)) return $title;
        $tail = collect($kv)->map(fn($v, $k) => "{$k}: {$v}")->implode(', ');
        return "{$title} — {$tail}";
    }
}
