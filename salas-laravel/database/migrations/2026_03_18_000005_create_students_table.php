<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('matricula', 64)->unique();
            $table->string('name', 255);

            // `courses.id` no banco atual é INT UNSIGNED (não bigint), então o FK precisa ser compatível.
            $table->unsignedInteger('course_id');
            $table->enum('turno', ['MANHA', 'NOITE']);

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');

            $table->index(['course_id', 'turno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
