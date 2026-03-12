<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            return;
        }
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 32);
            $table->unsignedBigInteger('coordinator_id')->nullable();

            $table->foreign('coordinator_id')->references('id')->on('coordinators')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
