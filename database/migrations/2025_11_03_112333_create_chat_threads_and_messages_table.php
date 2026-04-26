<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $t) {
            $t->id();

            // หัวข้อของห้องแชท หรือหัวเรื่องการสนทนา
            $t->string('title', 180);

            // ผู้สร้างห้องแชท (Author)
            $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();

            // สถานะการล็อคห้องแชท (true = ปิดรับข้อความใหม่, false = คุยต่อได้)
            $t->boolean('is_locked')->default(false);

            $t->timestamps();
            $t->softDeletes();

            $t->index('created_at', 'chat_threads_created_at_idx');
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();

            // ห้องแชทที่ข้อความนี้สังกัดอยู่
            $t->foreignId('chat_thread_id')->constrained('chat_threads')->cascadeOnDelete();

            // ผู้ส่งข้อความ
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // เนื้อหาข้อความการสนทนา
            $t->text('body');

            $t->timestamps();
            $t->softDeletes();

            $t->index(['chat_thread_id', 'created_at'], 'chat_messages_thread_created_idx');
            $t->index('user_id', 'chat_messages_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
    }
};
