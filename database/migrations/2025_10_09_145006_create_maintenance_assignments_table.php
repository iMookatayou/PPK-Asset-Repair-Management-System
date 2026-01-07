<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_request_id')
                ->constrained('maintenance_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // บทบาทในงาน (เช่น tech, helper)
            $table->string('role', 50)->nullable();

            // เป็นช่างหลักหรือไม่
            $table->boolean('is_lead')->default(false);

            // วันที่ถูกมอบหมาย
            $table->dateTime('assigned_at')->nullable();

            /**
             * การตอบรับงานของช่าง
             * - pending        : ยังไม่ตอบ
             * - acknowledged  : รับทราบแล้ว
             * - accepted      : รับเรื่อง
             * - rejected      : ไม่รับเรื่อง
             */
            $table->enum('response_status', [
                'pending',
                'acknowledged',
                'accepted',
                'rejected',
            ])->default('pending');

            // วันที่ตอบรับ / ปฏิเสธ
            $table->dateTime('responded_at')->nullable();

            /**
             * เหตุผล / บันทึกการตอบรับ
             * ใช้กับกรณี "ไม่รับเรื่อง" เป็นหลัก
             */
            $table->string('remark', 2000)->nullable();

            /**
             * สถานะความคืบหน้างาน (ระดับ assignment)
             * - assigned     : อยู่ในรายการ / ยังไม่เริ่ม
             * - in_progress  : กำลังดำเนินการ
             * - done         : เสร็จสิ้น
             * - cancelled    : ยกเลิก / คืนงาน / ไม่รับเรื่อง
             */
            $table->enum('status', [
                'assigned',
                'in_progress',
                'done',
                'cancelled',
            ])->default('assigned');

            $table->timestamps();

            // หนึ่งช่าง ต่อหนึ่งใบงาน
            $table->unique(
                ['maintenance_request_id', 'user_id'],
                'ma_req_user_uniq'
            );

            // index สำหรับงานของช่าง
            $table->index(
                ['user_id', 'status'],
                'ma_user_status_idx'
            );

            $table->index(
                ['maintenance_request_id', 'status'],
                'ma_req_status_idx'
            );

            // index สำหรับ MyJob (response)
            $table->index(
                ['user_id', 'response_status'],
                'ma_user_resp_idx'
            );

            $table->index(
                ['maintenance_request_id', 'response_status'],
                'ma_req_resp_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_assignments');
    }
};
