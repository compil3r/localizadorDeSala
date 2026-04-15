<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fic_areas')) {
            Schema::create('fic_areas', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('kiosk_after_graduation')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (!Schema::hasTable('fic_courses')) {
            Schema::create('fic_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fic_area_id')->constrained('fic_areas')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (!Schema::hasTable('fic_sessions')) {
            Schema::create('fic_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fic_course_id')->constrained('fic_courses')->cascadeOnDelete();
                $table->string('label')->nullable();
                $table->date('session_date');
                $table->time('starts_at')->nullable();
                $table->time('ends_at')->nullable();
                $table->string('room')->nullable();
                $table->string('docente')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->index(['fic_course_id', 'session_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fic_sessions');
        Schema::dropIfExists('fic_courses');
        Schema::dropIfExists('fic_areas');
    }
};
