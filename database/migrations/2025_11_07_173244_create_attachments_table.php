<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // ชี้ไปยังไฟล์จริง (ต้องมีตาราง files ก่อน)
            $table->foreignId('file_id')
                  ->constrained('files')
                  ->cascadeOnDelete();

            // เป้าหมายแบบ polymorphic: แนบได้ทุกโมดูล
            $table->morphs('attachable'); // attachable_type + attachable_id + index

            // ชื่อไฟล์ตอนอัปโหลด (เพื่อแสดงผลเท่านั้น)
            $table->string('original_name', 255);
            $table->string('extension', 16)->nullable();

            // การแสดงผล / ลำดับ
            $table->string('caption', 512)->nullable();
            $table->string('alt_text', 512)->nullable();
            $table->unsignedInteger('order_column')->default(0);

            // ความเป็นส่วนตัว
            $table->boolean('is_private')->default(false);

            // ผู้ใช้ที่อัปโหลด + ช่องทาง
            $table->foreignId('uploaded_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->string('source', 32)->default('web'); // web|api|import|job|seed ...

            // นโยบายเก็บรักษา
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes ที่ใช้จริง
            $table->index(['attachable_type', 'attachable_id', 'created_at'], 'attachments_target_created_idx');
            $table->index(['is_private'], 'attachments_is_private_idx');
            $table->index(['uploaded_by'], 'attachments_uploaded_by_idx');

            // กันแนบไฟล์เดียวกันซ้ำกับ target เดิม (ถ้าไม่ต้องการ uniqueness ให้คอมเมนต์ออก)
            $table->unique(['attachable_type', 'attachable_id', 'file_id'], 'attachments_unique_per_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
