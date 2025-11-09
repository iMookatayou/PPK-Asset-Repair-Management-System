<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Department short code เช่น IT, HR, MED');
            $table->string('name_th')->comment('ชื่อแผนกภาษาไทย');
            $table->string('name_en')->nullable()->comment('ชื่อแผนกภาษาอังกฤษ');
            $table->timestamps();

            $table->index(['name_th', 'name_en']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('departments');
    }
};
