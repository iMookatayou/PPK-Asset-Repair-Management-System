<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('files', function (Blueprint $t) {
            $t->id();
            $t->string('path', 2048);
            $t->string('disk', 50)->default('public');
            $t->string('mime', 100)->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->string('checksum_sha256', 64)->nullable()->unique();
            $t->foreignId('variant_of_id')->nullable()->constrained('files')->nullOnDelete();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->string('path_hash', 64)->nullable();
            $t->index(['mime']);
            $t->index(['disk', 'path_hash'], 'files_disk_path_hash_idx');
            $t->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
