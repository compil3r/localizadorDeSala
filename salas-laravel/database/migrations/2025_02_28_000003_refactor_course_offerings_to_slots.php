<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renomeia course_offerings antiga e cria nova estrutura (só vínculo course_id + offering_slot_id + origin_type).
     * A migração de dados vem em 2025_02_28_000004.
     */
    public function up(): void
    {
        if (!Schema::hasTable('course_offerings')) {
            return;
        }
        Schema::rename('course_offerings', 'course_offerings_legacy');

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('course_id');
            $table->unsignedBigInteger('offering_slot_id'); // offering_slots.id é bigint
            $table->enum('origin_type', ['PROPRIA', 'OPTATIVA', 'COMPARTILHADA'])->default('PROPRIA');

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('offering_slot_id')->references('id')->on('offering_slots')->onDelete('cascade');
            $table->unique(['course_id', 'offering_slot_id'], 'course_offerings_course_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offerings');
        if (Schema::hasTable('course_offerings_legacy')) {
            Schema::rename('course_offerings_legacy', 'course_offerings');
        }
    }
};
