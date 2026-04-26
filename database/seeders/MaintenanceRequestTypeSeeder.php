<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequestType;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestTypeSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับประเภทการแจ้งซ่อม
    public function run(): void
    {
        // ล้างข้อมูลเก่าเพื่อตั้งต้นใหม่ (Software, Network, Hardware)
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('maintenance_request_types')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // รายการประเภทการซ่อมหลักๆ พร้อมตั้งค่า SLA เริ่มต้น
        $rows = [
            [
                'name' => 'Software',
                'description' => 'ปัญหาโปรแกรม, ระบบ HIS, การเข้าใช้งาน, แก้ไขฐานข้อมูล',
                'default_department_code' => 'IT',
                'default_role_code' => 'programmer',
                'default_response_minutes' => 60,
                'default_resolution_minutes' => 480, // 8h
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Network',
                'description' => 'ปัญหาอินเทอร์เน็ต, LAN, WiFi, สายสัญญาณ',
                'default_department_code' => 'IT',
                'default_role_code' => 'network',
                'default_response_minutes' => 30,
                'default_resolution_minutes' => 240, // 4h
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware',
                'description' => 'คอมพิวเตอร์พัง, Printer, จอภาพ, อุปกรณ์ต่อพ่วง',
                'default_department_code' => 'IT',
                'default_role_code' => 'support',
                'default_response_minutes' => 120,
                'default_resolution_minutes' => 1440, // 24h
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $r) {
            MaintenanceRequestType::create($r);
        }
    }
}
