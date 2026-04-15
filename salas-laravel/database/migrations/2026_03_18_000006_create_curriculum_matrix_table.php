<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curriculum_matrix')) {
            return;
        }

        Schema::create('curriculum_matrix', function (Blueprint $table) {
            $table->id();

            // `courses.id` no banco atual é INT UNSIGNED (não bigint).
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('course_semester');
            // `disciplines.id` no banco atual é INT UNSIGNED (não bigint).
            $table->unsignedInteger('discipline_id');
            $table->boolean('is_optional')->default(false);

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('cascade');

            // Deduplica por item curricular (para import eficiente e consistente).
            $table->unique(['course_id', 'course_semester', 'discipline_id'], 'curriculum_matrix_unique_item');

            $table->index(['course_id', 'course_semester'], 'idx_curriculum_course_semester');
            $table->index(['discipline_id'], 'idx_curriculum_discipline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_matrix');
    }
};
