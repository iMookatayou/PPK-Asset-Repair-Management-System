<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // ฟังก์ชันหลักที่รัน Seeder ทั้งหมด
    public function run(): void
    {
        $this->call([
            // 1. ข้อมูลพื้นฐานที่จำเป็น (Essential Metadata)
            RoleSeeder::class,               // สร้างสิทธิ์การใช้งาน (Admin, User, etc.)
            MaintenanceRequestTypeSeeder::class, // สร้างประเภทการซ่อม (Hardware, Software, etc.)
            MockUsersSeeder::class,          // สร้างผู้ใช้งานจำลองสำหรับการทดสอบ

            // 2. ข้อมูลจำลองหลัก (Master Demo Data)
            DemoDataSeeder::class,           // สร้างข้อมูลครุภัณฑ์และรายการซ่อมจำลอง

            // 3. ข้อมูลเสริมเพื่อความสมบูรณ์ (Optional Enrichment)
            ChatSeeder::class,               // ข้อความแชทคุยกันในใบซ่อม
            DemoRatingSeeder::class,         // ข้อมูลการประเมินความพึงพอใจ
            // EvaluationTestDataSeeder::class, // (ปิดไว้) ข้อมูลทดสอบประสิทธิภาพ
            AdminEvaluationSeeder::class,    // ข้อมูลการประเมินสำหรับแอดมิน
        ]);
    }
}
