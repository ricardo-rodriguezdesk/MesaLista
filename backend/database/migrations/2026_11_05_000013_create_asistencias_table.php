<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->timestamp('entrada');
            $table->timestamp('salida')->nullable();
            $table->decimal('horas', 5, 2)->nullable();

            $table->index(['empleado_id', 'entrada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
