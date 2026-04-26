<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;
use App\Models\User;

class DemoRatingSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับสร้างคะแนนการประเมินจำลอง (Ratings)
    public function run(): void
    {
        // 1. ดึงงาน Demo ทั้งหมดที่ซ่อมเสร็จแล้ว (Resolved หรือ Closed)
        // เราจะกวาดล้างงาน Demo ทุกใบให้มีคะแนน เพื่อไม่ให้ค้างที่หน้า Dashboard
        $demoJobs = MaintenanceRequest::whereIn('status', [MaintenanceRequest::STATUS_CLOSED])
            ->where(function($q) {
                $q->whereJsonContains('extra->is_demo', true)
                  ->orWhereJsonContains('extra->is_demo', 1)
                  ->orWhereJsonContains('extra->is_demo', "1");
            })
            ->get();

        $comments = [
            'บริการดีมากครับ',
            'แก้ไขปัญหาได้รวดเร็ว',
            'สุภาพเรียบร้อย',
            'ให้คำแนะนำดีมาก',
            'มาช้าไปนิดแต่ซ่อมดีครับ',
            'ขอบคุณที่ช่วยดูแลครับ',
            'ช่างมีความเชี่ยวชาญมาก',
        ];

        // เตรียมรายชื่อพนักงานเผื่อกรณีไม่มีผู้แจ้ง
        $fallbackReporters = User::whereIn('role', ['member', 'admin'])->pluck('id')->toArray();
        $fallbackTechs = User::whereIn('role', User::workerRoles())->pluck('id')->toArray();

        $count = 0;
        foreach ($demoJobs as $job) {
            // ใช้ updateOrCreate เพื่อไม่ให้เกิดข้อมูลซ้ำ
            MaintenanceRating::updateOrCreate(
                ['maintenance_request_id' => $job->id],
                [
                    'rater_id'      => $job->reporter_id ?: (!empty($fallbackReporters) ? $fallbackReporters[array_rand($fallbackReporters)] : 1),
                    'technician_id' => $job->technician_id ?: (!empty($fallbackTechs) ? $fallbackTechs[array_rand($fallbackTechs)] : 1),
                    'score'         => rand(3, 5),
                    'comment'       => rand(1, 100) <= 30 ? $comments[array_rand($comments)] : null,
                    'created_at'    => $job->closed_at ?? $job->resolved_at ?? now(),
                ]
            );
            $count++;
        }

        // $this->command->info("Rated {$count} demo jobs successfully.");
    }
}
