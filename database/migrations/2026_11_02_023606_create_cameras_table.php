
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de inventario de cámaras.
     */
    public function up(): void
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();

            // Identificación del activo
            $table->string('codigo_inventario', 50)->unique();
            $table->string('numero_serie', 100)->nullable()->unique();

            // Características técnicas
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('tipo', 50);
            $table->string('direccion_ip', 45)->nullable();

            // Gestión institucional
            $table->string('ubicacion', 150);
            $table->string('estado', 30)->default('activo');
            $table->text('observaciones')->nullable();

            // Auditoría básica
            $table->timestamps();

            // Índices de consulta
            $table->index('ubicacion');
            $table->index('estado');
        });
    }

    /**
     * Revierte únicamente esta migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
