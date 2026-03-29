<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_operation_logs', function (Blueprint $table) {
            $table->id();

            // ผูกกับใบงาน (1 request มีได้ 1 operation log)
            $table->foreignId('maintenance_request_id')
                ->constrained('maintenance_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // คนที่บันทึก (ช่าง / แอดมิน)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // วันที่ปฏิบัติงาน / วันที่ลงรายงาน
            $table->date('operation_date')->nullable();

            // วิธีการปฏิบัติ : ตามใบเบิก / ค่าบริการ / อื่น ๆ
            $table->enum('operation_method', ['requisition', 'service_fee', 'other'])
                ->nullable();

            // รหัสครุภัณฑ์ (รพจ.)
            $table->string('property_code', 100)
                ->nullable()
                ->comment('รหัสครุภัณฑ์ (รพจ.)');

            // ต้องมีการแจ้ง/ขออนุญาตก่อนปฏิบัติงาน/ปิดเครื่อง
            $table->boolean('require_precheck')->default(false);

            // หมายเหตุ/รายละเอียดเพิ่มเติมในการปฏิบัติงาน
            $table->text('remark')->nullable();

            // ประเภทปัญหา
            $table->boolean('issue_software')->default(false);
            $table->boolean('issue_hardware')->default(false);

            $table->timestamps();

            // ===== Index/Unique ให้ตรง schema dump =====
            $table->unique('maintenance_request_id', 'uniq_operation_log_request');

            $table->index('user_id');               // maintenance_operation_logs_user_id_index
            $table->index('operation_date');        // maintenance_operation_logs_operation_date_index
            $table->index('property_code');         // maintenance_operation_logs_property_code_index
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_operation_logs');
    }
};
