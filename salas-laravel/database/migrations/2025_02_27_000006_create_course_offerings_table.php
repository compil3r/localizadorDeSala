<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_offerings')) {
            return;
        }
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('discipline_id');
            $table->unsignedBigInteger('teacher_id');
            $table->enum('turno', ['MANHA', 'NOITE']);
            $table->enum('dia_semana', ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB']);
            $table->string('room', 32)->nullable();
            $table->string('observation', 255)->nullable();
            $table->enum('origin_type', ['PROPRIA', 'OPTATIVA', 'COMPARTILHADA'])->default('PROPRIA');

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('restrict');

            $table->index(['course_id', 'turno', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
    }
};
