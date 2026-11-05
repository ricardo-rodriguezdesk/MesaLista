<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->foreignId('mesero_id')->index()->constrained('usuarios')->restrictOnDelete();
            $table->unsignedSmallInteger('comensales')->default(1);
            $table->enum('estado', ['abierta', 'cuenta_pedida', 'cerrada', 'cancelada'])->default('abierta');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamp('abierta_en')->useCurrent();
            $table->timestamp('cerrada_en')->nullable();

            $table->index(['mesa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
