<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->index()->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('cajero_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('metodo', ['efectivo', 'tarjeta', 'transferencia']);

            $table->decimal('monto', 10, 2);
            $table->decimal('recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
