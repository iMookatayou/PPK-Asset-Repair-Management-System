<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   
            $table->string('slug')->unique();    
            $table->string('color', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_code', 100)->unique();
            $table->string('name');
            $table->string('type', 100)->nullable();
            $table->string('category', 100)->nullable();  
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('location')->nullable();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('asset_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('purchase_date')->nullable();
            $table->date('warranty_expire')->nullable();
            $table->enum('status', ['active','in_repair','disposed'])->default('active');

            $table->timestamps();

            $table->index('type');
            $table->index('category');
            $table->index('location');
            $table->index('department_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
