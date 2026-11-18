<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambios aditivos: los registros actuales conservan sus identificadores y valores.
        Schema::table('incidents', function (Blueprint $table): void {
            $table->string('technical_status', 20)->default('pendiente');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->index('technical_status');
        });

        Schema::table('maintenances', function (Blueprint $table): void {
            $table->foreignId('incident_id')->nullable()->constrained('incidents')->restrictOnDelete();
            $table->boolean('repair_resolved')->nullable();
            $table->index(['incident_id', 'maintenance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table): void {
            $table->dropIndex(['incident_id', 'maintenance_date']);
            $table->dropConstrainedForeignId('incident_id');
            $table->dropColumn('repair_resolved');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex(['technical_status']);
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['technical_status', 'resolved_at']);
        });
    }
};
