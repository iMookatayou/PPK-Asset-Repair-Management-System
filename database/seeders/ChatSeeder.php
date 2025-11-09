<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $users = User::pluck('id')->all();

        if (empty($users)) {
            $this->command->warn('⚠️ ไม่มี user ในระบบ — ข้ามการสร้าง ChatSeeder');
            return;
        }

        $threads = [];
        $messages = [];

        for ($i = 1; $i <= 20; $i++) {
            $authorId = fake()->randomElement($users);
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

        foreach ($threadIds as $threadId) {
            $msgCount = random_int(3, 8);
            for ($j = 0; $j < $msgCount; $j++) {
                $userId = fake()->randomElement($users);
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
