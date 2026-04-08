<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequestType;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        // ล้างข้อมูลเก่าก่อนเพื่อให้เหลือแค่ 3 ประเภทหลักตามต้องการ โดยปิด check foreign key ชั่วคราว
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('maintenance_request_types')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $rows = [
            [
                'name' => 'Software',
                'description' => 'ปัญหาโปรแกรม, ระบบ HIS, การเข้าใช้งาน, แก้ไขฐานข้อมูล',
                'default_department_code' => 'IT',
                'default_role_code' => 'programmer',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Network',
                'description' => 'ปัญหาอินเทอร์เน็ต, LAN, WiFi, สายสัญญาณ',
                'default_department_code' => 'IT',
                'default_role_code' => 'network',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware',
                'description' => 'คอมพิวเตอร์พัง, Printer, จอภาพ, อุปกรณ์ต่อพ่วง',
                'default_department_code' => 'IT',
                'default_role_code' => 'support',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $r) {
            MaintenanceRequestType::create($r);
        }
    }
}
