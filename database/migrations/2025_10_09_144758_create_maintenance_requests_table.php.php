<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('priority', ['low','medium','high','urgent'])->default('medium');

            // ใช้ string แทน enum เพื่อรองรับสถานะเพิ่มในอนาคต
            $table->string('status', 32)->default('pending');

            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // ไทม์ไลน์หลัก
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('assigned_date')->nullable();
            $table->timestamp('completed_date')->nullable(); // legacy/back-compat

            // เวิร์กโฟลว์ละเอียด
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('on_hold_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('remark')->nullable();
            $table->decimal('cost', 10, 2)->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['asset_id', 'request_date']);
            $table->index(['status', 'priority']);
            $table->index(['technician_id', 'status']);
            $table->index(['resolved_at', 'closed_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('maintenance_requests');
    }
};
