<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disciplines')) {
            return;
        }
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owning_course_id')->nullable();

            $table->foreign('owning_course_id')->references('id')->on('courses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplines');
    }
};
