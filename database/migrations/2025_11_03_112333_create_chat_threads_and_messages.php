<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('chat_threads', function (Blueprint $t) {
      $t->id();
      $t->string('title', 180);
      $t->foreignId('author_id')->constrained('users')->cascadeOnDelete();
      $t->boolean('is_locked')->default(false);
      $t->timestamps();
      $t->index(['created_at']);
    });

    Schema::create('chat_messages', function (Blueprint $t) {
      $t->id();
      $t->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $t->text('body');
      $t->timestamps();
      $t->index(['thread_id','created_at']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('chat_messages');
    Schema::dropIfExists('chat_threads');
  }
};
