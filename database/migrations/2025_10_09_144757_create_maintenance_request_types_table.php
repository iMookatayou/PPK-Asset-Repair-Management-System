<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_request_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150)->unique();
            $table->text('description')->nullable();

            $table->string('default_department_code', 100)->nullable()->index();
            $table->string('default_role_code', 50)->nullable()->index();

            $table->foreignId('default_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_types');
    }
};
