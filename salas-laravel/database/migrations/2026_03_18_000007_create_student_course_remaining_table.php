<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_course_remaining')) {
            return;
        }

        Schema::create('student_course_remaining', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('student_id');
            $table->unsignedInteger('course_semester');
            // `disciplines.id` no banco atual é INT UNSIGNED (não bigint).
            $table->unsignedInteger('discipline_id');

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('cascade');

            // Deduplica por (aluno, semestre, disciplina), evitando duplicidade em importação.
            $table->unique(['student_id', 'course_semester', 'discipline_id'], 'student_course_remaining_unique_item');

            $table->index(['student_id'], 'idx_remaining_student');
            $table->index(['course_semester', 'discipline_id'], 'idx_remaining_semester_discipline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_course_remaining');
    }
};
