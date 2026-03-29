<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();

            // ====== เลขใบงาน ======
            $table->string('request_no', 32)->nullable()->unique();

            // ====== ครุภัณฑ์ ======
            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // ====== ผู้แจ้ง ======
            $table->foreignId('reporter_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('reporter_name', 255)->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->string('reporter_email', 255)->nullable();

            // ====== หน่วยงาน / สถานที่ (ของผู้แจ้ง/จุดเกิดเหตุ) ======
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // ====== ประเภทงาน (Report Type) ======
            $table->foreignId('type_id')
                ->nullable()
                ->constrained('maintenance_request_types')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('location_text', 255)->nullable();

            // ====== รายละเอียดงาน ======
            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                ->default('medium');

            // ====== สถานะ ======
            $table->string('status', 32)->default('pending');
            $table->timestamp('status_updated_at')->nullable();
            $table->foreignId('status_updated_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // ====== ผู้รับผิดชอบ (current assignee) ======
            $table->foreignId('technician_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // ====== Timeline ======
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('assigned_date')->nullable();
            $table->timestamp('completed_date')->nullable(); // legacy

            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('on_hold_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamp('sla_due_date')->nullable();
            $table->unsignedInteger('paused_duration_minutes')->default(0);

            // ====== ผลการซ่อม ======
            $table->text('remark')->nullable();
            $table->text('resolution_note')->nullable();
            $table->decimal('cost', 10, 2)->nullable();

            // ====== Metadata ======
            $table->string('source', 32)->default('web');
            $table->json('extra')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ====== Index ======
            $table->index(['asset_id', 'request_date']);
            $table->index(['status', 'priority']);
            $table->index(['technician_id', 'status']);
            $table->index(['resolved_at', 'closed_at']);
            $table->index(['department_id', 'status']);
            $table->index(['type_id', 'status']);

            // index audit
            $table->index(['status_updated_at']);
            $table->index(['status_updated_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
