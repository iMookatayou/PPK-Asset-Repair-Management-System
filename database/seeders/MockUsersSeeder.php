<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class MockUsersSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับสร้างผู้ใช้งานจำลอง (Mock Users)
    public function run(): void
    {
        // รายการผู้ใช้สำหรับการทดสอบระบบในบทบาทต่างๆ
        $users = [
            [
                'name'        => 'Admin',
                'citizen_id'  => '1000000000001',
                'email'       => 'admin.simple@example.com',
                'role'        => User::ROLE_ADMIN,
                'password'    => '12345678',
            ],
            [
                'name'        => 'หัวหน้า ทดสอบ',
                'citizen_id'  => '1000000000002',
                'email'       => 'supervisor@example.com',
                'role'        => User::ROLE_SUPERVISOR,
                'password'    => '12345678',
            ],
            [
                'name'        => 'เจ้าหน้าที่ ไอที 1',
                'citizen_id'  => '1000000000003',
                'email'       => 'it1@example.com',
                'role'        => User::ROLE_IT_SUPPORT,
                'department'  => 'IT',
                'password'    => '12345678',
            ],
            [
                'name'        => 'เจ้าหน้าที่ ไอที 2',
                'citizen_id'  => '1000000000004',
                'email'       => 'it2@example.com',
                'role'        => User::ROLE_IT_SUPPORT,
                'department'  => 'IT',
                'password'    => '12345678',
            ],
            [
                'name'        => 'เจ้าหน้าที่ เน็ตเวิร์ค 1',
                'citizen_id'  => '1000000000005',
                'email'       => 'net1@example.com',
                'role'        => User::ROLE_NETWORK,
                'department'  => 'IT',
                'password'    => '12345678',
            ],
            [
                'name'        => 'เจ้าหน้าที่ นักพัฒนา 1',
                'citizen_id'  => '1000000000006',
                'email'       => 'dev1@example.com',
                'role'        => User::ROLE_DEVELOPER,
                'department'  => 'IT',
                'password'    => '12345678',
            ],
            [
                'name'        => 'บุคลากร ตัวอย่าง',
                'citizen_id'  => '1000000000007',
                'email'       => 'member1@example.com',
                'role'        => User::ROLE_MEMBER,
                'department'  => 'OPD',
                'password'    => '12345678',
            ],
            [
                'name'        => 'Admin',
                'citizen_id'  => '1234567890123',
                'email'       => 'dev@example.com',
                'role'        => User::ROLE_ADMIN,
                'password'    => 'Dev12345!',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['citizen_id' => $u['citizen_id']],
                [
                    'name'              => $u['name'],
                    'citizen_id'        => $u['citizen_id'],
                    'email'             => $u['email'],
                    'role'              => $u['role'],
                    'password'          => Hash::make($u['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
