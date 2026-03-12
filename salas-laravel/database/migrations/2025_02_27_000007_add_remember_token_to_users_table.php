<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Para banco existente (vindo do PHP antigo): adiciona coluna que o Laravel Auth usa.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'remember_token')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken()->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropRememberToken();
            });
        }
    }
};
