<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequestType;

class MaintenanceRequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Software',
                'description' => 'ปัญหาโปรแกรม/ระบบ/ติดตั้ง/อัปเดต',
                'default_department_code' => 'IT',
                'default_role_code' => 'programmer',
                'default_user_id' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Network',
                'description' => 'LAN/WiFi/Internet/สายแลน',
                'default_department_code' => 'IT',
                'default_role_code' => 'network',
                'default_user_id' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Hardware/Support',
                'description' => 'คอมพัง/อุปกรณ์/Printer/Support ทั่วไป',
                'default_department_code' => 'IT',
                'default_role_code' => 'support',
                'default_user_id' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $r) {
            MaintenanceRequestType::updateOrCreate(
                ['name' => $r['name']],
                $r
            );
        }
    }
}
