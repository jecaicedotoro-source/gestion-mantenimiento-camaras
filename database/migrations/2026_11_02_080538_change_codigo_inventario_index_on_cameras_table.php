<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropUnique(
                'cameras_codigo_inventario_unique'
            );

            $table->index('codigo_inventario');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropIndex(
                'cameras_codigo_inventario_index'
            );

            $table->unique('codigo_inventario');
        });
    }
};
