
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear la tabla de roles institucionales.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('name', 60)->unique();
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Revertir la creación de la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
