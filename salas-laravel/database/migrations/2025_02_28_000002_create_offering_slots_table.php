<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offering_slots')) {
            return;
        }
        Schema::create('offering_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            // unsignedInteger: compatibilidade com MySQL onde id de disciplines/teachers pode ser int
            $table->unsignedInteger('discipline_id');
            $table->unsignedInteger('teacher_id');
            $table->enum('turno', ['MANHA', 'NOITE']);
            $table->enum('dia_semana', ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB']);
            $table->string('room', 32)->nullable();
            $table->string('observation', 255)->nullable();

            $table->foreign('period_id')->references('id')->on('periods')->onDelete('cascade');
            $table->foreign('discipline_id')->references('id')->on('disciplines')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('restrict');

            $table->unique(['period_id', 'discipline_id', 'teacher_id', 'turno', 'dia_semana'], 'offering_slots_unique_turma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offering_slots');
    }
};
