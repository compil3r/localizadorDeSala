<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('periods')) {
            return;
        }
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 32)->unique(); // ex: "2026/1"
            $table->string('slug', 32)->unique();  // ex: "2026-1" para URLs
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
        });
        DB::table('periods')->insert([
            'name' => '2026/1',
            'slug' => '2026-1',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
