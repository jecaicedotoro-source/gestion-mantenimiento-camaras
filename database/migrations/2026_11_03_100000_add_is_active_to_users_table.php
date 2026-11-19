<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // La migración conserva todas las cuentas existentes y sus relaciones.
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('auth_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'auth_version']);
        });
    }
};
