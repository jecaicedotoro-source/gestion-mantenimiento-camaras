<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('camera_id')
                ->constrained('cameras')
                ->restrictOnDelete();

            $table->date('maintenance_date');
            $table->string('type', 20);
            $table->text('description');
            $table->string('responsible', 150);
            $table->text('result');

            $table->timestamps();

            $table->index(['camera_id', 'maintenance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};