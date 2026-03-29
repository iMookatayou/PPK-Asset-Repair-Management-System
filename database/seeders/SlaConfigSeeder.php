<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SlaConfig;

class SlaConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'priority_level' => SlaConfig::PRIORITY_DEFAULT,
                'name' => 'Standard SLA (งานทั่วไป)',
                'response_time_minutes' => 120, // 2 hours
                'resolution_time_minutes' => 2880, // 48 hours
                'is_active' => true,
                'description' => 'เป้าหมายมาตรฐานสำหรับใบแจ้งซ่อมที่ไม่ได้ระบุความเร่งด่วน (ระบบงานไหนมาก่อนทำก่อน)',
            ],
            [
                'priority_level' => SlaConfig::PRIORITY_URGENT,
                'name' => 'Urgent SLA (เร่งด่วนมาก)',
                'response_time_minutes' => 30, // 30 minutes
                'resolution_time_minutes' => 240, // 4 hours
                'is_active' => true,
                'description' => 'งานฉุกเฉินที่ส่งผลกระทบระดับวิกฤต (เร่งด่วนมาก)',
            ],
            [
                'priority_level' => SlaConfig::PRIORITY_HIGH,
                'name' => 'High SLA (เร่งด่วน)',
                'response_time_minutes' => 60, // 1 hour
                'resolution_time_minutes' => 1440, // 24 hours
                'is_active' => true,
                'description' => 'งานสำคัญที่ต้องรีบดำเนินการแก้ไข (เร่งด่วน)',
            ],
            [
                'priority_level' => SlaConfig::PRIORITY_MEDIUM,
                'name' => 'Medium SLA (ปานกลาง)',
                'response_time_minutes' => 240, // 4 hours
                'resolution_time_minutes' => 2880, // 48 hours
                'is_active' => true,
                'description' => 'งานซ่อมบำรุงทั่วไป ความเร่งด่วนระดับปานกลาง',
            ],
            [
                'priority_level' => SlaConfig::PRIORITY_LOW,
                'name' => 'Low SLA (ปกติ)',
                'response_time_minutes' => 480, // 8 hours
                'resolution_time_minutes' => 4320, // 72 hours
                'is_active' => true,
                'description' => 'งานซ่อมบำรุงที่รอได้ ความเร่งด่วนต่ำ',
            ],
        ];

        foreach ($configs as $config) {
            SlaConfig::updateOrCreate(
                ['priority_level' => $config['priority_level']],
                $config
            );
        }
    }
}
