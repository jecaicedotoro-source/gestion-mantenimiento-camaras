<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('camera_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('description');
            $table->string('confirmed_category', 30)->nullable();
            $table->string('review_status', 20)->default('pendiente');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedInteger('classification_attempts')->default(0);
            // La revisión permite adquirir primero un bloqueo de escritura también en SQLite.
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });

        Schema::create('incident_classifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('kind', 20);
            $table->string('status', 30);
            $table->string('suggested_category', 30)->nullable();
            $table->string('confirmed_category', 30)->nullable();
            $table->text('explanation')->nullable();
            $table->string('model', 150)->nullable();
            $table->string('failure_code', 40)->nullable();
            $table->char('input_hash', 64)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            // Relaciona la decisión con su propuesta sin duplicar ni borrar el historial.
            $table->foreignId('suggestion_id')->nullable()->constrained('incident_classifications')->restrictOnDelete();
            $table->timestamps();
            $table->index(['incident_id', 'kind', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_classifications');
        Schema::dropIfExists('incidents');
    }
};
