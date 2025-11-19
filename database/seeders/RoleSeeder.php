<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'code'       => 'admin',
                'name_th'    => 'ผู้ดูแลระบบ',
                'name_en'    => 'Administrator',
                'sort_order' => 10,
            ],
            [
                'code'       => 'supervisor',
                'name_th'    => 'หัวหน้าหน่วยงาน',
                'name_en'    => 'Supervisor',
                'sort_order' => 20,
            ],
            [
                'code'       => 'it_support',
                'name_th'    => 'เจ้าหน้าที่ IT',
                'name_en'    => 'IT Support',
                'sort_order' => 30,
            ],
            [
                'code'       => 'network',
                'name_th'    => 'เจ้าหน้าที่ Network',
                'name_en'    => 'Network Admin',
                'sort_order' => 40,
            ],
            [
                'code'       => 'developer',
                'name_th'    => 'นักพัฒนา',
                'name_en'    => 'Developer',
                'sort_order' => 50,
            ],
            [
                'code'       => 'technician',
                'name_th'    => 'ช่างซ่อมบำรุง',
                'name_en'    => 'Technician',
                'sort_order' => 60,
            ],
            [
                'code'       => 'member',
                'name_th'    => 'บุคลากรทั่วไป',
                'name_en'    => 'Member',
                'sort_order' => 80,
            ],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(
                ['code' => $r['code']],
                $r
            );
        }
    }
}
