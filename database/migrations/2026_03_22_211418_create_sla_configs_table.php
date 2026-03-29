<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sla_configs', function (Blueprint $table) {
            $table->id();
            $table->string('priority_level')->unique()->comment('e.g., default, low, medium, high, urgent');
            $table->string('name')->comment('Display name e.g., Standard SLA, High Priority SLA');
            $table->integer('response_time_minutes')->default(120)->comment('Target time to acknowledge');
            $table->integer('resolution_time_minutes')->default(2880)->comment('Target time to resolve');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_configs');
    }
};
