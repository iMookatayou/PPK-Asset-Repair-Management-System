<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class ChatSeeder extends Seeder
{
    // เริ่มต้นการรัน Seeder สำหรับข้อมูลจำลองระบบแชท
    public function run(): void
    {
        // Cleanup: ล้างข้อมูลแชทเก่าออกให้หมดก่อนเพื่อเริ่มต้นใหม่
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('chat_messages')->truncate();
        DB::table('chat_threads')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();
        $users = User::pluck('id')->all();

        // ตรวจสอบว่ามีผู้ใช้งานในระบบหรือไม่
        if (empty($users)) {
            $this->command->warn('⚠️ ไม่มี user ในระบบ — ข้ามการสร้าง ChatSeeder');
            return;
        }

        $threads = [];
        $messages = [];

        // สร้างหัวข้อสนทนา (Threads) จำนวน 20 หัวข้อ
        for ($i = 1; $i <= 20; $i++) {
            // บังคับให้ Dev Admin (ID 409) เป็นคนสร้างกระทู้ใน 5 กระทู้แรก
            $authorId = ($i <= 5) ? (User::find(409) ? 409 : fake()->randomElement($users)) : fake()->randomElement($users);
            $title = ucfirst(fake()->words(random_int(2, 5), true));

            $threads[] = [
                'title'       => $title,
                'author_id'   => $authorId,
                'is_locked'   => fake()->boolean(10),
                'created_at'  => $now->copy()->subDays(random_int(0, 30)),
                'updated_at'  => $now,
            ];
        }

        DB::table('chat_threads')->insert($threads);
        $threadIds = DB::table('chat_threads')->pluck('id')->all();

        foreach ($threadIds as $index => $threadId) {
            $msgCount = random_int(3, 8);
            for ($j = 0; $j < $msgCount; $j++) {
                // บังคับให้ Dev Admin (ID 409) ตอบในกระทู้อื่นๆ
                $userId = ($index > 5 && $j === 0) ? (User::find(409) ? 409 : fake()->randomElement($users)) : fake()->randomElement($users);
                $messages[] = [
                    'chat_thread_id' => $threadId,
                    'user_id'        => $userId,
                    'body'           => fake()->paragraph(random_int(1, 3)),
                    'created_at'     => $now->copy()->subMinutes(random_int(0, 3000)),
                    'updated_at'     => $now,
                ];
            }
        }

        DB::table('chat_messages')->insert($messages);
    }
}
