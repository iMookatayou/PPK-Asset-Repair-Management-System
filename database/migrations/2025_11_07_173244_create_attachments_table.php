<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // เป้าหมายแบบ polymorphic: ใช้ซ้ำได้ทุกโมดูล
            $table->morphs('attachable'); // attachable_type, attachable_id (indexed)

            // ข้อมูลไฟล์หลัก
            $table->string('original_name', 255);
            $table->string('extension', 16)->nullable();
            $table->string('path', 2048);                  // storage key / path
            $table->string('disk', 50)->default('public'); // local|public|s3
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum_sha256', 64)->nullable()->unique(); // กันไฟล์ซ้ำ / ตรวจครบถ้วน

            // การแสดงผล / ลำดับ
            $table->string('caption', 512)->nullable();
            $table->string('alt_text', 512)->nullable();
            $table->unsignedInteger('order_column')->default(0);

            // ความเป็นส่วนตัว & ความสัมพันธ์เวอร์ชัน (เช่น thumbnail ของไฟล์แม่)
            $table->boolean('is_private')->default(false);
            $table->foreignId('variant_of_id')->nullable()
                  ->constrained('attachments')->nullOnDelete();

            // audit & meta
            $table->foreignId('uploaded_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('web'); // web|api|import...
            $table->json('meta')->nullable();             // exif,width,height,duration,...

            // นโยบายเก็บรักษา
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes ที่ใช้จริง
            $table->index(['attachable_type', 'attachable_id', 'created_at']);
            $table->index(['mime']);
            $table->index(['is_private']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
