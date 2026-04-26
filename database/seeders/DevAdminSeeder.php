<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevAdminSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับสร้าง Admin หลักของระบบ (Developer Admin)
    public function run(): void
    {
        // ป้องกันไม่ให้รัน Seeder นี้บน Production Environment เพื่อความปลอดภัย
        if (app()->environment('production')) {
            $this->command?->warn('DevAdminSeeder: skipped on production environment.');
            return;
        }

        // ดึงข้อมูลการตั้งค่าจากไฟล์ .env (ถ้าไม่มีจะใช้ค่าเริ่มต้นที่กำหนดไว้)
        $citizenId = env('DEV_ADMIN_CITIZEN_ID', '1234567890123');
        $email     = env('DEV_ADMIN_EMAIL', 'dev@example.com');
        $name      = env('DEV_ADMIN_NAME', 'Developer Admin');
        $password  = env('DEV_ADMIN_PASSWORD', 'Dev12345!');

        // สร้างหรืออัปเดตข้อมูลแอดมิน
        User::updateOrCreate(
            ['citizen_id' => $citizenId],
            [
                'name'              => $name,
                'citizen_id'        => $citizenId,
                'email'             => $email,
                'email_verified_at' => now(),
                'password'          => Hash::make($password),
                'role'              => User::ROLE_ADMIN,
                'department'        => 'IT',
            ],
        );

        $this->command?->info("Dev admin user seeded: citizen_id={$citizenId}, email={$email}");
    }
}
